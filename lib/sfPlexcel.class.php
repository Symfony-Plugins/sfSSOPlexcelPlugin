<?php

# include original plexcel.php file
if (sfConfig::get('app_sf_guard_sso_lib_dir',false))
{
  require_once sfConfig::get('app_sf_guard_sso_lib_dir') . '/common.php';
  require_once sfConfig::get('app_sf_guard_sso_lib_dir') . '/plexcel.php';
}
else
{
  require_once dirname(__FILE__) . '/vendor/common.php';
  require_once dirname(__FILE__) . '/vendor/plexcel.php';
}



/**
 * class for use plexcel
 *
 */
class sfPlexcel
{

  private static $resource = false;

  /**
   * create a new connection
   * @return ressource self::$ressource
   */
  public static function getConnection()
  {
    if (self::$resource == false)
    {
      self::$resource = plexcel_new(self::getLdapConfig(), array());
      if ( !is_resource(self::$resource))
      {
        throw new sfException("Connection error");
      }
    }
    return self::$resource ;
  }

  /**
   * build the dsn
   *
   * @return array $config
   */
  public static function getLdapConfig()
  {
    $authority        = NULL;
    $base             = NULL;
    $bindstr          = NULL;
    $px               = NULL;
    $action           = NULL;
    $is_authenticated = FALSE;

    if ($base == NULL)
    {
      $base = 'DefaultNamingContext';
    }

    $authority = NULL;

    if (!$authority)
    {
      /* If the authority is not set and a username is
       * supplied, use the domain as the authority.
       */
      $username = NULL;
      if ($username)
      {
        $pos = strpos($username, '@');
        if ($pos > 0)
        {
          $domain = substr($username, $pos + 1);
          if ($domain)
          {
            $authority = $domain;
          }

        }
      }

    }

    $bindstr = 'ldap://';
    if ($authority)
    {
      $bindstr .= $authority;
    }

    $bindstr .= '/' . $base;

    return $bindstr;
  }

  /**
   * return the status of the connection
   * @return unknown_type
   */
  public static function status()
  {
    return  plexcel_status(self::getConnection());
  }

  /**
   * return a message for the status
   * @return unknown_type
   */
  public static function getStatusMessage()
  {
    switch(self::status())
    {
      case 'PLEXCEL_NO_CREDS':
             return "no creds";
             break;
      case 'PLEXCEL_PRINCIPAL_UNKNOW':
            return "principal unknow";
            break;
      case 'PLEXCEL_LOGON_FAILED':
            return "logon failed";
            break;
      default:
            return "error :".self::status();
    }
  }

  /*
   * search in the ldap
   * $params = array(scope" => "sub",
   *                 "filter" => "(&(objectClass=user)(logonCount=0))"
   *                );
   * @param array() $params
   *       $params accept options :
   *          string base (default value empty, empty => RootDSE)
   *          string scope (default value base)
   *          string filter (default value objectClass=*)
   *          array attrs (default value NULL)
   *          boolean attronly (default value FALSE)
   /*          int   timeout ( default value 60)
   * @return array() $result
   */
  public static function search( $params = array())
  {
    return plexcel_search_objects(self::getConnection(), $params);
  }

  /**
   * logon with sso
   *
   * @param string $username
   * @param string $password
   * @return boolean
   */
  public static function logOn($username, $password)
  {

    return plexcel_logon(self::getConnection(), session_id(), $username, $password);
  }

  /**
   * clear the SSO Session
   * @return unknown_type
   */
  public static function logOff($username)
  {
    plexcel_logoff(self::getConnection(), session_id(), $username);
  }

  /**
   * retrieve informations for an account
   * if account is not specified, this retrieve for the current user
   *
   * Example :
   * <?php getAccount('nicolas.dupuis', array(
   *   'userPrincipalName', 'sn' ))?>
   * attributes choices can be :
   *    general :
   *     userPrincipalName, sAMAccountName, givenName, sn, initials, displayName,
   *     description, physicalDeliveryOfficeName, telephoneNumber, mail, wWWHomePage,
   *   for authentification :
   *     primaryGroupID, userWorkstations,
   *   for profile :
   *     profilePath, scriptPath, homeDrive, homeDirectory,
   *   for address :
   *     streetAddress, postOfficeBox, l, st, postalCode, co, c
   *   for telephone :
   *     homePhone, mobile, facsimileTelephoneNumber, pager, ipPhone, info
   *   for organization :
   *     title, department, company, manager
   *   other :
   *     countryCode, codePage, instanceType
   *
   *
   *   check: memberOf
   *
   * @param string $account account name
   * @param array() $attribut
   * @return array
   */
  public static function getAccount($account = null, $attribut = PLEXCEL_SUPPLEMENTAL)
  {
    return plexcel_get_account(self::getConnection(), $account, $attribut );
  }


  /**
   * return the current user or false
   * @return unknown_type
   */
  public static function getCurrentUser()
  {
    self::getConnection();
    if(self::isSSO())
    {
      sfContext::getInstance() ->getLogger() ->log('DEBUG splexcel is sso = true');
      $result = self::getAccount(null);
      if ($result == false)
      {
        return self::status();
      }

      return $result['sAMAccountName'];
    }
    sfContext::getInstance() ->getLogger() ->log('DEBUG splexcel is sso = false');
    return false;
  }

  /**
   * return groups of user
   * @param string $user
   * @return array() unknown_type
   */
  public static function getUserGroups($user)
  {
    return self::getAccount($user, array('CN'));
  }

 /**
  * check if an user is member of one group in a groups array
  * @param String $user
  * @param Array $groups
  * @return bool
  */
  public static function chechIfUserIsMemberOfGroups($user, $groups)
  {
    $groups_of_user  = self::getUserGroups($user);

    if (isset($groups_of_user['memberOf']))
    {
      foreach($groups as $group)
      {
        if (preg_match('#[=,]'.$group.",#", var_export($groups_of_user['memberOf'], true)))
        {
          return true;
        }
      }
    }
    return false;
  }

  /**
   * return group for the current user
   * @return array() unknown_type
   */
  public static function getCurrentUserGroups()
  {
    return self::getUserGroups();
  }

  /**
   * check if the user is auth
   * $options can be :
   *   authority, base
   *
   * @param array() $options
   * @return boolean
   */
  public static function isAuthenticated($options = null)
  {
    return self::isSSO(self::getConnection(), $options);
  }

  /**
   * check if the current user is member of a group
   * @param string group
   * @return unknown_type
   */
  public static function isMemberOf($group)
  {
    return plexcel_is_member_of(self::getConnection(), $group);
  }

  /**
   * return the current authority
   *
   * If the $user parameter is FALSE, the hostname of the directory binding is returned. If the $user value is
   * TRUE, the hostname of the server that is an authority for the user is returned.

   * @param string $user
   * @return unknown_type
   */
  public static function getAuthority($user = null)
  {
    return plexcel_get_authority(self::getConnection(), $user);
  }

  /**
   * write into the plexcel log
   * @param $level
   * @return unknown_type
   */
  public static function writeLog($level,$message)
  {
    plexcel_log($level, $message);
  }

  public static function plexcel_token($name)
  {
    $tok = $_SESSION[$name] = rand(10000, 99999);
    return $tok;
  }
  /**
   * plexcel doc :
   * The plexcel_accept_token function accepts and returns base 64 encoded authentication tokens and
   * authenticates the Plexcel context resource in the process. It is used almost exclusively by the plexcel_sso
   * function, in conjunction with plexcel_status, to implement the 'Negotiate' form of HTTP authentication supported
   * by modern browsers.
   * @param $token
   * @return unknown_type
   */
  public static function acceptToken($token )
  {
    return plexcel_accept_token(self::getConnection(), $token);
  }

  /**
   *
   * plexcel doc :
   *
   * @param $px
   * @param $options
   * @return unknown_type
   */
  public static function isSSO($options=NULL)
  {
    if (sfConfig::get('sf_environment') != 'test')
    {
      $headers = apache_request_headers();
      sfContext::getInstance() ->getLogger() ->log('DEBUG sfPlexcel, is sso : headers : '. var_export($headers, true) .' ok');
    }

    return plexcel_sso(self::getConnection(), $options);
 }

  /**
   * change an user password (need the current password)
   *
   * @param $user
   * @param $current_password
   * @param $new_password
   * @return boolean
   */
  public static function changePassword($user, $current_password, $new_password)
  {
    return plexcel_change_password(
            self::getConnection(),
            $user,
            $current_password,
            $new_password);
  }


  /**
   * administrator command
   */


  /**
   * change a password user
   *
   *
   * Current user need to be an Administrators or an Account Operators
   * @param $user
   * @param $password
   * @return boolean
   */
  public static function setPassword($user, $password)
  {
    return plexcel_set_password(self::getConnection(),
             $user,
             $password);
  }


}
