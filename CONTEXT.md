# Gestão de projetos

Este contexto define a linguagem usada para organizar o trabalho, preparar e registrar reuniões e compartilhar informações no sistema de gestão de projetos.

## Reuniões

### Preparação e pauta

**Anotações prévias**:
Informações preparatórias registradas antes da reunião para orientar a conversa. Não são o resultado nem o registro formal das decisões da reunião.
_Evitar_: Notas, ata, transcrição

**Pauta**:
Conjunto ordenado de assuntos que serão tratados na reunião, podendo incluir projetos, tarefas ou itens independentes.
_Evitar_: Anotações prévias

**Item de pauta**:
Um assunto individual dentro da pauta, ordenado para discussão e que pode ter anotações e comentários próprios.

**Anotações prévias do item**:
Informações preparatórias específicas de um item de pauta, registradas antes da reunião para orientar a discussão daquele assunto.
_Evitar_: Notas do item, ata do item

**Item independente**:
Item de pauta com título próprio e sem vínculo com um projeto ou tarefa existentes. Representa, por exemplo, uma ideia ou assunto que ainda não foi convertido em objeto de trabalho.
_Evitar_: Item solto, item sem projeto

### Registro da reunião

**Ata**:
Registro final dos assuntos relevantes tratados na reunião e das conclusões obtidas em cada assunto. Pode ser redigida durante a reunião e revisada depois dela.
_Evitar_: Anotações prévias, transcrição

**Transcrição**:
Registro textual bruto da fala ocorrida na reunião, produzido por uma ferramenta externa e armazenado para consulta. Não é a Ata nem substitui a síntese das conclusões.
_Evitar_: Ata, anotações prévias

## Arquivos

Este contexto define a linguagem usada para armazenar arquivos relacionados ao trabalho e referenciá-los nos textos do sistema.

### Arquivos e Menções

**Arquivo**:
Documento, imagem ou outro conteúdo armazenado e associado a uma entidade do sistema, independentemente de ser citado em um texto.
_Evitar_: Anexo, mídia

**Proprietário do arquivo**:
Projeto, tarefa ou reunião a que um Arquivo pertence de forma exclusiva e permanente e da qual deriva seu ciclo de vida e seu acesso.
_Evitar_: Pasta, vínculo do arquivo

**Autor do arquivo**:
Usuário que realizou o envio de um Arquivo e pode administrá-lo enquanto mantiver acesso ao Proprietário do arquivo.
_Evitar_: Dono do arquivo, proprietário do arquivo

**Nome original do arquivo**:
Nome informado pelo navegador no momento do envio, preservado de forma imutável para estabelecer a proveniência do Arquivo.
_Evitar_: Nome físico, nome exibido

**Nome exibido do arquivo**:
Nome editável apresentado aos usuários e utilizado para compor o nome seguro oferecido no download; não identifica nem altera fisicamente o Arquivo.
_Evitar_: Nome original, nome físico

**Miniatura**:
Imagem derivada de um Arquivo para sua representação visual nos cards, sem substituir nem alterar o conteúdo original.
_Evitar_: Thumbnail

**Menção a arquivo**:
Menção que aponta para um Arquivo existente sem tornar o texto proprietário dele.
_Evitar_: Referência de arquivo, anexo, arquivo incorporado

**Compartilhamento de arquivo com reunião**:
Relação explícita que torna um Arquivo pertencente a outro objeto acessível aos participantes de uma reunião, sem alterar o Proprietário do arquivo.
_Evitar_: Transferência do arquivo, Menção a arquivo

## Menções

**Menção**:
Ligação estruturada presente em um texto que aponta para uma entidade identificável do sistema, como um usuário, projeto, tarefa, reunião ou Arquivo.
_Evitar_: Referência interna

**Menção a usuário**:
Menção que aponta para um usuário com a intenção de chamar sua atenção.

**Entidade mencionável**:
Entidade do sistema que pode ser destino de uma Menção por possuir identidade estável e regras próprias de descoberta e visualização.

**Rótulo histórico da Menção**:
Nome ou título da Entidade mencionável no momento em que a Menção foi inserida, preservado no texto mesmo que a entidade seja renomeada.

## Integrações por API

**Chave de API**:
Credencial pertencente a um Sistema cliente e usada para acessar os recursos autorizados do Projeto ao qual esse sistema está vinculado.
_Evitar_: token global, chave compartilhada

**Projeto de escopo da Chave**:
Projeto ao qual pertence o Sistema cliente proprietário da Chave de API e cujo limite de recursos ela não pode ultrapassar.
_Evitar_: Projeto proprietário da Chave, projeto da sessão, projeto do usuário

**Papel da Chave de API**:
Nível de acesso concedido a uma Chave de API. `viewer` permite leitura e `contributor` acrescenta o envio de Solicitações, sem transformar a credencial em membro do Projeto.
_Evitar_: papel do usuário, papel do membro, administrador da API

**Sistema cliente**:
Identidade estável de um sistema externo dentro de um Projeto, proprietária de suas Chaves de API e de suas Solicitações mesmo quando uma credencial é renovada.
_Evitar_: usuário da API, LLM

**Solicitação**:
Proposta de correção ou melhoria enviada por um Sistema cliente para avaliação em um Projeto. Permanece registrada após a avaliação e, quando aceita, dá origem a uma nova Tarefa vinculada.
_Evitar_: Tarefa pendente, Tarefa externa

**Avaliador da Solicitação**:
Usuário com vínculo local de administrador ou contribuidor no Projeto que aceita ou rejeita uma Solicitação. Quando a aceita, torna-se também o criador da Tarefa resultante, enquanto o Sistema cliente permanece registrado como origem da Solicitação.
_Evitar_: criador externo da Tarefa, Sistema cliente avaliador

**URL de origem da Solicitação**:
Link opcional para a página do Sistema cliente na qual a Solicitação se originou, preservado apenas como caminho de consulta para o avaliador.
_Evitar_: callback, webhook, URL da API

**Descrição da Solicitação**:
Texto simples enviado pelo Sistema cliente para explicar o pedido original, preservando quebras de linha e sem produzir Markdown ou Menções.
_Evitar_: descrição da Tarefa, conteúdo Markdown

**Solicitação aceita**:
Solicitação em estado final cuja avaliação decidiu incorporá-la ao trabalho do Projeto e originou uma única nova Tarefa vinculada.
_Evitar_: Solicitação confirmada, Tarefa aprovada

**Solicitação rejeitada**:
Solicitação em estado final cuja avaliação decidiu não incorporá-la ao trabalho do Projeto e que permanece registrada com uma Resposta à Solicitação, sem originar Tarefa.
_Evitar_: Solicitação excluída, Tarefa rejeitada

**Resposta à Solicitação**:
Explicação devolvida ao Sistema cliente sobre o resultado da avaliação de uma Solicitação; é obrigatória na rejeição e opcional na aceitação.
_Evitar_: Comentário da Tarefa, motivo da exclusão
