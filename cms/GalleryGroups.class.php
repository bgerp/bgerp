<?php



/**
 * Клас 'cms_GalleryGroups' - групи от картинки
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_GalleryGroups extends core_Manager {
    
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
     * Плъгини за зареждане
     */
    var $loadList = "plg_RowTools,cms_Wrapper,cms_GalleryWrapper,plg_Created";
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'gallery_Groups';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('vid', 'varchar(32)', 'caption=Идентификатор');
        
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие');
        
        $this->FLD('tpl', 'html', 'caption=Шаблон');
        
        $this->FLD('columns', 'int', 'caption=Колони');
     
        $this->FLD('tWidth', 'int', 'caption=Тъмб->широчина');
        $this->FLD('tHeight', 'int', 'caption=Тъмб->височина');
        
        $this->FLD('width', 'int', 'caption=Картинка->широчина');
        $this->FLD('height', 'int', 'caption=Картинка->височина');
         
        $this->setDbUnique('vid');
    }
    
    /**
     * допълнение към подготовката на вербално представяне
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec, $fields)
    {
     	$row->vid = "[gallery=#" . $rec->vid . "]";
    }
}