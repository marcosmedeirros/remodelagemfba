<?php
require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';
requireAuth();

$user = getUserSession();
$pdo = db();

$stmtTeam = $pdo->prepare('SELECT * FROM teams WHERE user_id = ? LIMIT 1');
$stmtTeam->execute([$user['id']]);
$team = $stmtTeam->fetch() ?: null;
$teamId = $team['id'] ?? null;
$league = $team['league'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <?php include __DIR__ . '/includes/head-pwa.php'; ?>
  <title>Trade List - FBA Manager</title>
  
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#0a0a0c">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="FBA Manager">
  <link rel="apple-touch-icon" href="/img/icon-192.png">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/css/styles.css" />
  <style>
    .player-card { background: var(--fba-card-bg); border: 1px solid var(--fba-border); border-radius: 8px; padding: 12px; margin-bottom: 12px; }
    .player-card:hover { border-color: var(--fba-orange); box-shadow: 0 4px 12px rgba(241, 117, 7, 0.15); }
    .player-name { font-weight: 600; color: var(--fba-text); }
    .player-meta { font-size: 0.9rem; color: var(--fba-text-muted); }
    .team-chip { display: inline-flex; align-items: center; gap: 8px; background: var(--fba-dark-bg); border: 1px solid var(--fba-border); padding: 6px 10px; border-radius: 20px; }
    .team-chip img { width: 24px; height: 24px; border-radius: 50%; object-fit: cover; }
    .search-bar { display: flex; gap: 10px; }
  </style>
<?php require_once __DIR__ . "/_sidebar-picks-theme.php"; echo $novoSidebarThemeCss; ?>
</head>
<body>
  <!-- Botão Hamburguer para Mobile -->
  <button class="sidebar-toggle" id="sidebarToggle">
    <i class="bi bi-list fs-4"></i>
  </button>
  
  <!-- Overlay para fechar sidebar no mobile -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="dashboard-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="text-white fw-bold mb-0"><i class="bi bi-list-stars me-2 text-orange"></i>Trade List</h1>
      <span class="badge bg-secondary" id="countBadge">0 jogadores</span>
    </div>

    <div class="search-bar mb-3">
      <input type="text" class="form-control bg-dark text-white border-orange" id="searchInput" placeholder="Procurar por nome...">
      <select class="form-select bg-dark text-white border-orange" id="sortSelect">
        <option value="ovr_desc">OVR (Maior primeiro)</option>
        <option value="ovr_asc">OVR (Menor primeiro)</option>
        <option value="name_asc">Nome (A-Z)</option>
        <option value="name_desc">Nome (Z-A)</option>
        <option value="age_asc">Idade (Menor primeiro)</option>
        <option value="age_desc">Idade (Maior primeiro)</option>
        <option value="position_asc">Posição (A-Z)</option>
        <option value="position_desc">Posição (Z-A)</option>
        <option value="team_asc">Time (A-Z)</option>
        <option value="team_desc">Time (Z-A)</option>
      </select>
    </div>

    <div id="playersList"></div>
  </div>

  <script>
  window.__USER_LEAGUE__ = '<?= htmlspecialchars($league, ENT_QUOTES) ?>';
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/js/sidebar.js"></script>
  <script src="/js/trade-list.js"></script>
  <script src="/js/pwa.js"></script>
</body>
</html>

