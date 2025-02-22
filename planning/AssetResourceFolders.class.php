<?php


/**
 * Мениджър за папки в които са споделени ресурсите
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_AssetResourceFolders extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Споделени папки към ресурси';


    /**
     * Заглавие
     */
    public $singleTitle = 'Споделена папка';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'planning_Wrapper, plg_RowTools2';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, planningMaster';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, planningMaster';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, planning';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     *  @var string
     */
    public $hideListFieldsIfEmpty = 'users';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('classId', 'class', 'caption=Клас,mandatory,silent,input=hidden');
        $this->FLD('objectId', 'int', 'caption=Оборудване/Група,mandatory,silent,input=hidden');
        $this->FLD('folderId', 'key(mvc=doc_Folders, select=title)', 'caption=Папка, mandatory, tdClass=leftCol wrapText');
        $this->FLD('users', 'userList', 'caption=Отговорници');
        
        $this->setDbUnique('classId, objectId, folderId');
        $this->setDbIndex('classId, folderId');
    }
    
    
    /**
     * Подготвяме  общия изглед за 'List'
     */
    public function prepareDetail_($data)
    {
        $data->TabCaption = 'Папки';
        $data->classId = $data->masterMvc->getClassId();
        $data->objectId = $data->masterId;
        
        setIfNot($data->masterMvc, $this->Master);
        $data->query = $this->getQuery();
        
        // Добавяме връзката с мастер-обекта
        $data->query->where(array("#classId = '[#1#]'", $data->classId));
        $data->query->where(array("#objectId = '[#1#]'", $data->objectId));
        
        // Подготвяме навигацията по страници
        $this->prepareListPager($data);
        
        // Името на променливата за страниране на детайл
        if (is_object($data->pager)) {
            $data->pager->setPageVar($data->masterMvc->className, $data->masterId, $this->className);
            if (cls::existsMethod($data->masterMvc, 'getHandle')) {
                $data->pager->addToUrl = array('#' => $data->masterMvc->getHandle($data->masterId));
            }
        }
        
        // Подготвяме редовете от таблицата
        $this->prepareListRecs($data);

        // Подготвяме вербалните стойности за редовете
        $this->prepareListRows($data);

        if($data->masterMvc instanceof planning_AssetResources){
            foreach ($data->rows as $id => $row){
                $rec = $data->recs[$id];
            }
        }

        $data->toolbar = cls::get('core_Toolbar');
        if ($this->haveRightFor('add')) {
            Request::setProtected(array('classId', 'objectId'));
            $data->toolbar->addBtn(
                'Нов запис',
                array(
                    $this,
                    'add',
                    'classId' => $data->classId,
                    'objectId' => $data->objectId,
                    'ret_url' => true
                ),
            'id=btnAdd',
                'ef_icon = img/16/star_2.png,title=Създаване на нов запис'
            );
        }
        
        return $data;
    }
    
    
    /**
     * Рендираме общия изглед за 'List'
     */
    public function renderDetail_($data)
    {
        if (!isset($data->listClass)) {
            $data->listClass = 'listRowsDetail';
        }
        
        if (!isset($this->currentTab)) {
            $this->currentTab = $data->masterMvc->title;
        }

        // Рендираме общия лейаут
        $tpl = new ET("
            <div class='clearfix21 planning_AssetResourceFolders'>
                [#ListPagerTop#]
                [#ListTable#]
                [#ListPagerBottom#]
                [#ListToolbar#]
            </div>
        ");
        
        // Попълваме таблицата с редовете
        setIfNot($data->listTableMvc, clone $this);
        $data->listFields = 'folderId=Папка,users=Отговорници';
        $tpl->append($this->renderListTable($data), 'ListTable');
        
        // Попълваме таблицата с редовете
        $pagerHtml = $this->renderListPager($data);
        $tpl->append($pagerHtml, 'ListPagerTop');
        $tpl->append($pagerHtml, 'ListPagerBottom');
        
        // Попълваме долния тулбар
        $tpl->append($this->renderListToolbar($data), 'ListToolbar');

        $resTpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $resTpl->append($tpl, 'content');
        $resTpl->append(tr("Споделени в папки"), 'title');

        return $resTpl;
    }
    
    
    /**
     * @see core_Manager::act_Add()
     */
    public function act_Add()
    {
        Request::setProtected(array('classId', 'objectId'));
        
        return parent::act_Add();
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->folderId = doc_Folders::getVerbalLinks($rec->folderId, true, true);
        
        if ($rec->users) {
            $row->users = $mvc->fields['users']->type->toVerbal($rec->users);
            
            $usersArr = type_UserList::toArray($rec->users);
            $row->users = '';
            foreach ($usersArr as $userId) {
                $row->users .= ($row->users) ? ',' : '';
                $row->users .= crm_Profiles::createLink($userId);
            }
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     *
     * @param planning_AssetResourceFolders $mvc
     * @param stdClass                      $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        $forType = null;
        if ($rec->classId) {
            $Inst = cls::get($rec->classId);
            if ($Inst instanceof planning_AssetResources) {
                $forType = 'assets';
            } elseif ($Inst instanceof planning_Hr) {
                $forType = 'hr';
            }
        }
        
        // Допустимите папки
        $suggestions = doc_FolderResources::getFolderSuggestions($forType);
        $form->setOptions('folderId', array('' => '') + $suggestions);
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        if ($form->isSubmitted()) {
            if($rec->classId == planning_AssetResources::getClassId()){
                if(!planning_AssetResources::canAssetBeAddedToFolder($rec->objectId, $rec->folderId)) {
                    $form->setError('folderId', 'Материалните ресурс не може да е в повече от един център на дейност|*!');
                }
            }
        }
    }


    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $rec = $data->form->rec;
        if ($rec->classId) {
            $data->form->title = core_Detail::getEditTitle($rec->classId, $rec->objectId, $mvc->singleTitle, $rec->id, 'към');
        }
    }


    /**
     * Добавяне на дефолтна папка за обект
     *
     * @param int      $classId  - ид на класа
     * @param int      $objectId - ид на обекта
     * @param int|NULL $folderId - дефолтна папка
     *
     * @return int|void
     */
    public static function addDefaultFolder($classId, $objectId, $folderId = null, $users = null)
    {
        if (self::fetch("#classId = {$classId} AND #objectId = {$objectId}")) {
            
            return;
        }
        
        $defFolderId = (isset($folderId)) ? $folderId : planning_Centers::getUndefinedFolderId();
        $users = (isset($users)) ? $users : null;
        $rec = (object) array('classId' => $classId, 'objectId' => $objectId, 'folderId' => $defFolderId, 'users' => $users);
        
        return self::save($rec);
    }
}
