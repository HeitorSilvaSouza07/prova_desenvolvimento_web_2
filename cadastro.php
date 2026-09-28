<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect(app_url('dashboard/index.php'));
}

$type        = ($_GET['type'] ?? '') === 'professor' ? 'professor' : 'aluno';
$isProfessor = $type === 'professor';

$errors = [];
$form = [
    'nome'          => '',
    'email'         => '',
    'identificador' => '',
    'curso'         => '',
];

if (is_post()) {
    $form['nome']          = trim((string) ($_POST['nome'] ?? ''));
    $form['email']         = trim((string) ($_POST['email'] ?? ''));
    $form['identificador'] = trim((string) ($_POST['identificador'] ?? ''));
    $form['curso']         = trim((string) ($_POST['curso'] ?? ''));
    $senha                 = (string) ($_POST['senha'] ?? '');
    $confirmar             = (string) ($_POST['confirmar_senha'] ?? '');
    $tipo                  = ($_POST['tipo'] ?? '') === 'professor' ? 'professor' : 'aluno';

    if (!csrf_check()) {
        $errors[] = 'Sessão expirada. Recarregue a página e tente novamente.';
    }
    if ($form['nome'] === '' || mb_strlen($form['nome']) < 3) {
        $errors[] = 'Informe seu nome completo.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Informe um e-mail válido.';
    }
    if ($form['identificador'] === '') {
        $errors[] = $tipo === 'professor' ? 'Informe o registro funcional.' : 'Informe a matrícula.';
    }
    if (mb_strlen($senha) < 8) {
        $errors[] = 'A senha precisa ter no mínimo 8 caracteres.';
    }
    if ($senha !== $confirmar) {
        $errors[] = 'As senhas precisam ser iguais.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT COUNT(*) AS total FROM usuarios WHERE email = :email');
        $stmt->execute([':email' => $form['email']]);
        if ((int) $stmt->fetch()['total'] > 0) {
            $errors[] = 'Já existe uma conta com este e-mail.';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT COUNT(*) AS total FROM usuarios WHERE matricula = :matricula');
        $stmt->execute([':matricula' => $form['identificador']]);
        if ((int) $stmt->fetch()['total'] > 0) {
            $errors[] = 'Já existe uma conta com esta matrícula/registro.';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO usuarios (nome, email, senha, tipo, matricula, setor)
             VALUES (:nome, :email, :senha, :tipo, :matricula, :setor)'
        );
        $stmt->execute([
            ':nome'      => $form['nome'],
            ':email'     => $form['email'],
            ':senha'     => password_hash($senha, PASSWORD_BCRYPT),
            ':tipo'      => $tipo,
            ':matricula' => $form['identificador'],
            ':setor'     => $form['curso'] !== '' ? $form['curso'] : null,
        ]);

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) db()->lastInsertId();

        flash_set('success', 'Conta criada com sucesso! Bem-vindo ao ProSiga.');
        redirect(app_url('dashboard/index.php'));
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Criar Conta — ProSiga</title>
<link rel="icon" href="<?= e(app_url('assets/img/logo-mark.png')) ?>">
<link rel="stylesheet" href="<?= e(app_url('assets/css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(app_url('assets/css/signup.css')) ?>">
</head>
<body>
<div class="signup-page">
  <header class="signup-header">
    <a href="<?= e(app_url('index.php')) ?>" class="signup-brand">
      <span class="signup-brand__mark"><img src="<?= e(app_url('assets/img/logo-mark.png')) ?>" alt=""></span>ProSiga
    </a>
  </header>

  <main class="signup-main">
    <div class="signup-side">
      <a class="signup-back" href="<?= e(app_url('criar-conta.php')) ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
        Trocar tipo de conta
      </a>
      <div class="signup-side-art">
        <img class="signup-side-mascot"
             src="<?= e(app_url('assets/img/' . ($isProfessor ? 'mascot-teacher.svg' : 'mascot-happy.svg'))) ?>"
             alt="<?= $isProfessor ? 'Mascote professor do ProSiga' : 'Mascote aluno feliz do ProSiga' ?>">
      </div>
      <h2><?= $isProfessor
            ? 'Organize sua rotina e inspire seus <strong>alunos</strong>'
            : 'Seu caminho acadêmico começa aqui, <em>aluno</em>' ?></h2>
      <p><?= $isProfessor
            ? 'Crie suas turmas, vincule alunos, agende aulas, provas e atividades e mantenha toda a comunicação acadêmica em um só lugar.'
            : 'Tenha suas disciplinas, aulas, provas e atividades sempre ao alcance para estudar com mais tranquilidade.' ?></p>
    </div>

    <section class="signup-card" aria-labelledby="signup-title">
      <div class="signup-card__bar"></div>
      <div class="signup-card__body">
        <span class="signup-badge">Cadastro de <?= $isProfessor ? 'professor' : 'aluno' ?></span>
        <h1 id="signup-title">Criar Conta</h1>
        <p class="signup-subtitle">Preencha os dados abaixo para <?= $isProfessor ? 'organizar suas turmas' : 'acompanhar suas disciplinas' ?> no ProSiga.</p>

        <?php if ($errors): ?>
          <div class="signup-feedback" role="alert">
            <?php foreach ($errors as $msg): ?>
              <div><?= e($msg) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form class="signup-form" method="post" action="<?= e(app_url('cadastro.php?type=' . $type)) ?>" novalidate data-auth-form>
          <?= csrf_field() ?>
          <input type="hidden" name="tipo" value="<?= e($type) ?>">

          <label class="signup-field">
            <span>Nome completo</span>
            <div class="signup-input">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>
              <input type="text" name="nome" value="<?= e($form['nome']) ?>" placeholder="ex: Maria Oliveira" required>
            </div>
          </label>

          <label class="signup-field">
            <span><?= $isProfessor ? 'E-mail institucional' : 'E-mail' ?></span>
            <div class="signup-input">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4V6Zm0 1 8 6 8-6"/></svg>
              <input type="email" name="email" value="<?= e($form['email']) ?>"
                     placeholder="<?= $isProfessor ? 'ex: professor@prosiga.edu' : 'ex: aluno@prosiga.edu' ?>" required>
            </div>
          </label>

          <label class="signup-field">
            <span><?= $isProfessor ? 'Registro funcional' : 'Matrícula' ?></span>
            <div class="signup-input">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h10l3 4v13H4V7l3-4ZM9 12h6M9 16h6"/></svg>
              <input type="text" name="identificador" value="<?= e($form['identificador']) ?>"
                     placeholder="<?= $isProfessor ? 'ex: 4521' : 'ex: 20261002' ?>" required>
            </div>
          </label>

          <label class="signup-field">
            <span><?= $isProfessor ? 'Departamento' : 'Curso' ?></span>
            <div class="signup-input">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V7l8-4 8 4v13M2 20h20M8 20v-5h8v5"/></svg>
              <input type="text" name="curso" value="<?= e($form['curso']) ?>"
                     placeholder="<?= $isProfessor ? 'ex: Ciência da Computação' : 'ex: Engenharia de Software' ?>">
            </div>
          </label>

          <div class="signup-field-row">
            <label class="signup-field">
              <span>Senha</span>
              <div class="signup-input">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                <input type="password" name="senha" id="signup-senha" minlength="8" placeholder="Crie uma senha" required>
                <button type="button" class="signup-eye" data-toggle-password="signup-senha" aria-label="Mostrar senha">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
            </label>

            <label class="signup-field">
              <span>Confirmar senha</span>
              <div class="signup-input">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                <input type="password" name="confirmar_senha" id="signup-senha-conf" minlength="8" placeholder="Repita a senha" required>
              </div>
            </label>
          </div>

          <label class="signup-checkbox">
            <input type="checkbox" required>
            <span>Li e aceito os <a href="#termos">Termos de Uso</a> e a <a href="#privacidade">Política de Privacidade</a>.</span>
          </label>

          <button type="submit" class="signup-submit">
            Criar Conta
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </button>

          <p class="signup-login">Já possui acesso ao ProSiga? <a href="<?= e(app_url('entrar.php')) ?>">Entrar</a></p>
        </form>
      </div>
    </section>
  </main>

  <footer class="signup-footer" id="ajuda">
    <span class="signup-footer__brand"><img src="<?= e(app_url('assets/img/logo-mark.png')) ?>" alt=""><strong>ProSiga</strong> © <?= date('Y') ?> Todos os direitos reservados.</span>
    <span class="signup-footer__links"><a href="#termos">Termos de Uso</a><a href="#privacidade">Política de Privacidade</a><a href="#suporte">Suporte Técnico</a></span>
  </footer>
</div>
<script src="<?= e(app_url('assets/js/auth.js')) ?>"></script>
</body>
</html>
