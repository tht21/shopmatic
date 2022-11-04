# Release Notes version - 1.3.0

**Date:** 10th March, 2021

## PR Link 
- https://bitbucket.org/combinesell/combinesell/pull-requests/140/region-id-related

1. **added 3 images for lazada product but only 1 was pushed**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-305
- Description : added 3 images for the lazada product but only 1 was pushed.
- Fixes : Along with CSM-292.

2. **Export all product based on category and integration category**

- Ticket : https://myshopmatic.atlassian.net/browse/CSM-316
- Description: clicking on the export all button, it should export all regardless of the page number in that particular category.  
- Fixed: Export all the products based on category and integration category regardless with the page number.

3. **Export missing integration category**

- Ticket : https://myshopmatic.atlassian.net/browse/CSM-325
- Description : Export product and get error message "Please make sure there is integration category selected" 
- Fixes 
 - when retrieve the product attributes query will filter by region_id and integration_id 
 - Integration category button change query 


4. **Invalid attribute Export product**

- Ticket : https://myshopmatic.atlassian.net/browse/CSM-288
- Description : Getting invalid attribute message during export product 
- Fixes: Check the value which is an empty string array before the validation.	

5. **Restructuring product attributes table to support region id**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-297
- Description : Restructuring product attributes and prices to support region id when product is created and exported to marketplaces from dashboard UI and Excel both. 
- Changed:  Products attribute and price are mapped to integration id and region id in product_attributes and product_prices table.	


6. **Export to Lazada, integration category not selected**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-309
- Description : Receive integration category not selected during export, but in fact it is selected 
- Fixes : This was due to region id, did not check based on region id 


7. **unable to export products to shopee SG throws error The selected condition.value is invalid.**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-310

8. **Editing of single products, field empty**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-261

9. **exported only 1 product but in past exports page lot of products are shown in pending**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-289

10. **images not exported to lazada MY**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-294

11. **unable to export products through export all button**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-272

**No Configuration Changes**

**DB Changes Required**

```
Table: product_attributes
Column To Add: region_id

Table: product_prices
Column To Add: region_id
```

**DB migration artisan Command**

```
php artisan migrate --path=/database/migrations/2021_02_09_125841_add_region_id_to_product_attributes.php
php artisan migrate --path=/database/migrations/2021_02_11_120937_add_region_id_to_product_prices.php

(Script will add the column region_id column to product_attributes and product_prices table)
```

**To be Deployed on:** Beta Server 

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**

## PR Link 
- https://bitbucket.org/combinesell/combinesell/pull-requests/150/csm-292-https-myshopmaticatlassiannet

12. **uploaded 4 images while creating the product but product got created with 1 image only**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-292
- Description : uploaded excel to create the product with 4 variations which has 5 different images..but product got created with 1 image only.
- Fixes : Variants along with the respective variant images will be displayed in MP as well as in CS.


## PR Link 
- https://bitbucket.org/combinesell/combinesell/pull-requests/139

13. **Export, products not showing after select integration category**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-249
- didnt check attribtues with region id 


## PR Link 
- https://bitbucket.org/combinesell/combinesell/pull-requests/142/bugfix-csm-332-bulk-upload-excel

14. **bulk upload file failed for 1st time but sucessfull on second time**

- Jira Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-332
- Description :The issue is happening due to “old” column value is passed as null and in the table Not Null constraint is defined meaning old column can’t be null.

**No Configuration Changes**

**DB Changes Required**

```
Table: product_images
Column To Add: region_id
```

**To be Deployed on:** Beta Server 

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**


## PR Link 
- https://bitbucket.org/combinesell/combinesell/pull-requests/147/csm-329-shopee-image-fix

15. **Product creation failed to Shopee**

- Jira Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-329
- Description : Product creation was failing due to missing region id.

**No Configuration Changes**

**No DB Changes Required**

**To be Deployed on:** Beta Server 

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**



## PR Link 
- https://bitbucket.org/combinesell/combinesell/pull-requests/148/csm-298-image-regionid-implementation-cs

16. **Restructuring product images to support region id**

- Jira Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-298

**No Configuration Changes**

**No DB Changes Required**

**To be Deployed on:** Beta Server 

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**



## PR Link 
- https://bitbucket.org/combinesell/combinesell/pull-requests/141

17. **Unable to export products across region**

- Jira Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-340

**No Configuration Changes**

**No DB Changes Required**

**To be Deployed on:** Beta Server 

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**




## PR Link 
- None

18. **400 error while exporting products to lazada MY.**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-271
- Description :400 error while exporting products to lazada MY. Click export all button. 400 error is thrown.
- Fixes : The issue was one of the product id was in FINISHED STATE i.e already exported and hence the loop exit and didn't continue to other product ids.

**No Configuration Changes**

**No DB Changes Required**

**To be Deployed on:** Beta Server 

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**





## PR Link
- 

19. **Error while exporting products to Lazada MY**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-334
- Description : Getting Internal Server Error. Undefined index: country_user_info while exporting products
- Fixed : When retrieving a token from lazada sometimes it does not return us country_user_info so will do checking on it.	

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**





## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/143

20. **Unable to export single product to lazada MY**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-349
- Description: 
Getting error message "The selected product warranty.value is invalid" when enter the product warranty attribute value.
- Fixed: 
On the validation rule checking we will check the data column without checking the integration category attribute type.
Have changed it to when the type is either TEXT or RICH TEXT and the system won't check for the data column.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**





## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/144

21. **Failed to export products**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-339 
- Description: 
Getting error "Call to a member function toArray() on null" message when exporting product
Getting error "undefined index: selling" message when exporting product 
- Fixed: 
On every integration create function change the retrieve attribute query filter by integration and region. 
When uploading csv file to create product, the product prices will be added region id and integration id.
Currently only implement for Lazada SG, MY, Shopee SG MY and Shopify 

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**



## PR Link
- None

22. **Fields value missing in export page**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-315
- Description : There is some field value in export page does not showing
- Fixed: 
When uploading csv file the attribute header name format will changed. 
So it need to be convert back to the same name with integration category attribute name.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**


23. **Missing categories and brands from lazada MY**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-303
- Description : Missing some categories or brands from Lazada MY.
There is nothing change. Will manually retrieve the brands and categories from marketplaces.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**


24. **Main image duplicate error even after removing duplicate images**

- Jira Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-113

**No Configuration Changes**

**No DB Changes Required**

**To be Deployed on:** Beta Server 

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**




