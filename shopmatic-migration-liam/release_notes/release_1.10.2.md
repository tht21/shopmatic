# Release Notes version - 1.10.2

**Date:** 20th April, 2021

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/247/csm-450-amend-email-notification-text

1. **Amend the text of email**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-450 
- Description : Change the text of email notification for disabled account.
- Fixed: Changed the email text.

** No Staging Changes configuration:** 

** No Production Changes configuration:** 

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/250/csm-275-shopify-invalid-url

2. **Shopify - add error handling for shopify integration**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-275
- Description : Add new account for shopify, enter incorrect url format and system redirect to a broken page.
- Fixed: Added url validation for input field.

** No Staging Changes configuration:** 

** No Production Changes configuration:** 

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/251/csm-421-tinymce-update-version

3. **Edit product page html description field not showing icons**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-421
- Description : On edit product page html description tinymce is not showing icon on toolbar.
- Fixed: Updated the tinymce library version.

** Staging Changes configuration:**
- Run npm 

** Production Changes configuration:**
- Run npm 

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/252/csm-442-shopify-export-weight-unit-fixed

4. **Shopify Export Single Product failed**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-442
- Description : When export single product without selecting weight unit and system will thrown error message "{"errors":{"weight_unit":["'[]' is not a valid weight unit"]}}".
- Fixed: Added checking when weight unit is in empty json string, will change to empty string.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**


## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/249/csm-448-https-myshopmaticatlassiannet

5. **P2 Shopify - Under products page shows a list of the same products**

- Ticket : https://myshopmatic.atlassian.net/browse/CSM-448
- Description : Under prices & stock has 2 different types shown, selling and retail for Shopify.
- Fixed: Fixed Only price type selling will be displayed in product page.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**
