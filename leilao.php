<?php
require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/helpers.php';
requireAuth();

$user    = getUserSession();
$pdo     = db();
$is_admin = ($user['user_type'] ?? 'jogador') === 'admin';

// ── Time do usuário ──────────────────────────────────
$stmtTeam = $pdo->prepare('
    SELECT t.*, t.photo_url, t.city
    FROM teams t
    WHERE t.user_id = ?
    ORDER BY t.id DESC LIMIT 1
');
$stmtTeam->execute([$user['id']]);
$team    = $stmtTeam->fetch(PDO::FETCH_ASSOC) ?: null;
$team_id = $team ? (int)$team['id'] : null;

// ── League ID (para o JS) ────────────────────────────
$league_id = null;
if ($team && !empty($team['league'])) {
    try {
        $stmtLg = $pdo->prepare('SELECT id FROM leagues WHERE name = ? LIMIT 1');
        $stmtLg->execute([$team['league']]);
        $lgRow = $stmtLg->fetch(PDO::FETCH_ASSOC);
        if ($lgRow) $league_id = (int)$lgRow['id'];
    } catch (Exception $e) {}
}

// ── Temporada ────────────────────────────────────────
$seasonDisplayYear = date('Y');
try {
    $league = $team['league'] ?? $user['league'] ?? 'ELITE';
    $stmtSeason = $pdo->prepare('
        SELECT s.season_number, s.year, sp.start_year, sp.sprint_number
        FROM seasons s
        INNER JOIN sprints sp ON s.sprint_id = sp.id
        WHERE s.league = ? AND (s.status IS NULL OR s.status NOT IN (\'completed\'))
        ORDER BY s.created_at DESC LIMIT 1
    ');
    $stmtSeason->execute([$league]);
    $currentSeason = $stmtSeason->fetch(PDO::FETCH_ASSOC);
    if ($currentSeason) {
        $y = isset($currentSeason['start_year'], $currentSeason['season_number'])
            ? (int)$currentSeason['start_year'] + (int)$currentSeason['season_number'] - 1
            : (int)($currentSeason['year'] ?? date('Y'));
        $seasonDisplayYear = (string)$y;
    }
} catch (Exception $e) {}

// ── Ligas (para select do admin) ─────────────────────
$leagues = [];
if ($is_admin) {
    try {
        $leagues = $pdo->query('SELECT id, name FROM leagues ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

$userPhoto = getUserPhoto($user['photo_url'] ?? null);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/includes/head-pwa.php'; ?>
    <title>Leilão - FBA Manager</title>

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0a0a0c">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="FBA Manager">
    <link rel="apple-touch-icon" href="/img/icon-192.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/styles.css?v=20260411">

    <style>
        /* ── Design Tokens ───────────────────��─────────── */
        :root {
            --red:        #fc0025;
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
            --sidebar-w:  260px;
            --font:       'Poppins', sans-serif;
            --radius:     14px;
            --radius-sm:  10px;
            --ease:       cubic-bezier(.2,.8,.2,1);
            --t:          200ms;
        }
        :root[data-theme="light"] {
            --bg:        #f6f7fb;
            --panel:     #ffffff;
            --panel-2:   #f2f4f8;
            --panel-3:   #e9edf4;
            --border:    #e3e6ee;
            --border-md: #d7dbe6;
            --border-red:rgba(252,0,37,.18);
            --text:      #111217;
            --text-2:    #5b6270;
            --text-3:    #8b93a5;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-text-size-adjust: 100%; }
        html, body { height: 100%; background: var(--bg); color: var(--text); font-family: var(--font); -webkit-font-smoothing: antialiased; }
        body { overflow-x: hidden; }
        a, button { -webkit-tap-highlight-color: transparent; }

        /* ── Sidebar ────────────────────────��────────── */
        .sidebar {
            position: fixed; top: 0; left: 0;
            width: var(--sidebar-w); height: 100vh;
            background: var(--panel); border-right: 1px solid var(--border);
            display: flex; flex-direction: column; z-index: 300;
            transition: transform var(--t) var(--ease);
            overflow-y: auto; scrollbar-width: none;
        }
        .sidebar::-webkit-scrollbar { display: none; }
        .sb-brand {
            padding: 22px 18px 18px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 12px; flex-shrink: 0;
        }
        .sb-logo {
            width: 34px; height: 34px; border-radius: 9px; background: var(--red);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 13px; color: #fff; flex-shrink: 0;
        }
        .sb-brand-text { font-weight: 700; font-size: 15px; line-height: 1.1; }
        .sb-brand-text span { display: block; font-size: 11px; font-weight: 400; color: var(--text-2); }
        .sb-team {
            margin: 14px 14px 0; background: var(--panel-2); border: 1px solid var(--border);
            border-radius: var(--radius-sm); padding: 14px;
            display: flex; align-items: center; gap: 10px; flex-shrink: 0;
        }
        .sb-team img { width: 40px; height: 40px; border-radius: 9px; object-fit: cover; border: 1px solid var(--border-md); flex-shrink: 0; }
        .sb-team-name { font-size: 13px; font-weight: 600; color: var(--text); line-height: 1.2; }
        .sb-team-league { font-size: 11px; color: var(--red); font-weight: 600; }
        .sb-season {
            margin: 10px 14px 0; background: var(--red-soft); border: 1px solid var(--border-red);
            border-radius: 8px; padding: 8px 12px;
            display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;
        }
        .sb-season-label { font-size: 10px; font-weight: 600; letter-spacing: .8px; text-transform: uppercase; color: var(--text-2); }
        .sb-season-val { font-size: 14px; font-weight: 700; color: var(--red); }
        .sb-nav { flex: 1; padding: 12px 10px 8px; }
        .sb-section { font-size: 10px; font-weight: 600; letter-spacing: 1.2px; text-transform: uppercase; color: var(--text-3); padding: 12px 10px 5px; }
        .sb-nav a {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 10px; border-radius: var(--radius-sm);
            color: var(--text-2); font-size: 13px; font-weight: 500;
            text-decoration: none; margin-bottom: 2px;
            transition: all var(--t) var(--ease);
        }
        .sb-nav a i { font-size: 15px; width: 18px; text-align: center; flex-shrink: 0; }
        .sb-nav a:hover { background: var(--panel-2); color: var(--text); }
        .sb-nav a.active { background: var(--red-soft); color: var(--red); font-weight: 600; }
        .sb-nav a.active i { color: var(--red); }
        .sb-theme-toggle {
            margin: 0 14px 12px; padding: 8px 10px; border-radius: 10px;
            border: 1px solid var(--border); background: var(--panel-2); color: var(--text);
            display: flex; align-items: center; justify-content: center; gap: 8px;
            font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--t) var(--ease);
            width: calc(100% - 28px);
        }
        .sb-theme-toggle:hover { border-color: var(--border-red); color: var(--red); }
        .sb-footer {
            padding: 12px 14px; border-top: 1px solid var(--border);
            display: flex; align-items: center; gap: 10px; flex-shrink: 0;
        }
        .sb-avatar { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-md); flex-shrink: 0; }
        .sb-username { font-size: 12px; font-weight: 500; color: var(--text); flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sb-logout {
            width: 26px; height: 26px; border-radius: 7px; background: transparent;
            border: 1px solid var(--border); color: var(--text-2);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; cursor: pointer; transition: all var(--t) var(--ease);
            text-decoration: none; flex-shrink: 0;
        }
        .sb-logout:hover { background: var(--red-soft); border-color: var(--red); color: var(--red); }

        /* ── Topbar ─────────────────────���─────────────── */
        .topbar {
            display: none; position: fixed; top: 0; left: 0; right: 0;
            height: 54px; background: var(--panel); border-bottom: 1px solid var(--border);
            align-items: center; padding: 0 16px; gap: 12px; z-index: 240;
        }
        .topbar-title { font-weight: 700; font-size: 15px; flex: 1; }
        .topbar-title em { color: var(--red); font-style: normal; }
        .menu-btn {
            width: 34px; height: 34px; border-radius: 9px;
            background: var(--panel-2); border: 1px solid var(--border);
            color: var(--text); display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 17px;
        }
        .sb-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.65); backdrop-filter: blur(4px); z-index: 250; }
        .sb-overlay.show { display: block; }

        /* ── Main ─────────────────────────────────────── */
        .main { margin-left: var(--sidebar-w); width: calc(100% - var(--sidebar-w)); padding: 32px 40px 60px; }
        .page-top { display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; margin-bottom: 22px; flex-wrap: wrap; }
        .page-eyebrow { font-size: 12px; letter-spacing: .2em; text-transform: uppercase; color: var(--text-3); margin-bottom: 8px; }
        .page-title { font-size: 28px; font-weight: 800; font-family: var(--font); }
        .page-title i { color: var(--red); }
        .page-sub { color: var(--text-2); font-size: 13px; margin-top: 4px; }

        /* ── Panel ────────────────────────────────────── */
        .panel {
            background: var(--panel); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 20px 22px;
        }
        .panel + .panel { margin-top: 18px; }
        .panel-header {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; margin-bottom: 18px; flex-wrap: wrap;
        }
        .panel-title { font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .panel-title i { color: var(--red); font-size: 16px; }

        /* ── Tabs ─────────────────────────────────────── */
        .tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 22px; }
        .tab-btn {
            padding: 8px 18px; border-radius: 999px; font-size: 13px; font-weight: 600;
            border: 1px solid var(--border); background: var(--panel-2); color: var(--text-2);
            cursor: pointer; transition: all var(--t) var(--ease);
        }
        .tab-btn:hover { border-color: var(--border-red); color: var(--red); }
        .tab-btn.active { background: var(--red-soft); border-color: var(--border-red); color: var(--red); }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }

        /* ── Auction Card (leilao.js generated) ──────── */
        .auction-card {
            background: var(--panel-2); border: 1px solid var(--border);
            border-radius: var(--radius-sm); padding: 16px;
            transition: border-color var(--t) var(--ease);
        }
        .auction-card:hover { border-color: var(--border-md); }
        .auction-card.my-card { border-color: rgba(252,193,7,.3); }
        .auction-card-name { font-size: 15px; font-weight: 700; color: var(--red); margin-bottom: 6px; }
        .auction-card-meta { font-size: 12px; color: var(--text-2); display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
        .auction-card-meta span { display: flex; align-items: center; gap: 4px; }
        .auction-timer-wrap {
            display: flex; align-items: center; gap: 6px;
            background: var(--panel-3); border-radius: 8px; padding: 6px 10px;
            margin-bottom: 10px; font-size: 13px; font-weight: 700;
        }
        .auction-timer-wrap i { color: var(--red); }
        .auction-timer { font-variant-numeric: tabular-nums; }

        /* ── Field / Form ─────────────────��───────────── */
        .fgrid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0,1fr));
            gap: 12px;
        }
        .span-2  { grid-column: span 2; }
        .span-3  { grid-column: span 3; }
        .span-4  { grid-column: span 4; }
        .span-5  { grid-column: span 5; }
        .span-6  { grid-column: span 6; }
        .span-12 { grid-column: span 12; }
        .field { display: flex; flex-direction: column; gap: 5px; }
        .field label { font-size: 11px; color: var(--text-2); text-transform: uppercase; letter-spacing: .08em; }
        .field input, .field select, .field textarea {
            background: var(--panel-2); border: 1px solid var(--border);
            border-radius: 10px; padding: 9px 12px;
            color: var(--text); font-size: 14px; font-family: var(--font);
        }
        .field input:focus, .field select:focus, .field textarea:focus {
            outline: none; border-color: var(--border-red); box-shadow: 0 0 0 3px var(--red-soft);
        }
        .field input::placeholder, .field textarea::placeholder { color: var(--text-3); }
        .field select option { background: var(--panel-2); }

        /* ── Btn ──────────────────────────────────────── */
        .btn-primary-red {
            background: var(--red); border: none; color: #fff; font-weight: 700;
            border-radius: 10px; padding: 10px 18px; font-size: 13px; cursor: pointer;
            font-family: var(--font); transition: transform var(--t), box-shadow var(--t);
        }
        .btn-primary-red:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 8px 16px var(--red-glow); }
        .btn-primary-red:disabled { opacity: .45; cursor: not-allowed; }
        .btn-ghost {
            background: transparent; border: 1px solid var(--border);
            color: var(--text-2); border-radius: 10px; padding: 9px 16px;
            font-size: 13px; cursor: pointer; font-family: var(--font);
            transition: all var(--t) var(--ease);
        }
        .btn-ghost:hover { border-color: var(--border-red); color: var(--red); background: var(--red-soft); }

        /* ── List group (search results) ──────────────── */
        .list-group-item {
            background: var(--panel-2) !important; border-color: var(--border) !important;
            color: var(--text) !important; font-size: 13px;
        }
        .list-group-item:hover, .list-group-item-action:hover {
            background: var(--panel-3) !important; color: var(--text) !important;
        }

        /* ── Bootstrap overrides (leilao.js HTML) ──── */
        .text-orange     { color: var(--red)    !important; }
        .text-light-gray { color: var(--text-2) !important; }
        .text-muted      { color: var(--text-3) !important; }
        .text-white      { color: var(--text)   !important; }

        .table-dark {
            --bs-table-bg: transparent;
            --bs-table-border-color: var(--border);
            --bs-table-color: var(--text);
            --bs-table-hover-bg: var(--panel-3);
        }
        .table-dark > :not(caption) > * > * { color: var(--text); border-color: var(--border); }
        .table-dark thead th {
            color: var(--text-3) !important; font-size: 11px;
            text-transform: uppercase; letter-spacing: .12em;
        }

        .card {
            background: var(--panel-2) !important;
            border-color: var(--border) !important;
            border-radius: var(--radius-sm) !important;
            color: var(--text) !important;
        }
        .card-header {
            background: var(--panel-3) !important;
            border-color: var(--border) !important;
        }
        .card.border-warning { border-color: rgba(252,193,7,.35) !important; }
        .card.border-success { border-color: rgba(37,198,119,.35) !important; }
        .card.border-secondary { border-color: var(--border) !important; }

        .form-control, .form-select {
            background-color: var(--panel-2) !important;
            border-color: var(--border) !important;
            color: var(--text) !important;
            border-radius: 10px !important;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--border-red) !important;
            box-shadow: 0 0 0 3px var(--red-soft) !important;
            background-color: var(--panel-2) !important;
            color: var(--text) !important;
        }
        .form-select option { background: var(--panel-2); }
        .form-check-input:checked { background-color: var(--red) !important; border-color: var(--red) !important; }
        .form-check-label { color: var(--text-2) !important; }

        .modal-content {
            background: var(--panel) !important;
            border: 1px solid var(--border) !important;
            border-radius: var(--radius) !important;
            color: var(--text) !important;
        }
        .modal-header, .modal-footer { border-color: var(--border) !important; }
        .modal-title { color: var(--text) !important; font-weight: 700; }
        .btn-close { filter: invert(1) brightness(.7); }

        .btn-primary  { background: var(--red) !important; border-color: var(--red) !important; }
        .btn-primary:hover { background: #d4001f !important; }
        .btn-success  { background: #25c677 !important; border-color: #25c677 !important; }
        .btn-secondary { background: var(--panel-3) !important; border-color: var(--border-md) !important; color: var(--text-2) !important; }
        .btn-secondary:hover { background: var(--panel-2) !important; color: var(--text) !important; }
        .btn-info     { background: #2196f3 !important; border-color: #2196f3 !important; }
        .btn-outline-warning { border-color: #ffc107 !important; color: #ffc107 !important; }
        .btn-outline-warning:hover { background: rgba(255,193,7,.12) !important; }
        .btn-outline-danger  { border-color: var(--red) !important; color: var(--red) !important; }
        .btn-outline-danger:hover { background: var(--red-soft) !important; }
        .btn-outline-info    { border-color: #2196f3 !important; color: #2196f3 !important; }
        .btn-outline-info:hover { background: rgba(33,150,243,.12) !important; }
        .btn-outline-orange  { border: 1px solid var(--border-red) !important; color: var(--red) !important; background: transparent; border-radius: 10px; }
        .btn-outline-orange:hover { background: var(--red-soft) !important; }
        .btn-orange   { background: var(--red) !important; border: none !important; color: #fff !important; border-radius: 10px; font-weight: 700; }
        .btn-orange:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 8px 16px var(--red-glow); }
        .btn-orange:disabled { opacity: .45; cursor: not-allowed; }

        .alert-info    { background: var(--panel-2) !important; border-color: var(--border) !important; color: var(--text) !important; }
        .alert-warning { background: rgba(255,193,7,.10) !important; border-color: rgba(255,193,7,.3) !important; color: var(--text) !important; }
        .alert-danger  { background: var(--red-soft) !important; border-color: var(--border-red) !important; color: var(--text) !important; }

        .badge.bg-info     { background: #2196f3 !important; }
        .badge.bg-warning  { background: #ffc107 !important; color: #000 !important; }
        .badge.bg-success  { background: #25c677 !important; }
        .badge.bg-secondary{ background: var(--panel-3) !important; color: var(--text-2) !important; }
        .badge.bg-danger   { background: var(--red) !important; }
        .badge.bg-dark     { background: var(--panel-3) !important; color: var(--text-2) !important; }
        .badge.bg-primary  { background: var(--red) !important; }

        hr { border-color: var(--border) !important; opacity: 1 !important; }

        /* ── Responsive ─────────────────��─────────────── */
        @media (max-width: 820px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .topbar { display: flex; }
            .main { margin-left: 0; width: 100%; padding: 70px 16px 40px; }
            .fgrid { grid-template-columns: 1fr 1fr; }
            .span-2, .span-3, .span-4, .span-5, .span-6 { grid-column: span 2; }
            .span-12 { grid-column: span 2; }
        }
        @media (max-width: 480px) {
            .fgrid { grid-template-columns: 1fr; }
            .span-2, .span-3, .span-4, .span-5, .span-6, .span-12 { grid-column: span 1; }
        }
    </style>
</head>
<body>
<div class="app">

    <!-- ══════════════════════════════════════════════
         SIDEBAR
    ══════════════════════════════════════════════ -->
    <aside class="sidebar" id="sidebar">

        <div class="sb-brand">
            <div class="sb-logo">FBA</div>
            <div class="sb-brand-text">
                FBA Manager
                <span>Painel do GM</span>
            </div>
        </div>

        <?php if ($team): ?>
        <div class="sb-team">
            <img src="<?= htmlspecialchars($team['photo_url'] ?? '/img/default-team.png') ?>"
                 alt="<?= htmlspecialchars($team['name'] ?? '') ?>"
                 onerror="this.src='/img/default-team.png'">
            <div>
                <div class="sb-team-name"><?= htmlspecialchars(($team['city'] ?? '') . ' ' . ($team['name'] ?? '')) ?></div>
                <div class="sb-team-league"><?= htmlspecialchars($team['league'] ?? '') ?></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($currentSeason): ?>
        <div class="sb-season">
            <div>
                <div class="sb-season-label">Temporada</div>
                <div class="sb-season-val"><?= htmlspecialchars($seasonDisplayYear) ?></div>
            </div>
            <div style="text-align:right">
                <div class="sb-season-label">Sprint</div>
                <div class="sb-season-val"><?= (int)($currentSeason['sprint_number'] ?? 1) ?></div>
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
            <a href="/free-agency.php"><i class="bi bi-coin"></i> Free Agency</a>
            <a href="/leilao.php" class="active"><i class="bi bi-hammer"></i> Leilão</a>
            <a href="/drafts.php"><i class="bi bi-trophy"></i> Draft</a>

            <div class="sb-section">Liga</div>
            <a href="/rankings.php"><i class="bi bi-bar-chart-fill"></i> Rankings</a>
            <a href="/history.php"><i class="bi bi-clock-history"></i> Histórico</a>
            <a href="/diretrizes.php"><i class="bi bi-clipboard-data"></i> Diretrizes</a>
            <a href="/ouvidoria.php"><i class="bi bi-chat-dots"></i> Ouvidoria</a>
            <a href="https://games.fbabrasil.com.br/auth/login.php" target="_blank" rel="noopener"><i class="bi bi-controller"></i> FBA Games</a>

            <?php if ($is_admin): ?>
            <div class="sb-section">Admin</div>
            <a href="/admin.php"><i class="bi bi-shield-lock-fill"></i> Admin</a>
            <a href="/temporadas.php"><i class="bi bi-calendar3"></i> Temporadas</a>
            <?php endif; ?>

            <div class="sb-section">Conta</div>
            <a href="/settings.php"><i class="bi bi-gear-fill"></i> Configurações</a>
        </nav>

        <button class="sb-theme-toggle" type="button" id="themeToggle">
            <i class="bi bi-moon"></i>
            <span>Modo escuro</span>
        </button>

        <div class="sb-footer">
            <img src="<?= htmlspecialchars(getUserPhoto($user['photo_url'] ?? null)) ?>"
                 alt="<?= htmlspecialchars($user['name'] ?? '') ?>"
                 class="sb-avatar"
                 onerror="this.src='https://ui-avatars.com/api/?name=<?= rawurlencode($user['name'] ?? 'U') ?>&background=1c1c21&color=fc0025'">
            <span class="sb-username"><?= htmlspecialchars($user['name'] ?? '') ?></span>
            <a href="/logout.php" class="sb-logout" title="Sair"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </aside>

    <!-- Overlay mobile -->
    <div class="sb-overlay" id="sbOverlay"></div>

    <!-- Topbar mobile -->
    <header class="topbar">
        <button class="menu-btn" id="menuBtn"><i class="bi bi-list"></i></button>
        <div class="topbar-title">FBA <em>Leilão</em></div>
        <?php if ($currentSeason): ?>
        <span style="font-size:11px;font-weight:700;color:var(--red)"><?= htmlspecialchars($seasonDisplayYear) ?></span>
        <?php endif; ?>
    </header>

<!-- ── Main ───────────────────────────────────────── -->
<main class="main">
    <!-- Page top -->
    <div class="page-top">
        <div>
            <div class="page-eyebrow">FBA Manager</div>
            <h1 class="page-title"><i class="bi bi-hammer"></i> Leilão</h1>
            <p class="page-sub">Leilões de jogadores em andamento e histórico de trocas</p>
        </div>
        <?php if ($is_admin): ?>
        <span style="background:var(--red-soft);border:1px solid var(--border-red);color:var(--red);border-radius:999px;padding:4px 14px;font-size:12px;font-weight:700;">
            <i class="bi bi-shield-lock-fill me-1"></i>Admin
        </span>
        <?php endif; ?>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn active" id="tab-btn-ativos"
                data-bs-toggle="tab" data-bs-target="#auction-active">
            <i class="bi bi-hammer me-1"></i>Leilões ativos
        </button>
        <?php if ($is_admin): ?>
        <button class="tab-btn" id="tab-btn-admin"
                data-bs-toggle="tab" data-bs-target="#auction-admin">
            <i class="bi bi-shield-lock-fill me-1"></i>Admin leilão
        </button>
        <?php endif; ?>
    </div>

    <!-- Tab content -->
    <div class="tab-content-wrap">

        <!-- ── Aba: Leilões Ativos ─────────────────── -->
        <div class="tab-pane fade show active" id="auction-active" role="tabpanel">

            <!-- Leilões em andamento -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><i class="bi bi-hammer"></i>Leilões em andamento</div>
                    <button class="btn-ghost" style="padding:7px 14px;font-size:12px;" onclick="carregarLeiloesAtivos()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Atualizar
                    </button>
                </div>
                <div id="leiloesAtivosContainer">
                    <div class="text-center py-4">
                        <div class="spinner-border" style="color:var(--red);width:1.6rem;height:1.6rem;" role="status"></div>
                    </div>
                </div>
            </div>

            <?php if ($team_id): ?>
            <!-- Propostas recebidas -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><i class="bi bi-inbox"></i>Propostas recebidas</div>
                </div>
                <div id="propostasRecebidasContainer">
                    <div class="text-center py-4">
                        <div class="spinner-border" style="color:var(--red);width:1.6rem;height:1.6rem;" role="status"></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Histórico -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><i class="bi bi-clock-history"></i>Histórico de leilões</div>
                </div>
                <div id="leiloesHistoricoContainer">
                    <div class="text-center py-4">
                        <div class="spinner-border" style="color:var(--red);width:1.6rem;height:1.6rem;" role="status"></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($is_admin): ?>
        <!-- ── Aba: Admin ──────────────────────────── -->
        <div class="tab-pane fade" id="auction-admin" role="tabpanel">

            <!-- Formulário de cadastro -->
            <div class="panel">
                <div class="panel-title" style="margin-bottom:18px;"><i class="bi bi-plus-circle"></i>Cadastrar jogador no leilão</div>

                <div class="fgrid" style="margin-bottom:16px;">
                    <!-- Liga -->
                    <div class="field span-3">
                        <label for="selectLeague">Liga</label>
                        <select id="selectLeague">
                            <option value="">Selecione...</option>
                            <?php foreach ($leagues as $lg): ?>
                            <option value="<?= (int)$lg['id'] ?>" data-league-name="<?= htmlspecialchars($lg['name']) ?>">
                                <?= htmlspecialchars($lg['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Modo -->
                    <div class="field span-6" style="justify-content:flex-end;">
                        <label>Modo</label>
                        <div style="display:flex;gap:18px;align-items:center;height:42px;">
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text);cursor:pointer;">
                                <input class="form-check-input" type="radio" name="auctionMode" id="auctionModeSearch" value="search" checked>
                                Buscar jogador
                            </label>
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text);cursor:pointer;">
                                <input class="form-check-input" type="radio" name="auctionMode" id="auctionModeCreate" value="create">
                                Criar jogador
                            </label>
                        </div>
                    </div>

                    <!-- Botão iniciar -->
                    <div class="span-3" style="display:flex;align-items:flex-end;">
                        <button id="btnCadastrarLeilao" class="btn-primary-red w-100" disabled style="padding:10px;">
                            <i class="bi bi-play-fill me-1"></i>Iniciar leilão (20 min)
                        </button>
                    </div>
                </div>

                <!-- Área busca -->
                <div id="auctionSearchArea" style="border-top:1px solid var(--border);padding-top:16px;">
                    <div class="fgrid">
                        <div class="field span-6">
                            <label for="auctionPlayerSearch">Buscar jogador na liga</label>
                            <input type="text" id="auctionPlayerSearch" placeholder="Digite o nome do jogador">
                        </div>
                        <div class="span-2" style="display:flex;align-items:flex-end;">
                            <button class="btn-ghost w-100" id="auctionSearchBtn" style="padding:10px;">
                                <i class="bi bi-search me-1"></i>Buscar
                            </button>
                        </div>
                    </div>
                    <div id="auctionPlayerResults" class="list-group mt-2" style="display:none;"></div>
                    <div id="auctionSelectedLabel" class="mt-2" style="display:none;font-size:13px;color:var(--red);"></div>
                    <input type="hidden" id="auctionSelectedPlayerId">
                    <input type="hidden" id="auctionSelectedTeamId">
                </div>

                <!-- Área criar -->
                <div id="auctionCreateArea" style="display:none;border-top:1px solid var(--border);padding-top:16px;">
                    <p style="font-size:12px;color:var(--text-2);margin-bottom:14px;">
                        <i class="bi bi-info-circle me-1" style="color:var(--red);"></i>
                        O jogador será criado diretamente no leilão, sem time de origem.
                    </p>
                    <div class="fgrid" style="margin-bottom:14px;">
                        <div class="field span-4">
                            <label for="auctionPlayerName">Nome</label>
                            <input type="text" id="auctionPlayerName" placeholder="Nome do jogador">
                        </div>
                        <div class="field span-2">
                            <label for="auctionPlayerPosition">Posição</label>
                            <select id="auctionPlayerPosition">
                                <option value="PG">PG</option>
                                <option value="SG">SG</option>
                                <option value="SF">SF</option>
                                <option value="PF">PF</option>
                                <option value="C">C</option>
                            </select>
                        </div>
                        <div class="field span-2">
                            <label for="auctionPlayerAge">Idade</label>
                            <input type="number" id="auctionPlayerAge" value="25" min="16" max="45">
                        </div>
                        <div class="field span-2">
                            <label for="auctionPlayerOvr">OVR</label>
                            <input type="number" id="auctionPlayerOvr" value="70" min="40" max="99">
                        </div>
                        <div class="span-2" style="display:flex;align-items:flex-end;">
                            <button class="btn-ghost w-100" id="btnCriarJogadorLeilao" style="padding:10px;">
                                <i class="bi bi-plus-circle me-1"></i>Criar e pendente
                            </button>
                        </div>
                    </div>

                    <!-- Lista de pendentes criados -->
                    <div style="border-top:1px solid var(--border);padding-top:14px;">
                        <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                            <i class="bi bi-person-plus" style="color:var(--red);"></i>
                            Jogadores criados (pendentes)
                        </div>
                        <div id="auctionTempList">
                            <p style="font-size:13px;color:var(--text-2);">Nenhum jogador criado ainda.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista admin de leilões -->
            <div class="panel">
                <div class="panel-title" style="margin-bottom:16px;"><i class="bi bi-list-ul"></i>Todos os leilões</div>
                <div id="adminLeiloesContainer">
                    <div class="text-center py-4">
                        <div class="spinner-border" style="color:var(--red);width:1.6rem;height:1.6rem;" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /tab-content-wrap -->
</main>

<!-- ── Modal: Enviar Proposta ─────────────────────── -->
<div class="modal fade" id="modalProposta" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-send me-2" style="color:var(--red);"></i>Enviar Proposta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="leilaoIdProposta">

                <div style="background:var(--panel-2);border:1px solid var(--border);border-radius:10px;padding:12px 16px;margin-bottom:16px;">
                    <div style="font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:var(--text-2);margin-bottom:4px;">Jogador em leilão</div>
                    <div id="jogadorLeilaoNome" style="font-size:16px;font-weight:700;color:var(--red);"></div>
                </div>

                <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:10px;">
                    <i class="bi bi-people-fill me-1" style="color:var(--red);"></i>Jogadores que você oferece
                </div>
                <div id="meusJogadoresParaTroca" class="mb-3">
                    <p style="color:var(--text-2);font-size:13px;">Carregando...</p>
                </div>

                <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:10px;">
                    <i class="bi bi-ticket-detailed me-1" style="color:var(--red);"></i>Picks que você oferece
                    <span style="font-weight:400;font-size:12px;color:var(--text-2);"> (opcional)</span>
                </div>
                <div id="minhasPicksParaTroca" class="mb-3">
                    <p style="color:var(--text-2);font-size:13px;">Carregando...</p>
                </div>

                <div class="field mb-3">
                    <label for="notasProposta">O que você oferece na proposta</label>
                    <textarea id="notasProposta" rows="3" placeholder="Ex: 1 jogador + escolha de draft ou moedas"></textarea>
                </div>
                <div class="field">
                    <label for="obsProposta">Observações adicionais <span style="color:var(--text-3);">(opcional)</span></label>
                    <textarea id="obsProposta" rows="2" placeholder="Detalhes extras..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnEnviarProposta">
                    <i class="bi bi-send me-1"></i>Enviar proposta
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal: Ver Propostas ───────────────────────── -->
<div class="modal fade" id="modalVerPropostas" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-inbox me-2" style="color:var(--red);"></i>Propostas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="leilaoIdVerPropostas">
                <div id="listaPropostasRecebidas">
                    <p style="color:var(--text-2);font-size:13px;">Carregando...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- ── Scripts ────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* ── Theme ────────────────────────────────────────── */
(function () {
    const saved = localStorage.getItem('fba-theme');
    const preferLight = window.matchMedia?.('(prefers-color-scheme: light)').matches;
    document.documentElement.dataset.theme = saved || (preferLight ? 'light' : 'dark');
})();

document.addEventListener('DOMContentLoaded', function () {
    const key  = 'fba-theme';
    const root = document.documentElement;

    const setBtn = (btn, theme) => {
        if (!btn) return;
        const isLight = theme === 'light';
        btn.setAttribute('aria-pressed', String(isLight));
        btn.innerHTML = isLight
            ? '<i class="bi bi-moon-stars-fill"></i><span>Tema escuro</span>'
            : '<i class="bi bi-sun-fill"></i><span>Tema claro</span>';
    };
    ['themeToggle','themeToggleMobile'].forEach(id => {
        const btn = document.getElementById(id);
        setBtn(btn, root.dataset.theme);
        btn?.addEventListener('click', () => {
            const next = root.dataset.theme === 'light' ? 'dark' : 'light';
            root.dataset.theme = next;
            localStorage.setItem(key, next);
            setBtn(document.getElementById('themeToggle'), next);
        });
    });

    /* ── Sidebar mobile ───────────────────────── */
    const sidebar   = document.getElementById('sidebar');
    const sbOverlay = document.getElementById('sbOverlay');
    document.getElementById('menuBtn')?.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        sbOverlay.classList.toggle('show');
    });
    sbOverlay?.addEventListener('click', () => {
        sidebar.classList.remove('open');
        sbOverlay.classList.remove('show');
    });
    sidebar?.querySelectorAll('.sb-nav a').forEach(a => {
        a.addEventListener('click', () => {
            if (window.innerWidth <= 820) {
                sidebar.classList.remove('open');
                sbOverlay.classList.remove('show');
            }
        });
    });

    /* ── Tabs ───────────────��─────────────────── */
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });
    /* Sync Bootstrap tab shown event → custom .active on .tab-btn */
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(trigger => {
        trigger.addEventListener('shown.bs.tab', e => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll(`.tab-btn[data-bs-target="${e.target.dataset.bsTarget}"]`)
                .forEach(b => b.classList.add('active'));
        });
    });
});
</script>

<script>
/* ── JS vars para leilao.js ─────────────────��─────── */
const isAdmin       = <?= $is_admin ? 'true' : 'false' ?>;
const userTeamId    = <?= $team_id  ? (int)$team_id    : 'null' ?>;
const userTeamName  = '<?= addslashes($team ? ($team['city'] . ' ' . $team['name']) : '') ?>';
const currentLeagueId = <?= $league_id ? (int)$league_id : 'null' ?>;
</script>

<script src="/js/leilao.js?v=<?= time() ?>"></script>
<script src="/js/pwa.js"></script>
</div><!-- /.app -->
</body>
</html>
