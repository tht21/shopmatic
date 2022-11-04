# Release Notes version - 1.14.2

**Date:** 20th May, 2021

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/296/bugfix-csm-446-bulk-import-product

1. **Bulk import products - product information is missing**

- Ticket : https://myshopmatic.atlassian.net/browse/CSM-446
- Description : Uploaded excel for bulk products failed on first time with error SQLSTATE due to product information missing.
- Fixed:  In Bulk Product Excel Upload , if the basic information is missing for any of the product , then the product is not created and also log message entry is made with the missing fields into “product_import_tasks” table messages column.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/297

2. **New imported products category should be null**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-469

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Ninjoe**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/298/csm-191-import-products-to-delete

3. **Option to remove deleted variants during product import**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-191

4. **Export products to shopify failed**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-484

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Ninjoe**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/299/csm-184-init-common-auth-type-2-remove

5. **Shopify add show only url input**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-184

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Ninjoe**

##PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/300/csm-466

6. **Product export issue l Internal operation team**

- Ticket : https://myshopmatic.atlassian.net/browse/CSM-466
- Description : Ramzul was telling me that we are not using the bulk upload function because when we do bulk upload an error comes out but we dont know which product specifically and the details of the error.  
  *Implication *on the team: The effort required to deep dive and try and find out has forced the listing team to revert to manually data entering each listing within the marketplace itself.  We need to minimise data entry into the marketplaces itself if we have such bulk uploading tools available. Reporter According to Natalie's email: V2 bulk upload tool
- Fixed:  Made it show the columns that are not filled up + which line they are (Currently only for basic information like sku, price and etc)

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Gerald**

\- **Developed by: Gerald**