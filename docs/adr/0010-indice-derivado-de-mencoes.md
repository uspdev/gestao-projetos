# Índice derivado de menções

**Status:** aceito

O Markdown bruto permanecerá como fonte da verdade, mas a primeira implementação incluirá uma tabela `mentions` como índice derivado. Cada vínculo identificará a fonte polimórfica, o campo textual, o usuário mencionado e o autor que criou a Menção, com unicidade por fonte, campo e usuário; ocorrências repetidas no mesmo campo produzirão um único vínculo.

A sincronização ocorrerá transacionalmente em cada salvamento: vínculos novos serão criados, vínculos ausentes no novo texto serão removidos e os existentes serão preservados. Um comando reconstruirá o índice a partir dos textos; remoções de fontes também limparão seus vínculos. Notificações por qualquer canal, caixa de entrada, backlinks e telas de consulta ficam fora do escopo inicial, evitando transformar o índice derivado em uma segunda fonte editorial ou introduzir um subsistema de notificações nesta entrega.
