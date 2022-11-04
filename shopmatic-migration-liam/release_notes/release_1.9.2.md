# Release Notes version - 1.9.2

**Date:** 15th April, 2021

## PR Link 
- https://bitbucket.org/combinesell/combinesell/pull-requests/233/csm-420-https-myshopmaticatlassiannet

1. **P2 Download CSV file errors**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-420
- Description : Download CSV file error 
- Fixed : Undefined checked added for logistics data in excel download.

**No Configuration Changes**

**No DB Changes Required**

**To be Deployed on:** Beta Server 

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/234/bugfix-csm-365-lazada-attributes-fixes

2. **P2 : lazada attributes are not saved in product details**

- Ticket : https://myshopmatic.atlassian.net/browse/CSM-365
- Description : Lazada attributes are not saved in product details 
- Fixed: Lazada attributes like 'brand','model' etc were unset after product listing successfully in MP and were not stored in CS database.Now it's fixed we are not unsetting it anymore.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/235/bugfix-csm-436-shopify-price-wrong

3. **Shopify - Price incorrect after exported**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-436
- Description : Short descripton, Length, width, height and weight, price are incorrect after exported to shopify.
- Fixed: If the product exists we will only update the lenght,weight and height in attribute table instead update the value in product variant table. The price will change to the correct currency.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/236/bugfix-csm-391-product-name-not-updated

4. **Updated product name on CS not synced to lazada sg**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-391
- Description : Updated product name and description on cs, updated successfully on shopee SG and MY lazada MY but not got updated on Lazada sg
- Fixed: Update product name attribute for all integration

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/237/csm-428-shopify-images-fixed

5. **Shopify - images not pushed to shopify**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-428
- Description : Export product to shopify and image does not push to shopify.
- Fixed: When get images should filter with integration id and region id.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**


6. **Email notification for V2 integrations**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-445
- Description : Notify clients when integrations get deactivated through email
- Fixed: Send email notification to shop email

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Gerald**

\- **Developed by: Gerald**
