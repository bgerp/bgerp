<?php


/**
 * Клас 'voucher_Cards' - Мениджър за карти за ваучери
 *
 * @category  bgerp
 * @package   voucher
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class voucher_Cards extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Ваучери';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Ваучер';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, voucher_Wrapper, plg_State2, plg_Select, plg_Search, plg_Sorting';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';


    /**
     * Заглавие в единствено число
     */
    public $canChangestate = 'ceo, voucher';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой има право да разглежда?
     */
    public $canList = 'ceo, voucher';


    /**
     * Кой има право да освобождава?
     */
    public $canUnlink = 'ceo, voucher';


    /**
     * Кой може да го освободи?
     */
    public $canRelease = 'ceo, voucher';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, voucher';


    /**
     * Кой може да свързва препоръчител с карти
     */
    public $canLinktoreferrer = 'ceo, voucher';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'number,typeId,usedOn,objectId,referrer,createdOn,createdBy,activatedOn,state,validTo';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'number,referrer';


    /**
     * Константа за състояние използвано
     */
    const STATUS_USED = 'USED';


    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'typeId';


    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'usedOn,classId,objectId,referrer';


    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'barcode_SearchIntf';


    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 20;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('number', 'varchar(16)', 'caption=Номер,mandatory,input=none');
        $this->FLD('typeId', 'key(mvc=voucher_Types,select=name)', 'caption=Тип,mandatory,silent,removeAndRefreshForm=referrer');

        $this->FLD('referrer', 'key2(mvc=crm_Persons,select=name,allowEmpty)', 'caption=Собственик');
        $this->FLD('usedOn', 'datetime(format=smartTime)', 'caption=Употреба->Кога,input=none');
        $this->FLD('classId', 'class', 'caption=Обект,input=none');
        $this->FLD('objectId', 'int', 'caption=Употреба->Къде,input=none,tdClass=leftCol');
        $this->FLD('state', 'enum(active=Активно,closed=Затворено,pending=Чакащо)', 'caption=Състояние,input=none');
        $this->FLD('activatedOn', 'datetime(format=smartTime)', 'caption=Активиране||Activated,input=none');
        $this->FLD('validTo', 'datetime', 'caption=Валиден до');

        $this->setDbUnique('number');
        $this->setDbIndex('referrer');
        $this->setDbIndex('classId,objectId');
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(isset($rec->referrer)){
            $row->referrer = crm_Persons::getHyperlink($rec->referrer, true);
        }
        $row->typeId = voucher_Types::getHyperlink($rec->typeId);

        if(isset($rec->classId) && isset($rec->objectId)){
            $Class = cls::get($rec->classId);
            if($Class->hasPlugin('doc_DocumentPlg')){
                $row->objectId = cls::get($rec->classId)->getLink($rec->objectId, 0);
            } else {
                $row->objectId = cls::get($rec->classId)->getHyperlink($rec->objectId, true);
            }
        }

        core_RowToolbar::createIfNotExists($row->_rowTools);
        if ($mvc->haveRightFor('unlink', $rec)) {
            $url = array($mvc, 'unlink', 'id' => $rec->id, 'ret_url' => true);
            $row->_rowTools->addLink('Отвързване', $url, array('ef_icon' => 'img/16/link_break.png', 'title' => 'Отвързване от препоръчителя'));
        }

        if ($mvc->haveRightFor('release', $rec)) {
            $url = array($mvc, 'release', 'id' => $rec->id, 'ret_url' => true);
            $row->_rowTools->addLink('Освобождаване', $url, array('ef_icon' => 'img/16/lightning.png', 'warning' => 'Наистина ли желаете да ПРЕМАХНЕТЕ използването на ваучера, така че да може да се използва отново на друго място|*?', 'title' => 'Ваучерът да може да се използва отново'));
        }

        if($fields['-detail']){
            $row->_rowTools->removeBtn("del{$rec->id}");
        }

        if(empty($rec->validTo)){
            $typeTime = voucher_Types::fetchField($rec->typeId, 'validTime');
            if(empty($typeTime)){
                $row->validTo = "<i class='quiet'>" . tr('Без срок'). "</i>";
            } else {
                if($rec->state == 'pending'){
                    $timeVerbal = core_Type::getByName('time')->toVerbal($typeTime);
                    $row->validTo = tr("|*<i class='quiet'>{$timeVerbal} |след акт.|*</i>");
                }
            }
        }
    }


    /**
     * Подготовка на филтър формата
     *
     * @param core_Mvc $mvc
     * @param StdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->FLD('filter', 'enum(all=Всички,used=Използвани,unUsed=Неизползвани,pending=Чакащи,closed=Затворени,withReferrers=От препоръчители)');
        $data->listFilter->setDefault('filter', 'all');
        $data->listFilter->setFieldTypeParams('typeId', 'allowEmpty');
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();

        if(isset($data->masterMvc)){
            $data->listFilter->showFields = 'search,filter,referrer';
            unset($data->listFields['typeId']);
        } else {
            $data->listFilter->showFields = 'search,typeId,filter,referrer';
        }

        if($filter = $data->listFilter->rec){
            if(isset($filter->referrer)){
                $data->query->where("#referrer = {$filter->referrer}");
                unset($data->listFields['referrer']);
            }

            if(isset($filter->typeId)){
                $data->query->where("#typeId = {$filter->typeId}");
                unset($data->listFields['typeId']);
            }

            if($filter->filter == 'withReferrers'){
                $data->query->where("#referrer IS NOT NULL");
            } elseif($filter->filter == 'pending'){
                $data->query->where("#state = 'pending'");
            } elseif($filter->filter == 'closed'){
                $data->query->where("#state = 'closed'");
            } elseif($filter->filter != 'all'){
                $where = $filter->filter == 'used' ? "#usedOn IS NOT NULL" : "#usedOn IS NULL";
                $data->query->where($where);
            }
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc      $mvc
     * @param string        $requiredRoles
     * @param string        $action
     * @param stdClass|NULL $rec
     * @param int|NULL      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'linktoreferrer' && isset($rec)){
            if(empty($rec->referrer)){
                $requiredRoles = 'no_one';
            } else {
                if(!crm_Persons::haveRightFor('edit', $rec->referrer)){
                    $requiredRoles = 'no_one';
                }
            }
        }

        if(in_array($action, array('changestate', 'delete')) && isset($rec)){
            if(!empty($rec->usedOn)){
                $requiredRoles = 'no_one';
            }
        }

        if($action == 'unlink' && isset($rec)) {
            if (empty($rec->referrer) || !empty($rec->usedOn)) {
                $requiredRoles = 'no_one';
            }
        }

        if($action == 'changestate' && isset($rec)) {
            if(!empty($rec->validTo) && $rec->validTo <= dt::now()){
                $requiredRoles = 'no_one';
            }
        }

        if($action == 'release' && isset($rec)) {
            if(empty($rec->usedOn)){
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Отвързване на ваучер от препоръчителя му
     */
    public function act_Unlink()
    {
        $this->requireRightFor('unlink');
        expect($id = Request::get('id', 'int'));
        expect($rec = static::fetch($id));
        $this->requireRightFor('unlink', $rec);

        $typeRec = voucher_Types::fetch($rec->typeId);
        $rec->referrer = null;
        $rec->state = ($typeRec->referrer == 'yes') ? 'pending' : 'active';
        if($rec->state == 'pending'){
            unset($rec->activatedOn);
            unset($rec->validTo);
        }
        static::save($rec, 'referrer,state,validTo,activatedOn');

        followRetUrl(null, 'Ваучерът е отвързан от собственика|*!');
    }


    /**
     * Създаване на ваучери
     */
    public function act_Release()
    {
        $this->requireRightFor('release');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('release', $rec);

        voucher_Cards::mark($id, false);
        cls::get($rec->classId)->logWrite('Ръчно освобождаване на използван ваучер|*!', $rec->objectId);

        followRetUrl(null, 'Ваучерът е освободен');
    }


    /**
     * Създаване на ваучери
     */
    public function act_Linktoreferrer()
    {
        // Подготвяме форма за започване на нова тема
        $this->requireRightFor('linktoreferrer');
        expect($referrer = Request::get('referrer', 'int'));
        expect($referrerRec = crm_Persons::fetchRec($referrer));
        $this->requireRightFor('linktoreferrer', (object)array('referrer' => $referrerRec->id));

        // Подготовка на формата
        $form = cls::get('core_Form');
        $form->title = "Ваучери на|*: " . cls::get('crm_Persons')->getFormTitleLink($referrerRec);;
        $form->FLD('text', 'text(rows=5)', 'caption=Ваучери,mandatory');
        $form->input();

        // Ако е събмитната формата
        if ($form->isSubmitted()) {
            $rec = $form->rec;

            $errors = $okVouchers = array();
            $valueArr = preg_split('/[^0-9]+/', $rec->text);

            $now = dt::now();
            foreach ($valueArr as $v){
                $res = static::getByNumber($v);

                if(!$res){
                    $errors[] = "Невалиден номер|*: <b>{$v}</b>";
                } else {

                    if(empty($res['id'])){
                        $errors[] = "Невалиден номер|*: <b>{$v}</b>";
                    } else {
                        if($res['status'] == self::STATUS_USED) {
                            $errors[] = "Ваучерът е използван|*: <b>{$v}</b>";
                        } elseif($res['referrer']) {
                            $errors[] = "Ваучерът е вече свързан|*: <b>{$v}</b>";
                        } else {
                            $updateRec = (object)array('id' => $res['id'], 'referrer' => $referrerRec->id, 'state' => 'active', 'activatedOn' => $now);
                            if(!empty($res['validTime'])){
                                $updateRec->validTo = dt::addSecs($res['validTime'], $updateRec->activatedOn);
                            }
                            $okVouchers[$res['id']] = $updateRec;
                        }
                    }
                }
            }

            if(countR($errors)){
                $errorString = implode("<br>", $errors);
                $form->setError('text', $errorString);
            }

            if(!$form->gotErrors()){
                $count = countR($okVouchers);
                $this->saveArray($okVouchers, 'id,referrer,state,validTo,activatedOn');

                followRetUrl(null, "Свързани ваучери|*: {$count}");
            }
        }

        $form->toolbar->addSbBtn('Създаване', 'save', 'ef_icon = img/16/star_2.png, title = Създаване на карти');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

        // Рендиране на опаковката
        $tpl = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);

        return $tpl;
    }


    /**
     * Връща информация за ваучера от подадения стринг
     *
     * @param string $number - номер за проверка
     * @param array $ignoreIfUsedIn - игнориране ако е използван в някой документ
     * @return null|stdClass - null, ако не е ваучер или обект с информация за него
     */
    public static function getByNumber($number, $ignoreIfUsedIn = array())
    {
        $number = trim($number);
        if(!static::checkCheckSum($number)) return;

        $res = array('id' => null);
        $rec = static::fetch(array("#number = '[#1#]'", $number));

        if(is_object($rec)){
            $res['id'] = $rec->id;
            $res['typeId'] = $rec->typeId;
            $res['validTime'] = voucher_Types::fetchField($rec->typeId, 'validTime');
            $res['referrer'] = $rec->referrer;
            if($rec->state == 'pending'){
                $res['error'] = 'Ваучерът още не е активиран|*!';
            } elseif(!empty($rec->usedOn)){
                if(!countR($ignoreIfUsedIn)){
                    $res['error'] = 'Ваучерът е използван|*!';
                } elseif(!($ignoreIfUsedIn['classId'] == $rec->classId && $ignoreIfUsedIn['objectId'] == $rec->objectId)){
                    $res['error'] = 'Ваучерът е използван|*!';
                }
            } elseif($rec->state == 'closed') {
                $res['error'] = 'Ваучерът е затворен|*!';
            }
        }

        return $res;
    }


    /**
     * Проверка дали подадения стринг, контролната му сума отговаря на ваучера
     *
     * @param string $string
     * @return bool
     */
    public static function checkCheckSum($string)
    {
        if(strlen($string) != 16) return false;

        // Извличане на първите 12 символа и проверка дали контролната им сума отговаря
        $firstPart = substr($string, 0, 12);
        $checkSumPart = substr($string, 12, 4);
        $expectedCheckSum = static::getCheckSum($firstPart);

        return $expectedCheckSum == $checkSumPart;
    }


    /**
     * Генериране на номер на ваучер;
     *
     * @return string
     */
    public static function getNumber()
    {
        // Генериране на произволни 12 цифри, към тях се добавя чексума от 4 цифри
        $str = str::getRand("############");
        $rest = static::getCheckSum($str);

        return "{$str}{$rest}";
    }


    /**
     * Връща контролната сума на подадения стринг
     *
     * @param string $string
     * @return string
     */
    public static function getCheckSum($string)
    {
        $hash = crc32("{$string}" . EF_SALT);
        $rest = $hash % 10000;

        return str_pad($rest, 4, "0",STR_PAD_LEFT);
    }


    /**
     * Подготовка на ваучерни карти на избраното лице
     */
    public function prepareCards($data)
    {
        $masterRec = $data->masterData->rec;
        $data->Pager = cls::get('core_Pager', array('itemsPerPage' => 10));

        $query = $this->getQuery();
        $data->listFields = arr::make('number=Номер,typeId=Вид,activatedOn=Активиране,usedOn=Употреба,state=Състояние', true);
        $query->where("#referrer = '{$masterRec->id}'");
        $query->EXT('haveReferrer', 'voucher_Types', "externalName=referrer,externalKey=typeId");

        $query->orderBy("#state");
        $data->Pager->setLimit($query);
        $fields = $this->selectFields();
        $fields['-detail'] = true;

        while ($rec = $query->fetch()) {
            $row = $this->recToVerbal($rec, $fields);
            if($rec->haveReferrer == 'yes'){
                $row->number = ht::createHint("<span style='color:blue'>{$row->number}</span>", 'Лицето е препоръчител|*!', 'notice', false);
            }
            $data->rows[$rec->id] = $row;
        }

        if($this->haveRightFor('linkToReferrer', (object)array('referrer' => $data->masterId))) {
            $linkUrl = array($this, 'linkToReferrer', 'referrer' => $data->masterId, 'ret_url' => true);
            $data->linkBtn = ht::createLink('', $linkUrl, null, array('ef_icon' => 'img/16/add.png', 'class' => 'addSalecond', 'title' => 'Свързване към клиентски ваучери'));
        }
    }


    /**
     * Рендиране на ваучерни карти на избрания клиент
     */
    public function renderCards($data)
    {
        $tpl = new core_ET('');
        $fieldset = new core_FieldSet("");
        $table = cls::get('core_TableView', array('mvc' => $fieldset));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        $data->listFields = core_TableView::filterEmptyColumns($data->rows, $data->listFields, 'usedOn');

        $details = $table->get($data->rows, $data->listFields);
        $tpl->append($details);
        if (isset($data->Pager)) {
            $tpl->append($data->Pager->getHtml());
        }

        if (isset($data->linkBtn)) {
            $tpl->append($data->linkBtn, 'addVoucherBtn');
        }

        return $tpl;
    }


    /**
     * Ф-я връщаща грешка ако има артикули изискващи препоръчител
     * и ваучерът не е с препоръчител (или няма ваулчер)
     *
     * @param int $id
     * @param array $products
     * @return string|null
     */
    public static function getErrorForVoucherAndProducts($id, $products, $startErrString = null)
    {
        if(isset($id)){
            $rec = static::fetchRec($id);

            $type = voucher_Types::fetch($rec->typeId);
            if($type->referrer == 'yes' && !empty($rec->referrer)) return null;
        }

        // Ако има артикули, които изискват ваучер от препоръчител
        $productsWithoutRequiredParams = array();
        $requireReferrerId = cat_Params::force('requireReferrer', 'Изискуем препоръчител', 'cond_type_YesOrNo', null, '', false, false);
        foreach ($products as $productId){
            $requireReferrerVal = cat_Products::getParams($productId, $requireReferrerId);
            if($requireReferrerVal != 'yes') continue;
            $productsWithoutRequiredParams[] = "<b>" . cat_Products::getTitleById($productId) . "</b>";
        }

        if(!countR($productsWithoutRequiredParams)) return null;

        // Ако няма ваучер или има такъв без препоръчител, тогава ще се покаже грешката
        if(isset($rec) && !empty($rec->referrer)) return null;

        $startErrString = !empty($startErrString) ? $startErrString : 'Следните артикули изискват ваучер от препоръчител';

        return "{$startErrString}|*: " . implode(', ', $productsWithoutRequiredParams);
    }


    /**
     * Връща дали има грешка при контиране
     *
     * @param int $id                    - ид на ваучер
     * @param array $products            - масив от ид-та на артикули
     * @param int $classId               - ид на клас
     * @param int $objectId              - ид на обект
     * @return string|void               - съобщение за грешка или null ако всичко е ок
     */
    public static function getContoErrors($id, $products, $classId, $objectId)
    {
        // Ако е за приключване на сторно бележка да не се проверява за изисквируем препоръчител
        if($classId == pos_Receipts::getClassId() && isset($objectId)){
            $receiptRec = pos_Receipts::fetch($objectId);
            if(isset($receiptRec->revertId)) return;
        }

        if(isset($id)){
            $rec = static::fetch($id);
            if(!empty($rec->usedOn)) {
                if(!($classId == $rec->classId && $objectId == $rec->objectId)) return 'Ваучерът е вече използван|*!';
            }
        }

        return static::getErrorForVoucherAndProducts($id, $products);
    }


    /**
     * Маркира ваучерът като използван/неизползван
     *
     * @param int $voucherId
     * @param bool $isUsed
     * @param int|null $classId
     * @param int|null $objectId
     * @return void
     */
    public static function mark($voucherId, $isUsed, $classId = null, $objectId = null, $close = false)
    {
        $rec = static::fetch($voucherId);

        if($isUsed){
            $rec->usedOn = dt::now();
            $rec->classId = $classId;
            $rec->objectId = $objectId;
            $rec->state = $close ? 'closed' : 'active';
        } else {
            $rec->usedOn = null;
            $rec->classId = null;
            $rec->objectId = null;
            $rec->state = 'active';
        }

        static::save($rec);
    }


    /**
     * Връща дали има грешка при възстановяване
     *
     * @param int $id
     * @return string|void
     */
    public static function getRestoreError($id)
    {
        $vRec = voucher_Cards::fetch($id);
        if(!empty($vRec->usedOn)) return "Ваучерът е вече използван|*!";

        if($vRec->state == 'closed') return "Ваучерът вече не е активен|*";
    }


    /**
     * Генериране на карти спрямо зададения тип
     *
     * @param stdClass $typeRec
     * @return void
     */
    public static function generateCards($typeRec)
    {
        $clone = (object)array('typeId' => $typeRec->id);

        $personIds = array(null => null);
        if(!empty($typeRec->groupId)){
            $personQuery = crm_Persons::getQuery();
            $personQuery->where("#state != 'rejected'");
            plg_ExpandInput::applyExtendedInputSearch('crm_Persons', $personQuery, $typeRec->groupId);

            $personQuery->show('id');
            $personIds = arr::extractValuesFromArray($personQuery->fetchAll(), 'id');
        }

        $now = dt::now();
        foreach ($personIds as $personId){

            // Генериране на уникални номера за ваучерите
            foreach (range(1, $typeRec->createCount) as $i){
                $c = clone $clone;
                $c->referrer = $personId;
                $c->number = self::getNumber();

                while (self::fetch(array("#number = '[#1#]'", $c->number))) {
                    $c->number = self::getNumber();
                }
                $c->state = !empty($c->referrer) ? 'active' : ($typeRec->referrer == 'yes' ? 'pending' : 'active');
                if($c->state == 'active'){
                    $c->activatedOn = $now;
                    if(!empty($typeRec->validTime)){
                        $c->validTo = dt::addSecs($typeRec->validTime, $c->activatedOn);
                    }
                }

                self::save($c);
            }
        }
    }


    /**
     * Търси по подадения баркод
     *
     * @param string $str
     * @return array
     *               ->title - заглавие на резултата
     *               ->url - линк за хипервръзка
     *               ->comment - html допълнителна информация
     *               ->priority - приоритет
     */
    public function searchByCode($str)
    {
        $resArr = array();

        $str = trim($str);
        $query = $this->getQuery();
        $query->where(array("#number = '[#1#]'", $str));
        $rec = $query->fetch();
        if(!$rec) return $resArr;

        $number = voucher_Cards::haveRightFor('list') ? ht::createLink($rec->number, array('voucher_Cards', 'list', 'search' => $str)) : $rec->number;
        $res = (object)array('title' => tr('Ваучер') . ": {$number}",
                             'url' => array(),
                             'comment' => tr('Тип') . ": " . voucher_Types::getHyperlink($rec->typeId),
                             'priority' => 1);
        $resArr[] = $res;

        return $resArr;
    }


    /**
     * Затваряне на изтеклите ваучери
     */
    function cron_CloseExpiredVoucher()
    {
        $now = dt::now();

        $save = array();
        $query = static::getQuery();
        $query->where("#validTo IS NOT NULL AND #validTo <= '{$now}'");
        while($rec = $query->fetch()){
            $rec->state = 'closed';
            $save[$rec->id] = $rec;
        }

        $this->saveArray($save, 'id,state');
    }
}
