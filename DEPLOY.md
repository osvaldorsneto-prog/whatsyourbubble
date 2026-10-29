# Publicar o whatsyourbubble.com

Fluxo: **repositório GitHub → aba do cPanel (Freeola) → public_html**. Ninguém precisa arrastar arquivo.

1. Commit e `git push` em `main` deste repositório (`osvaldorsneto-prog/whatsyourbubble`).
2. No MyFreeola → SSD Hosting → **Quick Login** do `whatsyourbubble.com`. Abre o cPanel já logado.
3. Na aba do cPanel, rodar um script que, para cada arquivo de `index.html` e `ai/**`:
   - baixa de `https://raw.githubusercontent.com/osvaldorsneto-prog/whatsyourbubble/<SHA>/<caminho>` (usar o SHA do commit, não `main`, pra evitar cache);
   - envia com `POST <cpsess>/execute/Fileman/upload_files` (`dir`, `overwrite=1`, `file-1`).
4. Antes de publicar, copiar `public_html/ai` para `~/ai_backup_<versão>` (Fileman `fileop` op=copy).
5. Testar em `https://whatsyourbubble.com/ai/?v=<versão>` (o `?v=` fura o cache).

Observações:
- Arquivos de texto pequenos podem ir direto com `Fileman/save_file_content`.
- Os vídeos de `ai/story/` são gerados por `video/render.py stories`; o vídeo do TikTok por `video/render.py tiktok pt en zh`.
- Versões de cache dos scripts ficam nas tags `?v=` dentro de `ai/index.html`.
