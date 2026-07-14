# Registros de reunião e itens de pauta independentes

**Status:** aceito

Esta decisão reúne a modelagem e as regras de compatibilidade da implementação de registros de reunião e itens de pauta independentes. A reunião preservará suas anotações prévias existentes, ganhará Ata e Transcrição como registros gerais distintos e passará a aceitar itens de pauta sem vínculo com projeto ou tarefa, sem criar um segundo modelo de itens.

## Contexto

Atualmente, `meetings.notes` é o único campo textual geral da reunião e os registros em `meeting_items` precisam apontar para um projeto ou uma tarefa. A nova funcionalidade precisa distinguir o planejamento da reunião de seu resultado, armazenar uma transcrição produzida externamente e representar ideias que ainda não correspondem a entidades existentes.

O sistema está em produção ativa. Portanto, registros atuais não podem ser reinterpretados nem descartados, e a expansão do esquema precisa permitir a publicação do código sem interromper o comportamento existente.

## Decisões

### Registros gerais da reunião

- `meetings.notes` será preservado fisicamente e passará a ser denominado **Anotações prévias**. Seu conteúdo existente será mantido exatamente como está; não haverá cópia ou migração automática para outro campo.
- Será adicionado o campo técnico `ata`, correspondente à **Ata**: um texto livre geral, escrito durante ou após a reunião, que resume os assuntos relevantes e as conclusões. O limite será de 10.000 caracteres.
- Será adicionado o campo técnico `transcription`, correspondente à **Transcrição**: texto bruto produzido por uma ferramenta externa, armazenado sem processamento ou sumarização pelo sistema. O limite será de 100.000 caracteres.
- Ata, Transcrição e Anotações prévias serão textos simples, preservando quebras de linha e sem interpretação de Markdown ou HTML. Valores vazios ou compostos apenas por espaços serão armazenados como `NULL`.
- O envio acima dos limites será rejeitado; não haverá truncamento silencioso.
- A Ata e a Transcrição serão editáveis por contribuidores em qualquer status da reunião. As Anotações prévias manterão o comportamento atual, inclusive o bloqueio quando a reunião estiver concluída.

### Itens de pauta

- Itens independentes continuarão sendo armazenados em `meeting_items`, para preservar ordenação, anotações do item, comentários, notificações, permissões e comportamento das associações já existentes.
- As referências polimórficas de projeto ou tarefa passarão a ser opcionais para permitir itens independentes. Um item terá exatamente uma representação: projeto, tarefa ou título independente; essa regra será garantida na aplicação para manter compatibilidade entre MariaDB, MySQL e SQLite.
- Um item independente terá título próprio obrigatório, com 3 a 255 caracteres após a remoção de espaços nas extremidades. O título poderá ser editado antes da conclusão da reunião, ficará bloqueado durante a conclusão e voltará a ser editável se a reunião for reaberta.
- Não haverá conversão nem associação posterior de um item independente a projeto ou tarefa nesta implementação.
- As anotações do item manterão o comportamento atual: texto Markdown, limite de 10.000 caracteres, conversão de vazio para `NULL` e edição bloqueada enquanto a reunião estiver concluída.
- A criação continuará exigindo pelo menos um projeto vinculado à reunião; essa exigência não impede a inclusão de itens independentes na pauta.

### Interface e permissões

- As Anotações prévias serão exibidas em um bloco imediatamente acima da Pauta.
- Ata e Transcrição serão exibidas próximas uma da outra, em um bloco de registro da reunião abaixo da Pauta, cada uma com seu próprio editor.
- A criação da reunião continuará cuidando das Anotações prévias. Ata, Transcrição e itens de pauta serão administrados depois que a reunião existir; o formulário genérico de edição não incorporará esses textos longos.
- Visualizadores poderão ler Ata e Transcrição; contribuidores poderão editá-las. A criação, edição e remoção de itens independentes seguirão as permissões da reunião.
- O seletor de item de pauta oferecerá Projeto, Tarefa e Item independente. Ao escolher Item independente, exibirá o campo obrigatório Título do item.
- O controle de adição terá o texto visível **Adicionar item de pauta** junto ao botão `+`, deixando sua finalidade explícita.

### Compatibilidade, migração e reversão

- A migração será expansiva e retrocompatível: novos campos gerais serão anuláveis, a coluna de título será adicionada sem afetar itens existentes e as referências polimórficas atuais serão preservadas.
- O `down()` da migração não poderá remover dados silenciosamente. A reversão deverá falhar explicitamente se houver Ata, Transcrição ou itens independentes persistidos; só poderá desfazer a expansão quando não houver dados que seriam perdidos.
- A documentação de implantação deverá orientar a execução da migração, a validação do esquema e somente depois a publicação do código. A execução do deploy não faz parte da responsabilidade desta implementação.

### Auditoria

- Alterações da Ata continuarão registrando o conteúdo no mecanismo de auditoria existente.
- Alterações da Transcrição serão auditadas apenas por metadados mínimos de alteração, como usuário, data e tamanho ou hash quando aplicável. O texto bruto não será copiado para `activity_log`, evitando replicar até 100.000 caracteres potencialmente sensíveis a cada atualização.

## Opções consideradas

- **Criar uma tabela separada para itens independentes:** rejeitada porque duplicaria o modelo e exigiria replicar ordenação, comentários, notificações, permissões e anotações.
- **Copiar as anotações existentes para a Ata ou para a Transcrição:** rejeitada porque alteraria o significado de dados históricos e poderia duplicar conteúdo.
- **Usar `minutes` como nome técnico da Ata:** rejeitada por poder ser confundido com a unidade de tempo “minutos”.
- **Impor a representação exclusiva com `CHECK constraint`:** rejeitada para manter compatibilidade entre os bancos suportados; a aplicação será a autoridade dessa invariável.
- **Replicar o texto bruto da Transcrição na auditoria:** rejeitada para evitar duplicação desnecessária de conteúdo potencialmente sensível.

## Consequências

A implementação terá um único ponto de decisão arquitetural para o conjunto de registros da reunião, facilitando a leitura e a manutenção da intenção. Em contrapartida, o ADR fica mais extenso e reúne decisões que poderiam evoluir em ritmos diferentes; se alguma delas for revisada isoladamente no futuro, deverá receber um novo ADR que superscreva ou complemente este registro.
