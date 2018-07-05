<?php 


/**
 * Клас 'drdata_Banks - Банки'
 *
 *
 * @category  bgerp
 * @package   bglocal
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bglocal_Banks extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, bglocal_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name, bic';
    
    
    /**
     * Заглавие
     */
    public $title = 'Банки';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin, common';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'admin, common';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'admin, common';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'admin, common';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(255)', 'caption=Име, mandatory');
        $this->FLD('bic', 'varchar(8)', 'caption=BIC/SWIFT, mandatory');
        
        $this->setDbUnique('bic');
    }
    
    
    /**
     * Подреждаме банките по азбучен ред
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('#name');
    }
    
    
    /**
     * Записи за инициализиране на таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        $file = 'bglocal/data/Banks.csv';
        $fields = array(0 => 'name', 1 => 'bic');
        $cntObj = csv_Lib::importOnceFromZero($mvc, $file, $fields);
        $res .= $cntObj->html;
    }
    
    
    /**
     * Връща името на банката и нейния бик по зададен IBAN
     * @param  string $iban
     * @return string $rec->bic or NULL
     */
    public static function getBankName($iban)
    {
        if (preg_match('/^#/', $iban)) {
            return;
        }
        $parts = iban_Type::getParts($iban);
        
        if ($parts['bank'] && $rec = static::fetch(array("#bic LIKE '%[#1#]%'", $parts['bank']))) {
            
            return $rec->name;
        }
    }
    
    
    /**
     * Връща името на бика на банката  по зададен IBAN
     * @param  string $iban
     * @return string $rec->bic or NULL
     */
    public static function getBankBic($iban)
    {
        if (preg_match('/^#/', $iban)) {
            return;
        }
        $parts = iban_Type::getParts($iban);
        
        if ($parts['bank'] && $rec = static::fetch(array("#bic LIKE '%[#1#]%'", $parts['bank']))) {
            
            return $rec->bic;
        }
    }
}
