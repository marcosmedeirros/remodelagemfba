<?php
require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/helpers.php';
requireAuth();

$user    = getUserSession();
$pdo     = db();
$isAdmin = ($user['user_type'] ?? 'jogador') === 'admin';

$stmtTeam = $pdo->prepare('SELECT * FROM teams WHERE user_id = ? LIMIT 1');
$stmtTeam->execute([$user['id']]);
$team   = $stmtTeam->fetch(PDO::FETCH_ASSOC) ?: null;
$teamId = (int)($team['id'] ?? 0);

$userLeague   = strtoupper((string)($team['league'] ?? $user['league'] ?? ''));
$userMoedas   = (int)($team['moedas'] ?? 0);
$rosterLimit  = 15;
$pendingOffers = 0;
$rosterCount   = 0;

if ($teamId) {
    try {
        $s = $pdo->prepare('SELECT COUNT(*) FROM players WHERE team_id = ?');
        $s->execute([$teamId]);
        $rosterCount = (int)$s->fetchColumn();
    } catch (\Exception $e) {}
}

$defaultAdminLeague = $userLeague;
$leagues = ['ELITE','NEXT','RISE','ROOKIE'];

$useNewFreeAgency = true; // flag que o JS lê
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
    <title>Free Agency - FBA Manager</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/styles.css">

    <style>
        /* ── Tokens ──────────────────────────────────────── */
        :root {
            --red:        #fc0025;
            --red-2:      #ff2a44;
            --red-soft:   rgba(252,0,37,.10);
            --red-glow:   rgba(252,0,37,.20);
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
            --cyan:       #06b6d4;
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

        /* ── Sidebar ─────────────────────────────────────── */
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

        /* ── Topbar mobile ───────────────────────────────── */
        .topbar { display: none; position: fixed; top: 0; left: 0; right: 0; height: 54px; background: var(--panel); border-bottom: 1px solid var(--border); align-items: center; padding: 0 16px; gap: 12px; z-index: 199; }
        .topbar-title { font-weight: 700; font-size: 15px; flex: 1; }
        .topbar-title em { color: var(--red); font-style: normal; }
        .menu-btn { width: 34px; height: 34px; border-radius: 9px; background: var(--panel-2); border: 1px solid var(--border); color: var(--text); display: flex; align-items: center; justify-content: center; font-size: 17px; cursor: pointer; }
        .sb-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.65); backdrop-filter: blur(4px); z-index: 199; }
        .sb-overlay.show { display: block; }

        /* ── Main ────────────────────────────────────────── */
        .main { margin-left: var(--sidebar-w); min-height: 100vh; width: calc(100% - var(--sidebar-w)); display: flex; flex-direction: column; }
        .dash-hero { padding: 32px 32px 0; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .dash-eyebrow { font-size: 11px; font-weight: 600; letter-spacing: 1.4px; text-transform: uppercase; color: var(--red); margin-bottom: 4px; }
        .dash-title { font-size: 26px; font-weight: 800; line-height: 1.1; }
        .dash-sub { font-size: 13px; color: var(--text-2); margin-top: 3px; }
        .hero-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding-top: 4px; }
        .content { padding: 20px 32px 40px; flex: 1; }

        /* ── Stat pills ──────────────────────────────────── */
        .stat-pill { background: var(--panel); border: 1px solid var(--border); border-radius: 10px; padding: 9px 14px; display: flex; align-items: center; gap: 8px; }
        .stat-pill-icon { width: 28px; height: 28px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }
        .stat-pill-val { font-size: 15px; font-weight: 800; line-height: 1; }
        .stat-pill-label { font-size: 10px; color: var(--text-2); margin-top: 1px; }

        /* ── Tab nav ─────────────────────────────────────── */
        .tab-nav-wrap {
            display: flex;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 4px;
            margin-bottom: 18px;
            overflow-x: auto;
            scrollbar-width: none;
            gap: 2px;
        }
        .tab-nav-wrap::-webkit-scrollbar { display: none; }
        .tab-btn-r {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 14px; border-radius: 8px;
            background: transparent; border: none;
            color: var(--text-2); font-family: var(--font); font-size: 12px; font-weight: 600;
            cursor: pointer; white-space: nowrap;
            transition: all var(--t) var(--ease);
        }
        .tab-btn-r i { font-size: 13px; }
        .tab-btn-r:hover { background: var(--panel-2); color: var(--text); }
        .tab-btn-r.active { background: var(--red-soft); color: var(--red); }
        .tab-pane-r { display: none; animation: fadeUp .25s var(--ease) both; }
        .tab-pane-r.show { display: block; }

        /* ── Panel ───────────────────────────────────────── */
        .panel { background: var(--panel); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 14px; }
        .panel-head { padding: 14px 18px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; background: var(--panel-2); }
        .panel-title { font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .panel-title i { color: var(--red); }
        .panel-body { padding: 18px; }

        /* ── Buttons ─────────────────────────────────────── */
        .btn-r { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: var(--radius-sm); font-family: var(--font); font-size: 12px; font-weight: 600; cursor: pointer; border: 1px solid transparent; transition: all var(--t) var(--ease); white-space: nowrap; }
        .btn-r.primary { background: var(--red); color: #fff; border-color: var(--red); }
        .btn-r.primary:hover { filter: brightness(1.1); color: #fff; }
        .btn-r.ghost { background: transparent; color: var(--text-2); border-color: var(--border-md); }
        .btn-r.ghost:hover { background: var(--panel-2); color: var(--text); }
        .btn-r.green { background: rgba(34,197,94,.12); color: var(--green); border-color: rgba(34,197,94,.25); }
        .btn-r.green:hover { background: var(--green); color: #fff; }
        .btn-r.amber { background: rgba(245,158,11,.12); color: var(--amber); border-color: rgba(245,158,11,.25); }
        .btn-r.amber:hover { background: var(--amber); color: #000; }
        .btn-r.blue { background: rgba(59,130,246,.12); color: var(--blue); border-color: rgba(59,130,246,.25); }
        .btn-r.blue:hover { background: var(--blue); color: #fff; }
        .btn-r.sm { padding: 5px 10px; font-size: 11px; }
        .btn-r.danger { background: rgba(239,68,68,.12); color: #ef4444; border-color: rgba(239,68,68,.25); }
        .btn-r.danger:hover { background: #ef4444; color: #fff; }
        .btn-r:disabled { opacity: .45; pointer-events: none; }

        /* ── Tags ────────────────────────────────────────── */
        .tag { display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; }
        .tag.green  { background: rgba(34,197,94,.12); color: var(--green); border: 1px solid rgba(34,197,94,.2); }
        .tag.amber  { background: rgba(245,158,11,.12); color: var(--amber); border: 1px solid rgba(245,158,11,.2); }
        .tag.blue   { background: rgba(59,130,246,.12); color: var(--blue);  border: 1px solid rgba(59,130,246,.2); }
        .tag.gray   { background: var(--panel-3); color: var(--text-2); border: 1px solid var(--border); }
        .tag.red    { background: var(--red-soft); color: var(--red); border: 1px solid var(--border-red); }
        .tag.cyan   { background: rgba(6,182,212,.12); color: var(--cyan); border: 1px solid rgba(6,182,212,.2); }

        /* ── FA Player card ──────────────────────────────── */
        .fa-card {
            background: var(--panel-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 16px;
            transition: border-color var(--t) var(--ease), transform var(--t) var(--ease);
            display: flex; flex-direction: column; gap: 12px;
            height: 100%;
        }
        .fa-card:hover { border-color: var(--border-md); transform: translateY(-2px); }
        .fa-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
        .fa-card-name { font-size: 14px; font-weight: 700; color: var(--text); line-height: 1.2; }
        .fa-card-meta { font-size: 11px; color: var(--text-2); margin-top: 2px; }
        .fa-card-origin { font-size: 11px; color: var(--text-3); display: flex; align-items: center; gap: 4px; }
        .fa-ovr { display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 10px; font-size: 17px; font-weight: 900; flex-shrink: 0; }
        .fa-ovr.elite { background: rgba(34,197,94,.15); color: var(--green); border: 1px solid rgba(34,197,94,.25); }
        .fa-ovr.high  { background: rgba(59,130,246,.15); color: var(--blue);  border: 1px solid rgba(59,130,246,.25); }
        .fa-ovr.mid   { background: rgba(245,158,11,.15); color: var(--amber); border: 1px solid rgba(245,158,11,.25); }
        .fa-ovr.low   { background: var(--panel-3); color: var(--text-2); border: 1px solid var(--border); }
        .fa-my-offer  { background: rgba(6,182,212,.08); border: 1px solid rgba(6,182,212,.2); border-radius: 7px; padding: 6px 10px; font-size: 12px; color: var(--cyan); font-weight: 600; display: flex; align-items: center; gap: 5px; }

        /* ── FA search/filter bar ────────────────────────── */
        .fa-filter-bar { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
        .f-input { background: var(--panel-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 9px 10px; color: var(--text); font-family: var(--font); font-size: 13px; outline: none; transition: border-color var(--t) var(--ease); }
        .f-input:focus { border-color: var(--red); }
        .f-input::placeholder { color: var(--text-3); }
        .f-select { background: var(--panel-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 9px 10px; color: var(--text); font-family: var(--font); font-size: 13px; outline: none; cursor: pointer; }
        .f-select:focus { border-color: var(--red); }
        .f-select option { background: var(--panel-2); }
        .f-label { font-size: 10px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: var(--text-2); display: block; margin-bottom: 5px; }

        /* ── Toggle switch (admin) ───────────────────────── */
        .toggle-wrap { display: flex; align-items: center; gap: 10px; }
        .toggle-switch { position: relative; width: 40px; height: 22px; flex-shrink: 0; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-track { position: absolute; inset: 0; background: var(--panel-3); border: 1px solid var(--border-md); border-radius: 999px; cursor: pointer; transition: background var(--t) var(--ease); }
        .toggle-track::before { content: ''; position: absolute; left: 3px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; border-radius: 50%; background: var(--text-2); transition: all var(--t) var(--ease); }
        .toggle-switch input:checked + .toggle-track { background: var(--green); border-color: var(--green); }
        .toggle-switch input:checked + .toggle-track::before { left: calc(100% - 17px); background: #fff; }
        .toggle-label { font-size: 12px; font-weight: 600; color: var(--text-2); }

        /* ── Form fields ─────────────────────────────────── */
        .form-control, .form-select { background: var(--panel-2) !important; border: 1px solid var(--border) !important; border-radius: var(--radius-sm) !important; color: var(--text) !important; font-family: var(--font); font-size: 13px; }
        .form-control:focus, .form-select:focus { border-color: var(--red) !important; box-shadow: 0 0 0 .18rem rgba(252,0,37,.15) !important; outline: none !important; }
        .form-control::placeholder { color: var(--text-3) !important; }
        .form-select option { background: var(--panel-2); }
        .form-label { font-size: 11px; font-weight: 600; letter-spacing: .4px; text-transform: uppercase; color: var(--text-2); margin-bottom: 5px; }

        /* ── Table ───────────────────────────────────────── */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { font-size: 10px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; color: var(--text-3); padding: 10px 14px; border-bottom: 1px solid var(--border); text-align: left; }
        .data-table td { padding: 11px 14px; border-bottom: 1px solid var(--border); font-size: 13px; vertical-align: middle; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tbody tr { transition: background var(--t) var(--ease); }
        .data-table tbody tr:hover { background: var(--panel-2); }

        /* ── Admin card (offers group) ───────────────────── */
        .admin-group { background: var(--panel-2); border: 1px solid var(--border); border-radius: var(--radius-sm); overflow: hidden; margin-bottom: 12px; }
        .admin-group-head { padding: 12px 16px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
        .admin-group-body { padding: 14px 16px; }
        .admin-group:last-child { margin-bottom: 0; }

        /* ── Status banner (FA closed) ───────────────────── */
        .fa-closed-banner { background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.2); border-left: 3px solid #ef4444; border-radius: var(--radius-sm); padding: 12px 16px; font-size: 13px; color: #f87171; display: flex; align-items: center; gap: 8px; margin-bottom: 16px; }

        /* ── Empty / loading ─────────────────────────────── */
        .empty-r { padding: 40px 20px; text-align: center; color: var(--text-3); }
        .empty-r i { font-size: 26px; display: block; margin-bottom: 10px; }
        .empty-r p { font-size: 13px; }
        .spinner-r { display: inline-block; width: 24px; height: 24px; border: 2px solid var(--border); border-top-color: var(--red); border-radius: 50%; animation: spin .6s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Modal ───────────────────────────────────────── */
        .modal-content { background: var(--panel) !important; border: 1px solid var(--border-md) !important; border-radius: var(--radius) !important; font-family: var(--font); color: var(--text); }
        .modal-header { background: var(--panel-2) !important; border-color: var(--border) !important; padding: 16px 20px; border-radius: var(--radius) var(--radius) 0 0 !important; }
        .modal-title { font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .modal-title i { color: var(--red); }
        .modal-body { padding: 20px; }
        .modal-footer { background: var(--panel-2) !important; border-color: var(--border) !important; padding: 14px 20px; border-radius: 0 0 var(--radius) var(--radius) !important; }
        .btn-close-white { filter: invert(1); }

        /* ── Coin input ──────────────────────────────────── */
        .coin-input-wrap { position: relative; }
        .coin-input-wrap .coin-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--amber); font-size: 14px; pointer-events: none; }
        .coin-input-wrap input { padding-left: 30px; }

        /* ── Animations ──────────────────────────────────── */
        @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .panel { animation: fadeUp .35s var(--ease) both; }

        /* ── Responsive ──────────────────────────────────── */
        @media (max-width: 860px) {
            :root { --sidebar-w: 0px; }
            .sidebar { transform: translateX(-260px); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; width: 100%; padding-top: 54px; }
            .topbar { display: flex; }
            .dash-hero, .content { padding-left: 16px; padding-right: 16px; }
            .dash-hero { padding-top: 18px; }
        }
    </style>
</head>
<body>
<div class="app">

    <!-- ══════════ SIDEBAR ══════════════════════════════ -->
    <aside class="sidebar" id="sidebar">
        <div class="sb-brand">
            <div class="sb-logo">FBA</div>
            <div class="sb-brand-text">FBA Manager<span>Liga <?= htmlspecialchars($userLeague) ?></span></div>
        </div>

        <?php if ($team): ?>
        <div class="sb-team">
            <img src="<?= htmlspecialchars($team['photo_url'] ?? '/img/default-team.png') ?>"
                 alt="" onerror="this.src='/img/default-team.png'">
            <div>
                <div class="sb-team-name"><?= htmlspecialchars(($team['city'] ?? '') . ' ' . ($team['name'] ?? '')) ?></div>
                <div class="sb-team-league"><?= htmlspecialchars($userLeague) ?></div>
            </div>
        </div>
        <?php endif; ?>

        <nav class="sb-nav">
            <div class="sb-section">Principal</div>
            <a href="/dashboard.php"><i class="bi bi-house-door-fill"></i> Dashboard</a>
            <a href="/teams.php"><i class="bi bi-people-fill"></i> Times</a>
            <a href="/my-roster.php"><i class="bi bi-person-fill"></i> Meu Elenco</a>
            <a href="/picks.php"><i class="bi bi-calendar-check-fill"></i> Picks</a>
            <a href="/trades.php"><i class="bi bi-arrow-left-right"></i> Trades</a>
            <a href="/free-agency.php" class="active"><i class="bi bi-coin"></i> Free Agency</a>
            <a href="/leilao.php"><i class="bi bi-hammer"></i> Leilão</a>
            <a href="/drafts.php"><i class="bi bi-trophy"></i> Draft</a>

            <div class="sb-section">Liga</div>
            <a href="/rankings.php"><i class="bi bi-bar-chart-fill"></i> Rankings</a>
            <a href="/history.php"><i class="bi bi-clock-history"></i> Histórico</a>

            <?php if ($isAdmin): ?>
            <div class="sb-section">Admin</div>
            <a href="/admin.php"><i class="bi bi-shield-lock-fill"></i> Admin</a>
            <a href="/temporadas.php"><i class="bi bi-calendar3"></i> Temporadas</a>
            <?php endif; ?>

            <div class="sb-section">Conta</div>
            <a href="/settings.php"><i class="bi bi-gear-fill"></i> Configurações</a>
        </nav>

        <div class="sb-footer">
            <img src="<?= htmlspecialchars(getUserPhoto($user['photo_url'] ?? null)) ?>"
                 alt="<?= htmlspecialchars($user['name']) ?>" class="sb-avatar"
                 onerror="this.src='https://ui-avatars.com/api/?name=<?= rawurlencode($user['name']) ?>&background=1c1c21&color=fc0025'">
            <span class="sb-username"><?= htmlspecialchars($user['name']) ?></span>
            <a href="/logout.php" class="sb-logout" title="Sair"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </aside>

    <div class="sb-overlay" id="sbOverlay"></div>

    <header class="topbar">
        <button class="menu-btn" id="menuBtn"><i class="bi bi-list"></i></button>
        <div class="topbar-title">FBA <em>Free Agency</em></div>
    </header>

    <!-- ══════════ MAIN ══════════════════════════════════ -->
    <main class="main">

        <!-- Hero -->
        <div class="dash-hero">
            <div>
                <div class="dash-eyebrow">Mercado Livre · <?= htmlspecialchars($userLeague) ?></div>
                <h1 class="dash-title">Free Agency</h1>
                <p class="dash-sub">Solicite jogadores disponíveis e acompanhe suas propostas</p>
            </div>
            <div class="hero-meta">
                <!-- Moedas -->
                <div class="stat-pill">
                    <div class="stat-pill-icon" style="background:rgba(245,158,11,.12);color:var(--amber)"><i class="bi bi-coin"></i></div>
                    <div>
                        <div class="stat-pill-val"><?= $userMoedas ?></div>
                        <div class="stat-pill-label">Moedas</div>
                    </div>
                </div>
                <!-- Elenco -->
                <div class="stat-pill">
                    <div class="stat-pill-icon" style="background:var(--red-soft);color:var(--red)"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="stat-pill-val"><?= $rosterCount ?><span style="font-size:12px;color:var(--text-3);font-weight:400">/<?= $rosterLimit ?></span></div>
                        <div class="stat-pill-label">Elenco</div>
                    </div>
                </div>
                <?php if ($isAdmin): ?>
                <!-- Admin: status toggle -->
                <div class="stat-pill" style="gap:14px">
                    <div class="toggle-wrap">
                        <label class="toggle-switch">
                            <input type="checkbox" id="faStatusToggle" checked>
                            <span class="toggle-track"></span>
                        </label>
                        <span class="toggle-label" id="faStatusLabel">Propostas abertas</span>
                    </div>
                    <span id="faStatusBadge" class="tag green">Ativo</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Content -->
        <div class="content">

            <!-- ── Tab Navigation ── -->
            <div class="tab-nav-wrap">
                <button class="tab-btn-r active" data-tab="fa-market" onclick="switchFaTab('fa-market', this)">
                    <i class="bi bi-shop"></i> Mercado
                </button>
                <button class="tab-btn-r" data-tab="fa-my" onclick="switchFaTab('fa-my', this)">
                    <i class="bi bi-send-fill"></i> Minhas Propostas
                    <span id="faNewMyCount" style="background:var(--red);color:#fff;border-radius:999px;font-size:10px;font-weight:700;padding:1px 6px;display:none">0</span>
                </button>
                <button class="tab-btn-r" id="fa-history-tab" data-tab="fa-history" onclick="switchFaTab('fa-history', this); dispatchFaTabEvent(this)">
                    <i class="bi bi-clock-history"></i> Histórico
                </button>
                <?php if ($isAdmin): ?>
                <button class="tab-btn-r" id="fa-admin-tab" data-tab="fa-admin" onclick="switchFaTab('fa-admin', this); dispatchFaTabEvent(this)">
                    <i class="bi bi-shield-lock-fill"></i> Admin
                </button>
                <?php endif; ?>
            </div>

            <!-- ═══════════════════════════════════════════
                 TAB: Mercado
            ═══════════════════════════════════════════ -->
            <div class="tab-pane-r show" id="tab-fa-market">

                <!-- Formulário de solicitação (nova FA) -->
                <div class="panel" style="animation-delay:.04s">
                    <div class="panel-head">
                        <div class="panel-title"><i class="bi bi-plus-circle-fill"></i> Solicitar Jogador</div>
                        <span id="faNewRemainingBadge" class="tag green">— disponíveis</span>
                    </div>
                    <div class="panel-body">
                        <form id="faNewRequestForm">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Nome do Jogador</label>
                                    <input type="text" id="faNewPlayerName" class="form-control" placeholder="Ex: LeBron James" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Posição</label>
                                    <select id="faNewPosition" class="form-select">
                                        <option value="PG">PG</option>
                                        <option value="SG">SG</option>
                                        <option value="SF">SF</option>
                                        <option value="PF">PF</option>
                                        <option value="C">C</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Pos. 2ª</label>
                                    <input type="text" id="faNewSecondary" class="form-control" placeholder="SG">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">Idade</label>
                                    <input type="number" id="faNewAge" class="form-control" value="24" min="16" max="45">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">OVR</label>
                                    <input type="number" id="faNewOvr" class="form-control" value="70" min="40" max="99">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Oferta (moedas)</label>
                                    <div class="coin-input-wrap">
                                        <i class="bi bi-coin coin-icon"></i>
                                        <input type="number" id="faNewOffer" class="form-control" value="1" min="0">
                                    </div>
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" class="btn-r primary" id="faNewSubmitBtn">
                                        <i class="bi bi-send-fill"></i> Enviar Proposta
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de agentes livres -->
                <div class="panel" style="animation-delay:.08s">
                    <div class="panel-head">
                        <div class="panel-title"><i class="bi bi-person-badge-fill"></i> Agentes Disponíveis</div>
                    </div>
                    <div class="panel-body">
                        <div class="fa-filter-bar">
                            <div style="position:relative;flex:1;min-width:160px">
                                <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:12px;pointer-events:none"></i>
                                <input type="text" id="faSearchInput" class="f-input" style="width:100%;padding-left:28px" placeholder="Buscar por nome...">
                            </div>
                            <select id="faPositionFilter" class="f-select">
                                <option value="">Todas as posições</option>
                                <option>PG</option><option>SG</option><option>SF</option><option>PF</option><option>C</option>
                            </select>
                        </div>
                        <div id="freeAgentsContainer">
                            <div class="empty-r"><div class="spinner-r"></div></div>
                        </div>
                    </div>
                </div>

            </div><!-- /tab-fa-market -->

            <!-- ═══════════════════════════════════════════
                 TAB: Minhas Propostas
            ═══════════════════════════════════════════ -->
            <div class="tab-pane-r" id="tab-fa-my">
                <div class="panel" style="animation-delay:.04s">
                    <div class="panel-head">
                        <div class="panel-title"><i class="bi bi-send-fill"></i> Minhas Propostas</div>
                        <button class="btn-r ghost sm" onclick="carregarMinhasPropostasNovaFA()">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                    <div class="panel-body">
                        <div id="faNewMyRequests">
                            <div class="empty-r"><div class="spinner-r"></div></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════
                 TAB: Histórico
            ═══════════════════════════════════════════ -->
            <div class="tab-pane-r" id="tab-fa-history">

                <!-- Contratações -->
                <div class="panel" style="animation-delay:.04s">
                    <div class="panel-head">
                        <div class="panel-title"><i class="bi bi-check-circle-fill"></i> Contratações</div>
                        <select id="faHistorySeasonFilter" class="f-select" style="font-size:12px;padding:5px 8px">
                            <option value="">Todas temporadas</option>
                        </select>
                    </div>
                    <div class="panel-body" style="padding:0">
                        <div id="faHistoryContainer">
                            <div class="empty-r"><div class="spinner-r"></div></div>
                        </div>
                    </div>
                </div>

                <!-- Dispensados (waivers) -->
                <div class="panel" style="animation-delay:.08s">
                    <div class="panel-head">
                        <div class="panel-title"><i class="bi bi-person-x-fill"></i> Dispensados (Waivers)</div>
                        <div style="display:flex;gap:8px">
                            <select id="faWaiversSeasonFilter" class="f-select" style="font-size:12px;padding:5px 8px">
                                <option value="">Todas temporadas</option>
                            </select>
                            <select id="faWaiversTeamFilter" class="f-select" style="font-size:12px;padding:5px 8px">
                                <option value="">Todos os times</option>
                            </select>
                        </div>
                    </div>
                    <div class="panel-body" style="padding:0">
                        <div id="faWaiversContainer">
                            <div class="empty-r"><div class="spinner-r"></div></div>
                        </div>
                    </div>
                </div>

            </div><!-- /tab-fa-history -->

            <?php if ($isAdmin): ?>
            <!-- ═══════════════════════════════════════════
                 TAB: Admin
            ═══════════════════════════════════════════ -->
            <div class="tab-pane-r" id="tab-fa-admin">

                <!-- Admin league selector -->
                <div class="panel" style="animation-delay:.04s">
                    <div class="panel-head">
                        <div class="panel-title"><i class="bi bi-shield-lock-fill"></i> Painel Administrativo</div>
                        <select id="adminLeagueSelect" class="f-select" onchange="onAdminLeagueChange()" style="min-width:120px">
                            <?php foreach ($leagues as $lg): ?>
                            <option value="<?= $lg ?>" <?= $lg === $userLeague ? 'selected' : '' ?>><?= $lg ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Nova FA: solicitações -->
                <div class="panel" style="animation-delay:.08s">
                    <div class="panel-head">
                        <div class="panel-title"><i class="bi bi-inbox-fill"></i> Solicitações Pendentes</div>
                        <div style="display:flex;gap:8px;align-items:center">
                            <select id="faNewAdminLeague" class="f-select" style="font-size:12px;padding:5px 8px;min-width:100px">
                                <?php foreach ($leagues as $lg): ?>
                                <option value="<?= $lg ?>" <?= $lg === $userLeague ? 'selected' : '' ?>><?= $lg ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn-r ghost sm" onclick="carregarSolicitacoesNovaFA()">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div id="faNewAdminRequests">
                            <div class="empty-r"><div class="spinner-r"></div></div>
                        </div>
                    </div>
                </div>

                <!-- Add free agent (legacy) -->
                <div class="panel" style="animation-delay:.12s">
                    <div class="panel-head">
                        <div class="panel-title"><i class="bi bi-plus-circle-fill"></i> Adicionar Agente Livre</div>
                    </div>
                    <div class="panel-body">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">Liga</label>
                                <select id="faLeague" class="form-select">
                                    <?php foreach ($leagues as $lg): ?>
                                    <option value="<?= $lg ?>" <?= $lg === $userLeague ? 'selected' : '' ?>><?= $lg ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nome</label>
                                <input type="text" id="faPlayerName" class="form-control" placeholder="Nome do jogador">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Posição</label>
                                <select id="faPosition" class="form-select">
                                    <option>PG</option><option>SG</option><option>SF</option><option>PF</option><option>C</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Pos. 2ª</label>
                                <input type="text" id="faSecondaryPosition" class="form-control" placeholder="SG">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Idade</label>
                                <input type="number" id="faAge" class="form-control" value="25" min="16" max="45">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">OVR</label>
                                <input type="number" id="faOvr" class="form-control" value="70" min="40" max="99">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button class="btn-r primary w-100" id="btnAddFreeAgent">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Free agents cadastrados -->
                <div class="panel" style="animation-delay:.16s">
                    <div class="panel-head">
                        <div class="panel-title"><i class="bi bi-list-ul"></i> Agentes Cadastrados</div>
                    </div>
                    <div class="panel-body" style="padding:0">
                        <div id="adminFreeAgentsContainer">
                            <div class="empty-r"><div class="spinner-r"></div></div>
                        </div>
                    </div>
                </div>

                <!-- Propostas pendentes admin -->
                <div class="panel" style="animation-delay:.20s">
                    <div class="panel-head">
                        <div class="panel-title"><i class="bi bi-hourglass-split"></i> Propostas Pendentes</div>
                    </div>
                    <div class="panel-body">
                        <div id="adminOffersContainer">
                            <div class="empty-r"><div class="spinner-r"></div></div>
                        </div>
                    </div>
                </div>

                <!-- Histórico de contratações admin -->
                <div class="panel" style="animation-delay:.24s">
                    <div class="panel-head">
                        <div class="panel-title"><i class="bi bi-clock-history"></i> Histórico de Contratações</div>
                    </div>
                    <div class="panel-body" style="padding:0">
                        <div id="faContractsHistoryContainer">
                            <div class="empty-r"><div class="spinner-r"></div></div>
                        </div>
                    </div>
                </div>

            </div><!-- /tab-fa-admin -->
            <?php endif; ?>

        </div><!-- /content -->
    </main>
</div>

<!-- ══════════ MODAL: PROPOSTA ══════════════════════════ -->
<div class="modal fade" id="modalOffer" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-coin"></i> Enviar Proposta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p style="font-size:14px;margin-bottom:16px">
                    Jogador: <strong id="freeAgentNomeOffer" style="color:var(--red)">—</strong>
                </p>
                <input type="hidden" id="freeAgentIdOffer">

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Oferta em moedas</label>
                        <div class="coin-input-wrap">
                            <i class="bi bi-coin coin-icon"></i>
                            <input type="number" id="offerAmount" class="form-control" min="0" value="1">
                        </div>
                        <div style="font-size:11px;color:var(--text-3);margin-top:5px">Use 0 para cancelar sua proposta atual.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Prioridade</label>
                        <select id="offerPriority" class="form-select">
                            <option value="1">1 — Alta</option>
                            <option value="2">2 — Média</option>
                            <option value="3">3 — Baixa</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-r ghost" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn-r primary" id="btnConfirmOffer">
                    <i class="bi bi-send-fill"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════ SCRIPTS ══════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/pwa.js"></script>

<script>
    /* ── Globals lidos pelo free-agency.js ──────────────── */
    const isAdmin            = <?= $isAdmin ? 'true' : 'false' ?>;
    const userLeague         = '<?= htmlspecialchars($userLeague, ENT_QUOTES) ?>';
    const defaultAdminLeague = '<?= htmlspecialchars($defaultAdminLeague, ENT_QUOTES) ?>';
    const userTeamId         = <?= $teamId ?>;
    const userMoedas         = <?= $userMoedas ?>;
    const rosterLimit        = <?= $rosterLimit ?>;
    const useNewFreeAgency   = <?= $useNewFreeAgency ? 'true' : 'false' ?>;
    let   userRosterCount    = <?= $rosterCount ?>;
    let   userPendingOffers  = <?= $pendingOffers ?>;

    /* ── Sidebar mobile ─────────────────────────────────── */
    const sidebar   = document.getElementById('sidebar');
    const sbOverlay = document.getElementById('sbOverlay');
    document.getElementById('menuBtn')?.addEventListener('click', () => { sidebar.classList.toggle('open'); sbOverlay.classList.toggle('show'); });
    sbOverlay.addEventListener('click', () => { sidebar.classList.remove('open'); sbOverlay.classList.remove('show'); });

    /* ── Tab switcher ───────────────────────────────────── */
    function switchFaTab(id, btn) {
        document.querySelectorAll('.tab-pane-r').forEach(p => p.classList.remove('show'));
        document.querySelectorAll('.tab-btn-r').forEach(b => b.classList.remove('active'));
        const pane = document.getElementById('tab-' + id);
        if (pane) pane.classList.add('show');
        if (btn) btn.classList.add('active');
    }

    /* Dispatch Bootstrap-style shown.bs.tab for free-agency.js listeners */
    function dispatchFaTabEvent(btn) {
        const evt = new CustomEvent('shown.bs.tab', { bubbles: true, detail: { tab: btn.dataset.tab } });
        btn.dispatchEvent(evt);
    }

    /* Bootstrap Tab shim: free-agency.js calls addEventListener('shown.bs.tab') on elements */
    document.getElementById('fa-history-tab')?.addEventListener('shown.bs.tab', () => {
        if (typeof carregarHistoricoNovaFA === 'function') carregarHistoricoNovaFA();
        if (typeof carregarDispensados === 'function') carregarDispensados();
    });
    document.getElementById('fa-admin-tab')?.addEventListener('shown.bs.tab', () => {
        if (typeof carregarFreeAgentsAdmin === 'function') carregarFreeAgentsAdmin();
        if (typeof carregarPropostasAdmin === 'function') carregarPropostasAdmin();
        if (typeof carregarHistoricoContratacoes === 'function') carregarHistoricoContratacoes();
        if (typeof carregarSolicitacoesNovaFA === 'function') carregarSolicitacoesNovaFA();
    });

    /* ── Status toggle label sync ───────────────────────── */
    document.getElementById('faStatusToggle')?.addEventListener('change', function() {
        const label = document.getElementById('faStatusLabel');
        const badge = document.getElementById('faStatusBadge');
        if (this.checked) {
            if (label) label.textContent = 'Propostas abertas';
            if (badge) { badge.className = 'tag green'; badge.textContent = 'Ativo'; }
        } else {
            if (label) label.textContent = 'Propostas fechadas';
            if (badge) { badge.className = 'tag red'; badge.textContent = 'Bloqueado'; }
        }
    });

    /* ── Update badge count helper ──────────────────────── */
    window.updateFaBadge = function(count) {
        const el = document.getElementById('faNewMyCount');
        if (!el) return;
        el.textContent = count;
        el.style.display = count > 0 ? '' : 'none';
    };

    /* ── Patch faNewMyCount update from JS ──────────────── */
    const _origSetText = Object.getOwnPropertyDescriptor(Element.prototype, 'textContent');
    // Let free-agency.js set faNewMyCount directly — no patch needed, badge auto-updates via JS

    /* ── Stagger animation delays ───────────────────────── */
    document.querySelectorAll('.panel').forEach((el, i) => {
        el.style.animationDelay = (i * 0.04 + 0.04) + 's';
    });
</script>

<!-- Free Agency logic (must come after globals) -->
<script src="/js/free-agency.js?v=<?= time() ?>"></script>
</body>
</html>