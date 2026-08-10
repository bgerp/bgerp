/**
 * Synchronizes the bgERP Service Worker with the active PWA manifest.
 *
 * The browser registration is the source of truth. Local storage is used only
 * as diagnostic information and must never prevent a missing worker from being
 * registered again.
 */
(function (global) {
    'use strict';

    var serviceWorkerPath = '/serviceWorker.js';
    var serviceWorkerScope = '/';
    var storageKey = 'data-sw-date';
    var ensureInFlight = null;
    var installPromptEvent = null;

    function logError(message, error) {
        if (global.console && typeof global.console.error === 'function') {
            global.console.error(message, error);
        }
    }

    function logInfo(message) {
        if (global.console && typeof global.console.log === 'function') {
            global.console.log(message);
        }
    }

    function setStoredVersion(version) {
        try {
            global.localStorage.setItem(storageKey, version || '');
        } catch (error) {
            logError('ServiceWorker local storage update failed:', error);
        }
    }

    function clearStoredVersion() {
        try {
            global.localStorage.removeItem(storageKey);
        } catch (error) {
            logError('ServiceWorker local storage cleanup failed:', error);
        }
    }

    function isInternetExplorer() {
        try {
            return typeof global.isIE === 'function' && global.isIE();
        } catch (error) {
            logError('Unable to detect Internet Explorer:', error);

            return false;
        }
    }

    function getManifestElement() {
        return global.document.querySelector('link[rel="manifest"]');
    }

    function isInstalledPwa() {
        if (global.navigator.standalone === true) {
            return true;
        }

        try {
            return typeof global.matchMedia === 'function' && global.matchMedia('(display-mode: standalone)').matches;
        } catch (error) {
            logError('Unable to inspect PWA display mode:', error);

            return false;
        }
    }

    function initializeInstallButton() {
        if (typeof global.document.getElementById !== 'function') {
            return;
        }

        var installButton = global.document.getElementById('pwa-install-button');
        if (!installButton) {
            // Не прихващаме beforeinstallprompt извън собствения профил, за
            // да може браузърът да запази стандартния си install интерфейс.
            return;
        }

        function hideInstallButton() {
            installButton.style.display = 'none';
            installButton.setAttribute('aria-hidden', 'true');
        }

        function showInstallButton() {
            if (!installPromptEvent || isInstalledPwa()) {
                hideInstallButton();

                return;
            }

            installButton.style.display = '';
            installButton.removeAttribute('aria-hidden');
        }

        hideInstallButton();

        installButton.addEventListener('click', function (event) {
            event.preventDefault();

            var currentPrompt = installPromptEvent;
            installPromptEvent = null;
            hideInstallButton();

            if (!currentPrompt || isInstalledPwa()) {
                return;
            }

            try {
                currentPrompt.prompt();
                Promise.resolve(currentPrompt.userChoice).catch(function (error) {
                    logError('Unable to read the PWA install choice:', error);
                });
            } catch (error) {
                logError('Unable to show the PWA install prompt:', error);
            }
        });

        global.addEventListener('beforeinstallprompt', function (event) {
            if (isInstalledPwa()) {
                hideInstallButton();

                return;
            }

            event.preventDefault();
            installPromptEvent = event;
            showInstallButton();
        });

        global.addEventListener('appinstalled', function () {
            installPromptEvent = null;
            hideInstallButton();
        });

        if (typeof global.matchMedia === 'function') {
            var displayMode = global.matchMedia('(display-mode: standalone)');
            if (typeof displayMode.addEventListener === 'function') {
                displayMode.addEventListener('change', function (event) {
                    if (event.matches) {
                        installPromptEvent = null;
                        hideInstallButton();
                    }
                });
            }
        }
    }

    function initializeInstallButtonWhenReady() {
        if (global.document.readyState !== 'loading') {
            initializeInstallButton();

            return;
        }

        var onDomReady = function () {
            global.document.removeEventListener('DOMContentLoaded', onDomReady);
            initializeInstallButton();
        };
        global.document.addEventListener('DOMContentLoaded', onDomReady);
    }

    function getDesiredWorker() {
        var manifest = getManifestElement();
        if (!manifest) {
            return null;
        }

        var version = manifest.getAttribute('data-sw-date') || '';
        var url = serviceWorkerPath;
        if (version) {
            url += '?v=' + encodeURIComponent(version);
        }

        return {url: url, version: version};
    }

    function getRegistrationWorkers(registration) {
        return [registration.installing, registration.waiting, registration.active].filter(function (worker) {
            return Boolean(worker);
        });
    }

    function isBgErpRegistration(registration) {
        return getRegistrationWorkers(registration).some(function (worker) {
            try {
                var workerUrl = new URL(worker.scriptURL, global.location.href);

                return workerUrl.origin === global.location.origin && workerUrl.pathname === serviceWorkerPath;
            } catch (error) {
                logError('Unable to inspect ServiceWorker registration:', error);

                return false;
            }
        });
    }

    function getActualRegistrations() {
        var serviceWorkerContainer = global.navigator.serviceWorker;
        if (typeof serviceWorkerContainer.getRegistrations === 'function') {
            return serviceWorkerContainer.getRegistrations();
        }

        if (typeof serviceWorkerContainer.getRegistration === 'function') {
            return serviceWorkerContainer.getRegistration(serviceWorkerScope).then(function (registration) {
                return registration ? [registration] : [];
            });
        }

        return Promise.reject(new Error('Unable to inspect Service Worker registrations.'));
    }

    function getBgErpRegistrations() {
        return getActualRegistrations().then(function (registrations) {
            return registrations.filter(isBgErpRegistration);
        });
    }

    /**
     * Ensures that the concrete /serviceWorker.js registration exists.
     *
     * @returns {Promise<ServiceWorkerRegistration>}
     */
    global.ensurePwaServiceWorker = function () {
        if (isInternetExplorer() || !('serviceWorker' in global.navigator)) {
            return Promise.reject(new Error('Service Workers are not supported by this browser.'));
        }

        var desiredWorker = getDesiredWorker();
        if (!desiredWorker) {
            return Promise.reject(new Error('The PWA manifest is not active on this page.'));
        }

        if (ensureInFlight) {
            return ensureInFlight;
        }

        var didRegister = false;
        var ensurePromise = getBgErpRegistrations()
            .catch(function (error) {
                // Registration itself is still worth attempting when an
                // inspection API fails transiently.
                logError('Unable to inspect existing ServiceWorker registrations:', error);

                return [];
            })
            .then(function (registrations) {
                var currentRegistration = registrations.find(function (registration) {
                    return registration.scope === new URL(serviceWorkerScope, global.location.href).href;
                });
                var desiredUrl = new URL(desiredWorker.url, global.location.href).href;
                var alreadyCurrent = currentRegistration && getRegistrationWorkers(currentRegistration).some(function (worker) {
                    return worker.scriptURL === desiredUrl;
                });

                if (alreadyCurrent) {
                    return currentRegistration;
                }

                // register() creates a missing registration and updates an existing
                // registration in place when the versioned script URL has changed.
                didRegister = true;
                return global.navigator.serviceWorker.register(desiredWorker.url, {scope: serviceWorkerScope});
            })
            .then(function (registration) {
                setStoredVersion(desiredWorker.version);
                if (didRegister) {
                    logInfo('ServiceWorker registered/updated: ' + desiredWorker.url);
                }

                return registration;
            })
            .catch(function (error) {
                logError('ServiceWorker registration failed:', error);

                throw error;
            });

        ensureInFlight = ensurePromise.then(function (registration) {
            ensureInFlight = null;

            return registration;
        }, function (error) {
            ensureInFlight = null;

            throw error;
        });

        return ensureInFlight;
    };

    function unregisterBgErpServiceWorkers() {
        if (!('serviceWorker' in global.navigator)) {
            clearStoredVersion();

            return Promise.resolve([]);
        }

        return getBgErpRegistrations()
            .then(function (registrations) {
                return Promise.all(registrations.map(function (registration) {
                    return registration.unregister().then(function (wasUnregistered) {
                        if (wasUnregistered) {
                            logInfo('ServiceWorker registration unregistered: ' + registration.scope);
                        }

                        return wasUnregistered;
                    });
                }));
            })
            .then(function (results) {
                clearStoredVersion();

                return results;
            })
            .catch(function (error) {
                logError('ServiceWorker unregister failed:', error);

                throw error;
            });
    }

    global.syncServiceWorker = function () {
        if (isInternetExplorer() || !('serviceWorker' in global.navigator)) {
            return Promise.resolve(null);
        }

        if (!getManifestElement()) {
            return unregisterBgErpServiceWorkers().then(function () {
                return null;
            }).catch(function () {
                return null;
            });
        }

        return global.ensurePwaServiceWorker().catch(function () {
            return null;
        });
    };

    initializeInstallButtonWhenReady();
})(window);
