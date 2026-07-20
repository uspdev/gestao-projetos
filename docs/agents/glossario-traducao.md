# Glossário de tradução das skills

Este glossário orienta a tradução dos termos operacionais usados pelas skills de engenharia. Os documentos do projeto devem usar português; nomes técnicos do código, nomes de campos, identificadores de arquivos e rótulos exatos do tracker permanecem inalterados quando necessário para manter a integração.

| Termo em inglês | Equivalência em português | Uso no projeto |
|---|---|---|
| `spec` / specification | especificação | Documento que consolida problema, solução, histórias, decisões e testes. |
| `issue` | issue | Unidade de trabalho publicada no issue tracker local. |
| `ticket` | ticket | Mantido como termo técnico corrente; pode ser explicado como demanda de trabalho. |
| `issue tracker` | issue tracker | Local onde especificações e issues são armazenadas. |
| `triage` | triagem | Classificação do estado e da prontidão do trabalho. |
| `ready-for-agent` | pronto para agente | O rótulo permanece exatamente em inglês para compatibilidade com o tracker. |
| `user story` | história de usuário | Necessidade expressa do ponto de vista de um ator. |
| `problem statement` | declaração do problema | Descrição do problema na perspectiva do usuário. |
| `implementation decisions` | decisões de implementação | Escolhas técnicas e de comportamento já resolvidas. |
| `testing decisions` | decisões de teste | Estratégia, seams e comportamentos que precisam ser verificados. |
| `acceptance criteria` | critérios de aceitação | Condições observáveis que definem a conclusão de um ticket. |
| `out of scope` | fora do escopo | O que não será entregue nesta especificação. |
| `further notes` | observações adicionais | Informações complementares que não cabem nas seções principais. |
| `seam` | ponto de teste | Limite de integração em que o comportamento externo será verificado. |
| `vertical slice` | fatia vertical | Entrega completa atravessando estrutura do banco, aplicação, interface e testes. |
| `blocking edge` / `blocked by` | dependência de bloqueio / bloqueado por | Relação que indica quais tickets precisam terminar antes. |
| `expand-contract` | ampliar primeiro, remover depois | Estratégia em duas fases: adicionar a nova estrutura mantendo a antiga compatível e só removê-la depois que os consumidores tiverem migrado. |
| `rollout` | implantação | Publicação gradual ou ordenada de uma mudança. |
| `rollback` | reversão | Retorno controlado a uma versão ou estrutura anterior. |
| `domain glossary` | glossário de domínio | Vocabulário canônico registrado em `CONTEXT.md`. |
| `AST` / `Abstract Syntax Tree` | árvore de sintaxe abstrata | Representação estruturada produzida pelo parser antes da renderização; a sigla `AST` pode ser mantida no código e em explicações técnicas. |
| `ADR` | ADR | Documento que registra uma decisão difícil de reverter e suas razões. |
| `meeting` | reunião | Entidade de reunião do sistema. |
| `meeting item` | item de pauta | Assunto individual dentro da pauta. |
| `task` | tarefa | Objeto de trabalho; o identificador da classe `Task` permanece inalterado. |
| `notes` | Anotações prévias | Conceito de domínio do campo legado de reunião. O identificador técnico permanece `notes`. |
| `transcription` | Transcrição | Registro textual bruto produzido externamente. O identificador técnico permanece `transcription`. |
| `minutes` | Ata | Termo técnico rejeitado para o campo da Ata por ser ambíguo com “minutos”. |
