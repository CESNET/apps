<?php

OCP\Util::addscript('user_saml', 'settings_personal');
OCP\JSON::checkLoggedIn();

if ($_POST) {
	// CSRF check
	OCP\JSON::callCheck();

	$username = \OCP\User::getUser();
	$password = isset($_POST['password']) ? $_POST['password'] : null;
	$passwordAgain = isset($_POST['confirm_password']) ?
		$_POST['confirm_password'] : '';
	if (!is_null($password)) {
		if (\OC_User::setPassword($username, $password)) {
			\OCP\JSON::success();
		} else {
			\OCP\JSON::error(array("data" =>array("message"	=>
				$l->t("Error occured. Please try again.")) ));
		}
	}
	exit();
}

// fill template
$tmpl = new OCP\Template( 'user_saml', 'settings_personal');
$tmpl->assign('user_name_login', \OCP\User::getUser());
return $tmpl->fetchPage();
