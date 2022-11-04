# Release Notes version - 1.58.0

**Date:** 9nd June 2022
## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1322/csm-1580-amazon-enabled

1. ** Show Amazon SG integration to everyone on production **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1580

Description: Previously, amazon SG integration was hidden from all merchants, and only shown for specific CS accounts.

Solution: Amazon Enabled for all merchants in CS


** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Syed**

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1325/bugfix-csm-1204

2. ** P1 Cancellation of orders that result in 0 stock is not updated in CS - Shopee  **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1204

Description: tested with ghanler account in beta on products with sku whiteshirt,blackshirt2 from lazada and blackshirt,blackshirt1 from shopee.

Solution: Adding new code for Out Of Stock case.


** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1331/bugfix-csm-1532

3. ** P1 Medpro Medical Supplies - serialisation failure during import for qoo10 sg  **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1532

Description: this error message happens when import is done for medpro qoo10 sg. We have imported 5 times and this has shown every single time
             happening for AUD client too

Reason: dead locks when excute mysql query. 

Solution:  use transactions as they provide an automatic retry feature

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**


## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1297/csm-618-p2-amazon-issue-with-order-refund

4. ** P2 Amazon - issue with order refund   **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-618

Description: 1)After the order is refunded..popup message is incorrect it says successfully cancelled order change it to succefully refunded order or successfully initiated refund
            2)after the refund is successful.. payment status is still shown as paid and order status as shipped.order id : 503-7863107-1995055

Solution:  Implement new logic. Set the payment status and fulfillment status after orderAdjustment

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**


## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1296/csm-1451-p2-amazon-able-to-refund-more

5. ** P2 Amazon - able to refund more than product price   **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1451

Description: product price is 0.7 but refund of 1 is shown as successful

Solution:  Implement new logic. Compare the priceAdjustment and order grand total.

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Liam**

\- **Developed by: Liam**

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1328/bugfix-csm-1528-dev

6. ** P1 STG Industrial - Qoo10 Global import issue   **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1528

Description: Qoo10 global products cannot be imported at all, even though there is only one product with the ‘unable to find integration category’ error

Reason: Error category, so the import interrupted.

Solution:  Ignore the wrong category, write log into server.

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1345

7. ** P1 export is failing for lazada on preprod   **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1597

Description: Invalid argument when export data.

Reason: Some time the message is not start with '[' then it can not use as array

Solution: So we need to check first to make sure we can use implode.

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**
