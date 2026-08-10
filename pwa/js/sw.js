'use strict';

var shareTokenHeader = 'X-PWA-Share-Token';
var shareTokenField = 'pwaShareToken';
var shareErrorCodes = ['token', 'quota', 'size', 'upload', 'network', 'url'];

self.addEventListener('install', function (event) {
    event.waitUntil(
        self.skipWaiting().then(function () {
            console.log('ServiceWorker installed');
        }).catch(function (error) {
            console.error('ServiceWorker install failed:', error);
            throw error;
        })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        self.clients.claim().then(function () {
            console.log('ServiceWorker activated');
        }).catch(function (error) {
            console.error('ServiceWorker activation failed:', error);
            throw error;
        })
    );
});

function getShareTargetClient(event) {
    var clientIds = [event.resultingClientId, event.clientId].filter(function (clientId) {
        return Boolean(clientId);
    });

    return clientIds.reduce(function (clientPromise, clientId) {
        return clientPromise.then(function (client) {
            if (client) {
                return client;
            }

            return self.clients.get(clientId).catch(function (error) {
                console.error('Unable to resolve share-target client:', error);

                return null;
            });
        });
    }, Promise.resolve(null)).then(function (client) {
        if (client) {
            return client;
        }

        return self.clients.matchAll({type: 'window', includeUncontrolled: true}).then(function (windowClients) {
            return windowClients.find(function (windowClient) {
                try {
                    return new URL(windowClient.url).pathname.indexOf('/pwa_Share/Target') !== -1;
                } catch (error) {
                    return false;
                }
            }) || null;
        });
    });
}

function redirectShareTargetClient(event, targetUrl) {
    return getShareTargetClient(event).then(function (client) {
        if (client) {
            if (typeof client.navigate === 'function') {
                return client.navigate(targetUrl).then(function (navigatedClient) {
                    if (navigatedClient) {
                        return navigatedClient;
                    }

                    return self.clients.openWindow(targetUrl);
                }).catch(function (error) {
                    console.error('Unable to navigate share-target client:', error);

                    return self.clients.openWindow(targetUrl);
                });
            }

            try {
                client.postMessage(targetUrl);

                return client;
            } catch (error) {
                console.error('Unable to notify share-target client:', error);
            }
        }

        if (self.clients && typeof self.clients.openWindow === 'function') {
            return self.clients.openWindow(targetUrl);
        }

        return null;
    });
}

function getShareErrorCode(url, fallbackCode) {
    var errorCode = null;

    if (url) {
        try {
            errorCode = new URL(url, self.location.origin).searchParams.get('shareError');
        } catch (error) {
            errorCode = null;
        }
    }

    if (shareErrorCodes.indexOf(errorCode) !== -1) {
        return errorCode;
    }

    return shareErrorCodes.indexOf(fallbackCode) !== -1 ? fallbackCode : 'network';
}

function getShareErrorUrl(errorCode) {
    return '/pwa_Share/Target?shareError=' + getShareErrorCode(null, errorCode);
}

function createShareTargetError(errorCode, message) {
    var error = new Error(message);
    error.shareErrorCode = getShareErrorCode(null, errorCode);

    return error;
}

function showShareTargetError(event, error, fallbackCode) {
    console.error('PWA share-target processing failed:', error);

    var errorCode = error && error.shareErrorCode;
    errorCode = getShareErrorCode(null, errorCode || fallbackCode);

    return redirectShareTargetClient(event, getShareErrorUrl(errorCode)).catch(function (navigationError) {
        console.error('Unable to show the share-target error page:', navigationError);

        return null;
    });
}

function handleShareTarget(event, shareRequest, shareToken) {
    return Promise.resolve().then(function () {
        return shareRequest.formData();
    }).then(function (data) {
        if (!shareToken) {
            throw createShareTargetError('token', 'Missing PWA share token.');
        }

        data.set(shareTokenField, shareToken);

        var files = data.getAll('file');
        var haveFile = false;

        files.forEach(function (file) {
            data.append('ulfile[]', file);
            haveFile = true;
        });
        if (haveFile) {
            data.delete('file');
        }

        if (!haveFile) {
            // Keep a real shared URL in `link`. Plain shared text must remain a
            // description so pwa_Share can create the note/cache entry.
            var link = data.get('link');
            var description = data.get('description');
            var name = data.get('name');
            if (!link && !description && name) {
                data.set('description', name);
            }
        }

        // This is the only POST that processes the shared payload. The original
        // navigation is answered with a GET loader response below.
        return fetch('/pwa_Share/Target', {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
            headers: {'X-PWA-Share-Worker': '1'}
        });
    }).then(function (response) {
        var serverErrorCode = getShareErrorCode(response.url, null);
        if (response.url && new URL(response.url, self.location.origin).searchParams.get('shareError')) {
            return redirectShareTargetClient(event, getShareErrorUrl(serverErrorCode));
        }

        if (!response.ok) {
            throw createShareTargetError('upload', 'Share-target upload failed with HTTP status ' + response.status + '.');
        }

        return redirectShareTargetClient(event, response.url);
    }).catch(function (error) {
        return showShareTargetError(event, error, 'network');
    });
}

function isAllowedShareTargetRequest(request) {
    if (!request || request.mode !== 'navigate') {
        return false;
    }

    var fetchSite = '';
    var origin = '';
    if (request.headers && typeof request.headers.get === 'function') {
        fetchSite = (request.headers.get('Sec-Fetch-Site') || '').toLowerCase();
        origin = request.headers.get('Origin') || '';
    }

    if (fetchSite) {
        if (fetchSite === 'none') {
            // Web Share Target is opened by browser/OS UI and has no web
            // initiator. Chromium can therefore expose its opaque Origin as
            // the literal "null" while Sec-Fetch-Site remains "none".
            return !origin || origin === 'null' || origin === self.location.origin;
        }

        if (fetchSite === 'same-origin') {
            return !origin || origin === self.location.origin;
        }

        return false;
    }

    // Стар браузър без Fetch Metadata се допуска само с точен Origin.
    return origin === self.location.origin;
}

self.addEventListener('fetch', function (event) {
    var requestUrl;

    try {
        requestUrl = new URL(event.request.url);
    } catch (error) {
        return;
    }

    if (requestUrl.origin !== self.location.origin ||
        requestUrl.pathname !== '/pwa_Share/Target' ||
        event.request.method !== 'POST' ||
        !isAllowedShareTargetRequest(event.request)) {
        return;
    }

    var shareRequest = event.request.clone();
    var loaderResponse = fetch(event.request.url, {method: 'GET', credentials: 'same-origin', cache: 'no-store'});

    // Prevent the untransformed navigation POST from reaching PHP. A GET keeps
    // the existing loader/message UX while the transformed POST runs once.
    event.respondWith(loaderResponse);
    event.waitUntil(
        loaderResponse.then(function (response) {
            var loaderErrorCode = getShareErrorCode(response.url, null);
            if (response.url && new URL(response.url, self.location.origin).searchParams.get('shareError')) {
                return redirectShareTargetClient(event, getShareErrorUrl(loaderErrorCode));
            }

            if (!response.ok) {
                throw createShareTargetError('network', 'Unable to render the share-target loader (HTTP ' + response.status + ').');
            }

            var shareToken = response.headers && response.headers.get(shareTokenHeader);
            if (!shareToken) {
                throw createShareTargetError('token', 'The share-target loader did not provide a PWA share token.');
            }

            return handleShareTarget(event, shareRequest, shareToken);
        }).catch(function (error) {
            return showShareTargetError(event, error, 'network');
        })
    );
});

self.addEventListener('notificationclick', function (event) {
    var notification = event.notification;
    var notificationData = notification && notification.data;

    if (notification) {
        notification.close();
    }

    if (notificationData && typeof notificationData.url === 'string' && notificationData.url) {
        event.waitUntil(
            self.clients.openWindow(notificationData.url).catch(function (error) {
                console.error('Unable to open PUSH notification URL:', error);
            })
        );
    }
});

function showPushNotification(message) {
    var bodyData;

    try {
        bodyData = JSON.parse(message);
    } catch (error) {
        return Promise.reject(new Error('Invalid PUSH notification payload: ' + error.message));
    }

    if (!bodyData || typeof bodyData !== 'object' || Array.isArray(bodyData)) {
        return Promise.reject(new Error('Invalid PUSH notification payload.'));
    }

    var options = {};
    var title = bodyData.title || '';

    options.body = bodyData.text;
    if (bodyData.icon) {
        options.icon = bodyData.icon;
    }

    if (bodyData.badge) {
        options.badge = bodyData.badge;
    }

    if (bodyData.image) {
        options.image = bodyData.image;
    }

    var vibration = bodyData.vibration || bodyData.vibrate;
    if (vibration) {
        options.vibrate = Array.isArray(vibration) ? vibration : [200, 100, 300, 100, 400, 100, 500];
    }

    if (!bodyData.sound) {
        options.silent = true;
    }

    if (bodyData.tag) {
        options.tag = bodyData.tag;
    }

    if (bodyData.url) {
        options.data = {url: bodyData.url};
    }

    return self.registration.showNotification(title, options);
}

self.addEventListener('push', function (event) {
    if (!event.data) {
        return;
    }

    event.waitUntil(
        Promise.resolve().then(function () {
            return showPushNotification(event.data.text());
        }).catch(function (error) {
            console.error('Unable to show PUSH notification:', error);
        })
    );
});
