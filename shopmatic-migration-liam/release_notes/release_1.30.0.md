# Release Notes version - 1.30.0

**Date:** 28th October 2021

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/604/csm-890-p1-resync-products-inventory

1. ** P1 – Resync Products Inventory Immediately when Automatic Inventory Sync is switched on **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-890

Description:
Right now, inventory syncs happen once a day. However, there should be a resync of products inventory immediately after the automatic inventory sync is switched on. 
Will need this feature to be prioritised as it affects the migration/integration process done by the ops team 

Solution: When user save account setting, we will check automatic_inventory_sync is enable. If yes, we will dispatch immediately.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/599/csm-848-p1-export-shopee-export-error-in

2. ** P1 Export -Shopee-Export error in cross listing **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-848

Description:
1. Create and export product to any market place: Eg woocommerce
2. From CS, go to export page and select shopee account.
3. Select integration.
4. Find the product from the Show table
5. Enter mandatory information in table and export from actions button 
Observation: Error:Internal Server Error., get_headers(): Filename cannot be empty
Test Data:lvarexp

Solution: If image_url is empty, we will use source_url.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/598/update-price-unable-to-update-price

3. ** P2: Export Update-price-Unable to update price From CS once product is created from Bulk products excel **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-885

Description:
1.Select the integration and download the excel, export the product
2.After successful export of product, update the price from CS
Observation: We dont have the option for price

Solution: Set default selling price and special price is zero.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**