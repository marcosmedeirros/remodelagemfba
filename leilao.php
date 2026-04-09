<?php
declare(strict_types=1);

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

$defaultLeagueId = null;
if (!empty($team['league_id'])) {
    $defaultLeagueId = (int)$team['league_id'];
}

$leagues = [];
try {
    $stmtLeagues = $pdo->query('SELECT id, name FROM leagues ORDER BY id ASC');
    $leagues = $stmtLeagues ? $stmtLeagues->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable $e) { $leagues = []; }

if ($defaultLeagueId === null && !empty($team['league']) && $leagues) {
    foreach ($leagues as $league) {
        if (strcasecmp((string)$league['name'], (string)$team['league']) === 0) {
            $defaultLeagueId = (int)$league['id'];
            break;
        }
    }
}
if ($defaultLeagueId === null && $leagues) {
    $defaultLeagueId = (int)$leagues[0]['id'];
}

$userLeague = strtoupper((string)($team['league'] ?? $user['league'] ?? ''));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#fc0025">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <?php include __DIR__ . '/includes/head-pwa.php'; ?>
    <title>Leilão - FBA Manager</title>

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
        .content { padding: 20px 32px 40px; flex: 1; }

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
        .btn-r.danger { background: rgba(239,68,68,.12); color: #ef4444; border-color: rgba(239,68,68,.25); }
        .btn-r.danger:hover { background: #ef4444; color: #fff; }
        .btn-r.sm { padding: 5px 10px; font-size: 11px; }
        .btn-r:disabled { opacity: .45; pointer-events: none; }

        /* ── Tags ────────────────────────────────────────── */
        .tag { display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; }
        .tag.green  { background: rgba(34,197,94,.12); color: var(--green); border: 1px solid rgba(34,197,94,.2); }
        .tag.amber  { background: rgba(245,158,11,.12); color: var(--amber); border: 1px solid rgba(245,158,11,.2); }
        .tag.blue   { background: rgba(59,130,246,.12); color: var(--blue);  border: 1px solid rgba(59,130,246,.2); }
        .tag.gray   { background: var(--panel-3); color: var(--text-2); border: 1px solid var(--border); }
        .tag.red    { background: var(--red-soft); color: var(--red); border: 1px solid var(--border-red); }

        /* ── Form ────────────────────────────────────────── */
        .form-control, .form-select { background: var(--panel-2) !important; border: 1px solid var(--border) !important; border-radius: var(--radius-sm) !important; color: var(--text) !important; font-family: var(--font); font-size: 13px; }
        .form-control:focus, .form-select:focus { border-color: var(--red) !important; box-shadow: 0 0 0 .18rem rgba(252,0,37,.15) !important; outline: none !important; }
        .form-control::placeholder { color: var(--text-3) !important; }
        .form-select option { background: var(--panel-2); }
        .form-label { font-size: 11px; font-weight: 600; letter-spacing: .4px; text-transform: uppercase; color: var(--text-2); margin-bottom: 5px; display: block; }
        .form-check-input { background-color: var(--panel-2) !important; border-color: var(--border-md) !important; }
        .form-check-input:checked { background-color: var(--red) !important; border-color: var(--red) !important; }
        .form-check-label { font-size: 13px; color: var(--text-2); }
        .input-group .form-control { border-radius: var(--radius-sm) 0 0 var(--radius-sm) !important; }
        .input-group .btn { border-radius: 0 var(--radius-sm) var(--radius-sm) 0 !important; }

        /* ── Layout grid ─────────────────────────────────── */
        .two-col { display: grid; grid-template-columns: 1fr 360px; gap: 14px; }

        /* ── League filter bar ───────────────────────────── */
        .league-filter-bar {
            display: flex; align-items: center; gap: 10px;
            background: var(--panel); border: 1px solid var(--border);
            border-radius: var(--radius-sm); padding: 10px 14px;
            margin-bottom: 18px; flex-wrap: wrap;
        }
        .league-filter-bar label { font-size: 12px; font-weight: 600; color: var(--text-2); flex-shrink: 0; }
        .f-select-sm { background: var(--panel-2); border: 1px solid var(--border); border-radius: 8px; padding: 6px 10px; color: var(--text); font-family: var(--font); font-size: 12px; outline: none; cursor: pointer; min-width: 140px; }
        .f-select-sm:focus { border-color: var(--red); }
        .f-select-sm option { background: var(--panel-2); }

        /* ── Empty / loading ─────────────────────────────── */
        .empty-r { padding: 36px 20px; text-align: center; color: var(--text-3); }
        .empty-r i { font-size: 26px; display: block; margin-bottom: 10px; }
        .empty-r p { font-size: 13px; }
        .spinner-r { display: inline-block; width: 22px; height: 22px; border: 2px solid var(--border); border-top-color: var(--red); border-radius: 50%; animation: spin .6s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loading-inline { padding: 24px 18px; display: flex; align-items: center; gap: 10px; color: var(--text-2); font-size: 13px; }

        /* ── Search results list-group ───────────────────── */
        .list-group { border-radius: var(--radius-sm); overflow: hidden; }
        .list-group-item { background: var(--panel-2) !important; border-color: var(--border) !important; color: var(--text) !important; font-size: 13px; cursor: pointer; padding: 9px 14px; transition: background var(--t) var(--ease); }
        .list-group-item:hover { background: var(--panel-3) !important; }

        /* ── Modal ───────────────────────────────────────── */
        .modal-content { background: var(--panel) !important; border: 1px solid var(--border-md) !important; border-radius: var(--radius) !important; font-family: var(--font); color: var(--text); }
        .modal-header { background: var(--panel-2) !important; border-color: var(--border) !important; padding: 16px 20px; border-radius: var(--radius) var(--radius) 0 0 !important; }
        .modal-title { font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .modal-title i { color: var(--red); }
        .modal-body { padding: 20px; }
        .modal-footer { background: var(--panel-2) !important; border-color: var(--border) !important; padding: 14px 20px; border-radius: 0 0 var(--radius) var(--radius) !important; }
        .btn-close-white { filter: invert(1); }

        /* Modal scrollable area */
        .offer-scroll { max-height: 220px; overflow-y: auto; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 10px; background: var(--panel-2); scrollbar-width: thin; scrollbar-color: var(--red) transparent; }

        /* ── Animations ──────────────────────────────────── */
        @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .panel { animation: fadeUp .35s var(--ease) both; }

        /* ── Responsive ──────────────────────────────────── */
        @media (max-width: 1100px) { .two-col { grid-template-columns: 1fr; } }
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
            <a href="/free-agency.php"><i class="bi bi-coin"></i> Free Agency</a>
            <a href="/leilao.php" class="active"><i class="bi bi-hammer"></i> Leilão</a>
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
        <div class="topbar-title">FBA <em>Leilão</em></div>
    </header>

    <!-- ══════════ MAIN ══════════════════════════════════ -->
    <main class="main">

        <!-- Hero -->
        <div class="dash-hero">
            <div>
                <div class="dash-eyebrow">Mercado · <?= htmlspecialchars($userLeague) ?></div>
                <h1 class="dash-title">Leilão</h1>
                <p class="dash-sub">Negocie jogadores por propostas de troca e picks</p>
            </div>
        </div>

        <div class="content">

            <!-- League filter -->
            <div class="league-filter-bar">
                <label><i class="bi bi-funnel" style="margin-right:4px"></i> Liga:</label>
                <select id="leagueFilter" class="f-select-sm">
                    <option value="">Todas</option>
                    <?php foreach ($leagues as $league): ?>
                    <option value="<?= (int)$league['id'] ?>"
                        <?= ($defaultLeagueId !== null && (int)$league['id'] === $defaultLeagueId) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$league['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <span style="font-size:12px;color:var(--text-3)">Filtre os leilões por liga</span>
            </div>

            <!-- Two-col layout: leilões ativos + minhas propostas -->
            <div class="two-col">

                <!-- Leilões ativos -->
                <div class="panel" style="animation-delay:.04s">
                    <div class="panel-head">
                        <div class="panel-title"><i class="bi bi-hammer"></i> Leilões Ativos</div>
                        <button class="btn-r ghost sm" onclick="carregarLeiloesAtivos()">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                    <div class="panel-body" style="padding:0">
                        <div id="leiloesAtivosContainer">
                            <div class="loading-inline"><div class="spinner-r"></div> Carregando leilões...</div>
                        </div>
                    </div>
                </div>

                <!-- Minhas propostas -->
                <div class="panel" style="animation-delay:.08s">
                    <div class="panel-head">
                        <div class="panel-title"><i class="bi bi-send-fill"></i> Minhas Propostas</div>
                        <button class="btn-r ghost sm" onclick="carregarMinhasPropostas?.()">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                    <div class="panel-body" style="padding:0">
                        <div id="minhasPropostasContainer">
                            <div class="loading-inline"><div class="spinner-r"></div> Carregando...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Propostas recebidas -->
            <?php if ($teamId > 0): ?>
            <div class="panel" style="animation-delay:.12s">
                <div class="panel-head">
                    <div class="panel-title"><i class="bi bi-inbox-fill"></i> Propostas Recebidas</div>
                </div>
                <div class="panel-body" style="padding:0">
                    <div id="propostasRecebidasContainer">
                        <div class="loading-inline"><div class="spinner-r"></div> Carregando...</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Histórico -->
            <div class="panel" style="animation-delay:.16s">
                <div class="panel-head">
                    <div class="panel-title"><i class="bi bi-clock-history"></i> Histórico de Leilões</div>
                </div>
                <div class="panel-body" style="padding:0">
                    <div id="leiloesHistoricoContainer">
                        <div class="loading-inline"><div class="spinner-r"></div> Carregando...</div>
                    </div>
                </div>
            </div>

            <!-- Admin panel -->
            <?php if ($isAdmin): ?>
            <div class="panel" style="animation-delay:.20s">
                <div class="panel-head">
                    <div class="panel-title"><i class="bi bi-shield-lock-fill"></i> Painel Admin</div>
                    <div style="display:flex;gap:8px;align-items:center">
                        <label style="font-size:12px;color:var(--text-2);margin:0">Liga:</label>
                        <select id="selectLeague" class="f-select-sm">
                            <option value="">Selecione</option>
                            <?php foreach ($leagues as $league): ?>
                            <option value="<?= (int)$league['id'] ?>"
                                data-league-name="<?= htmlspecialchars((string)$league['name'], ENT_QUOTES) ?>"
                                <?= ($defaultLeagueId !== null && (int)$league['id'] === $defaultLeagueId) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$league['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="panel-body">

                    <!-- Mode switch -->
                    <div style="display:flex;gap:16px;margin-bottom:18px;flex-wrap:wrap">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="auctionMode" id="auctionModeSearch" checked>
                            <label class="form-check-label" for="auctionModeSearch">Buscar jogador existente</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="auctionMode" id="auctionModeCreate">
                            <label class="form-check-label" for="auctionModeCreate">Criar novo jogador</label>
                        </div>
                    </div>

                    <!-- Search area -->
                    <div id="auctionSearchArea" style="margin-bottom:18px">
                        <label class="form-label">Buscar por nome</label>
                        <div class="input-group" style="max-width:420px">
                            <input type="text" id="auctionPlayerSearch" class="form-control" placeholder="Nome do jogador...">
                            <button class="btn-r primary" id="auctionSearchBtn" type="button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        <div id="auctionPlayerResults" class="list-group" style="display:none;max-width:420px;margin-top:6px"></div>
                        <input type="hidden" id="auctionSelectedPlayerId">
                        <input type="hidden" id="auctionSelectedTeamId">
                        <small id="auctionSelectedLabel" style="display:none;font-size:12px;color:var(--green);margin-top:6px;display:flex;align-items:center;gap:5px">
                            <i class="bi bi-check-circle-fill"></i> <span></span>
                        </small>
                    </div>

                    <!-- Create area -->
                    <div id="auctionCreateArea" style="display:none;margin-bottom:18px">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Nome</label>
                                <input type="text" id="auctionPlayerName" class="form-control" placeholder="Nome do jogador">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Posição</label>
                                <select id="auctionPlayerPosition" class="form-select">
                                    <option>PG</option><option>SG</option><option>SF</option><option>PF</option><option>C</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Idade</label>
                                <input type="number" id="auctionPlayerAge" class="form-control" value="25" min="16" max="45">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">OVR</label>
                                <input type="number" id="auctionPlayerOvr" class="form-control" value="70" min="40" max="99">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button id="btnCriarJogadorLeilao" type="button" class="btn-r blue w-100">
                                    <i class="bi bi-plus-lg"></i> Criar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Cadastrar button -->
                    <div style="margin-bottom:24px">
                        <button id="btnCadastrarLeilao" type="button" class="btn-r primary" disabled>
                            <i class="bi bi-hammer"></i> Cadastrar no Leilão
                        </button>
                    </div>

                    <!-- Admin sub-panels -->
                    <div class="two-col" style="gap:12px">
                        <div class="panel" style="margin-bottom:0;animation:none">
                            <div class="panel-head">
                                <div class="panel-title" style="font-size:13px"><i class="bi bi-list-ul"></i> Leilões Admin</div>
                            </div>
                            <div class="panel-body" style="padding:0">
                                <div id="adminLeiloesContainer">
                                    <div class="loading-inline"><div class="spinner-r"></div> Carregando...</div>
                                </div>
                            </div>
                        </div>
                        <div class="panel" style="margin-bottom:0;animation:none">
                            <div class="panel-head">
                                <div class="panel-title" style="font-size:13px"><i class="bi bi-hourglass-split"></i> Criados / Pendentes</div>
                            </div>
                            <div class="panel-body" style="padding:0">
                                <div id="auctionTempList">
                                    <div class="loading-inline"><div class="spinner-r"></div> Carregando...</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?php endif; ?>

        </div><!-- /content -->
    </main>
</div>

<!-- ══════════ MODAL: PROPOSTA ══════════════════════════ -->
<div class="modal fade" id="modalProposta" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-send-fill"></i>
                    Proposta — <span id="jogadorLeilaoNome" style="color:var(--red)">—</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="leilaoIdProposta">
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="form-label">Notas da proposta</label>
                        <input type="text" id="notasProposta" class="form-control"
                               placeholder="Ex: Incluo jogador jovem + pick 2ª rodada">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observações adicionais</label>
                        <textarea id="obsProposta" class="form-control" rows="2"
                                  placeholder="Detalhes opcionais sobre a proposta..."></textarea>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div style="font-size:13px;font-weight:700;margin-bottom:10px;display:flex;align-items:center;gap:6px">
                            <i class="bi bi-person-fill" style="color:var(--red)"></i> Meus Jogadores para Troca
                        </div>
                        <div id="meusJogadoresParaTroca" class="offer-scroll"></div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div style="font-size:13px;font-weight:700;margin-bottom:10px;display:flex;align-items:center;gap:6px">
                            <i class="bi bi-calendar-check" style="color:var(--amber)"></i> Minhas Picks para Troca
                        </div>
                        <div id="minhasPicksParaTroca" class="offer-scroll"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-r ghost" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnEnviarProposta" class="btn-r primary">
                    <i class="bi bi-send-fill"></i> Enviar Proposta
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════ MODAL: VER PROPOSTAS ════════════════════ -->
<div class="modal fade" id="modalVerPropostas" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-inbox-fill"></i> Propostas Recebidas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="leilaoIdVerPropostas">
                <div id="listaPropostasRecebidas">
                    <div class="loading-inline"><div class="spinner-r"></div> Carregando propostas...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-r ghost" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════ SCRIPTS ══════════════════════════════════ -->
<script>
    const isAdmin        = <?= $isAdmin ? 'true' : 'false' ?>;
    const userTeamId     = <?= $teamId > 0 ? (int)$teamId : 'null' ?>;
    let   currentLeagueId = <?= $defaultLeagueId !== null ? (int)$defaultLeagueId : 'null' ?>;
    let   faStatusEnabled = true;

    /* ── Sidebar mobile ─────────────────────────────────── */
    const sidebar   = document.getElementById('sidebar');
    const sbOverlay = document.getElementById('sbOverlay');
    document.getElementById('menuBtn')?.addEventListener('click', () => { sidebar.classList.toggle('open'); sbOverlay.classList.toggle('show'); });
    sbOverlay.addEventListener('click', () => { sidebar.classList.remove('open'); sbOverlay.classList.remove('show'); });

    /* ── League filter ──────────────────────────────────── */
    const leagueFilter = document.getElementById('leagueFilter');
    const selectLeague = document.getElementById('selectLeague');

    leagueFilter?.addEventListener('change', () => {
        currentLeagueId = leagueFilter.value ? +leagueFilter.value : null;
        if (typeof carregarLeiloesAtivos === 'function') carregarLeiloesAtivos();
        if (typeof carregarHistoricoLeiloes === 'function') carregarHistoricoLeiloes();
    });

    /* Keep admin selectLeague in sync if no value */
    if (selectLeague && leagueFilter && !selectLeague.value && leagueFilter.value) {
        selectLeague.value = leagueFilter.value;
    }

    /* ── Auction mode switch ────────────────────────────── */
    document.getElementById('auctionModeSearch')?.addEventListener('change', () => {
        document.getElementById('auctionSearchArea').style.display = '';
        document.getElementById('auctionCreateArea').style.display = 'none';
    });
    document.getElementById('auctionModeCreate')?.addEventListener('change', () => {
        document.getElementById('auctionSearchArea').style.display = 'none';
        document.getElementById('auctionCreateArea').style.display = '';
    });

    /* ── Stagger animation delays ───────────────────────── */
    document.querySelectorAll('.panel').forEach((el, i) => {
        if (!el.style.animationDelay) el.style.animationDelay = (i * 0.04 + 0.04) + 's';
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/pwa.js"></script>
<script src="/js/leilao.js?v=<?= time() ?>"></script>
</body>
</html>