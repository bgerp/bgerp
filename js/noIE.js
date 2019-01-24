function unregisterServiceWorker() {
    if(!$('link[rel="manifest"]').length) {
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
            for(var  registration of registrations) {
                registration.unregister();
            }
        });
    }
}

runOnLoad(unregisterServiceWorker);