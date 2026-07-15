# Registros de reunião e itens independentes de pauta

## Declaração do problema

As reuniões atualmente possuem um único campo de notas e uma pauta formada apenas por projetos e tarefas existentes. Isso mistura informação preparatória com o resultado da reunião, não oferece um local próprio para armazenar a transcrição produzida por uma ferramenta externa e impede registrar ideias ou assuntos que ainda não são objetos de trabalho.

O sistema também apresenta o botão de adição de item de pauta apenas como um ícone `+`, o que dificulta descobrir sua finalidade. Como a aplicação está em produção ativa, os dados atuais e os fluxos existentes precisam continuar funcionando durante a evolução.

## Solução

Separar os registros da reunião em Anotações prévias, Ata e Transcrição, preservando o conteúdo legado de `notes`. Anotações prévias existirão em nível da reunião e em nível de item de pauta; Ata e Transcrição serão registros gerais da reunião.

Permitir que a Pauta contenha itens independentes, com título próprio e sem vínculo com projeto ou tarefa. Esses itens usarão as mesmas regras de ordenação, anotações, permissões e remoção dos itens vinculados, mas não serão convertidos automaticamente em projetos ou tarefas.

Tornar a ação de adicionar item de pauta explícita na interface, com texto visível junto do ícone.

## Histórias de usuário

1. Como colaborador, quero registrar Anotações prévias gerais da reunião, para preparar os participantes antes do encontro.
2. Como visualizador, quero consultar as Anotações prévias gerais, para entender o contexto planejado da reunião.
3. Como colaborador, quero registrar Anotações prévias específicas em cada item de pauta, para preparar a discussão de cada assunto.
4. Como colaborador, quero editar Anotações prévias gerais enquanto a reunião não estiver concluída, para corrigir ou complementar a preparação.
5. Como colaborador, quero editar Anotações prévias de um item enquanto a reunião não estiver concluída, para atualizar a preparação daquele assunto.
6. Como colaborador, quero que Anotações prévias e Anotações prévias do item fiquem bloqueadas enquanto a reunião estiver concluída, para preservar a pauta histórica.
7. Como colaborador, quero que esses campos voltem a ser editáveis se a reunião for reaberta, respeitando o comportamento atual de status.
8. Como colaborador, quero registrar a Ata durante ou depois da reunião, para resumir os assuntos relevantes e as conclusões obtidas.
9. Como colaborador, quero editar a Ata mesmo depois de a reunião estar concluída, para revisar o registro final.
10. Como visualizador, quero consultar a Ata conforme a permissão de visualização da reunião, para conhecer seus resultados.
11. Como colaborador, quero colar uma Transcrição produzida por ferramenta externa, para armazená-la junto da reunião sem depender de integração externa.
12. Como colaborador, quero corrigir ou substituir a Transcrição depois que a reunião estiver concluída, para acomodar correções da ferramenta externa.
13. Como visualizador, quero consultar a Transcrição conforme a permissão de visualização da reunião, para consultar o registro bruto da conversa.
14. Como usuário, quero que a Transcrição seja tratada como texto bruto, para que o sistema não altere, resuma ou interprete o conteúdo externo.
15. Como usuário, quero que Ata e Transcrição preservem quebras de linha, para manter a legibilidade do material armazenado.
16. Como usuário, quero receber uma validação clara quando a Ata ultrapassar 10.000 caracteres, para saber por que o envio não foi aceito.
17. Como usuário, quero receber uma validação clara quando a Transcrição ultrapassar 100.000 caracteres, para evitar truncamento silencioso.
18. Como usuário, quero limpar Ata, Transcrição ou Anotações prévias deixando o campo vazio, para remover um conteúdo que não é mais necessário.
19. Como colaborador, quero adicionar um item de pauta independente, para registrar uma ideia ou assunto que ainda não é projeto ou tarefa.
20. Como colaborador, quero escolher entre Projeto, Tarefa e Item independente no mesmo fluxo de adição, para usar uma experiência consistente.
21. Como colaborador, quero informar um título obrigatório para um Item independente, para que o assunto seja identificado na Pauta.
22. Como colaborador, quero editar o título de um Item independente enquanto a reunião não estiver concluída, para corrigir sua descrição antes do encontro.
23. Como usuário, quero que o título de um Item independente aceite de 3 a 255 caracteres, para manter títulos úteis e consistentes com objetos existentes.
24. Como colaborador, quero posicionar um Item independente em qualquer ordem da Pauta, para organizar a sequência de discussão.
25. Como colaborador, quero reordenar implicitamente os itens ao inserir ou remover um Item independente, para manter uma sequência contínua.
26. Como colaborador, quero registrar Anotações prévias em um Item independente, para preparar o assunto com o mesmo contexto dos demais itens.
27. Como visualizador, quero consultar um Item independente conforme a permissão da reunião, para acompanhar assuntos ainda não convertidos em trabalho.
28. Como colaborador, quero remover um Item independente antes da conclusão da reunião, para retirar assuntos que deixaram de fazer parte da Pauta.
29. Como usuário, quero que itens de projeto e tarefa existentes continuem aparecendo e funcionando sem alteração, para preservar a Pauta já cadastrada.
30. Como usuário, quero ver o título próprio de um Item independente nas telas, para não receber um identificador genérico de item.
31. Como usuário, quero ver “Adicionar item de pauta” junto do botão `+`, para entender imediatamente a ação disponível.
32. Como usuário, quero ver Anotações prévias gerais imediatamente acima da Pauta, para distinguir preparação da lista de assuntos.
33. Como usuário, quero encontrar Ata e Transcrição próximas em um bloco de Registro da reunião abaixo da Pauta, para localizar facilmente o resultado e o registro bruto.
34. Como colaborador, quero editar Anotações prévias, Ata e Transcrição em ações próprias, para não misturar textos longos com título, status e projetos.
35. Como usuário, quero que os dados atuais de reuniões e itens sejam preservados durante a atualização, para continuar consultando o histórico existente.
36. Como equipe de manutenção, quero que as mudanças de estrutura do banco sejam expansivas e sem preenchimento retroativo, para reduzir risco durante a implantação em produção.
37. Como equipe de manutenção, quero que a reversão protegida recuse remover dados novos, para evitar perda silenciosa de Ata, Transcrição ou Itens independentes.
38. Como equipe de manutenção, quero auditar alterações da Ata, para rastrear quem modificou o registro final.
39. Como equipe de manutenção, quero auditar a alteração da Transcrição sem duplicar seu conteúdo bruto, para preservar rastreabilidade sem aumentar desnecessariamente o `activity_log`.

## Decisões de implementação

- O campo existente `notes` de Meeting será preservado, sem renomeação física, cópia ou preenchimento retroativo. Seu significado de produto passará a ser Anotações prévias gerais.
- O campo existente `notes` de MeetingItem será preservado e exibido como Anotações prévias do item. Seu limite de 10.000 caracteres e suporte atual a Markdown serão mantidos para compatibilidade.
- A tabela de reuniões receberá os campos opcionais `ata` e `transcription`, ambos armazenados como texto longo e sem valor padrão obrigatório.
- `ata` terá limite de aplicação de 10.000 caracteres. `transcription` terá limite de aplicação de 100.000 caracteres. Valores acima do limite serão rejeitados, nunca truncados silenciosamente.
- Ata e Transcrição serão texto simples, preservando quebras de linha e sem interpretação de Markdown ou HTML.
- Campos textuais serão aparados; vazio ou somente espaços limpará o valor e persistirá `NULL`.
- Ata e Transcrição serão editáveis por colaboradores em qualquer status da reunião. Usuários com permissão de visualização poderão consultá-las.
- Anotações prévias gerais, Anotações prévias do item e título de Item independente serão bloqueados quando o status atual for `COMPLETED` e voltarão a ser editáveis se a reunião for reaberta.
- A Ata será um campo geral de texto livre, sem estrutura obrigatória de conclusão por item.
- Itens independentes continuarão em `meeting_items`, com título próprio e `discussable_type`/`discussable_id` opcionais. Cada item deverá ter exatamente uma representação: projeto/tarefa vinculada ou título independente.
- Itens existentes manterão seus vínculos polimórficos e seu comportamento atual. Não haverá registro artificial, segunda tabela ou conversão automática para projeto/tarefa.
- Item independente terá título obrigatório de 3 a 255 caracteres e poderá ter Anotações prévias do item, ordem e remoção pelas regras existentes. Itens de pauta não serão destinos de comentários.
- A criação continuará usando o modal existente, acrescentando “Item independente” como tipo. Quando selecionado, o seletor de projeto/tarefa será substituído pelo campo “Título do item”.
- O botão de adição exibirá “Adicionar item de pauta” junto do ícone `+`.
- A tela de detalhes exibirá Anotações prévias gerais imediatamente acima da Pauta. Ata e Transcrição ficarão próximas em um bloco de Registro da reunião abaixo da Pauta. Cada conteúdo terá edição própria.
- O formulário de criação continuará permitindo Anotações prévias gerais. Ata, Transcrição e itens serão tratados depois que a reunião existir; o formulário geral de edição não receberá esses textos longos.
- Autorização de Item independente seguirá a autorização da reunião: visualização pela permissão de visualização e criação/edição/remoção pela permissão de edição. A reunião continuará vinculada a pelo menos um projeto.
- A migração deverá adicionar campos opcionais (`nullable`) e tornar referências polimórficas opcionais sem modificar valores existentes. A integridade “exatamente uma representação” será garantida pela aplicação, sem `CHECK` específico do banco nesta entrega.
- O método de reversão da migração não poderá remover a estrutura se houver Ata, Transcrição ou Item independente persistido; deverá falhar explicitamente para exigir decisão operacional.
- A implantação será documentada em duas fases: executar e validar a migração antes de publicar o código. A execução da implantação não faz parte deste trabalho.
- Ata continuará sendo auditada com conteúdo. Transcrição gerará apenas auditoria de alteração com usuário, data e metadados mínimos, sem copiar o texto bruto para `activity_log`.
- Itens de pauta não serão destinos de comentários nem de notificações de comentário; comentários continuarão disponíveis nos contextos que já os suportam, como reunião, projeto e tarefa.

## Decisões de teste

- O ponto de teste principal será um teste de funcionalidade HTTP do Laravel, exercitando as rotas com usuário autenticado e verificando comportamento externo: status HTTP, mensagens, autorização, persistência, validações, conteúdo renderizado e auditoria observável.
- Um fluxo Dusk cobrirá a interação do modal e da tela: texto visível “Adicionar item de pauta”, seleção de “Item independente”, preenchimento do título, criação e exibição do item.
- Os testes deverão cobrir a migração em banco de teste, preservação de registros existentes, referências opcionais, reversão protegida e rejeição de dados que excedam limites.
- Os testes deverão cobrir Ata e Transcrição em reuniões não concluídas e concluídas, incluindo visualização, edição, limpeza, limites e autorização.
- Os testes deverão cobrir Anotações prévias gerais, Anotações prévias do item, reabertura de reunião e preservação do comportamento Markdown das anotações de item.
- Os testes deverão cobrir criação, validação, edição, ordenação, remoção e rejeição de comentários em Itens de pauta, além de garantir que Projeto e Tarefa existentes continuem funcionando.
- Os testes devem observar comportamento público, não detalhes internos de controllers, models ou queries. O cenário deve ser preparado com os dados mínimos necessários, pois o repositório ainda não possui factories de domínio para reuniões.
- A referência de teste existente é a separação entre testes de funcionalidade HTTP do PHPUnit e Laravel Dusk para fluxos de navegador; não há testes de reuniões prévios a reutilizar.

## Fora do escopo

- Integração com aplicativos externos de gravação ou transcrição.
- Processamento, resumo, tradução, classificação ou interpretação automática da Transcrição.
- Upload de arquivos de áudio, vídeo ou documentos.
- Histórico/versionamento próprio de Ata ou Transcrição além da auditoria definida.
- Conversão ou associação posterior de Item independente a projeto ou tarefa.
- Conclusões estruturadas por item de pauta.
- Editor rico novo, suporte a HTML ou mudança do comportamento Markdown das Anotações prévias do item.
- Nova ACL específica para Ata, Transcrição ou Item independente.
- Redesenho do ciclo de status ou bloqueio permanente de reuniões concluídas.
- Execução operacional da implantação, criação de pipeline ou automação de backup.
