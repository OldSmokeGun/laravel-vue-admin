<?php

namespace App\Providers\Api;

use App\Utils\HttpResponse\HttpResponse;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Support\ServiceProvider;

class HttpResponseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(HttpResponse::class, function () {
            return new HttpResponse($this->app->make(ResponseFactory::class));
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
