# Release Notes version - 1.26.1

**Date:** 27th September 2021

## PR Link
N/A

1. **P1-Woocommerce-Import from WC failing multiple time on prod**

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-804

Description: 
1. Product import takes a long time to import and times out.

Solution: Optimized code to reduce database calls and added composite indexes.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Gerald**

\- **Developed by: Gerald**

## PR Link
N/A

2. **P1:Woocommerce - Import fails with improper Error message.**

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-845

Description:
1.import fails with the error message:Unknown product variant status for Woocommerce (Main product as variant)

2.Not able to identify for which product it is.

Solution: Added check for "future" product status - this isn't in the woocommerce API documentation

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Gerald**

\- **Developed by: Gerald**

## PR Link
N/A

3. **P1 Woocommerce – Orders Not Syncing**

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-843

Description:
Shows Pending, paid on CS but pending payment on WC. 

Solution: Set financial status to unpaid

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Gerald**

\- **Developed by: Gerald**
