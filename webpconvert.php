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
   * The WebP Convert Joomla! system plugin.
   */
  final class plgSystemWebPConvert extends CMSPlugin {
    /**
     * A reference to Joomla's application instance.
     *
     * @var  \Joomla\CMS\Application\CMSApplication
     */
    protected $app;
    protected $webp_support = false;

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

  public function onAfterInitialise() {
    //Ensure we are working with the front end
    if ($this->app->issite() === false) {
      return false;
    }
    //Check to see if browser can accept webp image formats
    if (\strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false) {
      $this->webp_support = true;
    }
  }

  public function onAfterRender() {
    //Check the WebP support flag
    if (!$this->webp_support) {
      return false;
    }
    //Check to see if PHP can convert image to webp
    if (!$this->webpAllowed()) {
      return false;
    }
    //Get the body from Joomla
    $body = $this->parseDocumentBody($this->app->getBody());
    //Ensure the body was successfully parsed
    if ($body instanceof \SimpleXMLElement) {
      //Convert images to WebP and update body
      $body = $this->addWebPToBody($body);
      //Set the body
      $this->app->setBody($body);
    }
  }
  
  protected function webpAllowed(): bool {
    //Check to see if imagewebp function exists
    if (\function_exists('imagewebp')) {
      return true;
    } else {
      return false;
    }
  }

  protected function addWebPToBody(\SimpleXMLElement $body): string {

  }

  /**
   * Attempt to parse the provided HTML document body into a SimpleXMLElement.
   *
   * If the provided document body is non-null and non-empty, this method will
   * attempt to silently parse it using `DOMDocument::loadHTML()`. Error
   * reporting is disabled to prevent overflow of `stderr` in FPM-based hosting
   * environments.
   *
   * Once the `DOMDocument` instance has successfully parsed the document body,
   * it is then converted to a `SimpleXMLElement` instance so that XPath is
   * supported.
   *
   * @param   string            $body  An HTML document body to be parsed.
   *
   * @return  SimpleXMLElement         `SimpleXMLElement` instance on success,
   *                                   `NULL` on failure.
   */
  protected function parseDocumentBody(?string $body): ?\SimpleXMLElement {
    // Create a DOMDocument instance to facilitate parsing the document body and
    // subsequent conversion to a SimpleXMLElement instance
    $document = new \DOMDocument();
    // Ensure that the document body is a non-empty string before parsing it
    if (\is_string($body) && \strlen($body) > 0) {
      // Configure libxml to use its internal logging mechanism and preserve the
      // current libxml logging preference for later restoration
      $logging = \libxml_use_internal_errors(true);
      // Attempt to parse the document body for conversion
      if ($document->loadHTML($body) === TRUE) {
        // Attempt to import the DOMDocument tree into a SimpleXMLElement
        // instance (so that XPath can be used)
        $document = \simplexml_import_dom($document);
      }
      // Restore the previous logging preference for libxml
      \libxml_use_internal_errors($logging);
    }
    // Check if the document body was parsed and converted successfully
    return $document instanceof \SimpleXMLElement ? $document : NULL;
  }  
}
