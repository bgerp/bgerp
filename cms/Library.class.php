<?php


/**
 * Библиотека с публично мултимедийно съдържание
 * о Картинки
 * о HTML
 * o аудио и видео
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cms_Library extends embed_Manager
{
    /**
     * Свойство, което указва интерфейса на вътрешните обекти
     */
    public $driverInterface = 'cms_LibraryIntf';


    /**
     * Заглавие
     */
    public $title = 'Мултимедийна библиотека';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Обект';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_State2, plg_RowTools2, cms_Wrapper, plg_Sorting, plg_Search';
    
        
    /**
     * Кой може да променя състоянието на валутата
     */
    public $canChangestate = 'cms,admin,ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'cms,admin,ceo';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'cms,admin,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,cms';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,cms';
    
    
    /**
     * Полета за листовия изглед
     */
    public $listFields = 'driverClass,tag';
    
    
    /**
     * Поле за инструментите на реда
     */
    public $rowToolsField = '✍';
    
    
    /**
     * По кои полета ще се търси
     */
    public $searchFields = 'description,name';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име,mandatory');
        $this->FLD('tag', 'varchar(64)', 'caption=Таг,input=none');
        $this->FLD('description', 'text(rows=2)', 'caption=Описание');

        $this->setDbUnique('name');
    }


    /**
     * Рендира обекта в HTML
     */
    public static function render($rec, $maxWidth, $absolute = false)
    {
        $Driver = self::getDriver($rec);
        
        $res = '';

        if($Driver) {
            $res = $Driver->render($rec, $maxWidth, $absolute);
        }

        return $res;
    }


    /**
     * След рендиране на единичния изглед
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        $maxWidth = 900;
        if (isset($data->rec)) {
            $preview = self::render($data->rec, $maxWidth);
            $tpl->append("<div style='max-width:{$maxWidth}px;padding:0px;'>");
            $tpl->append($preview);
            $tpl->append("</div>");
        }
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
        $row->tag = '[elm=' . $mvc->getVerbal($rec, 'name') . '-' . $rec->tag . ']';
    }


    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc     $mvc     Мениджър, в който възниква събитието
     * @param int          $id      Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass     $rec     Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields  Имена на полетата, които трябва да бъдат записани
     * @param string       $mode    Режим на записа: replace, ignore
     */
    public static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if(!isset($rec->tag)){
            $rec->tag = str::getRand('****'); 
        }

        $rec->name = str::canonize($rec->name);
    }

    
}
