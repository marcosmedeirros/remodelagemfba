<?php
declare(strict_types=1);

$rootDir = dirname(__DIR__);
$sourceFile = $rootDir . '/draft-players.php';
require __DIR__ . '/_sidebar-picks-theme.php';

if (!is_file($sourceFile)) {
		http_response_code(500);
		echo 'Pagina base de draft players nao encontrada.';
		exit;
}

$oldCwd = getcwd();
if ($oldCwd !== false) {
		chdir($rootDir);
}

ob_start();
require $sourceFile;
$html = (string)ob_get_clean();

if ($oldCwd !== false) {
		chdir($oldCwd);
}

if (preg_match('/<body[^>]*class="([^"]*)"/i', $html)) {
		$html = preg_replace('/<body([^>]*)class="([^"]*)"/i', '<body$1class="$2 novo-theme"', $html, 1);
} else {
		$html = preg_replace('/<body([^>]*)>/i', '<body$1 class="novo-theme">', $html, 1);
}

$themeCss = <<<'CSS'
<style id="novo-theme-style">
	.novo-theme {
		--n-bg-a: #070b12;
		--n-bg-b: #180f14;
		--n-card: rgba(18, 24, 36, 0.94);
		--n-border: rgba(255,255,255,0.14);
		--n-glow: rgba(252,0,37,0.22);
		background:
			radial-gradient(1200px 420px at 88% -8%, rgba(252,0,37,0.22), transparent 60%),
			radial-gradient(900px 380px at -6% 16%, rgba(13,202,240,0.14), transparent 64%),
			linear-gradient(160deg, var(--n-bg-a), var(--n-bg-b));
	}

	.novo-theme .dashboard-content {
		padding-top: 2rem;
	}

	.novo-theme .table {
		--bs-table-bg: transparent;
		--bs-table-striped-bg: rgba(255,255,255,.03);
		--bs-table-hover-bg: rgba(252,0,37,.1);
		border-color: rgba(255,255,255,.14);
	}

	.novo-theme .table thead {
		background: linear-gradient(135deg, #fc0025, #ff4b62) !important;
	}

	.novo-theme .form-control,
	.novo-theme .form-select,
	.novo-theme .input-group-text {
		border-radius: 12px;
		border-color: rgba(255,255,255,.2) !important;
		background: rgba(10,16,25,.92) !important;
	}
</style>
CSS;

$html = preg_replace('/<\/head>/i', $themeCss . "\n" . $novoSidebarThemeCss . "\n</head>", $html, 1);
echo $html;

