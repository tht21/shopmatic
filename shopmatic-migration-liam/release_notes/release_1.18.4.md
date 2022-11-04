# Release Notes version - 1.18.4

**Date:** 8th July, 2021

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/399/bugfix-csm-380-image-1-to-12-duplicate

1. **CSM-380 P2 Image duplication for main image, image 1 to 12 on CSV file**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-380
- Description : Image duplication for main image,for image 1 to 12 on CSV file.

- Solution - Image duplication for main image for image 1 to 12 fixed.Image won't be duplicated while downling the CSV.

**No Configuration Changes**

**No DB Changes Required**

**To be Deployed on:** Beta Server

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**

## PR Link
- NIL

2. **CSM-589 P1 Shopee - export to shopee failed**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-589
- Description : Shopee - export to shopee failed.

- Solution - The category id no longer exist in Shopee ,which is fixed by updating the integration
categories production.
Note : No code change for this ticket as we just need to run the categories sync job in beta server which updates the categories in DB.

**No Configuration Changes**

**No DB Changes Required**

**To be Deployed on:** Beta Server

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/400/csm-555

3. **Create a logger for all integrations**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-555

**No Configuration Changes**

**No DB Changes Required**

**To be Deployed on:** Beta Server

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Gerald**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/401/added-cache-column-for-total-orders-for

4. **Orders optimization**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-601

**No Configuration Changes**

**No DB Changes Required**

**To be Deployed on:** Beta Server

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Gerald**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/402

5. **Shopee API retry**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-609
- Shopee will get auth error which was issue on their end, we will retry 5 times before deactive the account

**No Configuration Changes**

**No DB Changes Required**

**To be Deployed on:** Beta Server

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Gerald**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/403/fix-for-duplicated-account-deactivated

6. **Repeated email deactivation alerts**

- Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-611
- make sure account deactivation email is sent once

**No Configuration Changes**

**No DB Changes Required**

**To be Deployed on:** Beta Server

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Gerald**