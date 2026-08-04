# Implantação da estrutura de registros de reunião

Esta etapa prepara o banco para Ata, Transcrição e Itens independentes de
pauta. A mudança é expansiva e não preenche dados históricos: `meetings.notes`
continua preservado como Anotações prévias.

## Ordem da implantação

A equipe responsável pela implantação deve executar e validar a migração antes
de publicar o código que usa os novos campos:

1. Execute a migração no banco alvo:

    ```sh
    php artisan migrate
    ```

2. Confirme que a migração
   `2026_07_14_090000_expand_meetings_and_meeting_items` aparece como executada:

    ```sh
    php artisan migrate:status
    ```

3. Valide a estrutura antes de liberar o novo código:
    - `meetings.ata` e `meetings.transcription` existem, são textos longos e
      aceitam `NULL`;
    - `meeting_items.title` existe e aceita `NULL`;
    - `meeting_items.discussable_type` e `meeting_items.discussable_id` aceitam
      `NULL`;
    - registros legados mantêm `meetings.notes`, seus vínculos e suas anotações
      sem alteração;
    - a contagem e a leitura de reuniões e itens legados continuam normais.

    Em MySQL ou MariaDB, a validação pode começar por:

    ```sql
    DESCRIBE meetings;
    DESCRIBE meeting_items;
    SELECT id, notes FROM meetings ORDER BY id LIMIT 10;
    SELECT id, meeting_id, discussable_type, discussable_id, notes
    FROM meeting_items
    ORDER BY id
    LIMIT 10;
    ```

4. Publique o código somente depois da validação da estrutura.

## Reversão protegida

O `down()` recusa a reversão se existir qualquer Ata, Transcrição, título de
item ou item sem vínculo polimórfico. Nessa situação, a operação falha
explicitamente para evitar perda de dados; não se deve forçar a remoção das
colunas.

A reversão só pode ser considerada em um ambiente sem dados novos e após uma
decisão operacional da equipe responsável. A execução da implantação e da
reversão não faz parte deste ticket.
