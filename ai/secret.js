// 5ª bolha secreta: aparece só com empate perfeito nos 4 perfis
(function(){
 const st=document.createElement('style');st.textContent=`
 :root{--cS:#7C3AED}
 @media (prefers-color-scheme: dark){:root{--cS:#B79CFF}}
 .teaser{margin:0;text-align:center;font-family:var(--display);font-weight:800;font-size:clamp(20px,5.2vw,23px);
   background:linear-gradient(90deg,#2F4BE8,#1E9E7A,#C98A12,#D9463B);-webkit-background-clip:text;background-clip:text;color:transparent}`;
 document.head.appendChild(st);
 const T={
  pt:["A Bolha Secreta","Você não cabe numa bolha só.","Você não se prende a um jeito fixo de usar IA: se adapta ao que cada situação pede. Planeja quando precisa, desconfia quando vale a pena, conecta quando ajuda e entrega quando é hora.","Trocar de estilo na hora certa.","Ninguém saber o que esperar de você.","Depende. E eu sei do quê."],
  en:["The Secret Bubble","You don't fit in just one bubble.","You don't stick to one fixed way of using AI: you adapt to what each moment needs. You plan when it matters, doubt when it pays off, connect when it helps and deliver when it's time.","Switching style at the right moment.","Nobody knowing what to expect from you.","It depends. And I know on what."],
  zh:["秘密泡泡","一个泡泡装不下你。","你不拘泥于一种使用AI的方式，而是根据情况调整：该规划时规划，该怀疑时怀疑，该连接时连接，该交付时交付。","在对的时候切换风格。","别人猜不透你。","看情况。而且我知道看什么。"]};
 for(const l in T) L[l].T.S=T[l];
 Object.defineProperty(COL,'S',{value:'--cS',enumerable:false});   // fora das listas dos 4 perfis
 HEX.S='#7C3AED';
 MASCOT.S=`<svg viewBox="0 0 120 120" aria-hidden="true"><defs><linearGradient id="gS" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#2F4BE8"/><stop offset=".38" stop-color="#1E9E7A"/><stop offset=".68" stop-color="#C98A12"/><stop offset="1" stop-color="#D9463B"/></linearGradient></defs><circle cx="60" cy="64" r="50" fill="url(#gS)"/><path d="M30 50 A34 34 0 0 1 46 28" fill="none" stroke="#FFF" stroke-opacity=".5" stroke-width="5" stroke-linecap="round"/><path d="M38 30 L44 14 L52 24 L60 10 L68 24 L76 14 L82 30 Z" fill="#FFF"/><circle cx="48" cy="25" r="2.6" fill="#2F4BE8"/><circle cx="56" cy="25" r="2.6" fill="#1E9E7A"/><circle cx="64" cy="25" r="2.6" fill="#C98A12"/><circle cx="72" cy="25" r="2.6" fill="#D9463B"/><path d="M38 66 Q46 57 54 66 M66 66 Q74 57 82 66" fill="none" stroke="#141B2E" stroke-width="4.5" stroke-linecap="round"/><path d="M44 80 Q60 96 76 80" fill="none" stroke="#FFF" stroke-width="4.5" stroke-linecap="round"/><path d="M104 30 l3 7 7 3 -7 3 -3 7 -3 -7 -7 -3 7 -3z" fill="#F2B23B"/><path d="M14 92 l2 5 5 2 -5 2 -2 5 -2 -5 -5 -2 5 -2z" fill="#7C90FF"/></svg>`;
 window.isSecret=()=>{const v=['A','D','M','F'].map(k=>score[k]||0);return v[0]>0&&v.every(x=>x===v[0])};
 window.pickTop=keys=>isSecret()?'S':keys[0];
 const TZ={pt:"E existe uma 5ª bolha secreta…",en:"And there's a secret 5th bubble…",zh:"还有一个隐藏的第5个泡泡……"};
 new MutationObserver(()=>{const g=document.querySelector('.bub-grid');
   if(g&&!document.querySelector('.teaser'))g.insertAdjacentHTML('afterend',`<p class="teaser">${TZ[lang]||TZ.en}</p>`);})
  .observe(document.getElementById('app'),{childList:true,subtree:true});
})();
