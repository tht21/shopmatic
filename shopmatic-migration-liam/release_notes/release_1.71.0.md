# Release Notes version - 1.71.0


**Date:** 22 September 2022



## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1919/bugfix-csm-2187

1. ---P1 Idocare Import Failure ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-2187

Description: 

** Shopee Import Failing **

Reason: 
Shopee Import is Failing due to the reason as below

For Shopee Product Import , we are fetching items with statuses : Normal & Banned

Its a sequential Api Call , first item with Normal statues are fetched followed by Banned Status

When the Api call is made with “Banned Status” , if the shop does not have any “Banned” items it returns response as below:

{"error":"","message":"","warning":"","request_id":"37373630363262646461613131623436:6130666338633065:3634303037333465","response":{"total_count":0,"has_next_page":false,"next":""}}

Since it does not have any items in the response payload , and due to empty item validation in product import,  exception is thrown as No products on shopee” and import task is updated as Failed,  even though the previous API call to fetch item with ”Normal” status was successful.

Solution: 
Handled empty response if Shop does not have any Banned Items.

Shop has Normal Items and No Banned Items : Import Success and Message : Success

Shop does not have Normal Item : Import Failed and Message : No products on Shopee

Api does not return any response : Unable to retrieve products for Shopee in Import,No response
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Syed Asfaquz Zaman **



## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1924

2. ---Schedule job as a cronjob in kernel to update categories and attributes for all mps ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1959

Description: 

Marketplace Categories Import scheduled every Friday startinf from  4 AM SGT morning onwards.

Reason: 

To keep marketplace categories and attributes upto date

Solution:

Marketplace Integration Categories Import Job will run once in a week on Friday starting from 4 AM SGT onwards till 4:45 AM SGT

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Syed Asfaquz Zaman **



## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1923/csm-2033-p2-shopee-v2-cant-update

3. ---P2 Shopee v2 Can't update dimension ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-2033

Description: 

Reason: CS can not update dimension because MPs do not support dimesion for varaint

Solution: Update logic from 1830 branch


** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1925/csm-2063-p2-shopee-v2-variant-images-in

4. ---P2: Shopee v2: Variant Images in shopee are not properly reflecting in CS ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-2063

Description:

 Created product in shopee with variants and performed import action and only main image is reflecting in shopee not the other images. Please find the attach screen shot and screen recordings. Thanks.


Reason: create new image but note save into variant


Solution: after create new image save it into varant



** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/1926/feature-csm-2027

5. ---Database - Store login details every time a user logs in ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-2027

Description:

 In order to add focus to user actions as we go forward with new feature development for the marketplace software, an added sub-feature should be logging user auth details for every successful login. This is also necessary from a security perspective. These details should include the following information at minimum to be stored: User ID used for login, login timestamp [ data and time of login ], IP address. The IP address may or may not be in the stored information in the database and could be a logging feature. Some of this information if made available on request, should be beneficial for our operational and KAM teams too. 

 

Details to store in DB

User ID

IP address

login timestamp (date and time)

session ID

 

Questions

do we store these in the users table? no, we will create a new table users_activity

for every user, can we store ALL login timestamps or just the last timestamp? ALL

 

Future implementation to consider

show last login date and time to user whenever they log in

 

Reason: show last login date and time to user whenever they log in


Solution: created new model to store last login date and time to user whenever they log in



** No Staging Changes configuration:**


** NO Production Changes configuration:**

**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/1927/csm-2124-p2-wintertime-duplicate-skus-in

6. ---P2 Wintertime - Duplicate SKUs in product details for mps---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-2124

Description:
Shopee: 
 Product: https://staging-v2.combinesell.com/dashboard/products/1103-88127

Reason: Show all prices associated with the removed product variant


Solution: Only show prices associated with product variant



** No Staging Changes configuration:**


** NO Production Changes configuration:**

**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/1931/csm-2106-p1-jean-perry-composite-inventory

7. ---P1 Jean Perry - Composite inventory not created for bundle product ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2106

Description:

 
 import products from Shopee with all options enabled

Inventory is created for SKU NVSCQC-EVERDEEN-S,NVMT-S,NVBP,2**NVPP,JPQ-S in All Inventories page

Inventory cannot be found for the same SKU in Composite Inventory page

Reason: Due to no passing variable $config

Solution: Add $config



** No Staging Changes configuration:**


** NO Production Changes configuration:**

**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**