# Release Notes version - 1.8.2

**Date:** 8th April, 2021

## PR Link 
- https://bitbucket.org/combinesell/combinesell/pull-requests/208/csm-190-https-myshopmaticatlassiannet

1. **P2 Shopify - images not pulled in cs**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-190
- Description : Initially we were fetching from “images” which always returns a single main image even if we have multiple images.Now in this fix its fetched from “image” where we have multiple images.
If the image does not have variant_ids it’s the main image.So considering all those image for the main image

**No Configuration Changes**

**No DB Changes Required**

**To be Deployed on:** Beta Server 

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/213/csm-387-fixed-shopify-generate-csv-and

2. **Shopify generate export CSV file without selecting category and export**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-387
- Description : Shopify generate export CSV file error message and unable to export product
- Fixed: Frontend checking changed and csv file format support for shopify account category.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/214/csm-217-shopify-order-checkbox

3. **Shopify - order is not selected when checkbox is checked**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-217
- Description : Shopify order checkbox item is not working 
- Fixed: Fixed the checkbox item on frontend

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/215/csm-277-export-products-api-limitation

4. **Shopify - API call limit issue for shopify export**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-279
- Description : Shopify API limit call exceeded
- Fixed: wait for 1 sec to export the product, fixed it in CSM-277 branch since both is related issue.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/215/csm-277-export-products-api-limitation

5. **Shopify - export products to shopify failed**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-277
- Description : Export product failed for few products with internal server error
- Fixed: This is due to the api limitation exceeded, will wait for 1 sec to retrieve the product after exported.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/216/csm-356-fixed-shopee-image-content-length

6. **Export to Shopee MY fails when product has variations**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-356
- Description : Products has 4 variations now export the product to Shopee MY..all other products are exported successfully
- Fixed: The error message is due to the image uploaded is invalid. Already added some image checking and return a proper message.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/218/csm-419-implemented-shopify-for-csv-file

7. **Enable bulk product csv file for shopify**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-419
- Description : Enable csv file on bulk product for shopify
- Fixed: Implemented shopify all categories for downloading csv file on bulk products

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/219/bugfix-csm-411-edit-products-error-fix

8. **P2 Cannot select marketplace from edit products page**

- Ticket : https://myshopmatic.atlassian.net/browse/CSM-411
- Description : P2 Cannot select marketplace from edit products page
- Fixed: Able to load edit page based Marketplace selection from dropdown.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**


#PR Link 
- None 

9. https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-186

- Fixed while fixing other issues

10. https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-424

- Enable Shopify 

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: -**