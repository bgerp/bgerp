<?php



/**
 * Клас 'fileman_GalleryGroups' - групи от картинки
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_GalleryGroups extends core_Manager
{
    
    
    /**
     * 
     */
    var $canRead = 'admin,ceo,cms';
    

    /**
     * Кой  може да пише?
     */
    var $canWrite = 'admin,ceo,cms';

    
    /**
     * Заглавие
     */
    var $title = 'Групи от картинки';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,cms';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,cms';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = "plg_RowTools,fileman_Wrapper,fileman_GalleryWrapper,plg_Created,cms_VerbalIdPlg";
    
    
    /**
     * Полета за изглед
     */
    var $listFields = 'id,vid=Код,title,columns,tWidth,tHeight,width,height,createdOn,createdBy';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'cms_GalleryGroups';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие');
        $this->FLD('position', 'enum(none=Без стил,center=Център,left=Ляво,right=Дясно)', 'caption=Позиция');
        $this->FLD('tpl', 'html', 'caption=Шаблон');
        
        $this->FLD('style', 'varchar', 'caption=Стил');

        $this->FLD('columns', 'int', 'caption=Колони');
     
        $this->FLD('tWidth', 'int', 'caption=Тъмб->широчина');
        $this->FLD('tHeight', 'int', 'caption=Тъмб->височина');
        
        $this->FLD('width', 'int', 'caption=Картинка->широчина');
        $this->FLD('height', 'int', 'caption=Картинка->височина');
    }
    
    /**
     * допълнение към подготовката на вербално представяне
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec, $fields)
    {
     	$row->vid = "[gallery=#" . $rec->vid . "]";
    }
}