<?php

namespace Gatekeeper\Provider;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Gatekeeper\Domain\Repository\FeatureRepositoryInterface;
use Gatekeeper\Console\Command\ScanViewsForFeaturesCommand;
use Gatekeeper\Commands\AddFeature;
use Gatekeeper\Commands\RemoveFeature;
use Gatekeeper\Commands\EnableFeature;
use Gatekeeper\Commands\DisableFeature;

class FeatureServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../Migration');

        $this->publishes([
            __DIR__.'/../Config/features.php' => config_path('features.php'),
        ]);

        $this->registerBladeDirectives();

        $this->commands([
            AddFeature::class,
            RemoveFeature::class,
            EnableFeature::class,
            DisableFeature::class,
        ]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/features.php', 'features');

        $config = $this->app->make('config');

        $this->app->bind(FeatureRepositoryInterface::class, function () use ($config) {
            return app()->make($config->get('features.repository'));
        });

        $this->registerConsoleCommand();
    }

    private function registerBladeDirectives()
    {
        $this->registerBladeFeatureDirective();
        $this->registerBladeFeatureForDirective();
    }

    private function registerBladeFeatureDirective()
    {
        Blade::directive('feature', function ($featureName) {
            return "<?php if (app(\\Gatekeeper\\Domain\\FeatureManager::class)->isEnabled($featureName)): ?>";
        });

        Blade::directive('endfeature', function () {
            return '<?php endif; ?>';
        });
    }

    private function registerBladeFeatureForDirective()
    {
        Blade::directive('featurefor', function ($args) {
            return "<?php if (app(\\Gatekeeper\\Domain\\FeatureManager::class)->isEnabledFor($args)): ?>";
        });

        Blade::directive('endfeaturefor', function () {
            return '<?php endif; ?>';
        });
    }

    private function registerConsoleCommand()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ScanViewsForFeaturesCommand::class
            ]);
        }
    }
}
