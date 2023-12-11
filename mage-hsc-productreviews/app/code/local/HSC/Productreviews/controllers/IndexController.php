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

    public function sendAttentitiveAPIAction() {
        //collect the data from the Ajax request
        //This request is coming from the front-end when a user submits their phone number (also email) via the optimonk
        //lead capture in the footer. The code for the initial request lives in the optimonk UI in the "code" section of the campaigns
        $dataFromAjax = json_decode(file_get_contents('php://input'));

        //user information
        $userinfo = $dataFromAjax->user;
        //phone
        $userPhone = $userinfo->phone;
        //email
        $userEmail = $userinfo->email;

        //API information
        $singUpSourceId = $dataFromAjax->signUpSourceId;
        $bearerToken = 'YkpuRVRxUFBhc0thblVPUzRiZFN4RE0zWnhManY2ZzVPMFl0';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.attentivemobile.com/v1/subscriptions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "user": {
                    "phone": "'. $userPhone .'",
                    "email": "'. $userEmail .'"
                },
                "signUpSourceId": "'. $singUpSourceId .'"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Access-Control-Allow-Origin: *',
                'Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token',
                "Authorization: Bearer $bearerToken"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;

        Mage::log($response, null, "AttentiveAPICall.log");

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

    public function buildXMLAction() {
        $model = Mage::getModel('hsc_productreviews/buildXML');
        $model->buildXML();
    }
}