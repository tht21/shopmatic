# Release Notes version - 1.42.0

**Date:** 25 January 2022

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/863/bugfix-csm-1028

1. ** P2-Qoo10- Images not getting exported correctly when updated in showtable **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1028

Description:
1. Create single and multiple variant product from bulk product upload
2. Go to export page,select integration
3. On showtable update the image and export

Observation: Updated Images not getting exported correctly.
https://www.loom.com/share/2b480798242c447a8351c535ae4cdb2a

Reason: Using product main image.
Solution: Use integration image.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/864/csm-1048-p2-lazada-invalid-character-error

2. ** P2-Lazada-Invalid Character Error while update for specific category **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1048

Description:
1. Create a product in Lazada in phone cases category
https://sellercenter.lazada.sg/apps/product/publish?spm=a1zawe.24863640.table_online_product.d_action_edit.181d4edf8CoaW9&productId=2159411583
2. Import to CS
3. Open product edit page and update
https://staging-v2.combinesell.com/dashboard/products/1103-972phncase

Issue: getting error as "Invalid Character Error Contact admin to resolve it."
https://www.loom.com/share/af9dd8ac05e540f2bdf9ec95772ca839

Note: this is specific category case.
issue found on staging

Reason: Some element has name is 0 then it can not create xml.
Solution: Ignore element when name is not correct.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/865/csm-1055-p2-qoo10-date-format-changes-when

3. ** P2-Qoo10-Date format changes when export excel is downloaded **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1055

Description:
1. create products using bulk product  upload
2. Go to export page and verify that expire date is showing correctly in showtable
3. Generate excel and download excel
4. Observe that expire date format is getting changed to different date format
5. without making any changes upload the same excel
6. observe that show table is not showing expire date anymore

https://www.loom.com/share/e9bab47535ab4430b71f604e8bffaec8

Reason: Date format when export and import are different.
Solution: Use the same format.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/866/bugfix-csm-1057-failed-time-changes

4. ** V2-Failed jobs in queue needs to be removed after 24hrs **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1057

Description:
recent_failed' => 4320,(min)
        'failed' => 4320,(min)
currently failed jobs are stored for 72 hrs, this needs to be changed to 24 hrs

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/867/bugfix-csm-960-merge-release-142

5. ** P1 Query Optimization for Excel Generation **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-960

Description:


** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**
