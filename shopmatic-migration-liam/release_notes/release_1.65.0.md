# Release Notes version - 1.65.0


**Date:** 10 August 2022




1. --- Replace Shopee v1.0 APIs with v2.0 APIs ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1128

Description: Important: To avoid cache clash caused by simultaneously using production and testing environment of Shopee Seller Centre/Mall under the same browser, please use incognito window for all testing operations.
v1.0 APIs will be deprecated in third quarter of the year. We need to replace them with v2.0 APIs, while ensuring that all functionalities on CS provided by v1.0 APIs will continue to be provided by v2.0 APIs. In order to do so, we must map the correct v1.0 APIs to v2.0 APIs.
This is the document with v1.0 APIs. This is provided by Shopee on the mapping of V1 to V2 API.
Tickets should be worked on in the order as shown in the document. They are labelled numerically from 1-19
In the future, we can evaluate if additional functionalities provided by v2.0 APIs will be good to implement.
The deprecation plan will happen across 3 phases, with each lasting for 90 days. Review attached list of Phase 1 APIs and complete migration by 23:59 (GMT+8) 10 August 2022. These APIs will be deprecated on 11 August 2022. 
*APIs in purple are the ones we are currently using


** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Steve **
