# Geração síncrona de miniaturas no envio de Arquivos

**Status:** aceito

Miniaturas de Arquivos serão geradas de forma síncrona durante o envio, antes
da confirmação da operação e da resposta de sucesso. A decisão elimina a
dependência da fila e evita que uma falha de processamento fique registrada
como um Arquivo aceito sem possibilidade de regeneração pelo usuário.

## Decisão

- `FileUploadService` será o caminho único para novos envios e executará a
  geração da miniatura dentro da mesma transação da persistência e da auditoria.
- `FileThumbnailGenerator` será um serviço síncrono, sem `Job`, listener ou
  despacho de fila. Ele persistirá somente `ready` ou `not_supported`.
- Formatos não raster, imagens fora dos limites ou formatos que o ambiente não
  consiga decodificar serão aceitos sem miniatura. Ausência do GD, falhas de
  armazenamento e exceções inesperadas cancelarão o envio.
- Uma falha técnica removerá o original e qualquer miniatura criada, não
  persistirá o `Media` nem o evento de upload e será informada ao usuário na
  própria resposta do envio.
- A rota autenticada de leitura da miniatura permanecerá; não haverá endpoint
  de regeneração para esta versão.
- A fila continuará sendo usada por e-mails e outros trabalhos assíncronos,
  mas não será requisito operacional para miniaturas.

## Opções consideradas

- **Manter a miniatura em fila:** rejeitada porque a fila introduz uma janela
  de Arquivo sem miniatura e já causa problemas de permissão e conflito com
  outra funcionalidade.
- **Aceitar o original com estado `failed`:** rejeitada porque não existe
  operação de regeneração e o usuário não teria como corrigir o resultado.
- **Criar endpoint de regeneração:** deixado fora do escopo; o usuário poderá
  reenviar o Arquivo quando uma falha técnica ocorrer.

## Consequências

O envio passa a depender de GD, permissões do processo web, armazenamento
gravável e timeout suficiente na requisição. Em compensação, uma resposta de
sucesso representa um Arquivo já classificado com miniatura pronta ou não
suportada, sem trabalho pendente nem necessidade de worker para esse fluxo.
