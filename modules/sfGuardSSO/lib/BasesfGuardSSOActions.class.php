<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package    symfony
 * @subpackage plugin
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class BasesfGuardSSOActions extends sfActions
{
  public function executeSignin($request)
  {
    $log = sfContext::getInstance()->getLogger();
    $sso_login  = false;
    $user       = $this->getUser();
    $class      = sfConfig::get('app_sf_guard_plugin_signin_form', 'sfGuardFormSignin');
    $this->form = new $class();

    if ($user->isAuthenticated())
    {
      return $this->redirect('@homepage');
    }


    if ( sfConfig::get('app_sf_guard_sso_active') && $this->getUser()->getAttribute('try_to_ident_with_sso', true, 'sf_sso_plexcel_plugin') )
    {
      $log->debug('{sfGuardSSO} try to ident the user with SSO' );
      $validator = new sfSSOValidatorUser();
      $values = $validator->getUserIfSSO();

      if( isset($values['user']) && $values['user'] instanceof sfGuardUser )
      {
        $this->getUser()->signin($values['user'], true);
        $signinUrl = sfConfig::get('app_sf_guard_sso_success_signin_url');
      }
      elseif ($values === false)
      {
        $log->debug('{sfGuardSSO} it\'s not a SSO user');
      }
      else
      {
        $log->debug('{sfGuardSSO} access denied');
        $this->getUser()->setFlash('message', 'Accès interdit.');
      }
      $this->getUser()->setAttribute('try_to_ident_with_sso', false, 'sf_sso_plexcel_plugin');
    }

    if ($request->isMethod('post') && count($request->getParameter($this->form->getName())))
    {
      $this->form->bind($request->getParameter($this->form->getName()));
      if ( $this->form->isValid())
      {
        $values = $this->form->getValues();
        $this->getUser()->signin($values['user'], true);

        $signinUrl = sfConfig::get('app_sf_guard_plugin_success_signin_url', $user->getReferer('@homepage'));
      }
    }
    else
    {
      if ($request->isXmlHttpRequest())
      {
        $this->getResponse()->setHeaderOnly(true);
        $this->getResponse()->setStatusCode(401);

        return sfView::NONE;
      }

      // if we have been forwarded, then the referer is the current URL
      // if not, this is the referer of the current request
      $user->setReferer($this->getContext()->getActionStack()->getSize() > 1 ? $request->getUri() : $request->getReferer());

      $module = sfConfig::get('sf_login_module');

      if ($this->getModuleName() != $module)
      {
        return $this->redirect($module.'/'.sfConfig::get('sf_login_action'));
      }

      $this->getResponse()->setStatusCode(401);
    }
    if (isset($signinUrl))
    {
      $this->redirect($signinUrl);
    }
  }

  public function executeSignout($request)
  {
    $this->getUser()->signOut();

    $signoutUrl = sfConfig::get('app_sf_guard_plugin_success_signout_url', $request->getReferer());

    $this->redirect('' != $signoutUrl ? $signoutUrl : '@homepage');
  }

  public function executeSecure()
  {
    $this->getResponse()->setStatusCode(403);
  }


}
