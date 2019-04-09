<?php 

/**
 * Общ редирект
 *
 *
 * @category  bgerp
 * @package   blast
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class blast_Redirect extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Редиректи';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, blast';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, blast';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, blast';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo, blast';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, blast';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, blast';
    
    
    /**
     * Кой може да праща информационните съобщения?
     */
    public $canBlast = 'ceo, blast';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'blast_Wrapper, plg_Created, plg_Modified, plg_RowTools2';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('domainId', 'key(mvc=cms_Domains, select=titleExt)', 'caption=Домейн,silent, removeAndRefreshForm, mandatory');
        $this->FLD('vid', 'varchar(64)', 'caption=Вербална част, mandatory');
        $this->FLD('url', 'url', 'caption=URL, mandatory');
        $this->FLD('rCnt', 'int', 'caption=Брой, input=none');
        
        $this->setDbUnique('vid');
    }
    
    
    /**
     * Прави редирект към съответния екшън
     * 
     * @param string  $vid
     */
    public static function doRedirect($vid)
    {
        $rec = self::fetch(array("#vid = '[#1#]'", $vid));
        
        $vRec = vislog_History::add("Редирект: {$vid}");
        
        if ($rec) {
            if ($vRec) {
                $rec->rCnt++;
                self::save($rec, 'rCnt');
            }
            
            header('Cache-Control: no-cache, must-revalidate'); // HTTP 1.1.
            header('Pragma: no-cache'); // HTTP 1.0.
            header('Expires: 0'); // Proxies.
            
            header("Location: {$rec->url}", true, 301);
            
            core_App::shutdown(false);
        }
        
        self::logWarning("Липсващ запис за '{$vid}'");
        
        redirect(array('Index'), false, '|Изтекла или липсваща връзка', 'error');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($rec->vid) {
            $row->vid = cms_Domains::getAbsoluteUrl($rec->domainId) . '/B/R/' . $row->vid;
            $row->vid = "<span onmouseUp='selectInnerText(this);'>{$row->vid}</span>";
        }
    }
}
