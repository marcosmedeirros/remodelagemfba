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
                'id' => $userData['id'],
                'name' => $userData['name'],
                'league' => $userData['league'] ?? 'ELITE',
                'user_type' => $userData['user_type'] ?? 'jogador'
            ];
            $team = [
                'id' => $userData['team_id'],
                'name' => $userData['team_name'],
                'city' => $userData['city'],
                'photo_url' => $userData['photo_url'],
                'league' => $userData['league']
            ];
        }
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);
$sidebarBaseUrl = 'https://blue-turkey-597782.hostingersite.com';

function sidebarHref(string $path): string {
    $cleanPath = trim($path);
    if ($cleanPath === '') {
        return '/';
    }

    if (str_contains($cleanPath, '?') || str_contains($cleanPath, '#')) {
        return $cleanPath;
    }

    if (!str_ends_with($cleanPath, '.php')) {
        $cleanPath .= '.php';
    }

    return $cleanPath;
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand sb-brand">
        <div class="sidebar-logo sb-logo">FBA</div>
        <div class="sidebar-brand-text sb-brand-text">
            FBA Manager
            <span>Liga <?= htmlspecialchars($user['league'] ?? 'ELITE') ?></span>
        </div>
    </div>

    <?php if (!empty($team)): ?>
    <div class="sidebar-myteam sb-team">
        <img src="<?= htmlspecialchars($team['photo_url'] ?? '/img/default-team.png') ?>" alt="Meu Time">
        <div class="sidebar-myteam-info">
            <div class="sidebar-myteam-name sb-team-name"><?= htmlspecialchars(trim(($team['city'] ?? '') . ' ' . ($team['name'] ?? ''))) ?></div>
            <div class="sidebar-myteam-sub sb-team-league">Franquia ativa</div>
        </div>
    </div>
    <?php endif; ?>

    <nav class="sidebar-nav sb-nav">
        <div class="sidebar-nav-label sb-section">Principal</div>
        <a href="<?= $sidebarBaseUrl . sidebarHref('/dashboard') ?>" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"><i class="bi bi-house-door-fill"></i> Dashboard</a>
        <a href="<?= $sidebarBaseUrl . sidebarHref('/teams') ?>" class="<?= $currentPage === 'teams.php' ? 'active' : '' ?>"><i class="bi bi-people-fill"></i> Times</a>
        <a href="<?= $sidebarBaseUrl . sidebarHref('/players') ?>" class="<?= $currentPage === 'players.php' ? 'active' : '' ?>"><i class="bi bi-person-lines-fill"></i> Jogadores</a>
        <a href="<?= $sidebarBaseUrl . sidebarHref('/my-roster') ?>" class="<?= $currentPage === 'my-roster.php' ? 'active' : '' ?>"><i class="bi bi-person-fill"></i> Meu Elenco</a>
        <a href="<?= $sidebarBaseUrl . sidebarHref('/picks') ?>" class="<?= $currentPage === 'picks.php' ? 'active' : '' ?>"><i class="bi bi-calendar-check-fill"></i> Picks</a>
        <a href="<?= $sidebarBaseUrl . sidebarHref('/trades') ?>" class="<?= $currentPage === 'trades.php' ? 'active' : '' ?>"><i class="bi bi-arrow-left-right"></i> Trades</a>

        <div class="sidebar-nav-label sb-section">Liga</div>
        <a href="<?= $sidebarBaseUrl . sidebarHref('/free-agency') ?>" class="<?= $currentPage === 'free-agency.php' ? 'active' : '' ?>"><i class="bi bi-coin"></i> Free Agency</a>
        <a href="<?= $sidebarBaseUrl . sidebarHref('/leilao') ?>" class="<?= $currentPage === 'leilao.php' ? 'active' : '' ?>"><i class="bi bi-hammer"></i> Leilão</a>
        <a href="<?= $sidebarBaseUrl . sidebarHref('/drafts') ?>" class="<?= $currentPage === 'drafts.php' ? 'active' : '' ?>"><i class="bi bi-trophy"></i> Draft</a>
        <a href="<?= $sidebarBaseUrl . sidebarHref('/rankings') ?>" class="<?= $currentPage === 'rankings.php' ? 'active' : '' ?>"><i class="bi bi-bar-chart-fill"></i> Rankings</a>
        <a href="<?= $sidebarBaseUrl . sidebarHref('/history') ?>" class="<?= $currentPage === 'history.php' ? 'active' : '' ?>"><i class="bi bi-clock-history"></i> Histórico</a>

        <?php if (($user['user_type'] ?? 'jogador') === 'admin'): ?>
        <div class="sidebar-nav-label sb-section">Admin</div>
        <a href="<?= $sidebarBaseUrl . sidebarHref('/admin') ?>" class="<?= $currentPage === 'admin.php' ? 'active' : '' ?>"><i class="bi bi-shield-lock-fill"></i> Admin</a>
        <a href="<?= $sidebarBaseUrl . sidebarHref('/temporadas') ?>" class="<?= $currentPage === 'temporadas.php' ? 'active' : '' ?>"><i class="bi bi-calendar3"></i> Temporadas</a>
        <?php endif; ?>

        <div class="sidebar-nav-label sb-section">Conta</div>
        <a href="<?= $sidebarBaseUrl . sidebarHref('/settings') ?>" class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>"><i class="bi bi-gear-fill"></i> Configurações</a>
        <button type="button" class="sidebar-theme-toggle" data-theme-toggle aria-pressed="false">
            <i class="bi bi-sun-fill"></i><span>Tema claro</span>
        </button>
    </nav>

    <div class="sidebar-footer sb-footer">
        <img src="<?= htmlspecialchars($user['photo_url'] ?? '/img/default-avatar.png') ?>" alt="<?= htmlspecialchars($user['name'] ?? 'Usuário') ?>" class="sidebar-user-avatar sb-avatar">
        <span class="sidebar-user-name sb-username"><?= htmlspecialchars($user['name'] ?? 'Usuário') ?></span>
        <a href="<?= $sidebarBaseUrl . sidebarHref('/logout') ?>" class="sidebar-logout sb-logout" title="Sair"><i class="bi bi-box-arrow-right"></i></a>
    </div>
</aside>
