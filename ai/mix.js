// Texto do resultado baseado nas porcentagens
(function(){
 const M={
  pt:{pure:"Você é {p}% {a}. Um caso puro: quase tudo que você faz com IA passa por esse jeito.",
      strong:"Você é {p}% {a}. É o seu jeito dominante, mas não o único.",
      mixed:"Você é {p}% {a}, quase empatado com {b} ({q}%). Uma bolha mista, e isso é mais raro.",
      second:"Seu segundo lado é {b} ({q}%): {s}",
      secret:"Empate perfeito: 25% de cada perfil. É um dos resultados mais raros do teste.",
      S:{A:"antes de agir, você ainda organiza bem o pedido.",D:"você não engole resposta sem conferir.",M:"você junta fontes e enxerga o quadro todo.",F:"quando precisa, você corta o papo e entrega."}},
  en:{pure:"You're {p}% {a}. A pure case: almost everything you do with AI goes through this style.",
      strong:"You're {p}% {a}. It's your main style, but not the only one.",
      mixed:"You're {p}% {a}, almost tied with {b} ({q}%). A mixed bubble, and that's rarer.",
      second:"Your second side is {b} ({q}%): {s}",
      secret:"A perfect tie: 25% of each type. One of the rarest results in the test.",
      S:{A:"before acting, you still frame the request well.",D:"you don't swallow an answer without checking it.",M:"you combine sources and see the whole picture.",F:"when it counts, you cut the talk and deliver."}},
  zh:{pure:"你是{p}%的{a}。纯正型：你用AI做的几乎每件事都是这个风格。",
      strong:"你是{p}%的{a}。这是你的主要风格，但不是唯一的。",
      mixed:"你是{p}%的{a}，和{b}（{q}%）几乎打平。混合型泡泡，更少见。",
      second:"你的第二面是{b}（{q}%）：{s}",
      secret:"完美平局：每种类型各25%。这是测试中最稀有的结果之一。",
      S:{A:"行动前你仍会把需求理清楚。",D:"你不会不经核实就接受答案。",M:"你会整合来源，看到全局。",F:"关键时刻，你少说多做，直接交付。"}}
 };
 const st=document.createElement('style');
 st.textContent='.mixline{margin:0;font-size:clamp(20px,5.2vw,23px);line-height:1.45}.mixline b{font-weight:700}';
 document.head.appendChild(st);
 const nm=k=>TN(k).replace(/^(O |The )/,'');
 function add(){
  const q=document.querySelector('.res-head ~ .quote, .card .quote');
  if(!q||q.parentNode.querySelector('.mixline'))return;
  const tot=Object.values(score).reduce((a,b)=>a+b,0);if(!tot)return;
  const ks=Object.keys(score).sort((a,b)=>score[b]-score[a]);const [k1,k2]=ks;
  const p=Math.round(score[k1]/tot*100),qq=Math.round(score[k2]/tot*100);const m=M[lang]||M.en;
  const b=s=>`<b>${esc(s)}</b>`;
  const lines=[];
  if(window.isSecret&&isSecret()){q.insertAdjacentHTML('afterend',`<p class="mixline">${esc(m.secret)}</p>`);return;}
  if(qq>0&&p-qq<=12) lines.push(fmt(esc(m.mixed),{p,a:b(nm(k1)),b:b(nm(k2)),q:qq}));
  else{lines.push(fmt(esc(m[p>=75?'pure':'strong']),{p,a:b(nm(k1))}));
       if(qq>0) lines.push(fmt(esc(m.second),{b:b(nm(k2)),q:qq,s:esc(((L[lang]||{}).S2||m.S)[k2])}));}
  q.insertAdjacentHTML('afterend',lines.map(l=>`<p class="mixline">${l}</p>`).join(''));
 }
 new MutationObserver(add).observe(document.getElementById('app'),{childList:true,subtree:true});
})();
