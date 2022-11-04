# Requirements

- Using memcached / redis (For syncing as we're using atomic locks)

# Installation

Run the following in CLI

```sh
composer install
php artisan key:generate
php artisan migrate
php artisan passport:install
php artisan db:seed
php artisan init:common
npm install
npm run dev
```
 
 --- 
# Creating Admin Account

```sh
php artisan user:create
```
 
 --- 
# Deployment Script

The following code should be run after every new commit / deployment

```sh
composer install --prefer-source --no-interaction --no-dev
php artisan migrate --force
php artisan view:clear
php artisan route:cache
php artisan config:cache
php artisan init:common
```

### Supervisor

You will need to restart supervisor if it's configured.
 
 
 ---
# Important Notes 

### Integration Categories
  - Categories are updated and refreshed when `init:common` is called
  - We're assuming that all categories names for the integration will not change. If we do want to cater to this, we will need to rewrite `AbstractProductAdapter::updateCategories()`
  - `main_account_id` is the main id used to refresh the TEMPORARY data for products. So this is what is displayed to users.
  - Command to run for specific marketplace and region: php artisan init:category {integrationName} {region_id}
  
 
 ---

# TODO
_Please remove as we clear this. There is also TODO in comments so do check them out._

1. Emails base layout and customization.
2. All the email notifications.
3. All emails should be pushed to a queue and not performed synchronously.
4. Add observers for models to hook onto the `saving` method to perform validation (Especially for enum fields), prior to actually saving. Alternatively we can use a package like [JeffreyWay/Laravel-Model-Validation](https://github.com/JeffreyWay/Laravel-Model-Validation). But we might want to build it / find other packages as it seems that the package's last update is 6 years ago.
5. Optimize category attributes (Especially duplicated attributes in `IntegrationCategory`)
6. Configurable notifications for users (E.g. Account status updates)
7. Notifications AJAX (With unread count)
8. Check if product HTML description is the same when importing back from the marketplace (This is to prevent unwanted customization for the product's description)
9. For product related attributes, if there's a same name, can prompt for user to automatically use those fields instead.
10. Add in an event / listener for when a new account is added / reactivated. This is for it to pull all orders / information / etc / start the sync.
11. If first account added, prompt what's next -> import products
12. Create an archive of the audit logs every month to prevent it from growing too big.
13. Create a cache table for product listing identifiers to speed up search (Low priority, see if we actually need this or not)
14. *HIGH PRIORITY*: When creating product in product adapter, should check product listing to find external_id for the main product instead of using associated SKU.
15. Fix sorting for tables / index pages
16. Allow for session settings, and in Product updateTempFields, to return data based on that setting. An example would be the currency / etc
17. For product importing, add in an array of configurable options instead of the current single `update` option.
18. Auto prune logs / trails and archive them somewhere else off the DB.
19. Bulk lazada processing (Including document printing)
20. php artisan init:category Qoo10 1
