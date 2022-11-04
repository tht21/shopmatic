# Release Notes version - 1.24.1

**Date:** 16th September 2021

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/547/hotfix-1241-strip-tags-allowed-tags

1. **P1- WooCommerce- Unable to create product from UI on prod**

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-823

Description:
Array To String Conversion in WC Product create & update from UI
Issue is due to the php version in CS Wb Server . The version is PHP 7.4.0 and strip tags "allowed_tag" parameter except the list of allowed tags to be an string but array given.

Fix : Allowed_tags list of tags is changed to string.


** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Syed Asfaquz**

\- **Developed by: Syed Asfaquz**

