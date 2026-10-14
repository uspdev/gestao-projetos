# Autenticação da API por cabeçalho e parâmetro de consulta

**Status:** aceito

A API de leitura aceitará a Chave de API tanto em `Authorization: Bearer`
quanto no parâmetro de consulta `api_key`. Essa decisão substitui somente a
restrição de transporte registrada no ADR 0009 e permite integrar clientes que
não conseguem enviar cabeçalhos personalizados; o cabeçalho Bearer permanece a
forma recomendada porque URLs podem ser registradas em históricos, logs,
proxies e ferramentas de monitoramento.

Quando uma requisição enviar as duas formas, a credencial do cabeçalho terá
precedência. O mesmo contrato vale para todas as rotas de leitura, inclusive o
download de Arquivos.
