# Release Notes version - 1.28.0

**Date:** 19th October 2021

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/583/csm-807

1. ** Override Function Not Working **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-807

Description:
Created a product with SKU orangepeel on Shopee, Lazada, Shopify with 10 stocks. Switched on override function for Lazada, and changed it to 5 stocks. After I placed an order on Lazada, the stocks for Lazada was correctly reduced to 4, while the rest of the stocks were incorrectly reduced to 9, when it should remain at 10 stocks.

Solution: Disable inventory deduction for overridden stocks

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Gerald**


## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/584/csm-855

2. ** P1 Woocommerce – Inventory Sync Issue for 'On Hold' Orders **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-855

Description:
1. Create on hold orders on wc. This is reflected as Unpaid, Processing order on cs.2. Inventory is deducted on wc, but not deducted on CS. eg 1 on CS, 0 on wc marketplace3. (error step) When inventory is updated on CS, the numbers will not be updated accordingly. eg when 5 is updated on CS, it is 5 on wc instead of 4

Solution: Added new status for fulfillment and made stocks deduct for processing payment status

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Gerald**


