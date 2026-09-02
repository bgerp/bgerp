(function () {
    'use strict';

    var STATE_CLASSES = [
        'pwa-push-default',
        'pwa-push-enabled',
        'pwa-push-disabled',
        'pwa-push-renew',
        'pwa-push-computing',
        'pwa-push-incompatible',
        'pwa-push-denied'
    ];
    var SERVICE_WORKER_TIMEOUT = 15000;
    var PUSH_OPERATION_TIMEOUT = 15000;
    var PERMISSION_TIMEOUT = 60000;
    var SERVER_TIMEOUT = 20000;
    var FOREGROUND_REFRESH_DELAY = 150;

    function initNotifications() {
        var pushButton = document.querySelector('#push-subscription-button');
        var unsubscribeButton = document.querySelector('#push-subscription-button-unsubscribe');
        if (!pushButton && !unsubscribeButton) {
            return;
        }

        // The script can be present in an AJAX fragment more than once.
        var initElement = pushButton || unsubscribeButton;
        if (initElement.getAttribute('data-pwa-notifications-ready') === 'yes') {
            return;
        }
        initElement.setAttribute('data-pwa-notifications-ready', 'yes');

        var subscriptionUrl = typeof pwaSubscriptionUrl !== 'undefined'
            ? pwaSubscriptionUrl
            : 'bgerp/pwa_PushSubscriptions/Subscribe';
        var serverKey = typeof applicationServerKey !== 'undefined' ? applicationServerKey : null;
        var renewSubscription = typeof forceRenewSubscription !== 'undefined' && forceRenewSubscription === 'yes';
        var serverSubscriptionState = typeof pwaServerSubscriptionState !== 'undefined'
            ? pwaServerSubscriptionState
            : 'unknown';
        var serverSubscriptionFingerprint = typeof pwaServerSubscriptionFingerprint !== 'undefined'
            ? pwaServerSubscriptionFingerprint
            : null;
        var renewedLocalEndpoint = null;
        var requestedRedirectUrl = typeof redirectUrl !== 'undefined' ? redirectUrl : 'none';
        var defaultButtonValue = pushButton ? pushButton.value : '';
        var defaultButtonTitle = pushButton ? pushButton.title : '';
        var operationNumber = 0;
        var isBusy = false;
        var permissionChangedWhileBusy = false;
        var foregroundRefreshTimer = null;

        function createError(code, message, userMessage) {
            var error = new Error(message);
            error.code = code;
            error.userMessage = userMessage || message;

            return error;
        }

        function ensureCurrentOperation(currentOperation) {
            if (currentOperation !== operationNumber) {
                throw createError('operation_cancelled', 'A newer notification operation replaced this one.', '');
            }
        }

        function withTimeout(promise, timeout, error) {
            return new Promise(function (resolve, reject) {
                var isSettled = false;
                var timer = setTimeout(function () {
                    if (isSettled) {
                        return;
                    }

                    isSettled = true;
                    reject(error);
                }, timeout);

                Promise.resolve(promise).then(function (result) {
                    if (isSettled) {
                        return;
                    }

                    isSettled = true;
                    clearTimeout(timer);
                    resolve(result);
                }, function (reason) {
                    if (isSettled) {
                        return;
                    }

                    isSettled = true;
                    clearTimeout(timer);
                    reject(reason);
                });
            });
        }

        function getCapabilityError() {
            if (typeof Notification === 'undefined') {
                return createError('incompatible', 'Notifications are not supported.', 'Този браузър не поддържа известия.');
            }

            if (!('serviceWorker' in navigator)) {
                return createError('incompatible', 'Service Workers are not supported.', 'Този браузър не поддържа Service Worker.');
            }

            if (typeof PushManager === 'undefined') {
                return createError('incompatible', 'PushManager is not supported.', 'Този браузър не поддържа PUSH известия.');
            }

            if (typeof ServiceWorkerRegistration === 'undefined' ||
                !('showNotification' in ServiceWorkerRegistration.prototype)) {
                return createError('incompatible', 'Service Worker notifications are not supported.', 'Това устройство не поддържа PUSH известия.');
            }

            return null;
        }

        function getButtonValues(state) {
            if (renewSubscription && state === 'disabled' && typeof pushButtonVals !== 'undefined' && pushButtonVals.renew) {
                return pushButtonVals.renew;
            }

            if (typeof pushButtonVals !== 'undefined' && pushButtonVals[state]) {
                return pushButtonVals[state];
            }

            var values = {btnText: defaultButtonValue, btnTitle: defaultButtonTitle};
            if (state === 'computing') {
                values.btnText = 'Настройване…';
                values.btnTitle = 'Изчаква се настройването на известията';
            } else if (state === 'denied') {
                values.btnText = 'Известия: блокирани';
                values.btnTitle = 'Разрешете известията от настройките на браузъра или устройството';
            } else if (state === 'incompatible') {
                values.btnText = 'Неподдържани известия';
                values.btnTitle = 'Браузърът или устройството не поддържа PUSH известия';
            }

            return values;
        }

        function changePushButtonState(state) {
            if (!pushButton) {
                return;
            }

            STATE_CLASSES.forEach(function (className) {
                pushButton.classList.remove(className);
            });
            var visualState = renewSubscription && state === 'disabled' ? 'renew' : state;
            pushButton.classList.add('pwa-push-' + visualState);

            var values = getButtonValues(state);
            pushButton.value = values.btnText;
            pushButton.title = values.btnTitle;
            pushButton.disabled = state === 'computing';
            pushButton.setAttribute('aria-busy', state === 'computing' ? 'true' : 'false');

        }

        function changeUnsubscribeBusyState(busy) {
            if (!unsubscribeButton) {
                return;
            }

            unsubscribeButton.classList.remove('pwa-push-computing');
            if (busy) {
                unsubscribeButton.classList.add('pwa-push-computing');
            }

            unsubscribeButton.disabled = busy;
            unsubscribeButton.setAttribute('aria-busy', busy ? 'true' : 'false');
            unsubscribeButton.setAttribute('aria-disabled', busy ? 'true' : 'false');
        }

        function showMessage(text, type) {
            if (!text) {
                return;
            }

            if (typeof render_showToast === 'function') {
                render_showToast({
                    timeOut: 800,
                    text: text,
                    isSticky: type === 'error',
                    stayTime: 10000,
                    type: type || 'warning'
                });

                return;
            }

            if (typeof window.alert === 'function') {
                var plainText = String(text).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                window.alert(plainText);
            }
        }

        function showDeniedMessage() {
            var text = typeof deniedText !== 'undefined'
                ? deniedText
                : 'Известията са блокирани. Разрешете ги от настройките на браузъра или операционната система и опитайте отново.';
            showMessage(text, 'error');
        }

        function ensureServiceWorkerRegistration() {
            var ensurePromise;
            try {
                if (typeof window.ensurePwaServiceWorker === 'function') {
                    ensurePromise = window.ensurePwaServiceWorker();
                } else {
                    ensurePromise = Promise.resolve(null);
                }
            } catch (error) {
                ensurePromise = Promise.reject(error);
            }

            return withTimeout(
                ensurePromise,
                SERVICE_WORKER_TIMEOUT,
                createError('sw_timeout', 'Service Worker registration timed out.', 'Service Worker не можа да се регистрира навреме. Обновете страницата и опитайте отново.')
            ).then(function () {
                return withTimeout(
                    navigator.serviceWorker.ready,
                    SERVICE_WORKER_TIMEOUT,
                    createError('sw_timeout', 'Service Worker readiness timed out.', 'Service Worker не се активира навреме. Обновете страницата и опитайте отново.')
                );
            });
        }

        function getExistingRegistration() {
            if (!('serviceWorker' in navigator)) {
                return Promise.resolve(null);
            }

            var registrationPromise;
            if (typeof navigator.serviceWorker.getRegistration === 'function') {
                registrationPromise = navigator.serviceWorker.getRegistration('/');
            } else if (typeof navigator.serviceWorker.getRegistrations === 'function') {
                registrationPromise = navigator.serviceWorker.getRegistrations().then(function (registrations) {
                    return registrations.length ? registrations[0] : null;
                });
            } else {
                return Promise.resolve(null);
            }

            return withTimeout(
                registrationPromise,
                SERVICE_WORKER_TIMEOUT,
                createError('sw_timeout', 'Unable to inspect the Service Worker.', 'Service Worker не можа да бъде проверен навреме.')
            );
        }

        function requestNotificationPermission() {
            if (Notification.permission === 'granted') {
                return Promise.resolve('granted');
            }

            if (Notification.permission === 'denied') {
                return Promise.reject(createError('permission_denied', 'Push messages are blocked.', 'Известията са блокирани.'));
            }

            // Поддържаме едновременно Promise и по-стария callback вариант на
            // API-то. Самото извикване остава синхронно в click handler-а, за
            // да се запази user gesture-ът.
            var permissionRequest = new Promise(function (resolve, reject) {
                var isSettled = false;
                var settlePermission = function (permission) {
                    if (!isSettled) {
                        isSettled = true;
                        resolve(permission);
                    }
                };
                var rejectPermission = function (error) {
                    if (!isSettled) {
                        isSettled = true;
                        reject(error);
                    }
                };

                try {
                    var result = Notification.requestPermission(settlePermission);
                    if (result && typeof result.then === 'function') {
                        result.then(settlePermission, rejectPermission);
                    }
                } catch (error) {
                    rejectPermission(error);
                }
            });

            return withTimeout(
                permissionRequest,
                PERMISSION_TIMEOUT,
                createError('permission_timeout', 'Notification permission timed out.', 'Не беше получен отговор за разрешението за известия. Опитайте отново.')
            ).then(function (permission) {
                if (permission !== 'granted') {
                    var code = permission === 'denied' ? 'permission_denied' : 'permission_not_granted';
                    throw createError(code, 'Notification permission was not granted.', 'Не е дадено разрешение за известия.');
                }

                return permission;
            });
        }

        function urlBase64ToUint8Array(base64String) {
            var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
            var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            var rawData = window.atob(base64);
            var outputArray = new Uint8Array(rawData.length);

            for (var i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }

            return outputArray;
        }

        function hasDifferentApplicationServerKey(subscription) {
            if (!subscription || !serverKey || !subscription.options || !subscription.options.applicationServerKey) {
                return false;
            }

            try {
                var actualKey = new Uint8Array(subscription.options.applicationServerKey);
                var expectedKey = urlBase64ToUint8Array(serverKey);
                if (actualKey.length !== expectedKey.length) {
                    return true;
                }

                for (var i = 0; i < actualKey.length; i++) {
                    if (actualKey[i] !== expectedKey[i]) {
                        return true;
                    }
                }
            } catch (error) {
                return false;
            }

            return false;
        }

        function getSubscriptionData(subscription) {
            if (!subscription) {
                return {publicKey: null, authToken: null, endpoint: null, contentEncoding: null};
            }

            var key = subscription.getKey('p256dh');
            var token = subscription.getKey('auth');
            var supportedEncodings = typeof PushManager !== 'undefined' && PushManager.supportedContentEncodings
                ? PushManager.supportedContentEncodings
                : ['aesgcm'];

            return {
                publicKey: key ? window.btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null,
                authToken: token ? window.btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null,
                endpoint: subscription.endpoint,
                contentEncoding: supportedEncodings[0]
            };
        }

        function stringToUtf8Bytes(value) {
            var encoded = encodeURIComponent(value);
            var bytes = [];

            for (var i = 0; i < encoded.length; i++) {
                if (encoded.charAt(i) === '%') {
                    bytes.push(parseInt(encoded.substr(i + 1, 2), 16));
                    i += 2;
                } else {
                    bytes.push(encoded.charCodeAt(i));
                }
            }

            return new Uint8Array(bytes);
        }

        function getSubscriptionFingerprint(subscription) {
            if (!subscription) {
                return Promise.resolve(null);
            }

            var subscriptionData = getSubscriptionData(subscription);
            if (!subscriptionData.endpoint || !subscriptionData.publicKey || !subscriptionData.authToken ||
                !window.crypto || !window.crypto.subtle || typeof window.crypto.subtle.digest !== 'function') {
                return Promise.resolve(null);
            }

            var fingerprintSource = subscriptionData.endpoint + '\0' +
                subscriptionData.publicKey + '\0' + subscriptionData.authToken;
            var digestPromise;
            try {
                digestPromise = window.crypto.subtle.digest('SHA-256', stringToUtf8Bytes(fingerprintSource));
            } catch (error) {
                return Promise.resolve(null);
            }

            return Promise.resolve(digestPromise).then(function (digest) {
                var bytes = new Uint8Array(digest);
                var result = '';
                for (var i = 0; i < bytes.length; i++) {
                    result += ('0' + bytes[i].toString(16)).slice(-2);
                }

                return result;
            }, function () {
                return null;
            });
        }

        function subscriptionMatchesServer(subscription) {
            if (!subscription || !serverSubscriptionFingerprint) {
                return Promise.resolve(false);
            }

            return getSubscriptionFingerprint(subscription).then(function (fingerprint) {
                return fingerprint !== null && fingerprint === serverSubscriptionFingerprint;
            });
        }

        function hasResponseCommand(response, command) {
            return Array.isArray(response) && response.some(function (item) {
                if (!item || item.func !== command) {
                    return false;
                }

                return command !== 'redirect' || (item.arg && item.arg.url);
            });
        }

        function responseRequiresRenewal(response) {
            return Array.isArray(response) && response.some(function (item) {
                return item && item.func === 'showToast' && item.arg && item.arg.pwaRenewSubscription;
            });
        }

        function sendSubscriptionToServer(subscription, action, currentOperation) {
            ensureCurrentOperation(currentOperation);

            var subscriptionData = getSubscriptionData(subscription);
            var data = {
                action: action,
                publicKey: subscriptionData.publicKey,
                authToken: subscriptionData.authToken,
                endpoint: subscriptionData.endpoint,
                contentEncoding: subscriptionData.contentEncoding,
                renewSubscription: renewSubscription ? 1 : 0,
                redirectUrl: requestedRedirectUrl
            };

            return new Promise(function (resolve, reject) {
                var request;
                try {
                    ensureCurrentOperation(currentOperation);
                    if (typeof getEfae !== 'function') {
                        throw createError('server_unavailable', 'The AJAX engine is not available.', 'Връзката със сървъра не може да бъде стартирана. Обновете страницата и опитайте отново.');
                    }

                    request = getEfae().process({url: subscriptionUrl}, data, true);
                } catch (error) {
                    reject(error);

                    return;
                }

                if (!request || typeof request.done !== 'function' || typeof request.fail !== 'function') {
                    reject(createError('server_unavailable', 'The AJAX request was not started.', 'Връзката със сървъра не може да бъде стартирана. Обновете страницата и опитайте отново.'));

                    return;
                }

                var isSettled = false;
                var timer = setTimeout(function () {
                    if (isSettled) {
                        return;
                    }

                    isSettled = true;
                    if (typeof request.abort === 'function') {
                        request.abort();
                    }
                    reject(createError('server_timeout', 'The server request timed out.', 'Сървърът не отговори навреме. Проверете връзката и опитайте отново.'));
                }, SERVER_TIMEOUT);

                request.done(function (response) {
                    if (isSettled) {
                        return;
                    }

                    isSettled = true;
                    clearTimeout(timer);
                    if (responseRequiresRenewal(response)) {
                        renewSubscription = true;
                        renewedLocalEndpoint = null;
                        serverSubscriptionState = 'stopped';
                        var renewError = createError(
                            'renew_required',
                            'The server requires a fresh PushSubscription.',
                            'Абонаментът е изтекъл. Натиснете „Поднови известията“.'
                        );
                        renewError.wasReported = true;
                        reject(renewError);

                        return;
                    }

                    if (hasResponseCommand(response, 'redirect')) {
                        resolve(response);

                        return;
                    }

                    var error = createError('server_response', 'The server did not confirm the subscription change.', 'Сървърът не потвърди промяната на известията. Опитайте отново.');
                    error.wasReported = hasResponseCommand(response, 'showToast');
                    reject(error);
                });

                request.fail(function (requestError) {
                    if (isSettled) {
                        return;
                    }

                    isSettled = true;
                    clearTimeout(timer);
                    var error = createError('server_error', 'The server request failed.', 'Възникна грешка при записването на известията. Проверете връзката и опитайте отново.');
                    error.request = requestError;
                    reject(error);
                });
            });
        }

        function finishWithError(error, currentOperation) {
            if (currentOperation !== operationNumber) {
                return;
            }

            isBusy = false;
            changeUnsubscribeBusyState(false);

            if ((typeof Notification !== 'undefined' && Notification.permission === 'denied') || error.code === 'permission_denied') {
                changePushButtonState('denied');
                if (!error.wasReported) {
                    showDeniedMessage();
                }

                refreshAfterPermissionChange();

                return;
            }

            if (error.code === 'incompatible') {
                changePushButtonState('incompatible');
            } else {
                changePushButtonState('disabled');
            }

            if (!error.wasReported) {
                showMessage(error.userMessage || 'Известията не можаха да бъдат настроени. Опитайте отново.', 'error');
            }

            refreshAfterPermissionChange();
        }

        function changePermissionAwareState(successState) {
            if (typeof Notification !== 'undefined' && Notification.permission === 'denied') {
                changePushButtonState('denied');
            } else if (successState === 'enabled' &&
                (typeof Notification === 'undefined' || Notification.permission !== 'granted')) {
                changePushButtonState('disabled');
            } else {
                changePushButtonState(successState);
            }
        }

        function refreshAfterPermissionChange() {
            if (!permissionChangedWhileBusy) {
                return;
            }

            permissionChangedWhileBusy = false;
            setTimeout(function () {
                refreshSubscriptionState();
            }, 0);
        }

        function refreshSubscriptionState() {
            if (!pushButton || isBusy) {
                return Promise.resolve();
            }

            // Инвалидира предишна асинхронна проверка и при ранно връщане
            // (например когато правото междувременно е станало denied).
            var currentOperation = ++operationNumber;
            var capabilityError = getCapabilityError();
            if (capabilityError) {
                changePushButtonState('incompatible');

                return Promise.resolve();
            }

            if (Notification.permission === 'denied') {
                changePushButtonState('denied');

                return Promise.resolve();
            }

            changePushButtonState('computing');

            return ensureServiceWorkerRegistration()
                .then(function (registration) {
                    return withTimeout(
                        registration.pushManager.getSubscription(),
                        PUSH_OPERATION_TIMEOUT,
                        createError('push_timeout', 'Subscription lookup timed out.', 'Абонаментът за известия не можа да бъде проверен навреме.')
                    );
                })
                .then(function (subscription) {
                    if (currentOperation !== operationNumber || isBusy) {
                        return;
                    }

                    if (hasDifferentApplicationServerKey(subscription)) {
                        renewSubscription = true;
                    }

                    var serverMatchPromise = serverSubscriptionState === 'unknown'
                        ? Promise.resolve(true)
                        : (serverSubscriptionState === 'active'
                            ? subscriptionMatchesServer(subscription)
                            : Promise.resolve(false));

                    return serverMatchPromise.then(function (serverCanSend) {
                        if (currentOperation !== operationNumber || isBusy) {
                            return;
                        }

                        if (!renewSubscription && serverCanSend && subscription && Notification.permission === 'granted') {
                            changePushButtonState('enabled');
                        } else {
                            changePushButtonState('disabled');
                        }
                    });
                })
                .catch(function () {
                    if (currentOperation === operationNumber && !isBusy) {
                        // A failed background check must never leave the button
                        // in a permanent computing state.
                        changePushButtonState('disabled');
                    }
                });
        }

        function subscribeFromUserGesture() {
            if (isBusy) {
                return;
            }

            var capabilityError = getCapabilityError();
            if (capabilityError) {
                changePushButtonState('incompatible');
                showMessage(capabilityError.userMessage, 'error');

                return;
            }

            if (Notification.permission === 'denied') {
                changePushButtonState('denied');
                showDeniedMessage();

                return;
            }

            // Must be created before the first asynchronous wait in order to
            // preserve the browser's transient user activation.
            var permissionPromise = requestNotificationPermission();
            var currentOperation = ++operationNumber;
            var registration;
            // След опит за запис вече не може да приемаме наследеното
            // `unknown` като потвърждение от сървъра.
            var previousServerSubscriptionState = serverSubscriptionState === 'unknown'
                ? 'missing'
                : serverSubscriptionState;
            var previousServerSubscriptionFingerprint = serverSubscriptionFingerprint;
            var shouldRecheckPreviousSubscription = previousServerSubscriptionState === 'active' &&
                !!previousServerSubscriptionFingerprint;
            isBusy = true;
            serverSubscriptionState = 'pending';
            changePushButtonState('computing');

            permissionPromise
                .then(function () {
                    ensureCurrentOperation(currentOperation);

                    return ensureServiceWorkerRegistration();
                })
                .then(function (serviceWorkerRegistration) {
                    ensureCurrentOperation(currentOperation);
                    registration = serviceWorkerRegistration;

                    return withTimeout(
                        registration.pushManager.getSubscription(),
                        PUSH_OPERATION_TIMEOUT,
                        createError('push_timeout', 'Subscription lookup timed out.', 'Абонаментът за известия не можа да бъде проверен навреме.')
                    );
                })
                .then(function (subscription) {
                    ensureCurrentOperation(currentOperation);
                    if (hasDifferentApplicationServerKey(subscription)) {
                        renewSubscription = true;
                    }

                    if (!renewSubscription || !subscription || renewedLocalEndpoint === subscription.endpoint) {
                        return subscription;
                    }

                    ensureCurrentOperation(currentOperation);

                    return withTimeout(
                        subscription.unsubscribe(),
                        PUSH_OPERATION_TIMEOUT,
                        createError('push_timeout', 'Old subscription removal timed out.', 'Старият абонамент не можа да бъде премахнат навреме.')
                    ).then(function (wasUnsubscribed) {
                        ensureCurrentOperation(currentOperation);
                        if (wasUnsubscribed === false) {
                            throw createError('unsubscribe_failed', 'The old subscription could not be removed.', 'Старият абонамент не можа да бъде премахнат. Обновете страницата и опитайте отново.');
                        }

                        return null;
                    });
                })
                .then(function (subscription) {
                    ensureCurrentOperation(currentOperation);
                    if (subscription) {
                        return subscription;
                    }

                    if (!serverKey) {
                        throw createError('configuration', 'The application server key is missing.', 'Липсва ключът за PUSH известия. Свържете се с администратор.');
                    }

                    ensureCurrentOperation(currentOperation);

                    return withTimeout(
                        registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: urlBase64ToUint8Array(serverKey)
                        }),
                        PUSH_OPERATION_TIMEOUT,
                        createError('push_timeout', 'Push subscription timed out.', 'Създаването на абонамента се забави. Опитайте отново.')
                    ).then(function (newSubscription) {
                        ensureCurrentOperation(currentOperation);
                        if (renewSubscription && newSubscription) {
                            renewedLocalEndpoint = newSubscription.endpoint;
                        }

                        return newSubscription;
                    });
                })
                .then(function (subscription) {
                    ensureCurrentOperation(currentOperation);

                    return sendSubscriptionToServer(subscription, 'subscribe', currentOperation).then(function () {
                        return subscription;
                    });
                })
                .then(function (subscription) {
                    if (currentOperation !== operationNumber) {
                        return;
                    }

                    return getSubscriptionFingerprint(subscription).then(function (fingerprint) {
                        if (currentOperation !== operationNumber) {
                            return;
                        }

                        isBusy = false;
                        renewSubscription = false;
                        renewedLocalEndpoint = null;
                        serverSubscriptionState = 'active';
                        serverSubscriptionFingerprint = fingerprint;
                        changePermissionAwareState('enabled');
                        refreshAfterPermissionChange();
                    });
                })
                .catch(function (error) {
                    if (serverSubscriptionState === 'pending') {
                        serverSubscriptionState = previousServerSubscriptionState;
                        serverSubscriptionFingerprint = previousServerSubscriptionFingerprint;
                    }

                    finishWithError(error, currentOperation);
                    if (shouldRecheckPreviousSubscription && currentOperation === operationNumber &&
                        typeof Notification !== 'undefined' && Notification.permission !== 'denied') {
                        setTimeout(function () {
                            refreshSubscriptionState();
                        }, 0);
                    }
                });
        }

        function unsubscribeFromUserGesture() {
            if (isBusy) {
                return;
            }

            var currentOperation = ++operationNumber;
            var localSubscription = null;
            var localError = null;
            isBusy = true;
            changePushButtonState('computing');
            changeUnsubscribeBusyState(true);

            getExistingRegistration()
                .then(function (registration) {
                    ensureCurrentOperation(currentOperation);
                    if (!registration || !registration.pushManager) {
                        return null;
                    }

                    return withTimeout(
                        registration.pushManager.getSubscription(),
                        PUSH_OPERATION_TIMEOUT,
                        createError('push_timeout', 'Subscription lookup timed out.', 'Локалният абонамент не можа да бъде проверен навреме.')
                    );
                })
                .then(function (subscription) {
                    ensureCurrentOperation(currentOperation);
                    localSubscription = subscription;
                    if (!subscription) {
                        return null;
                    }

                    ensureCurrentOperation(currentOperation);

                    return withTimeout(
                        subscription.unsubscribe(),
                        PUSH_OPERATION_TIMEOUT,
                        createError('push_timeout', 'Push unsubscribe timed out.', 'Локалният абонамент не можа да бъде премахнат навреме.')
                    ).then(function (wasUnsubscribed) {
                        ensureCurrentOperation(currentOperation);
                        if (wasUnsubscribed === false) {
                            throw createError('unsubscribe_failed', 'The subscription could not be removed.', 'Локалният абонамент не можа да бъде премахнат.');
                        }
                    });
                })
                .catch(function (error) {
                    ensureCurrentOperation(currentOperation);
                    // Closing the server record is still useful when browser
                    // storage or the Service Worker is already missing.
                    localError = error;
                })
                .then(function () {
                    ensureCurrentOperation(currentOperation);

                    return sendSubscriptionToServer(localSubscription, 'unsubscribe', currentOperation);
                })
                .then(function () {
                    if (currentOperation !== operationNumber) {
                        return;
                    }

                    isBusy = false;
                    serverSubscriptionState = 'closed';
                    serverSubscriptionFingerprint = null;
                    changeUnsubscribeBusyState(false);
                    changePermissionAwareState('disabled');
                    if (localError) {
                        showMessage(localError.userMessage, 'warning');
                    }
                    refreshAfterPermissionChange();
                })
                .catch(function (error) {
                    finishWithError(error, currentOperation);
                });
        }

        if (pushButton) {
            // ProfilePlg renders a real server-side fallback link. Once this
            // script is initialized, replace its inline navigation and let the
            // user-gesture subscription flow own the click.
            pushButton.onclick = null;
            pushButton.addEventListener('click', function (event) {
                event.preventDefault();
                subscribeFromUserGesture();
            });
        }

        if (unsubscribeButton) {
            unsubscribeButton.addEventListener('click', function (event) {
                event.preventDefault();
                unsubscribeFromUserGesture();
            });
        }

        if (pushButton) {
            refreshSubscriptionState();
        }

        window.addEventListener('pageshow', function (event) {
            if (!event.persisted) {
                return;
            }

            // Не допускаме противоположна операция, докато асинхронната
            // subscribe/unsubscribe верига от възстановения BFCache документ
            // още може да промени браузъра или сървъра. Всички нейни фази са
            // ограничени с timeout и сами ще освободят интерфейса.
            if (isBusy) {
                changePushButtonState('computing');
                changeUnsubscribeBusyState(true);

                return;
            }

            operationNumber++;
            isBusy = false;
            permissionChangedWhileBusy = false;
            changeUnsubscribeBusyState(false);
            changePushButtonState('disabled');
            refreshSubscriptionState();
        });

        function refreshWhenForegrounded() {
            if (typeof document.visibilityState !== 'undefined' && document.visibilityState !== 'visible') {
                return;
            }

            if (isBusy) {
                permissionChangedWhileBusy = true;

                return;
            }

            if (foregroundRefreshTimer) {
                clearTimeout(foregroundRefreshTimer);
            }
            foregroundRefreshTimer = setTimeout(function () {
                foregroundRefreshTimer = null;
                refreshSubscriptionState();
            }, FOREGROUND_REFRESH_DELAY);
        }

        window.addEventListener('focus', refreshWhenForegrounded);
        document.addEventListener('visibilitychange', refreshWhenForegrounded);

        if (navigator.permissions && typeof navigator.permissions.query === 'function') {
            navigator.permissions.query({name: 'notifications'}).then(function (permissionStatus) {
                permissionStatus.onchange = function () {
                    if (isBusy) {
                        permissionChangedWhileBusy = true;

                        return;
                    }

                    refreshSubscriptionState();
                };
            }).catch(function () {
                // Permissions API support differs between browsers. The
                // `pageshow` refresh remains the portable fallback.
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNotifications);
    } else {
        initNotifications();
    }
})();
