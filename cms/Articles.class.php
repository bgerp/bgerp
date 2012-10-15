<?php



/**
 * Публични статии
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_Articles extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Публични статии";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_State2, plg_RowTools, plg_Printing, cms_Wrapper, plg_Sorting, plg_Vid';
    

    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'cms_SourceIntf';


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
        $this->FLD('level', 'order', 'caption=Номер,mandatory');
        $this->FLD('menuId', 'key(mvc=cms_Content,select=menu)', 'caption=Меню,mandatory,silent');
        $this->FLD('title', 'varchar', 'caption=Заглавие,mandatory,width=100%');
        $this->FLD('body', 'richtext', 'caption=Текст,column=none');
    }

    
    /**
     *  Задава подредбата
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $data->query->orderBy('#menuId,#level');
    }


    /**
     * Подготвя някои полета на формата
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        $cQuery = cms_Content::getQuery();
        
        $selfClassId = core_Classes::fetchIdByName($mvc->className);

        while($cRec = $cQuery->fetch()) {
            $options[$cRec->id] = $cRec->menu;
        } 

        $data->form->setOptions('menuId', $options);
    }



    function getContentUrl($menuId)
    {
        $query = self::getQuery();
        $query->where("#menuId = {$menuId}");
        $query->orderBy("#level");

        $rec = $query->fetch("#menuId = {$menuId} AND #body != ''");

        if($rec) {
            return toUrl(array($this, 'Article', $rec->vid ? $rec->vid : $rec->id));
        } else {
            return toUrl(array($this, 'Article', 'menuId' => $menuId));
        }
    }



    /**
     *
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->title = ht::createLink($row->title, array($mvc, 'Article', $rec->vid ? $rec->vid : $rec->id));
    }


    function act_Article()
    {   
        Mode::set('wrapper', 'cms_tpl_Page');
        
        $conf = core_Packs::getConfig('cms');

        Mode::set('cmsLayout', $conf->CMS_THEME . '/Articles.shtml');

        $id = Request::get('id', 'int');
        
        if(!$id) { 
            $menuId =  Mode::get('cMenuId');

            if(!$menuId) {
                $menuId = Request::get('menuId');
            }
            expect($menuId, $menuId);
        } else {
            // Ако има, намира записа на страницата
            $rec = self::fetch($id);
        }
        
        if($rec) {

            $menuId = $rec->menuId;

            $lArr = explode('.', self::getVerbal($rec, 'level'));

            $content = new ET('[#1#]', self::getVerbal($rec, 'body'));

            // Рендираме тулбара за споделяне
            $conf = core_Packs::getConfig('cms');
            $content->prepend(new ET("<div style='margin-bottom:15px;'>[#1#]</div>", $conf->CMS_SHARE));

            $ptitle   = self::getVerbal($rec, 'title') . " » ";

            $content->prepend($ptitle, 'PAGE_TITLE');
            
            
        }

        if(!$content) $content = new ET();

        // Подготвя навигацията
        $query = self::getQuery();
        $query->where("#menuId = {$menuId}");
        $query->orderBy("#level");

        $navTpl = new ET();

        while($rec1 = $query->fetch()) {
            
            $lArr1 = explode('.', self::getVerbal($rec1, 'level'));

            if($lArr) {
                if($lArr1[2] && (($lArr[0] != $lArr1[0]) || ($lArr[1] != $lArr1[1]))) continue;
            }

            $title = self::getVerbal($rec1, 'title');


            if(!$rec && $rec1->body) {

                // Това е първата срещната статия

                $id = $rec1->id;

                $rec = self::fetch($id);

                $menuId = $rec->menuId;

                $lArr = explode('.', self::getVerbal($rec, 'level'));

                $content = new ET('[#1#]', self::getVerbal($rec, 'body'));

                $ptitle   = self::getVerbal($rec, 'title') . " » ";

                $content->prepend($ptitle, 'PAGE_TITLE');

            }

            $class = ($rec->id == $rec1->id) ? $class = 'sel_page' : '';


            if($lArr1[2]) {
                $class .= ' level3';
            } elseif($lArr1[1]) {
                $class .= ' level2';
            } elseif($lArr1[0]) {
                $class .= ' level1';
            }

            $navTpl->append("<div class='nav_item {$class}'>");
            if(trim($rec1->body)) {
                $navTpl->append(ht::createLink($title, array('cms_Articles', 'Article', $rec1->vid ? $rec1->vid : $rec1->id)));
            } else {
               $navTpl->append($title);
            }
            
            if(self::haveRightFor('edit', $rec1)) {
                $navTpl->append('&nbsp;');
                $navTpl->append(
                    ht::createLink( '<img src=' . sbf("img/16/edit.png") . ' width="12" height="12">', 
                                    array('cms_Articles', 'Edit', $rec1->id, 'ret_url' => array('cms_Articles', 'Article', $rec1->id) ))); 
            }

            $navTpl->append("</div>");

        }
        
        if(self::haveRightFor('add')) {
            $navTpl->append( "<div style='padding:2px; border:solid 1px #ccc; background-color:#eee; margin-top:10px;font-size:0.7em'>");
            $navTpl->append(ht::createLink( tr('+ добави страница'), array('cms_Articles', 'Add', 'menuId' => $menuId, 'ret_url' => array('cms_Articles', 'Article', 'menuId' => $menuId))));
            $navTpl->append( "</div>");
        }

        Mode::set('cMenuId', $menuId);

        $content->append($navTpl, 'NAVIGATION');

        $content->replace($title, 'META_KEYWORDS');

        if($rec) {
            if(core_Packs::fetch("#name = 'vislog'")) {
                vislog_History::add($rec->title);
            }
        }

        return $content;
    }

 }