<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web']], function () {

    // Landing / hub: player info, arena leaderboard, "new game" setup.
    Route::get('/battleship', 'BattleshipController@index');

    // Game screen. For VS PC the whole match runs client-side from here.
    Route::get('/' . config('battleship.prefix') . '/play', 'BattleshipController@play');

    // VS Player: friend matches (lobby + invite, then server-run turns).
    Route::post('/' . config('battleship.prefix') . '/match', 'MatchController@create');
    Route::get('/' . config('battleship.prefix') . '/match/{code}', 'MatchController@show')->where('code', '[A-Z0-9]{8}');
    Route::middleware('auth')->group(function () {
        Route::post('/' . config('battleship.prefix') . '/match/{code}/join', 'MatchController@join')->where('code', '[A-Z0-9]{8}');
        Route::post('/' . config('battleship.prefix') . '/match/{code}/place', 'MatchController@place')->where('code', '[A-Z0-9]{8}');
        Route::post('/' . config('battleship.prefix') . '/match/{code}/fire', 'MatchController@fire')->where('code', '[A-Z0-9]{8}');
        Route::post('/' . config('battleship.prefix') . '/match/{code}/surrender', 'MatchController@surrender')->where('code', '[A-Z0-9]{8}');
        Route::get('/' . config('battleship.prefix') . '/match/{code}/state', 'MatchController@state')->where('code', '[A-Z0-9]{8}');
    });
});
