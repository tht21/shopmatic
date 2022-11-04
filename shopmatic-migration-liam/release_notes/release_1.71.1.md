# Release Notes version - 1.71.1


**Date:** 26 September 2022



## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1953/csm-2189-p2-idocare-unable-to-print-lazada

1. ---P1 Idocare Import Failure ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-2189

Description: 

to be fixed and released by 29 sep

client is unable to print lazada invoice for all lazada orders. An example is order 84435297930009. Printing shipping label works.


Printing invoice for orders can be done in Lazada itself 

Reason:  issue is we are passing order item ids parameter “Order Id” as String. 

Solution:  Convert external id to integer.
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **