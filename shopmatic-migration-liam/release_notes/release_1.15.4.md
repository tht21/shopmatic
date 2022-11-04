# Release Notes version - 1.15.4

**Date:** 17th June, 2021

## PR Link 
- https://bitbucket.org/combinesell/combinesell/pull-requests/345/bugfix-csm-521-shopee-product-import

1. **Shopee product import stuck**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-521
- Description : Product import stuck
- Solution - Set curl request and response timeout 
- Solution - Reduce Shopee product import per request chunk size from 100 to 50

**No Configuration Changes**

**No DB Changes Required**

**To be Deployed on:** Beta Server 

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Ninjoe**

## PR Link 
- https://bitbucket.org/combinesell/combinesell/pull-requests/346/bugfix-csm-548-lazada-import-products

2. **Lazada import failed**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-548
- Description : Lazada Api failed, error was due to Lazada end 
- Solution - We will try to call Api at least 3 times, before throw exception50

**No Configuration Changes**

**No DB Changes Required**

**To be Deployed on:** Beta Server 

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Jowy**
