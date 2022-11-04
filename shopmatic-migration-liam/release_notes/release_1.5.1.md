# Release Notes version - 1.5.1

**Date:** 23rd March, 2021

## PR Link 
- https://bitbucket.org/combinesell/combinesell/pull-requests/177/csm-386-sync-inventory-check-account

1. **Listing inventory not sync**

- Jira Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-386
- Description : Stock not udpated to listing 

**No Configuration Changes**

**No DB Changes Required**

**To be Deployed on:** Beta Server 

\- **Signed Off given by:  Ninjoe**

\- **Developed by: Ninjoe**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/178/csm-379-single-export-product-failed

2. **Save button not working for single product in export page**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-379
- Description : Changes are not save on product on export page
- Fixed: Added region id for attribute in transform product listing.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/179

9. **Export all exports only 1 product**

- Ticket : https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-384
- Description : During export all products system throw error 'product already exported' message
- Fixed: The issue happened is because some of the products is already exported to marketplace previously, and user deleted the product listing.
When user wanted to export the same product again the system will check the latest export task status and the latest status is 'finish exported'.
Added when user delete any product listing will delete the export task data as well.

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Jowy Lim**

## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/180

10. **P2 Selected optional attributes were prefilled**

- Ticket : https://myshopmatic.atlassian.net/browse/CSM-361
- Description : Selected optional attributes were already prefilled (is_pre_order, days_to_ship_, condition) 
- Fixed: 
    Downloading CSV file to export products to a new marketplace 

    No optional attributes should be pre-filled 

    Marketplace-specific fields should be empty

    Downloading CSV file to edit the listings and re-uploading the file to the export page (in the event of failed export)

    All previously filled in details should be retained

**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**


## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/180

11. **P2 : Logistics value is coming as true in export csv**

- Ticket : https://myshopmatic.atlassian.net/browse/CSM-362
- Description : Selected optional attributes were already prefilled (is_pre_order, days_to_ship_, condition) 
- Fixed : Is logistic enabled it will be appear as "Yes" in CSV
    
**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**


## PR Link
- https://bitbucket.org/combinesell/combinesell/pull-requests/180

12. **P2 : condition value invalid error for shopee MY export**

- Ticket : https://myshopmatic.atlassian.net/browse/CSM-363
- Description : In downloaded excel Condition value is auto filled as NEW(should be New or Used)
- Fixed : Condition value if exist will be  auto filled as New or Used.
    
**No Staging Changes configuration:**

**No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Ninjoe**

\- **Developed by: Syed Asfaquz Zaman**



