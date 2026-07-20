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

### Arquivos e referências

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

**Referência de arquivo**:
Ocorrência em um texto que aponta para um Arquivo existente sem se tornar proprietária dele.
_Evitar_: Anexo, arquivo incorporado

**Compartilhamento de arquivo com reunião**:
Relação explícita que torna um Arquivo pertencente a outro objeto acessível aos participantes de uma reunião, sem alterar o Proprietário do arquivo.
_Evitar_: Transferência do arquivo, referência de arquivo

## Menções e referências

**Menção**:
Ocorrência em um texto que aponta para um usuário com a intenção de chamar sua atenção.
_Evitar_: Menção a projeto, menção a tarefa, menção a reunião, menção a arquivo

**Referência interna**:
Ocorrência em um texto que aponta para um projeto, tarefa, reunião ou Arquivo do sistema para permitir navegação, sem chamar um usuário à conversa por si só.
_Evitar_: Menção
