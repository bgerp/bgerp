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
    public $listFields = 'name';
    
    
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
        $this->FLD('name', 'varchar(64)', 'caption=Име');
        $this->FLD('hash', 'varchar(3)', 'caption=Хеш,input=none');
        $this->FLD('description', 'text(rows=2)', 'caption=Описание');

        $this->setDbUnique('name,hash');
    }


    /**
     * Рендира обекта в HTML
     */
    public static function render($rec, $maxWidth)
    {
        $Driver = self::getDriver($rec);

        $res = $Driver->render($rec, $maxWidth);

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
            $tpl->append("<div style='max-width:{$maxWidth}px;padding:10px;'>");
            $tpl->append($preview);
            $tpl->append("</div>");
        }
    }
    
}
