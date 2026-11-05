{{-- Board, shot-feedback and placement styles shared by VS PC (play) and VS Player (match). --}}
    <style>
        .bship-board { display: grid; gap: 3px; width: 100%; max-width: 440px; }
        .bship-cell { aspect-ratio: 1 / 1; border: 2px solid #14323B; display: flex; align-items: center; justify-content: center; position: relative; }
        .bship-cell .mark { width: 42%; height: 42%; }
        .cell-water  { background: #1E93AB; }
        .cell-fire   { background: #1E93AB; cursor: crosshair; }
        .cell-fire:hover { background: #157687; }
        .cell-miss   { background: #DCDCDC; }
        .cell-miss .mark { border-radius: 9999px; background: #14323B33; }
        .cell-ship   { background: #14323B; }
        .cell-hit    { background: #E62727; }
        .cell-hit .mark { background: #fff; transform: rotate(45deg); }
        .cell-sunk   { background: #7f1414; }
        .cell-sunk .mark { background: #fff; clip-path: polygon(20% 0,0 20%,30% 50%,0 80%,20% 100%,50% 70%,80% 100%,100% 80%,70% 50%,100% 20%,80% 0,50% 30%); }
        .cell-mine   { background: #FFB900; }
        .cell-mine .mark { background: #14323B; clip-path: polygon(50% 0,61% 35%,98% 35%,68% 57%,79% 91%,50% 70%,21% 91%,32% 57%,2% 35%,39% 35%); }
        /* Shot feedback: impact on the newest shot, stamped callout, red flash when you're hit. */
        .cell-new.cell-hit, .cell-new.cell-sunk, .cell-new.cell-mine { animation: bship-impact 0.5s ease-out; }
        .cell-new.cell-miss { animation: bship-splash 0.6s ease-out; }
        @keyframes bship-impact { 0% { transform: scale(1.5); filter: brightness(2); } 60% { transform: scale(0.9); } 100% { transform: scale(1); filter: none; } }
        @keyframes bship-splash { 0% { box-shadow: 0 0 0 0 rgba(255,255,255,.9); } 100% { box-shadow: 0 0 0 14px rgba(255,255,255,0); } }
        .shot-callout { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; opacity: 0; }
        .shot-callout span { font-family: 'Russo One', ui-sans-serif, sans-serif; text-transform: uppercase; letter-spacing: .08em; font-size: clamp(1.75rem, 6vw, 3.25rem); padding: .2em .6em; border: 4px solid #14323B; box-shadow: 6px 6px 0 0 #14323B; }
        .shot-callout.is-on { animation: bship-callout 1.1s ease-out both; }
        @keyframes bship-callout { 0% { opacity: 0; transform: scale(2) rotate(-8deg); } 15% { opacity: 1; transform: scale(1) rotate(-4deg); } 75% { opacity: 1; transform: scale(1) rotate(-4deg); } 100% { opacity: 0; transform: scale(.95) rotate(-4deg); } }
        #player-window.is-hit { animation: bship-shake 0.5s ease-in-out; }
        #player-window.is-hit::after { content: ''; position: absolute; inset: 0; background: #E62727; pointer-events: none; animation: bship-flash 0.7s ease-out both; }
        @keyframes bship-flash { 0% { opacity: .65; } 100% { opacity: 0; } }
        @keyframes bship-shake { 0%, 100% { transform: translateX(0); } 20% { transform: translateX(-8px); } 40% { transform: translateX(7px); } 60% { transform: translateX(-5px); } 80% { transform: translateX(3px); } }
        @media (prefers-reduced-motion: reduce) {
            .cell-new, #player-window.is-hit { animation: none !important; }
            .shot-callout.is-on { animation-duration: 0.01s; animation-delay: 0s; opacity: 1; }
        }
        .cell-preview-ok  { outline: 3px solid #22c55e; outline-offset: -3px; }
        .cell-preview-bad { outline: 3px solid #E62727; outline-offset: -3px; }
    </style>
