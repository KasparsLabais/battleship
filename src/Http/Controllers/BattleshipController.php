<?php

namespace PartyGames\Battleship\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Battleship — a 1v1 strategy game. Modes: VS PC (fully client-side),
 * VS Random and VS Player (online, relayed through the game-node server).
 * Arena Mode is a weekly leaderboard fed only by ranked VS-Random games.
 */
class BattleshipController extends Controller
{
    /** Hub: player info, arena leaderboard, and the new-game setup panel. */
    public function index()
    {
        return view('battleship::pages.index')->with([
            'config' => $this->boardConfig(),
        ]);
    }

    /** The game screen. VS PC plays out entirely in the browser. */
    public function play(Request $request)
    {
        $cfg = $this->boardConfig();

        // Sanitise the setup the hub handed us (query string).
        $mode  = in_array($request->get('mode'), ['pc', 'random', 'player']) ? $request->get('mode') : 'pc';
        $size  = (int) $request->get('size', 10);
        $ships = (int) $request->get('ships', 5);
        $mines = (int) $request->get('mines', 0) ? 1 : 0;
        $arena = (int) $request->get('arena', 0) ? 1 : 0;
        // Official rules let ships touch; by default we require open water
        // between them unless the player opted in on the setup screen.
        $touch = (int) $request->get('touch', 0) ? 1 : 0;

        $size  = max($cfg['min_board'], min($cfg['max_board'], $size));
        $ships = max($cfg['min_ships'], min($cfg['max_ships'], $ships));

        return view('battleship::pages.play')->with([
            'mode'  => $mode,
            'size'  => $size,
            'ships' => $ships,
            'mines' => $mines,
            'arena' => $arena,
            'touch' => $touch,
        ]);
    }

    /** Board / fleet / mine bounds, from config. */
    private function boardConfig(): array
    {
        return [
            'min_board' => config('battleship.min_board', 8),
            'max_board' => config('battleship.max_board', 14),
            'min_ships' => config('battleship.min_ships', 3),
            'max_ships' => config('battleship.max_ships', 7),
        ];
    }
}
