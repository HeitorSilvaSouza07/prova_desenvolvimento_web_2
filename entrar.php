<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect(app_url('dashboard/index.php'));
}

$error = '';
$identifier = '';

if (is_post()) {
    $identifier = trim((string) ($_POST['identifier'] ?? ''));
    $senha      = (string) ($_POST['senha'] ?? '');

    if (!csrf_check()) {
        $error = 'Sessão expirada. Recarregue a página e tente novamente.';
    } elseif ($identifier === '' || $senha === '') {
        $error = 'Preencha seu e-mail ou matrícula e sua senha.';
    } else {
        $stmt = db()->prepare('SELECT * FROM usuarios WHERE (email = :email OR matricula = :matricula) AND ativo = 1 LIMIT 1');
        $stmt->execute([':email' => $identifier, ':matricula' => $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['senha'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            if (!empty($_POST['lembrar'])) {
                $token = bin2hex(random_bytes(32));
                $hash  = hash('sha256', $token);
                $stmt  = db()->prepare('UPDATE usuarios SET token = :token WHERE id = :id');
                $stmt->execute([':token' => $hash, ':id' => $user['id']]);
                setcookie('prosiga_token', $token, [
                    'expires'  => time() + 60 * 60 * 24 * 30,
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
            flash_set('success', 'Bem-vindo de volta, ' . explode(' ', $user['nome'])[0] . '!');
            redirect(app_url('dashboard/index.php'));
        }

        $error = 'E-mail, matrícula ou senha inválidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Entrar — ProSiga</title>
<link rel="icon" href="<?= e(app_url('assets/img/logo-mark.png')) ?>">
<link rel="stylesheet" href="<?= e(app_url('assets/css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(app_url('assets/css/login.css')) ?>">
</head>
<body>
<div class="login-page">
  <header class="login-header">
    <a href="<?= e(app_url('index.php')) ?>" class="login-brand" aria-label="ProSiga - página inicial">
      <img src="<?= e(app_url('assets/img/logo-mark.png')) ?>" alt="" class="login-brand__logo">
      <span>ProSiga</span>
    </a>
    <nav class="login-nav" aria-label="Navegação principal"></nav>
  </header>

  <main class="login-main">
    <section class="login-intro" id="sobre">
      <div class="login-mascot-wrap">
        <img class="login-mascot" src="<?= e(app_url('assets/img/mascot-teacher.svg')) ?>"
             alt="Mascote ProSiga usando um capelo de formatura">
      </div>
      <div class="login-intro__copy">
        <h1>Feito para o <em>aluno</em> e para o <strong>professor</strong></h1>
        <p>O ProSiga reúne turmas, aulas, provas, atividades e comunicação acadêmica em uma plataforma moderna,
           intuitiva e acessível.</p>
      </div>
    </section>

    <section class="login-card" aria-labelledby="login-title">
      <div class="login-card__accent"></div>
      <div class="login-card__heading">
        <h2 id="login-title">Acessar o Portal</h2>
        <p>Insira suas credenciais para continuar no sistema</p>
      </div>

      <?php if ($error !== ''): ?>
        <div class="login-feedback login-feedback--error" role="status">
          <span aria-hidden="true">!</span><?= e($error) ?>
        </div>
      <?php endif; ?>
      <?= flash_show() ?>

      <form class="login-form" method="post" action="<?= e(app_url('entrar.php')) ?>" novalidate data-auth-form>
        <?= csrf_field() ?>

        <label class="login-field">
          <span>E-mail ou Matrícula</span>
          <div class="login-input-wrap">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>
            <input type="text" name="identifier" value="<?= e($identifier) ?>"
                   placeholder="ex: aluno@prosiga.edu ou 20261002" autocomplete="username" required>
          </div>
        </label>

        <label class="login-field">
          <span class="login-field__label-row">
            Senha
            <a href="<?= e(app_url('criar-conta.php')) ?>">Criar conta</a>
          </span>
          <div class="login-input-wrap">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
            <input type="password" name="senha" id="login-senha" placeholder="••••••••"
                   autocomplete="current-password" required>
            <button type="button" class="login-password-toggle" data-toggle-password="login-senha"
                    aria-label="Mostrar senha">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </label>

        <label class="login-remember">
          <input type="checkbox" name="lembrar" value="1">
          <span>Mantenha-me conectado neste dispositivo</span>
        </label>

        <button type="submit" class="login-submit">
          <span>Entrar no Sistema</span>
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
      </form>

      <div class="login-divider"><span>acesso de demonstração</span></div>
      <p class="login-signup">
        Admin: <strong>admin@prosiga.edu</strong> / <strong>admin123</strong><br>
      </p>

      <p class="login-signup">Ainda não possui acesso ao ProSiga?
        <a href="<?= e(app_url('criar-conta.php')) ?>">Solicite seu cadastro</a>
      </p>
    </section>
  </main>

  <footer class="login-footer" id="ajuda">
    <span class="login-footer__brand"><img src="<?= e(app_url('assets/img/logo-mark.png')) ?>" alt=""><strong>ProSiga</strong> © <?= date('Y') ?> Todos os direitos reservados.</span>
    <span class="login-footer__links"><a href="#termos">Termos de Uso</a><a href="#privacidade">Política de Privacidade</a><a href="#suporte">Suporte Técnico</a></span>
  </footer>
</div>
<script src="<?= e(app_url('assets/js/auth.js')) ?>"></script>
</body>
</html>
