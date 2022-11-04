# Release Notes version - 1.19.1

**Date:** 29th July, 2021

## PR Link 
- https://bitbucket.org/combinesell/combinesell/pull-requests/440

1. **P2 Lazada - implement new order status for Lazada**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-663

- Description : Implemented new order status for Lazada
- Solution - 

Handled new order statuses “packed,repacked,ready_to_ship_pending” in Lazada OrderAdapter

In UI dashboard order page added the new statuses “packed,repacked,ready_to_ship_pending” to the filter FULFILLMENT STATUS

When the order status is “packed” or “ready_to_ship_pending“  the immediate next status is “ready_to_ship“ hence  action available is “Ready To Ship “

When the order status is “repacked”  the immediate next status is “packed” hence the action available is “Pack”


**No Configuration Changes**

**No DB Changes Required**

**To be Deployed on:** Beta Server 

\- **Signed Off given by:  Gerald**

\- **Developed by: Syed Asfaquz Zaman**
