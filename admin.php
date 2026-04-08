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

// Buscar time do usuário (se tiver)
$stmtTeam = $pdo->prepare('SELECT * FROM teams WHERE user_id = ? LIMIT 1');
$stmtTeam->execute([$user['id']]);
$team = $stmtTeam->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
  <meta name="theme-color" content="#fc0025">
  <title>Admin - Ligas</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root {
      --bg: #0a0a0a;
      --surface: #141414;
      --border: #262626;
      --text-1: #ffffff;
      --text-2: #a3a3a3;
      --text-3: #737373;
      --red: #ff3b30;
      --green: #34c759;
      --orange: #ff9500;
    }

    body {
      background-color: var(--bg);
      color: var(--text-1);
      font-family: 'Space Grotesk', sans-serif;
      margin: 0;
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar Styles - Guideline 10 */
    .sidebar a {
      text-decoration: none !important;
    }
    /* Guideline 9 */
    .sidebar a.active {
      background: rgba(255, 255, 255, 0.05);
      color: var(--text-1);
    }

    .main-layout {
      display: flex;
      width: 100%;
    }

    /* Guideline 5: Padding do content */
    .dashboard-content {
      flex: 1;
      padding: 20px 32px 40px;
      overflow-x: hidden;
    }

    /* Guideline 6: Seções de label */
    .section-label {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 1px;
      text-transform: uppercase;
      color: var(--text-3);
      margin-bottom: 16px;
      display: block;
    }

    /* Guideline 7: Loading spinner */
    .loading-spinner {
      width: 24px;
      height: 24px;
      border: 2px solid var(--border);
      border-top-color: var(--red);
      border-radius: 50%;
      animation: spin .6s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .page-header h1 {
      font-size: 24px;
      font-weight: 700;
      letter-spacing: -0.5px;
    }

    .breadcrumb-admin {
      margin-bottom: 24px;
    }
    .breadcrumb-item a {
      color: var(--text-2);
      text-decoration: none;
      font-size: 14px;
    }
    .breadcrumb-item.active {
      color: var(--text-1);
    }
    .breadcrumb-item + .breadcrumb-item::before {
      color: var(--text-3);
    }

    /* Admin specific cards - Preserving logic/classes */
    .admin-check-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 20px;
      transition: border-color 0.2s;
    }
    .admin-check-card.is-accepted {
      border-color: var(--green) !important;
    }
    /* Guideline 8: Never use hardcoded colors */
    .admin-check-card:not(.is-accepted) {
      border-color: var(--red) !important;
    }

    #mainContainer {
      display: grid;
      gap: 24px;
    }

    /* Utility classes using tokens */
    .text-orange { color: var(--orange) !important; }
    .text-red { color: var(--red) !important; }
    .text-green { color: var(--green) !important; }
    .text-muted { color: var(--text-3) !important; }

    @media (max-width: 768px) {
      .dashboard-content {
        padding: 20px 16px 40px;
      }
    }
  </style>
</head>
<body>
  <div class="main-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
      <!-- Breadcrumb Navigation - Preserving ID -->
      <div class="breadcrumb-admin" id="breadcrumbContainer" style="display: none;">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0" id="breadcrumb">
            <li class="breadcrumb-item"><a href="#" onclick="showHome(); return false;">Admin</a></li>
          </ol>
        </nav>
      </div>

      <!-- Header -->
      <div class="page-header mb-4">
        <span class="section-label">Painel de Controle</span>
        <h1 class="text-white mb-0">
          <i class="bi bi-shield-lock-fill me-2 text-orange"></i>
          <span id="pageTitle">Painel Administrativo</span>
        </h1>
      </div>

      <!-- Container Principal - Preserving ID for JS -->
      <div id="mainContainer">
        <!-- Conteúdo será carregado dinamicamente via admin.js -->
        <div class="d-flex justify-content-center py-5">
          <div class="loading-spinner"></div>
        </div>
      </div>
    </main>
  </div>

  <!-- Scripts - Preserving all external JS files -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/js/sidebar.js?v=<?= time() ?>"></script>
  <script src="/js/admin.js?v=<?= time() ?>"></script>
  <script src="/js/seasons.js?v=<?= time() ?>"></script>
  <script src="/js/pwa.js"></script>
</body>
</html>
