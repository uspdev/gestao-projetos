# Hierarquia de projetos não compartilha arquivos

**Status:** aceito

A hierarquia e a herança de permissões entre projetos controlam o acesso de pessoas, mas não ampliam o escopo de Arquivos nem de Referências de arquivo. Um texto de subprojeto ou de suas tarefas não poderá referenciar Arquivos do projeto pai apenas por causa da hierarquia, e o projeto pai também não receberá acesso aos Arquivos dos filhos.

Essa separação é necessária porque um usuário pode ser membro direto do subprojeto sem visualizar o pai. Permitir a referência criaria textos cujo Arquivo estaria disponível para alguns leitores e indisponível para outros dentro da mesma entidade; quando um conteúdo precisar pertencer ao contexto do subprojeto, deverá ser enviado como um novo Arquivo desse subprojeto.
