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

if (OCP\App::isEnabled('user_saml')) {
	$ocVersion = implode('.',OCP\Util::getVersion());
	if (version_compare($ocVersion,'5.0','<')) {
		if ( ! function_exists('p')) {
			function p($string) {
				print(OCP\Util::sanitizeHTML($string));
			}
		}
	}

	require_once OC_App::getAppPath('user_saml').'/user_saml.php';

	OC_User::useBackend('SAML');

	OCP\App::registerAdmin('user_saml', 'settings');
	OCP\App::registerPersonal('user_saml', 'settings_personal');

	OC::$CLASSPATH['OCA\User_Saml\Hooks'] = 'user_saml/lib/hooks.php';
	OC::$CLASSPATH['OCA\User_Saml\Util'] = 'user_saml/lib/util.php';
	OC::$CLASSPATH['OCA\User_Saml\Checker'] = 'user_saml/lib/checker.php';
	OC::$CLASSPATH['OCA\User_Saml\IdentityMapper'] = 'user_saml/lib/identity_mapper.php';

	OCP\Util::connectHook('OC_User', 'post_login',
			      'OCA\User_Saml\Hooks', 'post_login');
	OCP\Util::connectHook('OC_User', 'logout',
			      'OCA\User_Saml\Hooks', 'logout');
	OCP\Util::connectHook('OC_User', 'post_deleteUser',
			      'OCA\User_Saml\Hooks', 'post_deleteUser');
	OCP\Util::connectHook('OC_User', 'post_createUser',
			      'OCA\User_Saml\Hooks', 'post_createUser');

	$forceLogin = OCP\Config::getAppValue('user_saml',
		'saml_force_saml_login', false);
	$themedLogin = OCP\Config::getAppValue('user_saml',
		'saml_custom_theme_login', false);
	
	if (!OCP\User::isLoggedIn()) {
		/** 
		 * Load js code in order to render the SAML link and
		 * to hide parts of the normal login form. If themed
		 * login is enabled, we assume that login page with
		 * link is rendered by theme.
		 */
		if (!$themedLogin) {
			OCP\Util::addScript('user_saml', 'utils');
		}
	}

	if(   (isset($_GET['app']) && $_GET['app'] == 'user_saml')
	   || (!OCP\User::isLoggedIn()
	   && $forceLogin
	   && !isset($_GET['admin_login']) )) {

		if (!OC_User::login('', '')) {
			\OCP\Util::writeLog('saml', 'Error trying to '
				.'authenticate the user', OCP\Util::DEBUG);
		}
		
		if (isset($_GET["linktoapp"])) {
			$path = OC::$WEBROOT . '/?app='.$_GET["linktoapp"];
            		if (isset($_GET["linktoargs"])) {
				$path .= '&'.urldecode($_GET["linktoargs"]);
			}
			header( 'Location: ' . $path);
			exit();
		}

		OC::$REQUESTEDAPP = '';
		OC_Util::redirectToDefaultPage();
	}
}
