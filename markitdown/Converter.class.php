<?php


/**
 * Извличане на съдържанието на файловете в markdown с MarkItDown (на Microsoft)
 *
 * Съдържанието се записва във `fileman_Indexes` с тип 'markdown' и се показва
 * в едноименния таб на файла.
 *
 * @category  bgerp
 * @package   markitdown
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 *
 * @link      https://github.com/microsoft/markitdown
 */
class markitdown_Converter extends core_Manager
{
    /**
     * Интерфейсни методи
     */
    public $interfaces = 'fileman_MarkdownIntf, fileman_FileActionsIntf';


    /**
     * Заглавие
     */
    public $title = 'MarkItDown';


    /**
     * Кои потребители могат да пускат извличане на съдържанието в markdown
     */
    public $canMarkdown = 'powerUser';


    /**
     * Типа на индекса, в който се записва извлеченото съдържание
     */
    public static $indexType = 'markdown';


    /**
     * Разширения на архивите, които markitdown разгъва и обхожда
     */
    public static $archiveExtArr = array('zip' => 'zip');


    /**
     * Масив с програмите и функциите за определяне на пътя до тях
     */
    public $fconvProgramPaths = array('markitdown' => 'markitdown_Setup::MARKITDOWN_PATH');


    /**
     * Кода, който ще се изпълнява
     */
    public $fconvLineExec = 'markitdown [#INPUTF#] -o [#OUTPUTF#]';


    /**
     * Интерфейсен метод на fileman_FileActionsIntf
     *
     * Връща масив с действия, които могат да се извършат с дадения файл
     *
     * @param stdClass $fRec - Обект с данни от модела
     *
     * @return array|NULL $arr - Масив с данните
     *                    $arr['url'] - array URL на действието
     *                    $arr['title'] - Заглавието на бутона
     *                    $arr['icon'] - Иконата
     */
    public static function getActionsForFile_($fRec)
    {
        $arr = null;

        if (self::haveRightFor('markdown') && self::canExtract($fRec)) {
            $btnParams = array();

            $btnParams['order'] = 65;
            $btnParams['title'] = 'Извличане на съдържанието в markdown';

            // Ако вече е извличано съдържанието, ще се извлече наново
            $procMarkdown = fileman_Indexes::isProcessStarted(array('type' => static::$indexType, 'dataId' => $fRec->dataId));
            if ($procMarkdown) {
                $btnParams['warning'] = 'Съдържанието на файла вече е извличано. Да се извлече ли наново?';
            }

            $arr = array();
            $arr['markitdown']['url'] = array(get_called_class(), 'getMarkdown', $fRec->fileHnd, 'ret_url' => true);
            $arr['markitdown']['title'] = 'MD';
            $arr['markitdown']['icon'] = 'img/16/doc_convert.png';
            $arr['markitdown']['btnParams'] = $btnParams;
        }

        return $arr;
    }


    /**
     * Екшън за извличане на съдържанието в markdown
     *
     * @see fileman_MarkdownIntf
     */
    public function act_getMarkdown()
    {
        // Манипулатора на файла
        $fh = Request::get('id');

        // Вземаме записа за файла
        $fRec = fileman_Files::fetchByFh($fh);

        expect($fRec);

        // Очакваме да може да се извлича
        expect(static::canExtract($fRec));

        $this->requireRightFor('markdown');

        fileman_Files::requireRightFor('single', $fRec);

        // Извличането е пуснато ръчно - махаме стария индекс, за да се направи наново
        if ($fRec->dataId) {
            fileman_Indexes::delete(array("#dataId = [#1#] AND #type = '[#2#]'", $fRec->dataId, static::$indexType));
        }

        $this->getMarkdown($fRec);

        // URL' то където ще редиректваме
        $retUrl = getRetUrl();

        // Ако не може да се определи
        if (empty($retUrl)) {
            $retUrl = array('fileman_Files', 'single', $fRec->fileHnd);
        }

        return new Redirect($retUrl);
    }


    /**
     * Извлича съдържанието на файла в markdown
     *
     * @param stdClass|string $fRec - Записите за файла или път до файл
     *
     * @return string|NULL - При синхронна обработка - извлеченото съдържание
     *
     * @see fileman_MarkdownIntf
     */
    public function getMarkdown($fRec)
    {
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => get_called_class() . '::afterGetMarkdown',
            'createdBy' => core_Users::getCurrent('id'),
            'type' => static::$indexType,
            'dataId' => null,
        );

        if (is_object($fRec)) {
            $params['dataId'] = $fRec->dataId;
            $params['asynch'] = true;
            $file = $fRec->fileHnd;
        } else {
            $params['asynch'] = false;
            $params['isPath'] = true;
            $file = $fRec;
        }

        $lId = fileman_webdrv_Generic::prepareLockId($fRec);

        // Променливата, с която ще заключим процеса
        $params['lockId'] = fileman_webdrv_Generic::getLockId($params['type'], $lId);

        // Проверяваме дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) {

            return ;
        }

        // Заключваме процеса за определено време
        if (core_Locks::obtain($params['lockId'], 300, 0, 0, false)) {
            if ($params['dataId']) {
                fileman_Data::logDebug('Извличане на съдържанието в markdown', $params['dataId']);
            }

            // Стартираме извличането
            return static::extract($file, $params);
        }
    }


    /**
     * Извлича съдържанието на подадения файл в markdown
     *
     * @param string $file   - Манипулатор на файла или път до файл
     * @param array  $params - Допълнителни параметри
     *
     * @return string - При синхронна обработка - извлеченото съдържание
     */
    public static function extract($file, $params)
    {
        // Инстанция на конвертиращия скрипт
        $Script = cls::get('fconv_Script');

        // Пътя до файла, в който ще се записва полученото съдържание
        $outFilePath = $Script->tempDir . 'content.md';

        // Задаваме файловете
        $Script->setFile('INPUTF', $file);
        $Script->setFile('OUTPUTF', $outFilePath);

        // Заместваме програмата с пътя от конфига
        $Script->setProgram('markitdown', markitdown_Setup::get('PATH'));
        $Script->setProgramPath(get_called_class(), 'fconvProgramPaths');

        $inst = cls::get(get_called_class());
        if (markitdown_Setup::get('USE_PLUGINS') == 'yes') {
            $inst->fconvLineExec = 'markitdown --use-plugins [#INPUTF#] -o [#OUTPUTF#]';
        } else {
            $inst->fconvLineExec = 'markitdown [#INPUTF#] -o [#OUTPUTF#]';
        }

        $errFilePath = fileman_webdrv_Generic::getErrLogFilePath($outFilePath);

        // Скрипта, който ще извлича съдържанието
        $Script->lineExec(get_called_class() . '::fconvLineExec', array('LANG' => 'en_US.UTF-8', 'HOME' => $Script->tempPath, 'errFilePath' => $errFilePath));

        // Функцията, която ще се извика след приключване на операцията, ако се стартира асинхронно
        if ($params['asynch']) {
            $Script->callBack($params['callBack']);
        }

        // Други допълнителни параметри
        $params['errFilePath'] = $errFilePath;
        $params['outFilePath'] = $outFilePath;

        $Script->params = $params;

        $Script->setCheckProgramsArr('markitdown');

        // Стартираме скрипта
        if ($Script->run($params['asynch']) === false) {
            fileman_Indexes::createError($params);

            core_Locks::release($params['lockId']);

            return '';
        }

        // При асинхронна обработка, съдържанието се записва в callBack функцията
        if ($params['asynch']) {

            return '';
        }

        $content = static::getContentFromFile($outFilePath);

        if ($params['dataId']) {
            $params['content'] = $content;

            if ($content || !fileman_Indexes::haveErrors($outFilePath, $params)) {
                fileman_Indexes::saveContent($params);
            }
        }

        core_Locks::release($params['lockId']);

        if ($Script->tempDir && core_Os::deleteDir($Script->tempDir)) {
            fconv_Processes::delete(array("#processId = '[#1#]'", $Script->id));
        }

        return $content;
    }


    /**
     * Получава управлението след приключване на извличането
     *
     * @param fconv_Script $script - Обект с данните за обработката
     *
     * @return bool - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всички
     *              временни файлове и записа от таблицата fconv_Process
     */
    public function afterGetMarkdown($script)
    {
        $params = $script->params;

        $params['content'] = static::getContentFromFile($params['outFilePath']);

        // Ако няма съдържание, проверяваме дали е имало грешка при обработката
        if ($params['content'] || !fileman_Indexes::haveErrors($params['outFilePath'], $params)) {

            // Обновяваме данните за запис във fileman_Indexes
            fileman_Indexes::saveContent($params);
        }

        // Отключваме процеса
        core_Locks::release($params['lockId']);

        return true;
    }


    /**
     * Връща подготвеното съдържание от генерирания файл
     *
     * @param string $outFilePath
     *
     * @return string
     */
    protected static function getContentFromFile($outFilePath)
    {
        if (!is_file($outFilePath)) {

            return '';
        }

        $content = @file_get_contents($outFilePath);

        if (!$content) {

            return '';
        }

        $content = i18n_Charset::convertToUtf8($content, 'UTF-8');

        $content = trim($content);

        // Ограничаваме дължината на съдържанието, което ще се записва в индекса
        $maxLen = markitdown_Setup::get('MAX_CONTENT_LEN');
        if ($maxLen) {
            $content = mb_strcut($content, 0, $maxLen);
        }

        return $content;
    }


    /**
     * Проверява дали от файла може да се извлича съдържание в markdown
     *
     * @param stdClass|string $fRec
     *
     * @return bool
     *
     * @see fileman_MarkdownIntf
     */
    public static function canExtract($fRec)
    {
        if (empty($fRec)) {

            return false;
        }

        $name = $fRec;
        if (is_object($fRec)) {
            $name = $fRec->name;
        }

        $ext = strtolower(fileman_Files::getExt($name));

        if (!$ext) {

            return false;
        }

        // Ако разширението не е в поддържаните
        $allowedExtArr = static::getAllowedExtArr();
        if (!isset($allowedExtArr[$ext])) {

            return false;
        }

        $fileLen = static::getFileLen($fRec);

        // Ограничение по размер на файла
        $maxFileLen = markitdown_Setup::get('MAX_FILE_LEN');

        if ($maxFileLen && $fileLen && ($fileLen > $maxFileLen)) {

            return false;
        }

        // Архивите се разгъват и обхождат целите - слагаме им допълнителни ограничения
        if (isset(static::$archiveExtArr[$ext])) {

            return static::canExtractFromArchive($fRec, $fileLen);
        }

        return true;
    }


    /**
     * Проверява дали от архива може да се извлича съдържание
     *
     * markitdown обхожда всички файлове в архива (и разгъва рекурсивно вложените архиви),
     * като държи съдържанието в паметта. Затова тук архивът се преглежда предварително -
     * четат се само данните от индекса му, без да се разархивира.
     *
     * @param stdClass|string $fRec
     * @param int|NULL        $fileLen - размера на архива (компресиран)
     *
     * @return bool
     */
    protected static function canExtractFromArchive($fRec, $fileLen)
    {
        // Ограничение по размера на самия архив
        $maxArchiveLen = markitdown_Setup::get('MAX_ARCHIVE_LEN');

        if ($maxArchiveLen && $fileLen && ($fileLen > $maxArchiveLen)) {

            return false;
        }

        if (!class_exists('ZipArchive')) {

            return false;
        }

        // Пътя до архива
        if (is_object($fRec)) {
            $filePath = fileman_Files::fetchByFh($fRec->fileHnd, 'path');
        } else {
            $filePath = $fRec;
        }

        if (!$filePath || !is_file($filePath)) {

            return false;
        }

        $zip = new ZipArchive();

        // Ако архивът не може да се отвори, няма да може и markitdown
        if ($zip->open($filePath, ZipArchive::CHECKCONS) !== true) {

            return false;
        }

        $maxFilesCnt = markitdown_Setup::get('MAX_ARCHIVE_FILES');
        $maxContentLen = markitdown_Setup::get('MAX_ARCHIVE_CONTENT_LEN');

        $filesCnt = 0;
        $contentLen = 0;
        $res = true;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);

            if ($stat === false) {
                continue;
            }

            // Директориите не се броят
            if (substr($stat['name'], -1) == '/') {
                continue;
            }

            // Криптираните архиви не могат да се обработят
            if (!empty($stat['encryption_method'])) {
                $res = false;
                break;
            }

            // Вложените архиви се разгъват рекурсивно и не може да се предвиди
            // колко ще станат след разархивиране
            $entryExt = strtolower(fileman_Files::getExt($stat['name']));
            if (isset(static::$archiveExtArr[$entryExt])) {
                $res = false;
                break;
            }

            $filesCnt++;
            $contentLen += (int) $stat['size'];

            // Твърде много файлове в архива
            if ($maxFilesCnt && ($filesCnt > $maxFilesCnt)) {
                $res = false;
                break;
            }

            // Твърде голям размер след разархивиране - защита от "бомби"
            if ($maxContentLen && ($contentLen > $maxContentLen)) {
                $res = false;
                break;
            }
        }

        $zip->close();

        return $res;
    }


    /**
     * Връща размера на файла
     *
     * @param stdClass|string $fRec
     *
     * @return int|NULL
     */
    protected static function getFileLen($fRec)
    {
        if (is_object($fRec)) {

            return $fRec->fileLen ?? null;
        }

        return is_file($fRec) ? filesize($fRec) : null;
    }


    /**
     * Връща масив с разширенията, от които ще се извлича съдържание
     *
     * @return array
     */
    public static function getAllowedExtArr()
    {
        $extArr = arr::make(strtolower(markitdown_Setup::get('EXTENSIONS')), true);

        return $extArr;
    }


    /**
     * Връща id' то на класа в core_Classes
     *
     * Чете се направо от модела, а не с `core_Classes::getId()`, защото кешът на класовете
     * още не знае за класа, когато това се вика веднага след регистрацията му (при инсталация)
     *
     * @return int|NULL
     */
    public static function getClassId()
    {
        return core_Classes::fetchField(array("#name = '[#1#]'", get_called_class()), 'id');
    }


    /**
     * След началното установяване на този мениджър
     */
    public static function loadSetupData()
    {
        // Вземаме конфига на fileman
        $conf = core_Packs::getConfig('fileman');

        // Ако вече има избрана програма, не я подменяме
        if (!empty($conf->_data['FILEMAN_MARKDOWN'])) {

            return ;
        }

        $classId = static::getClassId();

        if (!$classId) {

            return ;
        }

        // Да се използва този клас
        core_Packs::setConfig('fileman', array('FILEMAN_MARKDOWN' => $classId));

        return "<li class='debug-new'>Избран е '" . get_called_class() . "' за извличане на съдържанието в markdown</li>";
    }
}
