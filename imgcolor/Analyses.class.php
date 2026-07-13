<?php


/**
 * Запазени резултати от анализ на цветове - за преизползване и справка.
 * Записите се създават програмно от imgcolor_Demo след успешен анализ;
 * няма ръчна форма за добавяне (canAdd = no_one).
 *
 * @category  bgerp
 * @package   imgcolor
 *
 * @license   GPL 3
 * @since     v 0.2
 */
class imgcolor_Analyses extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'История на анализите на цветове за печат';


    /**
     * Полета в листовия изглед
     */
    public $listFields = 'imageFile, profileId';


    /**
     * Плъгини за зареждане - createdOn/createdBy за справка
     */
    public $loadList = 'plg_Created';


    /**
     * Права - записите се създават само програмно
     */
    public $canRead = 'imgcolor, ceo, admin';
    public $canList = 'imgcolor, ceo, admin';
    public $canSingle = 'imgcolor, ceo, admin';
    public $canAdd = 'no_one';
    public $canEdit = 'no_one';
    public $canDelete = 'ceo, admin';


    /**
     * Описание на модела (таблицата)
     */
    protected function description()
    {
        $this->FLD('imageFile', 'fileman_FileType(bucket=imgcolorImages)', 'caption=Изходно изображение');
        $this->FLD('profileId', 'key(mvc=imgcolor_Profiles, select=name, allowEmpty)', 'caption=Профил');
        $this->FLD('colorsJson', 'text', 'caption=Резултат (JSON),input=none');
        $this->FLD('calibrationJson', 'text', 'caption=Калибриране (JSON),input=none');
        $this->FLD('croppedImage', 'fileman_FileType(bucket=imgcolorImages)', 'caption=Изрязано изображение,allowEmpty');
    }


    /**
     * Записва резултат от анализ за бъдещо преизползване/справка.
     *
     * @param string      $imageFh    fileman handle на изходния файл
     * @param int|null    $profileId  избран профил, или null/0 за глобална конфигурация
     * @param string      $colorsJson JSON резултат от imgcolor_Analyzer
     * @param string|null $croppedFh  fileman handle на изрязаното изображение, ако е запазено
     * @param array|null  $calibrationValues действително използваните стойности за калибриране
     *
     * @return int id на новия запис
     */
    public static function createFromResult($imageFh, $profileId, $colorsJson, $croppedFh = null, $calibrationValues = null)
    {
        $rec = new stdClass();
        $rec->imageFile = $imageFh;
        $rec->profileId = $profileId ?: null;
        $rec->colorsJson = $colorsJson;
        $rec->calibrationJson = $calibrationValues === null ? null : json_encode(imgcolor_Calibration::getValues($calibrationValues), JSON_PRESERVE_ZERO_FRACTION);
        $rec->croppedImage = $croppedFh;

        self::save($rec);

        return $rec->id;
    }


    /**
     * Рендира запазен резултат по същия начин като живия преглед в imgcolor_Demo.
     *
     * @param stdClass $rec
     *
     * @return string
     */
    public static function renderRec($rec)
    {
        $croppedBytes = null;
        if (!empty($rec->croppedImage)) {
            $croppedBytes = fileman::extractStr($rec->croppedImage);
        }

        return imgcolor_Demo::renderColorsHtml($rec->colorsJson, $croppedBytes);
    }
}
