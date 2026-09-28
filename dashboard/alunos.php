<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['admin', 'professor']);

$user = current_user();
$pdo  = db();

if (is_post() && ($_POST['acao'] ?? '') === 'criar') {
    if (!csrf_check()) {
        flash_set('error', 'Sessão expirada. Recarregue a página e tente novamente.');
        redirect(app_url('dashboard/alunos.php'));
    }

    $nome      = trim((string) ($_POST['nome'] ?? ''));
    $email     = trim((string) ($_POST['email'] ?? ''));
    $matricula = trim((string) ($_POST['matricula'] ?? ''));
    $curso     = trim((string) ($_POST['curso'] ?? ''));
    $senha     = (string) ($_POST['senha'] ?? '');

    $erros = [];
    if (mb_strlen($nome) < 3)                       { $erros[] = 'Informe o nome completo do aluno.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $erros[] = 'Informe um e-mail válido.'; }
    if ($matricula === '')                          { $erros[] = 'Informe a matrícula.'; }
    if (mb_strlen($senha) < 8)                      { $erros[] = 'A senha precisa ter no mínimo 8 caracteres.'; }

    if (!$erros) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE email = :email OR matricula = :matricula');
        $stmt->execute([':email' => $email, ':matricula' => $matricula]);
        if ((int) $stmt->fetchColumn() > 0) {
            $erros[] = 'Já existe um usuário com este e-mail ou matrícula.';
        }
    }

    if ($erros) {
        flash_set('error', implode(' ', $erros));
        redirect(app_url('dashboard/alunos.php'));
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (nome, email, senha, tipo, matricula, setor)
             VALUES (:nome, :email, :senha, :tipo, :matricula, :setor)'
        );
        $stmt->execute([
            ':nome'      => $nome,
            ':email'     => $email,
            ':senha'     => password_hash($senha, PASSWORD_BCRYPT),
            ':tipo'      => 'aluno',
            ':matricula' => $matricula,
            ':setor'     => $curso !== '' ? $curso : null,
        ]);
        flash_set('success', 'Aluno "' . $nome . '" cadastrado com sucesso.');
    } catch (PDOException $e) {
        flash_set('error', $e->getCode() === '23000'
            ? 'Já existe um usuário com este e-mail ou matrícula.'
            : 'Não foi possível cadastrar o aluno.');
    }
    redirect(app_url('dashboard/alunos.php'));
}


if (is_post() && ($_POST['acao'] ?? '') === 'alternar') {
    if ($user['tipo'] !== 'admin' || !csrf_check()) {
        flash_set('error', 'Apenas o administrador pode alterar o status do usuário.');
        redirect(app_url('dashboard/alunos.php'));
    }
    $id = (int) ($_POST['usuario_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE usuarios SET ativo = 1 - ativo WHERE id = :id AND tipo = 'aluno' AND id <> :eu");
    $stmt->execute([':id' => $id, ':eu' => $user['id']]);
    flash_set('success', 'Status do aluno atualizado.');
    redirect(app_url('dashboard/alunos.php'));
}

$stmt = $pdo->query(
    "SELECT u.*,
            (SELECT COUNT(*) FROM turma_alunos ta WHERE ta.aluno_id = u.id) AS total_turmas
     FROM usuarios u
     WHERE u.tipo = 'aluno'
     ORDER BY u.ativo DESC, u.nome ASC"
);
$alunos = $stmt->fetchAll();

$pageHeader = [
    'title'  => 'Alunos',
    'sub'    => 'Cadastre alunos diretamente no sistema e vincule-os às turmas em "Vínculos".',
    'action' => '<button class="btn btn--primary" type="button" data-scroll-to="form-aluno">'
              . '<svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Novo aluno</button>',
];

$pageTitle = 'Alunos';
$active = 'alunos';
require __DIR__ . '/../includes/header.php';
?>

<section class="card" id="form-aluno">
  <div class="card__head">
    <div>
      <h2 class="card__title">Cadastrar aluno</h2>
      <p class="card__sub">Depois do cadastro, use a área de <a href="<?= e(app_url('dashboard/vinculos.php')) ?>">vínculos</a> para matriculá-lo nas turmas.</p>
    </div>
  </div>

  <form method="post" action="<?= e(app_url('dashboard/alunos.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="acao" value="criar">

    <div class="form-grid">
      <label class="field">
        <span>Nome completo *</span>
        <input type="text" name="nome" placeholder="ex: João Pedro Silva" required>
      </label>
      <label class="field">
        <span>E-mail *</span>
        <input type="email" name="email" placeholder="ex: aluno@prosiga.edu" required>
      </label>
      <label class="field">
        <span>Matrícula *</span>
        <input type="text" name="matricula" placeholder="ex: 20261002" required>
      </label>
      <label class="field">
        <span>Curso</span>
        <input type="text" name="curso" placeholder="ex: Engenharia de Software">
      </label>
      <label class="field">
        <span>Senha provisória *</span>
        <input type="password" name="senha" minlength="8" placeholder="mínimo 8 caracteres" required>
      </label>
    </div>

    <div class="form-actions">
      <button class="btn btn--primary" type="submit">
        <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        Cadastrar aluno
      </button>
      <button class="btn btn--ghost" type="reset">Limpar</button>
    </div>
  </form>
</section>

<section class="card" data-table>
  <div class="card__head">
    <div>
      <h2 class="card__title">Alunos cadastrados</h2>
      <p class="card__sub"><span data-table-count><?= count($alunos) ?> registros</span></p>
    </div>
  </div>

  <div class="toolbar">
    <label class="field">
      <span>Buscar</span>
      <input type="search" data-table-search placeholder="Nome, e-mail ou matrícula...">
    </label>
    <label class="field">
      <span>Status</span>
      <select data-table-filter>
        <option value="">Todos</option>
        <option value="ativos">Ativos</option>
        <option value="inativos">Inativos</option>
      </select>
    </label>
    <div class="toolbar__spacer"></div>
  </div>

  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th>Aluno</th>
          <th>Matrícula</th>
          <th>Curso</th>
          <th>Turmas</th>
          <th>Status</th>
          <?php if ($user['tipo'] === 'admin'): ?><th></th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($alunos as $aluno): ?>
          <tr data-row data-group="<?= $aluno['ativo'] ? 'ativos' : 'inativos' ?>">
            <td>
              <strong><?= e($aluno['nome']) ?></strong><br>
              <small class="muted"><?= e($aluno['email']) ?></small>
            </td>
            <td><?= e($aluno['matricula'] ?: '—') ?></td>
            <td><?= e($aluno['setor'] ?: '—') ?></td>
            <td><span class="badge"><?= (int) $aluno['total_turmas'] ?></span></td>
            <td>
              <?php if ($aluno['ativo']): ?>
                <span class="badge badge--green"><span class="badge-dot"></span>Ativo</span>
              <?php else: ?>
                <span class="badge badge--red"><span class="badge-dot"></span>Inativo</span>
              <?php endif; ?>
            </td>
            <?php if ($user['tipo'] === 'admin'): ?>
              <td class="actions">
                <form class="inline-form" method="post" action="<?= e(app_url('dashboard/alunos.php')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="acao" value="alternar">
                  <input type="hidden" name="usuario_id" value="<?= (int) $aluno['id'] ?>">
                  <button class="btn btn--ghost btn--sm" type="submit"
                          data-confirm="<?= $aluno['ativo'] ? 'Desativar' : 'Reativar' ?> o aluno <?= e($aluno['nome']) ?>?">
                    <?= $aluno['ativo'] ? 'Desativar' : 'Reativar' ?>
                  </button>
                </form>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tbody>
        <tr data-table-empty<?= $alunos ? ' hidden' : '' ?>>
          <td colspan="6">
            <div class="table-empty">
              <img src="<?= e(app_url('assets/img/mascot-confused.svg')) ?>" alt="">
              <p class="mb-0">Nenhum aluno cadastrado.</p>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
