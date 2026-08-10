<?php


/**
 * Екшън за споделяне на файлове чрез PWA
 * 
 * @package   pwa
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pwa_Share extends core_Mvc
{
    /** Header, с който loader-ът предава еднократния token на Service Worker-а */
    const SHARE_TOKEN_HEADER = 'X-PWA-Share-Token';

    /** Безопасни диагностични headers от контролиращия Service Worker */
    const SHARE_FILE_COUNT_SERVER_KEY = 'HTTP_X_PWA_SHARE_FILE_COUNT';
    const SHARE_FILE_FIELD_SERVER_KEY = 'HTTP_X_PWA_SHARE_FILE_FIELD';
    const SHARE_WORKER_VERSION_SERVER_KEY = 'HTTP_X_PWA_SHARE_WORKER_VERSION';

    /** Поле в трансформирания multipart POST */
    const SHARE_TOKEN_FIELD = 'pwaShareToken';

    /** Namespace за краткоживеещия persistent token запис */
    const SHARE_TOKEN_PERMANENT_NAMESPACE = 'pwaShareToken';

    /** Token може да започне POST до 2 минути, но се пази за бавен upload */
    const SHARE_TOKEN_START_MINUTES = 2;
    const SHARE_TOKEN_KEEP_MINUTES = 120;

    /** Ограничение на анонимните token-и за един BRID/IP */
    const SHARE_RATE_CACHE_TYPE = 'pwaShareRate';
    const SHARE_RATE_WINDOW_MINUTES = 5;
    const SHARE_RATE_BRID_LIMIT = 10;
    const SHARE_RATE_IP_LIMIT = 30;

    /** Един чакащ анонимен upload на BRID, с вторичен лимит по IP */
    const SHARE_ANONYMOUS_CACHE_TYPE = 'pwaShareAnonymous';
    const SHARE_ANONYMOUS_IP_CACHE_TYPE = 'pwaShareAnonymousIp';
    const SHARE_ANONYMOUS_KEEP_MINUTES = 60;
    const SHARE_ANONYMOUS_IP_LIMIT = 20;
    const SHARE_ANONYMOUS_MAX_BYTES = 104857600;

    /** Безопасни machine-readable кодове за грешка при споделяне */
    const SHARE_ERROR_TOKEN = 'token';
    const SHARE_ERROR_QUOTA = 'quota';
    const SHARE_ERROR_SIZE = 'size';
    const SHARE_ERROR_UPLOAD = 'upload';
    const SHARE_ERROR_NETWORK = 'network';
    const SHARE_ERROR_URL = 'url';

    /** Prefix за вътрешно предаване само на allowlisted диагностичен код */
    const SHARE_DIAG_EXCEPTION_PREFIX = 'PWA_SHARE_DIAG:';

    /** Временни файлове и маркер за вече прието споделяне */
    const SHARE_FILES_CACHE_TYPE = 'pwa_ShareFiles';
    const SHARE_FILES_KEEP_MINUTES = 35;
    const SHARE_FILES_CLEANUP_MINUTES = 30;
    const SHARE_FILES_CLAIMED_CACHE_TYPE = 'pwaShareClaimed';

    /** Временен текст за нова бележка */
    const SHARE_TEXT_CACHE_TYPE = 'pwa_Share';

    /** URL, който ще бъде свален еднократно след вход на потребителя */
    const SHARE_REMOTE_CACHE_TYPE = 'pwaShareRemote';
    const SHARE_REMOTE_KEEP_MINUTES = 30;
    const SHARE_REMOTE_HTML_MAX_BYTES = 5242880;

    
    
    /**
     * Екшън за качване на файловете
     */
    public function act_Target()
    {
        expect(core_Packs::isInstalled('pwa'));

        if ($shareError = Request::get('shareError', 'identifier')) {
            $shareError = self::normalizeShareError($shareError);
            $shareDiag = self::normalizeShareDiag(Request::get('shareDiag', 'identifier'));
            $shareWorkerVersion = self::normalizeShareWorkerVersion(Request::get('shareSw', 'varchar'));
            $errorMessages = self::getShareErrorMessages();
            $portalLink = ht::createLink('Към bgERP', array('Portal', 'Show'), null, 'class=button');
            $diagnostic = '';
            if (($shareDiag !== null || $shareWorkerVersion !== null) && haveRole('debug')) {
                $diagnosticLines = array("shareError={$shareError}");
                if ($shareDiag !== null) {
                    $diagnosticLines[] = "shareDiag={$shareDiag}";
                }
                if ($shareWorkerVersion !== null) {
                    $diagnosticLines[] = "shareSw={$shareWorkerVersion}";
                }
                $diagnosticText = implode("\n", $diagnosticLines);
                $diagnostic = '<div style="margin-top:12px"><div>' . tr('Диагностика за копиране')
                    . ':</div><textarea readonly rows="3" '
                    . 'style="width:100%;max-width:520px" onclick="this.select()" '
                    . 'aria-label="PWA share diagnostic">' . ht::escapeAttr($diagnosticText) . '</textarea></div>';
            }

            return new ET('<div class="formError">' . tr($errorMessages[$shareError]) . '</div>'
                . $diagnostic . '<div style="margin-top:20px">' . $portalLink . '</div>');
        }
        
        $tpl = new ET('<div class="loader"></div><input type="file" name="ulfile[]" multiple style="display:none"><input type="text" name="link" style="display:none">');
        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($requestMethod === 'GET') {
            if (!self::hasAllowedShareRequestOrigin()) {

                return self::getShareErrorRedirect(self::SHARE_ERROR_TOKEN);
            }

            try {
                self::issueShareToken();
            } catch (Throwable $t) {

                return self::getShareErrorRedirect(self::SHARE_ERROR_QUOTA);
            }
        }
        
        if ($requestMethod === 'POST') {
            // При надвишен post_max_size PHP оставя едновременно $_POST и
            // $_FILES празни. Разпознаваме това преди token проверката, за да
            // покажем коректната грешка за размер, а не "изтекла сесия".
            if (self::isPostBodyTooLarge()) {

                return self::getShareErrorRedirect(self::SHARE_ERROR_SIZE);
            }

            try {
                $isDirectShareFallback = !self::validateAndConsumeShareToken();
            } catch (Throwable $t) {
                $shareDiag = self::logShareTokenValidationFailure($t);
                $shareError = ($shareDiag === 'php_direct_rate_limit')
                    ? self::SHARE_ERROR_QUOTA
                    : self::SHARE_ERROR_TOKEN;

                return self::getShareErrorRedirect($shareError, $shareDiag);
            }

            // Новият manifest подава `file[]`, което PHP представя като
            // `$_FILES['file']`. Нормализираме го и при валиден token, за да
            // работи и преходът с вече активен по-стар Service Worker.
            $uploadDiagnostic = self::getShareUploadDiagnostic();
            self::normalizeDirectShareFiles();
            $uploadDiagnostic->normalizedCount = self::countSharedFileEntries($_FILES['ulfile'] ?? array());

            $bucketId = fileman_Buckets::fetchByName('pwa');
            if (!$bucketId) {

                return self::getShareErrorRedirect(self::SHARE_ERROR_UPLOAD);
            }

            $desc = Request::get('description');
            $link = Request::get('link');
            $name = Request::get('name');
            $desc = is_scalar($desc) ? trim((string) $desc) : '';
            $link = is_scalar($link) ? trim((string) $link) : '';
            $name = is_scalar($name) ? trim((string) $name) : '';
            
            $res = new ET();
            
            $fhArr = array();
            $haveUploadedFiles = false;
            if (!empty($_FILES['ulfile']) && isset($_FILES['ulfile']['error'])) {
                foreach ((array) $_FILES['ulfile']['error'] as $uploadError) {
                    if ((int) $uploadError !== UPLOAD_ERR_NO_FILE) {
                        $haveUploadedFiles = true;

                        break;
                    }
                }
            }
            if ($haveUploadedFiles) {
                if ($uploadError = self::getShareUploadError($_FILES['ulfile'])) {

                    return self::getShareErrorRedirect($uploadError);
                }

                $anonymousQuota = null;
                $anonymousOwnedFiles = array();
                $lastFileId = null;
                if (core_Users::getCurrent() <= 0) {
                    try {
                        self::validateAnonymousUploadSize($_FILES['ulfile']);
                    } catch (Throwable $t) {

                        return self::getShareErrorRedirect(self::SHARE_ERROR_SIZE);
                    }

                    try {
                        $anonymousQuota = self::reserveAnonymousUploadSlot();
                    } catch (Throwable $t) {

                        return self::getShareErrorRedirect(self::SHARE_ERROR_QUOTA);
                    }
                }

                $uploadLockId = null;
                $uploadLockObtained = false;
                try {
                    if ($anonymousQuota) {
                        $uploadLockId = 'pwaShareUpload|' . (int) $bucketId;
                        $uploadLockObtained = core_Locks::obtain($uploadLockId, 120, 10, 10);
                        expect($uploadLockObtained, 'Зает PWA share upload');
                        $lastFileId = self::getLastFileIdInBucket($bucketId);
                    }
                    $fhArr = fileman_Upload::makeUpload(array('ulfile' => $_FILES['ulfile']), $bucketId, $res);
                    if ($anonymousQuota) {
                        $anonymousOwnedFiles = self::collectNewSharedFiles($bucketId, $lastFileId);
                    }
                } catch (Throwable $t) {
                    // makeUpload() може да е създал първите файлове, преди
                    // следващ файл да хвърли изключение. Докато държим lock-а,
                    // намираме и махаме само новите анонимни FH записи.
                    if ($anonymousQuota && $uploadLockObtained && $lastFileId !== null) {
                        try {
                            $partialFiles = self::collectNewSharedFiles($bucketId, $lastFileId);
                            self::deleteUnclaimedSharedFiles($partialFiles);
                        } catch (Throwable $cleanupError) {
                            reportException($cleanupError);
                        }
                    }
                    self::releaseAnonymousUploadSlot($anonymousQuota);
                    reportException($t);

                    return self::getShareErrorRedirect(self::SHARE_ERROR_UPLOAD);
                } finally {
                    if ($uploadLockObtained) {
                        core_Locks::release($uploadLockId);
                    }
                }
            }

            // Някои native share източници подават само title. При
            // директния browser-verified fallback го пазим като текст.
            if ($isDirectShareFallback && !$haveUploadedFiles && !$desc && !$link && $name) {
                $desc = $name;
            }

            // Пазим оригиналния споделен текст за fallback бележка, ако URL-ът
            // не може да бъде свален безопасно след login.
            $body = $desc . (($desc && $link) ? "\n\n" : '') . $link;
            
            $fhArrCnt = countR($fhArr);
            $uploadDiagnosticSummary = self::reportShareUploadDiagnostic($uploadDiagnostic, $fhArrCnt);
            if ($fhArrCnt) {
                $fStr = $fhArrCnt == 1 ? 'Файл' : 'Файлове';
                // При анонимно share-ване може да има login и безопасно URL
                // сваляне преди финалния екран. Пазим списъка с всички
                // качени файлове достатъчно дълго, за да бъде видим там.
                status_Messages::newStatus(
                    "|*<div>|{$fStr}|*:</div>" . $res->getContent(),
                    'notice',
                    null,
                    300
                );

                if (core_Users::getCurrent() > 0) {
                    foreach ($fhArr as $fh) {
                        fileman_Log::updateLogInfo($fh, 'upload');
                    }
                }

                try {
                    $fileKey = self::storeSharedFiles(
                        $fhArr,
                        $anonymousQuota ?? null,
                        $anonymousOwnedFiles ?? array(),
                        $uploadDiagnosticSummary
                    );
                } catch (Throwable $t) {
                    self::releaseAnonymousUploadSlot($anonymousQuota ?? null);
                    reportException($t);

                    return self::getShareErrorRedirect(self::SHARE_ERROR_UPLOAD);
                }

                $key = null;
                $remoteKey = null;
                if ($link) {
                    try {
                        $remoteKey = self::storeSharedRemoteUrl($link, $name, 'file', $body);
                    } catch (Throwable $t) {
                        self::logWarning('Невалиден URL към споделени PWA файлове');
                        try {
                            $key = self::storeSharedUrlAsText($body, $name);
                            self::showSharedUrlFallbackStatus();
                        } catch (Throwable $fallbackError) {
                            reportException($fallbackError);
                            status_Messages::newStatus(tr(self::getShareErrorMessages()[self::SHARE_ERROR_URL]), 'warning');
                        }
                    }
                }

                return new Redirect(array(
                    'pwa_Share',
                    'SaveTargetFiles',
                    'fileKey' => $fileKey,
                    'key' => $key,
                    'remoteKey' => $remoteKey
                ));
            }

            if ($haveUploadedFiles) {
                if (!empty($anonymousOwnedFiles)) {
                    self::deleteUnclaimedSharedFiles($anonymousOwnedFiles);
                }
                self::releaseAnonymousUploadSlot($anonymousQuota ?? null);

                return self::getShareErrorRedirect(self::SHARE_ERROR_UPLOAD);
            }

            // Публичният share-target само запомня URL-а. Реалното сваляне
            // става след login в SaveTargetFiles, с DNS/IP/redirect проверки.
            if ($link) {
                try {
                    $remoteKey = self::storeSharedRemoteUrl($link, $name, 'file', $body);
                } catch (Throwable $t) {
                    try {
                        $key = self::storeSharedUrlAsText($body, $name);
                        self::showSharedUrlFallbackStatus();

                        return new Redirect(array('pwa_Share', 'SaveTargetFiles', 'key' => $key));
                    } catch (Throwable $fallbackError) {
                        reportException($t);
                        reportException($fallbackError);
                    }

                    return self::getShareErrorRedirect(self::SHARE_ERROR_URL);
                }

                return new Redirect(array('pwa_Share', 'SaveTargetFiles', 'remoteKey' => $remoteKey));
            }

            if ($desc && self::isRemoteShareUrl($desc)) {
                try {
                    $remoteKey = self::storeSharedRemoteUrl($desc, $name, 'html', $body);
                } catch (Throwable $t) {
                    reportException($t);

                    return self::getShareErrorRedirect(self::SHARE_ERROR_URL);
                }

                return new Redirect(array('pwa_Share', 'SaveTargetFiles', 'remoteKey' => $remoteKey));
            }

            if ($body) {
                $name = $name ? $name : tr('Споделен текст');
                try {
                    $key = self::storeSharedText($body, $name);
                } catch (Throwable $t) {
                    reportException($t);

                    return self::getShareErrorRedirect(self::SHARE_ERROR_UPLOAD);
                }

                return new Redirect(array('pwa_Share', 'SaveTargetFiles', 'key' => $key));
            }

            return self::getShareErrorRedirect(self::SHARE_ERROR_UPLOAD);
        }
         
        $script = "  navigator.serviceWorker.onmessage = (event) => {
                        window.location.href = event.data;
                    };";
        $tpl->append($script, 'SCRIPTS');
        
        $css = " .loader {
                      border: 16px solid #f3f3f3; /* Light grey */
                      border-top: 16px solid #3498db; /* Blue */
                      border-radius: 50%;
                      width: 120px;
                      height: 120px;
                      animation: spin 2s linear infinite;
                      margin: 100px auto;
                    }
                    
                    @keyframes spin {
                      0% { transform: rotate(0deg); }
                      100% { transform: rotate(360deg); }
                    }";
        $tpl->append($css, 'STYLES');
        
        return $tpl;
    }


    /**
     * Нормализира публичния код за грешка до затворен списък
     *
     * @param string $error
     *
     * @return string
     */
    protected static function normalizeShareError($error)
    {
        $allowed = array(
            self::SHARE_ERROR_TOKEN,
            self::SHARE_ERROR_QUOTA,
            self::SHARE_ERROR_SIZE,
            self::SHARE_ERROR_UPLOAD,
            self::SHARE_ERROR_NETWORK,
            self::SHARE_ERROR_URL
        );

        return in_array($error, $allowed, true) ? $error : self::SHARE_ERROR_NETWORK;
    }


    /**
     * Публични текстове без вътрешни данни от exception-а
     *
     * @return array
     */
    protected static function getShareErrorMessages()
    {
        return array(
            self::SHARE_ERROR_TOKEN => 'Сесията за споделяне е изтекла. Споделете съдържанието отново.',
            self::SHARE_ERROR_QUOTA => 'Има незавършено споделяне от това устройство или са направени твърде много опити. Опитайте по-късно.',
            self::SHARE_ERROR_SIZE => 'Споделените файлове са по-големи от разрешения размер. Намалете размера им и опитайте отново.',
            self::SHARE_ERROR_UPLOAD => 'Споделеното съдържание не можа да бъде запазено. Проверете файла и опитайте отново.',
            self::SHARE_ERROR_NETWORK => 'Споделянето не можа да бъде завършено. Проверете връзката и опитайте отново.',
            self::SHARE_ERROR_URL => 'Адресът не може да бъде свален безопасно. Допускат се само публични HTTP/HTTPS адреси.'
        );
    }


    /**
     * Редиректва само с безопасен machine-readable код
     *
     * @param string $error
     *
     * @param string|null $diagnostic
     *
     * @return Redirect
     */
    protected static function getShareErrorRedirect($error, $diagnostic = null)
    {
        $url = array('pwa_Share', 'Target', 'shareError' => self::normalizeShareError($error));
        if ($diagnostic = self::normalizeShareDiag($diagnostic)) {
            $url['shareDiag'] = $diagnostic;
        }

        return new Redirect($url);
    }


    /**
     * Нормализира диагностичния код до безопасен затворен списък
     *
     * @param string|null $diagnostic
     *
     * @return string|null
     */
    protected static function normalizeShareDiag($diagnostic)
    {
        $allowed = array(
            'php_missing_token',
            'php_invalid_token_format',
            'php_token_lock_busy',
            'php_expired_or_replayed',
            'php_upload_started_too_late',
            'php_browser_mismatch',
            'php_domain_mismatch',
            'php_user_mismatch',
            'php_direct_worker_request',
            'php_direct_site',
            'php_direct_mode',
            'php_direct_destination',
            'php_direct_content_type',
            'php_direct_origin',
            'php_direct_fields',
            'php_direct_empty_payload',
            'php_direct_rate_limit',
            'php_worker_marker',
            'php_worker_site',
            'php_worker_mode',
            'php_worker_destination',
            'php_worker_content_type',
            'php_worker_origin',
            'php_worker_fields',
            'php_worker_empty_payload',
            'php_worker_rate_limit',
            'php_internal_error',
            'php_error_without_diagnostic',
            'php_loader_error_without_diagnostic',
            'sw_form_data',
            'sw_missing_share_token',
            'sw_post_network',
            'sw_post_http',
            'sw_handle_failed',
            'sw_loader_http',
            'sw_loader_no_token',
            'sw_loader_failed'
        );

        return is_string($diagnostic) && in_array($diagnostic, $allowed, true)
            ? $diagnostic
            : null;
    }


    /**
     * Допуска в debug диагностиката само безопасна SW версия
     *
     * @param string|null $version
     *
     * @return string|null
     */
    protected static function normalizeShareWorkerVersion($version)
    {
        return is_string($version) && preg_match('/^[a-zA-Z0-9._-]{1,64}$/D', $version)
            ? $version
            : null;
    }


    /**
     * Превежда само стандартния PHP upload status до публичен код
     *
     * @param array $files
     *
     * @return string|null
     */
    protected static function getShareUploadError($files)
    {
        $failed = false;
        foreach ((array) ($files['error'] ?? array()) as $uploadError) {
            $uploadError = (int) $uploadError;
            if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {

                return self::SHARE_ERROR_SIZE;
            }

            if ($uploadError !== UPLOAD_ERR_OK && $uploadError !== UPLOAD_ERR_NO_FILE) {
                $failed = true;
            }
        }

        return $failed ? self::SHARE_ERROR_UPLOAD : null;
    }


    /**
     * Събира само безопасни броячи за пътя browser -> SW -> PHP.
     * Не включва имена, token-и, BRID или съдържание.
     *
     * @return stdClass
     */
    protected static function getShareUploadDiagnostic()
    {
        $isWorker = trim((string) ($_SERVER['HTTP_X_PWA_SHARE_WORKER'] ?? '')) === '1';
        $expectedCount = null;
        if ($isWorker) {
            $rawCount = trim((string) ($_SERVER[self::SHARE_FILE_COUNT_SERVER_KEY] ?? ''));
            if (preg_match('/^(?:0|[1-9][0-9]{0,3})$/D', $rawCount)) {
                $expectedCount = (int) $rawCount;
            }
        }

        $fileField = trim((string) ($_SERVER[self::SHARE_FILE_FIELD_SERVER_KEY] ?? ''));
        if (!$isWorker || !in_array($fileField, array('file', 'file-array', 'both', 'none'), true)) {
            if (isset($_FILES['file'])) {
                $fileField = is_array($_FILES['file']['name'] ?? null) ? 'file-array' : 'file';
            } elseif (isset($_FILES['ulfile'])) {
                $fileField = 'ulfile';
            } else {
                $fileField = 'none';
            }
        }

        $workerVersion = $isWorker
            ? self::normalizeShareWorkerVersion($_SERVER[self::SHARE_WORKER_VERSION_SERVER_KEY] ?? null)
            : null;

        return (object) array(
            'source' => $isWorker ? 'worker' : 'direct',
            'field' => $fileField,
            'workerVersion' => $workerVersion ?: 'none',
            'expectedCount' => $expectedCount,
            'nativeCount' => self::countSharedFileEntries($_FILES['file'] ?? array()),
            'workerCount' => self::countSharedFileEntries($_FILES['ulfile'] ?? array()),
            'normalizedCount' => 0,
            'maxFileUploads' => max(0, (int) ini_get('max_file_uploads'))
        );
    }


    /**
     * Брой реално представени файлови записи в стандартен $_FILES елемент.
     *
     * @param array $files
     *
     * @return int
     */
    protected static function countSharedFileEntries($files)
    {
        if (!is_array($files) || !array_key_exists('error', $files)) {

            return 0;
        }

        $count = 0;
        foreach ((array) $files['error'] as $uploadError) {
            if ((int) $uploadError !== UPLOAD_ERR_NO_FILE) {
                $count++;
            }
        }

        return $count;
    }


    /**
     * Показва броячите на debug потребител и предупреждава при реална загуба.
     *
     * @param stdClass $diagnostic
     * @param int      $savedCount
     *
     * @return string|null
     */
    protected static function reportShareUploadDiagnostic($diagnostic, $savedCount)
    {
        if (!is_object($diagnostic)) {

            return null;
        }

        $savedCount = max(0, (int) $savedCount);
        $expectedText = isset($diagnostic->expectedCount) ? (string) $diagnostic->expectedCount : 'unknown';
        $summary = 'source=' . $diagnostic->source
            . '; field=' . $diagnostic->field
            . '; sw=' . $diagnostic->workerVersion
            . '; expected=' . $expectedText
            . '; native=' . (int) $diagnostic->nativeCount
            . '; worker=' . (int) $diagnostic->workerCount
            . '; normalized=' . (int) $diagnostic->normalizedCount
            . '; saved=' . $savedCount
            . '; max_file_uploads=' . (int) $diagnostic->maxFileUploads;

        $expectedMismatch = isset($diagnostic->expectedCount) &&
            (int) $diagnostic->expectedCount !== (int) $diagnostic->normalizedCount;
        $savedMismatch = (int) $diagnostic->normalizedCount !== $savedCount;
        if ($expectedMismatch || $savedMismatch) {
            self::logWarning('PWA share upload count mismatch: ' . $summary);
        }

        if ($expectedMismatch) {
            status_Messages::newStatus(
                tr('Не всички споделени файлове достигнаха до PHP. Проверете настройката max_file_uploads.')
                    . ' (' . (int) $diagnostic->normalizedCount . '/' . (int) $diagnostic->expectedCount . ')',
                'warning',
                null,
                300
            );
        } elseif ($savedMismatch) {
            status_Messages::newStatus(
                tr('Не всички приети файлове бяха записани.')
                    . ' (' . $savedCount . '/' . (int) $diagnostic->normalizedCount . ')',
                'warning',
                null,
                300
            );
        }

        $hasFileSignal = isset($diagnostic->expectedCount) && (int) $diagnostic->expectedCount > 0;
        $hasFileSignal = $hasFileSignal || (int) $diagnostic->nativeCount > 0 ||
            (int) $diagnostic->workerCount > 0 || (int) $diagnostic->normalizedCount > 0 || $savedCount > 0;
        if ($hasFileSignal && haveRole('debug')) {
            self::showShareUploadDebugStatus($summary);
        }

        return $hasFileSignal ? $summary : null;
    }


    /**
     * Показва копируемите безопасни броячи само на debug потребител.
     *
     * @param string $summary
     */
    protected static function showShareUploadDebugStatus($summary)
    {
        if (!haveRole('debug') || !is_string($summary) || strlen($summary) > 500 ||
            !preg_match('/^[a-zA-Z0-9=; ._-]+$/D', $summary)) {

            return;
        }

        $debugStatus = '<div>' . tr('PWA upload диагностика') . ':</div>'
            . '<textarea readonly rows="2" style="width:100%;max-width:620px" '
            . 'onclick="this.select()" aria-label="PWA upload diagnostic">'
            . ht::escapeAttr($summary) . '</textarea>';
        status_Messages::newStatus($debugStatus, 'notice', null, 300);
    }


    /**
     * Проверява дали web server-ът е приел POST, по-голям от PHP лимита
     *
     * @return bool
     */
    protected static function isPostBodyTooLarge()
    {
        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        $postMaxSize = (int) core_Os::getBytes(ini_get('post_max_size'));

        return $contentLength > 0 && $postMaxSize > 0 && $contentLength > $postMaxSize;
    }


    /**
     * Издава еднократен, краткоживеещ token за share-target POST
     *
     * @return string
     */
    protected static function issueShareToken()
    {
        expect(self::hasAllowedShareRequestOrigin(), 'Невалиден източник на PWA share заявката');
        self::enforceShareTokenRateLimit();

        $token = getRandomString(24);
        $userId = (int) core_Users::getCurrent();
        $data = (object) array(
            'brid' => log_Browsers::getBrid(),
            'domainId' => (int) cms_Domains::getCurrent('id', false),
            'userId' => $userId > 0 ? $userId : 0,
            'issuedOn' => time()
        );

        expect(core_Permanent::set(self::getShareTokenPermanentKey($token), $data, self::SHARE_TOKEN_KEEP_MINUTES),
            'Неуспешно запазване на PWA share token');
        header(self::SHARE_TOKEN_HEADER . ': ' . $token);
        header('Cache-Control: no-store, no-cache, must-revalidate');

        return $token;
    }


    /**
     * Валидира и атомарно консумира token-а преди обработката на upload-а
     */
    protected static function validateAndConsumeShareToken()
    {
        $token = Request::get(self::SHARE_TOKEN_FIELD, 'varchar');
        if ($token === null || $token === '') {
            // Подадена, но празна token стойност е невалидна. Fallback-ът
            // е само за заявки, в които token поле изобщо липсва.
            expect(!array_key_exists(self::SHARE_TOKEN_FIELD, $_POST) &&
                !array_key_exists(self::SHARE_TOKEN_FIELD, $_GET), 'Невалиден PWA share token');

            $isWorkerFallback = array_key_exists('HTTP_X_PWA_SHARE_WORKER', $_SERVER);
            if ($isWorkerFallback) {
                self::validateWorkerShareFallback();
            } else {
                // Rolling-update fallback само за оригиналния POST от
                // browser/OS share UI. Background POST без точния worker
                // marker остава забранен.
                self::validateDirectShareFallback();
            }

            if (core_Users::getCurrent() <= 0) {
                try {
                    self::enforceShareTokenRateLimit();
                } catch (Throwable $t) {
                    $rateDiagnostic = $isWorkerFallback
                        ? 'php_worker_rate_limit'
                        : 'php_direct_rate_limit';
                    throw new RuntimeException(self::SHARE_DIAG_EXCEPTION_PREFIX . $rateDiagnostic);
                }
            }

            return false;
        }

        expect(is_string($token) && preg_match('/^[a-f0-9]{48}$/D', $token), 'Невалиден PWA share token');

        $tokenKey = self::getShareTokenPermanentKey($token);
        $lockId = $tokenKey;
        expect(core_Locks::obtain($lockId, 5, 1, 1), 'Зает PWA share token');

        try {
            $data = core_Permanent::get($tokenKey);
            core_Permanent::remove($tokenKey);
        } finally {
            core_Locks::release($lockId);
        }

        expect(is_object($data), 'Изтекъл или вече използван PWA share token');
        $requestStartedOn = (int) ($_SERVER['REQUEST_TIME'] ?? time());
        expect(!empty($data->issuedOn) && ($requestStartedOn - (int) $data->issuedOn) <= self::SHARE_TOKEN_START_MINUTES * 60,
            'PWA share token-ът е изтекъл преди началото на upload-а');
        expect($data->brid === log_Browsers::getBrid(), 'PWA share token-ът е от друго устройство');
        expect((int) $data->domainId === (int) cms_Domains::getCurrent('id', false), 'PWA share token-ът е от друг домейн');
        if (!empty($data->userId)) {
            expect((int) $data->userId === (int) core_Users::getCurrent(), 'PWA share token-ът е от друг потребител');
        }

        return true;
    }


    /**
     * Връща namespaced ключ за persistent token записа
     *
     * @param string $token
     *
     * @return string
     */
    protected static function getShareTokenPermanentKey($token)
    {
        // core_Permanent скъсява дългите ключове, като запазва началото им.
        // Token-ът е пръв, за да не намаляваме излишно entropy-то на ключа.
        return $token . '|' . self::SHARE_TOKEN_PERMANENT_NAMESPACE;
    }


    /**
     * Допуска tokenless POST само когато е директна browser/OS навигация
     * по manifest share_target, а не fetch от Service Worker или сайт.
     */
    protected static function validateDirectShareFallback()
    {
        if (array_key_exists('HTTP_X_PWA_SHARE_WORKER', $_SERVER)) {
            self::throwShareDiagnostic('php_direct_worker_request');
        }

        $fetchSite = strtolower(trim($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
        if ($fetchSite !== 'none') {
            self::throwShareDiagnostic('php_direct_site');
        }

        $fetchMode = strtolower(trim($_SERVER['HTTP_SEC_FETCH_MODE'] ?? ''));
        if ($fetchMode !== 'navigate') {
            self::throwShareDiagnostic('php_direct_mode');
        }

        $fetchDestination = strtolower(trim($_SERVER['HTTP_SEC_FETCH_DEST'] ?? ''));
        if ($fetchDestination !== 'document') {
            self::throwShareDiagnostic('php_direct_destination');
        }

        $contentType = trim($_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? ''));
        $contentTypeParts = explode(';', $contentType, 2);
        $mediaType = strtolower(trim($contentTypeParts[0]));
        if ($mediaType !== 'multipart/form-data' ||
            !preg_match('/(?:^|;)\s*boundary\s*=\s*(?:"[^"]+"|[^;\s]+)/i', $contentType)) {
            self::throwShareDiagnostic('php_direct_content_type');
        }

        $origin = trim($_SERVER['HTTP_ORIGIN'] ?? '');
        if ($origin !== '' && strtolower($origin) !== 'null' && !self::isExactRequestOrigin($origin)) {
            self::throwShareDiagnostic('php_direct_origin');
        }

        $allowedPostFields = array('name' => true, 'description' => true, 'link' => true);
        foreach (array_keys($_POST) as $field) {
            if (!isset($allowedPostFields[$field]) || !is_scalar($_POST[$field])) {
                self::throwShareDiagnostic('php_direct_fields');
            }
        }

        foreach (array_keys($_FILES) as $field) {
            if ($field !== 'file' || !is_array($_FILES[$field])) {
                self::throwShareDiagnostic('php_direct_fields');
            }
        }

        $haveText = false;
        foreach ($allowedPostFields as $field => $dummy) {
            if (isset($_POST[$field]) && trim((string) $_POST[$field]) !== '') {
                $haveText = true;

                break;
            }
        }

        if (!$haveText && !self::hasDirectSharedFile()) {
            self::throwShareDiagnostic('php_direct_empty_payload');
        }
    }


    /**
     * Допуска tokenless POST от активния Service Worker само като
     * browser-verified same-origin fetch. Това покрива proxy/redirect,
     * който е премахнал token header-а от loader GET отговора.
     */
    protected static function validateWorkerShareFallback()
    {
        if (trim((string) ($_SERVER['HTTP_X_PWA_SHARE_WORKER'] ?? '')) !== '1') {
            self::throwShareDiagnostic('php_worker_marker');
        }

        $fetchSite = strtolower(trim($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
        if ($fetchSite !== 'same-origin') {
            self::throwShareDiagnostic('php_worker_site');
        }

        $fetchMode = strtolower(trim($_SERVER['HTTP_SEC_FETCH_MODE'] ?? ''));
        if ($fetchMode !== 'cors') {
            self::throwShareDiagnostic('php_worker_mode');
        }

        $fetchDestination = strtolower(trim($_SERVER['HTTP_SEC_FETCH_DEST'] ?? ''));
        if ($fetchDestination !== 'empty') {
            self::throwShareDiagnostic('php_worker_destination');
        }

        $contentType = trim($_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? ''));
        $contentTypeParts = explode(';', $contentType, 2);
        $mediaType = strtolower(trim($contentTypeParts[0]));
        if ($mediaType !== 'multipart/form-data' ||
            !preg_match('/(?:^|;)\s*boundary\s*=\s*(?:"[^"]+"|[^;\s]+)/i', $contentType)) {
            self::throwShareDiagnostic('php_worker_content_type');
        }

        // Same-origin FormData POST обикновено има Origin. Допускаме
        // липсващ header за reverse proxy, но никога opaque "null" Origin.
        $origin = trim($_SERVER['HTTP_ORIGIN'] ?? '');
        if ($origin !== '' && !self::isExactRequestOrigin($origin)) {
            self::throwShareDiagnostic('php_worker_origin');
        }

        $allowedPostFields = array('name' => true, 'description' => true, 'link' => true);
        foreach (array_keys($_POST) as $field) {
            if (!isset($allowedPostFields[$field]) || !is_scalar($_POST[$field])) {
                self::throwShareDiagnostic('php_worker_fields');
            }
        }

        $workerFileFields = array_keys($_FILES);
        foreach ($workerFileFields as $field) {
            if (!in_array($field, array('ulfile', 'file'), true) || !is_array($_FILES[$field])) {
                self::throwShareDiagnostic('php_worker_fields');
            }
        }
        if (count($workerFileFields) > 1) {
            self::throwShareDiagnostic('php_worker_fields');
        }

        $haveText = false;
        foreach ($allowedPostFields as $field => $dummy) {
            if (isset($_POST[$field]) && trim((string) $_POST[$field]) !== '') {
                $haveText = true;

                break;
            }
        }

        if (!$haveText && !self::hasWorkerSharedFile() && !self::hasDirectSharedFile()) {
            self::throwShareDiagnostic('php_worker_empty_payload');
        }
    }


    /**
     * Има ли поне един реален файл в нормализираното worker поле
     *
     * @return bool
     */
    protected static function hasWorkerSharedFile()
    {
        if (empty($_FILES['ulfile']) || !array_key_exists('error', $_FILES['ulfile'])) {

            return false;
        }

        foreach ((array) $_FILES['ulfile']['error'] as $uploadError) {
            if ((int) $uploadError !== UPLOAD_ERR_NO_FILE) {

                return true;
            }
        }

        return false;
    }


    /**
     * Има ли поне един реално подаден файл в native share полето
     *
     * @return bool
     */
    protected static function hasDirectSharedFile()
    {
        if (empty($_FILES['file']) || !array_key_exists('error', $_FILES['file'])) {

            return false;
        }

        foreach ((array) $_FILES['file']['error'] as $uploadError) {
            if ((int) $uploadError !== UPLOAD_ERR_NO_FILE) {

                return true;
            }
        }

        return false;
    }


    /**
     * Превежда native manifest полето `file` към очакваното от fileman
     * `ulfile[]`. Извиква се след token/fallback проверката и пази прехода
     * между стар и нов manifest/Service Worker.
     */
    protected static function normalizeDirectShareFiles()
    {
        if (empty($_FILES['file']) || isset($_FILES['ulfile'])) {

            return;
        }

        $file = $_FILES['file'];
        if (!is_array($file)) {

            return;
        }

        $normalized = array();
        foreach (array('name', 'full_path', 'type', 'tmp_name', 'error', 'size') as $field) {
            if (!array_key_exists($field, $file)) {
                continue;
            }

            $normalized[$field] = is_array($file[$field]) ? $file[$field] : array($file[$field]);
        }

        if (!array_key_exists('error', $normalized)) {

            return;
        }

        $_FILES['ulfile'] = $normalized;
        unset($_FILES['file']);
    }


    /**
     * Хвърля exception, съдържащ само allowlisted диагностичен код
     *
     * @param string $diagnostic
     */
    protected static function throwShareDiagnostic($diagnostic)
    {
        $diagnostic = self::normalizeShareDiag($diagnostic);
        if (!$diagnostic) {
            $diagnostic = 'php_internal_error';
        }

        throw new RuntimeException(self::SHARE_DIAG_EXCEPTION_PREFIX . $diagnostic);
    }


    /**
     * Логва само безопасен код за причината, без token и binding данни
     *
     * @param Throwable $exception
     */
    protected static function logShareTokenValidationFailure($exception)
    {
        $reason = self::getShareTokenValidationReason($exception);
        self::logWarning('PWA share token validation failed: ' . $reason);

        return $reason;
    }


    /**
     * Извлича само безопасен reason code от exception-а
     *
     * @param Throwable $exception
     *
     * @return string
     */
    protected static function getShareTokenValidationReason($exception)
    {
        $details = array($exception->getMessage());
        if (method_exists($exception, 'getDebug')) {
            $debug = $exception->getDebug();
            if (is_array($debug)) {
                $details = array_merge($details, $debug);
            }
        }

        $reasonMap = array(
            'Липсващ PWA share token' => 'php_missing_token',
            'Невалиден PWA share token' => 'php_invalid_token_format',
            'Зает PWA share token' => 'php_token_lock_busy',
            'Изтекъл или вече използван PWA share token' => 'php_expired_or_replayed',
            'PWA share token-ът е изтекъл преди началото на upload-а' => 'php_upload_started_too_late',
            'PWA share token-ът е от друго устройство' => 'php_browser_mismatch',
            'PWA share token-ът е от друг домейн' => 'php_domain_mismatch',
            'PWA share token-ът е от друг потребител' => 'php_user_mismatch'
        );

        $reason = 'php_internal_error';
        foreach ($details as $detail) {
            if (!is_string($detail)) {
                continue;
            }

            if (strpos($detail, self::SHARE_DIAG_EXCEPTION_PREFIX) === 0) {
                $diagnostic = substr($detail, strlen(self::SHARE_DIAG_EXCEPTION_PREFIX));
                $reason = self::normalizeShareDiag($diagnostic) ?: 'php_internal_error';

                break;
            }

            if (isset($reasonMap[$detail])) {
                $reason = $reasonMap[$detail];

                break;
            }
        }

        return $reason;
    }


    /**
     * Проверява browser-provided origin metadata за share заявката
     *
     * @return bool
     */
    protected static function hasAllowedShareRequestOrigin($allowTopLevelNavigation = true)
    {
        $fetchSite = strtolower(trim($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
        if ($fetchSite) {
            if ($fetchSite === 'none') {

                return (bool) $allowTopLevelNavigation;
            }

            if ($fetchSite !== 'same-origin') {

                return false;
            }

            // Sec-Fetch-Site е browser-controlled. Ако има и Origin, той
            // също трябва да съвпада точно с origin-а на текущата заявка.
            $origin = trim($_SERVER['HTTP_ORIGIN'] ?? '');

            return !$origin || self::isExactRequestOrigin($origin);
        }

        // Fallback за по-стари браузъри: без нито един от двата browser
        // сигнала заявката се отхвърля, вместо да се приема по подразбиране.
        $origin = trim($_SERVER['HTTP_ORIGIN'] ?? '');
        if (!$origin) {

            return false;
        }

        return self::isExactRequestOrigin($origin);
    }


    /**
     * Сравнява Origin със scheme/host/port на текущата заявка
     *
     * @param string $origin
     *
     * @return bool
     */
    protected static function isExactRequestOrigin($origin)
    {
        $originParts = @parse_url($origin);
        $requestParts = @parse_url(core_App::getSelfURL());
        if (!is_array($originParts) || !is_array($requestParts) ||
            empty($originParts['scheme']) || empty($originParts['host']) ||
            empty($requestParts['scheme']) || empty($requestParts['host'])) {

            return false;
        }

        if (!in_array(strtolower($originParts['scheme']), array('http', 'https'), true) ||
            strtolower($originParts['scheme']) !== strtolower($requestParts['scheme']) ||
            strtolower($originParts['host']) !== strtolower($requestParts['host']) ||
            isset($originParts['user']) || isset($originParts['pass']) ||
            isset($originParts['query']) || isset($originParts['fragment']) ||
            (!empty($originParts['path']) && $originParts['path'] !== '/')) {

            return false;
        }

        $originPort = self::getOriginPort($originParts);
        $requestPort = self::getOriginPort($requestParts);

        return $originPort === $requestPort;
    }


    /**
     * Нормализира default порта на origin
     *
     * @param array $parts
     *
     * @return int
     */
    protected static function getOriginPort($parts)
    {
        if (isset($parts['port'])) {

            return (int) $parts['port'];
        }

        return strtolower($parts['scheme']) === 'https' ? 443 : 80;
    }


    /**
     * Ограничава издаването на token-и към анонимни клиенти
     */
    protected static function enforceShareTokenRateLimit()
    {
        if (core_Users::getCurrent() > 0) {

            return;
        }

        $domainId = (int) cms_Domains::getCurrent('id', false);
        self::incrementShareRate('brid|' . $domainId . '|' . log_Browsers::getBrid(), self::SHARE_RATE_BRID_LIMIT);
        self::incrementShareRate('ip|' . $domainId . '|' . self::getShareClientIp(), self::SHARE_RATE_IP_LIMIT);
    }


    /**
     * Увеличава един rate-limit брояч под lock
     *
     * @param string $scope
     * @param int    $limit
     */
    protected static function incrementShareRate($scope, $limit)
    {
        $cacheKey = md5($scope);
        $lockId = self::SHARE_RATE_CACHE_TYPE . '|' . $cacheKey;
        expect(core_Locks::obtain($lockId, 5, 3, 1), 'Зает PWA share rate limit');

        try {
            $count = (int) core_Cache::get(self::SHARE_RATE_CACHE_TYPE, $cacheKey);
            expect($count < $limit, 'Твърде много опити за PWA споделяне. Опитайте след няколко минути.');
            core_Cache::set(self::SHARE_RATE_CACHE_TYPE, $cacheKey, $count + 1, self::SHARE_RATE_WINDOW_MINUTES);
        } finally {
            core_Locks::release($lockId);
        }
    }


    /**
     * Проверява общия размер на анонимен upload
     *
     * @param array $files
     */
    protected static function validateAnonymousUploadSize($files)
    {
        $totalSize = 0;
        foreach ((array) ($files['size'] ?? array()) as $size) {
            $totalSize += max(0, (int) $size);
        }

        expect($totalSize > 0 && $totalSize <= self::SHARE_ANONYMOUS_MAX_BYTES,
            'Анонимното PWA споделяне е ограничено до 100 MB. Влезте в системата и опитайте отново.');
    }


    /**
     * Запазва един чакащ анонимен upload за BRID/домейн. Отделният IP
     * брояч допуска нормална работа зад NAT, но ограничава масови BRID-и.
     *
     * @return stdClass
     */
    protected static function reserveAnonymousUploadSlot()
    {
        $domainId = (int) cms_Domains::getCurrent('id', false);
        $cacheKey = md5('brid|' . $domainId . '|' . log_Browsers::getBrid());
        $ipCacheKey = md5('ip|' . $domainId . '|' . self::getShareClientIp());
        $slotId = getRandomString(16);
        $lockId = self::SHARE_ANONYMOUS_CACHE_TYPE . '|' . $cacheKey;
        expect(core_Locks::obtain($lockId, 5, 3, 1), 'Зает PWA share upload quota');

        $ipLockId = self::SHARE_ANONYMOUS_IP_CACHE_TYPE . '|' . $ipCacheKey;
        $ipLockObtained = false;
        try {
            expect(!core_Cache::get(self::SHARE_ANONYMOUS_CACHE_TYPE, $cacheKey),
                'Има незавършено анонимно споделяне от това устройство. Влезте в системата или опитайте по-късно.');

            $ipLockObtained = core_Locks::obtain($ipLockId, 5, 3, 1);
            expect($ipLockObtained, 'Зает PWA share IP quota');
            $ipSlots = core_Cache::get(self::SHARE_ANONYMOUS_IP_CACHE_TYPE, $ipCacheKey);
            $ipSlots = is_array($ipSlots) ? $ipSlots : array();
            $now = time();
            foreach ($ipSlots as $activeSlotId => $expiresOn) {
                if ((int) $expiresOn <= $now) {
                    unset($ipSlots[$activeSlotId]);
                }
            }
            expect(count($ipSlots) < self::SHARE_ANONYMOUS_IP_LIMIT,
                'Твърде много незавършени анонимни споделяния от тази мрежа.');
            $ipSlots[$slotId] = $now + self::SHARE_ANONYMOUS_KEEP_MINUTES * 60;
            core_Cache::set(self::SHARE_ANONYMOUS_IP_CACHE_TYPE, $ipCacheKey, $ipSlots, self::SHARE_ANONYMOUS_KEEP_MINUTES);
            core_Cache::set(self::SHARE_ANONYMOUS_CACHE_TYPE, $cacheKey, $slotId, self::SHARE_ANONYMOUS_KEEP_MINUTES);
        } finally {
            if ($ipLockObtained) {
                core_Locks::release($ipLockId);
            }
            core_Locks::release($lockId);
        }

        return (object) array('key' => $cacheKey, 'id' => $slotId, 'ipKey' => $ipCacheKey);
    }


    /**
     * IP за share квотите. X-Forwarded-For не се приема без локална
     * конфигурация за доверени proxy-та, защото клиентът може да го подправи.
     *
     * @return string
     */
    protected static function getShareClientIp()
    {
        $ip = trim($_SERVER['REMOTE_ADDR'] ?? '');

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
    }


    /**
     * Освобождава квотата на завършен/неуспешен анонимен upload
     *
     * @param stdClass|string|null $slot
     */
    protected static function releaseAnonymousUploadSlot($slot)
    {
        if (is_object($slot)) {
            $cacheKey = $slot->key ?? ($slot->anonymousQuotaKey ?? null);
            $slotId = $slot->id ?? ($slot->anonymousQuotaId ?? null);
            $ipCacheKey = $slot->ipKey ?? ($slot->anonymousQuotaIpKey ?? null);
        } else {
            // Кратка съвместимост с вече започнал share от предишната версия.
            $cacheKey = $slot;
            $slotId = null;
            $ipCacheKey = null;
        }

        if (!$cacheKey || !preg_match('/^[a-f0-9]{32}$/D', $cacheKey) ||
            ($slotId !== null && !preg_match('/^[a-f0-9]{32}$/D', $slotId)) ||
            ($ipCacheKey !== null && !preg_match('/^[a-f0-9]{32}$/D', $ipCacheKey))) {

            return;
        }

        $lockId = self::SHARE_ANONYMOUS_CACHE_TYPE . '|' . $cacheKey;
        if (!core_Locks::obtain($lockId, 5, 3, 1)) {

            return;
        }

        try {
            $currentSlotId = core_Cache::get(self::SHARE_ANONYMOUS_CACHE_TYPE, $cacheKey);
            $isCurrentSlot = $slotId !== null
                ? is_string($currentSlotId) && hash_equals($currentSlotId, $slotId)
                : ($currentSlotId === 1 || $currentSlotId === '1');
            if ($isCurrentSlot) {
                core_Cache::remove(self::SHARE_ANONYMOUS_CACHE_TYPE, $cacheKey);
                self::releaseAnonymousIpSlot($ipCacheKey, $slotId);
            }
        } finally {
            core_Locks::release($lockId);
        }
    }


    /**
     * Освобождава вторичния IP slot само по неговия непроменим id
     *
     * @param string|null $ipCacheKey
     * @param string|null $slotId
     */
    protected static function releaseAnonymousIpSlot($ipCacheKey, $slotId)
    {
        if (!$ipCacheKey || !$slotId ||
            !preg_match('/^[a-f0-9]{32}$/D', $ipCacheKey) ||
            !preg_match('/^[a-f0-9]{32}$/D', $slotId)) {

            return;
        }

        $lockId = self::SHARE_ANONYMOUS_IP_CACHE_TYPE . '|' . $ipCacheKey;
        if (!core_Locks::obtain($lockId, 5, 3, 1)) {

            return;
        }

        try {
            $ipSlots = core_Cache::get(self::SHARE_ANONYMOUS_IP_CACHE_TYPE, $ipCacheKey);
            if (!is_array($ipSlots) || !array_key_exists($slotId, $ipSlots)) {

                return;
            }

            unset($ipSlots[$slotId]);
            if ($ipSlots) {
                core_Cache::set(self::SHARE_ANONYMOUS_IP_CACHE_TYPE, $ipCacheKey, $ipSlots, self::SHARE_ANONYMOUS_KEEP_MINUTES);
            } else {
                core_Cache::remove(self::SHARE_ANONYMOUS_IP_CACHE_TYPE, $ipCacheKey);
            }
        } finally {
            core_Locks::release($lockId);
        }
    }


    /**
     * Последният fileman id в кофата преди текущия upload
     *
     * @param int $bucketId
     *
     * @return int
     */
    protected static function getLastFileIdInBucket($bucketId)
    {
        $query = fileman_Files::getQuery();
        $query->where(array("#bucketId = '[#1#]'", (int) $bucketId));
        $query->orderBy('id', 'DESC');
        $query->show('id');
        $query->limit(1);
        $rec = $query->fetch();

        return $rec ? (int) $rec->id : 0;
    }


    /**
     * Връща точен snapshot на анонимните fileman записи, създадени след
     * началото на текущия upload. Методът се извиква под PWA upload lock,
     * включително при частично завършил makeUpload().
     *
     * @param int   $bucketId
     * @param int   $lastFileId
     *
     * @return array
     */
    protected static function collectNewSharedFiles($bucketId, $lastFileId)
    {
        $res = array();
        $query = fileman_Files::getQuery();
        $query->where(array("#bucketId = '[#1#]' AND #id > '[#2#]'", (int) $bucketId, (int) $lastFileId));
        $query->where('(#createdBy IS NULL OR #createdBy <= 0)');
        $query->show('id,fileHnd,dataId,bucketId,name,createdOn,createdBy');
        $query->orderBy('id', 'ASC');
        while ($rec = $query->fetch()) {
            $res[] = (object) array(
                'id' => (int) $rec->id,
                'fileHnd' => $rec->fileHnd,
                'dataId' => (int) $rec->dataId,
                'bucketId' => (int) $rec->bucketId,
                'name' => $rec->name,
                'createdOn' => $rec->createdOn ?? null,
                'createdBy' => (int) ($rec->createdBy ?? 0)
            );
        }

        return $res;
    }


    /**
     * Запазва временно файловете от един share-target POST
     *
     * @param array             $fhArr
     * @param stdClass|null     $anonymousQuota
     * @param array             $anonymousOwnedFiles
     * @param string|null       $uploadDiagnostic
     *
     * @return string
     */
    protected static function storeSharedFiles(
        $fhArr,
        $anonymousQuota = null,
        $anonymousOwnedFiles = array(),
        $uploadDiagnostic = null
    )
    {
        $anonymousQuotaKey = is_object($anonymousQuota) ? ($anonymousQuota->key ?? null) : $anonymousQuota;
        $anonymousQuotaId = is_object($anonymousQuota) ? ($anonymousQuota->id ?? null) : null;
        $anonymousQuotaIpKey = is_object($anonymousQuota) ? ($anonymousQuota->ipKey ?? null) : null;
        $key = md5(str::getRand() . microtime(true) . json_encode($fhArr));
        $data = (object) array(
            'files' => $fhArr,
            'brid' => log_Browsers::getBrid(),
            'userId' => (int) core_Users::getCurrent(),
            'domainId' => (int) cms_Domains::getCurrent('id', false),
            'anonymousQuotaKey' => $anonymousQuotaKey,
            'anonymousQuotaId' => $anonymousQuotaId,
            'anonymousQuotaIpKey' => $anonymousQuotaIpKey,
            'uploadDiagnostic' => is_string($uploadDiagnostic) ? $uploadDiagnostic : null
        );

        if ($anonymousQuotaKey) {
            expect($anonymousQuotaId && preg_match('/^[a-f0-9]{32}$/D', $anonymousQuotaId), 'Невалиден PWA share quota slot');
            $data->cleanupData = (object) array(
                'fileKey' => $key,
                'cleanupId' => getRandomString(16),
                'anonymousQuotaKey' => $anonymousQuotaKey,
                'anonymousQuotaId' => $anonymousQuotaId,
                'anonymousQuotaIpKey' => $anonymousQuotaIpKey,
                'domainId' => $data->domainId,
                'ownedFiles' => array_values((array) $anonymousOwnedFiles)
            );
        }

        if (!empty($data->cleanupData)) {
            try {
                core_Cache::set(self::SHARE_FILES_CACHE_TYPE, $key, $data, self::SHARE_FILES_KEEP_MINUTES);
                $callId = core_CallOnTime::setOnce(
                    'pwa_Share',
                    'CleanupAnonymousShare',
                    $data->cleanupData,
                    dt::addSecs(self::SHARE_FILES_CLEANUP_MINUTES * 60)
                );
                expect($callId, 'Неуспешно планиране на PWA share cleanup');
                $data->cleanupCallId = (int) $callId;
                core_Cache::set(self::SHARE_FILES_CACHE_TYPE, $key, $data, self::SHARE_FILES_KEEP_MINUTES);
            } catch (Throwable $t) {
                self::removeScheduledAnonymousCleanup($data);
                self::cleanupAnonymousShare($data->cleanupData);

                throw $t;
            }
        } else {
            core_Cache::set(self::SHARE_FILES_CACHE_TYPE, $key, $data, self::SHARE_FILES_KEEP_MINUTES);
        }

        return $key;
    }


    /**
     * Отложено изчистване на непотърсен анонимен upload
     *
     * @param stdClass $data
     *
     * @return string|null
     */
    public function callback_CleanupAnonymousShare($data)
    {
        return self::cleanupAnonymousShare($data);
    }


    /**
     * Изтрива само доказано създадените от този share файлови записи
     *
     * @param stdClass $cleanupData
     *
     * @return string|null
     */
    protected static function cleanupAnonymousShare($cleanupData)
    {
        if (!is_object($cleanupData) ||
            empty($cleanupData->fileKey) || !preg_match('/^[a-f0-9]{32}$/D', $cleanupData->fileKey) ||
            empty($cleanupData->cleanupId) || !preg_match('/^[a-f0-9]{32}$/D', $cleanupData->cleanupId) ||
            empty($cleanupData->anonymousQuotaKey) || !preg_match('/^[a-f0-9]{32}$/D', $cleanupData->anonymousQuotaKey) ||
            empty($cleanupData->anonymousQuotaId) || !preg_match('/^[a-f0-9]{32}$/D', $cleanupData->anonymousQuotaId)) {

            return null;
        }

        $lockId = self::getSharedFilesLockId($cleanupData->fileKey);
        if (!core_Locks::obtain($lockId, 120, 20, 20)) {
            $retryData = clone $cleanupData;
            $retryData->retry = (int) ($retryData->retry ?? 0) + 1;
            if ($retryData->retry <= 5) {
                core_CallOnTime::setOnce('pwa_Share', 'CleanupAnonymousShare', $retryData, dt::addSecs(60));
            }

            return null;
        }

        $deleted = 0;
        try {
            if (core_Cache::get(self::SHARE_FILES_CLAIMED_CACHE_TYPE, $cleanupData->fileKey)) {
                self::releaseAnonymousUploadSlot($cleanupData);

                return null;
            }

            $shareData = core_Cache::get(self::SHARE_FILES_CACHE_TYPE, $cleanupData->fileKey);
            if (is_object($shareData)) {
                if (empty($shareData->cleanupData) ||
                    !is_object($shareData->cleanupData) ||
                    ($shareData->cleanupData->cleanupId ?? null) !== $cleanupData->cleanupId ||
                    ($shareData->anonymousQuotaKey ?? null) !== $cleanupData->anonymousQuotaKey ||
                    ($shareData->anonymousQuotaId ?? null) !== $cleanupData->anonymousQuotaId) {

                    return null;
                }
            }

            $deleted = self::deleteUnclaimedSharedFiles($cleanupData->ownedFiles ?? array());
            core_Cache::remove(self::SHARE_FILES_CACHE_TYPE, $cleanupData->fileKey);
            self::releaseAnonymousUploadSlot($cleanupData);
        } finally {
            core_Locks::release($lockId);
        }

        return "Изчистени PWA share файлове: {$deleted}";
    }


    /**
     * Премахва точно планирания cleanup, включително ако е останал pending
     * след прекъснат cron. Claimed marker-ът пази от вече стартирал callback.
     *
     * @param stdClass $shareData
     *
     * @return bool
     */
    protected static function removeScheduledAnonymousCleanup($shareData)
    {
        if (!is_object($shareData) || empty($shareData->cleanupCallId) || empty($shareData->cleanupData)) {

            return false;
        }

        $callId = (int) $shareData->cleanupCallId;
        $callRec = core_CallOnTime::fetch($callId);
        $cleanupData = $shareData->cleanupData;
        if (!$callRec ||
            $callRec->className !== 'pwa_Share' ||
            $callRec->methodName !== 'CleanupAnonymousShare' ||
            !is_object($callRec->data) ||
            ($callRec->data->fileKey ?? null) !== ($cleanupData->fileKey ?? null) ||
            ($callRec->data->cleanupId ?? null) !== ($cleanupData->cleanupId ?? null)) {

            return false;
        }

        core_CallOnTime::delete($callId);

        return true;
    }


    /**
     * Изтрива непотърсени нови файлови записи, без да засяга deduplicated,
     * вече използвани или преместени файлове.
     *
     * @param array $ownedFiles
     *
     * @return int
     */
    protected static function deleteUnclaimedSharedFiles($ownedFiles)
    {
        $deleted = 0;
        $pwaBucketId = (int) fileman_Buckets::fetchByName('pwa');
        if (!$pwaBucketId) {

            return $deleted;
        }

        foreach ((array) $ownedFiles as $owned) {
            if (!is_object($owned) || empty($owned->id) || empty($owned->fileHnd) || empty($owned->dataId)) {
                continue;
            }

            $rec = fileman_Files::fetch((int) $owned->id);
            if (!$rec ||
                (int) $rec->bucketId !== $pwaBucketId ||
                (int) $rec->bucketId !== (int) ($owned->bucketId ?? 0) ||
                (int) $rec->dataId !== (int) $owned->dataId ||
                (string) $rec->fileHnd !== (string) $owned->fileHnd ||
                (string) $rec->name !== (string) ($owned->name ?? '') ||
                (string) ($rec->createdOn ?? '') !== (string) ($owned->createdOn ?? '') ||
                (int) ($rec->createdBy ?? 0) !== (int) ($owned->createdBy ?? 0)) {
                continue;
            }

            // Ако междувременно файлът е бил употребен по друг валиден път,
            // запазваме го и оставяме стандартната fileman логика да го следи.
            if (fileman_Log::fetch(array("#fileId = '[#1#]' OR #dataId = '[#2#]'", $rec->id, $rec->dataId)) ||
                doc_Files::fetch(array("#dataId = '[#1#]'", $rec->dataId)) ||
                fileman_Versions::fetch(array("#fileHnd = '[#1#]'", $rec->fileHnd))) {
                continue;
            }

            fileman_Download::deleteFileFromSbf($rec->id);
            if (fileman_Files::delete($rec->id)) {
                // Физическите data байтове се оставят на стандартния fileman
                // lifecycle. Директно unlink-ване тук има race с паралелен
                // absorb() на същото съдържание.
                cls::get('fileman_Data')->decreaseLinks($rec->dataId);
                $deleted++;
            }
        }

        return $deleted;
    }


    /**
     * Lock за четене и еднократно консумиране на файлов share
     *
     * @param string $fileKey
     *
     * @return string
     */
    protected static function getSharedFilesLockId($fileKey)
    {
        return 'pwaShareFiles|' . $fileKey;
    }


    /**
     * Проверява само синтаксиса преди да запази URL-а. DNS/IP проверката се
     * прави непосредствено преди свалянето след login.
     *
     * @param string $url
     *
     * @return bool
     */
    protected static function isRemoteShareUrl($url)
    {
        if (!is_string($url) || !$url || strlen($url) > 2048 ||
            preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {

            return false;
        }

        try {
            $parts = @parse_url($url);
        } catch (Throwable $t) {
            $parts = false;
        }
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host']) ||
            isset($parts['user']) || isset($parts['pass'])) {

            return false;
        }

        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, array('http', 'https'), true) ||
            preg_match('/[^\x21-\x7e]/', $parts['host']) || strpos($parts['host'], '%') !== false) {

            return false;
        }

        if (isset($parts['port'])) {
            $port = (int) $parts['port'];
            if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {

                return false;
            }
        }

        return true;
    }


    /**
     * Запазва URL без да прави мрежова заявка от публичния share-target.
     *
     * @param string $url
     * @param string $name
     * @param string $mode file|html
     * @param string|null $fallbackBody Оригиналният текст за бележка при отказ
     *
     * @return string
     */
    protected static function storeSharedRemoteUrl($url, $name, $mode, $fallbackBody = null)
    {
        expect(self::isRemoteShareUrl($url), 'Невалиден URL за PWA споделяне');
        expect(in_array($mode, array('file', 'html'), true), 'Невалиден режим за PWA URL');

        if (!is_string($fallbackBody) || !strlen($fallbackBody)) {
            $fallbackBody = $url;
        }

        $remoteKey = md5(str::getRand() . microtime(true) . $url);
        $remoteData = (object) array(
            'url' => $url,
            'name' => $name ? str::limitLen($name, 255) : tr('Споделен адрес'),
            'mode' => $mode,
            'fallbackBody' => $fallbackBody,
            'brid' => log_Browsers::getBrid(),
            'userId' => (int) core_Users::getCurrent(),
            'domainId' => (int) cms_Domains::getCurrent('id', false)
        );
        core_Cache::set(self::SHARE_REMOTE_CACHE_TYPE, $remoteKey, $remoteData, self::SHARE_REMOTE_KEEP_MINUTES);

        return $remoteKey;
    }


    /**
     * Атомарно взема еднократния URL след login и проверява binding-а.
     *
     * @param string $remoteKey
     *
     * @return stdClass
     */
    protected static function consumeSharedRemoteUrl($remoteKey)
    {
        expect(is_string($remoteKey) && preg_match('/^[a-f0-9]{32}$/D', $remoteKey), 'Невалиден PWA URL ключ');

        $lockId = self::SHARE_REMOTE_CACHE_TYPE . '|' . $remoteKey;
        expect(core_Locks::obtain($lockId, 30, 10, 10), 'Зает PWA URL ключ');

        try {
            $remoteData = core_Cache::get(self::SHARE_REMOTE_CACHE_TYPE, $remoteKey);
            expect(is_object($remoteData), 'Изтекъл или вече използван PWA URL ключ');
            expect(isset($remoteData->brid, $remoteData->domainId, $remoteData->url, $remoteData->mode),
                'Непълен PWA URL запис');
            expect((string) $remoteData->brid === (string) log_Browsers::getBrid(),
                'PWA URL-ът е от друго устройство');
            expect((int) $remoteData->domainId === (int) cms_Domains::getCurrent('id', false),
                'PWA URL-ът е от друг домейн');
            if (!empty($remoteData->userId)) {
                expect((int) $remoteData->userId === (int) core_Users::getCurrent(),
                    'PWA URL-ът е от друг потребител');
            }
            expect(self::isRemoteShareUrl($remoteData->url), 'Невалиден URL за PWA споделяне');
            expect(in_array($remoteData->mode, array('file', 'html'), true), 'Невалиден режим за PWA URL');

            core_Cache::remove(self::SHARE_REMOTE_CACHE_TYPE, $remoteKey);
        } finally {
            core_Locks::release($lockId);
        }

        return $remoteData;
    }


    /**
     * Запазва оригиналния URL/текст като еднократни данни за бележка.
     * Методът не прави мрежова заявка.
     *
     * @param string $body
     * @param string $subject
     *
     * @return string
     */
    protected static function storeSharedUrlAsText($body, $subject = '')
    {
        $subject = $subject ? str::limitLen($subject, 255) : tr('Споделен адрес');

        return self::storeSharedText($body, $subject);
    }


    /**
     * Показва безопасно съобщение, без URL или exception подробности.
     */
    protected static function showSharedUrlFallbackStatus()
    {
        status_Messages::newStatus(
            tr('Адресът не можа да бъде свален безопасно и е подготвен като текст за бележка.'),
            'warning'
        );
    }


    /**
     * Запазва временен текст за нова бележка, обвързан с browser/domain/user.
     *
     * @param string $body
     * @param string $subject
     *
     * @return string
     */
    protected static function storeSharedText($body, $subject)
    {
        expect(is_string($body) && strlen($body), 'Липсва текст за PWA споделяне');

        $key = md5(str::getRand() . $subject . $body);
        $textData = (object) array(
            'body' => $body,
            'subject' => $subject,
            'brid' => log_Browsers::getBrid(),
            'userId' => (int) core_Users::getCurrent(),
            'domainId' => (int) cms_Domains::getCurrent('id', false)
        );
        core_Cache::set(self::SHARE_TEXT_CACHE_TYPE, $key, $textData, 30);

        return $key;
    }


    /**
     * Атомарно взема временен текст за нова бележка
     *
     * @param string $key
     * @param bool   $isBackgroundFetch
     *
     * @return array|null
     */
    public static function consumeSharedText($key, $isBackgroundFetch = false)
    {
        if ($isBackgroundFetch || !is_string($key) || !preg_match('/^[a-f0-9]{32}$/D', $key)) {

            return null;
        }

        $lockId = 'pwaShareText|' . $key;
        if (!core_Locks::obtain($lockId, 30, 10, 10)) {

            return null;
        }

        $value = null;
        try {
            $cached = core_Cache::get(self::SHARE_TEXT_CACHE_TYPE, $key);
            if (is_object($cached)) {
                $isValid = isset($cached->brid, $cached->domainId) &&
                    (string) $cached->brid === (string) log_Browsers::getBrid() &&
                    (int) $cached->domainId === (int) cms_Domains::getCurrent('id', false) &&
                    (empty($cached->userId) || (int) $cached->userId === (int) core_Users::getCurrent());
                if ($isValid) {
                    $value = array(
                        'body' => $cached->body ?? null,
                        'subject' => $cached->subject ?? null
                    );
                }
            } elseif (is_array($cached)) {
                // Кратка съвместимост със записи, създадени от стар worker.
                $value = $cached;
            }

            if ($value !== null) {
                core_Cache::remove(self::SHARE_TEXT_CACHE_TYPE, $key);
            }
        } finally {
            core_Locks::release($lockId);
        }

        return $value;
    }


    /**
     * Сваля вече валидиран и еднократно консумиран URL след login.
     *
     * @param stdClass $remoteData
     *
     * @return stdClass fh|key
     */
    protected static function importSharedRemoteUrl($remoteData)
    {
        expect(haveRole('user'), 'URL може да се сваля само от логнат потребител');

        $download = pwa_SafeUrl::download($remoteData->url);
        try {
            $isHtml = in_array(strtolower((string) $download->mimeType), array('text/html', 'application/xhtml+xml'), true);
            if ($remoteData->mode === 'html' && $isHtml) {
                if ((int) $download->size > self::SHARE_REMOTE_HTML_MAX_BYTES) {
                    throw new RuntimeException('HTML съдържанието е по-голямо от разрешеното', pwa_SafeUrl::ERROR_SIZE);
                }

                $html = @file_get_contents($download->tmpPath);
                if ($html === false) {
                    throw new RuntimeException('HTML съдържанието не може да бъде прочетено', pwa_SafeUrl::ERROR_TEMP_FILE);
                }

                $body = html2text_Converter::toRichText($html);
                if (!is_string($body) || !strlen(trim($body))) {
                    $body = $remoteData->url;
                }

                return (object) array(
                    'key' => self::storeSharedText($body, $remoteData->name ?: tr('Споделен адрес'))
                );
            }

            $bucketId = fileman_Buckets::fetchByName('pwa');
            if (!$bucketId) {
                throw new RuntimeException('Липсва PWA кофа', pwa_SafeUrl::ERROR_TEMP_FILE);
            }

            $errors = array();
            if (!fileman_Buckets::isValid($errors, $bucketId, $download->fileName, $download->tmpPath, $download->size)) {
                throw new RuntimeException('Сваленият файл не отговаря на правилата на PWA кофата', pwa_SafeUrl::ERROR_SIZE);
            }

            $fh = fileman::absorb($download->tmpPath, 'pwa', $download->fileName);
            if (!$fh) {
                throw new RuntimeException('Сваленият файл не може да бъде добавен', pwa_SafeUrl::ERROR_TEMP_FILE);
            }

            return (object) array('fh' => $fh);
        } catch (RuntimeException $e) {
            if (in_array((int) $e->getCode(), array(pwa_SafeUrl::ERROR_SIZE, pwa_SafeUrl::ERROR_TEMP_FILE), true)) {
                throw $e;
            }

            throw new RuntimeException('Сваленото съдържание не можа да бъде запазено', pwa_SafeUrl::ERROR_TEMP_FILE, $e);
        } catch (Throwable $t) {
            throw new RuntimeException('Сваленото съдържание не можа да бъде запазено', pwa_SafeUrl::ERROR_TEMP_FILE, $t);
        } finally {
            pwa_SafeUrl::cleanup($download);
        }
    }


    /**
     * Превежда вътрешния тип на безопасното сваляне към публичен код.
     *
     * @param Throwable $exception
     *
     * @return string
     */
    protected static function getRemoteShareError($exception)
    {
        $type = pwa_SafeUrl::getErrorType($exception);
        if ($type === 'size') {

            return self::SHARE_ERROR_SIZE;
        }
        if (in_array($type, array('url', 'blocked', 'redirect', 'response'), true)) {

            return self::SHARE_ERROR_URL;
        }
        if ($type === 'temp') {

            return self::SHARE_ERROR_UPLOAD;
        }

        return self::SHARE_ERROR_NETWORK;
    }


    /**
     * Валидира и еднократно приема вече качените share-target файлове.
     * Изпълнява се преди remote URL fetch, за да няма частично импортиране.
     *
     * @param string $fileKey
     *
     * @return array
     */
    protected static function claimSharedFiles($fileKey)
    {
        expect(is_string($fileKey) && preg_match('/^[a-f0-9]{32}$/D', $fileKey), 'Невалиден PWA share ключ');
        $lockId = self::getSharedFilesLockId($fileKey);
        expect(core_Locks::obtain($lockId, 30, 10, 10), 'Зает PWA share ключ');

        $shareData = null;
        $claimMarkerSet = false;
        $fArr = array();
        try {
            $shareData = core_Cache::get(self::SHARE_FILES_CACHE_TYPE, $fileKey);
            expect(is_object($shareData), 'Изтекли или невалидни данни за PWA споделяне');
            expect((string) $shareData->brid === (string) log_Browsers::getBrid(), 'PWA споделянето е от друго устройство');
            expect((int) $shareData->domainId === (int) cms_Domains::getCurrent('id', false), 'PWA споделянето е от друг домейн');
            if (!empty($shareData->userId)) {
                expect((int) $shareData->userId === (int) core_Users::getCurrent(), 'PWA споделянето е от друг потребител');
            }

            $pwaBucketId = fileman_Buckets::fetchByName('pwa');
            expect($pwaBucketId, 'Липсва PWA кофата');
            foreach ((array) $shareData->files as $fh) {
                $fileRec = fileman_Files::fetchByFh($fh);
                expect($fileRec && (int) $fileRec->bucketId === (int) $pwaBucketId, 'Невалиден файл в PWA споделяне');
                $fArr[$fh] = $fh;
            }
            expect(count($fArr), 'Липсват файлове в PWA споделянето');

            if (!empty($shareData->cleanupData) && is_object($shareData->cleanupData)) {
                core_Cache::set(self::SHARE_FILES_CLAIMED_CACHE_TYPE, $fileKey, 1, 1440);
                $claimMarkerSet = true;
                if (!self::removeScheduledAnonymousCleanup($shareData)) {
                    // Съвместимост с cache записи от предишната версия.
                    core_CallOnTime::remove('pwa_Share', 'CleanupAnonymousShare', $shareData->cleanupData);
                }
            }

            core_Cache::remove(self::SHARE_FILES_CACHE_TYPE, $fileKey);
        } catch (Throwable $t) {
            if ($claimMarkerSet) {
                core_Cache::remove(self::SHARE_FILES_CLAIMED_CACHE_TYPE, $fileKey);
            }

            throw $t;
        } finally {
            core_Locks::release($lockId);
        }

        self::releaseAnonymousUploadSlot($shareData);
        if (empty($shareData->userId) && !empty($shareData->uploadDiagnostic)) {
            self::showShareUploadDebugStatus($shareData->uploadDiagnostic);
        }

        return $fArr;
    }


    /**
     * Екшън,който добавя файловете в последни
     */
    public function act_SaveTargetFiles()
    {
        $fileKey = Request::get('fileKey');
        $key = Request::get('key');
        $remoteKey = Request::get('remoteKey');

        if (!haveRole('user')) {
            
            return new Redirect(array('core_Users', 'login', 'ret_url' => array(
                'pwa_Share',
                'SaveTargetFiles',
                'fileKey' => $fileKey,
                'key' => $key,
                'remoteKey' => $remoteKey,
                'force' => true
            )));
        }

        $fArr = array();
        if ($fileKey) {
            try {
                $fArr = self::claimSharedFiles($fileKey);
            } catch (Throwable $t) {
                self::logWarning('Изтекъл или невалиден ключ за PWA файлове');

                return self::getShareErrorRedirect(self::SHARE_ERROR_TOKEN);
            }
        }

        if ($remoteKey) {
            $remoteData = null;
            try {
                // Cache binding-ът се проверява и ключът се консумира преди
                // мрежовата заявка, така refresh не може да свали два пъти.
                $remoteData = self::consumeSharedRemoteUrl($remoteKey);
            } catch (Throwable $t) {
                self::logWarning('Изтекъл или невалиден ключ за споделен URL');
                if (!$fileKey) {

                    return self::getShareErrorRedirect(self::SHARE_ERROR_TOKEN);
                }
                status_Messages::newStatus(tr(self::getShareErrorMessages()[self::SHARE_ERROR_TOKEN]), 'warning');
            }

            if ($remoteData) {
                try {
                    $remoteResult = self::importSharedRemoteUrl($remoteData);
                    if (!empty($remoteResult->fh)) {
                        $fArr[$remoteResult->fh] = $remoteResult->fh;
                    }
                    if (!empty($remoteResult->key)) {
                        $key = $remoteResult->key;
                    }
                } catch (Throwable $t) {
                    $error = self::getRemoteShareError($t);
                    if ($error === self::SHARE_ERROR_UPLOAD) {
                        reportException($t);
                    } else {
                        self::logWarning('Неуспешно безопасно сваляне на споделен URL');
                    }

                    $fallbackStored = false;
                    try {
                        $fallbackBody = isset($remoteData->fallbackBody) &&
                            is_string($remoteData->fallbackBody) && strlen($remoteData->fallbackBody)
                            ? $remoteData->fallbackBody
                            : $remoteData->url;
                        $key = self::storeSharedUrlAsText($fallbackBody, $remoteData->name ?? '');
                        $fallbackStored = true;
                        self::showSharedUrlFallbackStatus();
                    } catch (Throwable $fallbackError) {
                        reportException($fallbackError);
                    }

                    if (!$fallbackStored) {
                        if (!$fileKey) {

                            return self::getShareErrorRedirect($error);
                        }
                        status_Messages::newStatus(tr(self::getShareErrorMessages()[$error]), 'warning');
                    }
                }
            }
        }

        if (Request::get('force') && $fArr) {
            foreach ($fArr as $fh) {
                fileman_Log::updateLogInfo($fh, 'upload');
            }
        }

        $hasTextKey = is_string($key) && preg_match('/^[a-f0-9]{32}$/D', $key);
        if ($hasTextKey || !$fArr) {
            $defFolder = doc_Folders::getDefaultFolder(core_Users::getCurrent());

            return new Redirect(array('doc_Notes', 'add', 'folderId' => $defFolder, 'key' => $key));
        }
        
        if (haveRole('powerUser')) {
            
            return new Redirect(array('doc_Files'));
        } else {
            
            return new Redirect(array('Index'));
        }
    }
    
    
    /**
     * Помощен екшън за редирект към портала
     * 
     * @return Redirect
     */
    function act_Portal()
    {
        $v = Request::get('v');
        if ($v) {
            $v = str::checkHash($v);
        }
        
        if (!$v) {
            wp(Request::get('v'));
        }
        
        Mode::setPermanent('isPWA', true);
        
        return new Redirect(array('Portal', 'Show'));
    }
}
