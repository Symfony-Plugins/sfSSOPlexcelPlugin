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
    $this->addMessage('invalid_groups_not_authorized', 'Your user group is not authorized.');
    $this->addMessage('invalid_cn_not_authorized'    , 'Your user didn\'t have an authorized CN');
  }

  protected function doClean($values)
  {
    $username = isset($values[$this->getOption('username_field')]) ? $values[$this->getOption('username_field')] : '';
    $password = isset($values[$this->getOption('password_field')]) ? $values[$this->getOption('password_field')] : '';
    $sf_user = sfContext::getInstance()->getUser();
    $log = sfContext::getInstance()->getLogger();
    $log->log('DEBUG validator, checking');
    if(! preg_match('#@#', $username) && sfConfig::get('app_sf_guard_sso_auto_add_domaine', false))
    {
      $username_sso = $username.'@'. sfConfig::get('app_sf_guard_sso_auto_add_domaine', false) ;
    }else
    {
      $username_sso = $username;
    }
    $log->log('DEBUG validator, checking : '.$username_sso);
    // user exists?
    if (sfPlexcel::logOn($username_sso, $password))
    {
      $log->log('DEBUG validator, user it\'s a sso user');
      $this->checkGroupsForRestrictedLogin($username_sso);
      $this->checkAuthorizedCN($username_sso);
      $log->log('DEBUG valiadtor, log success');
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
      if(!$user )
      {
        $user = new sfGuardUser();
        $user->setUsername($username);
        $user->getProfile()->set(sfConfig::get('app_sf_guard_sso_field','is_local'),false);
        $user->save();
      }
      /*else
      if($user->getProfile()->get(sfConfig::get('app_sf_guard_sso_field','is_local')) == true)
      {
        // local user, who have been added to the ldap.
        $user->getProfile()
             ->set(sfConfig::get('app_sf_guard_sso_field','is_local'), false)
             ->save();
      }*/

      return array_merge($values, array('user' => $user));
    }

    // check if the config of the application authorize a local user.
    if(sfConfig::get('app_sf_guard_sso_local_login',true))
    {
      $user = Doctrine_Query::create()
                ->from('sfGuardUser sgu')
                ->leftJoin('sgu.Profile sgp')
                ->addWhere('sgp.'.sfConfig::get('app_plexcel_extend_table_field','is_local').' = true AND sgu.username = ?', $username)
                ->limit(1)
                ->execute()
                ->getFirst();

      // check if an user if found and the password match
      if($user && $user->checkPassword($password))
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
  * this function check if the user who try to login have an authorized cn.
  *
  */
  public function checkAuthorizedCN($username)
  {
    $log = sfContext::getInstance()->getLogger();
    $result = array();
    $log->log('DEBUG validator authorized CN');
    if (is_array(sfConfig::get('app_sf_guard_sso_authorized_cn', false)) )
    {
      $user_cn = sfPlexcel::getAccount($username, array('distinguishedName'));
      $log->log('DEBUG validator authorized CN, CN : '.$user_cn);
      preg_match_all('/CN\=(.*?),/i', $user_cn, $result);

      $authorized_group = false;
      $configured_group = sfConfig::get('app_sf_guard_sso_authorized_cn');
      foreach ($result[1] as $group)
      {
        if (in_array(trim($group), $configured_group))
        {
          $log->log('DEBUG validator authorized CN, group '.$group.' is authorized');
          $authorized_group = true;
        }
      }

      if (! $authorized_group)
      {
        $log->log('DEBUG validator authorized CN, user is not authorized');
        throw new sfValidatorError($this, 'invalid_cn_not_authorized');
      }
    }
    $log->log('DEBUG validator authorized CN, return true');
    return true;
  }

 /**
   * this function check if the user who try to login is member of an authorized group.
   *
   * @param $username
   * @param bool
   */
  public function checkGroupsForRestrictedLogin($username)
  {
    $log = sfContext::getInstance()->getLogger();
    if (sfConfig::get('app_sf_guard_sso_restrict_login_to_groups', false))
    {
      $log->log('DEBUG validator checking groups ');
      $auth_groups = sfConfig::get('app_ldap_search_groups');
      if (!sfPlexcel::chechIfUserIsMemberOfGroups($username, $auth_groups))
      {
        $log->log('DEBUG validator groups is not authorized ('.sfPlexcel::getUserGroups($username).') ');
        throw new sfValidatorError($this, 'invalid_groups_not_authorized');
      }
    }
    $log->log('DEBUG validator checking groups, groups authorized, return true');
    return true;
  }

}

