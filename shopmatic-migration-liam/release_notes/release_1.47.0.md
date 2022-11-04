# Release Notes version - 1.47.0

**Date:** 9 March 2022

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/959/bugfix-csm-1196

1. ** P2 Qoo10 - remove button update shipping info when status is shipped **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1196

Description:

Deploy issue. After set permission and rebuild frontend on staging. it’s working well.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Suraj Chowdhry**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/960/csm-1170-p1-replacing-qoo10-legacy-code

2. ** P2 Replacing Qoo10 Legacy Code with APIs - shipping statement **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1170

Description:
ShippingBasic.GetShippingInfo_v2
works on QSM for orders that are non pick up, but does not work for legacy, so we need to check if it works for API

Reason: API provides us with information for shipping statement, but does not provide the format and html doc for shipping statement found in QSM

Solution:  hide 'shipping statement’ button for qoo10 orders

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/961/csm-1197-p2-qoo10-waybill-button-to-be

3. ** p2 Qoo10- waybill button to be shown on shipped status **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1197

Description:
Order 347566582

Reason: Only show airwaybill button when fulfillment_status = 1.

Solution: Also show button when fulfillment_status = shipped

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/962/feature-csm-1126

4. ** Optimize Product Alerts **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1126

Description:
[2022-01-20 15:02:08] production.INFO: select * from product_alerts where product_alerts.product_id = 19492 and product_alerts.product_id is not null and type = 2 and dismissed_at is null
Time: 5163.54

rachel@speckledspace.sg has 1.3M product alerts. With this product 19492, they has about 40k alerts. So when we go to prod page in production, server will down.

Reason: We get all product alerts, but some product has too much alerts, it will cause the performance issue. 

Solution: We check code and see we only use that code to count, then we will count instead of select all.

Please test this branch in Pre-Prod.

Implementation:
1.Maintaining an alerts notification counter in redis as key value pair 
Key Format : 'product-alert-'.SHOP_ID.'-'.PRODUCT_ID;
2.In Product Page, the alerts notification count will be fetched from redis cache.
3.In case of the alerts notification key is not found in redis , it will fallback to database.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/977/csm-1242-p1-qoo10-global-toggle-is-not

5. ** p1 Qoo10 global - toggle is not working for Qoo10 global products **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1242

Description:

Reason: Did not make feature toggleEnable

Solution: Add feature toggleEnable

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/975

6. ** P1 Qoo10 Global API - Apply Qoo10 SG APIs and change endpoints **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1089

Description:

A Qoo10 account with both SG and global mp will have the same APIs
For qoo10 global, use the same APIs and endpoints but change service urls so that it points to qoo10 global website

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Suraj Chowdhry**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/975

7. ** P1 Qoo10 Global - Add Integration Option **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1091

Description:
current UI when Qoo10 is clicked
new UI to be implemented when Qoo10 is clicked
step 1:
step 2: options shown should be ‘Singapore’ and ‘Global’
step 3: text should be ‘for Qoo10 in Singapore’ when Singapore is clicked in step 2, and ‘for Qoo10 in Global’ when Global is clicked in step 2

API Key: BhiQeRsAhG2aTaBn2faNuGIeFE_g_1_PNHfL3LCVjzlspsE_g_3_ 

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/939

8. ** P1 Qoo10 Global - Imports fail **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1124

Description:
imports for Qoo10 global fails with error  - Unable to find IntegrationCategory for product.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**
Go to Integration table and change id 11004, change region_ids from [2] to [1, 2]

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/939

9. ** P1 Qoo10 Global - Categories list is empty for Qoo10 global **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1125

Description:

Reason: Did not sync category from Qoo10 Global.

Solution: Run command php artisan product:categories 11004 1 to sync category. We add custom code to sync for specific region id to improve performance.

Need run command: php artisan product:categories {integration} {region_id}

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/940

10. ** P1 Qoo10 global - length,width, height and weight fields are changed to 0 after creating product on Qoo10 global **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1163

Description:
https://preprod.combinesell.com/dashboard/products/1103-birdfoodtest1/edit

steps : 
All products → create new product → choose qoo10 global and fill all mandatory information and create product
product gets created successfuly on Qoo10 global however ength,width, height and weight fields are changed to 0. 

Reason: When get product info from Qoo10, it does not has dimension. Previous dev hard code 0, 0, 0, 0. 

Solution: Get correct dimension to update product. 

Video:https://www.loom.com/share/8b9c66ba7c7a49a3bb13a51b77df30fb 

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/941

11. ** p1 Qoo10 global- Invalid integration category 10 error on export excel upload **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1202

Description:
create products from bulk upload
1.Go to export page, select category download excel
2.Update values on excel and upload.
user is getting Invalid integration category 10 error

Reason: snake_case is not replace space to _ in some case.

Solution: Use str_replace to replace space to $integrationNameAndRegionNameAndIntegrationCategoryId = str_replace(' ', '_', trim($group));  

ProductImportJob.php  line 334

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/972

12. ** P1 Separate Qoo10 SG and Qoo10 Global orders **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1219

Description:
Details
Currently, even if just Qoo10 SG integration is added into CS, both SG and global orders are being pulled in.

To be implemented
If Qoo10 SG account is added, only Qoo10 SG orders should be pulled in.
If Qoo10 global account is added, only Qoo10 global orders should be pulled in.

check CSM-1214: P2 Qoo10 - Differentiate Qoo10 SG and Qoo10 Global OrdersCLOSED and CSM-1225: P1 Qoo10 Global - Fulfilment of Orders OPEN for more info

Acceptance Criteria
merchant should be able to fulfil orders from qoo10 SG and Global

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**