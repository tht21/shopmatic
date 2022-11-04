# Release Notes version - 1.44.0

**Date:** 16 February 2022

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/906/csm-1071-p2-lazada-pick-up-store-indicate

1. ** P2 Lazada Pick Up Store - Indicate Shipment Provider in Order Details **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1071

Description:
right now, the order does not indicate the delivery provider if it is store pick up.
need to ensure that the provider is shown to the user (reference pic below) when lazada returns us store pick up as shipment provider through the API

Reason: Did not implement code when shipment_method == 'pickup_in_store'.
Solution:Implement new logic.
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/907/csm-1071-p2-lazada-pick-up-store-indicate

2. ** P1 Client Email Notifications Not sent out when new orders come in **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1113

Description:

GuzzleHttp\Exception\ServerException

Server error: POST https://api.mailgun.net/v3/mg.combinesell.com/messages.mime resulted in a 500 Internal Server Error response:
{"message": "Internal server error"}

facing error sending email notifs when new orders are coming in
Reason: Can not detect what order has issue.
Solution:Add log to know more details.
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**

## PR Link
https://bitbucket.org/combinesell/combinesell/pull-requests/908/csm-1081-p1-delays-jobs-for

3. ** P1 Delays jobs for UploadProductImage **

Jira Ticket : https://myshopmatic.atlassian.net/browse/CSM-1081

Description:
Right now, when a product image is uploaded into CS (from mp product imports/ creation of new products on CS), url is stored in master db, and the the slave db takes time to replicate this data. The job is processed right away to check for url in slave db, so when is a lag time, the url is not present yet and will result in an error. After three tries, the job fails and the product images are not shown in CS.

We need to increase the delay time of the next two calls after the first one so that the slave db is given some time to replicate the data.
Reason: The problem is in the use Illuminate\Queue\SerializesModels;, which tries to serialize the model param which you're passing to the constructor. Since your model is not saved yet, it doesn't have the identifier.
According to Laravel documentation:
If your queued job accepts an Eloquent model in its constructor, only the identifier for the model will be serialized onto the queue. When the job is actually handled, the queue system will automatically re-retrieve the full model instance from the database.
Solution: The workaround would be to remove SerializesModels trait from the Job class you're willing to save your model in.
** No Staging Changes configuration:**

** No Production Changes configuration:**

**No DB Changes Required:**

**To be deployed on:** Beta Server

\- **Signed Off given by: Michael**

\- **Developed by: Peter**
