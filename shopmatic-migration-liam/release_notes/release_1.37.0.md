# Release Notes version - 1.37.0

**Date:** 22 December 2021

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/776/feature-csm-977

1. ** Google Analytics: Install google tag manager **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-977

Description:
Paste this code as high in the <head> of the page as possible:
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-KPMMCZQ');</script>
<!-- End Google Tag Manager -->
Additionally, paste this code immediately after the opening <body> tag:
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KPMMCZQ"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
This should take effect on every page of the v2 platform

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/777/csm-913-p1-woocommerce-unable-to-export

2. ** P1-Woocommerce-Unable to export product from export page **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-913

Description:
Issue found on stage:
1.Create multiple product from bulk product upload
2. Go to export page and select integration.
3. Do export all
Issue:Internal Server Error., Trying to get property 'inventory' of non-object	
Note: Same issue in case of Actions>Export

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/778/feature-csm-974

3. ** P2 Herbal Links: Include product names in the first column of the bulk update inventory excel **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-974

Description:
New inventory file should have an additional column on the left, with column name 'Product Name'. Column values should be product names linked to the SKUs. 
The order of products should follow the current order in the code, which is the oldest product to the newest product imported into combinesell, if i am not wrong. Need to check and confirm this. 

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/779/csm-952-p2-lazada-export-thumbnail-not

4. ** P2-Lazada export thumbnail not showing in CS and MP after the export **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-952

Description:
1. Create products with variants for lazada through bulk product upload
2. Go to export page and select integration and category
3. Drag and drop image and click on Actions>Export
Issue: After the product is exported, the thumbnail is not showing in lazada. When check in CS product edit page, color thumbnail is also not present there.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/782/bugfix-csm-921

5. ** P1-Lazada export- Variation information error on export page **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-921

Description:
Getting different types of error when trying to export from export page such as:
1.Variation Information:BIZ_CHECK_PACKAGE_NUMERIC_NEGATIVE:Package attribute value cannot be negative [com.alibaba.gpf.sdk.model.pagemodel.PageValueModel@27d93678]PFA video for the same
SKU:testpet1
2.Variation Information:C008:Duplicate sales attribute, the repeated attribute is
https://www.loom.com/share/40771d4b4944441a9a739a13b004d9b1

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/781/csm-978-p1-staging-unable-to-change-order

6. ** P1 (Staging) Unable to Change Order from Ready to Ship to Delivered **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-978

Description:
Blocker for tickets with all orders (pick up and regular orders)
Order 68434323356860
Order 68521304956860
issue come when user trying to change status from Ready to set to Delivered
Please find out what API this is

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**


## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/786/bugfix-csm-907-update-price-by-integration

7. ** P1:Export-Single products not showing all the columns in show table **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-907

Description:
steps in staging:
1. create products through bulk product excel
2. in export page, select the category and in show table there are no fields for dimensions to add or edit
3.if the product is exported, for shpe it throws an error: Call to a member function prices() on null
4. if the product is exported to lazada it throws an error: Variation Information:C004:Variation Information is required

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**
