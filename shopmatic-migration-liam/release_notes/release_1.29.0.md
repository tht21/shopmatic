# Release Notes version - 1.29.0

**Date:** 21st October 2021

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/589/individual-orders-phone-number-to-be-added

1. ** Individual Orders – Phone Number to be Added **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-870

Description:
In combinesell v2 platform, go to Orders > All Orders > Click on one order Under Details, after the section on 'Customer Name', please add a section on 'Phone Number', that has the customer's phone number pulled in from the marketplace. 

Solution: Added new section for phone number

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/588/remove-variation-sku-column

2. ** Excel for Orders – Remove Variation SKU column **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-869

Description:
In combinesell v2 platform, go to Orders > all Orders > download sign > downloaded files to retrieve the excel sheet with a compilation of all orders. In the excel sheet, there are two columns titled 'SKU' and 'Variation SKU'. They contain the same information. Please remove the column titled 'Variant SKU'.

Solution: Remove Variation SKU column when export

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/593/bulk-inventory-update-page-does-not-show

3. ** P3 bulk inventory update page does not show the correct number of products **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-878

Description:
Inventory > Bulk Update > go to the bottom of the page > change the limit to any number apart from 10 > number of products will be reflected accurately > click into any other page > only 10 products will be shown.

Solution: Add limit param into pagination.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
-

4. ** P1: Lazada order import **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-878

Description:
Imports are breaking for accounts with orders that has order items with the status "shipped_back_status"

Solution: Mapped shipped_back_status to returned

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Gerald**

\- **Developed by: Gerald**
