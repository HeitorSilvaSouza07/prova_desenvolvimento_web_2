<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = current_user();
$podeGerenciar = pode_gerenciar($user);
$pdo = db();

$turmas = turmas_visiveis($pdo, $user);
$turmaIds = array_map(fn($t) => (int) $t['id'], $turmas);
$placeholder = $turmaIds ? implode(',', $turmaIds) : '0';


if (is_post() && ($_POST['acao'] ?? '') === 'criar') {
    if (!$podeGerenciar) {
        flash_set('error', 'Somente professores podem agendar provas.');
        redirect(app_url('dashboard/provas.php'));
    }
    if (!csrf_check()) {
        flash_set('error', 'Sessão expirada. Recarregue a página e tente novamente.');
        redirect(app_url('dashboard/provas.php'));
    }

    $turmaId     = (int) ($_POST['turma_id'] ?? 0);
    $titulo      = trim((string) ($_POST['titulo'] ?? ''));
    $dataProva   = (string) ($_POST['data_prova'] ?? '');
    $hora        = (string) ($_POST['hora'] ?? '');
    $local       = trim((string) ($_POST['local'] ?? ''));
    $peso        = str_replace(',', '.', trim((string) ($_POST['peso'] ?? '1')));
    $descricao   = trim((string) ($_POST['descricao'] ?? ''));
    $professorId = (int) ($_POST['professor_id'] ?? 0);

    $erros = [];
    if ($titulo === '')                      { $erros[] = 'Informe o título da prova.'; }
    if (!$dataProva || !strtotime($dataProva)) { $erros[] = 'Informe uma data válida.'; }
    if (!is_numeric($peso) || $peso <= 0)    { $erros[] = 'O peso da prova deve ser maior que zero.'; }
    if (!in_array($turmaId, $turmaIds, true)) { $erros[] = 'Selecione uma turma válida.'; }

    if (!$erros) {
        $ins = $pdo->prepare(
            'INSERT INTO provas (turma_id, professor_id, titulo, data_prova, hora, local, peso, descricao, criado_por)
             VALUES (:turma, :professor, :titulo, :data, :hora, :local, :peso, :descricao, :criador)'
        );
        $ins->execute([
            ':turma'     => $turmaId,
            ':professor' => $professorId > 0 ? $professorId : null,
            ':titulo'    => $titulo,
            ':data'      => $dataProva,
            ':hora'      => $hora !== '' ? $hora : null,
            ':local'     => $local !== '' ? $local : null,
            ':peso'      => (float) $peso,
            ':descricao' => $descricao !== '' ? $descricao : null,
            ':criador'   => $user['id'],
        ]);
        flash_set('success', 'Prova agendada para ' . formatar_data($dataProva) . '.');
    } else {
        flash_set('error', implode(' ', $erros));
    }
    redirect(app_url('dashboard/provas.php'));
}

/* ------------------------------- Excluir ------------------------------ */
if (is_post() && ($_POST['acao'] ?? '') === 'excluir') {
    if (!$podeGerenciar || !csrf_check()) {
        flash_set('error', 'Operação não permitida.');
        redirect(app_url('dashboard/provas.php'));
    }
    $id = (int) ($_POST['prova_id'] ?? 0);
    $del = $pdo->prepare("DELETE FROM provas WHERE id = :id AND turma_id IN ($placeholder)");
    $del->execute([':id' => $id]);
    flash_set($del->rowCount() ? 'success' : 'error',
        $del->rowCount() ? 'Prova removida.' : 'Prova não encontrada.');
    redirect(app_url('dashboard/provas.php'));
}

/* -------------------------------- Listagem ---------------------------- */
$filtroTurma = (int) ($_GET['turma'] ?? 0);
$busca       = trim((string) ($_GET['q'] ?? ''));

$sql = "SELECT p.*, t.nome AS turma_nome, t.codigo AS turma_codigo, u.nome AS professor_nome
        FROM provas p
        INNER JOIN turmas t ON t.id = p.turma_id
        LEFT JOIN usuarios u ON u.id = p.professor_id
        WHERE p.turma_id IN ($placeholder)";
$params = [];

if (in_array($filtroTurma, $turmaIds, true)) {
    $sql .= ' AND p.turma_id = :turma';
    $params[':turma'] = $filtroTurma;
}
if ($busca !== '') {
    $sql .= ' AND (p.titulo LIKE :q OR t.nome LIKE :q OR p.local LIKE :q)';
    $params[':q'] = '%' . $busca . '%';
}
$sql .= ' ORDER BY p.data_prova ASC, p.hora ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$provas = $stmt->fetchAll();

$professores = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo = 'professor' AND ativo = 1 ORDER BY nome")->fetchAll();

$pageHeader = [
    'title' => 'Provas',
    'sub'   => $podeGerenciar
        ? 'Agende as provas, defina peso, local e horário para cada turma.'
        : 'Confira as provas agendadas das suas turmas.',
    'action' => $podeGerenciar
        ? '<button class="btn btn--primary" type="button" data-scroll-to="form-prova">'
             . '<svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Agendar prova</button>'
        : '',
];

$pageTitle = 'Provas';
$active = 'provas';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($podeGerenciar): ?>
<section class="card" id="form-prova">
  <div class="card__head">
    <div>
      <h2 class="card__title">Agendar prova</h2>
      <p class="card__sub">O peso é usado no cálculo da média da turma.</p>
    </div>
  </div>

  <form method="post" action="<?= e(app_url('dashboard/provas.php')) ?>">
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
        <span>Título da prova *</span>
        <input type="text" name="titulo" placeholder="ex: Prova bimestral de matemática" required>
      </label>

      <label class="field">
        <span>Data *</span>
        <input type="date" name="data_prova" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
      </label>

      <label class="field">
        <span>Horário</span>
        <input type="time" name="hora" value="08:00">
      </label>

      <label class="field">
        <span>Local</span>
        <input type="text" name="local" placeholder="ex: Sala 12">
      </label>

      <label class="field">
        <span>Peso</span>
        <input type="number" name="peso" step="0.25" min="0.25" value="1">
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
        <span>Conteúdo / observações</span>
        <textarea name="descricao" placeholder="Capítulos, fórmulas permitidas, materiais..."></textarea>
      </label>
    </div>

    <div class="form-actions">
      <button class="btn btn--primary" type="submit">
        <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        Agendar prova
      </button>
      <button class="btn btn--ghost" type="reset">Limpar</button>
    </div>
  </form>
</section>
<?php endif; ?>

<section class="card" data-table>
  <div class="card__head">
    <div>
      <h2 class="card__title">Provas agendadas</h2>
      <p class="card__sub"><span data-table-count><?= count($provas) ?> registros</span></p>
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
          <th>Prova</th>
          <th>Turma</th>
          <th>Horário</th>
          <th>Local</th>
          <th>Peso</th>
          <th>Professor</th>
          <?php if ($podeGerenciar): ?><th></th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($provas as $prova): ?>
          <tr data-row data-group="<?= (int) $prova['turma_id'] ?>">
            <td>
              <strong><?= formatar_data($prova['data_prova']) ?></strong><br>
              <small class="muted"><?= date('D', strtotime($prova['data_prova'])) ?></small>
            </td>
            <td>
              <strong><?= e($prova['titulo']) ?></strong>
              <?php if ($prova['descricao']): ?>
                <br><small class="muted"><?= e(mb_strimwidth($prova['descricao'], 0, 70, '…')) ?></small>
              <?php endif; ?>
            </td>
            <td>
              <?= e($prova['turma_nome']) ?><br>
              <small class="muted"><?= e($prova['turma_codigo']) ?></small>
            </td>
            <td><span class="badge"><?= e(formatar_hora($prova['hora']) ?: '—') ?></span></td>
            <td><?= e($prova['local'] ?: '—') ?></td>
            <td><span class="badge badge--amber"><?= rtrim(rtrim(number_format((float) $prova['peso'], 2, ',', '.'), '0'), ',') ?></span></td>
            <td><?= e($prova['professor_nome'] ?: '—') ?></td>
            <?php if ($podeGerenciar): ?>
              <td class="actions">
                <form class="inline-form" method="post" action="<?= e(app_url('dashboard/provas.php')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="acao" value="excluir">
                  <input type="hidden" name="prova_id" value="<?= (int) $prova['id'] ?>">
                  <button class="btn btn--danger btn--sm" type="submit"
                          data-confirm="Remover a prova <?= e($prova['titulo']) ?>?">Excluir</button>
                </form>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tbody>
        <tr data-table-empty<?= $provas ? ' hidden' : '' ?>>
          <td colspan="8">
            <div class="table-empty">
              <img src="<?= e(app_url('assets/img/mascot-confused.svg')) ?>" alt="">
              <p class="mb-0">Nenhuma prova agendada.</p>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
