<?php



/**
 * Клас 'drdata_PhoneCache' - Кеш за телефонните номера
 *
 *
 * @category  bgerp
 * @package   drdata
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class drdata_PhoneCache extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Кеш за телефонните номера';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'drdata_Wrapper,plg_Sorting,plg_Modified';

    /**
     * Кой има право да променя?
     *
     * @var string|array
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     *
     * @var string|array
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     *
     * @var string|array
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('tel', 'varchar(128)', 'caption=Телефон');
        $this->FLD('dCC', 'varchar(32)', 'caption=Код по подразбиране->На държава');
        $this->FLD('dAC', 'varchar(32)', 'caption=Код по подразбиране->На място');

        $this->FLD('res', 'blob(serialize)', 'caption=Резултат');
         
        $this->setDbUnique('tel,dCC,dAC');
    }
    
    
    /**
     * Метод за четене от телефонния кеш
     */
    public static function get($tel, $dCC, $dAC)
    {
        $query = self::getQuery();
        $query->show('res');
        if ($rec = $query->fetch(array("#tel = '[#1#]' AND #dCC = '[#2#]' AND #dAC = '[#3#]'", $tel, $dCC, $dAC))) {
            return $rec->res;
        }
    }
    
    
    /**
     * Метод за запис в телефонния кеш
     */
    public static function set($tel, $dCC, $dAC, $res)
    {
        $rec = (object) array(
            'tel' => $tel,
            'dCC' => $dCC,
            'dAC' => $dAC,
            'res' => $res,
            );
        self::save($rec, null, 'REPLACE');
    }
}
