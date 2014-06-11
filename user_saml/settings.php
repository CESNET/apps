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

OCP\User::checkAdminUser();

$fields1 = array(
	'saml_ssp_path' => 'SimpleSAMLphp path',
	'saml_sp_source' => 'SimpleSAMLphp SP source',
	'saml_default_group' => 'Default group when autocreating users'
		.' and not group data found for the user',

);

$cboxes1 = array(
	'saml_force_saml_login' => 'Force SAML login?',
	'saml_autocreate' => 'Autocreate user after SAML login?',
	'saml_update_user_data' => 'Update user data after login?',
	'saml_custom_theme_login' => 'Theme with custom login page enabled?',
	'saml_filter_hostel_loa' => 'Restrict Hostel accounts to LoA > 1?',
	'saml_filter_library_readers' => 'Restrict access from specific'
		.' IdPs to employees only?'
);

$arrays1 = array(
	'saml_filtered_scopes' => 'For this scopes, allow access to employees only',
	'saml_protected_groups' => 'Groups that will not be unlinked from the'
		.' user when sync the IdP and the owncloud',
);

$fields2 = array();
$cboxes2 = array();
$arrays2 = array(
	'saml_username_mapping' => 'Username',
	'saml_email_mapping' => 'E-mail',
	'saml_displayname_mapping' => 'Display Name',
	'saml_group_mapping' => 'Group',
	'saml_hostel_loa' => 'Hostel LoA',
	'saml_affiliation_mapping' => 'Affiliation'
);

$all = array_merge($fields1,$fields2,$arrays1,$arrays2,$cboxes1,$cboxes2);
$cbs = array_merge($cboxes1,$cboxes2);

OCP\Util::addscript('user_saml', 'settings');

if ($_POST) {
	OCP\JSON::callCheck();
	foreach($all as $param => $desc) {
		if (isset($_POST[$param])) {
			OCP\Config::setAppValue('user_saml', $param, $_POST[$param]);
		}  
		elseif (isset($cbs[$param])) {
			// unchecked checkboxes are not included in the post paramters
			OCP\Config::setAppValue('user_saml', $param, 0);
		}
	}
}
$tbs1 = array_merge($fields1,$arrays1);
$tbs2 = array_merge($fields2,$arrays2);
// fill template
$tmpl = new OCP\Template( 'user_saml', 'settings');
$tmpl->assign('tboxes1', $tbs1);
$tmpl->assign('cboxes1', $cboxes1);
$tmpl->assign('tboxes2', $tbs2);
$tmpl->assign('cboxes2', $cboxes2);

// settings with default values
foreach (array_merge($tbs1, $tbs2) as $fld => $desc) {
$tmpl->assign($fld, htmlentities(OCP\Config::getAppValue('user_saml', $fld, '')));
}
foreach ($cbs as $fld => $desc) {
$tmpl->assign($fld, htmlentities(OCP\Config::getAppValue('user_saml', $fld, 0)));
}
$tmpl->assign('saml_ssp_path',
	OCP\Config::getAppValue('user_saml',
		'saml_ssp_path', '/var/www/sp/simplesamlphp'));
$tmpl->assign('saml_sp_source',
	OCP\Config::getAppValue('user_saml', 'saml_sp_source', 'default-sp'));
$tmpl->assign('saml_username_mapping',
	OCP\Config::getAppValue('user_saml', 'saml_username_mapping', 'uid'));
$tmpl->assign('saml_email_mapping',
	OCP\Config::getAppValue('user_saml', 'saml_email_mapping', 'mail'));
$tmpl->assign('saml_displayname_mapping',
	OCP\Config::getAppValue('user_saml',
		'saml_displayname_mapping', 'displayName'));
return $tmpl->fetchPage();
