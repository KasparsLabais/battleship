<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web']], function () {

    // Landing / hub: player info, arena leaderboard, "new game" setup.
    Route::get('/battleship', 'BattleshipController@index');

    // Game screen. For VS PC the whole match runs client-side from here.
    Route::get('/' . config('battleship.prefix') . '/play', 'BattleshipController@play');
});
