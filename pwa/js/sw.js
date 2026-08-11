'use strict';

var shareTokenHeader = 'X-PWA-Share-Token';
var shareTokenField = 'pwaShareToken';
var shareErrorCodes = ['token', 'quota', 'size', 'upload', 'network', 'url'];
var shareDiagnosticParam = 'shareDiag';
var shareWorkerVersionParam = 'shareSw';
var shareFileCountHeader = 'X-PWA-Share-File-Count';
var shareFileFieldHeader = 'X-PWA-Share-File-Field';
var shareWorkerVersionHeader = 'X-PWA-Share-Worker-Version';

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

function getShareDiagnosticCode(url, fallbackCode) {
    var diagnosticCode = null;

    if (url) {
        try {
            diagnosticCode = new URL(url, self.location.origin).searchParams.get(shareDiagnosticParam);
        } catch (error) {
            diagnosticCode = null;
        }
    }

    diagnosticCode = diagnosticCode || fallbackCode || '';

    return /^(php|sw)_[a-z0-9_]{1,48}$/.test(diagnosticCode) ? diagnosticCode : '';
}

function getShareWorkerVersion() {
    try {
        var version = new URL(self.location.href).searchParams.get('v') || '';

        return /^[a-zA-Z0-9._-]{1,64}$/.test(version) ? version : '';
    } catch (error) {
        return '';
    }
}

function getShareErrorUrl(errorCode, diagnosticCode) {
    var url = '/pwa_Share/Target?shareError=' + encodeURIComponent(getShareErrorCode(null, errorCode));
    diagnosticCode = getShareDiagnosticCode(null, diagnosticCode);
    if (diagnosticCode) {
        url += '&' + shareDiagnosticParam + '=' + encodeURIComponent(diagnosticCode);
    }

    var workerVersion = getShareWorkerVersion();
    if (workerVersion) {
        url += '&' + shareWorkerVersionParam + '=' + encodeURIComponent(workerVersion);
    }

    return url;
}

function createShareTargetError(errorCode, message, diagnosticCode) {
    var error = new Error(message);
    error.shareErrorCode = getShareErrorCode(null, errorCode);
    error.shareDiagnosticCode = getShareDiagnosticCode(null, diagnosticCode);

    return error;
}

function showShareTargetError(event, error, fallbackCode, fallbackDiagnosticCode) {
    console.error('PWA share-target processing failed:', error);

    var errorCode = error && error.shareErrorCode;
    errorCode = getShareErrorCode(null, errorCode || fallbackCode);
    var diagnosticCode = error && error.shareDiagnosticCode;
    diagnosticCode = getShareDiagnosticCode(null, diagnosticCode || fallbackDiagnosticCode);

    return redirectShareTargetClient(event, getShareErrorUrl(errorCode, diagnosticCode)).catch(function (navigationError) {
        console.error('Unable to show the share-target error page:', navigationError);

        return null;
    });
}

function handleShareTarget(event, shareRequest, shareToken) {
    return Promise.resolve().then(function () {
        return shareRequest.formData();
    }).catch(function (error) {
        throw createShareTargetError('upload', 'Unable to read the shared payload.', 'sw_form_data');
    }).then(function (data) {
        if (shareToken) {
            data.set(shareTokenField, shareToken);
        } else {
            // If a proxy/redirect stripped the loader response header, the
            // server can still authenticate this as a strict same-origin
            // worker fetch using Fetch Metadata and the worker marker below.
            data.delete(shareTokenField);
        }

        // Новият manifest използва PHP array име (`file[]`), за да не
        // изгуби файлове при директен POST без контролиращ worker. Старите
        // и custom manifest-и може още да изпращат полето като `file`.
        var legacyFiles = data.getAll('file');
        var arrayFiles = data.getAll('file[]');
        var files = legacyFiles.concat(arrayFiles);
        var fileField = 'none';
        if (legacyFiles.length && arrayFiles.length) {
            fileField = 'both';
        } else if (arrayFiles.length) {
            fileField = 'file-array';
        } else if (legacyFiles.length) {
            fileField = 'file';
        }
        var haveFile = false;

        files.forEach(function (file) {
            data.append('ulfile[]', file);
            haveFile = true;
        });
        if (haveFile) {
            data.delete('file');
            data.delete('file[]');
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
        var workerHeaders = {
            'X-PWA-Share-Worker': '1'
        };
        workerHeaders[shareFileCountHeader] = String(files.length);
        workerHeaders[shareFileFieldHeader] = fileField;
        var workerVersion = getShareWorkerVersion();
        if (workerVersion) {
            workerHeaders[shareWorkerVersionHeader] = workerVersion;
        }

        return fetch('/pwa_Share/Target', {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
            headers: workerHeaders
        }).catch(function (error) {
            throw createShareTargetError('network', 'Share-target POST failed.', 'sw_post_network');
        });
    }).then(function (response) {
        var serverErrorCode = getShareErrorCode(response.url, null);
        if (response.url && new URL(response.url, self.location.origin).searchParams.get('shareError')) {
            return redirectShareTargetClient(event, getShareErrorUrl(
                serverErrorCode,
                getShareDiagnosticCode(response.url, 'php_error_without_diagnostic')
            ));
        }

        if (!response.ok) {
            throw createShareTargetError(
                'upload',
                'Share-target upload failed with HTTP status ' + response.status + '.',
                'sw_post_http'
            );
        }

        return redirectShareTargetClient(event, response.url);
    }).catch(function (error) {
        return showShareTargetError(event, error, 'network', 'sw_handle_failed');
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
                return redirectShareTargetClient(event, getShareErrorUrl(
                    loaderErrorCode,
                    getShareDiagnosticCode(response.url, 'php_loader_error_without_diagnostic')
                ));
            }

            if (!response.ok) {
                throw createShareTargetError(
                    'network',
                    'Unable to render the share-target loader (HTTP ' + response.status + ').',
                    'sw_loader_http'
                );
            }

            var shareToken = response.headers && response.headers.get(shareTokenHeader);
            return handleShareTarget(event, shareRequest, shareToken);
        }).catch(function (error) {
            return showShareTargetError(event, error, 'network', 'sw_loader_failed');
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
