# Release Notes version - 1.70.0


**Date:** 14 September 2022



## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1876/bugfix-csm-1989-latest

1. ---P1 One Desire - Qoo10: When client clicks on AWB, the shipment provider changes ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1989

Description: Context

Options for shipment providers are shown to buyers when they place orders, so buyers can choose which shipment provider they prefer. 
Client Issue
For order ID: 350975414, the shipment provider chosen was Singpost. When client clicked on "Print Airway Bill" and it changed to Qxpress shipping instead, which resulted in client needing to pay more for shipping comparing to Singpost mail.
What to fix
When client clicks on AWB option in UI, we need to first check which shipment provider it is. If it is not delivered by Qxpress, error message should show ‘Airway bill cannot be generated as the shipment provider chosen for this order is not Qxpress'
*Please check if delivery provider ‘Qx Quick shipping mode’ has AWB as well 

Reason: 
Solution: if the shipment provider is different from ‘Qxpress’, ‘Qprime’,'QX Quick','Qxpress normal mail' then i will return error message ‘Airway bill cannot be generated as the shipment provider chosen for this order is not Qxpress,Qprime,QX Quick or Qxpress normal mail’
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **


## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1878/bugfix-csm-1961-last

2. --- P2: Inventory: While overriding have to click 2 times ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1961

Description:I try to override an inventary

Firstly i unsync the inventary and click on update

It says successfull but it will not override/update stock as we have provided

Again if i give value and click on update it works. Please find attach screen shots and screen recordings. thanks.

Reason: Prevent the user from making changes while the API has not yet called successfully
Solution: Add popups to prevent users from making changes without successfully calling the API

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **
## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1880/feature-csm-1900

3. --- P1 Nutrition Pro - Duplicated Qoo10 inventory ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1900

Description: to be planned for 1 Sept release

when checking the inventories for some products, i see that they are linked to 2 of the same Qoo10 account. I am able to update the stock here and it is reflected correctly in the mp. It shows that the product is linked to 4 mps in the product page too. However, it is unclear why there are duplicated qoo10 inventories. Example of SKUs affected: nspg-20006, npsg-20078

To fix the bug, I tried to delete the product on CS, and re-import from all 4 mps. It shows up correctly in the products page, but there is no inventory created and linked to Qoo10. The inventory is only linked to the other 3 mps. Created another ticket for this.

Reason: Duplicated qoo10 inventory
Solution:Create command to check if ‘old data’ listing is exist. If yes, delete it

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **


\- **Developed by: Harry **
## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1881/bugfix-csm-1762

4. --- P2 Shopify | Can not save dimension ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1762

Description: 

Reason: 
Solution:

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Developed by: Harry **
## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1882/bugfix-csm-1830

5. --- P2: Shopee v2: Update products - Name field and dimension is not getting updated in cs for shopee products ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1830

Description: I created a product of shopee and tried to update name, sku, dimension, price, weight etc. but sku and price is getting edited but name, dimensions are not getting edited. Please find attach screen shots and screen recording. Thanks.

Reason:  For this ticket, i see the code change in CSM-1762: P2 Shopify | Can not save dimensionTO BE DEPLOYED ON PRE-PROD affect the display of this ticket.
Solution:I will change and update new logic in the code on 1762 branch and merge it into 1830 branch

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server



\- **Developed by: Harry **
## PR Link:  https://bitbucket.org/combinesell/combinesell/pull-requests/1883/csm-1911-csm-1911-p2-shopee-v2-banned

6. --- P2 Shopee-V2 | Banned product status in CS(Draft) ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1911

Description:Created the product in Shopee and imported it successfully to CS but after sometime it is getting banned from Shopee end and it is showing Banned in Shopee-MP but in CS it is in Draft status. @Eunice Lee Let us know what should be the status in CS. Please check the attached screenshots.

Reason:New product status Banned
Solution:Initialize a new product state and handle the save for this state

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server

\- **Developed by: Harry **
## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1884/csm-1919-update-price-failed-please-try

7. --- Shopee V2: Update price failed, please try later ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1919

Description:Payload:

{
  "item_id": 19432176080,
  "price_list": [
    {
      "model_id": 0,
      "original_price": 0
    }
  ]
}
Response:

{
  "error": "product.error_update_price_fail",
  "message": "Update price failed, please try later.",
  "request_id": "1f1650ed5a23e9231654cf1f1cc56c7e",
  "response": {
    "failure_list": [
      {
        "failed_reason": "Original_price is less than min price limit."
      }
    ],
    "success_list": []
  }
}


Reason: When update price failed, it only show generic error
Solution:Show properly error.

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server

\- **Developed by: Harry **
## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1885/csm-1971-p2-shopee-v2-edit-product-while

8. --- P2: Shopee v2: Edit product: While editing product getting "Undefined index: logistics contact admin" ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1971

Description: when i try to edit the product which i have imported im getting Undefined index: logistics contact admin. Please find attach screen shots and screen recordings. Thanks

Reason: An error case when $data['logistics'] does not exist should result in an error
Solution:Check isset($data['logistics']) for the case where $data does not provide a logistics field. If logitics is not provided, we will skip it when updating product.

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server

\- **Developed by: Harry **
## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/1886/csm-2031-p2-shopee-v2-product-edit-weight

9. --- P2: Shopee v2: Product edit: weight more than 3 it is showing error "invalid logistic info , channel_id:19100 (weight or size is invalid for the open channel)" ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2031

Description: I created a product in CS and tried to edit the product by giving weight as 3 and getting this error as “invalid logistic info , channel_id:19100 (weight or size is invalid for the open channel)”. Please find the attach screen shot and screen recording. Thanks.

Reason: Submit weight and dimension values ​​that exceed the value provided by logistics
Solution:Display an error so the user knows how to enter the value for weight and dimension

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server

\- **Developed by: Harry **
## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1887/bugfix-csm-2086

10. --- P2: Lazada: Export: while exporting getting error ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2086

Description:I tried to perform export and facing error of “Price & Stock:C012:Main image has duplicate https://sg-live-02.slatic.net/p/fbd19894f5621f364afd67d6757c9579.png”. Please find attach screen recording and screen shot.

Reason:variant image has duplicate
Solution:add function array_unique

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Developed by: Harry **
## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/1888/csm-1742-getting-logistic-is-required

11. --- P2: Getting "Logistic is required" error while editing Q10 global products.---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1742

Description: While editing products of Q10 Global getting error of "Logistic is required" in Production Environment. But we dont have any option of giving Logistic. Please find above screen shots and screen recording. Thanks.

Reason:  $data['attributes']['logistics'] does not exist in the elseif. logistics are not necessary and not provide, we do not need to throw an error
Solution:$data['attributes']['logistics'] => $data['logistics']


** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Developed by: Harry **
## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1889/bugfix-csm-1860-last

12. --- P2: Shopee v2: Product update - Main images in shopee are not getting updated in cs ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1860

Description:I tried to delete main image and tried to update it. It is successfully updating in shopee but in cs it reflects old image only. Please find attach screen shots and screen recording. Thanks.

Reason:products page main images are reflecting but not variant images
Solution:Handling image saving for product variant

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server

\- **Developed by: Harry **

13. --- P2 Shopee V2 | Export error - invalid field Model.TierIndex: value '[]' must contain at least 1 elements ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1764

Description:

Reason:Tier index is not calculate properly.
Solution: When tier has many option_list, need to calculate tier_index properly.

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server

\- **Developed by: Harry **

14. --- p1: shopee v2: create product: Category and attribute not match. getting this error. ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2019

Description:while creating product under Laptop category im getting this particular error. Please find attach screen shot and screen recording. Thanks.

Reason:
Solution: 
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Developed by: Harry **

15. --- P2 Shopee-V2 | Product creation | Getting "Invalid attribute. repeated attribute value [100434]" error ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1932

Description:While creating the product under Laptop category we are getting the error saying “Invalid attribute. repeated attribute value [100434]“. Please check the attached screenshots and recordings

Reason:wrong value_id when we use parameter for api add_item
Solution: Save value_id when we init category, and get it before call api add_item
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server