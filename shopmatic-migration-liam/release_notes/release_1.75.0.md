# Release Notes version - 1.75.0


**Date:** 20 October 2022



## PR Link: https://bitbucket.org/combinesell/combinesell/commits/80301084c9a10fe41e7aef5ce1c29d47352eed46

1. --- Remove deleted images in s3 bucket---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-2119

Description: 

Remove images from S3 bucket once the images are deleted from CS for Marketplace 


Solution: Remove images from S3 bucket once any product images are deleted from CS Marketplace Product Edit
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Syed **




## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/2064

2. --- Query Optimisation for Orders ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-2103

Description: 

Due to wrong data type of external_id in below queries , index was dropped in select and update query and hence more number of rows were scanned.
The data type of external_id is “Varchar” and while fetching its used as “Integer” hence index was dropped in select and update


Solution: 
1. Type Casting of the external id to String in the search query for order.
2.  Composite index already added in production db "fulfillment status,payment status & deleted_at" for query optimization so that retrieval of ordes are faster 

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Syed **

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2072/bugfix-csm-2213-v2

3. ---Lazada - SPU Image now required for CreateProduct API (Part 2)---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2213

Description: 

Here is Lazada’s announcement, which has taken effect on 30 July

 

From Lazada

In order to regulate the use of create product API, we have adjusted the rules of creating product API. It is estimated that on July 30, 2022, when using the create product API to create a product, the SPU image needs to be passed in the request, otherwise an error will be reported. We hereby issue an announcement to inform you. For more details, find the following.

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Peter **
## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2104/csm-2318-can-not-create-mutilple-varia

4. ---Lazada - SPU Image now required for CreateProduct API (Part 2)---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2318

Description: 
example of options and variants

option - colour | variants - red, blue, yellow

option - size | variants - S, M

Reason: Resolve conflict

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Peter **

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2104/csm-2318-can-not-create-mutilple-varia

5. ---p1: lazada: cannot create variant product ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2310

Description: 
I tried to create variant product for lazada in pre prod and found that few varaiants are missing. Please find attach screen shot and screen recording. Thanks

Reason: Resolve conflict

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Peter **

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2066/bugfix-csm-2188

6. ---P2 Lazada | Image is not getting delete while editing.---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2188

Description: 
I created a product for variant in lazada and give dimension to 1 variant but it is not getting copied to another varaiant. Please find attach screen shot and screen recording. Thanks

Reason: Can't see all the photos on the product page

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Peter **

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2075/csm-1974-csm-2305-csm-2218-p2-flip-flop

7. ---P2: lazada: while creating product for variants it is showing as "Category Integration does not allow for products with more than one variant to be created".---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2305

Description: 
I tried to create new product in CS for lazada and gave variants and when click on create button it shows error as “ category Integration does not allow for products with more than one variant to be created". But from MP end i can able to create product for same category and it has varaitions. Please find attach screen shot and screen recording. Thanks

Note: i tried with many category as lipstick, eyeliner, television and for all it is failing

Reason:Get the wrong categories to check

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Peter **

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2075/csm-1974-csm-2305-csm-2218-p2-flip-flop

8. ---p2: Lazada: While exporting getting error as "Internal Server Error., Call to undefined method Illuminate\Database\Eloquent\Builder::isIntegrationCategoryNotHaveAttributeIsSaleProp()".---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2218

Description: 
I tried to perform export all by clicking on export all button. For few products im getting this error as “Internal Server Error., Call to undefined method Illuminate\Database\Eloquent\Builder::isIntegrationCategoryNotHaveAttributeIsSaleProp()”. Please find attach screen shot and screen recording. Thanks.

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Peter **

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2079/csm-1673-p1-horizon-lazada-new-order

9. ---P1 horizon - Lazada new order status Failed Delivery---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1673

Description: 
What to do

Please create a new fulfilment status ‘Failed Delivery’ in CS, and link it to CS financial status ‘Paid’. These should be mapped to Failed Delivery status from Lazada

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Peter **

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2079/csm-1673-p1-horizon-lazada-new-order

10. ---P2 Lazada | Import getting failed "Undefined index: data"---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2085

Description: 
For our account as well as for client integrated account in staging Lazada import is getting failed “Undefined index: data“. Please check the attached recordings.
Reason: Fill data from respone is empty

Solution: Add validation and add logs

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Peter **

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2069/feature-csm-2185

11. ---Log IP address when inventory is updated ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2185

Description: 
Ip address needs to be added to the backend logs whenever inventory is manually updated for both single inventory updates and bulk update



Take note: right now, if they are using VPN, Ip addresses will be the same. So we cannot differentiate between whoever uses VPN.
Reason: Missing define ip_address, I don't get this error when testing locally

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Peter **

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2070/p2-multiple-mp-getting-duplicate-sku-after

12. ---P2 Multiple MP | Getting duplicate SKU after creating the product for multiple MP ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2221

Description: 
Go to product page and click on Add New button and choose 3 MP(Woocommerce, Lazada & Shopify) give all the details which are necessary while creating product(Single variant) and Click on Create button after product getting created successfully open the particular product and in the select MP dropdown change the MP. We are getting the duplicate SKU under each MP.

Reason: 

We are showing the Product price associated with the Product variant. 

But now BE is sending back the entire Product price associated with the product variant but not filtered by integration_id.

Solution: 

Filter product prices by integration id selected before showing to FE
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Peter **

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2097/csm-2243-csm-2243-p1-one-desire-update

13. ---P1 One Desire - Update Products: Qoo10 Price---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2243

Description: 

Qoo10 prices are off.

Reason: Not same font size
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Peter **

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2075/csm-1974-csm-2305-csm-2218-p2-flip-flop

14. ---P2 Flip Flop: Lazada Import - unable to import - Change hard delete to soft delete for categories---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1974

Description: 

these are the error messages when we try to import the products. 

Lazada access logs show this but I do not see any error:

Reason: Change hard delete to soft delete

Solution: Use visible column to remove category
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Peter **

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2094/csm-2309-p1-all-mp-except-lazada-we-are

15. ---P1 All MP(Except Lazada) | We are unable to enable Multiple variant option for all MP(Except Lazada)---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2309

Description: 

When we try to create the product multiple variant we suppose to enable the variant option but on staging if we enable the multiple option we are not getting any option to enter the variant details for all the MP except Lazada. Please check the attached screenshot and recording.

Solution: Put validation only handle for Lazada
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Peter **

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2113/csm-2316-p1-multiple-mp-while-creating-the

16. ---P1 Multiple MP | While creating the product for multiple MP Image's getting vanished from UI---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2316

Description: 

While creating the product for multiple MP(Qoo10, Woocommerce, Shopify) after giving all the mandatory filed and images for the particular MP. While clicking on create button Images are not getting saved automatically getting vanished. Please check the attached recording.


Reason:
- When i test same test case i got another problem:

Problem: Trying to get property “external_id”…

Solution: 
- Put validation and get correct external_id
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Peter **

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2126/csm-2308-csm-2308-errorillegal-string

17. ---P2 Multiple MP(Qoo10) | While editing getting " ErrorIllegal string offset 'value' Contact admin to resolve it."---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2308

Description: 

Created the product in CS together for multiple MP successfully and while editing the product by deleting the product for CS MP and saving the image with new image getting the error saying “Illegal string offset 'value' Contact admin to resolve it.“. Please check the attached screenshots and recording.

Product name “3MP laptop for import and action staging“

Solution: Put check another case

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Peter **

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2096/bugfix-csm-2010-v2

18. ---P1 One Desire: Lazada Create Product - Price & Stock:C008:Duplicate sales attribute, the repeated attribute is Volume:100"---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2010

Description: 

When I try to create a product and add multiple variants to be created in Lazada, and have the same value for volume field for both variants, I get an error - “Price & Stock:C008:Duplicate sales attribute, the repeated attribute is Volume:100”

Issue has been recreated in prod with Associated SKU: lu1

Issue Price & Stock:C008:Duplicate sales attribute, the repeated attribute is Volume:100

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Peter **

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2119/csm-2013-p2-one-desire-update-product

18. ---P2 One Desire: Update Product - Unable to delete options"---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2013

Description: 

ONLY for main section, not to make any changes in marketplace sections

There should be an x option beside the variant names, similar to what happens for the creation of products, so that users can delete the variant

when users click on the cross, there should be a pop up with “This will delete this variant option for the product on Combinesell and the marketplace(s). Are you sure?
Yes No

yes and no options will be provided to the merchant. If they click yes, they will be allowed to proceed. If they click no, the pop up will close.

here is an example of the pop up design format

Reason: Checked the whole project, no need to authorize for delete

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Peter **