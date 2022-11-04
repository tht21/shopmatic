# Release Notes version - 1.68.0


**Date:** 1 September 2022



## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1794/csm-1932-p2-shopee-v2-product-creation

1. --- P2 Shopee-V2 | Product creation | Getting "Invalid attribute. repeated attribute value [100434]" error ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1932

Description: While creating the product under Laptop category we are getting the error saying “Invalid attribute. repeated attribute value [100434]“. Please check the attached screenshots and recordings.

Reason: wrong value_id when we use parameter for api add_item
Solution: Save value_id when we init category, and get it before call api add_item
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link:  

2. --- P1: shopee v2: white editing product error occurs as brand information required ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-2026

Description: I created product in MP and then imported it to CS. Later i tried to update the product, while updating im getting error as brand information required. But in CS i dont see any brand option while editing. Please find attach screen shot and screen recordings. Thanks


Reason :

Solution:  

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1795/csm-1936-p2-shopee-v2-editing-product

3. --- P2 Shopee-V2 | Editing product error "global tier option image is not equal to tier one option num" ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1936

Description: While editing & saving the imported product under “Home Appliances” Category we are getting the error saying “global tier option image is not equal to tier one option num“. Please check the attached screenshots and recording.

Reason: Wrong mapping image_id in tier variantion
Solution: Change to correct logic mapping image_id

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: 

4. --- P2: Shopee v2: Getting Undefined index: logistics contact admin to resolve it error while editing ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1898

Description: I have created new product with variants and tried to edit the product but getting this particular error. Please find attach screen shots and screen recordings. Thanks.

Reason : 

Solution:  

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: 

5. --- P2 :  Shopee v2: Product creating: Getting error while product creation ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1880

Description: When I try to create product it shows “server internal error” and if i again click on retry it shows “no variant in this product”. But product is getting created. Please find attach screen shots and screen recording. Thanks

Category: Animals & Pet Supplies > Live Animal

Sub category : Men Shoes > Boots

Reason: API response has_model with value = false. But we only check isset

Solution: Check isset() and has_model = true
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **
## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1796/csm-1918-p2-parameter-is-not-match-the

6. --- P2 Parameter is not match the constraints, You are not in the whitelist to add images in description, can only upload plain text. ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1918

Description:when update stock in https://preprod.combinesell.com/dashboard/products/1103-steve0901/edit we have this error
Reason: Only whitelable seller can use description_info field.

Solution: use description_type = normal and use description field.

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1797/csm-1960-p1-shopee-v2-create-product

7. --- P1 Shopee V2 - Create Product: Getting error of "invalid logistic info , channel_id:10088 (fulfillment channel is not allowed)" ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1960

Description:I tried to create product with variants but got this error "invalid logistic info , channel_id:10088 (fulfillment channel is not allowed)" Please find attach screen shots and screen recordings. Thanks.
Reason: Show all the logistics has contain mask_channel_id! = 0.
Solution: Filter sellers can only choose channels with enable=true and mask_channel_id=0

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1798/csm-1866-p1-shopify-it-is-showing-default

8. ---P1 Shopify | It is Showing "Default Title" under product name in Shopify ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1866
Description:After creating the product successfully from CS, if I click on that product for editing in individual product page it is showing “Default Title” under product name. Please check the attached recording.
Reason: Shopify returns title instead of name for both main and variants. please use title instead of name.
Solution: Because in Shopify parameter doesn’t have title, we just push option1 and Shopify send it back into title variant

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **


## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1799/bugfix-csm-1314

9. ---P1 horizon - Ares Marketing ordersyncJob fail for Woocommerce in Prod---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1314
Description:After creating the product successfully from CS, if I click on that product for editing in individual product page it is showing “Default Title” under product name. Please check the attached recording.
Reason: Missing 2 status ready-pickup and delivered

Solution: Delivered (fulfilment), paid (financial), ready for Pick Up (fulfilment), paid (financial)

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1800/csm-1982-p1-woocommerce-import-is-getting

10. ---P1 Woocommerce | Import is getting failed "Trying to get property 'main_image' of non-object"---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1982
Description:After creating the product successfully from CS, if I click on that product for editing in individual product page it is showing “Default Title” under product name. Please check the attached recording.
Reason: Some product image does not link with exist product.


 

Solution: If image is not linked to any product. Don't need to do anything.

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1801/csm-1715-exception-shopify-has-different

11. ---P1 | Exception: Shopify has different payment status---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1715
Description:Exception: Shopify has different payment status|"authorized" in /var/www/html/combinesell/app/Integrations/Shopify/OrderAdapter.php:281

While placing the order and cancelling. Please check the attached screenshots.
Reason: Shopify is missing payment status authorized

Solution: Add payment status authorized

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1802/bugfix-csm-1856

12. ---P1 horizon - Shopify has different payment status|"partially_paid"---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1856
Description:https://beta.combinesell.com/horizon/failed/575696090
Reason: Shopify is missing payment status partially_paid

Solution: Add payment status partially_paid

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **
