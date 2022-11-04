# Release Notes version - 1.38.0

**Date:** 28 December 2021

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/798/bugfix-csm-957

1. ** P1 Preprod :Orders-Fails to Cancel the orders **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-957

Description:
Environment:
1.1.the issue is occuring on 808 branch which is merged on prepod.
steps:
1.select the orders and then click the cancel the button
2.then select the reason, the drop down has no values.
Observation:
1.it throws an error :You need to select the reason to cancel, but the drop down has no values
2.Cancelling the orders throws the interval server error.
https://www.loom.com/share/734a4e79b9584ab4994544e3e4d43ae7

Reason: Lazada change api endpoint.
Solution: Use new api endpoint order/reverse/cancel/validate.
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/799/bugfix-csm-882

2. ** P2:Lazada Pick Up-No drop down for pick up option- Bulk products Excel **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-882

Description:
steps:
1. In bulk products select Cs category and Lazada Category
2.Download the bulk product excel 
Observation
1.the downloaded excel do not have drop down for delivery_option_store_pick up
Reason: delivery_option_store_pick_up use value = 0 and value = No with the same meaning. 
Solution: Only use No/Yes instead of 0/1.
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/800/bugfix-872

3. ** P2- Lazada Pick Up -Radio button value not retained after creating product from UI **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-872

Description:
1. Create a product from UI(using Add new  option)
2.Select pickup store option as Yes from radio button
3. After product successfully created, again go to edit page

Observation: Yes radio button value not retained
https://staging-v2.combinesell.com/dashboard/products/1103-lstd1/edit
SKU:lstd1 
Reason: delivery_option_store_pick_up use value = 0 and value = No with the same meaning. 
Solution: Only use No/Yes instead of 0/1.
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/801/bugfix-csm-971

4. ** P2: pick up - After setting invoice, there is no option to proceed to fulfill order **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-971

Description:
Environment :
1.the issue is occuring on 808 branch which is merged on prepod.
steps:
1.place pick up orders in lazada
2.In CS Select the order, and then set the invoice number
3.then we do not have any option to further proceed to fulfill order
4.the order remains in packed status only
Observation
1.there is no Ready to ship button to fulfill order from CS.
https://www.loom.com/share/4861554d69ba4d408f419dc4dbbaa79a
Reason: The button 'Ready to ship' is hidden.
Solution: Show button 'Ready to ship'.
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/802/bugfix-csm-876

5. ** P2: Lazada Pick Up-Not able to Arrange shipment for pick up orders From CS **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-876

Description:
steps:
Scenario:
1.In CS, select the lazada order with pick up store,
2.the status of the product is packed in CS
3.post that we are able to do, print documents, set invoice, ready to ship, cancel
4.Now, set invoice and then click ready to ship
5.i throws an error: Unable to mark as RTS because the shipment provider and/or tracking number is invalid.
https://www.loom.com/share/25d006304f79460a943140e47fbed77b
https://www.loom.com/share/f625311016584dd79cff0df1b1b2b6d8
Reason: Lazada api return wrong data.
Solution: When order is pickup, we will use default shipment provider.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/803/bugfix-csm-970

6. ** P2- Error on Update pick up values from UI **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-970

Description:
Scenario1:
1. Create a product with pick up option as Yes.
2. After product created , go to edit page and change  option from yes to no and update

Issue: error on update
Scenario2:
1. Create a product with pick up option as No.
2. After product created , go to edit page and change  option from no to yes and update
https://www.loom.com/share/554254ab631840f09a75800728b54e48
Reason: Sometime data is string, sometime is array then it's no working.
Solution: Check both case string and array.
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/804/csm-928-p2-lazada-crosslisting-error-when

7. ** P2-Lazada crosslisting- Error when dropdown items are uploaded by excel and exported **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-928

Description:
https://www.loom.com/share/16f051672f8042859cd1f5437f17bcd5
1. create products in shopee and import to CS
2. On export page select lazada account
3. Select integration and download the excel
4. Add dropdown items in the excel and upload
5. Export from Actions>Export
error:Internal Server Error., array_key_exists() expects parameter 2 to be array, string given
Reason: Frontend did not handle multi_enum.
Solution: Handle multi_enum.
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/805/csm-1013-try-catch-jsonparse

8. ** P1-Stating-Getting error as SyntaxError: Unexpected end of JSON input when integration category selected in lazada **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1013

Description:
1. Select integration category as sneakers for lazada export.
Issue: user getting error as Getting error as SyntaxError: Unexpected end of JSON input 
this issue also comes when export excel is uploaded and show table is refreshed.
https://www.loom.com/share/f0cc649f4096412dae5ae979ff60bba5
Reason: Atribute name can has array, string, json..then sometime it can not parse.
Soluion: Add try catch to handle unexpected case and set default value is empty.
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/806/bugfix-csm-953

9. ** P2:lazada-improper drop down values for lazada attributes in Excel **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-953

Description:
Steps:
1. Create bulk products through excel
2.select CS category ANIMALS & PET SUPPLIES > PET SUPPLIES > FISH SUPPLIES > AQUARIUM FILTERS
3.select lazada category PET SUPPLIES > PET ACCESSORIES > AQUARIUM NEEDS > FILTERS & ACCESSORIES
4. download the excel and fill the mandatory information, fill attribute animal_pets, from drop down in the excel, the drop down has values {"id":12662,"name":"Fish","en_name":"Fish"}
5.upload the file and in export page, the drop down value appears to be correct, when the product is exported it throws an error
6.Type of Animal:C011:Attribute value that you input is not included in the dropdown list given. Please select from dropdown to avoid error 12662[-1]
https://www.loom.com/share/f1e465bafb7548deacf8a8c62dab6607
Reason: Sometime attribute is array, sometime object.
Solution: Handle both cases.
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

10. ** P1: Chemworks – Lazada Order Not Synced **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-924

Description:
Client: Chemworks
Order ID 65586951154327 from Lazada was not pulled into CS. It is the most recent order on 7th Nov. Other orders before this was pulled in 
Reason: Lazada has new status damaged_by_3pl
Solution: Handle damaged_by_3pl status.
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**


## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/807/bugfix-csm-909

11. ** P1 Implement Lazada AdjustQuantity API **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-909

Description:
From this announcement, Lazada is limiting a few APIs during 11/11, including the API UpdatePriceQuantity which will prevent users from updating prices and inventories of products
They are also coming up with new APIs, which we need to implement before 11/11 so merchants can edit the inventory levels of existing products. Details below:
 
Update:
[NewAPI] - AdjustSellableQuantity and UpdateSellableQuantity is live now
Lazada announcement 
Lazada API document
 
Please implement these APIs in areas where API UpdatePriceQuantity was used to edit inventories of existing products.  
These areas are: 
export csv file
all inventory page
composite inventory page
bulk update inventory page (panel and csv)
 
Acceptance criteria:
Ensure the inventory can be set and increased/decreased for each area, when UpdatePriceQuantity is disabled, to check if the two new APIs work well
Reason: Lazada has separate API when running sale champaign.
Solution: Apply both api.
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/719

12. ** P2:Pick up-No option to set invoice in lazada for pick up orders **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-925

Description:
1.In lazada place pickup orders, the orders are in to ship status.
2.in CS the order is in packed status
3.In lazada, if we print the invoice it dosenot allow user to enter any invoice number, it sets the invoice number which is same as order number
4. In CS, after pick up order is placed, it is in packed status, now if we print the invoice, it asks to enter the invoice number, where as lazada does not ask for pick up orders.
https://www.loom.com/share/17f174364ef44924ae95dcb4ac1fae5d
Reason: Invoice number is not set.
Solution: Set invoice number is this.order.external_id
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/645

13. ** P1 staging -Cross list Lazada error **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-927

Description:
1. Create product in shopee and import to Combinesell
2. Go to export and select lazada account
3. Select integration and click on Actions>Export.
User is getting error as: Internal Server Error., get_headers(): This function may only be used against URLs
https://www.loom.com/share/2a61c91f49b94ff7a766883e15efbe6f
Reason: Image url is missing.
Solution: Use source url when image url is missing.
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**
