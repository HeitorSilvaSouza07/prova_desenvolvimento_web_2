<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['admin', 'professor']);

$user = current_user();
$pdo  = db();

$turmaSelecionada = (int) ($_REQUEST['turma'] ?? 0);

/* --------------------------- Salvar vínculos -------------------------- */
if (is_post() && ($_POST['acao'] ?? '') === 'salvar') {
    if (!csrf_check()) {
        flash_set('error', 'Sessão expirada. Recarregue a página e tente novamente.');
        redirect(app_url('dashboard/vinculos.php'));
    }

    $turmaId = (int) ($_POST['turma_id'] ?? 0);
    $professores = array_filter(array_map('intval', (array) ($_POST['professores'] ?? [])));
    $alunos      = array_filter(array_map('intval', (array) ($_POST['alunos'] ?? [])));

    $stmt = $pdo->prepare('SELECT id, capacidade FROM turmas WHERE id = :id');
    $stmt->execute([':id' => $turmaId]);
    $turma = $stmt->fetch();

    if (!$turma) {
        flash_set('error', 'Selecione uma turma válida.');
        redirect(app_url('dashboard/vinculos.php'));
    }
    if (count($alunos) > (int) $turma['capacidade']) {
        flash_set('error', 'A quantidade de alunos selecionados excede a capacidade da turma ('
            . (int) $turma['capacidade'] . ').');
        redirect(app_url('dashboard/vinculos.php?turma=' . $turmaId));
    }

    $professores = array_values(array_unique($professores));
    $alunos      = array_values(array_unique($alunos));

    try {
        $pdo->beginTransaction();

        $pdo->prepare('DELETE FROM turma_professores WHERE turma_id = :t')->execute([':t' => $turmaId]);
        $pdo->prepare('DELETE FROM turma_alunos WHERE turma_id = :t')->execute([':t' => $turmaId]);

        if ($professores) {
            $in = implode(',', array_fill(0, count($professores), '?'));
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE tipo = 'professor' AND ativo = 1 AND id IN ($in)");
            $stmt->execute($professores);
            $validos = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            $ins = $pdo->prepare('INSERT INTO turma_professores (turma_id, professor_id) VALUES (:t, :p)');
            foreach ($validos as $pid) {
                $ins->execute([':t' => $turmaId, ':p' => $pid]);
            }
        }

        if ($alunos) {
            $in = implode(',', array_fill(0, count($alunos), '?'));
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE tipo = 'aluno' AND ativo = 1 AND id IN ($in)");
            $stmt->execute($alunos);
            $validos = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            $ins = $pdo->prepare('INSERT INTO turma_alunos (turma_id, aluno_id) VALUES (:t, :a)');
            foreach ($validos as $aid) {
                $ins->execute([':t' => $turmaId, ':a' => $aid]);
            }
        }

        $pdo->commit();
        flash_set('success', 'Vínculos atualizados: ' . count($professores) . ' professor(es) e '
            . count($alunos) . ' aluno(s).');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash_set('error', 'Não foi possível salvar os vínculos.');
    }

    redirect(app_url('dashboard/vinculos.php?turma=' . $turmaId));
}

/* ------------------------------- Dados -------------------------------- */
$turmas = $pdo->query('SELECT t.*, (SELECT COUNT(*) FROM turma_alunos ta WHERE ta.turma_id = t.id) AS total_alunos,
                       (SELECT COUNT(*) FROM turma_professores tp WHERE tp.turma_id = t.id) AS total_professores
                       FROM turmas t ORDER BY t.nome')->fetchAll();

$turmaAtual = null;
foreach ($turmas as $t) {
    if ((int) $t['id'] === $turmaSelecionada) {
        $turmaAtual = $t;
        break;
    }
}

$vinculadosProfessores = [];
$vinculadosAlunos = [];

if ($turmaAtual) {
    $stmt = $pdo->prepare('SELECT professor_id FROM turma_professores WHERE turma_id = :t');
    $stmt->execute([':t' => $turmaAtual['id']]);
    $vinculadosProfessores = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    $stmt = $pdo->prepare('SELECT aluno_id FROM turma_alunos WHERE turma_id = :t');
    $stmt->execute([':t' => $turmaAtual['id']]);
    $vinculadosAlunos = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

$professores = $pdo->query(
    "SELECT id, nome, email, matricula, setor FROM usuarios
     WHERE tipo = 'professor' AND ativo = 1 ORDER BY nome"
)->fetchAll();

$alunos = $pdo->query(
    "SELECT id, nome, email, matricula, setor FROM usuarios
     WHERE tipo = 'aluno' AND ativo = 1 ORDER BY nome"
)->fetchAll();

$pageHeader = [
    'title' => 'Vínculos',
    'sub'   => 'Associe professores e alunos às turmas. Desmarque para remover um vínculo.',
];

$pageTitle = 'Vínculos';
$active = 'vinculos';
require __DIR__ . '/../includes/header.php';
?>

<section class="card">
  <div class="card__head">
    <div>
      <h2 class="card__title">Selecionar turma</h2>
      <p class="card__sub">Escolha a turma para gerenciar os vínculos de professores e alunos.</p>
    </div>
  </div>

  <form method="get" action="<?= e(app_url('dashboard/vinculos.php')) ?>" class="toolbar">
    <label class="field">
      <span>Turma</span>
      <select name="turma" onchange="this.form.submit()">
        <option value="">— Selecione —</option>
        <?php foreach ($turmas as $t): ?>
          <option value="<?= (int) $t['id'] ?>" <?= (int) $t['id'] === $turmaSelecionada ? 'selected' : '' ?>>
            <?= e($t['nome'] . ' — ' . $t['disciplina'] . ' (' . $t['codigo'] . ')') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <div class="toolbar__spacer"></div>
    <button class="btn btn--ghost" type="submit">Abrir turma</button>
  </form>

  <?php if (!$turmas): ?>
    <div class="hint">Nenhuma turma cadastrada ainda. <a href="<?= e(app_url('dashboard/turmas.php')) ?>">Crie a primeira turma</a> para começar a vincular.</div>
  <?php endif; ?>
</section>

<?php if ($turmaAtual): ?>
  <div class="hint">
    <strong><?= e($turmaAtual['nome']) ?></strong> · <?= e($turmaAtual['disciplina']) ?> ·
    semestre <?= e($turmaAtual['semestre']) ?> ·
    <?= count($vinculadosProfessores) ?> professor(es) e <?= count($vinculadosAlunos) ?> aluno(s) vinculado(s) ·
    capacidade <?= (int) $turmaAtual['capacidade'] ?> ·
    <?= e($turmaAtual['sala'] ?: 'sem sala definida') ?>
  </div>

  <form method="post" action="<?= e(app_url('dashboard/vinculos.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="acao" value="salvar">
    <input type="hidden" name="turma_id" value="<?= (int) $turmaAtual['id'] ?>">

    <div class="link-grid">
      <section class="card mb-0">
        <div class="card__head">
          <div>
            <h2 class="card__title">Professores</h2>
            <p class="card__sub">Marque os professores desta turma.</p>
          </div>
          <span class="badge badge--green"><?= count($vinculadosProfessores) ?> vinculado(s)</span>
        </div>

        <div class="check-list">
          <?php if (!$professores): ?>
            <p class="muted text-small">Nenhum professor ativo. <a href="<?= e(app_url('dashboard/professores.php')) ?>">Cadastre um professor</a>.</p>
          <?php endif; ?>
          <?php foreach ($professores as $prof):
              $checked = in_array((int) $prof['id'], $vinculadosProfessores, true);
          ?>
            <label class="check-item<?= $checked ? ' check-item--linked' : '' ?>">
              <input type="checkbox" name="professores[]" value="<?= (int) $prof['id'] ?>" <?= $checked ? 'checked' : '' ?>>
              <span class="check-item__meta">
                <strong><?= e($prof['nome']) ?></strong>
                <small><?= e($prof['setor'] ?: $prof['email']) ?></small>
              </span>
            </label>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="card mb-0">
        <div class="card__head">
          <div>
            <h2 class="card__title">Alunos</h2>
            <p class="card__sub">Marque os alunos desta turma.</p>
          </div>
          <span class="badge badge--green"><?= count($vinculadosAlunos) ?> vinculado(s)</span>
        </div>

        <div class="check-list">
          <?php if (!$alunos): ?>
            <p class="muted text-small">Nenhum aluno ativo. <a href="<?= e(app_url('cadastro.php?type=aluno')) ?>">Peça o cadastro</a> ou use o <a href="<?= e(app_url('dashboard/alunos.php')) ?>">cadastro de alunos</a>.</p>
          <?php endif; ?>
          <?php foreach ($alunos as $aluno):
              $checked = in_array((int) $aluno['id'], $vinculadosAlunos, true);
          ?>
            <label class="check-item<?= $checked ? ' check-item--linked' : '' ?>">
              <input type="checkbox" name="alunos[]" value="<?= (int) $aluno['id'] ?>" <?= $checked ? 'checked' : '' ?>>
              <span class="check-item__meta">
                <strong><?= e($aluno['nome']) ?></strong>
                <small><?= e($aluno['matricula'] ?: $aluno['email']) ?></small>
              </span>
            </label>
          <?php endforeach; ?>
        </div>
      </section>
    </div>

    <div class="form-actions">
      <button class="btn btn--primary" type="submit">
        <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        Salvar vínculos
      </button>
      <a class="btn btn--ghost" href="<?= e(app_url('dashboard/vinculos.php')) ?>">Limpar seleção</a>
    </div>
  </form>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
