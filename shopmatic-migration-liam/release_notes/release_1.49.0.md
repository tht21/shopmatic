# Release Notes version - 1.49.0

**Date:** 7 April 2022

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/925/csm-1151-p2-create-logs-for-lazada

1. ** P2 Create logs for Lazada migrateimage API **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1151

Description:

need to monitor new API for a week to make sure it is working fine, before deciding if we want to remove the logs
What to implement
what time it started, what time it ended
which client
which integration (email address, name of shop, mp)
which associated SKU and SKU
image URL
error codes and messages if job fails


Reason: 

Solution:

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/1016

2. ** Duplicate too many inventory logs **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1371

Description:

Staging - sku: casualdresssku
Restocked from order 348250939 (Qoo10) because of qoo10 logic Out of Stock(by Seller) duplicate 4968 times

Reason: The log will create every time we change inventory.

Solution:when displaying in the UI, it should show distinct values

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/1017/csm-1216

3. ** Duplicate too many inventory logs **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1216

Description:

Subscription plans update 

1. Starter : 10 integrations and 1000 SKUs across all CS shops
2. Growth  :  25 integrations and 8000 SKUs across all CS shops
3. Full Service/MEP  : 25 integrations and unlimited SKUs across all CS

 Solution:

 Plans configuration are updated in the Subscription Constants

 Note : Constant values are only updated as per the ticket requirement no code implementaion or changes

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/1018/bugfix-csm-1403

4. ** P1 Cancellation of orders that result in 0 stock is not updated in CS - Lazada **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1403

Description:

LAZADA
(to be fixed) when order with whiteshirt,blackshirt2 was cancelled on lazada with reasons incorrect pricing and unable to reserve your stock, lazada changed stock to 0 but CS replaced stock from order and pushed this stock to lazada.

Reason:

Solution:

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

