<?php


/**
 * OCR обработка на файлове с olmOCR-2-7B през Doubleword.ai
 *
 * @category  vendors
 * @package   doubleword
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doubleword_Converter extends core_Manager
{
    /**
     * Максимален размер на дългата страна, препоръчан за olmOCR-2-7B
     */
    const PAGE_MAX_SIZE = 1288;


    /**
     * Максимален брой едновременни заявки към API
     */
    const MAX_PARALLEL_REQUESTS = 4;


    /**
     * Време за свързване и изпълнение на една API заявка
     */
    const CONNECT_TIMEOUT = 20;
    const REQUEST_TIMEOUT = 600;
    const PATH_REQUEST_TIMEOUT = 600;


    /**
     * Повторения при временна API грешка и при корекция на ротацията
     */
    const MAX_API_RETRIES = 0;
    const MAX_PATH_API_RETRIES = 0;
    const MAX_ROTATION_RETRIES = 2;
    const RETRY_BASE_DELAY = 500000;


    /**
     * Максимален брой пиксели във входно изображение
     */
    const MAX_SOURCE_PIXELS = 25000000;
    const MAX_IMAGE_BYTES = 67108864;


    /**
     * Архивните драйвери подават временен път и чакат синхронен резултат
     */
    const MAX_PATH_PDF_PAGES = 4;


    /**
     * Интерфейсни методи
     */
    public $interfaces = 'fileman_OCRIntf, fileman_FileActionsIntf';


    /**
     * Заглавие
     */
    public $title = 'Doubleword.ai olmOCR';


    /**
     * Кои потребители имат права за OCR на документ
     */
    public static $canOCR = 'powerUser';
    public $canOcr = 'powerUser';


    /**
     * Позволени разширения
     */
    public static $allowedExt = array('pdf', 'bmp', 'jpeg', 'jpg', 'gif', 'png');


    /**
     * Пътят до програмата за преобразуване на PDF страници
     */
    public array $fconvProgramPaths = array('pdftoppm' => 'doubleword_Setup::DOUBLEWORD_PDFTOPPM_PATH');


    /**
     * Команда за преобразуване на PDF страници до препоръчания от модела размер
     */
    public $fconvLineExec = 'timeout --signal=TERM --kill-after=10s [#PDF_TIMEOUT#] [#PDFTOPPM#] -png -f [#FIRST_PAGE#] -l [#LAST_PAGE#] -r 150 -scale-to 1288 [#INPUTF#] [#OUTPUT_DIR#]/page 2> [#ERROR_FILE#] && touch [#SUCCESS_FILE#]';


    /**
     * Интерфейсен метод на fileman_FileActionsIntf
     *
     * @param stdClass $fRec
     *
     * @return array|null
     */
    public static function getActionsForFile_($fRec)
    {
        $arr = null;

        if (self::haveRightFor('ocr') && self::canExtract($fRec)) {
            $btnParams = array();
            $btnParams['order'] = 81;
            $btnParams['title'] = 'Разпознаване на текст с Doubleword.ai olmOCR-2-7B';

            $procTextOcr = fileman_Indexes::isProcessStarted(array(
                'type' => 'textOcr',
                'dataId' => $fRec->dataId,
            ));
            if ($procTextOcr) {
                $btnParams['warning'] = 'Doubleword: Файлът е преминал през разпознаване на текст';
            } elseif (!self::haveTextForOcr($fRec)) {
                $btnParams['warning'] = 'Няма текст за разпознаване';
            }

            $arr = array();
            $arr['doubleword']['url'] = array(get_called_class(), 'getTextByOcr', $fRec->fileHnd, 'ret_url' => true);
            $arr['doubleword']['title'] = 'Doubleword olmOCR';
            $arr['doubleword']['icon'] = 'img/16/scanner2.png';
            $arr['doubleword']['btnParams'] = $btnParams;
        }

        return $arr;
    }


    /**
     * Екшън за извличане на текст чрез OCR
     *
     * @see fileman_OCRIntf
     */
    public function act_getTextByOcr()
    {
        $this->requireRightFor('ocr');

        $fh = Request::get('id');
        $fRec = fileman_Files::fetchByFh($fh);

        expect($fRec, 'Файлът за OCR не може да бъде намерен');
        expect(static::canExtract($fRec), 'Файловият формат не се поддържа за OCR');

        fileman_Files::requireRightFor('single', $fRec);
        try {
            $text = $this->getTextByOcr($fRec);
            if ($text !== null) {
                if ($fRec->dataId && ($dRec = fileman_Data::fetch((int) $fRec->dataId))) {
                    fileman_Data::resetProcess($dRec);
                }

                status_Messages::newStatus('|Текстът е извлечен успешно с Doubleword.ai OCR', 'success');
            }
        } catch (Throwable $e) {
            status_Messages::newStatus(
                '|Грешка при OCR обработката|*: ' . static::getExceptionMessage($e),
                'error'
            );
        }

        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $retUrl = array('fileman_Files', 'single', $fRec->fileHnd);
        }

        return new Redirect($retUrl);
    }


    /**
     * Стартира OCR обработката
     *
     * @param stdClass|string $fRec
     *
     * @return string|null
     *
     * @see fileman_OCRIntf
     */
    public function getTextByOcr($fRec)
    {
        $isFileRec = is_object($fRec);
        $params = array(
            'callBack' => get_called_class() . '::afterGetTextByDoubleword',
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'textOcr',
            'isPath' => !$isFileRec,
            'asynch' => $isFileRec,
        );

        if ($isFileRec) {
            $params['dataId'] = $fRec->dataId;
            $params['fileHnd'] = $fRec->fileHnd;
            $file = $fRec->fileHnd;
        } else {
            $params['fileHnd'] = $fRec;
            if (!is_file($fRec) || !is_readable($fRec)) {
                static::registerError($params, 'Файлът за OCR не е достъпен');

                return null;
            }

            $file = $fRec;
        }

        $lId = fileman_webdrv_Generic::prepareLockId($fRec);
        if (!$lId) {
            static::registerError($params, 'Не може да се определи идентификатор за OCR обработката');

            return null;
        }

        $params['lockId'] = fileman_webdrv_Generic::getLockId($params['type'], $lId);
        if (core_Locks::isLocked($params['lockId'])) {
            if ($isFileRec) {
                status_Messages::newStatus('|В момента се прави тази OCR обработка');

                return null;
            }
        }

        $lockDuration = static::getProcessTimeLimit(!$isFileRec, $file);
        $maxTries = $isFileRec ? 0 : 60;
        $waitTimeout = $isFileRec ? 0 : 60;
        if (!core_Locks::obtain(
            $params['lockId'],
            $lockDuration,
            $maxTries,
            $waitTimeout,
            !$params['asynch']
        )) {
            static::registerError($params, 'Друга OCR обработка на същия файл още не е завършила');

            return null;
        }

        if ($isFileRec) {
            fileman_Data::logWrite('OCR обработка на файл с Doubleword.ai', $fRec->dataId);
            fileman_Files::logWrite('OCR обработка на файл с Doubleword.ai', $fRec->id);

            try {
                $Script = cls::get('fconv_Script');
                $Script->stopRemote = true;
                $Script->params = $params;
                $Script->callBack($params['callBack']);
                if ($Script->run(true, $lockDuration) === false) {
                    expect(false, 'Асинхронната OCR обработка не може да бъде стартирана');
                }
            } catch (Throwable $e) {
                static::registerError($params, static::getExceptionMessage($e));
                core_Locks::release($params['lockId']);

                throw $e;
            }

            status_Messages::newStatus('|Стартирано е извличането на текст с OCR', 'success');

            return null;
        }

        try {
            return static::getText($file, $params);
        } catch (Throwable $e) {
            static::registerError($params, static::getExceptionMessage($e));

            throw $e;
        } finally {
            core_Locks::release($params['lockId']);
        }
    }


    /**
     * Взема текстовата част от подадения файл
     *
     * @param string $fileHnd
     * @param array  $params
     *
     * @return string
     */
    public static function getText($fileHnd, $params = array())
    {
        setIfNot($params['isPath'], is_file($fileHnd));
        core_App::setTimeLimit(static::getProcessTimeLimit(!empty($params['isPath']), $fileHnd));

        if ($params['isPath']) {
            expect(is_file($fileHnd) && is_readable($fileHnd), 'Файлът за OCR не е достъпен');
            expect(static::canExtract($fileHnd), 'Файловият формат не се поддържа за OCR');
            $name = basename($fileHnd);
        } else {
            $fRec = fileman_Files::fetchByFh($fileHnd);
            expect($fRec, 'Файлът за OCR не може да бъде намерен');
            expect(static::canExtract($fRec), 'Файловият формат не се поддържа за OCR');
            $name = $fRec->name;
        }

        $requestTimeout = !empty($params['isPath']) ? self::PATH_REQUEST_TIMEOUT : self::REQUEST_TIMEOUT;
        $maxApiRetries = !empty($params['isPath']) ? self::MAX_PATH_API_RETRIES : self::MAX_API_RETRIES;

        $dataUris = static::getPageDataUris($fileHnd, $params);
        $parts = array();
        foreach (array_chunk($dataUris, self::MAX_PARALLEL_REQUESTS, true) as $chunk) {
            $parts += static::requestDataUris($chunk, $requestTimeout, $maxApiRetries);
        }

        ksort($parts, SORT_NUMERIC);
        $res = implode("\n\n", $parts);

        $res = trim((string) $res);
        expect(strlen($res), 'Doubleword.ai не върна разпознат текст');

        if (!empty($params['dataId'])) {
            $params['content'] = $res;
            $savedId = fileman_Indexes::saveContent($params);
            expect($savedId, 'Разпознатият текст не може да бъде записан');
            fileman_Data::logInfo('Завършена OCR обработка с Doubleword.ai (' . strlen($res) . ' байта)', $params['dataId']);
        }

        return $res;
    }


    /**
     * Изпълнява асинхронната OCR обработка след стартиране от fconv
     *
     * @param fconv_Script $script
     *
     * @return bool
     */
    public function afterGetTextByDoubleword($script)
    {
        core_App::flushAndClose(false);

        $params = (isset($script->params) && is_array($script->params)) ? $script->params : array();

        try {
            try {
                expect(!empty($params['fileHnd']), 'Липсва файл за асинхронната OCR обработка');
                static::getText($params['fileHnd'], $params);
            } catch (Throwable $e) {
                static::registerError($params, static::getExceptionMessage($e));
            }

            if (!empty($params['dataId'])) {
                try {
                    fileman_Data::resetProcess((int) $params['dataId']);
                } catch (Throwable $e) {
                    fileman_Data::logWarning(
                        'Doubleword OCR: обработката на файла не може да бъде нулирана',
                        (int) $params['dataId']
                    );
                }
            }
        } finally {
            if (!empty($params['lockId'])) {
                core_Locks::release($params['lockId']);
            }
        }

        return true;
    }


    /**
     * Преобразува PDF в страници и ги подава към OCR
     *
     * @param string $fileHnd
     * @param array  $params
     *
     * @return array
     */
    protected static function getDataUrisFromPdf($fileHnd, $params, $onlyPage = null)
    {
        $maxPages = static::getMaxPdfPages(!empty($params['isPath']));
        if ($onlyPage !== null) {
            $onlyPage = (int) $onlyPage;
            expect($onlyPage >= 1 && $onlyPage <= $maxPages, 'Невалиден номер на PDF страница');
            $firstPage = $onlyPage;
            $lastPage = $onlyPage;
        } else {
            $firstPage = 1;
            $lastPage = $maxPages + 1;
        }

        $Script = cls::get('fconv_Script');
        $Script->setFolders('OUTPUT_DIR', 'pages');
        $Script->setFile('INPUTF', $fileHnd, true);
        $Script->setProgram('timeout', 'timeout');
        $Script->setProgram('pdftoppm', doubleword_Setup::get('PDFTOPPM_PATH'));
        $Script->setProgramPath(get_called_class(), 'fconvProgramPaths');
        $Script->setParam('PDFTOPPM', doubleword_Setup::get('PDFTOPPM_PATH'), true);
        $pdfTimeout = max(30, min(1800, (int) doubleword_Setup::get('PDF_RENDER_TIMEOUT')));
        $Script->setParam('PDF_TIMEOUT', $pdfTimeout, true);
        $Script->setParam('FIRST_PAGE', $firstPage, true);
        $Script->setParam('LAST_PAGE', $lastPage, true);

        $successFilePath = $Script->tempDir . 'pdftoppm.success';
        $Script->setParam('SUCCESS_FILE', $successFilePath, true);

        $errFilePath = fileman_webdrv_Generic::getErrLogFilePath($Script->tempDir . 'pdftoppm');
        $Script->setParam('ERROR_FILE', $errFilePath, true);
        $Script->lineExec(get_called_class() . '::fconvLineExec');
        $Script->setCheckProgramsArr('timeout,pdftoppm');
        $Script->params = $params;

        try {
            if ($Script->run(false, $pdfTimeout) === false) {
                expect(false, 'Програмата pdftoppm не може да бъде стартирана');
            }

            $pagePaths = static::getRenderedPagePaths($Script->tempDir . 'pages');
            if (!is_file($successFilePath)) {
                $err = is_file($errFilePath) ? trim((string) @file_get_contents($errFilePath)) : '';
                $err = $err ? ': ' . static::limitError($err) : '';
                expect(false, 'PDF файлът не може да бъде преобразуван в изображения' . $err);
            }
            expect(!empty($pagePaths), 'PDF файлът не съдържа страници за разпознаване');
            if ($onlyPage !== null) {
                expect(count($pagePaths) == 1 && isset($pagePaths[$onlyPage]),
                    "PDF страница {$onlyPage} не може да бъде преобразувана");
            } else {
                expect(count($pagePaths) <= $maxPages,
                    "PDF файлът надвишава разрешените {$maxPages} страници за Doubleword OCR");
            }

            $dataUris = array();
            foreach ($pagePaths as $pageNo => $path) {
                $content = (string) @file_get_contents($path);
                expect(strlen($content), "Преобразуваната PDF страница {$pageNo} не може да бъде прочетена");
                $dataUris[$pageNo] = static::imageToDataUri($content);
            }

            return $dataUris;
        } finally {
            core_Os::deleteDir($Script->tempDir);
            fconv_Processes::delete(array("#processId = '[#1#]'", $Script->id));
        }
    }


    /**
     * Подготвя една или всички страници като PNG data URI
     *
     * @param string   $fileHnd
     * @param array    $params
     * @param int|null $onlyPage
     * @param int      $rotation
     *
     * @return array
     */
    protected static function getPageDataUris($fileHnd, $params, $onlyPage = null, $rotation = 0)
    {
        if (!empty($params['isPath'])) {
            $name = basename($fileHnd);
        } else {
            $fRec = fileman_Files::fetchByFh($fileHnd);
            expect($fRec, 'Файлът за OCR не може да бъде намерен');
            $name = $fRec->name;
        }

        $ext = strtolower(fileman_Files::getExt($name));
        if ($ext == 'pdf') {
            $dataUris = static::getDataUrisFromPdf($fileHnd, $params, $onlyPage);
        } else {
            expect($onlyPage === null || (int) $onlyPage === 1, 'Невалиден номер на страница за изображение');
            $content = static::getInputContent($fileHnd, !empty($params['isPath']));
            $dataUris = array(1 => static::imageToDataUri($content));
        }

        $rotation = ((int) $rotation % 360 + 360) % 360;
        if ($rotation) {
            expect(in_array($rotation, array(90, 180, 270), true), 'Невалидна ротация на OCR страница');
            foreach ($dataUris as $pageNo => $dataUri) {
                $dataUris[$pageNo] = static::rotateDataUri($dataUri, $rotation);
            }
        }

        return $dataUris;
    }


    /**
     * Връща безопасния лимит за страници на PDF
     *
     * @param bool $isPath При временен файл от архив лимитът е по-строг
     *
     * @return int
     */
    protected static function getMaxPdfPages($isPath = false)
    {
        $maxPages = (int) doubleword_Setup::get('MAX_PDF_PAGES');
        $maxPages = max(1, min(20, $maxPages));

        if ($isPath) {
            $maxPages = min($maxPages, self::MAX_PATH_PDF_PAGES);
        }

        return $maxPages;
    }


    /**
     * Максимално време за процеса и неговия lock
     *
     * @param bool        $isPath
     * @param string|null $fileHnd
     *
     * @return int
     */
    protected static function getProcessTimeLimit($isPath = false, $fileHnd = null)
    {
        $pages = static::getMaxPdfPages($isPath);
        if ($fileHnd !== null) {
            if ($isPath) {
                $name = $fileHnd;
            } else {
                $fRec = fileman_Files::fetchByFh($fileHnd);
                $name = $fRec ? $fRec->name : '';
            }
            if (strtolower(fileman_Files::getExt($name)) != 'pdf') {
                $pages = 1;
            }
        }
        $batches = (int) ceil($pages / self::MAX_PARALLEL_REQUESTS);
        $requestTimeout = $isPath ? self::PATH_REQUEST_TIMEOUT : self::REQUEST_TIMEOUT;
        $apiRetries = $isPath ? self::MAX_PATH_API_RETRIES : self::MAX_API_RETRIES;
        $apiAttempts = $apiRetries + 1;
        $rotationAttempts = self::MAX_ROTATION_RETRIES + 1;
        $renderTimeout = max(30, min(1800, (int) doubleword_Setup::get('PDF_RENDER_TIMEOUT')));

        return max(
            1800,
            ($batches * $requestTimeout * $apiAttempts * $rotationAttempts) + $renderTimeout + 300
        );
    }


    /**
     * Връща сортираните изображения, генерирани от pdftoppm
     *
     * @param string $dir
     *
     * @return array
     */
    protected static function getRenderedPagePaths($dir)
    {
        $res = array();
        if (!is_dir($dir) || !is_readable($dir)) {

            return $res;
        }

        foreach ((array) scandir($dir) as $file) {
            if (preg_match('/^page\-([0-9]+)\.png$/i', $file, $matches)) {
                $res[(int) $matches[1]] = rtrim($dir, '/') . '/' . $file;
            }
        }
        ksort($res, SORT_NUMERIC);

        return $res;
    }


    /**
     * Чете входното изображение
     *
     * @param string $fileHnd
     * @param bool   $isPath
     *
     * @return string
     */
    protected static function getInputContent($fileHnd, $isPath)
    {
        if ($isPath) {
            $fileLen = @filesize($fileHnd);
            expect($fileLen !== false && $fileLen > 0, 'Изображението за OCR не може да бъде прочетено');
            expect($fileLen <= self::MAX_IMAGE_BYTES, 'Изображението за OCR е прекалено голямо');
            $content = @file_get_contents($fileHnd);
        } else {
            $fRec = fileman_Files::fetchByFh($fileHnd);
            expect($fRec && !empty($fRec->dataId), 'Изображението за OCR не може да бъде намерено');
            $fileLen = fileman_Data::fetchField((int) $fRec->dataId, 'fileLen');
            expect($fileLen && $fileLen <= self::MAX_IMAGE_BYTES,
                'Изображението за OCR е прекалено голямо');
            $content = fileman::extractStr($fileHnd);
        }

        expect(is_string($content) && strlen($content), 'Изображението за OCR не може да бъде прочетено');
        expect(strlen($content) <= self::MAX_IMAGE_BYTES, 'Изображението за OCR е прекалено голямо');

        return $content;
    }


    /**
     * Нормализира изображение до PNG с максимална страна 1288 px
     *
     * @param string $content
     *
     * @return string
     */
    protected static function imageToDataUri($content)
    {
        expect(function_exists('imagecreatefromstring'), 'PHP разширението GD не е инсталирано');

        $size = @getimagesizefromstring($content);
        expect($size && !empty($size[0]) && !empty($size[1]), 'Неподдържан или повреден формат на изображението');

        $width = (int) $size[0];
        $height = (int) $size[1];
        expect(((float) $width * $height) <= self::MAX_SOURCE_PIXELS, 'Изображението е с прекалено големи размери');

        $source = @imagecreatefromstring($content);
        expect($source, 'Изображението не може да бъде декодирано');

        list($newWidth, $newHeight) = thumb_Img::scaleSize(
            $width,
            $height,
            self::PAGE_MAX_SIZE,
            self::PAGE_MAX_SIZE,
            'small-fit'
        );

        $newWidth = (int) $newWidth;
        $newHeight = (int) $newHeight;

        $target = imagecreatetruecolor($newWidth, $newHeight);
        if (!$target) {
            imagedestroy($source);
            expect(false, 'Не може да бъде създадено изображение за OCR');
        }

        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);
        imagealphablending($target, true);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        $saved = imagepng($target, null, 6);
        $png = ob_get_clean();

        imagedestroy($target);
        imagedestroy($source);

        expect($saved && is_string($png) && strlen($png), 'Изображението не може да бъде преобразувано в PNG');

        return 'data:image/png;base64,' . base64_encode($png);
    }


    /**
     * Валидира задължителния YAML front matter на olmOCR
     *
     * Празният текст след YAML е валиден за отделна празна страница.
     *
     * @param string   $content
     * @param int      $pageNo
     * @param int|null $rotationCorrection
     *
     * @return string
     */
    protected static function parseOcrContent($content, $pageNo, &$rotationCorrection)
    {
        $rotationCorrection = null;
        $metadata = array();
        $text = static::stripFrontMatter($content, $metadata);
        $required = array(
            'primary_language',
            'is_rotation_valid',
            'rotation_correction',
            'is_table',
            'is_diagram',
        );
        $missing = array_diff($required, array_keys($metadata));
        expect(empty($missing),
            "Doubleword.ai не върна пълна metadata за страница {$pageNo}");

        $isRotationValid = static::parseBoolean($metadata['is_rotation_valid']);
        $isTable = static::parseBoolean($metadata['is_table']);
        $isDiagram = static::parseBoolean($metadata['is_diagram']);
        expect($isRotationValid !== null && $isTable !== null && $isDiagram !== null,
            "Doubleword.ai върна невалидни boolean стойности за страница {$pageNo}");

        $rawCorrection = trim((string) $metadata['rotation_correction'], " \t\n\r\0\x0B\"'");
        expect(preg_match('/^(?:0|90|180|270)$/D', $rawCorrection),
            "Doubleword.ai върна невалидна корекция за ориентацията на страница {$pageNo}");
        $correction = (int) $rawCorrection;

        if ($isRotationValid) {
            expect($correction === 0,
                "Doubleword.ai върна противоречива metadata за ориентацията на страница {$pageNo}");
        } else {
            expect(in_array($correction, array(90, 180, 270), true),
                "Doubleword.ai не върна корекция за ориентацията на страница {$pageNo}");
            $rotationCorrection = $correction;
        }

        return $text;
    }


    /**
     * Изпраща една група страници едновременно към Doubleword.ai
     *
     * @param array $dataUris
     * @param int   $requestTimeout
     * @param int   $maxApiRetries
     * @param int   $apiAttempt
     * @param int   $rotationAttempt
     *
     * @return array
     */
    protected static function requestDataUris(
        $dataUris,
        $requestTimeout = self::REQUEST_TIMEOUT,
        $maxApiRetries = self::MAX_API_RETRIES,
        $apiAttempt = 0,
        $rotationAttempt = 0
    )
    {
        expect(function_exists('curl_multi_init'), 'PHP разширението cURL не е инсталирано');

        $multi = curl_multi_init();
        expect($multi, 'Не може да бъде стартирана връзка към Doubleword.ai');

        $handles = array();
        $res = array();
        $retryUris = array();
        $rotationUris = array();
        try {
            foreach ($dataUris as $pageNo => $dataUri) {
                $handle = static::createCurlHandle($dataUri, $requestTimeout);
                $handles[$pageNo] = $handle;
                curl_multi_add_handle($multi, $handle);
            }

            do {
                $multiStatus = curl_multi_exec($multi, $active);
                if ($active && $multiStatus == CURLM_OK) {
                    $ready = curl_multi_select($multi, 1.0);
                    if ($ready === -1) {
                        usleep(100000);
                    }
                }
            } while ($active && $multiStatus == CURLM_OK);

            expect($multiStatus == CURLM_OK, 'Грешка при изпълнение на заявките към Doubleword.ai');

            foreach ($handles as $pageNo => $handle) {
                $body = curl_multi_getcontent($handle);
                $httpCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
                $curlError = curl_error($handle);

                if (static::isRetryableApiFailure($httpCode, $curlError) &&
                    ($apiAttempt < $maxApiRetries)) {
                    $retryUris[$pageNo] = $dataUris[$pageNo];
                    continue;
                }

                $rotationCorrection = null;
                $content = static::decodeApiResponse(
                    $body,
                    $httpCode,
                    $curlError,
                    $pageNo,
                    $rotationCorrection
                );

                if ($rotationCorrection !== null) {
                    expect($rotationAttempt < self::MAX_ROTATION_RETRIES,
                        "Doubleword.ai не успя да определи ориентацията на страница {$pageNo}");
                    $rotationUris[$pageNo] = static::rotateDataUri(
                        $dataUris[$pageNo],
                        $rotationCorrection
                    );
                } else {
                    $res[$pageNo] = $content;
                }
            }
        } finally {
            foreach ($handles as $handle) {
                curl_multi_remove_handle($multi, $handle);
                curl_close($handle);
            }
            curl_multi_close($multi);
        }

        if (!empty($retryUris)) {
            $delay = self::RETRY_BASE_DELAY * (1 << $apiAttempt);
            usleep($delay);
            $res = array_replace(
                $res,
                static::requestDataUris(
                    $retryUris,
                    $requestTimeout,
                    $maxApiRetries,
                    $apiAttempt + 1,
                    $rotationAttempt
                )
            );
        }

        if (!empty($rotationUris)) {
            $res = array_replace(
                $res,
                static::requestDataUris(
                    $rotationUris,
                    $requestTimeout,
                    $maxApiRetries,
                    0,
                    $rotationAttempt + 1
                )
            );
        }

        ksort($res, SORT_NUMERIC);

        return $res;
    }


    /**
     * Дали заявката може безопасно да се повтори след кратко изчакване
     *
     * @param int    $httpCode
     * @param string $curlError
     *
     * @return bool
     */
    protected static function isRetryableApiFailure($httpCode, $curlError)
    {
        if (strlen((string) $curlError)) {

            return true;
        }

        return in_array((int) $httpCode, array(408, 425, 429, 500, 502, 503, 504), true);
    }


    /**
     * Подготвя cURL заявка за една страница
     *
     * @param string $dataUri
     * @param int    $requestTimeout
     *
     * @return resource|CurlHandle
     */
    protected static function createCurlHandle($dataUri, $requestTimeout = self::REQUEST_TIMEOUT)
    {
        $apiKey = trim((string) doubleword_Setup::get('API_KEY'));
        expect(strlen($apiKey), 'Не е зададен API ключ за Doubleword.ai');

        $payload = array(
            'model' => doubleword_Setup::get('OCR_MODEL'),
            'service_tier' => 'flex',
            'max_tokens' => 8000,
            'temperature' => 0.1,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => array(
                        array('type' => 'text', 'text' => static::getOcrPrompt()),
                        array(
                            'type' => 'image_url',
                            'image_url' => array('url' => $dataUri),
                        ),
                    ),
                ),
            ),
        );

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        expect(is_string($json), 'Не може да бъде създадена JSON заявка към Doubleword.ai');

        $curl = curl_init(doubleword_Setup::get('API_URL'));
        expect($curl, 'Не може да бъде стартирана връзка към Doubleword.ai');

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ),
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => max(30, (int) $requestTimeout),
            CURLOPT_NOSIGNAL => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => 'bgERP Doubleword OCR/0.1',
        ));

        return $curl;
    }


    /**
     * Извлича текста от OpenAI-съвместим Chat Completions отговор
     *
     * @param string $body
     * @param int    $httpCode
     * @param string $curlError
     * @param int    $pageNo
     * @param int|null $rotationCorrection
     *
     * @return string
     */
    protected static function decodeApiResponse($body, $httpCode, $curlError, $pageNo, &$rotationCorrection = null)
    {
        $rotationCorrection = null;
        expect(!$curlError, "Doubleword.ai: грешка при страница {$pageNo}: " . static::limitError($curlError));

        $decoded = json_decode((string) $body);
        if ($httpCode < 200 || $httpCode >= 300) {
            $message = static::getApiError($decoded);
            expect(false, "Doubleword.ai HTTP {$httpCode} при страница {$pageNo}: {$message}");
        }

        expect(json_last_error() == JSON_ERROR_NONE && is_object($decoded),
            "Doubleword.ai върна невалиден JSON за страница {$pageNo}");

        if (!empty($decoded->error)) {
            expect(false, "Doubleword.ai при страница {$pageNo}: " . static::getApiError($decoded));
        }

        expect(!empty($decoded->choices[0]->message),
            "Doubleword.ai върна неочакван отговор за страница {$pageNo}");

        $finishReason = $decoded->choices[0]->finish_reason ?? null;
        expect($finishReason === 'stop',
            "Doubleword.ai върна незавършен отговор за страница {$pageNo}");

        $content = $decoded->choices[0]->message->content;
        if (is_array($content)) {
            $text = '';
            foreach ($content as $part) {
                if (is_object($part) && isset($part->text)) {
                    $text .= $part->text;
                }
            }
            $content = $text;
        }

        expect(is_string($content) && strlen(trim($content)),
            "Doubleword.ai не върна текст за страница {$pageNo}");

        return static::parseOcrContent($content, $pageNo, $rotationCorrection);
    }


    /**
     * Премахва служебния YAML front matter и връща стойностите му
     *
     * @param string $content
     * @param array  $metadata
     *
     * @return string
     */
    protected static function stripFrontMatter($content, &$metadata)
    {
        $metadata = array();
        $content = preg_replace('/^\xEF\xBB\xBF/', '', (string) $content);

        if (!preg_match('/\A\s*---[ \t]*\R(.*?)\R---[ \t]*(?:\R|\z)/s', $content, $matches)) {

            return trim($content);
        }

        foreach (preg_split('/\R/', $matches[1]) as $line) {
            if (preg_match('/^\s*([a-z_]+)\s*:\s*(.*?)\s*$/i', $line, $parts)) {
                $metadata[strtolower($parts[1])] = trim($parts[2]);
            }
        }

        $knownKeys = array('primary_language', 'is_rotation_valid', 'rotation_correction', 'is_table', 'is_diagram');
        if (!array_intersect($knownKeys, array_keys($metadata))) {
            $metadata = array();

            return trim($content);
        }

        return trim(substr($content, strlen($matches[0])));
    }


    /**
     * Преобразува YAML булева стойност
     *
     * @param mixed $value
     *
     * @return bool|null
     */
    protected static function parseBoolean($value)
    {
        $value = strtolower(trim((string) $value, " \t\n\r\0\x0B\"'"));
        if (in_array($value, array('true', 'yes', '1'), true)) {

            return true;
        }
        if (in_array($value, array('false', 'no', '0'), true)) {

            return false;
        }

        return null;
    }


    /**
     * Завърта PNG data URI обратно според указанието на модела
     *
     * @param string $dataUri
     * @param int    $degrees
     *
     * @return string
     */
    protected static function rotateDataUri($dataUri, $degrees)
    {
        $prefix = 'data:image/png;base64,';
        expect(strpos($dataUri, $prefix) === 0, 'Невалидно изображение за корекция на ориентацията');

        $png = base64_decode(substr($dataUri, strlen($prefix)), true);
        expect(is_string($png) && strlen($png), 'Изображението за ротация не може да бъде прочетено');

        $source = @imagecreatefromstring($png);
        expect($source, 'Изображението за ротация не може да бъде декодирано');

        $white = imagecolorallocate($source, 255, 255, 255);
        $rotated = @imagerotate($source, (float) $degrees, $white);
        imagedestroy($source);
        expect($rotated, 'Изображението не може да бъде завъртяно');

        ob_start();
        $saved = imagepng($rotated, null, 6);
        $rotatedPng = ob_get_clean();
        imagedestroy($rotated);

        expect($saved && is_string($rotatedPng) && strlen($rotatedPng),
            'Завъртяното изображение не може да бъде записано');

        return $prefix . base64_encode($rotatedPng);
    }


    /**
     * Връща съобщението за API грешка без чувствителни данни
     *
     * @param mixed $decoded
     *
     * @return string
     */
    protected static function getApiError($decoded)
    {
        $message = 'неуточнена API грешка';
        if (is_object($decoded) && isset($decoded->error)) {
            if (is_object($decoded->error) && isset($decoded->error->message)) {
                $message = $decoded->error->message;
            } elseif (is_string($decoded->error)) {
                $message = $decoded->error;
            }
        } elseif (is_object($decoded) && isset($decoded->message)) {
            $message = $decoded->message;
        }

        return static::limitError($message);
    }


    /**
     * Ограничава дължината на съобщение за грешка
     *
     * @param string $message
     *
     * @return string
     */
    protected static function limitError($message)
    {
        $message = trim(strip_tags((string) $message));

        return mb_substr($message, 0, 500);
    }


    /**
     * Връща описателното съобщение от възникнала грешка
     *
     * @param Throwable $e
     *
     * @return string
     */
    protected static function getExceptionMessage($e)
    {
        $message = $e->getMessage();
        if (($e instanceof core_exception_Expect) && $message === '500 Грешка в сървъра') {
            $dump = $e->getDump();
            if (is_array($dump) && isset($dump[0]) && is_string($dump[0]) && strlen(trim($dump[0]))) {
                $message = $dump[0];
            }
        }

        return static::limitError($message);
    }


    /**
     * Записва грешка от OCR обработка
     *
     * @param array  $params
     * @param string $message
     */
    protected static function registerError($params, $message)
    {
        $message = static::limitError($message);

        if (!empty($params['dataId'])) {
            fileman_Data::logErr('Doubleword OCR: ' . $message, $params['dataId']);
            fileman_Indexes::createError($params);
            fileman_Indexes::createErrorLog($params['dataId'], $params['type']);
        } else {
            static::logErr('Doubleword OCR: ' . $message);
        }
    }


    /**
     * Официалният prompt за olmOCR-2-7B
     *
     * @return string
     */
    protected static function getOcrPrompt()
    {
        return 'Attached is one page of a document that you must process. '
            . 'Just return the plain text representation of this document as if you were reading it naturally. '
            . 'Convert equations to LaTeX and tables to HTML. '
            . 'If there are any figures or charts, label them with the following markdown syntax '
            . '![Alt text describing the contents of the figure](page_startx_starty_width_height.png). '
            . 'Return your output as markdown, with a front matter section on top specifying values for the '
            . 'primary_language, is_rotation_valid, rotation_correction, is_table, and is_diagram parameters.';
    }


    /**
     * Проверява дали файлът може да бъде обработен
     *
     * @param stdClass|string $fRec
     *
     * @return bool
     *
     * @see fileman_OCRIntf
     */
    public static function canExtract($fRec)
    {
        $name = is_object($fRec) ? $fRec->name : $fRec;
        $ext = strtolower(fileman_Files::getExt($name));

        return $ext && in_array($ext, self::$allowedExt, true);
    }


    /**
     * Бърза проверка дали има смисъл от OCR обработка
     *
     * @param stdClass|string $fRec
     *
     * @return bool
     *
     * @see fileman_OCRIntf
     */
    public static function haveTextForOcr($fRec)
    {
        return true;
    }


    /**
     * Връща id на класа директно от core_Classes
     *
     * @return int|null
     */
    public static function getClassId()
    {
        return core_Classes::fetchField(array("#name = '[#1#]'", get_called_class()), 'id');
    }
}
