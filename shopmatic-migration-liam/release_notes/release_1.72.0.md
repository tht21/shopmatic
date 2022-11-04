# Release Notes version - 1.72.0


**Date:** 28 September 2022



## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/1965/feature-csm-1886

1. ---Lazada - SPU Image now required for CreateProduct API (Part 1) ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1886

Description: 

Here is Lazada’s announcement, which has taken effect on 30 July 
From Lazada
In order to regulate the use of create product API, we have adjusted the rules of creating product API. It is estimated that on July 30, 2022, when using the create product API to create a product, the SPU image needs to be passed in the request, otherwise an error will be reported. We hereby issue an announcement to inform you. For more details, find the following.
Examples of errors are as follows:
What to implement

check if we are currently sending SPU image when creating product in Lazada

check if SPU image is currently a mandatory field in our code before we create product in Lazada

ensure it is a required mandatory field when we are creating products from CS in these three areas

Create single product

How to navigate there: Products >  All Products > Add New > Select Lazada > Select CS and Lazada category > switch from ‘Main’ to Lazada section on the right side > scroll down to Images for main product > 

ensure images field is compulsory in code 

this field is mapped to SPU image parameter in response to createproduct API, with images sent in sequence of images uploaded here

error messages related to SPU images for createproduct API should show up as a pop up just like all the other error messages generated when creating a product. Refer to more information for error messages in the last point below

*take note that the field for main product images is different from the field for variant images

Bulk Products page

dev to check if current code is enforcing that any fields are mandatory. If it is not, we do not need to make any changes here

Export table

How to navigate there: Products > Export to lazada > select CS category and Lazada category > show table > basic info > edit description and images >

ensure images field is compulsory in code 

this field is mapped to SPU image parameter in response to createproduct API, with images sent in sequence of images uploaded here 

Images has an asterisk sign right beside it in UI 

Export excel 

How to navigate there: Products > Export to lazada > select CS category and Lazada category > download excel file by clicking on green button 

ensure Main Image field under basic information column is compulsory in code 

this field is mapped to SPU image parameter in response to createproduct API

error messages related to createproduct API should show up in the past imports page. Refer to more information for error messages in the last point below

4. when calling the createproduct API, there might be errors related to the SPU image. We need to implement the error messages to display in CS UI when there are errors

Reason: 

Solution: add message
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **


## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1961/bugfix-csm-1838-latest

2. ---P2 Lazada: Can not save product variant name ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1838

Description: https://staging-v2.combinesell.com/dashboard/products/1103-v2cookingoil1670/edit
Environment

staging-v2 and production


Reason:  when import there are no variant name 

Solution:  After checking API /products/get find fill saleProp, variant name fields are returned
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **


## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1962/csm-2196-change-log-in-and-log-out-details

3. ---Change log in and log out details in user_activity table ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2196

Description: 
Right now, the log in and log out timestamps for the same session are in different entries, with different session ids
What to do
ensure log in and log out timestamp are in the same entry, for the same session_id
add user’s email as a new column to track in the same table

Reason: Change log in and log out details in user_activity table 

Solution:  Save old session id into in Session and use it after successful logout
- Create new field user_email, when create the record will add user_email in login, logout
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **


## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1963/bugfix-csm-1063

4. ---P2 Shopee V2 Export - Short description cannot be edited with excel file ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1063

Description: 

Created a product on CS

downloaded excel file, edited excel file by filling up brand, shipment provider, short description etc

uploaded excel file 

short description is not recorded in the export table, while other updates are fine 

when the excel file is downloaded again, short description is shown

Reason:  
Not update short_description,
For type Excel\CreateProducts we don’t save account in source 

Solution:  Put vadition handle for shopee only


** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **



## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/1964/csm-2186-change-import-logic-for-products

5. ---Change Import logic for products that are not successfully imported---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-2186

Description: 

Lazada's way of working is to announce that the product has not been successfully imported and continue to import the remaining products

With Qoo10 and Shopee, if there is an error, it will stop at the time of the error. Do not continue to import.
We need to follow lazada’s logic for all 6 marketplaces - lazada, shopee, qoo10, amazon, shopify, woocommerce. 

Reason:  

Solution: Change logic and add message for Qoo10, Woo, Amazon.
Old logic is working properly in Lazada, Shopify, Shopee.
For Shopee. Unprocessed message display and processed on related ticket 2035.
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **


## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/1964/csm-2186-change-import-logic-for-products

6. ---Improve "Unable to find IntegrationCategory for product." error message to specify integration category---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-2035

Description: 

Need to add integration category in so we know what is the category that is not working.

 

This should be implemented in both product alerts and past imports page, whenever integration category cannot be found for ALL marketplaces.

 

Past imports page

When importing products, if there is a missing integration category, 

Error message should read “Unable to find integration category  “__(integration category ID)___ “ for product with name (product name) and associated SKU (associated SKU) from ____(marketplace name) (marketplace region) (email address/ website). Please inform the system admin about this issue.”

 

example: Unable to find integration category ID 324 for product with name “abc” and associated SKU “ppppl0” from Shopee SG (euniceleerl@hotmail.com). Please inform the system admin about this issue.

 

If there are errors for multiple products, it should look like this: 

example: Unable to find integration category ID 324 for product with name “abc” and associated SKU “ppppl0” from Shopee SG (euniceleerl@hotmail.com). Unable to find integration category ID 325 for product with name “dgh” and associated SKU “pwke” from Shopee SG (euniceleerl@hotmail.com). Unable to find integration category ID 333 for product with name “bcd” and associated SKU “ppkh1” from Lazada SG (euniceleerl@hotmail.com). Please inform the system admin about this issue.

 

Product alerts page

For existing products in CS, if existing integration category is removed from DB,

Message should read “Unable to find integration category  “__(integration category ID)___ “ for product from ____(marketplace name) (marketplace region) (email address/ website). Please inform the system admin about this issue.”

When user clicks on the product alert, it should bring the user to the product details page. 

 

example: Unable to find integration category ID 324 for product from Shopee SG (euniceleerl@hotmail.com). Please inform the system admin about this issue.

Reason:  When i retest this ticket on Staging, import working file but Product Alert not working 

Solution: Change create alert event and logic to create alert  
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **