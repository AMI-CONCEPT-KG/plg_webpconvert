<?php
  /**
   * @copyright  Copyright (C) Travis Risner. All rights reserved.
   * @license    GNU Lesser General Public License version 3 or later.
   */

  // Prevent unauthorized access to this file outside of the context of a
  // Joomla application
  defined('_JEXEC') or die;

  use \Joomla\CMS\Factory;
  use \Joomla\CMS\Plugin\CMSPlugin;

  /**
   * The HTTP/2 Push automated Joomla! system plugin.
   */
  final class plgSystemWebPConvert extends CMSPlugin {
    /**
     * A reference to Joomla's application instance.
     *
     * @var  \Joomla\CMS\Application\CMSApplication
     */
    protected $app;

    /**
     * Fetches a reference to Joomla's application instance and calls the
     * constructor of the parent class.
     */
    public function __construct(&$subject, $config = array()) {
      // Fetch a reference to Joomla's application instance
      $this->app = Factory::getApplication();
      // Call the parent class constructor to finish initializing the plugin
      parent::__construct($subject, $config);
  }
}
