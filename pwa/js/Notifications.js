document.addEventListener('DOMContentLoaded', () => {
    let isPushEnabled = false;
    if (typeof pwaSubsctiptionUrl === 'undefined') {
        pwaSubsctiptionUrl = 'bgerp/pwa_PushSubscriptions/Subscribe';
    }

    if (typeof forceSubscibe === 'undefined') {
        forceSubscibe = 'no';
    }

    // Бутона за абониране и отписване от известия
    const pushButton = document.querySelector('#push-subscription-button');
    const pushButtonUnsubscribe = document.querySelector('#push-subscription-button-unsubscribe');
    if (!pushButton && !pushButtonUnsubscribe) {

        // return;
    }

    // Проверява състоянието на абониране
    check_subscription();

    if (pushButton) {
        pushButton.addEventListener('click', function() {
            if (isPushEnabled) {
                getEfae().process({url: pwaSubsctiptionUrl}, {haveSubscription: 1}, false);

                return ;
            } else {
                push_subscribe();
            }
        });
    }

    if (pushButtonUnsubscribe) {
        pushButtonUnsubscribe.addEventListener('click', function() {
            push_unsubscribe();
        });
    }

    // Проверяваме дали браузърът поддържа Service Workers и Push Notifications
    if (!('serviceWorker' in navigator)) {
        changePushButtonState('incompatible');
        return;
    }

    if (!('PushManager' in window)) {
        changePushButtonState('incompatible');
        return;
    }

    if (!('showNotification' in ServiceWorkerRegistration.prototype)) {
        changePushButtonState('incompatible');
        return;
    }

    // Ako потребителят е отказал известията, не можем да го абонираме
    if (Notification.permission === 'denied') {
        changePushButtonState('denied');
        return;
    }

    /**
     * Проверява и променя състояението на бутона за абониране, спрямо зададени опции
     *
     * @param state
     *
     * @returns {boolean}
     */
    function changePushButtonState(state)
    {
        let buttonToWork = pushButton;
        if (!buttonToWork) {

            buttonToWork = pushButtonUnsubscribe;
        }

        if (!buttonToWork) {

            return ;
        }
        buttonToWork.classList.add('pwa-push-' + state);

        var pushButtonValue = buttonToWork.value;
        var pushButtonTitle = buttonToWork.title;

        if (state && typeof pushButtonVals != 'undefined' && pushButtonVals[state]) {
            pushButtonValue = pushButtonVals[state].btnText;
            pushButtonTitle = pushButtonVals[state].btnTitle;
        }

        // Променяме класа и надписа на бутона, в завиисмост от състоянието
        switch (state) {
            case 'enabled':
                buttonToWork.disabled = false;
                isPushEnabled = true;
                break;
            case 'disabled':
                buttonToWork.disabled = false;
                isPushEnabled = false;
                break;
            case 'computing':
                buttonToWork.disabled = true;
                break;
            case 'incompatible':
                buttonToWork.onclick = function() { alert(buttonToWork.title); };
                break;
            case 'denied':
                buttonToWork.onclick = function() { alert(buttonToWork.title); };
                break;
            default:
                break;
        }

        buttonToWork.value = pushButtonValue;
        buttonToWork.title = pushButtonTitle;
    }


    /**
     * Проверява дали потребителят е абониран за известия и дали е дал разрешение
     *
     * @returns {Promise<unknown>}
     */
    function checkNotificationPermission()
    {
        return new Promise((resolve, reject) => {
            if (Notification.permission === 'denied') {
                return reject(new Error('Push messages are blocked.'));
            }

            if (Notification.permission === 'granted') {
                return resolve();
            }

            if (Notification.permission === 'default') {
                return Notification.requestPermission().then(result => {
                    if (result !== 'granted') {
                        reject(new Error('Bad permission result'));
                    } else {
                        resolve();
                    }
                });
            }

            return reject(new Error('Unknown permission'));
        });
    }


    /**
     * Проверява дали потребителят е абониран за известия
     */
    function check_subscription() {
        navigator.serviceWorker.ready
            .then(serviceWorkerRegistration => serviceWorkerRegistration.pushManager.getSubscription())
            .then(subscription => subscription).then(subscription => subscription && changePushButtonState('enabled'));
    }


    /**
     * Абониране за известия
     *
     * @returns {Promise<*>}
     */
    function push_subscribe() {
        changePushButtonState('computing');

        return checkNotificationPermission()
            .then(() => navigator.serviceWorker.ready)
            .then(serviceWorkerRegistration =>
                serviceWorkerRegistration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(applicationServerKey),
                })
            )
            .then(subscription => {
                // Пращаме заявка към сървъра, след успешно абониране
                return push_sendSubscriptionToServer(subscription, 'subscribe');
            })
            .then(subscription => subscription && changePushButtonState('enabled')) // update your UI
            .catch(e => {
                if (Notification.permission === 'denied') {
                    // Потребителят е отказал известията
                    changePushButtonState('denied');
                } else {
                    // Не сме успели да абонираме потребителя
                    changePushButtonState('disabled');
                }
            });
    }


    /**
     * Отписване от получаване на известия
     */
    function push_unsubscribe() {
        changePushButtonState('computing');

        // To unsubscribe from push messaging, you need to get the subscription object
        navigator.serviceWorker.ready
            .then(serviceWorkerRegistration => serviceWorkerRegistration.pushManager.getSubscription())
            .then(subscription => {
                // Ако нямаме абонамент, няма нужда да правим нищо, освен да сменим състоянието на бутона
                if (!subscription) {
                    changePushButtonState('disabled');
                    return;
                }

                // Махаме абонамента за получаване на известия
                return push_sendSubscriptionToServer(subscription, 'unsubscribe');
            })
            .then(subscription => subscription.unsubscribe())
            .then(() => changePushButtonState('disabled'))
            .catch(e => {
                // При грешка, променяме състоянието на бутона
                changePushButtonState('disabled');
            });
    }


    /**
     * Изпраща заявка към сървъра след абониране
     *
     * @param subscription
     * @param action
     *
     * @returns {Promise<Response>}
     */
    function push_sendSubscriptionToServer(subscription, action) {
        const key = subscription.getKey('p256dh');
        const token = subscription.getKey('auth');
        const contentEncoding = (PushManager.supportedContentEncodings || ['aesgcm'])[0];

        var publicKey = key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null;
        var authToken = token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null;
        var endpoint = subscription.endpoint;
        var subscription = subscription;

        if (typeof redirectUrl === 'undefined') {
            redirectUrl = 'none';
        }

        getEfae().process({url: pwaSubsctiptionUrl}, {action: action, publicKey: publicKey, authToken: authToken, endpoint: endpoint, contentEncoding: contentEncoding, redirectUrl: redirectUrl}, false);

        return subscription;
    }


    /**
     * Помощна функция
     *
     * @param base64String
     * @returns {Uint8Array}
     */
    function urlBase64ToUint8Array(base64String)
    {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    // Ако е зададено форсирано абониране, го правим
    if (forceSubscibe == 'yes') {
        if (isPushEnabled) {
            getEfae().process({url: pwaSubsctiptionUrl}, {haveSubscription: 1}, false);
        } else {
            push_subscribe();
        }
    }
});
