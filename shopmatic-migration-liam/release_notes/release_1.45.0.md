# Release Notes version - 1.45.0

**Date:** 23 February 2022

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/928

1. ** P2- Images are not showing in showtable for cross list product **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-972

Description:
1. Create a product in lazada/shopify 
2. import product in CS and verify that image showing in UI
3. Go to export page and select any account for cross list---eg shopee or lazada
4. select integration and check show table
5. in show table product image showing as 0
6. When excel is downloaded, we can see that there are images present

Issue: show table should show the images of the product present in cross list
this issue present in all MPs

Solution: Showing variants images and main images in showtable for all MP
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Suraj Chowdhry**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/929/csm-1168-p1-replacing-qoo10-legacy-code

2. ** P1 Replacing Qoo10 Legacy Code with APIs - Waybill **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1168

Description:
ShippingBasic.PrintQxpressInvoice

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/930/bugfix-csm-1169

3. ** P1 Replacing Qoo10 Legacy Code with APIs - Print Shipping Address **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1169

Description:
ShippingBasic.GetShippingInfo_v2
output parameter: 
works on QSM for orders that are non pick up, but does not work for legacy, so we need to check if it works for API

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/931/csm-1195-p1-lazada-lazada-order-sync-job

4. ** p1 lazada - Lazada order sync job failed **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1195

Description:
https://beta.combinesell.com/horizon/failed/231686294
Ordersyncjob is failing on prod due to exception
Exception: Lazada has different fulfilment statuses in /var/www/html/combinesell/app/Integrations/Lazada/OrderAdapter.php:375

Reason: Status lost has lower case.
Solution: Consider 'lost_by_3pl' as lost status.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**