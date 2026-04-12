<?php
require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/helpers.php';
requireAuth();

$user = getUserSession();
$pdo = db();

$stmtTeam = $pdo->prepare('SELECT * FROM teams WHERE user_id = ? LIMIT 1');
$stmtTeam->execute([$user['id']]);
$team = $stmtTeam->fetch() ?: null;
$teamId = $team['id'] ?? null;

// Buscar limite de trades da liga
$maxTrades = 10;
$tradesEnabled = 1;
if ($team) {
    $stmtSettings = $pdo->prepare('SELECT max_trades, trades_enabled FROM league_settings WHERE league = ?');
    $stmtSettings->execute([$team['league']]);
    $settings = $stmtSettings->fetch();
    $maxTrades = $settings['max_trades'] ?? 10;
    $tradesEnabled = $settings['trades_enabled'] ?? 1;
}

$currentSeason = null;
$currentSeasonYear = null;
$seasonDisplayYear = null;
if (!empty($team['league'])) {
    try {
        $stmtSeason = $pdo->prepare('
            SELECT s.season_number, s.year, sp.start_year, sp.sprint_number
            FROM seasons s
            LEFT JOIN sprints sp ON s.sprint_id = sp.id
            WHERE s.league = ? AND (s.status IS NULL OR s.status NOT IN ("completed"))
            ORDER BY s.created_at DESC
            LIMIT 1
        ');
        $stmtSeason->execute([$team['league']]);
        $currentSeason = $stmtSeason->fetch();
        if ($currentSeason && isset($currentSeason['start_year'], $currentSeason['season_number'])) {
            $currentSeasonYear = (int)$currentSeason['start_year'] + (int)$currentSeason['season_number'] - 1;
        } elseif ($currentSeason && isset($currentSeason['year'])) {
            $currentSeasonYear = (int)$currentSeason['year'];
        }
    } catch (Exception $e) {
        $currentSeasonYear = null;
    }
}
if (!$currentSeasonYear) {
    $currentSeasonYear = (int)date('Y');
}
$seasonDisplayYear = (string)$currentSeasonYear;

function syncTeamTradeCounter(PDO $pdo, int $teamId): int
{
    try {
        $stmt = $pdo->prepare('SELECT current_cycle, trades_cycle, trades_used FROM teams WHERE id = ?');
        $stmt->execute([$teamId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return 0;
        $currentCycle = (int)($row['current_cycle'] ?? 0);
        $tradesCycle  = (int)($row['trades_cycle']  ?? 0);
        $tradesUsed   = (int)($row['trades_used']   ?? 0);
        if ($currentCycle > 0 && $tradesCycle <= 0) {
            $pdo->prepare('UPDATE teams SET trades_cycle = ? WHERE id = ?')->execute([$currentCycle, $teamId]);
            return $tradesUsed;
        }
        if ($currentCycle > 0 && $tradesCycle > 0 && $tradesCycle !== $currentCycle) {
            $pdo->prepare('UPDATE teams SET trades_used = 0, trades_cycle = ? WHERE id = ?')->execute([$currentCycle, $teamId]);
            return 0;
        }
        return $tradesUsed;
    } catch (Exception) {
        return 0;
    }
}

$tradeCount = (int)($team['trades_used'] ?? 0);
$tradesLeft = max(0, $maxTrades - $tradeCount);
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/includes/head-pwa.php'; ?>
    <title>Trades - FBA Manager</title>

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#07070a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="FBA Manager">
    <link rel="apple-touch-icon" href="/img/icon-192.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/styles.css?v=20260410">

    <style>
        /* ── Design Tokens ─────────────────────────────── */
        :root {
            --red: #fc0025;
            --red-2: #ff2a44;
            --red-soft: rgba(252,0,37,.10);
            --red-glow: rgba(252,0,37,.18);
            --bg: #07070a;
            --panel: #101013;
            --panel-2: #16161a;
            --panel-3: #1c1c21;
            --border: rgba(255,255,255,.06);
            --border-strong: rgba(255,255,255,.10);
            --border-red: rgba(252,0,37,.22);
            --text: #f0f0f3;
            --text-2: #868690;
            --text-3: #48484f;
            --radius: 14px;
            --radius-sm: 10px;
            --radius-xs: 6px;
            --sidebar-w: 260px;
            --font-display: 'Poppins', sans-serif;
            --font-body: 'Poppins', sans-serif;
            --ease: cubic-bezier(.2,.8,.2,1);
            --t: 200ms;
        }
        :root[data-theme="light"] {
            --bg: #f6f7fb;
            --panel: #ffffff;
            --panel-2: #f2f4f8;
            --panel-3: #e9edf4;
            --border: #e3e6ee;
            --border-strong: #d7dbe6;
            --border-red: rgba(252,0,37,.18);
            --text: #111217;
            --text-2: #5b6270;
            --text-3: #8b93a5;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-text-size-adjust: 100%; }
        html, body { height: 100%; background: var(--bg); color: var(--text); font-family: var(--font-body); -webkit-font-smoothing: antialiased; }
        body { overflow-x: hidden; }
        a, button { -webkit-tap-highlight-color: transparent; }

        /* ── Layout ────────────────────────────────────── */
        .app { display: flex; min-height: 100vh; }

        /* ── Sidebar ───────────────────────────────────── */
        .sidebar {
            position: fixed; top: 0; left: 0;
            width: var(--sidebar-w); height: 100vh;
            background: var(--panel);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            z-index: 300;
            transition: transform var(--t) var(--ease);
        }
        .sb-brand {
            padding: 24px 20px 20px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 12px;
        }
        .sb-logo {
            width: 36px; height: 36px; border-radius: 10px;
            background: var(--red);
            display: flex; align-items: center; justify-content: center;
            font-family: var(--font-display); font-weight: 800; font-size: 14px;
            color: #fff; letter-spacing: -0.5px; flex-shrink: 0;
        }
        .sb-brand-text { font-family: var(--font-display); font-weight: 800; font-size: 16px; color: var(--text); line-height: 1.1; }
        .sb-brand-text span { display: block; font-size: 11px; font-weight: 400; color: var(--text-2); }

        .sb-team {
            margin: 16px 14px; background: var(--panel-2);
            border: 1px solid var(--border); border-radius: var(--radius-sm);
            padding: 14px; display: flex; align-items: center; gap: 10px;
        }
        .sb-team img { width: 38px; height: 38px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border-strong); flex-shrink: 0; }
        .sb-team-name { font-weight: 600; font-size: 13px; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sb-team-league { font-size: 11px; color: var(--text-2); }

        .sb-season {
            margin: 0 14px 8px; padding: 10px 12px;
            border-radius: var(--radius-sm);
            background: var(--panel-2); border: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
        }
        .sb-season-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-3); font-weight: 600; }
        .sb-season-val { font-size: 13px; font-weight: 700; color: var(--text); }

        .sb-nav { flex: 1; overflow-y: auto; padding: 8px 10px; scrollbar-width: none; }
        .sb-nav::-webkit-scrollbar { display: none; }
        .sb-section { font-size: 10px; font-weight: 600; letter-spacing: 1.2px; text-transform: uppercase; color: var(--text-3); padding: 12px 10px 6px; }
        .sb-nav a {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 10px; border-radius: var(--radius-sm);
            color: var(--text-2); font-size: 14px; font-weight: 500;
            text-decoration: none; transition: all var(--t) var(--ease); margin-bottom: 2px;
        }
        .sb-nav a i { font-size: 16px; width: 20px; text-align: center; flex-shrink: 0; }
        .sb-nav a:hover { background: var(--panel-2); color: var(--text); }
        .sb-nav a.active { background: var(--red-soft); color: var(--red); font-weight: 600; }
        .sb-nav a.active i { color: var(--red); }

        .sb-theme-toggle {
            margin: 0 14px 12px; padding: 8px 10px; border-radius: 10px;
            border: 1px solid var(--border); background: var(--panel-2); color: var(--text);
            display: flex; align-items: center; justify-content: center; gap: 8px;
            font-size: 12px; font-weight: 600; cursor: pointer;
            transition: all var(--t) var(--ease); font-family: var(--font-body); width: calc(100% - 28px);
        }
        .sb-theme-toggle:hover { border-color: var(--border-red); color: var(--red); }

        .sb-footer { padding: 14px; border-top: 1px solid var(--border); display: flex; align-items: center; gap: 10px; }
        .sb-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-strong); flex-shrink: 0; }
        .sb-username { font-size: 13px; font-weight: 500; color: var(--text); flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sb-logout {
            width: 28px; height: 28px; border-radius: 8px;
            background: transparent; border: 1px solid var(--border);
            color: var(--text-2); display: flex; align-items: center; justify-content: center;
            font-size: 13px; cursor: pointer; transition: all var(--t) var(--ease); text-decoration: none; flex-shrink: 0;
        }
        .sb-logout:hover { background: var(--red-soft); border-color: var(--red); color: var(--red); }

        /* ── Topbar mobile ─────────────────────────────── */
        .topbar {
            display: none; position: fixed; top: 0; left: 0; right: 0;
            height: 56px; background: var(--panel); border-bottom: 1px solid var(--border);
            align-items: center; padding: 0 16px; gap: 12px; z-index: 199;
        }
        .topbar-title { font-family: var(--font-display); font-weight: 800; font-size: 16px; color: var(--text); flex: 1; }
        .topbar-title em { color: var(--red); font-style: normal; }
        .menu-btn {
            width: 36px; height: 36px; border-radius: 10px;
            background: var(--panel-2); border: 1px solid var(--border);
            color: var(--text); display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 18px;
        }

        .sb-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.65); backdrop-filter: blur(4px); z-index: 250; }
        .sb-overlay.show { display: block; }

        /* ── Main ──────────────────────────────────────── */
        .main { margin-left: var(--sidebar-w); min-height: 100vh; display: flex; flex-direction: column; width: calc(100% - var(--sidebar-w)); }

        /* ── Page Header ───────────────────────────────── */
        .page-top {
            padding: 32px 32px 0;
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 16px; flex-wrap: wrap;
        }
        .page-eyebrow { font-size: 11px; font-weight: 600; letter-spacing: 1.4px; text-transform: uppercase; color: var(--red); margin-bottom: 6px; }
        .page-title { font-family: var(--font-display); font-size: 28px; font-weight: 800; color: var(--text); line-height: 1.1; }
        .page-sub { font-size: 13px; color: var(--text-2); margin-top: 4px; }
        .page-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        /* ── Stats Strip ───────────────────────────────── */
        .stats-strip { display: flex; gap: 12px; padding: 24px 32px 0; flex-wrap: wrap; }
        .stat-pill {
            background: var(--panel); border: 1px solid var(--border);
            border-radius: 12px; padding: 12px 18px;
            display: flex; align-items: center; gap: 10px;
        }
        .stat-pill-icon {
            width: 32px; height: 32px; border-radius: 8px;
            background: var(--red-soft); display: flex; align-items: center; justify-content: center;
            color: var(--red); font-size: 14px; flex-shrink: 0;
        }
        .stat-pill-icon.green { background: rgba(22,163,74,.12); color: #4ade80; }
        .stat-pill-icon.yellow { background: rgba(245,158,11,.12); color: #f59e0b; }
        .stat-pill-val { font-family: var(--font-display); font-weight: 700; font-size: 17px; color: var(--text); line-height: 1; }
        .stat-pill-label { font-size: 11px; color: var(--text-2); margin-top: 2px; }

        /* ── Tabs ──────────────────────────────────────── */
        .tabs-wrap { padding: 20px 32px 0; }
        .tabs-bar {
            display: flex; gap: 2px;
            background: var(--panel); border: 1px solid var(--border);
            border-radius: var(--radius-sm); padding: 4px;
            overflow-x: auto; scrollbar-width: none;
        }
        .tabs-bar::-webkit-scrollbar { display: none; }
        .tab-btn {
            display: flex; align-items: center; gap: 7px;
            padding: 8px 14px; border-radius: 8px; border: none;
            background: transparent; color: var(--text-2);
            font-family: var(--font-body); font-size: 13px; font-weight: 500;
            cursor: pointer; transition: all var(--t) var(--ease);
            white-space: nowrap; flex-shrink: 0;
        }
        .tab-btn:hover { background: var(--panel-2); color: var(--text); }
        .tab-btn.active { background: var(--red-soft); color: var(--red); font-weight: 600; }
        .tab-btn i { font-size: 14px; }

        /* ── Content Area ──────────────────────────────── */
        .content-area { padding: 20px 32px 40px; flex: 1; }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }

        /* ── Panel Card ────────────────────────────────── */
        .panel-card {
            background: var(--panel); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 24px;
        }
        .panel-header {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; flex-wrap: wrap; margin-bottom: 20px;
        }
        .panel-title { font-family: var(--font-display); font-weight: 700; font-size: 16px; color: var(--text); }
        .panel-sub { font-size: 12px; color: var(--text-2); margin-top: 3px; }

        /* ── Search / Filter ───────────────────────────── */
        .filter-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
        .filter-bar input, .filter-bar select {
            background: var(--panel-2); border: 1px solid var(--border);
            color: var(--text); border-radius: 10px; padding: 9px 14px;
            font-family: var(--font-body); font-size: 13px; outline: none;
            transition: border-color var(--t) var(--ease);
        }
        .filter-bar input { flex: 1; min-width: 200px; }
        .filter-bar input::placeholder { color: var(--text-3); }
        .filter-bar input:focus, .filter-bar select:focus { border-color: var(--red); }
        .filter-bar select { cursor: pointer; }

        /* ── Trade cards from JS ───────────────────────── */
        #receivedTradesList .trade-card,
        #sentTradesList .trade-card,
        #historyTradesList .trade-card,
        #leagueTradesList .trade-card {
            background: var(--panel-2) !important;
            border: 1px solid var(--border) !important;
            border-radius: var(--radius-sm) !important;
            margin-bottom: 10px;
        }

        /* ── Player card (trade-list) ──────────────────── */
        .player-card {
            background: var(--panel-2); border: 1px solid var(--border);
            border-radius: var(--radius-sm); padding: 14px 16px;
            margin-bottom: 8px; display: flex; align-items: center; gap: 14px;
            transition: border-color var(--t) var(--ease), transform var(--t) var(--ease);
        }
        .player-card:hover { border-color: var(--border-strong); transform: translateY(-1px); }
        .player-ovr {
            width: 40px; height: 40px; border-radius: 10px;
            background: var(--red-soft); color: var(--red);
            display: flex; align-items: center; justify-content: center;
            font-family: var(--font-display); font-weight: 700; font-size: 15px;
            flex-shrink: 0;
        }
        .player-name { font-weight: 600; font-size: 14px; color: var(--text); }
        .player-meta { font-size: 12px; color: var(--text-2); margin-top: 2px; }

        /* ── Rumor card ────────────────────────────────── */
        .rumor-card {
            background: var(--panel-2); border: 1px solid var(--border);
            border-radius: var(--radius-sm); padding: 14px 16px; margin-bottom: 8px;
        }
        .rumor-author { font-weight: 600; font-size: 13px; color: var(--text); }
        .rumor-date { font-size: 11px; color: var(--text-3); }
        .rumor-text { font-size: 13px; color: var(--text-2); margin-top: 6px; }

        /* ── Buttons ───────────────────────────────────── */
        .btn-primary-red {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 18px; border-radius: 10px;
            background: var(--red); border: none; color: #fff;
            font-family: var(--font-body); font-size: 13px; font-weight: 600;
            cursor: pointer; transition: all var(--t) var(--ease); text-decoration: none;
        }
        .btn-primary-red:hover { background: var(--red-2); color: #fff; }
        .btn-primary-red:disabled { opacity: .45; cursor: not-allowed; }

        .btn-ghost {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 18px; border-radius: 10px;
            background: var(--panel-2); border: 1px solid var(--border); color: var(--text-2);
            font-family: var(--font-body); font-size: 13px; font-weight: 500;
            cursor: pointer; transition: all var(--t) var(--ease); text-decoration: none;
        }
        .btn-ghost:hover { border-color: var(--border-strong); color: var(--text); }
        .btn-ghost:disabled { opacity: .45; cursor: not-allowed; }

        .btn-outline-red {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 18px; border-radius: 10px;
            background: var(--red-soft); border: 1px solid var(--border-red); color: var(--red);
            font-family: var(--font-body); font-size: 13px; font-weight: 600;
            cursor: pointer; transition: all var(--t) var(--ease); text-decoration: none;
        }
        .btn-outline-red:hover { background: var(--red); color: #fff; }
        .btn-outline-red:disabled { opacity: .45; cursor: not-allowed; }

        /* ── Badge ─────────────────────────────────────── */
        .badge-pill {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 3px 10px; border-radius: 999px;
            font-size: 11px; font-weight: 600;
        }
        .badge-pill.red { background: var(--red-soft); color: var(--red); }
        .badge-pill.gray { background: var(--panel-3); color: var(--text-2); }
        .badge-pill.green { background: rgba(22,163,74,.12); color: #4ade80; }
        .badge-pill.yellow { background: rgba(245,158,11,.12); color: #f59e0b; }

        /* ── Admin toggle ──────────────────────────────── */
        .admin-toggle-wrap {
            display: flex; align-items: center; gap: 10px;
            background: var(--panel-2); border: 1px solid var(--border);
            border-radius: 10px; padding: 8px 14px;
        }
        .form-switch .form-check-input { cursor: pointer; }
        .form-check-input:checked { background-color: var(--red); border-color: var(--red); }

        /* ── Pick selector ─────────────────────────────── */
        .pick-selector {
            background: var(--panel-2); border: 1px solid var(--border);
            border-radius: var(--radius-sm); padding: 14px;
        }
        .pick-options {
            max-height: 200px; overflow-y: auto;
            display: flex; flex-direction: column; gap: 8px; margin-bottom: 10px;
            scrollbar-width: thin; scrollbar-color: var(--border) transparent;
        }
        .pick-option-card {
            display: flex; justify-content: space-between; align-items: center;
            background: var(--panel-3); border: 1px solid var(--border);
            border-radius: 8px; padding: 9px 12px;
            transition: border-color var(--t) var(--ease); cursor: pointer;
        }
        .pick-option-card:hover { border-color: var(--border-strong); }
        .pick-option-card.is-selected { opacity: .5; pointer-events: none; }

        .selected-picks { display: flex; flex-direction: column; gap: 8px; }
        .selected-pick-card {
            display: flex; flex-wrap: wrap; gap: 8px;
            justify-content: space-between; align-items: center;
            background: var(--red-soft); border: 1px solid var(--border-red);
            border-radius: 8px; padding: 10px 12px;
        }
        .selected-pick-info { flex: 1; min-width: 160px; }
        .selected-pick-actions { display: flex; gap: 8px; align-items: center; }

        .pick-title { font-size: 13px; font-weight: 600; color: var(--text); }
        .pick-meta { font-size: 11px; color: var(--text-2); margin-top: 2px; }

        .pick-empty-state {
            text-align: center; padding: 12px;
            background: var(--panel-3); border: 1px dashed var(--border);
            border-radius: 8px; color: var(--text-3); font-size: 12px;
        }

        .pick-protection-select {
            background: var(--panel-3) !important; color: var(--text) !important;
            border: 1px solid var(--border-red) !important;
            border-radius: 8px; padding: 5px 8px; font-size: 12px;
            font-family: var(--font-body); min-width: 130px;
        }

        /* ── CAP Impact card ───────────────────────────── */
        .cap-impact-card {
            background: var(--panel-2); border: 1px solid var(--border);
            border-radius: var(--radius-sm); padding: 16px;
        }
        .cap-impact-label { font-size: 12px; color: var(--text-2); margin-bottom: 6px; }
        .cap-impact-row { display: flex; justify-content: space-between; align-items: center; }
        .cap-impact-val { font-family: var(--font-display); font-weight: 700; font-size: 15px; color: var(--text); }
        .cap-impact-delta { font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 999px; }

        /* ── Modals ────────────────────────────────────── */
        .modal-content {
            background: var(--panel) !important; border: 1px solid var(--border-strong) !important;
            border-radius: var(--radius) !important; color: var(--text);
        }
        .modal-header {
            border-bottom: 1px solid var(--border) !important;
            padding: 18px 22px; background: var(--panel-2) !important;
            border-radius: var(--radius) var(--radius) 0 0 !important;
        }
        .modal-title { font-family: var(--font-display); font-weight: 700; font-size: 16px; color: var(--text); }
        .modal-body { padding: 20px 22px; }
        .modal-footer {
            border-top: 1px solid var(--border) !important; padding: 14px 22px;
            background: var(--panel-2) !important;
            border-radius: 0 0 var(--radius) var(--radius) !important;
        }
        .btn-close-white { filter: invert(1) grayscale(1) brightness(2); }

        .form-label { font-size: 12px; font-weight: 600; color: var(--text-2); text-transform: uppercase; letter-spacing: .6px; margin-bottom: 6px; }
        .form-control, .form-select {
            background: var(--panel-2) !important; border: 1px solid var(--border) !important;
            color: var(--text) !important; border-radius: 10px !important;
            font-family: var(--font-body); font-size: 13px;
        }
        .form-control::placeholder { color: var(--text-3) !important; }
        .form-control:focus, .form-select:focus {
            border-color: var(--red) !important; box-shadow: none !important; outline: none;
        }
        textarea.form-control { resize: none; }

        .section-divider {
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1px; color: var(--red); padding-bottom: 8px;
            border-bottom: 1px solid var(--border-red); margin-bottom: 14px;
        }

        /* ── Multi-trade item ──────────────────────────── */
        .multi-trade-item {
            background: var(--panel-2); border: 1px solid var(--border);
            border-radius: var(--radius-sm); padding: 14px 16px; margin-bottom: 8px;
        }

        /* ── Empty state ───────────────────────────────── */
        .empty-state { padding: 48px 20px; text-align: center; color: var(--text-3); }
        .empty-state i { font-size: 32px; margin-bottom: 10px; display: block; }
        .empty-state p { font-size: 13px; }

        /* ── Lock banner ───────────────────────────────── */
        .lock-banner {
            display: flex; align-items: center; gap: 12px;
            background: rgba(252,0,37,.06); border: 1px solid var(--border-red);
            border-radius: var(--radius-sm); padding: 14px 18px; margin-bottom: 20px;
        }
        .lock-banner i { font-size: 18px; color: var(--red); flex-shrink: 0; }
        .lock-banner-text { font-size: 13px; color: var(--text-2); }
        .lock-banner-text strong { color: var(--text); }

        /* ── Reaction bar (rumores) ─────────────────────── */
        .reaction-bar { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
        .reaction-chip {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 9px; border-radius: 999px;
            border: 1px solid var(--border); background: transparent;
            color: var(--text-2); font-size: 12px; cursor: pointer;
            transition: all var(--t) var(--ease); font-family: var(--font-body);
        }
        .reaction-chip:hover { border-color: var(--border-strong); color: var(--text); }
        .reaction-chip.active { border-color: var(--red); background: var(--red-soft); color: var(--red); }

        /* ── Responsive ────────────────────────────────── */
        @media (max-width: 900px) {
            :root { --sidebar-w: 0px; }
            .sidebar { transform: translateX(-260px); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; width: 100%; padding-top: 56px; }
            .topbar { display: flex; }
            .page-top, .stats-strip, .tabs-wrap, .content-area { padding-left: 16px; padding-right: 16px; }
            .page-top { padding-top: 20px; }
        }
        @media (max-width: 600px) {
            .page-title { font-size: 22px; }
            .page-actions { width: 100%; }
            .panel-card { padding: 16px; }
        }

        /* ── Animations ────────────────────────────────── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .tab-pane.active { animation: fadeUp .25s var(--ease) both; }

        /* pick-swaps hidden */
        #pick-swaps, .pick-swaps, .pick-swap { display: none !important; }
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

        <div class="sb-team">
            <img src="<?= htmlspecialchars($team['photo_url'] ?? '/img/default-team.png') ?>"
                 alt="<?= htmlspecialchars($team['name'] ?? 'Time') ?>"
                 onerror="this.src='/img/default-team.png'">
            <div>
                <div class="sb-team-name"><?= htmlspecialchars(($team['city'] ?? '') . ' ' . ($team['name'] ?? 'Sem time')) ?></div>
                <div class="sb-team-league"><?= htmlspecialchars($user['league']) ?></div>
            </div>
        </div>

        <?php if ($currentSeason): ?>
        <div class="sb-season">
            <div>
                <div class="sb-season-label">Temporada</div>
                <div class="sb-season-val"><?= $seasonDisplayYear ?></div>
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
            <a href="/trades.php" class="active"><i class="bi bi-arrow-left-right"></i> Trades</a>
            <a href="/free-agency.php"><i class="bi bi-coin"></i> Free Agency</a>
            <a href="/leilao.php"><i class="bi bi-hammer"></i> Leilão</a>
            <a href="/drafts.php"><i class="bi bi-trophy"></i> Draft</a>

            <div class="sb-section">Liga</div>
            <a href="/rankings.php"><i class="bi bi-bar-chart-fill"></i> Rankings</a>
            <a href="/history.php"><i class="bi bi-clock-history"></i> Histórico</a>
            <a href="/diretrizes.php"><i class="bi bi-clipboard-data"></i> Diretrizes</a>
            <a href="/ouvidoria.php"><i class="bi bi-chat-dots"></i> Ouvidoria</a>
            <a href="https://games.fbabrasil.com.br/auth/login.php" target="_blank" rel="noopener"><i class="bi bi-controller"></i> FBA Games</a>

            <?php if (($user['user_type'] ?? 'jogador') === 'admin'): ?>
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
                 alt="<?= htmlspecialchars($user['name']) ?>"
                 class="sb-avatar"
                 onerror="this.src='https://ui-avatars.com/api/?name=<?= rawurlencode($user['name']) ?>&background=1c1c21&color=fc0025'">
            <span class="sb-username"><?= htmlspecialchars($user['name']) ?></span>
            <a href="/logout.php" class="sb-logout" title="Sair"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </aside>

    <!-- Overlay mobile -->
    <div class="sb-overlay" id="sbOverlay"></div>

    <!-- Topbar mobile -->
    <header class="topbar">
        <button class="menu-btn" id="menuBtn"><i class="bi bi-list"></i></button>
        <div class="topbar-title">FBA <em>Manager</em></div>
        <?php if ($currentSeason): ?>
        <span style="font-size:11px;font-weight:700;color:var(--red)"><?= $seasonDisplayYear ?></span>
        <?php endif; ?>
    </header>

    <!-- ═══ MAIN ═══════════════════════════════════════════════ -->
    <main class="main">

        <!-- Page Header -->
        <div class="page-top">
            <div>
                <div class="page-eyebrow">Liga · <?= $currentSeasonYear ?></div>
                <h1 class="page-title">Trades</h1>
                <p class="page-sub">Gerencie propostas e negociações da sua franquia</p>
            </div>
            <div class="page-actions">
                <?php if (($user['user_type'] ?? 'jogador') === 'admin'): ?>
                <div class="admin-toggle-wrap">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="tradesStatusToggle"
                               <?= ($tradesEnabled ?? 1) == 1 ? 'checked' : '' ?>>
                    </div>
                    <span id="tradesStatusBadge" class="badge-pill <?= ($tradesEnabled ?? 1) == 1 ? 'green' : 'red' ?>">
                        <?= ($tradesEnabled ?? 1) == 1 ? 'Trocas abertas' : 'Trocas bloqueadas' ?>
                    </span>
                </div>
                <?php endif; ?>
                <?php if ($tradesEnabled == 0): ?>
                    <button class="btn-ghost" disabled>
                        <i class="bi bi-lock-fill"></i> Bloqueadas
                    </button>
                <?php else: ?>
                    <button class="btn-outline-red" data-bs-toggle="modal" data-bs-target="#multiTradeModal"
                            <?= $tradeCount >= $maxTrades ? 'disabled' : '' ?>>
                        <i class="bi bi-people-fill"></i> Múltipla
                    </button>
                    <button class="btn-primary-red" data-bs-toggle="modal" data-bs-target="#proposeTradeModal"
                            <?= $tradeCount >= $maxTrades ? 'disabled' : '' ?>>
                        <i class="bi bi-plus-lg"></i> Nova Trade
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats Strip -->
        <div class="stats-strip">
            <div class="stat-pill">
                <div class="stat-pill-icon"><i class="bi bi-arrow-left-right"></i></div>
                <div>
                    <div class="stat-pill-val"><?= $tradeCount ?></div>
                    <div class="stat-pill-label">Trocas usadas</div>
                </div>
            </div>
            <div class="stat-pill">
                <div class="stat-pill-icon <?= $tradesLeft > 0 ? 'green' : 'red' ?>">
                    <i class="bi bi-<?= $tradesLeft > 0 ? 'check2' : 'x-lg' ?>"></i>
                </div>
                <div>
                    <div class="stat-pill-val"><?= $tradesLeft ?></div>
                    <div class="stat-pill-label">Restantes</div>
                </div>
            </div>
            <div class="stat-pill">
                <div class="stat-pill-icon yellow"><i class="bi bi-bar-chart-fill"></i></div>
                <div>
                    <div class="stat-pill-val"><?= $maxTrades ?></div>
                    <div class="stat-pill-label">Limite da liga</div>
                </div>
            </div>
        </div>

        <?php if (!$teamId): ?>
        <div class="content-area">
            <div class="empty-state">
                <i class="bi bi-exclamation-circle"></i>
                <p>Você ainda não possui um time cadastrado.</p>
            </div>
        </div>
        <?php else: ?>

        <?php if ($tradesEnabled == 0): ?>
        <div style="padding: 16px 32px 0;">
            <div class="lock-banner">
                <i class="bi bi-lock-fill"></i>
                <div class="lock-banner-text">
                    <strong>Trades bloqueadas</strong> — O administrador desativou as trocas temporariamente.
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="tabs-wrap">
            <div class="tabs-bar">
                <button class="tab-btn active" data-tab="received">
                    <i class="bi bi-inbox-fill"></i> Recebidas
                </button>
                <button class="tab-btn" data-tab="sent">
                    <i class="bi bi-send-fill"></i> Enviadas
                </button>
                <button class="tab-btn" data-tab="history">
                    <i class="bi bi-clock-history"></i> Histórico
                </button>
                <button class="tab-btn" data-tab="league">
                    <i class="bi bi-trophy"></i> Liga
                </button>
                <button class="tab-btn" data-tab="rumors">
                    <i class="bi bi-megaphone"></i> Rumores
                </button>
                <button class="tab-btn" data-tab="trade-list">
                    <i class="bi bi-list-stars"></i> Trade List
                </button>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">

            <!-- Recebidas -->
            <div class="tab-pane active" id="tab-received">
                <div id="receivedTradesList"></div>
            </div>

            <!-- Enviadas -->
            <div class="tab-pane" id="tab-sent">
                <div id="sentTradesList"></div>
            </div>

            <!-- Histórico -->
            <div class="tab-pane" id="tab-history">
                <div id="historyTradesList"></div>
            </div>

            <!-- Liga -->
            <div class="tab-pane" id="tab-league">
                <div class="panel-card">
                    <div class="panel-header">
                        <div>
                            <div class="panel-title">Trocas da Liga</div>
                            <div class="panel-sub">Histórico completo de negociações aceitas</div>
                        </div>
                        <span class="badge-pill gray" id="leagueTradesCount">0 trocas</span>
                    </div>
                    <div class="filter-bar">
                        <input type="text" id="leagueTradesSearch" placeholder="Buscar jogador...">
                        <select id="leagueTradesTeamFilter">
                            <option value="">Todos os times</option>
                        </select>
                    </div>
                    <div id="leagueTradesList"></div>
                </div>
            </div>

            <!-- Rumores -->
            <div class="tab-pane" id="tab-rumors">
                <div class="panel-card">
                    <div class="panel-header">
                        <div>
                            <div class="panel-title">Rumores da Liga</div>
                            <div class="panel-sub">Compartilhe o que está procurando ou quer negociar</div>
                        </div>
                        <span class="badge-pill gray" id="rumorsCount">0 rumores</span>
                    </div>

                    <!-- Admin comments -->
                    <div class="mb-4">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <span style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text-3);">
                                <i class="bi bi-pin-angle-fill" style="color:var(--red);margin-right:4px;"></i>Fixados pelo Admin
                            </span>
                            <?php if (($user['user_type'] ?? 'jogador') === 'admin'): ?>
                            <button class="btn-ghost" style="padding:5px 12px;font-size:12px;" id="addAdminCommentBtn">
                                <i class="bi bi-plus-lg"></i> Adicionar
                            </button>
                            <?php endif; ?>
                        </div>
                        <div id="adminCommentsList"></div>
                    </div>

                    <!-- New rumor -->
                    <div style="margin-bottom:20px;">
                        <label class="form-label">Seu rumor</label>
                        <textarea class="form-control" id="rumorContent" rows="2"
                                  placeholder="Ex.: Procuro SG com OVR 80+ ou vendo PF..."></textarea>
                        <div style="display:flex;justify-content:flex-end;margin-top:8px;">
                            <button class="btn-primary-red" id="submitRumorBtn" style="padding:8px 16px;">
                                <i class="bi bi-megaphone-fill"></i> Publicar
                            </button>
                        </div>
                    </div>

                    <div id="rumorsList"></div>
                </div>
            </div>

            <!-- Trade List -->
            <div class="tab-pane" id="tab-trade-list">
                <div class="panel-card">
                    <div class="panel-header">
                        <div>
                            <div class="panel-title">Jogadores Disponíveis</div>
                            <div class="panel-sub">Atletas marcados como disponíveis para troca na sua liga</div>
                        </div>
                        <span class="badge-pill gray" id="countBadge">0 jogadores</span>
                    </div>
                    <div class="filter-bar">
                        <input type="text" id="searchInput" placeholder="Buscar por nome...">
                        <select id="sortSelect">
                            <option value="ovr_desc">OVR (Maior primeiro)</option>
                            <option value="ovr_asc">OVR (Menor primeiro)</option>
                            <option value="name_asc">Nome (A-Z)</option>
                            <option value="name_desc">Nome (Z-A)</option>
                            <option value="age_asc">Idade (Menor)</option>
                            <option value="age_desc">Idade (Maior)</option>
                            <option value="position_asc">Posição (A-Z)</option>
                            <option value="team_asc">Time (A-Z)</option>
                        </select>
                    </div>
                    <div id="playersList"></div>
                </div>
            </div>

        </div><!-- /.content-area -->
        <?php endif; ?>

    </main>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     MODAL: Propor Trade
═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="proposeTradeModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-left-right" style="color:var(--red);margin-right:8px;"></i>Propor Trade</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="proposeTradeForm">

                    <div style="margin-bottom:20px;">
                        <label class="form-label">Para qual time?</label>
                        <select class="form-select" id="targetTeam" required>
                            <option value="">Selecione um time...</option>
                        </select>
                    </div>

                    <div class="row g-4">
                        <!-- Você oferece -->
                        <div class="col-md-6">
                            <div class="section-divider">Você oferece</div>

                            <label class="form-label" style="margin-bottom:8px;">Jogadores</label>
                            <div class="pick-selector" style="margin-bottom:14px;">
                                <div class="pick-options" id="offerPlayersOptions"></div>
                                <div class="selected-picks" id="offerPlayersSelected"></div>
                            </div>

                            <label class="form-label" style="margin-bottom:8px;">Picks</label>
                            <div class="pick-selector">
                                <div class="pick-options" id="offerPicksOptions"></div>
                                <div class="selected-picks" id="offerPicksSelected"></div>
                            </div>
                        </div>

                        <!-- Você quer -->
                        <div class="col-md-6">
                            <div class="section-divider">Você quer</div>

                            <label class="form-label" style="margin-bottom:8px;">Jogadores</label>
                            <div class="pick-selector" style="margin-bottom:14px;">
                                <div class="pick-options" id="requestPlayersOptions"></div>
                                <div class="selected-picks" id="requestPlayersSelected"></div>
                            </div>

                            <label class="form-label" style="margin-bottom:8px;">Picks</label>
                            <div class="pick-selector">
                                <div class="pick-options" id="requestPicksOptions"></div>
                                <div class="selected-picks" id="requestPicksSelected"></div>
                            </div>
                        </div>
                    </div>

                    <!-- CAP Impact -->
                    <div class="row g-3 mt-2" id="capImpactRow">
                        <div class="col-md-6">
                            <div class="cap-impact-card">
                                <div class="cap-impact-label">Seu time</div>
                                <div class="cap-impact-row">
                                    <div>
                                        <div style="font-size:11px;color:var(--text-3);">Atual: <strong id="capMyCurrent" style="color:var(--text);">-</strong></div>
                                        <div style="font-size:11px;color:var(--text-3);">Após: <strong id="capMyProjected" style="color:var(--red);">-</strong></div>
                                    </div>
                                    <span class="cap-impact-delta badge-pill gray" id="capMyDelta">±0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="cap-impact-card">
                                <div class="cap-impact-label" id="capTargetLabel">Time alvo</div>
                                <div class="cap-impact-row">
                                    <div>
                                        <div style="font-size:11px;color:var(--text-3);">Atual: <strong id="capTargetCurrent" style="color:var(--text);">-</strong></div>
                                        <div style="font-size:11px;color:var(--text-3);">Após: <strong id="capTargetProjected" style="color:var(--red);">-</strong></div>
                                    </div>
                                    <span class="cap-impact-delta badge-pill gray" id="capTargetDelta">±0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mensagem -->
                    <div style="margin-top:16px;">
                        <label class="form-label">Mensagem (opcional)</label>
                        <textarea class="form-control" id="tradeNotes" rows="2" placeholder="Deixe uma mensagem para o outro GM..."></textarea>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                <?php if ($tradesEnabled == 0): ?>
                    <button type="button" class="btn-ghost" id="submitTradeBtn" disabled>
                        <i class="bi bi-lock-fill"></i> Bloqueado
                    </button>
                <?php else: ?>
                    <button type="button" class="btn-primary-red" id="submitTradeBtn">
                        <i class="bi bi-send"></i> Enviar Proposta
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     MODAL: Trade Múltipla
═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="multiTradeModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-people-fill" style="color:var(--red);margin-right:8px;"></i>Trade Múltipla</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="multiTradeForm">
                    <div style="margin-bottom:20px;">
                        <label class="form-label">Times participantes <span style="color:var(--text-3);font-size:11px;">(máx. 7)</span></label>
                        <div id="multiTradeTeamsList" class="d-flex flex-column gap-2"></div>
                    </div>
                    <div style="margin-bottom:20px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                            <label class="form-label mb-0">Itens da troca</label>
                            <button type="button" class="btn-ghost" style="padding:6px 12px;font-size:12px;" id="addMultiTradeItemBtn">
                                <i class="bi bi-plus-lg"></i> Adicionar item
                            </button>
                        </div>
                        <div id="multiTradeItems"></div>
                    </div>
                    <div>
                        <label class="form-label">Mensagem (opcional)</label>
                        <textarea class="form-control" id="multiTradeNotes" rows="2" placeholder="Descreva os detalhes da troca..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                <?php if ($tradesEnabled == 0): ?>
                    <button type="button" class="btn-ghost" id="submitMultiTradeBtn" disabled>
                        <i class="bi bi-lock-fill"></i> Bloqueado
                    </button>
                <?php else: ?>
                    <button type="button" class="btn-primary-red" id="submitMultiTradeBtn">
                        <i class="bi bi-send"></i> Enviar Trade Múltipla
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
    window.__TEAM_ID__           = <?= $teamId ? (int)$teamId : 'null' ?>;
    window.__USER_LEAGUE__       = '<?= htmlspecialchars($user['league'], ENT_QUOTES) ?>';
    window.__CURRENT_SEASON_YEAR__ = <?= (int)$currentSeasonYear ?>;
    window.__TEAM_NAME__         = '<?= htmlspecialchars(trim(($team['city'] ?? '') . ' ' . ($team['name'] ?? '')), ENT_QUOTES) ?>';
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/sidebar.js"></script>
<script src="/js/trades.js?v=20260309"></script>
<script src="/js/trade-list.js?v=20260130"></script>
<script src="/js/rumors.js?v=20260130"></script>
<script src="/js/pwa.js?v=20260130"></script>

<script>
/* ── Custom Tabs ───────────────────────────────── */
(function () {
    const btns  = document.querySelectorAll('.tab-btn');
    const panes = document.querySelectorAll('.tab-pane');

    btns.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;
            btns.forEach(b => b.classList.remove('active'));
            panes.forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            const pane = document.getElementById('tab-' + target);
            if (pane) pane.classList.add('active');
        });
    });
})();

/* ── Theme toggle ──────────────────────────────── */
(function () {
    const btn  = document.getElementById('themeToggle');
    const html = document.documentElement;
    const icon = btn.querySelector('i');
    const span = btn.querySelector('span');
    const saved = localStorage.getItem('fba-theme') || '';
    html.setAttribute('data-theme', saved);
    const update = () => {
        const light = html.getAttribute('data-theme') === 'light';
        icon.className = light ? 'bi bi-sun' : 'bi bi-moon';
        span.textContent = light ? 'Modo claro' : 'Modo escuro';
    };
    update();
    btn.addEventListener('click', () => {
        const next = html.getAttribute('data-theme') === 'light' ? '' : 'light';
        html.setAttribute('data-theme', next);
        localStorage.setItem('fba-theme', next);
        update();
    });
})();

<?php if (($user['user_type'] ?? 'jogador') === 'admin'): ?>
/* ── Admin: toggle trades ──────────────────────── */
(function () {
    const toggle = document.getElementById('tradesStatusToggle');
    const badge  = document.getElementById('tradesStatusBadge');
    const league = window.__USER_LEAGUE__;
    if (!toggle || !league) return;
    toggle.addEventListener('change', async (e) => {
        const enabled = e.target.checked ? 1 : 0;
        try {
            const res  = await fetch('/api/admin.php?action=league_settings', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ league, trades_enabled: enabled })
            });
            const data = await res.json();
            if (!res.ok || data.success === false) throw new Error(data.error || 'Erro');
            badge.className = 'badge-pill ' + (enabled ? 'green' : 'red');
            badge.textContent = enabled ? 'Trocas abertas' : 'Trocas bloqueadas';
        } catch {
            alert('Erro ao atualizar status das trocas');
            e.target.checked = !e.target.checked;
        }
    });
})();
<?php endif; ?>
</script>
</body>
</html>
