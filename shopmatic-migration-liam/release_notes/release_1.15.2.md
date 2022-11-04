# Release Notes version - 1.15.2

**Date:** 28th May, 2021

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/313/csm-488-https-myshopmaticatlassiannet

1. **Export table is taking too much time to show correct category filter**

- Ticket : https://myshopmatic.atlassian.net/browse/CSM-488
- Description : Export table is taking too much time to show correct category filter due to missing index and too many resulset fetched.
- Fixed:  
          a. Added index to region_id column in product_images.
          b. Optimised the query in export page.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**DB Changes Required:**

Need to run migration "2021_05_21_181803_add_index_to_product_images.php" to add index to the region_id column in product_images.

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**

