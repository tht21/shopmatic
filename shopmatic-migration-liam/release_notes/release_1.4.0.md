# Release Notes version - 1.4.0

**Date:** 18th March, 2021

## PR Link 
- https://bitbucket.org/combinesell/combinesell/pull-requests/164/csm-374-order-sync-inventory-deduct

1. **Order sync inventory issue**

- Jira Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-374
- Description : Inventory deduct for orders created before integrated

**No Configuration Changes**

**No DB Changes Required**

**To be Deployed on:** Beta Server 

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Ninjoe**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/165
 
2. **Pricing required when exporting products**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-353
- Description : Price is required during exporting products even thought the price was filled 
- Fixed: Retrieve the price by filtering region id and integration id
When saving the product price insert accurate region currency 

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/166/bugfix-csm-358-incorrect-value-csv

3. **Incorrect color values in export csv and brand is not shown as mandatory**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-358
- Description : Color value selection showing invalid selection and brand field does not have '*'
- Fixed: Added numberic category type checking on ProductAttributesExportExcelJob file and added '*' for brand field 

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/168/csm-383
4. **Inventory Bulk Update error in upload**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-383
- Description : Search for SKU
  Downloaded the file
  Changed the stock count
  Uploaded file back
  Error thrown: mb_strpos() expects parameter 1 to be string, array given
- Fixed: Made sure inventory sync checks for active integration

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Gerald**

\- **Developed by: Gerald**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/169/csm-375-actual
4. **Download export csv file does not work**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-375
- Description : 1)go to exports
  2)choose lazada sg
  3)choose category handbag
  4)click on download excel
  throws server error
- Fixed: Pushed processing of jobs to a background job instead

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Gerald**

\- **Developed by: Gerald**
