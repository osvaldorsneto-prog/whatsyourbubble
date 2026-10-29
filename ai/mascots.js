// Mascotes dos 4 perfis (direção B)
window.MASCOT={
A:`<svg viewBox="0 0 120 120" aria-hidden="true"><circle cx="60" cy="64" r="50" fill="#2F4BE8"/><path d="M28 44 Q60 4 92 44 Z" fill="#F2B23B"/><rect x="20" y="41" width="80" height="9" rx="4.5" fill="#F2B23B"/><path d="M60 14 L60 42" stroke="#D4931C" stroke-width="4"/><circle cx="46" cy="66" r="8" fill="#FFF"/><circle cx="74" cy="66" r="8" fill="#FFF"/><circle cx="48" cy="67" r="4" fill="#141B2E"/><circle cx="76" cy="67" r="4" fill="#141B2E"/><path d="M48 84 Q60 93 72 84" fill="none" stroke="#FFF" stroke-width="4" stroke-linecap="round"/><path d="M96 70 L112 52" stroke="#FFF" stroke-width="5" stroke-linecap="round"/><path d="M112 52 L114 46 L108 50 Z" fill="#141B2E"/></svg>`,
D:`<svg viewBox="0 0 120 120" aria-hidden="true"><circle cx="60" cy="64" r="50" fill="#D9463B"/><path d="M30 42 Q60 10 90 42 Z" fill="#6B4A2E"/><path d="M22 44 Q60 32 98 44 Q60 52 22 44 Z" fill="#5A3D25"/><path d="M60 18 L60 40" stroke="#8A6640" stroke-width="3"/><circle cx="46" cy="66" r="8" fill="#FFF"/><circle cx="74" cy="66" r="8" fill="#FFF"/><circle cx="47" cy="67" r="4" fill="#141B2E"/><circle cx="75" cy="67" r="4" fill="#141B2E"/><circle cx="74" cy="66" r="12" fill="none" stroke="#F2B23B" stroke-width="3.5"/><path d="M85 72 Q92 88 84 100" fill="none" stroke="#F2B23B" stroke-width="2"/><path d="M50 86 L70 86" stroke="#FFF" stroke-width="4" stroke-linecap="round"/><path d="M38 54 L52 57" stroke="#FFF" stroke-width="3.5" stroke-linecap="round"/></svg>`,
M:`<svg viewBox="0 0 120 120" aria-hidden="true"><circle cx="60" cy="62" r="50" fill="#1E9E7A"/><path d="M44 20 Q52 6 66 14 Q58 16 56 24" fill="#141B2E"/><circle cx="46" cy="58" r="8" fill="#FFF"/><circle cx="74" cy="58" r="8" fill="#FFF"/><circle cx="46" cy="56" r="4" fill="#141B2E"/><circle cx="74" cy="56" r="4" fill="#141B2E"/><path d="M48 74 Q60 84 72 74" fill="none" stroke="#FFF" stroke-width="4" stroke-linecap="round"/><path d="M60 96 L46 88 L46 104 Z" fill="#141B2E"/><path d="M60 96 L74 88 L74 104 Z" fill="#141B2E"/><circle cx="60" cy="96" r="4" fill="#141B2E"/><path d="M100 40 L116 18" stroke="#FFF" stroke-width="4" stroke-linecap="round"/><circle cx="100" cy="40" r="4" fill="#FFF"/></svg>`,
F:`<svg viewBox="0 0 120 120" aria-hidden="true"><path d="M2 50 L18 50 M0 64 L14 64 M4 78 L18 78" stroke="#C98A12" stroke-width="4" stroke-linecap="round"/><circle cx="66" cy="64" r="50" fill="#C98A12"/><rect x="18" y="38" width="96" height="11" rx="2" fill="#FFF" transform="rotate(-6 66 43)"/><path d="M112 36 L120 28 M112 44 L120 46" stroke="#FFF" stroke-width="4" stroke-linecap="round"/><path d="M44 58 L58 62 M76 62 L90 58" stroke="#141B2E" stroke-width="4" stroke-linecap="round"/><circle cx="52" cy="70" r="7" fill="#FFF"/><circle cx="82" cy="70" r="7" fill="#FFF"/><circle cx="54" cy="71" r="3.5" fill="#141B2E"/><circle cx="84" cy="71" r="3.5" fill="#141B2E"/><path d="M56 88 Q68 96 80 88" fill="none" stroke="#FFF" stroke-width="4" stroke-linecap="round"/></svg>`
};
(function(){
 // troca a letra pelo mascote em qualquer selo que aparecer
 const swap=root=>root.querySelectorAll('.badge,.orb').forEach(el=>{
   const k=el.textContent.trim();if(!MASCOT[k]||el.dataset.m)return;
   el.dataset.m=k;el.innerHTML=MASCOT[k];el.classList.add('mascot');el.setAttribute('role','img');el.setAttribute('aria-label',TN(k));});
 swap(document);new MutationObserver(()=>swap(document)).observe(document.getElementById('app'),{childList:true,subtree:true});

 // selo pra baixar/compartilhar com o mascote
 const img=k=>new Promise(r=>{const i=new Image();i.onload=()=>r(i);i.onerror=()=>r(null);
   i.src='data:image/svg+xml;charset=utf-8,'+encodeURIComponent(MASCOT[k].replace('<svg ','<svg xmlns="http://www.w3.org/2000/svg" width="600" height="600" '))});
 badgeFile=async function(k){
  try{await document.fonts.ready}catch(_){}
  const t=L[lang].T[k],u=U();const W=1080,H=1350;const c=document.createElement('canvas');c.width=W;c.height=H;const g=c.getContext('2d');
  const col=HEX[k];g.fillStyle='#0F1422';g.fillRect(0,0,W,H);
  [[160,180,90,.18],[930,260,140,.14],[880,1120,110,.16],[120,1080,60,.2],[540,120,30,.25]].forEach(([x,y,r,a])=>{g.beginPath();g.arc(x,y,r,0,7);g.strokeStyle=col;g.globalAlpha=a;g.lineWidth=6;g.stroke();g.globalAlpha=1});
  const m=await img(k);if(m)g.drawImage(m,W/2-280,170,560,560);
  g.fillStyle='#fff';g.textAlign='center';g.textBaseline='alphabetic';
  g.font='800 96px "Bricolage Grotesque","Noto Sans SC",sans-serif';g.fillText(t[0],W/2,860);
  g.fillStyle='#C9D1E6';g.font='500 44px "IBM Plex Sans","Noto Sans SC",sans-serif';wrap(g,t[1],W/2,940,880,58);
  g.fillStyle=col;g.font='500 40px "IBM Plex Sans","Noto Sans SC",sans-serif';g.fillText('“'+t[5]+'”',W/2,1090,960);
  g.fillStyle='#fff';g.font='800 52px "Bricolage Grotesque","Noto Sans SC",sans-serif';g.fillText(u.andyou,W/2,1210);
  g.fillStyle='#9AA4BB';g.font='500 34px "IBM Plex Mono",monospace';g.fillText('whatsyourbubble.com',W/2,1280);
  const b=await new Promise(r=>c.toBlob(r,'image/png'));
  return b?new File([b],`whatsyourbubble-${k.toLowerCase()}.png`,{type:'image/png'}):null;
 };
})();
