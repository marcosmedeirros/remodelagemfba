<?php
require_once __DIR__ . '/backend/auth.php';
requireAuth();

$user = getUserSession();
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
	<?php include __DIR__ . '/includes/head-pwa.php'; ?>
	<title>Configuracao Inicial - FBA Manager</title>

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="/css/styles.css">

	<style>
		:root {
			--red: #fc0025;
			--red-soft: rgba(252,0,37,.12);
			--bg: #07070a;
			--panel: #111116;
			--panel-2: #181821;
			--panel-3: #1f1f2a;
			--border: rgba(255,255,255,.08);
			--text: #f0f0f4;
			--text-2: #8d8d98;
			--font: 'Poppins', sans-serif;
			--radius: 16px;
			--radius-sm: 10px;
		}

		* { box-sizing: border-box; }
		html, body { min-height: 100%; }
		body {
			margin: 0;
			font-family: var(--font);
			color: var(--text);
			background:
				radial-gradient(1000px 420px at 12% 8%, rgba(252,0,37,.16), transparent 55%),
				radial-gradient(850px 420px at 90% 92%, rgba(252,0,37,.10), transparent 55%),
				var(--bg);
		}

		.onb-shell {
			min-height: 100vh;
			padding: 26px 14px;
			display: grid;
			place-items: center;
		}

		.onb-wrap {
			width: min(1100px, 100%);
			background: linear-gradient(180deg, var(--panel-2), var(--panel));
			border: 1px solid var(--border);
			border-radius: 20px;
			box-shadow: 0 18px 40px rgba(0,0,0,.35);
			overflow: hidden;
		}

		.onb-head {
			border-bottom: 1px solid var(--border);
			padding: 24px 22px 18px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			gap: 14px;
			flex-wrap: wrap;
		}

		.onb-brand { display: flex; align-items: center; gap: 12px; }
		.onb-brand img { width: 58px; height: 58px; object-fit: contain; }
		.onb-kicker {
			margin: 0;
			font-size: 11px;
			font-weight: 700;
			letter-spacing: 1.2px;
			text-transform: uppercase;
			color: var(--red);
		}
		.onb-title { margin: 2px 0 0; font-size: 24px; font-weight: 800; line-height: 1.08; }
		.onb-sub { margin: 4px 0 0; color: var(--text-2); font-size: 13px; }

		.step-indicator {
			display: flex;
			align-items: center;
			gap: 10px;
			margin: 0;
		}
		.step-item { display: flex; align-items: center; gap: 8px; }
		.step {
			width: 32px;
			height: 32px;
			border-radius: 50%;
			display: grid;
			place-items: center;
			background: var(--panel-3);
			border: 1px solid var(--border);
			color: var(--text-2);
			font-weight: 700;
			font-size: 13px;
		}
		.step.active {
			background: var(--red-soft);
			border-color: rgba(252,0,37,.35);
			color: var(--red);
		}
		.step.completed {
			background: rgba(34,197,94,.14);
			border-color: rgba(34,197,94,.35);
			color: #22c55e;
		}
		.step-line {
			width: 42px;
			height: 2px;
			background: var(--border);
		}
		.step-label {
			font-size: 12px;
			color: var(--text-2);
			font-weight: 600;
		}

		.onb-body { padding: 20px; }

		.step-content { display: none; }
		.step-content.active { display: block; }

		.onb-card {
			background: linear-gradient(180deg, var(--panel-2), var(--panel));
			border: 1px solid var(--border);
			border-radius: var(--radius);
			padding: 20px;
		}
		.onb-card-title {
			margin: 0 0 14px;
			font-size: 19px;
			font-weight: 800;
			display: flex;
			align-items: center;
			gap: 8px;
		}
		.onb-card-title i { color: var(--red); }

		.form-label {
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: .5px;
			color: var(--text-2);
			font-weight: 700;
			margin-bottom: 6px;
		}
		.form-control, .form-select {
			min-height: 46px;
			border-radius: var(--radius-sm);
			background: var(--panel-3) !important;
			border: 1px solid var(--border) !important;
			color: var(--text) !important;
		}
		.form-control:focus, .form-select:focus {
			border-color: var(--red) !important;
			box-shadow: 0 0 0 .2rem rgba(252,0,37,.16) !important;
		}

		.photo-upload-container {
			width: 150px;
			height: 150px;
			border-radius: 18px;
			overflow: hidden;
			border: 1px solid var(--border);
			position: relative;
			background: var(--panel-3);
			margin: 0 auto;
		}
		.photo-preview { width: 100%; height: 100%; object-fit: cover; }
		.photo-upload-overlay {
			position: absolute;
			inset: auto 0 0 0;
			min-height: 40px;
			background: rgba(0,0,0,.62);
			color: #fff;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 6px;
			font-size: 11px;
			font-weight: 600;
			cursor: pointer;
			padding: 6px;
		}

		.btn-red {
			background: var(--red);
			border: 1px solid var(--red);
			color: #fff;
			min-height: 44px;
			border-radius: 10px;
			font-weight: 700;
			font-size: 13px;
			padding: 0 16px;
		}
		.btn-red:hover { filter: brightness(1.08); color: #fff; }

		.btn-soft {
			background: transparent;
			border: 1px solid var(--border);
			color: var(--text-2);
			min-height: 44px;
			border-radius: 10px;
			font-weight: 700;
			font-size: 13px;
			padding: 0 16px;
		}
		.btn-soft:hover { background: var(--panel-3); color: var(--text); }

		.text-light-gray { color: var(--text-2) !important; }

		@media (max-width: 860px) {
			.onb-head { padding: 18px 16px 14px; }
			.onb-title { font-size: 20px; }
			.onb-body { padding: 14px; }
			.step-label { display: none; }
			.step-line { width: 24px; }
		}
	</style>
</head>
<body>
	<div class="onb-shell">
		<div class="onb-wrap">
			<div class="onb-head">
				<div class="onb-brand">
					<img src="/img/fba-logo.png" alt="FBA" onerror="this.src='/img/logo-fba-preta.jpg'">
					<div>
						<p class="onb-kicker">Primeiro acesso</p>
						<h1 class="onb-title">Bem-vindo, <?= htmlspecialchars($user['name']) ?>!</h1>
						<p class="onb-sub">Vamos configurar sua franquia em 2 passos</p>
					</div>
				</div>

				<div class="step-indicator">
					<div class="step-item">
						<div class="step active" id="step-indicator-1">1</div>
						<span class="step-label">Perfil</span>
					</div>
					<div class="step-line"></div>
					<div class="step-item">
						<div class="step" id="step-indicator-2">2</div>
						<span class="step-label">Time</span>
					</div>
				</div>
			</div>

			<div class="onb-body">
				<div class="step-content active" id="step-1">
					<div class="onb-card">
						<h2 class="onb-card-title"><i class="bi bi-person-circle"></i>Seu perfil</h2>

						<div class="row g-3 g-lg-4 align-items-start">
							<div class="col-lg-4 text-center">
								<div class="photo-upload-container">
									<img src="<?= htmlspecialchars($user['photo_url'] ?? '/img/default-avatar.png') ?>" alt="Foto" class="photo-preview" id="user-photo-preview">
									<label for="user-photo-upload" class="photo-upload-overlay">
										<i class="bi bi-camera-fill"></i>
										<span>Adicionar foto</span>
									</label>
									<input type="file" id="user-photo-upload" class="d-none" accept="image/*">
								</div>
							</div>

							<div class="col-lg-8">
								<div class="mb-3">
									<label class="form-label">Nome</label>
									<input type="text" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" readonly>
								</div>
								<div class="mb-3">
									<label class="form-label">E-mail</label>
									<input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled aria-disabled="true" title="Seu e-mail nao pode ser alterado">
									<small class="text-light-gray">Seu e-mail esta vinculado a conta e nao pode ser editado.</small>
								</div>
								<div class="mb-1">
									<label class="form-label">Liga</label>
									<input type="text" class="form-control" value="<?= htmlspecialchars($user['league']) ?>" readonly>
								</div>
							</div>
						</div>

						<div class="text-end mt-4">
							<button class="btn btn-red" onclick="nextStep(2)">Proximo <i class="bi bi-arrow-right ms-2"></i></button>
						</div>
					</div>
				</div>

				<div class="step-content" id="step-2">
					<div class="onb-card">
						<h2 class="onb-card-title"><i class="bi bi-trophy"></i>Dados do seu time</h2>

						<form id="form-team">
							<div class="text-center mb-4">
								<div class="photo-upload-container" style="width:150px;height:150px;">
									<img src="/img/default-team.png" alt="Logo" class="photo-preview" id="team-photo-preview">
									<label for="team-photo-upload" class="photo-upload-overlay">
										<i class="bi bi-image-fill"></i>
										<span>Logo do time</span>
									</label>
									<input type="file" id="team-photo-upload" class="d-none" accept="image/*">
								</div>
							</div>

							<div class="row g-3">
								<div class="col-md-6">
									<label class="form-label">Nome do time *</label>
									<input type="text" name="name" class="form-control" placeholder="Ex: Lakers" required>
								</div>
								<div class="col-md-6">
									<label class="form-label">Cidade *</label>
									<input type="text" name="city" class="form-control" placeholder="Ex: Los Angeles" required>
								</div>
								<div class="col-12">
									<label class="form-label">Mascote</label>
									<input type="text" name="mascot" class="form-control" placeholder="Ex: Aguia Dourada">
									<small class="text-light-gray">Opcional</small>
								</div>
								<div class="col-md-6">
									<label class="form-label">Conferencia *</label>
									<select name="conference" class="form-select" required>
										<option value="">Selecione...</option>
										<option value="LESTE">LESTE</option>
										<option value="OESTE">OESTE</option>
									</select>
								</div>
								<div class="col-md-6 d-flex align-items-end">
									<small class="text-light-gray">Usamos a conferencia para organizar tabelas e confrontos.</small>
								</div>
							</div>
						</form>

						<div class="d-flex justify-content-between mt-4">
							<button type="button" class="btn btn-soft" onclick="prevStep(1)"><i class="bi bi-arrow-left me-2"></i>Voltar</button>
							<button type="button" class="btn btn-red" onclick="saveTeamAndFinish()">Concluir <i class="bi bi-check-circle ms-2"></i></button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	<script src="/js/onboarding.js"></script>
	<script src="/js/pwa.js"></script>
</body>
</html>
