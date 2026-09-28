<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect(app_url('dashboard/index.php'));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Criar conta — ProSiga</title>
<link rel="icon" href="<?= e(app_url('assets/img/logo-mark.png')) ?>">
<link rel="stylesheet" href="<?= e(app_url('assets/css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(app_url('assets/css/account.css')) ?>">
</head>
<body>
<div class="account-page">
  <header class="account-header">
    <a href="<?= e(app_url('index.php')) ?>" class="account-brand" aria-label="ProSiga - página inicial">
      <span class="account-brand__mark" aria-hidden="true"><img src="<?= e(app_url('assets/img/logo-mark.png')) ?>" alt=""></span>
      <span>ProSiga</span>
    </a>
  </header>

  <main class="account-main">
    <?= flash_show() ?>

    <div class="account-heading">
      <span class="account-eyebrow">Primeiro passo</span>
      <h1>Como você vai usar o <span class="account-heading__accent">ProSiga</span>?</h1>
      <p>Escolha o seu perfil para continuarmos com o cadastro. As informações da próxima etapa mudam de acordo com essa escolha.</p>
    </div>

    <div class="account-options">
      <a href="<?= e(app_url('cadastro.php?type=professor')) ?>" class="account-option">
        <span class="account-option__icon">
          <img src="<?= e(app_url('assets/img/mascot-teacher.svg')) ?>" alt="" width="46" height="46">
        </span>
        <span class="account-option__content">
          <strong>Sou professor</strong>
          <span>Crio turmas, vinculo alunos, agendo aulas, provas e atividades e envio avisos à turma.</span>
        </span>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>

      <a href="<?= e(app_url('cadastro.php?type=aluno')) ?>" class="account-option">
        <span class="account-option__icon account-option__icon--student">
          <img src="<?= e(app_url('assets/img/mascot-happy.svg')) ?>" alt="" width="46" height="46">
        </span>
        <span class="account-option__content">
          <strong>Sou aluno</strong>
          <span>Acompanho a agenda das minhas disciplinas: aulas, provas, atividades e avisos.</span>
        </span>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
    </div>

    <p class="account-login">Já possui acesso ao ProSiga? <a href="<?= e(app_url('entrar.php')) ?>">Entrar no sistema</a></p>
  </main>

  <footer class="account-footer" id="ajuda">
    <span class="account-footer__brand"><img src="<?= e(app_url('assets/img/logo-mark.png')) ?>" alt=""><strong>ProSiga</strong> © <?= date('Y') ?> Todos os direitos reservados.</span>
    <span class="account-footer__links"><a href="#termos">Termos de Uso</a><a href="#privacidade">Política de Privacidade</a><a href="#suporte">Suporte Técnico</a></span>
  </footer>
</div>
</body>
</html>
