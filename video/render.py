"""Vídeos do whatsyourbubble: TikTok (todos os mascotes) e stories animados por perfil."""
import io, re, math, os, subprocess, sys, wave, struct, random
import numpy as np, cairosvg
from PIL import Image, ImageDraw, ImageFont
import estilo as E

ROOT = os.path.dirname(os.path.abspath(__file__))
SITE = os.path.join(ROOT, '..', 'ai')
W, H, FPS = 1080, 1920, 30
BG = (15, 20, 34)
BLACK = '/usr/share/fonts/opentype/noto/NotoSansCJK-Black.ttc'
REG = '/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc'
_fc = {}
def F(path, size):
    k = (path, size)
    if k not in _fc: _fc[k] = ImageFont.truetype(path, size, index=2)
    return _fc[k]

src = open(os.path.join(SITE, 'mascots.js'), encoding='utf-8').read() + open(os.path.join(SITE, 'secret.js'), encoding='utf-8').read()
SVG = dict(re.findall(r'^\s*(?:MASCOT\.)?(\w)[:=]`(<svg.*?</svg>)`', src, re.M))
_mc = {}
def mascot(k, size):
    size = max(8, int(size))
    key = (k, size)
    if key not in _mc:
        s = SVG[k].replace('<svg ', f'<svg xmlns="http://www.w3.org/2000/svg" width="{size}" height="{size}" ', 1)
        _mc[key] = Image.open(io.BytesIO(cairosvg.svg2png(bytestring=s.encode()))).convert('RGBA')
    return _mc[key]

COL = {'A': (47, 75, 232), 'D': (217, 70, 59), 'M': (30, 158, 122), 'F': (201, 138, 18), 'S': (124, 58, 237)}
NAMES = {
 'pt': {'A': 'O Arquiteto', 'D': 'O Detetive', 'M': 'O Maestro', 'F': 'O Finalizador', 'S': 'A Bolha Secreta'},
 'en': {'A': 'The Architect', 'D': 'The Detective', 'M': 'The Maestro', 'F': 'The Finisher', 'S': 'The Secret Bubble'},
 'zh': {'A': '建筑师', 'D': '侦探', 'M': '指挥家', 'F': '终结者', 'S': '秘密泡泡'}}
LINES = {
 'pt': {'A': 'Você ganha a partida antes de digitar.', 'D': 'Nenhuma resposta passa sem interrogatório.', 'M': 'Você rege várias ferramentas ao mesmo tempo.', 'F': 'Pronto é melhor que perfeito.', 'S': 'Você não cabe numa bolha só.'},
 'en': {'A': 'You win before you start typing.', 'D': 'No answer gets past without questioning.', 'M': 'You conduct several tools at once.', 'F': 'Done beats perfect.', 'S': "You don't fit in just one bubble."},
 'zh': {'A': '还没打字，你就已经赢了。', 'D': '没有一个答案能逃过审问。', 'M': '你同时指挥好几种工具。', 'F': '完成胜过完美。', 'S': '一个泡泡装不下你。'}}
UI = {
 'pt': {'q': 'Que tipo de usuário\nde IA é você?', 'secret': '+ uma bolha secreta…', 'cta': 'Descubra a sua', 'you': 'E você?', 'me': 'Eu sou'},
 'en': {'q': 'What kind of\nAI user are you?', 'secret': '+ one secret bubble…', 'cta': 'Find yours', 'you': 'What about you?', 'me': "I'm"},
 'zh': {'q': '你是哪种\nAI用户？', 'secret': '+ 一个隐藏泡泡……', 'cta': '测出你的', 'you': '你呢？', 'me': '我是'}}

def ease_back(t):  # pop com leve exagero
    t = max(0.0, min(1.0, t)); c1, c3 = 1.70158, 2.70158
    return 1 + c3 * (t - 1) ** 3 + c1 * (t - 1) ** 2
def ease_out(t):
    t = max(0.0, min(1.0, t)); return 1 - (1 - t) ** 3

random.seed(7)
DEC = [(random.uniform(0, W), random.uniform(0, H), random.uniform(30, 120), random.uniform(.3, 1), random.choice(list(COL.values()))) for _ in range(14)]
def background(t):
    im = Image.new('RGB', (W, H), BG); d = ImageDraw.Draw(im)
    for x, y, r, sp, c in DEC:
        yy = (y - t * 60 * sp) % (H + 300) - 150
        d.ellipse([x - r, yy - r, x + r, yy + r], outline=tuple(int(v * .55 + 15) for v in c), width=5)
    return im

def text_c(d, y, s, font, fill, spacing=10, x=W / 2):
    d.multiline_text((x, y), s, font=font, fill=fill, anchor='ma', align='center', spacing=spacing)

def wrap(d, s, font, maxw):
    cjk = re.search(r'[㐀-鿿]', s) is not None
    parts = list(s) if cjk else s.split(' ')
    lines, cur = [], ''
    for p in parts:
        test = (cur + ('' if cjk else ' ') + p) if cur else p
        if d.textlength(test, font=font) > maxw and cur: lines.append(cur); cur = p
        else: cur = test
    lines.append(cur); return '\n'.join(lines)

def paste_c(im, m, cx, cy):
    im.paste(m, (int(cx - m.width / 2), int(cy - m.height / 2)), m)

def encode(path, frames_fn, n):
    tmp = path[:-4] + '.part.mp4'
    p = subprocess.Popen(['ffmpeg', '-y', '-loglevel', 'error', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{W}x{H}', '-r', str(FPS), '-i', '-',
                          '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '21', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', tmp], stdin=subprocess.PIPE)
    for i in range(n): p.stdin.write(frames_fn(i / FPS).tobytes())
    p.stdin.close()
    if p.wait() == 0: os.replace(tmp, path)
    else: raise SystemExit('ffmpeg falhou: ' + path)

def pops_wav(path, times, dur, sr=44100):
    a = np.zeros(int(sr * dur))
    for t0, pitch in times:
        n = int(.22 * sr); tt = np.arange(n) / sr
        f = pitch * np.exp(-tt * 18) + 120
        tone = np.sin(2 * np.pi * np.cumsum(f) / sr) * np.exp(-tt * 22) * .5
        for j, fr in enumerate((880, 1175, 1568)):
            st = int((.12 + j * .07) * sr); m = int(.3 * sr); t2 = np.arange(m) / sr
            seg = np.sin(2 * np.pi * fr * t2) * np.exp(-t2 * 12) * .12
            i0 = int(t0 * sr) + st; a[i0:i0 + m] += seg[:max(0, min(m, len(a) - i0))]
        i0 = int(t0 * sr); a[i0:i0 + n] += tone[:max(0, min(n, len(a) - i0))]
    a = np.clip(a, -1, 1)
    with wave.open(path, 'wb') as w:
        w.setnchannels(1); w.setsampwidth(2); w.setframerate(sr)
        w.writeframes((a * 32767).astype('<i2').tobytes())

def mux(video, audio, out):
    tmp = out[:-4] + '.part.mp4'
    subprocess.run(['ffmpeg', '-y', '-loglevel', 'error', '-i', video, '-i', audio, '-map', '0:v', '-map', '1:a', '-c:v', 'copy', '-c:a', 'aac', '-b:a', '128k', '-shortest', tmp], check=True)
    os.replace(tmp, out)

# ---------- TikTok: apresenta os 5 ----------
# Área segura do TikTok: fora das abas (topo), da coluna de botões (direita) e da legenda (base)
SAFE_X = 470          # centro horizontal útil (deslocado pra esquerda)
def music(dur, sr=44100, bpm=112):
    """Trilha original simples: bumbo, chimbal, baixo e acordes em pizzicato (C-G-Am-F)."""
    n = int(dur * sr); t = np.arange(n) / sr; out = np.zeros(n)
    beat = 60 / bpm
    chords = [(261.6, 329.6, 392.0), (196.0, 246.9, 293.7), (220.0, 261.6, 329.6), (174.6, 220.0, 261.6)]
    bass = [65.4, 49.0, 55.0, 43.7]
    k = 0
    while k * beat < dur:
        t0 = k * beat; i0 = int(t0 * sr); bar = (k // 4) % 4
        # bumbo
        m = int(.25 * sr); tt = np.arange(m) / sr
        kick = np.sin(2 * np.pi * (50 + 90 * np.exp(-tt * 30)) * tt) * np.exp(-tt * 9) * .55
        out[i0:i0 + m] += kick[:max(0, min(m, n - i0))]
        # chimbal no contratempo
        j0 = int((t0 + beat / 2) * sr); m2 = int(.05 * sr)
        hat = np.random.default_rng(k).standard_normal(m2) * np.exp(-np.arange(m2) / sr * 90) * .06
        out[j0:j0 + m2] += hat[:max(0, min(m2, n - j0))]
        # baixo
        m3 = int(beat * .9 * sr); tt = np.arange(m3) / sr
        bs = np.sin(2 * np.pi * bass[bar] * tt) * np.exp(-tt * 3) * .22
        out[i0:i0 + m3] += bs[:max(0, min(m3, n - i0))]
        # acorde em pizzicato nos tempos 2 e 4
        if k % 2 == 1:
            m4 = int(.4 * sr); tt = np.arange(m4) / sr
            ch = sum(np.sin(2 * np.pi * f * 2 * tt) for f in chords[bar]) * np.exp(-tt * 7) * .07
            out[i0:i0 + m4] += ch[:max(0, min(m4, n - i0))]
        k += 1
    fade = np.minimum(1, np.minimum(t / .3, (dur - t) / .8)); return out * fade

def mix_wav(path, dur, pops, sr=44100):
    tmp = path + '.p.wav'; pops_wav(tmp, pops, dur, sr)
    import wave as _w
    with _w.open(tmp) as w: p = np.frombuffer(w.readframes(w.getnframes()), '<i2').astype(float) / 32767
    os.remove(tmp)
    m = music(dur, sr)[:len(p)]
    a = np.clip(m * .8 + p * .9, -1, 1)
    with _w.open(path, 'wb') as w:
        w.setnchannels(1); w.setsampwidth(2); w.setframerate(sr); w.writeframes((a * 32767).astype('<i2').tobytes())

def _mascot_rays(im, k, size, cx, cy, t, s=1.0):
    """Mascote com raios girando atrás (estilo do site)."""
    if s <= 0.01: return
    r = E.rays(int(size * 1.55 * s), COL['A' if k == 'S' else k] if k != 'S' else E.GOLD, t)
    paste_c(im, r, cx, cy)
    paste_c(im, mascot(k, size * s), cx, cy)

def _name_tag(im, txt, cx, y, font, shadow, s=1.0):
    st = E.sticker_text(txt, font, wrap, 600, shadow=shadow, pad=(30, 14))
    st = E.scaled(st, s)
    if st: im.paste(st, (int(cx - st.width / 2), int(y)), st)

SNAME = {'pt': 'A Bolha Secreta', 'en': 'The Secret Bubble', 'zh': '秘密泡泡'}
DARK_S = ((28, 42, 140), (126, 30, 25))

def tiktok(lang, out, preview=None):
    u, N = UI[lang], NAMES[lang]
    order = ['A', 'D', 'M', 'F']
    T0, STEP, T_SECRET, T_END, DUR = 2.2, 1.25, 7.4, 9.4, 12.5
    pos = {'A': (255, 760), 'D': (685, 760), 'M': (255, 1120), 'F': (685, 1120)}
    sec = E.sticker_text(u['secret'], F(BLACK, 40), wrap, 760, fill=E.GOLD, shadow=E.INK, pad=(30, 14))
    url = E.sticker_text('whatsyourbubble.com', F(BLACK, 58), wrap, 900, fill=E.WHITE, shadow=E.PINK, pad=(40, 22))
    def frame(t):
        im = E.background(t); d = ImageDraw.Draw(im)
        ty = 720 - 520 * ease_out((t - 1.4) / .7)
        if t < T_END:
            E.title_text(d, (SAFE_X, ty), u["q"], F(BLACK, 84), sw=10)   # título já no 1º quadro
        for i, k in enumerate(order):
            ts = T0 + i * STEP
            if ts <= t < T_END:
                s = ease_back((t - ts) / .45); bob = math.sin((t - ts) * 3 + i) * 10
                cx, cy = pos[k]; _mascot_rays(im, k, 250, cx, cy + bob, t, s)
                if t - ts > .25: _name_tag(im, N[k], cx, cy + 150, F(BLACK, 38), POS_OF[k], ease_back((t - ts - .25) / .3))
        if T_SECRET <= t < T_END:
            s = ease_back((t - T_SECRET) / .5); m = mascot('S', 150 * s).copy()
            if t - T_SECRET < 1.2:
                al = m.split()[3]; sil = Image.new('RGBA', m.size, (60, 68, 92, 255)); sil.putalpha(al); m = sil
            paste_c(im, m, SAFE_X, 1370)
            st = E.scaled(sec, ease_back((t - T_SECRET - .2) / .4), -3)
            if st: paste_c(im, st, SAFE_X, 1500)
        if t >= T_END:
            e = t - T_END
            E.title_text(d, (SAFE_X, 300), u['cta'], F(BLACK, 110), sw=12)
            row = [('A', 130, 720), ('D', 300, 660), ('S', SAFE_X, 860), ('M', 640, 660), ('F', 810, 720)]
            for i, (kk, x, y) in enumerate(row):
                sz = 250 if kk == 'S' else 180
                sc = ease_back((e - i * .08) / .45)
                if kk == 'S': _mascot_rays(im, kk, sz, x, y + math.sin(e * 3 + i) * 12, t, max(sc, 0))
                elif sc > 0: paste_c(im, mascot(kk, sz * sc), x, y + math.sin(e * 3 + i) * 12)
            st = E.scaled(url, ease_back((e - .3) / .4), 2)
            if st: paste_c(im, st, SAFE_X, 1110)
            sub = {'pt': '8 perguntas · 1 minuto · link na bio', 'en': '8 questions · 1 minute · link in bio', 'zh': '8道题 · 1分钟 · 链接在主页'}[lang]
            text_c(ImageDraw.Draw(im), 1230, sub, F(BLACK, 40), E.INK, x=SAFE_X)
        return np.asarray(im)
    if preview: [still(frame, t, f'{out[:-4]}-{t}.png') for t in preview]; return
    v = out[:-4] + '.v.mp4'; encode(v, frame, int(DUR * FPS))
    wav = out[:-4] + '.wav'
    mix_wav(wav, DUR, [(T0 + i * STEP, 520 + i * 60) for i in range(4)] + [(T_SECRET, 700), (T_SECRET + 1.2, 900), (T_END, 600)])
    mux(v, wav, out); os.remove(v); os.remove(wav)

POS_OF = {'A': E.CYAN, 'D': E.PINK, 'M': E.LIME, 'F': E.VIOLET}

# ---------- Story animado por perfil ----------
def story(lang, k, out, preview=None):
    u, N, Lx = UI[lang], NAMES[lang], LINES[lang]; col = COL[k]; DUR = 6.0
    conf = [(random.uniform(0, W), random.uniform(-200, 600), random.uniform(-6, 6), random.uniform(6, 16), random.choice([col, col, E.PINK, E.GOLD, E.CYAN]), random.random() < .35) for _ in range(90)]
    name = E.sticker_text(N[k], F(BLACK, 96), wrap, 960, ink=E.WHITE, shadow=col, grad=E.DARK.get(k, DARK_S), pad=(50, 26))
    line = E.sticker_text(Lx[k], F(BLACK, 50), wrap, 900, shadow=E.GOLD, pad=(40, 26))
    url = E.sticker_text('whatsyourbubble.com', F(BLACK, 56), wrap, 900, shadow=E.PINK, pad=(40, 20))
    def frame(t):
        im = E.background(t); d = ImageDraw.Draw(im)
        E.title_text(d, (W / 2, 140), u['me'], F(BLACK, 76), sw=9)
        s = ease_back((t - .3) / .6); bob = math.sin(t * 3) * 14
        _mascot_rays(im, k, 560, W / 2, 740 + bob, t, max(s, 0))
        if t > .9:
            st = E.scaled(name, ease_back((t - .9) / .4), -2)
            if st: paste_c(im, st, W / 2, 1140)
        if t > 1.3:
            st = E.scaled(line, ease_back((t - 1.3) / .4), 1.5)
            if st: paste_c(im, st, W / 2, 1350)
        if t > 1.0:  # chuva de bolhas/confete
            el = t - 1.0
            for x, y0, vx, r, c, b in conf:
                x2 = x + vx * el * 30; y2 = y0 + el * 420 + el * el * 120
                if y2 > H + 40: continue
                if b: d.ellipse([x2 - r * 1.6, y2 - r * 1.6, x2 + r * 1.6, y2 + r * 1.6], outline=c, width=5)
                else: d.rectangle([x2 - r, y2 - r * .45, x2 + r, y2 + r * .45], fill=c)
        if t > 2.2:
            E.title_text(d, (W / 2, 1560), u['you'], F(BLACK, 84), sw=10)
            st = E.scaled(url, ease_back((t - 2.4) / .4))
            if st: paste_c(im, st, W / 2, 1760)
        return np.asarray(im)
    if preview: [still(frame, t, f'{out[:-4]}-{t}.png') for t in preview]; return
    v = out[:-4] + '.v.mp4'; encode(v, frame, int(DUR * FPS))
    wav = out[:-4] + '.wav'; pops_wav(wav, [(.35, 560)], DUR)
    mux(v, wav, out); os.remove(v); os.remove(wav)

# ---------- Vídeo de divulgação de um quiz temático (quizzes/<slug>.json) ----------
QUI = {'pt': {'q1': 'Pergunta 1', 'which': 'Qual bolha é a sua?', 'cta': 'Descubra a sua', 'sub': '8 perguntas · 1 minuto · link na bio'},
       'en': {'q1': 'Question 1', 'which': "Which bubble are you?", 'cta': 'Find yours', 'sub': '8 questions · 1 minute · link in bio'},
       'zh': {'q1': '第1题', 'which': '你是哪个泡泡？', 'cta': '测出你的', 'sub': '8道题 · 1分钟 · 链接在主页'}}

def quiz(slug, lang, out, preview=None):
    import json
    q = json.load(open(os.path.join(ROOT, '..', 'quizzes', f'{slug}.json'), encoding='utf-8'))[lang]
    u, N = QUI[lang], NAMES[lang]
    pergunta, ops = q['Q'][0]
    keys = ['D', 'F', 'A', 'M']                       # ordem fixa embaralhada: a cor não indica o perfil
    titulo = E.sticker_text(q['titulo'], F(BLACK, 76), wrap, 860, shadow=E.PINK, pad=(46, 34))
    tag = E.sticker_text(u['q1'], F(BLACK, 38), wrap, 500, fill=E.INK, ink=E.WHITE, shadow=E.GOLD, pad=(28, 12))
    balao = E.sticker_text(pergunta, F(BLACK, 54), wrap, 650, shadow=E.INK, pad=(40, 30), align='left')
    opc = [E.sticker_text(ops[k], F(BLACK, 44), wrap, 860, fill=tuple(int(c * .22 + 255 * .78) for c in E.POS[i]), shadow=E.POS[i], pad=(30, 22), align='left', dot=E.POS[i]) for i, k in enumerate(keys)]
    url = E.sticker_text('whatsyourbubble.com', F(BLACK, 58), wrap, 900, shadow=E.PINK, pad=(40, 22))
    T1, T2, T3, DUR = 2.4, 7.2, 10.2, 13.5
    pos = {'A': (255, 640), 'D': (685, 640), 'M': (255, 1010), 'F': (685, 1010)}
    def frame(t):
        im = E.background(t); d = ImageDraw.Draw(im)
        if t < T1:                                     # 1. título do quiz
            st = E.scaled(titulo, .9 + .1 * ease_back(t / .3), -2)   # título já no 1º quadro
            if st: paste_c(im, st, SAFE_X, 640)
            for i, k in enumerate('ADMF'):
                s = ease_back((t - .15 - i * .1) / .35)
                if s > 0: paste_c(im, mascot(k, 170 * s), 140 + i * 220, 1060 + math.sin(t * 4 + i) * 12)
        elif t < T2:                                   # 2. o mascote faz a pergunta 1
            e = t - T1
            st = E.scaled(tag, ease_back(e / .3), -3)
            if st: paste_c(im, st, SAFE_X - 260, 250)
            _mascot_rays(im, 'ADMF'[0], 160, 140, 520 + math.sin(t * 3) * 8, t, ease_back(e / .4))
            st = E.scaled(balao, ease_back((e - .2) / .4))
            if st: im.paste(st, (250, int(520 - st.height / 2)), st)
            y = 520 + balao.height / 2 + 50
            for i, o in enumerate(opc):
                s = ease_back((e - .9 - i * .35) / .35)
                st = E.scaled(o, s)
                if st: im.paste(st, (int(SAFE_X - st.width / 2), int(y)), st)
                y += o.height + 18
        elif t < T3:                                   # 3. as 4 bolhas
            e = t - T2
            E.title_text(d, (SAFE_X, 260), u['which'], F(BLACK, 80), sw=10)
            for i, k in enumerate('ADMF'):
                s = ease_back((e - i * .2) / .4)
                cx, cy = pos[k]; _mascot_rays(im, k, 240, cx, cy + math.sin(e * 3 + i) * 10, t, max(s, 0))
                if e - i * .2 > .25: _name_tag(im, N[k], cx, cy + 140, F(BLACK, 36), POS_OF[k], ease_back((e - i * .2 - .25) / .3))
            if e > 1.4:
                m = mascot('S', 130 * ease_back((e - 1.4) / .4)); al = m.split()[3]
                sil = Image.new('RGBA', m.size, (60, 68, 92, 255)); sil.putalpha(al); paste_c(im, sil, SAFE_X, 1320)
        else:                                          # 4. chamada final
            e = t - T3
            E.title_text(d, (SAFE_X, 360), u['cta'], F(BLACK, 110), sw=12)
            st = E.scaled(titulo, .62, 0)
            if st: paste_c(im, st, SAFE_X, 640)
            for i, k in enumerate('ADMF'):
                sc = ease_back((e - i * .08) / .4)
                if sc > 0: paste_c(im, mascot(k, 170 * sc), 140 + i * 220, 930 + math.sin(e * 3 + i) * 12)
            st = E.scaled(url, ease_back((e - .3) / .4), 2)
            if st: paste_c(im, st, SAFE_X, 1160)
            text_c(ImageDraw.Draw(im), 1280, u['sub'], F(BLACK, 40), E.INK, x=SAFE_X)
        return np.asarray(im)
    if preview: [still(frame, t, f'{out[:-4]}-{t}.png') for t in preview]; return
    v = out[:-4] + '.v.mp4'; encode(v, frame, int(DUR * FPS))
    wav = out[:-4] + '.wav'
    mix_wav(wav, DUR, [(0, 600)] + [(T1 + .9 + i * .35, 520 + i * 60) for i in range(4)] + [(T2 + i * .2, 560 + i * 50) for i in range(4)] + [(T3, 700)])
    mux(v, wav, out); os.remove(v); os.remove(wav)

def still(fn, t, path): Image.fromarray(fn(t)).save(path)

if __name__ == '__main__':
    what = sys.argv[1]
    if what == 'tiktok':
        for lang in sys.argv[2:]: tiktok(lang, os.path.join(ROOT, f'tiktok-{lang}.mp4'))
    elif what == 'quiz':          # python video/render.py quiz <slug> pt en zh
        for lang in sys.argv[3:]: quiz(sys.argv[2], lang, os.path.join(ROOT, f'quiz-{sys.argv[2]}-{lang}.mp4'))
    elif what == 'previa':        # python video/render.py previa  -> PNGs pra conferir o visual
        tiktok('pt', os.path.join(ROOT, 'p-tiktok.mp4'), preview=[1.0, 6.5, 8.6, 11.0])
        story('pt', 'M', os.path.join(ROOT, 'p-story.mp4'), preview=[3.0])
        quiz('churrasco', 'pt', os.path.join(ROOT, 'p-quiz.mp4'), preview=[1.5, 5.8, 9.5, 12.0])
    elif what == 'stories':
        os.makedirs(os.path.join(SITE, 'story'), exist_ok=True)
        for lang in ('pt', 'en', 'zh'):
            for k in 'ADMFS': story(lang, k, os.path.join(SITE, 'story', f'{k.lower()}-{lang}.mp4'))
