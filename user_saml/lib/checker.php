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
 * This class provides methods for checking the required user attributes.
 */
class Checker {

	/**
         * Checks, if the user is an employee.
         * @param string $uid username
         * @param array $attributes user's saml attributes
         * @param array $affiliationMapping field name in attributes for affiliation info
         */
	public static function checkEmployee($uid, $attributes, $affiliationMapping) {
		$affiliations = Util::getAttr($affiliationMapping, $attributes, true);
		if (!in_array('employee@'.explode('@', $uid)[1], $affiliations, true) === TRUE) {
				\OCP\Util::writeLog('user_saml','Blocked UID:'.$uid
					.', he seems to be library reader only', \OCP\Util::WARN);
				$message = 'You must be a library employee in order to access '
					.'this service. Library readers don\'t meet the access '
					.'criteria declared in Terms of Service '
					.'<https://du.cesnet.cz/wiki/doku.php/en/provozni_pravidla>.';
				self::print403($message);
		}
	}


	/**
         * Checks, if the user's LoA is greater than 1.
         * @param string $uid username
	 * @param array $attributes user's saml attributes
	 * @param array $hostelMapping field name in attributes for hostelLoa
         */
	public static function checkHostelLoa($uid, $attributes, $hostelMapping) {
		$pattern = '/@.*hostel.*$/';
		$loa = Util::getAttr($hostelMapping, $attributes);
		preg_match($pattern, $uid, $matches);
		if (!empty($matches) && $loa < 2) {
			\OCP\Util::writeLog('hostel','Blocked UID:'.$uid
				.' with LOA:'.$loa, \OCP\Util::WARN);
			$message = 'Your Hostel account has insufficient level'
				.' of verification for this service. For further info,'
				.' please refer to: http://hostel.eduid.cz/en/overeni_identity.html';
			self::print403($message);
		}
	}
	
	/**
         * Prints a 403 Forbidden page when check fails.
         * @param string $message to display to user
         */
	private static function print403($message) {
		$l = \OCP\Util::getL10N('user_saml');
		header('HTTP/1.0 403 Forbidden');
		$tmpl = new \OCP\Template('core', '403', 'guest');
		\OCP\Util::addScript('user_saml', '403');
		$tmpl->assign('file',$l->t($message));
		$tmpl->printPage();
		Util::destroySamlSession(new \OC_USER_SAML());
		exit();
        }
}
