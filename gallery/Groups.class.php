<?php



/**
 * Клас 'gallery_Groups' - групи от картинки
 *
 *
 * @category  vendors
 * @package   gallery
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class gallery_Groups extends core_Manager {
    
    
     
    
    /**
     * Заглавие
     */
    var $title = 'Групи от картинки';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = "plg_RowTools,gallery_Wrapper,plg_Created";
    
    
    /**
     * Кой  може да пише?
     */
    var $canWrite = "admin,cms,ceo";
    
    
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
}