# Release Notes version - 1.57.0

**Date:** 2nd June 2022
## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1287/csm-1511-p1-cs-inventory-not-updated-for
1. ** P1 CS Inventory not updated for qoo10 products**

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1511

Description: Steps to recreate issue:
- create product with 1 variation on qoo10. Both associated sku and sku must be the same.
- import into cs.
- update inventory in cs → cs inventory is not changed under listings, only - recorded in logs. inventory on qoo10 is changed
To fix: cs inventory needs to be changed as well

Solution:
- When update by productListing, pass $sku instead of $externalID. 
- Image for the test attacked.
Reason:
- Same issue with CSM-1482
- After update stock into Qoo10, the system cs update database. But when get product detail for the update, wrong externalID passed. Qoo10 need an integer ID, not a name of variant. (Log attacked at the file .log below) 

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1304/bugfix-csm-1489-release
2. ** P1 horizon - Lazada OrderSyncJob failure**

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1489

Description: Exception: Lazada invalid access token. in /var/www/html/combinesell/app/Integrations/Lazada/Client.php:469

Solution: Use response body to know exactly what is the error.

Reason: I think the error message is not correct. It always throw exception Lazada invalid access token when status code is not 200. Maybe it has another error.

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Liam**

\- **Developed by: Liam**

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1303/bugfix-csm-1507
3. ** P2 Stock getting updated in CS for suspended products**

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1507

Description: Stock should not be updated for suspended products.

Solution: Dont update CS inventory.

Reason: Still update CS inventory when it’s suspended product.

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Liam**

\- **Developed by: Liam**