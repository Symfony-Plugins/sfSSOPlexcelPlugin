<?php

/**
 * SSO Validator
 * use plexcel for check if the user have an account in the ldap
 *
 * @author pierre
 *
 */
class sfSSOValidatorUser extends sfGuardValidatorUser
{
  public function configure($options = array(), $messages = array())
  {
    parent::configure($options , $messages );
    $this->addOption('sso_login_field',                'sso_login');

    $this->addMessage('invalid_groups_not_authorized', 'Your user group is not authorized.');
    $this->addMessage('invalid_cn_not_authorized'    , 'Your user didn\'t have an authorized CN');
  }

  protected function doClean($values)
  {
    $username  = isset($values[$this->getOption('username_field')])  ? $values[$this->getOption('username_field')]  : '';
    $password  = isset($values[$this->getOption('password_field')])  ? $values[$this->getOption('password_field')]  : '';
    $sso_login = isset($values[$this->getOption('sso_login_field')]) ? $values[$this->getOption('sso_login_field')] : false;

    $sf_user      = sfContext::getInstance()->getUser();
    $log          = sfContext::getInstance()->getLogger();
    $is_sso_user  = false;

    $log->log('DEBUG validator, checking');

    if ($sso_login == true)
    {
      $log->log('{sfSSOValidator} user request a SSO auth');
      if (sfPlexcel::isSSO())
      {
        $account     = sfPlexcel::getAccount(NULL, PLEXCEL_SUPPLEMENTAL);
        $username    = $account['sAMAccountName'];
        $is_sso_user = true;
        $log->log("{sfSSOValidator} it's a SSO user ($username) ");
      }
      else
      {
        return false;
      }
    }

    if (!preg_match('#@#', $username) && sfConfig::get('app_sf_guard_sso_auto_add_domaine', false))
    {
      $username_sso = $username.'@'. sfConfig::get('app_sf_guard_sso_auto_add_domaine', false) ;
    }
    else
    {
      $username_sso = $username;
    }

    $log->log('{sfSSOValidator} checking : '.$username_sso);

    if ( sfConfig::get('app_sf_guard_sso_active') && ($is_sso_user OR sfPlexcel::logOn($username_sso, $password)))
    {
      $log->log('{sfSSOValidator} user logon');
      $this->checkIfuserIsMemberOf();
      $log->log('{sfSSOValidator} log success');
      $sf_user->setSSOAuthentification(true);
      $sf_user->setSSOUsername($username);
      $user = Doctrine_Query::create()
                ->from('sfGuardUser sgu')
                ->leftJoin('sgu.Profile sgp')
                ->addWhere('sgu.username = ? ', $username)
                ->addWhere('sgp.'.sfConfig::get('app_sf_guard_sso_field','is_local').' = false')
                ->limit(1)
                ->execute()
                ->getFirst();

      // looking for an existant user in the database.
      if (!$user)
      {
        $user = new sfGuardUser();
        $user->setUsername($username);

        $userInfos = sfPlexcel::getAccount(null, array('givenName', 'sn', 'mail', 'distinguishedName'));
        $user->getProfile()->setFirstname($userInfos['givenName']);
        $user->getProfile()->setLastname($userInfos['sn']);
        $user->getProfile()->setEmail($userInfos['mail']);
        $user->getProfile()->set(sfConfig::get('app_sf_guard_sso_field','is_local'),false);
        $user->getProfile()->setDistinguishedName($userInfos['distinguishedName']);
        $user->save();
      }

      return array_merge($values, array('user' => $user));
    }

    // check if the config of the application authorize a local user.
    if (sfConfig::get('app_sf_guard_sso_local_login',true))
    {
      $user = Doctrine_Query::create()
                ->from('sfGuardUser sgu')
                ->leftJoin('sgu.Profile sgp')
                ->addWhere('sgp.'.sfConfig::get('app_plexcel_extend_table_field','is_local').' = true AND sgu.username = ?', $username)
                ->limit(1)
                ->execute()
                ->getFirst();

      // check if an user if found and the password match
      if ($user && $user->checkPassword($password))
      {
        $sf_user->setSSOAuthentification(false);
        return array_merge($values, array('user' => $user));
      }
    }

    if ($this->getOption('throw_global_error'))
    {
      throw new sfValidatorError($this, 'invalid');
    }

    throw new sfValidatorErrorSchema($this, array($this->getOption('username_field') => new sfValidatorError($this, 'invalid')));
  }



 /**
  * this function check if the user is member of a groups
  *
  */
  protected function checkIfuserIsMemberOf()
  {
    $log = sfContext::getInstance()->getLogger();
    $result = array();
    $log->log('{sfSSOValidator} check if user has an authorized CN');
    if( is_array(sfConfig::get('app_sf_guard_sso_authorized_cn')) )
    {
      $groups = sfConfig::get('app_sf_guard_sso_authorized_cn');
      foreach($groups as $group)
      {
          if(sfPlexcel::isMemberOf($group))
          {
            $log->log('{sfSSOValidator} user is a member of '.$group);
            return true;
          }
          $log->log('{sfSSOValidator} user is NOT a member of '.$group);
      }
     throw new sfValidatorError($this, 'invalid_groups_not_authorized');
   }
    $log->log('{sfSSOValidator} user not a member of authorized groups');
    return false;
  }

  public function getUserIfSSO()
  {
    try
    {
      return $this->doClean(array('sso_login' => true ));
    }
    catch(sfValidatorError $e)
    {
      return $e->getMessage();
    }
  }
}

