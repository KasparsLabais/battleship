<?php

namespace PartyGames\Battleship;

use Illuminate\Support\ServiceProvider;

class BattleshipServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'battleship');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../resources/static' => public_path('vendor/battleship-game'),
        ], 'battleship-game-assets');
    }

    public function register()
    {
        // Own config key so it never clashes with the trivia package's "settings".
        $this->mergeConfigFrom(__DIR__ . '/../config/settings.php', 'battleship');

        // Register routes here so it works without a composer discovery refresh.
        $this->app->register(RouteServiceProvider::class);
    }
}
