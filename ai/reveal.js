// Revelação do resultado: suspense + bolhas/confete + "pop"
(function(){
 const MSG={pt:"Lendo sua bolha…",en:"Reading your bubble…",zh:"正在读取你的泡泡…"};
 const reduce=matchMedia('(prefers-reduced-motion: reduce)').matches;
 const st=document.createElement('style');st.textContent=`
 .suspense{min-height:70vh;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:28px;text-align:center}
 .suspense .row{display:flex;gap:10px}
 .suspense .row div{width:clamp(56px,16vw,76px);animation:hop .7s ease-in-out infinite}
 .suspense .row div:nth-child(2){animation-delay:.12s}.suspense .row div:nth-child(3){animation-delay:.24s}.suspense .row div:nth-child(4){animation-delay:.36s}
 .suspense p{font-family:var(--display);font-weight:800;font-size:clamp(26px,7vw,34px);margin:0}
 @keyframes hop{0%,100%{transform:translateY(0)}50%{transform:translateY(-18px)}}
 @media (prefers-reduced-motion:reduce){.suspense .row div{animation:none}}`;
 document.head.appendChild(st);

 let ac=null;
 function pop(){
  try{if(!ac)return;const t=ac.currentTime,o=ac.createOscillator(),g=ac.createGain();
   o.type='sine';o.frequency.setValueAtTime(520,t);o.frequency.exponentialRampToValueAtTime(140,t+.12);
   g.gain.setValueAtTime(.0001,t);g.gain.exponentialRampToValueAtTime(.35,t+.01);g.gain.exponentialRampToValueAtTime(.0001,t+.18);
   o.connect(g).connect(ac.destination);o.start(t);o.stop(t+.2);
   [880,1175,1568].forEach((f,i)=>{const s=ac.createOscillator(),h=ac.createGain(),u=t+.14+i*.08;
    s.type='triangle';s.frequency.value=f;h.gain.setValueAtTime(.0001,u);h.gain.exponentialRampToValueAtTime(.12,u+.01);h.gain.exponentialRampToValueAtTime(.0001,u+.35);
    s.connect(h).connect(ac.destination);s.start(u);s.stop(u+.4);});
  }catch(_){}
 }
 function burst(col){
  if(reduce)return;
  const c=document.createElement('canvas');c.style.cssText='position:fixed;inset:0;width:100%;height:100%;pointer-events:none;z-index:50';
  document.body.appendChild(c);const dpr=Math.min(2,devicePixelRatio||1);const W=c.width=innerWidth*dpr,H=c.height=innerHeight*dpr;const g=c.getContext('2d');
  const cols=[col,col,col,'#FFFFFF','#F2B23B','#7C90FF','#43C9A2','#FF7A6E'];
  const P=Array.from({length:130},(_,n)=>({x:W/2+(Math.random()-.5)*W*.3,y:H*.35,vx:(Math.random()-.5)*14*dpr,vy:(-Math.random()*16-6)*dpr,
   r:(Math.random()*7+4)*dpr,c:cols[n%cols.length],b:n%3===0,rot:Math.random()*6,vr:(Math.random()-.5)*.3,life:1}));
  const t0=performance.now();
  (function f(now){const el=(now-t0)/1000;g.clearRect(0,0,W,H);
   for(const p of P){p.vy+=.45*dpr;p.vx*=.99;p.x+=p.vx;p.y+=p.vy;p.rot+=p.vr;const a=Math.max(0,1-el/2.6);g.globalAlpha=a;
    if(p.b){g.beginPath();g.arc(p.x,p.y,p.r*1.4,0,7);g.strokeStyle=p.c;g.lineWidth=2*dpr;g.stroke();g.beginPath();g.arc(p.x-p.r*.5,p.y-p.r*.5,p.r*.35,0,7);g.fillStyle='rgba(255,255,255,.8)';g.fill();}
    else{g.save();g.translate(p.x,p.y);g.rotate(p.rot);g.fillStyle=p.c;g.fillRect(-p.r,-p.r*.45,p.r*2,p.r*.9);g.restore();}}
   if(el<2.7)requestAnimationFrame(f);else c.remove();})(t0);
 }
 const original=result;
 result=function(){
  if(stage!=='ask'){original();return;}           // troca de idioma: sem repetir a revelação
  try{ac=ac||new (window.AudioContext||window.webkitAudioContext)();ac.resume&&ac.resume()}catch(_){}
  stage='suspense';
  app.innerHTML=`<div class="suspense"><div class="row">${'ADMF'.split('').map(k=>`<div>${window.MASCOT?MASCOT[k]:''}</div>`).join('')}</div><p>${MSG[lang]}</p></div>`;
  window.scrollTo(0,0);
  setTimeout(()=>{original();const k=last&&last.k;pop();burst(HEX[k]||'#2F4BE8');},1500);
 };
})();
