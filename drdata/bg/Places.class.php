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
        $this->FLD('city', 'varchar', 'caption=Град, mandatory');
        $this->FLD('lat', 'double(minDecimals=4)', 'caption=Ширина');
        $this->FLD('lng', 'double(minDecimals=4)', 'caption=Дължина');
        $this->FLD('population', 'int(7,size=7)', 'caption=Популация');
        
        $this->setDbUnique('city');
    }
    
    
    /**
     * Преди запис
     */
    public static function on_BeforeImportRec($mvc, $rec)
    {
        if (isset($rec->city)) {
            $rec->city = $rec->city . ' ' . $rec->city;
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        $file = 'drdata/data/bgNew.csv';
        $fields = array(0 => 'city', 1 => 'lat', 2 => 'lng', 3 => 'population');
        $cntObj = csv_Lib::largeImportOnceFromZero($mvc, $file, $fields);
        $res .= $cntObj->html;
    }
}
