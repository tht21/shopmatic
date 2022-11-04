# Release Notes version - 1.26.0

**Date:** 23th September 2021

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/566/bugfix-csm-699-wc-not-shows-the-updated

1. **P2 Woocommerce - Not shows the updated dimensions value(L W H) for the Woocommerce product in CS**

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-699

Description: 
1.Create a product in Woocommerce MP(SKU: LaptopBag) with  Attribute and many Variations 
2.Import to CS and then Update the Dimensions Value(DIMENSIONS (L W H values)) 
3.Dimensions value(L* W *H) and weight value (SKU:BlackSmallSizebag, BrownSmallSizebag) of the product for "variations" on CS are not updated

Solution: fixed the dimension problem in CS

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Mitrajit**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/567/bugfix-csm-769-does-not-disable-variation

2. **P3-WooCommerce- Disabling variation from CS does not disable variation in WooCommerce**

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-769

Description: 
 1.Create a product with multiple variants in CS and export to woocommerce.
 2.From product main page toggle all/particular variant 
 3.After sucessfully saved , verify the same in woocmmmerce.

Observation: 
 1.Variations are not disabled in woocommerce
 2.Product should become simple product in woocommerce, but still showing a variable product

Solution: Removed the toggle buttons for WC product's variants from the product details page

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Mitrajit**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/564/csm-725-wc-dimesnion-fixed-v201

3. **P2-WooCommerce-Dimensions,weight not populating in Market placewhen product exported to WooCommerce**

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-725

Description:
 1.Create a product and export directly on WooCommerce with multiple variant and optional details like L,W,H,weight from UI
 2.In WooCommerce edit page for the product, dimension details and weight not showing.

Solution: Fixed.Product Exported from CS to WC have no proper dimensions bot with and without variants

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Syed**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/565/bugfix-csm-623-the-main-image-error

4. **P2 : The Main Image error retains**

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-623

Description:
 1.Created the products through csv and exported to shopee sg
 2.The products failed to export, due to  image error.
 3.Changed the image and tried to export but retains the previous image error.

Solution: Fixed.
1. Will not create the product if main image url is broken or invalid.
2. Will continue the creation of the product if any of the additional images are invalid but skip the entry of those invalid additional images.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Syed**
