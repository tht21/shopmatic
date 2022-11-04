# Release Notes version - 1.60.0

**Date:** 30th June 2022
## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1423/bugfix-csm-1487-1572

1. P1 horizon - OrderSyncJob fail Shopify
Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1487

Description: 
https://beta.combinesell.com/horizon/failed/352675801
Exception: Shopify has unsupported item status in /var/www/html/combinesell/app/Integrations/Shopify/OrderAdapter.php:377
Alice Claudine Shopify

Reason: Can not access horizon and dont know what account, what order id, what is new status.
Solution: Improve log to detect necessary infomation quickly.

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Steve **

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1424/csm-1575-p1-qoo10-categories-not-updating

2. P1 Qoo10 categories not updating
Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1575

Description: 
Import categories cronjob ran successfully in Production.
I checked the Database , Lazada,Shopee Categories were imported
For Qoo10 SG , there were no categories update and For Qoo10 Global only 10 categories were updatedPlease check it once.

Reason: Because update_at doesn't update the date
Solution: Update time for update_at

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter **

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1425/bugfix-csm-1631

3. P1 Shopee Export | Getting "Contains invalid attribute value" error
Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1631

Description: 
When we try to export the product to Shopee after filling the all the mandatory info we are getting error in past export page as "Contains invalid attribute value".

Reason: A few attributes has empty value but they are not required.
Solution: If attribute is not required and value is empty, we don't push them to Lazada API.

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter **

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1426/csm-1663-p1-goods-order-information-save

4. P1 | Goods Order information Save Error in Qoo10-Global
Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1663

Description: 
When we try to export by clicking on show table under Qoo10-Global facing saving issue. If we enter some data and tries to save but that particular data is not getting saved again it is getting updated with default or previous data and getting error like "Goods Order information Save Error ([-1004] fail to make order information)" in past export page. Please check the attached screenshots and recording.

Reason: Item Price is zero then Qoo10 does not allow.
Solution: Add validation to make sure item price > 0.

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Steve **

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1427/csm-1655-p3-ares-marketing-delete-button

5. P3 Ares Marketing - Delete button for product is missing
Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1655

Description: 
associated sku: sku1211maincableparent
delete button is missing. Please fix it

Reason: Check null listings.account.
Solution: Remove listing with null account.

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Steve **

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1428/csm-1660-p1-saving-issue-with-many

6. P1 | Saving issue with many Attributes while doing Show Table export
Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1660

Description: 
When we try to do Lazada export action by clicking on Show table it is asking for different attribute to fill before exporting as an error but if we fill the required attributes in an show table it is not getting saved while saving and again it is asking for the same attribute to fill. Please check the attached screenshots and recordings. 

Reason: in function attributes of Product variants model using ->whereNull('product_listing_id').
Solution: Remove this condition and add condition for the logic related to the old function.

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Steve **

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1423/bugfix-csm-1487-1572

7. P1 Alice Claudine Shopify - failed jobs
Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1572

Description: 
Too many failed job for these account and its resulted high memory consumption

Reason: fulfillment_status is restocked
Solution: $itemFulfillmentStatus for fulfillment_status when it is restocked

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Steve **

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1430/csm-1629-p2-horizon-unable-to-retrieve

8. P2 Horizon - Unable to retrieve Shopify orders
Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1629

Description: 
to fix this issue, check the logs to see why the api fetchOrder is not returning data for the specified client

Reason: Exceeded 2 calls per second for api client. Reduce request rates to resume uninterrupted service
Solution: use Cache::log

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Steve **
