"""Vídeos do whatsyourbubble: TikTok (todos os mascotes) e stories animados por perfil."""
import io, re, math, os, subprocess, sys, wave, struct, random
import numpy as np, cairosvg
from PIL import Image, ImageDraw, ImageFont

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
def tiktok(lang, out, preview=None):
    u, N = UI[lang], NAMES[lang]
    order = ['A', 'D', 'M', 'F']
    T_TITLE, T0, STEP, T_SECRET, T_END, DUR = 0, 2.2, 1.25, 7.4, 9.4, 12.5
    pos = {'A': (300, 760), 'D': (780, 760), 'M': (300, 1260), 'F': (780, 1260)}
    def frame(t):
        im = background(t); d = ImageDraw.Draw(im)
        # título: entra e sobe pro topo
        a = ease_out(t / .6); ty = 700 - 480 * ease_out((t - 1.4) / .7)
        if t < T_END:
            text_c(d, ty, u['q'], F(BLACK, 92), (255, 255, 255), 14) if a > 0 else None
        for i, k in enumerate(order):
            ts = T0 + i * STEP
            if T_TITLE <= t and t >= ts and t < T_END:
                s = ease_back((t - ts) / .45); bob = math.sin((t - ts) * 3 + i) * 10
                cx, cy = pos[k]; m = mascot(k, 340 * s)
                paste_c(im, m, cx, cy + bob)
                if t - ts > .25: text_c(d, cy + 190, N[k], F(BLACK, 50), (255, 255, 255), x=cx)
        if T_SECRET <= t < T_END:
            s = ease_back((t - T_SECRET) / .5)
            m = mascot('S', 230 * s).copy()
            if t - T_SECRET < 1.2:  # silhueta antes de revelar
                al = m.split()[3]; sil = Image.new('RGBA', m.size, (60, 68, 92, 255)); sil.putalpha(al); m = sil
            paste_c(im, m, W / 2, 1660)
            text_c(d, 1800, u['secret'], F(BLACK, 46), (242, 178, 59))
        if t >= T_END:
            e = t - T_END
            text_c(d, 260, u['cta'], F(BLACK, 120), (255, 255, 255))
            row = [('A', 190, 820), ('D', 400, 760), ('S', 540, 980), ('M', 680, 760), ('F', 890, 820)]
            for i, (kk, x, y) in enumerate(row):
                sz = 300 if kk == 'S' else 230
                sc = ease_back((e - i * .08) / .45)
                if sc > 0: paste_c(im, mascot(kk, sz * sc), x, y + math.sin(e * 3 + i) * 12)
            text_c(d, 1330, 'whatsyourbubble.com', F(BLACK, 76), (124, 144, 255))
            text_c(d, 1450, '8 perguntas · 1 minuto' if lang == 'pt' else ('8 questions · 1 minute' if lang == 'en' else '8道题 · 1分钟'), F(REG, 48), (201, 209, 230))
        return np.asarray(im)
    if preview: [still(frame, t, f'{out[:-4]}-{t}.png') for t in preview]; return
    v = out[:-4] + '.v.mp4'; encode(v, frame, int(DUR * FPS))
    wav = out[:-4] + '.wav'; pops_wav(wav, [(T0 + i * STEP, 520 + i * 60) for i in range(4)] + [(T_SECRET, 700), (T_SECRET + 1.2, 900), (T_END, 600)], DUR)
    mux(v, wav, out); os.remove(v); os.remove(wav)

# ---------- Story animado por perfil ----------
def story(lang, k, out, preview=None):
    u, N, Lx = UI[lang], NAMES[lang], LINES[lang]; col = COL[k]; DUR = 6.0
    conf = [(random.uniform(0, W), random.uniform(-200, 600), random.uniform(-6, 6), random.uniform(6, 16), random.choice([col, col, (255, 255, 255), (242, 178, 59)]), random.random() < .35) for _ in range(90)]
    def frame(t):
        im = background(t); d = ImageDraw.Draw(im)
        text_c(d, 220, u['me'], F(REG, 64), (201, 209, 230))
        s = ease_back((t - .3) / .6); bob = math.sin(t * 3) * 14
        if s > 0: paste_c(im, mascot(k, 640 * s), W / 2, 760 + bob)
        if t > .9:
            a = ease_out((t - .9) / .4); y = 1180 + 40 * (1 - a)
            text_c(d, y, N[k], F(BLACK, 112), (255, 255, 255))
            text_c(d, y + 160, wrap(d, Lx[k], F(REG, 54), 900), F(REG, 54), col, 12)
        if t > 1.0:  # chuva de bolhas/confete
            el = t - 1.0
            for x, y0, vx, r, c, b in conf:
                x2 = x + vx * el * 30; y2 = y0 + el * 420 + el * el * 120
                if y2 > H + 40: continue
                if b: d.ellipse([x2 - r * 1.6, y2 - r * 1.6, x2 + r * 1.6, y2 + r * 1.6], outline=c, width=4)
                else: d.rectangle([x2 - r, y2 - r * .45, x2 + r, y2 + r * .45], fill=c)
        if t > 2.2:
            text_c(d, 1600, u['you'], F(BLACK, 80), (255, 255, 255))
            text_c(d, 1720, 'whatsyourbubble.com', F(BLACK, 58), (124, 144, 255))
        return np.asarray(im)
    if preview: [still(frame, t, f'{out[:-4]}-{t}.png') for t in preview]; return
    v = out[:-4] + '.v.mp4'; encode(v, frame, int(DUR * FPS))
    wav = out[:-4] + '.wav'; pops_wav(wav, [(.35, 560)], DUR)
    mux(v, wav, out); os.remove(v); os.remove(wav)

def still(fn, t, path): Image.fromarray(fn(t)).save(path)

if __name__ == '__main__':
    what = sys.argv[1]
    if what == 'tiktok':
        for lang in sys.argv[2:]: tiktok(lang, os.path.join(ROOT, f'tiktok-{lang}.mp4'))
    elif what == 'stories':
        os.makedirs(os.path.join(SITE, 'story'), exist_ok=True)
        for lang in ('pt', 'en', 'zh'):
            for k in 'ADMFS': story(lang, k, os.path.join(SITE, 'story', f'{k.lower()}-{lang}.mp4'))
