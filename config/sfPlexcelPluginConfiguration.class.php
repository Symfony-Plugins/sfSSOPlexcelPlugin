<?php

/**
 * sfPlexcelPlugin configuration.
 * 
 * @package     sfPlexcelPlugin
 * @subpackage  config
 * @author      Pierre Cahard <pcahard@gmail.com>
 * @version     SVN: $Id: PluginConfiguration.class.php 12628 2008-11-04 14:43:36Z Kris.Wallsmith $
 */
class sfPlexcelPluginConfiguration extends sfPluginConfiguration
{
  /**
   * @see sfPluginConfiguration
   */
  public function initialize()
  {
    if(sfConfig::get('sf_environment') != 'test')
    {
      if (!extension_loaded('plexcel'))
      {
        throw new sfException("This plugin required plexcel extension, check the manual.");
      }
      if(!in_array('sfDoctrineGuardPlugin',$this->configuration->getPlugins()))
      {
        throw new sfException ("This plugin required sfDoctrineGuardPlugin.");
      }
    }
  }
}
