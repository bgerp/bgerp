<?php


/**
 * Публични статии
 *
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cms_Articles extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Публични статии';
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Публична статия';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_Modified, plg_Search, plg_State2, plg_RowTools2, plg_Printing, cms_Wrapper, plg_Sorting, cms_VerbalIdPlg, change_Plugin';
    
    
    public $vidFieldName = 'vid';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'cms_SourceIntf';
    
    
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'level, menuId,  title, body, footerTitleLink';
    
    
    /**
     * Кой може да променя записа
     */
    public $canChangerec = 'cms,admin,ceo';
    
    
    /**
     * Кой може да променя записа
     */
    public $canChangestate = 'cms,admin,ceo';
    
    
    public $canEdit = 'no_one';
    
    
    public $canDelete = 'admin,ceo,cms';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'cms/tpl/SingleLayoutArticles.shtml';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'level,title,menuId,state,modifiedOn,modifiedBy';
    
    
    /**
     * Поле за инструментите на реда
     */
    public $rowToolsField = '✍';
    
    
    /**
     * По кои полета да се прави пълнотекстово търсене
     */
    public $searchFields = 'title,body';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'cms,admin,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,cms';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,cms';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'cms,admin,ceo';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('level', 'order(11)', 'caption=№,tdClass=rowtools-column,mandatory');
        $this->FLD('menuId', 'key(mvc=cms_Content,select=menu)', 'caption=Меню,mandatory,silent');
        $this->FLD('title', 'varchar', 'caption=Заглавие,mandatory,width=100%');
        $this->FLD('body', 'richtext(bucket=Notes,hideTextAfterLength=10000000)', 'caption=Текст,column=none');
        
        $this->FLD('footerTitleLink', 'varchar', 'caption=Показване във футъра->Заглавие,autohide');
        
        $this->setDbUnique('menuId,level');
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    public function on_AfterPrepareListFilter($mvc, $data)
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

        $domainId = cms_Domains::getPublicDomain('id');
        $opt = cms_Content::getMenuOpt($mvc, $domainId);
        $form->setOptions('menuId', $opt);
        
        $form->setField('menuId', 'refreshForm');
        
        if (countR($opt) == 0) {
            redirect(array('cms_Content'), false, '|Моля въведете поне един елемент от менюто');
        }
        
        if (!$opt[$form->rec->menuId]) {
            $form->rec->menuId = key($opt);
        }
        
        $data->query->where(array("#menuId = '[#1#]'", $form->rec->menuId));
        
        $data->query->orderBy('#menuId,#level');
    }
    
    
    /**
     * Подготвя някои полета на формата
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        if ($id = $data->form->rec->id) {
            $rec = self::fetch($id);
            $cRec = cms_Content::fetch($rec->menuId);
            cms_Domains::selectCurrent($cRec->domainId);
        }

        $domainId = cms_Domains::getPublicDomain('id');
        $data->form->setOptions('menuId', arr::combine(array('' => ''), cms_Content::getMenuOpt($mvc, $domainId)));
    }
    
    
    /**
     * Изпълнява се след преобразуването към вербални стойности на полетата на записа
     */
    public function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        if (trim($rec->body) && $fields['-list'] && $mvc->haveRightFor('show', $rec)) {
            $row->title = ht::createLink($row->title, toUrl(self::getUrl($rec)), null, 'ef_icon=img/16/monitor.png');
        }
    }
    
    
    /**
     * Връща записа на предходната или на следващата статия от даденото меню спрямо тази
     */
    public static function getPrevOrNext($rec, $dir = 1)
    {
        $query = self::getQuery();
        $query->limit(1);
        if ($dir > 0) {
            $query->orderBy('level');
            $query->where("#level > {$rec->level}");
        } else {
            $query->orderBy('level', 'DESC');
            $query->where("#level < {$rec->level}");
        }
        $query->where("#menuId = {$rec->menuId}");
        
        $nextOrPrevRec = $query->fetch();
        
        return $nextOrPrevRec;
    }
    
    
    /**
     * Екшън за разглеждане на статия
     */
    public function act_Article()
    {
        Mode::set('wrapper', 'cms_page_External');
        
        $conf = core_Packs::getConfig('cms');
        
        if (Mode::is('screenMode', 'narrow')) {
            Mode::set('cmsLayout', 'cms/themes/default/ArticlesNarrow.shtml');
        } else {
            Mode::set('cmsLayout', 'cms/themes/default/Articles.shtml');
        }
        
        $id = Request::get('id', 'int');
        
        if (!$id || !is_numeric($id)) {
            $menuId = Mode::get('cMenuId');
            
            if (!$menuId) {
                $menuId = Request::get('menuId', 'int');
            }
            if (!$menuId) {
                
                return new Redirect(array('Index'));
            }
        } else {
            // Ако има, намира записа на страницата
            $rec = self::fetch($id);
        }
        
        if (is_object($rec) && $rec->state != 'active' && !haveRole('admin,ceo,cms')) {
            error('404 Липсваща страница');
        }
        
        if ($rec && !trim($rec->body)) {
            $nextRec = self::getPrevOrNext($rec);
            
            if ($nextRec) {
                $url = self::getUrl($nextRec);
                
                return new Redirect($url);
            }
        }
        
        if ($rec) {
            $rec->body = trim($rec->body);
            
            $menuId = $rec->menuId;
            
            $lArr = explode('.', self::getVerbal($rec, 'level'));
            
            $content = new ET('[#1#]', self::getVerbal($rec, 'body'));
        }
        
        // Задава текущото меню, съответстващо на страницата
        if ($menuId) {
            cms_Content::setCurrent($menuId);
        }
        
        if (!$content) {
            $content = new ET();
        }
        
        $navData = $this->prepareNavigation($rec, $menuId, $content, $lArr);
        
        
        // Подготвяме SEO елементите
        cms_Content::prepareSeo($rec, array('seoDescription' => $rec->body, 'seoTitle' => $rec->title));
        
        if ($navData->cnt + Mode::is('screenMode', 'wide') > 1) {
            $content->append($this->renderNavigation($navData), 'NAVIGATION');
        }
        
        // Задаване на SEO елементите
        cms_Content::renderSeo($content, $rec);
        
        // Линкове за следваща/предишна статия
        $prevLink = $nextLink = '';
        if ($navData->prev) {
            $prevLink = ht::createLink('«&nbsp;' . $navData->prev->title, $navData->prev->url);
        }
        if ($navData->next) {
            $nextLink = ht::createLink($navData->next->title . '&nbsp;»', $navData->next->url);
        }
        
        if ($prevLink || $nextLink) {
            $content->append("<div class='prevNextNav'><div style='float:left;margin-right:5px;'>{$prevLink}</div><div style='float:right;margin-left:5px;'>{$nextLink}</div></div>");
        }
        
        
        if ($rec && $rec->id) {
            if (core_Packs::fetch("#name = 'vislog'")) {
                vislog_History::add($rec->title);
            }
            
            // Добавя канонично URL
            $url = self::getUrl($rec, true);
            $url = toUrl($url, 'absolute');
            cms_Content::addCanonicalUrl($url, $content);
        }
        
        // Страницата да се кешира в браузъра за 1 час
        Mode::set('BrowserCacheExpires', $conf->CMS_BROWSER_CACHE_EXPIRES);
        
        Mode::set('cmsNav', false);
        
        return $content;
    }
    
    
    /**
     * Подготовка на данните за навигацията
     */
    public function prepareNavigation(&$rec, $menuId, &$content, $lArr)
    {
        // Подготвя навигацията
        $query = self::getQuery();
        
        if ($menuId) {
            $query->where("#menuId = {$menuId}");
        }
        
        $query->orderBy('#level');
        
        $navData = new stdClass();
        $navData->cnt = 0;
        
        if (($q = Request::get('q')) && $menuId > 0 && !$rec) {
            $rec = new stdClass();
            $navData->q = $q;
            $rec->menuId = $menuId;
            $lArr = array('a', 'a', 'a');
            
            $content->append(cms_Content::renderSearchResults($menuId, $q));
            
            vislog_History::add("Търсене в статиите: {$q}");
        }
        
        Mode::set('cmsNav', true);
        
        if (haveRole('admin,ceo,cms') && isset($rec->id)) {
            $query->where("#state = 'active' OR #id = {$rec->id}");
        } else {
            $query->where("#state = 'active'");
        }
        
        $flagSelected = false;
        $navData->next = $navData->prev = null;
        
        while ($rec1 = $query->fetch()) {
            $navData->cnt++;
            
            $lArr1 = explode('.', self::getVerbal($rec1, 'level'));
            
            if ($lArr) {
                if ($lArr1[2] && (($lArr[0] != $lArr1[0]) || ($lArr[1] != $lArr1[1]))) {
                    continue;
                }
            }
            
            $title = self::getVerbal($rec1, 'title');
            
            if (!$rec && $rec1->body) {
                
                // Това е първата срещната статия
                $id = $rec1->id;
                $rec = self::fetch($id);
                $menuId = $rec->menuId;
                $lArr = explode('.', self::getVerbal($rec, 'level'));
                $content = new ET('[#1#]', self::getVerbal($rec, 'body'));
                $ptitle = self::getVerbal($rec, 'title') . ' » ';
                $content->prepend($ptitle, 'PAGE_TITLE');
            }
            
            $l = new stdClass();
            
            $l->selected = ($rec->id == $rec1->id);
            
            if ($lArr1[2]) {
                $l->level = 3;
            } elseif ($lArr1[1]) {
                $l->level = 2;
            } elseif ($lArr1[0]) {
                $l->level = 1;
            }
            
            if (trim($rec1->body)) {
                $l->url = self::getUrl($rec1);
            }
            
            $l->title = $title;
            
            if ($this->haveRightFor('changerec', $rec1)) {
                // Вземаме линка за промяна на записа
                $l->editLink = $this->getChangeLink($rec1->id);
            }
            
            if ($rec1->state == 'closed') {
                $l->closed = true;
            }
            
            $navData->links[] = $l;
            
            if ($l->selected) {
                $flagSelected = true;
            } elseif ($l->url) {
                if (!$flagSelected) {
                    $navData->prev = $l;
                }
                if ($flagSelected && !$navData->next) {
                    $navData->next = $l;
                }
            }
        }
        
        $navData->searchCtr = 'cms_Articles';
        $navData->searchAct = 'Article';
        
        // Оцветяваме ако има търсене
        if ($q && isset($rec->id)) {
            plg_Search::highlight($content, $q, 'searchContent');
        }
        
        $navData->menuId = $rec->menuId;
        
        if (self::haveRightFor('add')) {
            $navData->addLink = ht::createLink(tr('+ добави страница'), array('cms_Articles', 'Add', 'menuId' => $menuId,
                'ret_url' => array('cms_Articles', 'Article', 'menuId' => $menuId)));
        }
        
        return $navData;
    }
    
    
    /**
     * $data->items = $array( $rec{$level, $title, $url, $isSelected, $icon, $editLink} )
     * $data->new = {$caption, $url}
     *
     */
    public function renderNavigation_($data)
    {
        $navTpl = new ET();
        $noRootClass = ($data->hasRootNavigation) ? '' : 'noRoot';

        if(is_array($data->links)){
            foreach ($data->links as $l) {
                $selected = ($l->selected) ? 'sel_page' : '';
                if ($l->closed) {
                    $aAttr = array('style' => 'color:#aaa !important;');
                } else {
                    $aAttr = array();
                }

                $navTpl->append("<div class='nav_item {$noRootClass} level{$l->level} {$selected}'>");
                if ($l->url) {
                    $navTpl->append(ht::createLink($l->title, $l->url, null, $aAttr));
                } else {
                    $navTpl->append('<span>' . $l->title .'</span>');
                }
                if ($selected) {
                    $currentPage = $l->title;
                }
                if ($l->editLink) {
                    // Добавяме интервал
                    $navTpl->append('&nbsp;');

                    // Добавяме линка
                    $navTpl->append($l->editLink);
                }
                $navTpl->append('</div>');
            }
        }

        if ($data->addLink) {
            $navTpl->append("<div class='addPage'>");
            $navTpl->append($data->addLink);
            $navTpl->append('</div>');
        }
        
        if ($data->menuId > 0 && ($data->searchCtr)) {
            if (!$data->q) {
                $data->q = Request::get('q');
            }
            $searchForm = cls::get('core_Form', array('method' => 'GET'));
            $searchForm->layout = new ET(tr(getFileContent('cms/tpl/SearchForm.shtml')));
            $searchForm->layout->replace(toUrl(array($data->searchCtr, $data->searchAct)), 'ACTION');
            $searchForm->layout->replace(sbf('img/16/find.png', ''), 'FIND_IMG');
            $searchForm->layout->replace(ht::escapeAttr($data->q), 'VALUE');
            $searchForm->setHidden('menuId', $data->menuId);
            $navTpl->replace($searchForm->renderHtml(), 'SEARCH_BOX');
            $toggleLink = ht::createLink('', null, null, array('ef_icon' => 'img/menu.png', 'class' => 'toggleLink'));
            $navTpl->replace($toggleLink, 'TOGGLE_BTN');
            $navTpl->replace($currentPage, 'CURRENT_PAGE');
        }
        
        if (Mode::is('screenMode', 'narrow')) {
            jquery_Jquery::run($navTpl, 'toggleNarrowMenu();', true);
        }
        
        return $navTpl;
    }
    
    
    /**
     * Какви са необходимите роли за съотвентото действие?
     */
    public static function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec = null, $userId = null)
    {
        if ($rec->state == 'active' && $action == 'delete') {
            $roles = 'no_one';
        } elseif ($rec->createdBy != core_Users::getCurrent() && $action == 'delete') {
            $roles = 'admin';
        }
        
        if ($action == 'show' && is_object($rec) && $rec->state != 'active') {
            $roles = 'admin,cms,ceo';
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
    public function getUrlByMenuId($menuId)
    {
        $query = self::getQuery();
        $query->orderBy('#level');
        
        $rec = $query->fetch("#menuId = {$menuId} AND #body != '' AND #state = 'active'");
        
        if ($rec) {
            
            return self::getUrl($rec);
        }
    }
    
    
    /**
     * Връща URL към посочената статия
     */
    public static function getUrl($rec, $canonical = false)
    {
        expect($rec->menuId, $rec);
        
        $domainId = cms_Content::fetch($rec->menuId)->domainId;
        $lang = cms_Domains::fetch($domainId)->lang;
        
        if ($lang == 'bg' || $lang == 'en') {
            $lang = ucfirst($lang);
            $res = array($lang, $rec->vid ? urlencode($rec->vid) : $rec->id, 'PU' => (haveRole('powerUser') && !$canonical) ? 1 : null);
        } else {
            $res = array('A', 'a', $rec->vid ? urlencode($rec->vid) : $rec->id, 'PU' => (haveRole('powerUser') && !$canonical) ? 1 : null);
        }
        
        return $res;
    }
    
    
    /**
     * Връща кратко URL към съдържание на статия
     */
    public static function getShortUrl($url)
    {
        $vid = urldecode($url['id']);
        
        if ($vid) {
            $id = cms_VerbalId::fetchId($vid, 'cms_Articles');
            
            if (!$id) {
                $id = self::fetchField(array("#vid = '[#1#]'", $vid), 'id');
            }
            
            if (!$id && is_numeric($vid)) {
                $id = $vid;
            }
            
            if ($id) {
                $rec = self::fetch($id);
                $domainId = cms_Content::fetch($rec->menuId)->domainId;
                if ($domainId && ($lg = cms_Domains::fetch($domainId)->lang)) {
                    $ctr = ucfirst($lg);
                    if (cls::load($ctr, true)) {
                        $url['Ctr'] = $ctr;
                        unset($url['Act']);
                    } else {
                        $url['Ctr'] = 'A';
                        $url['Act'] = 'a';
                    }
                    $url['id'] = $id;
                }
            }
        }
        
        unset($url['PU']);
        
        return $url;
    }
    
    
    /**
     * Връща връща масив със заглавия и URL-ta, които отговарят на търсенето
     */
    public static function getSearchResults($menuId, $q, $maxResults = 15)
    {
        $queryM = self::getQuery();
        $queryM->where("#menuId = {$menuId} AND #state = 'active'");
        $queryM->limit($maxResults);
        $queryM->orderBy('modifiedOn=DESC');
        $res = array();
        
        $query = clone($queryM);
        plg_Search::applySearch($q, $query, null, 5, 64);
        
        while ($r = $query->fetch()) {
            $title = str::cut($r->body, '[h1]', '[/h1]');
            if (strlen($r->title) > strlen($title) || (strlen($title) > 64)) {
                $title = $r->title;
            }
            
            $url = self::getUrl($r);
            $url['q'] = $q;
            
            $res[toUrl($url)] = (object) array('title' => $title, 'url' => $url);
        }
        
        if (countR($res) < $maxResults) {
            $query = clone($queryM);
            plg_Search::applySearch($q, $query, null, 9);
            
            while ($r = $query->fetch()) {
                $title = str::cut($r->body, '[h1]', '[/h1]');
                if (strlen($r->title) > strlen($title) || (strlen($title) > 64)) {
                    $title = $r->title;
                }
                
                $url = self::getUrl($r);
                $url['q'] = $q;
                
                $res[toUrl($url)] = (object) array('title' => $title, 'url' => $url);
            }
        }
        
        
        if (countR($res) < $maxResults) {
            $query = clone($queryM);
            plg_Search::applySearch($q, $query, null, 3);
            
            while ($r = $query->fetch()) {
                $title = str::cut($r->body, '[h1]', '[/h1]');
                if (strlen($r->title) > strlen($title) || (strlen($title) > 64)) {
                    $title = $r->title;
                }
                
                $url = self::getUrl($r);
                $url['q'] = $q;
                
                $res[toUrl($url)] = (object) array('title' => $title, 'url' => $url);
            }
        }
        
        return $res;
    }
    
    
    /**
     * Добавя ключовите думи от обектите в менюто към масива
     */
    public static function getAllSearchKeywords($menuId)
    {
        $kArr = array();
        
        $text = '';
        
        $query = self::getQuery();
        $query->where("#state = 'active' AND #menuId = {$menuId}");
        while ($rec = $query->fetch()) {
            $text .= ' ' . $rec->searchKeywords;
        }
        
        if ($text) {
            $text = strtolower(str::canonize($text, ' '));
            $wArr = explode(' ', $text);
            foreach ($wArr as $w) {
                if (strlen($w) > 3) {
                    $kArr[$w] = true;
                }
            }
        }
        
        return $kArr;
    }
    
    
    /**
     * Връща URL към вътрешната част (работилницата), отговарящо на посочената точка в менюто
     */
    public function getWorkshopUrl($menuId)
    {
        $url = array('cms_Articles', 'list', 'menuId' => $menuId);
        
        return $url;
    }
    
    
    /**
     * След подготвяне на сингъла, добавяме и лога с промените
     */
    public function on_AfterPrepareSingle($mvc, $res, $data)
    {
        // Инстанция на класа
        $inst = cls::get('core_TableView');
        
        // Вземаме таблицата с попълнени данни
        $fields = 'createdOn=Дата, createdBy=От, Version=Версия';
        $data->row->CHANGE_LOG = $inst->get(change_Log::prepareLogRow($mvc->className, $data->rec->id), $fields);
    }
    
    
    protected static function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        $data->toolbar->addBtn('Конкатениране', array($mvc, 'ShowAll', 'menuId' => $data->listFilter->rec->menuId), 'ef_icon=img/16/concatenate.png');
        
        if ($mvc->haveRightFor('add')) {
            $data->toolbar->addBtn(
                'Нова статия',
                array(
                    $mvc,
                    'add',
                    'menuId' => $data->listFilter->rec->menuId,
                ),
                'id=btnAdd',
                'ef_icon = img/16/star_2.png,title=Създаване на нов запис'
            );
        }
    }
    
    
    public function act_ShowAll()
    {
        requireRole('admin,cms');
        
        $form = cls::get('core_Form');
        $form->FNC('menuId', 'key(mvc=cms_Content,select=menu)', 'caption=Меню,mandatory,silent');
        $form->FNC('articles', 'keylist(mvc=cms_Articles,select=title)', 'caption=Статии,columns=1,input');
        $form->FNC('divider', 'richtext(rows=3,bucket=Notes)', 'caption=Разделител,input');
        
        $form->input(null, 'silent');
//        $form->method = 'GET';
        
        if ($form->rec->menuId) {
            $query = self::getQuery();
            $query->where("#menuId = {$form->rec->menuId} AND #state = 'active'");
            $suggestions = array();
            while ($rec = $query->fetch()) {
                $suggestions[$rec->id] = $this->getVerbal($rec, 'level') . ' ' . $this->getVerbal($rec, 'title');
                $selected .= $rec->id . '|';
            }
            $form->setSuggestions('articles', $suggestions);
            $form->setDefault('articles', '|' . $selected);
        }
        
        $inRec = $form->input();
        
        if ($form->isSubmitted()) {
            $typeOrder = cls::get('type_Order');
            
            $query = self::getQuery();
            $query->where("#menuId = {$inRec->menuId} AND #state = 'active'");
            $commaList = str_replace('|', ',', trim($inRec->articles, '|'));
            $query->where("#id IN ({$commaList})");
            $rt = cls::get('type_Richtext');
            $query->orderBy('#level=ASC');

            $res = '';

            while ($rec = $query->fetch()) {
                if (!$res) {
                    $res = new ET("<div style='max-width:800px;'>[#CONTENT#]</div>");
                } else {
                    $res->append($rt->toVerbal($inRec->divider), 'CONTENT');
                }
                $rec->body = trim(str_replace('[h1][/h1]', "\n", $rec->body));
                $rec->body = trim(str_replace('[h2][/h2]', "\n", $rec->body));
                $rec->body = trim(str_replace('[h3][/h3]', "\n", $rec->body));
                $rec->body = trim(str_replace('[h4][/h4]', "\n", $rec->body));
                $rec->body = trim(str_replace('[h5][/h5]', "\n", $rec->body));
                $rec->body = trim(str_replace('[h6][/h6]', "\n", $rec->body));
                
                $res->append($this->getVerbal($rec, 'body'), 'CONTENT');
            }
        } else {
            $form->title = 'Конкатиниране на статии';
            $form->toolbar->addSbBtn('Покажи');
            $res = $form->renderHtml('menuId,articles,divider');
        }
        
        return $res;
    }
    
    
    /**
     * Изпълнява се преди запис, за да премести записите, които са в под-дървото на записвания
     */
    protected static function on_BeforeSave($mvc, &$res, $rec, $fields = null, $mode = null)
    {
        if ($rec->id && $rec->level) {
            $exRec = self::fetch($rec->id);
            
            $exRec->level = self::trim3zeros($exRec->level);
            $level = self::trim3zeros($rec->level);
            
            if (strlen($exRec->level) <= 6 && ($exRec->level != $level)) {
                $query = self::getQuery();
                while ($curRec = $query->fetch("#level LIKE '{$exRec->level}%' AND #menuId = {$exRec->menuId}")) {
                    $curRec->level = $level . substr($curRec->level, strlen($level));
                    $mvc->save_($curRec, 'level');
                }
            }
        }
    }
    
    
    private static function trim3zeros($level)
    {
        if (substr($level, -3) === '000') {
            $level = substr($level, 0, strlen($level) - 3);
            $level = self::trim3zeros($level);
        }
        
        return $level;
    }
    
    
    /**
     * Титлата за листовия изглед
     * Съдържа и текущия домейн
     */
    public static function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        $data->title .= cms_Domains::getCurrentDomainInTitle();
    }
    
    
    /**
     * Връща URL за промяна на записа
     *
     * @param int $id
     *
     * @return array
     */
    public static function getChangeUrl($id)
    {
        if (Mode::is('cmsNav')) {
            $retUrl = toUrl(array(get_called_class(), 'Article', $id), 'local');
        } else {
            $retUrl = true;
        }
        $res = array(get_called_class(), 'changeFields', $id, 'ret_url' => $retUrl);
        
        return $res;
    }
    
    
    /**
     * Проверява дали може да се променя записа в зависимост от състоянието на документа
     *
     * @param core_Mvc $mvc
     * @param bool     $res
     * @param string   $state
     */
    public static function on_AfterCanChangeRec($mvc, &$res, $rec)
    {
        // Чернова и затворени документи не могат да се променят
        if ($res !== false && $rec->state != 'draft') {
            $res = true;
        }
    }
    
    
    /**
     * Добавяне на текстове за съгласие към формата
     *
     * @param int|NULL $domainId - за кой домейн
     *
     * @return core_ET $tpl      - шаблон с линковете
     */
    public static function addFooterLinks($domainId = null)
    {
        $domainId = isset($domainId) ? $domainId : cms_Domains::getPublicDomain()->id;
        
        // Всички активни статии към домейна с информация за добавяне във футъра
        $query = self::getQuery();
        $query->EXT('domainId', 'cms_Content', 'externalName=domainId,externalKey=menuId');
        $query->where("#domainId = '{$domainId}' AND #state = 'active'");
        $query->where("#footerTitleLink IS NOT NULL AND #footerTitleLink != ''");
        $query->orderBy('id', 'ASC');
        
        $links = array();
        while ($rec = $query->fetch()) {
            $link = ht::createLink($rec->footerTitleLink, self::getUrl($rec));
            $links[] = $link->getContent();
        }
        
        if (!countR($links)) {
            
            return new core_ET('');
        }
        
        $links = implode(' | ', $links);
        $tpl = new core_ET('[#FOOTER_LINKS#]');
        $tpl->append($links, 'FOOTER_LINKS');
        
        return $tpl;
    }
    
    
    /**
     * Връща връща масив със обекти, съдържащи връзки към публичните страници, генерирани от този обект
     */
    public function getSitemapEntries($menuId)
    {
        $query = self::getQuery();
        $query->where("#state = 'active' AND #menuId = {$menuId}");
        
        $res = array();
        
        while ($rec = $query->fetch()) {
            if (!trim($rec->body)) {
                continue;
            }
            $resObj = new stdClass();
            $resObj->loc = $this->getUrl($rec, true);
            $resObj->lastmod = date('c', dt::mysql2timestamp($rec->modifiedOn));
            $resObj->priority = 0.5;
            $res[] = $resObj;
        }
        
        return $res;
    }
}
