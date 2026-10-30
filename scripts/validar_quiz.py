#!/usr/bin/env python3
"""Confere se um quiz em quizzes/<slug>.json está no formato que o motor espera.

Uso: python scripts/validar_quiz.py quizzes/churrasco.json [outros...]
Sem argumentos, confere todos os quizzes. Sai com erro se algum falhar.
"""
import json
import re
import sys
import unicodedata
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
LANGS = ('pt', 'en', 'zh')
KEYS = ('A', 'D', 'M', 'F')
NAMES = {
    'pt': {'A': 'O Arquiteto', 'D': 'O Detetive', 'M': 'O Maestro', 'F': 'O Finalizador', 'S': 'A Bolha Secreta'},
    'en': {'A': 'The Architect', 'D': 'The Detective', 'M': 'The Maestro', 'F': 'The Finisher', 'S': 'The Secret Bubble'},
    'zh': {'A': '建筑师', 'D': '侦探', 'M': '指挥家', 'F': '终结者', 'S': '秘密泡泡'},
}
SLUG = re.compile(r'^[a-z0-9]+(-[a-z0-9]+)*$')
CATEGORIAS = ('role', 'trabalho', 'familia', 'viagem', 'comida', 'jogos', 'pets', 'celular', 'estudos', 'casa', 'esporte')
RESERVADOS = ('ia', 'index')
# Rede de segurança simples das regras de conteúdo (quizzes/REGRAS.md).
# Não substitui o bom senso de quem escreve o quiz.
# Termos com * no fim pegam qualquer palavra que comece assim; os outros, só a palavra inteira.
PROIBIDAS = [
    'politic*', 'polític*', 'eleiç*', 'eleic*', 'partido', 'presidente', 'governo', 'esquerda', 'direita',
    'bolsonar*', 'lula', 'election*', 'democrat*', 'republican*', 'trump',
    'religi*', 'igreja*', 'church*', 'deus', 'god', 'jesus', 'bíblia', 'biblia', 'bible', 'reza', 'pray*',
    'raça', 'racis*', 'race', 'negro', 'gay', 'trans', 'gordo*', 'gorda*', 'fat', 'velho*', 'pobre*', 'poor',
    'morte', 'morrer', 'death', 'die', 'suicid*', 'doença*', 'disease*', 'câncer', 'cancer', 'arma', 'armas',
    'gun', 'guns', 'weapon*', 'droga*', 'drug*', 'bêbad*', 'drunk', 'sexo', 'sex', 'sexy',
    '政治', '选举', '宗教', '上帝', '种族', '死亡', '毒品',
]


def norm(s):
    return unicodedata.normalize('NFC', s).lower()


def erros_do_quiz(path):
    e = []
    try:
        q = json.loads(Path(path).read_text(encoding='utf-8'))
    except Exception as ex:  # noqa: BLE001
        return [f'JSON inválido: {ex}']
    slug = q.get('slug', '')
    if not SLUG.match(slug) or len(slug) > 40:
        e.append(f'slug inválido: {slug!r}')
    if slug in RESERVADOS:
        e.append(f'slug reservado: {slug}')
    if q.get('categoria') not in CATEGORIAS:
        e.append(f'categoria precisa ser uma de: {", ".join(CATEGORIAS)}')
    if q.get('mascote') not in KEYS:
        e.append('mascote precisa ser A, D, M ou F (é o que aparece no cartão da vitrine)')
    if not re.match(r'^\d{4}-\d{2}-\d{2}$', str(q.get('criado_em', ''))):
        e.append('criado_em precisa ser uma data AAAA-MM-DD')
    if Path(path).stem != slug:
        e.append(f'o nome do arquivo precisa ser {slug}.json')
    textos = []
    for l in LANGS:
        d = q.get(l)
        if not isinstance(d, dict):
            e.append(f'{l}: falta o idioma')
            continue
        for campo in ('titulo', 'lead'):
            if not isinstance(d.get(campo), str) or not d[campo].strip():
                e.append(f'{l}.{campo}: vazio')
            else:
                textos.append(d[campo])
        if len(d.get('titulo', '')) > 60:
            e.append(f'{l}.titulo: mais de 60 caracteres')
        T = d.get('T', {})
        for k in KEYS + ('S',):
            t = T.get(k)
            if not (isinstance(t, list) and len(t) == 6 and all(isinstance(x, str) and x.strip() for x in t)):
                e.append(f'{l}.T.{k}: precisa ter 6 textos (nome, frase, descrição, superpoder, cuidado, bordão)')
                continue
            if t[0] != NAMES[l][k]:
                e.append(f'{l}.T.{k}: o nome tem que ser "{NAMES[l][k]}"')
            if len(t[1]) > 70:
                e.append(f'{l}.T.{k}: a frase passa de 70 caracteres (não cabe no selo)')
            textos += t
        S2 = d.get('S2', {})
        for k in KEYS:
            if not isinstance(S2.get(k), str) or not S2[k].strip():
                e.append(f'{l}.S2.{k}: vazio')
            else:
                textos.append(S2[k])
        Q = d.get('Q', [])
        if len(Q) != 8:
            e.append(f'{l}.Q: precisa ter 8 perguntas (tem {len(Q)})')
        for n, item in enumerate(Q, 1):
            if not (isinstance(item, list) and len(item) == 2 and isinstance(item[0], str) and isinstance(item[1], dict)):
                e.append(f'{l}.Q{n}: formato [pergunta, {{A, D, M, F}}]')
                continue
            ops = item[1]
            if sorted(ops) != sorted(KEYS) or not all(isinstance(ops[k], str) and ops[k].strip() for k in KEYS):
                e.append(f'{l}.Q{n}: precisa das 4 opções A, D, M, F')
                continue
            if len({norm(ops[k]) for k in KEYS}) < 4:
                e.append(f'{l}.Q{n}: opções repetidas')
            textos += [item[0]] + list(ops.values())
    tudo = norm(' '.join(textos))
    for p in PROIBIDAS:
        if not p.isascii() and not re.search(r'[a-zà-ÿ]', p):  # chinês: busca simples
            achou = p in tudo
        else:
            fim = r'\w*' if p.endswith('*') else r'\b'
            achou = re.search(r'(?<!\w)' + re.escape(p.rstrip('*')) + fim, tudo)
        if achou:
            e.append(f'tema proibido pelas regras: contém "{p.rstrip("*")}"')
    return e


def main(args):
    arquivos = args or sorted(str(p) for p in (ROOT / 'quizzes').glob('*.json') if p.name != 'index.json')
    falhou = False
    for a in arquivos:
        e = erros_do_quiz(a)
        if e:
            falhou = True
            print(f'✗ {a}')
            for x in e:
                print(f'   - {x}')
        else:
            print(f'✓ {a}')
    return 1 if falhou else 0


if __name__ == '__main__':
    sys.exit(main(sys.argv[1:]))
