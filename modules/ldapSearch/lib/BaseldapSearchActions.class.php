<?php

/**
 * Base actions for the sfSSOPlexcelPlugin ldapSearch module.
 * 
 * @package     sfSSOPlexcelPlugin
 * @subpackage  ldapSearch
 * @author      Pierre Cahard <pcahard@gmail.com>
 * @version     SVN: $Id: BaseActions.class.php 12534 2008-11-01 13:38:27Z Kris.Wallsmith $
 */
abstract class BaseldapSearchActions extends sfActions
{
  
  /**
   * a simple action for count all users who never connected
   */
  public function executeCountUsersNeverConnected(sfWebRequest $request)
  {
    $this->forward404Unless(sfConfig::get('app_ldap_search_action_count_users_never_connected',false),"You need to configure your app.yml for access to this action.");
    
    $params = array(
      "scope" => "sub",
      "filter" => "(&(objectClass=user)(logonCount=0))");
    $this->results = sfPlexcel::search($params);
  }
  
  /**
   * get some informations about an user
   * @param sfWebRequest $request
   */
  public function executeSearch(sfWebRequest $request)
  {
    $this->forward404Unless(sfConfig::get('app_ldap_search_action_search', false),"You need to configure your app.yml for access to this action.");

    $this->form = new ldapSearchForm();

    if($request->getMethod() != 'POST')
    {
      return sfView::SUCCESS;
    }
    
    $this->form->bind($request->getParameter('search'));
    $params = $this->form->getValue('filter');
    
    $options = array(
      'givenName', 
      'sn',
      'cn',
      'company',
      'department',
      'manager');

    $this->result = sfPlexcel::getAccount($params, $options);
  }
}
