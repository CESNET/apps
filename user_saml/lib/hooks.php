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
 * This class contains all implemented user backend hooks.
 */
class Hooks {

	static public function post_login($parameters) {
		$us = new \OC_USER_SAML();

		if (!$us->auth->isAuthenticated()) { return false; }

		$attrs = $us->auth->getAttributes();
		$uid = Util::getAttr($us->usernameMapping, $attrs);

		if ($uid !== '' && $uid === $parameters['uid']) {
			if (\OCP\User::userExists($uid)) {
				if ($us->updateUserData) {
					Util::updateUserData($uid, $us, $attrs);
				}
			} else {
				\OCP\Util::writeLog('saml','User '.$uid
					.' does not exist!', \OCP\Util::ERROR);
				return false;
			}
			if (!\OC_User::getDisplayName($uid)) {
				\OCP\Util::writeLog('saml','User '.$uid
					.' is missing displayName.', \OCP\Util::WARN);
			}
			return true;
		}
	}

	static public function post_createUser($parameters) {
		$uid = $parameters['uid'];
		$us = new \OC_USER_SAML();
		if ($us->auth->isAuthenticated()) {
			$attrs = $us->auth->getAttributes();
			if (!$us->updateUserData) {
				// Ensure that user data will be filled atleast once
				Util::updateUserData($uid, $us, $attrs);
			}
		}
		\OCP\Util::writeLog('saml','User '.$uid, \OCP\Util::INFO);
		if (\OCP\App::isEnabled('mail_notifications')) {
			require_once __DIR__ . '/../../mail_notifications/appinfo/app.php';
			\OCA\Mail_Notifications\Hooks::post_createUser($parameters);
		}
	}

	static public function post_deleteUser($parameters) {
		$uid = $parameters['uid'];
//		IdentityMapper::deleteMappings($uid);
	}

	static public function logout($parameters) {
                Util::destroySamlSession(new \OC_USER_SAML(),
			\OCP\Util::linkToAbsolute('','index.php',array('logout'=>'true')));
                return true;
        }
}
