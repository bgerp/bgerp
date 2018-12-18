<?php


/**
 *
 *
 * @category  vendors
 * @package   peripheral
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class peripheral_Devices extends embed_Manager
{
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Периферни устройства';
    
    
    /**
     * Титлата на обекта в единичен изглед
     */
    public $singleTitle = 'Периферно устройство';
    
    
    /**
     * Интерфейс на драйверите
     */
    public $driverInterface = 'peripheral_DeviceIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Sorting, plg_Created, plg_Modified, peripheral_Wrapper, plg_RowTools2';
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'admin, peripheral';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'admin, peripheral';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'admin, peripheral';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin, peripheral';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin, peripheral';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'admin, peripheral';
    
    
    /**
     * Кой има достъп до сингъла
     */
    public $canSingle = 'admin, peripheral';
    
    
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Име, mandatory');
        $this->FLD('brid', 'varchar(8)', 'caption=Компютър->Браузър');
        $this->FLD('ip', 'ip', 'caption=Компютър->IP');
    }
    
    
    /**
     *
     *
     * @param string      $intfName
     * @param null|string $brid
     * @param null|string $ip
     */
    public static function getDevices($intfName, $brid = null, $ip = null)
    {
        static $cArr = array();
        
        $hash = md5($intfName . '|' . $brid . '|' . $ip);
        
        if (isset($cArr[$hash])) {
            
            return $cArr[$hash];
        }
        
        $me = cls::get(get_called_class());
        $cArr[$hash] = array();
        
        $clsArr = core_Classes::getOptionsByInterface($intfName);
        
        if (empty($clsArr)) {
            
            return $cArr[$hash];
        }
        
        $clsArr = array_keys($clsArr);
        
        $query = self::getQuery();
        $query->in($me->driverClassField, $clsArr);
        
        if ($brid) {
            $query->where(array("#brid = '[#1#]'", $brid));
        }
        
        if ($ip) {
            $query->where(array("#ip = '[#1#]'", $ip));
        }
        
        $query->orderBy('createdOn', 'DESC');
        
        $cArr[$hash] = $query->fetchAll();
        
        return $cArr[$hash];
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $brid = log_Browsers::getBrid();
        $data->form->setSuggestions('brid', array('' => '', $brid => $brid));
        
        $ip = core_Users::getRealIpAddr();
        $data->form->setSuggestions('ip', array('' => '', $ip => $ip));
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            if (!$form->rec->brid && !$form->rec->ip) {
                $form->setError('brid, ip', 'Непопълнено задължително поле');
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn);
        $row->brid = log_Browsers::getLink($rec->brid);
    }
}
