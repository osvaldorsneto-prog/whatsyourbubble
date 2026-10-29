// Tela inicial com peso visual nos perfis
(function(){
 const X={pt:{which:"Qual bolha é a sua?",go:"Descobrir a minha",meta:"8 perguntas · 1 minuto"},
          en:{which:"Which bubble is yours?",go:"Find mine",meta:"8 questions · 1 minute"},
          zh:{which:"你属于哪个泡泡？",go:"测出我的",meta:"8道题 · 1分钟"}};
 start=function(){
  stage='start';const u=U();const x=X[lang];
  app.innerHTML=`${inviteCard()}
  ${friend?'':`<span class="tag">${x.meta}</span>`}
  ${friend?`<p class="which">${x.which}</p>`:`<h1 class="hero-h">${u.h1}</h1>`}
  <div class="bub-grid${friend?' compact':''}">${Object.keys(COL).map((k,n)=>`<div class="bub" style="--c:var(${COL[k]});--i:${n}">
    <div class="orb">${k}</div><div class="bt"><b>${esc(TN(k))}</b></div></div>`).join('')}</div>
  <button class="btn cta" id="go">${x.go}</button>
  <p class="fine">${u.fine}</p>`;
  document.getElementById('go').onclick=()=>{i=0;score={A:0,D:0,M:0,F:0};order=L.pt.Q.map(()=>shuffle(Object.keys(COL)));ask()};
 };
 if(stage==='start')start();
})();
