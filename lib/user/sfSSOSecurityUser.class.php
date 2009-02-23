<?php


class sfSSOSecurityUser extends sfGuardSecurityUser
{
  /**
   * retrieve all credentials
   *   
   * @return unknown_type
   */
  public function getLdapCredentials()
  {
    if($this->sso_auth)
    {
      return sfPlexcel::getCurrentUserGroups();
    }else{ 
      return null;
    }
  }
  
  
  
  /**
   * sign out with SSO
   */
  public function signOutSSO()
  {
    sfPlexcel::logOff($this->getUsername());
    $this->setUsername(null);
    parent::signOut();
  }
 
  /**
   * return the username of the auth user
   *  idem que pour setUsername
   * @return unknown_type
   */
  public function getSSOUsername()
  {
    return $this->getAttribute('username', null, 'sf_sso_plexcel_plugin');
  } 
  
  /**
   * set the username of the auth user
   * à déplacer certainemetn dans sfGuard ou voir avec sfGuard
   * @param string $username
   */
  public function setSSOUsername($username)
  {
    $this->setAttribute('username', $username, 'sf_sso_plexcel_plugin');
  }
  
  /**
   * set the authentification type
   * true  for a SSO user
   * false for a local user
   * @param boolean $bool
   */
  public function setSSOAuthentification($bool)
  {
    $this->setAttribute('is_local', ! (bool) $bool, 'sf_sso_plexcel_plugin');
  }
  

  /**
   * get authentification type
   * true  for a SSO user
   * false for a local user
   * @return boolean $sso 
   */
  public function getSSOAuthentification()
  {
    return ! $this->getAttribute('is_local', true, 'sf_sso_plexcel_plugin');    
  }
}