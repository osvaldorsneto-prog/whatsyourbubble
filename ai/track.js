// Contagem anônima (sem cookies, sem IP): manda só o nome do evento pro servidor
(function(){
 const ping=(e,extra)=>{try{const q=new URLSearchParams({e,...(extra||{})});if(window.QUIZ_ID)q.set('q',window.QUIZ_ID);
   const u=(window.COUNT_URL||'count.php')+'?'+q;navigator.sendBeacon?navigator.sendBeacon(u):fetch(u,{keepalive:true});}catch(_){}};
 ping('view');if(friend)ping('friend');
 let done=false;
 document.addEventListener('click',ev=>{
  const t=ev.target.closest('#go,#sh,#cp,#dl,.btn.vid,.net');if(!t)return;
  if(t.id==='go'){done=false;ping('start',{l:lang});}
  else if(t.id==='sh')ping('share');else if(t.id==='cp')ping('copy');else if(t.id==='dl')ping('dl');
  else if(t.classList.contains('vid'))ping('vid');
  else if(t.classList.contains('net'))ping('net_'+t.dataset.n);
 },true);
 new MutationObserver(()=>{if(!done&&document.querySelector('.res-head')&&last){done=true;ping('finish',{k:last.k});}})
  .observe(document.getElementById('app'),{childList:true,subtree:true});
})();
