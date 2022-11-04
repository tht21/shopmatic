# Release Notes version - 1.46.0

**Date:** 3 March 2022

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/939/csm-1125-p1-qoo10-global-categories-list

1. ** P1 Qoo10 Global - Categories list is empty for Qoo10 global **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1125

Description:

Reason: Did not sync category from Qoo10 Global.

Solution: Run command php artisan product:categories 11004 1 to sync category. We add custom code to sync for specific region id to improve performance.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Suraj Chowdhry**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/940/bugfix-csm-1163

2. ** P1 Qoo10 global - length,width, height and weight fields are changed to 0 after creating product on Qoo10 global **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1163

Description:
https://preprod.combinesell.com/dashboard/products/1103-birdfoodtest1/edit

steps : 
All products → create new product → choose qoo10 global and fill all mandatory information and create product
product gets created successfuly on Qoo10 global however ength,width, height and weight fields are changed to 0. 

Reason: When get product info from Qoo10, it does not has dimension. Previous dev hard code 0, 0, 0, 0. 

Solution: Get correct dimension to update product. 

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/941/feature-csm-1202-qoo10-global

3. ** p1 Qoo10 global- Invalid integration category 10 error on export excel upload **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1202

Description:
1. create products from bulk upload
2. Go to export page, select category download excel
3. Update values on excel and upload.
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
https://bitbucket.org/combinesell/combinesell/pull-requests/944/feature-csm-1126-alert-cache

4. ** Optimize Product Alerts **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1126

Description:
[2022-01-20 15:02:08] production.INFO: select * from product_alerts where product_alerts.product_id = 19492 and product_alerts.product_id is not null and type = 2 and dismissed_at is null
Time: 5163.54
rachel@speckledspace.sg has 1.3M product alerts. With this product 19492, they has about 40k alerts. So when we go to prod page in production, server will down.

Reason: We get all product alerts, but some product has too much alerts, it will cause the performance issue. 

Solution: We check code and see we only use that code to count, then we will count instead of select all.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**
