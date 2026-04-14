<?php
require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';
requireAuth();

$user = getUserSession();
$pdo = db();

$stmtTeam = $pdo->prepare('SELECT * FROM teams WHERE user_id = ? LIMIT 1');
$stmtTeam->execute([$user['id']]);
$team = $stmtTeam->fetch();

$isAdmin = ($user['user_type'] ?? 'jogador') === 'admin';
$userLeague = strtoupper($team['league'] ?? $user['league'] ?? 'ELITE');
$currentTeamId = (int)($team['id'] ?? 0);
$currentSeason = null;
$currentSeasonYear = (int)date('Y');
if (!empty($team['league'])) {
    try {
        $stmtSeason = $pdo->prepare('SELECT s.season_number, s.year, sp.start_year FROM seasons s LEFT JOIN sprints sp ON s.sprint_id = sp.id WHERE s.league = ? AND (s.status IS NULL OR s.status NOT IN ("completed")) ORDER BY s.created_at DESC LIMIT 1');
        $stmtSeason->execute([$team['league']]);
        $currentSeason = $stmtSeason->fetch();
        if ($currentSeason && isset($currentSeason['start_year'], $currentSeason['season_number'])) {
            $currentSeasonYear = (int)$currentSeason['start_year'] + (int)$currentSeason['season_number'] - 1;
        } elseif ($currentSeason && isset($currentSeason['year'])) {
            $currentSeasonYear = (int)$currentSeason['year'];
        }
    } catch (Exception $e) { $currentSeason = null; }
}
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
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/img/fba-logo.png?v=3">
    <?php include __DIR__ . '/includes/head-pwa.php'; ?>
    <title>Rankings - FBA Manager</title>

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

        /* ── Hero ────────────────────────────────── */
        .dash-hero { padding: 32px 32px 0; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .dash-eyebrow { font-size: 11px; font-weight: 600; letter-spacing: 1.4px; text-transform: uppercase; color: var(--red); margin-bottom: 4px; }
        .dash-title { font-size: 26px; font-weight: 800; line-height: 1.1; }
        .dash-sub { font-size: 13px; color: var(--text-2); margin-top: 3px; }
        .hero-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding-top: 4px; }

        /* ── Buttons ─────────────────────────────── */
        .btn-r { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: var(--radius-sm); font-family: var(--font); font-size: 12px; font-weight: 600; cursor: pointer; border: 1px solid transparent; transition: all var(--t) var(--ease); white-space: nowrap; text-decoration: none; }
        .btn-r.primary { background: var(--red); color: #fff; border-color: var(--red); }
        .btn-r.primary:hover { filter: brightness(1.1); color: #fff; }
        .btn-r.ghost { background: transparent; color: var(--text-2); border-color: var(--border-md); }
        .btn-r.ghost:hover { background: var(--panel-2); color: var(--text); }
        .btn-r.amber { background: rgba(245,158,11,.12); color: var(--amber); border-color: rgba(245,158,11,.25); }
        .btn-r.amber:hover { background: var(--amber); color: #000; }

        /* ── Content ─────────────────────────────── */
        .content { padding: 20px 32px 40px; flex: 1; }

        /* ── League filter tabs ──────────────────── */
        .league-tabs { display: flex; gap: 6px; margin-bottom: 20px; flex-wrap: wrap; }
        .league-tab {
            padding: 8px 20px; border-radius: 999px;
            background: var(--panel); border: 1px solid var(--border);
            color: var(--text-2); font-family: var(--font); font-size: 12px; font-weight: 700;
            cursor: pointer; letter-spacing: .5px; text-transform: uppercase;
            transition: all var(--t) var(--ease);
        }
        .league-tab:hover { border-color: var(--border-md); color: var(--text); }
        .league-tab.active { background: var(--red); border-color: var(--red); color: #fff; box-shadow: 0 6px 18px rgba(252,0,37,.25); }

        /* ── Podium (top 3) ──────────────────────── */
        .podium { display: flex; align-items: flex-end; justify-content: center; gap: 12px; margin-bottom: 24px; }
        .podium-item { display: flex; flex-direction: column; align-items: center; gap: 8px; flex: 1; max-width: 180px; }
        .podium-card {
            width: 100%; background: var(--panel);
            border: 1px solid var(--border); border-radius: var(--radius);
            padding: 16px 12px; text-align: center;
            position: relative; overflow: hidden;
            transition: transform var(--t) var(--ease);
        }
        .podium-card:hover { transform: translateY(-3px); }
        .podium-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: var(--podium-color, var(--border));
        }
        .podium-card.first  { --podium-color: #f59e0b; border-color: rgba(245,158,11,.25); background: linear-gradient(180deg, rgba(245,158,11,.08), var(--panel)); }
        .podium-card.second { --podium-color: #94a3b8; border-color: rgba(148,163,184,.2); }
        .podium-card.third  { --podium-color: #cd7c4a; border-color: rgba(205,124,74,.2); }
        .podium-logo { width: 52px; height: 52px; border-radius: 50%; object-fit: cover; border: 2px solid var(--podium-color, var(--border)); display: block; margin: 0 auto 8px; background: var(--panel-3); }
        .podium-rank { font-size: 22px; font-weight: 900; line-height: 1; }
        .podium-rank.first  { color: #f59e0b; }
        .podium-rank.second { color: #94a3b8; }
        .podium-rank.third  { color: #cd7c4a; }
        .podium-name { font-size: 12px; font-weight: 700; color: var(--text); line-height: 1.3; }
        .podium-owner { font-size: 10px; color: var(--text-2); margin-top: 2px; }
        .podium-pts { font-size: 15px; font-weight: 800; color: var(--amber); }
        .podium-pts-label { font-size: 9px; color: var(--text-3); text-transform: uppercase; letter-spacing: .5px; }
        .podium-pillar {
            width: 100%; border-radius: var(--radius-sm) var(--radius-sm) 0 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700; color: rgba(255,255,255,.5);
            letter-spacing: .3px;
        }
        .podium-item.first  .podium-pillar { height: 44px; background: rgba(245,158,11,.12); border: 1px solid rgba(245,158,11,.2); border-bottom: none; }
        .podium-item.second .podium-pillar { height: 30px; background: rgba(148,163,184,.08); border: 1px solid rgba(148,163,184,.15); border-bottom: none; }
        .podium-item.third  .podium-pillar { height: 20px; background: rgba(205,124,74,.08); border: 1px solid rgba(205,124,74,.15); border-bottom: none; }

        /* ── Ranking table ───────────────────────── */
        .ranking-panel { background: var(--panel); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
        .ranking-head { padding: 14px 18px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; background: var(--panel-2); }
        .ranking-head-title { font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .ranking-head-title i { color: var(--red); }

        .rank-table { width: 100%; border-collapse: collapse; }
        .rank-table th { font-size: 10px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; color: var(--text-3); padding: 10px 16px; border-bottom: 1px solid var(--border); text-align: left; }
        .rank-table th.center { text-align: center; }
        .rank-table td { padding: 12px 16px; border-bottom: 1px solid var(--border); font-size: 13px; vertical-align: middle; }
        .rank-table tr:last-child td { border-bottom: none; }
        .rank-table tbody tr { transition: background var(--t) var(--ease); }
        .rank-table tbody tr:hover { background: var(--panel-2); }

        /* My team highlight */
        .rank-table tbody tr.is-me { background: rgba(34,197,94,.06); }
        .rank-table tbody tr.is-me:hover { background: rgba(34,197,94,.10); }
        .rank-table tbody tr.is-me td:first-child { border-left: 3px solid var(--green); }

        /* Rookie highlight */
        .rank-table tbody tr.top5-rookie { background: rgba(59,130,246,.06); }
        .rank-table tbody tr.top5-rookie td:first-child { border-left: 3px solid var(--blue); }

        /* Rank number cell */
        .rank-num { font-size: 15px; font-weight: 800; width: 40px; text-align: center; }
        .rank-num.gold   { color: #f59e0b; }
        .rank-num.silver { color: #94a3b8; }
        .rank-num.bronze { color: #cd7c4a; }
        .rank-num.normal { color: var(--text-3); }

        /* Team cell */
        .rank-team-cell { display: flex; align-items: center; gap: 10px; }
        .rank-logo { width: 32px; height: 32px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border-md); background: var(--panel-3); flex-shrink: 0; }
        .rank-team-name { font-size: 13px; font-weight: 600; color: var(--text); }
        .rank-owner { font-size: 11px; color: var(--text-2); }

        /* Tags */
        .tag { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; }
        .tag.blue  { background: rgba(59,130,246,.12); color: var(--blue); border: 1px solid rgba(59,130,246,.2); }
        .tag.amber { background: rgba(245,158,11,.12); color: var(--amber); border: 1px solid rgba(245,158,11,.2); }
        .tag.green { background: rgba(34,197,94,.12); color: var(--green); border: 1px solid rgba(34,197,94,.2); }
        .tag.red   { background: var(--red-soft); color: var(--red); border: 1px solid var(--border-red); }

        /* ── Loading / empty ─────────────────────── */
        .loading-wrap { padding: 56px 20px; text-align: center; color: var(--text-3); }
        .spinner-r { display: inline-block; width: 28px; height: 28px; border: 2px solid var(--border); border-top-color: var(--red); border-radius: 50%; animation: spin .6s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .empty-r { padding: 48px 20px; text-align: center; color: var(--text-3); }
        .empty-r i { font-size: 30px; display: block; margin-bottom: 10px; }
        .empty-r p { font-size: 13px; }

        /* ── Error ───────────────────────────────── */
        .err-box { padding: 14px 18px; background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.2); border-radius: var(--radius-sm); font-size: 13px; color: #f87171; display: flex; align-items: center; gap: 8px; }

        /* ── Modal ───────────────────────────────── */
        .modal-content { background: var(--panel) !important; border: 1px solid var(--border-md) !important; border-radius: var(--radius) !important; font-family: var(--font); color: var(--text); }
        .modal-header { background: var(--panel-2) !important; border-color: var(--border) !important; padding: 16px 20px; border-radius: var(--radius) var(--radius) 0 0 !important; }
        .modal-title { font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .modal-title i { color: var(--red); }
        .modal-body { padding: 20px; }
        .modal-footer { background: var(--panel-2) !important; border-color: var(--border) !important; padding: 14px 20px; border-radius: 0 0 var(--radius) var(--radius) !important; }
        .btn-close-white { filter: invert(1); }
        .form-control { background: var(--panel-2) !important; border: 1px solid var(--border) !important; border-radius: var(--radius-sm) !important; color: var(--text) !important; font-family: var(--font); font-size: 13px; }
        .form-control:focus { border-color: var(--red) !important; box-shadow: 0 0 0 .18rem rgba(252,0,37,.15) !important; }
        .table-dark { --bs-table-bg: transparent !important; --bs-table-color: var(--text); --bs-table-border-color: var(--border); }
        .table-dark thead th { font-size: 10px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; color: var(--text-3); border-color: var(--border) !important; padding: 10px 14px; background: var(--panel-2); }
        .table-dark tbody td { border-color: var(--border) !important; padding: 10px 14px; font-size: 13px; vertical-align: middle; }
        .table-dark tbody tr:hover { background: var(--panel-2) !important; }

        /* ── Animations ──────────────────────────── */
        @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .ranking-panel { animation: fadeUp .35s var(--ease) both; }

        /* ── Light theme overrides ───────────────────── */
        :root[data-theme="light"] {
            --bg:         #f4f6fb;
            --panel:      #ffffff;
            --panel-2:    #f0f2f8;
            --panel-3:    #e8ebf4;
            --border:     rgba(15,23,42,.09);
            --border-md:  rgba(15,23,42,.14);
            --border-red: rgba(252,0,37,.20);
            --text:       #111217;
            --text-2:     #5b6270;
            --text-3:     #9ca0ae;
        }
        [data-theme="light"] body { background: var(--bg); color: var(--text); }
        [data-theme="light"] .modal-content { background: var(--panel) !important; color: var(--text) !important; border-color: var(--border-md) !important; }
        [data-theme="light"] .modal-header { background: var(--panel-2) !important; border-color: var(--border) !important; }
        [data-theme="light"] .modal-footer { background: var(--panel-2) !important; border-color: var(--border) !important; }
        [data-theme="light"] .btn-close { filter: none; }
        [data-theme="light"] .form-control { background: var(--panel-2) !important; border-color: var(--border-md) !important; color: var(--text) !important; }
        [data-theme="light"] .table-dark { --bs-table-bg: var(--panel); --bs-table-color: var(--text); --bs-table-border-color: var(--border); }
        [data-theme="light"] .table-dark thead th { background: var(--panel-2); color: var(--text-3); border-color: var(--border) !important; }
        [data-theme="light"] .table-dark tbody td { border-color: var(--border) !important; }
        [data-theme="light"] .table-dark tbody tr:hover { background: var(--panel-2) !important; }

        /* ── Sidebar toggle (hidden — topbar handles mobile) ─ */
        .sidebar-toggle { display: none !important; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.65); backdrop-filter: blur(4px); z-index: 199; }
        .sidebar-overlay.active, .sidebar-overlay.show { display: block; }

        /* ── Responsive ──────────────────────────── */
        @media (max-width: 860px) {
            :root { --sidebar-w: 0px; }
            .sidebar { transform: translateX(-260px); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; width: 100%; padding-top: 54px; }
            .topbar { display: flex; }
            .dash-hero, .content { padding-left: 16px; padding-right: 16px; }
            .dash-hero { padding-top: 18px; }
            .podium { gap: 8px; }
            .podium-item { max-width: 130px; }
            .podium-logo { width: 40px; height: 40px; }
            .hide-mobile { display: none !important; }
        }
        @media (max-width: 480px) {
            .podium { display: none; }
            .league-tabs { gap: 4px; }
            .league-tab { padding: 6px 12px; font-size: 11px; }
        }
    </style>
</head>
<body>
<div class="app">

    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list fs-4"></i>
    </button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ══════════ SIDEBAR ══════════ -->
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <header class="topbar">
        <button class="menu-btn" id="menuBtn"><i class="bi bi-list"></i></button>
        <div class="topbar-title">FBA <em>Rankings</em></div>
    </header>

    <!-- ══════════ MAIN ══════════ -->
    <main class="main">

        <!-- Hero -->
        <div class="dash-hero">
            <div>
                <div class="dash-eyebrow">Liga · <?= htmlspecialchars($userLeague) ?></div>
                <h1 class="dash-title">Rankings</h1>
                <p class="dash-sub">Classificação geral das franquias por pontos e títulos</p>
            </div>
            <div class="hero-actions">
                <a href="/hall-da-fama.php" class="btn-r ghost">
                    <i class="bi bi-award"></i> Hall da Fama
                </a>
                <?php if ($isAdmin): ?>
                <button class="btn-r primary" id="btnEditRanking"
                        data-bs-toggle="modal" data-bs-target="#editRankingModal">
                    <i class="bi bi-pencil-square"></i> Editar
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Content -->
        <div class="content">

            <!-- League tabs -->
            <div class="league-tabs">
                <?php foreach (['ELITE','NEXT','RISE','ROOKIE'] as $lg): ?>
                <button class="league-tab <?= $lg === $userLeague ? 'active' : '' ?>"
                        data-league="<?= $lg ?>"
                        onclick="loadRanking('<?= $lg ?>')">
                    <?= $lg ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Podium (top 3) rendered here -->
            <div id="podiumWrap"></div>

            <!-- Ranking panel -->
            <div id="rankingContainer">
                <div class="loading-wrap">
                    <div class="spinner-r"></div>
                    <div style="font-size:13px;color:var(--text-2);margin-top:12px">Carregando ranking...</div>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- ══════════ MODAL EDIT RANKING (ADMIN) ══════════ -->
<?php if ($isAdmin): ?>
<div class="modal fade" id="editRankingModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square"></i>
                    Editar Ranking — <span id="editRankingLeague"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="editRankingLoading" style="text-align:center;padding:32px 0">
                    <div class="spinner-r"></div>
                </div>
                <div style="overflow-x:auto" id="editRankingTableWrap" style="display:none">
                    <table class="table table-dark mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th style="width:140px">Títulos</th>
                                <th style="width:140px">Pontos</th>
                            </tr>
                        </thead>
                        <tbody id="editRankingBody"></tbody>
                    </table>
                </div>
                <div id="editRankingEmpty" style="display:none;text-align:center;padding:24px;color:var(--text-2);font-size:13px">
                    Sem times para esta liga.
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-r ghost" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn-r primary" id="btnSaveRanking"><i class="bi bi-save2"></i> Salvar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ══════════ SCRIPTS ══════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/sidebar.js"></script>
<script src="/js/pwa.js"></script>
<script>
    document.getElementById('menuBtn')?.addEventListener('click', () => {
        document.getElementById('sidebarToggle')?.click();
    });

    /* ── State ───────────────────────────────────── */
    const USER_LEAGUE    = '<?= htmlspecialchars($userLeague) ?>';
    const CURRENT_TEAM_ID = <?= $currentTeamId ?>;
    let currentLeague = USER_LEAGUE;

    /* ── Medal colors ────────────────────────────── */
    const MEDAL = ['gold','silver','bronze'];

    /* ── Tab switch ──────────────────────────────── */
    function setActiveTab(league) {
        document.querySelectorAll('.league-tab').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.league === league);
        });
    }

    /* ── Podium render (top 3) ───────────────────── */
    function renderPodium(ranking) {
        const wrap = document.getElementById('podiumWrap');
        if (!ranking || ranking.length < 2) { wrap.innerHTML = ''; return; }

        const top = ranking.slice(0, Math.min(3, ranking.length));
        // Reorder: 2nd, 1st, 3rd for visual podium
        const order = top.length >= 3 ? [top[1], top[0], top[2]] : [null, top[0], top[1] || null];
        const classes = top.length >= 3 ? ['second','first','third'] : ['second','first',''];
        const rankLabels = top.length >= 3 ? ['2º','1º','3º'] : ['2º','1º'];

        const items = order.map((team, i) => {
            if (!team) return '';
            const cls  = classes[i];
            const rank = rankLabels[i];
            const logo = team.photo_url
                ? `<img class="podium-logo" src="${escHtml(team.photo_url)}" alt="" onerror="this.src='/img/default-team.png'">`
                : `<div class="podium-logo" style="display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--text-3)">—</div>`;

            const pillarH = cls === 'first' ? '44px' : cls === 'second' ? '30px' : '20px';
            return `
            <div class="podium-item ${cls}">
                <div class="podium-card ${cls}">
                    ${logo}
                    <div class="podium-rank ${cls}">${rank}</div>
                    <div class="podium-name">${escHtml(team.team_name)}</div>
                    <div class="podium-owner">${escHtml(team.owner_name || '')}</div>
                    <div style="margin-top:10px">
                        <div class="podium-pts">${team.total_points || 0}</div>
                        <div class="podium-pts-label">pts</div>
                    </div>
                </div>
                <div class="podium-pillar" style="height:${pillarH}">${rank}</div>
            </div>`;
        });

        wrap.innerHTML = `<div class="podium">${items.join('')}</div>`;
    }

    /* ── Ranking table render ────────────────────── */
    function renderRankingTable(ranking, league) {
        const isRookie = league === 'ROOKIE';
        const tableRows = ranking.map((team, idx) => {
            const isMe     = CURRENT_TEAM_ID && +team.team_id === CURRENT_TEAM_ID;
            const isTop5   = isRookie && idx < 5;
            const medal    = MEDAL[idx] || 'normal';
            const rowCls   = [isMe ? 'is-me' : '', isTop5 && !isMe ? 'top5-rookie' : ''].filter(Boolean).join(' ');
            const logo     = team.photo_url
                ? `<img class="rank-logo" src="${escHtml(team.photo_url)}" alt="" onerror="this.src='/img/default-team.png'">`
                : `<div class="rank-logo" style="display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:var(--text-3)">—</div>`;

            const meBadge = isMe ? `<span class="tag green" style="margin-left:8px">Você</span>` : '';
            const promo   = isTop5 && !isMe ? `<span class="tag blue" style="margin-left:6px;font-size:9px">Promoção</span>` : '';

            return `
            <tr class="${rowCls}">
                <td style="text-align:center"><span class="rank-num ${medal}">${idx + 1}</span></td>
                <td>
                    <div class="rank-team-cell">
                        ${logo}
                        <div>
                            <div class="rank-team-name">${escHtml(team.team_name)}${meBadge}${promo}</div>
                            <div class="rank-owner">${escHtml(team.owner_name || '—')}</div>
                        </div>
                    </div>
                </td>
                <td class="hide-mobile"><span class="tag" style="background:var(--panel-3);color:var(--text-2);border:1px solid var(--border)">${escHtml(team.league)}</span></td>
                <td style="text-align:center">
                    <span class="tag blue">${team.total_titles || 0} <i class="bi bi-trophy" style="font-size:9px;margin-left:2px"></i></span>
                </td>
                <td style="text-align:center">
                    <span class="tag amber">${team.total_points || 0} <i class="bi bi-star-fill" style="font-size:9px;margin-left:2px"></i></span>
                </td>
            </tr>`;
        }).join('');

        return `
        <div class="ranking-panel">
            <div class="ranking-head">
                <div class="ranking-head-title">
                    <i class="bi bi-bar-chart-fill"></i>
                    Classificação · ${escHtml(league)}
                    <span style="font-size:11px;font-weight:400;color:var(--text-2);margin-left:4px">${ranking.length} times</span>
                </div>
                ${isRookie ? '<span style="font-size:11px;color:var(--blue);display:flex;align-items:center;gap:4px"><i class="bi bi-arrow-up-circle-fill"></i> Top 5 sobem de divisão</span>' : ''}
            </div>
            <div style="overflow-x:auto">
                <table class="rank-table">
                    <thead>
                        <tr>
                            <th class="center" style="width:50px">#</th>
                            <th>Time</th>
                            <th class="hide-mobile" style="width:90px">Liga</th>
                            <th class="center" style="width:90px">Títulos</th>
                            <th class="center" style="width:90px">Pontos</th>
                        </tr>
                    </thead>
                    <tbody>${tableRows}</tbody>
                </table>
            </div>
        </div>`;
    }

    /* ── Load ranking ────────────────────────────── */
    async function loadRanking(league = USER_LEAGUE) {
        currentLeague = league.toUpperCase();
        setActiveTab(currentLeague);

        const container = document.getElementById('rankingContainer');
        const podiumWrap = document.getElementById('podiumWrap');
        container.innerHTML = `
            <div class="loading-wrap">
                <div class="spinner-r"></div>
                <div style="font-size:13px;color:var(--text-2);margin-top:12px">Carregando ranking...</div>
            </div>`;
        podiumWrap.innerHTML = '';

        try {
            const res  = await fetch(`/api/history-points.php?action=get_ranking&league=${encodeURIComponent(currentLeague)}`);
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'Erro');

            const ranking = data.ranking?.[currentLeague] || [];

            if (!ranking.length) {
                podiumWrap.innerHTML = '';
                container.innerHTML = `
                    <div class="ranking-panel">
                        <div class="ranking-head"><div class="ranking-head-title"><i class="bi bi-bar-chart-fill"></i> Classificação · ${currentLeague}</div></div>
                        <div class="empty-r"><i class="bi bi-bar-chart"></i><p>Nenhum dado de ranking disponível para ${currentLeague}.</p></div>
                    </div>`;
                return;
            }

            renderPodium(ranking);
            container.innerHTML = renderRankingTable(ranking, currentLeague);

        } catch(e) {
            podiumWrap.innerHTML = '';
            container.innerHTML = `
                <div class="err-box">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    Erro ao carregar ranking: ${escHtml(e.message || 'Desconhecido')}
                </div>`;
        }
    }

    /* ── Util ────────────────────────────────────── */
    function escHtml(s) {
        return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ── Init ────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', () => loadRanking(USER_LEAGUE));

    <?php if ($isAdmin): ?>
    /* ── Admin edit modal ────────────────────────── */
    const editModal    = document.getElementById('editRankingModal');
    const editLeagueEl = document.getElementById('editRankingLeague');
    const editLoading  = document.getElementById('editRankingLoading');
    const editWrap     = document.getElementById('editRankingTableWrap');
    const editBody     = document.getElementById('editRankingBody');
    const editEmpty    = document.getElementById('editRankingEmpty');
    const btnSave      = document.getElementById('btnSaveRanking');

    editModal?.addEventListener('show.bs.modal', async () => {
        editLeagueEl.textContent = currentLeague;
        editLoading.style.display = 'block';
        editWrap.style.display   = 'none';
        editEmpty.style.display  = 'none';
        editBody.innerHTML = '';

        try {
            const res  = await fetch(`/api/history-points.php?action=get_ranking&league=${encodeURIComponent(currentLeague)}`);
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'Falha');
            const rows = data.ranking?.[currentLeague] || [];
            if (!rows.length) { editEmpty.style.display = 'block'; return; }
            rows.forEach(row => {
                editBody.innerHTML += `
                    <tr data-team-id="${row.team_id}">
                        <td><strong>${escHtml(row.team_name)}</strong><div style="font-size:11px;color:var(--text-2)">${escHtml(row.owner_name||'')}</div></td>
                        <td><input type="number" class="form-control js-edit-titles" value="${row.total_titles||0}" min="0" step="1"></td>
                        <td><input type="number" class="form-control js-edit-points" value="${row.total_points||0}" min="0" step="1"></td>
                    </tr>`;
            });
            editWrap.style.display = 'block';
        } catch(e) {
            editEmpty.textContent = 'Erro ao carregar ranking para edição.';
            editEmpty.style.display = 'block';
        } finally {
            editLoading.style.display = 'none';
        }
    });

    btnSave?.addEventListener('click', async () => {
        const rows = [...editBody.querySelectorAll('tr[data-team-id]')];
        const team_points = rows.map(tr => ({
            team_id: +tr.dataset.teamId,
            titles:  +(tr.querySelector('.js-edit-titles')?.value||0),
            points:  +(tr.querySelector('.js-edit-points')?.value||0),
        }));
        btnSave.disabled = true;
        btnSave.innerHTML = '<span style="display:inline-block;width:12px;height:12px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;animation:spin .6s linear infinite;margin-right:6px"></span> Salvando...';
        try {
            const res = await fetch('/api/history-points.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'save_ranking_totals', league: currentLeague, team_points })
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'Falha ao salvar');
            bootstrap.Modal.getInstance(editModal)?.hide();
            loadRanking(currentLeague);
        } catch(e) { alert(e.message || 'Erro ao salvar'); }
        finally { btnSave.disabled = false; btnSave.innerHTML = '<i class="bi bi-save2"></i> Salvar'; }
    });
    <?php endif; ?>
</script>
</body>
</html>