# Release Notes version - 1.23.1

**Date:** 8th September 2021

## PR Link
N/A

1. **Woocommerce**

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-776

Issue: Updated inventory by bulk update, and the stock was updated on main but not on the integrations linked to the products. When I try to update the stock individually, it only changes on main but does not update stock in marketplace pages and in marketplaces themselves (Shopee SG and Lazada SG).

Description: The fix does not particularly fix the bulk update - but it forces a sync inventory for the inventory even if the main stock is not changed.



** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Gerald**

\- **Developed by: Gerald**
