#!/usr/bin/env python3
"""Gera as imagens de prévia (og:image) genéricas dos quizzes em q/og/.

Servem pra qualquer quiz temático: mostram só o mascote e o nome do perfil,
sem falar de um tema específico. O título do quiz aparece no texto da prévia.
Uso: python scripts/og_generico.py
"""
import os
import sys

from PIL import Image, ImageDraw

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, os.path.join(ROOT, 'video'))
from render import BLACK, COL, F, NAMES, REG, mascot  # noqa: E402

OUT = os.path.join(ROOT, 'q', 'og')
W, H = 1200, 630
BG = (15, 20, 34)
LINE = {'pt': 'Qual é a sua bolha?', 'en': "What's your bubble?", 'zh': '你是哪个泡泡？'}


def circles(d, col):
    for x, y, r in [(90, 90, 48), (1120, 110, 78), (60, 560, 34), (1080, 560, 60)]:
        d.ellipse([x - r, y - r, x + r, y + r], outline=col, width=4)


def perfil(k):
    im = Image.new('RGB', (W, H), BG)
    d = ImageDraw.Draw(im)
    circles(d, COL[k])
    m = mascot(k, 420)
    im.paste(m, (90, 105), m)
    x = 560
    size = 70
    while d.textlength(NAMES['en'][k], font=F(BLACK, size)) > W - x - 40:
        size -= 2
    d.text((x, 175 + (70 - size) // 2), NAMES['en'][k], font=F(BLACK, size), fill='white')
    d.text((x, 270), f"{NAMES['pt'][k]}  ·  {NAMES['zh'][k]}", font=F(REG, 38), fill=(201, 209, 230))
    d.text((x, 370), LINE['en'], font=F(BLACK, 46), fill='white')
    d.text((x, 432), f"{LINE['pt']}  ·  {LINE['zh']}", font=F(REG, 32), fill=COL[k])
    d.text((x, 520), 'whatsyourbubble.com', font=F(REG, 32), fill=(154, 164, 187))
    return im


def capa():
    im = Image.new('RGB', (W, H), BG)
    d = ImageDraw.Draw(im)
    for n, k in enumerate('ADMF'):
        m = mascot(k, 220)
        im.paste(m, (70 + n * 270, 70), m)
    def c(y, s, font, fill):
        w = d.textlength(s, font=font)
        d.text(((W - w) / 2, y), s, font=font, fill=fill)
    c(330, LINE['en'], F(BLACK, 76), 'white')
    c(440, f"{LINE['pt']}  ·  {LINE['zh']}", F(REG, 40), (201, 209, 230))
    c(530, 'whatsyourbubble.com', F(REG, 32), (154, 164, 187))
    return im


def main():
    os.makedirs(OUT, exist_ok=True)
    for k in 'ADMFS':
        perfil(k).save(os.path.join(OUT, f'{k.lower()}.png'), optimize=True)
    capa().save(os.path.join(OUT, 'default.png'), optimize=True)
    print('ok:', sorted(os.listdir(OUT)))


if __name__ == '__main__':
    main()
