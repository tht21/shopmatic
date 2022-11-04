# Release Notes version - 1.67.0


**Date:** 25 August 2022



## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1757/csm-1909-add-id-listing-to-debug-csm-1909

1. --- P1 Ares Marketing - Woocommerce inventory unlinking issue ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1909

Description: Problem

The inventory for SKU 1051steameyemasklavender1sachet and SKU 1490steameyemaskcassia1piece under associated SKU 1050 are linked to 3 woocommerce integrations. 

Sometimes when the client updates the inventory individually, the SKUs face unlinking issues. Product alerts show up like this and there is no listing under the inventories. 

The client will need to delete the products on CS, and reimport them to link the inventories to the 3 woocommerce mps again.

Reason : First, I spend a lot of time debugging because the given account information is not correct ( The correct account is inventory@aresmarketing.com.sg - Password1! Evidence:

Solution: Temporary i added variable $listing->id into variable $alertMessage to debug this issue in the future. 

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link:  https://bitbucket.org/combinesell/combinesell/pull-requests/1760/csm-1946-p2-lazada-export-error

2. --- P2 Lazada | Export error "THD_IC_ERR_F_IC_INFRA_PRODUCT_036" ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1946

Description: While exporting the product to Lazada-MP from CS under Shoes Category we are getting the error in the past import page saying “THD_IC_ERR_F_IC_INFRA_PRODUCT_036:Insert sku outer id relation failed, outerId already exists, sell ID:100,051,644,sku ID:14,418,969,224,outer ID:W01-R24 2,serverIP:33.42.123.8“. Please check the attached screenshots & recordi


Reason : Need to clearly show the cause of the error

Solution:  Add new message when we face this error

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1761/csm-1847

3. --- Standardise marketplace identifiers on the platform - delete products function ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1847

Description: Right now, it is confusing when merchants are trying to filter/ choose marketplace products etc if they have multiple mp accounts from the same mp, as we are not indicating the region the mp account is from.
To Implement
We should indicate the marketplace regions, names, and email addresses in 1 place that does not show them
It needs to be standardised as well
Area to Implement It
Delete function for single products
How to get to this page: go to products > all products > click on 1 product > edit > delete
Mp and region should be added right before the email address mentioned, while email address should be in a bracket.

Reason :

Solution:  

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1763/csm-1861-p2-three-green-peas-unable-to

4. --- P2 Three Green Peas - Unable to update lazada inventory due to Invalid Request Format ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1861

Description: need to release as a hotfix 
    SKU affected: kumon_same&different_K
    SKU cannot be updated in the CS account. There is a product alert related to it too. 
    Lazada open platform logs:
    when i test in API test tool:
    it shows the same error
    other affected SKUs with the same error:

Reason : The cause of the error is from the special character

Solution:   Escape/Replace the special character

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1762/bugfix-csm-1977

5. --- P1 Qoo10 | Inventory update is not reflecting under "Listing" ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1977

Description: When we import the product from Qoo10-MP to CS and if we do the inventory update action it is getting edited and reflecting in MP also But in that particular inventory page under listing it is not reflecting. Please check the attached screenshot and recoding.

Reason : Qty has been updated in MP but the api still gives an error
Solution:  Add log when we face error
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **
## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1764/release-release-1670

6. --- P1 Jean Perry - Inventory Missing for Shopee and Lazada" ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1937

Description:Associated SKU JPHOMERBS - in the products page, it is linked to Shopee and Lazada, but in the inventory, its variants are only linked to Lazada

Associated SKU JPHOMERQC - in the products page, it is linked to Shopee and Lazada, but in the inventory, its variants are not linked to any mp

I have deleted the products in Cs and reimported them, but the issues still persist

Reason : When i check i see it is error in $log_string when createProduct 

Solution:  Change it into Integration::INTEGRATIONS[$this->integrationCategory->integration_id]
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **
