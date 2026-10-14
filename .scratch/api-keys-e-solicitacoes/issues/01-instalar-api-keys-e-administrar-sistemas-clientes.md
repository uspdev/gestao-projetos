# 01 — Instalar API Keys e administrar Sistemas clientes

**O que construir:** permitir que administradores cadastrem uma identidade estável para cada integração de um Projeto e gerenciem suas Chaves de API usando diretamente o ciclo de emissão, renovação e revogação fornecido pela biblioteca.

**Blocked by:** Nenhum — pode iniciar imediatamente.

**Status:** ready-for-agent

- [ ] Elevar o requisito mínimo do projeto para PHP 8.3 e instalar `uspdev/api-keys` com a restrição `^0.1`, sem configurar o repositório irmão como dependência local.
- [ ] Publicar e configurar os recursos exigidos pelo package conforme seu processo oficial, mantendo autenticação Bearer e desabilitando chave por query string.
- [ ] Criar a persistência de Sistema cliente vinculado a exatamente um Projeto, com nome obrigatório de 3 a 120 caracteres e único no Projeto, descrição opcional em texto simples de até 1.000 caracteres, auditoria e timestamps.
- [ ] Tornar Sistema cliente owner das Chaves de API sob o alias estável `client-system` e resolver os papéis `viewer` e `contributor` para as abilities acordadas, sem `administrator` nem wildcard.
- [ ] Configurar as finalidades `integration` e `ai` como metadados sem diferença de autorização ou resposta.
- [ ] Permitir expiração opcional e preservar integralmente os comportamentos de exibição única, renovação e revogação da biblioteca.
- [ ] Adicionar a seção Integrações às configurações do Projeto, com listagem, criação e edição de Sistemas clientes, sem ações de desativação ou exclusão.
- [ ] Disponibilizar uma página própria de gerenciamento das chaves de cada Sistema cliente, renderizando diretamente o componente do package em vez de recriar suas operações.
- [ ] Autorizar a administração para administradores locais e globais, recusando contribuidores, visualizadores e pessoas com acesso apenas herdado.
- [ ] Revogar todas as chaves ativas dos Sistemas clientes quando o Projeto for excluído e exigir novas chaves após eventual restauração, preservando Sistemas clientes e histórico.
- [ ] Cobrir o fluxo por testes HTTP integrados, incluindo autorização administrativa, validações, owner, abilities, finalidade, expiração, renovação, revogação e ciclo de exclusão do Projeto.
