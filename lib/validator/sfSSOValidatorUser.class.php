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
      $this->checkIfuserIsMemberOf();
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
  * this function check if the user is member of a groups
  *
  */
  public function checkIfuserIsMemberOf()
  {
    $log = sfContext::getInstance()->getLogger();
    $result = array();
    $log->log('DEBUG validator authorized CN');
    if( is_array(sfConfig::get('app_sf_guard_sso_authorized_cn')) )
    {
      $groups = sfConfig::get('app_sf_guard_sso_authorized_cn');
      foreach($groups as $group)
      {
          if(sfPlexcel::isMemberOf($group))
          {
            $log->log('DEBUG validator authorized CN : user is a member of '.$group);
            return true;
          }
          $log->log('DEBUG validator authorized CN : user is NOT a member of '.$group);
      }
      throw new sfValidatorError($this, 'invalid_groups_not_authorized');

    }
    $log->log('DEBUG validator is not a member of a groups');
    return false;
  }

}

