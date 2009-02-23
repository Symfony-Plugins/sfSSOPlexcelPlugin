<?php

class BaseSSOPlexcelForm extends BasesfGuardFormSignin
{
  public function setup()
  {
    parent::setup();
    // change the default Post validator
    $this->validatorSchema->setPostValidator(new sfSSOValidatorUser());
  }
}