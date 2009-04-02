<?php

class BaseSSOPlexcelForm extends BasesfGuardFormSignin
{
  public function setup()
  {
    parent::setup();

    $this->widgetSchema['sso_login']    = new sfWidgetFormInputHidden();
    $this->validatorSchema['sso_login'] = new sfValidatorBoolean();

    // change the default Post validator
    $this->validatorSchema->setPostValidator(new sfSSOValidatorUser());
  }
}
