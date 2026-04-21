<?php
declare(strict_types=1);

$novoSidebarThemeCss = <<<'CSS'
<style id="novo-sidebar-picks-theme">
  /* Compatibilidade visual do sidebar padrao, sem injetar HTML/JS */
  :root { --sidebar-w: 280px; }

  .sidebar,
  .dashboard-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 260px;
    height: 100vh;
    background: var(--panel, #121826);
    border-right: 1px solid var(--border, rgba(255, 255, 255, 0.12));
    display: flex;
    flex-direction: column;
    z-index: 200;
    transition: transform var(--t, 220ms) var(--ease, ease);
    overflow-y: auto;
    box-shadow: 12px 0 36px rgba(0, 0, 0, 0.18);
  }

  .sidebar-brand {
    padding: 24px 20px 20px;
    border-bottom: 1px solid var(--border, rgba(255, 255, 255, 0.12));
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .sidebar-logo {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: var(--red, #fc0025);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 14px;
  }

  .sidebar-brand-text {
    color: var(--text, #e5e7eb);
    font-weight: 800;
    font-size: 16px;
    line-height: 1.1;
  }

  .sidebar-brand-text span {
    display: block;
    margin-top: 2px;
    font-size: 11px;
    font-weight: 400;
    color: var(--text-2, #9ca3af);
  }

  .sidebar-myteam {
    margin: 16px 14px;
    background: var(--panel-2, rgba(255, 255, 255, 0.03));
    border: 1px solid var(--border, rgba(255, 255, 255, 0.12));
    border-radius: 10px;
    padding: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .sidebar-myteam img {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid var(--border-strong, rgba(255, 255, 255, 0.2));
    flex-shrink: 0;
  }

  .sidebar-myteam-info { flex: 1; min-width: 0; }

  .sidebar-myteam-name {
    color: var(--text, #e5e7eb);
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .sidebar-myteam-sub {
    color: var(--text-2, #9ca3af);
    font-size: 11px;
  }

  .sidebar-nav {
    flex: 1;
    overflow-y: auto;
    padding: 8px 10px;
    scrollbar-width: none;
  }

  .sidebar-nav::-webkit-scrollbar { display: none; }

  .sidebar-nav-label {
    padding: 12px 10px 6px;
    color: var(--text-3, #6b7280);
    font-size: 10px;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    font-weight: 600;
  }

  .sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 10px;
    border-radius: 10px;
    color: var(--text-2, #9ca3af);
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: all var(--t, 220ms) var(--ease, ease);
    margin-bottom: 2px;
  }

  .sidebar-nav a i {
    width: 20px;
    text-align: center;
    flex-shrink: 0;
  }

  .sidebar-nav a:hover {
    background: var(--panel-2, rgba(255, 255, 255, 0.03));
    color: var(--text, #e5e7eb);
  }

  .sidebar-nav a.active {
    background: var(--red-soft, rgba(252, 0, 37, 0.12));
    color: var(--red, #fc0025);
    font-weight: 600;
  }

  /* Compatibilidade com sidebar legado (sidebar-menu / team-avatar) */
  .team-avatar {
    width: 74px;
    height: 74px;
    border-radius: 14px;
    object-fit: cover;
    border: 1px solid var(--border, rgba(255, 255, 255, 0.12));
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.28);
    margin: 0 auto;
    display: block;
  }

  .sidebar-menu {
    list-style: none;
    padding: 8px 10px;
    margin: 0;
  }

  .sidebar-menu li {
    margin: 0;
  }

  .sidebar-menu li a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 10px;
    border-radius: 10px;
    color: var(--text-2, #9ca3af);
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    margin-bottom: 2px;
    transition: all var(--t, 220ms) var(--ease, ease);
  }

  .sidebar-menu li a i {
    width: 20px;
    text-align: center;
    font-style: normal;
    flex-shrink: 0;
  }

  .sidebar-menu li a:hover {
    background: var(--panel-2, rgba(255, 255, 255, 0.03));
    color: var(--text, #e5e7eb);
  }

  .sidebar-menu li a.active {
    background: var(--red-soft, rgba(252, 0, 37, 0.12));
    color: var(--red, #fc0025);
    font-weight: 600;
  }

  .sidebar-theme-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 10px;
    border-radius: 10px;
    border: 1px solid transparent;
    color: var(--text-2, #9ca3af);
    background: transparent;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    margin-bottom: 2px;
    transition: all var(--t, 220ms) var(--ease, ease);
    cursor: pointer;
    text-align: left;
  }

  .sidebar-theme-toggle i {
    width: 20px;
    text-align: center;
    flex-shrink: 0;
  }

  .sidebar-theme-toggle:hover {
    background: var(--panel-2, rgba(255, 255, 255, 0.03));
    color: var(--text, #e5e7eb);
  }

  .sidebar-theme-toggle[aria-pressed="true"] {
    background: var(--red-soft, rgba(252, 0, 37, 0.12));
    border-color: var(--red, #fc0025);
    color: var(--red, #fc0025);
    font-weight: 600;
  }

  .sidebar-footer {
    padding: 14px;
    border-top: 1px solid var(--border, rgba(255, 255, 255, 0.12));
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .sidebar-user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid var(--border-strong, rgba(255, 255, 255, 0.2));
  }

  .sidebar-user-name {
    color: var(--text, #e5e7eb);
    font-size: 13px;
    font-weight: 500;
    flex: 1;
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .sidebar-logout {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    border: 1px solid var(--border, rgba(255, 255, 255, 0.12));
    color: var(--text-2, #9ca3af);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all var(--t, 220ms) var(--ease, ease);
    flex-shrink: 0;
  }

  .sidebar-logout:hover {
    background: var(--red-soft, rgba(252, 0, 37, 0.12));
    border-color: var(--red, #fc0025);
    color: var(--red, #fc0025);
  }

  .dashboard-content,
  .main {
    margin-left: var(--sidebar-w);
  }

  .sidebar-toggle {
    position: fixed;
    top: 12px;
    left: 12px;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    border: 1px solid rgba(15, 23, 42, .12);
    background: #ffffff;
    color: #0f172a;
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 400;
  }

  .sidebar-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.65);
    backdrop-filter: blur(4px);
    display: none;
    z-index: 299;
  }

  .sidebar-overlay.show,
  .sidebar-overlay.active {
    display: block;
  }

  @media (max-width: 860px) {
    .sidebar-toggle {
      display: inline-flex;
    }

    .dashboard-sidebar,
    .sidebar {
      transform: translateX(calc(-1 * var(--sidebar-w)));
    }

    .dashboard-sidebar.open,
    .sidebar.open {
      transform: translateX(0);
    }

    .dashboard-content,
    .main {
      margin-left: 0;
      width: 100%;
      padding-top: 56px;
    }
  }
</style>
CSS;
?>
