<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = current_user();
$podeGerenciar = pode_gerenciar($user);
$pdo = db();

$turmas = turmas_visiveis($pdo, $user);
$turmaIds = array_map(fn($t) => (int) $t['id'], $turmas);
$placeholder = $turmaIds ? implode(',', $turmaIds) : '0';

if ($podeGerenciar) {
    $totalTurmas      = (int) $pdo->query('SELECT COUNT(*) FROM turmas')->fetchColumn();
    $totalAlunos      = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'aluno' AND ativo = 1")->fetchColumn();
    $totalProfessores = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'professor' AND ativo = 1")->fetchColumn();
} else {
    $totalTurmas      = count($turmas);
    $totalAlunos      = (int) $pdo->query("SELECT COUNT(DISTINCT aluno_id) FROM turma_alunos WHERE turma_id IN ($placeholder)")->fetchColumn();
    $totalProfessores = (int) $pdo->query("SELECT COUNT(DISTINCT professor_id) FROM turma_professores WHERE turma_id IN ($placeholder)")->fetchColumn();
}

$hoje = date('Y-m-d');

$totalAulas = (int) $pdo->query("SELECT COUNT(*) FROM aulas WHERE data_aula >= '$hoje' AND turma_id IN ($placeholder)")->fetchColumn();
$proximasProvas = (int) $pdo->query("SELECT COUNT(*) FROM provas WHERE data_prova >= '$hoje' AND turma_id IN ($placeholder)")->fetchColumn();
$atividadesAbertas = (int) $pdo->query("SELECT COUNT(*) FROM atividades WHERE data_entrega >= '$hoje' AND turma_id IN ($placeholder)")->fetchColumn();


$agenda = [];

$stmt = $pdo->query(
    "SELECT a.data_aula AS data, a.hora_inicio AS hora, a.titulo, a.local, t.nome AS turma, 'aula' AS tipo
     FROM aulas a INNER JOIN turmas t ON t.id = a.turma_id
     WHERE a.data_aula >= '$hoje' AND a.turma_id IN ($placeholder)
     ORDER BY a.data_aula ASC, a.hora_inicio ASC LIMIT 8"
);
foreach ($stmt as $row) { $agenda[] = $row; }

$stmt = $pdo->query(
    "SELECT p.data_prova AS data, p.hora, p.titulo, p.local, t.nome AS turma, 'prova' AS tipo
     FROM provas p INNER JOIN turmas t ON t.id = p.turma_id
     WHERE p.data_prova >= '$hoje' AND p.turma_id IN ($placeholder)
     ORDER BY p.data_prova ASC, p.hora ASC LIMIT 8"
);
foreach ($stmt as $row) { $agenda[] = $row; }

$stmt = $pdo->query(
    "SELECT at.data_entrega AS data, at.hora_entrega AS hora, at.titulo, NULL AS local, t.nome AS turma, 'atividade' AS tipo
     FROM atividades at INNER JOIN turmas t ON t.id = at.turma_id
     WHERE at.data_entrega >= '$hoje' AND at.turma_id IN ($placeholder)
     ORDER BY at.data_entrega ASC, at.hora_entrega ASC LIMIT 8"
);
foreach ($stmt as $row) { $agenda[] = $row; }

usort($agenda, fn($a, $b) => [$a['data'], $a['hora'] ?? ''] <=> [$b['data'], $b['hora'] ?? '']);
$agenda = array_slice($agenda, 0, 8);

$meses = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

$pageHeader = [
    'title' => 'Olá, ' . explode(' ', trim($user['nome']))[0],
    'sub'   => $podeGerenciar
        ? 'Aqui está o resumo das turmas e da agenda acadêmica hoje, ' . date('d/m/Y') . '.'
        : 'Acompanhe as aulas, provas e atividades das suas turmas.',
];

$pageTitle = 'Painel';
$active = 'painel';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($podeGerenciar): ?>
  <div class="stat-grid">
    <div class="stat stat--dark">
      <span class="stat__label">Turmas</span>
      <span class="stat__value"><?= $totalTurmas ?></span>
      <span class="stat__hint">cadastradas no sistema</span>
    </div>
    <div class="stat">
      <span class="stat__label">Alunos</span>
      <span class="stat__value"><?= $totalAlunos ?></span>
      <span class="stat__hint">matriculados</span>
    </div>
    <div class="stat">
      <span class="stat__label">Professores</span>
      <span class="stat__value"><?= $totalProfessores ?></span>
      <span class="stat__hint">ativos na plataforma</span>
    </div>
    <div class="stat">
      <span class="stat__label">Aulas futuras</span>
      <span class="stat__value"><?= $totalAulas ?></span>
      <span class="stat__hint">já agendadas</span>
    </div>
  </div>
<?php else: ?>
  <div class="stat-grid">
    <div class="stat stat--dark">
      <span class="stat__label">Minhas turmas</span>
      <span class="stat__value"><?= $totalTurmas ?></span>
      <span class="stat__hint">vinculadas ao seu perfil</span>
    </div>
    <div class="stat">
      <span class="stat__label">Aulas</span>
      <span class="stat__value"><?= $totalAulas ?></span>
      <span class="stat__hint">agendadas</span>
    </div>
    <div class="stat">
      <span class="stat__label">Provas</span>
      <span class="stat__value"><?= $proximasProvas ?></span>
      <span class="stat__hint">a acontecer</span>
    </div>
    <div class="stat">
      <span class="stat__label">Atividades</span>
      <span class="stat__value"><?= $atividadesAbertas ?></span>
      <span class="stat__hint">com entrega aberta</span>
    </div>
  </div>
<?php endif; ?>

<div class="grid-2">
  <section class="card">
    <div class="card__head">
      <div>
        <h2 class="card__title">Próximos compromissos</h2>
        <p class="card__sub">Aulas, provas e atividades ordenadas por data.</p>
      </div>
    </div>

    <?php if (!$agenda): ?>
      <div class="table-empty">
        <img src="<?= e(app_url('assets/img/mascot-confused.svg')) ?>" alt="">
        <p class="mb-0">Nada agendado para os próximos dias.</p>
      </div>
    <?php else: ?>
      <ul class="timeline">
        <?php foreach ($agenda as $item):
            $ts = strtotime($item['data']);
            $rotulo = $item['tipo'] === 'aula' ? 'Aula' : ($item['tipo'] === 'prova' ? 'Prova' : 'Entrega');
            $css = $item['tipo'] === 'aula' ? 'aula' : ($item['tipo'] === 'prova' ? 'prova' : 'atividade');
            $badge = $item['tipo'] === 'prova' ? 'badge--amber' : ($item['tipo'] === 'atividade' ? 'badge--green' : '');
        ?>
          <li class="timeline__item timeline--<?= e($css) ?>">
            <span class="timeline__date">
              <strong><?= date('d', $ts) ?></strong>
              <small><?= $meses[(int) date('n', $ts) - 1] ?></small>
            </span>
            <div class="timeline__body">
              <h4><?= e($item['titulo']) ?></h4>
              <p>
                <?= e($item['turma']) ?>
                <?php if ($item['hora']): ?> · <?= e(formatar_hora($item['hora'])) ?><?php endif; ?>
                <?php if (!empty($item['local'])): ?> · <?= e($item['local']) ?><?php endif; ?>
              </p>
              <div class="timeline__tags">
                <span class="badge <?= $badge ?>"><?= $rotulo ?></span>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <section class="card">
    <div class="card__head">
      <div>
        <h2 class="card__title"><?= $podeGerenciar ? 'Minhas turmas' : 'Turmas vinculadas' ?></h2>
        <p class="card__sub"><?= count($turmas) ?> turma(s) visível(is) para o seu perfil.</p>
      </div>
      <a class="btn btn--ghost btn--sm" href="<?= e(app_url('dashboard/turmas.php')) ?>">Ver todas</a>
    </div>

    <?php if (!$turmas): ?>
      <div class="table-empty">
        <img src="<?= e(app_url('assets/img/mascot-confused.svg')) ?>" alt="">
        <p class="mb-0"><?= $podeGerenciar ? 'Crie sua primeira turma para começar.' : 'Você ainda não está vinculado a nenhuma turma.' ?></p>
        <?php if ($podeGerenciar): ?>
          <p><a class="btn btn--primary btn--sm" href="<?= e(app_url('dashboard/turmas.php')) ?>">Criar turma</a></p>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="data">
          <thead>
            <tr>
              <th>Turma</th>
              <th>Disciplina</th>
              <th>Semestre</th>
              <th>Alunos</th>
              <th>Prof.</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($turmas as $turma): ?>
              <tr>
                <td><strong><?= e($turma['nome']) ?></strong><br><small class="muted"><?= e($turma['codigo']) ?></small></td>
                <td><?= e($turma['disciplina']) ?></td>
                <td><span class="badge badge--muted"><?= e($turma['semestre']) ?></span></td>
                <td><?= (int) $turma['total_alunos'] ?></td>
                <td><?= (int) $turma['total_professores'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
