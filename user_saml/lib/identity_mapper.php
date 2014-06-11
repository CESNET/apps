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
 * This class is used for mapping multiple SAML identities onto a single OC
 * account of a user.
 */
class IdentityMapper {

	/**
         * Creates a new mapping from saml identity to OC user account.
         * @param string $samlUid
         * @param string $ocUid
         * @param string $samlEmail
	 * @param int $lastSeen last saml login timestamp
         */
	public static function createMapping($samlUid, $ocUid, $samlEmail='', $lastSeen='') {
		if ($lastSeen === '') { $lastSeen = time(); }
		//Avoid inserting same mapping twice
		if (!self::userExists($samlUid)) {
			\OCP\Util::writeLog('saml','Creating mapping: '
				.$samlUid.'('.$samlEmail.') -> '.$ocUid, \OCP\Util::INFO);
			$query = \OC_DB::prepare('INSERT INTO `*PREFIX*users_mapping` '
				.'(`saml_uid`,`oc_uid`,`saml_email`,`last_seen`) VALUES(?,?,?,?)');
			$result = $query->execute(array($samlUid, $ocUid, $samlEmail, $lastSeen));
			return $result ? true : false;
		} else {
			\OCP\Util::writeLog('saml','Mapping: '.$samlUid.'('.$samlEmail.')'
				.' -> '.$ocUid.' already exists', \OCP\Util::WARN);
		}
	}

	/**
         * Updates email mapping for a user.
         * @param string $samlUid
         * @param string $samlEmail
         */
	 public static function updateEmail($samlUid, $samlEmail) {
                $query = \OC_DB::prepare('UPDATE `*PREFIX*users_mapping` SET `saml_email`'
			.' = (?) WHERE saml_uid = LOWER(?)');
                $result = $query->execute(array($samlEmail, $samlUid));
                if (\OC_DB::isError($result)) {
                        \OC_Log::write('core', \OC_DB::getErrorMessage($result), \OC_Log::ERROR);
                }
        }

	/**
         * Updates last seen timestamp for the user
         * @param string $samlUid
         */
         public static function updateLastSeen($samlUid, $lastSeen='') {
		if ($lastSeen === '') { $lastSeen = time(); }
                $query = \OC_DB::prepare('UPDATE `*PREFIX*users_mapping` SET `last_seen`'
                        .' = (?) WHERE saml_uid = LOWER(?)');
                $result = $query->execute(array($lastSeen, $samlUid));
                if (\OC_DB::isError($result)) {
                        \OC_Log::write('core', \OC_DB::getErrorMessage($result), \OC_Log::ERROR);
                }
        }

	/**
         * Returns OC username for saml identity.
         * @param string $samlUid
         */
	public static function getOcUid($samlUid) {
		$query = \OC_DB::prepare('SELECT `oc_uid` FROM `*PREFIX*users_mapping`'
			.' WHERE LOWER(`saml_uid`) = LOWER(?)');
		$result = $query->execute(array($samlUid))->fetchAll();
		if (empty($result)) {
			return $samlUid;
		}
		$ouid = trim($result[0]['oc_uid'], ' ');
		if (!empty($ouid)) {
			return $ouid;
		}
		return $samlUid;
	}

	/**
         * Returns OC username by SAML email.
         * @param string $samlEmail
         */
	public static function getOcUidByEmail($samlEmail) {
		$query = \OC_DB::prepare('SELECT `oc_uid` FROM `*PREFIX*users_mapping`'
			.' WHERE LOWER(`saml_email`) = LOWER(?)');
		$result = $query->execute(array($samlEmail))->fetchAll();
		if (empty($result)) {
			/* Found nothing, try internal OC emails */
			$query = \OC_DB::prepare("SELECT `userid` FROM `*PREFIX*preferences`"
				." WHERE `configkey` = 'email' AND LOWER(`configvalue`) = LOWER(?)");
			$result = $query->execute(array($samlEmail))->fetchAll();
			if (empty($result)) {
				/* Nothing there too */
				return;
			} else {
				return trim($result[0]['userid'], ' ');
			}
		}
		return trim($result[0]['oc_uid'], ' ');
	}

	/**
         * Returns true if mapping from saml to oc identity exists.
         * @param string $samlUid
         */
	public static function userExists($samlUid) {
		$query = \OC_DB::prepare('SELECT COUNT(*) FROM `*PREFIX*users_mapping`'
			.' WHERE LOWER(`saml_uid`) = LOWER(?)');
		$result = $query->execute(array($samlUid));
		if (\OC_DB::isError($result)) {
			\OC_Log::write('core', \OC_DB::getErrorMessage($result), \OC_Log::ERROR);
			return false;
		}
		return $result->fetchOne() > 0;
	}
}
