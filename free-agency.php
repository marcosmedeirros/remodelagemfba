<?php
declare(strict_types=1);
require __DIR__ . '/_sidebar-picks-theme.php';

ob_start();
$sourceCandidates = [
	__DIR__ . '/../free-agency.php',
	__DIR__ . '/pages/free-agency.php',
	__DIR__ . '/public/free-agency.php',
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
	echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Free Agency indisponivel</title></head><body><p>Nao foi possivel carregar a pagina Free Agency. Verifique se o arquivo de origem existe.</p></body></html>';
}
$html = ob_get_clean();

if ($html === false) {
		$html = '';
}

if (preg_match('/<body[^>]*class="([^"]*)"/i', $html)) {
		$html = preg_replace('/<body([^>]*)class="([^"]*)"/i', '<body$1class="$2 free-agency-novo"', $html, 1);
} else {
		$html = preg_replace('/<body([^>]*)>/i', '<body$1 class="free-agency-novo">', $html, 1);
}

$themeCss = <<<'CSS'
<style id="free-agency-novo-theme">
	.free-agency-novo {
		--novo-bg-a: #0a1017;
		--novo-bg-b: #1a0f12;
		--novo-card: #111826;
		--novo-card-2: #0d1420;
		--novo-border: rgba(255, 255, 255, 0.12);
		--novo-glow: rgba(252, 0, 37, 0.22);
		background:
			radial-gradient(1200px 420px at 85% -10%, rgba(252, 0, 37, 0.22), transparent 60%),
			radial-gradient(900px 380px at -10% 20%, rgba(13, 202, 240, 0.18), transparent 62%),
			linear-gradient(160deg, var(--novo-bg-a), var(--novo-bg-b));
	}

	.free-agency-novo .dashboard-content {
		padding-top: 2rem;
	}

	.free-agency-novo .free-agency-header {
		background: linear-gradient(135deg, rgba(17, 24, 38, 0.95), rgba(13, 20, 32, 0.95));
		border: 1px solid var(--novo-border);
		border-radius: 18px;
		padding: 1rem 1.1rem;
		box-shadow: 0 18px 38px rgba(0, 0, 0, 0.35);
		backdrop-filter: blur(6px);
	}

	.free-agency-novo .free-agency-tabs .nav-link {
		border: 1px solid rgba(255, 255, 255, 0.12);
		background: rgba(17, 24, 38, 0.75);
		color: #dbe7ff;
		font-weight: 600;
		letter-spacing: 0.02em;
	}

	.free-agency-novo .free-agency-tabs .nav-link.active {
		background: linear-gradient(135deg, #fc0025, #ff4a5f);
		color: #fff;
		border-color: transparent;
		box-shadow: 0 10px 20px var(--novo-glow);
	}

	.free-agency-novo .card.fa-new-card,
	.free-agency-novo .card.bg-dark-panel,
	.free-agency-novo .card.bg-dark {
		border-radius: 18px;
		border: 1px solid var(--novo-border) !important;
		background: linear-gradient(165deg, rgba(17, 24, 38, 0.95), rgba(13, 20, 32, 0.95)) !important;
		box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
	}

	.free-agency-novo .card-header {
		background: linear-gradient(120deg, rgba(252, 0, 37, 0.16), rgba(13, 202, 240, 0.14)) !important;
		border-bottom: 1px solid var(--novo-border) !important;
	}

	.free-agency-novo .table {
		--bs-table-bg: transparent;
		--bs-table-striped-bg: rgba(255, 255, 255, 0.04);
		--bs-table-hover-bg: rgba(252, 0, 37, 0.09);
		border-color: rgba(255, 255, 255, 0.12);
	}

	.free-agency-novo .btn-danger,
	.free-agency-novo .btn-red {
		background: linear-gradient(135deg, #fc0025, #ff4a5f) !important;
		border-color: transparent !important;
	}

	.free-agency-novo .btn-danger:hover,
	.free-agency-novo .btn-red:hover {
		filter: brightness(1.08);
		transform: translateY(-1px);
	}

	.free-agency-novo .form-control,
	.free-agency-novo .form-select {
		border-radius: 12px;
		border-color: rgba(255, 255, 255, 0.18) !important;
		background: rgba(8, 13, 22, 0.9) !important;
	}

	.free-agency-novo .badge.bg-warning.text-dark {
		background: linear-gradient(135deg, #ffc857, #ffb300) !important;
		color: #2a1700 !important;
	}

	@media (max-width: 768px) {
		.free-agency-novo .free-agency-header {
			padding: 0.9rem;
			border-radius: 14px;
		}
	}
</style>
CSS;

$html = preg_replace('/<\/head>/i', $themeCss . "\n" . $novoSidebarThemeCss . "\n</head>", $html, 1);

echo $html;

