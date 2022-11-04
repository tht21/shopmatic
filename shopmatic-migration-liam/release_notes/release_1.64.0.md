# Release Notes version - 1.64.0


**Date:** 29th July 2022

## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1532/csm-1679-p1-healing-movement-amazon-sg


1. --- P1 Healing Movement - Amazon SG import not working ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1679

Description: have tried importing but it failed 4 times


Reason: Report variable is not init.


Solution: To ensure it's working, I have add check isset for $report.

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Steve **

## PR Link: https://bitbucket.org/combinesell/combinesell/branch/release/release_1.64.0


2. --- P1 | Horizon Inventory - Trying to get property 'stock' of non-object in ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1703

Description: https://staging-v2.combinesell.com/horizon/failed/139016219


Reason: Product haven’t inventory


Solution: Add condition check null inventory

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Steve **


## PR Link: https://bitbucket.org/combinesell/combinesell/pull-requests/1531/csm-1804-p1-undefined-index-id-error


3. --- P1 | Undefined index id error  ---

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1804

Description: While doing import action with “herbal pharm“ client account in staging we are getting “Undefined index ; id” error in past import page. Please check the attached screenshot.


Reason: $product->listing->rawData has no ID


Solution: $product->listing->rawData['product-id']

** No Staging Changes configuration:**


** NO Production Changes configuration:**


**No DB Changes Required:**


**To be deployed on:** Beta Server


\- **Signed Off given by: Syed**


\- **Developed by: Steve **
