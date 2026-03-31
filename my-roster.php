<?php
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/db.php';
requireAuth();

$user = getUserSession();
$pdo = db();

$stmtTeam = $pdo->prepare('SELECT * FROM teams WHERE user_id = ? LIMIT 1');
$stmtTeam->execute([$user['id']]);
$team = $stmtTeam->fetch() ?: null;
$teamId = $team['id'] ?? null;

$capMin = 0; $capMax = 999;
if ($team && !empty($team['league'])) {
    try {
        $stmtCapLimits = $pdo->prepare('SELECT cap_min, cap_max FROM league_settings WHERE league = ?');
        $stmtCapLimits->execute([$team['league']]);
        $capLimits = $stmtCapLimits->fetch();
        if ($capLimits) { $capMin = (int)($capLimits['cap_min'] ?? 0); $capMax = (int)($capLimits['cap_max'] ?? 999); }
    } catch (Exception $e) {}
}

$league = strtoupper((string)($team['league'] ?? ($user['league'] ?? 'LEAGUE')));
$canAddPlayers = in_array($league, ['ELITE', 'NEXT'], true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#fc0025">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="FBA Manager">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/img/fba-logo.png">
    <?php include __DIR__ . '/../includes/head-pwa.php'; ?>
    <title>Meu Elenco - FBA Manager</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        /* -- Tokens -------------------------------- */
        :root {
            --red:        #fc0025;
            --red-2:      #ff2a44;
            --red-soft:   rgba(252,0,37,.10);
            --red-glow:   rgba(252,0,37,.18);
            --bg:         #07070a;
            --panel:      #101013;
            --panel-2:    #16161a;
            --panel-3:    #1c1c21;
            --court:      #0e1117;
            --court-line: rgba(252,0,37,.22);
            --court-soft: rgba(252,0,37,.10);
            --border:     rgba(255,255,255,.06);
            --border-md:  rgba(255,255,255,.10);
            --border-red: rgba(252,0,37,.24);
            --text:       #f0f0f3;
            --text-2:     #8b8b95;
            --text-3:     #4b4b54;
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
        html, body { height: 100%; overflow-x: hidden; }
        body { font-family: var(--font); background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; -webkit-tap-highlight-color: transparent; }
        a { color: inherit; text-decoration: none; }

        .app { display: flex; min-height: 100vh; }

        /* -- Sidebar ------------------------------- */
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
        .sb-nav a i { width: 18px; text-align: center; font-size: 15px; flex-shrink: 0; }
        .sb-nav a:hover { background: var(--panel-2); color: var(--text); }
        .sb-nav a.active { background: var(--red-soft); color: var(--red); font-weight: 600; }
        .sb-nav a.active i { color: var(--red); }
        .sb-footer { border-top: 1px solid var(--border); padding: 12px 14px; display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .sb-avatar { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-md); flex-shrink: 0; }
        .sb-username { font-size: 12px; font-weight: 500; color: var(--text); flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sb-logout { width: 26px; height: 26px; border-radius: 7px; background: transparent; border: 1px solid var(--border); color: var(--text-2); display: flex; align-items: center; justify-content: center; font-size: 12px; cursor: pointer; transition: all var(--t) var(--ease); flex-shrink: 0; }
        .sb-logout:hover { background: var(--red-soft); border-color: var(--red); color: var(--red); }

        /* -- Topbar mobile ------------------------- */
        .topbar { display: none; position: fixed; top: 0; left: 0; right: 0; height: 54px; background: var(--panel); border-bottom: 1px solid var(--border); align-items: center; padding: 0 16px; gap: 12px; z-index: 199; }
        .topbar-title { font-weight: 700; font-size: 15px; flex: 1; }
        .topbar-title em { color: var(--red); font-style: normal; }
        .menu-btn { width: 34px; height: 34px; border-radius: 9px; background: var(--panel-2); border: 1px solid var(--border); color: var(--text); display: flex; align-items: center; justify-content: center; font-size: 17px; cursor: pointer; }
        .sb-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.65); backdrop-filter: blur(4px); z-index: 199; }
        .sb-overlay.show { display: block; }

        /* -- Main ---------------------------------- */
        .main { margin-left: var(--sidebar-w); min-height: 100vh; width: calc(100% - var(--sidebar-w)); display: flex; flex-direction: column; }

        /* -- Hero ---------------------------------- */
        .dash-hero { padding: 30px 32px 0; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .dash-eyebrow { font-size: 11px; font-weight: 600; letter-spacing: 1.4px; text-transform: uppercase; color: var(--red); margin-bottom: 4px; }
        .dash-title { font-size: 26px; font-weight: 800; line-height: 1.08; }
        .dash-sub { font-size: 13px; color: var(--text-2); margin-top: 3px; }

        /* -- KPIs ---------------------------------- */
        .kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; min-width: 360px; max-width: 520px; width: 100%; }
        .kpi { background: var(--panel); border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px; }
        .kpi-label { font-size: 10px; text-transform: uppercase; letter-spacing: .6px; color: var(--text-2); font-weight: 700; }
        .kpi-value { font-size: 20px; font-weight: 800; color: var(--text); line-height: 1.1; margin-top: 3px; }
        .kpi-value.ok { color: var(--green); }
        .kpi-value.warn { color: #ef4444; }
        .kpi-value.amber { color: var(--amber); }

        /* -- Content ------------------------------- */
        .content { padding: 18px 32px 40px; flex: 1; }

        /* -- Panel --------------------------------- */
        .panel { background: var(--panel); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 14px; }
        .panel-head { padding: 14px 18px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; background: var(--panel-2); }
        .panel-title { font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .panel-title i { color: var(--red); }
        .panel-body { padding: 18px; }

        /* -- Toolbar ------------------------------- */
        .toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; gap: 10px; flex-wrap: wrap; }
        .view-switch { display: flex; background: var(--panel-3); padding: 3px; border-radius: 9px; border: 1px solid var(--border); gap: 2px; }
        .view-btn { border: none; background: transparent; color: var(--text-2); padding: 6px 12px; border-radius: 7px; font-size: 12px; font-weight: 600; font-family: var(--font); cursor: pointer; transition: all var(--t) var(--ease); display: flex; align-items: center; gap: 5px; }
        .view-btn.active { background: var(--panel-2); color: var(--text); box-shadow: 0 2px 6px rgba(0,0,0,.25); }

        /* -- Filters ------------------------------- */
        .filters { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
        .f-input { background: var(--panel-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 8px 10px; color: var(--text); font-family: var(--font); font-size: 13px; outline: none; flex: 1; min-width: 160px; transition: border-color var(--t) var(--ease); }
        .f-input:focus { border-color: var(--red); }
        .f-input::placeholder { color: var(--text-3); }
        .f-select { background: var(--panel-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 8px 10px; color: var(--text); font-family: var(--font); font-size: 13px; outline: none; cursor: pointer; }
        .f-select:focus { border-color: var(--red); }
        .f-select option { background: var(--panel-2); }

        /* -- Buttons ------------------------------- */
        .btn-r { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: var(--radius-sm); font-family: var(--font); font-size: 12px; font-weight: 600; cursor: pointer; border: 1px solid transparent; transition: all var(--t) var(--ease); white-space: nowrap; }
        .btn-r.primary { background: var(--red); color: #fff; border-color: var(--red); }
        .btn-r.primary:hover { filter: brightness(1.1); color: #fff; }
        .btn-r.ghost { background: transparent; color: var(--text-2); border-color: var(--border); }
        .btn-r.ghost:hover { background: var(--panel-2); color: var(--text); border-color: var(--border-md); }
        .btn-r.cyan { background: rgba(6,182,212,.12); color: #22d3ee; border-color: rgba(6,182,212,.25); }
        .btn-r.cyan:hover { background: #06b6d4; color: #fff; }
        .btn-r.sm { padding: 5px 10px; font-size: 11px; }
        .btn-r.icon-sm { width: 30px; height: 30px; padding: 0; justify-content: center; border-radius: 8px; font-size: 13px; }

        /* -- Tags ---------------------------------- */
        .tag { display: inline-flex; align-items: center; gap: 4px; padding: 2px 7px; border-radius: 999px; font-size: 10px; font-weight: 700; }
        .tag.green  { background: rgba(34,197,94,.12);  color: var(--green); border: 1px solid rgba(34,197,94,.2); }
        .tag.red    { background: var(--red-soft);      color: var(--red);   border: 1px solid var(--border-red); }
        .tag.amber  { background: rgba(245,158,11,.12); color: var(--amber); border: 1px solid rgba(245,158,11,.2); }
        .tag.gray   { background: var(--panel-3);       color: var(--text-2); border: 1px solid var(--border); }
        .tag.blue   { background: rgba(59,130,246,.12); color: var(--blue);  border: 1px solid rgba(59,130,246,.2); }

        /* -- Add player form ----------------------- */
        .form-label { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-2); font-weight: 700; display: block; margin-bottom: 5px; }
        .form-control, .form-select { background: var(--panel-3) !important; border: 1px solid var(--border) !important; border-radius: var(--radius-sm) !important; color: var(--text) !important; min-height: 40px; font-family: var(--font); font-size: 13px; }
        .form-control:focus, .form-select:focus { border-color: var(--red) !important; box-shadow: 0 0 0 .18rem rgba(252,0,37,.15) !important; outline: none !important; }
        .form-control::placeholder { color: var(--text-3) !important; }
        .form-select option { background: var(--panel-3); }
        .form-check-input { background-color: var(--panel-3) !important; border-color: var(--border-md) !important; }
        .form-check-input:checked { background-color: var(--red) !important; border-color: var(--red) !important; }
        .form-check-label { font-size: 13px; color: var(--text-2); }

        /* -- Court --------------------------------- */
        .court-container {
            position: relative;
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
            aspect-ratio: 16 / 10;
            background: var(--court);
            border: 2px solid var(--court-line);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: inset 0 0 80px rgba(0,0,0,.5), 0 20px 60px rgba(0,0,0,.4);
        }
        /* center line */
        .court-container::before {
            content: '';
            position: absolute;
            top: 0; left: 50%; bottom: 0; width: 1px;
            background: var(--court-soft);
            transform: translateX(-50%);
        }
        /* center circle */
        .court-container::after {
            content: '';
            position: absolute;
            left: 50%; top: 50%;
            width: 80px; height: 80px;
            border: 1px solid var(--court-soft);
            border-radius: 50%;
            transform: translate(-50%, -50%);
        }
        /* court wood texture overlay */
        .court-lines {
            position: absolute; inset: 0;
            background:
                repeating-linear-gradient(
                    to bottom,
                    transparent,
                    transparent 29px,
                    rgba(255,255,255,.015) 29px,
                    rgba(255,255,255,.015) 30px
                );
            pointer-events: none;
        }
        /* 3pt arc suggestion */
        .court-arc {
            position: absolute;
            bottom: -30%;
            left: 50%;
            width: 60%;
            height: 120%;
            border: 1px solid var(--court-soft);
            border-radius: 50%;
            transform: translateX(-50%);
            pointer-events: none;
        }

        /* Position label above slot */
        .pos-label {
            position: absolute;
            top: -22px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: rgba(255,255,255,.5);
            white-space: nowrap;
        }

        /* Starter slot */
        .starter-slot {
            position: absolute;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
            transition: transform var(--t) var(--ease);
        }

        /* Empty slot indicator */
        .slot-empty {
            width: 64px; height: 64px;
            border-radius: 50%;
            border: 2px dashed rgba(255,255,255,.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700;
            color: rgba(255,255,255,.25);
            letter-spacing: .5px;
        }

        /* Player card on court */
        .player-card {
            width: 90px;
            background: rgba(20,20,26,.92);
            backdrop-filter: blur(8px);
            border: 1px solid var(--border-md);
            border-radius: 12px;
            padding: 8px 6px;
            text-align: center;
            cursor: grab;
            position: relative;
            transition: all var(--t) var(--ease);
            box-shadow: 0 6px 20px rgba(0,0,0,.45);
            user-select: none;
            touch-action: none;
        }
        .player-card:hover { border-color: var(--border-red); transform: translateY(-2px); box-shadow: 0 10px 28px rgba(0,0,0,.55); }
        .player-card:active { cursor: grabbing; }
        .player-card.dragging { opacity: 0.4; transform: scale(1.05); }

        .player-img-wrap { position: relative; width: 52px; height: 52px; margin: 0 auto 5px; }
        .player-img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 2px solid var(--border-red); background: var(--panel-3); }
        .player-ovr-badge { position: absolute; bottom: -2px; right: -3px; color: #fff; font-size: 9px; font-weight: 800; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid rgba(20,20,26,.92); }
        .player-ovr-badge.ovr-low { background: #ef4444; }
        .player-ovr-badge.ovr-mid { background: #f59e0b; }
        .player-ovr-badge.ovr-good { background: #3b82f6; }
        .player-ovr-badge.ovr-high { background: #22c55e; }
        .player-name { font-size: 10px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #fff; margin-bottom: 1px; }
        .player-pos-label { font-size: 9px; font-weight: 600; color: var(--text-2); text-transform: uppercase; }

        /* Slot positions */
        .pos-pg { bottom: 12%; left: 50%; transform: translateX(-50%); }
        .pos-sg { bottom: 34%; left: 76%; transform: translateX(-50%); }
        .pos-sf { bottom: 34%; left: 24%; transform: translateX(-50%); }
        .pos-pf { bottom: 62%; left: 34%; transform: translateX(-50%); }
        .pos-c  { bottom: 68%; left: 50%; transform: translateX(-50%); }

        /* Drop zone */
        .drop-zone { border: 2px dashed transparent; border-radius: 14px; transition: all var(--t) var(--ease); }
        .drop-zone.drag-over { border-color: var(--red); background: var(--red-soft); transform: scale(1.04); }

        /* -- Bench --------------------------------- */
        .bench-section { margin-top: 20px; }
        .bench-header { font-size: 11px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; color: var(--text-3); margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .bench-header::after { content: ''; flex: 1; height: 1px; background: var(--border); }
        .bench-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
            gap: 10px;
            min-height: 110px;
            padding: 14px;
            background: rgba(255,255,255,.02);
            border: 1px dashed var(--border);
            border-radius: var(--radius);
            transition: all var(--t) var(--ease);
        }
        .bench-grid.drag-over { background: var(--red-soft); border-color: var(--red); }

        /* -- Roster table -------------------------- */
        .roster-table { width: 100%; border-collapse: collapse; }
        .roster-table th { font-size: 10px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; color: var(--text-3); padding: 10px 14px; border-bottom: 1px solid var(--border); text-align: left; cursor: pointer; user-select: none; white-space: nowrap; }
        .roster-table th:hover { color: var(--text-2); }
        .roster-table th i { font-size: 10px; margin-left: 3px; }
        .roster-table td { padding: 11px 14px; border-bottom: 1px solid var(--border); font-size: 13px; vertical-align: middle; }
        .roster-table tr:last-child td { border-bottom: none; }
        .roster-table tbody tr { transition: background var(--t) var(--ease); }
        .roster-table tbody tr:hover { background: var(--panel-2); }

        /* Player cell */
        .player-cell { display: flex; align-items: center; gap: 10px; }
        .player-avatar { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-red); background: var(--panel-3); flex-shrink: 0; }
        .player-cell-name { font-size: 13px; font-weight: 600; color: var(--text); }
        .pos-badge { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 22px; border-radius: 5px; background: var(--red-soft); color: var(--red); font-size: 10px; font-weight: 800; letter-spacing: .3px; text-transform: uppercase; }
        .ovr-val { font-size: 15px; font-weight: 800; }
        .ovr-val.ovr-low { color: #ef4444; }
        .ovr-val.ovr-mid { color: #f59e0b; }
        .ovr-val.ovr-good { color: #3b82f6; }
        .ovr-val.ovr-high { color: #22c55e; }
        .role-pill { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; }

        /* Action buttons in table */
        .tbl-actions { display: flex; gap: 5px; justify-content: flex-end; }
        .btn-icon { width: 28px; height: 28px; border-radius: 7px; border: 1px solid var(--border); background: transparent; color: var(--text-2); display: flex; align-items: center; justify-content: center; font-size: 12px; cursor: pointer; transition: all var(--t) var(--ease); }
        .btn-icon:hover { background: var(--panel-2); color: var(--text); border-color: var(--border-md); }
        .btn-icon.edit:hover { background: var(--red-soft); color: var(--red); border-color: var(--border-red); }
        .btn-icon.waive:hover { background: rgba(245,158,11,.12); color: var(--amber); border-color: rgba(245,158,11,.25); }
        .btn-icon.retire:hover { background: rgba(239,68,68,.12); color: #ef4444; border-color: rgba(239,68,68,.25); }
        .btn-icon.trade-on:hover { background: rgba(34,197,94,.12); color: var(--green); border-color: rgba(34,197,94,.25); }
        .btn-icon.trade-off:hover { background: var(--red-soft); color: var(--red); border-color: var(--border-red); }

        /* -- Empty / loading ----------------------- */
        .empty-r { padding: 40px 20px; text-align: center; color: var(--text-3); }
        .empty-r i { font-size: 28px; display: block; margin-bottom: 10px; }
        .empty-r p { font-size: 13px; }
        .spinner-r { display: inline-block; width: 24px; height: 24px; border: 2px solid var(--border); border-top-color: var(--red); border-radius: 50%; animation: spin .6s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* -- Drag ghost ---------------------------- */
        .sortable-ghost { opacity: 0.25; transform: scale(0.9); }
        .sortable-chosen { box-shadow: 0 8px 24px rgba(0,0,0,.5); }

        /* -- Modals -------------------------------- */
        .modal-content { background: var(--panel) !important; border: 1px solid var(--border-md) !important; border-radius: var(--radius) !important; font-family: var(--font); color: var(--text); }
        .modal-header { background: var(--panel-2) !important; border-color: var(--border) !important; padding: 16px 20px; border-radius: var(--radius) var(--radius) 0 0 !important; }
        .modal-title { font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .modal-title i { color: var(--red); }
        .modal-body { padding: 20px; }
        .modal-footer { background: var(--panel-2) !important; border-color: var(--border) !important; padding: 14px 20px; border-radius: 0 0 var(--radius) var(--radius) !important; }
        .btn-close-white { filter: invert(1); }

        /* -- Hint text ----------------------------- */
        .hint { font-size: 11px; color: var(--text-3); display: flex; align-items: center; gap: 5px; margin-bottom: 14px; }

        /* -- AI modal ------------------------------ */
        #aiAnalysisModal .modal-header { background: rgba(6,182,212,.15) !important; border-color: rgba(6,182,212,.25) !important; }
        #aiAnalysisModal .modal-title i { color: #22d3ee; }

        /* -- Animations ---------------------------- */
        @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .panel { animation: fadeUp .35s var(--ease) both; }

        /* -- Responsive ---------------------------- */
        @media (max-width: 860px) {
            :root { --sidebar-w: 0px; }
            .sidebar { transform: translateX(-260px); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; width: 100%; padding-top: 54px; }
            .topbar { display: flex; }
            .dash-hero, .content { padding-left: 16px; padding-right: 16px; }
            .dash-hero { padding-top: 18px; }
            .kpis { min-width: 100%; max-width: 100%; grid-template-columns: repeat(2, 1fr); }
            .court-container { aspect-ratio: 1 / 1.15; }
            .player-card { width: 78px; }
            .player-img-wrap { width: 44px; height: 44px; }
        }
        @media (max-width: 480px) {
            .bench-grid { grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); }
        }
    </style>
</head>
<body>
<div class="app">

    <!-- ---------- SIDEBAR ---------- -->
    <aside class="sidebar" id="sidebar">
        <div class="sb-brand">
            <div class="sb-logo">FBA</div>
            <div class="sb-brand-text">FBA Manager<span>Liga <?= htmlspecialchars($league) ?></span></div>
        </div>

        <div class="sb-team">
            <img src="<?= htmlspecialchars($team['photo_url'] ?? '/img/default-team.png') ?>" alt="" onerror="this.src='/img/default-team.png'">
            <div>
                <div class="sb-team-name"><?= htmlspecialchars($team ? (($team['city'] ?? '') . ' ' . ($team['name'] ?? '')) : 'Sem time') ?></div>
                <div class="sb-team-league"><?= htmlspecialchars($league) ?></div>
            </div>
        </div>

        <nav class="sb-nav">
            <div class="sb-section">Principal</div>
            <a href="https://blue-turkey-597782.hostingersite.com/dashboard.php"><i class="bi bi-house-door-fill"></i> Dashboard</a>
            <a href="https://blue-turkey-597782.hostingersite.com/teams.php"><i class="bi bi-people-fill"></i> Times</a>
            <a href="https://blue-turkey-597782.hostingersite.com/players.php"><i class="bi bi-person-lines-fill"></i> Jogadores</a>
            <a href="https://blue-turkey-597782.hostingersite.com/my-roster.php" class="active"><i class="bi bi-person-fill"></i> Meu Elenco</a>
            <a href="https://blue-turkey-597782.hostingersite.com/picks.php"><i class="bi bi-calendar-check-fill"></i> Picks</a>
            <a href="https://blue-turkey-597782.hostingersite.com/trades.php"><i class="bi bi-arrow-left-right"></i> Trades</a>
            <a href="https://blue-turkey-597782.hostingersite.com/free-agency.php"><i class="bi bi-coin"></i> Free Agency</a>
            <a href="https://blue-turkey-597782.hostingersite.com/drafts.php"><i class="bi bi-trophy"></i> Draft</a>

            <div class="sb-section">Liga</div>
            <a href="https://blue-turkey-597782.hostingersite.com/rankings.php"><i class="bi bi-bar-chart-fill"></i> Rankings</a>
            <a href="https://blue-turkey-597782.hostingersite.com/history.php"><i class="bi bi-clock-history"></i> Histórico</a>

            <?php if (($user['user_type'] ?? 'jogador') === 'admin'): ?>
            <div class="sb-section">Admin</div>
            <a href="https://blue-turkey-597782.hostingersite.com/admin.php"><i class="bi bi-shield-lock-fill"></i> Admin</a>
            <a href="https://blue-turkey-597782.hostingersite.com/temporadas.php"><i class="bi bi-calendar3"></i> Temporadas</a>
            <?php endif; ?>

            <div class="sb-section">Conta</div>
            <a href="https://blue-turkey-597782.hostingersite.com/settings.php"><i class="bi bi-gear-fill"></i> Configurações</a>
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
        <div class="topbar-title">FBA <em>Elenco</em></div>
    </header>

    <!-- ---------- MAIN ---------- -->
    <main class="main">

        <!-- Hero -->
        <div class="dash-hero">
            <div>
                <div class="dash-eyebrow">Gestão do elenco · <?= htmlspecialchars($league) ?></div>
                <h1 class="dash-title">Meu Elenco</h1>
                <p class="dash-sub">Monte seu quinteto, gerencie banco e acompanhe o CAP em tempo real</p>
            </div>

            <div class="kpis">
                <div class="kpi">
                    <div class="kpi-label">Jogadores</div>
                    <div class="kpi-value" id="total-players">—</div>
                </div>
                <div class="kpi">
                    <div class="kpi-label">CAP Top 8</div>
                    <div class="kpi-value" id="cap-top8">—</div>
                </div>
                <div class="kpi">
                    <div class="kpi-label">Dispensas</div>
                    <div class="kpi-value" id="waivers-count">—</div>
                </div>
                <div class="kpi">
                    <div class="kpi-label">Contratações</div>
                    <div class="kpi-value" id="signings-count">—</div>
                </div>
            </div>
        </div>

        <div class="content">

            <?php if (!$teamId): ?>
            <div class="panel">
                <div class="panel-body">
                    <div class="empty-r"><i class="bi bi-exclamation-circle"></i><p>Você ainda não possui um time. Crie um no onboarding.</p></div>
                </div>
            </div>
            <?php else: ?>

            <!-- Add player (leagues that allow) -->
            <?php if ($canAddPlayers): ?>
            <div class="panel" style="animation-delay:.04s">
                <div class="panel-head">
                    <div class="panel-title"><i class="bi bi-plus-circle-fill"></i> Adicionar Jogador</div>
                    <button class="btn-r ghost sm" type="button" data-bs-toggle="collapse" data-bs-target="#addCollapse" aria-expanded="false" aria-controls="addCollapse">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </div>
                <div id="addCollapse" class="collapse">
                    <div class="panel-body">
                        <form id="form-player">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Nome</label>
                                    <input type="text" class="form-control" name="name" placeholder="Ex: John Doe" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Idade</label>
                                    <input type="number" class="form-control" name="age" min="16" max="45" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">OVR</label>
                                    <input type="number" class="form-control" name="ovr" min="40" max="99" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Posição</label>
                                    <select class="form-select" name="position" required>
                                        <option value="">—</option>
                                        <option>PG</option><option>SG</option><option>SF</option><option>PF</option><option>C</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Pos. 2ª</label>
                                    <select class="form-select" name="secondary_position">
                                        <option value="">—</option>
                                        <option>PG</option><option>SG</option><option>SF</option><option>PF</option><option>C</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Função</label>
                                    <select class="form-select" name="role" required>
                                        <option value="Titular">Titular</option>
                                        <option value="Banco">Banco</option>
                                        <option value="Outro">Outro</option>
                                        <option value="G-League">G-League</option>
                                    </select>
                                </div>
                                <div class="col-md-5 d-flex align-items-end gap-3">
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="checkbox" id="available_for_trade" name="available_for_trade" checked>
                                        <label class="form-check-label" for="available_for_trade">Disponível para troca</label>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn-r primary w-100" id="btn-add-player">
                                        <i class="bi bi-cloud-upload"></i> Cadastrar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Roster viewer -->
            <div class="panel" style="animation-delay:.08s">
                <div class="panel-head">
                    <div class="panel-title"><i class="bi bi-dribbble"></i> Visualizador do Elenco</div>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                        <button id="btn-ai-analysis" class="btn-r cyan">
                            <i class="bi bi-robot"></i> IA
                        </button>
                        <button class="btn-r ghost" id="btn-refresh-players">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>

                <div class="panel-body">
                    <!-- Toolbar -->
                    <div class="toolbar">
                        <div class="view-switch">
                            <button class="view-btn" id="btn-view-court" type="button">
                                <i class="bi bi-dribbble"></i> Quadra
                            </button>
                            <button class="view-btn active" id="btn-view-list" type="button">
                                <i class="bi bi-list-ul"></i> Lista
                            </button>
                        </div>
                        <select id="sort-select" class="f-select" style="width:auto;min-width:160px">
                            <option value="role">Ordenar: Função</option>
                            <option value="name">Ordenar: Nome</option>
                            <option value="ovr">Ordenar: OVR</option>
                            <option value="position">Ordenar: Posição</option>
                            <option value="age">Ordenar: Idade</option>
                        </select>
                    </div>

                    <!-- Filters -->
                    <div class="filters">
                        <input type="text" id="players-search" class="f-input" placeholder="Buscar por nome ou posição...">
                        <select id="players-role-filter" class="f-select" style="min-width:160px">
                            <option value="">Todas as funções</option>
                            <option value="Titular">Titular</option>
                            <option value="Banco">Banco</option>
                            <option value="G-League">G-League</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </div>

                    <div class="hint"><i class="bi bi-arrows-move"></i> Arraste os cards para reposicionar jogadores entre quadra e banco</div>

                    <!-- Loading -->
                    <div id="players-status" style="display:none;text-align:center;padding:32px">
                        <div class="spinner-r"></div>
                        <div style="font-size:13px;color:var(--text-2);margin-top:10px">Carregando jogadores...</div>
                    </div>

                    <!-- -- COURT VIEW -- -->
                    <div id="players-grid">
                        <div class="court-container">
                            <div class="court-lines"></div>
                            <div class="court-arc"></div>

                            <div class="starter-slot pos-pg drop-zone" data-role="Titular" data-pos="PG">
                                <div class="pos-label">PG</div>
                                <div class="slot-empty">PG</div>
                            </div>
                            <div class="starter-slot pos-sg drop-zone" data-role="Titular" data-pos="SG">
                                <div class="pos-label">SG</div>
                                <div class="slot-empty">SG</div>
                            </div>
                            <div class="starter-slot pos-sf drop-zone" data-role="Titular" data-pos="SF">
                                <div class="pos-label">SF</div>
                                <div class="slot-empty">SF</div>
                            </div>
                            <div class="starter-slot pos-pf drop-zone" data-role="Titular" data-pos="PF">
                                <div class="pos-label">PF</div>
                                <div class="slot-empty">PF</div>
                            </div>
                            <div class="starter-slot pos-c drop-zone" data-role="Titular" data-pos="C">
                                <div class="pos-label">C</div>
                                <div class="slot-empty">C</div>
                            </div>
                        </div>

                        <div class="bench-section">
                            <div class="bench-header"><i class="bi bi-people-fill" style="color:var(--text-3)"></i> Banco de Reservas</div>
                            <div id="bench-grid" class="bench-grid drop-zone" data-role="Banco"></div>
                        </div>
                    </div>

                    <!-- -- LIST VIEW -- -->
                    <div id="players-table-wrapper" style="display:none">
                        <div style="overflow-x:auto">
                            <table class="roster-table">
                                <thead>
                                    <tr>
                                        <th data-sort="name">Jogador <i class="bi bi-chevron-expand"></i></th>
                                        <th data-sort="position">Pos <i class="bi bi-chevron-expand"></i></th>
                                        <th data-sort="ovr">OVR <i class="bi bi-chevron-expand"></i></th>
                                        <th data-sort="age">Idade <i class="bi bi-chevron-expand"></i></th>
                                        <th data-sort="role">Função <i class="bi bi-chevron-expand"></i></th>
                                        <th>Troca</th>
                                        <th style="text-align:right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="players-table-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </main>
</div>

<!-- ---------- MODALS ---------- -->

<!-- Edit player -->
<div class="modal fade" id="editPlayerModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Editar Jogador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-player-id">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome</label>
                        <input type="text" id="edit-name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Foto do jogador</label>
                        <input type="file" id="edit-foto-adicional" class="form-control" accept="image/*">
                        <div style="margin-top:8px">
                            <img id="edit-foto-preview" src="/img/default-avatar.png"
                                 style="width:52px;height:52px;object-fit:cover;border-radius:50%;border:2px solid var(--border-red);background:var(--panel-3)">
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Idade</label>
                        <input type="number" id="edit-age" class="form-control" min="16" max="50" required>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">OVR</label>
                        <input type="number" id="edit-ovr" class="form-control" min="40" max="99" required>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Posição</label>
                        <select id="edit-position" class="form-select" required>
                            <option>PG</option><option>SG</option><option>SF</option><option>PF</option><option>C</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Pos. 2ª</label>
                        <select id="edit-secondary-position" class="form-select">
                            <option value="">—</option>
                            <option>PG</option><option>SG</option><option>SF</option><option>PF</option><option>C</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Função</label>
                        <select id="edit-role" class="form-select" required>
                            <option value="Titular">Titular</option>
                            <option value="Banco">Banco</option>
                            <option value="Outro">Outro</option>
                            <option value="G-League">G-League</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex align-items-center gap-3 mt-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit-available">
                            <label class="form-check-label" for="edit-available">Disponível para troca</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-r ghost" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn-r primary" id="btn-save-edit"><i class="bi bi-save2"></i> Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Waive player -->
<div class="modal fade" id="waivePlayerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-x"></i> Dispensar Jogador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p style="font-size:14px;color:var(--text-2);margin-bottom:12px">
                    Dispensar <strong style="color:var(--text)" id="waive-player-name">jogador</strong>?<br>
                    Seu CAP Top 8 passará para <strong style="color:var(--amber)" id="waive-player-cap">0</strong>.
                </p>
                <p style="font-size:13px;color:var(--text-2)" id="waive-cap-status">Você vai ficar dentro do CAP.</p>
            </div>
            <div class="modal-footer">
                <button class="btn-r ghost" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn-r" id="btn-confirm-waive"
                        style="background:rgba(245,158,11,.12);color:var(--amber);border-color:rgba(245,158,11,.25)">
                    <i class="bi bi-person-x"></i> Dispensar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- AI Analysis -->
<div class="modal fade" id="aiAnalysisModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-robot"></i> Relatório do Assistente Técnico</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="ai-loading" style="text-align:center;padding:40px 0">
                    <div class="spinner-r" style="width:36px;height:36px;border-width:3px"></div>
                    <div style="font-size:14px;color:var(--text-2);margin-top:14px">A IA está analisando seu elenco...</div>
                </div>
                <div id="ai-results" style="display:none">
                    <div style="margin-bottom:20px">
                        <div style="font-size:12px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--green);margin-bottom:10px;display:flex;align-items:center;gap:6px">
                            <i class="bi bi-arrow-up-circle-fill"></i> Pontos fortes
                        </div>
                        <ul id="ai-strengths" style="padding-left:18px;color:var(--text);font-size:13px;display:flex;flex-direction:column;gap:6px"></ul>
                    </div>
                    <div>
                        <div style="font-size:12px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:#ef4444;margin-bottom:10px;display:flex;align-items:center;gap:6px">
                            <i class="bi bi-arrow-down-circle-fill"></i> Pontos de atenção
                        </div>
                        <ul id="ai-weaknesses" style="padding-left:18px;color:var(--text);font-size:13px;display:flex;flex-direction:column;gap:6px"></ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-r ghost" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- ---------- SCRIPTS ---------- -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    window.__TEAM_ID__ = <?= $teamId ? (int)$teamId : 'null' ?>;
    window.__CAP_MIN__ = <?= (int)$capMin ?>;
    window.__CAP_MAX__ = <?= (int)$capMax ?>;

    /* -- Sidebar ----------------------------------- */
    const sidebar   = document.getElementById('sidebar');
    const sbOverlay = document.getElementById('sbOverlay');
    document.getElementById('menuBtn')?.addEventListener('click', () => { sidebar.classList.toggle('open'); sbOverlay.classList.toggle('show'); });
    sbOverlay.addEventListener('click', () => { sidebar.classList.remove('open'); sbOverlay.classList.remove('show'); });

    /* -- View switch ------------------------------- */
    const btnCourt  = document.getElementById('btn-view-court');
    const btnList   = document.getElementById('btn-view-list');
    const courtView = document.getElementById('players-grid');
    const listView  = document.getElementById('players-table-wrapper');

    function setRosterView(mode) {
        const isList = mode === 'list';
        courtView.style.display = isList ? 'none' : 'block';
        listView.style.display  = isList ? 'block' : 'none';
        btnList.classList.toggle('active', isList);
        btnCourt.classList.toggle('active', !isList);
    }
    btnCourt?.addEventListener('click', () => setRosterView('court'));
    btnList?.addEventListener('click',  () => setRosterView('list'));

    /* -- State ------------------------------------- */
    let players = [];
    let sortables = [];
    let currentSort = { field: 'role', ascending: true };
    let currentSearch = '';
    let currentRoleFilter = '';
    let pendingWaivePlayerId = null;
    let editPhotoFile = null;
    const DEFAULT_FA = { waiversUsed: 0, waiversMax: 3, signingsUsed: 0, signingsMax: 3 };
    let FA = { ...DEFAULT_FA };

    const ROLE_ORDER = { Titular: 0, Banco: 1, Outro: 2, 'G-League': 3 };
    const POS_ORDER  = { PG: 0, SG: 1, SF: 2, PF: 3, C: 4 };
    const ROLE_COLORS = {
        Titular: { bg: 'rgba(252,0,37,.12)', color: '#fc0025', border: 'rgba(252,0,37,.25)' },
        Banco:   { bg: 'rgba(59,130,246,.12)', color: '#3b82f6', border: 'rgba(59,130,246,.25)' },
        Outro:   { bg: 'rgba(134,134,144,.10)', color: '#868690', border: 'rgba(134,134,144,.2)' },
        'G-League': { bg: 'rgba(245,158,11,.12)', color: '#f59e0b', border: 'rgba(245,158,11,.25)' },
    };

    function normRole(r) {
        const s = (r || '').toLowerCase().trim();
        if (s === 'titular') return 'Titular';
        if (s === 'banco')   return 'Banco';
        if (s === 'g-league' || s === 'gleague') return 'G-League';
        return 'Outro';
    }

    function playerPhoto(p) {
        const cp = (p?.foto_adicional || '').toString().trim().replace(/\\/g, '/');
        if (cp) return /^(data:image|https?:\/\/)/.test(cp) ? cp : `/${cp.replace(/^\/+/, '')}`;
        if (p?.photo_url) return p.photo_url;
        if (p?.nba_player_id) return `https://cdn.nba.com/headshots/nba/latest/1040x760/${p.nba_player_id}.png`;
        return '/img/default-avatar.png';
    }

    function b64(file) {
        return new Promise((res, rej) => { const r = new FileReader(); r.onload = () => res(r.result); r.onerror = rej; r.readAsDataURL(file); });
    }

    async function api(url, opts = {}) {
        const r = await fetch(url, { headers: { 'Content-Type': 'application/json' }, ...opts });
        let body = {}; try { body = await r.json(); } catch(_) {}
        if (!r.ok || body.success === false) throw body;
        return body;
    }

    async function fetchPlayers() {
        if (!window.__TEAM_ID__) return;
        document.getElementById('players-status').style.display = 'block';
        try {
            const d = await api(`/api/players.php?team_id=${window.__TEAM_ID__}`, { method: 'GET' });
            players = Array.isArray(d.players) ? d.players : [];
            renderRoster();
        } catch(e) { players = []; renderRoster(); }
        finally { document.getElementById('players-status').style.display = 'none'; }
    }

    async function loadFALimits() {
        try {
            const d = await fetch('/api/free-agency.php?action=limits').then(r => r.json());
            FA = { waiversUsed: +d.waivers_used||0, waiversMax: +d.waivers_max||3, signingsUsed: +d.signings_used||0, signingsMax: +d.signings_max||3 };
        } catch(_) { FA = {...DEFAULT_FA}; }
        updateKPIs();
    }

    function capTop8(list) {
        return [...list].sort((a,b) => +b.ovr - +a.ovr).slice(0,8).reduce((s,p) => s + +p.ovr, 0);
    }

    function capAfterRemove(id) {
        return capTop8(players.filter(p => String(p.id) !== String(id)));
    }

    function capStatusText(cap) {
        if (+window.__CAP_MIN__ > 0 && cap < +window.__CAP_MIN__) return 'Ficará abaixo do CAP mínimo.';
        if (+window.__CAP_MAX__ < 999 && cap > +window.__CAP_MAX__) return 'Ficará acima do CAP máximo.';
        return 'Ficará dentro do CAP.';
    }

    function buildLocalAIAnalysis(list) {
        const strengths = [];
        const weaknesses = [];

        if (!Array.isArray(list) || list.length === 0) {
            return {
                strengths,
                weaknesses: ['Sem jogadores no elenco para análise.']
            };
        }

        const avg = list.reduce((s, p) => s + (+p.ovr || 0), 0) / list.length;
        const top = [...list].sort((a, b) => (+b.ovr || 0) - (+a.ovr || 0))[0] || null;
        const positionCounts = { PG: 0, SG: 0, SF: 0, PF: 0, C: 0 };
        list.forEach((p) => {
            const pos = (p.position || '').toUpperCase();
            if (positionCounts[pos] !== undefined) positionCounts[pos] += 1;
        });

        const missingPositions = Object.entries(positionCounts)
            .filter(([, count]) => count < 2)
            .map(([pos]) => pos);

        if (avg >= 78) strengths.push(`OVR médio sólido (${avg.toFixed(1)}), elenco competitivo.`);
        else weaknesses.push(`OVR médio em ${avg.toFixed(1)}; vale buscar mais talento no mercado.`);

        if (top && (+top.ovr || 0) >= 89) strengths.push(`${top.name} (${top.ovr} OVR) é uma estrela para liderar o time.`);
        else if (top) weaknesses.push(`Falta um franchise player (89+). Melhor jogador atual: ${top.name} (${top.ovr} OVR).`);

        if (missingPositions.length === 0) strengths.push('Boa cobertura de posições no elenco (PG/SG/SF/PF/C).');
        else weaknesses.push(`Pouca profundidade nas posições: ${missingPositions.join(', ')}.`);

        if (strengths.length === 0) strengths.push('Base do elenco montada; ajustes pontuais podem elevar o nível.');
        if (weaknesses.length === 0) weaknesses.push('Sem pontos críticos detectados no momento.');

        return { strengths, weaknesses };
    }

    function updateKPIs() {
        const cap = capTop8(players);
        const capMin = +window.__CAP_MIN__, capMax = +window.__CAP_MAX__;
        const capOk = (capMin === 0 || cap >= capMin) && (capMax === 999 || cap <= capMax);
        const el = document.getElementById('cap-top8');
        el.textContent = cap;
        el.className = 'kpi-value ' + (capOk ? 'ok' : 'warn');
        document.getElementById('total-players').textContent = players.length;
        document.getElementById('waivers-count').textContent  = `${FA.waiversUsed} / ${FA.waiversMax}`;
        document.getElementById('signings-count').textContent = `${FA.signingsUsed} / ${FA.signingsMax}`;
    }

    function applyFilters(list) {
        const term = currentSearch.toLowerCase();
        return list.filter(p => {
            const roleOk = !currentRoleFilter || normRole(p.role) === normRole(currentRoleFilter);
            if (!term) return roleOk;
            return roleOk && `${p.name} ${p.position} ${p.secondary_position||''}`.toLowerCase().includes(term);
        });
    }

    function getSorted(list) {
        const out = applyFilters([...list]);
        out.sort((a, b) => {
            let av = a[currentSort.field], bv = b[currentSort.field];
            if (currentSort.field === 'role') { av = ROLE_ORDER[normRole(av)]??999; bv = ROLE_ORDER[normRole(bv)]??999; }
            if (['ovr','age'].includes(currentSort.field)) { av = +av; bv = +bv; }
            if (av < bv) return currentSort.ascending ? -1 : 1;
            if (av > bv) return currentSort.ascending ?  1 : -1;
            if (currentSort.field === 'role' && normRole(a.role) === 'Titular') {
                return (POS_ORDER[a.position]??99) - (POS_ORDER[b.position]??99);
            }
            return 0;
        });
        return out;
    }

    function createCard(p) {
        const photo = playerPhoto(p);
        const ovrClass = getOvrClass(p.ovr);
        const div = document.createElement('div');
        div.className = 'player-card';
        div.dataset.id = p.id;
        div.innerHTML = `
            <div class="player-img-wrap">
                <img class="player-img" src="${photo}" onerror="this.src='/img/default-avatar.png'">
                <div class="player-ovr-badge ${ovrClass}">${p.ovr}</div>
            </div>
            <div class="player-name">${p.name}</div>
            <div class="player-pos-label">${p.position}</div>`;
        return div;
    }

    function getOvrClass(ovr) {
        const value = +ovr || 0;
        if (value >= 90) return 'ovr-high';
        if (value >= 82) return 'ovr-good';
        if (value >= 74) return 'ovr-mid';
        return 'ovr-low';
    }

    function renderRoster() {
        /* clear slots */
        document.querySelectorAll('.starter-slot').forEach(s => {
            const lbl = s.querySelector('.pos-label');
            const empty = s.querySelector('.slot-empty');
            s.innerHTML = '';
            if (lbl)   s.appendChild(lbl);
            if (empty) s.appendChild(empty);
        });
        document.getElementById('bench-grid').innerHTML = '';
        document.getElementById('players-table-body').innerHTML = '';

        const sorted = getSorted(players);

        sorted.forEach((p, i) => {
            const card = createCard(p);
            const role = normRole(p.role);

            if (role === 'Titular') {
                const slot = document.querySelector(`.starter-slot[data-pos="${p.position}"]`);
                const target = slot || document.getElementById('bench-grid');
                /* hide empty indicator if slot filled */
                if (slot) { const em = slot.querySelector('.slot-empty'); if (em) em.style.display = 'none'; }
                target.appendChild(card);
            } else {
                document.getElementById('bench-grid').appendChild(card);
            }

            /* table row */
            const rc = ROLE_COLORS[role] || ROLE_COLORS.Outro;
            const canRetire = +p.age >= 33;
            const tr = document.createElement('tr');
            const ovrClass = getOvrClass(p.ovr);
            tr.innerHTML = `
                <td>
                    <div class="player-cell">
                        <img class="player-avatar" src="${playerPhoto(p)}" onerror="this.src='/img/default-avatar.png'">
                        <span class="player-cell-name">${p.name}</span>
                    </div>
                </td>
                <td><span class="pos-badge">${p.position}</span></td>
                <td><span class="ovr-val ${ovrClass}">${p.ovr}</span></td>
                <td style="color:var(--text-2)">${p.age}</td>
                <td><span class="role-pill" style="background:${rc.bg};color:${rc.color};border:1px solid ${rc.border}">${role}</span></td>
                <td>${p.available_for_trade ? '<span class="tag green">Sim</span>' : '<span class="tag gray">Não</span>'}</td>
                <td>
                    <div class="tbl-actions">
                        <button class="btn-icon edit btn-edit-player" data-id="${p.id}" title="Editar"><i class="bi bi-pencil"></i></button>
                        <button class="btn-icon waive btn-waive-player" data-id="${p.id}" title="Dispensar"><i class="bi bi-person-x"></i></button>
                        ${canRetire ? `<button class="btn-icon retire btn-retire-player" data-id="${p.id}" data-name="${(p.name||'').replace(/"/g,'&quot;')}" title="Aposentar"><i class="bi bi-box-arrow-right"></i></button>` : ''}
                        <button class="btn-icon trade-toggle ${p.available_for_trade?'trade-on':'trade-off'} btn-toggle-trade" data-id="${p.id}" data-trade="${p.available_for_trade?1:0}" title="Toggle troca"><i class="bi bi-arrow-left-right"></i></button>
                    </div>
                </td>`;
            document.getElementById('players-table-body').appendChild(tr);
        });

        initSortable();
        updateKPIs();
    }

    function initSortable() {
        sortables.forEach(s => s.destroy());
        sortables = [];
        document.querySelectorAll('.starter-slot, #bench-grid').forEach(el => {
            sortables.push(new Sortable(el, {
                group: 'roster', animation: 150,
                ghostClass: 'sortable-ghost', chosenClass: 'sortable-chosen',
                onAdd: async (evt) => {
                    const pid = evt.item.dataset.id;
                    const newRole = el.dataset.role;
                    const newPos  = el.dataset.pos || null;
                    const player  = players.find(x => String(x.id) === String(pid));

                    /* displace if slot already has a player */
                    if (newRole === 'Titular') {
                        const others = [...el.querySelectorAll('.player-card')].filter(c => c !== evt.item);
                        for (const other of others) {
                            const oid = other.dataset.id;
                            const op  = players.find(x => String(x.id) === String(oid));
                            document.getElementById('bench-grid').appendChild(other);
                            await updateOnServer(oid, 'Banco', null);
                        }
                        /* hide/show empty indicator */
                        const em = el.querySelector('.slot-empty');
                        if (em) em.style.display = 'none';
                    }

                    try { await updateOnServer(pid, newRole, newPos); }
                    catch { renderRoster(); }
                }
            }));
        });
    }

    async function updateOnServer(id, role, pos) {
        const payload = { id: +id, role: normRole(role) };
        if (pos) payload.position = pos;
        await api('/api/players.php', { method: 'PUT', body: JSON.stringify(payload) });
        const p = players.find(x => x.id == id);
        if (p) { p.role = payload.role; if (pos) p.position = pos; }
        updateKPIs();
    }

    /* -- Waive modal ------------------------------- */
    function openWaiveModal(player) {
        if (!player) return;
        pendingWaivePlayerId = player.id;
        document.getElementById('waive-player-name').textContent = player.name || '—';
        const nc = capAfterRemove(player.id);
        document.getElementById('waive-player-cap').textContent = nc;
        document.getElementById('waive-cap-status').textContent  = capStatusText(nc);
        new bootstrap.Modal(document.getElementById('waivePlayerModal')).show();
    }

    async function doWaive(id) {
        try {
            const r = await api('/api/players.php', { method: 'DELETE', body: JSON.stringify({ id: +id }) });
            alert(r.message || 'Jogador dispensado.');
            await fetchPlayers(); await loadFALimits();
        } catch(e) { alert('Erro: ' + (e?.error || 'Desconhecido')); }
    }

    /* -- Edit modal -------------------------------- */
    async function openEditModal(id) {
        const p = players.find(x => String(x.id) === String(id));
        if (!p) return;
        editPhotoFile = null;
        document.getElementById('edit-player-id').value = p.id;
        document.getElementById('edit-name').value  = p.name || '';
        document.getElementById('edit-age').value   = p.age || '';
        document.getElementById('edit-position').value = p.position || 'PG';
        document.getElementById('edit-secondary-position').value = p.secondary_position || '';
        document.getElementById('edit-ovr').value   = p.ovr || '';
        document.getElementById('edit-role').value  = normRole(p.role);
        document.getElementById('edit-available').checked = !!+p.available_for_trade;
        const prev = document.getElementById('edit-foto-preview');
        const inp  = document.getElementById('edit-foto-adicional');
        if (prev) prev.src = playerPhoto(p);
        if (inp)  inp.value = '';
        new bootstrap.Modal(document.getElementById('editPlayerModal')).show();
    }

    async function toggleTrade(id, cur) {
        await api('/api/players.php', { method: 'PUT', body: JSON.stringify({ id: +id, available_for_trade: cur ? 0 : 1 }) });
        const p = players.find(x => x.id == id);
        if (p) p.available_for_trade = cur ? 0 : 1;
        renderRoster();
    }

    async function retire(id, name) {
        if (!confirm(`Aposentar ${name}?`)) return;
        try {
            const r = await api('/api/players.php', { method: 'DELETE', body: JSON.stringify({ id: +id, retirement: true }) });
            alert(r.message || 'Aposentado com sucesso.');
            await fetchPlayers();
        } catch(e) { alert('Erro: ' + (e?.error||'')); }
    }

    /* -- Event delegation -------------------------- */
    document.getElementById('players-table-body')?.addEventListener('click', async (e) => {
        const btn = e.target.closest('button');
        if (!btn) return;
        const id = btn.dataset.id;
        if (btn.classList.contains('btn-edit-player'))   { await openEditModal(id); return; }
        if (btn.classList.contains('btn-waive-player'))  { openWaiveModal(players.find(p => String(p.id) === String(id))); return; }
        if (btn.classList.contains('btn-toggle-trade'))  { await toggleTrade(id, String(btn.dataset.trade)==='1'); return; }
        if (btn.classList.contains('btn-retire-player')) { await retire(id, btn.dataset.name); return; }
    });

    /* -- Sort click on thead ----------------------- */
    document.querySelector('.roster-table thead')?.addEventListener('click', (e) => {
        const th = e.target.closest('th[data-sort]');
        if (!th) return;
        const field = th.dataset.sort;
        if (currentSort.field === field) currentSort.ascending = !currentSort.ascending;
        else { currentSort.field = field; currentSort.ascending = field !== 'role'; }
        renderRoster();
    });

    /* -- Search / filter --------------------------- */
    document.getElementById('players-search')?.addEventListener('input', e => { currentSearch = e.target.value; renderRoster(); });
    document.getElementById('players-role-filter')?.addEventListener('change', e => { currentRoleFilter = e.target.value; renderRoster(); });
    document.getElementById('sort-select')?.addEventListener('change', e => {
        currentSort.field = e.target.value; currentSort.ascending = e.target.value !== 'role';
        renderRoster();
    });

    /* -- Photo preview ----------------------------- */
    document.getElementById('edit-foto-adicional')?.addEventListener('change', (e) => {
        const file = e.target.files?.[0]; if (!file) return;
        editPhotoFile = file;
        const prev = document.getElementById('edit-foto-preview');
        if (prev.dataset.objUrl) URL.revokeObjectURL(prev.dataset.objUrl);
        const url = URL.createObjectURL(file);
        prev.src = url; prev.dataset.objUrl = url;
    });

    /* -- Save edit --------------------------------- */
    document.getElementById('btn-save-edit')?.addEventListener('click', async () => {
        const payload = {
            id: +document.getElementById('edit-player-id').value,
            name: document.getElementById('edit-name').value,
            age: +document.getElementById('edit-age').value,
            position: document.getElementById('edit-position').value,
            secondary_position: document.getElementById('edit-secondary-position').value || null,
            ovr: +document.getElementById('edit-ovr').value,
            role: document.getElementById('edit-role').value,
            available_for_trade: document.getElementById('edit-available').checked ? 1 : 0,
        };
        if (editPhotoFile) payload.foto_adicional = await b64(editPhotoFile);
        try {
            await api('/api/players.php', { method: 'PUT', body: JSON.stringify(payload) });
            bootstrap.Modal.getInstance(document.getElementById('editPlayerModal'))?.hide();
            await fetchPlayers();
        } catch(e) { alert('Erro: ' + (e?.error||'')); }
    });

    /* -- Confirm waive ----------------------------- */
    document.getElementById('btn-confirm-waive')?.addEventListener('click', async () => {
        const id = pendingWaivePlayerId; pendingWaivePlayerId = null;
        bootstrap.Modal.getInstance(document.getElementById('waivePlayerModal'))?.hide();
        await doWaive(id);
    });

    /* -- Add player form --------------------------- */
    document.getElementById('form-player')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.currentTarget);
        const payload = {
            team_id: +window.__TEAM_ID__,
            name: (fd.get('name')||'').trim(),
            age: +fd.get('age'),
            position: fd.get('position'),
            secondary_position: fd.get('secondary_position')||null,
            role: fd.get('role') || 'Titular',
            ovr: +fd.get('ovr'),
            available_for_trade: fd.get('available_for_trade') ? 1 : 0,
        };
        if (!payload.name || !payload.age || !payload.position || !payload.ovr) { alert('Preencha nome, idade, posição e OVR.'); return; }
        const btn = document.getElementById('btn-add-player');
        btn.disabled = true; btn.innerHTML = '<span style="display:inline-block;width:12px;height:12px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;animation:spin .6s linear infinite;margin-right:6px"></span> Enviando...';
        try {
            const r = await api('/api/players.php', { method: 'POST', body: JSON.stringify(payload) });
            alert(r.message || 'Jogador adicionado!');
            e.currentTarget.reset();
            document.getElementById('available_for_trade').checked = true;
            await fetchPlayers(); await loadFALimits();
        } catch(err) { alert('Erro: ' + (err?.error||'')); }
        finally { btn.disabled = false; btn.innerHTML = '<i class="bi bi-cloud-upload"></i> Cadastrar'; }
    });

    /* -- Refresh ----------------------------------- */
    document.getElementById('btn-refresh-players')?.addEventListener('click', fetchPlayers);

    /* -- AI Analysis ------------------------------- */
    document.getElementById('btn-ai-analysis')?.addEventListener('click', async () => {
        const modal = new bootstrap.Modal(document.getElementById('aiAnalysisModal'));
        modal.show();
        document.getElementById('ai-loading').style.display = 'block';
        document.getElementById('ai-results').style.display = 'none';

        const fill = (elId, items) => {
            const ul = document.getElementById(elId);
            ul.innerHTML = '';
            (items || []).forEach(txt => {
                const li = document.createElement('li');
                li.textContent = txt;
                ul.appendChild(li);
            });
        };

        try {
            const res = await fetch('/api/ai-analysis.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ team_id: window.__TEAM_ID__, players })
            });
            const d = await res.json();
            if (!d.success) throw d;
            fill('ai-strengths',  d.strengths  || []);
            fill('ai-weaknesses', d.weaknesses || []);
            document.getElementById('ai-loading').style.display = 'none';
            document.getElementById('ai-results').style.display = 'block';
        } catch(e) {
            const local = buildLocalAIAnalysis(players);
            fill('ai-strengths', local.strengths);
            fill('ai-weaknesses', local.weaknesses);
            document.getElementById('ai-loading').style.display = 'none';
            document.getElementById('ai-results').style.display = 'block';
        }
    });

    /* -- Init -------------------------------------- */
    document.addEventListener('DOMContentLoaded', async () => {
        const addCollapseEl = document.getElementById('addCollapse');
        if (addCollapseEl) addCollapseEl.classList.remove('show');
        setRosterView('list');
        await Promise.all([fetchPlayers(), loadFALimits()]);
    });
</script>
</body>
</html>