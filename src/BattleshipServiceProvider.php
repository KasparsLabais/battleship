<?php

namespace PartyGames\Battleship;

use Illuminate\Support\ServiceProvider;

class BattleshipServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../resources/static' => public_path('vendor/battleship-game'),
        ], 'battleship-game-assets');
    }
}