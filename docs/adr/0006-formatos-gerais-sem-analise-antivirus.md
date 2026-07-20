# Formatos gerais sem análise antivírus

**Status:** aceito

O módulo aceitará formatos gerais de Arquivo, sem uma lista fechada de extensões ou MIME e sem análise antivírus presente ou futura. A detecção de MIME será armazenada como metadado e poderá orientar apresentação e conversões, mas nunca será apresentada como certificação de segurança; a proteção nativa da Media Library contra extensões executáveis em qualquer segmento do nome será mantida.

## Consequências e controles compensatórios

- Um Arquivo autorizado ainda pode conter malware, macros, conteúdo ativo, arquivos poliglotas ou cargas maliciosas dentro de formatos e contêineres válidos. Usuários não devem interpretar a presença do Arquivo no sistema como garantia de segurança.
- Somente imagens raster validadas e decodificáveis poderão ser exibidas inline. Os demais formatos serão entregues como download com `Content-Disposition: attachment` e `X-Content-Type-Options: nosniff`; SVG, HTML, PDF e outros conteúdos ativos não serão incorporados nas páginas.
- O nome físico será opaco e separado do Nome exibido do arquivo. Nomes recebidos serão normalizados para impedir travessia de diretórios, quebra de cabeçalhos e extensões executáveis disfarçadas, sem substituir o sanitizador seguro da biblioteca.
- O Nome original do arquivo será preservado como metadado imutável e restrito a quem puder administrar o Arquivo. Essa preservação registra o que foi efetivamente enviado, permite investigar extensões duplas ou divergências de MIME e mantém a proveniência após uma renomeação; ele não participará de URLs, caminhos físicos nem cabeçalhos de download.
- O armazenamento privado e a autorização reduzem exposição, mas não impedem que um usuário autorizado envie ou baixe conteúdo malicioso. Upload, renomeação e exclusão serão auditados.
- Arquivos compactados não serão extraídos pelo servidor. Conversões ficarão restritas a imagens raster, com limites de dimensões e memória para reduzir risco de imagens construídas para esgotar recursos.
- O limite de 100 MB exige monitoramento de capacidade, backups e falhas de transferência. A ausência inicial de cotas por usuário ou Proprietário do arquivo deverá ser tratada como risco operacional conhecido, não como armazenamento ilimitado.
