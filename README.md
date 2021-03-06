# EMS Online plugin for Joomla HikaShop

## About

EMS helps entrepreneurs with the best, smartest and most efficient payment systems. Both 
in your physical store and online in your webshop. With a wide range of payment methods 
you can serve every customer.

Why EMS?

Via the EMS website you can create a free test account online 24/7 and try out the online 
payment solution. EMS's online solution also offers the option of sending payment links and 
accepting QR payments.

The ideal online payment page for your webshop:
- Free test account - available online 24/7
- Wide range of payment methods
- Easy integration via a plug-in or API
- Free shopping cart plug-ins
- Payment page in the look & feel of your webshop
- Reports in the formats CAMT.053, MT940S, MT940 & CODA
- One clear dashboard for all your payment, turnover data and administration functions

Promotion promotion extended!

Choose the EMS Online Payment Solution now
and pay no subscription costs at € 9.95 throughout 2020!

Start immediately with your test account
Request it https://portal.emspay.eu/create-test-account?language=NL_NL 

Satisfied after testing?
Click on the yellow button [Begin→]
 in the test portal and
simply request your live account.
## Version number
Version v1.5.2

## Pre-requisites to install the plug-ins 
* PHP v5.4 and above
* MySQL v5.4 and above

## Installation
Manual installation of the EMS HikaShop plugin using (s)FTP

1. Upload the plugin ZIP file in HikaShop. Go to your Joomla admin environment and select ´Administrator´ > ´Extensions´ > ´Manage´ > ´Install´ > ´Upload Package File´.
2. Select ‘Extensions’ > ‘Manage’ > ‘Manage’.
3. Select the EMS Online payment methods you would like to enable.
Enable a payment method by clicking on the ‘x’ icon next to the payment method name
4. Select ‘Component’ > ‘HikaShop’ > ‘System’tab > ‘Payment Methods’ and click green button ‘New’.
5. Configure the added payment methods - tab ‘Payment Method Information’ and ‘Configuration’.
    - Set the ‘Published’ field to ‘Yes’.
    - Set Image from list.
    - Copy the API key to `API key´ field.
    - Set ‘Use cURL CA bundle’ field to ‘Yes’.
This fixes a cURL SSL Certificate issue that appears in some web-hosting environments where you do not have access to the PHP.ini file and therefore are not able to update server certificates.
    - Click ‘Save&Close’.
    - Afterpay specific configuration 
    For the payment method Afterpay there are several specific settings:
	    - Order shipped
	Configure the Order shipped status to Shipped. This ensures that if you change the status of a Afterpay order to Shopped the order is automatically captured to Afterpay for further processing.
	    - Afterpay test API key Copy the API Key of your test webshop in the Test API key field.
	When your Afterpay application was approved an extra test webshop was created for you to use in your test with Afterpay. The name of this webshop starts with ‘TEST Afterpay’.
	    - IP Address Filtering
	You can choose to offer Afterpay only to a limited set of whitelisted IP addresses. You can use this for instance when you are in the testing phase and want to make sure that Afterpay is not available yet for your customers.
	To do this enter the IP addresses that you want to whitelist, separate the addresses by a comma (“,”). The payment method Afterpay will only be presented to customers who use a whitelisted IP address.
	If you want to offer Afterpay to all your customers, you can leave the field empty.
	    - Countries availability for AfterPay:
    To allow AfterPay to be used for any other country just add its country code (in ISO 2 standard) to the "Countries available for AfterPay" field. Example: BE, NL, FR.
    If field is empty then AfterPay will be available for all countries.
    - Klarna specific configuration
    For the payment method Klarna; see all the specific settings for Afterpay.
    - In your Joomla admin environment click ‘Save’ when you have finished configuring the plugin.
6. Compatibility: Joomla 3.9.13 and HikaShop 4.2.2
7. Install languages and populate tables in the database for new languages
    - Select ‘Extensions’ > ‘Languages’ > ‘Installed’ and Install languages
    - Select ‘Component’ > ‘HikaShop’ > ‘Configuration’ > ‘Languages’ and click plus on the line line with the language
    - After that, tables for additional languages will be created. 
    - View the translation in the file that opens.
    - Edit as per your requirements.
    - Click ‘Save’.