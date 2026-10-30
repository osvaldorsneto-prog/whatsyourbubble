#!/usr/bin/env python3
"""Salva os números do painel público em data/painel.json.

Lê https://whatsyourbubble.com/ai/painel-7q3x.php e converte os cartões e
tabelas em JSON, pra que as tarefas automáticas leiam os números pelo GitHub.
"""
import json
import re
import sys
import urllib.request
from datetime import datetime, timezone
from html.parser import HTMLParser
from pathlib import Path

URL = "https://whatsyourbubble.com/ai/painel-7q3x.php"
OUT = Path(__file__).resolve().parent.parent / "data" / "painel.json"


class Painel(HTMLParser):
    def __init__(self):
        super().__init__()
        self.secao = "Resumo"
        self.secoes = {}  # secao -> {"cartoes": [...], "linhas": [...]}
        self.tag = None
        self.buf = ""
        self.cartao = None
        self.linha = None

    def _sec(self):
        return self.secoes.setdefault(self.secao, {"cartoes": [], "linhas": []})

    def handle_starttag(self, tag, attrs):
        cls = dict(attrs).get("class", "")
        if tag == "div" and cls == "c":
            self.cartao = []
        elif tag == "tr":
            self.linha = []
        if tag in ("h2", "b", "span", "td", "th"):
            self.tag, self.buf = tag, ""

    def handle_data(self, data):
        if self.tag:
            self.buf += data

    def handle_endtag(self, tag):
        if tag != self.tag:
            return
        txt = self.buf.strip()
        self.tag = None
        if tag == "h2":
            self.secao = txt
        elif tag in ("b", "span") and self.cartao is not None:
            self.cartao.append(txt)
            if len(self.cartao) == 2:
                self._sec()["cartoes"].append(self.cartao)
                self.cartao = None
        elif tag in ("td", "th") and self.linha is not None:
            self.linha.append(txt)


def num(s):
    try:
        return int(s)
    except ValueError:
        return s


def parse(html):
    p = Painel()
    # fecha linhas de tabela ao ver </tr>
    orig_end = p.handle_endtag

    def end(tag):
        orig_end(tag)
        if tag == "tr" and p.linha is not None:
            p._sec()["linhas"].append(p.linha)
            p.linha = None

    p.handle_endtag = end
    p.feed(html)
    p.close()

    def cartoes(nome):
        # "começaram (11%)" -> "começaram": chave estável, sem a porcentagem
        return {re.sub(r"\s*\(.*\)$", "", lbl): num(v) for v, lbl in p.secoes.get(nome, {}).get("cartoes", [])}

    def tabela(nome):
        linhas = p.secoes.get(nome, {}).get("linhas", [])
        if not linhas:
            return []
        cab = linhas[0]
        return [{c: num(v) for c, v in zip(cab, l)} for l in linhas[1:]]

    resumo = cartoes("Resumo")
    if not resumo:
        raise ValueError("painel sem os cartões de resumo; o HTML mudou?")
    return {
        "fonte": URL,
        "atualizado_em": datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
        "resumo": resumo,
        "perfis": cartoes("Perfis"),
        "compartilhamento": {r["Canal"]: r["Toques"] for r in tabela("Compartilhamento")},
        "idiomas": cartoes("Idiomas"),
        "por_quiz": tabela("Por quiz"),
        "por_dia": tabela("Por dia"),
    }


def main():
    req = urllib.request.Request(URL, headers={"User-Agent": "wyb-painel-snapshot"})
    with urllib.request.urlopen(req, timeout=30) as r:
        html = r.read().decode("utf-8")
    dados = parse(html)
    OUT.parent.mkdir(exist_ok=True)
    antigo = json.loads(OUT.read_text()) if OUT.exists() else {}
    if {k: v for k, v in antigo.items() if k != "atualizado_em"} == {
        k: v for k, v in dados.items() if k != "atualizado_em"
    }:
        print("sem mudança")
        return
    OUT.write_text(json.dumps(dados, ensure_ascii=False, indent=2) + "\n")
    print(json.dumps(dados, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    sys.exit(main())
