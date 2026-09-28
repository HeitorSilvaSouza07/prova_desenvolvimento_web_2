<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['admin', 'professor']);

$user = current_user();
$pdo = db();

/* ------------------------------ Cadastro ------------------------------ */
if (is_post() && ($_POST['acao'] ?? '') === 'criar') {
    if (!csrf_check()) {
        flash_set('error', 'Sessão expirada. Recarregue a página e tente novamente.');
        redirect(app_url('dashboard/professores.php'));
    }

    $nome      = trim((string) ($_POST['nome'] ?? ''));
    $email     = trim((string) ($_POST['email'] ?? ''));
    $matricula = trim((string) ($_POST['matricula'] ?? ''));
    $setor     = trim((string) ($_POST['setor'] ?? ''));
    $senha     = (string) ($_POST['senha'] ?? '');

    $erros = [];
    if (mb_strlen($nome) < 3)                       { $erros[] = 'Informe o nome completo do professor.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $erros[] = 'Informe um e-mail válido.'; }
    if ($matricula === '')                          { $erros[] = 'Informe o registro funcional.'; }
    if (mb_strlen($senha) < 8)                      { $erros[] = 'A senha precisa ter no mínimo 8 caracteres.'; }

    if (!$erros) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE email = :email OR matricula = :matricula');
        $stmt->execute([':email' => $email, ':matricula' => $matricula]);
        if ((int) $stmt->fetchColumn() > 0) {
            $erros[] = 'Já existe um usuário com este e-mail ou registro.';
        }
    }

    if ($erros) {
        flash_set('error', implode(' ', $erros));
        redirect(app_url('dashboard/professores.php'));
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
            ':tipo'      => 'professor',
            ':matricula' => $matricula,
            ':setor'     => $setor !== '' ? $setor : null,
        ]);
        flash_set('success', 'Professor "' . $nome . '" cadastrado com sucesso.');
    } catch (PDOException $e) {
        flash_set('error', $e->getCode() === '23000'
            ? 'Já existe um usuário com este e-mail ou registro.'
            : 'Não foi possível cadastrar o professor.');
    }
    redirect(app_url('dashboard/professores.php'));
}

/* -------------------------- Ativar / desativar ------------------------ */
if (is_post() && ($_POST['acao'] ?? '') === 'alternar') {
    if ($user['tipo'] !== 'admin' || !csrf_check()) {
        flash_set('error', 'Apenas o administrador pode alterar o status do usuário.');
        redirect(app_url('dashboard/professores.php'));
    }
    $id = (int) ($_POST['usuario_id'] ?? 0);
    if ($id === (int) $user['id']) {
        flash_set('error', 'Você não pode desativar o próprio usuário.');
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios SET ativo = 1 - ativo WHERE id = :id AND tipo = 'professor'");
        $stmt->execute([':id' => $id]);
        flash_set('success', 'Status do professor atualizado.');
    }
    redirect(app_url('dashboard/professores.php'));
}

$stmt = $pdo->query(
    "SELECT u.*,
            (SELECT COUNT(*) FROM turma_professores tp WHERE tp.professor_id = u.id) AS total_turmas
     FROM usuarios u
     WHERE u.tipo = 'professor'
     ORDER BY u.ativo DESC, u.nome ASC"
);
$professores = $stmt->fetchAll();

$pageHeader = [
    'title'  => 'Professores',
    'sub'    => 'Cadastre professores e acompanhe em quais turmas cada um está vinculado.',
    'action' => '<button class="btn btn--primary" type="button" data-scroll-to="form-professor">'
              . '<svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Novo professor</button>',
];

$pageTitle = 'Professores';
$active = 'professores';
require __DIR__ . '/../includes/header.php';
?>

<section class="card" id="form-professor">
  <div class="card__head">
    <div>
      <h2 class="card__title">Cadastrar professor</h2>
      <p class="card__sub">O professor recebe um acesso próprio e já pode gerenciar as suas turmas.</p>
    </div>
  </div>

  <form method="post" action="<?= e(app_url('dashboard/professores.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="acao" value="criar">

    <div class="form-grid">
      <label class="field">
        <span>Nome completo *</span>
        <input type="text" name="nome" placeholder="ex: Ana Beatriz Costa" required>
      </label>
      <label class="field">
        <span>E-mail institucional *</span>
        <input type="email" name="email" placeholder="ex: professor@prosiga.edu" required>
      </label>
      <label class="field">
        <span>Registro funcional *</span>
        <input type="text" name="matricula" placeholder="ex: 4521" required>
      </label>
      <label class="field">
        <span>Departamento</span>
        <input type="text" name="setor" placeholder="ex: Ciência da Computação">
      </label>
      <label class="field">
        <span>Senha provisória *</span>
        <input type="password" name="senha" minlength="8" placeholder="mínimo 8 caracteres" required>
      </label>
    </div>

    <div class="form-actions">
      <button class="btn btn--primary" type="submit">
        <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        Cadastrar professor
      </button>
      <button class="btn btn--ghost" type="reset">Limpar</button>
    </div>
  </form>
</section>

<section class="card" data-table>
  <div class="card__head">
    <div>
      <h2 class="card__title">Professores cadastrados</h2>
      <p class="card__sub"><span data-table-count><?= count($professores) ?> registros</span></p>
    </div>
  </div>

  <div class="toolbar">
    <label class="field">
      <span>Buscar</span>
      <input type="search" data-table-search placeholder="Nome, e-mail ou registro...">
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
          <th>Professor</th>
          <th>Registro</th>
          <th>Departamento</th>
          <th>Turmas</th>
          <th>Status</th>
          <?php if ($user['tipo'] === 'admin'): ?><th></th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($professores as $prof): ?>
          <tr data-row data-group="<?= $prof['ativo'] ? 'ativos' : 'inativos' ?>">
            <td>
              <strong><?= e($prof['nome']) ?></strong><br>
              <small class="muted"><?= e($prof['email']) ?></small>
            </td>
            <td><?= e($prof['matricula'] ?: '—') ?></td>
            <td><?= e($prof['setor'] ?: '—') ?></td>
            <td><span class="badge"><?= (int) $prof['total_turmas'] ?></span></td>
            <td>
              <?php if ($prof['ativo']): ?>
                <span class="badge badge--green"><span class="badge-dot"></span>Ativo</span>
              <?php else: ?>
                <span class="badge badge--red"><span class="badge-dot"></span>Inativo</span>
              <?php endif; ?>
            </td>
            <?php if ($user['tipo'] === 'admin'): ?>
              <td class="actions">
                <form class="inline-form" method="post" action="<?= e(app_url('dashboard/professores.php')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="acao" value="alternar">
                  <input type="hidden" name="usuario_id" value="<?= (int) $prof['id'] ?>">
                  <button class="btn btn--ghost btn--sm" type="submit"
                          data-confirm="<?= $prof['ativo'] ? 'Desativar' : 'Reativar' ?> o professor <?= e($prof['nome']) ?>?">
                    <?= $prof['ativo'] ? 'Desativar' : 'Reativar' ?>
                  </button>
                </form>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tbody>
        <tr data-table-empty<?= $professores ? ' hidden' : '' ?>>
          <td colspan="6">
            <div class="table-empty">
              <img src="<?= e(app_url('assets/img/mascot-confused.svg')) ?>" alt="">
              <p class="mb-0">Nenhum professor cadastrado.</p>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
