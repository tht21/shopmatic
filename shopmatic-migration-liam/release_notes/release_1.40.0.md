# Release Notes version - 1.40.0

**Date:** 11 January 2022

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/837/bugfix-csm-1027

1. ** P2-Qoo10- Values in showtable are not saved properly after edit and save **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1027

Description:
1. Create products from bulk product upload
2. Go to  export page and select integration
3. On show table edit values in columns like HS code, Qoo10 shipping code etc and save
Issue: After saving when table is refreshed, value remains unchanged.
https://www.loom.com/share/7a7b88836ecc428a81f8af44d5aba042

Reason: FE only push attribute when it has value.
Solution: Push all attribute even if empty.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/835/bugfix-csm-1024

2. ** P3-Qoo10-Description field not retaining value on update **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1024

Description:
1. create a product and export to Qoo10
2. Go to edit page in CS and update description.
Issue: Description field is not retaining value after saving
https://www.loom.com/share/31d90b1c48a94491ae1e1fbf3c488164
Reason: product data is missing when transform product.
Solution: Keep product data into separate variable.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/836/bugfix-csm-1023

3. ** P3-Qoo10-Brand field is not getting exported **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1023

Description:
Scenario1:
1. create product from bulk upload
2. go to export page and select integration
3. choose the brand and export
the brand does not show in Qoo10 page

Scenario 2:
1. create product from Add new option . select brand while creating.
the brand does not show in Qoo10 page

https://www.loom.com/share/931f6322dc574c00a1a6daf55abeb956 
Reason: When ItemsBasic.UpdateGoods, did not push brand.
Solution: Add brand when ItemsBasic.UpdateGoods.
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link


4. ** P2 Qoo10 - order status does not change after marking shipping info **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-640

Description:
In cs order status is still shown as processing after updating the shipping info however order is moved to ondelivery status in qoo10. (waited more than 15 mins)
Order 340854169

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**