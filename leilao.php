<?php
declare(strict_types=1);
require __DIR__ . '/_sidebar-picks-theme.php';

ob_start();
$sourceCandidates = [
  __DIR__ . '/../leilao.php',
  __DIR__ . '/pages/leilao.php',
  __DIR__ . '/public/leilao.php',
];

$sourceFile = null;
foreach ($sourceCandidates as $candidate) {
  if (is_file($candidate) && realpath($candidate) !== __FILE__) {
    $sourceFile = $candidate;
    break;
  }
}

if ($sourceFile !== null) {
  require $sourceFile;
} else {
  http_response_code(500);
  echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Leilao indisponivel</title></head><body><p>Nao foi possivel carregar a pagina de Leilao. Verifique se o arquivo de origem existe.</p></body></html>';
}
$html = ob_get_clean();

if ($html === false) {
    $html = '';
}

if (preg_match('/<body[^>]*class="([^"]*)"/i', $html)) {
    $html = preg_replace('/<body([^>]*)class="([^"]*)"/i', '<body$1class="$2 leilao-novo"', $html, 1);
} else {
    $html = preg_replace('/<body([^>]*)>/i', '<body$1 class="leilao-novo">', $html, 1);
}

$themeCss = <<<'CSS'
<style id="leilao-novo-theme">
  .leilao-novo {
    --novo-bg-a: #080d14;
    --novo-bg-b: #1a0f14;
    --novo-bg-c: #0d1420;
    --novo-border: rgba(255, 255, 255, 0.12);
    --novo-glow: rgba(252, 0, 37, 0.24);
    background:
      radial-gradient(1100px 420px at 82% -10%, rgba(252, 0, 37, 0.2), transparent 60%),
      radial-gradient(900px 350px at -10% 28%, rgba(13, 202, 240, 0.14), transparent 62%),
      linear-gradient(160deg, var(--novo-bg-a), var(--novo-bg-b) 55%, var(--novo-bg-c));
  }

  .leilao-novo .dashboard-content {
    padding-top: 2rem;
  }

  .leilao-novo .page-header,
  .leilao-novo .hero,
  .leilao-novo .auction-header {
    border-radius: 18px;
    border: 1px solid var(--novo-border);
    background: linear-gradient(155deg, rgba(17, 24, 38, 0.96), rgba(13, 20, 32, 0.96));
    box-shadow: 0 16px 34px rgba(0, 0, 0, 0.35);
    padding: 1rem 1.15rem;
  }

  .leilao-novo .card,
  .leilao-novo .panel,
  .leilao-novo .bg-dark-panel,
  .leilao-novo .bg-dark {
    border-radius: 18px !important;
    border: 1px solid var(--novo-border) !important;
    background: linear-gradient(165deg, rgba(17, 24, 38, 0.94), rgba(13, 20, 32, 0.94)) !important;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.34);
  }

  .leilao-novo .card-header,
  .leilao-novo .panel-head,
  .leilao-novo .modal-header {
    background: linear-gradient(120deg, rgba(252, 0, 37, 0.16), rgba(13, 202, 240, 0.14)) !important;
    border-bottom-color: rgba(255, 255, 255, 0.14) !important;
  }

  .leilao-novo .table {
    --bs-table-bg: transparent;
    --bs-table-striped-bg: rgba(255, 255, 255, 0.035);
    --bs-table-hover-bg: rgba(252, 0, 37, 0.09);
    border-color: rgba(255, 255, 255, 0.13);
  }

  .leilao-novo .btn-danger,
  .leilao-novo .btn-orange,
  .leilao-novo .btn-red,
  .leilao-novo .btn-primary {
    background: linear-gradient(135deg, #fc0025, #ff4960) !important;
    border-color: transparent !important;
  }

  .leilao-novo .btn-danger:hover,
  .leilao-novo .btn-orange:hover,
  .leilao-novo .btn-red:hover,
  .leilao-novo .btn-primary:hover {
    filter: brightness(1.08);
    transform: translateY(-1px);
  }

  .leilao-novo .form-control,
  .leilao-novo .form-select {
    border-radius: 12px;
    border-color: rgba(255, 255, 255, 0.18) !important;
    background: rgba(8, 13, 22, 0.9) !important;
  }

  .leilao-novo .badge,
  .leilao-novo .status,
  .leilao-novo .auction-status {
    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.08) inset;
  }

  .leilao-novo .modal-content {
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    background: linear-gradient(160deg, rgba(18, 24, 35, 0.98), rgba(9, 14, 24, 0.98));
  }

  @media (max-width: 768px) {
    .leilao-novo .dashboard-content {
      padding-top: 1.4rem;
    }
  }
</style>
CSS;

$html = preg_replace('/<\/head>/i', $themeCss . "\n" . $novoSidebarThemeCss . "\n</head>", $html, 1);

echo $html;
