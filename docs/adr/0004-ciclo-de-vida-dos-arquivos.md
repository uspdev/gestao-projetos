# Ciclo de vida dos arquivos

**Status:** aceito

A exclusão lógica do Proprietário do arquivo preservará seus Arquivos, que ficarão inacessíveis pelas rotas normais e voltarão a ser acessíveis se o Proprietário for restaurado. A exclusão definitiva do Proprietário removerá seus Arquivos, metadados e conversões; a exclusão individual de um Arquivo também será definitiva e auditada, sem `deleted_at`, restauração ou lixeira própria.
