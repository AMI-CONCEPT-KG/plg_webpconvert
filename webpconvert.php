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
    protected $quality = 100;

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
      $body = $this->addWebpToBody($body);
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

  protected function addWebpToBody(\SimpleXMLElement $body): string {
    //Locate all possible images resources
    $images = $body->xpath('//img[@src]');
    $converted_images = [];
    $i = 0;
    //Loop through all images
    foreach ($images as &$image) {
      //Store the original filename
      $converted_images[$i]['original'] = (string) $image['src'];
      //Run image through conversion script
      $image = $this->processImage($image['src']);
      //Store the webp filename
      $converted_images[$i]['webp'] = $image;
      $i++;
    }
    //Convert XML Element back to string
    $body = $body->asXML();
    //Loop through the converted images array
    foreach($converted_images as $image) {
      //replace original filename with webp filename
      $body = \str_replace($image['original'], $image['webp'], $body);
    }
    return $body;
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

  protected function processImage(string $image): ?string {
    //Convert file extension to webp
    $webp_path = \substr_replace($image, 'webp', \strrpos($image, '.') +1);
    //$webp_path = \dirname($webp_path).'/webp/'.\basename($webp_path);
    //Check for the existence of a webp
    if(!$this->webpExists($webp_path)) {
      //Run the conversion
      $this->convertImage($image, $webp_path);
    }
    return $webp_path;
  }

  protected function webpExists(string $image): bool {
    //If file exists
    if(\is_file(JPATH_ROOT.'/'.$image)) {
      return true;
    } else {
      return false;
    }
  }

  protected function convertImage(string $origin, string $dest): bool {
    //Check to ensure origin file exists
    if(\is_file($origin)) {
      //Create an image resource from the origin
      $image_resource = \imagecreatefromstring(\file_get_contents($origin));
      //Return true if conversion is possible
      if(\imagewebp($image_resource, $dest, $this->quality)) {
        return true;
      } else {
        //Return false on error
        return false;
      }
    } else {
      //Return false is origin doesn't exist
      return false;
    }
  }
}
