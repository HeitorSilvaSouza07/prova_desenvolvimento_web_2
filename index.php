<?php
require_once __DIR__ . '/includes/auth.php';

$testimonials = [
    ['name' => 'Heitor Silva',     'role' => 'Diretor escolar',            'quote' => 'Reduziu o retrabalho da secretaria em poucas semanas de uso.',        'initials' => 'HS'],
    ['name' => 'Matheus Terra',    'role' => 'Coordenador pedagógico',      'quote' => 'A comunicação com as famílias ficou muito mais simples.',             'initials' => 'MT'],
    ['name' => 'Arthur Souza',     'role' => 'Professor de matemática',     'quote' => 'Consigo acompanhar cada turma sem me perder em planilhas.',           'initials' => 'AS'],
    ['name' => 'Leandro Abreu',    'role' => 'Professor de computação',     'quote' => 'Lançar as atividades nunca esteve tão fácil.',                       'initials' => 'LA'],
];

$features = [
    ['icon' => 'board',    'title' => 'Mural de recados',         'description' => 'Compartilhe avisos importantes com toda a turma em segundos.'],
    ['icon' => 'calendar', 'title' => 'Calendário acadêmico',     'description' => 'Organize provas, entregas e eventos em um calendário único.'],
    ['icon' => 'bell',     'title' => 'Notificações em tempo real', 'description' => 'Alunos e responsáveis são avisados assim que você publica algo novo.'],
    ['icon' => 'report',   'title' => 'Boletim digital',          'description' => 'Acompanhe notas e frequência sem depender de planilhas soltas.'],
    ['icon' => 'chat',     'title' => 'Mensagens diretas',        'description' => 'Fale com professores ou com a turma inteira sem sair da plataforma.'],
    ['icon' => 'chart',    'title' => 'Relatórios de desempenho', 'description' => 'Veja o progresso da turma em gráficos simples de entender.'],
];

$steps = [
    ['title' => 'Crie a turma',           'description' => 'Cadastre alunos e professores em poucos minutos, sem planilhas.'],
    ['title' => 'Compartilhe o dia a dia', 'description' => 'Publique aulas, provas e atividades em um só lugar.'],
    ['title' => 'Acompanhe tudo',          'description' => 'Veja a agenda da turma inteira e o que ainda precisa de atenção.'],
];

$faqItems = [
    ['id' => 'celular',     'question' => 'O ProSiga funciona no celular?',
     'answer'  => 'Sim. A plataforma se adapta a qualquer tela, e alunos, professores e responsáveis recebem notificações direto no celular.'],
    ['id' => 'instalacao',  'question' => 'Preciso instalar algum programa?',
     'answer'  => 'Não. O ProSiga funciona pelo navegador, sem instalação e sem ocupar espaço no aparelho.'],
    ['id' => 'tamanho',     'question' => 'Dá para usar em qualquer escola?',
     'answer'  => 'Sim. Funciona para escolas de qualquer porte, de uma turma única a redes com várias unidades.'],
    ['id' => 'comecar',     'question' => 'Como faço para começar?',
     'answer'  => 'Clique em <a class="pro-siga__inline-link" href="' . e(app_url('criar-conta.php')) . '">Criar conta</a> e configure sua primeira turma em poucos minutos.'],
];

$iconPaths = [
    'board'    => 'M5 5h22a2 2 0 0 1 2 2v18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z M9 13h14 M9 18h14 M9 23h9',
    'calendar' => 'M5 8a2 2 0 0 1 2-2h18a2 2 0 0 1 2 2v18a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8Z M5 13h22 M11 3v6 M21 3v6',
    'bell'     => 'M16 5c-5 0-8 4-8 9v4l-3 4h22l-3-4v-4c0-5-3-9-8-9Z M13 25a3 3 0 0 0 6 0',
    'report'   => 'M8 4h13l6 6v18a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z M21 4v6h6 M11 17h10 M11 22h10 M11 12h5',
    'chat'     => 'M5 6h22a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H14l-6 6v-6H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z M9 13h14 M9 17h9',
    'chart'    => 'M5 27V6 M5 27h22 M10 27V17 M17 27V11 M24 27V15',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ProSiga — Sistema Acadêmico Inteligente</title>
<meta name="description" content="O ProSiga organiza turmas, aulas, provas, atividades e a comunicação acadêmica em um só lugar.">
<link rel="icon" href="<?= e(app_url('assets/img/logo-mark.png')) ?>">
<link rel="stylesheet" href="<?= e(app_url('assets/css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(app_url('assets/css/landing.css')) ?>">
</head>
<body>
<div class="pro-siga">
  <header class="pro-siga__header">
    <a class="pro-siga__brand" href="<?= e(app_url('index.php')) ?>">
      <img src="<?= e(app_url('assets/img/logo-mark.png')) ?>" alt="ProSiga" class="pro-siga__logo-mark">
      <span class="pro-siga__wordmark">ProSiga</span>
    </a>
    <nav class="pro-siga__nav" aria-label="Navegação principal">
      <div class="pro-siga__nav-links">
        <a class="pro-siga__nav-link" href="#recursos">Recursos</a>
        <a class="pro-siga__nav-link" href="#como-funciona">Como funciona</a>
        <a class="pro-siga__nav-link" href="#depoimentos">Depoimentos</a>
        <a class="pro-siga__nav-link" href="#faq">Dúvidas</a>
      </div>
      <div class="pro-siga__nav-actions">
        <a class="pro-siga__btn pro-siga__btn--ghost" href="<?= e(app_url('entrar.php')) ?>">Entrar</a>
        <a class="pro-siga__btn pro-siga__btn--primary" href="<?= e(app_url('criar-conta.php')) ?>">Criar conta</a>
      </div>
    </nav>
  </header>

  <main>
    <section class="pro-siga__hero">
      <div class="pro-siga__hero-art">
        <img class="pro-siga__mascot" src="<?= e(app_url('assets/img/mascot-confused.svg')) ?>"
             alt="Mascote do ProSiga cercado por interrogações, representando dúvidas do dia a dia acadêmico">
      </div>
      <div class="pro-siga__hero-copy">
        <h1 class="pro-siga__eyebrow-free-heading">Feito para <em>o aluno</em> e para <em>o professor</em></h1>
        <p>O ProSiga organiza turmas, aulas, provas, atividades e recados da rotina acadêmica em um
           único lugar, com acesso simples para quem ensina e para quem aprende.</p>
        <a class="pro-siga__btn pro-siga__btn--primary" href="<?= e(app_url('criar-conta.php')) ?>">Experimente já</a>
      </div>
    </section>

    <section class="pro-siga__trust" id="depoimentos" aria-labelledby="pro-siga-trust-heading">
      <div class="pro-siga__trust-intro">
        <h2 id="pro-siga-trust-heading" class="pro-siga__trust-heading">Aprovado por quem ensina e por quem aprende</h2>
        <p class="pro-siga__trust-sub">Escolas de todos os tamanhos usam o ProSiga para simplificar a rotina de professores,
          alunos e famílias.</p>
      </div>
      <ul class="pro-siga__testimonials">
        <?php $last = count($testimonials) - 1; foreach ($testimonials as $i => $person): ?>
          <li class="pro-siga__testimonial<?= $i === $last ? ' pro-siga__testimonial--last' : '' ?>">
            <div class="pro-siga__testimonial-person">
              <span class="pro-siga__avatar" aria-hidden="true"><?= e($person['initials']) ?></span>
              <div>
                <p class="pro-siga__testimonial-name"><?= e($person['name']) ?></p>
                <p class="pro-siga__testimonial-role"><?= e($person['role']) ?></p>
              </div>
            </div>
            <p class="pro-siga__testimonial-quote">&ldquo;<?= e($person['quote']) ?>&rdquo;</p>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>

    <section class="pro-siga__section" id="recursos" aria-labelledby="pro-siga-features-title">
      <h2 id="pro-siga-features-title" class="pro-siga__section-heading">Tudo o que a rotina escolar precisa, em um só lugar</h2>
      <p class="pro-siga__section-sub">Recursos pensados para simplificar a comunicação entre escola, professores, alunos e famílias.</p>
      <div class="pro-siga__feature-grid">
        <?php foreach ($features as $feature): ?>
          <div class="pro-siga__feature">
            <svg class="pro-siga__feature-icon" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="<?= e($iconPaths[$feature['icon']]) ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h3><?= e($feature['title']) ?></h3>
            <p><?= e($feature['description']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="pro-siga__steps" id="como-funciona" aria-labelledby="pro-siga-steps-title">
      <h2 id="pro-siga-steps-title" class="pro-siga__section-heading">Como funciona</h2>
      <p class="pro-siga__section-sub">Três passos para colocar a sua turma no ProSiga.</p>
      <ol class="pro-siga__steps-list">
        <?php foreach ($steps as $index => $step): ?>
          <li class="pro-siga__step">
            <span class="pro-siga__step-index" aria-hidden="true"><?= $index + 1 ?></span>
            <h3><?= e($step['title']) ?></h3>
            <p><?= e($step['description']) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
    </section>

    <section class="pro-siga__features" aria-labelledby="pro-siga-features-heading">
      <h2 id="pro-siga-features-heading" class="pro-siga__features-heading">Pensado em <em>agilidade e facilidade</em> no caminho da informação</h2>
      <p class="pro-siga__features-sub">Centralize aulas, provas, atividades e recados em um só lugar, com acesso simples
        para professores e alunos.</p>

      <div class="pro-siga__cards">
        <article class="pro-siga__card">
          <img class="pro-siga__mascot" src="<?= e(app_url('assets/img/mascot-teacher.svg')) ?>"
               alt="Mascote do ProSiga na versão do professor, segurando anotações">
          <h3>O professor</h3>
          <p>Organiza conteúdos, agenda aulas e provas, acompanha a turma e simplifica a rotina acadêmica.</p>
        </article>
        <article class="pro-siga__card">
          <img class="pro-siga__mascot" src="<?= e(app_url('assets/img/mascot-happy.svg')) ?>"
               alt="Mascote do ProSiga na versão do aluno, com uma notificação no celular">
          <h3>O aluno</h3>
          <p>Recebe avisos, acompanha a agenda das disciplinas e se mantém conectado.</p>
        </article>
      </div>

      <div class="pro-siga__features-cta">
        <a class="pro-siga__btn pro-siga__btn--primary" href="<?= e(app_url('criar-conta.php')) ?>">Quero conhecer a plataforma</a>
      </div>
    </section>

    <section class="pro-siga__faq" id="faq" aria-labelledby="pro-siga-faq-title">
      <div class="pro-siga__faq-grid">
        <div class="pro-siga__faq-intro">
          <h2 id="pro-siga-faq-title" class="pro-siga__section-heading">Perguntas frequentes</h2>
          <p class="pro-siga__section-sub">
            Não encontrou o que procurava?
            <a class="pro-siga__inline-link" href="mailto:contato@prosiga.com.br">Fale com a gente</a>.
          </p>
        </div>
        <div class="pro-siga__faq-list">
          <?php foreach ($faqItems as $index => $item): ?>
            <div class="pro-siga__faq-item">
              <button type="button" class="pro-siga__faq-question" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
                      aria-controls="faq-panel-<?= e($item['id']) ?>" data-faq>
                <?= e($item['question']) ?>
                <span class="pro-siga__faq-icon" aria-hidden="true"><?= $index === 0 ? '&minus;' : '+' ?></span>
              </button>
              <p class="pro-siga__faq-answer" id="faq-panel-<?= e($item['id']) ?>"<?= $index === 0 ? '' : ' hidden' ?>>
                <?= $item['answer'] ?>
              </p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="pro-siga__final-cta">
      <div class="pro-siga__final-cta-copy">
        <h2>Pronto para simplificar a rotina da sua escola?</h2>
        <p>Crie sua conta gratuita e configure a primeira turma em poucos minutos.</p>
        <a class="pro-siga__btn pro-siga__btn--primary" href="<?= e(app_url('criar-conta.php')) ?>">Criar conta gratuita</a>
      </div>
      <img class="pro-siga__mascot pro-siga__final-cta-mascot" src="<?= e(app_url('assets/img/mascot-happy.svg')) ?>"
           alt="Mascote do ProSiga acenando, convidando para criar uma conta">
    </section>
  </main>

  <footer class="pro-siga__footer">
    <div class="pro-siga__footer-inner">
      <div class="pro-siga__footer-col pro-siga__footer-brand-col">
        <div class="pro-siga__footer-brand">
          <img src="<?= e(app_url('assets/img/logo-mark.png')) ?>" alt="ProSiga" class="pro-siga__logo-mark pro-siga__logo-mark--light">
          <span class="pro-siga__wordmark pro-siga__wordmark--light">ProSiga</span>
        </div>
        <p class="pro-siga__footer-tagline">A rotina acadêmica organizada para quem ensina e para quem aprende.</p>
      </div>
      <div class="pro-siga__footer-col">
        <h3>Produto</h3>
        <a href="#recursos">Recursos</a>
        <a href="#como-funciona">Como funciona</a>
        <a href="#depoimentos">Depoimentos</a>
      </div>
      <div class="pro-siga__footer-col">
        <h3>Suporte</h3>
        <a href="#faq">Perguntas frequentes</a>
        <a href="mailto:contato@prosiga.com.br">Fale com a gente</a>
      </div>
    </div>
    <p class="pro-siga__footer-copy">&copy; <?= date('Y') ?> ProSiga. Todos os direitos reservados.</p>
  </footer>
</div>
<script src="<?= e(app_url('assets/js/landing.js')) ?>"></script>
</body>
</html>
