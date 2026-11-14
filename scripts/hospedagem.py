#!/usr/bin/env python3
"""Conversa com o receptor da hospedagem (https://whatsyourbubble.com/q/receber.php).

A chave não aparece aqui: o ambiente da nuvem tem um "network secret" para o host
whatsyourbubble.com, e o proxy põe o cabeçalho "Authorization: Bearer ..." sozinho.

  python3 scripts/hospedagem.py ping
  python3 scripts/hospedagem.py baixar-quizzes          # quizzes/*.json e quizzes/index.json iguais aos do site
  python3 scripts/hospedagem.py publicar slug1 slug2    # envia esses quizzes + quizzes/index.json (entram no ar na hora)
  python3 scripts/hospedagem.py pacote saida.tar.gz     # monta o pacote de código da rotina (precisa do git)
  python3 scripts/hospedagem.py enviar-codigo saida.tar.gz

Instagram (o servidor publica pela API oficial do Meta; nada de navegador):
  python3 scripts/hospedagem.py ig-agendar video.mp4 legenda.txt "2026-11-15 18:30" slug   # sem fuso = horário de Londres
  python3 scripts/hospedagem.py ig-fila                 # conta conectada? fila e último ciclo
  python3 scripts/hospedagem.py ig-cancelar <id>
  python3 scripts/hospedagem.py ig-ciclo                # roda o agendador agora (o normal é o cron do servidor)
"""
import datetime, hashlib, json, os, pathlib, subprocess, sys, urllib.error, urllib.request

URL = os.environ.get('WYB_RECEPTOR', 'https://whatsyourbubble.com/q/receber.php')   # outro endereço só pra teste local
CHAVE_TESTE = os.environ.get('WYB_CHAVE_TESTE')   # só em teste local; na nuvem quem põe a chave é o proxy
ROOT = pathlib.Path(__file__).resolve().parent.parent
QUIZZES = ROOT / 'quizzes'
PARTE = 4 * 1024 * 1024
# o que a rotina precisa: regras, quizzes, scripts, gerador de vídeo com fundos e os mascotes
PACOTE = ['quizzes', 'scripts', 'video/estilo.py', 'video/render.py', 'video/fundos', 'ai/mascots.js', 'ai/secret.js']


def pedir(acao, corpo=None, tipo='application/json', **params):
    q = '&'.join([f'acao={acao}'] + [f'{k}={v}' for k, v in params.items()])
    cab = {'Content-Type': tipo, 'User-Agent': 'whatsyourbubble-rotina'}
    if CHAVE_TESTE:
        cab['Authorization'] = f'Bearer {CHAVE_TESTE}'
    req = urllib.request.Request(f'{URL}?{q}', data=corpo, method='POST' if corpo is not None else 'GET', headers=cab)
    try:
        with urllib.request.urlopen(req, timeout=120) as r:
            return json.loads(r.read().decode('utf-8'))
    except urllib.error.HTTPError as e:
        corpo_erro = e.read().decode('utf-8', 'replace')
        sys.exit(f'receptor respondeu {e.code}: {corpo_erro[:300]}')
    except urllib.error.URLError as e:
        sys.exit(f'não consegui falar com o receptor: {e.reason} (o ambiente tem o network secret pra whatsyourbubble.com?)')


def grava_json(caminho, dados, indent):
    caminho.write_text(json.dumps(dados, ensure_ascii=False, indent=indent) + '\n', encoding='utf-8')


def baixar_quizzes():
    r = pedir('quizzes')
    QUIZZES.mkdir(exist_ok=True)
    for slug, q in r['quizzes'].items():
        grava_json(QUIZZES / f'{slug}.json', q, 2)
    if r.get('indice'):
        grava_json(QUIZZES / 'index.json', r['indice'], 1)
    print(f"{len(r['quizzes'])} quizzes baixados" + (' + índice' if r.get('indice') else ''))


def publicar(slugs):
    quizzes = {s: json.loads((QUIZZES / f'{s}.json').read_text(encoding='utf-8')) for s in slugs}
    indice = json.loads((QUIZZES / 'index.json').read_text(encoding='utf-8'))
    corpo = json.dumps({'quizzes': quizzes, 'indice': indice}, ensure_ascii=False).encode('utf-8')
    r = pedir('publicar', corpo)
    print('publicados:', ', '.join(r['gravados']) or '(nenhum)', '| índice atualizado' if r.get('indice') else '')


def pacote(saida):
    subprocess.run(['git', '-C', str(ROOT), 'archive', '--format=tar.gz', '-o', str(pathlib.Path(saida).resolve()), 'HEAD', *PACOTE], check=True)
    print(saida, pathlib.Path(saida).stat().st_size, 'bytes')


def enviar_codigo(arquivo):
    dados = pathlib.Path(arquivo).read_bytes()
    sha = hashlib.sha256(dados).hexdigest()
    partes = [dados[i:i + PARTE] for i in range(0, len(dados), PARTE)]
    for n, p in enumerate(partes, 1):
        r = pedir('codigo', p, 'application/octet-stream', parte=n, total=len(partes), sha256=sha)
    print('pacote no servidor:', r.get('pacote'), 'bytes, sha256', sha[:12])


def enviar_partes(acao, dados):
    sha = hashlib.sha256(dados).hexdigest()
    partes = [dados[i:i + PARTE] for i in range(0, len(dados), PARTE)]
    r = {}
    for n, p in enumerate(partes, 1):
        r = pedir(acao, p, 'application/octet-stream', parte=n, total=len(partes), sha256=sha)
    return r


def quando_iso(txt):
    from zoneinfo import ZoneInfo
    d = datetime.datetime.fromisoformat(txt.strip().replace('Z', '+00:00'))
    if d.tzinfo is None:
        d = d.replace(tzinfo=ZoneInfo('Europe/London'))
    falta = (d - datetime.datetime.now(datetime.timezone.utc)).total_seconds()
    if not 180 <= falta <= 29 * 86400:
        sys.exit(f'horário fora da janela (de 3 min a 29 dias pra frente): {d.isoformat()}')
    return d.isoformat(timespec='seconds')


def ig_agendar(video, legenda_arq, quando, nome):
    quando = quando_iso(quando)
    legenda = pathlib.Path(legenda_arq).read_text(encoding='utf-8').strip()
    r = enviar_partes('ig_video', pathlib.Path(video).read_bytes())
    if not r.get('video'):
        sys.exit(f'o servidor não confirmou o vídeo: {r}')
    corpo = json.dumps({'video': r['video'], 'legenda': legenda, 'quando': quando, 'nome': nome}, ensure_ascii=False).encode('utf-8')
    a = pedir('ig_agendar', corpo)
    print(f"agendado: {a['id']} para {a['quando']}")


def ig_fila():
    r = pedir('ig_fila')
    print('conta:', r.get('conta') or 'NÃO CONECTADA', '| token até', r.get('token_expira_em'))
    u = r.get('ultimo_ciclo') or {}
    if u:
        em = u.get('em')
        em = datetime.datetime.fromtimestamp(em, datetime.timezone.utc).isoformat(timespec='minutes') if isinstance(em, int) else em
        print('último ciclo:', em, u.get('erro') or '; '.join(u.get('feito') or []) or 'nada a fazer')
    for i in r.get('fila', []):
        print(f"  {i.get('id')}  {i.get('status')}  {i.get('quando_txt')}  {i.get('link') or i.get('erro') or ''}")


if __name__ == '__main__':
    cmd, args = (sys.argv[1], sys.argv[2:]) if len(sys.argv) > 1 else ('', [])
    if cmd == 'ping':
        print(pedir('ping'))
    elif cmd == 'baixar-quizzes':
        baixar_quizzes()
    elif cmd == 'publicar' and args:
        publicar(args)
    elif cmd == 'pacote' and args:
        pacote(args[0])
    elif cmd == 'enviar-codigo' and args:
        enviar_codigo(args[0])
    elif cmd == 'ig-agendar' and len(args) == 4:
        ig_agendar(*args)
    elif cmd == 'ig-fila':
        ig_fila()
    elif cmd == 'ig-cancelar' and args:
        print(pedir('ig_cancelar', b'', id=args[0]))
    elif cmd == 'ig-ciclo':
        print(pedir('ig_ciclo', b''))
    else:
        sys.exit(__doc__)
