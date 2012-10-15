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
        $this->FLD('menu',    'varchar(64)', 'caption=Меню,mandatory');
        $this->FLD('source',  'class(interface=cms_SourceIntf, allowEmpty)', 'caption=Източник');
        $this->XPR('order', 'double', '0+#menu', 'caption=Подредба,column=none');
        $this->FLD('url',  'varchar(128)', 'caption=URL');
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
                
                list($f, $s) = explode(' ', $rec->menu, 2);

                if(is_Numeric($f)) {
                    $rec->menu = $s;
                }

                $attr = array();
                if( ($cMenuId == $rec->id)) {
                    $attr['class'] = 'selected';
                } 
                
                $url = $this->getContentUrl($rec);
                
                $tpl->append(ht::createLink($rec->menu, $url, NULL, $attr));
            }    
        }
 
        return $tpl;
    }


    /**
     *
     */
    function getContentUrl($rec) 
    {
        if($rec->source) {
            $source = cls::get($rec->source);
            $url = $source->getContentUrl($rec->id);
        } elseif($rec->url) {
            $url = arr::make($rec->url);
        } else {
            expect(FALSE);
        }

        return $url;
    }
    
    
    /**
     *
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {   
        $url = $mvc->getContentUrl($rec);

        $row->menu = ht::createLink($row->menu, $url);
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
        $layoutPath = Mode::get('cmsLayout');

        $layout = new ET($layoutPath ? getFileContent($layoutPath) : '[#PAGE_CONTENT#]');
    
        return $layout;
    }


    
    /**
     *
     */
    function act_Show()
    {  
        $menuId = Request::get('id');
        
        if(!$menuId) {
            $query = self::getQuery();
            $query->where("#state = 'active'");
            $query->orderBy("#order");
            $rec = $query->fetch();
        } else {
            $rec = $this->fetch($menuId);
        }
        
        Mode::set('cMenuId', $menuId);
        
        if($rec) {
            return new Redirect($this->getContentUrl($rec));
        } else {
            return new Redirect(array('bgerp_Portal', 'Show'));
        }
    }
   
    
 }