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
        $this->FLD('url',  'varchar(128)', 'caption=URL,mandatory');
        $this->FLD('layout', 'html', 'caption=Лейаут');

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

        $data->items = $query->fetchAll("#state = 'active'");
    }

    
    /**
     * Рендира публичното меню
     */
    function renderMenu_($data)
    {   
        $tpl = new ET();
        
        $cMenuId = Mode::get('cMenuId');
        
        if(!$cMenuId) {
            $cMenuId = Request::get('cMenuId');
            Mode::set('cMenuId', $cMenuId);
        }

        if (is_array($data->items)) {
            foreach($data->items as $rec) {
                $attr = array();
                if( ($cMenuId == $rec->id)) {
                    $attr['class'] = 'selected';
                } 

                if($rec->url) {
                    $tpl->append(ht::createLink($rec->menu, arr::make($rec->url), NULL, $attr));
                } else {
                    $tpl->append(ht::createLink($rec->menu, array('cms_Content', 'show', $rec->id), NULL, $attr));
                }
            }    
        }
 
        return $tpl;
    }
    
    
    /**
     *
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->menu = ht::createLink($row->menu, array($mvc, 'Show',  $rec->vid ? $rec->vid : $rec->id));
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
        return '<div style="float:right;font-size:0.8em;">задвижвано от <b>bgERP</b>&nbsp;</div>';
    }
    
     
    /**
     * Връща футера на страницата
     */
    static function getLayout()
    {
        $cMenuId = Mode::get('cMenuId');

        
        if($cMenuId) {

            $l = self::fetchField($cMenuId, 'layout');
            
            if($l) $tpl = new ET($l);

        } 

        if(!$tpl) {
            $tpl = new ET("<div class='cms-row'>
                    <!--ET_BEGIN NAVIGATION-->
                    <div class='fourcol' id='cmsNavigation' style='padding-top:20px;padding-left:20px;'>
                        [#NAVIGATION#]
                    </div>
                    <!--ET_END NAVIGATION-->
                    <div class='sevencol'  style='padding-top:20px;'>
                        [#PAGE_CONTENT#]
                     </div>
                </div>");
        }

        return $tpl;
    }


    
    /**
     *
     */
    function act_Show()
    {  
        $menuId = Request::get('id');
        
        if(!$menuId) {
            $menuId = 1;
        }

        Mode::set('cMenuId', $menuId);
        
        Request::push(array('Ctr' => 'cms_Articles', 'Act' => 'Article', 'id' => 0));
       
        return Request::forward();
    }
   
    
 }