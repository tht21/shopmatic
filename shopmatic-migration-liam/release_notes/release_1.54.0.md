# Release Notes version - 1.54.0

**Date:** 5 May 2022

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/1190/csm-1464-p1-replace-lazada

1. ** P1 Replace Lazada GetTransactionDetails API to queryTransactionDetails API **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1464

Description: Objective

Lazada just sent a notification regarding the cessation of support for gettransactiondetails API (/finance/transaction/detail/get). It is scheduled to go offline at 24:00 on April 30th. 
What to do
We need to check if we are using gettransactiondetails API
If yes, to avoid affecting our business, we need to stop calling this API before the API goes offline and add Related services are migrated to the queryTransactionDetails API (/finance/transaction/details/get).
queryTransactionDetails API details:
API Reference - Lazada Open Platform 
Note: The responses of GetTransactionDetails API and queryTransactionDetails API are the same，There are no new fields in the response

Reason:

Solution:

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1191/csm-1469-p1-nutrition-pro-qoo10-inventory


2. ** P1 Nutrition Pro - Qoo10 Inventory Update not working for products with 1 option **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1469

Description:
Bug
When inventory is updated in CS, Qoo10 products with 1 option are not having the options inventory updated. Instead, their main product inventory is updated. Options inventory is the one that will be shown to buyers
example: nutrition pro SKU npsg-20394
Inventory update is working fine for Qoo10 products with multiple options
What to fix
products with 1 inventory should have their option inventory updated instead of main product inventory updated when stock is changed on cs
please ensure this is fixed for qoo10 sg and global
Reason: When product has only 1 option, it’s only update quantity of main product.

Solution: Add quantity to option product as well.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/1192/csm-1440-p2-horizon-undefined-property-due

3. ** P2 Horizon - Undefined property due to new order notification **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1440

Description: [NewOrderNotification]Debug Log|Order Id|415134|Message|Undefined property: Illuminate\Contracts\Database\ModelIdentifier::$external_order_number

Reason: order->account and order->account->integration is not ready.

Solution:  Check order->account and order->account->integration are set or not. If no, dont do anything.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/1193/csm-1488-p1-horizon-qoo10-ordersyncjob

4. ** P1 horizon - Qoo10 OrderSyncJob failure **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1488

Description: ErrorException: Undefined variable: json in /var/www/html/combinesell/app/Integrations/Qoo10/Client.php:150
https://beta.combinesell.com/horizon/failed/352669298

Reason:

Solution:

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**
