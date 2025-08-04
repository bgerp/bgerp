<?php


/**
 * Последни документи и папки, посетени от даден потребител
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Последно видяни документи от потребител
 */
class bgerp_LastSeenDocumentByUser extends core_Manager
{
    /**
     * Необходими мениджъри
     */
    public $loadList = 'bgerp_Wrapper';


    /**
     * Заглавие
     */
    public $title = 'Последно посетени документи от потребител';


    /**
     * Права за писане
     */
    public $canWrite = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'containerId,threadId,folderId,userId,lastOn';


    /**
     * Опашка от записи
     */
    protected $queue = array();


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('docClass', 'class(interface=doc_DocumentIntf,select=title)', 'caption=Документ вид');
        $this->FLD('docId', 'int', 'caption=Документ номер');
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Документ');
        $this->FLD('threadId', 'key(mvc=doc_Threads)', 'caption=Нишка');
        $this->FLD('folderId', 'key(mvc=doc_Folders)', 'caption=Папка');
        $this->FLD('userId', 'user', 'caption=Потребител');
        $this->FLD('lastOn', 'datetime(format=smartTime)', 'caption=Посетено');

        $this->setDbUnique('containerId,userId');
        $this->setDbUnique('docClass,docId,userId');
        $this->setDbIndex('docClass,userId');
        $this->setDbIndex('docClass,docId');
        $this->setDbIndex('containerId');
        $this->setDbIndex('lastOn');
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     * @param array $fields
     * @return void
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->containerId = doc_Containers::getDocument($rec->containerId)->getLink(0);
        $row->threadId = doc_Threads::recToVerbal(doc_Threads::fetch($rec->threadId))->title;
        $row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;

        $state = doc_Containers::fetchField($rec->containerId, 'state');
        $row->ROW_ATTR['class'] = "state-{$state}";
    }


    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->setFieldTypeParams('docClass', 'allowEmpty');
        $data->listFilter->view = 'horizontal';
        $data->listFilter->FNC('users', 'users(rolesForAll=ceo, rolesForTeams=ceo|manager|admin)', 'caption=Потребител,input,silent,refreshForm');
        $data->listFilter->showFields = "docClass,users";
        $data->listFilter->input();

        // Добавяме бутон за филтриране
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        // Ако не е избран потребител по подразбиране
        if (!$data->listFilter->rec->users) {
            $data->listFilter->rec->users = '|' . core_Users::getCurrent() . '|';
        }

        // Ако има филтър
        if ($filter = $data->listFilter->rec) {
            if (!((strpos($filter->users, '|-1|') !== false) && (haveRole('ceo')))) {
                $usersArr = type_Keylist::toArray($filter->users);
                if (countR($usersArr)) {
                    $data->query->orWhereArr('userId', $usersArr);
                } else {
                    $data->query->where('1=2');
                }
            }

            if(isset($filter->docClass)){
                $data->query->where("#docClass = {$filter->docClass}");
            }
        }

        $data->query->orderBy('#lastOn', 'DESC');
    }


    /**
     * Помощна функция за добавяне на запис в модела
     *
     * @param int $containerId - ид на контейнер на документ
     * @param int|null $userId - ид на потребител, null за текущия
     * @param datetime $date   - на коя дата, null за СЕГА
     * @return void
     */
    public static function queueToLog($containerId, $userId = null, $date = null)
    {
        $userId = $userId ?? core_Users::getCurrent();
        if(empty($userId)) return;
        $date = $date ?? dt::now();

        $key = "{$containerId}|{$userId}";
        cls::get(get_called_class())->queue[$key] = (object)array('containerId' => $containerId, 'userId' => $userId, 'date' => $date);
    }


    /**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    public static function on_Shutdown($mvc)
    {
        if(countR($mvc->queue)){
            $containerIds = arr::extractValuesFromArray($mvc->queue, 'containerId');

            // Извличане на старите записи
            $query = $mvc->getQuery();
            $query->in('containerId', $containerIds);
            $eRecs = $query->fetchAll();

            $save = $cRecs = array();
            $cQuery = doc_Containers::getQuery();
            $cQuery->in('id', $containerIds);
            while($cRec1 = $cQuery->fetch()){
                $cRecs[$cRec1->id] = $cRec1;
            }

            // Подготовка на новите записи
            foreach ($mvc->queue as $qRec){
                $cRec = $cRecs[$qRec->containerId];
                $obj = (object)array('docClass' => $cRec->docClass, 'docId' => $cRec->docId, 'threadId' => $cRec->threadId, 'folderId' => $cRec->folderId, 'containerId' => $cRec->id);
                $obj->lastOn = $qRec->date;
                $obj->userId = $qRec->userId;
                $save[] = $obj;
            }

            // Синхронизиране на
            $res = arr::syncArrays($save, $eRecs, 'containerId,userId', 'lastOn');
            if (countR($res['insert'])) {
                $mvc->saveArray($res['insert']);
            }
            if (countR($res['update'])) {
                $mvc->saveArray($res['update'], 'id,lastOn');
            }
        }
    }


    /**
     * Помощна функция връщаща последно видяните документи от даден потребител
     *
     * @param mixed $class        - клас, който да е видян последно
     * @param null|int $driverId  - драйвер на класа, null ако няма
     * @param null|int $userId    - ид на потребител, null за текущия
     * @param null|int $limit     - лимит на резултатите, null за без ограничение
     * @param bool $verbal        - дали да е вербална стойноста или не;
     * @return array $res
     */
    public static function getLastSeenByUser($class, $driverId = null, $userId = null, $limit = null, $verbal = true)
    {
        $res = array();
        $Class = cls::get($class);
        $userId = $userId ?? core_Users::getCurrent();
        if(empty($userId)) return $res;

        // Последните $limit виждания на посочения вид документ от подадения потребител
        $query = static::getQuery();
        $query->EXT('state', 'doc_Containers', 'externalName=state,externalKey=containerId');
        $query->where("#docClass = {$Class->getClassId()} AND #userId = '{$userId}'");
        $query->notIn("state", array('rejected', 'template'));
        $query->orderBy('lastOn', 'DESC');

        // Ако има драйвер, ограничават се последните виждания на документи от посочения драйвер
        if(isset($driverId) && isset($Class->driverClassField)){
            $query->EXT($Class->driverClassField, $Class->className, "externalName={$Class->driverClassField},externalKey=docId");
            $query->where("#{$Class->driverClassField} = {$driverId}");
        }

        $count = 0;
        while($rec = $query->fetch()){
            if($Class->haveRightFor('single', $rec->docId, $userId)){
                $title = ($verbal) ? $Class->getTitleById($rec->docId, false) : $rec->docId;
                $res[$rec->docId] = $title;
                $count++;
            }

            if(isset($limit) && $count >= $limit) break;
        }

        return $res;
    }


    /**
     * Изтриване на много стари записи по разписание
     */
    public function cron_DeleteOldRecs()
    {
        $lifetime = bgerp_Setup::get('LAST_SEEN_DOC_BY_USER_CACHE_LIFETIME');
        $lastSeenDate =  dt::addSecs(-1 * $lifetime);

        static::delete("#lastOn <= '{$lastSeenDate}'");
    }
}