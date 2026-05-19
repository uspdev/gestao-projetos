@extends('layouts.app')

@section('title', 'Gestão de Projetos USP')

@section('content')
  <style>
    :root {
      --landing-bg-1: #f7fbff;
      --landing-bg-2: #e9f5ec;
      --landing-primary: #004b87;
      --landing-accent: #0d7a5f;
      --landing-text: #16324f;
      --landing-muted: #4a6072;
      --landing-card: #ffffff;
      --landing-border: #d5e3ee;
    }

    .landing-wrap {
      min-height: calc(100vh - 120px);
      background: radial-gradient(circle at top left, var(--landing-bg-2) 0%, transparent 45%),
        linear-gradient(135deg, var(--landing-bg-1) 0%, #ffffff 100%);
      padding: 3.5rem 0 2.5rem;
    }

    /* Container expandido para resolver o espaço em branco nas laterais */
    .landing-container {
      width: 100%;
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 2rem;
    }

    /* Hero Section */
    .landing-hero {
      border: 1px solid var(--landing-border);
      border-radius: 18px;
      background: var(--landing-card);
      box-shadow: 0 16px 48px rgba(22, 50, 79, 0.08);
      overflow: hidden;
      margin-bottom: 3rem;
    }

    .landing-hero-content {
      padding: 3.5rem 3rem;
      text-align: center;
      max-width: 800px;
      margin: 0 auto;
    }

    .landing-badge {
      display: inline-block;
      font-size: 0.85rem;
      font-weight: 700;
      letter-spacing: 0.04em;
      color: var(--landing-primary);
      background: #e6f0fa;
      border: 1px solid #c3dbf1;
      border-radius: 999px;
      padding: 0.4rem 1rem;
      margin-bottom: 1.5rem;
      text-transform: uppercase;
    }

    .landing-title {
      font-size: clamp(2rem, 4vw, 3rem);
      line-height: 1.2;
      font-weight: 800;
      color: var(--landing-text);
      margin-bottom: 1.2rem;
    }

    .landing-subtitle {
      color: var(--landing-muted);
      font-size: 1.15rem;
      line-height: 1.6;
      margin-bottom: 2rem;
    }

    .landing-actions .btn {
      border-radius: 10px;
      padding: 0.8rem 2rem;
      font-size: 1.1rem;
      font-weight: 600;
      transition: transform 0.2s ease;
    }

    .landing-actions .btn:hover {
      transform: translateY(-2px);
    }

    /* Features Grid */
    .section-title {
      text-align: center;
      color: var(--landing-text);
      font-weight: 700;
      margin-bottom: 2rem;
      font-size: 1.8rem;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 1.5rem;
      margin-bottom: 3rem;
    }

    /* Roadmap Callout */
    .roadmap-callout {
      background: linear-gradient(160deg, var(--landing-primary) 0%, #0d5b6f 100%);
      border-radius: 16px;
      padding: 2.5rem;
      color: #ffffff;
      text-align: center;
    }

    .roadmap-callout h3 {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 1rem;
      color: #ffffff;
    }

    .roadmap-callout p {
      color: #e7f2ff;
      font-size: 1.05rem;
      max-width: 700px;
      margin: 0 auto 1.5rem auto;
      line-height: 1.6;
    }

    .roadmap-btn {
      background-color: transparent;
      color: #ffffff;
      border: 2px solid #ffffff;
      border-radius: 8px;
      padding: 0.6rem 1.5rem;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s;
      display: inline-block;
    }

    .roadmap-btn:hover {
      background-color: #ffffff;
      color: var(--landing-primary);
    }

    @media (max-width: 768px) {
      .landing-container {
        padding: 0 1rem;
      }

      .landing-hero-content {
        padding: 2rem 1.5rem;
      }

      .roadmap-callout {
        padding: 1.5rem;
      }
    }
  </style>

  <section class="landing-wrap">
    <div class="landing-container">

      <div class="landing-hero">
        <div class="landing-hero-content">
          <h1 class="landing-title">Gestão de projetos</h1>
          <p class="landing-subtitle">
            Uma plataforma criada para centralizar seus projetos, simplificar a organização de tarefas e
            facilitar o acompanhamento de entregas, feita para atender desde equipes de desenvolvimento até
            setores administrativos.
          </p>

          <div class="landing-actions">
            @auth
              <a href="{{ route('projects.index') }}" class="btn btn-primary btn-lg">Começar</a>
            @else
              <a href="{{ url('/login') }}" class="btn btn-primary btn-lg">
                <i class="fas fa-sign-in-alt me-2"></i> Login com Senha Única
              </a>
            @endauth
          </div>
        </div>
      </div>

      <h2 class="section-title">O que o sistema oferece?</h2>

      <div class="features-grid">

        <x-card.preview class="h-100 shadow-sm border">
          <div class="preview-card__feature-icon"><i class="fas fa-shield-alt"></i></div>
          <h3 class="preview-card__title preview-card__title--feature mb-2">Acesso Seguro Integrado</h3>
          <p class="preview-card__feature-desc">
            Navegue em um ambiente 100% seguro utilizando sua <strong>Senha Única da USP</strong>. Sem
            necessidade de criar novos cadastros ou memorizar novas senhas.
          </p>
        </x-card.preview>

        <x-card.preview class="h-100 shadow-sm border">
          <div class="preview-card__feature-icon"><i class="fas fa-project-diagram"></i></div>
          <h3 class="preview-card__title preview-card__title--feature mb-2">Ciclo de Vida de Projetos</h3>
          <p class="preview-card__feature-desc">
            Crie e gerencie projetos acompanhando seu status real (Planejamento, Desenvolvimento, Produção,
            etc.). Veja membros da equipe e todas as demandas em um só lugar.
          </p>
        </x-card.preview>

        <x-card.preview class="h-100 shadow-sm border">
          <div class="preview-card__feature-icon"><i class="fas fa-users-cog"></i></div>
          <h3 class="preview-card__title preview-card__title--feature mb-2">Colaboração e Permissões</h3>
          <p class="preview-card__feature-desc">
            Adicione colegas aos seus projetos com papéis bem definidos (Proprietário, Membro ou Leitor). O
            sistema garante de forma inteligente que funções críticas fiquem protegidas.
          </p>
        </x-card.preview>

        <x-card.preview class="h-100 shadow-sm border">
          <div class="preview-card__feature-icon"><i class="fas fa-tasks"></i></div>
          <h3 class="preview-card__title preview-card__title--feature mb-2">Organização de Tarefas</h3>
          <p class="preview-card__feature-desc">
            Quebre grandes entregas em tarefas menores. Defina responsáveis, níveis de prioridade, prazos de
            entrega e o status atual de cada atividade.
          </p>
        </x-card.preview>

        <x-card.preview class="h-100 shadow-sm border">
          <div class="preview-card__feature-icon"><i class="fas fa-tags"></i></div>
          <h3 class="preview-card__title preview-card__title--feature mb-2">Categorização e Filtros</h3>
          <p class="preview-card__feature-desc">
            Utilize etiquetas (labels) como 'Funcionalidade', 'Correção' ou 'Documentação' para classificar o
            trabalho, mantendo o painel do projeto organizado visualmente.
          </p>
        </x-card.preview>

        <x-card.preview class="h-100 shadow-sm border">
          <div class="preview-card__feature-icon"><i class="fas fa-user-circle"></i></div>
          <h3 class="preview-card__title preview-card__title--feature mb-2">Seu Espaço de Trabalho</h3>
          <p class="preview-card__feature-desc">
            Um painel pessoal dedicado onde você visualiza rapidamente seu perfil, atalhos de navegação e apenas
            os projetos e as tarefas que estão atribuídas diretamente a você.
          </p>
        </x-card.preview>

      </div>

      <div class="roadmap-callout">
        <h3>O sistema está em evolução contínua</h3>
        <p>
          Esta é apenas uma primeira versão (MVP). Novas funcionalidades já estão planejadas, como:
          integração com o GitHub, dashboards gerenciais, registro de reuniões e muito mais, tudo pensado para
          otimizar ainda mais o seu dia a dia. <strong>Tem alguma ideia? Sugestões para o nosso roadmap são muito
            bem-vindas!</strong>
        </p>
        <a href="https://github.com/uspdev/gestao-projetos/blob/main/docs/roadmap.md" target="_blank" class="roadmap-btn">
          <i class="fab fa-github me-2"></i> Ver Roadmap Completo
        </a>
      </div>

    </div>
  </section>
@endsection
