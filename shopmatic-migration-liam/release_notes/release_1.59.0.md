# Release Notes version - 1.59.0

**Date:** 22nd June 2022
## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1407/bugfix-csm-1516

1. Export - invalid attribute during export to Shopee

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1516

Description: 
 1.create product in lazada with sku Refrigerator - test 1
 2.import into cs in ghanler account
 3.assign ‘home & garden … > refrigerator’ CS category to it 
 4.export to shopee under refrigerator category
 5.edit show table > save > export
 6.past exports page error: Invalid attribute,spex error : global_attr_id=100278 is FREE_TEXT quantitative_attribute: invalid field|field_name=item_attr_value_ext_info.customised_value|err=field must be nil.|: item.attribute.set_item_attr_list failed attribute [100355] value unit should be in W

Reason: Some attributes has QUANTITATIVE type then it will need unit data.
Solution: Add 'unit_list' to attributes   


** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Steve **
## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1403/csm-1632-p1-exporting-to-qoo10-global-is

2. Exporting to Qoo10 Global is getting fail relate SKU

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1632

Description: 
After fixing the error in 1599 I discovered a new error related to SKU when running the job
{"meta":{"error":true,"message":"Internal Server Error!","status_code":500},"response":[],"debug":[{"message":"Undefined index: sku","exception":"ErrorException","file":"\/Users\/synguyen\/Downloads\/combinesell\/app\/Integrations\/TransformedProductListing.php","line":148,"trace":...

Reason: Not check null condition relate SKU
Solution: Check null condition relate SKU 


** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Steve **

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1404/bugfix-csm-1570

3. DB Optimisation Required for Product Image Update

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1570

Description: 
Optimisation Required for Product Image Update

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Syed **

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1405/bugfix-csm-1506

4. Woocommerce FatalThrowableError: Call to a member function getIdentifier() on null

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1506

Description: 
from syed: This specific error is happening as we are not getting the data that need to pass to the function getIdentifier so we have to handle it by not calling this function when the parameter is empty related to inventory, when external id info is not passed to us. Implications: jobs will keep retrying if this info is not found

Reason: function getIdentifier() null 
Solution: Add condition

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Liam **

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1402/csm-1531-p1-alice-claudine-wrong-delivery

5. Wrong delivery provider

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1531

Description: 
Order 79120713708164 and Order 78489033320203 show that the only delivery option is singpost mail. However, client has ninjavan as their default delivery provider. Please check why the wrong delivery provider is shown here and how it can be fixed

Reason: Can not call api order/pack when provider null.
Solution: Set default value for provider.

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Liam **

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1406/csm-1599-p1-exporting-to-qoo10-global-is

6. Exporting to Qoo10 Global is getting fail

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1599

Description: 
When we try to export individual product by clicking on Show table to Qoo10 Global getting error “Internal Server Error., count(): Parameter must be an array or an object that implements Countable“. Please check the attached screenshot and recording.

Reason: Duplicate function setTaskMessages because conflict
Solution: Remove function setTaskMessages

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Steve **