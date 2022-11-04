# Release Notes version - 1.15.3

**Date:** 7th June, 2021

## PR Link 
- https://bitbucket.org/combinesell/combinesell/pull-requests/320/csm-516-cross-listing-excel-without-main

1. **Generated cross listing excel doesnt contains variants main images, and images from 1 to 12**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-516
- Description : Generated cross listing excel doesnt contains variants main images, and images from 1 to 12
- Solution - Make sure that Lazada imported images for the variants, and set the main images. Things to note, Lazada Api doesnt return us main product images 
- Solution - for single variant products, first get variant main images, if null, then get it from product main images 
- Solution - get variant's images and assign to image 1 to 12 for both single variant and multiple variants product 

**No Configuration Changes**

**No DB Changes Required**

**To be Deployed on:** Beta Server 

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Ninjoe**
