<?php


/**
 * Именувани, преизползваеми набори от прагове за калибриране на
 * imgcolor_Analyzer. Глобалните IMGCOLOR_* константи (imgcolor_Setup)
 * остават дефолтният, нулево-конфигуриран режим - профил е явен, опционален
 * override (виж imgcolor_Analyzer::buildOptions()).
 *
 * @category  bgerp
 * @package   imgcolor
 *
 * @license   GPL 3
 * @since     v 0.2
 */
class imgcolor_Profiles extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Профили за калибриране на цветови анализ';


    /**
     * Полета в листовия изглед
     */
    public $listFields = 'sysId, name, clusterKMax, clusterFixedK';


    /**
     * Права
     */
    public $canRead = 'imgcolor, ceo, admin';
    public $canAdd = 'imgcolor, ceo, admin';
    public $canEdit = 'imgcolor, ceo, admin';
    public $canList = 'imgcolor, ceo, admin';
    public $canSingle = 'imgcolor, ceo, admin';
    public $canDelete = 'imgcolor, ceo, admin';


    /**
     * Описание на модела (таблицата)
     */
    protected function description()
    {
        $this->FLD('sysId', 'varchar(16)', 'caption=Име->Съкратено,mandatory');
        $this->FLD('name', 'varchar(64)', 'caption=Име->Детайлно,mandatory');

        $this->FLD('cropLightnessMin', 'double', 'caption=Изрязване->Мин. светлота (L*),mandatory');
        $this->FLD('cropChromaMax', 'double', 'caption=Изрязване->Макс. насищане (chroma),mandatory');
        $this->FLD('cropLineContentFraction', 'double', 'caption=Изрязване->Праг съдържание на линия,mandatory');
        $this->FLD('cropAlphaThreshold', 'int', 'caption=Изрязване->Праг прозрачност (0-255),mandatory');

        $this->FLD('clusterFixedK', 'int', 'caption=Клъстеризиране->Фиксиран брой цветове (празно=авто),allowEmpty');
        $this->FLD('clusterKMax', 'int', 'caption=Клъстеризиране->Макс. брой цветове,mandatory');
        $this->FLD('clusterHistogramBits', 'int', 'caption=Клъстеризиране->Резолюция хистограма (битове/канал),mandatory');
        $this->FLD('clusterMergeDeltaE', 'double', 'caption=Клъстеризиране->Сливане при deltaE под,mandatory');
        $this->FLD('clusterMinCoverage', 'double', 'caption=Клъстеризиране->Мин. покривност на клъстер,mandatory');
        $this->FLD('clusterSeed', 'int', 'caption=Клъстеризиране->Seed (детерминизъм),mandatory');
        $this->FLD('clusterAlphaThreshold', 'int', 'caption=Клъстеризиране->Праг прозрачност (0-255),mandatory');

        $this->FLD('notes', 'richtext', 'caption=Бележки');

        $this->setDbUnique('sysId');
    }


    /**
     * При добавяне на нов профил, попълва праговете с текущите глобални
     * IMGCOLOR_* стойности - редакцията на съществуващ профил не се пипа.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

        if (empty($rec->id)) {
            $rec->cropLightnessMin = imgcolor_Setup::get('CROP_LIGHTNESS_MIN');
            $rec->cropChromaMax = imgcolor_Setup::get('CROP_CHROMA_MAX');
            $rec->cropLineContentFraction = imgcolor_Setup::get('CROP_LINE_CONTENT_FRACTION');
            $rec->cropAlphaThreshold = imgcolor_Setup::get('CROP_ALPHA_THRESHOLD');
            $rec->clusterFixedK = imgcolor_Setup::get('CLUSTER_FIXED_K');
            $rec->clusterKMax = imgcolor_Setup::get('CLUSTER_KMAX');
            $rec->clusterHistogramBits = imgcolor_Setup::get('CLUSTER_HISTOGRAM_BITS');
            $rec->clusterMergeDeltaE = imgcolor_Setup::get('CLUSTER_MERGE_DELTAE');
            $rec->clusterMinCoverage = imgcolor_Setup::get('CLUSTER_MIN_COVERAGE');
            $rec->clusterSeed = imgcolor_Setup::get('CLUSTER_SEED');
            $rec->clusterAlphaThreshold = imgcolor_Setup::get('CLUSTER_ALPHA_THRESHOLD');
        }
    }


    /**
     * Отхвърля профили с прагове извън допустимите диапазони на библиотеката,
     * със същия стил грешка като imgcolor_Analyzer/imgcolor_Demo.
     *
     * @param core_Mvc     $mvc
     * @param int          $id
     * @param stdClass     $rec
     * @param string|array $fields
     * @param string       $mode
     */
    public static function on_BeforeSave($mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        $values = array();
        foreach (imgcolor_Calibration::$fields as $f) {
            $values[$f] = $rec->{$f};
        }

        try {
            imgcolor_Calibration::buildOptions($values);
        } catch (InvalidArgumentException $e) {
            throw new core_exception_Expect('imgcolor: ' . $e->getMessage(), 'Несъответствие');
        }
    }
}
