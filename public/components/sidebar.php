<?php
// Sidebar público para páginas em /public
$publicPage = basename($_SERVER['PHP_SELF'] ?? '');

function publicSidebarHref(string $path): string {
    $cleanPath = trim($path);
    if ($cleanPath === '') {
        return '/';
    }

    if (!str_ends_with($cleanPath, '.php')) {
        $cleanPath .= '.php';
    }

    return $cleanPath;
}

$links = [
    ['href' => '/dashboard', 'icon' => 'bi-house-door-fill', 'label' => 'Dashboard'],
    ['href' => '/teams', 'icon' => 'bi-people-fill', 'label' => 'Times'],
    ['href' => '/players', 'icon' => 'bi-person-lines-fill', 'label' => 'Jogadores'],
    ['href' => '/my-roster', 'icon' => 'bi-person-fill', 'label' => 'Meu Elenco'],
    ['href' => '/picks', 'icon' => 'bi-calendar-check-fill', 'label' => 'Picks'],
    ['href' => '/trades', 'icon' => 'bi-arrow-left-right', 'label' => 'Trades'],
    ['href' => '/free-agency', 'icon' => 'bi-coin', 'label' => 'Free Agency'],
    ['href' => '/leilao', 'icon' => 'bi-hammer', 'label' => 'Leilão'],
    ['href' => '/drafts', 'icon' => 'bi-trophy', 'label' => 'Draft'],
    ['href' => '/rankings', 'icon' => 'bi-bar-chart-fill', 'label' => 'Rankings'],
    ['href' => '/history', 'icon' => 'bi-clock-history', 'label' => 'Histórico'],
    ['href' => '/settings', 'icon' => 'bi-gear-fill', 'label' => 'Configurações'],
];
?>
<div class="col-md-3 p-0">
    <div class="dashboard-sidebar">
        <div class="text-center mb-4">
            <img src="/img/default-team.png" alt="Time" class="team-avatar">
            <h5 class="text-white mb-1">Menu</h5>
        </div>
        <hr style="border-color: var(--fba-border);">
        <ul class="sidebar-menu">
            <?php foreach ($links as $link): ?>
                <?php $href = publicSidebarHref($link['href']); ?>
                <li>
                    <a href="<?= htmlspecialchars($href) ?>" class="<?= str_ends_with($href, '/' . $publicPage) ? 'active' : '' ?>">
                        <i class="bi <?= htmlspecialchars($link['icon']) ?>"></i>
                        <?= htmlspecialchars($link['label']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
            <li>
                <button type="button" class="sidebar-theme-toggle" data-theme-toggle aria-pressed="false">
                    <i class="bi bi-sun-fill"></i><span>Tema claro</span>
                </button>
            </li>
        </ul>
        <hr style="border-color: var(--fba-border);">
        <div class="text-center">
            <a href="/logout.php" class="btn btn-outline-danger btn-sm w-100">
                <i class="bi bi-box-arrow-right me-2"></i>Sair
            </a>
        </div>
    </div>
</div>
