<?php

namespace Composite\InvoiceWrapper;

use Composite\InvoiceWrapper\Factories\InvoiceGatewayFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class InvoiceWrapperServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $configPath = __DIR__.'/../config/invoice-wrapper.php';

        $this->publishes([
            $configPath => $this->app->configPath('invoice-wrapper.php'),
        ], 'config');

        $this->mergeConfigFrom($configPath, 'invoice-wrapper');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind('invoice-wrapper', static function (Application $app) {
            $config = $app['config']['invoice-wrapper'];

            return new InvoiceWrapper(
                InvoiceGatewayFactory::create($config['selected_provider'])
            );
        });

        $this->app->alias('invoice-wrapper', InvoiceWrapper::class);
    }
}
