# Regras dos quizzes do whatsyourbubble

Valem pra todo quiz novo, feito à mão ou pela tarefa automática. Os quizzes entram no ar sozinhos, então estas regras são o filtro.

## Conteúdo

- **Nunca:** política, religião, racismo ou qualquer discriminação (gênero, origem, corpo, idade, classe, orientação), nem temas pesados (doença, morte, tragédia, violência, armas, drogas, sexo).
- **Tom:** leve, do dia a dia. Rolê, trabalho, família, viagem, comida, jogos, pets, celular, estudos, esporte, casa.
- **Humor com a situação, nunca com um grupo de pessoas.** Os 4 perfis são sempre elogios, ninguém sai "perdendo".
- **Na dúvida, não.** Tema em zona cinzenta é descartado e trocado por outro.
- Sem marcas, pessoas famosas ou personagens reais.

## Formato (`quizzes/<slug>.json`)

- `slug`: minúsculas, números e hífen (ex.: `churrasco`, `grupo-da-familia`). O arquivo tem o mesmo nome.
- Três idiomas: `pt`, `en`, `zh`, cada um com:
  - `titulo` (até 60 caracteres) e `lead` (uma frase convidando).
  - `T`: os 5 perfis `A`, `D`, `M`, `F`, `S`, cada um com 6 textos, nesta ordem: nome (fixo), frase curta (até 70 caracteres), descrição, superpoder, cuidado com, bordão.
  - `S2`: uma frase por perfil (`A`, `D`, `M`, `F`) pra "seu segundo lado é…".
  - `Q`: 8 perguntas, cada uma `[pergunta, {"A": ..., "D": ..., "M": ..., "F": ...}]`.
- Os perfis mantêm a personalidade em qualquer tema:
  - **Arquiteto:** planeja antes.
  - **Detetive:** confere tudo.
  - **Maestro:** cuida de várias coisas ao mesmo tempo.
  - **Finalizador:** resolve e entrega.
  - **Bolha Secreta:** se adapta a cada situação.

Antes de subir: `python scripts/validar_quiz.py quizzes/<slug>.json`. Depois do commit, o quiz fica em `https://whatsyourbubble.com/q/?s=<slug>`.
