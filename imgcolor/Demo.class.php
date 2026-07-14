<?php


/**
 * Тестов интерфейс и fileman действие за анализ на цветове за печат.
 *
 * @category  bgerp
 * @package   imgcolor
 *
 * @license   GPL 3
 * @since     v 0.1
 */
class imgcolor_Demo extends core_Manager
{
    /**
     * Интерфейси
     */
    public $interfaces = 'fileman_FileActionsIntf';


    /**
     * Заглавие
     */
    public $title = 'Анализ на цветове за печат';


    /**
     * Права
     */
    public $canAnalyze = 'imgcolor, ceo, admin';
    public $canAnalyzecolors = 'imgcolor, ceo, admin';


    /**
     * Няма таблица - само контролер
     */
    public function description()
    {
    }


    /**
     * Входна точка към формата за качване
     */
    public function act_Default()
    {
        $this->requireRightFor('analyze');

        return new Redirect(array($this->className, 'analyze'));
    }


    /**
     * Форма за качване на изображение и преглед на резултата
     */
    public function act_Analyze()
    {
        $this->requireRightFor('analyze');

        $form = $this->getForm();
        $form->title = 'Анализ на цветове за печат';
        $form->FLD('imageFile', 'fileman_FileType(bucket=imgcolorImages)', 'caption=Източник->Изображение');
        $refreshFields = implode('|', imgcolor_Calibration::$fields);
        $form->FLD('profileId', 'key(mvc=imgcolor_Profiles, select=name, allowEmpty)', "caption=Източник->Профил на калибриране,placeholder=Глобална конфигурация,silent,removeAndRefreshForm={$refreshFields}");
        self::addCalibrationFields($form);
        $form->FLD('newProfileSysId', 'varchar(16)', 'caption=Нов профил->Съкратено име');
        $form->FLD('newProfileName', 'varchar(64)', 'caption=Нов профил->Име');

        $form->input('profileId', 'silent');

        $profileRec = null;
        if (!empty($form->rec->profileId)) {
            $profileRec = imgcolor_Profiles::fetchRec($form->rec->profileId);
            if (!$profileRec || !imgcolor_Profiles::haveRightFor('single', $profileRec)) {
                $form->setError('profileId', 'Избраният профил липсва или не е достъпен');
                $profileRec = null;
            }
        }

        $initialValues = $profileRec ? imgcolor_Calibration::getValues($profileRec) : imgcolor_Calibration::getDefaultValues();
        foreach ($initialValues as $field => $value) {
            $form->setDefault($field, $value);
        }
        $form->input();

        $resultHtml = '';
        $calibrationValues = null;
        $options = null;

        if ($form->cmd && $form->cmd != 'refresh' && !$form->gotErrors()) {
            try {
                $calibrationValues = imgcolor_Calibration::getValues($form->rec);
                $options = imgcolor_Calibration::buildOptions($calibrationValues);
            } catch (InvalidArgumentException $e) {
                $form->setError(implode(',', imgcolor_Calibration::$fields), 'Некоректни настройки за калибриране: ' . $e->getMessage());
            }
        }

        if ($form->cmd == 'analyze' && empty($form->rec->imageFile)) {
            $form->setError('imageFile', 'Изберете изображение за анализ');
        }

        if ($form->cmd == 'saveprofile') {
            if (!imgcolor_Profiles::haveRightFor('add')) {
                $form->setError('newProfileSysId,newProfileName', 'Нямате права за създаване на профил');
            }
            if (trim((string) ($form->rec->newProfileSysId ?? '')) === '') {
                $form->setError('newProfileSysId', 'Въведете съкратено име на профила');
            } elseif (imgcolor_Profiles::fetch(array("#sysId = '[#1#]'", $form->rec->newProfileSysId))) {
                $form->setError('newProfileSysId', 'Вече съществува профил с това съкратено име');
            }
            if (trim((string) ($form->rec->newProfileName ?? '')) === '') {
                $form->setError('newProfileName', 'Въведете име на профила');
            }
        }

        if ($form->cmd == 'updateprofile') {
            if (!$profileRec) {
                $form->setError('profileId', 'Изберете профил за обновяване');
            } elseif (!imgcolor_Profiles::haveRightFor('edit', $profileRec)) {
                $form->setError('profileId', 'Нямате права за редактиране на избрания профил');
            }
        }

        if ($form->isSubmitted()) {
            if ($form->cmd == 'saveprofile') {
                $newProfile = new stdClass();
                $newProfile->sysId = $form->rec->newProfileSysId;
                $newProfile->name = $form->rec->newProfileName;
                imgcolor_Calibration::applyValues($newProfile, $calibrationValues);

                try {
                    imgcolor_Profiles::save($newProfile);
                } catch (core_exception_Expect $e) {
                    $form->setError('newProfileSysId,newProfileName', $e->getMessage());
                }

                if (!$form->gotErrors()) {
                    return new Redirect(array($this->className, 'analyze', 'profileId' => $newProfile->id), 'Профилът е записан');
                }
            } elseif ($form->cmd == 'updateprofile') {
                imgcolor_Calibration::applyValues($profileRec, $calibrationValues);

                try {
                    imgcolor_Profiles::save($profileRec, implode(',', imgcolor_Calibration::$fields));
                } catch (core_exception_Expect $e) {
                    $form->setError(implode(',', imgcolor_Calibration::$fields), $e->getMessage());
                }

                if (!$form->gotErrors()) {
                    return new Redirect(array($this->className, 'analyze', 'profileId' => $profileRec->id), 'Профилът е обновен');
                }
            } elseif ($form->cmd == 'analyze') {
                try {
                    $fRec = fileman_Files::fetchByFh($form->rec->imageFile);
                    if (!$fRec) {
                        throw new core_exception_Expect('imgcolor: липсва качения файл', 'Несъответствие');
                    }
                    fileman_Files::requireRightFor('single', $fRec);

                    if (!self::canAnalyzeFile($fRec)) {
                        throw new core_exception_Expect('imgcolor: поддържат се само PNG/JPEG изображения', 'Несъответствие');
                    }

                    $bytes = fileman::extractStr($form->rec->imageFile);
                    $result = imgcolor_Analyzer::processSeparated($bytes, $options, imgcolor_Analyzer::getTransParams($profileRec));
                    $resultHtml = self::renderResult($result);

                    self::persistResult($form->rec->imageFile, $form->rec->profileId, $result, $calibrationValues);
                } catch (core_exception_Expect $e) {
                    $form->setError('imageFile', $e->getMessage());
                }
            }
        }

        $form->toolbar->addSbBtn('Анализирай', 'analyze', 'ef_icon=img/16/color_swatch_1.png');
        if (imgcolor_Profiles::haveRightFor('add')) {
            $form->toolbar->addSbBtn('Запази като профил', 'saveprofile', 'ef_icon=img/16/disk.png');
        }
        if ($profileRec && imgcolor_Profiles::haveRightFor('edit', $profileRec)) {
            $form->toolbar->addSbBtn('Обнови профила', 'updateprofile', 'ef_icon=img/16/edit.png');
        }
        if (imgcolor_Profiles::haveRightFor('list')) {
            $form->toolbar->addBtn('Профили', array('imgcolor_Profiles', 'list', 'ret_url' => true), 'ef_icon=img/16/profile.png');
        }

        $tpl = getTplFromFile('imgcolor/tpl/Demo.shtml');
        $tpl->append($form->renderHtml(), 'FORM');
        $tpl->append($resultHtml, 'RESULT');

        return $this->renderWrapping($tpl);
    }


    /**
     * Добавя полетата за настройка на калибрирането към формата за анализ.
     *
     * @param core_Form $form
     */
    private static function addCalibrationFields($form)
    {
        $form->FLD('cropLightnessMin', 'double', 'caption=Изрязване->Мин. светлота (L*),mandatory');
        $form->FLD('cropChromaMax', 'double', 'caption=Изрязване->Макс. насищане (chroma),mandatory');
        $form->FLD('cropLineContentFraction', 'double', 'caption=Изрязване->Праг съдържание на линия,mandatory');
        $form->FLD('cropAlphaThreshold', 'int', 'caption=Изрязване->Праг прозрачност (0-255),mandatory');

        $form->FLD('clusterFixedK', 'int', 'caption=Клъстеризиране->Фиксиран брой цветове (празно=авто),allowEmpty');
        $form->FLD('clusterKMax', 'int', 'caption=Клъстеризиране->Макс. брой цветове,mandatory');
        $form->FLD('clusterHistogramBits', 'int', 'caption=Клъстеризиране->Резолюция хистограма (битове/канал),mandatory');
        $form->FLD('clusterMergeDeltaE', 'double', 'caption=Клъстеризиране->Сливане при deltaE под,mandatory');
        $form->FLD('clusterMinCoverage', 'double', 'caption=Клъстеризиране->Мин. покривност на клъстер,mandatory');
        $form->FLD('clusterSeed', 'int', 'caption=Клъстеризиране->Seed (детерминизъм),mandatory');
        $form->FLD('clusterAlphaThreshold', 'int', 'caption=Клъстеризиране->Праг прозрачност (0-255),mandatory');
    }


    /**
     * fileman действие: анализ на цветове върху вече качен файл
     */
    public function act_AnalyzeColors()
    {
        $this->requireRightFor('analyzecolors');

        $fh = Request::get('id');
        $fRec = fileman_Files::fetchByFh($fh);
        if (!$fRec) {
            $error = new core_exception_Expect('imgcolor: липсва файл за подадения handle', 'Несъответствие');

            return $this->renderWrapping(self::renderError($error));
        }

        fileman_Files::requireRightFor('single', $fRec);

        if (!self::canAnalyzeFile($fRec)) {
            $error = new core_exception_Expect('imgcolor: поддържат се само PNG/JPEG изображения', 'Несъответствие');

            return $this->renderWrapping(self::renderError($error));
        }

        try {
            $calibrationValues = imgcolor_Calibration::getDefaultValues();
            $result = imgcolor_Analyzer::processSeparated(fileman::extractStr($fh), imgcolor_Calibration::buildOptions($calibrationValues));
            self::persistResult($fh, null, $result, $calibrationValues);
        } catch (core_exception_Expect $e) {

            return $this->renderWrapping(self::renderError($e));
        }

        return $this->renderWrapping(self::renderResult($result));
    }


    /**
     * Интерфейсен метод на fileman_FileActionsIntf - бутон върху файла
     *
     * @param stdClass $fRec
     *
     * @return array|NULL
     */
    public static function getActionsForFile_($fRec)
    {
        $arr = null;

        if (self::haveRightFor('analyzecolors') && self::canAnalyzeFile($fRec)) {
            $btnParams = array();
            $btnParams['order'] = 80;
            $btnParams['title'] = 'Анализ на основните цветове за печат';

            $arr = array();
            $arr['imgcolor']['url'] = array(get_called_class(), 'analyzeColors', $fRec->fileHnd, 'ret_url' => true);
            $arr['imgcolor']['title'] = 'Цветове за печат';
            $arr['imgcolor']['icon'] = 'img/16/color_swatch_1.png';
            $arr['imgcolor']['btnParams'] = $btnParams;
        }

        return $arr;
    }


    /**
     * Може ли файлът да бъде анализиран (по разширение)
     *
     * @param stdClass|string $fRec
     *
     * @return bool
     */
    public static function canAnalyzeFile($fRec)
    {
        return imgcolor_Analyzer::canAnalyzeFile($fRec);
    }


    /**
     * Рендира резултата: изрязано изображение + цветови мостри. Споделено
     * между живия преглед (renderResult()) и запазените резултати
     * (imgcolor_Analyses::renderRec()), за да не се дублира HTML логиката.
     *
     * @param string      $colorsJson        JSON резултат ([{color, coverage_percent}, ...])
     * @param string|null $croppedImageBytes суровите байтове на изрязаното PNG, ако има
     * @param string|null $cmykJson          CMYK резултат за преливките, ако има такива
     *
     * @return string
     */
    public static function renderColorsHtml($colorsJson, $croppedImageBytes = null, $cmykJson = null)
    {
        $colors = json_decode($colorsJson, true);
        if (!is_array($colors)) {
            $colors = array();
        }

        $swatches = '';
        foreach ($colors as $c) {
            $hex = preg_replace('/[^#0-9A-Fa-f]/', '', (string) $c['color']);
            $pct = (float) $c['coverage_percent'];
            $swatches .= "<div style='display:flex;align-items:center;gap:8px;margin:2px 0'>"
                . "<span style='display:inline-block;width:20px;height:20px;border:1px solid #999;background:{$hex}'></span>"
                . "<code>{$hex}</code> - {$pct}%</div>";
        }

        $imgTag = '';
        if (is_string($croppedImageBytes) && $croppedImageBytes !== '') {
            $b64 = base64_encode($croppedImageBytes);
            $imgTag = "<div><img alt='cropped' style='max-width:320px;border:1px solid #ccc' src='data:image/png;base64,{$b64}'/></div>";
        }

        return "<div style='display:flex;gap:24px;flex-wrap:wrap'>"
            . "<div>{$imgTag}</div>"
            . "<div>{$swatches}</div>"
            . self::renderCmykHtml($cmykJson)
            . '</div>';
    }


    /**
     * Рендира CMYK блока за преливките: покритие, състав на мастилата
     * (сумиращ точно 100%) и използвания енджин за конверсия.
     *
     * @param string|null $cmykJson
     *
     * @return string празен низ, когато няма преливки
     */
    public static function renderCmykHtml($cmykJson)
    {
        if (!is_string($cmykJson) || $cmykJson === '') {

            return '';
        }

        $cmyk = json_decode($cmykJson, true);
        if (!is_array($cmyk) || !isset($cmyk['composition_percent'])) {

            return '';
        }

        $channels = array(
            'c' => array('Cyan', '#00AEEF'),
            'm' => array('Magenta', '#EC008C'),
            'y' => array('Yellow', '#FFF200'),
            'k' => array('Black', '#231F20'),
        );

        $bars = '';
        foreach ($channels as $ch => $def) {
            $pct = isset($cmyk['composition_percent'][$ch]) ? (float) $cmyk['composition_percent'][$ch] : 0.0;
            $width = (int) round($pct * 2);
            $bars .= "<div style='display:flex;align-items:center;gap:8px;margin:2px 0'>"
                . "<span style='display:inline-block;width:64px'>{$def[0]}</span>"
                . "<span style='display:inline-block;width:{$width}px;height:14px;background:{$def[1]};border:1px solid #999'></span>"
                . "<code>{$pct}%</code></div>";
        }

        $coverage = isset($cmyk['transition_coverage_percent']) ? (float) $cmyk['transition_coverage_percent'] : 0.0;
        $engine = isset($cmyk['conversion']['engine']) ? htmlspecialchars((string) $cmyk['conversion']['engine'], ENT_QUOTES, 'UTF-8') : '?';
        $note = tr('Преливки (CMYK)') . ": {$coverage}% " . tr('от анализираната площ');
        if (isset($cmyk['ink_total']) && (float) $cmyk['ink_total'] == 0.0) {
            $note .= ' - ' . tr('без мастилено съдържание');
        }

        return "<div><b>{$note}</b>{$bars}"
            . "<div style='color:#777;font-size:0.9em'>" . tr('Конверсия') . ": <code>{$engine}</code></div></div>";
    }


    /**
     * Рендира резултата от imgcolor_Analyzer::processSeparated() (или
     * legacy process()) - живия преглед.
     *
     * @param stdClass|\ImageColorAnalyzer\PublicAPI\ProcessedImageResult $result
     *
     * @return string
     */
    public static function renderResult($result)
    {
        $croppedBytes = null;
        if (is_object($result->croppedImage) && is_string($result->croppedImage->bytes) && $result->croppedImage->bytes !== '') {
            $croppedBytes = $result->croppedImage->bytes;
        }

        return self::renderColorsHtml($result->json, $croppedBytes, self::extractCmykJson($result));
    }


    /**
     * Записва завършен анализ в imgcolor_Analyses за бъдещо преизползване/справка.
     * Публичен метод, за да е тестваем директно (виж imgcolor_tests_Analyses).
     *
     * @param string                                                      $imageFh   fileman handle на изходния файл
     * @param int|null                                                    $profileId избран профил, или празно за глобална конфигурация
     * @param stdClass|\ImageColorAnalyzer\PublicAPI\ProcessedImageResult $result
     * @param array|null                                                  $calibrationValues действително използваните стойности
     */
    public static function persistResult($imageFh, $profileId, $result, $calibrationValues = null)
    {
        $croppedFh = null;
        if (is_object($result->croppedImage) && is_string($result->croppedImage->bytes) && $result->croppedImage->bytes !== '') {
            $croppedFh = fileman::absorbStr($result->croppedImage->bytes, 'imgcolorImages', 'cropped.png');
        }

        imgcolor_Analyses::createFromResult($imageFh, $profileId, $result->json, $croppedFh, $calibrationValues, self::extractCmykJson($result));
    }


    /**
     * CMYK JSON от резултат на processSeparated(); legacy process()
     * резултатите нямат такова поле.
     *
     * @param object $result
     *
     * @return string|null
     */
    private static function extractCmykJson($result)
    {
        return (isset($result->cmykJson) && is_string($result->cmykJson)) ? $result->cmykJson : null;
    }


    /**
     * Рендира грешка от анализа в UI контекст.
     *
     * @param core_exception_Expect $e
     *
     * @return string
     */
    private static function renderError($e)
    {
        $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');

        return "<div class='formError'>{$msg}</div>";
    }
}
