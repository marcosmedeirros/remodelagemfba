<?php
declare(strict_types=1);

$novoSidebarThemeCss = <<<'CSS'
<style id="novo-sidebar-picks-theme">
  :root {
    --sidebar-w: 260px;
  }

  .dashboard-sidebar,
  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: var(--sidebar-w);
    height: 100vh;
    background: #ffffff;
    border-right: 1px solid rgba(15, 23, 42, .10);
    overflow-y: auto;
    z-index: 300;
    scrollbar-width: none;
    transition: transform .2s cubic-bezier(.2,.8,.2,1);
  }

  .dashboard-sidebar::-webkit-scrollbar,
  .sidebar::-webkit-scrollbar {
    display: none;
  }

  .dashboard-content,
  .main {
    margin-left: var(--sidebar-w);
  }

  .team-avatar {
    width: 74px;
    height: 74px;
    border-radius: 14px;
    object-fit: cover;
    border: 1px solid rgba(15, 23, 42, .15);
    box-shadow: 0 6px 18px rgba(15, 23, 42, .12);
  }

  .sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .sidebar-menu li a {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 2px;
    padding: 9px 10px;
    border-radius: 10px;
    color: #334155;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    transition: all .2s cubic-bezier(.2,.8,.2,1);
  }

  .sidebar-menu li a i {
    width: 18px;
    text-align: center;
    font-size: 15px;
  }

  .sidebar-menu li a:hover {
    background: #f8fafc;
    color: #0f172a;
  }

  .sidebar-menu li a.active {
    background: rgba(252, 0, 37, .12);
    color: #fc0025;
    font-weight: 600;
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

  .sidebar-overlay.show {
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

echo $novoSidebarThemeCss;
?>