# Release Notes version - 1.39.0

**Date:** 5 January 2022

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/822/csm-492-p2-qoo10-description-is-not-being

1. ** P2 Qoo10 - Description is not being pushed to Qoo10 **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-492

Description:
Environment:
1)create a product on Qoo10 add short description and html description while creating the product.
https://staging-v2.combinesell.com/dashboard/products/1113-toysku/edit
2)push the product to Qoo10
3)On Qoo10 product description is not pushed however html description is pushed properly.
Reason: Qoo10 does no have short_description, and when we get qoo10 data to update system, it will be empty.
Solution: Use short_description of CS when update to product object when we get from qoo10.
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/824/bugfix-csm-495

2. ** P2 Qoo10 - items in the list are not in valid format **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-495

Description:
steps :
1)generate bulk products excel 
2) choose adult_item field from drop down
3)values are not in valid format
Please check for other fields as well.
4)export excel also has the same problem
Reason: Current code just handle qoo10 legacy.
Solution: Apply for both qoo10 legacy and qoo10.
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/823/csm-979-p1-qoo10-beta-error-on-product

3. ** P1-Qoo10|Beta -Error on product creation **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-979

Description:
1. Select Qoo10 account and click on Add New
2. Create product with all mandatory details

Issue: Error is shown in UI although product is getting created properly
https://www.loom.com/share/454748836da54dc497cf4144d07e71d6
Reason: Response data has different format.
Solution: Check if response has correct format before processing.
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

4. ** P2Qoo10 :Adult field remains unchanged **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-631

Description:
https://www.loom.com/share/079ddfa1499b4afe9fbe85c062200d02
Test case1:
1. create bulk products set the Adult field as yes
2. after export of product, the field remains to No.
3. the field is set to no, in both CS and Qoo10.
Test case 2:
1.the fileds of excel , contact_tel, video url, origin country code, brand no are filled and exported
2. the above fields  are not pushed to CS. and Qoo10
3. Only Origin country code is pushed to Qoo10 but not to CS.

Reason/Solution: Please take note for adult item only some specific category allow to “Yes“ for example like alcohol else Qoo10 will still set it as “No“.
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Jowy Lim**


## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/423/csm-644-https-myshopmaticatlassiannet

5. ** P2 Qoo10 - Cannot cancel order from Qoo10 **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-644

Reason: The cancel status is not handle.
Solution: Handle cancel status. Order can be cancelled only if the order status is in any of the below listed statuses:
PENDING = 0;
PROCESSING = 1;
READY_TO_SHIP = 10;
REQUEST_CANCEL = 29;
If the order status is not in any of the above listed status , then cancel action is not supported.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Gerald Lam**

\- **Developed by: Syed Asfaquz**


## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/425/csm-648-https-myshopmaticatlassiannet

6. ** P2: Qoo10, Error retains for the Qoo10 fields When exported ,even after changes made. **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-648

Description:
https://www.loom.com/share/c43da05fa57a4427ad0feb84fde2a183
Steps:
1.Export the product to Qoo10 
2. the error is occurred, due to Expire date format
3.Remove the field, or change the format in Excel and export again, the same error retains.

Reason: expire_date is not correct format.
Solution: Handle cancel status. Order can be cancelled only if the order status is in any of the below listed statuses:
PENDING = 0;
PROCESSING = 1;
READY_TO_SHIP = 10;
REQUEST_CANCEL = 29;
If the order status is not in any of the above listed status , then cancel action is not supported.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Gerald Lam**

\- **Developed by: Syed Asfaquz**


## PR Link
None

7. ** P2 Qoo10 -Add in selected reversed engineered features for Orders. **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-497

Description:
Required features:
Arrange pick up 
Shipping labels 
Please advise if there will be any impacts to the current Qoo10 integration.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Gerald Lam**

\- **Developed by: Gerald Lam**


## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/416/csm-638-https-myshopmaticatlassiannet

8. ** P2 Qoo10 : products when exported, Images are doubled in Qoo10. **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-638

Description:
1. created a product with Sku Test shoes1 and added 6 images to it 
2. delete the image in CS, it is still present in Qoo10, due to acceptance of default variantion images
Expectation:
if we want to delete an image in Cs it should get deleted in Qoo10
Observation 
if we are trying to delete the single image, we need to delete in both images and variation images.
https://www.loom.com/share/c06433991c4b46b990f7f55807a517f6

Reason: Product does not have variants.
Solution: In case of a product with no variants multiple images were pushed to Qoo10 which eventually leading to duplicate images in Qoo10, as in case of no variants in CS default variant were created which has the same images as that of the parent product.
So in case of no variant , the parent product and variant has the same sku , based on that condition skipping all the variant images while pushing the images to Qoo10 on product create and update as well.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Gerald Lam**

\- **Developed by: Syed Asfaquz**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/791/csm-979-p1-qoo10-beta-error-on-product

9. ** P2-Qoo10-Getting error on product create,inspite of product getting exported successfully. **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1007

Description:
1. Go to Add new and create product for Q0010
2. Fill mandatory details and click on create
Issue: User getting error as" trying to access array offset on value of type null" . Although product is getting successfully export and shown in live Status
Issue present in both staging and prod

Reason: $response['meta'] is not set.
Solution: Check if $response['meta'] is set or not.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Liam**
