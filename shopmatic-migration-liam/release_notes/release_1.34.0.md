# Release Notes version - 1.34.0

**Date:** 24 November 2021

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/687/bugfix-csm-916

1. ** P2: Export-Lazada- Images are not pushed to lazada **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-916

Description:
steps:
1. create products from Ui in CS
2. add main image(A)and variation image(B) to the product in CS and create
3.after creation in All products page for product-main image(A) is shown and in product page the variation image(B) is shown 
4. In Product edit page the main image(A) is over riden with variation image(B).
5. In lazada  products page, for the product-variation image(B) is shown
6. In lazada, in products edit page, there is no image, if we try to update the product it shows an error message, image is required.
https://www.loom.com/share/b055ba7b71824caea8c089e9864c21f6

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/688/csm-900-p2-export-lazada-export-of-product

2. ** P2:Export- Lazada- export of product fails, when lazada attributes is selected from Drop down list. **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-900

Description:
Steps:
1. Create bulk products through excel
2.select CS category ANIMALS & PET SUPPLIES > PET SUPPLIES > FISH SUPPLIES > AQUARIUM FILTERS
3.select lazada category PET SUPPLIES > PET ACCESSORIES > AQUARIUM NEEDS > FILTERS & ACCESSORIES
4. download the excel and fill the mandatory information, fill attribute animal_pets, from drop down in the excel-cat.
5. upload the file and in export page, in show table, we can see the value of the attribute animal_pets selected from the excel.
6.export the product through action button, it throws an error: Type of Animal:C011:Attribute value that you input is not included in the dropdown list given. Please select from dropdown to avoid error Cat[-1]
7.change the value and select dog from the drop down in the show table and export again, it throws an error :Type of Animal:C011:Attribute value that you input is not included in the dropdown list given. Please select from dropdown to avoid error Dog[-1]
8.again change the value and select the fish,export the product it gets succesfully exported.
Observation:
1.the attributes even selected from drop down fails to export.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/689/csm-918-p2-export-shope-images-are-not

3. ** P2:Export-Shope-images are not pulled in products edit page after export. **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-918

Description:
Steps:
1.create a product through bulk products excel
2.create 2 variants with same image
3.export the product to shpe, after successfull export of product in shpe we can see the main image and variant images also
4. In CS products edit page, there is no image for both main and variations.

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**
## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/690/csm-858-p2-export-lazada-user-getting

4. ** P2 Export-Lazada-User getting error for export after drag and drop image on show table **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-858

Description:
1. Create products on lazada from bulk product upload
2. Got to export page and select integration
3. In show table drag and upload image column add an image
4. Try to export from Actions>Export or Export all
SKU:shpcfp201
Observation: user getting error as THD_IC_F_IC_INFRA_DATATABLE_009:commit insert exception,table name

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**
## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/691/csm-862-p2-export-woocommerce-optional

5. ** P2 Export- Woocommerce- Optional values , images not getting updated from excel **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-862

Description:
1. Create product from bulk uploads to WC
2. Go to export field and download export
3. Update the optional values like purchase note,catalog visibility
4. Upate images in excel
5. Upload excel and refresh table
Observation: Optional fields and images not getting updated in show table

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**
## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/693/csm-853-p1-export-price-is-not-reflecting

6. ** P1 Export -Price is not reflecting correctly across various MPs in show table **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-853

Description:
1. Create product on CS from bulk product upload
2. Go to export page and select any account
3. Select integration and click on show table

Observation: Price field is not getting populated
Also, when excel is uploaded with updated values, price field is not showing
Issue is present accross all marketplaces
Note: Stock not getting updated after uploading the excel and exporting  in Lazada MP

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**


## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/694/csm-917-p1-export-unable-to-export-from

7. ** P1-Export-Unable to export from both excel and ui **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-917

Description:
User getting error as :
Internal Server Error., Invalid argument supplied for foreach()
https://www.loom.com/share/f0622088f4934eb5a10b6ade9b27c16c

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/699/csm-849-p2-export-shopee-new-product

8. ** P2 Export-Shopee-New Product variant getting created on excel upload for product with one variant **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-849

Description:
1. Create product for shopee from bulk product with only one variant
2. Go to export page, select integration category
3. Download the excel, add mandatory fields and upload
Observation: in show table, one new variant with sku name is getting created
SKU: headphn8

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/700/csm-898-p2-export-woocommerce-bulk-product

9. ** P2:Export- Woocommerce- bulk product when exported through excel, the price, length,width, height, weight is set to zero **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-898

Description:
steps:
1.create bulk products through excel and export to woocommerce
2.In export page, in show table , we can able to see the price, length, width, height, weight.
3. the export of product is  successful, we can not see the price in CS edit page
4.the dimensions length, width, height and weight are also set to zero
Observation
1.After successful export, we are not able to see the price in both CS and Mp

** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/701/csm-907-p1-export-single-products-not

10. ** P1:Export-Single products not showing all the columns in show table **

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
