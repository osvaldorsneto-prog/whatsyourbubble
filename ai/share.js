// Botões diretos das redes + chamada pro story + desafio
(function(){
 const X={
  pt:{or:"Ou mande direto:",story:"No Instagram ou TikTok: baixe o selo, poste no story e marque 3 amigos pra descobrirem a bolha deles.",dare:"Desafio: marque 3 amigos e descubram quem é de cada bolha.",vid:"Baixar story animado"},
  en:{or:"Or send it straight to:",story:"On Instagram or TikTok: download the badge, post it to your story and tag 3 friends to find their bubble.",dare:"Challenge: tag 3 friends and find out who's in which bubble.",vid:"Download animated story"},
  zh:{or:"或直接发送到：",story:"在Instagram或TikTok：下载徽章，发到快拍，并@3个朋友来测他们的泡泡。",dare:"挑战：@3个朋友，看看谁属于哪个泡泡。",vid:"下载动态快拍"}};
 const NET=[
  ["WhatsApp","#25D366",(t,u)=>`https://wa.me/?text=${encodeURIComponent(t+' '+u)}`],
  ["Facebook","#1877F2",(t,u)=>`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(u)}`],
  ["X","#141B2E",(t,u)=>`https://twitter.com/intent/tweet?text=${encodeURIComponent(t)}&url=${encodeURIComponent(u)}`],
  ["Telegram","#229ED9",(t,u)=>`https://t.me/share/url?url=${encodeURIComponent(u)}&text=${encodeURIComponent(t)}`],
  ["LinkedIn","#0A66C2",(t,u)=>`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(u)}`]];
 const st=document.createElement('style');st.textContent=`
 .nets{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
 .net{display:flex;align-items:center;justify-content:center;gap:10px;min-height:56px;border-radius:14px;border:1px solid var(--line);
   color:var(--ink);text-decoration:none;font-weight:700;font-size:clamp(19px,5vw,21px);background:var(--surface)}
 .net i{width:14px;height:14px;border-radius:50%;flex:none}
 .net:focus-visible{outline:3px solid var(--warm);outline-offset:2px}
 .nets .net:last-child:nth-child(odd){grid-column:1/-1}
 .story{margin:0;font-size:clamp(18px,4.8vw,21px);line-height:1.4;color:var(--muted);border-left:3px solid var(--warm);padding-left:12px}
 .or{margin:0;font-weight:700}
 .btn.vid{display:flex;align-items:center;justify-content:center;text-decoration:none;background:linear-gradient(90deg,#2F4BE8,#7C3AED);color:#fff}`;
 document.head.appendChild(st);
 function add(){
  const btns=document.querySelector('.btns');if(!btns||document.querySelector('.nets')||!last)return;
  const x=X[lang]||X.en,u=U(),t=L[lang].T[last.k];
  const text=()=>fmt(u.shareText,{type:t[0],p:last.p})+' '+x.dare;
  btns.insertAdjacentHTML('afterend',`<p class="story">${esc(x.story)}</p><a class="btn vid" href="story/${last.k.toLowerCase()}-${lang}.mp4" download="whatsyourbubble-${last.k.toLowerCase()}-${lang}.mp4">${esc(x.vid)}</a><p class="or">${esc(x.or)}</p>
   <div class="nets">${NET.map(([n,c])=>`<a class="net" data-n="${n}" href="#" target="_blank" rel="noopener"><i style="background:${c}"></i>${n}</a>`).join('')}</div>`);
  const nmEl=document.getElementById('nm');
  const upd=()=>{const url=shareUrl(last.k,(nmEl&&nmEl.value.trim())||'');
   document.querySelectorAll('.net').forEach(a=>{const f=NET.find(z=>z[0]===a.dataset.n)[2];a.href=f(text(),url)});};
  upd();nmEl&&nmEl.addEventListener('input',upd);
 }
 new MutationObserver(add).observe(document.getElementById('app'),{childList:true,subtree:true});
})();
