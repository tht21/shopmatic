<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\Article;
use App\Models\ArticleTag;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Integration;
use App\Models\IntegrationCategory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAlert;
use App\Models\ProductInventory;
use App\Models\ProductListing;
use App\Models\Shop;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Policies\AccountCategoryPolicy;
use App\Policies\AccountPolicy;
use App\Policies\ArticlePolicy;
use App\Policies\ArticleTagPolicy;
use App\Policies\BrandPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\IntegrationCategoryPolicy;
use App\Policies\IntegrationPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductAlertPolicy;
use App\Policies\ProductInventoryPolicy;
use App\Policies\ProductListingPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ShopPolicy;
use App\Policies\TicketCategoryPolicy;
use App\Policies\TicketPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{

    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        AccountCategory::class => AccountCategoryPolicy::class,
        Account::class => AccountPolicy::class,
        Article::class => ArticlePolicy::class,
        ArticleTag::class => ArticleTagPolicy::class,
        Brand::class => BrandPolicy::class,
        Category::class => CategoryPolicy::class,
        IntegrationCategory::class => IntegrationCategoryPolicy::class,
        Integration::class => IntegrationPolicy::class,
        Order::class => OrderPolicy::class,
        ProductAlert::class => ProductAlertPolicy::class,
        ProductInventory::class => ProductInventoryPolicy::class,
        ProductListing::class => ProductListingPolicy::class,
        Product::class => ProductPolicy::class,
        Shop::class => ShopPolicy::class,
        TicketCategory::class => TicketCategoryPolicy::class,
        Ticket::class => TicketPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::routes();
    }
}
