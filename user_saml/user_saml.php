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

class OC_USER_SAML extends \OC_User_Backend {

	// cached settings
	protected $sspPath;
	protected $spSource;
	public $forceLogin;
	public $autocreate;
	public $updateUserData;
	public $filterLibraryReaders;
	public $filterHostelLoa;
	public $protectedGroups;
	public $defaultGroup;
	public $usernameMapping;
	public $mailMapping;
	public $displayNameMapping;
	public $groupMapping;
	public $hostelLoaMapping;
	public $affiliationMapping;
	public $filteredScopes;
	public $auth;

	public function __construct() {
		$this->sspPath =
			\OCP\Config::getAppValue('user_saml', 'saml_ssp_path', '');
		$this->spSource =
			\OCP\Config::getAppValue('user_saml', 'saml_sp_source', '');
		$this->forceLogin =
			\OCP\Config::getAppValue('user_saml', 'saml_force_saml_login', false);
		$this->autocreate =
			\OCP\Config::getAppValue('user_saml', 'saml_autocreate', false);
		$this->updateUserData =
			\OCP\Config::getAppValue('user_saml', 'saml_update_user_data', false);
		$this->filterLibraryReaders = 
			\OCP\Config::getAppValue('user_saml', 'saml_filter_library_readers', false);
		$this->filterHostelLoa =
			\OCP\Config::getAppValue('user_saml', 'saml_filter_hostel_loa', false);
		$this->defaultGroup =
			\OCP\Config::getAppValue('user_saml', 'saml_default_group', '');
		$this->protectedGroups = explode (',', preg_replace('/\s+/', '',
			\OCP\Config::getAppValue('user_saml', 'saml_protected_groups', '')));
		$this->usernameMapping = explode (',', preg_replace('/\s+/', '',
			\OCP\Config::getAppValue('user_saml', 'saml_username_mapping', '')));
		$this->mailMapping = explode (',', preg_replace('/\s+/', '',
			\OCP\Config::getAppValue('user_saml', 'saml_email_mapping', '')));
		$this->displayNameMapping = explode (',', preg_replace('/\s+/', '',
			\OCP\Config::getAppValue('user_saml', 'saml_displayname_mapping', '')));
		$this->groupMapping = explode (',', preg_replace('/\s+/', '',
			\OCP\Config::getAppValue('user_saml', 'saml_group_mapping', '')));
		$this->hostelLoaMapping = explode(',', preg_replace('/\s+/', '',
			\OCP\Config::getAppValue('user_saml', 'saml_hostel_loa', '')));
                $this->affiliationMapping = explode(',', preg_replace('/\s+/', '',
			\OCP\Config::getAppValue('user_saml', 'saml_affiliation_mapping', '')));
		$this->filteredScopes = explode (',', preg_replace('/\s+/', '',
			\OCP\Config::getAppValue('user_saml', 'saml_filtered_scopes', '')));
		if (!empty($this->sspPath) && !empty($this->spSource)) {
			include_once $this->sspPath."/lib/_autoload.php";

			if (!isset($this->filteredScopes) || $this->filteredScopes[0] === '') {
				$idpMetadata =
					\SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler()
					->getList();
				$filterScopes = OCA\User_Saml\Util::getFilteredScopes($idpMetadata);
				OCP\Config::setAppValue('user_saml', 'saml_filtered_scopes', $filterScopes);
				$this->filteredScopes = explode(',',preg_replace('/\s+/','',$filterScopes));
			}

			$this->auth = new SimpleSAML_Auth_Simple($this->spSource);

			if (   isset($_COOKIE["user_saml_logged_in"])
			    && $_COOKIE["user_saml_logged_in"]
			    && !$this->auth->isAuthenticated()) {

				unset($_COOKIE["user_saml_logged_in"]);
				setcookie("user_saml_logged_in", null, -1);
				\OCP\User::logout();
			}
		} else {
			\OCP\Util::writeLog('saml','Please specify a path to '
				.'your simplesamlphp installation.', \OCP\Util::DEBUG);
		}
	}


	public function checkPassword($uid='', $password='') {

		if(!$this->auth->isAuthenticated()) {
			\OCP\Util::writeLog('saml','User not authenticated!', \OCP\Util::INFO);
			return false;
		}
		$attributes = $this->auth->getAttributes();
		$uid = \OCA\User_Saml\Util::getAttr($this->usernameMapping, $attributes);
		\OCP\Util::writeLog('saml','Authenticated user '.$uid, \OCP\Util::INFO);

		if ($uid && $uid !== '') {
			if ($this->filterLibraryReaders) {
				if (in_array(explode('@', $uid)[1],
				    $this->filteredScopes, true) === TRUE) {
					\OCA\User_Saml\Checker::checkEmployee(
						$uid, $attributes, $this->affiliationMapping);
				}
			}
			if ($this->filterHostelLoa) {
				\OCA\User_Saml\Checker::checkHostelLoa(
					$uid, $attributes, $this->hostelLoaMapping);
			}
			if (\OCA\User_Saml\IdentityMapper::userExists($uid)) {
				$euid = \OCA\User_Saml\IdentityMapper::getOcUid($uid);
			} else {
				\OCA\User_Saml\IdentityMapper::createMapping($uid, $uid);
				$euid = $uid;
			}

			if(!\OCP\User::userExists($euid)) {
			//      $this->checkConsolidator($uid, $attributes);
				if ($this->autocreate) {
					return $this->createUser($euid);
				}
			}
			return $euid;
		} else {
			$secure_cookie = \OCP\Config::getSystemValue("forcessl", false);
			$expires = time() + \OCP\Config::getSystemValue(
				'remember_login_cookie_lifetime', 60*60*24*15);
			setcookie("user_saml_logged_in", "1", $expires, '', '', $secure_cookie);
			\OCP\Util::writeLog('saml','Not found attribute used to get the username at'
				.' the requested saml attribute assertion',\OCP\Util::ERROR);
			return false;
		}
	}
	
	private function createUser($uid) {
		if (preg_match( '/[^a-zA-Z0-9 _\.@\-]/', $uid)) {
			\OCP\Util::writeLog('saml','Invalid username "'.$uid
				.'", allowed chars "a-zA-Z0-9" and "_.@-" ', \OCP\Util::ERROR);
			return false;
		} else {
			\OCP\Util::writeLog('saml','Creating new user: '.$uid, \OCP\Util::INFO);
			\OC_User::createUser($uid, \OC_Util::generateRandomBytes(64));
			return $uid;
		}	
	}
	
	public function hasUserListings() {
		return true;
	}

	public function getDisplayNames($search = '', $limit = null, $offset = null) {
		/* If search is an existing user uid, return nothing and rely
		 * on Database backend for providing DisplayName for uid. */
		if (\OCP\User::userExists($search)) { return array(); }

		/* Look for the user in SAML identity mapping table */
		if (\OCA\User_Saml\IdentityMapper::userExists($search)) {
			$ouid = \OCA\User_Saml\IdentityMapper::getOcUid($search);
			$dn = \OCP\User::getDisplayName($ouid);
			\OCP\Util::writeLog('saml','Searching user: '.$dn, \OCP\Util::INFO);
			return array($ouid => $dn);
		}
		/* Then look for the user by his OC email */
		$ouid = \OCA\User_Saml\IdentityMapper::getOcUidByEmail($search);
		if ($ouid) {
			$dn = \OCP\User::getDisplayName($ouid);
			if (strcmp($dn, $ouid) !== 0) {
				return array($ouid => $dn);
			} else {
				return array();
			}
		}
	}
}
