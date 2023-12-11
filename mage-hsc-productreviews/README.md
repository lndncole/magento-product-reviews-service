# mage-hsc-productreviews
Mage-HSC-Multisite is a Magento module that is designed to send product review solicitation emails to customers who've made a purchase from a Magento site.
The module takes advantage of Magento's built-in email queing system in so that email sends will be throttled. This is to avoid spam-like send behavior.

# Background Information
This module was built to solicit real product reviews from our customers that can be recorded for moderation and displayed on the site. 
We can associate and display on site, an aggregate star rating for a product after compiling reviews.
The product review email can offer an incentive for review, like a discount code.

# Module Configuration
The module can be configured to:
- Run only in a "dev", "local" or production environment 
- Set email addresses that will rceive test emails (used mostly in development)
- Set email addresses that will receive a receipt of all emails that received a product review solicitation
- Turn the module on or off on a store by store basis 
- Set the delay of email sends between today's date (when the module runs) and the date of the delivery confirmation 

# Current Module Specifications
The module is set up to be sent to any customer who makes a purchase from a Magento site, that does not have a business account with HC Brands.
The cron which runs daily to trigger the module, is set to kick-off at 6:00am.

## NOTICE
This module contains the settings/configurations for "Marketing Site Components". Marketing Site Components is a section of the configuration settings that marketing uses for marketing related actions to affect marketing functions related but not limited to:
- Product Review Solicitation Settings
- Gift Note Settings
- Frequently Purchased Items Settings

# Testing
To test, call the sendReviewEmailAction by executing a URL, such as: https://dev.simplystamps.com/productreviews/index/sendReviewEmail. Simply Stamps can be replaced with any of our magento stores. This action will fire off the emails and send them to whatever is configured in the HSC > Product Reviews configuration section.

