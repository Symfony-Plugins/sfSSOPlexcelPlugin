<?php
// plexcel.php - Plexcel PHP include file
// Copyright (c) 2007 IOPLEX Software
// http://www.ioplex.com/
// This software is private property and may not be used or distributed in
// any form without explicit written permission from IOPLEX Software.

if (!extension_loaded('plexcel'))
	die('Error: Plexcel PHP extension has not been loaded.');
if ('2.7.20' != PLEXCEL_VERSION)
	die('The file plexcel.php does not appear to be current. Please re-copy the Plexcel script files.');
if (isset($_SESSION) == FALSE)
	die('Error: $_SESSION not defined');

define('PLEXCEL_AUTH_NONE',      0x0);
define('PLEXCEL_AUTH_SSO',       0x1);
define('PLEXCEL_AUTH_LOGON',     0x2);
define('PLEXCEL_AUTH_SSO_LOGON', 0x3);

// General purpose functions
function plexcel_get_param($name, $default=NULL) {
	$str = '';
	if (isset($_POST[$name])) {
		$str = trim($_POST[$name]);
	} else if (isset($_GET[$name])) {
		$str = trim($_GET[$name]);
	}
	return strlen($str) > 0 ? $str : $default;
}
// Used to prevent re-POSTs
function plexcel_token($name) {
	$tok = $_SESSION[$name] = rand(10000, 99999);
	return $tok;
}
function plexcel_token_matches($name) {
	if (isset($_SESSION[$name]) && $_SESSION[$name] == plexcel_get_param($name, NULL)) {
		unset($_SESSION[$name]);
		return TRUE;
	}
	return FALSE;
}
// convert dom nodes into xml
function to_xml($node) {
	if (is_array($node) == FALSE) {
		/* Just some text or a number. Return as is.
		 */
		return $node;
	} else if (isset($node['#tag']) == FALSE) {
		/* Just a collection of nodes
		 */
		$xml = '';
		foreach ($node as $child) {
			$xml .= to_xml($child);
		}
		return $xml;
	}
	/* else we have a bonified dom node */

	$html = "<" . $node['#tag'];
	foreach ($node as $attr => $val) {
		if (is_numeric($attr) == FALSE && substr($attr, 0, 1) != '#') {
			$html .= " $attr=\"$val\"";
		}
	}

	$have_children = FALSE;
	foreach ($node as $key => $child) {
		if (!is_numeric($key)) {
			continue;
		}
		if ($have_children == FALSE) {
			$have_children = TRUE;
			$html .= ">";
		}
		if (is_array($child)) {
			$html .= to_xml($child);
		} else {
			$html .= $child;
		}
	}
	if ($have_children) {
		$html .= "</" . $node['#tag'] . ">";
	} else {
		$html .= "/>";
	}

	return $html;
}
function plexcel_modify_by_post($px, $distinguishedName, $attrs)
{
	$defs = plexcel_get_attrdefs($px, $attrs);
	if ($defs == NULL)
		return FALSE;

	/* A delete will fail if the attribute does not already exist so we must
	 * first get the current values.
	 */
	$acct = plexcel_get_account($px, $distinguishedName, $attrs);
	if (is_array($acct)) {
		foreach($attrs as $ai => $attr) {
			$pname = "p_$attr";
			$param = plexcel_get_param($pname, NULL);

			/* Note that this must use '!= NULL' so that the
			 * string '0' does not evaluate to false.
			 */
			if ($param != NULL) {
				/* True multivalued attributes should be handled using other
				 * methods that add and remove individual elements. But
				 * this will handle multivalued attributes as if they were
				 * single valued (e.g. description).
				 */
				$is_multi = ($defs[$attr]['flags'] & PLEXCEL_SINGLE_VALUED) == 0;

				/* Check to see if acct already has desired value.
				 */
				if (isset($acct[$attr])) {
					if ($is_multi) {
						if (in_array($param, $acct[$attr]))
							$param = NULL;
					} else if (strcmp($param, $acct[$attr]) == 0) {
						/* Must use strcmp so that inputs like '0' and
						 * '0.00' will not be considered equal.
						 */
						$param = NULL;
					}
				}

				if ($param != NULL) {
					$acct[$attr] = $is_multi ? array($param) : $param;
				} else {
					unset($attrs[$ai]);
				}
			} else {
				unset($attrs[$ai]);
				if (isset($_POST[$pname]) && isset($acct[$attr])) {
					$attrs[$attr] = PLEXCEL_MOD_DELETE;
				}
			}
		}

/*
echo "<pre>";
print_r($attrs);
print_r($acct);
echo "</pre>";
*/

		return count($attrs) == 0 || plexcel_modify_object($px, $acct, $attrs);
	}

	return FALSE;
}
function plexcel_get_request_url($proto=NULL) {
	if ($proto) {
		$url = strtolower($proto);
	} else {
		$url = isset($_SERVER['HTTPS']) ? "https://" : "http://";
	}
	$url .= $_SERVER['SERVER_NAME'];
	$default_port = isset($_SERVER['HTTPS']) ? 443 : 80;
	if ($_SERVER['SERVER_PORT'] != $default_port) {
		$url .= ":" . $_SERVER['SERVER_PORT'];
	}
	$url .= $_SERVER['REQUEST_URI'];
	return $url;
}

function plexcel_get_sso_helpmsg() {
	return "<ul>
<li>Your web broswer is not configured to negotiate <tt>WWW-Authenticate: Negotiate</tt> authentication. Please check the following settings:
<ul>
<li>For IE, check <i>Tools &gt; Internet Options &gt; Advanced &gt;</i> scroll all the way to the bottom and select <i>Enable Integrated Windows Authentication (requires restart)</i>.</li>
<li>For IE the target site may need to be added to the IntrAnet zone in your security preferences.</li>
<li>For Firefox, type <i>about:config</i> into the address bar, type <i>negotiate</i>&lt;enter&gt; into the Filter and add the target site or domain to both the <tt>network.negotiate-auth.trusted-uris</tt> and <tt>network.negotiate-auth.delegation-uris</tt> properties.</li>
</ul>
<li>If your browser is configured to use a proxy, the target site must be excluded from communication with the proxy server. For IE, check <i>Tools &gt; Internet Options &gt; Connections &gt; LAN Settings &gt; Advanced &gt;</i> and add the URL prefix of the target site to the <i>Exceptions</i> box.</li>
<li>Your Kerberos ticket has expired.</li>
<li>The server is not configured to perform SSO.</li>
</ul>";
}
function plexcel_sso($px, $options=NULL) {
	if (plexcel_token_matches('p_authenticate_repost')) {
		plexcel_status($px, PLEXCEL_NO_CREDS);
		return FALSE;
	}

	$token = '';
	$headers = apache_request_headers();
	if (isset($headers['Authorization'])) {
		$token = $headers['Authorization'];
		if (strncmp($token, 'Negotiate ', 10) != 0) {
			plexcel_status($px, 'Token does not begin with "Negotiate "');
			return FALSE;
		}

		$token = plexcel_accept_token($px, $token);

		if (plexcel_status($px) != PLEXCEL_CONTINUE_NEEDED) {
			if (plexcel_status($px) == PLEXCEL_SUCCESS) {
                                         /* authentication success */
				if ($token)
					header('WWW-Authenticate: Negotiate ' . $token, TRUE, 200);
				return TRUE;
			}
			/* authentication failed or something unexpected happend */
			return FALSE;
		}
		$token = ' ' . $token;
	}

	$auth_type = PLEXCEL_AUTH_SSO_LOGON;
	$logonurl = NULL;
	$authority = plexcel_get_param('p_authority', NULL);
	if ($options) {
		if (isset($options['auth_type']))
			$auth_type = $options['auth_type'];
		if (isset($options['logonurl']))
			$logonurl = $options['logonurl'];
		if (isset($options['authority']))
			$authority = $options['authority'];
	}

	header('WWW-Authenticate: Negotiate' . $token);
	header('HTTP/1.1 401 Unauthorized');
	$msg = '<html><head><title>Kerberos Authentication Required</title></head>';

	if ($auth_type & PLEXCEL_AUTH_LOGON) {
		/* If logon fallback is enabled, set a JavaScript redirect so that if the
		 * browser does not act on the WWW-Authenticate: Negotiate the client
		 * will submit again and give us an opportunity to do something about it.
		 *
		 * This repost token is not used to stop reposting but rather to detect
		 * the JavaScript redirect so that we can set PLEXCEL_NO_CREDS and
		 * is_authenticated=FALSE for which the application should present the user
		 * with a logon form (or guest view).
		 */
		$tok = plexcel_token('p_authenticate_repost');
		$action = '';
		if ($logonurl)
			$action = " action='$logonurl'";
		$msg .= "<body onload='document.form.submit()'>
<form name='form'$action method='POST'>
<input type='hidden' name='p_authenticate_repost' value='$tok'/>";
		/* If an authority has been specified, we need to propagate that value.
		 */
		if ($authority) {
			$msg .= "<input type='hidden' name='p_authority' value='$authority'/>";
		}
		$msg .= "</form>";
	} else {
		$msg .= "<body>\n";
	}
	$msg .= '<table width=\'600\'><tr><td><h2>Kerberos Authentication Required</h2>
<p>Kerberos 5 authentication is required. If you are seeing this message, authentication has failed. The cause could be one of the following:
<p>';
	$msg .= plexcel_get_sso_helpmsg();
	$msg .= 'For a definitive answer, please communicate this information to your network administrator.
<p>
<i>IOPLEX Plexcel for PHP version ' . PLEXCEL_VERSION . '</i></td></tr></table></body></html>';
	die($msg);
}
function plexcel_authenticate($px, $id, $options=NULL) {
	$auth_type = PLEXCEL_AUTH_SSO_LOGON;
	if ($options) {
		if (isset($options['auth_type']))
			$auth_type = $options['auth_type'];
	}

	if ($auth_type & PLEXCEL_AUTH_LOGON) {
		$username = plexcel_get_param("p_username");
		if ($username) {
			$password = plexcel_get_param("p_password");
			return plexcel_logon($px, $id, $username, $password);
		}
	}
	if ($auth_type & PLEXCEL_AUTH_SSO)
		return plexcel_sso($px, $options);

	plexcel_status($px, PLEXCEL_NO_CREDS);
	return FALSE;
}
//
// Negotiates specific domain / domain controller, authenticates
// using SSO or explicit logon / logoff and returns results in array.
//
function plexcel_preamble($options=NULL) {
	$ret = array();
	$err = NULL;
	$authority = NULL;
	$base = NULL;
	$bindstr = NULL;
	$px = NULL;
	$action = plexcel_get_param('p_action', 'default');
	$is_authenticated = FALSE;

	if ($action == 'change_authority') {
		$action = 'default';
	} else {
		if ($options) {
			if (isset($options['authority']))
				$authority = $options['authority'];
			if (isset($options['base']))
				$base = $options['base'];
		}
		if ($base == NULL)
			$base = 'DefaultNamingContext';
		$authority = plexcel_get_param('p_authority', $authority);

		if (!$authority) {
			/* If the authority is not set and a username is
			 * supplied, use the domain as the authority.
			 */
			$username = plexcel_get_param('p_username');
			if ($username) {
				$pos = strpos($username, '@');
				if ($pos > 0) {
					$domain = substr($username, $pos + 1);
					if ($domain)
						$authority = $domain;
				}
			}
		}

		$bindstr = 'ldap://';
		if ($authority)
			$bindstr .= $authority;
		$bindstr .= '/' . $base;

		$px = plexcel_new($bindstr, $options);
		if ($px == FALSE) {
			$err = "<p/>Plexcel error: <pre>" . plexcel_status(NULL) . "</pre>";
		} else {
			if (plexcel_authenticate($px, session_id(), $options) == FALSE) {
				if (plexcel_status($px) == PLEXCEL_NO_CREDS) {
					;
				} else if (plexcel_status($px) == PLEXCEL_PRINCIPAL_UNKNOWN) {
					$username = plexcel_get_param("p_username");
					$err = "<p/>Principal unknown: $username";
				} else if (plexcel_status($px) == PLEXCEL_LOGON_FAILED) {
					$err = "<p/>Logon failed (e.g. bad password)";
				} else {
					$err = "<p/>Plexcel error: <pre>" . plexcel_status($px) . "</pre>";
				}
			} else {
				if ($action == 'logoff') {
					$username = plexcel_get_param('p_username');
					if (plexcel_logoff($px, session_id(), $username) == FALSE) {
						$err = "<p/>Plexcel error: <pre>" . plexcel_status($px) . "</pre>";
					}
					$action = 'default';
				} else {
					$is_authenticated = TRUE;
				}
			}
			$authority = plexcel_get_authority($px, false);
		}
	}

	return array($authority,
				$bindstr,
				$px,
				$err,
				$action,
				$is_authenticated,
				'authority' => $authority,
				'bindstr' => $bindstr,
				'px' => $px,
				'err' => $err,
				'action' => $action,
				'is_authenticated' => $is_authenticated);
}

//
// These attribute definitions set desireable
// conversions for common attributes.
//
function plexcel_set_conv_attrdefs($px) {
	$attrdefs = array(
			'whenCreated' => array(
				'type' => PLEXCEL_TYPE_TIME,
				'flags' => PLEXCEL_SINGLE_VALUED,
				'conv' => PLEXCEL_CONV_TIME1970M_X_TIMEUTC),
			'whenChanged' => array(
				'type' => PLEXCEL_TYPE_TIME,
				'flags' => PLEXCEL_SINGLE_VALUED,
				'conv' => PLEXCEL_CONV_TIME1970M_X_TIMEUTC),
			'objectSid' => array(
				'type' => PLEXCEL_TYPE_BINARY,
				'flags' => PLEXCEL_SINGLE_VALUED,
				'conv' => PLEXCEL_CONV_SIDSTR_X_BINARY),
			'objectGUID' => array(
				'type' => PLEXCEL_TYPE_BINARY,
				'flags' => PLEXCEL_SINGLE_VALUED,
				'conv' => PLEXCEL_CONV_BASE64_X_BINARY),
			'badPasswordTime' => array(
				'type' => PLEXCEL_TYPE_INT64,
				'flags' => PLEXCEL_SINGLE_VALUED,
				'conv' => PLEXCEL_CONV_TIME1970M_X_TIME1601),
			'lastLogoff' => array(
				'type' => PLEXCEL_TYPE_INT64,
				'flags' => PLEXCEL_SINGLE_VALUED,
				'conv' => PLEXCEL_CONV_TIME1970M_X_TIME1601),
			'lastLogon' => array(
				'type' => PLEXCEL_TYPE_INT64,
				'flags' => PLEXCEL_SINGLE_VALUED,
				'conv' => PLEXCEL_CONV_TIME1970M_X_TIME1601),
			'pwdLastSet' => array(
				'type' => PLEXCEL_TYPE_INT64,
				'flags' => PLEXCEL_SINGLE_VALUED,
				'conv' => PLEXCEL_CONV_TIME1970M_X_TIME1601),
			'accountExpires' => array(
				'type' => PLEXCEL_TYPE_INT64,
				'flags' => PLEXCEL_SINGLE_VALUED,
				'conv' => PLEXCEL_CONV_TIME1970M_X_TIME1601),
			'userParameters' => array(
				'type' => PLEXCEL_TYPE_BINARY,
				'flags' => PLEXCEL_SINGLE_VALUED,
				'conv' => PLEXCEL_CONV_BASE64_X_BINARY));
	return plexcel_set_attrdefs($px, $attrdefs);
}

?>
