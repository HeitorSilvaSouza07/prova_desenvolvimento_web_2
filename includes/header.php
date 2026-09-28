<?php

$user = current_user();
$pageTitle = $pageTitle ?? 'Painel';
$active    = $active ?? 'painel';

$menu = [
    'painel'      => ['label' => 'Painel',      'icon' => 'M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7V11h-7v9Zm0-16v5h7V4h-7Z', 'url' => 'dashboard/index.php',      'roles' => ['admin', 'professor', 'aluno']],
    'turmas'      => ['label' => 'Turmas',      'icon' => 'M3 7h18v13H3V7Zm5-4h8l2 4H6l2-4Zm5 9v6',        'url' => 'dashboard/turmas.php',      'roles' => ['admin', 'professor', 'aluno']],
    'alunos'      => ['label' => 'Alunos',      'icon' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 9a8 8 0 0 1 16 0', 'url' => 'dashboard/alunos.php',      'roles' => ['admin', 'professor']],
    'professores' => ['label' => 'Professores', 'icon' => 'M4 20v-9a8 8 0 0 1 16 0v9M4 20h16M8 12h8',     'url' => 'dashboard/professores.php', 'roles' => ['admin', 'professor']],
    'vinculos'    => ['label' => 'Vínculos',    'icon' => 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM2 21a7 7 0 0 1 14 0m2-7a6 6 0 0 1 6 6', 'url' => 'dashboard/vinculos.php', 'roles' => ['admin', 'professor']],
    'aulas'       => ['label' => 'Aulas',       'icon' => 'M4 6h16v14H4V6Zm0 5h16M8 3v5m8-5v5',           'url' => 'dashboard/aulas.php',       'roles' => ['admin', 'professor', 'aluno']],
    'provas'      => ['label' => 'Provas',      'icon' => 'M6 3h9l4 4v14H6V3Zm9 0v4h4M9 13h7M9 17h5',     'url' => 'dashboard/provas.php',      'roles' => ['admin', 'professor', 'aluno']],
    'atividades'  => ['label' => 'Atividades',  'icon' => 'M5 4h14v17H5V4Zm4 5h6M9 13h6M9 17h4',          'url' => 'dashboard/atividades.php',  'roles' => ['admin', 'professor', 'aluno']],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> — ProSiga</title>
<link rel="icon" href="<?= e(app_url('assets/img/logo-mark.png')) ?>">
<link rel="stylesheet" href="<?= e(app_url('assets/css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(app_url('assets/css/dashboard.css')) ?>">
</head>
<body class="app-body">
<div class="app">
  <header class="app-topbar">
    <button class="app-burger" type="button" data-sidebar-toggle aria-label="Abrir menu">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
    </button>

    <a class="app-brand" href="<?= e(app_url('index.php')) ?>">
      <img src="<?= e(app_url('assets/img/logo-mark.png')) ?>" alt="" class="app-brand__logo">
      <span class="app-brand__name">ProSiga</span>
    </a>

    <div class="app-topbar__spacer"></div>

    <div class="app-user">
      <span class="app-user__avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr(trim($user['nome']), 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
      <span class="app-user__meta">
        <strong><?= e($user['nome']) ?></strong>
        <small><?= e(tipo_label($user['tipo'])) ?></small>
      </span>
      <a class="app-logout" href="<?= e(app_url('sair.php')) ?>" title="Sair do sistema">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 17l5-5-5-5M20 12H9M12 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6"/></svg>
        <span>Sair</span>
      </a>
    </div>
  </header>

  <div class="app-shell">
    <div class="app-overlay" data-sidebar-close></div>

    <aside class="app-sidebar" data-sidebar>
      <nav class="app-nav" aria-label="Menu do painel">
        <p class="app-nav__title">Navegação</p>
        <ul>
          <?php foreach ($menu as $key => $item): ?>
            <?php if (!in_array($user['tipo'], $item['roles'], true)) continue; ?>
            <li>
              <a class="app-nav__link<?= $active === $key ? ' is-active' : '' ?>" href="<?= e(app_url($item['url'])) ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7"
                     stroke-linecap="round" stroke-linejoin="round"><path d="<?= e($item['icon']) ?>"/></svg>
                <span><?= e($item['label']) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>

        <div class="app-sidebar__card">
          <img src="<?= e(app_url('assets/img/mascot-happy.svg')) ?>" alt="" class="app-sidebar__mascot">
          <p><strong>Dúvidas?</strong> Fale com a secretaria da sua escola.</p>
        </div>
      </nav>
    </aside>

    <main class="app-main">
      <?php if (isset($pageHeader)): ?>
        <div class="page-head">
          <div>
            <h1 class="page-head__title"><?= e($pageHeader['title'] ?? '') ?></h1>
            <p class="page-head__sub"><?= e($pageHeader['sub'] ?? '') ?></p>
          </div>
          <?php if (!empty($pageHeader['action'])): ?>
            <div class="page-head__action"><?= $pageHeader['action'] ?></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?= flash_show() ?>
