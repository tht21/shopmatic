# Release Notes version - 1.21.0

**Date:** 12th August 2021

## PR Link
N/A

1. **Product import not working**

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-610

Description: Ops team tried to re-import Lazada product for Leocato yesterday, but until today it still processing. It doesn't allow me to re-import as it still processing.

Solution: Added a code to check for failing jobs - as sometimees it does not call the failed in the job model - and to mark it as failed.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Gerald**

\- **Developed by: Gerald**

## PR Link
N/A

2. **Export to shopee giving internal error as Internal Server Error., Undefined variable: productImg**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-622
- Description : The variable “productImg” is undefined
- Solution    : Fixed.The variable “productImg” is initialised.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Gerald**

\- **Developed by: Syed Asfaquz Zaman**

## PR Link
N/A

3. **Hide update existing products in production**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-635
- Description : Hide the update existing products part in production
- Solution    : Hide the update existing products part by checking the env 

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Gerald**

\- **Developed by: Jowy Lim**

## PR Link
N/A

4. **Not contain drop down values for "product-id-type, condition" fields on downloaded bulk excel**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-655
- Description : Does not have dropdown selection on downloaded bulk excel
- Solution    : Fixed the excel dropdown code due to library package updated.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Gerald**

\- **Developed by: Jowy Lim**

## PR Link
N/A

5. **Not updating the stock for multiple product**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-671
- Description : Not updating the stock for multiple product for qoo10
- Solution    : Show the correct error message from qoo10 when updating the stock in negative on qoo10

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Gerald**

\- **Developed by: Jowy Lim**

## PR Link
N/A

6. **Shopee - Variation Images not pushed to shopee**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-524
- Description : Image variation does not push to shopee 
- Solution    : Added script to retrieve the product variation images and push to shopee

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Gerald**

\- **Developed by: Jowy Lim**
