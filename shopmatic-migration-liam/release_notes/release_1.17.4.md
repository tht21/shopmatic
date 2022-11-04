# Release Notes version - 1.17.4

**Date:** 1st July, 2021

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/279

1. **Shopify - add error handling**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-275 
- Description : Add error check and error message in our system saying URL is invalid
- Fixed: Adding validation for checking shopify URL instead of redirect to broken URL.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**


## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/384/bugfix-csm-541-lazada-content-length

2. **Lazada - error while exporting the product**

- Ticket : https://myshopmatic.atlassian.net/browse/CSM-541
- Description : Displaying message as "Undefined Length Error after export excel file" message showing wile try to export the product
- Fixed: Added the validation so that in case of missing image from remote url , error “undefined index: content-length” won’t be thrown..

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/385/bugfix-csm-426-shopify-refunding-issue-fix

3. **P2 Beta - Shopify Error refunding shopify order**

- Ticket : https://myshopmatic.atlassian.net/browse/CSM-426
- Description : Shopify Error refunding shopify order
- Fixed:
        a. Handling and showing proper api error message in case of fulfilment of an already fulfilled order or refunded order.
        b. Transaction parameter added the Shopify Refund Api’s payload.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/386/csm-542-image-retrial-implementation

4. **P3 Product alert shows error product image cannot be read and has been deleted**

- Ticket : https://myshopmatic.atlassian.net/browse/CSM-542
- Description : Product alert shows error product image cannot be read and has been deleted
- Fixed:
        a. Retries is implemented for all MP and will attempt 5 times to fetch the image.
           If any exception while fetching the image from Market place the job is  requeued again to be executed in 1 min ~ 60 sec from now.
           After 5 unsuccessful attempt there will be no more attempt to fetch the image.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**


## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/388/csm-584

5. **Support: Manual the conbinesell inventory to shopee platform**

- Ticket : https://myshopmatic.atlassian.net/browse/CSM-584
- Fix :  Instantly queue for syncing of orders - then syncing of all listings to ensure inventory is correct after reactivation of account

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Gerald**

## PR Link
- 

6. **Delete user on admin panels deletes orders, shops and integrations**

- Ticket : https://myshopmatic.atlassian.net/browse/CSM-346

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Gerald**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/389/fix-for-lazada-token-not-being-able-to

7. **Lazada token expiring**

- Ticket : https://myshopmatic.atlassian.net/browse/CSM-590

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Gerald**