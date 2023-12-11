//Front end API routes 
<?php

class HSC_Productreviews_IndexController extends Mage_Core_Controller_Front_Action {
    public function indexAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function sendReviewEmailAction() {
       $model = Mage::getModel('hsc_productreviews/email_template');
       $model->sendReviewEmail();
    }

    public function sendReviewReminderEmailAction() {
        $model = Mage::getModel('hsc_productreviews/email_template');
        $model->sendReviewReminderEmail();
    }

    public function sendNPSSurveyAction() {
        $model = Mage::getModel('hsc_productreviews/email_template');
        $model->sendNPSSurvey();
    }

    public function emptyCartAction() {
        $dataFromAjax = json_decode(file_get_contents('php://input'));
        $itemIdsToDeleteArray = $dataFromAjax->checkedItemIds;

        $cartHelper = Mage::helper('checkout/cart');

        foreach ($itemIdsToDeleteArray as $itemId) {
            $cartHelper->getCart()->removeItem($itemId);
        }

        $cartHelper->getCart()->save();

        echo "cart emptied.";
    }

    public function aovUpdateCartAction() {

        $dataFromAjax = json_decode(file_get_contents('php://input'));

        $request = $this->getRequest();
        $isAjax = $request->isAjax();
        if (!$isAjax) {
            $this->norouteAction();
            return;
        }

        $productIds = $dataFromAjax->productIds;
        $qty = $dataFromAjax->qty;

        $cart = Mage::helper('checkout/cart')->getCart();

        for ($i = 0; $i < count($productIds); $i++) {
            $product = Mage::getModel('catalog/product')->setStoreId(Mage::app()->getStore()->getId())->load($productIds[$i]);
            if ($product->getOptions()) {
                foreach ($product->getOptions() as $option) {
                    if ($option->getData()['option_id'] == '1227') {

                        $optionValue = array(
                            '1227' => 5861
                        );

                        $params = array(
                            'product' => $product->getId(),
                            'qty' => $qty[$i],
                            'options' => $optionValue
                        );

                        try {
                            $cart->addProduct($product, $params);
                            $cart->save();
                        } catch (Exception $e) {
                            Mage::log($e, null, "aovCartModal.log");
                        }
                    }
                }
            } else {
                try {
                    $cart->addProduct($product, $qty[$i]);
                    $cart->save();
                } catch (Exception $e) {
                    Mage::log($e, null, "aovCartModal.log");
                }
            }

        }
    }
}
