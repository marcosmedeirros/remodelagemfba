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

$defaultLeagueId = null;
if (!empty($team['league_id'])) {
    $defaultLeagueId = (int)$team['league_id'];
}

$leagues = [];
try {
    $stmtLeagues = $pdo->query('SELECT id, name FROM leagues ORDER BY id ASC');
    $leagues = $stmtLeagues ? $stmtLeagues->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable $e) {
    $leagues = [];
}

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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#fc0025">
    <?php include __DIR__ . '/includes/head-pwa.php'; ?>
    <title>Leilao - FBA Manager</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/styles.css">

    <style>
        .dashboard-content { margin-left: 280px; padding: 1.5rem; }
        .bg-dark-panel { background: #121826; border: 1px solid rgba(255,255,255,.12); border-radius: 14px; }
        .text-light-gray { color: #a5b1c4; }
        .text-orange { color: #ff9f43; }
        @media (max-width: 860px) {
            .dashboard-content { margin-left: 0; padding-top: 4rem; }
        }
    </style>
</head>
<body>
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Abrir menu"><i class="bi bi-list"></i></button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="dashboard-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-hammer me-2"></i>Leilao</h1>
            <p class="text-light-gray mb-0">Negocie jogadores por propostas de troca e picks</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <label for="leagueFilter" class="small text-light-gray mb-0">Liga:</label>
            <select id="leagueFilter" class="form-select form-select-sm" style="min-width:160px">
                <option value="">Todas</option>
                <?php foreach ($leagues as $league): ?>
                    <option value="<?= (int)$league['id'] ?>" <?= ($defaultLeagueId !== null && (int)$league['id'] === $defaultLeagueId) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$league['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card bg-dark-panel">
                <div class="card-header"><strong>Leiloes ativos</strong></div>
                <div class="card-body" id="leiloesAtivosContainer">
                    <div class="text-light-gray">Carregando...</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card bg-dark-panel h-100">
                <div class="card-header"><strong>Minhas propostas</strong></div>
                <div class="card-body" id="minhasPropostasContainer">
                    <div class="text-light-gray">Carregando...</div>
                </div>
            </div>
        </div>

        <?php if ($teamId > 0): ?>
        <div class="col-12">
            <div class="card bg-dark-panel">
                <div class="card-header"><strong>Propostas recebidas</strong></div>
                <div class="card-body" id="propostasRecebidasContainer">
                    <div class="text-light-gray">Carregando...</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="col-12">
            <div class="card bg-dark-panel">
                <div class="card-header"><strong>Historico de leiloes</strong></div>
                <div class="card-body" id="leiloesHistoricoContainer">
                    <div class="text-light-gray">Carregando...</div>
                </div>
            </div>
        </div>

        <?php if ($isAdmin): ?>
        <div class="col-12">
            <div class="card bg-dark-panel">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <strong>Painel admin</strong>
                    <div class="d-flex gap-2 align-items-center">
                        <label for="selectLeague" class="small text-light-gray mb-0">Liga:</label>
                        <select id="selectLeague" class="form-select form-select-sm" style="min-width:160px">
                            <option value="">Selecione</option>
                            <?php foreach ($leagues as $league): ?>
                                <option value="<?= (int)$league['id'] ?>" data-league-name="<?= htmlspecialchars((string)$league['name'], ENT_QUOTES) ?>" <?= ($defaultLeagueId !== null && (int)$league['id'] === $defaultLeagueId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)$league['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3 d-flex gap-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="auctionMode" id="auctionModeSearch" checked>
                            <label class="form-check-label" for="auctionModeSearch">Buscar jogador</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="auctionMode" id="auctionModeCreate">
                            <label class="form-check-label" for="auctionModeCreate">Criar jogador</label>
                        </div>
                    </div>

                    <div id="auctionSearchArea" class="mb-3">
                        <label class="form-label">Buscar por nome</label>
                        <div class="input-group mb-2">
                            <input type="text" id="auctionPlayerSearch" class="form-control" placeholder="Nome do jogador">
                            <button class="btn btn-outline-light" id="auctionSearchBtn" type="button"><i class="bi bi-search"></i></button>
                        </div>
                        <div id="auctionPlayerResults" class="list-group mb-2" style="display:none"></div>
                        <input type="hidden" id="auctionSelectedPlayerId">
                        <input type="hidden" id="auctionSelectedTeamId">
                        <small id="auctionSelectedLabel" class="text-light-gray" style="display:none"></small>
                    </div>

                    <div id="auctionCreateArea" class="mb-3" style="display:none">
                        <div class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label">Nome</label>
                                <input type="text" id="auctionPlayerName" class="form-control" placeholder="Nome do jogador">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Posicao</label>
                                <select id="auctionPlayerPosition" class="form-select">
                                    <option value="PG">PG</option>
                                    <option value="SG">SG</option>
                                    <option value="SF">SF</option>
                                    <option value="PF">PF</option>
                                    <option value="C">C</option>
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
                            <div class="col-md-1 d-flex align-items-end">
                                <button id="btnCriarJogadorLeilao" type="button" class="btn btn-outline-info w-100" title="Criar jogador">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mb-3">
                        <button id="btnCadastrarLeilao" type="button" class="btn btn-danger" disabled>
                            <i class="bi bi-hammer me-1"></i>Cadastrar no leilao
                        </button>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-xl-6">
                            <div class="card bg-dark border-secondary h-100">
                                <div class="card-header">Leiloes admin</div>
                                <div class="card-body" id="adminLeiloesContainer">
                                    <div class="text-light-gray">Carregando...</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-xl-6">
                            <div class="card bg-dark border-secondary h-100">
                                <div class="card-header">Criados/Pendentes</div>
                                <div class="card-body" id="auctionTempList">
                                    <div class="text-light-gray">Carregando...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<div class="modal fade" id="modalProposta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Enviar proposta - <span id="jogadorLeilaoNome"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="leilaoIdProposta">
                <div class="mb-3">
                    <label class="form-label">Notas da proposta</label>
                    <input type="text" id="notasProposta" class="form-control" placeholder="Ex: Incluo jogador jovem + pick 2a rodada">
                </div>
                <div class="mb-3">
                    <label class="form-label">Observacoes</label>
                    <textarea id="obsProposta" class="form-control" rows="2" placeholder="Detalhes opcionais"></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <h6 class="mb-2">Meus jogadores para troca</h6>
                        <div id="meusJogadoresParaTroca" class="border border-secondary rounded p-2" style="max-height:220px;overflow:auto"></div>
                    </div>
                    <div class="col-12 col-md-6">
                        <h6 class="mb-2">Minhas picks para troca</h6>
                        <div id="minhasPicksParaTroca" class="border border-secondary rounded p-2" style="max-height:220px;overflow:auto"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnEnviarProposta" class="btn btn-danger">Enviar proposta</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalVerPropostas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Propostas recebidas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="leilaoIdVerPropostas">
                <div id="listaPropostasRecebidas">
                    <div class="text-light-gray">Carregando...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
    const userTeamId = <?= $teamId > 0 ? (int)$teamId : 'null' ?>;
    let currentLeagueId = <?= $defaultLeagueId !== null ? (int)$defaultLeagueId : 'null' ?>;
    let faStatusEnabled = true;

    const leagueFilter = document.getElementById('leagueFilter');
    const selectLeague = document.getElementById('selectLeague');

    leagueFilter?.addEventListener('change', () => {
        currentLeagueId = leagueFilter.value || null;
        carregarLeiloesAtivos();
        carregarHistoricoLeiloes();
    });

    if (selectLeague && leagueFilter && !selectLeague.value && leagueFilter.value) {
        selectLeague.value = leagueFilter.value;
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/pwa.js"></script>
<script src="/js/sidebar.js"></script>
<script src="/js/leilao.js?v=<?= time() ?>"></script>
</body>
</html>
