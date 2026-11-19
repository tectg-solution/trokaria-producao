/* Only the generic offline screen is stored. Accounts, APIs, photos and messages stay network-only. */
const CACHE='trokar-offline-v1';const OFFLINE=new URL('offline.html',self.registration.scope).href;
self.addEventListener('install',event=>{event.waitUntil(caches.open(CACHE).then(cache=>cache.add(new Request(OFFLINE,{cache:'reload'}))).then(()=>self.skipWaiting()));});
self.addEventListener('activate',event=>{event.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(k=>k.startsWith('trokar-offline-')&&k!==CACHE).map(k=>caches.delete(k)))).then(()=>self.clients.claim()));});
self.addEventListener('fetch',event=>{
 const request=event.request;if(request.method!=='GET'||request.mode!=='navigate'||new URL(request.url).origin!==self.location.origin)return;
 event.respondWith(fetch(request).catch(()=>caches.match(OFFLINE)));
});
