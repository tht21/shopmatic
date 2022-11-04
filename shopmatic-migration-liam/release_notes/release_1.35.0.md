# Release Notes version - 1.35.0

**Date:** 7 December 2021

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/738/csm-945-export-update-variation-images-are

1. ** P2:Export update-variation images are not getting updated **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-945

Description:
steps:
1.create products with variations and export to shope
2.the variation images are pushed to shope and also pulled to CS
3.update the variation images and then save the updates
Observation:
1.when updates are save then it shows the message successsful
2.but the variation images are not updated

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/739/csm-930-p3-woocommerce-all-values-in

2. ** P3-Woocommerce- All values in showtable not updated properly from excel **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-930

Description:
1. Create products for WC from bulk product upload
2. Enter true for virtual product, back orders,reviews allowed featured
3. After product is created in draft, retrieve the same in export page
4. Download the excel and observe that all the true selected columns are now empty
5. Enter all the mandatory and optional info anagin and upload
6. Check in show table some columns are missing

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/740/csm-816-p2-export-images-are-empty-in

3. ** P2 Export - Images are empty in export excel. **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-816

Description:
1)created a product on shopee and imported to cs
2)product imported successfully with image
3)go to export and download export excel.
4) main image is missing in the excel
however in edit table image is showing up

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/741/csm-859-p2-staging-export-shopee-multiple

4. ** P2 (Staging) Export-Shopee-Multiple variant optional values not getting populated in show table on excel upload **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-859

Description:
1. Create product with multiple variants on shopee from bulk product upload
2. Go to export page>select integration
3. download excel and update the optional values for the variants and upload
4. refresh the show table and observe that values not getting updated

test data: cfp304

Optional values like color, dimension , warrenty type are not getting updated

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/742/csm-947-p3-woocommerce-images-not-getting

5. ** P3-WooCommerce- Images not getting exported correctly to woocommerce from excel **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-947

Description:
1. Create product with single and multiple variations through bulk product upload
2. On export page select integration and download the excel
3. Update the variation image with new image and upload 
4. Click on Export all to export

Issue 1: Existing variation image remains in showtable on top of new image ;instead of getting replaced by new image.
So when exporting both images are getting exported

Issue 2: In case of single variant product the image is not getting updated in showtable

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**


