# Release Notes version - 1.51.0

**Date:** 21 April 2022

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/1061/csm-1431

1. ** P1 Export - Can not edit color family **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1328

Description:

Reason: Optimization in 960 impacted this code,Product does not has attribute.

Solution:Revert 960 change,Checking how that product is created and fix.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/1062/csm-1447-p1-three-green-peas-import-errors

2. ** P1 Three Green Peas - Import errors **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1447

Description:
trying to import products on prod from shopee multiple times but facing these errors

Reason: When transform product info, $attribute['attribute_name'] does not have value.

Solution: Check if attribute_name is not ready, do nothing.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**