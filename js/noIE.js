function unregisterServiceWorker() {
    if($('#main-container').length && !$('link[rel="manifest"]').length) {
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
            for(var  registration of registrations) {
                registration.unregister();
            }
        });
    }
}

runOnLoad(unregisterServiceWorker);