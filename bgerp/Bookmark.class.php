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
    public $loadList = 'bgerp_Wrapper, plg_Created, plg_RowTools, plg_Search, plg_Sorting';
    
    
    /**
     * Полета на модела
     */
    public function description()
    {
        $this->FLD('user', 'user(roles=powerUser, rolesForTeams=admin, rolesForAll=ceo)', 'caption=Потребител');
        $this->FLD('title', 'varchar', 'caption=Заглавие, silent');
        $this->FLD('url', 'Url', 'caption=URL, silent');
        $this->FLD('position', 'double', 'caption=Позиция');
        
        $this->FLD('clickCnt', 'int', 'caption=Брой отваряния, input=none, notNull');
        
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
        
        $title = "<h3 class='centered'>" . ht::createLink(tr('Бързи връзки'), $url) . "</h3>";
        
        return $title;
    }
    
    
    /**
     * Връща бутон за добавяне на букмарк
     */
    public static function getBtn()
    {
        if (self::haveRightFor('add')) {
            $url = toUrl(array(get_called_class(), 'add'));
            $sUrl = addslashes($url);
            
            $attr = array();
            $attr['onclick'] = "addParamsToBookmarkBtn('{$sUrl}'); return ;";
            $attr['ef_icon'] = 'img/16/bookmark_document.png';
            $tpl = ht::createBtn('Добави', $url, FALSE, FALSE, $attr);
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
	    
	    $res = '';
	    while ($rec = $query->fetch()) {
	        $link = ht::createLink($rec->title, array(get_called_class(), "click", $rec->id));
	        $res .= "<div>" . $link . "<div>";
	    }
	    
	    return $res;
	}
    
	
	/**
	 * Подрежда записите в зависимост от подредбата на потребители и броя на показванията
	 * 
	 * @param core_Query $query
	 */
	protected static function orderQuery($query)
	{
	    // За да се избегне подребата на NULL Полетата
	    $query->XPR('positionA', 'double', "-#position");
	    
	    // С по-голям приоритет да са позициите зададени от потребителя
	    // След това в зависимост от броя на отварянията
	    $query->orderBy('positionA', 'DESC');
	    $query->orderBy('clickCnt', 'DESC');
	    $query->orderBy('createdOn', 'DESC');
	}
	
	
	/**
	 * Екшън който увеличава брояча за натискане и редиректва към съответния линк
	 */
	function act_Click()
	{
	    $id = Request::get('id', 'int');
	    
	    $rec = self::fetch($id);
	    
	    expect($rec);
	    
	    if ($rec->user == core_Users::getCurrent()) {
	        $rec->clickCnt++;
	    
	        self::save($rec, 'clickCnt');
	    }
	    
	    return redirect($rec->url);
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
        // Премахваме броя на нотификациите пред стринга
        if (!$data->rec->id && !$data->form->isSubmitted() && $data->form->rec->title) {
            $data->form->rec->title = preg_replace('/^\([0-9]*\) /', '', $data->form->rec->title);
        }
    }
    
    
    /**
     * 
     * 
     * @param bgerp_Bookmark $mvc
     * @param object $res
     * @param object $data
     */
    public static function on_AfterPrepareRetUrl($mvc, $res, $data)
	{
	    // Ако има URL в параметрите, да се редиректне към него
	    if (Request::get('url')) {
	        $data->retUrl = Request::get('url'); 
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
}
