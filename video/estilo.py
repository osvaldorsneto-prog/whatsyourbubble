"""Estilo "adesivo colorido" dos vídeos, igual ao do site: fundo em degradê rosa/lilás/azul,
bolhas e brilhos flutuando, caixas com contorno grosso e sombra colorida, raios atrás dos mascotes."""
import math
import random

import numpy as np
from PIL import Image, ImageDraw

W, H = 1080, 1920
INK = (20, 27, 46)
WHITE = (255, 255, 255)
PINK, CYAN, LIME, VIOLET, GOLD = (255, 92, 138), (18, 181, 203), (124, 199, 28), (140, 108, 245), (255, 201, 60)
POS = [PINK, CYAN, LIME, VIOLET]          # cores das opções (pela posição, nunca pelo perfil)
DARK = {'A': ((20, 28, 92), (34, 50, 143)), 'D': ((90, 21, 17), (142, 42, 35)),
        'M': ((8, 61, 48), (17, 101, 79)), 'F': ((82, 56, 7), (134, 96, 13))}


def _base():
    """Degradê do fundo: base lilás clara com manchas rosa, azul e lilás (como o site)."""
    y, x = np.mgrid[0:H, 0:W].astype(np.float32)
    t = (x / W * .35 + y / H * .65)[..., None]
    a, b, c = np.array([255, 240, 247.]), np.array([241, 235, 255.]), np.array([227, 247, 255.])
    img = np.where(t < .55, a + (b - a) * (t / .55), b + (c - b) * ((t - .55) / .45))
    for cx, cy, r, col in [(.15, .08, .45, (255, 209, 232)), (.9, .3, .5, (201, 241, 255)), (.25, .92, .55, (228, 214, 255))]:
        d = np.sqrt((x - cx * W) ** 2 + (y - cy * H) ** 2) / (r * W)
        k = np.clip(1 - d, 0, 1)[..., None] ** 1.5
        img = img * (1 - k) + np.array(col, np.float32) * k
    return Image.fromarray(img.clip(0, 255).astype(np.uint8))


BASE = _base()
_rng = random.Random(11)
BUBBLES = [(_rng.uniform(0, W), _rng.uniform(0, H), _rng.uniform(26, 110), _rng.uniform(.4, 1.1), _rng.choice([PINK, CYAN, VIOLET]))
           for _ in range(16)]
SPARKS = [(_rng.uniform(40, W - 40), _rng.uniform(80, H - 80), _rng.uniform(14, 30), _rng.uniform(0, 6), _rng.choice([GOLD, PINK, CYAN, VIOLET]))
          for _ in range(14)]


def star(d, cx, cy, r, fill):
    p = []
    for i in range(8):
        a = i * math.pi / 4
        rr = r if i % 2 == 0 else r * .32
        p.append((cx + math.cos(a) * rr, cy + math.sin(a) * rr))
    d.polygon(p, fill=fill)


def background(t):
    im = BASE.copy()
    d = ImageDraw.Draw(im)
    for x, y, r, sp, c in BUBBLES:
        yy = (y - t * 70 * sp) % (H + 300) - 150
        soft = tuple(int(v * .55 + 255 * .45) for v in c)
        d.ellipse([x - r, yy - r, x + r, yy + r], outline=soft, width=6)
    for x, y, r, ph, c in SPARKS:
        s = .35 + .65 * (0.5 + 0.5 * math.sin(t * 3 + ph))
        star(d, x, y, r * s, c)
    return im


def rays(size, color, t, n=22):
    """Raios girando (como no site), em camada RGBA do tamanho pedido."""
    im = Image.new('RGBA', (size, size), (0, 0, 0, 0))
    d = ImageDraw.Draw(im)
    c = size / 2
    rot = t * 30
    for i in range(n):
        a0 = math.radians(rot + i * 360 / n)
        a1 = a0 + math.radians(360 / n * .35)
        d.polygon([(c + math.cos(a0) * c * .55, c + math.sin(a0) * c * .55), (c + math.cos(a0) * c, c + math.sin(a0) * c),
                   (c + math.cos(a1) * c, c + math.sin(a1) * c), (c + math.cos(a1) * c * .55, c + math.sin(a1) * c * .55)],
                  fill=color + (150,))
    return im


def sticker(w, h, fill=WHITE, shadow=PINK, radius=46, border=9, off=16, grad=None):
    """Caixa estilo adesivo: contorno grosso INK e sombra deslocada colorida. Devolve RGBA (w+off, h+off)."""
    im = Image.new('RGBA', (w + off, h + off), (0, 0, 0, 0))
    d = ImageDraw.Draw(im)
    d.rounded_rectangle([off, off, w + off - 1, h + off - 1], radius, fill=shadow + (255,))
    if grad:
        g = Image.new('RGBA', (w, h))
        a, b = np.array(grad[0], float), np.array(grad[1], float)
        yy, xx = np.mgrid[0:h, 0:w]
        k = ((xx / w + yy / h) / 2)[..., None]
        g = Image.fromarray(np.concatenate([(a + (b - a) * k).astype(np.uint8), np.full((h, w, 1), 255, np.uint8)], 2), 'RGBA')
        m = Image.new('L', (w, h), 0)
        ImageDraw.Draw(m).rounded_rectangle([0, 0, w - 1, h - 1], radius, fill=255)
        im.paste(g, (0, 0), m)
        d.rounded_rectangle([0, 0, w - 1, h - 1], radius, outline=INK, width=border)
    else:
        d.rounded_rectangle([0, 0, w - 1, h - 1], radius, fill=fill + (255,), outline=INK, width=border)
    return im


def text_box(d, lines, font, spacing):
    """Largura e altura de um bloco de linhas."""
    ws = [d.textlength(l, font=font) for l in lines]
    asc, desc = font.getmetrics()
    lh = asc + desc
    return max(ws or [0]), lh * len(lines) + spacing * (len(lines) - 1), lh


def sticker_text(text, font, wrap_fn, maxw, fill=WHITE, ink=INK, shadow=PINK, pad=(48, 34), grad=None, align='center', dot=None):
    """Adesivo com texto dentro. dot = cor de uma bolinha à esquerda (opções)."""
    tmp = ImageDraw.Draw(Image.new('RGB', (10, 10)))
    extra = 74 if dot else 0
    lines = wrap_fn(tmp, text, font, maxw - extra).split('\n')
    tw, th, lh = text_box(tmp, lines, font, 12)
    w, h = int(max(tw + extra, 0) + pad[0] * 2), int(th + pad[1] * 2)
    im = sticker(w, h, fill=fill, shadow=shadow, grad=grad)
    d = ImageDraw.Draw(im)
    y = pad[1]
    if dot:
        cy = pad[1] + th / 2
        d.ellipse([pad[0], cy - 26, pad[0] + 52, cy + 26], fill=dot, outline=INK, width=7)
    for l in lines:
        lw = d.textlength(l, font=font)
        x = pad[0] + extra + ((tw - lw) / 2 if align == 'center' else 0)
        d.text((x, y), l, font=font, fill=ink)
        y += lh + 12
    return im


def title_text(d, xy, text, font, fill=WHITE, stroke=INK, sw=10, spacing=18, anchor='ma', shadow=PINK):
    """Título estilo anime: contorno escuro e sombra colorida deslocada."""
    sw = max(4, int(font.size * .07))
    x, y = xy
    o = max(5, int(font.size * .08))
    d.multiline_text((x + o, y + o), text, font=font, fill=shadow, stroke_width=sw, stroke_fill=shadow, anchor=anchor, align='center', spacing=spacing)
    d.multiline_text(xy, text, font=font, fill=fill, stroke_width=sw, stroke_fill=stroke, anchor=anchor, align='center', spacing=spacing)


def scaled(im, s, angle=0):
    if s <= 0.01:
        return None
    w, h = max(1, int(im.width * s)), max(1, int(im.height * s))
    out = im.resize((w, h), Image.LANCZOS)
    return out.rotate(angle, resample=Image.BICUBIC, expand=True) if angle else out
