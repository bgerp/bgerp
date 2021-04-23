<?php


/**
 * Клас 'cms_GalleryGroups' - групи от картинки
 *
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Milen Georgiev <milen@download.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cms_GalleryGroups extends core_Manager
{
    /**
     * Кой има право да чете
     */
    public $canRead = 'user';
    
    
    /**
     * Кой  може да пише?
     */
    public $canWrite = 'user';
    
    
    /**
     * Заглавие
     */
    public $title = 'Групи от картинки';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Група от картинки';
    
    
    /**
     * Кой може да използва групите
     */
    public $canUsegroup = 'user';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'user';
    
    
    /**
     * Кой може да променя съсъоянието
     *
     * @see plg_State2
     */
    public $canChangestate = 'user';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools,cms_Wrapper,plg_Created, plg_Modified, cms_GalleryTitlePlg, plg_Clone, plg_State2, cms_GalleryDialogWrapper';
    
    
    /**
     * Полета за изглед
     */
    public $listFields = 'id,title,position,roles,sharedTo,tWidth,tHeight,width,height,createdOn,createdBy,state';
    
    
    /**
     * Името на полето, което ще се използва от плъгина
     *
     * @see cms_GalleryTitlePlg
     */
    public $galleryTitleFieldName = 'title';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'fileman_GalleryGroups';
    
    
    /**
     * Брой елементи при показване на страница в диалогов прозорец
     */
    public $galleryListItemsPerPage = 10;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('position', 'enum(none=Без стил,center=Център,left=Ляво,right=Дясно)', 'caption=Позиция,mandatory');
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие');
        $this->FLD('tpl', 'html', 'caption=Шаблон');
        
        $this->FLD('style', 'varchar', 'caption=Стил');
        
        $this->FLD('tWidth', 'int', 'caption=Тъмб->Широчина');
        $this->FLD('tHeight', 'int', 'caption=Тъмб->Височина');
        
        $this->FLD('width', 'int', 'caption=Картинка->Широчина');
        $this->FLD('height', 'int', 'caption=Картинка->Височина');
        
        $this->FLD('roles', 'keylist(mvc=core_Roles, select=role, allowEmpty,groupBy=type)', 'caption=Споделяне->Роли, width=100%');
        $this->FLD('sharedTo', 'type_UserList', 'caption=Споделяне->Потребители, width=100%');
        
        $this->setDbUnique('title, position');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        // Пътя до файла с данните
        $file = 'cms/csv/GalleryGroups.csv';
        
        // Кои колонки ще вкарваме
        $fields = array(
            0 => 'title',
            1 => 'position',
            2 => 'tpl',
            3 => 'style',
            4 => 'tWidth',
            5 => 'tHeight',
            6 => 'width',
            7 => 'height',
            8 => 'roles',
        );
        
        // Импортираме данните от CSV файла.
        // Ако той не е променян - няма да се импортират повторно
        $cntObj = csv_Lib::importOnce($this, $file, $fields, null, array('delimiter' => '|'), false);
        
        // Записваме в лога вербалното представяне на резултата от импортирането
        $res = $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
        // Ако не са подадени роли
        if (!$rec->roles) {
            
            return ;
        }
        
        // Обхождаме всички роли и от името им определяме id-то
        $rolesStrArr = arr::make($rec->roles);
        $roleArr = array();
        foreach ($rolesStrArr as $role) {
            $roleId = core_Roles::fetchByName($role);
            
            if (!$roleId) {
                continue;
            }
            
            $roleArr[$roleId] = $roleId;
        }
        
        // Добавяме id-тата на записите
        $rec->roles = type_Keylist::fromArray($roleArr);
    }
    
    
    /**
     * Връща id на групата по подразбиране
     *
     * @return int
     */
    public static function getDefaultGroupId()
    {
        // По подразбиране да се използва групата централни
        return cms_GalleryGroups::fetchField("#title = 'Без-стил'");
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
        // Ако има запис и потребителя не е CEO или admin
        if ($rec && !haveRole('ceo, admin')) {
            
            // Ако ще изтриваме или редактираме група
            if ($action == 'delete' || $action == 'edit' || $action == 'changestate') {
                
                // Ако не сме създател
                if ($rec->createdBy != $userId) {
                    
                    // Да не можем да редактираме
                    $requiredRoles = 'no_one';
                }
            }
            
            if ($action == 'usegroup') {
                $groupQuery = cms_GalleryGroups::getQuery();
                $mvc->restrictQuery($groupQuery, $userId);
                $groupQuery->where($rec->id);
                $groupQuery->limit(1);
                
                if (!$groupQuery->fetch()) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        // Ако все още има права за изтриване
        if ($requiredRoles != 'no_one' && $rec && $action == 'delete') {
            
            // Да не могат да се трият групи, които са използвани в картиниките
            if (cms_GalleryImages::fetch("#groupId = '{$rec->id}'")) {
                $requiredRoles = 'no_one';
            }
        }
        
        // Ако ще се клонират данни на потребителя
        // see plg_Clone
        if ($rec && ($action == 'cloneuserdata')) {
            
            // Трябва да има права за добавяне за да може да клонира
            if (!$mvc->haveRightFor('add', $rec)) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Преди записване на клонирания запис
     *
     * @param core_Mvc $mvc
     * @param object   $rec
     * @param object   $nRec
     *
     * @see plg_Clone
     */
    public function on_BeforeSaveCloneRec($mvc, $rec, $nRec)
    {
        // Премахваме ненужните полета
        unset($nRec->createdOn);
        unset($nRec->createdBy);
        unset($nRec->modifiedOn);
        unset($nRec->modifiedBy);
        unset($nRec->state);
    }
    
    
    /**
     *  Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // Ограничаваме записите, които да се показват
        $mvc->restrictQuery($data->query);
    }
    
    
    /**
     * Поставя изискване да се селектират достъпните записи
     */
    public function on_BeforeMakeArray4Select($mvc, &$optArr, $fields = null, &$where = null)
    {
        $nQuery = $mvc->getQuery();
        
        // Ако има условие, от преди това
        $nQuery->where($where);
        
        $mvc->restrictQuery($nQuery);
        
        $nWhere = $nQuery->getWhereAndHaving(true)->w;
        
        $where = trim($nWhere);
    }
    
    
    /**
     *
     *
     * @param core_Query $query
     */
    public static function restrictQuery(&$query, $userId = null)
    {
        $orToPrevious = false;
        
        // Ограничаваме заявката да се показват само групите споделени с определени потребители
        if (static::restrictRoles($query, $orToPrevious, 'roles', $userId)) {
            $orToPrevious = true;
        }
        
        // Ограничаваме заявката да се показват само групите споделени до определени потребители
        if (static::restrictSharedTo($query, $orToPrevious, 'sharedTo', $userId)) {
            $orToPrevious = true;
        }
        
        // Ограничаваме да се показва само групите създадени от съответния потребител
        static::restrictCreated($query, $orToPrevious, 'createdBy', $userId);
    }
    
    
    /**
     * Ограничаваме заявката да се показват само групите споделени с определени потребители
     *
     * @param core_Query $query          - Заявката
     * @param bool       $orToPrevious   - Дали да се залепи с OR към предишната заявка
     * @param string     $rolesFieldName - Името на полето
     * @param int        $userId         - id на потребителя
     *
     * @return bool
     */
    public static function restrictRoles(&$query, $orToPrevious = false, $rolesFieldName = 'roles', $userId = null)
    {
        // Ако име роля ceo да може да вижда всички
        if (haveRole('ceo')) {
            
            return ;
        }
        
        // Ако не е подаден потребител, да се изпозлва текущия
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        if ($userId <= 0) {
            
            return ;
        }
        
        // Ролите на текущия потребител
        $userRoles = core_Users::getRoles($userId);
        
        // Да се показва групите за които е зададене някоя роля от тези на потребителя
        $query->likeKeylist($rolesFieldName, $userRoles, $orToPrevious);
        
        return true;
    }
    
    
    /**
     * Ограничаваме заявката да се показват само групите споделени до определени потребители
     *
     * @param core_Query $query          - Заявката
     * @param bool       $orToPrevious   - Дали да се залепи с OR към предишната заявка
     * @param string     $rolesFieldName - Името на полето
     * @param int        $userId         - id на потребителя
     *
     * @return bool
     */
    public static function restrictSharedTo(&$query, $orToPrevious = false, $rolesFieldName = 'sharedTo', $userId = null)
    {
        // Ако име роля ceo да може да вижда всички
        if (haveRole('ceo')) {
            
            return ;
        }
        
        // Ако не е подаден потребител, да се изпозлва текущия
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        if ($userId <= 0) {
            
            return ;
        }
        
        // Масив с текущия потребител
        $userIdArr = type_Keylist::fromArray(array($userId => $userId));
        
        // Да се показва групите за които е зададен е зададен потребителя
        $query->likeKeylist($rolesFieldName, $userIdArr, $orToPrevious);
        
        return true;
    }
    
    
    /**
     * Ограничаваме да се показва само групите създадени от съответния потребител
     *
     * @param core_Query $query          - Заявката
     * @param bool       $orToPrevious   - Дали да се залепи с OR към предишната заявка
     * @param string     $rolesFieldName - Името на полето
     * @param int        $userId         - id на потребителя
     *
     * @return bool
     */
    public static function restrictCreated(&$query, $orToPrevious = false, $rolesFieldName = 'createdBy', $userId = null)
    {
        // Ако име роля ceo да може да вижда всички
        if (haveRole('ceo')) {
            
            return ;
        }
        
        // Ако не е подаден потребител, да се изпозлва текущия
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        if ($userId <= 0) {
            
            return ;
        }
        
        // Да се показва групите, които са създадени от потребителя
        $query->where("#{$rolesFieldName} = '{$userId}'", $orToPrevious);
        
        return true;
    }
    
    
    /**
     * Подготвя полето за заглавие
     *
     * @param object $rec
     *
     * @see cms_GalleryTitlePlg
     */
    public function prepareRecTitle(&$rec)
    {
        // Името на полето
        $titleField = $this->galleryTitleFieldName;
        
        // Ако не е зададено заглавието
        if (!$rec->{$titleField} && $rec->position) {
            
            // Определяме заглавието от името на файла
            $rec->{$titleField} = $rec->position;
        }
    }
    
    
    /**
     * Екшън за показване на диалогов прозорец за с изображенията в галерията
     */
    public function act_DialogList()
    {
        Mode::set('dialogOpened', true);
        
        // Очакваме да е има права за добавяне
        $this->requireRightFor('list');
        
        // Обект с необходомите данни
        $data = new stdClass();
        
        // Създаваме заявката
        $data->query = $this->getQuery();
        
        // Подготвяме филтъра
        $this->prepareListFilter($data);
        
        // По - новите добавени да са по - напред
        $data->query->orderBy('#createdOn', 'DESC');
        
        Request::setProtected('callback');
        
        // Функцията, която ще се извика
        $data->callback = $this->callback = Request::get('callback', 'identifier');
        
        // Титлата на формата
        $data->title = 'Групи в галерия';
        
        // Брой елементи на страница
        $this->listItemsPerPage = $this->galleryListItemsPerPage;
        
        // Подготвяме навигацията по страници
        $this->prepareListPager($data);
        
        // Подготвяме записите за таблицата
        $this->prepareListRecs($data);
        
        $data->listFields = 'position,title,tWidth,tHeight,width,height';
        
        // Вербалната стойност на записите
        $this->prepareListRows($data);
        
        // Рендираме изгледа
        $tpl = $this->renderGalleryDialogList($data);
        
        // Задаваме врапера
        Mode::set('wrapper', 'page_Dialog');
        
        // Добавяме бутона за затваряне
        $tpl->append("<button onclick='javascript:window.close();' class='dialog-close'>X</button>");
        
        // Рендираме опаковката
        $tpl = $this->renderDialog($tpl);
        
        return $tpl;
    }
    
    
    /**
     * Подготвя вербалната стойност на данните
     *
     * @param stdClass
     *
     * @return stdClass
     *
     * @see core_Manager::prepareListRows_()
     */
    public function prepareListRows_(&$data)
    {
        parent::prepareListRows_($data);
        
        if (Mode::is('dialogOpened') && is_array($data->rows)) {
            foreach ($data->rows as $id => $row) {
                if ($data->recs[$id]->tWidth && $data->recs[$id]->tHeight) {
                    $row->tWH = $row->tWidth . '/' . $row->tHeight;
                } elseif ($data->recs[$id]->tWidth) {
                    $row->tWH = $row->tWidth . '/...';
                } elseif ($data->recs[$id]->tHeight) {
                    $row->tWH = '.../' . $row->tHeight;
                }
                
                if ($data->recs[$id]->width && $data->recs[$id]->height) {
                    $row->WH = $row->width . '/' . $row->height;
                } elseif ($data->recs[$id]->width) {
                    $row->WH = $row->width . '/...';
                } elseif ($data->recs[$id]->height) {
                    $row->WH = '.../' . $row->height;
                }
            }
        }
        
        return $data;
    }
    
    
    /**
     * Рендираме общия изглед за 'List'
     */
    public function renderGalleryDialogList($data)
    {
        // Рендираме общия лейаут
        $tpl = new ET("
            <div>
                [#ListTitle#]
                <div class='top-pager'> 
                	[#ListPagerTop#]
                </div>
                <div class='galleryListTable'>
                	[#ListTable#]
        		</div>
            </div>
        ");
        
        // Попълваме титлата
        $tpl->append($this->renderListTitle($data), 'ListTitle');
        
        // Попълваме горния страньор
        $tpl->append($this->renderListPager($data), 'ListPagerTop');
        
        // Попълваме таблицата с редовете
        $tpl->append($this->renderGalleryDialogListTable($data), 'ListTable');
        
        return $tpl;
    }
    
    
    /**
     * Рендира таблицата за показване в диалоговия прозорец на галерията
     *
     * @param stdClass $data
     */
    public function renderGalleryDialogListTable($data)
    {
        // Инстанция на класа
        $table = cls::get('core_TableView', array('mvc' => $this));
        
        // Полетата, които ще се показва
        $listFields = array('title' => 'Заглавие', 'position' => 'Позиция', 'WH' => 'Размери->Картинка', 'tWH' => 'Размери->Тъмб');
        
        // Рендираме таблицата
        $tpl = $table->get($data->rows, $listFields);
        
        return new ET("<div class='listRows'>[#1#]</div>", $tpl);
    }
}
