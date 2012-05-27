<?php



/**
 * Публично съдържание, подредено в меню
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_Content extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Публично съдържание";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_State2, plg_RowTools, plg_Printing, cms_Wrapper, plg_Sorting';


    /**
     * Полета, които ще се показват в листов изглед
     */
   // var $listFields = ' ';
    
     
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'cms,admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'cms,admin';
    
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {   
        $this->FLD('order', 'order', 'caption=Подредба,mandatory');
        $this->FLD('menu', 'varchar(64)', 'caption=Меню,mandatory');
        $this->FLD('source', 'class(interface=cms_ContentSourceIntf,select=title)', 'caption=Източник');
        $this->setDbUnique('menu');
    }
    
    
    
    /**
     *  Задава подредбата
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $data->query->orderBy('#order');
    }

    
    /**
     * Подготвя данните за публичното меню
     */
    function prepareMenu_($data)
    {
        $query = self::getQuery();
        
        $query->orderBy('#order');

        while($rec = $query->fetch("#state = 'active'")) {
            
            try {
                unset($cls);
                $cls = cls::get($rec->source);
                $rec->url = $cls->getContentUrl($rec->id);
            } catch (core_Exception_Expect $expect) {}

            $data->items[] = $rec;
        }
    }

    
    /**
     * Рендира публичното меню
     */
    function renderMenu_($data)
    {   
        $tpl = new ET();
        
        $cmsSelMenuId = Mode::get('cms_MenuId');

        foreach($data->items as $rec) {
            $attr = array();
            if( ($cmsSelMenuId == $rec->id) || (!$flag && !$cmsSelMenuId)) {
                $attr['class'] = 'selected';
            }  
            $tpl->append(ht::createLink($rec->menu, $rec->url, NULL, $attr));
            $flag = TRUE;
        }
 
        return $tpl;
    }

    
    /**
     * Връща основното меню
     */
    static function getMenu()
    {
        $data = new stdClass();
        $self = cls::get('cms_Content');
        $self->prepareMenu($data);
        
        return  $self->renderMenu($data);
    }

    
    /**
     * Връща футера на страницата
     */
    static function getFooter()
    {
        return '<small>&nbsp; Copyright © 1997-2012 Experta OOD</small>';
    }
    
    
 }