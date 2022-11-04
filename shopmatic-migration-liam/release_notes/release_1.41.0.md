# Release Notes version - 1.41.0

**Date:** 19 January 2022

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/815/csm-564-p1-qoo10-update-images-failed-for

1. ** p1 Qoo10 - update images failed for qoo10 **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-564

Description:
https://www.loom.com/share/68d2144c03c743d09dc58a3edaa5a0a9
1. in cs add images to the product 
2. not able to add images to the product 
it throws error contact admin to resolve it

Reason: When call updateImages function, current code does not update main picture and variant picture.
Solution: Update main picture and variant picture.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link


2. ** P2-Qoo10-Expire date not showing in showtable after excel upload **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1025

Description:
1. Create products for Qoo10 using bulk product upload
2. Go to export page, choose integration and download excel
3. Enter expire date in excel, save and upload
4. On showtable expire date is not reflecting
https://www.loom.com/share/04bbcf381180439dab9f0a363a4be944

Reason:
The date time format is not dd/mm/yyyy, you are using 2/2/2022 it is d/m/yyyy. It should be 02/02/2022
it’s not save as text in excel. If it has format text, it will be align left.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/846/csm-1026-p2-qoo10-data-not-showing

3. ** P2-Qoo10-Data not showing correctly in export excel, does not match the template in create product **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1026

Description:
1. Create bulk products with all mandatory and optional data.
2. After product is created go to export page and select integration
3. Download the export excel.

Observation: 1. Adult Y/N field was selected during product create but not showing in excel download
2. Expiry date was entered during product create, but not showing in excel download
3. Industrial code is showing different format in excel download

Reason: Adult Y/N has text = Y/N but when download excel, it compare with Yes/No and it’s not match. Then it show empty.
Solution: Change constant for text Yes > Y, No > N.
PFA template and export excel

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link


4. ** P3- Qoo10-Market place description is not retained after export from excel **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1046

Description:
1. Create products from bulk upload page
2. Go to export page, select integration ,select the record and export using Actions>export
3. Go to CS product edit page.
Observation: Short description missing from marketplace,although present in main

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link


5. ** P2-Qoo10-Error on Brand update for live products **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1047

Description:
1. Create product from Add New or from bulk product
2. Export product to Qoo10
3. Once product  is live, go to product edit page and try to update brand

issue: user unable to edit brand
getting issue as:
can't change BrandNo to System.Int32.

https://www.loom.com/share/db2f0c9c253141669213975c66902a53

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/847/bugfix-csm-1042

6. ** P3-Qoo10-Unidentified provider and tracking number for pickup orders **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1042

Description:
1. Create a pick up order in Qoo10.
2. In CS, check for the same order
Order 346420405 
Observation: Provider and tracking number shown in CS, in not found in Qoo10

Reason: current code used PackingNo instead of TrackingNo.
Solution: Use TrackingNo

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/848/bugfix-csm-1043

7. ** Export product: Delete product image is not working **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1043

Description:
1. Export product page.
2. Remove image.
3. Save.
4. Refresh.
5. Nothing happen. It should remove image.

Reason: 2 reasons: get image from image_url but remove image from source_url then it can not find image. Another reason is: get image when region_id is null and integration_id is null.
Solution: Delete image when region_id and integration_id is null. And compare url for both source_url and image_url.


** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link

8. ** P2-Prod-Qoo10- Brand list showing empty **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1053

Description:
Qoo10 brand not showing 

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/849/bugfix-csm-955

9. ** P1 Create log for jobs on prod and staging **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-955

Description:
This will help us figure out the priority of query optimization based on the time taken for these jobs to be processed 

Afterwards, we can decide whether to split queues, and write raw queries incrementally 

P1 as we are getting more clients onboarded and the loading time for various functions is taking a significant hit 

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/850/bugfix-csm-998

10. ** capture logs only other than status code 200 **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-998

Description:
capturing logs for success response is not much useful and is consuming space. hence log only responses other than status code 200. 

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

