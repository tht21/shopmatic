# Release Notes version - 1.66.0


**Date:** 18 August 2022



## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1692/csm-1934-p2-shopee-v2-import-is-taking

1. --- P2 Shopee-V2 | Import is taking time. ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1934

Description: While doing the import action for Shopee-V2 it is taking too much time almost around 15minute every time. Please check the attached recording.


Reason : Since we are using sleep(8) in get Function same V1 api

Solution: using isSync to check if it is import, sync function is calling get function or not

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1693/csm-1949-p1-horizon-product-listing-sync

2. --- P1 Horizon - Product listing sync job - 'Model sku' error ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1949

Description: https://beta.combinesell.com/horizon/failed/629880285


Reason : Not check exsit variation

Solution:  Check exsit variation

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1694/p1-horizon-trying-to-get-property-enabled

3. --- P1 Horizon - trying to get property 'enabled' of non object ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1950

Description: https://beta.combinesell.com/horizon/failed/629880285


Reason : Don’t check null inventory before get properties

Solution:  Check null for properties

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1695/csm-1927-p2-shopee-v2-getting-invalid

4. --- P2 Shopee-V2 | Getting "invalid field Model.TierIndex: value '[]' must contain at least 1 elements" error ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1927

Description: While exporting the product to Shopee-MP from CS under laptop category getting an error in past export page saying “invalid field Model.TierIndex: value '[]' must contain at least 1 elements“. Please check the attached screenshots and recording.


Reason : Product 1258334 don't have any variant. On export page, user set option 1 value (but sku is the same sku of main product). In code, if sku is the same (so it’s mean default variant) then we don't init.

Solution:   Only update tier when we have model.

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1696/csm_1793-unable-to-import-lazada-products

5. --- P1 S&L Sealing - Unable to import lazada products ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1793

Description: to beworked on  for release on 28 july


three products are not getting imported. When I check for them on lazada, I see that they are under digital goods category.


we will need to cater to digital goods when importing products

opened a ticket with lazada and they mentioned that we need to change some attributes to pull in digital goods information

Reason : Because there are products without Package Weight and Package Dimensions so don’t import products because it affects the Productvariant
Solution:  at $weight,$length, $width,$height in case there is no Package Weight and Package Dimensions the default is 0
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1697/csm-1889-p1-shopify-inventory-update-issue

6. --- P1 Shopify - Inventory Update Issue ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1889

Description: from harry:

When I try to update stock, Shopify shows an increase, it is wrong with the value I update. I have a problem in the Production environment. Please watch video to check it out
Inventory | CombineSell - 3 August 2022

Reason :  sometimes the key 'available' in the $parameters variable doesn't return the correct value
Solution:  change from 'adjust.json' to 'set.json'
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1698/csm-1629-p2-horizon-unable-to-retrieve

7. --- P2 Horizon - Unable to retrieve Shopify orders ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1629

Description: to fix this issue, check the logs to see why the api fetchOrder is not returning data for the specified client

Reason :  3 servers call cronjob in the same time.
Solution:  Use overlapping.
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1699/csm-1850-p2-undefined-index-attributes

8. --- P2 Shopify product edit| "Undefined index: attributes Contact admin to resolve it" ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1850

Description: While editing the Shopify product in CS it is getting edited in CS successfully but still we are getting error “Undefined index: attributes Contact admin to resolve it“ and the edited data is not reflecting in Shopify MP. Please check the attached screenshot and recording.





Reason :  there are product variants with attributes , there are product variants without attributes
Solution:  Set default values ​​for attributes weight , weight_unit , barcode ,inventory_policy,inventory_management
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1700/csm-1667-p2-in-shopify-while-editing

9. --- P2: In Shopify while editing product error message is displayed ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1667

Description: to be worked on for release on 28 july

While editing any of the products (newly created and existing products) in Shopify in pre production environment and also in Production Environment, product is getting edited but error is showing up.





Reason :   could not update options to []
Solution:  $productParams['options'] = count($optionsParam) === 0 ? null : $optionsParam
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1701/bugfix-csm-1901-latest

10. --- P1 Nutrition Pro - Unable to create inventory for Qoo10 product after import ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1901

Description: I deleted a product on CS that is linked to 4 mps, and re-imported the product from all 4 mps. It shows up correctly linked to 4 mps in the products page, but the inventory is only linked to the other 3 mps, and not linked to Qoo10. When inventory is updated, it is not reflected in Qoo10 too. 

 

SKU affected: nspg-20051



 

error specific to this client, not everyone





Reason :   Variable $unlink dit not create product for QOO10 but links to old product
Solution:  There is no need to use the variable $unlink anymore, so delete the code that checks the condition with it.
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1702/bugfix-csm-1878

11. --- P1 Three Green Peas: Lazada Inventory Sync Error E501: Update product failed ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1878

Description: Affected SKU: QN41_PK

Affected associated SKU: Xstamper_Namestamp

 

Lazada access logs

E501: Update product failed

 

Lazada API test tool

{ "code": "501", "type": "ISP", "message": "E501: Update product failed", "detail": [ { "message": "negative sellable on inventory,entityId=9279668598,id=6639414202", "seller_sku": "QN41_PK" } ], "request_id": "2141202a16594096092596394" }

 

Why it happened

This happened as occupy quantity = 2 for this product in Lazada seller account, so CS does not allow mechant to update inventory below occupy quantity. This is not shown in seller page. 

 

What to fix

We need to call getproductitem API to check for occupy quantity, then when seller updates inventory on CS to X, we need to  send total quantity to Lazada = X + occupy quantity when we call updatepricequantity API. 

Reason :   
Solution:  
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **
