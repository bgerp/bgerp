<?php 

/**
 * Букмаркване на линкове
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_Bookmark extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Отметки';
    
    
    /**
     * Заглавие в ед. ч.
     */
    public $singleTitle = 'Отметка';
    
    
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
    public $searchFields = 'title';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'bgerp_Wrapper, plg_Created, plg_Modified, plg_RowTools2, plg_Search, plg_Sorting, plg_StructureAndOrder,plg_RemoveCache';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'url=Линк, color, modifiedOn=Последно';
    
    
    public $saoTitleField = 'url';
    
    public static $curRec;
    
    const CACHE_KEY = 'BookmarksPerUserLinks4';
    
    
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
    public static function renderBookmarks()
    {
        $cookie = $_COOKIE['bookmarkInfo'];

        $screen = Mode::is('screenMode', 'narrow') ? 'm' : 'd';
        
        $userId = core_Users::getCurrent();
        
        $dataLinks = core_Cache::get($userId . '|' . self::CACHE_KEY, $screen);
        
        if (!$dataLinks && !is_array($dataLinks)) {
            $dataLinks = bgerp_Bookmark::prepareLinks();
            core_Cache::set($userId . '|' . self::CACHE_KEY, $screen, $dataLinks, 2000);
        }

        $htmlLinks = self::renderLinks($dataLinks, $cookie); 
        
        $tpl = new ET("<div class='sideBarTitle'>[#BOOKMARK_TITLE#][#BOOKMARK_BTN#]</div><div class='bookmark-links'>[#BOOKMARK_LINKS#]</div>");

        $title = bgerp_Bookmark::getTitle();
        $btn = bgerp_Bookmark::getBtn();
        
        $tpl->append($title, 'BOOKMARK_TITLE');
        $tpl->append($htmlLinks, 'BOOKMARK_LINKS');
        $tpl->append($btn, 'BOOKMARK_BTN');
        $tpl->cookie = $cookie . $screen;
        
        return $tpl;
    }
    
    
    /**
     * Функция за плъгина plg_RemoveCache
     */
    public function removeCache($rec)
    {
        $screen = Mode::is('screenMode', 'narrow') ? 'm' : 'd';

        return array($rec->user . '|' . self::CACHE_KEY, $screen);
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
        
        $img = ht::createElement('img', array('src' => sbf('img/32/table-bg.png', ''), 'title' => 'Редактиране на връзките', 'width' => 22, 'height' => 22, 'alt' => 'edit bookmark'));
        $list = ht::createLink($img, $url, null, array('class' => 'bookmarkLink listBookmarkLink'));
        $title = "<span class='bookmarkText'>" . tr('Отметки') . '</span>'.  $list ;
        
        return $title;
    }
    
    
    /**
     * Връща бутон за добавяне на букмарк
     */
    public static function getBtn()
    {
        $tpl = new ET();
        
        if (self::haveRightFor('add')) {
            $url = toUrl(array(get_called_class(), 'add', 'ret_url' => true));
            $sUrl = addslashes($url);
            $cUrl = getCurrentUrl();
            unset($cUrl['ret_url']);
            $localUrl = addslashes(toUrl($cUrl, 'local'));
            $icon = 'star-bg.png';
            $title = 'Добавяне на връзка';
            
            if (self::$curRec) {
                $url = toUrl(array(get_called_class(), 'edit', self::$curRec->id, 'ret_url' => true));
                $sUrl = addslashes($url);
                $icon = 'edit-fav2.png';
                $title = 'Редактиране на връзка';
                
                $attr = array();
                $attr['class'] = 'bookmarkLink addBookmarkLink';
                $img = ht::createElement('img', array('src' => sbf('img/32/delete-bg.png', ''), 'title' => 'Изтриване на връзка', 'width' => 22, 'height' => 22, 'alt' => 'add bookmark'));
                $tpl->append(ht::createLink($img, array(get_called_class(), 'delete', self::$curRec->id, 'ret_url' => true), 'Наистина ли желаете да премахнете връзката?', $attr));
            }
            
            $attr = array();
            $attr['onclick'] = "addParamsToBookmarkBtn(this, '{$sUrl}', '{$localUrl}'); return ;";
            
            $attr['class'] = 'bookmarkLink addBookmarkLink';
            $img = ht::createElement('img', array('src' => sbf('img/32/' . $icon, ''), 'title' => $title, 'width' => 22, 'height' => 22, 'alt' => 'add bookmark'));
            $tpl->append(ht::createLink($img, $url, false, $attr));
        }
        
        return $tpl;
    }
    
    
    /**
     * Връща всички линкове за съответния потребител
     *
     * @return string
     */
    public static function prepareLinks($limit = null, $userId = null)
    {
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        if ($userId < 1) {
            
            return ;
        }
        
        $query = self::getQuery();
        $query->where("#user = '{$userId}'");
        
        if (is_null($limit)) {
            $limit = 60;
        }
        
        if ($limit) {
            $query->limit((int) $limit);
        }
        
        
        $res = array();

        while ($rec = $query->fetch()) {
            $rec->title = self::getVerbal($rec, 'title');
            $attr = array();
            if ($rec->color) {
                $attr['style'] = 'color:' . $rec->color;
            }
            $rec->linkHtml =  self::getLinkFromUrl($rec->url, $rec->title, $attr);
            $res[] = $rec;
        }
        
        return $res;
    }


    /**
     * Рендира предварително подготвен масив
     */
    public static function renderLinks($links, $cookie = null)
    {
        $cUrl = getCurrentUrl();
        unset($cUrl['ret_url']);
        $localUrl = str_replace(array('/default', '//'), array('', '/'), toUrl($cUrl, 'local'));
        
        $opened = array();

        if ($cookie) {
            $cArr = explode(',', trim($cookie, ','));
            foreach ($cArr as $b) {
                $b = str_replace('bm', '', $b);
                $opened[$b] = $b;
            }
        }
        
        $res = '<ul>';

        foreach($links as $rec) {

            $title = $rec->title;

            $attr = array();
            
            if ($rec->color) {
                $attr['style'] = 'color:' . $rec->color;
            }
            
            // Затваряме група
            if ($openGroup > 0 && $openGroup != $rec->saoParentId) {
                $res .= '</ul></ul>';
                $openGroup = null;
            }
            
            if ($rec->type == 'group') {
                $class = 'ul-group';
                $display = "style='display:none;'";
                if ($opened[$rec->id]) {
                    $class .= ' open';
                    $display = '';
                }
                $attr['class'] = 'bookmark-group';
                $res .= "<ul class='{$class}' id='bm{$rec->id}'>\n" .
                         ht::createElement('li', $attr, $title) .
                        "\n<ul class='subBookmark' {$display}>";
                $openGroup = $rec->id;
            } else {
                $link = $rec->linkHtml;
                $rec->url = str_replace('/default', '', $rec->url);
                if (stripos(str_replace('//', '/', $rec->url), $localUrl) !== false) {
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
     *
     *
     * @param string $url
     * @param string $title
     *
     * @return string
     */
    public static function getLinkFromUrl($url, $title = null, $attr = array())
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
            if (core_Packs::isInstalled('remote')) {
                static $auths;
                
                expect($cu = core_Users::getCurrent());
                if (!$auths) {
                    $aQuery = remote_Authorizations::getQuery();
                    while ($aRec = $aQuery->fetch("#userId = {$cu}")) {
                        if (is_object($aRec->data) && $aRec->data->lKeyCC) {
                            $aUrl = rtrim(strtolower($aRec->url), '/ ');
                            $auths[$aRec->id] = $aUrl;
                        }
                    }
                }
                
                if ($auths && is_array($auths)) {
                    foreach ($auths as $id => $aUrl) {
                        if (strpos($url, $aUrl) === 0) {
                            $url = array('remote_BgerpDriver', 'Autologin', $id, 'url' => $url);
                            $target = null;
                            break;
                        }
                    }
                }
            }
            
            $lUrl = $url;
            if ($target) {
                $attr['target'] = $target;
            }
            $attr['class'] = 'bookmark-external-url';
        }
        
        if (!isset($title)) {
            $title = $url;
        }
        
        return ht::createLink($title, $lUrl, null, $attr);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        $data->toolbar->addBtn('Група', array($mvc, 'add', 'type' => 'group', 'ret_url' => true), false, 'ef_icon=img/16/plus.png,title=Добавяне на група от букмарки');
    }
    
    
    /**
     * Подготовка на филтър формата
     *
     * @param bgerp_Bookmark $mvc
     * @param object         $data
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
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
        
        $data->listFilter->fields['user']->refreshForm = 'refreshForm';
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Премахваме броя на нотификациите пред стринга и името на приложението
        if (!$data->form->rec->id && !$data->form->isSubmitted() && $data->form->rec->title) {
            $data->form->rec->title = preg_replace('/^\([0-9]*\) /', '', $data->form->rec->title);
            
            $delimiter = ' « ';
            $titleArr = explode($delimiter, $data->form->rec->title);
            if (countR($titleArr) > 1) {
                array_pop($titleArr);
            }
            
            $data->form->rec->title = implode($delimiter, $titleArr);
        }
        
        $form = $data->form;
        $rec = $form->rec;
        if (!$rec->type) {
            $rec->type = 'bookmark';
        }
        
        if ($rec->type != 'bookmark') {
            $form->setField('url', 'input=none');
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        // Само admin да може да изтрива/редактира записи на другите
        if ($rec) {
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
        
        if ($rec->type == 'group') {
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
        $res = array();
        $query->where("#user = {$rec->user}");
        while ($rec = $query->fetch()) {
            $res[$rec->id] = $rec;
        }
        
        return $res;
    }
}
