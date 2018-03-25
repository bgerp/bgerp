<?php 


/**
 * Букмаркване на линкове
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_Bookmark extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = "Отметки";


    /**
     * Заглавие в ед. ч.
     */
    public $singleTitle = "Отметка";

    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'powerUser';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'powerUser';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'powerUser';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'title';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'bgerp_Wrapper, plg_Created, plg_Modified, plg_RowTools2, plg_Search, plg_Sorting, plg_StructureAndOrder,plg_RemoveCache';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'url=Линк, color, modifiedOn=Последно';
    

    public $saoTitleField = 'url';

    static $curRec;

    const CACHE_KEY = 'BookmarksPerUser';
    
    /**
     * Полета на модела
     */
    public function description()
    {   
        $this->FLD('type', 'enum(,bookmark,group)', 'caption=Тип, input=hidden,silent');
        $this->FLD('user', 'user(roles=powerUser, rolesForTeams=admin, rolesForAll=ceo)', 'caption=Потребител, mandatory');
        $this->FLD('title', 'varchar', 'caption=Заглавие, silent, mandatory');
        $this->FLD('url', 'text', 'caption=URL, silent, mandatory');
        $this->FLD('color', 'color_Type', 'caption=Цвят');

        $this->setDbUnique('user, title');
    }


    /**
     * Рендира основното меню на страницата
     */
    static function renderBookmarks()
    {
        $screen = Mode::is('screenMode', 'narrow') ? 'm' : 'd';
 
        $userId = core_Users::getCurrent();

        $tpl = core_Cache::get(self::CACHE_KEY, $userId);

        $cookie = '';
        if (isset($_COOKIE['bookmarkInfo'])) {
            $cookie = $_COOKIE['bookmarkInfo'];
        }

        if(!$tpl || ($tpl->cookie != $cookie . $screen) || TRUE) {
            $tpl = new ET("<div class='sideBarTitle'>[#BOOKMARK_TITLE#][#BOOKMARK_BTN#]</div><div class='bookmark-links'>[#BOOKMARK_LINKS#]</div>");
            
            $cur = new stdClass();
            
            $links = bgerp_Bookmark::getLinks($cookie);
            $title = bgerp_Bookmark::getTitle();
            $btn = bgerp_Bookmark::getBtn();
            
            $tpl->append($title, 'BOOKMARK_TITLE');
            $tpl->append($links, 'BOOKMARK_LINKS');
            $tpl->append($btn, 'BOOKMARK_BTN');
            $tpl->cookie = $cookie . $screen;
            
	    	core_Cache::set(self::CACHE_KEY, $userId, $tpl, 2000);
        } 
   
        return $tpl;
    }


    /**
     * Функция за плъгина plg_RemoveCache
     */
    public function removeCache($rec)
    {
        return array(self::CACHE_KEY, $rec->user);
    }

    
    
    /**
     * Връща линк със заглавието
     * 
     * @return string
     */
    public static function getTitle()
    {
        $url = array();
        
        if (self::haveRightFor('list')) {
            $url = array(get_called_class(), 'list');
        }

        $img =  ht::createElement('img', array('src' => sbf('img/32/table-bg.png', ''), 'title' => 'Редактиране на връзките', 'width' => 20, 'height' => 20, 'alt' => 'edit bookmark'));
        $list = ht::createLink($img , $url, NULL, array('class' => 'bookmarkLink listBookmarkLink'));
        $title = "<span class='bookmarkText'>" . tr('Отметки') . "</span>".  $list ;
        
        return $title;
    }
    
    
    /**
     * Връща бутон за добавяне на букмарк
     */
    public static function getBtn()
    {
        if (self::haveRightFor('add')) {
            $url = toUrl(array(get_called_class(), 'add', 'ret_url' => TRUE));
            $sUrl = addslashes($url);
            
            $localUrl = addslashes(toUrl(getCurrentUrl(), 'local'));
            $icon = 'star-bg.png';

            if(self::$curRec) {  
                $url = toUrl(array(get_called_class(), 'edit', self::$curRec->id, 'ret_url' => TRUE));
                $sUrl = addslashes($url);
                $icon = 'edit-fav2.png';
            }


            $attr = array();
            $attr['onclick'] = "addParamsToBookmarkBtn(this, '{$sUrl}', '{$localUrl}'); return ;";

            $attr['class'] = 'bookmarkLink addBookmarkLink';
            $img =  ht::createElement('img', array('src' => sbf('img/32/' . $icon, ''), 'title' => tr('Добавяне на връзка'), 'width' => 20, 'height' => 20, 'alt' => 'add bookmark'));
            $tpl = ht::createLink($img, $url, FALSE, $attr);
        }
        
        return $tpl;
    }
	
	
	/**
	 * Връща всички линкове за съответния потребител
	 * 
	 * @return string
	 */
	public static function getLinks($cookie = NULL, $limit = NULL, $userId = NULL)
	{
	    if (!$userId) {
	        $userId = core_Users::getCurrent();
	    }
	    
	    if ($userId < 1) return ;
	    
	    $query = self::getQuery();
	    $query->where("#user = '{$userId}'");
	    
	    self::orderQuery($query);
	    
	    if (is_null($limit)) {
	        $limit = 60;
	    }
	    
	    if ($limit) {
	        $query->limit((int) $limit);
	    }
        
        $localUrl = str_replace('/default', '', toUrl(getCurrentUrl(), 'local'));
        
        $opened = array();
        if($cookie) {
            $cArr = explode(',', trim($cookie, ','));
            foreach($cArr as $b) {
                $b = str_replace('bm', '', $b);
                $opened[$b] = $b;
            }
        }
 
        $res = '<ul>';
	    while ($rec = $query->fetch()) {
	        
	        $title = self::getVerbal($rec, 'title');
            
            $attr = array();

            if($rec->color) {
                $attr['style'] = "color:" . $rec->color;
            }
         
            // Затваряме група
            if($openGroup > 0 && $openGroup !=  $rec->saoParentId) {
                $res .= "</ul></ul>";
                $openGroup = NULL;
            }

            if($rec->type == 'group') {
                $class = 'ul-group';
                $display = "style='display:none;'";
                if($opened[$rec->id]) {
                    $class .= ' open';
                    $display = '';
                }
                $attr['class'] = 'bookmark-group';
                $res .= "<ul class='{$class}' id='bm{$rec->id}'>\n" .
                         ht::createElement('li', $attr, $title) .
                        "\n<ul class='subBookmark' {$display}>";
                $openGroup = $rec->id;
            } else {

                $link = self::getLinkFromUrl($rec->url, $title, $attr);

                if(stripos($rec->url, $localUrl) !== FALSE) {
                    $attr['class'] = 'active';
                    $attr['style'] .= ';background-color:#503A66';
                    self::$curRec = $rec;
                }  
                $res .= ht::createElement('li', $attr, $link); 
            }            
	    }

	    $res .= '</ul>';

	    return $res;
	}
    

	/**
	 * Подрежда записите в зависимост от подредбата на потребители и броя на показванията
	 * 
	 * @param core_Query $query
	 */
	protected static function orderQuery($query)
	{
	    $query->orderBy('saoOrder');
	}
	
	
	/**
	 * 
	 * 
	 * @param string $url
	 * @param string $title
	 * 
	 * @return string
	 */
    public static function getLinkFromUrl($url, $title = NULL, $attr = array())
    {
        if (!preg_match('/^http[s]?\:\/\//i', $url) && (strpos($url, Request::get('App')) === 0)) {
            try {
                $urlArr = parseLocalUrl($url);
                $lUrl = toUrl($urlArr);
                $attr['class'] = 'bookmark-local-url';
            } catch (core_exception_Expect $e) {
                $lUrl = array();
                $attr['class'] = 'bookmark-wrong-url';
            }
	    } else {
            if(core_Packs::isInstalled('remote')) {
                
                static $auths;

                expect($cu = core_Users::getCurrent());
                if(!$auths) {
                    $aQuery = remote_Authorizations::getQuery();
                    while($aRec = $aQuery->fetch("#userId = {$cu}")) {
                        if(is_object($aRec->data) && $aRec->data->lKeyCC) {
                            $aUrl = rtrim(strtolower($aRec->url), '/ ');
                            $auths[$aRec->id] = $aUrl;
                        }
                    }
                }
                
                if($auths && is_array($auths)) {
                    foreach($auths as $id => $aUrl) {
                        if(strpos($url, $aUrl) === 0) {
                            $url =  array('remote_BgerpDriver', 'Autologin', $id, 'url' => $url);
                            $target = NULL;
                            break;
                        }
                    }
                }
            }

	        $lUrl = $url;
            if($target) {
	            $attr['target'] = $target;
            }
            $attr['class'] = 'bookmark-external-url';
	    }
	    
	    if (!isset($title)) {
	        $title = $url;
	    }
	    
	    return ht::createLink($title, $lUrl, NULL, $attr);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        $data->toolbar->addBtn('Група', array($mvc, 'add', 'type' => 'group', 'ret_url' => TRUE), FALSE, "ef_icon=img/16/plus.png,title=Добавяне на група от букмарки");
    }

    
    /**
     * Подготовка на филтър формата
     * 
     * @param bgerp_Bookmark $mvc
     * @param object $data
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->addAttr('user', array('refreshForm' => 'refreshForm'));
        
        $data->listFilter->title = 'Търсене';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->showFields = 'search, user';
        
        $data->listFilter->input($data->listFilter->showFields);
        
        $data->listFilter->setDefault('user', core_Users::getCurrent());
        
        $rec = $data->listFilter->rec;
        
        $userId = (int) $rec->user;
        
        $data->query->where("#user = {$userId}");
        self::orderQuery($data->query);
        
        $data->listFilter->fields['user']->refreshForm = 'refreshForm';
    }
	
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Премахваме броя на нотификациите пред стринга и името на приложението
        if (!$data->form->rec->id && !$data->form->isSubmitted() && $data->form->rec->title) {
            $data->form->rec->title = preg_replace('/^\([0-9]*\) /', '', $data->form->rec->title);
            
            $delimiter = ' « ';
            $titleArr = explode($delimiter, $data->form->rec->title);
            if (count($titleArr) > 1) {
                array_pop($titleArr);
            }
            
            $data->form->rec->title = implode($delimiter, $titleArr);
        }

        $form = $data->form;
        $rec = $form->rec;
        if(!$rec->type) {
            $rec->type = 'bookmark';
        }

        if($rec->type != 'bookmark') {
            $form->setField('url', 'input=none');
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Само admin да може да изтрива/редактира записи на другите
        if ($rec){
            if ($action == 'edit' || $action == 'delete') {
                if (!haveRole('admin')) {
                    if ($rec->user != $userId) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $title = $mvc->getVerbal($rec, 'title');
        
        if($rec->type == 'group') {
            $row->url = "<span class='linkWithIcon' style=\"" . ht::getIconStyle('img/16/plus.png') . "\">{$title}</span>";
        } else {
            $row->url = self::getLinkFromUrl($rec->url, $title);
        }
    }


    /**
     * Необходим метод за подреждането
     */
    public static function getSaoItems($rec)
    {
        setIfNot($rec->user, core_Users::getCurrent());
        $query = self::getQuery();
        $query->where("#user = {$rec->user}");
        while($rec = $query->fetch()) {
            $res[$rec->id] = $rec;
        }

        return $res;
    }


    /**
     * Имплементация на метод, необходим за plg_StructureAndOrder
     */
    public function saoCanHaveSublevel($rec, $newRec = NULL)
    {
        if($newRec && $newRec->type == 'group') return FALSE;

        $res = ($rec->type == 'group') && ($rec->saoLevel == 1);
        
        return $res;
    }

}
