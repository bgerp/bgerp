<?php


/**
 * Клас 'vislog_IpNames' -
 *
 *
 * @category  bgerp
 * @package   vislog
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class vislog_IpNames extends core_Manager
{
    /**
     * Страница от менюто
     */
    public $pageMenu = 'Система';
    
    
    /**
     * Заглавие
     */
    public $title = 'Search Log Ip-s';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 20;
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'vislog_Wrapper,plg_RowTools2';
    
    
    /**
     * Кой  може да пише?
     */
    public $canWrite = 'cms,admin,ceo';
    
    
    /**
     * Кой може да чете?
     */
    public $canRead = 'cms, ceo, admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, admin, cms';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, admin, cms';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('ip', 'ip(15)', 'caption=IP');
        $this->FLD('name', 'varchar(255)', 'caption=Име');
        
        $this->setDbUnique('ip');
    }
    
    
    /**
     * Добавя име на IP-adresa
     */
    public static function add($name, $ip = null)
    {
        $name = str_replace('&amp;', '&', $name);
        
        if (!$ip) {
            $ip = core_Users::getRealIpAddr();
        }
        
        $rec = self::fetch(array("#ip = '[#1#]'", $ip));
        $mustSave = true;
        
        if (!$rec) {
            $rec = (object) array('ip' => $ip, 'name' => $name);
        } else {
            if (strpos($rec->name, $name) === false) {
                $rec->name = $name . ', ' . $rec->name;
            } else {
                $mustSave = false;
            }
        }
        
        if ($mustSave) {
            $rec->name = str::truncate($rec->name, 255);
            self::save($rec);
        }
    }
}
