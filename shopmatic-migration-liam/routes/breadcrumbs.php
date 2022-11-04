<?php

use DaveJamesMiller\Breadcrumbs\Facades\Breadcrumbs;

Breadcrumbs::for('index', function ($trail) {
    $trail->push('<i class="fas fa-home"></i> Home', route('index'));
});

Breadcrumbs::for('pricing.index', function ($trail) {
    $trail->parent('index');
    $trail->push('Pricing', route('pricing.index'));
});

Breadcrumbs::for('about-us.index', function ($trail) {
    $trail->parent('index');
    $trail->push('About Us', route('about-us.index'));
});

Breadcrumbs::for('contact.index', function ($trail) {
    $trail->parent('index');
    $trail->push('Contact', route('contact.index'));
});

Breadcrumbs::for('integrations.index', function ($trail) {
    $trail->parent('index');
    $trail->push('Integrations', route('integrations.index'));
});

Breadcrumbs::for('enterprise.index', function ($trail) {
    $trail->parent('index');
    $trail->push('Enterprise', route('enterprise.index'));
});

Breadcrumbs::for('end-to-end.index', function ($trail) {
    $trail->push('Home', route('end-to-end.index'));
});

Breadcrumbs::for('terms.index', function ($trail) {
    $trail->parent('index');
    $trail->push('Terms of Service', route('terms.index'));
});

Breadcrumbs::for('privacy.index', function ($trail) {
    $trail->parent('index');
    $trail->push('Privacy Policy', route('privacy.index'));
});

Breadcrumbs::for('login', function ($trail) {
    $trail->parent('index');
    $trail->push('Login', route('login'));
});

Breadcrumbs::for('register', function ($trail) {
    $trail->parent('index');
    $trail->push('Register', route('register'));
});

Breadcrumbs::for('password.request', function ($trail) {
    $trail->parent('index');
    $trail->push('Forgot Password', route('password.request'));
});

Breadcrumbs::for('password.reset', function ($trail, $token) {
    $trail->parent('index');
    $trail->push('Reset Password', route('password.reset', [$token]));
});

Breadcrumbs::for('verification.notice', function ($trail) {
    $trail->parent('index');
    $trail->push('Verify Email', route('verification.notice'));
});

Breadcrumbs::for('articles.index', function ($trail) {
    $trail->parent('index');
    $trail->push('Knowledgebase', route('articles.index'));
});

Breadcrumbs::for('articles.show', function ($trail) {
    $trail->parent('index');
    $trail->push('Article', route('articles.show', 'id'));
});

Breadcrumbs::for('dashboard.index', function ($trail) {
    $trail->push('<i class="fas fa-home"></i> Home', route('dashboard.index'));
});

Breadcrumbs::for('dashboard.notifications.index', function ($trail) {
    $trail->parent('dashboard.index');
    $trail->push('Notifications', route('dashboard.notifications.index'));
});

Breadcrumbs::for('dashboard.account.categories.index', function ($trail) {
    $trail->parent('dashboard.index');
    $trail->push('Accounts', route('dashboard.accounts.index'));
    $trail->push('Categories', route('dashboard.account.categories.index'));
});

Breadcrumbs::for('dashboard.account.categories.create', function ($trail) {
    $trail->parent('dashboard.account.categories.index');
    $trail->push('Create', route('dashboard.account.categories.create'));
});

Breadcrumbs::for('dashboard.account.categories.edit', function ($trail, $accountCategory) {
    $trail->parent('dashboard.account.categories.index');
    $trail->push($accountCategory->name, route('dashboard.account.categories.edit', [$accountCategory->getRouteKey()]));
});

Breadcrumbs::for('dashboard.accounts.index', function ($trail) {
    $trail->parent('dashboard.index');
    $trail->push('Accounts', route('dashboard.accounts.index'));
});

Breadcrumbs::for('dashboard.accounts.create', function ($trail) {
    $trail->parent('dashboard.accounts.index');
    $trail->push('Create', route('dashboard.accounts.create'));
});

Breadcrumbs::for('dashboard.accounts.setup', function ($trail, $account) {
    $trail->parent('dashboard.accounts.index');
    $trail->push('Setup', route('dashboard.accounts.setup', [$account->getRouteKey()]));
});

Breadcrumbs::for('dashboard.shops.index', function ($trail) {
    $trail->parent('dashboard.index');
    $trail->push('Shops', route('dashboard.shops.index'));
});

Breadcrumbs::for('dashboard.shops.create', function ($trail) {
    $trail->parent('dashboard.index');
    $trail->push('Create', route('dashboard.shops.create'));
});

Breadcrumbs::for('dashboard.billing.index', function ($trail) {
    $trail->parent('dashboard.index');
    $trail->push('Billing', route('dashboard.billing.index'));
});

Breadcrumbs::for('dashboard.billing.create', function ($trail) {
    $trail->parent('dashboard.index');
    $trail->push('Create Billing', route('dashboard.billing.create'));
});

Breadcrumbs::for('dashboard.subscriptions.index', function ($trail) {
    $trail->parent('dashboard.index');
    $trail->push('Subscriptions', route('dashboard.subscriptions.index'));
});

Breadcrumbs::for('dashboard.subscriptions.create', function ($trail) {
    $trail->parent('dashboard.index');
    $trail->push('Create Subscriptions', route('dashboard.subscriptions.create'));
});

Breadcrumbs::for('dashboard.products.index', function ($trail) {
    $trail->parent('dashboard.index');
    $trail->push('Products', route('dashboard.products.index'));
});

Breadcrumbs::for('dashboard.products.create', function ($trail) {
    $trail->parent('dashboard.products.index');
    $trail->push('Create Product', route('dashboard.products.create'));
});

Breadcrumbs::for('dashboard.products.show', function ($trail, $product) {
    $trail->parent('dashboard.products.index');
    $trail->push($product->name, route('dashboard.products.show', [$product->getRouteKey()]));
});

Breadcrumbs::for('dashboard.products.edit', function ($trail, $product) {
    $trail->parent('dashboard.products.show', $product);
    $trail->push('Edit', route('dashboard.products.edit', [$product->getRouteKey()]));
});

Breadcrumbs::for('dashboard.products.bulk', function ($trail) {
    $trail->parent('dashboard.products.index');
    $trail->push('Bulk Products', route('dashboard.products.bulk'));
});

Breadcrumbs::for('dashboard.products.bulk.categories', function ($trail) {
    $trail->parent('dashboard.products.index');
    $trail->push('Bulk Edit Categories', route('dashboard.products.bulk.categories'));
});

Breadcrumbs::for('dashboard.products.import', function ($trail) {
    $trail->parent('dashboard.products.index');
    $trail->push('Import', route('dashboard.products.import'));
});

Breadcrumbs::for('dashboard.products.import.tasks', function ($trail) {
    $trail->parent('dashboard.products.import');
    $trail->push('Tasks', route('dashboard.products.import.tasks'));
});

Breadcrumbs::for('dashboard.products.export', function ($trail) {
    $trail->parent('dashboard.products.index');
    $trail->push('Export', route('dashboard.products.export'));
});

Breadcrumbs::for('dashboard.products.export.tasks', function ($trail) {
    $trail->parent('dashboard.products.export');
    $trail->push('Tasks', route('dashboard.products.export.tasks'));
});

Breadcrumbs::for('dashboard.products.alerts.index', function ($trail) {
    $trail->parent('dashboard.products.index');
    $trail->push('Alerts', route('dashboard.products.alerts.index'));
});

Breadcrumbs::for('dashboard.inventory.index', function ($trail) {
    $trail->parent('dashboard.index');
    $trail->push('Inventory', route('dashboard.inventory.index'));
});

Breadcrumbs::for('dashboard.inventory.show', function ($trail, $inventory) {
    $trail->parent('dashboard.inventory.index');
    $trail->push('View Inventory', route('dashboard.inventory.show', [$inventory->getRouteKey()]));
});

Breadcrumbs::for('dashboard.inventory.composite.index', function ($trail) {
    $trail->parent('dashboard.inventory.index');
    $trail->push('Composite Inventory', route('dashboard.inventory.composite.index'));
});

Breadcrumbs::for('dashboard.inventory.update.index', function ($trail) {
    $trail->parent('dashboard.inventory.index');
    $trail->push('Bulk Update', route('dashboard.inventory.update.index'));
});

Breadcrumbs::for('dashboard.orders.index', function ($trail) {
    $trail->parent('dashboard.index');
    $trail->push('Orders', route('dashboard.orders.index'));
});

Breadcrumbs::for('dashboard.orders.show', function ($trail, $order) {
    $trail->parent('dashboard.orders.index');
    $trail->push("View Order", route('dashboard.orders.show', [$order->getRouteKey()]));
});

Breadcrumbs::for('dashboard.orders.pickup', function ($trail) {
    $trail->parent('dashboard.orders.index');
    $trail->push("View Pickup", route('dashboard.orders.pickup'));
});

Breadcrumbs::for('dashboard.orders.bulk', function ($trail) {
    $trail->parent('dashboard.orders.index');
    $trail->push("Orders Bulk", route('dashboard.orders.bulk'));
});

Breadcrumbs::for('dashboard.reports.index', function ($trail, $keyword) {
    $trail->parent('dashboard.index');
    $trail->push(($keyword == 'retail') ? ucfirst($keyword).' Dashboard' : ucfirst($keyword).' Report', route('dashboard.reports.index', $keyword));
});

Breadcrumbs::for('dashboard.tickets.index', function ($trail) {
    $trail->parent('dashboard.index');
    $trail->push('Support', route('dashboard.tickets.index'));
});

Breadcrumbs::for('dashboard.tickets.create', function ($trail) {
    $trail->parent('dashboard.tickets.index');
    $trail->push('Create Ticket', route('dashboard.tickets.create'));
});

Breadcrumbs::for('dashboard.tickets.show', function ($trail, $id) {
    $trail->parent('dashboard.tickets.index');
    $trail->push('View Ticket', route('dashboard.tickets.show', $id));
});

Breadcrumbs::for('dashboard.users.index', function ($trail) {
    $trail->parent('index');
    $trail->push('User Settings', route('dashboard.users.index'));
});

Breadcrumbs::for('dashboard.shop.users.index', function ($trail) {
    $trail->parent('index');
    $trail->push('User Management', route('dashboard.shop.users.index'));
});
Breadcrumbs::for('dashboard.shop.users.create', function ($trail) {
    $trail->parent('dashboard.shop.users.index');
    $trail->push('Create New User', route('dashboard.shop.users.create'));
});
Breadcrumbs::for('dashboard.shop.users.show', function ($trail, $id) {
    $trail->parent('dashboard.shop.users.index');
    $trail->push('Edit User Management', route('dashboard.shop.users.show', $id));
});

Breadcrumbs::for('dashboard.shop.management.index', function ($trail) {
    $trail->parent('index');
    $trail->push('Shop Management', route('dashboard.shop.management.index'));
});

Breadcrumbs::for('dashboard.logistics.index', function ($trail) {
    $trail->parent('index');
    $trail->push('Logistics', route('dashboard.logistics.index'));
});
Breadcrumbs::for('dashboard.logistics.create', function ($trail) {
    $trail->parent('dashboard.logistics.index');
    $trail->push('Create Logistics', route('dashboard.logistics.create'));
});

Breadcrumbs::for('dashboard.chat.index', function ($trail) {
    $trail->parent('index');
    $trail->push('Chat', route('dashboard.chat.index'));
});
/*
 * Admin breadcrumbs
 */

Breadcrumbs::for('admin.index', function ($trail) {
    $trail->push('<i class="fas fa-home"></i> Home', route('admin.index'));
});

Breadcrumbs::for('admin.users.index', function ($trail) {
    $trail->parent('admin.index');
    $trail->push('All Users', route('admin.users.index'));
});

Breadcrumbs::for('admin.users.show', function ($trail, $id) {
    $trail->parent('admin.users.index');
    $trail->push('User detail', route('admin.users.show', $id));
});

Breadcrumbs::for('admin.shops.show', function ($trail, $user_id, $shop_id) {
    $trail->parent('admin.users.show', $user_id);
    $trail->push('Shop detail', route('admin.shops.show',[$user_id, $shop_id] ));
});

Breadcrumbs::for('admin.tickets.index', function ($trail) {
    $trail->parent('admin.index');
    $trail->push('All Tickets', route('admin.tickets.index'));
});

Breadcrumbs::for('admin.tickets.show', function ($trail) {
    $trail->parent('admin.tickets.index');
    $trail->push('View Ticket', route('admin.tickets.show', 'id'));
});

Breadcrumbs::for('admin.tickets.create', function ($trail) {
    $trail->parent('admin.tickets.index');
    $trail->push('Create Ticket', route('admin.tickets.show', 'id'));
});

Breadcrumbs::for('admin.tickets.category.index', function ($trail) {
    $trail->parent('admin.tickets.index');
        $trail->push('Ticket Categories', route('admin.tickets.category.index'));
});

Breadcrumbs::for('admin.articles.index', function ($trail) {
    $trail->parent('admin.index');
    $trail->push('All Articles', route('admin.articles.index'));
});

Breadcrumbs::for('admin.articles.create', function ($trail) {
    $trail->parent('admin.articles.index');
    $trail->push('Create Article', route('admin.articles.create'));
});

Breadcrumbs::for('admin.articles.edit', function ($trail) {
    $trail->parent('admin.articles.index');
    $trail->push('View Article', route('admin.articles.edit', 'id'));
});

Breadcrumbs::for('admin.articles.category.index', function ($trail) {
    $trail->parent('admin.articles.index');
    $trail->push('Article Categories', route('admin.articles.category.index'));
});

Breadcrumbs::for('admin.reports.index', function ($trail, $keyword) {
    $trail->parent('admin.index');
    $trail->push(ucfirst($keyword).' Dashboard', route('admin.reports.index', $keyword));
});
