<?php
session_start();

if (!isset($_SESSION['user_logged_in'])) {
    header("Location: applogin.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FIFA World Cup 2026 — Live</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <!-- HLS.js for m3u8 -->
    <script src="https://cdn.jsdelivr.net/npm/hls.js@0.14.17/dist/hls.min.js"></script>
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg: #0a0e1a;
            --card: rgba(255,255,255,0.04);
            --gold: #fbbd08;
            --text: #f0f0f0;
            --text2: #a0a8b8;
            --glow: rgba(251,189,8,0.25);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            background-image:
                radial-gradient(ellipse at 20% 50%, rgba(27,94,32,0.06) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(251,189,8,0.03) 0%, transparent 50%);
        }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: #c59b00; border-radius: 3px; }

        /* ===== TOP BAR ===== */
        .top-bar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.7rem 2rem;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.04);
            position: sticky; top: 0; z-index: 100;
        }

        .logo {
            font-family: 'Orbitron', monospace;
            font-size: 1rem; font-weight: 700;
            background: linear-gradient(135deg, #fbbd08, #ffd700);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .top-clock {
            display: flex; align-items: center; gap: 0.6rem;
            font-family: 'Orbitron', monospace;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--gold);
            letter-spacing: 2px;
        }

        .top-clock i { font-size: 0.9rem; color: var(--text2); }
        .top-clock .ampm {
            font-size: 0.7rem;
            color: var(--text2);
            font-family: 'Inter', sans-serif;
            font-weight: 600;
        }

        .live-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #ff4444;
            animation: pulse 1.5s infinite;
            display: inline-block;
        }

        /* ===== PLAYER SECTION ===== */
        .player-section {
            width: 100%;
            max-width: 1100px;
            margin: 1.5rem auto 0;
            padding: 0 1rem;
        }

        .player-frame {
            width: 100%;
            aspect-ratio: 16 / 9;
            background: #000;
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 0 50px rgba(251,189,8,0.08);
            border: 1px solid rgba(255,255,255,0.06);
        }

        .player-frame video {
            width: 100%; height: 100%;
            display: block;
            object-fit: contain;
            background: #000;
        }

        /* Overlay when nothing is playing */
        .overlay {
            position: absolute; inset: 0;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            background: linear-gradient(135deg, #0a0e1a 0%, #111 100%);
            color: var(--text2);
            gap: 0.8rem;
            z-index: 1;
            transition: opacity 0.4s;
        }

        .overlay.hidden { opacity: 0; pointer-events: none; }

        .overlay i { font-size: 2.5rem; color: var(--gold); opacity: 0.3; }
        .overlay p { font-size: 0.95rem; }
        .overlay small { font-size: 0.75rem; opacity: 0.5; }

        /* ===== CHANNEL BAR ===== */
        .channel-bar {
            display: flex; align-items: center; gap: 0.7rem;
            margin-top: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .ch-btn {
            display: flex; align-items: center; gap: 0.5rem;
            padding: 0.7rem 1.4rem;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.08);
            background: var(--card);
            color: var(--text2);
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem; font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }

        .ch-btn:hover {
            border-color: var(--glow);
            color: var(--text);
            transform: translateY(-2px);
        }

        .ch-btn.active {
            background: rgba(251,189,8,0.12);
            border-color: var(--gold);
            color: var(--gold);
            box-shadow: 0 0 20px rgba(251,189,8,0.1);
        }

        .ch-btn .num {
            font-family: 'Orbitron', monospace;
            font-size: 1rem;
        }

        .ch-btn .label { font-size: 0.75rem; opacity: 0.6; }

        .now-playing {
            text-align: center;
            margin-top: 0.6rem;
            font-size: 0.85rem;
            color: var(--text2);
        }

        .now-playing strong { color: var(--gold); }

        /* ===== SECTIONS ===== */
        section {
            padding: 3rem 1.5rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .sec-head {
            text-align: center;
            margin-bottom: 2rem;
        }

        .sec-tag {
            display: inline-block;
            padding: 0.25rem 1rem;
            border-radius: 50px;
            font-size: 0.7rem; font-weight: 600; letter-spacing: 2px;
            text-transform: uppercase;
            background: rgba(251,189,8,0.1);
            color: var(--gold);
            border: 1px solid var(--glow);
            margin-bottom: 0.6rem;
        }

        .sec-title {
            font-family: 'Orbitron', monospace;
            font-size: clamp(1.3rem, 3vw, 2rem);
            font-weight: 700;
        }

        .sec-title i { color: var(--gold); margin-right: 0.4rem; }

        /* ===== GROUPS ===== */
        .groups-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 1rem;
        }

        .g-card {
            background: var(--card);
            border-radius: 14px;
            padding: 1.2rem;
            border: 1px solid rgba(255,255,255,0.06);
            transition: all 0.3s;
        }

        .g-card:hover {
            transform: translateY(-3px);
            border-color: var(--glow);
            box-shadow: 0 0 25px rgba(251,189,8,0.08);
        }

        .g-card h3 {
            font-family: 'Orbitron', monospace;
            font-size: 0.95rem;
            color: var(--gold);
            margin-bottom: 0.6rem;
        }

        .t-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.4rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }

        .t-row:last-child { border-bottom: none; }

        .t-name {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .t-pts {
            font-weight: 700;
            color: var(--gold);
            font-size: 0.8rem;
        }

        /* ===== SCHEDULE ===== */
        .sched-box {
            background: var(--card);
            border-radius: 18px;
            border: 1px solid rgba(255,255,255,0.06);
            overflow: hidden;
        }

        .sched-tabs {
            display: flex;
            overflow-x: auto;
            padding: 0 0.6rem;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .sched-tab {
            padding: 0.7rem 1rem;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text2);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
            white-space: nowrap;
            background: none;
            border-top: none; border-left: none; border-right: none;
            font-family: 'Inter', sans-serif;
        }

        .sched-tab:hover { color: var(--text); }
        .sched-tab.active { color: var(--gold); border-bottom-color: var(--gold); }

        .sched-body { max-height: 450px; overflow-y: auto; }
        .sched-day { display: none; padding: 0.8rem; }
        .sched-day.active { display: block; }

        .m-item {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.7rem 0.3rem;
            border-bottom: 1px solid rgba(255,255,255,0.03);
        }

        .m-item:last-child { border-bottom: none; }

        .m-time {
            font-family: 'Orbitron', monospace;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--gold);
            min-width: 60px;
        }

        .m-teams { flex: 1; font-size: 0.9rem; font-weight: 500; }
        .m-venue { font-size: 0.7rem; color: var(--text2); min-width: 80px; text-align: right; }

        /* ===== BRACKET ===== */
        .bracket {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 0.8rem;
        }

        .b-round {
            background: var(--card);
            border-radius: 12px;
            padding: 0.9rem;
            border: 1px solid rgba(255,255,255,0.06);
            text-align: center;
        }

        .b-round h4 {
            font-family: 'Orbitron', monospace;
            font-size: 0.7rem;
            color: var(--gold);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 0.3rem;
        }

        .b-round .d { font-size: 0.75rem; color: var(--text2); }

        /* ===== FOOTER ===== */
        footer {
            text-align: center;
            padding: 1.5rem;
            color: var(--text2);
            font-size: 0.75rem;
            border-top: 1px solid rgba(255,255,255,0.04);
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        @media (max-width: 768px) {
            .top-bar { padding: 0.5rem 1rem; }
            .top-clock { font-size: 0.9rem; }
            .player-section { padding: 0 0.5rem; margin-top: 0.8rem; }
            .ch-btn { padding: 0.5rem 1rem; font-size: 0.8rem; }
            .m-venue { display: none; }
            .groups-grid { grid-template-columns: 1fr; }
            section { padding: 2rem 0.8rem; }
        }
    </style>
</head>
<body>

    <!-- ===== TOP BAR with 12H clock ===== -->
    <div class="top-bar">
        <span class="logo"><i class="fas fa-futbol"></i> WC 2026</span>
        <div class="top-clock">
            <span class="live-dot"></span>
            <span id="clockDisplay">12:00:00</span>
            <span class="ampm" id="ampmDisplay">AM</span>
            <i class="fas fa-clock"></i>
        </div>
    </div>

    <!-- ===== PLAYER ===== -->
    <div class="player-section">
        <div class="player-frame">
            <video id="mainPlayer" controls autoplay muted playsinline></video>
            <div class="overlay" id="overlay">
                <i class="fas fa-futbol"></i>
                <p>Select a server to watch</p>
                <small>Select Server 1, 2, or 3 below</small>
            </div>
        </div>

        <div class="channel-bar">
            <button class="ch-btn" onclick="switchCh(0)">
                <span class="num">Server</span>
                <span class="label">1</span>
            </button>
            <button class="ch-btn" onclick="switchCh(1)">
                <span class="num">Server</span>
                <span class="label">2</span>
            </button>
            <button class="ch-btn" onclick="switchCh(2)">
                <span class="num">Server</span>
                <span class="label">3</span>
            </button>
        </div>

        <div class="now-playing" id="nowPlaying">
            <span>🎯 <strong id="nowText">Click a channel above</strong></span>
        </div>
    </div>

    <!-- ===== GROUPS ===== -->
    <section>
        <div class="sec-head">
            <div class="sec-tag"><i class="fas fa-layer-group"></i> Groups</div>
            <h2 class="sec-title"><i class="fas fa-trophy"></i> Group Standings</h2>
        </div>
        <div class="groups-grid" id="groupsGrid"></div>
    </section>

    <!-- ===== SCHEDULE ===== -->
    <section>
        <div class="sec-head">
            <div class="sec-tag"><i class="fas fa-calendar-alt"></i> Fixtures</div>
            <h2 class="sec-title"><i class="fas fa-clock"></i> Full Schedule (BD Time)</h2>
        </div>
        <div class="sched-box">
            <div class="sched-tabs" id="schedTabs"></div>
            <div class="sched-body" id="schedBody"></div>
        </div>
    </section>

    <!-- ===== KNOCKOUT ===== -->
    <section>
        <div class="sec-head">
            <div class="sec-tag"><i class="fas fa-tree"></i> Knockout</div>
            <h2 class="sec-title"><i class="fas fa-route"></i> Road to the Final</h2>
        </div>
        <div class="bracket">
            <div class="b-round"><h4>R32</h4><div class="d">Jun 29 – Jul 4</div></div>
            <div class="b-round"><h4>R16</h4><div class="d">Jul 5 – Jul 8</div></div>
            <div class="b-round"><h4>QF</h4><div class="d">Jul 10 – Jul 12</div></div>
            <div class="b-round"><h4>SF</h4><div class="d">Jul 15 – Jul 16</div></div>
            <div class="b-round" style="border-color:var(--glow);">
                <h4 style="color:var(--gold)">🏆 Final</h4>
                <div class="d" style="color:var(--gold);font-weight:700;">Jul 20 • 1:00 AM</div>
                <div style="font-size:0.65rem;color:var(--text2);">MetLife Stadium</div>
            </div>
        </div>
    </section>

    <footer>
        <p>FIFA World Cup 2026 &bull;</p>
        <p style="margin-top:0.2rem;font-size:0.65rem;">Developed by Safwan Sabit</p>
    </footer>

    <script>
        // ================================================================
        //  STREAM CONFIG
        //  Channel 1 = m3u8 (HLS), Channel 2 & 3 = .ts
        // ================================================================
        const CHANNELS = [
            { name: 'T Sports', src: 'https://playztv-apps.pages.dev/tsports/index.m3u8', type: 'hls' },
            { name: 'Star News', src: 'https://owrcovcrpy.gpcdn.net/bpk-tv/1710/output/index.m3u8', type: 'hls' },
            { name: 'BTV', src: 'https://owrcovcrpy.gpcdn.net/bpk-tv/1709/output/index.m3u8', type: 'hls' }
        ];

        let hls = null;
        let mediaSource = null;
        let sourceBuffer = null;

        // ================================================================
        //  SWITCH CHANNEL
        // ================================================================
        function switchCh(index) {
            const ch = CHANNELS[index];
            const video = document.getElementById('mainPlayer');
            const overlay = document.getElementById('overlay');
            const nowText = document.getElementById('nowText');

            // Destroy previous HLS
            if (hls) { hls.destroy(); hls = null; }

            // Close previous MediaSource
            if (mediaSource) {
                if (mediaSource.readyState === 'open') mediaSource.endOfStream();
                mediaSource = null;
                sourceBuffer = null;
            }

            // Reset video
            video.pause();
            video.removeAttribute('src');
            while (video.firstChild) video.removeChild(video.firstChild);

            // Hide overlay
            overlay.classList.add('hidden');

            // Update button states
            document.querySelectorAll('.ch-btn').forEach((b, i) => b.classList.toggle('active', i === index));

            // Update text
            nowText.textContent = `📺 ${ch.name}`;

            // Load based on type
            if (ch.type === 'hls') {
                // HLS — use HLS.js
                if (Hls.isSupported()) {
                    hls = new Hls();
                    hls.loadSource(ch.src);
                    hls.attachMedia(video);
                    hls.on(Hls.Events.MANIFEST_PARSED, () => {
                        video.play().catch(() => {});
                    });
                    hls.on(Hls.Events.ERROR, (e, data) => {
                        if (data.fatal) {
                            nowText.textContent = '⚠️ HLS stream error — try another channel';
                        }
                    });
                } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                    const s = document.createElement('source');
                    s.src = ch.src;
                    s.type = 'application/x-mpegURL';
                    video.appendChild(s);
                    video.load();
                    video.play().catch(() => {});
                } else {
                    nowText.textContent = '⚠️ HLS not supported in this browser';
                    overlay.classList.remove('hidden');
                }
            } else {
                // .TS file — use fetch + MediaSource (MSE) to pipe raw TS data
                loadTSviaMSE(video, ch.src, index);
            }
        }

        // ================================================================
        //  LOAD .TS via MediaSource Extensions (MSE)
        //  This makes raw .ts files playable in any modern browser
        // ================================================================
        function loadTSviaMSE(video, url, index) {
            const nowText = document.getElementById('nowText');

            // Check MSE support
            if (!window.MediaSource) {
                nowText.textContent = '⚠️ MSE not supported — try Channel 1 (M3U8)';
                return;
            }

            mediaSource = new MediaSource();
            video.src = URL.createObjectURL(mediaSource);

            mediaSource.addEventListener('sourceopen', () => {
                // Use 'video/mp2t' as the MIME type — tells the browser it's MPEG-TS
                const mime = 'video/mp2t; codecs="avc1.42E01E,mp4a.40.2"';
                if (!MediaSource.isTypeSupported(mime)) {
                    // Fallback: just try loading directly (some browsers handle it)
                    nowText.textContent = '⚠️ TS format not supported natively, trying direct load...';
                    const s = document.createElement('source');
                    s.src = url;
                    s.type = 'video/mp2t';
                    video.appendChild(s);
                    video.load();
                    video.play().catch(() => {});
                    return;
                }

                sourceBuffer = mediaSource.addSourceBuffer(mime);

                // Fetch the .ts file and feed it to the source buffer
                fetch(url)
                    .then(res => {
                        if (!res.ok) throw new Error(`HTTP ${res.status}`);
                        return res.arrayBuffer();
                    })
                    .then(data => {
                        // Append the raw TS data
                        sourceBuffer.addEventListener('updateend', () => {
                            if (mediaSource.readyState === 'open') {
                                mediaSource.endOfStream();
                            }
                            video.play().catch(() => {});
                        }, { once: true });

                        sourceBuffer.appendBuffer(data);
                    })
                    .catch(err => {
                        nowText.textContent = `⚠️ TS load error: ${err.message}`;
                        document.getElementById('overlay').classList.remove('hidden');
                    });
            });

            mediaSource.addEventListener('sourceended', () => {
                // Stream ended
            });
        }

        // ================================================================
        //  12-HOUR CLOCK (Bangladesh Time)
        // ================================================================
        function updateClock() {
            const now = new Date();
            const bd = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Dhaka' }));
            let h = bd.getHours();
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12; // convert to 12h
            const m = String(bd.getMinutes()).padStart(2, '0');
            const s = String(bd.getSeconds()).padStart(2, '0');
            document.getElementById('clockDisplay').textContent = `${h}:${m}:${s}`;
            document.getElementById('ampmDisplay').textContent = ampm;
        }

        // ================================================================
        //  DATA & RENDER
        // ================================================================
        const GROUPS = [
            { name: 'A', teams: ['Mexico','South Africa','South Korea','Czechia'] },
            { name: 'B', teams: ['Canada','Bosnia & Herzegovina','Qatar','Switzerland'] },
            { name: 'C', teams: ['Brazil','Morocco','Haiti','Scotland'] },
            { name: 'D', teams: ['USA','Paraguay','Australia','Türkiye'] },
            { name: 'E', teams: ['Germany','Curaçao','Ivory Coast','Ecuador'] },
            { name: 'F', teams: ['Netherlands','Japan','Sweden','Tunisia'] },
            { name: 'G', teams: ['Belgium','Egypt','Iran','New Zealand'] },
            { name: 'H', teams: ['Spain','Cape Verde','Saudi Arabia','Uruguay'] },
            { name: 'I', teams: ['France','Senegal','Norway','Iraq'] },
            { name: 'J', teams: ['Argentina','Algeria','Austria','Jordan'] },
            { name: 'K', teams: ['Portugal','Colombia','Uzbekistan','DR Congo'] },
            { name: 'L', teams: ['England','Croatia','Ghana','Panama'] }
        ];

        const SCHEDULE = [
            { date: "Jun 12", matches: [
                { time: "1:00 AM", teams: "Mexico vs South Africa", venue: "Mexico City" },
                { time: "8:00 AM", teams: "South Korea vs Czechia", venue: "Guadalajara" }
            ]},
            { date: "Jun 13", matches: [
                { time: "1:00 AM", teams: "Canada vs Bosnia & Herz.", venue: "Toronto" },
                { time: "7:00 AM", teams: "USA vs Paraguay", venue: "Los Angeles" },
                { time: "10:00 AM", teams: "Australia vs Türkiye", venue: "Vancouver" }
            ]},
            { date: "Jun 14", matches: [
                { time: "1:00 AM", teams: "Qatar vs Switzerland", venue: "San Francisco" },
                { time: "4:00 AM", teams: "Brazil vs Morocco", venue: "New York/NJ" },
                { time: "7:00 AM", teams: "Haiti vs Scotland", venue: "Boston" },
                { time: "11:00 PM", teams: "Germany vs Curaçao", venue: "Houston" }
            ]},
            { date: "Jun 15", matches: [
                { time: "2:00 AM", teams: "Netherlands vs Japan", venue: "Dallas" },
                { time: "5:00 AM", teams: "Ivory Coast vs Ecuador", venue: "Philadelphia" },
                { time: "7:00 AM", teams: "Sweden vs Tunisia", venue: "Monterrey" },
                { time: "10:00 PM", teams: "Spain vs Cape Verde", venue: "Atlanta" }
            ]},
            { date: "Jun 16", matches: [
                { time: "1:00 AM", teams: "Belgium vs Egypt", venue: "Seattle" },
                { time: "4:00 AM", teams: "Saudi Arabia vs Uruguay", venue: "Miami" },
                { time: "7:00 AM", teams: "Iran vs New Zealand", venue: "Los Angeles" },
                { time: "10:00 AM", teams: "Austria vs Jordan", venue: "San Francisco" }
            ]},
            { date: "Jun 17", matches: [
                { time: "1:00 AM", teams: "France vs Senegal", venue: "New York/NJ" },
                { time: "4:00 AM", teams: "Iraq vs Norway", venue: "Boston" },
                { time: "7:00 AM", teams: "Argentina vs Algeria", venue: "Kansas City" },
                { time: "11:00 PM", teams: "Portugal vs DR Congo", venue: "Houston" }
            ]},
            { date: "Jun 18", matches: [
                { time: "2:00 AM", teams: "England vs Croatia", venue: "Dallas" },
                { time: "5:00 AM", teams: "Ghana vs Panama", venue: "Toronto" },
                { time: "8:00 AM", teams: "Uzbekistan vs Colombia", venue: "Mexico City" },
                { time: "10:00 PM", teams: "Czechia vs South Africa", venue: "Atlanta" }
            ]},
            { date: "Jun 19", matches: [
                { time: "1:00 AM", teams: "Switzerland vs Bosnia", venue: "Los Angeles" },
                { time: "4:00 AM", teams: "Canada vs Qatar", venue: "Vancouver" },
                { time: "7:00 AM", teams: "Mexico vs South Korea", venue: "Guadalajara" },
                { time: "10:00 AM", teams: "Türkiye vs Paraguay", venue: "San Francisco" }
            ]},
            { date: "Jun 20", matches: [
                { time: "1:00 AM", teams: "USA vs Australia", venue: "Seattle" },
                { time: "4:00 AM", teams: "Scotland vs Morocco", venue: "Boston" },
                { time: "7:00 AM", teams: "Brazil vs Haiti", venue: "Philadelphia" },
                { time: "10:00 AM", teams: "Tunisia vs Japan", venue: "Monterrey" },
                { time: "11:00 PM", teams: "Netherlands vs Sweden", venue: "Houston" }
            ]},
            { date: "Jun 21", matches: [
                { time: "2:00 AM", teams: "Germany vs Ivory Coast", venue: "Toronto" },
                { time: "6:00 AM", teams: "Ecuador vs Curaçao", venue: "Kansas City" },
                { time: "10:00 PM", teams: "Spain vs Saudi Arabia", venue: "Atlanta" }
            ]},
            { date: "Jun 22", matches: [
                { time: "1:00 AM", teams: "Belgium vs Iran", venue: "Los Angeles" },
                { time: "4:00 AM", teams: "Uruguay vs Cape Verde", venue: "Miami" },
                { time: "7:00 AM", teams: "New Zealand vs Egypt", venue: "Vancouver" },
                { time: "11:00 PM", teams: "Argentina vs Austria", venue: "Dallas" }
            ]},
            { date: "Jun 23", matches: [
                { time: "3:00 AM", teams: "France vs Iraq", venue: "Philadelphia" },
                { time: "6:00 AM", teams: "Norway vs Senegal", venue: "New York/NJ" },
                { time: "9:00 AM", teams: "Jordan vs Algeria", venue: "San Francisco" },
                { time: "11:00 PM", teams: "Portugal vs Uzbekistan", venue: "Houston" }
            ]},
            { date: "Jun 24", matches: [
                { time: "2:00 AM", teams: "England vs Ghana", venue: "Boston" },
                { time: "5:00 AM", teams: "Panama vs Croatia", venue: "Toronto" },
                { time: "8:00 AM", teams: "Colombia vs DR Congo", venue: "Guadalajara" }
            ]},
            { date: "Jun 25", matches: [
                { time: "1:00 AM", teams: "Switzerland vs Canada", venue: "Vancouver" },
                { time: "1:00 AM", teams: "Bosnia vs Qatar", venue: "Seattle" },
                { time: "4:00 AM", teams: "Morocco vs Haiti", venue: "Atlanta" },
                { time: "4:00 AM", teams: "Scotland vs Brazil", venue: "Miami" },
                { time: "7:00 AM", teams: "South Africa vs South Korea", venue: "Monterrey" },
                { time: "7:00 AM", teams: "Czechia vs Mexico", venue: "Mexico City" }
            ]},
            { date: "Jun 26", matches: [
                { time: "2:00 AM", teams: "Curaçao vs Ivory Coast", venue: "Philadelphia" },
                { time: "2:00 AM", teams: "Ecuador vs Germany", venue: "New York/NJ" },
                { time: "5:00 AM", teams: "Tunisia vs Netherlands", venue: "Kansas City" },
                { time: "5:00 AM", teams: "Japan vs Sweden", venue: "Dallas" },
                { time: "8:00 AM", teams: "Türkiye vs USA", venue: "Los Angeles" },
                { time: "8:00 AM", teams: "Paraguay vs Australia", venue: "San Francisco" }
            ]},
            { date: "Jun 27", matches: [
                { time: "1:00 AM", teams: "Norway vs France", venue: "Boston" },
                { time: "1:00 AM", teams: "Senegal vs Iraq", venue: "Toronto" },
                { time: "6:00 AM", teams: "Cape Verde vs Saudi Arabia", venue: "Houston" },
                { time: "6:00 AM", teams: "Uruguay vs Spain", venue: "Guadalajara" },
                { time: "9:00 AM", teams: "New Zealand vs Belgium", venue: "Vancouver" },
                { time: "9:00 AM", teams: "Egypt vs Iran", venue: "Seattle" }
            ]},
            { date: "Jun 28", matches: [
                { time: "3:00 AM", teams: "Panama vs England", venue: "New York/NJ" },
                { time: "3:00 AM", teams: "Croatia vs Ghana", venue: "Philadelphia" },
                { time: "5:30 AM", teams: "Colombia vs Portugal", venue: "Miami" },
                { time: "5:30 AM", teams: "DR Congo vs Uzbekistan", venue: "Atlanta" },
                { time: "8:00 AM", teams: "Algeria vs Austria", venue: "Kansas City" },
                { time: "8:00 AM", teams: "Jordan vs Argentina", venue: "Dallas" }
            ]},
            { date: "KO Stage", matches: [
                { time: "Jun 29+", teams: "Round of 32", venue: "Various" },
                { time: "Jul 5-8", teams: "Round of 16", venue: "Various" },
                { time: "Jul 10-12", teams: "Quarterfinals", venue: "Various" },
                { time: "Jul 15-16", teams: "Semifinals", venue: "Dallas/Atlanta" },
                { time: "Jul 20 1AM", teams: "🏆 FINAL", venue: "MetLife Stadium, NJ" }
            ]}
        ];

        function flag(team) {
            const m = {
                'Mexico':'🇲🇽','South Africa':'🇿🇦','South Korea':'🇰🇷','Czechia':'🇨🇿',
                'Canada':'🇨🇦','Bosnia & Herzegovina':'🇧🇦','Bosnia & Herz.':'🇧🇦','Qatar':'🇶🇦','Switzerland':'🇨🇭',
                'Brazil':'🇧🇷','Morocco':'🇲🇦','Haiti':'🇭🇹','Scotland':'🏴󠁧󠁢󠁳󠁣󠁴󠁿',
                'USA':'🇺🇸','Paraguay':'🇵🇾','Australia':'🇦🇺','Türkiye':'🇹🇷',
                'Germany':'🇩🇪','Curaçao':'🇨🇼','Ivory Coast':'🇨🇮','Ecuador':'🇪🇨',
                'Netherlands':'🇳🇱','Japan':'🇯🇵','Sweden':'🇸🇪','Tunisia':'🇹🇳',
                'Belgium':'🇧🇪','Egypt':'🇪🇬','Iran':'🇮🇷','New Zealand':'🇳🇿',
                'Spain':'🇪🇸','Cape Verde':'🇨🇻','Saudi Arabia':'🇸🇦','Uruguay':'🇺🇾',
                'France':'🇫🇷','Senegal':'🇸🇳','Norway':'🇳🇴','Iraq':'🇮🇶',
                'Argentina':'🇦🇷','Algeria':'🇩🇿','Austria':'🇦🇹','Jordan':'🇯🇴',
                'Portugal':'🇵🇹','Colombia':'🇨🇴','Uzbekistan':'🇺🇿','DR Congo':'🇨🇩',
                'England':'🏴󠁧󠁢󠁥󠁮󠁧󠁿','Croatia':'🇭🇷','Ghana':'🇬🇭','Panama':'🇵🇦'
            };
            return m[team] || '🏳️';
        }

        function renderGroups() {
            document.getElementById('groupsGrid').innerHTML = GROUPS.map(g => `
                <div class="g-card">
                    <h3><i class="fas fa-trophy"></i> Group ${g.name}</h3>
                    ${g.teams.map(t => `
                        <div class="t-row">
                            <span class="t-name"><span style="font-size:1.1rem;">${flag(t)}</span> ${t}</span>
                            <span class="t-pts">0</span>
                        </div>
                    `).join('')}
                </div>
            `).join('');
        }

        function renderSchedule() {
            const tabs = document.getElementById('schedTabs');
            const body = document.getElementById('schedBody');

            tabs.innerHTML = SCHEDULE.map((d, i) => `
                <button class="sched-tab ${i === 0 ? 'active' : ''}" onclick="switchTab(${i})">${d.date}</button>
            `).join('');

            body.innerHTML = SCHEDULE.map((d, i) => `
                <div class="sched-day ${i === 0 ? 'active' : ''}">
                    ${d.matches.map(m => `
                        <div class="m-item">
                            <span class="m-time">${m.time}</span>
                            <span class="m-teams">${m.teams}</span>
                            <span class="m-venue">${m.venue}</span>
                        </div>
                    `).join('')}
                </div>
            `).join('');
        }

        function switchTab(i) {
            document.querySelectorAll('.sched-tab').forEach((t, idx) => t.classList.toggle('active', idx === i));
            document.querySelectorAll('.sched-day').forEach((d, idx) => d.classList.toggle('active', idx === i));
        }

        // ================================================================
        //  INIT
        // ================================================================
        renderGroups();
        renderSchedule();
        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>
</html>
