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
    public $loadList = 'bgerp_Wrapper, plg_Created, plg_Modified, plg_RowTools, plg_Search, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, url = Линк, modifiedOn = Последно';
    
    
    /**
     * Полета на модела
     */
    public function description()
    {
        $this->FLD('user', 'user(roles=powerUser, rolesForTeams=admin, rolesForAll=ceo)', 'caption=Потребител, mandatory');
        $this->FLD('title', 'varchar', 'caption=Заглавие, silent, mandatory');
        $this->FLD('url', 'varchar', 'caption=URL, silent, mandatory');
        
        $this->setDbUnique('user, title');
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

        $img =  ht::createElement('img', array('src' => sbf('img/16/application_yellow.png', ''), 'title' => tr('Редактиране')));
        $list = ht::createLink($img , $url, NULL, array('class' => 'bookmarkLink'));
        $title = $list . "<span class='bookmarkText'>" . tr('Бързи връзки') . "</span>";
        
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
            
            $attr = array();
            $attr['onclick'] = "addParamsToBookmarkBtn(this, '{$sUrl}', '{$localUrl}'); return ;";

            $img =  ht::createElement('img', array('src' => sbf('img/16/add-yellow.png', ''), 'title' => tr('Добавяне'), 'class' => 'bookmarkLink'));
            $tpl = ht::createLink($img, $url, FALSE, $attr);
        }
        
        return $tpl;
    }
	
	
	/**
	 * Връща всички линкове за съответния потребител
	 * 
	 * @return string
	 */
	public static function getLinks($limit = NULL, $userId = NULL)
	{
	    if (!$userId) {
	        $userId = core_Users::getCurrent();
	    }
	    
	    if ($userId < 1) return ;
	    
	    $query = self::getQuery();
	    $query->where("#user = '{$userId}'");
	    
	    self::orderQuery($query);
	    
	    if (is_null($limit)) {
	        $conf = core_Packs::getConfig('bgerp');
	        $limit = $conf->BGERP_BOOKMARK_SHOW_LIMIT;
	    }
	    
	    if ($limit) {
	        $query->limit((int) $limit);
	    }

	    $res = '<ul>';
	    while ($rec = $query->fetch()) {
	        $link = ht::createLink($rec->title, array(get_called_class(), "click", $rec->id));
	        $res .= "<li>" . $link . "</li>";
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
	    $query->orderBy('modifiedOn', 'DESC');
	    $query->orderBy('createdOn', 'DESC');
	}
	
	
	/**
	 * 
	 * 
	 * @param string $url
	 * @param string $title
	 * 
	 * @return string
	 */
    public static function prepareUrl($url, $title = NULL)
    {
        if (!preg_match('/^http[s]?\:\/\//i', $url) && (strpos($url, Request::get('App')) === 0)) {
            
            $attr = array();
            
            try {
                
                $urlArr = parseLocalUrl($url);
                
                $lUrl = toUrl($urlArr);
                
                $attr['class'] = 'bookmark-local-url';
            } catch (core_exception_Expect $e) {
                $lUrl = array();
                
                $attr['class'] = 'bookmark-wrong-url';
            }
	    } else {
	        $lUrl = $url;
	        
            $attr['class'] = 'bookmark-external-url';
	    }
	    
	    if (!isset($title)) {
	        $title = $url;
	    }
	    
	    return ht::createLink($title, $lUrl, NULL, $attr);
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
        
        $row->url = self::prepareUrl($rec->url, $title);
    }
}
