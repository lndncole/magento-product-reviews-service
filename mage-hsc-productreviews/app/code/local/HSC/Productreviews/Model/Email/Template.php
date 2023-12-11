<?php

class HSC_Productreviews_Model_Email_Template extends Aschroder_SMTPPro_Model_Email_Template {

    public $isProductReviewsEnabled;
    public $isNpsSurveyEnabled;
    public $isInDevelopmentMode;
    public $isInLocalEnvironment;
    public $internalEmailRecipients;
    public $testEmailRecipient;
    //Initialize variable that will hold the emails of all marketing email recipients, used as internal receipt of marketing email recipients
    public $vars_InternalEmail_ReceiptOfSends = array('customerEmails' => '');

    /**
     * @return void
     */
    public function __construct()
    {
        $this->isProductReviewsEnabled = Mage::getStoreConfig('hsc_productreviews_settings/hsc_product_reviews/enable_product_reviews');

        $this->isNpsSurveyEnabled = Mage::getStoreConfig('hsc_productreviews_settings/hsc_product_reviews/enable_nps_survey');

        $this->isInDevelopmentMode = Mage::getStoreConfig('hsc_productreviews_settings/hsc_product_reviews/is_development');

        $this->isInLocalEnvironment = Mage::getStoreConfig('hsc_productreviews_settings/hsc_product_reviews/is_local');

        if ($this->isInDevelopmentMode || $this->isInLocalEnvironment) {
            $this->testEmailRecipient = Mage::getStoreConfig('hsc_productreviews_settings/hsc_product_reviews/development_email_address');
        }

        $this->internalEmailRecipients = array_filter(array_map('trim', explode(",", Mage::getStoreConfig('hsc_productreviews_settings/hsc_product_reviews/internal_email_addresses')))) != '' ? array_filter(array_map('trim', explode(",", Mage::getStoreConfig('hsc_productreviews_settings/hsc_product_reviews/internal_email_addresses')))) : "lmetcalf@hcbrands.com";
    }

    /**
     * @return void
     */
    public function sendReviewEmail()
    {
        //Set text to go in to internal receipt email
        $internalReceiptEmailText = "Product Review Email Receipt";
        //Declare which type of email will be sent
        $emailType = "Review";

        //Send product review email and internal receipt email
        $this->compileAndSendProductReviewEmail($internalReceiptEmailText, $emailType);
    }

    /**
     * @return void
     */
    public function sendReviewReminderEmail()
    {
        //Set text to go in to internal receipt email
        $internalReceiptEmailText = "Review Reminder Email Receipt";
        //Declare which type of email will be sent
        $emailType = "ReviewReminder";

        //Send product review email and internal receipt email
        $this->compileAndSendProductReviewEmail($internalReceiptEmailText, $emailType);
    }

    /**
     * @return void
     * @throws Mage_Core_Exception
     */
    public function sendNPSSurvey()
    {
        if(!$this->isNpsSurveyEnabled) {
            echo "NPS Surveys are Disabled";
            return;
        }

        //Initialize variables that will be passed in to the NPS Survey email
        $vars = array('customerName' => '', 'store' => '', 'emailHeaderImageUrl' => '', 'storeDomain' => '', 'customerEmail' => '', 'orderNumber' => '', 'existingCustomer' => '', 'fulfillmentLocation' => '');

        //Initialize variable that will hold the emails of all NPS Survey email recipients, used to send to internal recipients
        $this->vars_InternalEmail_ReceiptOfSends['customerEmails'] .= '<span> NPS Survey Email Receipt </span><br>';

        //If in local environment, use mock data, otherwise use data from the query
        $customerInfo = $this->isInLocalEnvironment ? [["customer_email" => "cartoonify@portfoliopet.com", "site" => "904 Custom English", "customer_firstname" => "Jacob", "customer_lastname" => "Gephart", "order_number" => "65046338", "store_id" => "5"], ["customer_email" => "p3fleming@yahoo.com", "site" => "All State Notary Supplies English", "customer_firstname" => "Paula", "customer_lastname" => "Fleming", "order_number" => "75021907", "store_id" => "10"]] : $this->getCustomerInfoForDelivery("NPSSurvey");

        //Loop through customer information that will be set inside the NPS Survey email
        foreach ($customerInfo as $info) {
            //Set store name, store ID and order number based on $customerInfo array
            $storeName = $info["site"];
            $storeId = $info['store_id'];
            $fulfillmentLocation = $info['fulfillment_location'];

            //Check if customer has made purchases in the past. If so, designate them as an existing customer
            $customerID = Mage::getModel("customer/customer")->loadByEmail($info['customer_email'])->getId();
            $orderCount = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('customer_id', $customerID)->count();

            //If they're an existing customer, designate that with a "1", else "0"
            $vars['existingCustomer'] = $orderCount > 1 ? 1 : 0;
            //Store customer email and order number to variables that will get passed to the transactional email template
            $vars['customerEmail'] = $info['customer_email'];
            $vars['orderNumber'] = $info["order_number"];
            //Customer name used to address customer in the email
            $vars['customerName'] = $info["customer_firstname"] ?: '';
            //Remove the word "english" from store name to be used in sender, trim to remove white space
            $vars['store'] = trim(str_replace('English', '', $storeName));
            //Set the domain of the store URL address
            $storeDomain = str_replace(' ', '', $vars['store']);
            $vars['storeDomain'] = $storeDomain;
            //Set logo image to be passed to NPS Survey email as a variable
            $vars['emailHeaderImageUrl'] = $this->_getLogoUrl($storeId);
            //Indicate fulfillment location
            $vars['fulfillmentLocation'] = $fulfillmentLocation;

            //Set template ID of transactional email
            $templateId = 32;
            //Set sender based on store name / url
            $sender = array('name' => trim(str_replace('English', '', $storeName)), 'email' => 'feedback@' . $storeDomain . '.com');

            //Set transactional email recipient and send email
            $this->setRecipientAndSendEmail($info, $templateId, $sender, $vars, $storeId);
        }

        //Send email to internal recipients who will get the receipt of the NPS Survey emails of the day
        //Do this only as long as 1 or more emails went out ($vars_InternalEmail_ReceiptOfSends has an email address)
        if (strpos($this->vars_InternalEmail_ReceiptOfSends['customerEmails'], '@')) {
            $this->sendInternalReceiptEmail($this->vars_InternalEmail_ReceiptOfSends);
        }
    }

    /**
     * @param string $emailType
     * @return array
     */
    public function getCustomerInfoForDelivery(string $emailType): array
    {
        //Initialize read only connection to the Magento Database
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');

        //If in development mode or local environment, use a limit on query results and change days to delay variable
        if ($this->isInDevelopmentMode || $this->isInLocalEnvironment) {
            $sendLimit = Mage::getStoreConfig('hsc_productreviews_settings/hsc_product_reviews/limit_of_productreview_emails');
            if ($sendLimit == '' || $sendLimit == null) {
                $sendLimit = 2;
            }
            $limiter = 'LIMIT ' . $sendLimit;
        } else {
            $limiter = '';
        }

        //Query magento database. Get product id, website of purchase origin, customer email, customer first name, customer last name
        //Get from tables that include sales records, group ids, delivery confirmation details, store details
        //Only get records where delivery confirmation is (days_delay_to_send) difference from today
        //Only get records where the customer belongs to any of the group ID's: 0, 1, 3, 4, 5 and 7

        //Initialize query variable
        $query = '';

        //Get days to delay send post delivery confirmation for product review and NPS survey
        $daysToDelay = Mage::getStoreConfig('hsc_productreviews_settings/hsc_product_reviews/days_delay_to_send');
        $npsSurveyDelay = Mage::getStoreConfig('hsc_productreviews_settings/hsc_product_reviews/days_delay_to_send_nps');

        //Based on which email type is being sent (Review, Review Reminder, NPS Survey), run the appropriate query to fetch results
        if ($emailType == "Review") {
            $query = "
                SELECT DISTINCT
                GROUP_CONCAT(sfoi.product_id) AS product_ids,
                cs.`name` AS site,
                so.customer_email AS customer_email,
                so.customer_firstname AS customer_firstname,
                so.customer_lastname AS customer_lastname,
                so.increment_id AS order_number,
                so.store_id AS store_id
                FROM hsc_magento.sales_flat_order_item sfoi
                    LEFT JOIN hsc_magento.sales_flat_order so ON sfoi.order_id = so.entity_id
                    LEFT JOIN customer_entity ON so.customer_id = customer_entity.entity_id
                    INNER JOIN hsc_multisite_downgrade_log dl ON so.increment_id = dl.po_number
                    INNER JOIN core_store cs ON so.store_id = cs.store_id
                WHERE customer_entity.group_id IN (0,1,3,4,5,7)
                AND DATEDIFF(CURDATE(), dl.actual_delivery_date) = $daysToDelay " . " " . "
                GROUP BY so.increment_id " . " " . "
                $limiter";
        } elseif ($emailType == "ReviewReminder") {
            //Send review reminder email 7 days after original review email
            $daysToDelay += 7;
            $query = "
                SELECT DISTINCT
                GROUP_CONCAT(sfoi.product_id) AS product_ids,
                cs.`name` AS site,
                so.customer_email AS customer_email,
                so.customer_firstname AS customer_firstname,
                so.customer_lastname AS customer_lastname,
                so.increment_id AS order_number,
                so.store_id AS store_id
                FROM hsc_magento.sales_flat_order_item sfoi
                    LEFT JOIN hsc_magento.sales_flat_order so ON sfoi.order_id = so.entity_id
                    LEFT JOIN customer_entity ON so.customer_id = customer_entity.entity_id
                    INNER JOIN hsc_multisite_downgrade_log dl ON so.increment_id = dl.po_number
                    INNER JOIN core_store cs ON so.store_id = cs.store_id
                    LEFT JOIN review_detail rd ON so.increment_id = rd.order_number
                WHERE customer_entity.group_id IN (0,1,3,4,5,7)
                AND rd.order_number IS NULL  
                AND DATEDIFF(CURDATE(), dl.actual_delivery_date) = $daysToDelay " . " " . "
                GROUP BY so.increment_id " . " " . "
                $limiter";
        } elseif ($emailType == "NPSSurvey") {
            $daysToDelay = $npsSurveyDelay;
            $query = "
                SELECT DISTINCT
                cs.`name` AS site,
                so.customer_email AS customer_email,
                so.customer_firstname AS customer_firstname,
                so.customer_lastname AS customer_lastname,
                so.increment_id AS order_number,
                so.store_id AS store_id,
                dl.chosen_location_code AS fulfillment_location
                FROM hsc_magento.sales_flat_order_item sfoi
                    LEFT JOIN hsc_magento.sales_flat_order so ON sfoi.order_id = so.entity_id
                    LEFT JOIN customer_entity ON so.customer_id = customer_entity.entity_id
                    INNER JOIN hsc_multisite_downgrade_log dl ON so.increment_id = dl.po_number
                    INNER JOIN core_store cs ON so.store_id = cs.store_id
                WHERE customer_entity.group_id IN (0,1,3,4,5,7)
                AND DATEDIFF(CURDATE(), dl.actual_delivery_date) = $daysToDelay $limiter";
        }

        //Fetch results from query and return them
        return $readConnection->fetchAll($query);
    }

    /**
     * @param string $internalReceiptEmailText
     * @param string $emailType
     * @return void
     */
    public function compileAndSendProductReviewEmail(string $internalReceiptEmailText, string $emailType)
    {
        //If product reviews are not enabled, don't run the function
        if (!$this->isProductReviewsEnabled) {
            echo "Product Reviews Are Disabled";
            return;
        }

        //Initialize variables that will be passed in to the Product Review Solicitation email
        $vars = array('customerName' => '', 'productInfo' => '', 'store' => '', 'emailHeaderImageUrl' => '', 'storeDomain' => '');

        $this->vars_InternalEmail_ReceiptOfSends['customerEmails'] .= '<span> ' . $internalReceiptEmailText . ' </span><br>';

        //Initialize subdomain variable to be used in Product Review Solicitation email
        $emailLinkSubDomain = '';

        //If we're in development mode change email subdomain to "dev."
        if ($this->isInDevelopmentMode) {
            $emailLinkSubDomain = "dev.";
        }
        //If we're in local environment, again use test recipient for email sends, change sub domain to "local."
        if ($this->isInLocalEnvironment) {
            $emailLinkSubDomain = "local.";
        }

        //If in local environment, use mock data, otherwise use data from the query
        $customerInfo = $this->isInLocalEnvironment ? [["product_ids" => "15261", "customer_email" => "one_beautiful_creation@yahoo.com", "site" => "Simply Stamps English", "customer_firstname" => "Blake", "customer_lastname" => "Morales", "store_id" => "5", "order_number" => "007"], ["product_ids" => "12222", "customer_email" => "drewmackay@gmail.com", "site" => "904 Custom English", "customer_firstname" => "Drew", "customer_lastname" => "Mackay", "store_id" => "7", "order_number" => "008"]] : $this->getCustomerInfoForDelivery($emailType);

        //Loop through customer information that will be set inside Product Review Solicitation email
        foreach ($customerInfo as $info) {
            //Set store name, store ID and order number based on $customerInfo array
            $storeName = $info["site"];
            $orderNumber = $info["order_number"];
            $storeId = $info['store_id'];

            //Check on the store level if reviews have been disabled, if they have, skip the store and continue with other stores
            $isProductReviewsEnabledForStore = Mage::getStoreConfig('hsc_productreviews_settings/hsc_product_reviews/enable_product_reviews', $storeId);
            if (!$isProductReviewsEnabledForStore) {
                continue;
            }

            //Customer name used to address customer in the email
            $vars['customerName'] = $info["customer_firstname"] ?: '';
            //Remove the word "english" from store name to be used in sender, trim to remove white space
            $vars['store'] = trim(str_replace('English', '', $storeName));
            //Set logo to be used in email to customer
            $vars['emailHeaderImageUrl'] = $this->_getLogoUrl($storeId);
            //Set the domain of the store URL address
            $storeDomain = str_replace(' ', '', $vars['store']);
            $vars['storeDomain'] = $storeDomain;

            //Initialize product info variable used to display products in the Product Review Solicitation email
            $vars['productInfo'] = '';

            //Set the product info variable that builds the email, here. We can have one Product Review Solicitation email per transaction. If there are multiple products in the transaction then we'll make sure to include them all in one email
            if ($info["product_ids"]) {

                //Initialize check-for-duplicates storage array
                $duplicateCheck = [];
                foreach (explode(",", $info["product_ids"]) as $product) {

                    //Check to see if product ID exists in review solicitation, if it doesn't add it, otherwise do nothing
                    if (!in_array($product, $duplicateCheck)) {
                        $duplicateCheck[] = $product;

                        $productInfo = Mage::getModel('catalog/product')->load($product);
                        $productUrl = Mage::helper('catalog/image')->init($productInfo, 'thumbnail')->__toString();

                        $vars['productInfo'] .= '
<!--[if IE]><div class="ie-container"><![endif]-->
<!--[if mso]><div class="mso-container"><![endif]-->
<table style="border-collapse: collapse;table-layout: fixed;border-spacing: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;vertical-align: top;min-width: 320px;Margin: 0 auto;background-color: #e7e7e7;width:100%" cellpadding="0" cellspacing="0">
    <tbody>
        <tr style="vertical-align: top">
            <td style="word-break: break-word;border-collapse: collapse !important;vertical-align: top">
                <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="background-color: #e7e7e7;"><![endif]-->
                <a href="https://' . $emailLinkSubDomain . $storeDomain . '.com/productreviews/?id=' . $product . '&onum=' . $orderNumber . '&utm_source=reviews&utm_medium=email&utm_campaign=product-reviews" target="_blank">
                    <div class="u-row-container" style="padding: 0px;background-color: transparent">
                        <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 500px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
                            <div style="border-collapse: collapse;display: table;width: 100%;background-color: transparent;">
                                <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: transparent;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:500px;"><tr style="background-color: transparent;"><![endif]-->
                                <!--[if (mso)|(IE)]><td align="center" width="500" style="width: 500px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;" valign="top"><![endif]-->
                                <div class="u-col u-col-100" style="max-width: 320px;min-width: 500px;display: table-cell;vertical-align: top;">
                                    <div style="width: 100% !important;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;">  
                                        <!--[if (!mso)&(!IE)]><!-->
                                        <div style="padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;"><!--<![endif]-->
                                            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                                <tbody>
                                                    <tr>
                                                        <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:arial,helvetica,sans-serif;" align="left">
                                                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                                <tr>
                                                                    <td style="padding-right: 0px;padding-left: 0px;" align="center">
                                                                        <img align="center" border="0" src="' . $productUrl . '" alt="" title="" style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;clear: both;display: inline-block !important;border: none;height: auto;float: none;width: 59%;max-width: 283.2px;" width="283.2"/>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                                <tbody>
                                                    <tr>
                                                        <td style="overflow-wrap:break-word;word-break:break-word;padding:4px;font-family:arial,helvetica,sans-serif;" align="left">
                                                            <div style="line-height: 110%; text-align: center; word-wrap: break-word;">
                                                                <p style="font-size: 14px; line-height: 110%;">
                                                                    <span style="font-size: 20px; line-height: 28px;">' . $productInfo->getName() . '</span>
                                                                </p>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                                <tbody>
                                                    <tr>
                                                        <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:arial,helvetica,sans-serif;" align="left">
                                                            <div align="center">
                                            <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-spacing: 0; border-collapse: collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;font-family:arial,helvetica,sans-serif;">
                                                <tr>
                                                    <td style="font-family:arial,helvetica,sans-serif;" align="center">
                                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="https://' . $emailLinkSubDomain . $storeDomain . '.com/productreviews/?id=' . $product . '&onum=' . $orderNumber . '&utm_source=reviews&utm_medium=email&utm_campaign=product-reviews" style="height:35px; v-text-anchor:middle; width:312px;" arcsize="16%" stroke="f" fillcolor="#233e94"><w:anchorlock/>
                                                            <center style="color:#FFFFFF;font-family:arial,helvetica,sans-serif;"><![endif]-->
                                                                <a href="https://' . $emailLinkSubDomain . $storeDomain . '.com/productreviews/?id=' . $product . '&onum=' . $orderNumber . '&utm_source=reviews&utm_medium=email&utm_campaign=product-reviews" target="_blank" 
                                                                    style="
                                                                        box-sizing: border-box;
                                                                        display: inline-block;
                                                                        font-family:arial,helvetica,sans-serif;
                                                                        text-decoration: none;
                                                                        -webkit-text-size-adjust: none;
                                                                        text-align: center;
                                                                        color: #FFFFFF; 
                                                                        background-color: #233e94; 
                                                                        border-radius: 8px;
                                                                        -webkit-border-radius: 8px; 
                                                                        -moz-border-radius: 8px; 
                                                                        width:65%; 
                                                                        max-width:100%; 
                                                                        overflow-wrap: break-word; 
                                                                        word-break: break-word; 
                                                                        word-wrap:break-word; 
                                                                        mso-border-alt: none;">
                                                                    <span style="display:block;padding:10px 20px;line-height:120%;">
                                                                        <span style="font-size: 20px; line-height: 30px;">
                                                                            Review Product Now
                                                                        </span>
                                                                        <br />
                                                                    </span>
                                                                </a>
                                                        <!--[if mso]></center>
                                                        </v:roundrect>
                                                    </td>
                                                </tr>
                                            </table><![endif]-->
                                                            </div>
                                            
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
                                    </div>
                                </div>
                                <!--[if (mso)|(IE)]></td><![endif]-->
                                <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
                            </div>
                        </div>
                    </div>
                </a>
                <!--[if (mso)|(IE)]></td></tr></table><![endif]-->
            </td>
        </tr>
    </tbody>
</table>
<!--[if mso]></div><![endif]-->
<!--[if IE]></div><![endif]-->
';
                    }
                }
                //Clear duplicate check array to make way for next customer and their set of products
                $duplicateCheck = [];
            }

            //Set template ID for Product Review Email template
            $templateId = 30;
            //Set sender based on store name / url
            $sender = array('name' => trim(str_replace('English', '', $storeName)), 'email' => 'sales@' . $storeDomain . '.com');

            //Set recipient email
            $this->setRecipientAndSendEmail($info, $templateId, $sender, $vars, $storeId);
        }

        //Send email to internal recipients who will get the receipt of the Product Review Solicitation emails of the day
        //Do this only as long as 1 or more emails went out
        if (strpos($this->vars_InternalEmail_ReceiptOfSends['customerEmails'], '@')) {
            $this->sendInternalReceiptEmail($this->vars_InternalEmail_ReceiptOfSends);
        }
    }

    /**
     * @param array $info
     * @param int $templateId
     * @param array $sender
     * @param array $vars
     * @param string $storeId
     * @return void
     */
    public function setRecipientAndSendEmail(array $info, int $templateId, array $sender, array $vars, string $storeId)
    {
        //If we're in development or local mode, make sure to send emails to test recipient found in the module configuration
        $recipientEmail = $this->testEmailRecipient ?: $info['customer_email'];
        //Set recipient name
        $recipientName = $info["customer_firstname"] . " " . $info["customer_lastname"];

        //Send product review email to customer, use Same ID as store from which purchase was made
        try {
            Mage::getModel('core/email_template')->sendTransactional($templateId, $sender, $recipientEmail, $recipientName, $vars, $storeId);
        } catch (Mage_Core_Exception $e) {
            var_dump($e);
        }

        //Add recipients of Product Review Solicitation here to be sent in internal receipt email
        $this->vars_InternalEmail_ReceiptOfSends['customerEmails'] .= '<span>' . $recipientEmail . '</span><br>';
    }

    /**
     * @param array $vars
     * @return void
     */
    public function sendInternalReceiptEmail(array $vars)
    {
        try {
            //Set template ID of internal receipt email
            $internalEmailTemplateId = 31;
            $internalSender = array('name' => 'Magento Transactional', 'email' => 'MarketingEmails@simplystamps.com');
            $internalRecipientName = 'HC Brands Marketing';

            //Send to all email addresses listed in internal recipients
            foreach ($this->internalEmailRecipients as $email) {
                Mage::getModel('core/email_template')->sendTransactional($internalEmailTemplateId, $internalSender, $email, $internalRecipientName, $vars, 5);
            }

        } catch (Exception $e) {
            var_dump($e);
        }
    }
}