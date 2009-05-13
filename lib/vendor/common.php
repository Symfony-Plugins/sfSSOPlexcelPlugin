<?php
// common.php - common routines used by setup.php and examples

require_once('plexcel.php');

define('PFF_NOINPUT',         0x000000001);
define('PFF_TIME',            0x000000002);
define('PFF_READONLY',        0x000000004);
define('PFF_WIDE',            0x000000008);
define('PFF_SPLIT_ROW',       0x000000010);
define('PFF_SPLIT_WIDE',      0x000000020);
define('PFF_TEXTAREA',        0x000000040);
define('PFF_LEFT_HALF',       0x000000080);
define('PFF_RIGHT_HALF',      0x000000100);
define('PFF_SELECT',          0x000000200);

function htmlesc($data) {
	return htmlentities($data, ENT_COMPAT, 'UTF-8');
}

class PlexcelFieldFormatter {

	var $labels;
	var $uaclabels;
	var $uacnames;
	var $tr;

	function PlexcelFieldFormatter() {
		$this->labels = array(
			'name' => 'Name',
			'cn' => 'CN',
			'sAMAccountName' => 'User logon name (pre-Windows 2000)',
			'userPrincipalName' => 'User logon name',
			'distinguishedName' => 'DN',
			'dn' => 'DN',
			'givenName' => 'First name',
			'sn' => 'Last name',
			'servicePrincipalName' => 'SPN',
			'displayName' => 'Display name',
			'homeDirectory' => 'Home folder',
			'description' => 'Description',
			'scriptPath' => 'Logon script',
			'userParameters' => 'userParameters',
			'userWorkstations' => 'Workstations',
			'lastLogon' => 'Last logon',
			'lastLogoff' => 'Last logoff',
			'accountExpires' => 'Account expires',
			'logonHours' => 'Logon Hours',
			'badPwdCount' => 'Bad password count',
			'countryCode' => 'Country code',
			'codePage' => 'Codepage',
			'primaryGroupID' => 'Primary group ID',
			'profilePath' => 'Profile path',
			'homeDrive' => 'Home drive',
			'objectSid' => 'SID',
			'instanceType' => 'instanceType',
			'whenCreated' => 'Create time',
			'whenChanged' => 'Change time',
			'uSNCreated' => 'uSNCreated',
			'uSNChanged' => 'uSNChanged',
			'objectGUID' => 'objectGUID',
			'badPasswordTime' => 'Bad password time',
			'pwdLastSet' => 'Password last set',
			'adminCount' => 'adminCount',
			'logonCount' => 'Logon count',
			'sAMAccountType' => 'sAMAccountType',
			'objectCategory' => 'objectCategory',
			'memberOf' => 'Groups',
			'initials' => 'Initials',
			'physicalDeliveryOfficeName' => 'Office',
			'telephoneNumber' => 'Telephone number',
			'mail' => 'E-mail',
			'wWWHomePage' => 'Web page',
			'streetAddress' => 'Street',
			'postOfficeBox' => 'P.O. Box',
			'l' => 'City',
			'st' => 'State/province',
			'postalCode' => 'Zip/Postal Code',
			'co' => 'Country/region',
			'c' => 'Country/region',
			'homePhone' => 'Home',
			'pager' => 'Pager',
			'mobile' => 'Mobile',
			'facsimileTelephoneNumber' => 'Fax',
			'ipPhone' => 'IP phone',
			'info' => 'Notes',
			'title' => 'Title',
			'department' => 'Department',
			'company' => 'Company',
			'manager' => 'Manager',
		);
		$this->uaclabels = array(
			PLEXCEL_ACCOUNTDISABLE => 'Account is disabled',
			PLEXCEL_LOCKOUT => 'Account is locked out',
			PLEXCEL_PASSWD_NOTREQD => 'Password not required',
			PLEXCEL_PASSWD_CANT_CHANGE => 'User cannot change password',
			PLEXCEL_DONT_EXPIRE_PASSWORD => 'Password never expires',
			PLEXCEL_SMARTCARD_REQUIRED => 'Smart card is required for interactive logon',
			PLEXCEL_TRUSTED_FOR_DELEGATION => 'Account is trusted for delegation',
			PLEXCEL_NOT_DELEGATED => 'Account is sensitive and cannot be delegated',
			PLEXCEL_USE_DES_KEY_ONLY => 'Use DES encryption types for this account',
			PLEXCEL_DONT_REQ_PREAUTH => 'Do not require Kerberos preauthentication',
			PLEXCEL_PASSWORD_EXPIRED => 'Password is expired',
			PLEXCEL_NO_AUTH_DATA_REQUIRED => 'Do not include PAC information in Kerberos tickets',
		);
		$this->uacnames = array(
			PLEXCEL_SCRIPT => 'PLEXCEL_SCRIPT',
			PLEXCEL_ACCOUNTDISABLE => 'PLEXCEL_ACCOUNTDISABLE',
			PLEXCEL_HOMEDIR_REQUIRED => 'PLEXCEL_HOMEDIR_REQUIRED',
			PLEXCEL_LOCKOUT => 'PLEXCEL_LOCKOUT',
			PLEXCEL_PASSWD_NOTREQD => 'PLEXCEL_PASSWD_NOTREQD',
			PLEXCEL_PASSWD_CANT_CHANGE => 'PLEXCEL_PASSWD_CANT_CHANGE',
			PLEXCEL_ENCRYPTED_TEXT_PWD_ALLOWED => 'PLEXCEL_ENCRYPTED_TEXT_PWD_ALLOWED',
			PLEXCEL_TEMP_DUPLICATE_ACCOUNT => 'PLEXCEL_TEMP_DUPLICATE_ACCOUNT',
			PLEXCEL_NORMAL_ACCOUNT => 'PLEXCEL_NORMAL_ACCOUNT',
			PLEXCEL_INTERDOMAIN_TRUST_ACCOUNT => 'PLEXCEL_INTERDOMAIN_TRUST_ACCOUNT',
			PLEXCEL_WORKSTATION_TRUST_ACCOUNT => 'PLEXCEL_WORKSTATION_TRUST_ACCOUNT',
			PLEXCEL_SERVER_TRUST_ACCOUNT => 'PLEXCEL_SERVER_TRUST_ACCOUNT',
			PLEXCEL_DONT_EXPIRE_PASSWORD => 'PLEXCEL_DONT_EXPIRE_PASSWORD',
			PLEXCEL_MNS_LOGON_ACCOUNT => 'PLEXCEL_MNS_LOGON_ACCOUNT',
			PLEXCEL_SMARTCARD_REQUIRED => 'PLEXCEL_SMARTCARD_REQUIRED',
			PLEXCEL_TRUSTED_FOR_DELEGATION => 'PLEXCEL_TRUSTED_FOR_DELEGATION',
			PLEXCEL_NOT_DELEGATED => 'PLEXCEL_NOT_DELEGATED',
			PLEXCEL_USE_DES_KEY_ONLY => 'PLEXCEL_USE_DES_KEY_ONLY',
			PLEXCEL_DONT_REQ_PREAUTH => 'PLEXCEL_DONT_REQ_PREAUTH',
			PLEXCEL_PASSWORD_EXPIRED => 'PLEXCEL_PASSWORD_EXPIRED',
			PLEXCEL_TRUSTED_TO_AUTH_FOR_DELEGATION => 'PLEXCEL_TRUSTED_TO_AUTH_FOR_DELEGATION',
			PLEXCEL_NO_AUTH_DATA_REQUIRED => 'PLEXCEL_NO_AUTH_DATA_REQUIRED',
		);

		/* <tr>
		 *     <td class="formlabel">XXX</td>
		 *     <td colspan="3">
		 *         <input/>
         *     </td>
		 * </tr>
		 */
		$this->tr = array('#tag' => 'tr',
				array('#tag' => 'td',
					'class' => 'formlabel',
					'XXX'),
				array('#tag' => 'td',
					'colspan' => 3,
					array('#tag' => 'input')));
	}

	function toxml($data, $name, $flags=0, $options=NULL) {
		$tr = $this->tr;
		$uacflag = 0;
		$label = '';
		$ret = '';

		if ($options == NULL)
			$options = array();

		if (isset($options['uacflag']))
			$uacflag = $options['uacflag'];

		if (isset($options['label'])) {
			$label = $options['label'];
			if (strlen($label) > 0)
				$label .= ':';
		} else {
			if ($name == NULL) {
				$label = '';
			} else if (isset($this->labels[$name])) {
				$label = $this->labels[$name] . ':';
			} else if ($uacflag > 0) {
				$label = $this->uaclabels[$uacflag];
			} else {
				$label = 'Unknown:';
			}
		}

		if ($data == NULL) {
			$data = '';
		} else if (is_array($data)) {
			if ($flags & PFF_SELECT) {
				// multivalued
				$data = isset($data[$name]) ? $data[$name] : array();
			} else {
				if (isset($data[$name])) {
					$data = $data[$name];
				}
				if (is_array($data)) {
					if (isset($data[0])) {
						$data = $data[0];
					} else {
						$data = '';
					}
				}
			}
		}

		$is_recent = FALSE;
		$is_nowrap = FALSE;
		if ($flags & PFF_TIME) {
			if ($data == '') {
				// not set
			} else if ($data == '0') {
				$data = '';
			} else if ($data == PLEXCEL_EXPIRES_NEVER) {
				$data = 'Never';
			} else {
				$data /= 1000;
				$delta = (time() + 60 * 5) - $data; // allow 5 min skew
				if ($delta > 0 && $delta < (60 * 20)) // 20 min
					$is_recent = TRUE;
// USA				$data = date('M j, Y g:i A', $data);
				$data = date('M j, Y H:i', $data);
				$is_nowrap = TRUE;
			}
		}

		if ($uacflag > 0) {
			$checked = $data & $uacflag ? ' checked' : '';
			$tr = "<tr><td class='formlabel'><input name='p_" . substr($this->uacnames[$uacflag], 8) . "' type='checkbox'$checked/><td colspan='3' nowrap>$label</td></tr>";
		} else if ($flags & PFF_NOINPUT) {
			if ($flags & PFF_SPLIT_WIDE) {
				$tr[0] = $tr[1];
				$tr[1] = '';
				$tr[0]['colspan'] = 4;
				$tr[0][0] = htmlesc($data);
				$flags |= PFF_SPLIT_ROW;
			} else if ($flags & PFF_WIDE) {
				$tr[0]['colspan'] = 4;
				unset($tr[0]['class']);
				$tr[0][0] = $label . ' ' .  htmlesc($data);
				$tr[1] = '';
			} else {
				$tr[0][0] = $label;
				$tr[1][0] = htmlesc($data);
				if ($is_recent)
					$tr[1][0] = '<span style=\'background-color: #ccffcc;\'>' . $tr[1][0] . '</span>';
				if ($is_nowrap)
					$tr[1]['nowrap'] = 'nowrap';
			}
		} else if ($flags & PFF_TEXTAREA) {
			$cols = 72;
			$rows = 4;
			if (isset($options['cols']))
				$cols = $options['cols'];
			if (isset($options['rows']))
				$rows = $options['rows'];
			unset($tr[0]['class']);
			$tr[0]['colspan'] = 4;
			$tr[0][0] = array('#tag' => 'textarea',
					'name' => "p_$name",
					'style' => 'width: 100%;',
					'cols' => $cols,
					'rows' => $rows,
					array(htmlesc($data)));
			$tr[1] = '';
			$flags |= PFF_SPLIT_ROW;
		} else if ($flags & PFF_SELECT) {
			$size = 1;
			if (isset($options['size'])) {
				$size = $options['size'];
			}
			$sa = '';
			if ($size > 1) {
				$sa = " multiple size='$size'";
			}
			$sel = "<td colspan='4'><select name='p_$name" . '[]' . "' style='width: 100%;'$sa>\n";
			foreach($data as $datum) {
				$datum = htmlesc($datum);
				$sel .= "<option value='$datum'>$datum</option>\n";
			}
			$sel .= "</select></td>\n";
			$tr[0] = $sel;
			$tr[1] = '';
			$flags |= PFF_SPLIT_ROW;
/*
echo "<pre>";
print_r($data);
echo "</pre>";
echo "<pre>" . str_replace('<', '&lt;', to_xml($tr)) . "</pre>";
*/
		} else {
			if ($flags & PFF_SPLIT_ROW) {
				$tr[0][0] = '';
			} else {
				$tr[0][0] = $label;
			}

			$type = 'text';
			$size = 36;
			if (isset($options['type']))
				$type = $options['type'];
			if (isset($options['size']))
				$size = $options['size'];
			$tr[1][0]['type'] = $type;
			$tr[1][0]['name'] = "p_$name";
			if ($flags & PFF_READONLY)
				$tr[1][0]['readonly'] = 'readonly';
			if (isset($options['style']))
				$tr[1][0]['style'] = $options['style'];
			if (isset($options['onFocus']))
				$tr[1][0]['onFocus'] = $options['onFocus'];
			$tr[1][0]['size'] = $size;
			$tr[1][0]['value'] = htmlesc($data);

			if ($flags & PFF_SPLIT_WIDE) {
				$tr[0] = $tr[1];
				$tr[1] = '';
				$tr[0]['colspan'] = 4;
				$tr[0][0]['size'] = $size;
				$flags |= PFF_SPLIT_ROW;
			}
			if ($flags & PFF_WIDE) {
				$tr[0]['colspan'] = 4;
				unset($tr[0]['class']);
				$tr[0][0] = $label . ' ';
				$tr[0][1] = $tr[1][0];
				$tr[1] = '';
				$tr[0][1]['size'] = $size;
			}
		}
		if ($flags & (PFF_LEFT_HALF | PFF_RIGHT_HALF)) {
			unset($tr['#tag']);
			unset($tr[1]['colspan']);
		}
		if (isset($options['col4'])) {
			$tr[1]['colspan']--;
			$tr[2][0] = '<td>' . $options['col4'] . '</td>';
		}

		if ($flags & PFF_SPLIT_ROW) {
			$ret = to_xml($tr);

			$tr[0] = array('#tag' => 'td', 'colspan' => 4, 'style' => 'text-align: left;', $label);
			$tr[1] = '';

			$ret = to_xml($tr) . "\n" . $ret;
		} else {
			$ret = to_xml($tr);
		}

		if ($flags & PFF_LEFT_HALF) {
			$ret = '<tr>' . $ret;
		} else if ($flags & PFF_RIGHT_HALF) {
			$ret = $ret . '</tr>';
		}

		return $ret . "\n";
	}
}
