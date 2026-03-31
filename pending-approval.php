<?php
session_start();
require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../backend/db.php';

if (!isset($_SESSION['user_id'])) {
	header('Location: /login.php');
	exit;
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
	header('Location: /login.php');
	exit;
}

if ((int)$user['approved'] === 1) {
	header('Location: /dashboard.php');
	exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
	<meta name="theme-color" content="#fc0025">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="apple-mobile-web-app-title" content="FBA Manager">
	<link rel="manifest" href="/manifest.json">
	<link rel="apple-touch-icon" href="/img/fba-logo.png">
	<title>Aguardando Aprovacao - FBA Manager</title>

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="/css/styles.css">

	<style>
		:root {
			--red: #fc0025;
			--bg: #07070a;
			--panel: #111116;
			--panel-2: #181821;
			--panel-3: #1f1f2a;
			--border: rgba(255,255,255,.08);
			--text: #f0f0f4;
			--text-2: #8d8d98;
			--font: 'Poppins', sans-serif;
		}

		html, body { min-height: 100%; }
		body {
			margin: 0;
			font-family: var(--font);
			background:
				radial-gradient(1000px 420px at 14% 10%, rgba(252,0,37,.16), transparent 55%),
				radial-gradient(900px 400px at 88% 92%, rgba(252,0,37,.10), transparent 55%),
				var(--bg);
			color: var(--text);
			display: grid;
			place-items: center;
			padding: 20px 14px;
		}

		.approval-wrap {
			width: min(760px, 100%);
			background: linear-gradient(180deg, var(--panel-2), var(--panel));
			border: 1px solid var(--border);
			border-radius: 20px;
			box-shadow: 0 18px 40px rgba(0,0,0,.35);
			overflow: hidden;
		}

		.approval-head {
			border-bottom: 1px solid var(--border);
			padding: 24px 22px;
			text-align: center;
		}
		.approval-logo { width: 72px; height: 72px; object-fit: contain; margin-bottom: 10px; }
		.approval-icon {
			width: 84px;
			height: 84px;
			border-radius: 50%;
			margin: 6px auto 14px;
			background: linear-gradient(135deg, var(--red), #ff2a44);
			color: #fff;
			display: grid;
			place-items: center;
			font-size: 40px;
			box-shadow: 0 8px 28px rgba(252,0,37,.35);
			animation: pulse 2s infinite;
		}

		@keyframes pulse {
			0%, 100% { transform: scale(1); opacity: 1; }
			50% { transform: scale(1.06); opacity: .85; }
		}

		.approval-title { margin: 0; font-size: 28px; font-weight: 800; }
		.approval-sub { margin: 8px 0 0; color: var(--text-2); font-size: 14px; }

		.approval-body { padding: 20px; }

		.info-card {
			background: var(--panel-3);
			border: 1px solid var(--border);
			border-radius: 14px;
			padding: 10px 14px;
			margin-bottom: 16px;
		}
		.info-row {
			display: flex;
			justify-content: space-between;
			gap: 12px;
			align-items: center;
			border-bottom: 1px solid var(--border);
			padding: 11px 0;
			font-size: 14px;
		}
		.info-row:last-child { border-bottom: 0; }
		.info-label { color: var(--text-2); font-weight: 500; }
		.info-value { color: var(--text); font-weight: 700; text-align: right; }

		.note {
			border: 1px solid rgba(13,202,240,.25);
			background: rgba(13,202,240,.08);
			color: #9be8ff;
			border-radius: 12px;
			padding: 11px 12px;
			font-size: 13px;
		}

		.actions { margin-top: 16px; display: flex; justify-content: center; }
		.btn-logout {
			border: 1px solid rgba(220,53,69,.5);
			background: transparent;
			color: #ff8f9a;
			border-radius: 10px;
			min-height: 42px;
			padding: 0 16px;
			font-weight: 700;
			text-decoration: none;
			display: inline-flex;
			align-items: center;
			gap: 8px;
		}
		.btn-logout:hover { background: rgba(220,53,69,.12); color: #ffc2c9; }

		@media (max-width: 620px) {
			.approval-title { font-size: 24px; }
			.info-row { flex-direction: column; align-items: flex-start; }
			.info-value { text-align: left; }
		}
	</style>
</head>
<body>
	<div class="approval-wrap">
		<div class="approval-head">
			<img src="/img/fba-logo.png" alt="FBA" class="approval-logo" onerror="this.src='/img/logo-fba-preta.jpg'">
			<div class="approval-icon"><i class="bi bi-hourglass-split"></i></div>
			<h1 class="approval-title">Aguardando aprovacao</h1>
			<p class="approval-sub">Seu cadastro foi concluido e esta em analise pela administracao.</p>
		</div>

		<div class="approval-body">
			<div class="info-card">
				<div class="info-row">
					<span class="info-label"><i class="bi bi-person me-2"></i>Nome</span>
					<span class="info-value"><?= htmlspecialchars($user['name']) ?></span>
				</div>
				<div class="info-row">
					<span class="info-label"><i class="bi bi-envelope me-2"></i>E-mail</span>
					<span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
				</div>
				<div class="info-row">
					<span class="info-label"><i class="bi bi-trophy me-2"></i>Liga</span>
					<span class="info-value"><?= htmlspecialchars($user['league']) ?></span>
				</div>
				<div class="info-row">
					<span class="info-label"><i class="bi bi-calendar me-2"></i>Cadastro</span>
					<span class="info-value"><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
				</div>
			</div>

			<div class="note">
				<i class="bi bi-info-circle me-2"></i>
				Voce recebera um e-mail assim que seu acesso for liberado.
			</div>

			<div class="actions">
				<a href="/logout.php" class="btn-logout"><i class="bi bi-box-arrow-right"></i>Sair</a>
			</div>
		</div>
	</div>
</body>
</html>
