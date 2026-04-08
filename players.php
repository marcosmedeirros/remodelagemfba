<?php
require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/helpers.php';
requireAuth();

$user = getUserSession();
$pdo = db();

$stmtTeam = $pdo->prepare('SELECT t.* FROM teams t WHERE t.user_id = ? LIMIT 1');
$stmtTeam->execute([$user['id']]);
$team = $stmtTeam->fetch(PDO::FETCH_ASSOC);

$isAdmin = ($user['user_type'] ?? 'jogador') === 'admin';
$league  = strtoupper($team['league'] ?? $user['league'] ?? 'ELITE');
$whatsappDefaultMessage = rawurlencode('Olá! Podemos conversar sobre nossas franquias na FBA?');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#fc0025">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="FBA Manager">
    <?php include __DIR__ . '/includes/head-pwa.php'; ?>
    <title>Jogadores - FBA Manager</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/styles.css">

    <style>
        /* ── Tokens ──────────────────────────────── */
        :root {
            --red:        #fc0025;
            --red-2:      #ff2a44;
            --red-soft:   rgba(252,0,37,.10);
            --red-glow:   rgba(252,0,37,.18);
            --bg:         #07070a;
            --panel:      #101013;
            --panel-2:    #16161a;
            --panel-3:    #1c1c21;
            --border:     rgba(255,255,255,.06);
            --border-md:  rgba(255,255,255,.10);
            --border-red: rgba(252,0,37,.22);
            --text:       #f0f0f3;
            --text-2:     #868690;
            --text-3:     #48484f;
            --green:      #22c55e;
            --amber:      #f59e0b;
            --blue:       #3b82f6;
            --sidebar-w:  260px;
            --font:       'Poppins', sans-serif;
            --radius:     14px;
            --radius-sm:  10px;
            --ease:       cubic-bezier(.2,.8,.2,1);
            --t:          200ms;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body { font-family: var(--font); background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; }
        a { color: inherit; text-decoration: none; }
        .app { display: flex; min-height: 100vh; }

        /* ── Sidebar ─────────────────────────────── */
        .sidebar { position: fixed; top: 0; left: 0; width: var(--sidebar-w); height: 100vh; background: var(--panel); border-right: 1px solid var(--border); display: flex; flex-direction: column; z-index: 300; overflow-y: auto; scrollbar-width: none; transition: transform var(--t) var(--ease); }
        .sidebar::-webkit-scrollbar { display: none; }
        .sb-brand { padding: 22px 18px 18px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
        .sb-logo { width: 34px; height: 34px; border-radius: 9px; background: var(--red); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; flex-shrink: 0; }
        .sb-brand-text { font-size: 15px; font-weight: 700; line-height: 1.1; }
        .sb-brand-text span { display: block; font-size: 11px; font-weight: 400; color: var(--text-2); }
        .sb-team { margin: 14px 14px 0; background: var(--panel-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px; display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .sb-team img { width: 40px; height: 40px; border-radius: 9px; object-fit: cover; border: 1px solid var(--border-md); flex-shrink: 0; }
        .sb-team-name { font-size: 13px; font-weight: 600; color: var(--text); line-height: 1.2; }
        .sb-team-league { font-size: 11px; color: var(--red); font-weight: 600; }
        .sb-nav { flex: 1; padding: 12px 10px 8px; }
        .sb-section { font-size: 10px; font-weight: 600; letter-spacing: 1.2px; text-transform: uppercase; color: var(--text-3); padding: 12px 10px 5px; }
        .sb-nav a { display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: var(--radius-sm); color: var(--text-2); font-size: 13px; font-weight: 500; margin-bottom: 2px; transition: all var(--t) var(--ease); }
        .sb-nav a i { font-size: 15px; width: 18px; text-align: center; flex-shrink: 0; }
        .sb-nav a:hover { background: var(--panel-2); color: var(--text); }
        .sb-nav a.active { background: var(--red-soft); color: var(--red); font-weight: 600; }
        .sb-nav a.active i { color: var(--red); }
        .sb-footer { padding: 12px 14px; border-top: 1px solid var(--border); display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .sb-avatar { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-md); flex-shrink: 0; }
        .sb-username { font-size: 12px; font-weight: 500; color: var(--text); flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sb-logout { width: 26px; height: 26px; border-radius: 7px; background: transparent; border: 1px solid var(--border); color: var(--text-2); display: flex; align-items: center; justify-content: center; font-size: 12px; cursor: pointer; transition: all var(--t) var(--ease); flex-shrink: 0; }
        .sb-logout:hover { background: var(--red-soft); border-color: var(--red); color: var(--red); }

        /* ── Topbar mobile ───────────────────────── */
        .topbar { display: none; position: fixed; top: 0; left: 0; right: 0; height: 54px; background: var(--panel); border-bottom: 1px solid var(--border); align-items: center; padding: 0 16px; gap: 12px; z-index: 199; }
        .topbar-title { font-weight: 700; font-size: 15px; flex: 1; }
        .topbar-title em { color: var(--red); font-style: normal; }
        .menu-btn { width: 34px; height: 34px; border-radius: 9px; background: var(--panel-2); border: 1px solid var(--border); color: var(--text); display: flex; align-items: center; justify-content: center; font-size: 17px; cursor: pointer; }
        .sb-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.65); backdrop-filter: blur(4px); z-index: 199; }
        .sb-overlay.show { display: block; }

        /* ── Main ────────────────────────────────── */
        .main { margin-left: var(--sidebar-w); min-height: 100vh; width: calc(100% - var(--sidebar-w)); display: flex; flex-direction: column; }
        .dash-hero { padding: 32px 32px 0; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .dash-eyebrow { font-size: 11px; font-weight: 600; letter-spacing: 1.4px; text-transform: uppercase; color: var(--red); margin-bottom: 4px; }
        .dash-title { font-size: 26px; font-weight: 800; line-height: 1.1; }
        .dash-sub { font-size: 13px; color: var(--text-2); margin-top: 3px; }
        .content { padding: 20px 32px 40px; flex: 1; }

        /* ── Panels ──────────────────────────────── */
        .panel { background: var(--panel); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 14px; }
        .panel-head { padding: 14px 18px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; background: var(--panel-2); }
        .panel-title { font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .panel-title i { color: var(--red); }
        .panel-body { padding: 18px; }

        /* ── Buttons ─────────────────────────────── */
        .btn-r { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: var(--radius-sm); font-family: var(--font); font-size: 12px; font-weight: 600; cursor: pointer; border: 1px solid transparent; transition: all var(--t) var(--ease); white-space: nowrap; }
        .btn-r.primary { background: var(--red); color: #fff; border-color: var(--red); }
        .btn-r.primary:hover { filter: brightness(1.1); color: #fff; }
        .btn-r.ghost  { background: transparent; color: var(--text-2); border-color: var(--border-md); }
        .btn-r.ghost:hover { background: var(--panel-2); color: var(--text); }
        .btn-r.green  { background: rgba(34,197,94,.12); color: var(--green); border-color: rgba(34,197,94,.25); }
        .btn-r.green:hover { background: var(--green); color: #fff; }
        .btn-r.amber  { background: rgba(245,158,11,.12); color: var(--amber); border-color: rgba(245,158,11,.25); }
        .btn-r.amber:hover { background: var(--amber); color: #000; }
        .btn-r.blue   { background: rgba(59,130,246,.12); color: var(--blue); border-color: rgba(59,130,246,.25); }
        .btn-r.blue:hover { background: var(--blue); color: #fff; }
        .btn-r.sm { padding: 5px 10px; font-size: 11px; }

        /* ── Tags ────────────────────────────────── */
        .tag { display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; letter-spacing: .3px; }
        .tag.green  { background: rgba(34,197,94,.12);  color: var(--green); border: 1px solid rgba(34,197,94,.2); }
        .tag.blue   { background: rgba(59,130,246,.12); color: var(--blue);  border: 1px solid rgba(59,130,246,.2); }
        .tag.amber  { background: rgba(245,158,11,.12); color: var(--amber); border: 1px solid rgba(245,158,11,.2); }
        .tag.gray   { background: var(--panel-3);       color: var(--text-2); border: 1px solid var(--border); }
        .tag.red    { background: var(--red-soft);      color: var(--red);   border: 1px solid var(--border-red); }

        /* ── Filter bar ──────────────────────────── */
        .filter-grid { display: grid; grid-template-columns: 1fr 140px 100px 100px 100px 100px 1fr auto; gap: 10px; align-items: end; }
        .f-label { font-size: 10px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: var(--text-2); display: block; margin-bottom: 5px; }
        .f-input, .f-select { width: 100%; background: var(--panel-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 9px 10px; color: var(--text); font-family: var(--font); font-size: 13px; outline: none; transition: border-color var(--t) var(--ease); }
        .f-input:focus, .f-select:focus { border-color: var(--red); }
        .f-input::placeholder { color: var(--text-3); }
        .f-select option { background: var(--panel-2); }
        .f-range { display: flex; gap: 6px; align-items: center; }
        .f-range .f-input { flex: 1; min-width: 0; }
        .f-range-sep { color: var(--text-3); font-size: 11px; flex-shrink: 0; }

        /* ── Results count badge ─────────────────── */
        .results-strip { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 8px; }
        .results-count { font-size: 12px; color: var(--text-2); }
        .results-count strong { color: var(--text); }

        /* ── Player table ────────────────────────── */
        .player-table { width: 100%; border-collapse: collapse; }
        .player-table th { font-size: 10px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; color: var(--text-3); padding: 10px 14px; border-bottom: 1px solid var(--border); text-align: left; cursor: pointer; user-select: none; white-space: nowrap; }
        .player-table th:hover { color: var(--text-2); }
        .player-table td { padding: 11px 14px; border-bottom: 1px solid var(--border); font-size: 13px; vertical-align: middle; }
        .player-table tr:last-child td { border-bottom: none; }
        .player-table tbody tr { transition: background var(--t) var(--ease); }
        .player-table tbody tr:hover { background: var(--panel-2); }

        /* Player cell */
        .p-cell { display: flex; align-items: center; gap: 10px; }
        .p-avatar { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-red); background: var(--panel-3); flex-shrink: 0; }
        .p-name { font-size: 13px; font-weight: 600; color: var(--text); }
        .p-team { font-size: 11px; color: var(--text-2); }
        .p-pos { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 22px; border-radius: 5px; background: var(--red-soft); color: var(--red); font-size: 10px; font-weight: 800; letter-spacing: .3px; text-transform: uppercase; }

        /* OVR badge */
        .ovr-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 24px; border-radius: 6px; font-size: 12px; font-weight: 800; padding: 0 6px; }
        .ovr-elite  { background: rgba(34,197,94,.15);  color: var(--green); border: 1px solid rgba(34,197,94,.25); }
        .ovr-high   { background: rgba(59,130,246,.15); color: var(--blue);  border: 1px solid rgba(59,130,246,.25); }
        .ovr-mid    { background: rgba(245,158,11,.15); color: var(--amber); border: 1px solid rgba(245,158,11,.25); }
        .ovr-low    { background: var(--panel-3); color: var(--text-2); border: 1px solid var(--border); }

        /* Action buttons */
        .p-actions { display: flex; gap: 5px; }

        /* ── Mobile cards ────────────────────────── */
        .mobile-cards { display: none; flex-direction: column; gap: 10px; }
        .p-card { background: var(--panel-2); border: 1px solid var(--border); border-radius: var(--radius-sm); overflow: hidden; transition: border-color var(--t) var(--ease); }
        .p-card:hover { border-color: var(--border-md); }
        .p-card-header { padding: 12px 14px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid var(--border); }
        .p-card-header .p-avatar { width: 44px; height: 44px; }
        .p-card-info { flex: 1; min-width: 0; }
        .p-card-name { font-size: 14px; font-weight: 700; color: var(--text); }
        .p-card-team { font-size: 11px; color: var(--text-2); margin-top: 1px; }
        .p-card-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; }
        .p-card-stat { padding: 8px 12px; text-align: center; border-right: 1px solid var(--border); }
        .p-card-stat:last-child { border-right: none; }
        .p-card-stat-val { font-size: 14px; font-weight: 800; color: var(--text); line-height: 1; }
        .p-card-stat-label { font-size: 10px; color: var(--text-3); margin-top: 2px; text-transform: uppercase; letter-spacing: .3px; }
        .p-card-footer { padding: 10px 14px; display: flex; gap: 6px; }
        .p-card-footer .btn-r { flex: 1; justify-content: center; }

        /* ── Pagination ──────────────────────────── */
        .pagination-strip { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding-top: 14px; border-top: 1px solid var(--border); margin-top: 4px; }
        .pagination-info { font-size: 12px; color: var(--text-2); }
        .pagination-btns { display: flex; gap: 6px; }

        /* ── Loading / empty ─────────────────────── */
        .loading-wrap { padding: 48px 20px; text-align: center; color: var(--text-3); }
        .spinner-r { display: inline-block; width: 26px; height: 26px; border: 2px solid var(--border); border-top-color: var(--red); border-radius: 50%; animation: spin .6s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .empty-r { padding: 48px 20px; text-align: center; color: var(--text-3); }
        .empty-r i { font-size: 28px; display: block; margin-bottom: 10px; }
        .empty-r p { font-size: 13px; }

        /* ── Modal ───────────────────────────────── */
        .modal-content { background: var(--panel) !important; border: 1px solid var(--border-md) !important; border-radius: var(--radius) !important; font-family: var(--font); color: var(--text); }
        .modal-header { background: var(--panel-2) !important; border-color: var(--border) !important; padding: 16px 20px; border-radius: var(--radius) var(--radius) 0 0 !important; }
        .modal-title { font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .modal-title i { color: var(--red); }
        .modal-body { padding: 20px; }
        .modal-footer { background: var(--panel-2) !important; border-color: var(--border) !important; padding: 14px 20px; border-radius: 0 0 var(--radius) var(--radius) !important; }
        .btn-close-white { filter: invert(1); }

        /* Modal detail grid */
        .detail-stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 20px; }
        .detail-stat { background: var(--panel-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 10px 12px; text-align: center; }
        .detail-stat-val { font-size: 17px; font-weight: 800; color: var(--red); line-height: 1; }
        .detail-stat-label { font-size: 10px; color: var(--text-3); text-transform: uppercase; letter-spacing: .4px; margin-top: 3px; }

        /* Timeline rows */
        .timeline-row { display: flex; align-items: center; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid var(--border); font-size: 13px; }
        .timeline-row:last-child { border-bottom: none; }
        .section-label { font-size: 11px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; color: var(--text-3); margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
        .section-label::after { content: ''; flex: 1; height: 1px; background: var(--border); }

        /* ── Animations ──────────────────────────── */
        @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .panel { animation: fadeUp .35s var(--ease) both; }

        /* ── Responsive ──────────────────────────── */
        @media (max-width: 860px) {
            :root { --sidebar-w: 0px; }
            .sidebar { transform: translateX(-260px); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; width: 100%; padding-top: 54px; }
            .topbar { display: flex; }
            .dash-hero, .content { padding-left: 16px; padding-right: 16px; }
            .dash-hero { padding-top: 18px; }
            .filter-grid { grid-template-columns: 1fr 1fr; }
            .filter-col-span2 { grid-column: span 2; }
        }
        @media (max-width: 768px) {
            #playerTableContainer { display: none; }
            .mobile-cards { display: flex; }
        }
        @media (max-width: 480px) {
            .filter-grid { grid-template-columns: 1fr; }
            .filter-col-span2 { grid-column: span 1; }
            .detail-stat-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
<div class="app">

    <!-- ══════════ SIDEBAR ══════════ -->
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="sb-overlay" id="sbOverlay"></div>

    <header class="topbar">
        <button class="menu-btn" id="menuBtn"><i class="bi bi-list"></i></button>
        <div class="topbar-title">FBA <em>Jogadores</em></div>
    </header>

    <!-- ══════════ MAIN ══════════ -->
    <main class="main">

        <!-- Hero -->
        <div class="dash-hero">
            <div>
                <div class="dash-eyebrow">Liga · <?= htmlspecialchars($league) ?></div>
                <h1 class="dash-title">Jogadores</h1>
                <p class="dash-sub">Lista completa de jogadores da sua liga com filtros avançados</p>
            </div>
        </div>

        <div class="content">

            <!-- Filter panel -->
            <div class="panel" style="animation-delay:.04s">
                <div class="panel-head">
                    <div class="panel-title"><i class="bi bi-funnel-fill"></i> Filtros</div>
                    <button class="btn-r ghost sm" id="btnClearFilters">
                        <i class="bi bi-x-circle"></i> Limpar
                    </button>
                </div>
                <div class="panel-body">
                    <div class="filter-grid">
                        <!-- Nome -->
                        <div class="filter-col-span2">
                            <label class="f-label">Nome do jogador</label>
                            <div style="position:relative">
                                <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:13px;pointer-events:none"></i>
                                <input type="text" id="playersSearchInput" class="f-input" style="padding-left:30px" placeholder="Buscar por nome...">
                            </div>
                        </div>

                        <!-- Posição -->
                        <div>
                            <label class="f-label">Posição</label>
                            <select id="playersPositionFilter" class="f-select">
                                <option value="">Todas</option>
                                <option>PG</option><option>SG</option><option>SF</option><option>PF</option><option>C</option>
                            </select>
                        </div>

                        <!-- OVR -->
                        <div>
                            <label class="f-label">OVR mín</label>
                            <input type="number" id="playersOvrMin" class="f-input" placeholder="40" min="40" max="99">
                        </div>
                        <div>
                            <label class="f-label">OVR máx</label>
                            <input type="number" id="playersOvrMax" class="f-input" placeholder="99" min="40" max="99">
                        </div>

                        <!-- Idade -->
                        <div>
                            <label class="f-label">Idade mín</label>
                            <input type="number" id="playersAgeMin" class="f-input" placeholder="16" min="16" max="45">
                        </div>
                        <div>
                            <label class="f-label">Idade máx</label>
                            <input type="number" id="playersAgeMax" class="f-input" placeholder="45" min="16" max="45">
                        </div>

                        <!-- Time -->
                        <div>
                            <label class="f-label">Time</label>
                            <select id="playersTeamFilter" class="f-select">
                                <option value="">Todos</option>
                            </select>
                        </div>

                        <!-- Buscar -->
                        <div style="display:flex;align-items:flex-end">
                            <button class="btn-r primary" id="playersSearchBtn" style="width:100%;justify-content:center;padding:10px 0">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results panel -->
            <div class="panel" style="animation-delay:.08s">
                <div class="panel-head">
                    <div class="panel-title"><i class="bi bi-person-badge-fill"></i> Jogadores da Liga</div>
                    <span class="tag gray" id="resultsCountBadge">— jogadores</span>
                </div>
                <div class="panel-body" style="padding:0">

                    <!-- Loading -->
                    <div id="playersLoading" class="loading-wrap">
                        <div class="spinner-r"></div>
                        <div style="font-size:13px;color:var(--text-2);margin-top:12px">Carregando jogadores...</div>
                    </div>

                    <!-- Table (desktop) -->
                    <div id="playerTableContainer" style="display:none;overflow-x:auto">
                        <table class="player-table">
                            <thead>
                                <tr>
                                    <th>Jogador</th>
                                    <th>OVR</th>
                                    <th>Idade</th>
                                    <th>Pos.</th>
                                    <th>Pos. 2ª</th>
                                    <th>Time</th>
                                    <th style="text-align:right">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="playersTableBody"></tbody>
                        </table>
                    </div>

                    <!-- Cards (mobile) -->
                    <div id="playersCardsWrap" class="mobile-cards" style="padding:14px"></div>

                    <!-- Empty -->
                    <div id="playersEmpty" class="empty-r" style="display:none">
                        <i class="bi bi-person-x"></i>
                        <p>Nenhum jogador encontrado com esses filtros.</p>
                    </div>

                    <!-- Pagination -->
                    <div id="playersPagination" class="pagination-strip" style="display:none;padding:0 18px 14px">
                        <div class="pagination-info" id="playersPaginationInfo"></div>
                        <div class="pagination-btns">
                            <button class="btn-r ghost sm" id="playersPrevPage">
                                <i class="bi bi-chevron-left"></i> Anterior
                            </button>
                            <button class="btn-r ghost sm" id="playersNextPage">
                                Próximo <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </main>
</div>

<!-- ══════════ MODAL DETALHES ══════════ -->
<div class="modal fade" id="playerDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="playerDetailsTitle">
                    <i class="bi bi-person-badge-fill"></i> Detalhes do Jogador
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="playerDetailsContent">
                <div class="loading-wrap">
                    <div class="spinner-r"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-r ghost" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════ SCRIPTS ══════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/pwa.js"></script>
<script>
    /* ── Sidebar mobile ──────────────────────────── */
    const sidebar   = document.getElementById('sidebar');
    const sbOverlay = document.getElementById('sbOverlay');
    document.getElementById('menuBtn')?.addEventListener('click', () => { sidebar.classList.toggle('open'); sbOverlay.classList.toggle('show'); });
    sbOverlay.addEventListener('click', () => { sidebar.classList.remove('open'); sbOverlay.classList.remove('show'); });

    /* ── State ───────────────────────────────────── */
    const WA_MSG   = '<?= $whatsappDefaultMessage ?>';
    let currentPage = 1;
    let totalPages  = 1;
    const PER_PAGE  = 50;

    /* ── DOM refs ────────────────────────────────── */
    const searchInput    = document.getElementById('playersSearchInput');
    const posFilter      = document.getElementById('playersPositionFilter');
    const ovrMinInput    = document.getElementById('playersOvrMin');
    const ovrMaxInput    = document.getElementById('playersOvrMax');
    const ageMinInput    = document.getElementById('playersAgeMin');
    const ageMaxInput    = document.getElementById('playersAgeMax');
    const teamFilter     = document.getElementById('playersTeamFilter');
    const searchBtn      = document.getElementById('playersSearchBtn');
    const loadingEl      = document.getElementById('playersLoading');
    const tableContainer = document.getElementById('playerTableContainer');
    const tableBody      = document.getElementById('playersTableBody');
    const cardsWrap      = document.getElementById('playersCardsWrap');
    const emptyState     = document.getElementById('playersEmpty');
    const paginationWrap = document.getElementById('playersPagination');
    const paginationInfo = document.getElementById('playersPaginationInfo');
    const prevBtn        = document.getElementById('playersPrevPage');
    const nextBtn        = document.getElementById('playersNextPage');
    const countBadge     = document.getElementById('resultsCountBadge');

    const isMobile = () => window.innerWidth <= 768;

    /* ── OVR badge class ─────────────────────────── */
    function ovrClass(ovr) {
        const n = +ovr;
        if (n >= 85) return 'ovr-elite';
        if (n >= 78) return 'ovr-high';
        if (n >= 72) return 'ovr-mid';
        return 'ovr-low';
    }

    /* ── Player photo ────────────────────────────── */
    function playerPhoto(p) {
        const cp = (p.foto_adicional || '').toString().trim();
        if (cp) return cp;
        if (p.nba_player_id) return `https://cdn.nba.com/headshots/nba/latest/1040x760/${p.nba_player_id}.png`;
        return `https://ui-avatars.com/api/?name=${encodeURIComponent(p.name)}&background=1c1c21&color=fc0025&rounded=true&bold=true`;
    }

    /* ── WhatsApp link ───────────────────────────── */
    function waLink(phone) {
        if (!phone) return null;
        return `https://api.whatsapp.com/send/?phone=${encodeURIComponent(phone)}&text=${WA_MSG}&type=phone_number&app_absent=0`;
    }

    /* ── Render table row ────────────────────────── */
    function renderRow(p) {
        const teamName = `${p.city || ''} ${p.team_name || ''}`.trim();
        const photo    = playerPhoto(p);
        const wa       = waLink(p.owner_phone_whatsapp);
        const ovrCls   = ovrClass(p.ovr);
        return `
        <tr>
            <td>
                <div class="p-cell">
                    <img class="p-avatar" src="${esc(photo)}"
                         alt="${esc(p.name)}"
                         onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(p.name)}&background=1c1c21&color=fc0025&rounded=true&bold=true'">
                    <div>
                        <div class="p-name">${esc(p.name)}</div>
                        <div class="p-team">${esc(teamName)}</div>
                    </div>
                </div>
            </td>
            <td><span class="ovr-badge ${ovrCls}">${p.ovr}</span></td>
            <td style="color:var(--text-2)">${p.age ?? '—'}</td>
            <td><span class="p-pos">${esc(p.position)}</span></td>
            <td><span style="font-size:12px;color:var(--text-2)">${esc(p.secondary_position || '—')}</span></td>
            <td>
                <div style="font-size:12px;font-weight:600;color:var(--text)">${esc(teamName)}</div>
            </td>
            <td>
                <div class="p-actions" style="justify-content:flex-end">
                    <button class="btn-r blue sm" onclick="openPlayerDetails(${p.id})">
                        <i class="bi bi-info-circle"></i> Detalhes
                    </button>
                    ${wa ? `<a class="btn-r green sm" href="${esc(wa)}" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i></a>` : ''}
                    <a class="btn-r amber sm" href="/trades.php?player=${p.id}&team=${p.team_id}">
                        <i class="bi bi-arrow-left-right"></i>
                    </a>
                </div>
            </td>
        </tr>`;
    }

    /* ── Render mobile card ──────────────────────── */
    function renderCard(p) {
        const teamName = `${p.city || ''} ${p.team_name || ''}`.trim();
        const photo    = playerPhoto(p);
        const wa       = waLink(p.owner_phone_whatsapp);
        const ovrCls   = ovrClass(p.ovr);
        return `
        <div class="p-card">
            <div class="p-card-header">
                <img class="p-avatar" style="width:44px;height:44px" src="${esc(photo)}"
                     alt="${esc(p.name)}"
                     onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(p.name)}&background=1c1c21&color=fc0025&rounded=true&bold=true'">
                <div class="p-card-info">
                    <div class="p-card-name">${esc(p.name)}</div>
                    <div class="p-card-team">${esc(teamName)}</div>
                </div>
                <span class="ovr-badge ${ovrCls}">${p.ovr}</span>
            </div>
            <div class="p-card-stats">
                <div class="p-card-stat">
                    <div class="p-card-stat-val"><span class="p-pos" style="font-size:11px">${esc(p.position)}</span></div>
                    <div class="p-card-stat-label">Posição</div>
                </div>
                <div class="p-card-stat">
                    <div class="p-card-stat-val">${p.age ?? '—'}</div>
                    <div class="p-card-stat-label">Idade</div>
                </div>
                <div class="p-card-stat">
                    <div class="p-card-stat-val" style="font-size:12px;color:var(--text-2)">${esc(p.secondary_position || '—')}</div>
                    <div class="p-card-stat-label">Pos. 2ª</div>
                </div>
            </div>
            <div class="p-card-footer">
                <button class="btn-r blue sm" onclick="openPlayerDetails(${p.id})">
                    <i class="bi bi-info-circle"></i> Detalhes
                </button>
                ${wa ? `<a class="btn-r green sm" href="${esc(wa)}" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i></a>` : ''}
                <a class="btn-r amber sm" href="/trades.php?player=${p.id}&team=${p.team_id}">
                    <i class="bi bi-arrow-left-right"></i> Trocar
                </a>
            </div>
        </div>`;
    }

    /* ── Load players ────────────────────────────── */
    async function carregarJogadores() {
        loadingEl.style.display      = 'block';
        tableContainer.style.display = 'none';
        cardsWrap.style.display      = 'none';
        emptyState.style.display     = 'none';
        paginationWrap.style.display = 'none';
        tableBody.innerHTML          = '';
        cardsWrap.innerHTML          = '';

        const params = new URLSearchParams({
            action:   'list_players',
            page:     currentPage,
            per_page: PER_PAGE,
        });
        if (searchInput.value.trim()) params.set('query',    searchInput.value.trim());
        if (posFilter.value)          params.set('position', posFilter.value);
        if (ovrMinInput.value)        params.set('ovr_min',  ovrMinInput.value);
        if (ovrMaxInput.value)        params.set('ovr_max',  ovrMaxInput.value);
        if (ageMinInput.value)        params.set('age_min',  ageMinInput.value);
        if (ageMaxInput.value)        params.set('age_max',  ageMaxInput.value);
        if (teamFilter.value)         params.set('team_id',  teamFilter.value);

        try {
            const res  = await fetch(`/api/team.php?${params}`);
            const data = await res.json();
            const players    = data.players || [];
            const pagination = data.pagination || { page: 1, per_page: PER_PAGE, total: players.length, total_pages: 1 };
            totalPages = pagination.total_pages || 1;

            countBadge.textContent = `${pagination.total ?? players.length} jogador${(pagination.total??players.length)!==1?'es':''}`;

            if (!players.length) {
                emptyState.style.display = 'block';
                return;
            }

            if (isMobile()) {
                cardsWrap.innerHTML     = players.map(renderCard).join('');
                cardsWrap.style.display = 'flex';
            } else {
                tableBody.innerHTML          = players.map(renderRow).join('');
                tableContainer.style.display = 'block';
            }

            if ((pagination.total ?? players.length) > PER_PAGE) {
                paginationInfo.textContent   = `Página ${pagination.page} de ${pagination.total_pages} · ${pagination.total} jogadores`;
                paginationWrap.style.display = 'flex';
                prevBtn.disabled = pagination.page <= 1;
                nextBtn.disabled = pagination.page >= pagination.total_pages;
            }
        } catch(e) {
            emptyState.innerHTML = '<i class="bi bi-exclamation-circle"></i><p>Erro ao carregar jogadores.</p>';
            emptyState.style.display = 'block';
        } finally {
            loadingEl.style.display = 'none';
        }
    }

    /* ── Load teams for filter ───────────────────── */
    async function carregarTimesFiltro() {
        try {
            const data = await fetch('/api/team.php').then(r => r.json());
            const teams = data.teams || [];
            teamFilter.innerHTML = '<option value="">Todos</option>' +
                teams.map(t => `<option value="${t.id}">${esc(t.city + ' ' + t.name)}</option>`).join('');
        } catch(e) {}
    }

    /* ── Player details modal ────────────────────── */
    async function openPlayerDetails(playerId) {
        const content  = document.getElementById('playerDetailsContent');
        const titleEl  = document.getElementById('playerDetailsTitle');
        const modalEl  = document.getElementById('playerDetailsModal');
        content.innerHTML = '<div class="loading-wrap"><div class="spinner-r"></div></div>';
        titleEl.innerHTML = '<i class="bi bi-person-badge-fill"></i> Carregando...';
        new bootstrap.Modal(modalEl).show();

        try {
            const res  = await fetch(`/api/team.php?action=player_details&player_id=${playerId}`);
            const data = await res.json();
            if (!data || data.error) throw new Error(data.error || 'Erro');

            const p            = data.player || {};
            const transfers    = Array.isArray(data.transfers)    ? data.transfers    : [];
            const ovrTimeline  = Array.isArray(data.ovr_timeline) ? data.ovr_timeline : [];
            const photo        = playerPhoto(p);

            titleEl.innerHTML = `<i class="bi bi-person-badge-fill"></i> ${esc(p.name || 'Jogador')}`;

            const transferHtml = transfers.length
                ? transfers.map(t => `
                    <div class="timeline-row">
                        <span style="font-size:12px;color:var(--text-2)">${esc(t.year || '—')}</span>
                        <span style="font-size:13px;color:var(--text)">${esc(t.from_team)} <i class="bi bi-arrow-right" style="color:var(--red);font-size:11px"></i> ${esc(t.to_team)}</span>
                    </div>`).join('')
                : '<div style="font-size:13px;color:var(--text-3);padding:10px 0">Nenhuma trade encontrada.</div>';

            const ovrHtml = ovrTimeline.length
                ? ovrTimeline.map(o => `
                    <div class="timeline-row">
                        <span style="font-size:12px;color:var(--text-2)">Idade ${o.age ?? '—'}</span>
                        <span class="ovr-badge ${ovrClass(o.ovr)}">${o.ovr ?? '—'}</span>
                    </div>`).join('')
                : '<div style="font-size:13px;color:var(--text-3);padding:10px 0">Sem histórico de OVR registrado.</div>';

            content.innerHTML = `
                <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px">
                    <img src="${esc(photo)}" style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid var(--border-red);background:var(--panel-3)"
                         onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(p.name||'?')}&background=1c1c21&color=fc0025&rounded=true&bold=true'">
                    <div>
                        <div style="font-size:16px;font-weight:700">${esc(p.name || '—')}</div>
                        <div style="font-size:12px;color:var(--text-2)">${esc(p.team_name || '—')}</div>
                    </div>
                </div>

                <div class="detail-stat-grid">
                    <div class="detail-stat">
                        <div class="detail-stat-val">${p.ovr ?? '—'}</div>
                        <div class="detail-stat-label">OVR</div>
                    </div>
                    <div class="detail-stat">
                        <div class="detail-stat-val">${p.age ?? '—'}</div>
                        <div class="detail-stat-label">Idade</div>
                    </div>
                    <div class="detail-stat">
                        <div class="detail-stat-val" style="font-size:14px">${esc(p.position ?? '—')}</div>
                        <div class="detail-stat-label">Posição</div>
                    </div>
                    <div class="detail-stat">
                        <div class="detail-stat-val" style="font-size:14px;color:var(--text-2)">${esc(p.secondary_position || '—')}</div>
                        <div class="detail-stat-label">Pos. 2ª</div>
                    </div>
                </div>

                <div class="section-label" style="margin-bottom:10px"><i class="bi bi-arrow-left-right" style="color:var(--text-3)"></i> Transferências</div>
                <div style="margin-bottom:20px">${transferHtml}</div>

                <div class="section-label" style="margin-bottom:10px"><i class="bi bi-graph-up" style="color:var(--text-3)"></i> Progressão de OVR</div>
                <div>${ovrHtml}</div>`;

        } catch(e) {
            content.innerHTML = `<div style="padding:20px;text-align:center;color:#f87171;font-size:14px"><i class="bi bi-exclamation-circle" style="font-size:24px;display:block;margin-bottom:8px"></i>${esc(e.message || 'Erro ao carregar detalhes.')}</div>`;
        }
    }

    /* ── Util ────────────────────────────────────── */
    function esc(s) {
        return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ── Events ──────────────────────────────────── */
    searchBtn.addEventListener('click',  () => { currentPage = 1; carregarJogadores(); });
    searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') { currentPage = 1; carregarJogadores(); } });
    posFilter.addEventListener('change',    () => { currentPage = 1; carregarJogadores(); });
    ovrMinInput.addEventListener('change',  () => { currentPage = 1; carregarJogadores(); });
    ovrMaxInput.addEventListener('change',  () => { currentPage = 1; carregarJogadores(); });
    ageMinInput.addEventListener('change',  () => { currentPage = 1; carregarJogadores(); });
    ageMaxInput.addEventListener('change',  () => { currentPage = 1; carregarJogadores(); });
    teamFilter.addEventListener('change',   () => { currentPage = 1; carregarJogadores(); });

    prevBtn.addEventListener('click', () => { if (currentPage > 1) { currentPage--; carregarJogadores(); } });
    nextBtn.addEventListener('click', () => { if (currentPage < totalPages) { currentPage++; carregarJogadores(); } });

    document.getElementById('btnClearFilters')?.addEventListener('click', () => {
        searchInput.value = '';
        posFilter.value   = '';
        ovrMinInput.value = '';
        ovrMaxInput.value = '';
        ageMinInput.value = '';
        ageMaxInput.value = '';
        teamFilter.value  = '';
        currentPage = 1;
        carregarJogadores();
    });

    /* Re-render on viewport breakpoint change */
    let lastMobile = isMobile(), resizeT;
    window.addEventListener('resize', () => {
        clearTimeout(resizeT);
        resizeT = setTimeout(() => {
            const nowMobile = isMobile();
            if (nowMobile !== lastMobile) { lastMobile = nowMobile; carregarJogadores(); }
        }, 400);
    });

    /* ── Init ────────────────────────────────────── */
    carregarTimesFiltro();
    carregarJogadores();
</script>
</body>
</html>