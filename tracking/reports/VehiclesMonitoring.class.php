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
    protected $groupByField = 'number';
    
    
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
        $fieldset->FLD('vehicle', 'keylist(mvc=tracking_Vehicles,select=number,)', 'caption=Превозно средство,after=title');
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
        $values = array();
        $parseData = array();
        
        $query = tracking_Log::getQuery();
        
        $query-> where(array("#createdOn >= '[#1#]' AND #createdOn <= '[#2#]'", $rec->from . ' 00:00:00', $rec->to . ' 23:59:59'));
        
        if ($rec->vehicle) {
            $vehicleArr = keylist::toArray($rec->vehicle);
            
            $query->in('vehicleId', $vehicleArr);
        }
        
        
        while ($vehicle = $query->fetch()) {
            $parseData['latitude'] = $parseData['longitude'] = 0;
            $time = null;
            $i++;
            
            $id = $vehicle->id;
            
            $parseData = tracking_Log::parseTrackingData($vehicle->data);
            $parseData['latitude'] = tracking_Log::DMSToDD($parseData['latitude']);
            $parseData['longitude'] = tracking_Log::DMSToDD($parseData['longitude']);
            $vehicleData = tracking_Vehicles::fetch($vehicle->vehicleId);
            $time = dt::mysql2verbal($vehicle->fixTime, $mask = 'd.m.y H:i:s');
            
            
            $coords[] = array($parseData['latitude'],$parseData['longitude'],array('info' => "{$vehicleData->number} » ${time}"));
            
            $values[$vehicle->vehicleId] = array(
                'coords' => $coords
            );
            
            
            $values[$vehicle->vehicleId] = core_Array::combine($values[$vehicle->vehicleId], array(
                'info' => $vehicleData->number.' » '.$time));
            
            $recs[$id] = (object) array(
                
                'number' => $vehicleData->number,
                'latitude' => $parseData['latitude'],
                'longitude' => $parseData['longitude'],
                'speed' => $parseData['speed'],
                'heading' => $parseData['heading'],
                'time' => $vehicle->fixTime,
                'personId' => $vehicleData->personId,
                'trackerId' => $vehicleData->trackerId,
                'createdOn' => $vehicle->createdOn,
            
            );
        }
        
        $recs['values'] = $values;
        
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
       $values = $data->recs['values'];
       
        if (is_array($values)) {
            $tpl = location_Paths::renderView($values);
            
            Mode::set('saveJS', true);
        } else {
            $tpl = 'Липсват данни';
        }
        
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
            $fld->FLD('time', 'varchar', 'caption=Време');
            $fld->FLD('latitude', 'double(smartRound,decimals=2)', 'smartCenter,caption=Ширина');
            $fld->FLD('longitude', 'double(smartRound,decimals=8)', 'smartCenter,caption=Дължина');
            $fld->FLD('speed', 'double(smartRound,decimals=2)', 'smartCenter,caption=Скорост');
            $fld->FLD('heading', 'double(smartRound,decimals=2)', 'smartCenter,caption=Посока');
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
        
        if (is_object($dRec)) {
            
            $row->number = $dRec->number. "<span class= 'fright'><span class= ''>" . 'Водач :' . crm_Persons::getVerbal($dRec->personId, 'name') . '</span>';
            $row->latitude = core_Type::getByName('double(decimals=8)')->toVerbal($dRec->latitude);
            $row->longitude = core_Type::getByName('double(decimals=8)')->toVerbal($dRec->longitude);
            $row->speed = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->speed);
            $row->heading = core_Type::getByName('int')->toVerbal($dRec->heading);
            $row->personId = $dRec->personId;
            $row->time = dt::mysql2verbal($dRec->time, $mask = 'd.m.y H:i:s');
      
        }
        
        return $row;
    }
}
