# Publicar o whatsyourbubble.com

**Automático:** depois que os segredos de FTP estiverem no GitHub, todo merge no `main` que mude `index.html`, `ai/` ou `q/` é publicado sozinho pelo workflow `.github/workflows/publicar-site.yml` (FTP seguro, só os arquivos que mudaram, e no fim confere se as páginas abrem). Dá pra rodar à mão em Actions → "Publicar site" → Run workflow. Enquanto os segredos não existirem, o workflow não faz nada.

O passo a passo manual abaixo continua valendo como plano B.

Fluxo: **repositório GitHub → aba do cPanel (Freeola) → public_html**. Ninguém precisa arrastar arquivo.

1. Commit e `git push` em `main` deste repositório (`osvaldorsneto-prog/whatsyourbubble`).
2. No MyFreeola → SSD Hosting → **Quick Login** do `whatsyourbubble.com`. Abre o cPanel já logado.
3. Na aba do cPanel, rodar um script que, para cada arquivo de `index.html`, `ai/**` e `q/**`:
   - baixa de `https://raw.githubusercontent.com/osvaldorsneto-prog/whatsyourbubble/<SHA>/<caminho>` (usar o SHA do commit, não `main`, pra evitar cache);
   - envia com `POST <cpsess>/execute/Fileman/upload_files` (`dir`, `overwrite=1`, `file-1`).
4. Antes de publicar, copiar `public_html/ai` para `~/ai_backup_<versão>` (Fileman `fileop` op=copy).
5. Testar em `https://whatsyourbubble.com/ai/?v=<versão>` (o `?v=` fura o cache).

Observações:
- Arquivos de texto pequenos podem ir direto com `Fileman/save_file_content`.
- Os vídeos de `ai/story/` são gerados por `video/render.py stories`; o vídeo do TikTok por `video/render.py tiktok pt en zh`.
- Versões de cache dos scripts ficam nas tags `?v=` dentro de `ai/index.html`.
- `data/painel.json` é atualizado sozinho a cada 3 h pelo workflow `.github/workflows/painel-snapshot.yml` (lê o painel e salva os números). Não precisa publicar: `data/` e `scripts/` não vão pro site.
- Quizzes temáticos (`quizzes/<slug>.json`) **não precisam de publicação**: o motor `q/index.php` busca o arquivo no GitHub (cópia de 10 min no servidor, em `~/wyb-cache/`). Endereço: `https://whatsyourbubble.com/q/?s=<slug>`. Regras de conteúdo e formato em `quizzes/REGRAS.md`; conferir com `python scripts/validar_quiz.py`.
- As imagens de prévia genéricas dos quizzes (`q/og/`) saem de `python scripts/og_generico.py`.
- A vitrine (`/q/`, pra onde a página inicial redireciona) lê a lista `quizzes/index.json` do GitHub. Depois de criar ou mudar um quiz, rodar `python scripts/indice_quizzes.py` e subir o `index.json` junto.
- Publicação no cPanel só é necessária quando mudar algo em `index.html`, `ai/**` ou `q/**`.
- Vídeos (estilo adesivo, `video/estilo.py`): `python video/render.py tiktok pt en zh` (vídeo dos mascotes), `python video/render.py stories` (stories do site, em `ai/story/`), `python video/render.py quiz <slug> pt en zh` (vídeo de divulgação de um quiz temático, em `video/quiz-<slug>-<lang>.mp4`), `python video/render.py previa` (PNGs pra conferir o visual).
