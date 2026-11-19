(()=>{'use strict';
 const button=document.querySelector('[data-install-app]');let promptEvent=null;
 if(document.body.dataset.notifications!=='enabled'&&'clearAppBadge' in navigator)navigator.clearAppBadge().catch(()=>{});
 const installed=()=>window.matchMedia('(display-mode: standalone)').matches||navigator.standalone===true;
 if(button)button.hidden=installed();
 if('serviceWorker' in navigator&&window.isSecureContext){window.addEventListener('load',()=>{navigator.serviceWorker.register('sw.js',{updateViaCache:'none'}).catch(()=>{});});}
 window.addEventListener('beforeinstallprompt',event=>{event.preventDefault();promptEvent=event;if(button)button.hidden=false;});
 window.addEventListener('appinstalled',()=>{promptEvent=null;if(button)button.hidden=true;});
 button?.addEventListener('click',async()=>{
  if(promptEvent){button.disabled=true;try{await promptEvent.prompt();await promptEvent.userChoice;promptEvent=null;}finally{button.disabled=false;}return;}
  const dialog=document.createElement('dialog');dialog.className='install-dialog';dialog.setAttribute('aria-labelledby','install-title');
  const title=document.createElement('h2');title.id='install-title';title.textContent='Leve o Trokar.ia com você';
  const text=document.createElement('p');text.textContent=/iPhone|iPad|iPod/.test(navigator.userAgent)?'No Safari, toque em Compartilhar e depois em Adicionar à Tela de Início.':'Abra o menu do navegador e procure Instalar aplicativo ou Adicionar à tela inicial. Se a opção ainda não aparecer, continue usando o site e tente novamente.';
  const close=document.createElement('button');close.type='button';close.className='button primary';close.textContent='Entendi';close.addEventListener('click',()=>dialog.close());dialog.addEventListener('close',()=>dialog.remove());dialog.append(title,text,close);document.body.append(dialog);dialog.showModal();
 });
})();
