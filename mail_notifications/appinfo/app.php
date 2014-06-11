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

require_once OC_App::getAppPath('mail_notifications').'/mail_notifications.php';

OC::$CLASSPATH['OCA\Mail_Notifications\Hooks'] = 'mail_notifications/lib/hooks.php';

# Connect hooks for which to send notifications
OCP\Util::connectHook('OC_User', 'post_createUser',
	'OCA\Mail_Notifications\Hooks', 'post_createUser');
OCP\Util::connectHook('OC_User', 'post_setPassword',
	'OCA\Mail_Notifications\Hooks', 'post_setPassword');
