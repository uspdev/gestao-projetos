# Roadmap

> **Navegação rápida**
>
> [**Funcionalidades Implementadas**](#Funcionalidades-Implementadas)
>

Este documento reúne as próximas funcionalidades planejadas para expandir a
usabilidade, a governança e a integração da plataforma. A lista está ordenada
por prioridade, com os itens de maior impacto apresentados primeiro.

---

## 1. Integração Bidirecional com GitHub

> **Problema:** Desenvolvedores duplicam trabalho ao atualizar o GitHub e o
> sistema interno de tarefas.
>
> **Solução:** Sincronizar tarefas com Issues e Pull Requests, propagando
> alterações relevantes entre os sistemas.

* **Implementação:** Usar a API oficial e Webhooks do GitHub, com mapeamento de
  identificadores, autenticação e processamento assíncrono dos eventos.
* **Prós:** Reduz o trabalho manual e mantém o sistema interno atualizado mesmo
  quando a equipe opera principalmente pelo GitHub.
* **Contras:** Exige tratamento de conflitos, indisponibilidade externa,
  duplicidade de eventos e permissões de acesso aos repositórios.

---

## 2. Sistema de Texto Rico (Rich Text) e Menções

> **Problema:** Descrições, notas e comentários em texto simples limitam a clareza e a eficácia da comunicação. A ausência de formatação, anexos visuais (como capturas de tela) e links cruzados dificulta o detalhamento de bugs, tarefas e requisitos do projeto.
> **Solução:** Implementar um ecossistema de texto rico baseado em Markdown, com suporte a mídias, links e menções polimórficas (usuários, tarefas, projetos), dividindo a responsabilidade entre um editor no front-end e um parser robusto no back-end.

* **Implementação:**
* Configurar o `league/commonmark` no back-end com políticas estritas de segurança (strip de HTML e bloqueio de links inseguros) para prevenção de XSS.
* Criar uma tabela bidirecional `mentions` e definir uma sintaxe estável no Markdown (ex: `@[Nome](mention:user:ID)`).
* Adicionar componentes Blade reutilizáveis (`<x-rich-text-editor>`) nos formulários de descrição, comentários e notas.


* **Prós:** Melhora drasticamente a legibilidade, permite navegação rápida entre entidades mencionadas e centraliza anexos de forma rastreável, elevando a plataforma a um padrão profissional de gestão.
* **Contras:** Introduz alta complexidade na camada de renderização, exige sanitização rigorosa contínua e aumenta o escopo de gerenciamento de arquivos estáticos.

**Questões em Aberto e Definições de Arquitetura (A explorar):**

* **Mecânica da Persistência em Markdown:** Consolidar o entendimento sobre o armazenamento da string bruta (Markdown) no banco de dados em oposição ao HTML, e como ocorre a transformação dinâmica (renderização) apenas no momento em que a view é entregue ao usuário.
* **Batalha de Editores (EasyMDE vs. TinyMCE vs. Tiptap):** Realizar um comparativo aprofundado e definitivo. Ponderar por que editores WYSIWYG baseados em HTML (TinyMCE) geram atrito com Markdown, as limitações visuais de editores leves (EasyMDE) e o alto custo de desenvolvimento de UI de frameworks modernos (Tiptap).
* **Uploads e *Draft Tokens*:** Entender o padrão da indústria para anexos. Avaliar as razões pelas quais gigantes da tecnologia não utilizam Base64 in-line (tamanho de banco, lentidão de queries) e por que adotar o modelo de "upload temporário + consolidação no salvamento" é justificado mesmo para sistemas de menor porte, mapeando alternativas mais simples e seus respectivos prejuízos.
* **Parsers e AST (Abstract Syntax Tree):** Desmistificar a extensão do motor CommonMark. Compreender o que é a Árvore de Sintaxe Abstrata (AST) e como manipular esses "nós" programaticamente no Laravel permite interceptar menções e links de forma segura, garantindo que expressões regulares (Regex) falhas não quebrem o sistema ou abram brechas de segurança.

---

## 3. Mídias em Textos Ricos (Fotos, Vídeos e Arquivos)

> **Problema:** Informações visuais, arquivos de apoio e fluxos complexos são
> difíceis de registrar e compreender apenas com texto.
>
> **Solução:** Permitir o envio e a incorporação de mídias em qualquer campo com
> editor de texto rico, incluindo documentos, descrições, comentários, reuniões
> e demais conteúdos que adotem esse formato.

* **Implementação:** Criar uma camada centralizada de anexos integrada ao
  Storage do Laravel, em disco local ou serviço compatível com S3, e conectá-la
  aos editores de texto rico. Os arquivos devem manter vínculo com o conteúdo de
  origem e respeitar suas regras de acesso.
* **Prós:** Enriquece a documentação e a comunicação em todo o sistema, permite
  contextualizar informações com imagens, vídeos e arquivos e evita soluções de
  upload diferentes para cada módulo.
* **Contras:** Demanda validação de tipo e tamanho, controle de acesso, proteção
  contra arquivos maliciosos, gerenciamento de armazenamento e limpeza de
  anexos órfãos ou não utilizados.

---

## 4. Relatórios, Dashboards e Inventário Gerencial

> **Problema:** A execução técnica (tarefas e reuniões) já está coberta, mas falta uma visão gerencial para acompanhar o fluxo de trabalho. É impossível medir a capacidade da equipe, o tempo de entrega (Lead Time), o gargalo de atendimento aos setores ou mapear a infraestrutura de servidores e sistemas mantidos.
>
> **Solução:** Implementar um módulo de relatórios analíticos, separando conceitualmente as *Demandas Gerenciais* (o pedido do setor) das *Tarefas Técnicas* (a execução), e introduzir um inventário de infraestrutura de TI seguro e rastreável.

* **Implementação:**
  * **Demandas e Entregas:** Criar a tabela `demands` com fluxo de estados explícito (recebido, em análise, aprovado, em andamento, entregue), vinculada a unidades solicitantes. Demandas podem gerar múltiplas tarefas e registrar entregas parciais.
  * **Inventário Seguro:** Modelar `servers`, `services`, `technologies` e `systems` com relações N-N. Nenhuma credencial será armazenada; o foco é mapeamento de arquitetura, responsáveis e criticidade.
  * **Métricas e Dashboards:** Usar consultas otimizadas no MySQL com cache (5 a 15 min). Criar classes de serviço dedicadas (`DemandReportService`) para centralizar os cálculos. O front-end utilizará Chart.js e componentes Blade, além de exportação direta para CSV.
  * **Autorização:** Criar permissões globais específicas para relatórios gerenciais e gestão de inventário, separando esses papéis da administração do sistema.
* **Prós:** Traz governança de TI. Permite responder "onde o tempo da equipe foi gasto", expõe a dívida técnica da infraestrutura e tira o foco apenas do micro-gerenciamento de tarefas.
* **Contras:** Aumenta a carga cognitiva da plataforma. Introduz o risco de métricas divergentes se os filtros não forem unificados e requer maturidade da equipe para preencher horas e atualizar fluxos de demandas corretamente.

**Questões em Aberto e Definições de Arquitetura (A explorar):**

* **Limites de Agregação no MySQL:** Compreender a partir de que volume as consultas `GROUP BY` e cálculos estatísticos (como medianas de Lead Time) começarão a travar o banco transacional, exigindo tabelas sumarizadas (materialized views).
* **Taxonomia e Fluxo de Trabalho:** Debater a usabilidade de forçar que o ciclo de vida comece sempre por uma "Demanda" em vez de uma "Tarefa" técnica direta, avaliando o impacto disso na agilidade de desenvolvedores que estão acostumados a criar tarefas *ad-hoc*.
* **Relatórios como Código (Services vs. BI Tools):** Avaliar a decisão de codificar os dashboards diretamente no Laravel usando Chart.js contra o benefício e o custo de usar ferramentas maduras (como Metabase ou Apache Superset) conectadas a uma réplica de leitura do banco de dados.

---

## 5. Interface de Auditoria do Projeto

> **Problema:** O backend já registra as alterações realizadas no sistema, mas
> esses dados não podem ser consultados pela interface. Isso dificulta descobrir
> quem alterou um projeto, quando a alteração ocorreu e quais valores foram
> modificados.
>
> **Solução:** Criar uma tela de auditoria no contexto de cada projeto, com uma
> linha do tempo das atividades do próprio projeto e de seus recursos
> relacionados.

* **Implementação:** Usar o modelo e os filtros de auditoria existentes para
  consultar eventos por projeto, usuário, tipo de recurso, ação e período. A
  interface deve apresentar autor, data, evento e comparação entre valores
  antigos e novos, com paginação e tratamento legível para relacionamentos,
  enums e campos sem valor anterior. O acesso deve ser protegido por uma
  permissão específica, respeitando o escopo do projeto.
* **Prós:** Torna a rastreabilidade disponível aos responsáveis pelo projeto,
  facilita investigações de alterações indevidas e melhora a transparência sem
  exigir acesso direto ao banco de dados.
* **Contras:** A diversidade dos eventos auditados exige formatação específica
  para que os registros sejam compreensíveis. Consultas sobre históricos
  extensos também podem demandar índices, paginação eficiente e limites de
  período.

---

## 6. Resumos de Reuniões e Projetos com LLM

> **Problema:** Reuniões e projetos acumulam informações que demandam leitura
> manual para a identificação de decisões, pendências e próximos passos.
>
> **Solução:** Usar uma API de LLM para gerar resumos de reuniões e projetos,
> com processamento em segundo plano e opção de execução manual pelo usuário.

* **Implementação:** Criar jobs enfileirados para geração dos resumos, permitir
  a execução sob demanda e oferecer configuração do prompt utilizado em cada
  contexto.
* **Prós:** Reduz o tempo necessário para compreender o histórico e facilita a
  comunicação de decisões e andamento.
* **Contras:** Introduz custo de API, tratamento de falhas externas e cuidados
  adicionais com privacidade, qualidade e previsibilidade dos resultados.

---

## 7. Calendário Pessoal do Usuário e Calendário do Projeto

> **Problema:** Embora o dashboard pessoal já reúna projetos, reuniões e tarefas,
> o usuário ainda não possui uma visão temporal unificada de seus compromissos.
> Da mesma forma, cada projeto carece de uma visão cronológica própria para
> acompanhar seus prazos e eventos.
>
> **Solução:** Disponibilizar um calendário pessoal com os prazos e reuniões
> acessíveis ao usuário e um calendário dentro de cada projeto, limitado às
> tarefas e reuniões daquele contexto.

* **Implementação:** Expor tarefas e reuniões em formato adequado a um componente
  de calendário, com consultas específicas para o escopo do usuário e do
  projeto, respeitando módulos habilitados, permissões e fusos horários.
* **Prós:** Facilita o planejamento individual e oferece às equipes uma visão
  compartilhada dos prazos, reuniões e possíveis conflitos do projeto.
* **Contras:** A experiência depende de regras claras para datas, eventos sem
  horário, sobreposição de compromissos e conversão de *timezones*.

---

## 8. Navegação por Atalhos de Teclado

> **Problema:** A dependência exclusiva do mouse desacelera ações frequentes,
> principalmente para usuários intensivos da plataforma.
>
> **Solução:** Adicionar atalhos globais para ações como criar tarefas, focar
> campos de busca e navegar entre áreas do sistema.

* **Implementação:** Configurar eventos globais de teclado e uma camada
  centralizada para registrar e documentar os atalhos disponíveis.
* **Prós:** Torna a navegação mais rápida e melhora a experiência de usuários
  recorrentes.
* **Contras:** É necessário evitar conflitos com campos de edição, navegador,
  sistema operacional e requisitos de acessibilidade.

---

<a id="Funcionalidades-Implementadas"></a>

# Funcionalidades Implementadas

Esta seção registra entregas concluídas. A ordem dos itens não representa
prioridade.

## Reuniões (Meetings)

**Implementado em:** 05/2026

O módulo permite criar, editar, consultar e remover reuniões vinculadas a um ou
mais projetos, com data, local, notas e estados de rascunho, agendada, em
andamento e concluída. A pauta pode ser composta por projetos e tarefas, inclusive
de subprojetos, com ordenação e notas próprias para cada item. Reuniões concluídas
podem ser ocultadas das listagens, e as reuniões pendentes também aparecem no
dashboard dos usuários que possuem acesso aos projetos relacionados.

O acesso respeita os módulos habilitados e as permissões de visualização e
contribuição dos projetos. Alterações relevantes são auditadas e podem gerar
notificações por e-mail para os participantes, mantendo decisões, contexto e
acompanhamento associados aos objetos de trabalho do sistema.

---

## Atualizações de Status (Status Updates)

**Implementado em:** 05/2026

O acompanhamento de status foi incorporado diretamente aos principais fluxos,
sem uma entidade separada de atualização periódica. Projetos possuem estados que
cobrem planejamento, execução, espera, conclusão, cancelamento e arquivamento;
tarefas transitam entre nova, atribuída, em andamento, em revisão, em espera e
concluída; reuniões possuem seu próprio ciclo entre rascunho e conclusão.

As alterações podem ser feitas nas telas dos respectivos recursos e refletem nas
visualizações. Tarefas recebem automaticamente o estado de atribuída ao ganhar um responsável,
registram a data de conclusão e notificam os envolvidos quando concluídas. Essa
abordagem mantém o andamento visível no contexto em que o trabalho acontece e
evita a duplicação de informações em relatos paralelos.

---

## Dashboard Pessoal do Usuário

**Implementado em:** 05/2026

A página inicial autenticada funciona como dashboard individual. Ela reúne os
projetos fixados pelo usuário, as reuniões agendadas dos projetos acessíveis e as
tarefas atribuídas a ele. As tarefas podem ser exibidas em Kanban ou cartões,
pesquisadas e filtradas para mostrar ou ocultar itens concluídos; as preferências
de visualização são mantidas na sessão.

O backend carrega somente tarefas de projetos com o módulo correspondente ativo
e seleciona reuniões de acordo com o acesso do usuário. Assim, a tela reduz a
necessidade de visitar cada projeto para descobrir pendências.

---

## Estrutura de Projetos e Subprojetos

**Implementado em:** 06/2026

Projetos podem ser organizacionais, independentes ou vinculados como subprojetos
por meio de uma relação pai e filho. A interface permite localizar, vincular e
desvincular subprojetos, exibi-los na visão geral do projeto organizacional e
navegar entre os níveis. As regras impedem vínculos inválidos e exigem
administração compatível entre os projetos envolvidos.

A estrutura também oferece visibilidade e herança configurável de permissões,
além de tipos de projeto que definem fases e módulos disponíveis. Cada projeto
mantém sua própria configuração de módulos, como tarefas e reuniões, respeitando
se eles são obrigatórios ou editáveis para o tipo escolhido. Isso substitui a
ideia de pastas, listas e subtarefas por uma hierarquia alinhada ao domínio do
sistema, com escopo funcional e acesso controlados por projeto.

---

## Navegação Contextual (Breadcrumbs)

**Implementado em:** 05/2026

As telas de projetos e tarefas exibem uma trilha contextual simples construída
diretamente nos componentes Blade. Ela oferece retorno à lista principal,
apresenta o projeto organizacional quando o usuário está em um subprojeto e
mantém links para o projeto atual e seus módulos, como tarefas e reuniões.

Os módulos ativos também alimentam o cabeçalho de navegação do projeto. A solução
é intencionalmente simples e baseada na estrutura suportada atualmente, sem
gerenciador central de breadcrumbs, pacote externo ou montagem recursiva de
hierarquias arbitrárias.

---

## Visões e Experiências por Contexto

**Implementado em:** 05/2026

Em vez de manter interfaces completas e duplicadas para perfis técnicos e
administrativos, o sistema adapta o conteúdo disponível ao contexto. Policies,
papéis de administrador, colaborador e visualizador, visibilidade e herança de
permissões determinam quais recursos e ações cada usuário pode acessar.

Tipos de projeto, fases e ativação granular de módulos permitem criar projetos
mais simples ou mais completos conforme a necessidade. No módulo de tarefas, o
usuário ainda pode alternar entre tabela e Kanban, mostrar apenas suas tarefas e
ocultar ou exibir itens concluídos. A combinação reduz sobrecarga visual sem
duplicar o front-end e mantém as restrições aplicadas também no backend.

---

## Trilha de Auditoria Avançada (Logs)

**Implementado em:** 06/2026

O backend registra criação e alterações dos principais modelos, incluindo
projetos, tarefas, reuniões, itens de pauta, comentários, tipos, módulos, fases e
tags. Os registros identificam o recurso, o evento, o usuário causador e os
valores antigos e novos dos campos modificados. Relações importantes, como
membros, responsáveis, projetos de uma reunião e módulos habilitados, também são
auditadas por meio dos modelos de pivot e de um subscriber dedicado.

O modelo de atividade fornece filtros por categoria, autor, recurso e evento. Há
ainda limpeza programada com políticas de retenção por categoria e suporte a
simulação por `dry-run`, evitando crescimento indefinido da tabela. A camada de
captura e consulta está pronta no backend, mas ainda não existe uma interface
gráfica para visualização dos logs.

---

## Notificações por E-mail

**Implementado em:** 05/2026

O sistema envia e-mails assíncronos para eventos relevantes: entrada de membros
em projetos, atribuição e conclusão de tarefas, criação de comentários,
atualizações ou cancelamento de reuniões e vínculo ou desvínculo de subprojetos.
As mensagens incluem o contexto do projeto e links para o recurso relacionado,
e o usuário que executou a ação é excluído da lista de destinatários.

Os envios usam filas por meio de classes `Mailable`, reduzindo o impacto no tempo
de resposta das ações web. Regras específicas evitam notificações prematuras,
como alterações em reuniões ainda mantidas como rascunho. A comunicação foi
concentrada em e-mail; não há inbox interna nem sistema de menções.

---

## Buscas Contextuais e Filtros de Tarefas

**Implementado em:** 06/2026

Foram adicionadas buscas reativas nos contextos em que o volume de dados exige
localização rápida. Projetos podem ser encontrados por nome, descrição e tags;
subprojetos, por nome; e tarefas, por título, projeto, prioridade e responsáveis.
As buscas funcionam nas visualizações pessoais e de projeto, inclusive em lista
e Kanban, exibem o estado sem resultados e atualizam as contagens visíveis.

As tarefas também podem ser filtradas entre todas ou somente as atribuídas ao
usuário e entre concluídas ou pendentes, com preferências mantidas na sessão. A
implementação atual é contextual e majoritariamente executada no front-end sobre
os dados já autorizados e carregados; não existe ainda uma barra única de busca
que consulte todas as entidades do sistema.
