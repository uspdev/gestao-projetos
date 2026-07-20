# Proprietário único dos arquivos

**Status:** aceito

Cada **Arquivo** terá exatamente um **Proprietário do arquivo** — projeto, tarefa ou reunião — definido na criação e imutável. Textos de outras entidades poderão criar Referências de arquivo sem compartilhar sua propriedade; essa restrição mantém autorização e ciclo de vida inequívocos.

Quando uma reunião precisar oferecer a sua audiência acesso a um Arquivo pertencente a um objeto relacionado, será criada uma relação explícita de **Compartilhamento de arquivo com reunião**, conforme o ADR 0011. Essa relação concede acesso sem transferir ou multiplicar a propriedade do Arquivo.
