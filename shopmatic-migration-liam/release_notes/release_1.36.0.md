# Release Notes version - 1.36.0

**Date:** 15 December 2021

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/763/feature-csm-819

1. ** P2 Export/ Import – Shopify Products duplicated if associated sku is not present in marketplace **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-819

Description:
When exporting product with associated sku driedpear2 and variant skus largedriedpear and smalldriedpear from shopee into shopify, another product is created as shopify does not take associated skus. There should only be one product linked to various marketplaces, regardless of whether that marketplace accepts associated skus. 

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/764/bugfix-csm-946

2. ** P2-Error on image update for variation for lazada **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-946

Description:
1. Create and export product on Lazada with variation image
2. Open the edit page for the product on CS
3. Delete the existing various image and Update new variation  images and Save
Note: Issue is coming even if ld variation image is present and new variation image added on top of it
Error:Trying to get property 'image_url' of non-object", exception: "ErrorException"
https://www.loom.com/share/a0e72dfa8f0343a0b84c7af9eedab7f1

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/765/bugfix-csm-954

3. ** P3:shope- same attributes for a category over rides with others after imports **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-954

Description:
steps:
1.create products in shope
2.select category in shope  :Health>Others
3.we can see attribute weight  and also dimension weight.
4.Fill dimension weight and leave the attribute weight as empty and create the product 
5.after import, select cs Category and save the product, it throws an error.
Observation
1.as the attribute and dimension has same name Weight, 
2. In CS the attribute weight takes the dimension weight value.
https://www.loom.com/share/d42c7546e8c140e3b904fbeb0b2debc6

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**