@extends('game-api::layout')
@section('body')

    <div class="w-full px-[15px] lg:px-[30px] pt-8 pb-12">
        <div class="max-w-5xl mx-auto flex flex-col gap-6">

            {{-- Header --}}
            <div class="flex flex-wrap items-center gap-3 justify-between">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="relative inline-block">
                        <span aria-hidden="true" class="absolute inset-0 -rotate-2 bg-brand"></span>
                        <span class="relative block russo text-white text-base md:text-xl tracking-widest uppercase px-3 py-1">Battleship</span>
                    </h1>
                    <span class="inline-block border-2 border-main-dark bg-main-light px-2 py-0.5 russo text-[10px] uppercase tracking-wide text-main-dark">VS PC</span>
                    <span class="inline-block border-2 border-main-dark bg-main-light px-2 py-0.5 russo text-[10px] uppercase tracking-wide text-main-dark">{{ $size }}×{{ $size }}</span>
                    <span class="inline-block border-2 border-main-dark bg-main-light px-2 py-0.5 russo text-[10px] uppercase tracking-wide text-main-dark">{{ $ships }} ships</span>
                    @if($mines)<span class="inline-block border-2 border-main-dark bg-pop px-2 py-0.5 russo text-[10px] uppercase tracking-wide text-main-dark">Mines</span>@endif
                    <span class="inline-block border-2 border-main-dark bg-main-light px-2 py-0.5 russo text-[10px] uppercase tracking-wide text-main-dark">{{ $touch ? 'Ships can touch' : 'No touching' }}</span>
                </div>
                <div class="flex items-center gap-2">
                <button type="button" id="sound-btn" aria-pressed="true" class="fira-sans font-bold uppercase text-xs tracking-wide bg-white text-main-dark border-4 border-main-dark shadow-[3px_3px_0_0_#14323B] px-3 py-2 transition-all duration-100 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[5px_5px_0_0_#14323B] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[0px_0px_0_0_#14323B]">Sound: On</button>
                <a href="/battleship" class="fira-sans font-bold uppercase text-xs tracking-wide bg-white text-main-dark border-4 border-main-dark shadow-[3px_3px_0_0_#14323B] px-4 py-2 transition-all duration-100 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[5px_5px_0_0_#14323B] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[0px_0px_0_0_#14323B]">&larr; Leave</a>
                </div>
            </div>

            {{-- ========================= PLACEMENT ========================= --}}
            <div id="place-phase" class="grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-6">
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
                        @if($touch)
                            Ships can sit side by side, but can't overlap.
                        @else
                            Ships can't overlap or touch &mdash; keep at least one square of water around each ship, diagonals included.
                        @endif
                    </p>
                    <div>
                        <span class="fira-sans text-xs uppercase tracking-widest text-main-dark/60">Fleet</span>
                        <div id="fleet-list" class="flex flex-col gap-2 mt-2"></div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <button type="button" id="rotate-btn" class="fira-sans font-black uppercase text-xs tracking-wide bg-white text-main-dark border-4 border-main-dark shadow-[3px_3px_0_0_#14323B] px-4 py-2 transition-all duration-100 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[5px_5px_0_0_#14323B] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[0px_0px_0_0_#14323B]">Rotate: <span id="orient-label">Horizontal</span></button>
                        <button type="button" id="auto-btn" class="fira-sans font-black uppercase text-xs tracking-wide bg-brand text-white border-4 border-main-dark shadow-[3px_3px_0_0_#14323B] px-4 py-2 transition-all duration-100 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[5px_5px_0_0_#14323B] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[0px_0px_0_0_#14323B]">Auto-place</button>
                        <button type="button" id="clear-btn" class="fira-sans font-black uppercase text-xs tracking-wide bg-white text-main-dark border-4 border-main-dark shadow-[3px_3px_0_0_#14323B] px-4 py-2 transition-all duration-100 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[5px_5px_0_0_#14323B] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[0px_0px_0_0_#14323B]">Clear</button>
                    </div>
                    <button type="button" id="ready-btn" disabled class="fira-sans font-black uppercase tracking-wider bg-main-accent text-white border-4 border-main-dark shadow-[5px_5px_0_0_#14323B] px-6 py-3 transition-all duration-100 enabled:hover:-translate-x-0.5 enabled:hover:-translate-y-0.5 enabled:hover:shadow-[7px_7px_0_0_#14323B] enabled:active:translate-x-1 enabled:active:translate-y-1 enabled:active:shadow-[0px_0px_0_0_#14323B] disabled:opacity-40 disabled:cursor-not-allowed">Start Battle</button>
                </div>
            </div>

            {{-- ========================= BATTLE ========================= --}}
            <div id="battle-phase" class="hidden flex-col gap-6">
                <div id="turn-banner" class="bg-main-dark text-white border-4 border-main-dark shadow-[4px_4px_0_0_#14323B] px-5 py-3 russo uppercase tracking-widest text-sm text-center">Your turn — fire at will</div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Enemy waters (fire here) --}}
                    <div class="bg-white border-4 border-main-dark shadow-[6px_6px_0_0_#14323B]">
                        <div class="flex items-center gap-2 bg-main-accent px-4 py-2.5">
                            <span class="w-3 h-3 rounded-full bg-white shrink-0"></span>
                            <span class="russo uppercase tracking-widest text-white text-xs">Enemy Waters</span>
                            <span id="enemy-left" class="ml-auto fira-sans text-white text-xs font-bold"></span>
                        </div>
                        <div class="relative p-3 md:p-4 flex justify-center">
                            <div id="enemy-board" class="bship-board"></div>
                            <div id="enemy-callout" class="shot-callout" aria-live="polite"></div>
                        </div>
                    </div>
                    {{-- Your fleet --}}
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
                    <div id="result-stats" class="fira-sans text-main-dark/70"></div>
                    <div class="flex flex-wrap justify-center gap-3 mt-2">
                        <button type="button" onclick="location.reload()" class="fira-sans font-black uppercase tracking-wider bg-main-accent text-white border-4 border-main-dark shadow-[5px_5px_0_0_#14323B] px-6 py-3 transition-all duration-100 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[7px_7px_0_0_#14323B] active:translate-x-1 active:translate-y-1 active:shadow-[0px_0px_0_0_#14323B]">Rematch</button>
                        <a href="/battleship" class="fira-sans font-black uppercase tracking-wider bg-white text-main-dark border-4 border-main-dark shadow-[5px_5px_0_0_#14323B] px-6 py-3 transition-all duration-100 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[7px_7px_0_0_#14323B] active:translate-x-1 active:translate-y-1 active:shadow-[0px_0px_0_0_#14323B]">New Setup</a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @include('battleship::partials.board-styles')

    <script>
        (function () {
            const CFG = { mode: '{{ $mode }}', size: {{ $size }}, ships: {{ $ships }}, mines: {{ (int) $mines }}, touch: {{ (int) $touch }} };
            const FLEETS = {3:[5,4,3], 4:[5,4,3,2], 5:[5,4,3,3,2], 6:[5,4,3,3,2,2], 7:[5,4,4,3,3,2,2]};
            const SIZE = CFG.size;
            const FLEET = FLEETS[CFG.ships] || FLEETS[5];
            const MINE_COUNT = CFG.mines ? Math.max(2, Math.round(SIZE * SIZE * 0.04)) : 0;
            // Pacing: how long a shot's result stays on screen before play moves on,
            // and how long the enemy "aims" before firing.
            const RESULT_PAUSE = 1300;
            const AIM_PAUSE = 800;
            const key = (r, c) => r + ',' + c;

            /* ---------------- sound ---------------- */
            const SFX = {
                hit: new Audio('/vendor/battleship-game/sounds/shot_hit.wav'),
                miss: new Audio('/vendor/battleship-game/sounds/shot_missed.wav'),
            };
            Object.values(SFX).forEach(a => { a.preload = 'auto'; });
            let muted = false;
            try { muted = localStorage.getItem('bship_muted') === '1'; } catch (e) {}
            function playSfx(name) {
                if (muted || !SFX[name]) return;
                const a = SFX[name].cloneNode(); // clone so quick shots can overlap
                a.volume = 0.8;
                a.play().catch(() => {});
            }
            const soundBtn = document.getElementById('sound-btn');
            function paintSound() {
                soundBtn.textContent = muted ? 'Sound: Off' : 'Sound: On';
                soundBtn.setAttribute('aria-pressed', muted ? 'false' : 'true');
                soundBtn.classList.toggle('opacity-60', muted);
            }
            soundBtn.addEventListener('click', () => {
                muted = !muted;
                try { localStorage.setItem('bship_muted', muted ? '1' : '0'); } catch (e) {}
                paintSound();
            });
            paintSound();

            /* ---------------- shot feedback ---------------- */
            const CALLOUT_STYLE = {
                hit:  'background:#E62727;color:#fff',
                sunk: 'background:#7f1414;color:#fff',
                miss: 'background:#fff;color:#14323B',
                mine: 'background:#FFB900;color:#14323B',
            };
            function callout(which, text, tag) {
                const el = document.getElementById(which + '-callout');
                el.innerHTML = '';
                const span = document.createElement('span');
                span.textContent = text;
                span.style.cssText = CALLOUT_STYLE[tag] || CALLOUT_STYLE.miss;
                el.appendChild(span);
                el.classList.remove('is-on'); void el.offsetWidth; el.classList.add('is-on');
            }
            // Red flash + shake on your fleet's window whenever your ships take damage.
            function flashPlayerHit() {
                const w = document.getElementById('player-window');
                w.classList.remove('is-hit'); void w.offsetWidth; w.classList.add('is-hit');
            }
            function feedback(tag) { playSfx(tag === 'miss' ? 'miss' : 'hit'); }
            const lastShot = { enemy: null, player: null };
            const inB = (r, c) => r >= 0 && c >= 0 && r < SIZE && c < SIZE;
            // Ship ids start at 0, so test occupancy against undefined (a plain
            // truthiness check treated ship #0 as empty water and let ships overlap).
            const occupied = (board, r, c) => board.grid[key(r, c)] !== undefined;

            function makeBoard() {
                return { ships: [], grid: {}, shots: new Set(), result: {}, mines: new Set() };
            }
            function shipCellsFrom(r, c, len, horiz) {
                const cells = [];
                for (let i = 0; i < len; i++) cells.push(horiz ? { r, c: c + i } : { r: r + i, c });
                return cells;
            }
            // Ships may never overlap. Unless "ships can touch" was enabled in setup,
            // they also need a ring of open water: no other ship in any of the 8
            // neighbouring squares (diagonals included).
            function canPlace(board, cells) {
                return cells.every(({ r, c }) => {
                    if (!inB(r, c) || occupied(board, r, c)) return false;
                    if (CFG.touch) return true;
                    for (let dr = -1; dr <= 1; dr++) for (let dc = -1; dc <= 1; dc++) {
                        if (inB(r + dr, c + dc) && occupied(board, r + dr, c + dc)) return false;
                    }
                    return true;
                });
            }
            function placeShip(board, cells) {
                const id = board.ships.length;
                board.ships.push({ id, cells, hits: new Set() });
                cells.forEach(({ r, c }) => board.grid[key(r, c)] = id);
                return id;
            }
            // Random fleet layout. Picks uniformly among every legal position for each
            // ship (largest first) and starts over if a ship has nowhere to go.
            function autoFleet(board) {
                for (let attempt = 0; attempt < 500; attempt++) {
                    board.ships = []; board.grid = {};
                    let ok = true;
                    for (const len of FLEET) {
                        const options = [];
                        for (let r = 0; r < SIZE; r++) for (let c = 0; c < SIZE; c++) {
                            for (const horiz of [true, false]) {
                                const cells = shipCellsFrom(r, c, len, horiz);
                                if (canPlace(board, cells)) options.push(cells);
                            }
                        }
                        if (!options.length) { ok = false; break; }
                        placeShip(board, options[Math.floor(Math.random() * options.length)]);
                    }
                    if (ok) return true;
                }
                return false;
            }
            function placeMines(board) {
                board.mines = new Set();
                let placed = 0, guard = 0;
                while (placed < MINE_COUNT && guard++ < 2000) {
                    const r = Math.floor(Math.random() * SIZE), c = Math.floor(Math.random() * SIZE);
                    const k = key(r, c);
                    if (!occupied(board, r, c) && !board.mines.has(k)) { board.mines.add(k); placed++; }
                }
            }
            const fleetSunk = (b) => b.ships.length > 0 && b.ships.every(s => s.hits.size === s.cells.length);
            const aliveSegments = (b) => {
                const out = [];
                b.ships.forEach(s => s.cells.forEach(({ r, c }) => { if (!s.hits.has(key(r, c))) out.push({ ship: s, r, c }); }));
                return out;
            };

            // Fire on targetBoard; mine backfire damages shooterBoard. Returns a result tag.
            function fire(targetBoard, shooterBoard, r, c) {
                const k = key(r, c);
                if (targetBoard.shots.has(k) || targetBoard.result[k]) return null;
                if (targetBoard.mines.has(k)) {
                    targetBoard.mines.delete(k);
                    targetBoard.result[k] = 'mine';
                    const seg = backfire(shooterBoard);
                    return { tag: 'mine', seg };
                }
                targetBoard.shots.add(k);
                const sid = targetBoard.grid[k];
                if (sid !== undefined) {
                    const ship = targetBoard.ships[sid];
                    ship.hits.add(k);
                    targetBoard.result[k] = 'hit';
                    return { tag: ship.hits.size === ship.cells.length ? 'sunk' : 'hit' };
                }
                targetBoard.result[k] = 'miss';
                return { tag: 'miss' };
            }
            // Damage a random still-afloat segment of the shooter's own fleet.
            function backfire(board) {
                const seg = aliveSegments(board);
                if (!seg.length) return null;
                const pick = seg[Math.floor(Math.random() * seg.length)];
                pick.ship.hits.add(key(pick.r, pick.c));
                return { r: pick.r, c: pick.c, sunk: pick.ship.hits.size === pick.ship.cells.length };
            }

            /* ---------------- rendering ---------------- */
            function renderBoard(el, board, { reveal, clickable, onFire, lastKey }) {
                el.style.gridTemplateColumns = 'repeat(' + SIZE + ', minmax(0, 1fr))';
                el.innerHTML = '';
                for (let r = 0; r < SIZE; r++) {
                    for (let c = 0; c < SIZE; c++) {
                        const k = key(r, c);
                        const cell = document.createElement('div');
                        cell.className = 'bship-cell';
                        cell.dataset.r = r; cell.dataset.c = c;
                        const res = board.result[k];
                        const hasShip = board.grid[k] !== undefined;
                        let cls = 'cell-water';
                        if (res === 'mine') cls = 'cell-mine';
                        else if (res === 'hit' || res === 'sunk' || (hasShip && isHit(board, k))) {
                            cls = shipSunk(board, k) ? 'cell-sunk' : 'cell-hit';
                        } else if (res === 'miss') cls = 'cell-miss';
                        else if (hasShip && reveal) cls = 'cell-ship';
                        else if (clickable) cls = 'cell-fire';
                        cell.classList.add(cls);
                        if (lastKey === k) cell.classList.add('cell-new');
                        if (cls === 'cell-miss' || cls === 'cell-hit' || cls === 'cell-sunk' || cls === 'cell-mine') {
                            const m = document.createElement('span'); m.className = 'mark'; cell.appendChild(m);
                        }
                        if (clickable && cls === 'cell-fire') {
                            cell.addEventListener('click', () => onFire(r, c));
                        }
                        el.appendChild(cell);
                    }
                }
            }
            const isHit = (b, k) => { const s = b.grid[k]; return s !== undefined && b.ships[s].hits.has(k); };
            const shipSunk = (b, k) => { const s = b.grid[k]; return s !== undefined && b.ships[s].hits.size === b.ships[s].cells.length; };

            const segsLeft = (b) => b.ships.reduce((n, s) => n + (s.cells.length - s.hits.size), 0);

            /* ---------------- state ---------------- */
            const playerBoard = makeBoard();
            const enemyBoard = makeBoard();
            let phase = 'place', orient = true, placeIdx = 0, locked = false;
            let shotsFired = 0, shotsHit = 0;
            const aiQueue = [];

            /* ---------------- placement ---------------- */
            const placeEl = document.getElementById('place-board');
            const fleetListEl = document.getElementById('fleet-list');
            const readyBtn = document.getElementById('ready-btn');

            function renderFleetList() {
                fleetListEl.innerHTML = '';
                FLEET.forEach((len, i) => {
                    const placed = i < playerBoard.ships.length;
                    const row = document.createElement('div');
                    const active = (i === placeIdx) && !placed;
                    row.className = 'flex items-center gap-2 border-2 border-main-dark px-2 py-1.5 ' +
                        (placed ? 'bg-main-light opacity-60' : (active ? 'bg-pop' : 'bg-white'));
                    const pips = document.createElement('div'); pips.className = 'flex';
                    for (let j = 0; j < len; j++) { const p = document.createElement('span'); p.className = 'w-3.5 h-3.5 bg-main-dark border border-white/40'; pips.appendChild(p); }
                    row.appendChild(pips);
                    const label = document.createElement('span');
                    label.className = 'fira-sans text-xs text-main-dark/70 ml-auto';
                    label.textContent = placed ? '✓ placed' : (active ? 'placing…' : len + ' long');
                    row.appendChild(label);
                    fleetListEl.appendChild(row);
                });
            }
            function renderPlacement() {
                renderBoard(placeEl, playerBoard, { reveal: true, clickable: false });
                // hover preview for the current ship
                if (placeIdx < FLEET.length) {
                    placeEl.querySelectorAll('.bship-cell').forEach(cell => {
                        cell.addEventListener('mouseenter', onPreview);
                        cell.addEventListener('mouseleave', clearPreview);
                        cell.addEventListener('click', onPlaceClick);
                        cell.style.cursor = 'pointer';
                    });
                }
                renderFleetList();
                readyBtn.disabled = playerBoard.ships.length !== FLEET.length;
            }
            function previewCells(r, c) { return shipCellsFrom(r, c, FLEET[placeIdx], orient); }
            function onPreview(e) {
                const r = +e.currentTarget.dataset.r, c = +e.currentTarget.dataset.c;
                const cells = previewCells(r, c);
                const ok = canPlace(playerBoard, cells);
                cells.forEach(({ r, c }) => {
                    if (!inB(r, c)) return;
                    const el = placeEl.querySelector('.bship-cell[data-r="' + r + '"][data-c="' + c + '"]');
                    if (el) el.classList.add(ok ? 'cell-preview-ok' : 'cell-preview-bad');
                });
            }
            function clearPreview() {
                placeEl.querySelectorAll('.cell-preview-ok,.cell-preview-bad').forEach(el => el.classList.remove('cell-preview-ok', 'cell-preview-bad'));
            }
            function onPlaceClick(e) {
                const r = +e.currentTarget.dataset.r, c = +e.currentTarget.dataset.c;
                const cells = previewCells(r, c);
                if (!canPlace(playerBoard, cells)) return;
                placeShip(playerBoard, cells);
                placeIdx++;
                renderPlacement();
            }
            document.getElementById('rotate-btn').addEventListener('click', () => {
                orient = !orient;
                document.getElementById('orient-label').textContent = orient ? 'Horizontal' : 'Vertical';
            });
            document.getElementById('auto-btn').addEventListener('click', () => { autoFleet(playerBoard); placeIdx = FLEET.length; renderPlacement(); });
            document.getElementById('clear-btn').addEventListener('click', () => { playerBoard.ships = []; playerBoard.grid = {}; placeIdx = 0; renderPlacement(); });
            readyBtn.addEventListener('click', startBattle);

            /* ---------------- battle ---------------- */
            const enemyEl = document.getElementById('enemy-board');
            const playerEl = document.getElementById('player-board');
            const banner = document.getElementById('turn-banner');
            const logEl = document.getElementById('log');

            function log(msg, who) {
                const line = document.createElement('div');
                line.innerHTML = '<span class="font-bold ' + (who === 'you' ? 'text-brand' : 'text-main-accent') + '">' +
                    (who === 'you' ? 'You' : 'Enemy') + ':</span> ' + msg;
                logEl.prepend(line);
            }
            function setBanner(text, bg) {
                banner.textContent = text;
                banner.className = 'border-4 border-main-dark shadow-[4px_4px_0_0_#14323B] px-5 py-3 russo uppercase tracking-widest text-sm text-center ' + bg;
            }
            function counts() {
                document.getElementById('enemy-left').textContent = segsLeft(enemyBoard) + ' hull left';
                document.getElementById('player-left').textContent = segsLeft(playerBoard) + ' hull left';
            }

            function startBattle() {
                if (playerBoard.ships.length !== FLEET.length) return;
                autoFleet(enemyBoard);
                if (MINE_COUNT) { placeMines(playerBoard); placeMines(enemyBoard); }
                phase = 'battle';
                document.getElementById('place-phase').classList.add('hidden');
                const bp = document.getElementById('battle-phase');
                bp.classList.remove('hidden'); bp.classList.add('flex');
                drawBattle();
                counts();
            }
            function drawBattle() {
                renderBoard(enemyEl, enemyBoard, { reveal: false, clickable: phase === 'battle' && !locked, onFire: playerFire, lastKey: lastShot.enemy });
                renderBoard(playerEl, playerBoard, { reveal: true, clickable: false, lastKey: lastShot.player });
            }

            function playerFire(r, c) {
                if (locked || phase !== 'battle') return;
                const res = fire(enemyBoard, playerBoard, r, c);
                if (!res) return;
                lastShot.player = null;
                locked = true; // no more shots until the enemy has answered
                shotsFired++;
                lastShot.enemy = key(r, c);
                if (res.tag === 'hit') { shotsHit++; log('Hit at ' + coord(r, c) + '!', 'you'); callout('enemy', 'Hit!', 'hit'); }
                else if (res.tag === 'sunk') { shotsHit++; log('Sunk an enemy ship at ' + coord(r, c) + '! 🔥', 'you'); callout('enemy', 'Sunk!', 'sunk'); }
                else if (res.tag === 'mine') {
                    log('Hit a <b>mine</b> at ' + coord(r, c) + ' — it backfired on your fleet!', 'you');
                    callout('enemy', 'Mine!', 'mine');
                    if (res.seg) {
                        lastShot.player = key(res.seg.r, res.seg.c);
                        log('Your ship took damage at ' + coord(res.seg.r, res.seg.c) + (res.seg.sunk ? ' and sank!' : '.'), 'you');
                        flashPlayerHit();
                    }
                } else { log('Miss at ' + coord(r, c) + '.', 'you'); callout('enemy', 'Miss', 'miss'); }
                feedback(res.tag);
                drawBattle(); counts();
                // Let the result sink in before the game moves on.
                if (gameOutcome()) { setTimeout(() => endGame(gameOutcome()), RESULT_PAUSE); return; }
                setTimeout(() => {
                    setBanner('Enemy is taking aim…', 'bg-main-accent text-white');
                    setTimeout(aiTurn, AIM_PAUSE);
                }, RESULT_PAUSE);
            }

            function aiTurn() {
                lastShot.enemy = null; // your last shot already played its animation
                let r, c;
                // hunt queue first
                while (aiQueue.length) {
                    const [qr, qc] = aiQueue.shift().split(',').map(Number);
                    if (!playerBoard.shots.has(key(qr, qc)) && !playerBoard.result[key(qr, qc)]) { r = qr; c = qc; break; }
                }
                if (r === undefined) {
                    const free = [];
                    for (let i = 0; i < SIZE; i++) for (let j = 0; j < SIZE; j++) {
                        const k = key(i, j);
                        if (!playerBoard.shots.has(k) && !playerBoard.result[k]) free.push([i, j]);
                    }
                    if (!free.length) { endGame(fleetSunk(playerBoard) ? 'lose' : 'win'); return; }
                    [r, c] = free[Math.floor(Math.random() * free.length)];
                }
                const res = fire(playerBoard, enemyBoard, r, c);
                lastShot.player = key(r, c);
                if (res.tag === 'hit' || res.tag === 'sunk') {
                    log('Hit your fleet at ' + coord(r, c) + (res.tag === 'sunk' ? ' — ship down!' : '.'), 'enemy');
                    callout('player', res.tag === 'sunk' ? 'Ship down!' : "You're hit!", res.tag);
                    flashPlayerHit();
                    if (res.tag === 'hit') [[1,0],[-1,0],[0,1],[0,-1]].forEach(([dr, dc]) => { if (inB(r+dr, c+dc)) aiQueue.push(key(r+dr, c+dc)); });
                    else aiQueue.length = 0;
                } else if (res.tag === 'mine') {
                    log('Struck a <b>mine</b> at ' + coord(r, c) + ' — the blast hit its own fleet!', 'enemy');
                    callout('player', 'Enemy hit a mine!', 'mine');
                } else {
                    log('Missed at ' + coord(r, c) + '.', 'enemy');
                    callout('player', 'Enemy missed', 'miss');
                }
                feedback(res.tag);
                drawBattle(); counts();
                if (gameOutcome()) { setTimeout(() => endGame(gameOutcome()), RESULT_PAUSE); return; }
                setTimeout(() => {
                    locked = false;
                    lastShot.player = null;
                    setBanner('Your turn — fire at will', 'bg-main-dark text-white');
                    drawBattle();
                }, RESULT_PAUSE);
            }

            function gameOutcome() {
                if (fleetSunk(enemyBoard)) return 'win';
                if (fleetSunk(playerBoard)) return 'lose';
                return null;
            }

            function endGame(outcome) {
                phase = 'over'; locked = true;
                drawBattle();
                document.getElementById('battle-phase').classList.add('hidden');
                const over = document.getElementById('over-phase');
                over.classList.remove('hidden');
                const card = document.getElementById('result-card');
                const win = outcome === 'win';
                card.classList.add(win ? 'bg-pop' : 'bg-main-dark');
                document.getElementById('result-emoji').textContent = win ? '🏆' : '💀';
                const title = document.getElementById('result-title');
                title.textContent = win ? 'Victory!' : 'Fleet Lost';
                title.classList.add(win ? 'text-main-dark' : 'text-white');
                const acc = shotsFired ? Math.round((shotsHit / shotsFired) * 100) : 0;
                const stats = document.getElementById('result-stats');
                stats.textContent = shotsFired + ' shots · ' + shotsHit + ' hits · ' + acc + '% accuracy';
                stats.classList.add(win ? 'text-main-dark/70' : 'text-white/70');
            }

            function coord(r, c) { return String.fromCharCode(65 + c) + (r + 1); }

            /* ---------------- boot ---------------- */
            autoFleet(playerBoard); placeIdx = FLEET.length; // start pre-placed; player can Clear/rearrange
            renderPlacement();
        })();
    </script>

@endsection
