@extends('game-api::layout')
@section('body')

    @php
        $btn = 'fira-sans font-black uppercase tracking-wider border-4 border-main-dark transition-all duration-100 hover:-translate-x-0.5 hover:-translate-y-0.5 active:translate-x-0.5 active:translate-y-0.5 disabled:opacity-40 disabled:cursor-not-allowed';
        $s = $match->settings;
    @endphp

    {{-- VS Player: a friend match. The server runs the game (see MatchController);
         this page polls /state, lets you place your fleet and fire on your turn,
         and animates every shot with the same feedback as VS PC. --}}
    <div class="w-full px-[15px] lg:px-[30px] pt-8 pb-12">
        <div class="max-w-5xl mx-auto flex flex-col gap-6">

            {{-- Header --}}
            <div class="flex flex-wrap items-center gap-3 justify-between">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="relative inline-block">
                        <span aria-hidden="true" class="absolute inset-0 -rotate-2 bg-brand"></span>
                        <span class="relative block russo text-white text-base md:text-xl tracking-widest uppercase px-3 py-1">Battleship</span>
                    </h1>
                    <span class="inline-block border-2 border-main-dark bg-pop px-2 py-0.5 russo text-[10px] uppercase tracking-wide text-main-dark">VS Player</span>
                    <span class="inline-block border-2 border-main-dark bg-main-light px-2 py-0.5 russo text-[10px] uppercase tracking-wide text-main-dark">{{ $s['size'] }}×{{ $s['size'] }}</span>
                    <span class="inline-block border-2 border-main-dark bg-main-light px-2 py-0.5 russo text-[10px] uppercase tracking-wide text-main-dark">{{ $s['ships'] }} ships</span>
                    @if($s['mines'])<span class="inline-block border-2 border-main-dark bg-pop px-2 py-0.5 russo text-[10px] uppercase tracking-wide text-main-dark">Mines</span>@endif
                    <span class="inline-block border-2 border-main-dark bg-main-light px-2 py-0.5 russo text-[10px] uppercase tracking-wide text-main-dark">{{ $s['touch'] ? 'Ships can touch' : 'No touching' }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="sound-btn" class="fira-sans font-bold uppercase text-xs tracking-wide bg-white text-main-dark border-4 border-main-dark shadow-[3px_3px_0_0_#14323B] px-3 py-2">Sound: On</button>
                    <a href="/battleship" class="fira-sans font-bold uppercase text-xs tracking-wide bg-white text-main-dark border-4 border-main-dark shadow-[3px_3px_0_0_#14323B] px-4 py-2">&larr; Leave</a>
                </div>
            </div>

            <div id="loading-phase" class="bg-white border-4 border-main-dark p-10 text-center fira-sans text-main-dark/60">Loading match&hellip;</div>

            {{-- ========================= LOBBY ========================= --}}
            <div id="lobby-phase" class="hidden bg-white border-4 border-main-dark shadow-[8px_8px_0_0_#14323B]">
                <div class="flex items-center gap-2.5 bg-main-dark px-4 py-2.5">
                    <span class="w-3.5 h-3.5 rounded-full bg-main-accent shrink-0"></span>
                    <span class="w-3.5 h-3.5 rounded-full bg-pop shrink-0"></span>
                    <span class="w-3.5 h-3.5 rounded-full bg-green-500 shrink-0"></span>
                    <span class="ml-2 russo uppercase tracking-widest text-white text-xs">Match Lobby</span>
                </div>
                <div class="p-5 md:p-8 flex flex-col gap-6">
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <div id="lobby-host" class="flex items-center gap-2 border-4 border-main-dark bg-main-light pl-1 pr-3 py-1"></div>
                        <span class="russo text-2xl text-main-accent">VS</span>
                        <div id="lobby-guest" class="flex items-center gap-2 border-4 border-dashed border-main-dark/40 bg-white px-4 py-2.5 fira-sans text-main-dark/60">Waiting for an opponent&hellip;</div>
                    </div>

                    <div id="lobby-join" class="hidden flex-col items-center gap-2">
                        <p class="fira-sans text-main-dark/70" id="lobby-join-text">You've been challenged to a Battleship match!</p>
                        <button type="button" id="join-btn" class="{{ $btn }} bg-main-accent text-white shadow-[6px_6px_0_0_#14323B] px-10 py-3 text-xl">Join Match</button>
                    </div>
                    <p id="lobby-full" class="hidden text-center fira-sans font-bold text-main-dark/70">This match already has two players.</p>

                    {{-- Invite (host, while waiting) --}}
                    <div id="lobby-invite" class="hidden border-4 border-main-dark">
                        <div class="bg-brand border-b-4 border-main-dark px-4 py-2 russo uppercase tracking-widest text-white text-xs">Invite a friend</div>
                        <div class="p-4 flex flex-col gap-3">
                            <div class="flex flex-row items-stretch border-4 border-main-dark">
                                <span class="flex-1 min-w-0 bg-main-light px-3 py-2 fira-sans text-sm font-bold text-main-dark break-all">{{ $joinUrl }}</span>
                                <button type="button" id="copy-btn" class="shrink-0 bg-main-dark text-white border-l-4 border-main-dark px-4 russo text-xs uppercase tracking-wide">Copy</button>
                            </div>
                            <div id="friend-list" class="flex flex-col gap-2"></div>
                        </div>
                    </div>
                    <p id="lobby-error" class="hidden text-center fira-sans font-bold text-main-accent"></p>
                </div>
            </div>

            {{-- ========================= PLACEMENT ========================= --}}
            <div id="place-phase" class="hidden grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-6">
                <div class="bg-white border-4 border-main-dark shadow-[8px_8px_0_0_#14323B]">
                    <div class="flex items-center gap-2.5 bg-main-dark px-4 py-2.5">
                        <span class="w-3.5 h-3.5 rounded-full bg-main-accent shrink-0"></span>
                        <span class="w-3.5 h-3.5 rounded-full bg-pop shrink-0"></span>
                        <span class="w-3.5 h-3.5 rounded-full bg-green-500 shrink-0"></span>
                        <span class="ml-2 russo uppercase tracking-widest text-white text-xs">Position Your Fleet</span>
                    </div>
                    <div class="p-4 md:p-6 flex justify-center">
                        <div id="place-board" class="bship-board"></div>
                    </div>
                </div>
                <div class="bg-white border-4 border-main-dark shadow-[6px_6px_0_0_#14323B] p-4 flex flex-col gap-4">
                    <p class="fira-sans text-xs text-main-dark/70 border-2 border-main-dark bg-main-light px-2 py-1.5">
                        @if($s['touch'])
                            Ships can sit side by side, but can't overlap.
                        @else
                            Ships can't overlap or touch &mdash; keep at least one square of water around each ship, diagonals included.
                        @endif
                    </p>
                    <div>
                        <span class="fira-sans text-xs uppercase tracking-widest text-main-dark/60">Fleet</span>
                        <div id="fleet-list" class="flex flex-col gap-2 mt-2"></div>
                    </div>
                    <div id="place-tools" class="flex flex-col gap-2">
                        <button type="button" id="rotate-btn" class="{{ $btn }} bg-white text-main-dark shadow-[3px_3px_0_0_#14323B] px-4 py-2 text-xs">Rotate: <span id="orient-label">Horizontal</span></button>
                        <button type="button" id="auto-btn" class="{{ $btn }} bg-brand text-white shadow-[3px_3px_0_0_#14323B] px-4 py-2 text-xs">Auto-place</button>
                        <button type="button" id="clear-btn" class="{{ $btn }} bg-white text-main-dark shadow-[3px_3px_0_0_#14323B] px-4 py-2 text-xs">Clear</button>
                    </div>
                    <button type="button" id="ready-btn" disabled class="{{ $btn }} bg-main-accent text-white shadow-[5px_5px_0_0_#14323B] px-6 py-3">Ready</button>
                    <div id="place-status" class="fira-sans text-sm text-main-dark/70 border-2 border-main-dark px-2 py-1.5"></div>
                </div>
            </div>

            {{-- ========================= BATTLE ========================= --}}
            <div id="battle-phase" class="hidden flex-col gap-6">
                <div>
                    <div id="turn-banner" class="bg-main-dark text-white border-4 border-main-dark shadow-[4px_4px_0_0_#14323B] px-5 py-3 russo uppercase tracking-widest text-sm text-center"></div>
                    {{-- Turn timer: runs out -> turn skipped; too many in a row -> forfeit --}}
                    <div class="h-3 bg-white border-4 border-t-0 border-main-dark overflow-hidden">
                        <div id="turn-bar" class="h-full bg-green-500" style="width:100%; transition: width 0.25s linear;"></div>
                    </div>
                    <p id="missed-warning" class="hidden mt-2 fira-sans text-sm font-bold text-main-accent text-center"></p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white border-4 border-main-dark shadow-[6px_6px_0_0_#14323B]">
                        <div class="flex items-center gap-2 bg-main-accent px-4 py-2.5">
                            <span class="w-3 h-3 rounded-full bg-white shrink-0"></span>
                            <span class="russo uppercase tracking-widest text-white text-xs truncate"><span id="enemy-name">Enemy</span>'s Waters</span>
                            <span id="enemy-left" class="ml-auto fira-sans text-white text-xs font-bold shrink-0"></span>
                        </div>
                        <div class="relative p-3 md:p-4 flex justify-center">
                            <div id="enemy-board" class="bship-board"></div>
                            <div id="enemy-callout" class="shot-callout" aria-live="polite"></div>
                        </div>
                    </div>
                    <div id="player-window" class="relative bg-white border-4 border-main-dark shadow-[6px_6px_0_0_#14323B]">
                        <div class="flex items-center gap-2 bg-brand px-4 py-2.5">
                            <span class="w-3 h-3 rounded-full bg-white shrink-0"></span>
                            <span class="russo uppercase tracking-widest text-white text-xs">Your Fleet</span>
                            <span id="player-left" class="ml-auto fira-sans text-white text-xs font-bold"></span>
                        </div>
                        <div class="relative p-3 md:p-4 flex justify-center">
                            <div id="player-board" class="bship-board"></div>
                            <div id="player-callout" class="shot-callout" aria-live="polite"></div>
                        </div>
                    </div>
                </div>
                <div class="bg-white border-4 border-main-dark shadow-[4px_4px_0_0_#14323B] p-4">
                    <span class="fira-sans text-[10px] uppercase tracking-widest text-main-dark/50">Battle log</span>
                    <div id="log" class="mt-2 flex flex-col gap-1 fira-sans text-sm text-main-dark max-h-32 overflow-y-auto"></div>
                </div>
            </div>

            {{-- ========================= RESULT ========================= --}}
            <div id="over-phase" class="hidden">
                <div id="result-card" class="border-4 border-main-dark shadow-[8px_8px_0_0_#14323B] p-8 text-center flex flex-col items-center gap-4">
                    <div id="result-emoji" class="text-6xl"></div>
                    <div id="result-title" class="russo text-3xl uppercase tracking-wider"></div>
                    <div id="result-stats" class="fira-sans"></div>
                    <div class="flex flex-wrap justify-center gap-3 mt-2">
                        <a href="/battleship" class="{{ $btn }} bg-main-accent text-white shadow-[5px_5px_0_0_#14323B] px-6 py-3">New Match</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('battleship::partials.board-styles')

    <script>
        (function () {
            const CODE = @json($match->code);
            const JOIN_URL = @json($joinUrl);
            const CSRF = '{{ csrf_token() }}';
            const SIZE = {{ (int) $s['size'] }};
            const TOUCH = {{ (int) $s['touch'] }};
            const FLEET = @json($fleet);
            const RESULT_PAUSE = 1300; // how long a shot's result holds before you can fire again
            const key = (r, c) => r + ',' + c;
            const inB = (r, c) => r >= 0 && c >= 0 && r < SIZE && c < SIZE;
            const $ = (id) => document.getElementById(id);
            const api = (path, method, body) => fetch('/btl/match/' + CODE + path, {
                method: method || 'GET', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: body ? JSON.stringify(body) : undefined,
            }).then(r => r.json()).catch(() => ({ success: false, message: 'Connection problem — try again.' }));

            /* ---------------- sound (same files + toggle as VS PC) ---------------- */
            const SFX = { hit: new Audio('/vendor/battleship-game/sounds/shot_hit.wav'), miss: new Audio('/vendor/battleship-game/sounds/shot_missed.wav') };
            Object.values(SFX).forEach(a => { a.preload = 'auto'; });
            let muted = false;
            try { muted = localStorage.getItem('bship_muted') === '1'; } catch (e) {}
            const soundBtn = $('sound-btn');
            const paintSound = () => { soundBtn.textContent = muted ? 'Sound: Off' : 'Sound: On'; soundBtn.classList.toggle('opacity-60', muted); };
            soundBtn.addEventListener('click', () => { muted = !muted; try { localStorage.setItem('bship_muted', muted ? '1' : '0'); } catch (e) {} paintSound(); });
            paintSound();
            const playSfx = (tag) => { if (muted) return; const a = SFX[tag === 'miss' ? 'miss' : 'hit'].cloneNode(); a.volume = 0.8; a.play().catch(() => {}); };

            const CALLOUT_STYLE = { hit: 'background:#E62727;color:#fff', sunk: 'background:#7f1414;color:#fff', miss: 'background:#fff;color:#14323B', mine: 'background:#FFB900;color:#14323B' };
            function callout(which, text, tag) {
                const el = $(which + '-callout');
                el.innerHTML = '';
                const span = document.createElement('span');
                span.textContent = text; span.style.cssText = CALLOUT_STYLE[tag] || CALLOUT_STYLE.miss;
                el.appendChild(span);
                el.classList.remove('is-on'); void el.offsetWidth; el.classList.add('is-on');
            }
            function flashPlayerHit() { const w = $('player-window'); w.classList.remove('is-hit'); void w.offsetWidth; w.classList.add('is-hit'); }
            function log(html, mine) {
                const line = document.createElement('div');
                line.innerHTML = '<span class="font-bold ' + (mine ? 'text-brand' : 'text-main-accent') + '">' + (mine ? 'You' : esc(oppName())) + ':</span> ' + html;
                $('log').prepend(line);
            }
            const esc = (t) => String(t).replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
            const coord = (r, c) => String.fromCharCode(65 + c) + (r + 1);

            /* ---------------- state ---------------- */
            let S = null;                 // last /state
            let lastShotId = null;        // shots already animated
            let busyUntil = 0, firing = false;
            const lastKey = { enemy: null, player: null };
            const myRole = () => S && S.role;
            const opp = () => S ? (S.role === 'host' ? S.guest : S.host) : null;
            const oppName = () => (opp() && opp().username) || 'Opponent';

            function show(phase) {
                ['loading', 'lobby', 'place', 'battle', 'over'].forEach(p => {
                    const el = $(p + '-phase');
                    el.classList.toggle('hidden', p !== phase);
                    if (p === 'battle') el.classList.toggle('flex', p === phase);
                });
            }

            function poll() { return api('/state').then(res => { if (res && res.success) apply(res.data); }); }

            function apply(d) {
                S = d;
                if (d.server_now) clockOffset = d.server_now - Date.now();
                if (d.status === 'lobby' || !d.role) { renderLobby(); show('lobby'); return; }
                if (d.status === 'placing') { renderPlacementStatus(); show('place'); return; }
                // playing / finished
                if (lastShotId === null) lastShotId = d.shots.length ? d.shots[d.shots.length - 1].id : 0; // reload: don't replay history
                d.shots.filter(s => s.id > lastShotId).forEach(animateShot);
                if (d.shots.length) lastShotId = Math.max(lastShotId, d.shots[d.shots.length - 1].id);
                show('battle');
                drawBattle();
                if (d.status === 'finished') setTimeout(endGame, Math.max(0, busyUntil - Date.now()));
            }

            /* ---------------- lobby ---------------- */
            function personChip(p, el, tag) {
                el.innerHTML = '';
                el.className = 'flex items-center gap-2 border-4 border-main-dark bg-main-light pl-1 pr-3 py-1';
                const img = document.createElement('img');
                img.src = p.avatar; img.alt = ''; img.className = 'w-9 h-9 border-2 border-main-dark object-cover';
                img.onerror = function () { this.onerror = null; this.src = '/images/default-avatar.jpg'; };
                const name = document.createElement('span'); name.className = 'fira-sans font-bold text-main-dark'; name.textContent = p.username;
                el.appendChild(img); el.appendChild(name);
                if (tag) { const t = document.createElement('span'); t.className = 'bg-pop border-2 border-main-dark px-1 russo text-[10px] uppercase'; t.textContent = tag; el.appendChild(t); }
            }
            let friendsLoaded = false;
            function renderLobby() {
                if (S.host) personChip(S.host, $('lobby-host'), 'Host');
                if (S.guest) personChip(S.guest, $('lobby-guest'));
                const canJoin = !S.role && !S.guest && S.status === 'lobby';
                $('lobby-join').classList.toggle('hidden', !canJoin);
                $('lobby-join').classList.toggle('flex', canJoin);
                if (canJoin && S.host) $('lobby-join-text').textContent = S.host.username + ' challenged you to a Battleship match!';
                $('lobby-full').classList.toggle('hidden', !( !S.role && S.guest));
                const inviting = S.role === 'host' && S.status === 'lobby';
                $('lobby-invite').classList.toggle('hidden', !inviting);
                if (inviting && !friendsLoaded) { friendsLoaded = true; loadFriends(); }
            }
            function loadFriends() {
                fetch('/friends/list', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' }).then(r => r.json()).then(res => {
                    const list = $('friend-list'); list.innerHTML = '';
                    const friends = (res && res.success) ? res.data : [];
                    if (!friends.length) { list.innerHTML = '<p class="fira-sans text-sm text-main-dark/60">No friends yet — add some from the mail envelope, or share the link.</p>'; return; }
                    friends.forEach(f => {
                        const row = document.createElement('div');
                        row.className = 'flex items-center gap-3 border-2 border-main-dark px-2 py-1.5';
                        const img = document.createElement('img'); img.src = f.avatar; img.alt = ''; img.className = 'w-8 h-8 border-2 border-main-dark object-cover';
                        img.onerror = function () { this.onerror = null; this.src = '/images/default-avatar.jpg'; };
                        const name = document.createElement('span'); name.className = 'flex-1 min-w-0 fira-sans font-bold text-main-dark truncate'; name.textContent = f.username;
                        const btn = document.createElement('button'); btn.type = 'button';
                        btn.className = 'border-2 border-main-dark px-3 py-1 russo text-[10px] uppercase tracking-wide bg-pop text-main-dark hover:bg-white disabled:opacity-60';
                        btn.textContent = 'Invite';
                        btn.addEventListener('click', () => invite(f, btn));
                        row.appendChild(img); row.appendChild(name); row.appendChild(btn);
                        list.appendChild(row);
                    });
                }).catch(() => {});
            }
            // The invite is a normal chat message carrying the match link; the friends
            // widget turns it into an invite card with a Join button.
            function invite(friend, btn) {
                btn.disabled = true; btn.textContent = 'Invited ✓';
                fetch('/friends/messages', {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ to_user_id: friend.id, message: '⚓ Battleship challenge! ' + JOIN_URL }),
                }).then(r => r.json()).then(res => {
                    if (!res || !res.success) { btn.disabled = false; btn.textContent = 'Invite'; return; }
                    const m = res.data;
                    if (window.socket && window.socket.connected) {
                        window.socket.emit('friendsMessage', { toUserId: friend.id, payload: { id: m.id, from_user_id: m.from_user_id, from_username: m.from_username, from_avatar: m.from_avatar, message: m.message, created_at: m.created_at } });
                    }
                }).catch(() => { btn.disabled = false; btn.textContent = 'Invite'; });
            }
            $('join-btn').addEventListener('click', () => {
                $('join-btn').disabled = true;
                api('/join', 'POST', {}).then(res => {
                    $('join-btn').disabled = false;
                    if (!res.success) { $('lobby-error').textContent = res.message; $('lobby-error').classList.remove('hidden'); }
                    poll();
                });
            });
            $('copy-btn').addEventListener('click', () => {
                if (!navigator.clipboard) return;
                navigator.clipboard.writeText(JOIN_URL).then(() => { $('copy-btn').textContent = 'Copied!'; setTimeout(() => { $('copy-btn').textContent = 'Copy'; }, 2000); });
            });

            /* ---------------- placement (same rules as VS PC) ---------------- */
            let ships = [];                // [[{r,c}...]...]
            let grid = {};                 // "r,c" -> ship index
            let orient = true, placeIdx = 0, submitted = false;
            const occupied = (r, c) => grid[key(r, c)] !== undefined;
            const shipCellsFrom = (r, c, len, horiz) => Array.from({ length: len }, (_, i) => horiz ? { r, c: c + i } : { r: r + i, c });
            function canPlace(cells) {
                return cells.every(({ r, c }) => {
                    if (!inB(r, c) || occupied(r, c)) return false;
                    if (TOUCH) return true;
                    for (let dr = -1; dr <= 1; dr++) for (let dc = -1; dc <= 1; dc++) if (inB(r + dr, c + dc) && occupied(r + dr, c + dc)) return false;
                    return true;
                });
            }
            function placeShip(cells) { const id = ships.length; ships.push(cells); cells.forEach(({ r, c }) => grid[key(r, c)] = id); }
            function autoFleet() {
                for (let attempt = 0; attempt < 500; attempt++) {
                    ships = []; grid = {};
                    let ok = true;
                    for (const len of FLEET) {
                        const options = [];
                        for (let r = 0; r < SIZE; r++) for (let c = 0; c < SIZE; c++) for (const h of [true, false]) {
                            const cells = shipCellsFrom(r, c, len, h);
                            if (canPlace(cells)) options.push(cells);
                        }
                        if (!options.length) { ok = false; break; }
                        placeShip(options[Math.floor(Math.random() * options.length)]);
                    }
                    if (ok) return;
                }
            }
            function cellEl(r, c, cls) {
                const cell = document.createElement('div');
                cell.className = 'bship-cell ' + cls; cell.dataset.r = r; cell.dataset.c = c;
                if (['cell-miss', 'cell-hit', 'cell-sunk', 'cell-mine'].includes(cls)) { const m = document.createElement('span'); m.className = 'mark'; cell.appendChild(m); }
                return cell;
            }
            function renderPlacement() {
                const el = $('place-board');
                el.style.gridTemplateColumns = 'repeat(' + SIZE + ', minmax(0, 1fr))';
                el.innerHTML = '';
                for (let r = 0; r < SIZE; r++) for (let c = 0; c < SIZE; c++) {
                    const cell = cellEl(r, c, occupied(r, c) ? 'cell-ship' : 'cell-water');
                    if (!submitted && placeIdx < FLEET.length) {
                        cell.style.cursor = 'pointer';
                        cell.addEventListener('mouseenter', () => preview(r, c));
                        cell.addEventListener('mouseleave', clearPreview);
                        cell.addEventListener('click', () => { const cells = shipCellsFrom(r, c, FLEET[placeIdx], orient); if (canPlace(cells)) { placeShip(cells); placeIdx++; renderPlacement(); } });
                    }
                    el.appendChild(cell);
                }
                const list = $('fleet-list'); list.innerHTML = '';
                FLEET.forEach((len, i) => {
                    const placed = i < ships.length, active = i === placeIdx && !placed;
                    const row = document.createElement('div');
                    row.className = 'flex items-center gap-2 border-2 border-main-dark px-2 py-1.5 ' + (placed ? 'bg-main-light opacity-60' : (active ? 'bg-pop' : 'bg-white'));
                    const pips = document.createElement('div'); pips.className = 'flex';
                    for (let j = 0; j < len; j++) { const p = document.createElement('span'); p.className = 'w-3.5 h-3.5 bg-main-dark border border-white/40'; pips.appendChild(p); }
                    const label = document.createElement('span'); label.className = 'fira-sans text-xs text-main-dark/70 ml-auto';
                    label.textContent = placed ? '✓ placed' : (active ? 'placing…' : len + ' long');
                    row.appendChild(pips); row.appendChild(label); list.appendChild(row);
                });
                $('ready-btn').disabled = submitted || ships.length !== FLEET.length;
                $('place-tools').classList.toggle('opacity-40', submitted);
                $('place-tools').querySelectorAll('button').forEach(b => { b.disabled = submitted; });
            }
            function preview(r, c) {
                const cells = shipCellsFrom(r, c, FLEET[placeIdx], orient), ok = canPlace(cells);
                cells.forEach(({ r, c }) => { const el = $('place-board').querySelector('[data-r="' + r + '"][data-c="' + c + '"]'); if (el) el.classList.add(ok ? 'cell-preview-ok' : 'cell-preview-bad'); });
            }
            function clearPreview() { $('place-board').querySelectorAll('.cell-preview-ok,.cell-preview-bad').forEach(el => el.classList.remove('cell-preview-ok', 'cell-preview-bad')); }
            function renderPlacementStatus() {
                const meReady = S[S.role + '_ready'], oppReady = S[(S.role === 'host' ? 'guest' : 'host') + '_ready'];
                if (meReady && !submitted) { submitted = true; renderPlacement(); }
                $('place-status').innerHTML = (meReady ? '✓ Your fleet is locked in. ' : '') +
                    '<b>' + esc(oppName()) + '</b>: ' + (oppReady ? 'ready ✓' : 'placing ships…');
                $('ready-btn').textContent = meReady ? 'Waiting for ' + oppName() + '…' : 'Ready';
            }
            $('rotate-btn').addEventListener('click', () => { orient = !orient; $('orient-label').textContent = orient ? 'Horizontal' : 'Vertical'; });
            $('auto-btn').addEventListener('click', () => { autoFleet(); placeIdx = FLEET.length; renderPlacement(); });
            $('clear-btn').addEventListener('click', () => { ships = []; grid = {}; placeIdx = 0; renderPlacement(); });
            $('ready-btn').addEventListener('click', () => {
                if (ships.length !== FLEET.length) return;
                submitted = true; renderPlacement();
                api('/place', 'POST', { fleet: ships.map(cells => cells.map(({ r, c }) => [r, c])) }).then(res => {
                    if (!res.success) { submitted = false; renderPlacement(); $('place-status').textContent = res.message; }
                    poll();
                });
            });

            /* ---------------- battle ---------------- */
            // My shots on the enemy board, and shots on my board (opponent's + my backfires).
            function boards() {
                const enemy = {}, mine = {};
                S.shots.forEach(s => {
                    if (s.result === 'timeout') return;
                    if (s.by === S.role) {
                        enemy[key(s.r, s.c)] = s.result;
                        if (s.backfire && s.backfire.r !== undefined) mine[key(s.backfire.r, s.backfire.c)] = 'hit';
                    } else {
                        mine[key(s.r, s.c)] = s.result;
                    }
                });
                return { enemy, mine };
            }
            function drawBattle() {
                const { enemy, mine } = boards();
                const myTurn = S.my_turn && Date.now() >= busyUntil && !firing && S.status === 'playing';
                // Enemy waters: shot results, sunk (and at the end, all) enemy ships.
                const sunkCells = {}, revealed = {};
                (S.sunk_enemy_ships || []).forEach(sh => sh.cells.forEach(([r, c]) => { (sh.sunk ? sunkCells : revealed)[key(r, c)] = true; }));
                const eEl = $('enemy-board');
                eEl.style.gridTemplateColumns = 'repeat(' + SIZE + ', minmax(0, 1fr))'; eEl.innerHTML = '';
                for (let r = 0; r < SIZE; r++) for (let c = 0; c < SIZE; c++) {
                    const k = key(r, c), res = enemy[k];
                    let cls = sunkCells[k] ? 'cell-sunk' : res === 'hit' || res === 'sunk' ? 'cell-hit' : res === 'mine' ? 'cell-mine' : res === 'miss' ? 'cell-miss' : revealed[k] ? 'cell-ship' : (myTurn ? 'cell-fire' : 'cell-water');
                    const cell = cellEl(r, c, cls);
                    if (lastKey.enemy === k) cell.classList.add('cell-new');
                    if (cls === 'cell-fire') cell.addEventListener('click', () => fire(r, c));
                    eEl.appendChild(cell);
                }
                // Your fleet.
                const myShips = S.my_fleet || [];
                const shipOf = {};
                myShips.forEach((cells, i) => cells.forEach(([r, c]) => { shipOf[key(r, c)] = i; }));
                const shipSunk = (i) => myShips[i].every(([r, c]) => mine[key(r, c)] === 'hit' || mine[key(r, c)] === 'sunk');
                const pEl = $('player-board');
                pEl.style.gridTemplateColumns = 'repeat(' + SIZE + ', minmax(0, 1fr))'; pEl.innerHTML = '';
                let left = 0, enemyLeft = 0;
                for (let r = 0; r < SIZE; r++) for (let c = 0; c < SIZE; c++) {
                    const k = key(r, c), res = mine[k], sid = shipOf[k];
                    let cls = 'cell-water';
                    if (sid !== undefined) cls = (res === 'hit' || res === 'sunk') ? (shipSunk(sid) ? 'cell-sunk' : 'cell-hit') : 'cell-ship';
                    else if (res === 'mine') cls = 'cell-mine';
                    else if (res === 'miss') cls = 'cell-miss';
                    if (sid !== undefined && cls === 'cell-ship') left++;
                    const cell = cellEl(r, c, cls);
                    if (lastKey.player === k) cell.classList.add('cell-new');
                    pEl.appendChild(cell);
                }
                const total = FLEET.reduce((a, b) => a + b, 0);
                enemyLeft = total - Object.values(enemy).filter(v => v === 'hit' || v === 'sunk').length;
                $('player-left').textContent = left + ' hull left';
                $('enemy-left').textContent = Math.max(0, enemyLeft) + ' hull left';
                $('enemy-name').textContent = oppName();
                updateBanner();
                const myMissed = S.missed ? S.missed[S.role] : 0;
                const warn = $('missed-warning');
                warn.classList.toggle('hidden', !(S.status === 'playing' && myMissed > 0));
                warn.textContent = '⚠ You\'ve missed ' + myMissed + ' turn' + (myMissed === 1 ? '' : 's') + ' in a row — miss ' + S.max_missed + ' and you forfeit.';
                if (S.my_turn && Date.now() < busyUntil) setTimeout(drawBattle, busyUntil - Date.now() + 20);
            }
            // Banner text + countdown, refreshed by a ticker between polls.
            let clockOffset = 0; // server clock - local clock
            function updateBanner() {
                if (!S || !$('turn-banner')) return;
                const banner = $('turn-banner'), bar = $('turn-bar');
                if (S.status !== 'playing') { banner.textContent = 'Battle over'; setBanner('bg-main-dark text-white'); bar.style.width = '0%'; return; }
                const left = Math.max(0, Math.ceil((S.turn_deadline - (Date.now() + clockOffset)) / 1000));
                const pct = Math.max(0, Math.min(100, (left / S.turn_seconds) * 100));
                bar.style.width = pct + '%';
                bar.className = 'h-full ' + (pct <= 20 ? 'bg-main-accent' : (pct <= 50 ? 'bg-pop' : 'bg-green-500'));
                if (S.my_turn) {
                    banner.textContent = 'Your turn — fire at will · ' + left + 's';
                    setBanner(left <= 10 ? 'bg-main-accent text-white' : 'bg-main-dark text-white');
                } else {
                    banner.textContent = oppName() + ' is taking aim… ' + left + 's';
                    setBanner('bg-brand text-white');
                }
            }
            setInterval(updateBanner, 250);
            function setBanner(colours) {
                $('turn-banner').className = 'border-4 border-main-dark shadow-[4px_4px_0_0_#14323B] px-5 py-3 russo uppercase tracking-widest text-sm text-center ' + colours;
            }
            function fire(r, c) {
                if (firing || !S.my_turn || Date.now() < busyUntil) return;
                firing = true; drawBattle();
                api('/fire', 'POST', { r, c }).then(res => {
                    firing = false;
                    if (res.success && res.data && res.data.shot) {
                        // Animate now; the next poll won't replay it.
                        animateShot(res.data.shot);
                        lastShotId = Math.max(lastShotId || 0, res.data.shot.id);
                    }
                    poll();
                });
            }
            function animateShot(s) {
                const mineShot = s.by === S.role;
                busyUntil = Date.now() + RESULT_PAUSE;
                if (s.result === 'timeout') {
                    if (mineShot) { callout('enemy', "Time's up!", 'mine'); log('Ran out of time — turn skipped.', true); }
                    else { callout('player', "Their time's up", 'mine'); log('Ran out of time — turn skipped.', false); }
                    playSfx('miss');
                    return;
                }
                if (mineShot) {
                    lastKey.enemy = key(s.r, s.c);
                    if (s.result === 'hit') { callout('enemy', 'Hit!', 'hit'); log('Hit at ' + coord(s.r, s.c) + '!', true); }
                    else if (s.result === 'sunk') { callout('enemy', 'Sunk!', 'sunk'); log('Sunk an enemy ship at ' + coord(s.r, s.c) + '! 🔥', true); }
                    else if (s.result === 'mine') {
                        callout('enemy', 'Mine!', 'mine');
                        log('Hit a <b>mine</b> at ' + coord(s.r, s.c) + ' — it backfired on your fleet!', true);
                        if (s.backfire && s.backfire.r !== undefined) { lastKey.player = key(s.backfire.r, s.backfire.c); flashPlayerHit(); }
                    } else { callout('enemy', 'Miss', 'miss'); log('Miss at ' + coord(s.r, s.c) + '.', true); }
                } else {
                    lastKey.player = key(s.r, s.c);
                    if (s.result === 'hit' || s.result === 'sunk') {
                        callout('player', s.result === 'sunk' ? 'Ship down!' : "You're hit!", s.result); flashPlayerHit();
                        log('Hit your fleet at ' + coord(s.r, s.c) + (s.result === 'sunk' ? ' — ship down!' : '.'), false);
                    } else if (s.result === 'mine') { callout('player', 'They hit a mine!', 'mine'); log('Struck a <b>mine</b> — the blast hit their own fleet!', false); }
                    else { callout('player', 'They missed', 'miss'); log('Missed at ' + coord(s.r, s.c) + '.', false); }
                }
                playSfx(s.result);
            }

            let ended = false;
            function endGame() {
                if (ended) return; ended = true;
                drawBattle();
                setTimeout(() => {
                    $('over-phase').classList.remove('hidden');
                    const win = S.winner_user_id == S.me;
                    const card = $('result-card');
                    card.classList.add(win ? 'bg-pop' : 'bg-main-dark');
                    $('result-emoji').textContent = win ? '🏆' : '💀';
                    $('result-title').textContent = win ? 'Victory!' : (S.ended_reason === 'forfeit' ? 'Forfeited' : 'Fleet Lost');
                    $('result-title').classList.add(win ? 'text-main-dark' : 'text-white');
                    const mineShots = S.shots.filter(s => s.by === S.role);
                    const hits = mineShots.filter(s => s.result === 'hit' || s.result === 'sunk').length;
                    const stats = $('result-stats');
                    const fired = mineShots.filter(s => s.result !== 'timeout');
                    const how = S.ended_reason === 'forfeit'
                        ? (win ? oppName() + ' forfeited after ' + S.max_missed + ' missed turns in a row' : 'You forfeited after ' + S.max_missed + ' missed turns in a row')
                        : (win ? 'You sank ' + oppName() + "'s fleet" : oppName() + ' sank your fleet');
                    stats.textContent = how + ' · ' + fired.length + ' shots · ' + hits + ' hits · ' + (fired.length ? Math.round(hits / fired.length * 100) : 0) + '% accuracy';
                    stats.classList.add(win ? 'text-main-dark/70' : 'text-white/70');
                }, 400);
            }

            /* ---------------- boot ---------------- */
            autoFleet(); placeIdx = FLEET.length; renderPlacement();
            poll();
            setInterval(() => { if (!ended) poll(); }, 1000);
        })();
    </script>

@endsection
