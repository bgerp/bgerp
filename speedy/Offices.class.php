<?php 


/**
 * Модел за офиси на speedy
 *
 * @category  bgerp
 * @package   speedy
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class speedy_Offices extends core_Manager
{
    /**
     * Заглавие на модела
     */
    public $title = 'Офиси на спиди';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'speedy_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "id,num,extName";
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('num', 'int', 'caption=Код');
        $this->FNC('extName', 'varchar', 'caption=Наименование');
        $this->FLD('name', 'varchar', 'caption=Име');
        $this->FLD('address', 'varchar', 'caption=Адрес');
        
        $this->setDbUnique('num');
    }
    
    
    /**
     * Изчисление на пълното наименование на офиса
     */
    protected static function on_CalcExtName($mvc, $rec)
    {
        $rec->extName = $rec->name . "({$rec->address})";
    }
    
    
    /**
     * Кои са достъпните офиси за избор
     * 
     * @return array $options
     */
    public static function getAvailable()
    {
        $options = array();
        $query = self::getQuery();
        while($rec = $query->fetch()){
            $options[$rec->id] = $rec->extName;
        }
        
        return $options;
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $file = 'speedy/data/Offices.csv';
        
        $fields = array(
            0 => 'num',
            1 => 'name',
            2 => 'address',
        );
        
        $cntObj = csv_Lib::importOnce($this, $file, $fields);
        $res = $cntObj->html;
        
        return $res;
    }
}