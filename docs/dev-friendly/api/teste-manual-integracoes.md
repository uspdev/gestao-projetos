# Teste manual rápido da API de Integrações

Este roteiro confirma, em poucos minutos, que uma Chave de API autentica, que
o Projeto é encontrado, que uma Solicitação é persistida e que ela aparece na
fila da interface.

## 1. Preparar a tela

Use um usuário administrador local do Projeto e confirme em
**Configurações → Módulos** que **Tarefas** está ativo.

Em **Configurações → Integrações**:

1. crie ou escolha um Sistema cliente;
2. clique em **Gerenciar chaves**;
3. crie uma API Key com papel **Contribuidor**;
4. copie a chave completa exibida no modal. Ela aparece somente uma vez.

Mantenha a aplicação local em execução:

```sh
php artisan serve
```

## 2. Informar chave e slug

Em outro terminal, carregue a chave sem exibi-la:

```sh
read -rsp 'Cole a chave e pressione Enter: ' API_TEST_KEY
printf '\n'
```

O slug é o trecho posterior a `/projects/` na URL do Projeto. Por exemplo, em
`http://127.0.0.1:8000/projects/gestao-projetos`, o slug é
`gestao-projetos`.

Configure o valor encontrado:

```sh
PROJECT_SLUG='gestao-projetos'
```

## 3. Confirmar autenticação e Projeto

```sh
curl -i -H "Authorization: Bearer ${API_TEST_KEY}" -H "Accept: application/json" "http://127.0.0.1:8000/api/projects/${PROJECT_SLUG}"
```

O resultado esperado é:

- `HTTP/1.1 200 OK`;
- `data.slug` igual ao slug informado;
- `data.modules.tasks_enabled` igual a `true`.

## 4. Criar uma Solicitação

```sh
curl -i -X POST -H "Authorization: Bearer ${API_TEST_KEY}" -H "Accept: application/json" -H "Content-Type: application/json" -d '{"title":"Teste externo","description":"Solicitação criada manualmente."}' "http://127.0.0.1:8000/api/projects/${PROJECT_SLUG}/requests"
```

O resultado esperado é:

- `HTTP/1.1 201 Created`;
- cabeçalho `Location` com a URL do detalhe;
- `data.title` igual a `Teste externo`;
- `data.status.value` igual a `pending`.

## 5. Conferir na interface

1. abra o mesmo Projeto no navegador;
2. acesse **Tarefas → Solicitações**;
3. atualize a página;
4. confirme que **Teste externo** aparece com estado **Pendente**.

Esse resultado comprova o fluxo local completo entre cliente HTTP,
autenticação Bearer, escopo do Projeto, persistência e fila da interface.

## Diagnóstico rápido

| Resultado | Verificação |
| --- | --- |
| `401 Unauthorized` | a chave está vazia, incompleta, expirada, revogada ou pertence a outra instalação |
| `403 Forbidden` | a chave não tem papel Contribuidor e falta a ability `requests.create` |
| `404 Not Found` | o slug está incorreto ou a chave pertence a outro Projeto |
| `409 Conflict` | o módulo de Tarefas está desabilitado |

O terminal integrado do VS Code pode ser usado normalmente. O `curl` é uma
requisição HTTP real e atravessa as mesmas rotas e middlewares usados por um
Sistema cliente externo. O teste local não cobre DNS, TLS ou proxies de um
ambiente implantado.
