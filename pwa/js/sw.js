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


self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    if (event.notification.data.url) {
        event.waitUntil(
            clients.openWindow(event.notification.data.url)
        );
    }
})


self.addEventListener('push', function (event) {

    if (!(self.Notification && self.Notification.permission === 'granted')) {

        return;
    }

    const sendNotification = body => {
        let opt = {};

        bodyData = JSON.parse(body);

        const title = bodyData.title;
        opt.body = bodyData.text;
        if (bodyData.icon) {
            opt.icon = bodyData.icon;
            opt.badge = bodyData.icon;
        }

        if (bodyData.badge) {
            opt.badge = bodyData.badge;
        }

        if (bodyData.image) {
            opt.image = bodyData.image;
        }

        if (bodyData.vibrate) {
            opt.vibrate = [200, 100, 300, 100, 400, 100, 500];
        }

        if (!bodyData.sound) {
            opt.silent = true;
        }

        if (bodyData.tag) {
            opt.tag = bodyData.tag;
        }

        if (bodyData.url) {
            opt.data = {url: bodyData.url};
        }

        return self.registration.showNotification(title, opt);
    };

    if (event.data) {
        const message = event.data.text();
        // clients.matchAll({ type: 'window' }).then(function(clientList) {
            // const client = clientList.find(c => c.visibilityState === 'visible');
            // if (!client) {
                event.waitUntil(sendNotification(message));
            // }
        // });
    }
});
