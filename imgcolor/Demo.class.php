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
        $form->FLD('imageFile', 'fileman_FileType(bucket=imgcolorImages)', 'caption=Изображение,mandatory');

        $form->input();

        $resultHtml = '';
        if ($form->isSubmitted()) {
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
                $result = imgcolor_Analyzer::process($bytes);
                $resultHtml = self::renderResult($result);
            } catch (core_exception_Expect $e) {
                $resultHtml = self::renderError($e);
            }
        }

        $form->toolbar->addSbBtn('Анализирай', 'save', 'ef_icon=img/16/color_swatch_1.png');

        $tpl = getTplFromFile('imgcolor/tpl/Demo.shtml');
        $tpl->append($form->renderHtml(), 'FORM');
        $tpl->append($resultHtml, 'RESULT');

        return $this->renderWrapping($tpl);
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
            $result = imgcolor_Analyzer::process(fileman::extractStr($fh));
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
     *
     * @return string
     */
    public static function renderColorsHtml($colorsJson, $croppedImageBytes = null)
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
            . '</div>';
    }


    /**
     * Рендира резултата от imgcolor_Analyzer::process() - живия преглед.
     *
     * @param \ImageColorAnalyzer\PublicAPI\ProcessedImageResult $result
     *
     * @return string
     */
    public static function renderResult($result)
    {
        $croppedBytes = null;
        if (is_object($result->croppedImage) && is_string($result->croppedImage->bytes) && $result->croppedImage->bytes !== '') {
            $croppedBytes = $result->croppedImage->bytes;
        }

        return self::renderColorsHtml($result->json, $croppedBytes);
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
