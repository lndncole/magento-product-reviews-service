<?php
require_once(Mage::getModuleDir('controllers','Mage_Review') . '/ProductController.php');

class HSC_Productreviews_ProductController extends Mage_Review_ProductController
{
    /**
     * Crops POST values
     * @param array $reviewData
     * @return array
     */
    protected function _cropReviewData(array $reviewData)
    {
        $croppedValues = array();
        $allowedKeys = array_fill_keys(array('detail', 'title', 'nickname', 'order_number'), true);

        foreach ($reviewData as $key => $value) {
            if (isset($allowedKeys[$key])) {
                $croppedValues[$key] = $value;
            }
        }
        
        return $croppedValues;
    }
}