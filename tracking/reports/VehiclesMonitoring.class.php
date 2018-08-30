<?php


/**
 * Мениджър на отчети за мониторинг на превозни средства
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Проследяване » Мониторинг на превозни средства
 */
class tracking_reports_VehiclesMonitoring extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,repAll, repAllGlobal';
    
    
    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField;
    
    
    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var string
     */
    protected $newFieldToCheck;
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;
    
    
    /**
     * Дали да има ChartTab
     */
    protected $enableChartTab = true;
    
    
    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'from,to,compare,group,dealers,contragent,crmGroup,articleType';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('vehicle', 'keylist(mvc=tracking_Vehicles,select=number,)', 'caption=Превозно средство,single=none,after=title');
        $fieldset->FLD('from', 'date', 'caption=От,after=compare,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_Form         $form
     * @param stdClass          $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            
            // Проверка на периоди
            if (isset($form->rec->from, $form->rec->to) && ($form->rec->from > $form->rec->to)) {
                $form->setError('from,to', 'Началната дата на периода не може да бъде по-голяма от крайната.');
            }
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
    }
    
    
    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)
    {
        $recs = array();
        
        
        $query = tracking_Log::getQuery();
        
        $query->where("(#createdOn  >= '{$rec->from}' AND #createdOn  <= '{$rec->to}')");
        
        while ($vehicle = $query->fetch()) {
            $parseData['latitude'] = $parseData['longitude'] = 0;
            
            $id = $vehicle->vehicleId;
            
            $parseData = tracking_Log::parseTrackingData($vehicle->data);
            $parseData['latitude'] = tracking_Log::DMSToDD($parseData['latitude']);
            $parseData['longitude'] = tracking_Log::DMSToDD($parseData['longitude']);
            
            $coords[] = array($parseData['latitude'],$parseData['longitude']);
            
            $recs[$id] = array(
                'coords' => $coords
            );
            
            $recs[$id] = core_Array::combine($recs[$id], array(
                'info' => tracking_Vehicles::fetchField($id, 'number')));

//             $recs[$id]= core_Array::combine($recs[$id], array(
//                 "number" => tracking_Vehicles::fetchField($id,'number')));
//             $recs[$id]= core_Array::combine($recs[$id], array(
//                 "make" => tracking_Vehicles::fetchField($id,'make')));
//             $recs[$id]= core_Array::combine($recs[$id], array(
//                 "model" => tracking_Vehicles::fetchField($id,'model')));
//             $recs[id]= core_Array::combine($recs[$id], array(
//                 "personId" => tracking_Vehicles::fetchField($id,'personId')));
//             $recs[$id]= core_Array::combine($recs[$id], array(
//                 "trackerId" => tracking_Vehicles::fetchField($id,'trackerId')));
        }
        
        return $recs;
    }
    
    
    /**
     * Chart render
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     */
    protected function renderChart($rec, &$data)
    {
        $tpl = location_Paths::renderView($data->recs);
        
        return $tpl;
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool     $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        
        
        if ($export === false) {
            $fld->FLD('number', 'varchar(10)', 'smartCenter,caption=Регистрационен номер');
            $fld->FLD('make', 'varchar(12)', 'smartCenter,caption=Марка');
            $fld->FLD('model', 'varchar(12)', 'smartCenter,caption=Модел');
            $fld->FLD('personId', 'varchar', 'caption=Водач');
            $fld->FLD('trackerId', 'varchar(12)', 'caption=Тракер Id');
        }
        
        return $fld;
    }
    
    
    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *                       - записа
     * @param stdClass $dRec
     *                       - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $groArr = array();
        $row = new stdClass();
        
        $row->vehicle = '<b>' . 'Превозно средство' . '</b>';
        
        return $row;
    }
}
