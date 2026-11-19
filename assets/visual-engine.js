(()=>{'use strict';
 const node=document.getElementById('visual-config');if(!node)return;const config=JSON.parse(node.textContent),original=new Map(),elements=new Map();
 const rootTags=['header','main','footer'];
 const keyOf=el=>{const root=el.closest('header,main,footer');if(!root)return null;let parts=[];for(let n=el;n&&n!==root;n=n.parentElement){const siblings=[...n.parentElement.children].filter(s=>s.tagName===n.tagName);parts.unshift(n.tagName.toLowerCase()+':'+siblings.indexOf(n));}return root.tagName.toLowerCase()+'/'+parts.join('/');};
 const sensitive=el=>{
  if(el.closest('.status-pill,.listing-card,.inbox-row,.message,.match-group,.stats,.user-row,.admin-review,.table-wrap'))return true;
  if(config.screen==='account'&&el.closest('.page-heading')&&!el.classList.contains('eyebrow'))return true;
  if(config.screen==='account'&&el.matches('main>section.panel p,main>section.panel strong'))return true;
  if(config.screen==='thread'&&el.closest('.page-heading')&&!el.classList.contains('eyebrow'))return true;
  if(el.closest('.listing-detail,.listing-manage')&&!el.closest('a.button,a.text-button,button,label,p.help'))return true;
  if(config.screen==='propose'&&el.matches('.form-panel>p:first-of-type'))return true;
  return false;
 };
 const textNodes=el=>[...el.childNodes].filter(n=>n.nodeType===3&&n.textContent.trim());
 for(const root of document.querySelectorAll(rootTags.join(',')))for(const el of [root,...root.querySelectorAll('*')]){
  if(el.closest('svg,script,style')||['INPUT','TEXTAREA','SELECT','OPTION','FORM','BR','META','LINK'].includes(el.tagName))continue;
  const key=keyOf(el);if(!key)continue;elements.set(key,el);el.dataset.visualKey=key;original.set(key,{style:el.getAttribute('style'),texts:textNodes(el).map(n=>[n,n.textContent])});
 }
 let device=window.innerWidth<=760?'mobile':'desktop';
 const editableText=el=>!sensitive(el)&&textNodes(el).length===1;
 const applyOne=(key,data)=>{const el=elements.get(key);if(!el)return;const base=original.get(key);if(base.style===null)el.removeAttribute('style');else el.setAttribute('style',base.style);base.texts.forEach(([n,text])=>n.textContent=text);
  if(!data)return;if(typeof data.text==='string'&&editableText(el))textNodes(el)[0].textContent=data.text;
  const s=data.styles||{};for(const prop of ['fontSize','width','height'])if(s[prop]!=null)el.style[prop]=s[prop]+'px';if(s.lineHeight!=null)el.style.lineHeight=s.lineHeight; if(s.translateX!=null||s.translateY!=null)el.style.translate=(s.translateX||0)+'px '+(s.translateY||0)+'px';
 };
 const apply=()=>{for(const key of elements.keys())applyOne(key,config.edits[device]?.[key]);};apply();window.addEventListener('resize',()=>{if(!config.preview){const next=innerWidth<=760?'mobile':'desktop';if(next!==device){device=next;apply();}}});
 window.trokarVisual={elements,editableText,keyOf,get:(key)=>JSON.parse(JSON.stringify(config.edits[device]?.[key]||{styles:{}})),setDevice:value=>{device=value;apply();},set:(key,data)=>{config.edits[device]??={};if(data)config.edits[device][key]=data;else delete config.edits[device][key];applyOne(key,data);}};
 if(config.preview){document.addEventListener('submit',e=>e.preventDefault(),true);document.addEventListener('click',e=>{e.preventDefault();e.stopImmediatePropagation();const el=e.target.closest('[data-visual-key]');if(el)window.parent.dispatchEvent(new CustomEvent('visual-select',{detail:{key:el.dataset.visualKey}}));},true);}
})();
