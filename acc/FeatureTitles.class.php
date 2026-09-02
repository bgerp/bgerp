<?php


/**
 * Заглавия на свойствата
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acc_FeatureTitles extends core_Manager
{
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Заглавия на свойства';
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'acc_WrapperSettings, plg_State2, plg_Created, plg_Sorting';
    
    
    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Счетоводство:Настройки';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,acc';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Заглавие на свойство';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да променя състоянието на валутата
     */
    public $canChangestate = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да го редактира?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 40;
    
    
    /**
     * Кеш в рамките на хита: заглавие на свойство -> ид
     */
    private static $titleToIdMap;


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Черта');
    }


    /**
     * Връща id на посочения признак. Ако го няма - създава го.
     */
    public static function fetchIdByTitle($title)
    {
        if (!isset(self::$titleToIdMap)) {
            self::fetchTitleToIdMap();
        }

        // Заглавието е с двоична колация, така че сравнението в масива е като това в SQL
        if (isset(self::$titleToIdMap[$title])) {

            return self::$titleToIdMap[$title];
        }

        $id = acc_FeatureTitles::fetchField(array("#title = '[#1#]'", $title), 'id');
        if (!isset($id)) {
            $ftRec = (object) array('title' => $title);
            acc_FeatureTitles::save($ftRec);
            $id = $ftRec->id;
        }

        self::$titleToIdMap[$title] = $id;

        return $id;
    }


    /**
     * Извлича масив с индекс заглавие на свойство и стойност - неговото ид
     *
     * @author Ivelin Dimov <ivelin_pdimov@abv.bg>
     */
    private static function fetchTitleToIdMap()
    {
        self::$titleToIdMap = array();

        $query = self::getQuery();
        $query->show('id,title');

        while ($rec = $query->fetch()) {
            self::$titleToIdMap[$rec->title] = $rec->id;
        }
    }
}
