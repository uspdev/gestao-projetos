# Arquivos: propriedade, acesso e compartilhamento

Arquivos podem ser enviados para Projetos, Tarefas e Reuniões. O armazenamento
é privado: uma pessoa só consegue abrir, baixar ou ver uma miniatura quando a
aplicação confirma que ela tem acesso ao contexto do Arquivo.

## Envio e propriedade

Todo Arquivo tem um único **Proprietário do arquivo**, definido no envio:
Projeto, Tarefa ou Reunião. Essa propriedade não muda. O limite de envio é de
100 MB por Arquivo.

O conteúdo também é imutável. Para substituir uma versão, envie um novo
Arquivo; não há histórico automático de versões. O Nome exibido pode ser
alterado sem modificar o conteúdo, a origem ou o endereço estável do Arquivo.

O Nome original do arquivo é preservado para proveniência, mas só fica visível
ao autor do envio, a Admins do contexto e a administradores globais. O nome
que aparece para as demais pessoas é o Nome exibido.

## Acesso e administração

Pessoas que podem visualizar o Proprietário do arquivo podem acessar o Arquivo.
O autor pode renomear ou excluir um Arquivo enquanto ainda tiver esse acesso;
Admins do contexto e administradores globais também podem administrá-lo.

Uma Tarefa Concluída fica bloqueada para novo envio, renomeação e exclusão de
seus Arquivos. Se o Projeto, a Tarefa ou a Reunião for removido de forma lógica,
seus Arquivos ficam indisponíveis nas rotas normais e voltam a ser acessíveis
caso o proprietário seja restaurado. A exclusão individual de um Arquivo é
definitiva e não possui lixeira.

## Visualização segura

Imagens raster válidas podem ter miniatura gerada e exibida em cartões. Outros
formatos são oferecidos para download; PDF, SVG, HTML e conteúdos ativos não
são incorporados na página. A aplicação não executa análise antivírus e aceita
formatos gerais, inclusive arquivos potencialmente perigosos.

Portanto, um Arquivo disponível no sistema não é garantia de que seja seguro.
Abra apenas conteúdo de origem confiável e siga os procedimentos de segurança
da sua equipe.

## Menções a arquivo no texto

Uma **Menção a arquivo** insere um link persistente no Markdown. Ela ajuda a
encontrar um Arquivo, mas não concede acesso a ele. Quem não puder visualizar o
Arquivo verá uma indicação de falta de permissão, sem expor seu nome.

Remover a Menção do texto também não exclui o Arquivo nem revoga um eventual
compartilhamento. Para isso, use a ação própria correspondente no card de
Arquivos.

## Compartilhar com uma Reunião

Uma Reunião pode disponibilizar explicitamente um Arquivo de outro contexto aos
seus participantes. Para ser elegível, o Arquivo deve pertencer a:

- um Projeto vinculado à Reunião ou incluído como item de pauta; ou
- uma Tarefa incluída na Pauta.

Quem edita a Reunião e consegue visualizar o Arquivo pode compartilhá-lo. O
compartilhamento dá acesso a todas as pessoas que podem visualizar a Reunião,
mas não transfere a propriedade nem libera os demais Arquivos da mesma origem.

Use **Remover da reunião** para revogar esse acesso. A revogação não exclui o
Arquivo original. Alterar a Pauta, os Projetos vinculados ou a configuração de
módulos também não desfaz compartilhamentos já feitos; eles persistem até a
remoção explícita, a exclusão da Reunião ou a exclusão definitiva do Arquivo.

## Boas práticas

- Escolha o Proprietário que representa o contexto permanente do Arquivo.
- Envie uma nova versão em vez de tentar substituir um conteúdo existente.
- Compartilhe apenas o Arquivo necessário, em vez de presumir que a Pauta dá
  acesso a todos os documentos de um Projeto.
- Use Menções a arquivo para facilitar a navegação, não como mecanismo de
  autorização.
