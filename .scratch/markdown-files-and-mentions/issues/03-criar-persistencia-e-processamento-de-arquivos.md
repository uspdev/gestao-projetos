# 03 — Criar persistência e processamento de Arquivos

**O que construir:** introduzir a base persistida e privada do módulo de Arquivos, seu ciclo de vida e o processamento assíncrono de miniaturas, ainda sem expor toda a interface final.

**Blocked by:** 02 — Integrar editor e pré-visualização oficial.

**Status:** ready-for-agent

- [ ] Instalar e configurar `spatie/laravel-medialibrary` compatível com Laravel 12 usando modelo `Media` próprio da aplicação.
- [ ] Publicar/adaptar a migração da biblioteca com UUID único e acrescentar `original_name` e `uploaded_by` com os índices e estratégia de preservação definidos na especificação.
- [ ] Limitar Proprietários a Projeto, Tarefa e Reunião e adicionar relações reutilizáveis aos três modelos.
- [ ] Configurar disk dedicado, privado e selecionável por ambiente, inicialmente local e sem dependência de `storage:link`.
- [ ] Gerar nome físico opaco UUID mais extensão normalizada; manter UUID, Proprietário, conteúdo, Nome original e nome físico imutáveis.
- [ ] Configurar limite unitário de 100 MB e preservar a sanitização/bloqueio nativo de extensões de scripts executáveis pelo servidor sem criar allowlist geral.
- [ ] Aceitar formatos gerais, registrar MIME apenas como metadado e manter `.exe`, `.bat` e `.sh` disponíveis como download.
- [ ] Implementar tarefa de miniatura em fila para raster decodificável por GD, com limites de 25 MP e 10.000 pixels por dimensão, primeiro quadro de GIF e execução depois da confirmação da transação.
- [ ] Persistir/derivar estados suficientes para exibir processamento, miniatura pronta ou card genérico após falha, sem bloquear o original.
- [ ] Integrar exclusão lógica, restauração e exclusão definitiva dos Proprietários: preservar e ocultar na exclusão lógica, recuperar na restauração e remover original/conversões na exclusão definitiva.
- [ ] Garantir exclusão definitiva individual sem `deleted_at`, substituição binária ou lixeira.
- [ ] Testar migrações, relações, imutabilidade, disk privado, sanitização, formatos gerais, limites, fila, conversões, falhas, exclusão lógica, restauração e exclusão definitiva com `Storage::fake()` e `Queue::fake()`.

## Critérios de conclusão

- Nenhum Arquivo ou conversão possui URL pública direta.
- Um Proprietário restaurado volta a enxergar os mesmos Arquivos; um Proprietário definitivamente excluído não deixa conteúdo físico ou metadados.
- Falha ou formato não suportado de miniatura nunca impede o download autorizado do original.
