/**
 * Функция за синхронизиране между регистрирания и желания ServiceWorker
 */
function syncServiceWorker() {
    if(!isIE() && ('serviceWorker' in navigator)) {

        // var serviceWorkerURL = document.head;
        var selector = document.querySelector('link[rel="manifest"]');

        var serviceWorkerURL = '/serviceWorker.js';
        var act = 'unregister';

        if (selector) {

            act = 'exist';

            var swDate = selector.getAttribute('data-sw-date');
            var lastUpdate = localStorage.getItem('data-sw-date');

            if (swDate) {
                // Ще предизвика инвалидиране на кеша за този файл
                serviceWorkerURL += '?v=' + swDate;

                if ((lastUpdate !== null) && (lastUpdate !== 'null')) {

                    var lastUpdate = new Date(lastUpdate);
                    var swDate = new Date(swDate);

                    if (lastUpdate < swDate) {
                        act = 'update';
                    }
                } else {
                    act = 'register';
                }
            }
        }

        if(typeof navigator.serviceWorker !== 'undefined') {
            navigator.serviceWorker.getRegistrations().then(function(r) {
                if (act == 'unregister') {
                    r.forEach(function(sw) {
                        sw.unregister();
                        console.log('ServiceWorker registration unregistered: ' + sw.active.scriptURL);

                        localStorage.setItem('data-sw-date', null);
                    });
                }

                if (act == 'register') {
                    navigator.serviceWorker.register(serviceWorkerURL, {scope: '/'}).then(function(registration) {
                        // Registration was successful
                        console.log('ServiceWorker registration successful: ' + serviceWorkerURL);

                        localStorage.setItem('data-sw-date', new Date());
                    }, function(err) {
                        // registration failed :(
                        console.log('ServiceWorker registration failed: ', err);
                    });
                }

                if (act == 'update') {
                    navigator.serviceWorker.register(serviceWorkerURL, {scope: '/'}).then(function(registration) {
                        registration.update().then(function(uRegistratrion) {
                            console.log('ServiceWorker update successful: ' + serviceWorkerURL);

                            localStorage.setItem('data-sw-date', new Date());
                        }, function(err) {
                            // registration failed :(
                            console.log('ServiceWorker update failed: ', err);
                        });
                        // Registration was successful
                    }, function(err) {
                        // registration failed :(
                        console.log('ServiceWorker update failed: ', err);
                    });
                }
            })
        }
    }
}