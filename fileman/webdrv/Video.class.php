<?php


/**
 * Драйвер за работа с видео файлове.
 *
 * @category  vendors
 * @package   fileman
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fileman_webdrv_Video extends fileman_webdrv_Media
{
    /**
     * Кой таб да е избран по подразбиране
     *
     * @Override
     *
     * @see fileman_webdrv_Generic::$defaultTab
     */
    public static $defaultTab = 'video';


    /**
     * Към кое разширение да се конвертира
     *
     * @var string
     */
    public static $toExt = 'mp4';


    /**
     * Конвертиране на видео с хардуерно ускорение (ако е налично)
     * и налагане на ограничения за резолюция, FPS, битрейт, H.264 main профил.
     * Резултатът е максимално съвместим с повечето браузъри и по-слаби компютри.
     *
     * @param $fRec
     * @param $force
     * @param $asynch
     * @return false|string
     * @throws core_exception_Expect
     */
    public static function convertVideo($fRec, $force = false, $asynch = false)
    {
        expect(defined('FILEMAN_FFMPEG_CONVERTER_PATH'), 'Не е дефиниран пътя до конвертора на видео файлове - FILEMAN_FFMPEG_CONVERTER_PATH');

        self::startVideoConverting($fRec, $asynch, $force);

        $fileHnd = fileman_Indexes::getInfoContentByFh($fRec->fileHnd, self::getType($fRec));

        if (is_string($fileHnd)) {

            return $fileHnd;
        }

        return false;
    }


    /**
     * Стартира извличането на информациите за файла
     *
     * @param object $fRec - Записите за файла
     *
     * @Override
     *
     * @see fileman_webdrv_Image::startProcessing
     */
    public static function startProcessing($fRec)
    {
        parent::startProcessing($fRec);
        static::startVideoConverting($fRec);
    }


    /**
     * Връща всички табове, които ги има за съответния файл
     *
     * @param object $fRec - Записите за файла
     *
     * @return array
     *
     * @Override
     *
     * @see fileman_webdrv_Generic::getTabs
     */
    public static function getTabs($fRec)
    {
        // Вземаме табовете от родителя
        $tabsArr = parent::getTabs($fRec);

        // Подготвяме стрелките
        $resArray = self::getArrows($fRec);
        $prevLink = $resArray['prevLink'];
        $nextLink = $resArray['nextLink'];

        if (defined('FILEMAN_FFMPEG_CONVERTER_PATH')) {
            $previewUrl = toUrl(array(get_called_class(), 'preview', $fRec->fileHnd));
            $previewHtml = "<div class='webdrvTabBody'><div class='webdrvFieldset'>{$prevLink}{$nextLink} <iframe src='{$previewUrl}' frameBorder='0' ALLOWTRANSPARENCY='true' class='webdrvIframe' id='imgIframe'></iframe></div></div>";
        } else {
            // Шаблона за видеото
            $videoTpl = self::getVideoTpl($fRec->fileHnd);
            $previewHtml = "<div class='webdrvTabBody' style='white-space:pre-wrap;'><div class='webdrvFieldset'>{$prevLink}{$nextLink}{$videoTpl}</div></div>";
        }

        // Таб за съдържанието
        $tabsArr['video'] = (object)
        array(
            'title' => 'Видео',
            'html' => $previewHtml,
            'order' => 2,
            'tpl' => $videoTpl,
        );

        return $tabsArr;
    }


    /**
     * Екшън за показване превю
     */
    public function act_Preview()
    {
        // Очакваме да има права за виждане
        $this->requireRightFor('view');

        // Манипулатора на файла
        $fileHnd = Request::get('id');

        if (!$fileHnd) {
            $fileHnd = Request::get('fileHnd');
        }

        expect($fileHnd);

        // Вземаме записа за файла
        $fRec = fileman_Files::fetchByFh($fileHnd);

        expect($fRec);

        // Очакваме да има права за разглеждане на записа
        $this->requireRightFor('view', $fRec);

        // Вземаме масива с изображенията
        $fileHnd = fileman_Indexes::getInfoContentByFh($fileHnd, self::getType($fRec));

        // Ако няма такъв запис
        if ($fileHnd === false) {

            // Сменяме мода на page_Waiting
            Mode::set('wrapper', 'page_Waiting');

            return ;
        }

        // Ако е обект и има съобщение за грешка
        if (is_object($fileHnd) && $fileHnd->errorProc) {

            // Сменяме мода
            Mode::set('wrapper', 'page_PreText');

            // Връщаме съобщението за грешка
            return tr($fileHnd->errorProc);
        }

        // Сменяма wrapper'а да е празна страница
        Mode::set('wrapper', 'page_Empty');

        return $this->getVideoTpl($fileHnd);
    }


    /**
     * Помощна фунцкия за връщане на шаблона за видео
     *
     * @param string $fileHnd
     * @return core_Et|ET
     */
    protected static function getVideoTpl($fileHnd)
    {
        // Определяме широчината на видеото в зависимост от мода
        if (mode::is('screenMode', 'narrow')) {
            $width = 567;
            $height = 400;
        } else {
            $width = 868;
            $height = 500;
        }

        return mejs_Adapter::createVideo($fileHnd, array('width' => $width, 'height' => $height));
    }
    

    /**
     * Получава управелението след приключване на конвертирането.
     *
     * @param fconv_Script $script - Парамтри
     *
     * @return bool
     */
    public static function afterConvertVideo($script)
    {
        // Десериализираме нужните помощни данни
        $params = unserialize($script->params);

        // Проверяваме дали е имало грешка при предишното конвертиране
        if (fileman_Indexes::haveErrors($script->outFilePath, $params)) {

            // Отключваме процеса
            core_Locks::release($params['lockId']);

            return false;
        }

        // Ако възникне грешка при качването на файла (липса на права)
        try {

            // Качваме файла в кофата и му вземаме манипулатора
            $fileHnd = fileman::absorb($script->outFilePath, 'fileIndex');
        } catch (core_exception_Expect $e) {

            // Създаваме запис в модела за грешка
            fileman_Indexes::createError($params);

            // Записваме грешката в лога
            fileman_Indexes::createErrorLog($params['dataId'], $params['type']);
        }

        // Ако се качи успешно записваме манипулатора в масив
        if ($fileHnd) {

            // Текстовата част
            $params['content'] = $fileHnd;

            // Обновяваме данните за запис във fileman_Indexes
            $savedId = fileman_Indexes::saveContent($params);
        }

        // Отключваме процеса
        core_Locks::release($params['lockId']);

        if ($savedId) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове
            // и записа от таблицата fconv_Process
            return true;
        }
    }


    /**
     * Помощна функция за връщане на типа на изходния файл
     *
     * @param stdClass $fRec
     *
     * @return string
     */
    protected static function getType($fRec)
    {
        $toExt = static::$toExt ? static::$toExt : fileman::getExt($fRec->name);

        return 'conv_' . $toExt;
    }


    /**
     * Конвертиране на видео с хардуерно ускорение (ако е налично)
     * и налагане на ограничения за резолюция, FPS, битрейт, H.264 main профил.
     * Резултатът е максимално съвместим с повечето браузъри и по-слаби компютри.
     *
     * @param object $fRec     - Записите за файла
     * @param bool   $asynch    - Стартиране на процеса асинхронно
     * @param bool   $force     - Принудително стартиране на процеса
     */
    public static function startVideoConverting($fRec, $asynch = true, $force = false)
    {
        if (!defined('FILEMAN_FFMPEG_CONVERTER_PATH')) {

            return ;
        }

        $type = self::getType($fRec);

        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'fileman_webdrv_Video::afterConvertVideo',
            'dataId' => $fRec->dataId,
            'asynch' => $asynch,
            'createdBy' => core_Users::getCurrent(),
            'type' => $type,
        );

        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $fRec->dataId);

        // Проверявама дали няма извлечена информация или не е заключен
        if (!$force && fileman_Indexes::isProcessStarted($params)) {

            return ;
        }

        // Заключваме процеса за определено време
        if ($force || core_Locks::get($params['lockId'], 300, 0, false)) {
            // Конвертираме видеото
            static::startVideConverting($fRec, $params);
        }
    }


    /**
     * Стартира конвертирането
     *
     * @param object $fRec   - Записите за файла
     * @param array  $params - Допълнителни параметри
     */
    public static function startVideConverting($fRec, $params)
    {
        if (!defined('FILEMAN_FFMPEG_CONVERTER_PATH')) {

            return ;
        }

        // Инстанция на класа
        $Script = cls::get('fconv_Script');
        $Script->setParam('FFMPEG_CONVERTER', FILEMAN_FFMPEG_CONVERTER_PATH);

        $nameExtArr = fileman::getNameAndExt($fRec->name);
        $toExt = static::$toExt ? static::$toExt : $nameExtArr['ext'];

        // Задаваме пътя до изходния файла
        $outFilePath = $Script->tempDir . '' . $nameExtArr['name'] . '-conv.' . $toExt;

        // Задаваме placeHolder' ите за входния и изходния файл
        $Script->setFile('INPUTF', $fRec->fileHnd);
        $Script->setFile('OUTPUTF', $outFilePath);

        $errFilePath = self::getErrLogFilePath($outFilePath);

        $Script->lineExec('[#FFMPEG_CONVERTER#] [#INPUTF#] [#OUTPUTF#]', array('errFilePath' => $errFilePath));

        // Функцията, която ще се извика след приключване на обработката на файла
        $Script->callBack($params['callBack']);

        $params['errFilePath'] = $errFilePath;

        // Други необходими променливи
        $Script->params = serialize($params);
        $Script->fName = $fRec->name;
        $Script->outFilePath = $outFilePath;
        $Script->fh = $fRec->fileHnd;

        $Script->setCheckProgramsArr(FILEMAN_FFMPEG_CONVERTER_PATH);

        // Ако е подаден параметър за стартиране синхронно
        if ($Script->run($params['asynch']) === false) {
            fileman_Indexes::createError($params);
        }
    }
}
