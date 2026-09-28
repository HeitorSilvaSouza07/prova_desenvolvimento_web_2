<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = current_user();
$podeGerenciar = pode_gerenciar($user);
$pdo = db();

$turmas = turmas_visiveis($pdo, $user);
$turmaIds = array_map(fn($t) => (int) $t['id'], $turmas);
$placeholder = $turmaIds ? implode(',', $turmaIds) : '0';

/* ------------------------------ Agendar ------------------------------- */
if (is_post() && ($_POST['acao'] ?? '') === 'criar') {
    if (!$podeGerenciar) {
        flash_set('error', 'Somente professores podem agendar aulas.');
        redirect(app_url('dashboard/aulas.php'));
    }
    if (!csrf_check()) {
        flash_set('error', 'Sessão expirada. Recarregue a página e tente novamente.');
        redirect(app_url('dashboard/aulas.php'));
    }

    $turmaId     = (int) ($_POST['turma_id'] ?? 0);
    $titulo      = trim((string) ($_POST['titulo'] ?? ''));
    $dataAula    = (string) ($_POST['data_aula'] ?? '');
    $horaInicio  = (string) ($_POST['hora_inicio'] ?? '');
    $horaFim     = (string) ($_POST['hora_fim'] ?? '');
    $local       = trim((string) ($_POST['local'] ?? ''));
    $descricao   = trim((string) ($_POST['descricao'] ?? ''));
    $professorId = (int) ($_POST['professor_id'] ?? 0);

    $erros = [];
    if ($titulo === '')                              { $erros[] = 'Informe o título da aula.'; }
    if (!$dataAula || !strtotime($dataAula))         { $erros[] = 'Informe uma data válida.'; }
    if (!$horaInicio || !$horaFim)                   { $erros[] = 'Informe o horário de início e término.'; }
    elseif ($horaFim <= $horaInicio)                 { $erros[] = 'O horário final deve ser depois do inicial.'; }
    if (!in_array($turmaId, $turmaIds, true))        { $erros[] = 'Selecione uma turma válida.'; }

    if (!$erros) {
        $ins = $pdo->prepare(
            'INSERT INTO aulas (turma_id, professor_id, titulo, data_aula, hora_inicio, hora_fim, local, descricao, criado_por)
             VALUES (:turma, :professor, :titulo, :data, :inicio, :fim, :local, :descricao, :criador)'
        );
        $ins->execute([
            ':turma'     => $turmaId,
            ':professor' => $professorId > 0 ? $professorId : null,
            ':titulo'    => $titulo,
            ':data'      => $dataAula,
            ':inicio'    => $horaInicio,
            ':fim'       => $horaFim,
            ':local'     => $local !== '' ? $local : null,
            ':descricao' => $descricao !== '' ? $descricao : null,
            ':criador'   => $user['id'],
        ]);
        flash_set('success', 'Aula agendada para ' . formatar_data($dataAula) . '.');
    } else {
        flash_set('error', implode(' ', $erros));
    }
    redirect(app_url('dashboard/aulas.php'));
}

/* ------------------------------- Excluir ------------------------------ */
if (is_post() && ($_POST['acao'] ?? '') === 'excluir') {
    if (!$podeGerenciar || !csrf_check()) {
        flash_set('error', 'Operação não permitida.');
        redirect(app_url('dashboard/aulas.php'));
    }
    $id = (int) ($_POST['aula_id'] ?? 0);
    $del = $pdo->prepare("DELETE FROM aulas WHERE id = :id AND turma_id IN ($placeholder)");
    $del->execute([':id' => $id]);
    flash_set($del->rowCount() ? 'success' : 'error',
        $del->rowCount() ? 'Aula removida.' : 'Aula não encontrada.');
    redirect(app_url('dashboard/aulas.php'));
}

/* -------------------------------- Listagem ---------------------------- */
$filtroTurma = (int) ($_GET['turma'] ?? 0);
$busca       = trim((string) ($_GET['q'] ?? ''));

$sql = "SELECT a.*, t.nome AS turma_nome, t.codigo AS turma_codigo, u.nome AS professor_nome
        FROM aulas a
        INNER JOIN turmas t ON t.id = a.turma_id
        LEFT JOIN usuarios u ON u.id = a.professor_id
        WHERE a.turma_id IN ($placeholder)";
$params = [];

if (in_array($filtroTurma, $turmaIds, true)) {
    $sql .= ' AND a.turma_id = :turma';
    $params[':turma'] = $filtroTurma;
}
if ($busca !== '') {
    $sql .= ' AND (a.titulo LIKE :q OR t.nome LIKE :q OR a.local LIKE :q)';
    $params[':q'] = '%' . $busca . '%';
}
$sql .= ' ORDER BY a.data_aula ASC, a.hora_inicio ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$aulas = $stmt->fetchAll();

$professores = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo = 'professor' AND ativo = 1 ORDER BY nome")->fetchAll();

$pageHeader = [
    'title' => 'Aulas',
    'sub'   => $podeGerenciar
        ? 'Agende as aulas das turmas: data, horário, local e conteúdo.'
        : 'Confira as aulas agendadas das suas turmas.',
    'action' => $podeGerenciar
        ? '<button class="btn btn--primary" type="button" data-scroll-to="form-aula">'
             . '<svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Agendar aula</button>'
        : '',
];

$pageTitle = 'Aulas';
$active = 'aulas';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($podeGerenciar): ?>
<section class="card" id="form-aula">
  <div class="card__head">
    <div>
      <h2 class="card__title">Agendar aula</h2>
      <p class="card__sub">A aula aparece automaticamente na agenda da turma e dos alunos vinculados.</p>
    </div>
  </div>

  <form method="post" action="<?= e(app_url('dashboard/aulas.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="acao" value="criar">

    <div class="form-grid">
      <label class="field">
        <span>Turma *</span>
        <select name="turma_id" required>
          <option value="">— Selecione —</option>
          <?php foreach ($turmas as $t): ?>
            <option value="<?= (int) $t['id'] ?>" <?= $filtroTurma === (int) $t['id'] ? 'selected' : '' ?>>
              <?= e($t['nome'] . ' — ' . $t['disciplina']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label class="field">
        <span>Título da aula *</span>
        <input type="text" name="titulo" placeholder="ex: Funções do 2º grau" required>
      </label>

      <label class="field">
        <span>Data *</span>
        <input type="date" name="data_aula" value="<?= date('Y-m-d') ?>" required>
      </label>

      <label class="field">
        <span>Início *</span>
        <input type="time" name="hora_inicio" value="08:00" required>
      </label>

      <label class="field">
        <span>Término *</span>
        <input type="time" name="hora_fim" value="09:00" required>
      </label>

      <label class="field">
        <span>Local</span>
        <input type="text" name="local" placeholder="ex: Sala 12">
      </label>

      <label class="field">
        <span>Professor responsável</span>
        <select name="professor_id">
          <option value="">— Sem professor definido —</option>
          <?php foreach ($professores as $prof): ?>
            <option value="<?= (int) $prof['id'] ?>" <?= $user['tipo'] === 'professor' && (int) $prof['id'] === (int) $user['id'] ? 'selected' : '' ?>>
              <?= e($prof['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label class="field field--span">
        <span>Descrição</span>
        <textarea name="descricao" placeholder="Conteúdo, material necessário, observações..."></textarea>
      </label>
    </div>

    <div class="form-actions">
      <button class="btn btn--primary" type="submit">
        <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        Agendar aula
      </button>
      <button class="btn btn--ghost" type="reset">Limpar</button>
    </div>
  </form>
</section>
<?php endif; ?>

<section class="card" data-table>
  <div class="card__head">
    <div>
      <h2 class="card__title">Aulas agendadas</h2>
      <p class="card__sub"><span data-table-count><?= count($aulas) ?> registros</span></p>
    </div>
  </div>

  <div class="toolbar">
    <label class="field">
      <span>Buscar</span>
      <input type="search" data-table-search value="<?= e($busca) ?>" placeholder="Título, turma ou local...">
    </label>
    <label class="field">
      <span>Turma</span>
      <select data-table-filter>
        <option value="">Todas</option>
        <?php foreach ($turmas as $t): ?>
          <option value="<?= (int) $t['id'] ?>" <?= $filtroTurma === (int) $t['id'] ? 'selected' : '' ?>>
            <?= e($t['nome']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <div class="toolbar__spacer"></div>
  </div>

  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th>Data</th>
          <th>Aula</th>
          <th>Turma</th>
          <th>Horário</th>
          <th>Local</th>
          <th>Professor</th>
          <?php if ($podeGerenciar): ?><th></th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($aulas as $aula): ?>
          <tr data-row data-group="<?= (int) $aula['turma_id'] ?>">
            <td>
              <strong><?= formatar_data($aula['data_aula']) ?></strong><br>
              <small class="muted"><?= date('D', strtotime($aula['data_aula'])) ?></small>
            </td>
            <td>
              <strong><?= e($aula['titulo']) ?></strong>
              <?php if ($aula['descricao']): ?>
                <br><small class="muted"><?= e(mb_strimwidth($aula['descricao'], 0, 70, '…')) ?></small>
              <?php endif; ?>
            </td>
            <td>
              <?= e($aula['turma_nome']) ?><br>
              <small class="muted"><?= e($aula['turma_codigo']) ?></small>
            </td>
            <td><span class="badge"><?= e(formatar_hora($aula['hora_inicio']) . ' – ' . formatar_hora($aula['hora_fim'])) ?></span></td>
            <td><?= e($aula['local'] ?: '—') ?></td>
            <td><?= e($aula['professor_nome'] ?: '—') ?></td>
            <?php if ($podeGerenciar): ?>
              <td class="actions">
                <form class="inline-form" method="post" action="<?= e(app_url('dashboard/aulas.php')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="acao" value="excluir">
                  <input type="hidden" name="aula_id" value="<?= (int) $aula['id'] ?>">
                  <button class="btn btn--danger btn--sm" type="submit"
                          data-confirm="Remover a aula <?= e($aula['titulo']) ?>?">Excluir</button>
                </form>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tbody>
        <tr data-table-empty<?= $aulas ? ' hidden' : '' ?>>
          <td colspan="7">
            <div class="table-empty">
              <img src="<?= e(app_url('assets/img/mascot-confused.svg')) ?>" alt="">
              <p class="mb-0">Nenhuma aula agendada.</p>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
