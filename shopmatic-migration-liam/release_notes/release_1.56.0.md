# Release Notes version - 1.56.0

**Date:** 26 May 2022
## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1266/feature-csm-1513

1. ** Remove Amazon integration option in prod **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1513

Description:
hide amazon integration option from all merchants, and only show it for our ghanler test account

Solution:
Enabling Amazon only for the testing Account Shop

Added a env variable  “AMAZON_ALLOWED_SHOP_IDS”.

Need to add the Shop Id’s to display Amazon MP for Seller Account

** Yes Staging Changes configuration:**

Added a env variable  “AMAZON_ALLOWED_SHOP_IDS” and defined the shopid's to enable Amazon

** Yes Production Changes configuration:**

Added a env variable  “AMAZON_ALLOWED_SHOP_IDS” and defined the shopid's to enable Amazon

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Syed**

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1267/bugfix-csm-1457-release-156

2. ** P3 Okeanos - Internal Server Error when deleting products not linked to mps **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1457

Description: Steps to recreate issue:
                delete individual product
                internal server error appears

Reason: Logic of this code is to count if there’re variants or not. If exists variant, the inventory will be deleted.

Solution: Check if inventory is null → no need to count anything → no need to delete any inventory.

** Yes Staging Changes configuration:**

** Yes Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Steve**

\- **Developed by: Steve**

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1268/bugfix-csm-1386-release-v1

3. ** P3 Okeanos - Wrong Product Name **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1386

Description:
    When the bulk inventory file is downloaded, the product names of 3 products are wrongly taken as the SKUs.
    Product names in Shopee . Product names are showing up correctly in CS
    Steps: Inventory > Bulk Update > CSV > Download inventory file

Reason: Name of $inventory->name same with sku.

Solution: Use product name instead.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1270/bugfix-csm-1440-release

4. ** P2 Horizon - Undefined property due to new order notification **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1440

Description:
    Error: NewOrderNotification | Debug Log|Order Id|415134|Message|Undefined property: Illuminate\Contracts\Database\ModelIdentifier::$external_order_number

Reason: Some order has null external_order_number . So they are not ready to send mail NewOrderNotification.

Solution: Move lines of code to check external_order_number to out-side of send mail function.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1271/feature-csm-1459-release

5. ** P1 Edit init script for marketplaces **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1459

Description:
    this story will have four child tickets
        remove xero and qoo10 legacy from the xero and qoo10 legacy init files respectively 
        add qoo10 global region config into the qoo10 init file
        run the script for all marketplaces automatically every week (Monday 1am)
        edit code such that we can run the script for a specific marketplace and region 

Solution: Change code of import category

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**
