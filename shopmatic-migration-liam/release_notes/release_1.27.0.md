# Release Notes version - 1.27.0

**Date:** 30th September 2021

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/574/bugfix-csm-814-mandatory-fields-for

1. **Cross listing excel, product basic information mandotory**

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-814

Description: 
- width, dimensions, stock etc should not be mandotry since variants already required, and it was stored in variants 

Solution: have two different set of mandatory field checking for product, and variants 

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Ninjoe**


## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/575/fixed-for-missing-pricing-after-adding-new

2. **p2 Woocommerce : Sale price field is not shown for variations**

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-800

Description:
While creating new product through UI there is no option to enter sale price for variations.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Gerald**


## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/576/added-option-saving

3. **P2-WooCommerce- Options getting removed from UI after product creation in CS**

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-815

Description:
1. Create a product with multiple variant in CS2. Enter product options as red, blue.3. Complete creating the product from ui and verify its exported

Observation: After product is created, in edit page, color option is showing blank and tab name for variation is showing sku name. But its reflecting correctly on WC

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Gerald**


## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/577/csm-682-shopee-weight-fixed

4. **P1 Shopee - Not able to export the shopee product - shows error as "The selected weight.value is invalid"**

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-682

Description:
Steps1.Product =>Export and download the template for shopee MP2.Filled the values in excel and upload the excel file (SKU: juice , LaptopTest)3.Not exported successfully showing error message as ""The selected weight.value is invalid" "

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Jowy**


## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/578/bugfix-csm-498-export-order-csv-file

5. **Export order CSV file"**

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-498

Description:
Incoming orders to be exported into CSV file 

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Mitrajit**
