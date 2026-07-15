<?php

namespace App\Providers;

use App\SiteExtension;
use Hyde\Foundation\HydeKernel;
use Hyde\Support\DataCollection;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        DataCollection::$sourceDirectory = 'content';

        HydeKernel::getInstance()->registerExtension(SiteExtension::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
