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
     * Време за свързване към API-то
     *
     * Времето за самата заявка е в настройката DOUBLEWORD_REQUEST_TIMEOUT, а за временен
     * файл се ползва по-малкото от нея и PATH_REQUEST_TIMEOUT - там се чака синхронно
     */
    const CONNECT_TIMEOUT = 30;
    const PATH_REQUEST_TIMEOUT = 600;


    /**
     * Повторения при временна API грешка и при корекция на ротацията
     *
     * Временните мрежови грешки се повтарят от самия curl (--retry), а още един кръг
     * заявки се пуска само ако моделът поиска завъртане или върне невалиден отговор
     */
    const MAX_CONFIGURED_API_RETRIES = 3;
    const MAX_RESPONSE_RETRIES = 1;
    const MAX_ROTATION_RETRIES = 2;
    const MAX_ROUNDS = 3;
    const RETRY_DELAY = 5;


    /**
     * Лимит на отговора за първата заявка
     *
     * Ограничава времето на генериране - без него моделът стига до собствения си таван и
     * една страница може да виси минути. Ако отговорът се отреже, следващият кръг за тази
     * страница тръгва без лимит. При null лимит не се задава изобщо.
     */
    const INITIAL_COMPLETION_TOKENS = 8000;
    const MIN_COMPLETION_TOKENS = 1000;


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
     * HTML таговете, които моделът може да върне в markdown отговора си
     */
    const HTML_TAGS = 'a|b|i|u|s|em|strong|span|font|small|big|sup|sub|code|pre|img|hr|br|' .
        'table|thead|tbody|tfoot|tr|th|td|caption|colgroup|col|ul|ol|li|dl|dt|dd|p|div|' .
        'h1|h2|h3|h4|h5|h6|blockquote|section|article|header|footer|figure|figcaption';


    /**
     * Служебен знак, с който временно се маркират редовете на таблица
     */
    const ROW_MARK = "\x01";


    /**
     * Интерфейсни методи
     */
    public $interfaces = 'fileman_OCRIntf, fileman_MarkdownIntf, fileman_FileActionsIntf';


    /**
     * Типовете индекси, които се записват от една обработка
     *
     * Моделът връща markdown с HTML таблици - той се пази както е в markdown индекса,
     * а в текстовия индекс влиза изчистеното текстово представяне.
     */
    public static $indexType = 'textOcr';
    public static $markdownIndexType = 'markdown';


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
     * Пътят до програмите, които скриптовете използват
     */
    public array $fconvProgramPaths = array(
        'pdftoppm' => 'doubleword_Setup::DOUBLEWORD_PDFTOPPM_PATH',
        'curl' => 'doubleword_Setup::DOUBLEWORD_CURL_PATH',
    );


    /**
     * Команда за преобразуване на PDF страници до препоръчания от модела размер
     */
    public $fconvLineExec = 'timeout --signal=TERM --kill-after=10s [#PDF_TIMEOUT#] [#PDFTOPPM#] -png -f [#FIRST_PAGE#] -l [#LAST_PAGE#] -r 150 -scale-to 1288 [#INPUTF#] [#OUTPUT_DIR#]/page 2> [#ERROR_FILE#] && touch [#SUCCESS_FILE#]';


    /**
     * Заявката за една страница
     *
     * Адресът, ключът и таймаутите стоят във файла с настройки на curl, за да не влизат
     * в текста на скрипта - fconv_Script::run() го записва в системния лог
     */
    public $curlLineExec = '[#CURL#] --config [#CURL_CONFIG#] --data-binary [#PAYLOAD#] --output [#OUT#] --write-out [#WRITE_OUT#] > [#META#] 2> [#ERR#] &';


    /**
     * Изчакване на пуснатите заявки от групата
     */
    public $waitLineExec = 'wait';


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
                'type' => static::$indexType,
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
        return static::startProcess($fRec, false);
    }


    /**
     * Извлича съдържанието на файла в markdown
     *
     * Ползва същата OCR обработка - суровият отговор на модела (markdown с HTML таблици)
     * се записва в 'markdown' индекса, а изчистеният текст - в текстовия.
     *
     * @param stdClass|string $fRec
     *
     * @return string|null
     *
     * @see fileman_MarkdownIntf
     */
    public function getMarkdown($fRec)
    {
        if (is_object($fRec)) {
            $started = fileman_Indexes::isProcessStarted(array(
                'type' => static::$markdownIndexType,
                'dataId' => $fRec->dataId,
            ));

            // Има вече извлечено съдържание или тече обработка, която ще го запише
            if ($started) {

                return null;
            }
        }

        return static::startProcess($fRec, true);
    }


    /**
     * Стартира обработката за текстовия и за markdown индекса
     *
     * @param stdClass|string $fRec
     * @param bool            $forMarkdown Дали е извикана през fileman_MarkdownIntf
     *
     * @return string|null
     */
    protected static function startProcess($fRec, $forMarkdown = false)
    {
        $isFileRec = is_object($fRec);
        $params = array(
            'callBack' => get_called_class() . '::afterGetTextByDoubleword',
            'createdBy' => core_Users::getCurrent('id'),
            'type' => static::$indexType,
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
        $params['lockIds'] = array($params['lockId']);
        if (core_Locks::isLocked($params['lockId'])) {
            if ($forMarkdown) {

                // Текущата OCR обработка ще запише и markdown съдържанието
                return null;
            }

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

        // Markdown индексът се пълни от същата обработка, затова се заключва и той. Ако друга
        // програма вече го прави, само не го пипаме - OCR обработката продължава нормално
        $markdownLockId = fileman_webdrv_Generic::getLockId(static::$markdownIndexType, $lId);
        if (core_Locks::obtain($markdownLockId, $lockDuration, 0, 0, false)) {
            $params['markdownType'] = static::$markdownIndexType;
            $params['lockIds'][] = $markdownLockId;
        } elseif ($forMarkdown) {
            static::releaseLocks($params);

            return null;
        }

        if ($isFileRec) {
            fileman_Data::logWrite('OCR обработка на файл с Doubleword.ai', $fRec->dataId);
            fileman_Files::logWrite('OCR обработка на файл с Doubleword.ai', $fRec->id);

            try {
                // Страниците се подготвят тук, а заявките ги изпълнява скриптът
                $params = static::prepareParams($file, $params);
                $dataUris = static::getPageDataUris($file, $params);
                static::startRound($dataUris, $params, true);
            } catch (Throwable $e) {
                static::registerError($params, static::getExceptionMessage($e));
                static::releaseLocks($params);

                throw $e;
            }

            if (!$forMarkdown) {
                status_Messages::newStatus('|Стартирано е извличането на текст с OCR', 'success');
            }

            return null;
        }

        try {
            $res = static::extract($file, $params);

            return $forMarkdown ? $res['markdown'] : $res['text'];
        } catch (Throwable $e) {
            static::registerError($params, static::getExceptionMessage($e));

            throw $e;
        } finally {
            static::releaseLocks($params);
        }
    }


    /**
     * Освобождава всички ключалки на обработката
     *
     * @param array $params
     */
    protected static function releaseLocks($params)
    {
        $lockIds = !empty($params['lockIds']) ? (array) $params['lockIds'] :
            (!empty($params['lockId']) ? array($params['lockId']) : array());

        foreach ($lockIds as $lockId) {
            core_Locks::release($lockId);
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
        $res = static::extract($fileHnd, $params);

        return $res['text'];
    }


    /**
     * Разпознава файла и връща съдържанието му в двата вида
     *
     * Страница, която не може да бъде разпозната, не прекратява обработката -
     * на нейно място в текста застава бележка за грешката.
     *
     * @param string $fileHnd
     * @param array  $params
     *
     * @return array с ключове 'markdown' (суровият отговор) и 'text' (изчистен текст)
     */
    protected static function extract($fileHnd, $params = array())
    {
        setIfNot($params['isPath'], is_file($fileHnd));
        core_App::setTimeLimit(static::getProcessTimeLimit(!empty($params['isPath']), $fileHnd));

        $params = static::prepareParams($fileHnd, $params);
        $dataUris = static::getPageDataUris($fileHnd, $params);

        // Синхронно - всеки кръг изчаква скрипта си и веднага се обработва
        while (true) {
            $Script = static::startRound($dataUris, $params, false);
            $dataUris = static::collectRound($Script, $params);
            static::cleanScript($Script);
            if (!count($dataUris)) {
                break;
            }
        }

        return static::finishExtraction($params);
    }


    /**
     * Допълва параметрите със състоянието, което се пренася между кръговете
     *
     * @param string $fileHnd
     * @param array  $params
     *
     * @return array
     */
    protected static function prepareParams($fileHnd, $params)
    {
        if (!empty($params['isPath'])) {
            expect(is_file($fileHnd) && is_readable($fileHnd), 'Файлът за OCR не е достъпен');
            expect(static::canExtract($fileHnd), 'Файловият формат не се поддържа за OCR');
        } else {
            $fRec = fileman_Files::fetchByFh($fileHnd);
            expect($fRec, 'Файлът за OCR не може да бъде намерен');
            expect(static::canExtract($fRec), 'Файловият формат не се поддържа за OCR');
        }

        setIfNot($params['startedOn'], microtime(true));
        setIfNot($params['round'], 0);
        setIfNot($params['texts'], array());
        setIfNot($params['errors'], array());
        setIfNot($params['partials'], array());
        setIfNot($params['rotations'], array());
        setIfNot($params['responseTries'], array());
        setIfNot($params['uncapped'], array());

        return $params;
    }


    /**
     * Сглобява резултата от всички кръгове и го записва в индексите
     *
     * Страница, която не може да бъде разпозната, не прекратява обработката -
     * на нейно място в текста застава бележка за грешката.
     *
     * @param array $params
     *
     * @return array с ключове 'markdown' (за markdown индекса) и 'text' (изчистен текст)
     */
    protected static function finishExtraction($params)
    {
        $dataId = !empty($params['dataId']) ? (int) $params['dataId'] : null;
        $allPages = (array) $params['allPages'];
        $parts = (array) $params['texts'];
        $pageErrors = (array) $params['errors'];
        $partialPages = (array) $params['partials'];

        ksort($parts, SORT_NUMERIC);

        $pageTexts = array();
        $failedPages = array();
        foreach ($allPages as $pageNo) {
            if (array_key_exists($pageNo, $parts)) {
                $pageTexts[$pageNo] = (string) $parts[$pageNo];

                continue;
            }

            $failedPages[] = $pageNo;
            $pageTexts[$pageNo] = static::getPageErrorNote(
                $pageNo,
                isset($pageErrors[$pageNo]) ? $pageErrors[$pageNo] : ''
            );
        }

        $assembled = trim(implode("\n\n", $pageTexts));
        $markdown = static::toMarkdown($assembled);
        $res = static::toPlainText($assembled);

        expect(count($parts), 'Doubleword.ai не разпозна нито една от ' . count($allPages) .
            ' страници' . (count($pageErrors) ?
                ': ' . static::getPageErrorSummary(reset($pageErrors)) : ''));
        expect(strlen($res), 'Doubleword.ai не върна разпознат текст');

        if (count($failedPages)) {
            static::logPageWarning(
                $dataId,
                'страници ' . implode(', ', $failedPages) . ' от общо ' . count($allPages) .
                ' не са разпознати - записан е текстът от останалите ' . count($parts)
            );
        }

        if ($dataId) {
            static::saveMarkdownContent($params, $markdown);

            $params['content'] = $res;
            $savedId = fileman_Indexes::saveContent($params);
            expect($savedId, 'Разпознатият текст не може да бъде записан');
            $duration = round(microtime(true) - $params['startedOn'], 3);
            fileman_Data::logInfo(
                'Завършена OCR обработка с Doubleword.ai (' . strlen($res) .
                ' байта, ' . count($parts) . ' от ' . count($allPages) . ' страници' .
                (count($partialPages) ? ', частично: ' . implode(', ', $partialPages) : '') .
                ", {$duration} сек.)",
                $dataId
            );
        }

        return array('markdown' => $markdown, 'text' => $res);
    }


    /**
     * Записва суровия отговор на модела в markdown индекса
     *
     * @param array  $params
     * @param string $content
     */
    protected static function saveMarkdownContent($params, $content)
    {
        if (empty($params['markdownType']) || empty($params['dataId'])) {

            return;
        }

        $params['type'] = $params['markdownType'];
        $params['content'] = $content;
        if (!fileman_Indexes::saveContent($params)) {
            fileman_Data::logWarning(
                'Doubleword OCR: съдържанието в markdown не може да бъде записано',
                (int) $params['dataId']
            );
        }
    }


    /**
     * Обработва отговорите от завършил кръг заявки
     *
     * Тежката работа е свършена от скрипта - тук само се четат готовите отговори и
     * или се пуска още един кръг, или резултатът се записва в индексите.
     *
     * @param fconv_Script $script
     *
     * @return bool
     */
    public function afterGetTextByDoubleword($script)
    {
        $params = (isset($script->params) && is_array($script->params)) ? $script->params : array();

        try {
            $pending = static::collectRound($script, $params);

            // Моделът е поискал завъртане или е върнал невалиден отговор - още един кръг
            if (count($pending)) {
                static::startRound($pending, $params, true);

                return true;
            }

            static::finishExtraction($params);
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

        static::releaseLocks($params);

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
        $renderStartedOn = microtime(true);
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
        $pdfTimeout = max(30, min(3600, (int) doubleword_Setup::get('PDF_RENDER_TIMEOUT')));
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

            if (!empty($params['dataId'])) {
                $duration = round(microtime(true) - $renderStartedOn, 3);
                fileman_Data::logInfo(
                    'Doubleword OCR: PDF файлът е преобразуван в ' . count($pagePaths) .
                    " страници за {$duration} сек.",
                    (int) $params['dataId']
                );
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
     * Максималното време за една заявка към модела
     *
     * @param bool $isPath
     *
     * @return int
     */
    protected static function getRequestTimeout($isPath = false)
    {
        $timeout = max(30, min(7200, (int) doubleword_Setup::get('REQUEST_TIMEOUT')));

        return $isPath ? min($timeout, self::PATH_REQUEST_TIMEOUT) : $timeout;
    }


    /**
     * Връща броя повторения при временна API грешка
     *
     * @param bool $isPath
     *
     * @return int
     */
    protected static function getMaxApiRetries($isPath = false)
    {
        $configName = $isPath ? 'PATH_API_RETRIES' : 'API_RETRIES';
        $retries = (int) doubleword_Setup::get($configName);

        return max(0, min(self::MAX_CONFIGURED_API_RETRIES, $retries));
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
        $requestTimeout = static::getRequestTimeout($isPath);
        $apiAttempts = static::getMaxApiRetries($isPath) + 1;
        $batchTime = $batches * $apiAttempts * ($requestTimeout + self::RETRY_DELAY);
        $renderTimeout = max(30, min(3600, (int) doubleword_Setup::get('PDF_RENDER_TIMEOUT')));

        return max(1800, ($batchTime * self::MAX_ROUNDS) + $renderTimeout + 600);
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
     * Подготвя и пуска един кръг заявки към Doubleword.ai
     *
     * Заявките се изпълняват от скрипта с curl - PHP само подготвя телата им и после
     * прочита готовите отговори, без да чака мрежата.
     *
     * @param array $dataUris Страниците за този кръг
     * @param array $params
     * @param bool  $asynch
     *
     * @return fconv_Script
     */
    protected static function startRound($dataUris, &$params, $asynch)
    {
        expect(count($dataUris), 'Няма страници за разпознаване');

        $Script = cls::get('fconv_Script');
        $Script->stopRemote = true;
        $Script->setProgram('curl', doubleword_Setup::get('CURL_PATH'));
        $Script->setProgramPath(get_called_class(), 'fconvProgramPaths');
        $Script->setCheckProgramsArr('curl');

        core_Os::requireDir($Script->tempDir);
        $dir = rtrim($Script->tempDir, '/') . '/';

        $configPath = $dir . 'curl.conf';
        expect(file_put_contents($configPath, static::getCurlConfig($params)) !== false,
            'Настройките за заявките не могат да бъдат записани');
        @chmod($configPath, 0600);

        $Script->setParam('CURL', doubleword_Setup::get('CURL_PATH'));
        $Script->setParam('CURL_CONFIG', $configPath);
        $Script->setParam('WRITE_OUT', '%{http_code} %{time_total}');

        $pages = array();
        foreach (array_chunk($dataUris, self::MAX_PARALLEL_REQUESTS, true) as $chunk) {
            foreach ($chunk as $pageNo => $dataUri) {
                // Страница, чийто отговор вече е бил отрязан, се иска без лимит
                $withLimit = empty($params['uncapped'][$pageNo]);
                $payloadPath = $dir . 'payload-' . $pageNo . '.json';
                expect(file_put_contents($payloadPath, static::getRequestPayload($dataUri, $withLimit)) !== false,
                    "Заявката за страница {$pageNo} не може да бъде записана");

                $Script->setParam('PAYLOAD', '@' . $payloadPath);
                $Script->setParam('OUT', $dir . 'out-' . $pageNo . '.json');
                $Script->setParam('META', $dir . 'meta-' . $pageNo . '.txt');
                $Script->setParam('ERR', $dir . 'err-' . $pageNo . '.txt');
                $Script->lineExec(get_called_class() . '::curlLineExec', array(), false);
                $pages[] = $pageNo;
            }

            // Групата се изчаква, преди да тръгне следващата
            $Script->lineExec(get_called_class() . '::waitLineExec', array(), false);
        }

        $params['pages'] = $pages;
        setIfNot($params['allPages'], $pages);
        $timeLimit = static::getProcessTimeLimit(!empty($params['isPath']));

        if ($asynch) {
            $Script->callBack($params['callBack']);
        }

        $Script->params = $params;
        if ($Script->run($asynch, $timeLimit) === false) {
            expect(false, 'Заявките към Doubleword.ai не могат да бъдат стартирани');
        }

        if (!empty($params['dataId'])) {
            fileman_Data::logInfo(
                'Doubleword OCR: стартиран кръг ' . ($params['round'] + 1) . ' за страници ' .
                implode(', ', $pages),
                (int) $params['dataId']
            );
        }

        return $Script;
    }


    /**
     * Прочита отговорите от завършилия кръг
     *
     * @param fconv_Script $script
     * @param array        $params
     *
     * @return array Страниците, които се нуждаят от още един кръг, с изображенията им
     */
    protected static function collectRound($script, &$params)
    {
        $dataId = !empty($params['dataId']) ? (int) $params['dataId'] : null;
        $dir = rtrim($script->tempDir, '/') . '/';
        $round = (int) $params['round'];
        $isLastRound = ($round + 1) >= self::MAX_ROUNDS;
        $pending = array();
        $done = array();

        foreach ((array) $params['pages'] as $pageNo) {
            $body = (string) @file_get_contents($dir . 'out-' . $pageNo . '.json');
            $meta = trim((string) @file_get_contents($dir . 'meta-' . $pageNo . '.txt'));
            $stdErr = trim((string) @file_get_contents($dir . 'err-' . $pageNo . '.txt'));

            $metaArr = preg_split('/\s+/', $meta, -1, PREG_SPLIT_NO_EMPTY);
            $httpCode = (int) ($metaArr[0] ?? 0);
            $curlInfo = array(
                'total_time' => isset($metaArr[1]) ? (float) $metaArr[1] : null,
                'round' => $round + 1,
                'max_rounds' => self::MAX_ROUNDS,
                'requested_max_tokens' => (self::INITIAL_COMPLETION_TOKENS === null ||
                    !empty($params['uncapped'][$pageNo])) ?
                    'provider_default' : self::INITIAL_COMPLETION_TOKENS,
            );

            // Без HTTP код заявката изобщо не е стигнала до отговор
            $curlError = $httpCode ? '' : ($stdErr ? $stdErr : 'заявката не върна отговор');

            try {
                $rotationCorrection = null;
                $responseRetryReason = null;
                $partialContent = null;
                $content = static::decodeApiResponse(
                    $body,
                    $httpCode,
                    $curlError,
                    $pageNo,
                    $rotationCorrection,
                    $curlInfo,
                    $responseRetryReason,
                    $partialContent
                );

                if ($responseRetryReason !== null) {
                    $diagnostics = static::getApiResponseDiagnostics(
                        json_decode($body),
                        $responseRetryReason === 'missing' ? null : $responseRetryReason,
                        $httpCode,
                        $body,
                        $curlInfo
                    );

                    // Отрязаният отговор има смисъл да се повтори само ако лимитът може да
                    // отпадне - иначе моделът пак ще опре в същото
                    $canLiftLimit = ($responseRetryReason === 'length') &&
                        (self::INITIAL_COMPLETION_TOKENS !== null) && empty($params['uncapped'][$pageNo]);
                    $canRetry = !$isLastRound &&
                        ($canLiftLimit || $responseRetryReason !== 'length') &&
                        ((int) ($params['responseTries'][$pageNo] ?? 0) < self::MAX_RESPONSE_RETRIES);

                    if ($canRetry) {
                        $params['responseTries'][$pageNo] = (int) ($params['responseTries'][$pageNo] ?? 0) + 1;
                        if ($canLiftLimit) {
                            $params['uncapped'][$pageNo] = true;
                        }
                        $pending[$pageNo] = static::getRoundImage($dir, $pageNo);
                        static::logApiRetry($dataId, "незавършен API отговор за страница {$pageNo}", $diagnostics);

                        continue;
                    }

                    $partial = static::getPartialOcrText($partialContent);
                    expect(strlen($partial),
                        "Doubleword.ai не върна валиден отговор за страница {$pageNo}" . $diagnostics);

                    $params['texts'][$pageNo] = $partial . "\n\n" .
                        static::getPartialPageNote($pageNo, $responseRetryReason);
                    $params['partials'][$pageNo] = $pageNo;
                    $done[] = $pageNo;
                    static::logPageWarning(
                        $dataId,
                        "страница {$pageNo} е разпозната частично (" . strlen($partial) . ' байта)' . $diagnostics
                    );

                    continue;
                }

                if ($rotationCorrection !== null) {
                    $tries = (int) ($params['rotations'][$pageNo] ?? 0);
                    expect(!$isLastRound && $tries < self::MAX_ROTATION_RETRIES,
                        "Doubleword.ai не успя да определи ориентацията на страница {$pageNo}");

                    $params['rotations'][$pageNo] = $tries + 1;
                    $pending[$pageNo] = static::rotateDataUri(
                        static::getRoundImage($dir, $pageNo),
                        $rotationCorrection
                    );
                    static::logApiRetry(
                        $dataId,
                        "корекция на ориентацията с {$rotationCorrection}° за страница {$pageNo}"
                    );

                    continue;
                }

                $params['texts'][$pageNo] = $content;
                $done[] = $pageNo;
            } catch (Throwable $e) {
                static::registerPageError($params['errors'], $pageNo, static::getExceptionMessage($e), $dataId);
            }
        }

        if ($dataId) {
            $finished = array_diff((array) $params['pages'], array_keys($pending));
            fileman_Data::logInfo(
                'Doubleword OCR: кръг ' . ($round + 1) . ' приключи' .
                static::getPagesOutcome($finished, $params['texts'], $params['partials']) .
                (count($pending) ? '; за следващ кръг: ' . implode(', ', array_keys($pending)) : ''),
                $dataId
            );
        }

        $params['round'] = $round + 1;

        return $pending;
    }


    /**
     * Връща изображението на страницата от заявката на приключилия кръг
     *
     * @param string $dir
     * @param int    $pageNo
     *
     * @return string
     */
    protected static function getRoundImage($dir, $pageNo)
    {
        $payload = json_decode((string) @file_get_contents($dir . 'payload-' . $pageNo . '.json'));
        $parts = $payload->messages[0]->content ?? array();
        foreach ((array) $parts as $part) {
            if (isset($part->image_url->url)) {

                return $part->image_url->url;
            }
        }

        expect(false, "Изображението на страница {$pageNo} не може да бъде намерено");
    }


    /**
     * Настройките на curl за заявките
     *
     * Държат се във файл, за да не влизат в текста на скрипта - той се записва в лога
     *
     * @param array $params
     *
     * @return string
     */
    protected static function getCurlConfig($params)
    {
        $apiKey = trim((string) doubleword_Setup::get('API_KEY'));
        expect(strlen($apiKey), 'Не е зададен API ключ за Doubleword.ai');

        $url = trim((string) doubleword_Setup::get('API_URL'));
        expect(strlen($url), 'Не е зададен API адрес за Doubleword.ai');

        $timeout = static::getRequestTimeout(!empty($params['isPath']));
        $retries = static::getMaxApiRetries(!empty($params['isPath']));

        $lines = array(
            'url = ' . static::quoteCurlValue($url),
            'header = ' . static::quoteCurlValue('Authorization: Bearer ' . $apiKey),
            'header = ' . static::quoteCurlValue('Content-Type: application/json'),
            'header = ' . static::quoteCurlValue('Accept: application/json'),
            'user-agent = ' . static::quoteCurlValue('bgERP Doubleword OCR/0.7'),
            'connect-timeout = ' . self::CONNECT_TIMEOUT,
            'max-time = ' . (int) $timeout,
            'compressed',
            'silent',
            'show-error',
        );

        if ($retries) {
            $lines[] = 'retry = ' . (int) $retries;
            $lines[] = 'retry-delay = ' . self::RETRY_DELAY;
            $lines[] = 'retry-connrefused';
        }

        return implode("\n", $lines) . "\n";
    }


    /**
     * Екранира стойност за файла с настройки на curl
     *
     * @param string $value
     *
     * @return string
     */
    protected static function quoteCurlValue($value)
    {
        return '"' . str_replace(array('\\', '"'), array('\\\\', '\\"'), (string) $value) . '"';
    }


    /**
     * Тялото на заявката за една страница
     *
     * @param string $dataUri
     *
     * @return string
     */
    protected static function getRequestPayload($dataUri, $withLimit = true)
    {
        $serviceTier = trim((string) doubleword_Setup::get('SERVICE_TIER'));
        $payload = array(
            'model' => doubleword_Setup::get('OCR_MODEL'),
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

        if (strlen($serviceTier)) {
            $payload['service_tier'] = $serviceTier;
        }

        if ($withLimit && self::INITIAL_COMPLETION_TOKENS !== null) {
            $payload['max_tokens'] = max(self::MIN_COMPLETION_TOKENS, (int) self::INITIAL_COMPLETION_TOKENS);
        }

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        expect(is_string($json), 'Не може да бъде създадена JSON заявка към Doubleword.ai');

        return $json;
    }


    /**
     * Изтрива временните файлове на приключил скрипт
     *
     * @param fconv_Script $script
     */
    protected static function cleanScript($script)
    {
        if (!empty($script->tempDir) && core_Os::deleteDir($script->tempDir)) {
            fconv_Processes::delete(array("#processId = '[#1#]'", $script->id));
        }
    }


    /**
     * Записва безопасна диагностика при повторение на API заявка
     *
     * @param int|null $dataId
     * @param string   $reason
     * @param string   $diagnostics
     */
    protected static function logApiRetry($dataId, $reason, $diagnostics = '')
    {
        $message = static::limitError('Doubleword OCR: повторение след ' . $reason . $diagnostics);
        if ($dataId) {
            fileman_Data::logNotice($message, (int) $dataId);
        } else {
            static::logNotice($message);
        }
    }


    /**
     * Отбелязва страница, която не може да бъде разпозната
     *
     * Първата грешка за страницата е меродавна - тя описва причината най-точно.
     *
     * @param array    $pageErrors
     * @param int      $pageNo
     * @param string   $message
     * @param int|null $dataId
     */
    protected static function registerPageError(&$pageErrors, $pageNo, $message, $dataId = null)
    {
        if (isset($pageErrors[$pageNo])) {

            return;
        }

        $message = static::limitError($message);
        $pageErrors[$pageNo] = $message;
        static::logPageWarning($dataId, "страница {$pageNo} не е разпозната: " . $message);
    }


    /**
     * Записва предупреждение за частично разпознат документ
     *
     * @param int|null $dataId
     * @param string   $message
     */
    protected static function logPageWarning($dataId, $message)
    {
        $message = static::limitError('Doubleword OCR: ' . $message);
        if ($dataId) {
            fileman_Data::logWarning($message, (int) $dataId);
        } else {
            static::logWarning($message);
        }
    }


    /**
     * Връща обобщение кои страници от групата са разпознати и кои не
     *
     * @param array $pageNumbers
     * @param array $texts
     * @param array $partials
     *
     * @return string
     */
    protected static function getPagesOutcome($pageNumbers, $texts, $partials = array())
    {
        $done = array();
        $partial = array();
        $failed = array();
        foreach ($pageNumbers as $pageNo) {
            if (!array_key_exists($pageNo, $texts)) {
                $failed[] = $pageNo;
            } elseif (isset($partials[$pageNo])) {
                $partial[] = $pageNo;
            } else {
                $done[] = $pageNo;
            }
        }

        $res = ' (разпознати: ' . (count($done) ? implode(', ', $done) : 'няма');
        if (count($partial)) {
            $res .= '; частично: ' . implode(', ', $partial);
        }
        if (count($failed)) {
            $res .= '; с грешка: ' . implode(', ', $failed);
        }

        return $res . ')';
    }


    /**
     * Извлича текста от незавършен API отговор
     *
     * Ако отговорът е прекъснат още в служебния YAML блок, няма какво да се спаси.
     *
     * @param string|null $content
     *
     * @return string
     */
    protected static function getPartialOcrText($content)
    {
        if (!is_string($content) || !strlen(trim($content))) {

            return '';
        }

        $metadata = array();
        $text = static::stripFrontMatter($content, $metadata);
        if (!count($metadata) && preg_match('/\A\s*---[ \t]*\R/u', $text)) {

            return '';
        }

        return trim($text);
    }


    /**
     * Бележката, която отбелязва край на непълно разпозната страница
     *
     * @param int    $pageNo
     * @param string $reason
     *
     * @return string
     */
    protected static function getPartialPageNote($pageNo, $reason)
    {
        $cause = $reason === 'length' ?
            'отговорът на модела е достигнал лимита си' :
            'отговорът на модела не е завършен';

        return "[Doubleword OCR: текстът на страница {$pageNo} е непълен - {$cause}]";
    }


    /**
     * Бележката, която застава в текста на мястото на неразпозната страница
     *
     * @param int    $pageNo
     * @param string $message
     *
     * @return string
     */
    protected static function getPageErrorNote($pageNo, $message)
    {
        $note = "Doubleword OCR: грешка при разпознаването на страница {$pageNo}";
        $message = static::getPageErrorSummary($message);
        if (strlen($message)) {
            $note .= ': ' . $message;
        }

        return '[' . $note . ']';
    }


    /**
     * Скъсява съобщение за грешка без техническата диагностика
     *
     * @param string $message
     *
     * @return string
     */
    protected static function getPageErrorSummary($message)
    {
        $message = static::limitError($message);
        $message = preg_replace(
            '/(?:\s*\([a-z_]+=[^()]*(?:,\s*[a-z_]+=[^()]*)*\))+\s*$/ui',
            '',
            $message
        );

        return mb_substr(trim((string) $message), 0, 200);
    }


    /**
     * Извлича текста от OpenAI-съвместим Chat Completions отговор
     *
     * @param string $body
     * @param int    $httpCode
     * @param string $curlError
     * @param int    $pageNo
     * @param int|null $rotationCorrection
     * @param array  $curlInfo
     * @param string|null $responseRetryReason
     * @param string|null $partialContent Текстът от незавършен отговор
     *
     * @return string|null
     */
    protected static function decodeApiResponse(
        $body,
        $httpCode,
        $curlError,
        $pageNo,
        &$rotationCorrection = null,
        $curlInfo = array(),
        &$responseRetryReason = null,
        &$partialContent = null
    )
    {
        $rotationCorrection = null;
        $responseRetryReason = null;
        $partialContent = null;
        $transportDiagnostics = static::getTransportDiagnostics($httpCode, $body, $curlInfo);
        expect(!strlen((string) $curlError),
            "Doubleword.ai: грешка при страница {$pageNo}: " . static::limitError($curlError) .
            $transportDiagnostics);

        $decoded = json_decode((string) $body);
        if ($httpCode < 200 || $httpCode >= 300) {
            $message = static::getApiError($decoded);
            expect(false, "Doubleword.ai HTTP {$httpCode} при страница {$pageNo}: {$message}" .
                $transportDiagnostics);
        }

        expect(json_last_error() == JSON_ERROR_NONE && is_object($decoded),
            "Doubleword.ai върна невалиден JSON за страница {$pageNo}" . $transportDiagnostics);

        if (!empty($decoded->error)) {
            expect(false, "Doubleword.ai при страница {$pageNo}: " . static::getApiError($decoded) .
                $transportDiagnostics);
        }

        $choices = $decoded->choices ?? null;
        $choice = (is_array($choices) && isset($choices[0]) && is_object($choices[0])) ? $choices[0] : null;
        $finishReason = is_object($choice) ? ($choice->finish_reason ?? null) : null;
        $message = is_object($choice) ? ($choice->message ?? null) : null;
        expect(is_object($message),
            "Doubleword.ai върна неочакван отговор за страница {$pageNo}" .
            static::getApiResponseDiagnostics($decoded, $finishReason, $httpCode, $body, $curlInfo));

        $responseDiagnostics = static::getApiResponseDiagnostics(
            $decoded,
            $finishReason,
            $httpCode,
            $body,
            $curlInfo
        );
        $content = $message->content ?? null;
        if (is_array($content)) {
            $text = '';
            foreach ($content as $part) {
                if (is_object($part) && isset($part->text)) {
                    $text .= $part->text;
                }
            }
            $content = $text;
        }

        if ($finishReason !== 'stop') {
            if ($finishReason === 'length' || $finishReason === null) {
                $responseRetryReason = $finishReason === null ? 'missing' : $finishReason;
                $partialContent = is_string($content) ? $content : null;

                return null;
            }
            if ($finishReason === 'content_filter') {
                $error = "Doubleword.ai прекрати отговора от content filter при страница {$pageNo}";
            } else {
                $error = "Doubleword.ai върна незавършен отговор за страница {$pageNo}";
            }
            expect(false, $error . $responseDiagnostics);
        }

        expect(is_string($content) && strlen(trim($content)),
            "Doubleword.ai не върна текст за страница {$pageNo}" . $responseDiagnostics);

        return static::parseOcrContent($content, $pageNo, $rotationCorrection);
    }


    /**
     * Връща безопасна диагностика за завършил API отговор
     *
     * Не включва OCR текста, входното изображение или API ключа.
     *
     * @param mixed  $decoded
     * @param mixed  $finishReason
     * @param int    $httpCode
     * @param string $body
     * @param array  $curlInfo
     *
     * @return string
     */
    protected static function getApiResponseDiagnostics($decoded, $finishReason, $httpCode, $body, $curlInfo)
    {
        $usage = is_object($decoded) ? ($decoded->usage ?? null) : null;
        $completionTokens = is_object($usage) ?
            ($usage->completion_tokens ?? $usage->output_tokens ?? null) : null;
        $promptTokens = is_object($usage) ?
            ($usage->prompt_tokens ?? $usage->input_tokens ?? null) : null;

        $diagnostics = array(
            'finish_reason' => $finishReason,
            'completion_tokens' => $completionTokens,
            'prompt_tokens' => $promptTokens,
            'requested_max_tokens' => $curlInfo['requested_max_tokens'] ?? 'provider_default',
            'response_id' => is_object($decoded) ? ($decoded->id ?? null) : null,
            'model' => is_object($decoded) ? ($decoded->model ?? null) : null,
            'service_tier' => is_object($decoded) ? ($decoded->service_tier ?? null) : null,
        );

        return static::formatDiagnostics($diagnostics) .
            static::getTransportDiagnostics($httpCode, $body, $curlInfo);
    }


    /**
     * Връща безопасна диагностика за HTTP заявката
     *
     * @param int    $httpCode
     * @param string $body
     * @param array  $curlInfo
     *
     * @return string
     */
    protected static function getTransportDiagnostics($httpCode, $body, $curlInfo)
    {
        $curlInfo = is_array($curlInfo) ? $curlInfo : array();
        $totalTime = $curlInfo['total_time'] ?? null;
        if (is_numeric($totalTime)) {
            $totalTime = round((float) $totalTime, 3);
        } else {
            $totalTime = null;
        }

        return static::formatDiagnostics(array(
            'http' => (int) $httpCode,
            'response_bytes' => strlen((string) $body),
            'total_time_sec' => $totalTime,
            'round' => $curlInfo['round'] ?? null,
            'max_rounds' => $curlInfo['max_rounds'] ?? null,
        ));
    }


    /**
     * Форматира диагностични стойности без чувствителни данни
     *
     * @param array $diagnostics
     *
     * @return string
     */
    protected static function formatDiagnostics($diagnostics)
    {
        $parts = array();
        foreach ((array) $diagnostics as $name => $value) {
            if ($value === null) {
                $value = 'null';
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_scalar($value)) {
                $value = preg_replace('/\s+/u', ' ', strip_tags((string) $value));
                $value = mb_substr(trim((string) $value), 0, 120);
                if (!strlen($value)) {
                    $value = "''";
                }
            } else {
                $value = gettype($value);
            }
            $parts[] = $name . '=' . $value;
        }

        return count($parts) ? ' (' . implode(', ', $parts) . ')' : '';
    }


    /**
     * Превръща отговора на модела в чист текст за текстовия индекс
     *
     * Клетките се разделят с табулация, а не с `|`, защото табът с текста чисти
     * съдържанието с израз, в който `|` е сред "белите" символи - при `| a | b |`
     * границите на редовете се слепват в един ред (виж fileman_webdrv_Generic::act_Text).
     * Табулациите остават непокътнати и са конвенцията за текст от таблични файлове
     * (виж str::tabsToMarkdownTable).
     *
     * @param string $content
     *
     * @return string
     */
    protected static function toPlainText($content)
    {
        return static::convertResponse($content, false);
    }


    /**
     * Превръща отговора на модела в markdown за markdown индекса
     *
     * @param string $content
     *
     * @return string
     */
    protected static function toMarkdown($content)
    {
        return static::convertResponse($content, true);
    }


    /**
     * Преобразува отговора на модела в един от двата вида съдържание
     *
     * По официалния промпт olmOCR връща markdown, в който таблиците са HTML. Тук те стават
     * markdown редове `| a | b |` или таб-разделени редове, а останалият HTML отпада -
     * таговете не се рендират никъде и само замърсяват търсенето.
     *
     * @param string $content
     * @param bool   $asMarkdown
     *
     * @return string
     */
    protected static function convertResponse($content, $asMarkdown)
    {
        $text = str_replace(self::ROW_MARK, '', (string) $content);

        if (preg_match('/<\/?(?:' . self::HTML_TAGS . ')\b[^>]*>/i', $text)) {
            $text = preg_replace_callback(
                '/<tr\b[^>]*>(.*?)(?:<\/tr\s*>|(?=<tr\b)|(?=<\/?table\b)|\z)/is',
                function ($match) use ($asMarkdown) {

                    return "\n" . self::ROW_MARK . static::htmlRowToText($match[1], $asMarkdown) . "\n";
                },
                $text
            );

            $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
            $text = preg_replace('/<li\b[^>]*>/i', "\n- ", $text);
            $text = preg_replace(
                '/<\/?(?:p|div|h[1-6]|hr|ul|ol|table|thead|tbody|tfoot|caption|blockquote|pre|' .
                'section|article|header|footer|figure)\b[^>]*>/i',
                "\n",
                $text
            );
            $text = static::stripHtmlTags($text);
            $text = static::decodeHtmlEntities($text);
        }

        // Ако моделът е върнал markdown таблици, в текста те стават таб-разделени редове -
        // табът с текста слепва редовете, разделени с `|`
        if (!$asMarkdown) {
            $text = static::markdownRowsToText($text);
        }

        $text = preg_replace('/[ \t]+$/m', '', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // Редовете на една таблица остават слепени, за да се чете като таблица
        $text = preg_replace('/' . self::ROW_MARK . '([^\n]*)\n+(?=' . self::ROW_MARK . ')/u', "$1\n", $text);
        $text = str_replace(self::ROW_MARK, '', $text);

        return trim(static::formatNotes($text, $asMarkdown));
    }


    /**
     * Превръща markdown таблични редове в таб-разделени
     *
     * @param string $text
     *
     * @return string
     */
    protected static function markdownRowsToText($text)
    {
        if (strpos($text, '|') === false) {

            return $text;
        }

        // Разделителният ред на markdown таблица няма какво да каже в текста
        $text = preg_replace('/^[ \t]*\|[ \t:|-]*\|[ \t]*$\n?/m', '', $text);

        return preg_replace_callback(
            '/^[ \t]*\|(.+)\|[ \t]*$/m',
            function ($match) {
                $cells = preg_split('/(?<!\\\\)\|/', $match[1]);
                foreach ($cells as $key => $cell) {
                    $cells[$key] = trim(str_replace('\|', '|', $cell));
                }

                return implode("\t", $cells);
            },
            $text
        );
    }


    /**
     * Отделя бележките за проблемни страници, за да личат в текста
     *
     * @param string $text
     * @param bool   $asMarkdown Дали бележката да е удебелена
     *
     * @return string
     */
    protected static function formatNotes($text, $asMarkdown = false)
    {
        $text = preg_replace_callback(
            '/\n*^[ \t]*(\[Doubleword OCR: [^\n]*\])[ \t]*$\n*/mu',
            function ($match) use ($asMarkdown) {
                $note = $asMarkdown ? '**' . $match[1] . '**' : $match[1];

                return "\n\n\n" . $note . "\n\n\n";
            },
            $text
        );

        // При две поредни бележки разстоянието се удвоява
        return preg_replace('/\n{4,}/', "\n\n\n", $text);
    }


    /**
     * Превръща един ред от HTML таблица в текстов ред
     *
     * @param string $row
     * @param bool   $asMarkdown
     *
     * @return string
     */
    protected static function htmlRowToText($row, $asMarkdown = false)
    {
        $cells = array();
        $pattern = '/<t[hd]\b[^>]*>(.*?)(?:<\/t[hd]\s*>|(?=<t[hd]\b)|(?=<\/tr\b)|\z)/is';
        if (preg_match_all($pattern, $row, $matches)) {
            foreach ($matches[1] as $cell) {
                $cells[] = static::htmlCellToText($cell, $asMarkdown);
            }
        }

        // Ред без клетки - остава като обикновен текст
        if (!count($cells)) {

            return static::htmlCellToText($row, $asMarkdown);
        }

        if (!$asMarkdown) {

            return implode("\t", $cells);
        }

        return '| ' . implode(' | ', $cells) . ' |';
    }


    /**
     * Превръща една клетка в текст на един ред
     *
     * @param string $cell
     * @param bool   $asMarkdown
     *
     * @return string
     */
    protected static function htmlCellToText($cell, $asMarkdown = false)
    {
        $cell = preg_replace('/<br\s*\/?>/i', ' ', $cell);
        $cell = static::stripHtmlTags($cell);
        $cell = static::decodeHtmlEntities($cell);
        if ($asMarkdown) {
            $cell = str_replace('|', '\|', $cell);
        }
        $cell = preg_replace('/\s+/u', ' ', $cell);

        return trim($cell);
    }


    /**
     * Премахва HTML таговете, без да закача текст като 'a < b'
     *
     * @param string $text
     *
     * @return string
     */
    protected static function stripHtmlTags($text)
    {
        return preg_replace('/<\/?(?:' . self::HTML_TAGS . ')\b[^>]*>/i', '', (string) $text);
    }


    /**
     * Декодира HTML същностите и нормализира твърдите интервали
     *
     * @param string $text
     *
     * @return string
     */
    protected static function decodeHtmlEntities($text)
    {
        $text = html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return str_replace("\xC2\xA0", ' ', $text);
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

            // Грешката се отбелязва за всеки индекс, който тази обработка е поела
            $types = array($params['type']);
            if (!empty($params['markdownType'])) {
                $types[] = $params['markdownType'];
            }

            foreach ($types as $type) {
                $params['type'] = $type;
                fileman_Indexes::createError($params);
                fileman_Indexes::createErrorLog($params['dataId'], $type);
            }
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
            . static::getTablePrompt()
            . 'If there are any figures or charts, label them with the following markdown syntax '
            . '![Alt text describing the contents of the figure](page_startx_starty_width_height.png). '
            . 'Return your output as markdown, with a front matter section on top specifying values for the '
            . 'primary_language, is_rotation_valid, rotation_correction, is_table, and is_diagram parameters.';
    }


    /**
     * Изречението от промпта, което определя вида на таблиците
     *
     * Само 'html' е официалният текст, с който моделът е трениран - другите два са за
     * сравнение и може да върнат по-нестабилен резултат.
     *
     * @return string
     */
    protected static function getTablePrompt()
    {
        $format = (string) doubleword_Setup::get('TABLE_FORMAT');

        if ($format == 'markdown') {

            return 'Convert equations to LaTeX and tables to markdown tables, '
                . 'with one row per line and the cells separated by the | character. '
                . 'Do not use HTML. ';
        }

        if ($format == 'text') {

            return 'Convert equations to LaTeX and tables to plain text, with one row per line '
                . 'and the cells of each row separated by a tab character. Do not use HTML. ';
        }

        return 'Convert equations to LaTeX and tables to HTML. ';
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
