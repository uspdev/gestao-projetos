# 01 — Expandir a estrutura do banco com segurança

**O que construir:** preparar a estrutura persistida para receber Ata, Transcrição e Itens independentes sem alterar ou invalidar reuniões e itens já existentes.

**Blocked by:** Nenhum — pode iniciar imediatamente.

**Status:** ready-for-agent

- [ ] Criar migração expansiva para os campos opcionais de reunião e para o título e referências opcionais dos itens de pauta.
- [ ] Garantir que todos os registros existentes mantenham seus valores e continuem sendo lidos pelo código atual.
- [ ] Garantir na aplicação a regra de que cada item tenha exatamente uma representação: projeto/tarefa vinculada ou título independente.
- [ ] Implementar reversão protegida, recusando a remoção da estrutura quando houver Ata, Transcrição ou Item independente persistido.
- [ ] Testar a migração em banco de teste, incluindo preservação de dados, nulabilidade e proteção contra perda na reversão.
- [ ] Documentar que a migração deve ser executada e validada antes da publicação do código pela equipe responsável pela implantação.
