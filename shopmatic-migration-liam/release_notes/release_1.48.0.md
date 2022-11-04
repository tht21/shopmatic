# Release Notes version - 1.48.0

**Date:** 24 March 2022

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/982/bugfix-csm-1232

1. ** P1 Nejilock Client - Export not working **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1232

Description:

when export button is clicked, there is a confirmation message from the system, but no export recorded in past exports page. Product is not exported to mp as well. 
client Nejilock is facing this issue.

Reason: Logic when get product list and export list are different. It require product attribute must have         integration_category_id.

Solution: Use the same logic when export. Product can has and has not integration_category_id.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/983/bugfix-csm-1239

2. ** P1 BellzKitchen - Qoo10 product has been unlinked **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1239

Description:

Prreviously after importing products fromQoo10, there were a lot of them that were getting unlinked. 
Currently, there are still a few products that are are still getting unlinked. 

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/998/csm-1247-p2-qoo10-global-weight-is-not

3. ** P2 Qoo10 global - weight is not pulled into CS **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1247

Description:

create product on Qoo10 global and import products to CS.. weight is not pulled into CS.

Reason:

Solution:

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/999/bugfix-csm-1226

4. ** P1 Cancellation of orders that result in 0 stock is not updated in CS (Part 2) **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1226

Description:

Qoo10
(no change needed) when cancel is chosen in CS order, restock happens on qoo10 but not cs

(no change needed) if you choose ‘cancel the order’ in qoo10, stock does not change.

if you choose 'cancel the order and options OOS',

(to fix) if the order contains a variant, stock in qsm will be changed to 0. CS inventory should be 0 too

(to fix) if the order contains a product without a variant, stock in qsm will be changed to 0. CS inventory should be 0 too

if you choose 'cancel the order and items OOS',

(to fix) if order contains a product without variant, stock in qsm will be changed to 0. CS inventory should be 0 too

(should not happen, no change needed) if order contains a variant, stock for variant in qsm will not be restocked. Stock for the entire product that the variant is under will be 0, so even though variants have stock, all variants are not allowed to be bought. eg stock dropped from 20 to 19 because of the order. when order is cancelled, stock remains at 19.

Reason:

Solution:

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/995

5. ** Export - Can not edit color family **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1328

Description:

Reason: Optimization in 960 impacted this code,Product does not has attribute.

Solution:Revert 960 change,Checking how that product is created and fix.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

