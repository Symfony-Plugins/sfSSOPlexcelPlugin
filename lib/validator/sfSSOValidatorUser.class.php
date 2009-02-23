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
  
  protected function doClean($values)
  {
    $username = isset($values[$this->getOption('username_field')]) ? $values[$this->getOption('username_field')] : '';
    $password = isset($values[$this->getOption('password_field')]) ? $values[$this->getOption('password_field')] : '';
    $sf_user = sfContext::getInstance()->getUser();
   
    // user exists?
    if( sfPlexcel::logOn($username, $password))
    {
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
  
}

