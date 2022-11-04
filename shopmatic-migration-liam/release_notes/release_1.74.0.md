# Release Notes version - 1.74.0


**Date:** 13 October 2022



## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2029/csm-2094-p2-wintertime-unlinking-issue-for

1. ---P2 Wintertime - Unlinking issue for Shopee and Qoo10 products ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-2094

Description: 

Unlinking issues are happening for variants (not sure which) under products with associated SKU 88127 and associated SKU 19938. These products are on both shopee and qoo10.



 

When I search for associated SKU 88127, I see that there are duplicates of the same SKU when i toggle to the shopee and qoo10 sections. There is also a lazada listing of the same variants with duplicated SKUs. Main section is working fine.



When I search for associated SKU 19938, I see that there are duplicates of the same SKU when i toggle to the shopee section, while there are no SKUs under qoo10 section. There is also a lazada listing of the same variants with duplicated SKUs. Main section is working fine.




 

What to do

ensure there are no duplicates of SKUs/ missing SKUs under mp sections

explain the condition for unlinking to happen

ensure the right unlinking message should be shown based on the point above (@Eunice Lee to provide the error message after point 2 is explained)

Reason: 

Solution: Changes messages 
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **


## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/2030/csm-1909-p1-ares-marketing-woocommerce

2. ---P2 Ares Marketing - Woocommerce inventory unlinking issue (Part 1) - Change from hard delete to soft delete---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1909

Description: 
Problem

The inventory for SKU 1051steameyemasklavender1sachet and SKU 1490steameyemaskcassia1piece under associated SKU 1050 are linked to 3 woocommerce integrations. 



 

Sometimes when the client updates the inventory individually, the SKUs face unlinking issues. Product alerts show up like this and there is no listing under the inventories. 




The client will need to delete the products on CS, and reimport them to link the inventories to the 3 woocommerce mps again. 

 

What to fix

Please find out why unlinking is happening occasionally, and fix it.

 

Client credentials

Home  - distribution@aresmarketing.com.sg    g87fPm6@

Home - StylePalaceSG  - ops.team@emailstylepalacesg.com  St3p56@&
Reason:  Change from hard delele to soft delete

Solution: $oldProduct->delete();
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **


## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/2031/csm-2199-order-cannot-read-properties-of

3. ---P2 Order: Cannot read properties of undefined (reading 'shortcode')---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2199

Description: 

Reason: JS error occurred

Solution: Put validation
- Create new field user_email, when create the record will add user_email in login, logout
** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **


## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2032/csm-2210-p2-listings-list-is-not-displayed

4. ---P2 | Listings list is not displayed---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2210

Description: 

In addition to the inventory list screen, the total listing is 3, but in the inventory details page, the listing is not displayed

Reason : JS error occurred

Solution : Put validation

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **

## PR Link:https://bitbucket.org/combinesell/combinesell/pull-requests/2020/feature-csm-2172

5. ---P2 Shopee | Getting error while creating product ---

Jira Ticket :https://myshopmatic.atlassian.net/browse/CSM-2252

Description: 

When we try to create the product for Shopee-MP on CS under laptop category getting the error after filling all the detail “mpsku_item.AddItemRequest.ItemSku: SellerStock: []*openapiv2_common.StockByLocation: decode slice: expect [ or n, but found 3, error found in #10 byte of ...|r_stock":3,"item_sku|..., bigger context ...|top 2202 PP","original_price":555,"seller_stock":3,"item_sku":"LLP laptop 2202","associated_sku":"LL|...“ Please check the attached screenshots and recording.

Reason : Don’t fix for multiple variant

Solution : Fix 'seller_stock' => [['stock' => $normal_stock]],

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Harry **


