addEventListener('install', function() {
  skipWaiting();
  console.log('install');
});

addEventListener('activate', function() {
  clients.claim();
  console.log('activate');
});

addEventListener('fetch', function (event) {
  if (event.request.url.indexOf('/pwa_Share/Target') == -1) {
	  
	  return ;
  }
  
  if (event.request.method !== 'POST') {
// event.respondWith(fetch(event.request));
	  return;
  }
  
// event.respondWith(Response.redirect('/doc_Files'));
  
  event.waitUntil(async function () {
    const data = await event.request.formData();
    const client = await self.clients.get(event.resultingClientId);
    const allF = data.getAll('file');
    
    var haveFile = false;
    for (const file of allF) {
	    data.append('ulfile[]', file);
	    haveFile = true;
	}
    
    if (!haveFile) {
    	const link = data.get('link') || data.get('description') || data.get('name') || '';
    	if (link) {
    		data.set('link', link);
    	}
    }
    
// await fetch('/pwa_Share/Target', {method: "POST", body: data}).then(response => {client.postMessage( response.url );});
    await fetch('/pwa_Share/Target', {method: "POST", body: data}).then(async function(response) {await client.postMessage( response.url );});
  }());
});
