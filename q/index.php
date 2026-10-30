<?php
// Motor dos quizzes temáticos: /q/?s=<slug> carrega quizzes/<slug>.json do GitHub.
// Guarda uma cópia por 10 min fora da pasta pública, pra não depender do GitHub a cada visita.
header('Cache-Control: no-cache');
require __DIR__ . '/lib.php';
$slug = wyb_slug($_GET['s'] ?? '');
if ($slug === '') { require __DIR__ . '/vitrine.php'; exit; }
$quiz = wyb_json("quizzes/$slug.json", $slug, 'wyb_quiz_ok');
if (!$quiz) {
  http_response_code(404);
  echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>What\'s your bubble?</title>'
     . '<body style="font:20px system-ui;padding:24px;background:#0F1422;color:#E8ECF5"><p>Esse quiz não existe (ainda). · This quiz doesn\'t exist (yet).</p>'
     . '<p><a style="color:#7C90FF" href="/q/">whatsyourbubble.com</a></p></body>';
  exit;
}
$h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$r = strtoupper($_GET['r'] ?? '');
$pt = $quiz['pt'];
if (in_array($r, ['A', 'D', 'M', 'F', 'S'], true)) {
  $ogTitle = $pt['T'][$r][0] . ' · ' . $pt['titulo'];
  $ogDesc = $pt['T'][$r][1] . ' ' . $quiz['en']['titulo'];
  $ogImg = strtolower($r);
} else {
  $ogTitle = $pt['titulo'] . ' · ' . $quiz['en']['titulo'];
  $ogDesc = $pt['lead'];
  $ogImg = 'default';
}
$url = "https://whatsyourbubble.com/q/?s=$slug";
$json = json_encode($quiz, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= $h($pt['titulo']) ?> · What's your bubble?</title>
<meta name="description" content="<?= $h($pt['lead']) ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= $h($ogTitle) ?>">
<meta property="og:description" content="<?= $h($ogDesc) ?>">
<meta property="og:image" content="https://whatsyourbubble.com/q/og/<?= $ogImg ?>.png">
<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">
<meta property="og:url" content="<?= $h($url) ?>">
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
  --accent:#7C90FF; --accent-ink:#0F1422; --warm:#F2B657;
  --cA:#7C90FF; --cD:#FF7A6E; --cM:#43C9A2; --cF:#E8B23F; color-scheme:dark }}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font-family:var(--body);font-size:clamp(19px,4.8vw,22px);line-height:1.5;
  padding-left:max(16px,env(safe-area-inset-left,0px));padding-right:max(16px,env(safe-area-inset-right,0px));
  padding-block:calc(14px + env(safe-area-inset-top,0px)) calc(24px + env(safe-area-inset-bottom,0px))}
.top{max-width:620px;margin:0 auto 14px;display:flex;justify-content:space-between;align-items:center;gap:12px}
.brand{font-family:var(--display);font-weight:800;font-size:clamp(20px,5.4vw,24px);color:var(--ink);text-decoration:none}
.brand b{color:var(--accent)}
.langs{display:flex;gap:4px}
.langs button{background:transparent;border:1px solid var(--line);color:var(--muted);border-radius:8px;padding:0 14px;min-height:44px;min-width:48px;font:500 15px var(--mono)}
.langs button[aria-pressed="true"]{background:var(--ink);color:var(--bg);border-color:var(--ink)}
.wrap{max-width:620px;margin:0 auto;display:flex;flex-direction:column;gap:16px}
.tag{font-family:var(--mono);font-size:clamp(14px,3.6vw,16px);letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
h1,h2{font-family:var(--display);text-wrap:balance;margin:0;line-height:1.08}
h1{font-size:clamp(40px,11vw,60px);font-weight:800}
h2{font-size:clamp(30px,8vw,40px);font-weight:800}
p{margin:0}
.lead{color:var(--muted);max-width:60ch}
.card{background:var(--surface);border:1px solid var(--line);border-radius:14px;padding:24px;display:flex;flex-direction:column;gap:16px}
.invite{border-color:var(--warm);border-width:2px}
.types{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.type{display:flex;gap:12px;align-items:center;font-weight:600;font-size:clamp(18px,4.6vw,21px)}
.dot{width:16px;height:16px;border-radius:50%;flex:none}
button{font:inherit;cursor:pointer}
.btn{background:var(--accent);color:var(--accent-ink);border:0;border-radius:14px;padding:0 26px;min-height:64px;font-weight:700;font-size:clamp(19px,5vw,22px)}
.btn.ghost{background:transparent;color:var(--ink);border:1px solid var(--line)}
.btn:focus-visible,.opt:focus-visible,.langs button:focus-visible,input:focus-visible{outline:3px solid var(--warm);outline-offset:2px}
.row{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
.progress{display:flex;gap:4px}
.progress span{flex:1;height:4px;border-radius:2px;background:var(--line)}
.progress span.on{background:var(--accent)}
.bubble{background:var(--surface);border:1px solid var(--line);border-radius:18px 18px 18px 4px;padding:24px 24px;font-size:clamp(25px,6.6vw,32px);font-weight:600;line-height:1.35}
.opts{display:flex;flex-direction:column;gap:14px;align-items:stretch}
.opt{background:transparent;border:1.5px solid var(--line);color:var(--ink);border-radius:18px 18px 4px 18px;padding:20px 22px;min-height:84px;font-size:clamp(20px,5.3vw,25px);font-weight:500;line-height:1.3;text-align:left;transition:border-color .15s,background .15s}
.opt:hover{border-color:var(--accent);background:var(--surface)}
.res-head{display:flex;gap:16px;align-items:center}
.badge{width:84px;height:84px;border-radius:50%;display:grid;place-items:center;font-family:var(--display);font-weight:800;font-size:30px;color:#fff;flex:none}
.bars{display:flex;flex-direction:column;gap:10px}
.bar{display:grid;grid-template-columns:120px 1fr 52px;gap:12px;align-items:center;font-size:17px}
.track{height:14px;border-radius:7px;background:var(--line);overflow:hidden}
.fill{height:100%;border-radius:5px}
.pct{font-family:var(--mono);font-variant-numeric:tabular-nums;text-align:right;color:var(--muted)}
.pair{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.pair div{display:flex;flex-direction:column;gap:4px}
.quote{font-family:var(--display);font-size:clamp(23px,6vw,28px);font-weight:500;border-left:3px solid var(--warm);padding-left:14px}
.vs{display:flex;gap:12px;align-items:center;border-top:1px solid var(--line);padding-top:14px}.vs .mini{width:44px;height:44px;font-size:22px}.vs p{font-size:clamp(17px,4.4vw,20px);line-height:1.35}
.vs .who{display:flex;flex-direction:column;align-items:center;gap:8px;font-weight:600}
.vs .x{font-family:var(--display);font-weight:800;color:var(--muted)}
input[type=text]{font:inherit;font-size:clamp(18px,4.8vw,21px);min-height:56px;padding:12px 16px;border-radius:10px;border:1px solid var(--line);background:var(--bg);color:var(--ink);flex:1;min-width:0}
.fine{font-size:clamp(15px,3.8vw,17px);color:var(--muted)}
.toast{font-size:16px;color:var(--muted)}.toast:empty{display:none}
.btns{display:grid;grid-template-columns:1fr 1fr;gap:10px}.btns .btn{width:100%;padding:0 12px}.btns #sh{grid-column:1/-1}
details summary{cursor:pointer;font-weight:700;min-height:48px;display:flex;align-items:center;list-style:none}details summary::-webkit-details-marker{display:none}details summary::after{content:"＋";margin-left:auto;color:var(--accent)}details[open] summary::after{content:"－"}details>div{display:flex;flex-direction:column;gap:16px;padding-top:8px}
@media (max-width:520px){.pair{grid-template-columns:1fr}.types{gap:10px}.bar{grid-template-columns:100px 1fr 48px;font-size:16px}.row .btn,#go,#again{width:100%}.card{padding:20px}}
@media (prefers-reduced-motion:reduce){*{transition:none!important}}
</style>
<link rel="stylesheet" href="/ai/home.css?v=8">
</head>
<body>
<header class="top">
  <a class="brand" href="/q/">whatsyour<b>bubble</b></a>
  <div class="langs" id="langs">
    <button data-l="pt">PT</button><button data-l="en">EN</button><button data-l="zh">中文</button>
  </div>
</header>
<main class="wrap" id="app"></main>

<script>
const SITE="https://whatsyourbubble.com";
const COL={A:"--cA",D:"--cD",M:"--cM",F:"--cF"};
const QUIZ=<?= $json ?>;
window.QUIZ_ID=<?= json_encode($slug) ?>;window.COUNT_URL='/ai/count.php';window.NO_STORY=true;
const UI={"pt": {"meta": "Quiz rápido · 8 perguntas · 1 minuto", "four": "Os 4 perfis", "go": "Começar", "fine": "Feito pra diversão. Não é teste científico e não guarda nenhum dado seu.", "q": "Pergunta {n} de {t}", "res": "Seu resultado", "power": "Superpoder", "watch": "Cuidado com", "mix": "Sua mistura", "share": "Compartilhe seu selo", "name": "Seu nome (pra comparar)", "shareBtn": "Compartilhar", "copyBtn": "Copiar link", "dl": "Baixar selo", "copied": "Link copiado.", "again": "Refazer o teste", "invite": "{who} é {type}.", "inviteSub": "Faça o teste e veja se vocês estão na mesma bolha.", "vsT": "Comparação", "same": "Mesma bolha! Vocês são iguais nisso.", "diff": "Bolhas diferentes. Juntos, vocês cobrem {a} e {b}.", "shareText": "Sou {type} ({p}%) no quiz \"{title}\". E você?", "you": "Você", "friend": "Seu amigo", "andyou": "E você?", "more": "Ver perfil completo"}, "en": {"meta": "Quick quiz · 8 questions · 1 minute", "four": "The 4 types", "go": "Start", "fine": "Just for fun. Not a scientific test, and it stores none of your data.", "q": "Question {n} of {t}", "res": "Your result", "power": "Superpower", "watch": "Watch out for", "mix": "Your mix", "share": "Share your badge", "name": "Your name (to compare)", "shareBtn": "Share", "copyBtn": "Copy link", "dl": "Download badge", "copied": "Link copied.", "again": "Take it again", "invite": "{who} is {type}.", "inviteSub": "Take the test and see if you're in the same bubble.", "vsT": "Comparison", "same": "Same bubble! You two are alike on this.", "diff": "Different bubbles. Together you cover {a} and {b}.", "shareText": "I'm {type} ({p}%) on \"{title}\". What about you?", "you": "You", "friend": "Your friend", "andyou": "What about you?", "more": "See full profile"}, "zh": {"meta": "快速测验 · 8道题 · 1分钟", "four": "4种类型", "go": "开始", "fine": "纯属娱乐，不是科学测试，也不保存你的任何数据。", "q": "第{n}题，共{t}题", "res": "你的结果", "power": "超能力", "watch": "小心", "mix": "你的组合", "share": "分享你的徽章", "name": "你的名字（用于对比）", "shareBtn": "分享", "copyBtn": "复制链接", "dl": "下载徽章", "copied": "链接已复制。", "again": "再测一次", "invite": "{who}是{type}。", "inviteSub": "来测一测，看看你们是不是在同一个泡泡里。", "vsT": "对比", "same": "同一个泡泡！你们在这方面一模一样。", "diff": "不同的泡泡。你们合起来覆盖了{a}和{b}。", "shareText": "我在「{title}」里是{type}（{p}%）。你呢？", "you": "你", "friend": "你的朋友", "andyou": "你呢？", "more": "查看完整档案"}};
const L={};
for(const l of ['pt','en','zh']){const q=QUIZ[l];
 L[l]={ui:Object.assign({},UI[l],{h1:q.titulo,lead:q.lead,shareText:UI[l].shareText.replace('{title}',q.titulo)}),
  T:{A:q.T.A,D:q.T.D,M:q.T.M,F:q.T.F},Q:q.Q,S2:q.S2};}

const app=document.getElementById('app');
const params=new URLSearchParams(location.search);
const friend=(()=>{const r=(params.get('r')||'').toUpperCase();return (COL[r]||r==='S')?{r,name:(params.get('de')||'').slice(0,30)}:null})();
let lang=(()=>{const p=params.get('lang');if(L[p])return p;try{const s=localStorage.getItem('wyb-lang');if(L[s])return s}catch(_){}
  const n=(navigator.language||'').toLowerCase();return n.startsWith('zh')?'zh':n.startsWith('pt')?'pt':'en'})();
let i=0,score={A:0,D:0,M:0,F:0},order=[],stage='start',last=null;
const esc=s=>String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
const fmt=(s,o)=>s.replace(/\{(\w+)\}/g,(_,k)=>o[k]??'');
const shuffle=a=>{a=a.slice();for(let k=a.length-1;k>0;k--){const j=Math.floor(Math.random()*(k+1));[a[k],a[j]]=[a[j],a[k]]}return a};
const U=()=>L[lang].ui, TN=k=>(L[lang].T[k]||[k])[0];

function setLang(l){lang=l;try{localStorage.setItem('wyb-lang',l)}catch(_){}
 document.documentElement.lang={pt:'pt-BR',en:'en',zh:'zh-CN'}[l];
 document.querySelectorAll('#langs button').forEach(b=>b.setAttribute('aria-pressed',b.dataset.l===l));
 stage==='start'?start():stage==='ask'?ask():result();}
document.querySelectorAll('#langs button').forEach(b=>b.onclick=()=>setLang(b.dataset.l));

function inviteCard(){
 if(!friend)return'';const u=U();const who=friend.name||u.friend;
 return `<div class="card invite"><div class="res-head"><div class="badge" style="background:var(${COL[friend.r]})">${friend.r}</div>
  <div><h2 style="font-size:24px">${esc(fmt(u.invite,{who,type:TN(friend.r)}))}</h2><p class="lead">${esc(u.inviteSub)}</p></div></div></div>`;
}
function start(){
 stage='start';const u=U();
 app.innerHTML=`${inviteCard()}
 <span class="tag">${u.meta}</span>
 <h1>${u.h1}</h1>
 ${friend?'':`<p class="lead">${u.lead}</p>`}
 <button class="btn" id="go" style="width:100%">${u.go}</button>
 <div class="types">${Object.keys(COL).map(k=>`<div class="type"><span class="dot" style="background:var(${COL[k]})"></span>${TN(k)}</div>`).join('')}</div>
 <p class="fine">${u.fine}</p>`;
 document.getElementById('go').onclick=()=>{i=0;score={A:0,D:0,M:0,F:0};order=L.pt.Q.map(()=>shuffle(Object.keys(COL)));ask()};
}
function ask(){
 stage='ask';const u=U();const [q,o]=L[lang].Q[i];const t=L[lang].Q.length;
 app.innerHTML=`
 <div class="progress">${L[lang].Q.map((_,k)=>`<span class="${k<=i?'on':''}"></span>`).join('')}</div>
 <span class="tag">${fmt(u.q,{n:i+1,t})}</span>
 <div class="bubble">${esc(q)}</div>
 <div class="opts">${order[i].map(k=>`<button class="opt" data-k="${k}">${esc(o[k])}</button>`).join('')}</div>`;
 app.querySelectorAll('.opt').forEach(b=>b.onclick=()=>{score[b.dataset.k]++;i++;i<t?ask():result()});
 app.querySelector('.opt').focus({preventScroll:true});
}
function shareUrl(top,name){
 const p=new URLSearchParams({s:QUIZ_ID,r:top,lang});if(name)p.set('de',name);
 return `${SITE}/q/?${p}`;
}
function result(){
 stage='result';const u=U();const tot=L[lang].Q.length;
 const keys=Object.keys(score).sort((a,b)=>score[b]-score[a]);const k=window.pickTop?pickTop(keys):keys[0];const t=L[lang].T[k];
 const pct=x=>Math.round((x==='S'?score.A:score[x])/tot*100);last={k,p:pct(k)};
 let vs='';
 if(friend){const same=friend.r===k;
  vs=`<div class="vs"><div class="badge mini" style="background:var(${COL[friend.r]})">${friend.r}</div>
   <p><b>${esc(friend.name||u.friend)}: ${esc(TN(friend.r))}.</b> ${esc(same?u.same:fmt(u.diff,{a:TN(k),b:TN(friend.r)}))}</p></div>`;}
 app.innerHTML=`
 <div class="card">
  <div class="res-head"><div class="badge" style="background:var(${COL[k]})">${k}</div>
   <div><span class="tag">${u.res} · ${pct(k)}%</span><h2>${esc(t[0])}</h2></div></div>
  <p class="quote">“${esc(t[5])}”</p>
  ${vs}
 </div>
 <div class="card">
  <input type="text" id="nm" placeholder="${esc(u.name)}" maxlength="30" aria-label="${esc(u.name)}">
  <div class="btns">
   <button class="btn" id="sh">${u.shareBtn}</button>
   <button class="btn ghost" id="cp">${u.copyBtn}</button>
   <button class="btn ghost" id="dl">${u.dl}</button>
  </div>
  <p class="toast" id="toast"></p>
 </div>
 <details class="card"><summary>${u.more}</summary><div>
  <p class="lead">${esc(t[1])}</p>
  <p>${esc(t[2])}</p>
  <div class="pair"><div><span class="tag">${u.power}</span><span>${esc(t[3])}</span></div>
   <div><span class="tag">${u.watch}</span><span>${esc(t[4])}</span></div></div>
  <span class="tag">${u.mix}</span>
  <div class="bars">${keys.map(x=>`<div class="bar"><span>${esc(TN(x).replace(/^(O |The )/,''))}</span><div class="track"><div class="fill" style="width:${pct(x)}%;background:var(${COL[x]})"></div></div><span class="pct">${pct(x)}%</span></div>`).join('')}</div>
  <button class="btn ghost" id="again">${u.again}</button>
 </div></details>`;
 const nm=()=>document.getElementById('nm').value.trim();
 const text=()=>fmt(u.shareText,{type:t[0],p:pct(k)});
 const toast=m=>{document.getElementById('toast').textContent=m};
 const copy=()=>{const s=text()+' '+shareUrl(k,nm());
  (navigator.clipboard?navigator.clipboard.writeText(s):Promise.reject()).then(()=>toast(u.copied),()=>toast(s))};
 document.getElementById('cp').onclick=copy;
 document.getElementById('sh').onclick=async()=>{
  const url=shareUrl(k,nm());
  if(navigator.share){try{const f=await badgeFile(k);
     if(f&&navigator.canShare&&navigator.canShare({files:[f]}))await navigator.share({files:[f],text:text()+' '+url});
     else await navigator.share({text:text(),url});return}catch(e){if(e&&e.name==='AbortError')return}}
  copy()};
 document.getElementById('dl').onclick=async()=>{const f=await badgeFile(k);if(!f)return;
  const a=document.createElement('a');a.href=URL.createObjectURL(f);a.download=f.name;document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(a.href),4000)};
 document.getElementById('again').onclick=start;
 window.scrollTo(0,0);
}

// Selo vertical 1080x1350 pro feed/story
const HEX={A:"#2F4BE8",D:"#D9463B",M:"#1E9E7A",F:"#C98A12"};
async function badgeFile(k){
 try{await document.fonts.ready}catch(_){}
 const t=L[lang].T[k],u=U();const W=1080,H=1350;const c=document.createElement('canvas');c.width=W;c.height=H;const g=c.getContext('2d');
 const col=HEX[k];g.fillStyle='#0F1422';g.fillRect(0,0,W,H);
 // bolhas decorativas
 [[160,180,90,.18],[930,260,140,.14],[880,1120,110,.16],[120,1080,60,.2],[540,140,30,.25]].forEach(([x,y,r,a])=>{g.beginPath();g.arc(x,y,r,0,7);g.strokeStyle=col;g.globalAlpha=a;g.lineWidth=6;g.stroke();g.globalAlpha=1});
 g.beginPath();g.arc(W/2,470,230,0,7);g.fillStyle=col;g.fill();
 g.beginPath();g.arc(W/2-80,390,40,0,7);g.fillStyle='rgba(255,255,255,.25)';g.fill();
 g.fillStyle='#fff';g.textAlign='center';g.textBaseline='middle';
 g.font='800 260px "Bricolage Grotesque","Noto Sans SC",sans-serif';g.fillText(k,W/2,485);
 g.textBaseline='alphabetic';
 g.font='800 96px "Bricolage Grotesque","Noto Sans SC",sans-serif';g.fillText(t[0],W/2,860);
 g.fillStyle='#C9D1E6';g.font='500 44px "IBM Plex Sans","Noto Sans SC",sans-serif';
 wrap(g,t[1],W/2,940,880,58);
 g.fillStyle=col;g.font='500 40px "IBM Plex Sans","Noto Sans SC",sans-serif';g.fillText('“'+t[5]+'”',W/2,1090,960);
 g.fillStyle='#fff';g.font='800 52px "Bricolage Grotesque","Noto Sans SC",sans-serif';g.fillText(u.andyou,W/2,1210);
 g.fillStyle='#9AA4BB';g.font='500 34px "IBM Plex Mono",monospace';g.fillText('whatsyourbubble.com',W/2,1280);
 const b=await new Promise(r=>c.toBlob(r,'image/png'));
 return b?new File([b],`whatsyourbubble-${k.toLowerCase()}.png`,{type:'image/png'}):null;
}
function wrap(g,s,x,y,max,lh){
 const cjk=/[㐀-鿿]/.test(s);const parts=cjk?[...s]:s.split(' ');let line='',yy=y;
 for(const w of parts){const test=line?(line+(cjk?'':' ')+w):w;
  if(g.measureText(test).width>max&&line){g.fillText(line,x,yy);line=w;yy+=lh}else line=test}
 g.fillText(line,x,yy);
}
setLang(lang);
</script>
<script src="/ai/mascots.js?v=7"></script>
<script src="/ai/secret.js?v=9"></script>
<script>for(const l in L)L[l].T.S=QUIZ[l].T.S;</script>
<script src="/ai/home.js?v=9"></script>
<script src="/ai/reveal.js?v=7"></script>
<script src="/ai/mix.js?v=10"></script>
<script src="/ai/share.js?v=12"></script>
<script src="/ai/track.js?v=13"></script>
<script src="/ai/fun.js?v=2"></script>
<script src="/q/extras.js?v=1"></script>
</body>
</html>
