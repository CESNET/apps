<?php

/**
 * ownCloud - mail_notifications
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

namespace OCA\Mail_Notifications;

class Notifier {
	
	/**
	 * Sends notification about account creatinon to
         * the user specified. You can fully customize contents
         * of the message here.
	 * @param $uid recipient's username
	 */
	public static function notifyUserCreation($uid) {
		if (!\OCP\User::userExists($uid)) {
			\OCP\Util::writeLog('notifications','Tried to send email to non-existent'
			.' user '.$uid, \OCP\Util::ERROR);
			return; 
		}
		$message = "An ownCloud account at Data Storage CESNET (%s) has been succesfully created for you.\r\n\r\n"
			. "Details about your account:\r\nUsername: %s\r\nData quota: %s\r\n\r\nIf you wish to use "
			. "synchronization client apps, please set your password here:\r\n%s\r\n\r\nYour account is"
			. " bound to Identity Provider used at first login. If you have identities at multiple IdP's,"
			. " always use your identity used at first login (%s) in order to access your data.\r\n"
			. "User manuals are available on the following urls:%s\r\n%s\r\n\r\nIf"
			. " you have questions or problems, feel free to contact us at du-support@cesnet.cz.";
		$domain = \OCP\Util::linkToAbsolute('index.php','');
		$quota = \OCP\Util::humanFileSize(\OC_Util::getUserQuota($uid));
		$psettings = \OCP\Util::linkToAbsolute('index.php', 'settings/personal');
		$manpage1 = 'https://du.cesnet.cz/wiki/doku.php/cs/navody/owncloud/';
		$manpage2 = \OC_Helper::linkToDocs('user-manual');
		self::sendEmail('account creation', $message, array($domain, $uid, $quota, $psettings,
			$uid, $manpage1, $manpage2), $uid);
	}

	/**
	 * Sends notification about password changed by user
	 * You can fully customize contents of the message here.
	 */
	public static function notifyPasswordChange() {
		$message = "You are receiving this e-mail because your ownCloud password for client apps has changed."
			. "\r\n\r\nIf you didn't change your password, please contact du-support@cesnet.cz";
		self::sendEmail('password change', $message);
	}

	private static function sendEmail($subject, $message, $args=array(), $user=null) {
		$from = \OCP\Util::getDefaultEmailAddress('noreply');
		if (!$user) {
			$user = \OCP\Config::getUserValue(
				\OCP\User::getUser(), 'settings', 'email', '');
			if ($user === '') {
				\OCP\Util::writeLog('notifications','Sending email failed,'
					.' no email address found for user '
					. \OCP\User::getUser(), \OCP\Util::INFO);
			}
		}
		\OCP\Util::writeLog('saml','Sending email to '.$user.' about '.$subject, \OCP\Util::INFO);
		$l = \OCP\Util::getL10N('mail_notifications');
		try {
			$defaults = new \OCP\Defaults();
			\OCP\Util::sendMail($user, '', $l->t('%s '.$subject,
				array($defaults->getName())), $l->t($message, $args),
				$from, $defaults->getName());
		} catch (Exception $e) {
			\OCP\Util::writeLog('notifications','Sending email to '
				.$user.' failed.', \OCP\Util::ERROR);
		}
	}
}
