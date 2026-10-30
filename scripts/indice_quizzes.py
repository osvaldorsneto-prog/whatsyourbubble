#!/usr/bin/env python3
"""Gera quizzes/index.json, a lista que a vitrine e o "próximo quiz" usam.

Rodar sempre que entrar ou mudar um quiz: python scripts/indice_quizzes.py
Com --conferir, só verifica se o arquivo está em dia (usado no workflow).
"""
import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / 'quizzes' / 'index.json'

# O quiz original de IA mora em /ai/, fora do motor.
IA = {
    'slug': 'ia', 'url': '/ai/', 'criado_em': '2026-10-29', 'categoria': 'celular', 'mascote': 'A',
    'titulo': {'pt': 'Que tipo de usuário de IA é você?', 'en': 'What kind of AI user are you?', 'zh': '你是哪种AI用户？'},
}


def gerar():
    lista = [IA]
    for p in sorted((ROOT / 'quizzes').glob('*.json')):
        if p.name == 'index.json':
            continue
        q = json.loads(p.read_text(encoding='utf-8'))
        lista.append({
            'slug': q['slug'], 'url': f"/q/?s={q['slug']}", 'criado_em': q['criado_em'],
            'categoria': q['categoria'], 'mascote': q['mascote'],
            'titulo': {l: q[l]['titulo'] for l in ('pt', 'en', 'zh')},
        })
    lista.sort(key=lambda x: (x['criado_em'], x['slug']), reverse=True)
    return json.dumps({'quizzes': lista}, ensure_ascii=False, indent=1) + '\n'


def main(args):
    novo = gerar()
    if '--conferir' in args:
        if not OUT.exists() or OUT.read_text(encoding='utf-8') != novo:
            print('quizzes/index.json desatualizado: rode python scripts/indice_quizzes.py')
            return 1
        print('quizzes/index.json em dia')
        return 0
    OUT.write_text(novo, encoding='utf-8')
    print(f'{OUT.relative_to(ROOT)}: {novo.count(chr(34) + "slug" + chr(34))} quizzes')
    return 0


if __name__ == '__main__':
    sys.exit(main(sys.argv[1:]))
