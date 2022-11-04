**Release Notes version - 1.32.5**

**PR Link:** https://bitbucket.org/combinesell/combinesell/pull-requests/95/release-110

**Date:** 18th Feb, 2021

**Changes :**

>**1. Admin User Management**

Ticket:- https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-295

Add a function to delete user account 

Ticket:- https://myshopmatic.atlassian.net/jira/software/projects/CSM/boards/7?selectedIssue=CSM-296

Duplicate 'create' button under users section


Contains configuration changes: 

**No Staging Changes configuration:**

**No Production Changes configuration:**

**DB migration involved in this feature.**

```
Drop column stripe_id from shops table 
```

**To be deployed on:**
    
    1. PRO Server
\- **SignedOff given by: Ninjoe**
\- **Developed by: Gerald**          