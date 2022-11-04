# Release Notes version - 1.52.0

**Date:** 27 April 2022

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/1141/bugfix-csm-1435

1. ** P1 Amazon - import is failing for amazon on prod **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1435

Description:

Reason: Sometime we can not get document id from amazon.

Solution:Throw customize message if we can not get document id instead of calling continue amazon api.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**

## PR Link


2. ** P2 Amazon - product id type are missing **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-587

Description:
on CS we have only 3 product id types whereas on amazon there are 6

Reason: product_id,product_id_type & condition not found in UI on preprod. This needs to be fixed as it is present in staging and prod. 

Solution: And when we create/update product, i can see we post product_id, product_id_type, and condition to Amazon.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/1143/bugfix-csm-1386

3. ** P3 Okeanos - Wrong Product Name **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1386

Description:
Details

When the bulk inventory file is downloaded, the product names of 3 products are wrongly taken as the SKUs. 
Product names in Shopee
Product names are showing up correctly in CS
Steps
Inventory > Bulk Update > CSV > Download inventory file
To Be Implemented
ensure that the correct product name is shown for all products in the bulk inventory file

Reason: when import inventory, it get sku as product name.

Solution: If product name has data, use product name. Otherwise use sku.For the old data which has created, we can change it from database or just upload the new css (with correct product name).

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/1144/csm-1406-p2-horizon-failed-job-update

4. ** P2 Horizon Failed Job - update order on other integrations **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1406

Description:

Reason: When we run it on replica database, sometime order model is not sync then it cause error.

Solution:  Do nothing when order model is not ready.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/1145/csm-1407-p2-horizon-failed-job-create

5. ** P2 Horizon Failed Job - update order on other integrations **

Jira Ticket : P2 Horizon Failed Job - create order on other integrations

Description:

Reason: When we run it on replica database, sometime order model is not sync then it cause error.

Solution:  Do nothing when order model is not ready.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/1146/feature-amazon-sp-api-implemenations

6. ** P3 Amazon - create a queue to check product status for amazon **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-582

Description:

Since in Amazon,product exported listing status is not known immediately,
we  are scheduling a task to check the listing status,which will execute as
a background job after 300 secs of product export to Amazon Marketplace.
For Amazon exported product , the task will be in processing state unless we get
status from Amazon about the product listing.

The background job will check the status of the product by calling the amazon API every after 30 mins and  the number of times the job may be attempted is 10 i.e 10 retry. 

The background job will run for maximum 300 mins i.e 5 hrs
The background job on receiving the status for the exported product from the API will update the task and the product status.

                    A.  If success the task status will be updated from “processing” to “finished” and product status will be updated from “Draft” to “Live”

                    B.    If failed the task status will be updated from “processing”  to  “failed”. No change in product  status ,it will remain in Draft 

                    C.   If background jobs time out happens and still we did not received the status of the exported product from Amazon , the task will be updated from “processing” to “failed” .No change in product status, it will remain in Draft.

 

For every call to Amazon Api , the execution time is logged to the log file for reference

For amazon logs refer to amazon-<current date).log file.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Syed**

## PR Link 
https://bitbucket.org/combinesell/combinesell/pull-requests/1146/feature-amazon-sp-api-implemenations

7. ** Amazon - add log to track responses from amazon **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-677

Description:

Implementation is done in Ticket CSM-582

Logs added for amazon response for imports, product creation, exports and orders import 

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Syed**


## PR Link 
https://bitbucket.org/combinesell/combinesell/pull-requests/1146/feature-amazon-sp-api-implemenations

8. ** P1 Amazon- Export all function is not working for amazon **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-621

Description:

Implementation is done in Ticket CSM-582

Added delay if is amazon to prevent limit exceed.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Syed**

## PR Link 
https://bitbucket.org/combinesell/combinesell/pull-requests/1149/csm-1480-p1-export-only-single-product-is

9. ** P1 export - only single product is exported for amazon on preprod **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1480

Description:

Reason:Class 'App\\Http\\Controllers\\Api\\Carbon' not found

Solution: Add use Carbon\Carbon;

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Peter**

\- **Developed by: Peter**