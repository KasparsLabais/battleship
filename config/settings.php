<?php

// Merged into the "battleship" config key (NOT "settings" — that belongs to the
// trivia package). Access with config('battleship.*').
return [
    'prefix'          => 'btl',
    // Registration token for GameApi::createGameInstance (matches games.token
    // for the Battleship row). Used for online matches.
    'token'           => 'sMwks5xen8XPRJILrgSYsG1CxKrJBuntls4w4F6nSx6rffrRRqQdhlKEiZ9UH0GJbMYWH7kDUjeWsdm7M4h1CMw8LxQ02WUdP0tcR1ZzIp923FxWcKk2aMCz',
    'version'         => '1.0.0',
    'start_point_url' => '/btl/play',
    'description'     => 'Battleship',
    'creators_name'   => 'Kaspars Labais',
    'creators_url'    => 'https://github.com/KasparsLabais',
    'image_url'       => '/vendor/battleship-game/images/battleship-logo.png',

    // Board / fleet / mine bounds the setup screen is allowed to request.
    'min_board'       => 8,
    'max_board'       => 14,
    'min_ships'       => 3,
    'max_ships'       => 7,
];
