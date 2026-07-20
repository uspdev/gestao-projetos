# Compartilhamento explícito de arquivos com reuniões

**Status:** aceito

Uma reunião poderá compartilhar explicitamente Arquivos pertencentes aos projetos vinculados e às tarefas incluídas em sua pauta. O compartilhamento concederá acesso a todos que puderem visualizar a reunião, sem transferir o Proprietário do arquivo nem liberar automaticamente outros Arquivos dos mesmos objetos relacionados.

O compartilhamento será uma relação persistida e independente do Markdown. O seletor poderá executar a ação **Compartilhar com a reunião e inserir**, mas remover uma Referência de arquivo do texto não revogará acesso; a revogação exigirá a ação própria **Remover da reunião** no card de Arquivos. Revogar não excluirá o original, enquanto excluir o Arquivo ou a reunião removerá a relação automaticamente.

Essa exceção à regra geral de que referências não concedem acesso atende à audiência conjunta de reuniões com participantes de projetos diferentes sem transformar o texto editável em mecanismo de autorização. Arquivos não compartilhados continuarão sujeitos exclusivamente ao acesso derivado de seu Proprietário.

Para compartilhar, o usuário deverá poder editar a reunião e visualizar o Arquivo de origem; qualquer colaborador autorizado a editar a reunião poderá remover o compartilhamento, enquanto visualizadores apenas acessarão o conjunto já compartilhado. Projetos e tarefas não excluídos poderão fornecer Arquivos independentemente de status, pois a operação não altera a origem nem o conteúdo.

Mudanças posteriores na pauta, nos projetos vinculados ou na habilitação de módulos não revogarão compartilhamentos existentes. A exclusão lógica do Proprietário tornará o Arquivo temporariamente indisponível e sua restauração recuperará o acesso; fora disso, o compartilhamento persistirá até remoção explícita ou exclusão definitiva do Arquivo ou da reunião.
