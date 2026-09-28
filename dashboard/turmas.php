<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = current_user();
$podeGerenciar = pode_gerenciar($user);
$pdo = db();

/* ------------------------------ Cadastro ------------------------------ */
if (is_post() && isset($_POST['acao']) && $_POST['acao'] === 'criar') {
    if (!$podeGerenciar) {
        flash_set('error', 'Você não tem permissão para criar turmas.');
        redirect(app_url('dashboard/turmas.php'));
    }
    if (!csrf_check()) {
        flash_set('error', 'Sessão expirada. Recarregue a página e tente novamente.');
        redirect(app_url('dashboard/turmas.php'));
    }

    $nome       = trim((string) ($_POST['nome'] ?? ''));
    $disciplina = trim((string) ($_POST['disciplina'] ?? ''));
    $codigo     = strtoupper(trim((string) ($_POST['codigo'] ?? '')));
    $semestre   = trim((string) ($_POST['semestre'] ?? ''));
    $sala       = trim((string) ($_POST['sala'] ?? ''));
    $capacidade = (int) ($_POST['capacidade'] ?? 40);

    if ($nome === '' || $disciplina === '' || $codigo === '') {
        flash_set('error', 'Preencha nome, disciplina e código da turma.');
        redirect(app_url('dashboard/turmas.php'));
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO turmas (nome, disciplina, codigo, semestre, sala, capacidade, criado_por)
             VALUES (:nome, :disciplina, :codigo, :semestre, :sala, :capacidade, :criado_por)'
        );
        $stmt->execute([
            ':nome'       => $nome,
            ':disciplina' => $disciplina,
            ':codigo'     => $codigo,
            ':semestre'   => $semestre !== '' ? $semestre : date('Y') . '.1',
            ':sala'       => $sala !== '' ? $sala : null,
            ':capacidade' => $capacidade > 0 ? $capacidade : 40,
            ':criado_por' => $user['id'],
        ]);
        flash_set('success', 'Turma "' . $nome . '" criada com sucesso.');
    } catch (PDOException $e) {
        flash_set('error', $e->getCode() === '23000'
            ? 'Já existe uma turma com este código.'
            : 'Não foi possível criar a turma.');
    }
    redirect(app_url('dashboard/turmas.php'));
}

/* ------------------------------- Exclusão ----------------------------- */
if (is_post() && isset($_POST['acao']) && $_POST['acao'] === 'excluir') {
    if (!$podeGerenciar || !csrf_check()) {
        flash_set('error', 'Operação não permitida.');
        redirect(app_url('dashboard/turmas.php'));
    }

    $id = (int) ($_POST['turma_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM turmas WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $turma = $stmt->fetch();

    if (!$turma) {
        flash_set('error', 'Turma não encontrada.');
    } elseif ($user['tipo'] !== 'admin' && (int) $turma['criado_por'] !== (int) $user['id']) {
        flash_set('error', 'Somente o administrador ou o criador da turma pode excluí-la.');
    } else {
        $del = $pdo->prepare('DELETE FROM turmas WHERE id = :id');
        $del->execute([':id' => $id]);
        flash_set('success', 'Turma excluída com sucesso.');
    }
    redirect(app_url('dashboard/turmas.php'));
}

$turmas = turmas_visiveis($pdo, $user);

$pageHeader = [
    'title' => 'Turmas',
    'sub'   => $podeGerenciar
        ? 'Crie turmas, acompanhe o quantitativo de alunos e professores vinculados.'
        : 'Relação de turmas às quais você está vinculado.',
    'action' => $podeGerenciar
        ? '<button class="btn btn--primary" type="button" data-scroll-to="form-turma">'
             . '<svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Nova turma</button>'
        : '',
];

$pageTitle = 'Turmas';
$active = 'turmas';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($podeGerenciar): ?>
<section class="card" id="form-turma">
  <div class="card__head">
    <div>
      <h2 class="card__title">Criar turma</h2>
      <p class="card__sub">O código é usado para identificar rapidamente a turma em todo o sistema.</p>
    </div>
  </div>

  <form method="post" action="<?= e(app_url('dashboard/turmas.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="acao" value="criar">

    <div class="form-grid">
      <label class="field">
        <span>Nome da turma *</span>
        <input type="text" name="nome" placeholder="ex: 3º Ano A" required>
      </label>

      <label class="field">
        <span>Disciplina *</span>
        <input type="text" name="disciplina" placeholder="ex: Matemática" required>
      </label>

      <label class="field">
        <span>Código *</span>
        <input type="text" name="codigo" placeholder="ex: MAT-3A-01" required>
      </label>

      <label class="field">
        <span>Semestre</span>
        <input type="text" name="semestre" placeholder="ex: 2026.1" value="<?= date('Y') ?>.1">
      </label>

      <label class="field">
        <span>Sala</span>
        <input type="text" name="sala" placeholder="ex: Bloco B - 12">
      </label>

      <label class="field">
        <span>Capacidade</span>
        <input type="number" name="capacidade" min="1" max="200" value="40">
      </label>
    </div>

    <div class="form-actions">
      <button class="btn btn--primary" type="submit">
        <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        Criar turma
      </button>
      <button class="btn btn--ghost" type="reset">Limpar</button>
    </div>
  </form>
</section>
<?php endif; ?>

<section class="card" data-table>
  <div class="card__head">
    <div>
      <h2 class="card__title"><?= $podeGerenciar ? 'Turmas cadastradas' : 'Minhas turmas' ?></h2>
      <p class="card__sub"><span data-table-count><?= count($turmas) ?> registros</span> em <strong><?= date('Y') ?>.1</strong></p>
    </div>
  </div>

  <div class="toolbar">
    <label class="field">
      <span>Buscar</span>
      <input type="search" data-table-search placeholder="Nome, disciplina ou código...">
    </label>
    <div class="toolbar__spacer"></div>
  </div>

  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th>Turma</th>
          <th>Disciplina</th>
          <th>Semestre</th>
          <th>Sala</th>
          <th>Alunos</th>
          <th>Professores</th>
          <?php if ($podeGerenciar): ?><th></th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($turmas as $turma): ?>
          <tr data-row>
            <td>
              <strong><?= e($turma['nome']) ?></strong><br>
              <small class="muted"><?= e($turma['codigo']) ?></small>
            </td>
            <td><?= e($turma['disciplina']) ?></td>
            <td><span class="badge badge--muted"><?= e($turma['semestre']) ?></span></td>
            <td><?= e($turma['sala'] ?: '—') ?></td>
            <td><span class="badge"><?= (int) $turma['total_alunos'] ?> / <?= (int) $turma['capacidade'] ?></span></td>
            <td><span class="badge badge--green"><?= (int) $turma['total_professores'] ?></span></td>
            <?php if ($podeGerenciar): ?>
              <td class="actions">
                <a class="btn btn--ghost btn--sm" href="<?= e(app_url('dashboard/vinculos.php?turma=' . (int) $turma['id'])) ?>">Vincular</a>
                <?php if ($user['tipo'] === 'admin' || (int) $turma['criado_por'] === (int) $user['id']): ?>
                  <form class="inline-form" method="post" action="<?= e(app_url('dashboard/turmas.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="turma_id" value="<?= (int) $turma['id'] ?>">
                    <button class="btn btn--danger btn--sm" type="submit"
                            data-confirm="Excluir a turma <?= e($turma['nome']) ?>? Todos os vínculos e agendamentos serão removidos.">Excluir</button>
                  </form>
                <?php endif; ?>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tbody>
        <tr data-table-empty<?= $turmas ? ' hidden' : '' ?>>
          <td colspan="7">
            <div class="table-empty">
              <img src="<?= e(app_url('assets/img/mascot-confused.svg')) ?>" alt="">
              <p class="mb-0">Nenhuma turma encontrada.</p>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
