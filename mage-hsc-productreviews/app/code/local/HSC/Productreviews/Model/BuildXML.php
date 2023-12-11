<?php
//// Load Magento's initialization script
//require_once('app/Mage.php');
//Mage::app();

class HSC_Productreviews_Model_BuildXML extends Mage_Core_Model_Abstract {


    public function buildXML() {

        echo "hello";
//        return;

        // Create a new sitemap object
        $sitemap = new Mage_Sitemap_Model_Sitemap();

// Set the path where you want to save the sitemap file
        $sitemap->setPath(Mage::getBaseDir('media') . DS . 'sitemap');

// Add URLs with custom elements (e.g., image tags)
        $urls = array(
            array(
                'url' => 'https://example.com/product1.html',
                'images' => array(
                    array('loc' => 'https://example.com/image1.jpg'),
                    array('loc' => 'https://example.com/image2.jpg'),
                ),
            ),
            // Add more URLs as needed
        );

        foreach ($urls as $urlData) {
            $url = new Varien_Object(array(
                'loc' => $urlData['url'],
                'images' => $urlData['images'],
            ));
            $sitemap->addItem($url);
        }

// Generate the sitemap
        $sitemap->generateXml();

// Update the sitemap index
        $sitemap->updateSitemap();

// Output a success message
        echo "Custom sitemap generated successfully.";
    }
}