<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/helpers.php';
requireAuth();

$user = getUserSession();
$pdo = db();
$isAdmin = ($user['user_type'] ?? 'jogador') === 'admin';

$stmtTeam = $pdo->prepare('SELECT * FROM teams WHERE user_id = ? LIMIT 1');
$stmtTeam->execute([$user['id']]);
$team = $stmtTeam->fetch(PDO::FETCH_ASSOC) ?: null;
$teamId = (int)($team['id'] ?? 0);

$userLeague = strtoupper((string)($team['league'] ?? $user['league'] ?? 'ELITE'));
$userMoedas = (int)($team['moedas'] ?? 0);
$rosterLimit = 15;
$rosterCount = 0;
$pendingOffers = 0;

if ($teamId > 0) {
    try {
        $stmtRoster = $pdo->prepare('SELECT COUNT(*) FROM players WHERE team_id = ?');
        $stmtRoster->execute([$teamId]);
        $rosterCount = (int)$stmtRoster->fetchColumn();
    } catch (Throwable $e) {
        $rosterCount = 0;
    }
}

$defaultAdminLeague = $userLeague;
$leagues = ['ELITE', 'NEXT', 'RISE', 'ROOKIE'];
$useNewFreeAgency = true;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#fc0025">
    <?php include __DIR__ . '/includes/head-pwa.php'; ?>
    <title>Free Agency - FBA Manager</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/styles.css">

    <style>
        :root {
            --fa-bg: #090b10;
            --fa-panel: #11151d;
            --fa-panel-2: #171c27;
            --fa-border: rgba(255,255,255,.09);
            --fa-text: #f2f5fb;
            --fa-text-2: #98a2b7;
            --fa-red: #fc0025;
            --fa-red-soft: rgba(252,0,37,.12);
            --fa-green: #22c55e;
            --fa-amber: #f59e0b;
            --fa-cyan: #06b6d4;
            --fa-radius: 14px;
            --fa-radius-sm: 10px;
            --fa-font: 'Poppins', sans-serif;
        }

        body {
            background:
                radial-gradient(1000px 420px at 85% -10%, rgba(252,0,37,.10), transparent 60%),
                radial-gradient(900px 360px at -10% 20%, rgba(59,130,246,.10), transparent 62%),
                var(--fa-bg);
            color: var(--fa-text);
            font-family: var(--fa-font);
        }

        .dashboard-content {
            margin-left: 280px;
            min-height: 100vh;
            padding: 1.6rem;
        }

        .fa-hero {
            background: linear-gradient(160deg, rgba(17,21,29,.96), rgba(23,28,39,.96));
            border: 1px solid var(--fa-border);
            border-radius: calc(var(--fa-radius) + 2px);
            padding: 1.25rem 1.35rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .fa-eyebrow {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--fa-red);
            font-weight: 700;
        }

        .fa-title {
            font-size: 28px;
            font-weight: 800;
            margin: .15rem 0 .2rem;
            line-height: 1.05;
        }

        .fa-sub {
            color: var(--fa-text-2);
            font-size: 13px;
            margin: 0;
        }

        .fa-kpis {
            display: grid;
            grid-template-columns: repeat(3, minmax(120px, 1fr));
            gap: .55rem;
            min-width: 300px;
        }

        .fa-kpi {
            background: rgba(255,255,255,.02);
            border: 1px solid var(--fa-border);
            border-radius: var(--fa-radius-sm);
            padding: .6rem .7rem;
        }

        .fa-kpi-label {
            font-size: 10px;
            text-transform: uppercase;
            color: var(--fa-text-2);
            letter-spacing: .08em;
            font-weight: 700;
        }

        .fa-kpi-value {
            font-size: 20px;
            font-weight: 800;
            line-height: 1.1;
            margin-top: .2rem;
        }

        .fa-kpi-value.red { color: var(--fa-red); }
        .fa-kpi-value.green { color: var(--fa-green); }

        .fa-tabs {
            background: var(--fa-panel);
            border: 1px solid var(--fa-border);
            border-radius: var(--fa-radius-sm);
            padding: .35rem;
            display: flex;
            gap: .35rem;
            overflow-x: auto;
            margin-bottom: 1rem;
        }

        .fa-tabs::-webkit-scrollbar { display: none; }

        .tab-btn-r {
            border: 1px solid transparent;
            background: transparent;
            color: var(--fa-text-2);
            font-size: 12px;
            font-weight: 700;
            border-radius: 9px;
            padding: .55rem .85rem;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            transition: all .2s ease;
        }

        .tab-btn-r:hover {
            color: var(--fa-text);
            background: rgba(255,255,255,.03);
        }

        .tab-btn-r.active {
            color: #fff;
            background: linear-gradient(135deg, #fc0025, #ff3a54);
            box-shadow: 0 8px 20px rgba(252,0,37,.25);
        }

        .tab-pane-r { display: none; }
        .tab-pane-r.show { display: block; }

        .fa-panel {
            background: var(--fa-panel);
            border: 1px solid var(--fa-border);
            border-radius: var(--fa-radius);
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .fa-panel-head {
            padding: .9rem 1.05rem;
            border-bottom: 1px solid var(--fa-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .65rem;
            flex-wrap: wrap;
            background: linear-gradient(120deg, rgba(252,0,37,.06), rgba(6,182,212,.06));
        }

        .fa-panel-title {
            font-size: 14px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: .45rem;
        }

        .fa-panel-title i { color: var(--fa-red); }

        .fa-panel-body { padding: 1.05rem; }

        .f-input,
        .f-select,
        .form-control,
        .form-select {
            background: var(--fa-panel-2) !important;
            border: 1px solid var(--fa-border) !important;
            color: var(--fa-text) !important;
            border-radius: 10px !important;
            font-size: 13px;
        }

        .f-input::placeholder,
        .form-control::placeholder { color: var(--fa-text-2) !important; }

        .f-input:focus,
        .f-select:focus,
        .form-control:focus,
        .form-select:focus {
            border-color: rgba(252,0,37,.45) !important;
            box-shadow: 0 0 0 .16rem rgba(252,0,37,.12) !important;
        }

        .form-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--fa-text-2);
            font-weight: 700;
            margin-bottom: .35rem;
        }

        .btn-r {
            border: 1px solid transparent;
            border-radius: 10px;
            padding: .5rem .75rem;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            transition: all .2s ease;
        }

        .btn-r.primary { background: var(--fa-red); color: #fff; }
        .btn-r.primary:hover { filter: brightness(1.08); }
        .btn-r.ghost { background: transparent; color: var(--fa-text-2); border-color: var(--fa-border); }
        .btn-r.ghost:hover { color: var(--fa-text); background: rgba(255,255,255,.03); }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            padding: .22rem .5rem;
            border: 1px solid transparent;
        }

        .tag.green { color: var(--fa-green); background: rgba(34,197,94,.12); border-color: rgba(34,197,94,.3); }
        .tag.red { color: var(--fa-red); background: var(--fa-red-soft); border-color: rgba(252,0,37,.35); }

        .fa-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: .8rem;
        }

        .fa-admin-offers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: .85rem;
        }

        .fa-offer-card {
            background: linear-gradient(160deg, rgba(23,28,39,.92), rgba(17,21,29,.92));
            border: 1px solid var(--fa-border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 26px rgba(0, 0, 0, .25);
        }

        .fa-offer-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .6rem;
            padding: .85rem 1rem;
            border-bottom: 1px solid var(--fa-border);
            background: linear-gradient(120deg, rgba(252,0,37,.10), rgba(6,182,212,.08));
        }

        .fa-offer-player {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.2;
        }

        .fa-offer-meta {
            margin: .2rem 0 0;
            color: var(--fa-text-2);
            font-size: 12px;
        }

        .fa-offer-count {
            font-size: 11px;
            font-weight: 700;
            color: #8be9fd;
            background: rgba(6,182,212,.14);
            border: 1px solid rgba(6,182,212,.26);
            border-radius: 999px;
            padding: .2rem .55rem;
            white-space: nowrap;
        }

        .fa-offer-body {
            padding: .95rem 1rem .75rem;
        }

        .fa-offer-actions {
            padding: .8rem 1rem 1rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .55rem;
        }

        .fa-offer-actions .btn {
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            padding: .48rem .6rem;
        }

        .fa-empty {
            padding: 1.4rem;
            text-align: center;
            color: var(--fa-text-2);
            border: 1px dashed var(--fa-border);
            border-radius: 10px;
        }

        .table-dark {
            --bs-table-bg: transparent !important;
            --bs-table-striped-bg: rgba(255,255,255,.02) !important;
            --bs-table-hover-bg: rgba(252,0,37,.07) !important;
            --bs-table-color: var(--fa-text) !important;
            --bs-table-border-color: var(--fa-border) !important;
        }

        .table-dark th {
            color: var(--fa-text-2) !important;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 700;
        }

        .coin-input-wrap { position: relative; }
        .coin-icon {
            position: absolute;
            left: .65rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--fa-amber);
            pointer-events: none;
        }

        .coin-input-wrap input { padding-left: 1.8rem; }

        .free-agency-surface .modal-content {
            background: var(--fa-panel);
            border: 1px solid var(--fa-border);
            color: var(--fa-text);
            border-radius: 14px;
        }

        .free-agency-surface .modal-header,
        .free-agency-surface .modal-footer {
            border-color: var(--fa-border);
            background: rgba(255,255,255,.01);
        }

        @media (max-width: 860px) {
            .dashboard-content {
                margin-left: 0;
                padding: 4.2rem .9rem 1rem;
            }

            .fa-hero { padding: 1rem; }
            .fa-title { font-size: 23px; }
            .fa-kpis { width: 100%; min-width: 0; }
            .fa-admin-offers-grid { grid-template-columns: 1fr; }
            .fa-offer-actions { grid-template-columns: 1fr; }
            .fa-offer-actions .btn { width: 100%; }
        }
    </style>
</head>
<body class="free-agency-surface">
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Abrir menu"><i class="bi bi-list"></i></button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="dashboard-content">
    <section class="fa-hero">
        <div>
            <div class="fa-eyebrow">Mercado Livre · <?= htmlspecialchars($userLeague) ?></div>
            <h1 class="fa-title">Free Agency</h1>
            <p class="fa-sub">Visual moderno e minimalista no padrão do sistema, mantendo todas as rotinas do módulo.</p>
        </div>
        <div class="fa-kpis">
            <div class="fa-kpi">
                <div class="fa-kpi-label">Moedas</div>
                <div class="fa-kpi-value red"><?= number_format($userMoedas, 0, ',', '.') ?></div>
            </div>
            <div class="fa-kpi">
                <div class="fa-kpi-label">Elenco</div>
                <div class="fa-kpi-value"><?= $rosterCount ?>/<?= $rosterLimit ?></div>
            </div>
            <div class="fa-kpi">
                <div class="fa-kpi-label">Pendências</div>
                <div class="fa-kpi-value green" id="faPendingKpi"><?= $pendingOffers ?></div>
            </div>
        </div>
    </section>

    <div class="fa-tabs">
        <button class="tab-btn-r active" data-tab="fa-market" onclick="switchFaTab('fa-market', this)"><i class="bi bi-shop"></i> Mercado</button>
        <button class="tab-btn-r" data-tab="fa-my" onclick="switchFaTab('fa-my', this)"><i class="bi bi-send-fill"></i> Minhas Propostas <span id="faNewMyCount" class="tag red" style="display:none">0</span></button>
        <button class="tab-btn-r" id="fa-history-tab" data-tab="fa-history" onclick="switchFaTab('fa-history', this); dispatchFaTabEvent(this)"><i class="bi bi-clock-history"></i> Histórico</button>
        <?php if ($isAdmin): ?>
            <button class="tab-btn-r" id="fa-admin-tab" data-tab="fa-admin" onclick="switchFaTab('fa-admin', this); dispatchFaTabEvent(this)"><i class="bi bi-shield-lock-fill"></i> Admin</button>
        <?php endif; ?>
    </div>

    <section class="tab-pane-r show" id="tab-fa-market">
        <article class="fa-panel">
            <header class="fa-panel-head">
                <h2 class="fa-panel-title"><i class="bi bi-plus-circle-fill"></i> Solicitar Jogador</h2>
                <span id="faNewRemainingBadge" class="tag green">-</span>
            </header>
            <div class="fa-panel-body">
                <form id="faNewRequestForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="faNewPlayerName">Nome</label>
                            <input type="text" id="faNewPlayerName" class="form-control" placeholder="Ex: Stephen Curry" required>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label" for="faNewPosition">Posição</label>
                            <select id="faNewPosition" class="form-select">
                                <option value="PG">PG</option><option value="SG">SG</option><option value="SF">SF</option><option value="PF">PF</option><option value="C">C</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label" for="faNewSecondary">Pos. 2ª</label>
                            <input type="text" id="faNewSecondary" class="form-control" placeholder="SG">
                        </div>
                        <div class="col-6 col-md-1">
                            <label class="form-label" for="faNewAge">Idade</label>
                            <input type="number" id="faNewAge" class="form-control" value="24" min="16" max="45">
                        </div>
                        <div class="col-6 col-md-1">
                            <label class="form-label" for="faNewOvr">OVR</label>
                            <input type="number" id="faNewOvr" class="form-control" value="70" min="40" max="99">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="faNewOffer">Oferta</label>
                            <div class="coin-input-wrap">
                                <i class="bi bi-coin coin-icon"></i>
                                <input type="number" id="faNewOffer" class="form-control" value="1" min="0">
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn-r primary" id="faNewSubmitBtn"><i class="bi bi-send-fill"></i> Enviar Proposta</button>
                        </div>
                    </div>
                </form>
            </div>
        </article>

        <div id="faSearchInput" style="display:none" aria-hidden="true"></div>
        <div id="faPositionFilter" style="display:none" aria-hidden="true"></div>
        <div id="freeAgentsContainer" style="display:none" aria-hidden="true"></div>
    </section>

    <section class="tab-pane-r" id="tab-fa-my">
        <article class="fa-panel">
            <header class="fa-panel-head">
                <h2 class="fa-panel-title"><i class="bi bi-send-fill"></i> Minhas Propostas</h2>
                <button class="btn-r ghost" onclick="carregarMinhasPropostasNovaFA()"><i class="bi bi-arrow-clockwise"></i></button>
            </header>
            <div class="fa-panel-body">
                <div id="faNewMyRequests"><div class="fa-empty">Carregando...</div></div>
            </div>
        </article>
    </section>

    <section class="tab-pane-r" id="tab-fa-history">
        <article class="fa-panel">
            <header class="fa-panel-head">
                <h2 class="fa-panel-title"><i class="bi bi-check-circle-fill"></i> Contratações</h2>
                <select id="faHistorySeasonFilter" class="f-select" style="max-width:200px"><option value="">Todas temporadas</option></select>
            </header>
            <div class="fa-panel-body" style="padding:0">
                <div id="faHistoryContainer"><div class="fa-empty m-3">Carregando...</div></div>
            </div>
        </article>

        <article class="fa-panel">
            <header class="fa-panel-head">
                <h2 class="fa-panel-title"><i class="bi bi-person-x-fill"></i> Dispensados (Waivers)</h2>
                <div class="d-flex gap-2 flex-wrap">
                    <select id="faWaiversSeasonFilter" class="f-select"><option value="">Todas temporadas</option></select>
                    <select id="faWaiversTeamFilter" class="f-select"><option value="">Todos os times</option></select>
                </div>
            </header>
            <div class="fa-panel-body" style="padding:0">
                <div id="faWaiversContainer"><div class="fa-empty m-3">Carregando...</div></div>
            </div>
        </article>
    </section>

    <?php if ($isAdmin): ?>
    <section class="tab-pane-r" id="tab-fa-admin">
        <article class="fa-panel">
            <header class="fa-panel-head">
                <h2 class="fa-panel-title"><i class="bi bi-shield-lock-fill"></i> Propostas Pendentes</h2>
                <div class="d-flex align-items-center gap-2 flex-wrap" style="width:100%;justify-content:space-between">
                    <div class="small" style="color:var(--fa-text-2)">Aprove ou recuse diretamente pelos cards abaixo.</div>
                    <select id="adminLeagueSelect" class="f-select" onchange="onAdminLeagueChange()" style="min-width:120px">
                        <?php foreach ($leagues as $lg): ?>
                            <option value="<?= $lg ?>" <?= $lg === $userLeague ? 'selected' : '' ?>><?= $lg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </header>
            <div class="fa-panel-body">
                <div id="faNewAdminRequests"><div class="fa-empty">Carregando solicitações...</div></div>
            </div>
        </article>

        <div id="faNewAdminLeague" style="display:none" aria-hidden="true"></div>
        <div id="faLeague" style="display:none" aria-hidden="true"></div>
        <div id="faPlayerName" style="display:none" aria-hidden="true"></div>
        <div id="faPosition" style="display:none" aria-hidden="true"></div>
        <div id="faSecondaryPosition" style="display:none" aria-hidden="true"></div>
        <div id="faAge" style="display:none" aria-hidden="true"></div>
        <div id="faOvr" style="display:none" aria-hidden="true"></div>
        <div id="btnAddFreeAgent" style="display:none" aria-hidden="true"></div>
        <div id="adminFreeAgentsContainer" style="display:none" aria-hidden="true"></div>
        <div id="adminOffersContainer" style="display:none" aria-hidden="true"></div>
        <div id="faContractsHistoryContainer" style="display:none" aria-hidden="true"></div>
        <div id="faApprovedInline" style="display:none" aria-hidden="true"></div>
        <div id="faStatusToggle" style="display:none" aria-hidden="true"></div>
        <div id="faStatusBadge" style="display:none" aria-hidden="true"></div>
        <div id="faViewApprovedBtn" style="display:none" aria-hidden="true"></div>
    </section>
    <?php endif; ?>

    <button id="btnOpenAdminTab" type="button" style="display:none"></button>
</main>

<div class="modal fade" id="modalOffer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-coin me-1"></i> Enviar Proposta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Jogador: <strong id="freeAgentNomeOffer">-</strong></p>
                <input type="hidden" id="freeAgentIdOffer">
                <div class="mb-3">
                    <label class="form-label" for="offerAmount">Oferta em moedas</label>
                    <div class="coin-input-wrap">
                        <i class="bi bi-coin coin-icon"></i>
                        <input type="number" id="offerAmount" class="form-control" min="0" value="1">
                    </div>
                </div>
                <div>
                    <label class="form-label" for="offerPriority">Prioridade</label>
                    <select id="offerPriority" class="form-select">
                        <option value="1">1 - Alta</option>
                        <option value="2">2 - Média</option>
                        <option value="3">3 - Baixa</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-r ghost" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn-r primary" id="btnConfirmOffer"><i class="bi bi-send-fill"></i> Confirmar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="faApprovedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-check2-circle me-1"></i> Solicitações Aprovadas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="faApprovedList">
                <div class="fa-empty">Carregando...</div>
            </div>
        </div>
    </div>
</div>

<script>
    const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
    const userLeague = '<?= htmlspecialchars($userLeague, ENT_QUOTES) ?>';
    const defaultAdminLeague = '<?= htmlspecialchars($defaultAdminLeague, ENT_QUOTES) ?>';
    const userTeamId = <?= $teamId ?>;
    const userMoedas = <?= $userMoedas ?>;
    const rosterLimit = <?= $rosterLimit ?>;
    const useNewFreeAgency = <?= $useNewFreeAgency ? 'true' : 'false' ?>;
    let userRosterCount = <?= $rosterCount ?>;
    let userPendingOffers = <?= $pendingOffers ?>;

    function switchFaTab(id, btn) {
        document.querySelectorAll('.tab-pane-r').forEach((pane) => pane.classList.remove('show'));
        document.querySelectorAll('.tab-btn-r').forEach((item) => item.classList.remove('active'));
        const pane = document.getElementById('tab-' + id);
        if (pane) pane.classList.add('show');
        if (btn) btn.classList.add('active');
    }

    function dispatchFaTabEvent(btn) {
        if (!btn) return;
        const event = new CustomEvent('shown.bs.tab', { bubbles: true, detail: { tab: btn.dataset.tab || '' } });
        btn.dispatchEvent(event);
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/pwa.js"></script>
<script src="/js/sidebar.js"></script>
<script src="/js/free-agency.js?v=<?= time() ?>"></script>
</body>
</html>
