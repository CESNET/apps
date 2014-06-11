<?php
/**
 * ownCloud - user_saml
 *
 * @author Sixto Martin <smartin@yaco.es>
 * @copyright 2012 Yaco Sistemas // CONFIA
 *
 * @author Miroslav Bauer <bauer@cesnet.cz>
 * @copyright 2014 CESNET
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\User_Saml;
/**
 * Class for utility functions mostly for saml metadata processing.
 */
class Util {

	 /**
         * Returns a comma separated list of scopes for which only
	 * employee users should be granted access.
         * @param array $idpMetadata metadata of all configured idps
         */
	public static function getFilteredScopes($idpMetadata) {
		$result = array_filter($idpMetadata, function($idp){
			$name = $idp['name']['en'];
			if (strpos($name, 'Library') !== FALSE) {
				return true;
			} else {
				if (strpos($name, 'library') != FALSE) {
					return true;
				} else {
					return false;
				}
			}
		});
		return join(',', array_map(function($lib) {
			return $lib['scope'][0];
		},$result));
	}

	/**
         * Returns an attribute value, selected by keys from mapping.
         * @param array $mapping keys for values to be returned from $attributes
	 * @param array $attributes array with saml metadata of user
         * @param boolean $retAttr if string or array should be returned
         * @return string or array depending on $retArr
         */	
	public static function getAttr($mapping, $attributes, $retArr=false) {
		if ($retArr) { $result = array(); }
		foreach($mapping as $m) {
			if (   array_key_exists($m, $attributes)
			    && !empty($attributes[$m][0])) {
	
				if ($retArr) {
					array_merge($result, $attributes[$m]);
				} else {
					return $attributes[$m][0];
				}
			}
		}
		if ($retArr) { return $result; }
		return '';
	}

	
	/**
	 * Fetches information about a user from it's metadata.
	 * @param string $uid user's username
	 * @param UserSaml $us an instance of the UserSaml backend
	 * @param array $attributes array with saml metadata of user
	 * @return array with email, display name, groups and protected groups
	 */
	private static function getUserAttributes($uid, $us, $attributes) {
		$result = array();
		$result['email'] = self::getAttr($us->mailMapping, $attributes);
		$result['displayName'] = self::getAttr($us->displayNameMapping, $attributes);
		$result['groups'] = self::getAttr($us->groupMapping, $attributes, true);
		if (empty($saml_groups) && !empty($us->defaultGroup)) {
			$saml_groups = array($us->defaultGroup);
			\OCP\Util::writeLog('saml','Using default group "'
				.$us->defaultGroup.'" for the user: '
				.$uid, \OCP\Util::DEBUG);
		}
		$result['protectedGroups'] = $us->protectedGroups;
		return $result;
	}

	/**
         * Updates internal settings for a user from the given saml attributes.
         * @param string $uid user's username
         * @param UserSaml $us an instance of the UserSaml backend
	 * @param array $attributes array with saml metadata of user
         * @param boolean $justCreated tells if the user if new
         */
	public static function updateUserData($uid, $us, $attributes, $justCreated=false) {
		if (!$us->auth->isAuthenticated()) { return; }
		$attrs = self::getUserAttributes($uid, $us, $attributes);
        	\OC_Util::setupFS($uid);
        	\OCP\Util::writeLog('saml','Updating data for the user: '.$uid, \OCP\Util::DEBUG);
		$suid    = self::getAttr($us->usernameMapping, $attributes);
		$dn      = $attrs['displayName'];
		$email   = $attrs['email'];
		$groups  = $attrs['groups'];
		$pgroups = $attrs['protectedGroups'];

		if(isset($email) && $email !== '') {
			self::updateMail($uid, $suid, $email);
		}
		if (isset($groups)) {
			self::updateGroups($uid, $groups, $pgroups, $justCreated);
		}
		if (isset($dn) && $dn !== '') {
			self::updateDisplayName($uid, $dn);
		}
		IdentityMapper::updateLastSeen($suid);
	}

	/**
         * Updates email in OC preferences table for a user to value given
         * @param string $uid user's username
         * @param string $suid user's saml identity
         * @param string $email user's email
         */
	private static function updateMail($uid, $suid, $email) {
		if ($email !== \OCP\Config::getUserValue(
			$uid, 'settings', 'email', '')) {
			if ($uid === $suid) {
				\OCP\Config::setUserValue(
					$uid, 'settings', 'email', $email);
				\OCP\Util::writeLog('saml','Set email "'.$email
					.'" for the user: '.$uid, \OCP\Util::DEBUG);
			}
			IdentityMapper::updateEmail($suid, $email);
		}
	}

	/**
         * Updates user's groups membership. 
         * @param string $uid user's username
         * @param array $groups that user should be member of
         * @param array $pgroups (protected) that user should be member of
         * @param boolean $justCreated is true if user is new
         */
	private static function updateGroups($uid, $groups, $pgroups, $justCreated=false) {
		if(!$justCreated) {
			$oldGroups = \OC_Group::getUserGroups($uid);
			foreach($oldGroups as $group) {
				if(   !in_array($group, $pgroups)
				   && !in_array($group, $groups)) {
					\OC_Group::removeFromGroup($uid,$group);
					\OCP\Util::writeLog('saml','Removed "'.$uid
						.'" from the group "'.$group.'"',
						\OCP\Util::WARN);
				}
			}
		}
		foreach($groups as $group) {
			if (preg_match( '/[^a-zA-Z0-9 _\.@\-]/', $group)) {
				\OCP\Util::writeLog('saml','Invalid group "'.$group
					.'", allowed chars "a-zA-Z0-9" and "_.@-" ',
					\OCP\Util::ERROR);
			}
			else {
				if (!\OC_Group::inGroup($uid, $group)) {
					if (!\OC_Group::groupExists($group)) {
						\OC_Group::createGroup($group);
						\OCP\Util::writeLog('saml',
							'New group created: '
							.$group, \OCP\Util::DEBUG);
					}
					# Do not automatically add to admin group
					if ($group === 'admin') { continue; }
					\OC_Group::addToGroup($uid, $group);
					\OCP\Util::writeLog('saml','Added "'.$uid
						.'" to the group "'.$group
						.'"', \OCP\Util::DEBUG);
				}
			}
		}
	}

	/**
	 * Sets a display name for the user
         * @param string $uid user's username
         * @param string $dn user's displayname to be set
	 */
	private static function updateDisplayName($uid, $dn) {
		\OC_User::setDisplayName($uid, $dn);
	}

	/**
         * Destroys a saml session cookie, logs the user out
	 * and redirects to location, if specified.
         * @param UserSaml $us an instance of the UserSaml backend
         * @param string $returnTo redirect to this location after logout
         */
	public static function destroySamlSession($us, $returnTo=null) {
		if ($us->auth->isAuthenticated()) {
			\OCP\Util::writeLog('saml', 'Executing SAML logout', \OCP\Util::INFO);
			setcookie('SimpleSAMLAuthToken', '', time()-3600, \OC::$WEBROOT);
			setcookie('SimpleSAMLAuthToken', '', time()-3600, \OC::$WEBROOT . '/');
			if(isset($returnTo)) {
				$us->auth->logout($returnTo);
			}
		}
	}
}
