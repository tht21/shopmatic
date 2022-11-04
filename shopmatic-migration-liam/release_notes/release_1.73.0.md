# Release Notes version - 1.73.0


**Date:** 12 October 2022



## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2020/feature-csm-2172

1. ---Shopee API - Switch to new stock fields: Step 4Ai ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-2200

Description: 

Product Requirements
Objective: Replace shopee old stock fields with new stock fields, so that the features on Combinesell work the same way before and after replacement, with existing bugs fixed.
Stock should always be updated through CS, and NOT the marketplaces. All stock updates only apply to marketplaces with inventory syncs switched on (i.e. no overriding).
Example: SKU abc with stock 10 in CS, Shopee and Lazada. Seller reserves stock of 3 for this SKU in shopee for a shopee promotion.  i.e. total_available_stock = 10, reserved_stock = 3

 Single Inventory Updates
There are 3 scenarios - inventory before promotion starts (this ticket), during promotion (CSM-2224: Shopee API - Switch to new stock fields: Step 4AiiTO BE DEPLOYED ON PRODUCTION), and after promotion (CSM-2225: Shopee API - Switch to new stock fields: Step 4AiiiTO BE DEPLOYED ON PRODUCTION)

Inventory BEFORE promotion starts
Inventory update

refer to “Key Pointers” under Step 3 for logic 


If reserved_stock >0, and CS stock is updated to ABOVE or EQUAL to reserved_stock, stock will be updated in all listings (including Shopee listing with promotion). Main stock = Shopee listing stock = seller_stock. Shopee will only allow us to update seller_stock for the particular SKU. Reserved_stock will not be allowed to be updated.

Inventory update message: No change needed for this. All mp stock will be updated, and success message will be shown.

Inventory logs: No change needed for this.

e.g. We have 10 stocks for an SKU that is in shopee, lazada, woocommerce. So seller_stock in Shopee = 10. 

We now set 3 stock for an upcoming promotion in Shopee. Promotion has not started. Assuming we have no shopee_stock, total_available_stock = seller_stock + shopee_stock - reserved_stock = 10 + 0 - 3 = 7

When merchant updates CS stock to 20, 20 is pushed to Lazada and woocommerce listings. 20 is also pushed to Shopee listing, so seller_stock = 20. Now, total_available_stock = seller_stock + shopee_stock - reserved_stock = 20 + 0 - 3 = 17


If reserved_stock >0, and CS stock is updated to BELOW reserved_stock, Shopee will not allow us to update stock for the particular SKU. 

If there are only Shopee listing(s) with promotions for this SKU, without any other mp listing(s) &/or with Shopee listing(s) without promotion, 

Inventory update message: 

show an error message as the inventory cannot be updated.Message should read: “Unable to update inventory.”

show another error message below after the first error message appears. Message should read: “Since the stock quantity of <number of reserved_stock> has been reserved for an upcoming <marketplace> promotion, the main stock quantity updated here must be greater than or equal to <number of reserved stock> in order to update the stock for listing <product SKU> (<listing ID>)”. 
e.g. “Since the stock quantity of 3 has been reserved for an upcoming Shopee promotion, the main stock quantity updated here must be greater than or equal to 3 in order to update the stock for listing ppregrshoes (201681786936).”
e.g. of format


Inventory logs: The first line of inventory log message should read “Manual change to <new main stock> attempted by <user name>” Second line of inventory log message should read “Changed from <current CS stock> to <SAME CS stock>”

right after this, there should be a message that reads “Unable to update the stock to <new main stock> for listing <product SKU> (<listing ID>) as the main stock quantity updated is not greater than or equal to the stock quantity of <number of reserved stock> that has been reserved for an upcoming Shopee promotion.”
e.g. of an existing inventory log update with first line and second line format:



 

e.g. of this format with the message to be implemented:
Unable to update the stock to 2 for listing ppregrshoes (201681786936) as the main stock quantity updated is not greater than or equal to the stock quantity of 3 that has been reserved for an upcoming Shopee promotion.


Manual change to <new main stock> attempted by QC_ghanler. 
Changed from 10 to 10


Exclamation icon indicating an error should be shown at the start of the inventory log message for ALL errors mentioned in the inventory log. This icon is the same one that is currently shown in product alerts. Dev to mention if there are currently any other error messages shown in inventory log. In this case, the icon should be beside the “Manual change…” message and beside “Unable to update…” message


Product alerts

NO CHANGE NEEDED. Ensure this is still shown in the product alerts page.





If there are other mp listing(s) &/or Shopee listing(s) without promotions, 

Inventory update message: No change needed for this. All mp stock (except for Shopee stock with promotion) will be updated, and success message will be shown.


Right after the success message appears, show an error message in red below that reads “Since the stock quantity of <number of reserved_stock> has been reserved for an upcoming <marketplace> promotion, the main stock quantity updated here must be greater than or equal to <number of reserved stock> in order to update the stock for listing <product SKU> (<listing ID>)”. 
e.g. “Since the stock quantity of 3 has been reserved for an upcoming Shopee promotion, the main stock quantity updated here must be greater than or equal to 3 in order to update the stock for listing ppregrshoes (201681786936).”

(Dev to check) The ticket here is for Shopee stock. If we are getting reserved stock for other mps, please check and we will work on it in a separate ticket.

Inventory logs: the change should be indicated as per normal. Message should be “Manual change by <user name>. Changed from <old main stock> to <new main stock>”. After the manual change message is shown, we will have messages for each of the marketplace listings. In this case, the message for Shopee should be slightly different, and should read as “ Unable to update the stock to <new main stock> for listing <product SKU> (<listing ID>) as the main stock quantity updated is not greater than or equal to the stock quantity of <number of reserved stock> that has been reserved for an upcoming Shopee promotion.
Changed from <current CS stock> to <SAME CS stock>”
e.g. of an existing inventory log update with the same format:




e.g. of inventory logs message:
Stock for listing ppregrshoes (14745841457) updated from 10 to 2 as it's different from the main stock.
Changed from 10 to 2. 


Stock for listing ppregrshoes (9358) updated from 10 to 2 as it's different from the main stock.
Changed from 10 to 2. 

Unable to update the stock to <new main stock> for ppregrshoes (201681786936) as the main stock quantity updated is not greater than or equal to the stock quantity of 3 that has been reserved for an upcoming Shopee promotion.
Changed from 10 to 10

Manual change by QC_ghanler.
Changed from 10 to 2. 


Exclamation icon indicating an error should be shown at the start of the inventory log message for ALL errors mentioned in the inventory log. This icon is the same one that is currently shown in product alerts. Dev to mention if there are currently any other error messages shown in inventory log. In this case, the icon should be beside the “Unable to update…” message





Product alerts

NO CHANGE NEEDED. Ensure this is still shown in the product alerts page.

Reason: Default get information of first promotion, this logic is not correct if a product has many variants

Solution: Changes code
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **


## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/2020/feature-csm-2172

2. ---Shopee API - Switch to new stock fields: Step 4Aii---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-2224

Description: 
Single Inventory Updates
There are 3 scenarios - inventory before promotion starts (CSM-2200: Shopee API - Switch to new stock fields: Step 4AiTO BE DEPLOYED ON PRODUCTION), during promotion (this ticket), and after promotion (CSM-2225: Shopee API - Switch to new stock fields: Step 4AiiiTO BE DEPLOYED ON PRODUCTION)

Inventory DURING promotion
Inventory update

Refer to “Key Pointers” under Step 3 for logic


If reserved_stock >0, and CS stock is updated to ABOVE or EQUAL to reserved_stock, stock will be updated in all listings (including Shopee listing with promotion). Main stock = Shopee listing stock = seller_stock. Shopee will only allow us to update seller_stock for the particular SKU. Reserved_stock will not be allowed to be updated.

Inventory update message: No change needed for this. All mp stock will be updated, and success message will be shown.



Inventory logs: No change needed for this.

If reserved_stock >0, and CS stock is updated to BELOW reserved_stock, Shopee will not allow us to update stock for the particular SKU. 

If there are only Shopee listing(s) with promotions for this SKU, without any other mp listing(s) &/or with Shopee listing(s) without promotion, 

Inventory update message: 

show an error message as the inventory cannot be updated.Message should read: “Unable to update inventory.”

show another error message below after the first error message appears. Message should read: “Since the stock quantity of <number of reserved_stock> has been reserved for an upcoming <marketplace> promotion, the main stock quantity updated here must be greater than or equal to <number of reserved stock> in order to update the stock for listing <product SKU> (<listing ID>)”. 
e.g. “Since the stock quantity of 3 has been reserved for an upcoming Shopee promotion, the main stock quantity updated here must be greater than or equal to 3 in order to update the stock for listing ppregrshoes (201681786936).”
e.g. of format


Inventory logs: The first line of inventory log message should read “Manual change to <new main stock> attempted by <user name>” Second line of inventory log message should read “Changed from <current CS stock> to <SAME CS stock>”

right after this, there should be a message that reads “Stock for listing <product SKU> (<listing ID>) cannot be updated to <new main stock> as it is not greater than or equal to the stock quantity of <number of reserved stock> that has been reserved for an ongoing Shopee promotion.”
e.g. of an existing inventory log update with first line and second line format:



 

e.g. of this format with the message to be implemented:
Stock for listing ppregrshoes (201681786936) cannot be updated to 2 as it is not greater than or equal to the stock quantity of 3 that has been reserved for an ongoing Shopee promotion.

Manual change to <new main stock> attempted by QC_ghanler. 
Changed from 10 to 10

Exclamation icon indicating an error should be shown at the start of the inventory log message for ALL errors mentioned in the inventory log. This icon is the same one that is currently shown in product alerts. Dev to mention if there are currently any other error messages shown in inventory log. In this case, the icon should be beside the “Manual change…” message and beside “Stock for … cannot be updated to…” message


Product alerts

NO CHANGE NEEDED. Ensure this is still shown in the product alerts page.


 

If there are other mp listing(s) &/or Shopee listing(s) without promotions, 

Inventory update message: No change needed for this. All mp stock (except for Shopee stock with promotion) will be updated, and success message will be shown.


Right after the success message appears, show an error message in red below that reads “Since the stock quantity of <number of reserved_stock> has been reserved for an upcoming <marketplace> promotion, the main stock quantity updated here must be greater than or equal to <number of reserved stock> in order to update the stock for listing <product SKU> (<listing ID>)”. 
e.g. “Since the stock quantity of 3 has been reserved for an upcoming Shopee promotion, the main stock quantity updated here must be greater than or equal to 3 in order to update the stock for listing ppregrshoes (201681786936).”

(Dev to check) The ticket here is for Shopee stock. If we are getting reserved stock for other mps, please check and we will work on it in a separate ticket.

Inventory logs: the change should be indicated as per normal. Message should be “Manual change by <user name>. Changed from <old main stock> to <new main stock>”. After the manual change message is shown, we will have messages for each of the marketplace listings. In this case, the message for Shopee should be slightly different, and should read as “Stock for listing <product SKU> (<listing ID>) cannot be updated to <new main stock> as it is not greater than or equal to the stock quantity of <number of reserved stock> that has been reserved for an ongoing Shopee promotion.
Changed from <current CS stock> to <SAME CS stock>”
e.g. of an existing inventory log update with the same format:



e.g. of inventory logs message:
Stock for listing ppregrshoes (14745841457) updated from 10 to 2 as it's different from the main stock.
Changed from 10 to 2. 


Stock for listing ppregrshoes (9358) updated from 10 to 2 as it's different from the main stock.
Changed from 10 to 2. 


Stock for listing ppregrshoes (201681786936) cannot be updated to 2 as it is not greater than or equal to the stock quantity of 3 that has been reserved for an ongoing Shopee promotion.
Changed from 10 to 10


Manual change by QC_ghanler.
Changed from 10 to 2. 


Exclamation icon indicating an error should be shown at the start of the inventory log message for ALL errors mentioned in the inventory log. This icon is the same one that is currently shown in product alerts. Dev to mention if there are currently any other error messages shown in inventory log. In this case, the icon should be beside the “Stock for listing…cannot be updated to….” message


 

Product alerts

NO CHANGE NEEDED. Ensure this is still shown in the product alerts page.


 

(dev to check) When promotion starts, is there any way we will be notified of this information so we can update stock in CS? 

If yes, we need to update Shopee listing in CS automatically. Main stock will NOT be updated.

Inventory logs message should be “Stock for listing <product SKU> (<listing ID>) updated from <old total_available_stock> to <new total_available_stock (i.e. total_reserved_stock)> as Shopee promotion has started and the promotion stock is set to <total_reserved_stock>.” Only Shopee listing with promotion in CS will be updated with this stock. The main stock and all other listings for this particular SKU will NOT be updated.

e.g. of inventory logs format


e.g. of inventory logs message:

Stock for listing ppregrshoes (201681786936) updated from 10 to 3 as Shopee promotion has started and the promotion stock is set to 3.
Changed from 10 to 3. 

Info icon should be shown at the start of the inventory log message for ALL promotion messages (like the one above about Shopee promotion) mentioned in the inventory log. This icon is the same one that is currently shown in product alerts.


Reason:  

Solution: 
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **


## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/2020/feature-csm-2172

3. ---Shopee API - Switch to new stock fields: Step 4B ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2201

Description: 
Product Requirements
Objective: Replace shopee old stock fields with new stock fields, so that the features on Combinesell work the same way before and after replacement, with existing bugs fixed.

 

Stock should always be updated through CS, and NOT the marketplaces. All stock updates only apply to marketplaces with inventory syncs switched on (i.e. no overriding).

Example: SKU abc with stock 10 in CS, Shopee and Lazada. Seller reserves stock of 3 for this SKU in shopee for a shopee promotion.  i.e. total_available_stock = 10, reserved_stock = 3

Bulk Inventory Updates
There are 3 scenarios - inventory before promotion starts, during promotion, and after promotion.

Inventory BEFORE promotion starts
Inventory update

refer to “Key Pointers” under Step 3 for logic 

Message in past imports page

If reserved_stock >0, and CS stock is updated to BELOW reserved_stock, Shopee will not allow us to update stock for the particular SKU, but we will still update stock for other mps with the same SKU. Import status to be “Finished” if inventory updates for non-shopee mps with same SKU and inventory updates for other SKUs are successful. Message should read “Unable to update stock for listing <SKU> (<listing ID>). Please check the inventory logs for this SKU in “All Inventory” page for more information.” If there are multiple SKUs, it should read: “Unable to update the stock for listing <SKU 1> (<listing 1 ID>), listing <SKU 1> (<listing 2 ID>) and listing <SKU 1> (<listing 3 ID>). Unable to update the stock for listing <SKU 2> (<listing 1 ID>), listing <SKU 2> (<listing 2 ID>) and listing <SKU 2> (<listing 3 ID>). Unable to update the stock for listing <SKU 3> (<listing 1 ID>)*. Please check the inventory logs for these SKUs in “All Inventory” page for more information.” 
*The different colours above are just to let you know the listings for one SKU will be mentioned in one sentence. All the mps listed in this message will be due to Shopee promotions, but we will consider other mps promotions in the future.

e.g. “Unable to update stock for listing abc (9358), listing abc (9372022) and listing abc (481793). Unable to update stock for listing bitoy (9358) and listing bitoy (14422). Unable to update stock for listing pettoy (9358) and listing pettoy (9358). Please check the inventory logs for these SKUs in “All Inventory” page for more information.”

Inventory logs 

SAME as point 3 of for inventory BEFORE promotion starts in CSM-2200: Shopee API - Switch to new stock fields: Step 4AiTO BE DEPLOYED ON PRODUCTION 


Inventory DURING promotion
Inventory update

refer to “Key Pointers” under Step 3 for logic

Error message in past imports page

SAME as point 2 for Inventory BEFORE promotion starts in THIS TICKET

Inventory logs 

If reserved_stock >0, and CS stock is updated to ABOVE or EQUAL to reserved_stock, stock will be updated in all listings (including Shopee listing with promotion). Main stock = Shopee listing stock = seller_stock. Shopee will only allow us to update seller_stock for the particular SKU. Reserved_stock will not be allowed to be updated.

Inventory logs: No change needed for this.

If reserved_stock >0, and CS stock is updated to BELOW reserved_stock, Shopee will not allow us to update stock for the particular SKU.

If there are only Shopee listing(s) with promotions for this SKU, without any other mp listing(s) &/or with Shopee listing(s) without promotion,

The first line of inventory log message should read “Manual change to <new main stock> attempted by <user name>” Second line of inventory log message should read “Changed from <current CS stock> to <SAME CS stock>”

right after this, there should be a message that reads “Stock for listing <product SKU> (<listing ID>) cannot be updated to <new main stock> as it is not greater than or equal to the stock quantity of <number of reserved stock> that has been reserved for an ongoing Shopee promotion.”
e.g. of an existing inventory log update with first line and second line format:



 

e.g. of this format with the message to be implemented:
Stock for listing ppregrshoes (201681786936) cannot be updated to 2 as it is not greater than or equal to the stock quantity of 3 that has been reserved for an ongoing Shopee promotion.

Manual change to <new main stock> attempted by QC_ghanler. 
Changed from 10 to 10

Exclamation icon indicating an error should be shown at the start of the inventory log message for ALL errors mentioned in the inventory log. This icon is the same one that is currently shown in product alerts. Dev to mention if there are currently any other error messages shown in inventory log. In this case, the icon should be beside the “Manual change…” message and beside “Stock for listing… cannot be updated to…” message


Product alerts

NO CHANGE NEEDED. Ensure this is still shown in the product alerts page.


 

If there are other mp listing(s) &/or Shopee listing(s) without promotions,

the change should be indicated as per normal. Message should be “Manual change by <user name>. Changed from <old main stock> to <new main stock>”. After the manual change message is shown, we will have messages for each of the marketplace listings. In this case, the message for Shopee should be slightly different, and should read as “ Stock for listing <product SKU> (<listing ID>) cannot be updated to <new main stock> as it is not greater than or equal to the stock quantity of <number of reserved stock> that has been reserved for an ongoing Shopee promotion.
Changed from <current CS stock> to <SAME CS stock>”
e.g. of an existing inventory log update with the same format:



e.g. of inventory logs message:
Stock for listing ppregrshoes (14745841457) updated from 10 to 2 as it's different from the main stock.
Changed from 10 to 2. 


Stock for listing ppregrshoes (9358) updated from 10 to 2 as it's different from the main stock.
Changed from 10 to 2. 


Stock for listing ppregrshoes (201681786936) cannot be updated to 2 as it is not greater than or equal to the stock quantity of 3 that has been reserved for an ongoing Shopee promotion.
Changed from 10 to 10


Manual change by QC_ghanler.
Changed from 10 to 2. 


Exclamation icon indicating an error should be shown at the start of the inventory log message for ALL errors mentioned in the inventory log. This icon is the same one that is currently shown in product alerts. Dev to mention if there are currently any other error messages shown in inventory log. In this case, the icon should be beside the “Stock for listing… cannot be updated to…” message


 

Product alerts

NO CHANGE NEEDED. Ensure this is still shown in the product alerts page.


 

 

 Inventory AFTER promotion
Inventory update

NO CHANGE from a regular bulk inventory update

Reason: 

Solution: 
- Create new field user_email, when create the record will add user_email in login, logout
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **


## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2020/feature-csm-2172

4. ---Shopee API - Switch to new stock fields: Step 4C ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-1063

Description: 

Creation of Product on CS

ensure API works well after fields are replaced


When testing, 

Ensure that there is new inventory created if there is no existing inventory

Ensure shopee listing is created for existing inventory, if there is already an existing inventory for other listings

Ensure inventory can be updated in Shopee through CS


** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2020/feature-csm-2172

5. ---P2 Shopee | Getting error while creating product ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2252

Description: 

When we try to create the product for Shopee-MP on CS under laptop category getting the error after filling all the detail “mpsku_item.AddItemRequest.ItemSku: SellerStock: []*openapiv2_common.StockByLocation: decode slice: expect [ or n, but found 3, error found in #10 byte of ...|r_stock":3,"item_sku|..., bigger context ...|top 2202 PP","original_price":555,"seller_stock":3,"item_sku":"LLP laptop 2202","associated_sku":"LL|...“ Please check the attached screenshots and recording.

Reason : Don’t fix for multiple variant

Solution : Fix 'seller_stock' => [['stock' => $normal_stock]],

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **


