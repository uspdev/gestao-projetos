# Roadmap

> **Navegação rápida**
>
> [**Roadmap concluído**](roadmap-concluded.md)

Este documento reúne apenas as funcionalidades que ainda não foram
implementadas. A lista está ordenada por prioridade, com os itens de maior
impacto apresentados primeiro.

Algumas propostas da versão anterior já foram atendidas por soluções mais
restritas ou diferentes da ideia original. O que foi entregue está registrado
em [Roadmap concluído](roadmap-concluded.md), com seus limites atuais; este
arquivo não trata essas entregas como pendências novamente.

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

## 2. Incorporação direta de mídias no editor

A base de Arquivos privados, miniaturas, seleção contextual, Menções a arquivo
e compartilhamento explícito com reuniões já está documentada no
[roadmap concluído](roadmap-concluded.md). Este item trata somente do que ainda
não foi entregue: incorporar mídias diretamente no conteúdo Markdown.

> **Problema:** O editor atualmente envia Arquivos pela área própria e insere
> links Markdown. Imagens, vídeos e outros conteúdos não são incorporados no
> texto nem enviados por arrastar, colar ou pelo próprio editor.
>
> **Solução:** Definir e implementar uma experiência segura para inserir mídias
> autorizadas dentro do conteúdo, sem transformar o Markdown em mecanismo de
> autorização.

* **Implementação:** Definir os formatos que poderão ser exibidos dentro do
  texto, avaliar miniaturas ou blocos incorporados, integrar o fluxo de upload
  temporário ao salvamento quando necessário e manter a autorização aplicada
  tanto ao proprietário quanto ao leitor do conteúdo.
* **Prós:** Aproxima a experiência de documentação de ferramentas de texto rico
  e reduz a necessidade de alternar entre o texto e a área de Arquivos.
* **Contras:** Amplia a superfície de segurança, exige tratar arquivos
  temporários e órfãos e pode tornar a renderização dependente de formatos e
  conversões que ainda não são aceitos.

---

## 3. Relatórios, dashboards e inventário gerencial

O dashboard pessoal, as buscas e os filtros de tarefas já existem; esta
pendência é a visão gerencial e o inventário de infraestrutura.

> **Problema:** A execução técnica (tarefas e reuniões) já está coberta, mas
> falta uma visão gerencial para acompanhar o fluxo de trabalho. É impossível
> medir a capacidade da equipe, o tempo de entrega (Lead Time), o gargalo de
> atendimento aos setores ou mapear a infraestrutura de servidores e sistemas
> mantidos.
>
> **Solução:** Implementar um módulo de relatórios analíticos, separando
> conceitualmente as Demandas Gerenciais (o pedido do setor) das Tarefas
> Técnicas (a execução), e introduzir um inventário de infraestrutura de TI
> seguro e rastreável.

* **Implementação:**
  * **Demandas e Entregas:** Criar a tabela `demands` com fluxo de estados
    explícito (recebido, em análise, aprovado, em andamento, entregue),
    vinculada a unidades solicitantes. Demandas podem gerar múltiplas tarefas e
    registrar entregas parciais.
  * **Inventário Seguro:** Modelar `servers`, `services`, `technologies` e
    `systems` com relações N-N. Nenhuma credencial será armazenada; o foco é
    mapeamento de arquitetura, responsáveis e criticidade.
  * **Métricas e Dashboards:** Usar consultas otimizadas no MySQL com cache (5 a
    15 min), classes de serviço dedicadas como `DemandReportService`,
    componentes Blade, Chart.js e exportação para CSV.
  * **Autorização:** Criar permissões globais específicas para relatórios
    gerenciais e gestão de inventário, separadas da administração do sistema.
* **Prós:** Traz governança de TI, permite responder onde o tempo da equipe foi
  gasto e expõe a dívida técnica da infraestrutura.
* **Contras:** Aumenta a carga cognitiva da plataforma, pode produzir métricas
  divergentes se os filtros não forem unificados e exige disciplina para
  preencher horas e atualizar fluxos de demandas.

**Questões em aberto:**

* Em que volume as agregações no MySQL exigirão tabelas sumarizadas?
* É necessário iniciar sempre por uma Demanda ou tarefas técnicas ad-hoc devem
  continuar permitidas?
* Dashboards no Laravel são suficientes ou uma ferramenta de BI seria mais
  adequada?

---

## 4. Interface de auditoria do projeto

O backend já registra alterações e oferece filtros; falta disponibilizar essa
informação para consulta pelos usuários autorizados.

> **Problema:** Os dados de auditoria não podem ser consultados pela interface.
> Isso dificulta descobrir quem alterou um projeto, quando a alteração ocorreu e
> quais valores foram modificados.
>
> **Solução:** Criar uma tela de auditoria no contexto de cada projeto, com uma
> linha do tempo das atividades do próprio projeto e de seus recursos
> relacionados.

* **Implementação:** Consultar os eventos por projeto, usuário, tipo de
  recurso, ação e período. Apresentar autor, data, evento e comparação entre
  valores antigos e novos, com paginação e tratamento legível para
  relacionamentos, enums e campos sem valor anterior. Proteger o acesso por
  permissão específica, respeitando o escopo do projeto.
* **Prós:** Torna a rastreabilidade disponível aos responsáveis pelo projeto e
  facilita investigações sem exigir acesso direto ao banco.
* **Contras:** A diversidade dos eventos exige formatação específica e
  históricos extensos podem demandar índices, paginação eficiente e limites de
  período.

---

## 5. Resumos de reuniões e projetos com LLM

> **Problema:** Reuniões e projetos acumulam informações que demandam leitura
> manual para a identificação de decisões, pendências e próximos passos.
>
> **Solução:** Uso do package API-KEYS para gerenciar chaves de acesso para consultas de
LLMs externas, permitindo gerar resumos de reuniões, projetos e tarefas, de forma que os usuários possam rapidamente compreender o histórico e o andamento.

* **Implementação:** Implementar integração com LLMs externas, utilizando a API-KEYS para gerenciar chaves de acesso. Criar endpoints para enviar dados de reuniões e projetos para a LLM, receber os resumos gerados e armazená-los de forma segura no sistema. Garantir que apenas usuários autorizados possam acessar os resumos gerados.
* **Prós:** Reduz o tempo necessário para compreender o histórico e facilita a
  comunicação de decisões e andamento.
* **Contras:** Introduz custo de API, tratamento de falhas externas e cuidados
  adicionais com privacidade, qualidade e previsibilidade dos resultados.

---

## 6. Calendário pessoal do usuário e calendário do projeto

O dashboard pessoal já reúne projetos, reuniões e tarefas, mas ainda não há uma
visão temporal unificada.

> **Problema:** O usuário não possui uma visão cronológica dos próprios
> compromissos, e cada projeto carece de uma visão dos seus prazos e eventos.
>
> **Solução:** Disponibilizar um calendário pessoal com os prazos e reuniões
> acessíveis ao usuário e um calendário dentro de cada projeto, limitado às
> tarefas e reuniões daquele contexto.

* **Implementação:** Expor tarefas e reuniões em formato adequado a um
  componente de calendário, com consultas específicas para o escopo do usuário
  e do projeto, respeitando módulos habilitados, permissões e fusos horários.
* **Prós:** Facilita o planejamento individual e oferece às equipes uma visão
  compartilhada dos prazos, reuniões e possíveis conflitos.
* **Contras:** A experiência depende de regras claras para datas, eventos sem
  horário, sobreposição de compromissos e conversão de fusos horários.

---

## 7. Navegação por atalhos de teclado

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
