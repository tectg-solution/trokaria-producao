(()=>{'use strict';const $=s=>document.querySelector(s);const toast=text=>{const el=$('#toast');if(!el)return;el.textContent=text;el.classList.add('show');setTimeout(()=>el.classList.remove('show'),3500);};const menu=$('.menu-button');menu?.addEventListener('click',()=>{const open=$('#navigation').classList.toggle('is-open');menu.setAttribute('aria-expanded',String(open));});
document.addEventListener('click',async event=>{const button=event.target.closest('[data-favorite]');if(!button||button.disabled)return;button.disabled=true;try{const body=new URLSearchParams({action:'favorite',id:button.dataset.favorite,csrf:$('meta[name=csrf-token]').content});const res=await fetch('index.php?r=home',{method:'POST',body,credentials:'same-origin',headers:{Accept:'application/json'}});if(!res.headers.get('Content-Type')?.includes('application/json'))throw Error('Sua sessão expirou. Entre novamente.');const data=await res.json();if(data.login){location.href=data.login;return;}if(!res.ok||data.error)throw Error(data.error||'Não foi possível salvar o favorito.');button.setAttribute('aria-pressed',String(data.favorite));toast(data.favorite?'Item salvo nos favoritos.':'Item removido dos favoritos.');}catch(error){toast(error.message);}finally{button.disabled=false;}});
const uploads=$('input[name="photos[]"]');let previews=[];uploads?.addEventListener('change',()=>{previews.forEach(URL.revokeObjectURL);previews=[];const holder=$('#upload-preview');holder.replaceChildren();if(uploads.files.length>4){uploads.setCustomValidity('Selecione até quatro fotos.');toast('Selecione até quatro fotos.');return;}uploads.setCustomValidity('');for(const file of uploads.files){if(file.size>5*1024*1024){uploads.setCustomValidity('Cada foto deve ter até 5 MB.');toast('Uma das fotos excede 5 MB.');break;}if(!['image/jpeg','image/png','image/webp'].includes(file.type)){uploads.setCustomValidity('Use JPG, PNG ou WebP.');break;}const image=document.createElement('img');image.alt='Prévia da foto selecionada';image.src=URL.createObjectURL(file);previews.push(image.src);holder.append(image);}});
const list=$('[data-thread]');
if(list){
 let signature='',inFlight=false,timer=null,failures=0,forceBottom=false,stopped=false;
 const feedback=document.createElement('p');feedback.className='muted';feedback.setAttribute('role','status');feedback.setAttribute('aria-live','polite');list.after(feedback);
 const draw=messages=>{
  const next=JSON.stringify(messages);if(next===signature&&!forceBottom)return;signature=next;
  const nearBottom=forceBottom||list.scrollTop+list.clientHeight>=list.scrollHeight-80;const oldTop=list.scrollTop;forceBottom=false;
  const frag=document.createDocumentFragment();
  for(const m of messages){
   const article=document.createElement('article');article.className='message'+(m.mine?' mine':'');
   const name=document.createElement('strong');name.textContent=m.name;
   const body=document.createElement('p');body.className='preserve-text';body.textContent=m.body;
   const time=document.createElement('small');time.textContent=m.date+' UTC';article.append(name,body,time);
   if(m.status!=='ACTIVE'){const status=document.createElement('span');status.className='status-pill';status.textContent=m.status==='REJECTED'?'Mensagem recusada':'Aguardando moderação';article.append(status);}
   else{const a=document.createElement('a');a.className='message-report';a.textContent='Denunciar';a.href='index.php?r=report&proposal_id='+encodeURIComponent(list.dataset.thread)+'&message_id='+encodeURIComponent(m.id);article.append(a);}
   frag.append(article);
  }
  list.replaceChildren(frag);list.scrollTop=nearBottom?list.scrollHeight:oldTop;
 };
 const poll=async()=>{
  clearTimeout(timer);if(stopped||document.hidden||inFlight)return;inFlight=true;
  const controller=new AbortController();const timeout=setTimeout(()=>controller.abort(),10000);
  try{
   const res=await fetch('index.php?r=thread-json&id='+encodeURIComponent(list.dataset.thread),{credentials:'same-origin',cache:'no-store',signal:controller.signal,headers:{Accept:'application/json'}});
   if(res.status===401||res.status===403){stopped=true;throw Error('Conversa indisponível. Entre novamente ou volte para suas conversas.');}
   if(!res.ok||!res.headers.get('Content-Type')?.includes('application/json'))throw Error('Conexão interrompida. Tentando atualizar novamente…');
   const data=await res.json();if(!Array.isArray(data.messages))throw Error('Não foi possível atualizar a conversa.');
   draw(data.messages);failures=0;feedback.textContent='Atualização automática ativa.';
  }catch(error){failures++;feedback.textContent=stopped?error.message:'Sem conexão com a conversa. Tentando novamente…';}
  finally{clearTimeout(timeout);inFlight=false;if(!stopped&&!document.hidden)timer=setTimeout(poll,Math.min(3000*Math.pow(2,Math.min(failures,3)),24000));}
 };
 list.scrollTop=list.scrollHeight;poll();
 document.addEventListener('visibilitychange',()=>{clearTimeout(timer);if(!document.hidden)poll();});
 window.addEventListener('focus',poll);
 window.addEventListener('pagehide',()=>{stopped=true;clearTimeout(timer);});
 window.addEventListener('pageshow',event=>{if(event.persisted){stopped=false;poll();}});
 const form=Array.from(document.querySelectorAll('form')).find(f=>f.querySelector('input[name="action"][value="message"]'));
 if(form){
  form.dataset.chatAsync='true';let sending=false;
  form.addEventListener('submit',async event=>{
   event.preventDefault();if(sending||!form.checkValidity())return;
   const input=form.querySelector('[name="body"]');if(!input.value.trim())return;
   sending=true;const body=new FormData(form);const sentDraft=input.value;const button=form.querySelector('button[type="submit"]');const label=button.textContent;button.disabled=true;button.textContent='Enviando…';
   const controller=new AbortController();const timeout=setTimeout(()=>controller.abort(),40000);
   try{
    const res=await fetch(form.action,{method:'POST',body,credentials:'same-origin',signal:controller.signal,headers:{Accept:'application/json'}});
    if(!res.headers.get('Content-Type')?.includes('application/json'))throw Error('Não foi possível confirmar o envio. Confira a conversa antes de reenviar.');
    const data=await res.json();if(!res.ok||!data.ok)throw Error(data.error||'Não foi possível enviar a mensagem.');
    if(input.value===sentDraft)input.value='';forceBottom=true;input.focus();poll();
    toast(data.status==='ACTIVE'?'Mensagem enviada.':'Mensagem enviada e aguardando moderação.');
   }catch(error){toast(error.name==='AbortError'?'Não foi possível confirmar o envio. Confira a conversa antes de reenviar.':error.message);}
   finally{clearTimeout(timeout);sending=false;button.disabled=false;button.textContent=label;}
  });
 }
}
if(document.body.dataset.notifications==='enabled'){
 let timer=null,busy=false,initialized=false,seen=new Set(),stopped=false;
 const update=async()=>{
  clearTimeout(timer);if(document.hidden||busy||stopped)return;busy=true;
  const controller=new AbortController();const timeout=setTimeout(()=>controller.abort(),10000);
  try{
   const res=await fetch('index.php?r=notifications-json',{credentials:'same-origin',cache:'no-store',signal:controller.signal,headers:{Accept:'application/json'}});
   if(res.status===401){stopped=true;document.querySelectorAll('[data-notification-count],[data-notification-summary]').forEach(el=>el.hidden=true);return;}
   if(!res.ok)throw Error('notifications');const data=await res.json();
   document.querySelectorAll('[data-notification-count]').forEach(el=>{el.textContent=data.threads>99?'99+':String(data.threads);el.hidden=!data.threads;el.setAttribute('aria-label',data.threads+(data.threads===1?' conversa com novidades':' conversas com novidades'));});
   const summary=$('[data-notification-summary]');if(summary){summary.hidden=!data.threads;summary.textContent=[data.messages?data.messages+(data.messages===1?' mensagem não lida':' mensagens não lidas'):'',data.proposals?data.proposals+(data.proposals===1?' nova proposta':' novas propostas'):''].filter(Boolean).join(' · ')+' →';}
   document.querySelectorAll('[data-inbox-thread]').forEach(row=>{const count=data.events.filter(e=>e.kind==='message'&&String(e.thread)===row.dataset.inboxThread).length;const badge=row.querySelector('[data-thread-unread]');if(badge){badge.hidden=!count;badge.textContent=count+(count===1?' nova mensagem':' novas mensagens');}});
   const additions=data.events.filter(e=>!seen.has(e.key));const activeThread=$('[data-thread]')?.dataset.thread;
   if(initialized&&additions.some(e=>String(e.thread)!==activeThread)){toast(additions.some(e=>e.kind==='proposal')?'Você recebeu uma nova proposta! Veja suas conversas.':'Você recebeu uma nova mensagem! Veja suas conversas.');}
   seen=new Set(data.events.map(e=>e.key));initialized=true;
   if('setAppBadge' in navigator){if(data.threads)navigator.setAppBadge(data.threads).catch(()=>{});else navigator.clearAppBadge?.().catch(()=>{});}
  }catch{}finally{clearTimeout(timeout);busy=false;if(!stopped&&!document.hidden)timer=setTimeout(update,10000);}
 };
 update();window.addEventListener('focus',update);document.addEventListener('visibilitychange',()=>{clearTimeout(timer);if(!document.hidden)update();});
 window.addEventListener('pagehide',()=>{stopped=true;clearTimeout(timer);});window.addEventListener('pageshow',e=>{if(e.persisted){stopped=false;update();}});
}
document.querySelectorAll('form[method=post]').forEach(form=>form.addEventListener('submit',()=>{if(form.dataset.chatAsync==='true'||!form.checkValidity())return;const button=form.querySelector('button[type=submit]');if(button){button.disabled=true;button.textContent='Aguarde…';}}));})();
