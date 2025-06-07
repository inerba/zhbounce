<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use TallStackUi\Facades\TallStackUi;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        TallStackUi::personalize()
            ->layout()
            ->block('main', 'mx-auto max-w-full p-4')
            ->block('wrapper.second.expanded', 'md:pl-56');

        TallStackUi::personalize()
            ->sideBar()
            ->block('desktop.sizes.expanded', 'w-56')
            ->block('desktop.wrapper.first.size', 'lg:w-56');
    }
}
