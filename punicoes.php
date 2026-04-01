<?php
require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';
requireAuth();
$user = getUserSession();
if (($user['user_type'] ?? 'jogador') !== 'admin') {
    header('Location: /dashboard.php');
    exit;
}
$pdo = db();

$stmtTeam = $pdo->prepare('SELECT * FROM teams WHERE user_id = ? LIMIT 1');
$stmtTeam->execute([$user['id']]);
$team = $stmtTeam->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <?php include __DIR__ . '/includes/head-pwa.php'; ?>
  <title>Punições - Admin</title>
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#0a0a0c">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/css/styles.css" />
  <style>
    .punicao-card {
      background: var(--fba-panel);
      border: 1px solid var(--fba-border);
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 12px;
    }
  </style>
<?php require_once __DIR__ . "/_sidebar-picks-theme.php"; echo $novoSidebarThemeCss; ?>
</head>
<body>
  <button class="sidebar-toggle" id="sidebarToggle">
    <i class="bi bi-list fs-4"></i>
  </button>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="dashboard-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="text-white fw-bold mb-0"><i class="bi bi-exclamation-triangle-fill me-2 text-orange"></i>Punições</h1>
    </div>

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="bg-dark-panel border-orange rounded p-3">
          <h5 class="text-white mb-3">Nova punição</h5>
          <div class="mb-3">
            <label class="form-label text-light-gray">Motivo</label>
            <select id="punicaoMotive" class="form-select bg-dark text-white border-orange"></select>
          </div>
          <div class="mb-3">
            <label class="form-label text-light-gray">Liga</label>
            <select id="punicaoLeague" class="form-select bg-dark text-white border-orange"></select>
          </div>
          <div class="mb-3">
            <label class="form-label text-light-gray">Time</label>
            <select id="punicaoTeam" class="form-select bg-dark text-white border-orange"></select>
          </div>
          <div class="mb-3">
            <label class="form-label text-light-gray">Consequência</label>
            <select id="punicaoType" class="form-select bg-dark text-white border-orange"></select>
          </div>
          <div class="mb-3" id="punicaoPickRow" style="display:none;">
            <label class="form-label text-light-gray">Pick específica</label>
            <select id="punicaoPick" class="form-select bg-dark text-white border-orange"></select>
          </div>
          <div class="mb-3" id="punicaoScopeRow" style="display:none;">
            <label class="form-label text-light-gray">Temporada</label>
            <select id="punicaoScope" class="form-select bg-dark text-white border-orange">
              <option value="current">Temporada atual</option>
              <option value="next">Próxima temporada</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label text-light-gray">Observações</label>
            <textarea id="punicaoNotes" class="form-control bg-dark text-white border-orange" rows="3" placeholder="Detalhes ou contexto..."></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label text-light-gray">Data da punição (manual)</label>
            <input type="datetime-local" id="punicaoDate" class="form-control bg-dark text-white border-orange" />
          </div>
          <button id="punicaoSubmit" class="btn btn-orange w-100"><i class="bi bi-check2-circle me-2"></i>Registrar punição</button>
        </div>
        <div class="bg-dark-panel border-orange rounded p-4 mt-3">
          <h5 class="text-white mb-3">Cadastrar motivo</h5>
          <div class="mb-3">
            <label class="form-label text-light-gray">Novo motivo</label>
            <input type="text" id="newMotiveLabel" class="form-control bg-dark text-white border-orange" placeholder="Ex: Diretrizes erradas">
          </div>
          <button class="btn btn-outline-light w-100" id="newMotiveBtn"><i class="bi bi-plus-circle me-2"></i>Salvar motivo</button>
        </div>

        <div class="bg-dark-panel border-orange rounded p-4 mt-3">
          <h5 class="text-white mb-3">Cadastrar consequência</h5>
          <div class="mb-3">
            <label class="form-label text-light-gray">Nova consequência</label>
            <input type="text" id="newPunishmentLabel" class="form-control bg-dark text-white border-orange" placeholder="Ex: Perda de pick específica">
          </div>
          <button class="btn btn-outline-light mt-3 w-100" id="newPunishmentBtn"><i class="bi bi-plus-circle me-2"></i>Salvar consequência</button>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="bg-dark-panel border-orange rounded p-4">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="text-white mb-0">Histórico do time</h5>
            <div class="d-flex flex-wrap gap-2">
              <select id="punicaoHistoryLeague" class="form-select bg-dark text-white border-orange">
                <option value="">Todas as ligas</option>
              </select>
              <select id="punicaoHistoryTeam" class="form-select bg-dark text-white border-orange">
                <option value="">Todos os times</option>
              </select>
            </div>
          </div>
          <div id="punicoesList" class="text-light-gray">Selecione um time para ver as punições.</div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/js/sidebar.js"></script>
  <script src="/js/punicoes.js"></script>
</body>
</html>

