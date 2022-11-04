# Release Notes version - 1.69.0


**Date:** 7 September 2022



## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1831/csm-2025-p1-wintertime-lazada-shopee-qoo10

1. --- P1 Wintertime - Lazada/Shopee/Qoo10 Product and Inventory Issue, Unlinking Products ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-2025

Description: to be released on 1 sep 

When an import is done for Lazada (wecare@wintertime.com), we encounter product alerts here
I see three marketplaces here in the products page, but when I search in the inventory page I see that the SKU is linked to Lazada marketplace only
What to do
fix the inventory and make sure it is connected to all 3 marketplaces
check why there are 2 products instead of 1 product linked to 3 mps in the products page - what is the logic behind linking products together? I see that both products have the same number of variants and same SKUs

Qoo10 API key: 

BhiQeRsAhG2AYWE0uOqIIm417o_g_2_ZoFJwSLdFJmwkKto_g_3_

Reason: When we create new variant we don’t update product_variant_id in old product listing, so inventory for new variants is not created
Solution: Update product listing 
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link:  https://bitbucket.org/combinesell/combinesell/pull-requests/1832/csm-2060-p1-horizon-shopify-order-sync

2. --- P1 Horizon - Shopify Order Sync Issue ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-2060

Description: order sync happening for a lot of shopify clients


Reason: Order job is scheduled it has to run only in 1 server not multiple servers.
Solution: Change OrdersSync job to run only 1 server
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1833/csm-2065-p1-s-l-unable-to-update-bulk

3. --- P1 S&L - Unable to update bulk inventory file ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2065

Description: Both files have been uploaded on 29 Aug but not all inventories have been updated. 

Reason: when we import inventory, some product got this error
Argument 1 passed to App\Integrations\AbstractProductAdapter::handleProduct() must be an instance of App\Integrations\TransformedProduct, bool given, called in /var/www/html/combinesell/app/Integrations/Qoo10/ProductAdapter.php on line 81
=> Because the error above causes some Sku to be updated and others not, because error on give up the loop when update SKU and the things I are going to process is put validation to them we are not a error more
Solution: Change some code

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

4. --- P1 Shopify | Horizon exception "Illuminate\Queue\MaxAttemptsExceededException" ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1991

Description: Related to Shopify we are getting the Exception error in Horizon pre-prod saying “Illuminate\Queue\MaxAttemptsExceededException“. Please check the attached screenshot.

Reason: Taking to long when sync order
Solution: Change time out to 5400 seconds

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **
