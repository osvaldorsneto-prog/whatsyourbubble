<?php
// Vitrine: página inicial com todos os quizzes. Incluída por q/index.php quando não há ?s=.
// A lista vem de quizzes/index.json no GitHub, então quiz novo aparece sem publicar nada.
$lista = wyb_indice();
$hoje = gmdate('Y-m-d');
$data = json_encode(['quizzes' => $lista, 'hoje' => $hoje], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>What's your bubble? · Qual é a sua bolha?</title>
<meta name="description" content="Quizzes de 1 minuto. As mesmas 4 bolhas, um tema novo todo dia.">
<meta property="og:type" content="website">
<meta property="og:title" content="What's your bubble? · Qual é a sua bolha?">
<meta property="og:description" content="Quizzes de 1 minuto. As mesmas 4 bolhas, um tema novo todo dia. · 1-minute quizzes, a new theme every day.">
<meta property="og:image" content="https://whatsyourbubble.com/q/og/default.png">
<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">
<meta property="og:url" content="https://whatsyourbubble.com/">
<meta name="twitter:card" content="summary_large_image">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,800&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&family=Noto+Sans+SC:wght@400;700&display=swap">
<style>
:root{
  --bg:#E9EDF3; --surface:#FFFFFF; --ink:#141B2E; --muted:#5A6378; --line:#D3D9E4;
  --accent:#2F4BE8; --accent-ink:#FFFFFF; --warm:#F2A93B;
  --cA:#2F4BE8; --cD:#D9463B; --cM:#1E9E7A; --cF:#C98A12;
  --display:"Bricolage Grotesque","Noto Sans SC","Segoe UI",system-ui,sans-serif;
  --body:"IBM Plex Sans","Noto Sans SC","Segoe UI",system-ui,sans-serif;
  --mono:"IBM Plex Mono",ui-monospace,Consolas,monospace;
  color-scheme:light;
}
@media (prefers-color-scheme: dark){:root{
  --bg:#0F1422; --surface:#182033; --ink:#E8ECF5; --muted:#9AA4BB; --line:#2A3550;
  --accent:#7C90FF; --accent-ink:#0F1422; --warm:#F2B657; color-scheme:dark }}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font-family:var(--body);font-size:clamp(17px,4.4vw,19px);line-height:1.45;
  padding-left:max(16px,env(safe-area-inset-left,0px));padding-right:max(16px,env(safe-area-inset-right,0px));
  padding-block:calc(14px + env(safe-area-inset-top,0px)) calc(28px + env(safe-area-inset-bottom,0px))}
a{color:inherit;text-decoration:none}
svg{width:100%;height:100%;display:block}
.top{max-width:620px;margin:0 auto 14px;display:flex;justify-content:space-between;align-items:center;gap:12px}
.brand{font-family:var(--display);font-weight:800;font-size:clamp(20px,5.4vw,24px)}
.brand b{color:var(--accent)}
.langs{display:flex;gap:4px}
.langs button{background:transparent;border:1px solid var(--line);color:var(--muted);border-radius:8px;padding:0 14px;min-height:44px;min-width:48px;font:500 15px var(--mono);cursor:pointer}
.langs button[aria-pressed="true"]{background:var(--ink);color:var(--bg);border-color:var(--ink)}
main{max-width:620px;margin:0 auto;display:flex;flex-direction:column}
.hero{text-align:center;display:flex;flex-direction:column;gap:8px;align-items:center}
.row4{display:flex;gap:6px}.row4 div{width:clamp(54px,15vw,72px);height:clamp(54px,15vw,72px)}
h1{font-family:var(--display);font-weight:800;font-size:clamp(34px,9.5vw,52px);line-height:1.04;margin:4px 0 0;text-wrap:balance}
h1 em{font-style:normal;color:var(--accent)}
.lead{margin:0;color:var(--muted);max-width:40ch}
h2{font-family:var(--display);font-size:clamp(21px,5.6vw,25px);font-weight:800;margin:26px 0 10px;display:flex;justify-content:space-between;align-items:baseline;gap:8px}
h2 small{font:600 14px var(--body);color:var(--muted)}
.novos{display:flex;gap:10px;overflow-x:auto;scroll-snap-type:x mandatory;margin-right:calc(-1 * max(16px,env(safe-area-inset-right,0px)));padding-bottom:4px}
.novo{flex:0 0 min(78%,300px);scroll-snap-align:start;background:var(--c);color:#fff;border-radius:22px;padding:16px;display:flex;flex-direction:column;gap:6px;position:relative;min-height:210px}
.novo .m{width:88px;height:88px;position:absolute;right:10px;top:10px;filter:drop-shadow(0 4px 6px rgba(0,0,0,.2))}
.pill{font:500 12px var(--mono);letter-spacing:.08em;background:rgba(255,255,255,.22);border-radius:6px;padding:3px 8px;align-self:flex-start}
.novo b{font-family:var(--display);font-size:clamp(21px,5.6vw,24px);line-height:1.12;margin-top:auto;padding-right:4px}
.novo .meta{color:rgba(255,255,255,.88)}
.meta{font-size:14px;color:var(--muted)}
.col{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:6px}
.col a,.col div{display:flex;flex-direction:column;align-items:center;gap:4px;font-size:12px;font-weight:600;color:var(--muted);text-align:center;line-height:1.2}
.col .m{width:clamp(44px,12vw,56px);height:clamp(44px,12vw,56px);display:grid;place-items:center}
.q{width:88%;aspect-ratio:1;border-radius:50%;border:2px dashed var(--muted);display:grid;place-items:center;font-weight:800;color:var(--muted);opacity:.7}
.lista{display:flex;flex-direction:column;gap:8px}
.li{display:flex;gap:12px;align-items:center;background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:10px 14px}
.li .n{font-family:var(--display);font-weight:800;color:var(--c);width:18px;font-size:20px}
.li .m{width:46px;height:46px;flex:none}.li b{display:block;font-weight:600;line-height:1.25}
.chips{display:flex;gap:6px;flex-wrap:wrap;margin:26px 0 10px}
.chips button{border:1px solid var(--line);border-radius:999px;padding:0 14px;min-height:40px;font:600 14px var(--body);background:var(--surface);color:var(--ink);cursor:pointer}
.chips button[aria-pressed="true"]{background:var(--ink);color:var(--bg);border-color:var(--ink)}
.grade{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
.g{background:var(--surface);border:1px solid var(--line);border-top:5px solid var(--c);border-radius:16px;padding:12px;display:flex;flex-direction:column;gap:8px}
.g .m{width:48px;height:48px}.g b{font-weight:600;line-height:1.25;font-size:clamp(15px,4vw,17px)}
.secret{margin-top:20px;display:flex;gap:12px;align-items:center;border-radius:18px;padding:14px;border:1px solid var(--line);
  background:linear-gradient(90deg,rgba(47,75,232,.12),rgba(30,158,122,.12),rgba(201,138,18,.12),rgba(217,70,59,.12))}
.secret .m{width:60px;height:60px;flex:none}.secret p{margin:0;font-size:15px}
.fine{font-size:14px;color:var(--muted);margin:22px 0 0;text-align:center}
.novo:focus-visible,.li:focus-visible,.g:focus-visible,.col a:focus-visible,button:focus-visible{outline:3px solid var(--warm);outline-offset:2px}
@media (prefers-reduced-motion:no-preference){.novo,.li,.g{transition:transform .15s}.novo:active,.li:active,.g:active{transform:scale(.98)}}
</style>
</head>
<body>
<header class="top">
  <a class="brand" href="/q/">whatsyour<b>bubble</b></a>
  <div class="langs" id="langs"><button data-l="pt">PT</button><button data-l="en">EN</button><button data-l="zh">中文</button></div>
</header>
<main id="app"></main>
<script>
const DATA=<?= $data ?>;
// mascots.js e secret.js esperam estes nomes globais
const L={pt:{T:{}},en:{T:{}},zh:{T:{}}};const COL={};const HEX={};
</script>
<script src="/ai/mascots.js?v=7"></script>
<script src="/ai/secret.js?v=9"></script>
<script>
(function(){
const X={
 pt:{h1:'Qual é a sua bolha <em>hoje</em>?',lead:'Quizzes de 1 minuto. As mesmas 4 bolhas, um tema novo todo dia.',
  novos:'Novos',hoje:'NOVO HOJE',meta:'8 perguntas · 1 min',jogaram:'{n} jogaram',suas:'Suas bolhas',de:'{a} de {b} temas',
  top:'Mais jogados',todos:'Todos',secret:'<b>Bolha Secreta:</b> só aparece com empate perfeito. Já tirou em algum quiz?',
  fine:'Feito pra diversão. Não é teste científico e não guarda nenhum dado seu.',
  cat:{role:'Rolê',trabalho:'Trabalho',familia:'Família',viagem:'Viagem',comida:'Comida',jogos:'Jogos',pets:'Pets',celular:'Celular',estudos:'Estudos',casa:'Casa',esporte:'Esporte'}},
 en:{h1:'What\'s your bubble <em>today</em>?',lead:'1-minute quizzes. The same 4 bubbles, a new theme every day.',
  novos:'New',hoje:'NEW TODAY',meta:'8 questions · 1 min',jogaram:'{n} played',suas:'Your bubbles',de:'{a} of {b} themes',
  top:'Most played',todos:'All',secret:'<b>Secret Bubble:</b> only shows up with a perfect tie. Have you got it yet?',
  fine:'Just for fun. Not a scientific test, and it stores none of your data.',
  cat:{role:'Going out',trabalho:'Work',familia:'Family',viagem:'Travel',comida:'Food',jogos:'Games',pets:'Pets',celular:'Phone',estudos:'Study',casa:'Home',esporte:'Sport'}},
 zh:{h1:'你<em>今天</em>是哪个泡泡？',lead:'1分钟小测验。同样的4个泡泡，每天一个新主题。',
  novos:'最新',hoje:'今日新题',meta:'8道题 · 1分钟',jogaram:'{n}人玩过',suas:'你的泡泡',de:'{a}/{b}个主题',
  top:'最热门',todos:'全部',secret:'<b>秘密泡泡：</b>只有完美平局才会出现。你测出来过吗？',
  fine:'纯属娱乐，不是科学测试，也不保存你的任何数据。',
  cat:{role:'出去玩',trabalho:'工作',familia:'家庭',viagem:'旅行',comida:'美食',jogos:'游戏',pets:'宠物',celular:'手机',estudos:'学习',casa:'家居',esporte:'运动'}}};
const CC={A:'var(--cA)',D:'var(--cD)',M:'var(--cM)',F:'var(--cF)'};
const app=document.getElementById('app');
const esc=s=>String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
const num=n=>{if(n<1000)return String(n);const v=(n/1000).toFixed(n>=10000?0:1).replace(/\.0$/,'');
 return lang==='pt'?v.replace('.',',')+' mil':lang==='zh'?v+'千':v+'k'};
let lang=(()=>{const p=new URLSearchParams(location.search).get('lang');if(X[p])return p;try{const s=localStorage.getItem('wyb-lang');if(X[s])return s}catch(_){}
  const n=(navigator.language||'').toLowerCase();return n.startsWith('zh')?'zh':n.startsWith('pt')?'pt':'en'})();
let cat='';
const bolhas=(()=>{try{return JSON.parse(localStorage.getItem('wyb-bolhas')||'{}')||{}}catch(_){return{}}})();
const Q=DATA.quizzes;
const titulo=q=>q.titulo[lang]||q.titulo.en;
const mas=k=>(window.MASCOT&&MASCOT[k])||'';
function render(){
 const x=X[lang];
 const novos=Q.slice(0,3);
 const top=Q.filter(q=>q.jogaram>=20).sort((a,b)=>b.jogaram-a.jogaram).slice(0,4);
 const feitos=Q.filter(q=>bolhas[q.slug]);const faltam=Q.filter(q=>!bolhas[q.slug]).slice(0,Math.max(0,6-feitos.length));
 const cats=[...new Set(Q.map(q=>q.categoria))];
 const grade=Q.filter(q=>!cat||q.categoria===cat);
 app.innerHTML=`
 <section class="hero"><div class="row4">${'ADMF'.split('').map(k=>`<div>${mas(k)}</div>`).join('')}</div>
  <h1>${x.h1}</h1><p class="lead">${esc(x.lead)}</p></section>
 <h2>${x.novos}</h2>
 <div class="novos">${novos.map(q=>`<a class="novo" href="${esc(q.url)}" style="--c:${CC[q.mascote]}"><div class="m">${mas(q.mascote)}</div>
   ${q.criado_em===DATA.hoje?`<span class="pill">${x.hoje}</span>`:''}<b>${esc(titulo(q))}</b>
   <span class="meta">${x.meta}${q.jogaram>=20?' · '+x.jogaram.replace('{n}',num(q.jogaram)):''}</span></a>`).join('')}</div>
 ${feitos.length?`<h2>${x.suas} <small>${x.de.replace('{a}',feitos.length).replace('{b}',Q.length)}</small></h2>
 <div class="col">${feitos.slice(0,6).map(q=>`<a href="${esc(q.url)}" title="${esc(titulo(q))}"><div class="m">${mas(bolhas[q.slug])}</div>${esc(x.cat[q.categoria]||'')}</a>`).join('')}
  ${faltam.map(q=>`<a href="${esc(q.url)}" title="${esc(titulo(q))}"><div class="m"><span class="q">?</span></div>${esc(x.cat[q.categoria]||'')}</a>`).join('')}</div>`:''}
 ${top.length>=3?`<h2>${x.top}</h2><div class="lista">${top.map((q,i)=>`<a class="li" href="${esc(q.url)}" style="--c:${CC[q.mascote]}"><span class="n">${i+1}</span><div class="m">${mas(q.mascote)}</div>
   <div><b>${esc(titulo(q))}</b><span class="meta">${x.jogaram.replace('{n}',num(q.jogaram))}</span></div></a>`).join('')}</div>`:''}
 ${cats.length>1?`<div class="chips">${['',...cats].map(c=>`<button data-c="${c}" aria-pressed="${c===cat}">${esc(c?x.cat[c]||c:x.todos)}</button>`).join('')}</div>`:`<h2>${x.todos}</h2>`}
 <div class="grade">${grade.map(q=>`<a class="g" href="${esc(q.url)}" style="--c:${CC[q.mascote]}"><div class="m">${mas(q.mascote)}</div><b>${esc(titulo(q))}</b></a>`).join('')}</div>
 <div class="secret"><div class="m">${mas('S')}</div><p>${x.secret}</p></div>
 <p class="fine">${esc(x.fine)}</p>`;
 app.querySelectorAll('.chips button').forEach(b=>b.onclick=()=>{cat=b.dataset.c;render()});
}
function setLang(l){lang=l;try{localStorage.setItem('wyb-lang',l)}catch(_){}
 document.documentElement.lang={pt:'pt-BR',en:'en',zh:'zh-CN'}[l];
 document.querySelectorAll('#langs button').forEach(b=>b.setAttribute('aria-pressed',b.dataset.l===l));render();}
document.querySelectorAll('#langs button').forEach(b=>b.onclick=()=>setLang(b.dataset.l));
setLang(lang);
})();
</script>
</body>
</html>
