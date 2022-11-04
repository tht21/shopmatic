# Release Notes version - 1.61.0

**Date:** 6 July 2022
## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1448/csm-1594-standardise-marketplace

1. Standardise marketplace identifiers on the platform
Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1594
Description: 
Pain Point

Right now, it is confusing when merchants are trying to filter/ choose marketplace products etc if they have multiple mp accounts from the same mp, as we are not indicating the region the mp account is from. 

 

To Implement 

We should indicate the marketplace regions, names, and email addresses in 9 places that do not show them 

It needs to be standardised as well

 

Areas to Implement It 

Filter in All Products Page 
How to get to this page: go to all products > click on filter icon


Marketplace and region needs to be indicated here before the email address. e.g. Lazada SG (eunice.lee@goshopmatic.com)

Marketplace accounts linked to products in All Products Page
How to get to this page: go to all products > look at mp accounts column 


Marketplace and region needs to be indicated here before the email address that should be enclosed in a bracket. formatting wise, marketplace name and region should be one line above email address
e.g. 
Lazada SG 
(eunice.lee@goshopmatic.com)

Mp Category information when adding/ editing product
How to get to this page: click on add new/ edit button in all products page > go to all the sections i.e. main section and mp sections


Mp and region should be added right before the email address mentioned, while email address should be in a bracket.
e.g. Lazada MY (eunice.lee@goshopmatic.com)

Selection of mp sections when adding/ editing product
How to get to this page: click on add new/ edit button in all products page



Mp and region should be added right before the email address mentioned, while email address should be in a bracket.
e.g. Lazada MY (eunice.lee@goshopmatic.com)

Export page - Marketplace category
How to get to this page: Products > Export > click on cs category > mp category pops up


Mp and region should be added right before the email address mentioned, in the same bracket.
e.g. Lazada SG (eunice.lee@goshopmatic.com)

Bulk products page - category names
How to get to this page: Products > bulk products > click on cs category > mp categories pop up


Mp and region should be added right before the email address mentioned, in the same bracket.
e.g. Lazada SG (eunice.lee@goshopmatic.com)

All Inventory page
How to get to this page: Inventory > All inventory > click on one inventory



Mp and Region needs to be indicated above the email address. e.g.
Lazada SG
eunice.lee@goshopmatic.com

Orders Page
How to get to this page: orders > all orders > click on one order


Email address should be added before the region indicated. Marketplace and region should be in the same bracket.
e.g. Shopee SG (eunice.lee@goshopmatic.com)

Bulk orders page
How to get to this page: Orders > Bulk orders


Marketplace and region should be added before the email address that should be in a bracket.
e.g. Shopee SG (eunice.lee@goshopmatic.com)

 

Take note

all countries should be written as an abbreviation. i.e. Singapore > SG, Malaysia > MY, United States > US, india > IN, Canada > CA.

if it is a global mp, it should be abbreviated as GLO
Reason: 
Solution: 
** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Liam **

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1449/csm-1581-p3-getting-goods-inventory

2. P3-Getting Goods Inventory information Error
Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1581

Description: 
When we search for the particular product(hair curler) it is displaying product in products page but in the alert we are getting error “Goods Inventory information Error ([-3] Inventory Update Error)“. 

Sku:-curlersku and Qoo10 Global MP.

Please check the attached recording.

Reason: The product id has 2 child products, but QSM only has 1 product.
Solution: Synchronization does not delete the redundancy in the database.

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Liam **

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1450/csm-1064-p1-export-attribute-type-of

3. P1 Export - Attribute type of attribute_id:100278 is FLOAT_TYPE during export to Shopee
Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1064

Description: 
create product with sku babybottlecap in CS

export product to shopee

successful import

during export, error obtained is Attribute type of attribute_id:100278 is FLOAT_TYPE

same issue is happening for products with variants as well. Associated sku is bottle cap, variants skus are starcap and circlecap
Reason:display attribute name to user in case of failure
Solution: Add new condition to check and show attribute name

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Liam **


## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1451/csm-1669-p1-getting-error-when-export

4. P1- Getting error when export relate Color_family
Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1669

Description: 
When I have entered the entire color_family for all variants, but still get the error message below
Reason: Cause comes from not pushing color_family to XML.
Solution: get attribute color_family and push into XML.

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Liam **

## PR Link:
https://bitbucket.org/combinesell/combinesell/pull-requests/1452/csm-1674-p1-only-seller-price-is-not

5. P1 | Only Seller price is not getting edited.
Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1674

Description: 
While we are exporting the product to Qoo10-Global if I try to edit only Seller price it is not getting edited except that we can edit different fields. Please check the attached recording.

Reason: Since region_id does not exist, price for variant . cannot be obtained
Solution: keep the old condition and change the data sort order

** No Staging Changes configuration:**

** NO Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Liam **

