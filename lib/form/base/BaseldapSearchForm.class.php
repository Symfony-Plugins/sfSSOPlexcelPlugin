<?php


class BaseldapSearchForm extends sfForm
{
  
  public function setup()
  {
    $this->setWidgets(array(
      'filter' => new sfWidgetFormInput()));
    
    $this->widgetSchema->setLabels(array(
      'filter' => 'Filter'));
    
    $this->setValidators(array(
      'filter' => new sfValidatorString(array('max_length' => 255, 'required' => false))));
    
    $this->widgetSchema->setNameFormat('search[%s]');
  }
  
}