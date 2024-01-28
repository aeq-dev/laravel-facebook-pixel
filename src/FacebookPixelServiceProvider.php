<?php

namespace Bkfdev\FacebookPixel;

use Illuminate\Support\Facades\View;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FacebookPixelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('facebook-pixel')
            ->hasConfigFile('facebook-pixel');
    }

    public function packageBooted()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'facebookpixel');

        $this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/bkfdev'),
        ], 'views');

        View::creator(
            ['facebookpixel::head', 'facebookpixel::body'],
            'Bkfdev\FacebookPixel\ScriptViewCreator'
        );
    }

    public function registeringPackage()
    {
        $this->app->singleton(FacebookPixel::class, function () {
            return new FacebookPixel();
        });
    }
}
