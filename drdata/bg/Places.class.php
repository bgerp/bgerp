<?php 

/**
 * Градове в България
 *
 *
 * @category  bgerp
 * @package   drdata
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class drdata_bg_Places extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Населени места в България';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Градиовете в България';
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'postcode,city,region,municipality,lat,lng';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, drdata_Wrapper, plg_Printing,
                       plg_SaveAndNew';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin,ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'admin,ceo';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('postcode', 'int(11)', 'caption=Пощенски код');
        $this->FLD('type', 'varchar(6)', 'caption=Тип');
        $this->FLD('city', 'varchar(255)', 'caption=Населено място, mandatory');
        $this->FLD('region', 'varchar(255)', 'caption=Община');
        $this->FLD('municipality', 'varchar(255)', 'caption=Област');
        $this->FLD('lat', 'varchar(60)', 'caption=Ширина');
        $this->FLD('lng', 'varchar(60)', 'caption=Дължина');
        
        $this->setDbUnique('city');
    }
    
    
    /**
     * Преди запис
     */
    public static function on_BeforeImportRec($mvc, $rec)
    {
        if (isset($rec->city)) {
            $rec->city = $rec->type . ' ' . $rec->city;
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        $file = 'drdata/data/bgCities.csv';
        $fields = array(0 => 'postcode', 1 => 'type', 2 => 'city', 3 => 'region', 4 => 'municipality', 5 => 'lat', 6 => 'lng');
        $cntObj = csv_Lib::largeImportOnceFromZero($mvc, $file, $fields);
        $res .= $cntObj->html;
    }
}
