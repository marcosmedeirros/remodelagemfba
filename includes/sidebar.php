<?php
// Garantir que a conexão está disponível
if (!function_exists('db')) {
    require_once __DIR__ . '/../backend/db.php';
}
// Buscar dados do usuário e time se não existirem
if (!isset($user) || !isset($team)) {
    $pdo = db();
    $userId = $_SESSION['user_id'] ?? null;

    if ($userId) {
        $stmt = $pdo->prepare("
            SELECT u.*, t.id as team_id, t.name as team_name, t.city, t.photo_url, t.league
            FROM users u
            LEFT JOIN teams t ON t.user_id = u.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            $user = [
                'id'         => $userData['id'],
                'name'       => $userData['name'],
                'email'      => $userData['email'] ?? '',
                'photo_url'  => $userData['photo_url'] ?? null,
                'league'     => $userData['league'] ?? 'ELITE',
                'user_type'  => $userData['user_type'] ?? 'jogador',
            ];
            $team = [
                'id'        => $userData['team_id'],
                'name'      => $userData['team_name'],
                'city'      => $userData['city'],
                'photo_url' => $userData['photo_url'],
                'league'    => $userData['league'],
            ];
        }
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">

    <?php if (!empty($team)): ?>
    <div class="sb-team">
        <img src="<?= htmlspecialchars($team['photo_url'] ?? '/img/default-team.png') ?>"
             alt="<?= htmlspecialchars(($team['city'] ?? '') . ' ' . ($team['name'] ?? '')) ?>"
             onerror="this.src='/img/default-team.png'">
        <div>
            <div class="sb-team-name"><?= htmlspecialchars(trim(($team['city'] ?? '') . ' ' . ($team['name'] ?? ''))) ?></div>
            <div class="sb-team-league"><?= htmlspecialchars($team['league'] ?? '') ?></div>
        </div>
    </div>
    <?php endif; ?>

    <nav class="sb-nav">
        <div class="sb-section">Principal</div>
        <a href="/dashboard.php"   class="<?= $currentPage === 'dashboard.php'   ? 'active' : '' ?>"><i class="bi bi-house-door-fill"></i> Dashboard</a>
        <a href="/teams.php"       class="<?= $currentPage === 'teams.php'       ? 'active' : '' ?>"><i class="bi bi-people-fill"></i> Times</a>
        <a href="/my-roster.php"   class="<?= $currentPage === 'my-roster.php'   ? 'active' : '' ?>"><i class="bi bi-person-fill"></i> Meu Elenco</a>
        <a href="/picks.php"       class="<?= $currentPage === 'picks.php'       ? 'active' : '' ?>"><i class="bi bi-calendar-check-fill"></i> Picks</a>
        <a href="/trades.php"      class="<?= $currentPage === 'trades.php'      ? 'active' : '' ?>"><i class="bi bi-arrow-left-right"></i> Trades</a>
        <a href="/free-agency.php" class="<?= $currentPage === 'free-agency.php' ? 'active' : '' ?>"><i class="bi bi-coin"></i> Free Agency</a>
        <a href="/leilao.php"      class="<?= $currentPage === 'leilao.php'      ? 'active' : '' ?>"><i class="bi bi-hammer"></i> Leilão</a>
        <a href="/drafts.php"      class="<?= $currentPage === 'drafts.php'      ? 'active' : '' ?>"><i class="bi bi-trophy"></i> Draft</a>

        <div class="sb-section">Liga</div>
        <a href="/rankings.php"   class="<?= $currentPage === 'rankings.php'   ? 'active' : '' ?>"><i class="bi bi-bar-chart-fill"></i> Rankings</a>
        <a href="/history.php"    class="<?= $currentPage === 'history.php'    ? 'active' : '' ?>"><i class="bi bi-clock-history"></i> Histórico</a>
        <a href="/diretrizes.php" class="<?= $currentPage === 'diretrizes.php' ? 'active' : '' ?>"><i class="bi bi-clipboard-data"></i> Diretrizes</a>
        <a href="/ouvidoria.php"  class="<?= $currentPage === 'ouvidoria.php'  ? 'active' : '' ?>"><i class="bi bi-chat-dots"></i> Ouvidoria</a>
        <a href="https://games.fbabrasil.com.br/auth/login.php" target="_blank" rel="noopener"><i class="bi bi-controller"></i> FBA Games</a>

        <?php if (($user['user_type'] ?? 'jogador') === 'admin'): ?>
        <div class="sb-section">Admin</div>
        <a href="/admin.php"      class="<?= $currentPage === 'admin.php'      ? 'active' : '' ?>"><i class="bi bi-shield-lock-fill"></i> Admin</a>
        <a href="/temporadas.php" class="<?= $currentPage === 'temporadas.php' ? 'active' : '' ?>"><i class="bi bi-calendar3"></i> Temporadas</a>
        <?php endif; ?>

        <div class="sb-section">Conta</div>
        <a href="/settings.php" class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>"><i class="bi bi-gear-fill"></i> Configurações</a>
    </nav>

    <button class="sb-theme-toggle" type="button" id="themeToggle" data-theme-toggle aria-pressed="false">
        <i class="bi bi-moon"></i>
        <span>Modo escuro</span>
    </button>

    <div class="sb-footer">
        <img src="<?= htmlspecialchars($user['photo_url'] ?? '/img/default-avatar.png') ?>"
             alt="<?= htmlspecialchars($user['name'] ?? 'Usuário') ?>"
             class="sb-avatar"
             onerror="this.src='https://ui-avatars.com/api/?name=<?= rawurlencode($user['name'] ?? 'U') ?>&background=1c1c21&color=fc0025'">
        <span class="sb-username"><?= htmlspecialchars($user['name'] ?? 'Usuário') ?></span>
        <a href="/logout.php" class="sb-logout" title="Sair"><i class="bi bi-box-arrow-right"></i></a>
    </div>
</aside>
