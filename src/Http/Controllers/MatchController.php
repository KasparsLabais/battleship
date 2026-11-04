<?php

namespace PartyGames\Battleship\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PartyGames\Battleship\Models\BtlMatch;
use PartyGames\GameApi\Models\User;

/**
 * VS Player: a friend match. lobby (invite a friend) -> placing (both place
 * their fleets) -> playing (turns) -> finished.
 *
 * Server-authoritative: fleets live only here and the opponent's ship squares
 * are never sent, except a ship's cells once it's sunk. Shots, mines and
 * turns are resolved here; pages poll /state about once a second.
 *
 * Shot log entries: { id, by: 'host'|'guest', r, c, result: miss|hit|sunk|mine,
 * backfire: {r, c, sunk}|null }. A shot by X targets the other player's board;
 * a mine's backfire damages X's own fleet. A turn that runs out of time is
 * logged as { by, r: null, c: null, result: 'timeout' }.
 *
 * Turn timer: each turn lasts turn_seconds (config). Running out skips the turn;
 * after max_missed_turns missed turns in a row that player forfeits. Missed turns are
 * settled whenever either player polls or fires, so a closed tab can't stall
 * the match.
 */
class MatchController extends Controller
{
    // Segment lengths per fleet size (same as the setup screen and VS PC).
    private const FLEETS = [3 => [5, 4, 3], 4 => [5, 4, 3, 2], 5 => [5, 4, 3, 3, 2], 6 => [5, 4, 3, 3, 2, 2], 7 => [5, 4, 4, 3, 3, 2, 2]];

    // Turn timer (config battleship.turn_seconds / max_missed_turns).
    private function turnSeconds(): int
    {
        return max(5, (int) config('battleship.turn_seconds', 30));
    }

    private function maxMissed(): int
    {
        return max(1, (int) config('battleship.max_missed_turns', 4));
    }

    public function create(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->to('/login?redirect=/battleship');
        }
        $size = max((int) config('battleship.min_board', 8), min((int) config('battleship.max_board', 14), (int) $request->get('size', 10)));
        $ships = max((int) config('battleship.min_ships', 3), min((int) config('battleship.max_ships', 7), (int) $request->get('ships', 5)));

        $match = BtlMatch::create([
            'code' => $this->newCode(),
            'host_user_id' => Auth::id(),
            'status' => 'lobby',
            'settings' => [
                'size' => $size,
                'ships' => $ships,
                'mines' => $request->boolean('mines') ? 1 : 0,
                'touch' => $request->boolean('touch') ? 1 : 0,
            ],
            'shots' => [],
        ]);

        return redirect()->to('/btl/match/' . $match->code);
    }

    public function show($code)
    {
        if (!Auth::check()) {
            return redirect()->to('/login?redirect=/btl/match/' . $code);
        }
        $match = BtlMatch::where('code', $code)->firstOrFail();

        return view('battleship::pages.match')->with([
            'match' => $match,
            'fleet' => $this->fleetFor($match),
            'joinUrl' => url('/btl/match/' . $match->code),
        ]);
    }

    public function join($code)
    {
        $match = BtlMatch::where('code', $code)->firstOrFail();
        if ($match->roleOf(Auth::id())) {
            return new JsonResponse(['success' => true]);
        }
        if ($match->status !== 'lobby' || $match->guest_user_id) {
            return new JsonResponse(['success' => false, 'message' => 'This match already has two players.'], 409);
        }
        $match->update(['guest_user_id' => Auth::id(), 'status' => 'placing']);

        return new JsonResponse(['success' => true]);
    }

    // Submit your fleet: [[ [r,c], ... ], ...]. Validated against the match rules.
    public function place($code, Request $request)
    {
        return DB::transaction(function () use ($code, $request) {
            $match = BtlMatch::where('code', $code)->lockForUpdate()->firstOrFail();
            $role = $match->roleOf(Auth::id());
            if (!$role || $match->status !== 'placing') {
                return new JsonResponse(['success' => false, 'message' => 'You can\'t place ships right now.'], 409);
            }
            if ($match->{$role . '_fleet'}) {
                return new JsonResponse(['success' => true]); // already locked in
            }
            $fleet = $this->validFleet($match, $request->input('fleet'));
            if (!$fleet) {
                return new JsonResponse(['success' => false, 'message' => 'That fleet layout breaks the rules for this match.'], 422);
            }
            $match->{$role . '_fleet'} = $fleet;

            // Both ready: hide mines and pick who fires first.
            if ($match->host_fleet && $match->guest_fleet) {
                $match->mines = $match->settings['mines'] ? [
                    'host' => $this->placeMines($match, $match->host_fleet),
                    'guest' => $this->placeMines($match, $match->guest_fleet),
                ] : ['host' => [], 'guest' => []];
                $match->status = 'playing';
                $match->turn_user_id = random_int(0, 1) ? $match->host_user_id : $match->guest_user_id;
                $match->turn_started_at = now();
            }
            $match->save();

            return new JsonResponse(['success' => true]);
        });
    }

    public function fire($code, Request $request)
    {
        return DB::transaction(function () use ($code, $request) {
            $match = BtlMatch::where('code', $code)->lockForUpdate()->firstOrFail();
            $role = $match->roleOf(Auth::id());
            if (!$role || $match->status !== 'playing') {
                return new JsonResponse(['success' => false, 'message' => 'The match isn\'t in play.'], 409);
            }
            if ($this->settleTimeouts($match)) {
                $match->save();
                if ($match->status !== 'playing') {
                    return new JsonResponse(['success' => false, 'message' => 'The match is over.'], 409);
                }
            }
            if ((int) $match->turn_user_id !== (int) Auth::id()) {
                return new JsonResponse(['success' => false, 'message' => 'Not your turn.'], 409);
            }

            $size = (int) $match->settings['size'];
            $r = (int) $request->input('r');
            $c = (int) $request->input('c');
            if ($r < 0 || $c < 0 || $r >= $size || $c >= $size) {
                return new JsonResponse(['success' => false, 'message' => 'Off the board.'], 422);
            }
            $shots = $match->shots ?: [];
            foreach ($shots as $s) {
                if ($s['by'] === $role && $s['r'] === $r && $s['c'] === $c) {
                    return new JsonResponse(['success' => false, 'message' => 'Already fired there.'], 422);
                }
            }

            $target = BtlMatch::other($role);
            $targetFleet = $match->{$target . '_fleet'};
            $mines = $match->mines ?: ['host' => [], 'guest' => []];
            $key = $r . ',' . $c;
            $shot = ['id' => count($shots) + 1, 'by' => $role, 'r' => $r, 'c' => $c, 'result' => 'miss', 'backfire' => null];

            if (in_array($key, $mines[$target] ?? [], true)) {
                // Mine: it blows up and a random afloat segment of the shooter's own fleet takes the hit.
                $mines[$target] = array_values(array_diff($mines[$target], [$key]));
                $shot['result'] = 'mine';
                $alive = [];
                $hitsOnShooter = $this->hitsOn($role, $shots);
                foreach ($match->{$role . '_fleet'} as $cells) {
                    foreach ($cells as [$sr, $sc]) {
                        if (!isset($hitsOnShooter[$sr . ',' . $sc])) $alive[] = [$sr, $sc];
                    }
                }
                if ($alive) {
                    [$br, $bc] = $alive[array_rand($alive)];
                    $shot['backfire'] = ['r' => $br, 'c' => $bc, 'sunk' => false];
                }
            } else {
                foreach ($targetFleet as $cells) {
                    foreach ($cells as [$sr, $sc]) {
                        if ($sr === $r && $sc === $c) $shot['result'] = 'hit';
                    }
                }
            }

            $shots[] = $shot;
            // Sunk checks need the shot in the log.
            if ($shot['result'] === 'hit' && $this->shipSunkAt($targetFleet, $r, $c, $this->hitsOn($target, $shots))) {
                $shots[count($shots) - 1]['result'] = 'sunk';
            }
            if ($shot['backfire'] && $this->shipSunkAt($match->{$role . '_fleet'}, $shot['backfire']['r'], $shot['backfire']['c'], $this->hitsOn($role, $shots))) {
                $shots[count($shots) - 1]['backfire']['sunk'] = true;
            }

            $match->shots = $shots;
            $match->mines = $mines;

            $match->{$role . '_missed'} = 0; // made a move: the missed-turn streak resets

            // A fleet with every segment hit is lost (a mine can sink the shooter).
            if ($this->fleetSunk($targetFleet, $this->hitsOn($target, $shots))) {
                $match->status = 'finished';
                $match->winner_user_id = Auth::id();
                $match->ended_reason = 'sunk';
            } elseif ($this->fleetSunk($match->{$role . '_fleet'}, $this->hitsOn($role, $shots))) {
                $match->status = 'finished';
                $match->winner_user_id = $match->userIdOf($target);
                $match->ended_reason = 'sunk';
            } else {
                $match->turn_user_id = $match->userIdOf($target); // turns alternate every shot, like VS PC
                $match->turn_started_at = now();
            }
            $match->save();

            return new JsonResponse(['success' => true, 'data' => ['shot' => $shots[count($shots) - 1]]]);
        });
    }

    public function state($code)
    {
        $match = BtlMatch::where('code', $code)->firstOrFail();
        // Settle any turns that ran out (locked, so two polls can't both skip the same turn).
        if ($match->status === 'playing' && $this->turnDeadlineMs($match) <= $this->nowMs()) {
            $match = DB::transaction(function () use ($match) {
                $locked = BtlMatch::lockForUpdate()->find($match->id);
                if ($this->settleTimeouts($locked)) $locked->save();
                return $locked;
            });
        }
        $role = $match->roleOf(Auth::id());
        $opp = $role ? BtlMatch::other($role) : null;
        $shots = $match->shots ?: [];

        $users = User::whereIn('id', array_filter([$match->host_user_id, $match->guest_user_id]))->get()->keyBy('id');
        $person = function ($id) use ($users) {
            $u = $id ? $users->get($id) : null;
            return $u ? ['user_id' => $u->id, 'username' => $u->username, 'avatar' => $u->avatar ?: '/images/default-avatar.jpg'] : null;
        };

        $data = [
            'status' => $match->status,
            'settings' => $match->settings,
            'role' => $role,
            'me' => Auth::id(),
            'host' => $person($match->host_user_id),
            'guest' => $person($match->guest_user_id),
            'host_ready' => (bool) $match->host_fleet,
            'guest_ready' => (bool) $match->guest_fleet,
            'my_turn' => $match->status === 'playing' && (int) $match->turn_user_id === (int) Auth::id(),
            'winner_user_id' => $match->winner_user_id,
            'ended_reason' => $match->ended_reason,
            'server_now' => $this->nowMs(),
            'turn_deadline' => $match->status === 'playing' ? $this->turnDeadlineMs($match) : null,
            'turn_seconds' => $this->turnSeconds(),
            'max_missed' => $this->maxMissed(),
            'missed' => ['host' => (int) $match->host_missed, 'guest' => (int) $match->guest_missed],
        ];

        if ($role) {
            // Shots are public to both players (they're what each side saw on screen);
            // ship positions are not: only your own fleet, plus enemy ships once sunk.
            // The opponent's mine backfire hit one of *their* ships: keep that it
            // happened (and whether it sank something), not where.
            $data['shots'] = array_map(function ($s) use ($role) {
                if ($s['by'] !== $role && !empty($s['backfire'])) {
                    $s['backfire'] = ['sunk' => (bool) $s['backfire']['sunk']];
                }
                return $s;
            }, $shots);
            $data['my_fleet'] = $match->{$role . '_fleet'};
            $data['sunk_enemy_ships'] = [];
            $oppFleet = $match->{$opp . '_fleet'};
            if ($oppFleet) {
                $hitsOnOpp = $this->hitsOn($opp, $shots);
                foreach ($oppFleet as $cells) {
                    $sunk = collect($cells)->every(fn ($cell) => isset($hitsOnOpp[$cell[0] . ',' . $cell[1]]));
                    // Finished: show the whole enemy fleet.
                    if ($sunk || $match->status === 'finished') $data['sunk_enemy_ships'][] = ['cells' => $cells, 'sunk' => $sunk];
                }
            }
        }

        return new JsonResponse(['success' => true, 'data' => $data]);
    }

    /* ---------------- turn timer ---------------- */

    private function nowMs(): int
    {
        return (int) floor(microtime(true) * 1000);
    }

    private function turnDeadlineMs(BtlMatch $match): int
    {
        $started = $match->turn_started_at ? $match->turn_started_at->getTimestamp() * 1000 : $this->nowMs();
        return $started + $this->turnSeconds() * 1000;
    }

    // Skip every turn whose time is up (several, if nobody was around), logging
    // each one; a player reaching the missed-turn limit forfeits. Returns whether
    // anything changed. Caller saves.
    private function settleTimeouts(BtlMatch $match): bool
    {
        $changed = false;
        $shots = $match->shots ?: [];
        while ($match->status === 'playing' && $this->turnDeadlineMs($match) <= $this->nowMs()) {
            $changed = true;
            $role = $match->roleOf($match->turn_user_id);
            $shots[] = ['id' => count($shots) + 1, 'by' => $role, 'r' => null, 'c' => null, 'result' => 'timeout', 'backfire' => null];
            $missed = (int) $match->{$role . '_missed'} + 1;
            $match->{$role . '_missed'} = $missed;
            if ($missed >= $this->maxMissed()) {
                $match->status = 'finished';
                $match->winner_user_id = $match->userIdOf(BtlMatch::other($role));
                $match->ended_reason = 'forfeit';
            } else {
                $match->turn_user_id = $match->userIdOf(BtlMatch::other($role));
                $match->turn_started_at = $match->turn_started_at->copy()->addSeconds($this->turnSeconds());
            }
        }
        if ($changed) $match->shots = $shots;
        return $changed;
    }

    /* ---------------- rules ---------------- */

    private function fleetFor(BtlMatch $match): array
    {
        return self::FLEETS[(int) $match->settings['ships']] ?? self::FLEETS[5];
    }

    // Normalised fleet if it matches the match's lengths, is straight and
    // contiguous, in bounds, doesn't overlap and (unless touching is allowed)
    // keeps one square of water around every ship.
    private function validFleet(BtlMatch $match, $input): ?array
    {
        if (!is_array($input)) return null;
        $size = (int) $match->settings['size'];
        $want = $this->fleetFor($match);
        $fleet = [];
        $lengths = [];
        $owner = [];
        foreach (array_values($input) as $i => $cells) {
            if (!is_array($cells) || !count($cells)) return null;
            $norm = [];
            foreach ($cells as $cell) {
                if (!is_array($cell) || count($cell) !== 2) return null;
                [$r, $c] = [(int) $cell[0], (int) $cell[1]];
                if ($r < 0 || $c < 0 || $r >= $size || $c >= $size) return null;
                if (isset($owner[$r . ',' . $c])) return null; // overlap
                $owner[$r . ',' . $c] = $i;
                $norm[] = [$r, $c];
            }
            usort($norm, fn ($a, $b) => $a[0] <=> $b[0] ?: $a[1] <=> $b[1]);
            $rows = array_unique(array_column($norm, 0));
            $cols = array_unique(array_column($norm, 1));
            $n = count($norm);
            $straight = (count($rows) === 1 && max($cols) - min($cols) === $n - 1)
                || (count($cols) === 1 && max($rows) - min($rows) === $n - 1);
            if (!$straight) return null;
            $fleet[] = $norm;
            $lengths[] = $n;
        }
        $sortedWant = $want; rsort($sortedWant);
        rsort($lengths);
        if ($lengths !== $sortedWant) return null;

        if (!$match->settings['touch']) {
            foreach ($owner as $key => $i) {
                [$r, $c] = array_map('intval', explode(',', $key));
                for ($dr = -1; $dr <= 1; $dr++) for ($dc = -1; $dc <= 1; $dc++) {
                    $n = ($r + $dr) . ',' . ($c + $dc);
                    if (isset($owner[$n]) && $owner[$n] !== $i) return null;
                }
            }
        }
        return $fleet;
    }

    // Same density as VS PC: ~4% of the sea, at least 2, never on a ship.
    private function placeMines(BtlMatch $match, array $fleet): array
    {
        $size = (int) $match->settings['size'];
        $occupied = [];
        foreach ($fleet as $cells) foreach ($cells as [$r, $c]) $occupied[$r . ',' . $c] = true;
        $free = [];
        for ($r = 0; $r < $size; $r++) for ($c = 0; $c < $size; $c++) {
            if (!isset($occupied[$r . ',' . $c])) $free[] = $r . ',' . $c;
        }
        shuffle($free);
        return array_slice($free, 0, max(2, (int) round($size * $size * 0.04)));
    }

    // Squares of $role's fleet that have been hit: the opponent's hits on it,
    // plus $role's own mine backfires.
    private function hitsOn(string $role, array $shots): array
    {
        $hits = [];
        foreach ($shots as $s) {
            if ($s['by'] !== $role && in_array($s['result'], ['hit', 'sunk'], true)) $hits[$s['r'] . ',' . $s['c']] = true;
            if ($s['by'] === $role && !empty($s['backfire'])) $hits[$s['backfire']['r'] . ',' . $s['backfire']['c']] = true;
        }
        return $hits;
    }

    private function shipSunkAt(array $fleet, int $r, int $c, array $hits): bool
    {
        foreach ($fleet as $cells) {
            $here = collect($cells)->contains(fn ($cell) => $cell[0] === $r && $cell[1] === $c);
            if ($here) return collect($cells)->every(fn ($cell) => isset($hits[$cell[0] . ',' . $cell[1]]));
        }
        return false;
    }

    private function fleetSunk(array $fleet, array $hits): bool
    {
        foreach ($fleet as $cells) foreach ($cells as [$r, $c]) if (!isset($hits[$r . ',' . $c])) return false;
        return count($fleet) > 0;
    }

    private function newCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (BtlMatch::where('code', $code)->exists());
        return $code;
    }
}
