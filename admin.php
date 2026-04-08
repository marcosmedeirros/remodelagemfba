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
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="FBA Manager">
  <meta name="mobile-web-app-capable" content="yes">
  <link rel="manifest" href="/manifest.json?v=3">
  <link rel="apple-touch-icon" href="/img/fba-logo.png?v=3">
  <title>Admin - Ligas</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/css/styles.css" />
  <style>
    body {
      background: #141414;
      color: #f3f4f6;
      transition: background-color .2s ease, color .2s ease;
    }

    .dashboard-content {
      transition: background-color .2s ease, color .2s ease;
    }

    :root[data-theme="light"] body {
      background: #f3f4f8;
      color: #111827;
    }

    :root[data-theme="light"] .dashboard-content {
      background: transparent;
      color: #111827;
    }

    :root[data-theme="light"] .page-header h1,
    :root[data-theme="light"] #pageTitle,
    :root[data-theme="light"] .text-white {
      color: #111827 !important;
    }

    :root[data-theme="light"] .breadcrumb,
    :root[data-theme="light"] .breadcrumb-item,
    :root[data-theme="light"] .breadcrumb-item a {
      color: #475569 !important;
    }

    :root[data-theme="light"] .card,
    :root[data-theme="light"] .bg-dark,
    :root[data-theme="light"] .bg-dark-panel,
    :root[data-theme="light"] .table-dark,
    :root[data-theme="light"] .modal-content {
      background: #ffffff !important;
      color: #111827 !important;
      border-color: rgba(15, 23, 42, 0.12) !important;
    }

    :root[data-theme="light"] .table-dark {
      --bs-table-bg: #ffffff;
      --bs-table-color: #111827;
      --bs-table-border-color: rgba(15, 23, 42, 0.12);
      --bs-table-striped-bg: rgba(15, 23, 42, 0.03);
      --bs-table-hover-bg: rgba(252, 0, 37, 0.06);
    }

    :root[data-theme="light"] .form-control,
    :root[data-theme="light"] .form-select,
    :root[data-theme="light"] .input-group-text {
      background: #ffffff !important;
      color: #111827 !important;
      border-color: rgba(15, 23, 42, 0.16) !important;
    }

    :root[data-theme="light"] .btn-outline-light {
      color: #111827;
      border-color: rgba(15, 23, 42, 0.2);
      background: #ffffff;
    }

    :root[data-theme="light"] .btn-outline-light:hover {
      color: #ffffff;
      background: #111827;
      border-color: #111827;
    }

    :root[data-theme="light"] .text-light,
    :root[data-theme="light"] .text-light-gray,
    :root[data-theme="light"] .text-muted {
      color: #64748b !important;
    }

    .admin-check-card {
      border: 2px solid var(--bs-danger) !important;
    }
    .admin-check-card.is-accepted {
      border-color: var(--bs-success) !important;
    }
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

  <div class="dashboard-content">
    <!-- Breadcrumb Navigation -->
    <div class="breadcrumb-admin" id="breadcrumbContainer" style="display: none;">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0" id="breadcrumb">
          <li class="breadcrumb-item"><a href="#" onclick="showHome(); return false;">Admin</a></li>
        </ol>
      </nav>
    </div>

    <!-- Header -->
    <div class="page-header mb-4">
      <h1 class="text-white fw-bold mb-0">
        <i class="bi bi-shield-lock-fill me-2 text-orange"></i>
        <span id="pageTitle">Painel Administrativo</span>
      </h1>
    </div>

    <!-- Container Principal -->
    <div id="mainContainer">
      <!-- Conteúdo será carregado dinamicamente aqui -->
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function() {
      const themeKey = 'fba-theme';
      localStorage.setItem(themeKey, 'light');
      document.documentElement.dataset.theme = 'light';
    })();
  </script>
  <script src="/js/sidebar.js?v=<?= time() ?>"></script>
  <script src="/js/admin.js?v=<?= time() ?>"></script>
  <script src="/js/seasons.js?v=<?= time() ?>"></script>
  <script src="/js/pwa.js"></script>
</body>
</html>

