@extends('game-api::layout')
@section('body')

    @php
        $labelClass = 'fira-sans text-xs uppercase tracking-widest text-main-dark/60 mb-1.5';
    @endphp

    <div class="w-full px-[15px] lg:px-[30px] pt-8 pb-12">
        <div class="max-w-6xl mx-auto flex flex-col gap-8">

            {{-- Header --}}
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                <div>
                    <h1 class="relative self-start inline-block">
                        <span aria-hidden="true" class="absolute inset-0 -rotate-2 bg-brand"></span>
                        <span class="relative block russo text-white text-lg md:text-2xl tracking-widest uppercase px-4 py-1.5">Battleships</span>
                    </h1>
                    <p class="fira-sans text-main-dark/60 mt-3 max-w-xl">Hunt down the enemy fleet before they sink yours. Play the computer, a random challenger, or a friend — then climb the weekly Arena.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Setup panel (spans 2 cols on desktop) --}}
                <div class="lg:col-span-2 bg-white border-4 border-main-dark shadow-[8px_8px_0_0_#14323B]">
                    <div class="flex items-center gap-2.5 bg-main-dark px-4 py-2.5">
                        <span class="w-3.5 h-3.5 rounded-full bg-main-accent shrink-0"></span>
                        <span class="w-3.5 h-3.5 rounded-full bg-pop shrink-0"></span>
                        <span class="w-3.5 h-3.5 rounded-full bg-green-500 shrink-0"></span>
                        <span class="ml-2 russo uppercase tracking-widest text-white text-xs">New Battle</span>
                    </div>
                    <div class="p-5 md:p-6 flex flex-col gap-6">

                        {{-- Opponent --}}
                        <div>
                            <span class="{{ $labelClass }}">Opponent</span>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <button type="button" data-opponent="pc" class="opp-btn text-left border-4 border-main-dark p-3 transition-all">
                                    <div class="russo text-main-dark text-sm uppercase">VS PC</div>
                                    <div class="fira-sans text-xs text-main-dark/60 mt-0.5">Play the computer</div>
                                </button>
                                <button type="button" data-opponent="random" class="opp-btn text-left border-4 border-main-dark p-3 transition-all">
                                    <div class="russo text-main-dark text-sm uppercase">VS Random</div>
                                    <div class="fira-sans text-xs text-main-dark/60 mt-0.5">Matchmake a stranger</div>
                                </button>
                                <button type="button" data-opponent="player" class="opp-btn text-left border-4 border-main-dark p-3 transition-all">
                                    <div class="russo text-main-dark text-sm uppercase">VS Player</div>
                                    <div class="fira-sans text-xs text-main-dark/60 mt-0.5">Invite a friend</div>
                                </button>
                            </div>
                            <p id="online-note" class="hidden fira-sans text-xs text-main-accent font-bold mt-2">⚓ Random matchmaking lands in a later update — play VS PC, or invite a friend with VS Player.</p>
                            <p id="player-note" class="hidden fira-sans text-xs text-main-dark/70 mt-2">You'll get a match lobby with a link to send — or invite a friend straight from your friends list.</p>
                        </div>

                        {{-- Board size --}}
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="{{ $labelClass }} mb-0">Battlefield size</span>
                                <span class="russo text-main-dark"><span id="size-val">10</span> × <span id="size-val2">10</span></span>
                            </div>
                            <input type="range" id="size-range" min="{{ $config['min_board'] }}" max="{{ $config['max_board'] }}" step="2" value="10" class="w-full mt-2 accent-brand">
                            <div class="flex justify-between fira-sans text-[10px] uppercase tracking-widest text-main-dark/40 mt-1">
                                <span>Smaller</span><span>Classic 10×10</span><span>Bigger</span>
                            </div>
                        </div>

                        {{-- Ship count --}}
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="{{ $labelClass }} mb-0">Fleet size</span>
                                <span class="russo text-main-dark"><span id="ships-val">5</span> ships</span>
                            </div>
                            <input type="range" id="ships-range" min="{{ $config['min_ships'] }}" max="{{ $config['max_ships'] }}" step="1" value="5" class="w-full mt-2 accent-brand">
                            <div id="fleet-preview" class="flex flex-wrap items-center gap-2 mt-3"></div>
                        </div>

                        {{-- Mines --}}
                        <label class="flex items-start gap-3 border-4 border-main-dark p-3 cursor-pointer bg-main-light">
                            <input type="checkbox" id="mines-toggle" class="mt-0.5 w-5 h-5 shrink-0 accent-main-accent">
                            <span>
                                <span class="russo text-main-dark text-sm uppercase">Mines</span>
                                <span class="block fira-sans text-xs text-main-dark/60 mt-0.5">Hidden mines are scattered across the sea. Fire on one and it backfires — a random one of <em>your own</em> ships takes a hit.</span>
                            </span>
                        </label>

                        {{-- Ships touching (off by default) --}}
                        <label class="flex items-start gap-3 border-4 border-main-dark p-3 cursor-pointer bg-main-light">
                            <input type="checkbox" id="touch-toggle" class="mt-0.5 w-5 h-5 shrink-0 accent-brand">
                            <span>
                                <span class="russo text-main-dark text-sm uppercase">Ships can touch</span>
                                <span class="block fira-sans text-xs text-main-dark/60 mt-0.5">Official rules let ships sit side by side. Leave this off to keep at least one square of open water around every ship (diagonals included). Ships can never overlap.</span>
                            </span>
                        </label>

                        {{-- Arena (random only) --}}
                        <label id="arena-row" class="hidden items-start gap-3 border-4 border-main-dark p-3 cursor-pointer bg-main-light">
                            <input type="checkbox" id="arena-toggle" class="mt-0.5 w-5 h-5 shrink-0 accent-pop" checked>
                            <span>
                                <span class="russo text-main-dark text-sm uppercase">Ranked (Arena)</span>
                                <span class="block fira-sans text-xs text-main-dark/60 mt-0.5">Count this game toward the weekly Arena leaderboard. Win and you climb — lose and you drop. Off = a casual game that can't hurt your rank.</span>
                            </span>
                        </label>

                        <button type="button" id="start-btn" class="self-start fira-sans font-black uppercase tracking-wider bg-main-accent text-white border-4 border-main-dark shadow-[5px_5px_0_0_#14323B] px-8 py-3 transition-all duration-100 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[7px_7px_0_0_#14323B] active:translate-x-1 active:translate-y-1 active:shadow-[0px_0px_0_0_#14323B]">Start Battle</button>
                    </div>
                </div>

                {{-- Side column: player info + arena leaderboard (Step 6 — filled in later) --}}
                <div class="flex flex-col gap-6">
                    <div class="bg-white border-4 border-main-dark shadow-[6px_6px_0_0_#14323B]">
                        <div class="flex items-center gap-2.5 bg-brand px-4 py-2.5">
                            <span class="w-3.5 h-3.5 rounded-full bg-main-accent shrink-0"></span>
                            <span class="w-3.5 h-3.5 rounded-full bg-pop shrink-0"></span>
                            <span class="w-3.5 h-3.5 rounded-full bg-white shrink-0"></span>
                            <span class="ml-2 russo uppercase tracking-widest text-white text-xs">Your Record</span>
                        </div>
                        <div class="p-5 grid grid-cols-2 gap-3">
                            <div class="border-2 border-main-dark px-3 py-2 text-center">
                                <div class="russo text-2xl text-main-dark leading-none">0</div>
                                <div class="fira-sans text-[10px] uppercase tracking-widest text-main-dark/60 mt-1">Battles</div>
                            </div>
                            <div class="border-2 border-main-dark px-3 py-2 text-center">
                                <div class="russo text-2xl text-brand leading-none">0</div>
                                <div class="fira-sans text-[10px] uppercase tracking-widest text-main-dark/60 mt-1">Wins</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border-4 border-main-dark shadow-[6px_6px_0_0_#14323B]">
                        <div class="flex items-center gap-2.5 bg-pop px-4 py-2.5">
                            <span class="w-3.5 h-3.5 rounded-full bg-main-accent shrink-0"></span>
                            <span class="w-3.5 h-3.5 rounded-full bg-white shrink-0"></span>
                            <span class="w-3.5 h-3.5 rounded-full bg-green-500 shrink-0"></span>
                            <span class="ml-2 russo uppercase tracking-widest text-main-dark text-xs">Arena — This Week</span>
                        </div>
                        <div class="p-5">
                            <div class="bg-main-light border-4 border-dashed border-main-dark/40 px-4 py-8 text-center">
                                <p class="russo text-main-dark/60">Arena opens soon</p>
                                <p class="fira-sans text-main-dark/60 text-sm mt-1">Ranked VS-Random battles will show here.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            // Fleet composition per ship count (segment lengths).
            const FLEETS = {3:[5,4,3], 4:[5,4,3,2], 5:[5,4,3,3,2], 6:[5,4,3,3,2,2], 7:[5,4,4,3,3,2,2]};

            let opponent = 'pc';
            const oppBtns = document.querySelectorAll('.opp-btn');
            const onlineNote = document.getElementById('online-note');
            const arenaRow = document.getElementById('arena-row');
            const startBtn = document.getElementById('start-btn');

            function paintOpponent() {
                oppBtns.forEach(b => {
                    const sel = b.dataset.opponent === opponent;
                    b.classList.toggle('bg-main-dark', sel);
                    b.classList.toggle('text-white', sel);
                    b.classList.toggle('shadow-[4px_4px_0_0_#14323B]', sel);
                    b.querySelectorAll('.russo, .fira-sans').forEach(t => t.classList.toggle('text-white', sel));
                });
                const soon = opponent === 'random';
                onlineNote.classList.toggle('hidden', !soon);
                document.getElementById('player-note').classList.toggle('hidden', opponent !== 'player');
                arenaRow.classList.toggle('hidden', opponent !== 'random');
                arenaRow.classList.toggle('flex', opponent === 'random');
                startBtn.textContent = soon ? 'Coming Soon' : (opponent === 'player' ? 'Create Match' : 'Start Battle');
                startBtn.classList.toggle('opacity-50', soon);
                startBtn.classList.toggle('cursor-not-allowed', soon);
            }
            oppBtns.forEach(b => b.addEventListener('click', () => { opponent = b.dataset.opponent; paintOpponent(); }));

            // Board size
            const sizeRange = document.getElementById('size-range');
            const sizeVal = document.getElementById('size-val');
            const sizeVal2 = document.getElementById('size-val2');
            sizeRange.addEventListener('input', () => { sizeVal.textContent = sizeVal2.textContent = sizeRange.value; });

            // Ship count + fleet preview
            const shipsRange = document.getElementById('ships-range');
            const shipsVal = document.getElementById('ships-val');
            const fleetPreview = document.getElementById('fleet-preview');
            function paintFleet() {
                const n = parseInt(shipsRange.value, 10);
                shipsVal.textContent = n;
                const fleet = FLEETS[n] || FLEETS[5];
                fleetPreview.innerHTML = '';
                fleet.forEach(len => {
                    const ship = document.createElement('div');
                    ship.className = 'flex';
                    for (let i = 0; i < len; i++) {
                        const cell = document.createElement('span');
                        cell.className = 'w-4 h-4 bg-main-dark border border-white/40';
                        ship.appendChild(cell);
                    }
                    fleetPreview.appendChild(ship);
                });
            }
            shipsRange.addEventListener('input', paintFleet);

            // Start
            startBtn.addEventListener('click', () => {
                if (opponent === 'random') return;
                if (opponent === 'player') {
                    // Friend match: create the lobby server-side (POST), then invite from there.
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/btl/match';
                    const fields = {
                        _token: '{{ csrf_token() }}',
                        size: sizeRange.value,
                        ships: shipsRange.value,
                        mines: document.getElementById('mines-toggle').checked ? 1 : 0,
                        touch: document.getElementById('touch-toggle').checked ? 1 : 0,
                    };
                    Object.entries(fields).forEach(([name, value]) => {
                        const input = document.createElement('input');
                        input.type = 'hidden'; input.name = name; input.value = value;
                        form.appendChild(input);
                    });
                    document.body.appendChild(form);
                    form.submit();
                    return;
                }
                const params = new URLSearchParams({
                    mode: 'pc',
                    size: sizeRange.value,
                    ships: shipsRange.value,
                    mines: document.getElementById('mines-toggle').checked ? 1 : 0,
                    touch: document.getElementById('touch-toggle').checked ? 1 : 0,
                    arena: 0,
                });
                window.location.href = '/btl/play?' + params.toString();
            });

            paintOpponent();
            paintFleet();
        })();
    </script>

@endsection
