# Release Notes version - 1.62.0

**Date:** 13th July 2022
## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1472/csm-1698-p1-getting-internal-server-error
--- PR ----

1. P1 | Getting "Internal Server Error., Error request Qoo10." error
Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1698
Description: While exporting product to Qoo-Global facing internal server error telling “Internal Server Error., Error request Qoo10.“ in past export page. Please check the attached screenshot and recording.

Reason: When I check the input parameter of the api ItemsBasic.SetNewGoods at Qoo10 Developer's Guide  the BriefDescription is not found

Solution: Remove field BriefDescription
** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Liam **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1473/csm-1578-p1-alice-claudine-unable-to
--- PR ----

2. P1 Alice Claudine - Unable to fulfil shopify order that is partially refunded
Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1578
Description: to be fixed for release on 14 july

Order ID: 4627116130391

order came in from buyer

merchant partially refunded client as item oos

when they tried to fulfil the rest of the order on CS, they received a message 'There is no location set for the order

this is happening for other regular orders (order ID 4629840724055) that they want to fulfil too 
NINSQLGNOS6WJ2
https://www.ninjavan.co/en-sg/tracking?id=NINSQLGNOS6WJ2

Reason: location_id not exist.

Solution: Get assign_location_id from fulfillment_orders api replacing location_id
** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Liam **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1474/csm-1578-p1-alice-claudine-unable-to
--- PR ----

3. P1 horizon - Lazada OrderSyncJob failure
Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1701
Description: https://beta.combinesell.com/horizon/failed/493913589

Reason: $order['statuses'] not found

Solution: If statuses is not found then $order['statuses'] is empty
** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Liam **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1475/csm-1578-p1-alice-claudine-unable-to
--- PR ----

4. P1: Creating product in Lazada showing error message
Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1670
Description: When I tried to create new product in Lazada in PreProduction Environment and also in Beta Environment also, Product is created with error message is showing up.

Reason: Account null
Solution: Use another way to get account information
** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Liam **

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1476/csm-1546-p1-happy-walker-qoo10-sg-unlinked
--- PR ----

5. P1 Happy Walker - Qoo10 SG Unlinked inventory
Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1546
Description: products are being unlinkedeven though they have different skus. Please check why and fix it. 

Reason: EXTERNAL_ID have number type is not string type should unlinkedeven though they have different skus
Solution: not check EXTERNAL_ID according string type
** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Liam **