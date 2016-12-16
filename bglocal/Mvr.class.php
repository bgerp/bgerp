<?php



/**
 * Клас 'drdata_Mvr'
 *
 * МВР по страната
 *
 * @category  bgerp
 * @package   bglocal
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bglocal_Mvr extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools2, bglocal_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, city, account';
    
    
    /**
     * Заглавие
     */
    var $title = 'МВР по страната';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, common';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin, common';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, common';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin, common';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('city',    'varchar', 'caption=Град, mandatory, export');
        $this->FLD('account', 'varchar', 'caption=Сметка, input=none, export');
        $this->setDbUnique('city');
    }
    
    
    /**
     * Сортиране по city
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('#city');
    }
    
    
    /**
     * След подготовката на модела, инициализира началните данни
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        
        // Подготвяме пътя до файла с данните 
        $file = "bglocal/data/Mvr.csv";
        
        // Кои колонки ще вкарваме
        $fields = array(
            0 => "city",
            1 => "account",
        );
        
        // Импортираме данните от CSV файла. 
        // Ако той не е променян - няма да се импортират повторно 
        $cntObj = csv_Lib::importOnce($mvc, $file, $fields, NULL, NULL, TRUE);
        
        // Записваме в лога вербалното представяне на резултата от импортирането 
        $res .= $cntObj->html;
    }
}