# Release Notes version - 1.43.0

**Date:** 8 February 2022

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/891/feature-csm-1049

1. ** Lazada Product API Update for Image URLs **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1049

Description:
Lazada Announcement: https://open.lazada.com/announcement/index.htm#/announcement/detail?id=711&_k=sl2dag 
External URLs will stop being supported in CreateProduct, UpdateProduct and SetImage APIs by February 15 2022.  

The change will impact below parameters:
SPU image
SKU image
Images in product descriptions. 

Changes to be Implemented In All Areas in CS
- ALL Image urls used in these APIs must be uploaded to Lazada system first (find more details from MigrateImages)
- To create new product or update product via api, you need to upload product images into the LAZADA system. Upload single image using MigrateImage API or batch upload images using MigrateImages/GetResponse. Then create or update product with the returned image url. 

Areas in CS affected
area 1: creation of single products > add new > images section in Lazada page
area 2: creation of single products > edit > images section in Lazada page
area 3: products > export to lazada > excel file uploaded for products to be created in lazada

Acceptance Criteria
changes to be implemented in area 1
changes to be implemented in area 2
changes to be implemented in area 3

when there are errors returned by the APIs, the CS system has to proceed according to the error code table above

Questions
Eunice: depending on the error codes, how will the system proceed? added error code table above

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/893/csm-1112-p2-system-exception-on-lazada

2. ** P2-System exception on lazada export **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1112

Description:

Reason: Need space between ‘NoBrand’
Solution: Before call Lazada api, we need replace ‘NoBrand’ to ‘No Brand'

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/894/csm-1116-template-generation-query

3. ** P1 export - High CPU utilisation is happening while generating export template **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1116

Description:
Steps : 
Go to exports page
select category and integration category mask 
click on generate template..Unable to generate template and CPU utilisation goes high

Please refer to AWS for CPU utilisation.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**
