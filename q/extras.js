// Fim do quiz: guarda a bolha na coleção (só neste aparelho) e sugere o próximo quiz.
// Usado no motor (/q/) e no quiz de IA (/ai/).
(function(){
 const ID=window.QUIZ_ID||'ia';
 const X={
  pt:{next:'Próximo quiz',play:'Jogar agora · 1 min',col:'Suas bolhas até agora',feitos:'Você já fez {a} de {b} quizzes.',todos:'Ver todos os quizzes'},
  en:{next:'Next quiz',play:'Play now · 1 min',col:'Your bubbles so far',feitos:"You've done {a} of {b} quizzes.",todos:'See all quizzes'},
  zh:{next:'下一个测验',play:'现在就玩 · 1分钟',col:'你目前的泡泡',feitos:'你已经完成了{a}/{b}个测验。',todos:'查看全部测验'}};
 const get=()=>{try{return JSON.parse(localStorage.getItem('wyb-bolhas')||'{}')||{}}catch(_){return{}}};
 const save=(k)=>{try{const b=get();b[ID]=k;localStorage.setItem('wyb-bolhas',JSON.stringify(b))}catch(_){}};
 const st=document.createElement('style');st.textContent=`
 .nextq{background:#141B2E;color:#fff;border-radius:18px;padding:18px;display:flex;flex-direction:column;gap:12px}
 .nextq .tag{color:#9AA4BB}.nextq .nx{display:flex;gap:14px;align-items:center;color:#fff;text-decoration:none}
 .nextq .nm{width:64px;height:64px;flex:none}.nextq svg,.colq svg{width:100%;height:100%;display:block}
 .nextq b{font-family:var(--display);font-size:clamp(20px,5.4vw,24px);line-height:1.15}
 .nextq .btn{display:flex;align-items:center;justify-content:center;text-decoration:none}
 .colq .mini{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:6px}
 .colq .mi{aspect-ratio:1;display:grid;place-items:center}
 .colq .qq{width:88%;aspect-ratio:1;border-radius:50%;border:2px dashed var(--muted);display:grid;place-items:center;font-weight:800;color:var(--muted);opacity:.7}
 .colq a.all{color:var(--accent);font-weight:700;text-decoration:none}`;
 document.head.appendChild(st);
 let lista=null,pedindo=false;
 function carregar(){if(lista||pedindo)return;pedindo=true;
  fetch('/q/indice.php').then(r=>r.json()).then(d=>{lista=(d&&d.quizzes)||[];add()}).catch(()=>{lista=[]});}
 let salvo='';
 function add(){
  if(typeof last==='undefined'||!last||!document.querySelector('.res-head'))return;
  const key=ID+last.k;if(salvo!==key){save(last.k);salvo=key;}
  if(!lista){carregar();return;}
  if(document.querySelector('.nextq'))return;
  const anchor=[...document.querySelectorAll('#app > .card')].find(c=>c.querySelector('#nm'));if(!anchor)return;
  const x=X[lang]||X.en,b=get(),tit=q=>q.titulo[lang]||q.titulo.en;
  const outros=lista.filter(q=>q.slug!==ID);
  const prox=outros.find(q=>!b[q.slug])||outros[0];
  const feitos=lista.filter(q=>b[q.slug]),faltam=lista.filter(q=>!b[q.slug]).slice(0,Math.max(0,6-feitos.length));
  const M=k=>(window.MASCOT&&MASCOT[k])||'';
  let html='';
  if(prox) html+=`<div class="nextq"><span class="tag">${esc(x.next)}</span>
   <a class="nx" href="${esc(prox.url)}"><div class="nm">${M(prox.mascote)}</div><b>${esc(tit(prox))}</b></a>
   <a class="btn" href="${esc(prox.url)}">${esc(x.play)}</a></div>`;
  if(lista.length>1) html+=`<div class="card colq"><span class="tag">${esc(x.col)}</span>
   <div class="mini">${feitos.slice(0,6).map(q=>`<a class="mi" href="${esc(q.url)}" title="${esc(tit(q))}">${M(b[q.slug])}</a>`).join('')}${faltam.map(q=>`<a class="mi" href="${esc(q.url)}" title="${esc(tit(q))}"><span class="qq">?</span></a>`).join('')}</div>
   <p class="fine">${esc(x.feitos.replace('{a}',feitos.length).replace('{b}',lista.length))} <a class="all" href="/q/">${esc(x.todos)}</a></p></div>`;
  if(html)anchor.insertAdjacentHTML('afterend',html);
 }
 new MutationObserver(add).observe(document.getElementById('app'),{childList:true,subtree:true});
})();
