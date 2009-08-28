<?php

class BaseSSOPlexcelForm extends BasesfGuardFormSignin
{
  public function configure()
  {
    parent::configure();

    $this->widgetSchema['sso_login']    = new sfWidgetFormInputHidden();
    $this->validatorSchema['sso_login'] = new sfValidatorBoolean();

    // change the default Post validator
    $this->validatorSchema->setPostValidator(new sfSSOValidatorUser());
  }
}
