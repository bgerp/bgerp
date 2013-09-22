<?php



/**
 * Публични статии
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_Articles extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Публични статии";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_Modified, plg_Search, plg_State2, plg_RowTools, plg_Printing, cms_Wrapper, plg_Sorting, plg_Vid, plg_AutoFilter';
    

    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'cms_SourceIntf';


    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'level,title,state,modifiedOn,modifiedBy';
    
    var $rowToolsField = 'level';
     
    var $searchFields = 'title,body';

    /**
     * Кой може да пише?
     */
    var $canWrite = 'cms,admin,ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,cms';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,cms';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'cms,admin,ceo';
    
 
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('level', 'order', 'caption=Номер,mandatory');
        $this->FLD('menuId', 'key(mvc=cms_Content,select=menu)', 'caption=Меню,mandatory,silent,autoFilter');
        $this->FLD('title', 'varchar', 'caption=Заглавие,mandatory,width=100%');
        $this->FLD('body', 'richtext(bucket=Notes)', 'caption=Текст,column=none');

        $this->setDbUnique('menuId,level');
    }


    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $form->showFields = 'search, menuId';
        
        $form->input('search, menuId', 'silent');

        $form->setOptions('menuId', $opt = self::getMenuOpt());

        if(!$opt[$form->rec->menuId]) {
            $form->rec->menuId = key($opt);
        }

        $data->query->where(array("#menuId = '[#1#]'", $form->rec->menuId));
    }


    /**
     * Връща възможните опции за менюто, в което може да се намира дадената статия, 
     * в зависимост от езика
     */
    static function getMenuOpt($lang = NULL)
    {
        if(!$lang) {
            $lang = cms_Content::getLang();
        }

        $cQuery = cms_Content::getQuery();
 
        $cQuery->where("#lang = '{$lang}'");
         
        $cQuery->orderBy('#menu');
        
        $options = array();

        while($cRec = $cQuery->fetch("#source = " . self::getClassId())) {
            $options[$cRec->id] = $cRec->menu;
        }

        return $options;
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
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        if($menuId = $data->form->rec->menuId) {
            $lang = cms_Content::fetchField($menuId, 'lang');
        }
        
        $data->form->setOptions('menuId', self::getMenuOpt($lang));
    }


    /**
     * Изпълнява се след преобразуването към вербални стойности на полетата на записа
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {  
        $row->title = ht::createLink($row->title, array('A', 'a', $rec->vid ? $rec->vid : $rec->id));
    }


    /**
     * Екшън за разглеждане на статия
     */
    function act_Article()
    {   
        Mode::set('wrapper', 'cms_Page');
        
        $conf = core_Packs::getConfig('cms');
		$ThemeClass = cls::get($conf->CMS_THEME);
        
		if(Mode::is('screenMode', 'narrow')) {
            Mode::set('cmsLayout', $ThemeClass->getNarrowArticleLayout());
        } else {
            Mode::set('cmsLayout', $ThemeClass->getArticleLayout());
        }
		
        $id = Request::get('id', 'int'); 
        
        if(!$id || !is_numeric($id)) { 
            $menuId =  Mode::get('cMenuId');

            if(!$menuId) {
                $menuId = Request::get('menuId', 'int');
            }
            if(!$menuId) {
                return new Redirect(array('Index'));
            }
        } else {
            // Ако има, намира записа на страницата
            $rec = self::fetch($id);
        }
        
        if($rec) { //bp(toUrl(getCurrentUrl()));

            $menuId = $rec->menuId;

            $lArr = explode('.', self::getVerbal($rec, 'level'));

            $content = new ET('[#1#]', $desc = self::getVerbal($rec, 'body'));
            
            // Рендираме тулбара за споделяне
            //$content->append(new ET("<div style='margin-top:15px;margin-bottom:15px;clear:both;'>[#1#]</div>", $conf->CMS_SHARE));
            $content->replace(toUrl(getCurrentUrl()), 'SOC_URL');
            $content->replace(self::getVerbal($rec, 'title'), 'SOC_TITLE');
            $content->replace('article', 'SOC_SUMMARY');
            
            $ptitle = self::getVerbal($rec, 'title') . " » ";
 
            $content->prepend($ptitle, 'PAGE_TITLE');
            
        	// Подготвяме информаията за ографа на статията
            $ogp = $this->prepareOgraph($rec);
        }

        if(!$content) $content = new ET();

        // Подготвя навигацията
        $query = self::getQuery();
        
        if($menuId) {
            $query->where("#menuId = {$menuId}");
        }

        $query->orderBy("#level");

        $navTpl = new ET();
        
        $cnt = 0;

        while($rec1 = $query->fetch()) {

            $cnt++;
            
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

                $content = new ET('[#1#]', $desc = self::getVerbal($rec, 'body'));

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
                $navTpl->append(ht::createLink($title, array('A', 'a', $rec1->vid ? $rec1->vid : $rec1->id)));
            } else {
               $navTpl->append($title);
            }
            
            if(self::haveRightFor('edit', $rec1)) {
                $navTpl->append('&nbsp;');
                $navTpl->append(
                    ht::createLink( '<img src=' . sbf("img/16/edit.png") . ' width="12" height="12">', 
                                    array('A', 'Edit', $rec1->id, 'ret_url' => array('A', 'a', $rec1->id) ))); 
            }

            $navTpl->append("</div>");

        }
        
        if(self::haveRightFor('add')) {
            $navTpl->append( "<div style='padding:2px; border:solid 1px #ccc; background-color:#eee; margin-top:10px;font-size:0.7em'>");
            $navTpl->append(ht::createLink( tr('+ добави страница'), array('cms_Articles', 'Add', 'menuId' => $menuId, 'ret_url' => array('cms_Articles', 'Article', 'menuId' => $menuId))));
            $navTpl->append( "</div>");
        }
		
        Mode::set('cMenuId', $menuId);

        if($menuId) {
            cms_Content::setLang(cms_Content::fetchField($menuId, 'lang'));
        }
        
        if($cnt + Mode::is('screenMode', 'wide') > 1) {
            $content->append($navTpl, 'NAVIGATION');
        }
        
        $richText = cls::get('type_RichText');
        $desc = ht::escapeAttr(str::truncate(ht::extractText($desc), 200, FALSE));

        $content->replace($desc, 'META_DESCRIPTION');

        if($ogp){
        	
        	  // Генерираме ограф мета таговете
        	  $ogpHtml = ograph_Factory::generateOgraph($ogp);
        	  $content->append($ogpHtml);
        }
        
        if($rec) {
            if(core_Packs::fetch("#name = 'vislog'")) {
                vislog_History::add($rec->title);
            }
        }

        // Добавяне на параметрите за социално споделяне
        // $content->append('????', 'SOC_URL');
		
        return $content; 
    }
    
    
    /**
     * Подготвя Информацията за генериране на Ографа
     * @param stdClass $rec 
     * @return stdClass $ogp
     */
    function prepareOgraph($rec)
    {
    	$ogp = new stdClass();
    	$conf = core_Packs::getConfig('cms');
    	
    	// Добавяме изображението за ографа ако то е дефинирано от потребителя
        if($conf->CMS_OGRAPH_IMAGE != '') {
        	
	        $file = fileman_Files::fetchByFh($conf->CMS_OGRAPH_IMAGE);
	        $type = fileman_Files::getExt($file->name);
	        
	        $attr = array('isAbsolute' => TRUE, 'qt' => '');
        	$size = array(200, 'max'=>TRUE);
	        $imageURL = thumbnail_Thumbnail::getLink($file->fileHnd, $size, $attr);
	    	$ogp->imageInfo = array('url'=> $imageURL,
	    						    'type'=> "image/{$type}",
	    						 	);
        }
        				 
    	$richText = cls::get('type_RichText');
    	$desc = ht::extractText($richText->toHtml($rec->body));
    		
    	// Ако преглеждаме единична статия зареждаме и нейния Ograph
	    $ogp->siteInfo = array('Locale' =>'bg_BG',
	    				  'SiteName' =>'bgerp.com',
	    	              'Title' => self::getVerbal($rec, 'title'),
	    	              'Description' => $desc,
	    	              'Type' =>'article',
	    				  'Url' =>toUrl(getCurrentUrl(), 'absolute'),
	    				  'Determiner' =>'the',);
	        
	    // Създаваме Open Graph Article  обект
	    $ogp->recInfo = array('published' => $rec->createdOn);
	    
    	return $ogp;
    }


    /**
     *
     */
    static function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec = NULL, $userId = NULL)
    {
        if($rec->state == 'active' && $action == 'delete') {
            $roles = 'no_one';
        }
    }



    /**********************************************************************************************************
     *
     * Интерфейс cms_SourceIntf
     *
     **********************************************************************************************************/


    /**
     * Връща URL към публичната част (витрината), отговаряща на посоченото меню
     */
    function getContentUrl($menuId)
    {
        $query = self::getQuery();
        $query->where("#menuId = {$menuId}");
        $query->orderBy("#level");

        $rec = $query->fetch("#menuId = {$menuId} AND #body != ''");

        if($rec) {
            return toUrl(array('A', 'a', $rec->vid ? $rec->vid : $rec->id));
        } else {
            return NULL ;
        }
    }


    /**
     * Връща URL към вътрешната част (работилницата), отговарящо на посочената точка в менюто
     */
    function getWorkshopUrl($menuId)
    {
        $url = array('cms_Articles', 'list', 'menuId' => $menuId);
 
        return $url;
    }


}