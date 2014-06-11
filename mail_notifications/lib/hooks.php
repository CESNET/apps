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
/**
 * This class contains all implemented action hooks.
 */
class Hooks {

	static public function post_createUser($parameters) {
                $uid = $parameters['uid'];
		Notifier::notifyUserCreation($uid);
        }

        static public function post_setPassword($parameters) {
		Notifier::notifyPasswordChange();
        }
}
