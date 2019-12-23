<?php 

/**
 * Споделяне в социалните мрежи
 *
 *
 * @category  bgerp
 * @package   social
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class social_SharingCnts extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Броене на споделянията';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Брой споделяния';
    
    
    /**
     * Разглеждане на листов изглед
     */
    public $canSingle = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'social_Wrapper, plg_Created';
    
    
    /**
     * Полета за листовия изглед
     */
    public $listFields = 'networkId,url,cnt,createdOn=Създаване';
    
    
    /**
     * Поле за инструментите на реда
     */
    public $rowToolsField = '✍';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'cms, social, admin, ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'cms, social, admin, ceo';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('networkId', 'key(mvc=social_Sharings)', 'caption=Медия, input=none');
        $this->FLD('url', 'varchar(128)', 'caption=URL, input=none, hint=URL за споделяне');
        $this->FLD('cnt', 'int', 'caption=Споделяния, input=none,notNull');
    }
    
    public static function addHit($networkId, $url)
    {
        // Взимаме записите от модела, който брои споделянията
        $rec = self::fetch(array("#networkId = '{$networkId}' AND #url = '[#1#]'", $url));
        
        // Ако нямаме записи, създаваме записа
        if (!$rec) {
            $rec = new stdClass();
            $rec->networkId = $networkId;
            $rec->url = $url;
        }
        
        // Уваеличаваме брояча и записваме
        $rec->cnt++;
        self::save($rec);
    }
    
    
    /**
     * Вербално оформление на линквете
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->url = ht::createLink(type_Varchar::escape($rec->url), $rec->url);
    }
    
    
    /**
     * Преди извличане на записите за листови изглед - подреждане
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#createdOn', 'DESC');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'edit' || $action == 'add') {
            $requiredRoles = 'no_one';
        }
    }
}
