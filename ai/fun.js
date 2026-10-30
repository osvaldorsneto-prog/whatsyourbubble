// Tela das perguntas mais viva: um mascote "pergunta", opções coloridas,
// progresso em bolhas, bolhas flutuando no fundo e um "pop" ao escolher.
// As cores das opções vêm da POSIÇÃO (a ordem é sorteada), nunca do perfil, pra não entregar a resposta.
(function(){
 const reduce=matchMedia('(prefers-reduced-motion: reduce)').matches;
 const CHEER={pt:{half:'Já passou da metade!',last:'Última pergunta!'},en:{half:"You're past halfway!",last:'Last question!'},zh:{half:'已经过半啦！',last:'最后一题！'}};
 const POS=['#FF5C8A','#12B5CB','#7CC71C','#8C6CF5'];
 const st=document.createElement('style');st.textContent=`
 .fun-bg{position:fixed;inset:0;pointer-events:none;z-index:-1;overflow:hidden}
 .fun-bg i{position:absolute;bottom:-80px;border-radius:50%;border:2px solid var(--accent);opacity:.10;animation:fun-up linear infinite}
 @keyframes fun-up{to{transform:translateY(-120vh)}}
 .progress{gap:6px!important;align-items:center}
 .progress span{flex:1;height:12px!important;border-radius:50%!important;max-width:12px;background:var(--line)!important;transition:transform .3s}
 .progress span.on{background:var(--accent)!important}
 .progress span.now{transform:scale(1.6);box-shadow:0 0 0 4px color-mix(in srgb,var(--accent) 25%,transparent)}
 .progress{justify-content:space-between}
 .asker{display:flex;gap:12px;align-items:flex-end}
 .asker .who{width:clamp(64px,17vw,84px);flex:none;animation:fun-wig 2.4s ease-in-out infinite}
 .asker .who svg{width:100%;height:auto;display:block}
 .asker .bubble{flex:1;position:relative;border-radius:22px 22px 22px 6px!important;box-shadow:0 10px 24px -14px rgba(20,27,46,.45);animation:fun-in .35s cubic-bezier(.2,.8,.3,1.3) both}
 .asker .bubble::before{content:"";position:absolute;left:-9px;bottom:10px;width:18px;height:18px;background:var(--surface);border-left:1px solid var(--line);border-bottom:1px solid var(--line);transform:rotate(45deg)}
 @keyframes fun-wig{0%,100%{transform:rotate(-4deg) translateY(0)}50%{transform:rotate(4deg) translateY(-6px)}}
 @keyframes fun-in{from{transform:scale(.85);opacity:0}}
 .cheer{font-family:var(--display);font-weight:800;color:var(--accent);margin:-6px 0 0}
 .opt{position:relative;display:flex!important;align-items:center;gap:14px;border-width:2px!important;background:var(--surface)!important;
   border-color:color-mix(in srgb,var(--pc) 40%,var(--line))!important;transition:transform .12s,border-color .15s,background .15s}
 .opt::before{content:"";flex:none;width:22px;height:22px;border-radius:50%;background:var(--pc);box-shadow:inset -4px -4px 0 rgba(0,0,0,.12),inset 3px 3px 0 rgba(255,255,255,.45)}
 .opt:hover{border-color:var(--pc)!important;transform:translateY(-2px)}
 .opt:active{transform:scale(.97)}
 .opt.picked{background:var(--pc)!important;border-color:var(--pc)!important;color:#fff!important;transform:scale(1.03)}
 .opt.picked::before{background:#fff}
 .opts.done .opt:not(.picked){opacity:.45}
 .pop{position:fixed;width:14px;height:14px;border-radius:50%;pointer-events:none;z-index:60;animation:fun-pop .55s ease-out forwards}
 @keyframes fun-pop{to{transform:translate(var(--dx),var(--dy)) scale(.2);opacity:0}}
 /* Fundo colorido em todas as telas do quiz: fica no <html> pra cobrir a tela inteira e ficar atrás das bolhas */
 html:has(body.fun){min-height:100%;background:radial-gradient(circle at 15% 10%,#FFD1E8 0,transparent 40%),radial-gradient(circle at 90% 25%,#C9F1FF 0,transparent 42%),radial-gradient(circle at 30% 95%,#E4D6FF 0,transparent 45%),linear-gradient(160deg,#FFF0F7,#F1EBFF 55%,#E3F7FF);background-attachment:fixed}
 @media (prefers-color-scheme:dark){html:has(body.fun){background:radial-gradient(circle at 15% 10%,#5B1D52 0,transparent 42%),radial-gradient(circle at 90% 25%,#123E63 0,transparent 42%),radial-gradient(circle at 30% 95%,#3A2577 0,transparent 45%),linear-gradient(160deg,#1E1238,#151A3D 55%,#0E2236);background-attachment:fixed}}
 body.fun{background:transparent!important}
 body.fun .cta{background:#2F4BE8!important;color:#fff!important;border:3px solid #141B2E!important;box-shadow:5px 5px 0 #FF5C8A}
 body.fun .bub{border:3px solid #141B2E!important;box-shadow:5px 5px 0 #141B2E}
 body.fun .fun-bg i{opacity:.3;border-width:3px}body.fun .fun-bg b{opacity:1}
 body.fun .fun-bg i:nth-child(3n){border-color:#FF5C8A}body.fun .fun-bg i:nth-child(3n+1){border-color:#12B5CB}body.fun .fun-bg i:nth-child(3n+2){border-color:#8C6CF5}
 /* v2: mais cor, estilo anime/sticker (só durante as perguntas) */
 body.asking .fun-bg i{opacity:.35;border-width:3px}
 body.asking .fun-bg i:nth-child(3n){border-color:#FF5C8A}body.asking .fun-bg i:nth-child(3n+1){border-color:#12B5CB}body.asking .fun-bg i:nth-child(3n+2){border-color:#8C6CF5}
 .fun-bg b{position:absolute;width:18px;height:18px;background:#FFC93C;clip-path:polygon(50% 0,62% 38%,100% 50%,62% 62%,50% 100%,38% 62%,0 50%,38% 38%);opacity:0;animation:fun-tw 3s ease-in-out infinite}
 body.asking .fun-bg b{opacity:1}
 @keyframes fun-tw{0%,100%{transform:scale(.4) rotate(0)}50%{transform:scale(1.1) rotate(45deg)}}
 body.asking .progress span:nth-child(4n+1).on{background:#FF5C8A!important}body.asking .progress span:nth-child(4n+2).on{background:#12B5CB!important}
 body.asking .progress span:nth-child(4n+3).on{background:#7CC71C!important}body.asking .progress span:nth-child(4n).on{background:#8C6CF5!important}
 body.asking .asker .bubble{border:3px solid #141B2E!important;box-shadow:6px 6px 0 #141B2E!important;background:#fff!important;color:#141B2E!important}
 body.asking .asker .bubble::before{background:#fff;border-left:3px solid #141B2E;border-bottom:3px solid #141B2E;left:-11px}
 body.asking .asker .who{position:relative;isolation:isolate}
 body.asking .asker .who::before{content:"";position:absolute;inset:-14px;background:repeating-conic-gradient(from 0deg,var(--mc) 0 6deg,transparent 6deg 18deg);border-radius:50%;opacity:.35;z-index:-1;animation:fun-spin 12s linear infinite;
   -webkit-mask:radial-gradient(circle,transparent 42%,#000 44%);mask:radial-gradient(circle,transparent 42%,#000 44%)}
 @keyframes fun-spin{to{transform:rotate(360deg)}}
 body.asking .tag{display:inline-block;align-self:flex-start;background:#141B2E;color:#fff!important;border-radius:999px;padding:4px 14px;transform:rotate(-2deg)}
 body.asking .cheer{display:inline-block;align-self:flex-start;background:#FFC93C;color:#141B2E;border:3px solid #141B2E;border-radius:12px;padding:2px 12px;transform:rotate(2deg);box-shadow:3px 3px 0 #141B2E;margin:0}
 body.asking .opt{background:color-mix(in srgb,var(--pc) 22%,#fff)!important;border:3px solid #141B2E!important;color:#141B2E!important;box-shadow:5px 5px 0 var(--pc);font-weight:600!important}
 body.asking .opt::before{width:26px;height:26px;border:3px solid #141B2E}
 body.asking .opt:hover{transform:translate(-2px,-2px);box-shadow:7px 7px 0 var(--pc)}
 body.asking .opt:active{transform:translate(3px,3px);box-shadow:2px 2px 0 var(--pc)}
 body.asking .opt.picked{background:var(--pc)!important;color:#fff!important;text-shadow:0 1px 0 rgba(0,0,0,.25)}
 body.asking .opts.done .opt:not(.picked){opacity:.5}
 /* v3: resultado no mesmo estilo (adesivos coloridos sobre fundo colorido, igual nos dois temas) */
 body.result{--surface:#fff;--ink:#141B2E;--muted:#4A5266;--line:#C9CFDB;--bg:#F1EEFA;--accent:#2F4BE8;--accent-ink:#fff;}
   body.result .top .brand{color:#fff}body.result .langs button{color:#C9D1E6;border-color:#4A4F7A}body.result .langs button[aria-pressed="true"]{background:#fff;color:#141B2E}}
 body.result .fun-bg i{opacity:.3;border-width:3px}body.result .fun-bg b{opacity:1}
 body.result #app > .card,body.result details.card{border:3px solid #141B2E!important;border-radius:22px!important;box-shadow:6px 6px 0 #141B2E;background:#fff;color:#141B2E}
 body.result .res-card{color:#fff!important;position:relative;overflow:visible!important}
 body.result .res-card[data-k=A]{background:linear-gradient(135deg,#141C5C,#22328F)!important;box-shadow:6px 6px 0 #2F4BE8!important}
 body.result .res-card[data-k=D]{background:linear-gradient(135deg,#5A1511,#8E2A23)!important;box-shadow:6px 6px 0 #D9463B!important}
 body.result .res-card[data-k=M]{background:linear-gradient(135deg,#083D30,#11654F)!important;box-shadow:6px 6px 0 #1E9E7A!important}
 body.result .res-card[data-k=F]{background:linear-gradient(135deg,#523807,#86600D)!important;box-shadow:6px 6px 0 #C98A12!important}
 body.result .res-card[data-k=S]{background:linear-gradient(135deg,#1C2A8C,#0F5E4C 35%,#7A5208 68%,#7E1E19)!important;box-shadow:6px 6px 0 #7C3AED!important}
 body.result .res-card .tag{color:rgba(255,255,255,.75)!important}
 body.result .res-card h2,body.result .res-card .mixline b{color:#fff}
 body.result .res-card .quote{border-left-color:#FFC93C;color:#fff}
 body.result .res-card .mixline,body.result .res-card .vs p{color:rgba(255,255,255,.92)}
 body.result .res-card .vs{border-top-color:rgba(255,255,255,.25)}
 body.result .res-card .badge{position:relative;isolation:isolate;overflow:visible}
 body.result .res-card .badge::before{content:"";position:absolute;inset:-10px;border-radius:50%;z-index:-1;opacity:.45;animation:fun-spin 12s linear infinite;
   background:repeating-conic-gradient(from 0deg,#FFC93C 0 6deg,transparent 6deg 18deg);-webkit-mask:radial-gradient(circle,transparent 44%,#000 46%);mask:radial-gradient(circle,transparent 44%,#000 46%)}
 body.result .btn{border:3px solid #141B2E!important;box-shadow:4px 4px 0 #141B2E;transition:transform .1s,box-shadow .1s}
 body.result .btn:active{transform:translate(3px,3px);box-shadow:1px 1px 0 #141B2E}
 body.result .btn.ghost{background:#fff!important;color:#141B2E!important;box-shadow:4px 4px 0 #12B5CB}
 body.result .btn.ghost#dl{box-shadow:4px 4px 0 #FF5C8A}
 body.result .btn.vid{box-shadow:4px 4px 0 #FFC93C}
 body.result input[type=text]{border:3px solid #141B2E!important;background:#FFF8E1!important;color:#141B2E!important}
 body.result .story{background:#FFF3C4;border:3px solid #141B2E!important;border-radius:14px;padding:10px 14px!important;color:#141B2E!important;transform:rotate(-1deg)}
 body.result .net{border:3px solid #141B2E!important;background:#fff!important;color:#141B2E!important;box-shadow:4px 4px 0 var(--nc,#141B2E)}
 body.result .net[data-n=WhatsApp]{--nc:#25D366}body.result .net[data-n=Facebook]{--nc:#1877F2}body.result .net[data-n=X]{--nc:#8C6CF5}
 body.result .net[data-n=Telegram]{--nc:#229ED9}body.result .net[data-n=LinkedIn]{--nc:#0A66C2}
 body.result .nextq{border:3px solid #141B2E;box-shadow:6px 6px 0 #FF5C8A;background:linear-gradient(135deg,#1E1238,#141B2E)!important}
 body.result details summary::after{color:#FF5C8A}
 body.result .bars .fill{border:2px solid #141B2E}
 @media (prefers-reduced-motion:reduce){.asker .who,.asker .bubble,.fun-bg i,.fun-bg b,.asker .who::before,.res-card .badge::before{animation:none!important}}`;
 document.head.appendChild(st);
 document.body.classList.add('fun');
 if(!reduce){const bg=document.createElement('div');bg.className='fun-bg';
  for(let n=0;n<9;n++){const i=document.createElement('i');const s=20+Math.random()*60;
   i.style.cssText=`left:${Math.random()*100}%;width:${s}px;height:${s}px;animation-duration:${14+Math.random()*14}s;animation-delay:${-Math.random()*20}s`;bg.appendChild(i);}
  const SP=['#FFC93C','#FF5C8A','#12B5CB','#8C6CF5'];
  for(let n=0;n<7;n++){const b=document.createElement('b');b.style.cssText=`left:${5+Math.random()*90}%;top:${5+Math.random()*90}%;background:${SP[n%4]};animation-delay:${-Math.random()*3}s`;bg.appendChild(b);}
  document.body.appendChild(bg);}
 function enfeitar(){
  document.body.classList.toggle('asking',!!document.querySelector('#app .opt, #app .suspense'));
  const res=document.querySelector('#app .res-head');
  document.body.classList.toggle('result',!!res);
  if(res&&typeof last!=='undefined'&&last){const c=res.closest('.card');if(c){c.classList.add('res-card');c.dataset.k=last.k;}}
  const q=document.querySelector('#app > .bubble');if(!q||q.parentNode.classList.contains('asker'))return;
  const n=(typeof i==='number'?i:0),t=L[lang].Q.length;
  const wrap=document.createElement('div');wrap.className='asker';
  wrap.style.setProperty('--mc',['#2F4BE8','#D9463B','#1E9E7A','#C98A12'][n%4]);
  wrap.innerHTML=`<div class="who">${(window.MASCOT&&MASCOT['ADMF'[n%4]])||''}</div>`;
  q.replaceWith(wrap);wrap.appendChild(q);
  const ps=document.querySelectorAll('.progress span');if(ps[n])ps[n].classList.add('now');
  const c=CHEER[lang]||CHEER.en;const msg=n===t-1?c.last:(n===Math.floor(t/2)?c.half:'');
  if(msg)document.querySelector('#app > .tag')?.insertAdjacentHTML('afterend',`<p class="cheer">${msg}</p>`);
  document.querySelectorAll('.opt').forEach((b,k)=>b.style.setProperty('--pc',POS[k%4]));
 }
 // Ao escolher: destaca, solta bolhas e só então segue pra próxima pergunta
 document.addEventListener('click',ev=>{
  const b=ev.target.closest('.opt');if(!b||b.dataset.go)return;
  ev.stopImmediatePropagation();ev.preventDefault();
  if(b.closest('.opts').classList.contains('done'))return;
  b.closest('.opts').classList.add('done');b.classList.add('picked');
  if(!reduce){const r=b.getBoundingClientRect(),cx=r.left+r.width*.12,cy=r.top+r.height/2,col=getComputedStyle(b).getPropertyValue('--pc');
   for(let k=0;k<10;k++){const p=document.createElement('i');p.className='pop';const a=Math.random()*Math.PI*2,d=40+Math.random()*60;
    p.style.cssText=`left:${cx}px;top:${cy}px;background:${k%3?col:'#fff'};--dx:${Math.cos(a)*d}px;--dy:${Math.sin(a)*d}px`;document.body.appendChild(p);setTimeout(()=>p.remove(),600);}}
  setTimeout(()=>{b.dataset.go='1';b.click();},reduce?60:260);
 },true);
 new MutationObserver(enfeitar).observe(document.getElementById('app'),{childList:true});
})();
