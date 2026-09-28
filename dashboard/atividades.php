<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = current_user();
$podeGerenciar = pode_gerenciar($user);
$pdo = db();

$turmas = turmas_visiveis($pdo, $user);
$turmaIds = array_map(fn($t) => (int) $t['id'], $turmas);
$placeholder = $turmaIds ? implode(',', $turmaIds) : '0';

$tipos = ['tarefa' => 'Tarefa', 'trabalho' => 'Trabalho', 'exercicio' => 'Exercício', 'projeto' => 'Projeto', 'outro' => 'Outro'];

/* ------------------------------ Agendar ------------------------------- */
if (is_post() && ($_POST['acao'] ?? '') === 'criar') {
    if (!$podeGerenciar) {
        flash_set('error', 'Somente professores podem cadastrar atividades.');
        redirect(app_url('dashboard/atividades.php'));
    }
    if (!csrf_check()) {
        flash_set('error', 'Sessão expirada. Recarregue a página e tente novamente.');
        redirect(app_url('dashboard/atividades.php'));
    }

    $turmaId     = (int) ($_POST['turma_id'] ?? 0);
    $titulo      = trim((string) ($_POST['titulo'] ?? ''));
    $tipo        = array_key_exists($_POST['tipo'] ?? '', $tipos) ? $_POST['tipo'] : 'tarefa';
    $dataEntrega = (string) ($_POST['data_entrega'] ?? '');
    $hora        = (string) ($_POST['hora_entrega'] ?? '');
    $valor       = str_replace(',', '.', trim((string) ($_POST['valor'] ?? '10')));
    $descricao   = trim((string) ($_POST['descricao'] ?? ''));
    $professorId = (int) ($_POST['professor_id'] ?? 0);

    $erros = [];
    if ($titulo === '')                          { $erros[] = 'Informe o título da atividade.'; }
    if (!$dataEntrega || !strtotime($dataEntrega)) { $erros[] = 'Informe uma data de entrega válida.'; }
    if (!is_numeric($valor))                     { $erros[] = 'Informe um valor numérico para a atividade.'; }
    if (!in_array($turmaId, $turmaIds, true))    { $erros[] = 'Selecione uma turma válida.'; }

    if (!$erros) {
        $ins = $pdo->prepare(
            'INSERT INTO atividades (turma_id, professor_id, titulo, tipo, data_entrega, hora_entrega, valor, descricao, criado_por)
             VALUES (:turma, :professor, :titulo, :tipo, :data, :hora, :valor, :descricao, :criador)'
        );
        $ins->execute([
            ':turma'     => $turmaId,
            ':professor' => $professorId > 0 ? $professorId : null,
            ':titulo'    => $titulo,
            ':tipo'      => $tipo,
            ':data'      => $dataEntrega,
            ':hora'      => $hora !== '' ? $hora : null,
            ':valor'     => (float) $valor,
            ':descricao' => $descricao !== '' ? $descricao : null,
            ':criador'   => $user['id'],
        ]);
        flash_set('success', 'Atividade cadastrada com entrega em ' . formatar_data($dataEntrega) . '.');
    } else {
        flash_set('error', implode(' ', $erros));
    }
    redirect(app_url('dashboard/atividades.php'));
}

/* ------------------------------- Excluir ------------------------------ */
if (is_post() && ($_POST['acao'] ?? '') === 'excluir') {
    if (!$podeGerenciar || !csrf_check()) {
        flash_set('error', 'Operação não permitida.');
        redirect(app_url('dashboard/atividades.php'));
    }
    $id = (int) ($_POST['atividade_id'] ?? 0);
    $del = $pdo->prepare("DELETE FROM atividades WHERE id = :id AND turma_id IN ($placeholder)");
    $del->execute([':id' => $id]);
    flash_set($del->rowCount() ? 'success' : 'error',
        $del->rowCount() ? 'Atividade removida.' : 'Atividade não encontrada.');
    redirect(app_url('dashboard/atividades.php'));
}

/* -------------------------------- Listagem ---------------------------- */
$filtroTurma = (int) ($_GET['turma'] ?? 0);
$busca       = trim((string) ($_GET['q'] ?? ''));

$sql = "SELECT a.*, t.nome AS turma_nome, t.codigo AS turma_codigo, u.nome AS professor_nome
        FROM atividades a
        INNER JOIN turmas t ON t.id = a.turma_id
        LEFT JOIN usuarios u ON u.id = a.professor_id
        WHERE a.turma_id IN ($placeholder)";
$params = [];

if (in_array($filtroTurma, $turmaIds, true)) {
    $sql .= ' AND a.turma_id = :turma';
    $params[':turma'] = $filtroTurma;
}
if ($busca !== '') {
    $sql .= ' AND (a.titulo LIKE :q OR t.nome LIKE :q)';
    $params[':q'] = '%' . $busca . '%';
}
$sql .= ' ORDER BY a.data_entrega ASC, a.hora_entrega ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$atividades = $stmt->fetchAll();

$professores = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo = 'professor' AND ativo = 1 ORDER BY nome")->fetchAll();

$pageHeader = [
    'title' => 'Atividades',
    'sub'   => $podeGerenciar
        ? 'Cadastre tarefas, trabalhos e projetos com data de entrega e valor.'
        : 'Confira as atividades e prazos das suas turmas.',
    'action' => $podeGerenciar
        ? '<button class="btn btn--primary" type="button" data-scroll-to="form-atividade">'
             . '<svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Nova atividade</button>'
        : '',
];

$pageTitle = 'Atividades';
$active = 'atividades';
require __DIR__ . '/../includes/header.php';

$hoje = date('Y-m-d');
?>

<?php if ($podeGerenciar): ?>
<section class="card" id="form-atividade">
  <div class="card__head">
    <div>
      <h2 class="card__title">Nova atividade</h2>
      <p class="card__sub">Defina o prazo de entrega; os alunos recebem a atividade na agenda.</p>
    </div>
  </div>

  <form method="post" action="<?= e(app_url('dashboard/atividades.php')) ?>">
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
        <span>Título *</span>
        <input type="text" name="titulo" placeholder="ex: Lista de exercícios 03" required>
      </label>

      <label class="field">
        <span>Tipo</span>
        <select name="tipo">
          <?php foreach ($tipos as $chave => $rotulo): ?>
            <option value="<?= e($chave) ?>"><?= e($rotulo) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label class="field">
        <span>Data de entrega *</span>
        <input type="date" name="data_entrega" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
      </label>

      <label class="field">
        <span>Horário limite</span>
        <input type="time" name="hora_entrega" value="23:59">
      </label>

      <label class="field">
        <span>Valor (nota)</span>
        <input type="number" name="valor" step="0.5" min="0" value="10">
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
        <textarea name="descricao" placeholder="Enunciado, formato de entrega, critérios de avaliação..."></textarea>
      </label>
    </div>

    <div class="form-actions">
      <button class="btn btn--primary" type="submit">
        <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        Cadastrar atividade
      </button>
      <button class="btn btn--ghost" type="reset">Limpar</button>
    </div>
  </form>
</section>
<?php endif; ?>

<section class="card" data-table>
  <div class="card__head">
    <div>
      <h2 class="card__title">Atividades cadastradas</h2>
      <p class="card__sub"><span data-table-count><?= count($atividades) ?> registros</span></p>
    </div>
  </div>

  <div class="toolbar">
    <label class="field">
      <span>Buscar</span>
      <input type="search" data-table-search value="<?= e($busca) ?>" placeholder="Título ou turma...">
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
          <th>Entrega</th>
          <th>Atividade</th>
          <th>Turma</th>
          <th>Tipo</th>
          <th>Valor</th>
          <th>Professor</th>
          <th>Situação</th>
          <?php if ($podeGerenciar): ?><th></th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($atividades as $ativ):
            $encerrada = $ativ['data_entrega'] < $hoje;
        ?>
          <tr data-row data-group="<?= (int) $ativ['turma_id'] ?>">
            <td>
              <strong><?= formatar_data($ativ['data_entrega']) ?></strong><br>
              <small class="muted"><?= e(formatar_hora($ativ['hora_entrega']) ?: 'sem horário') ?></small>
            </td>
            <td>
              <strong><?= e($ativ['titulo']) ?></strong>
              <?php if ($ativ['descricao']): ?>
                <br><small class="muted"><?= e(mb_strimwidth($ativ['descricao'], 0, 70, '…')) ?></small>
              <?php endif; ?>
            </td>
            <td>
              <?= e($ativ['turma_nome']) ?><br>
              <small class="muted"><?= e($ativ['turma_codigo']) ?></small>
            </td>
            <td><span class="badge badge--muted"><?= e($tipos[$ativ['tipo']] ?? $ativ['tipo']) ?></span></td>
            <td><?= rtrim(rtrim(number_format((float) $ativ['valor'], 2, ',', '.'), '0'), ',') ?></td>
            <td><?= e($ativ['professor_nome'] ?: '—') ?></td>
            <td>
              <?php if ($encerrada): ?>
                <span class="badge badge--red"><span class="badge-dot"></span>Encerrada</span>
              <?php else: ?>
                <span class="badge badge--green"><span class="badge-dot"></span>Aberta</span>
              <?php endif; ?>
            </td>
            <?php if ($podeGerenciar): ?>
              <td class="actions">
                <form class="inline-form" method="post" action="<?= e(app_url('dashboard/atividades.php')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="acao" value="excluir">
                  <input type="hidden" name="atividade_id" value="<?= (int) $ativ['id'] ?>">
                  <button class="btn btn--danger btn--sm" type="submit"
                          data-confirm="Remover a atividade <?= e($ativ['titulo']) ?>?">Excluir</button>
                </form>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tbody>
        <tr data-table-empty<?= $atividades ? ' hidden' : '' ?>>
          <td colspan="8">
            <div class="table-empty">
              <img src="<?= e(app_url('assets/img/mascot-confused.svg')) ?>" alt="">
              <p class="mb-0">Nenhuma atividade cadastrada.</p>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
