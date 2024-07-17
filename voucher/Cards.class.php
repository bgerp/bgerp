<?php


/**
 * Клас 'voucher_Cards'
 *
 * Мениджър за карти за ваучери
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
class voucher_Cards extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Ваучерни карти';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Ваучерна карта';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, voucher_Wrapper, plg_State2, plg_Select, plg_Search, plg_Sorting';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, voucher';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой има право да разглежда?
     */
    public $canList = 'ceo, voucher';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, voucher';


    /**
     * Кой може да генерира карти
     */
    public $canGenerate = 'ceo, voucher';


    /**
     * Кой може да свързва препоръчител с карти
     */
    public $canLinktoreferrer = 'ceo, voucher';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'number,typeId,usedOn,objectId,referrer,createdOn,createdBy,state';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'number,referrer';


    /**
     * Константа за състояние използвано
     */
    const STATUS_USED = 'USED';


    /**
     * Константа за състояние деактивирано
     */
    const STATUS_INACTIVE = 'INACTIVE';


    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'usedOn,classId,objectId,referrer';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('number', 'varchar(16)', 'caption=Номер,mandatory,input=none');
        $this->FLD('typeId', 'key(mvc=voucher_Types,select=name)', 'caption=Тип,mandatory,silent,removeAndRefreshForm=referrer');
        $this->EXT('priceListId', 'voucher_Types', 'externalName=priceListId,externalKey=typeId');
        $this->EXT('requireReferrer', 'voucher_Types', 'externalName=referrer,externalKey=typeId');

        $this->FLD('referrer', 'key2(mvc=crm_Persons,select=name,allowEmpty)', 'caption=Препоръчител');
        $this->FLD('usedOn', 'datetime(format=smartTime)', 'caption=Употреба->Кога,input=none');
        $this->FLD('classId', 'class', 'caption=Обект,input=none');
        $this->FLD('objectId', 'int', 'caption=Употреба->Къде,input=none');

        $this->setdbUnique('number');
        $this->setDbIndex('referrer');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;

        if($form->rec->typeId){
            $requireReferrer = voucher_Types::fetchField($form->rec->typeId, 'referrer');
            if($requireReferrer == 'no'){
                $form->setField('referrer', 'input=none');
            }
        }
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if ($mvc->haveRightFor('generate')) {
            $data->toolbar->addBtn('Генериране', array($mvc, 'generate', 'ret_url' => true), null, 'ef_icon = img/16/star_2.png,title=Масово създаване на карти');
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if(isset($rec->referrer)){
            $row->referrer = crm_Persons::getHyperlink($rec->referrer, true);
        }

        if(isset($rec->classId) && isset($rec->objectId)){
            $row->objectId = cls::get($rec->classId)->getHyperlink($rec->objectId, true);
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
        $data->listFilter->FLD('isUsed', 'enum(all=Всички,used=Използвани,unUsed=Неизползвани)');
        $data->listFilter->setDefault('isUsed', 'all');
        $data->listFilter->setFieldTypeParams('typeId', 'allowEmpty');
        $data->listFilter->showFields = 'search,typeId,isUsed,referrer';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();

        if($filter = $data->listFilter->rec){
            if(isset($filter->referrer)){
                $data->query->where("#referrer = {$filter->referrer}");
                unset($data->listFields['referrer']);
            }

            if(isset($filter->typeId)){
                $data->query->where("#typeId = {$filter->typeId}");
                unset($data->listFields['typeId']);
            }

            if($filter->isUsed != 'all'){
                $where = $filter->isUsed == 'used' ? "#usedOn IS NOT NULL" : "#usedOn IS NULL";
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

        if($action == 'changestate' && isset($rec)){
            if(!empty($rec->usedOn)){
                $requiredRoles = 'no_one';
            }
        }
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
        $form->title = "Ваучъри към препоръчител|*: " . cls::get('crm_Persons')->getFormTitleLink($referrerRec);;
        $form->FLD('text', 'text(rows=5)', 'caption=Ваучъри,mandatory');
        $form->input();

        // Ако е събмитната формата
        if ($form->isSubmitted()) {
            $rec = $form->rec;

            $errors = $okVouchers = array();
            $valueArr = explode("\n", $rec->text);
            foreach ($valueArr as $v){
                $res = static::getByNumber($v);
                if(!$res){
                    $errors[] = "Невалиден номер|*: <b>{$v}</b>";
                } else {
                    if(empty($res['id'])){
                        $errors[] = "Невалиден номер|*: <b>{$v}</b>";
                    } elseif($res['requireReferrer'] == 'no'){
                        $errors[] = "Ваучерът не очаква препорачител|*: <b>{$v}</b>";
                    } elseif($res['status'] == self::STATUS_USED) {
                        $errors[] = "Ваучерът е използван|*: <b>{$v}</b>";
                    } elseif($res['referrer']) {
                        $errors[] = "Ваучерът е вече свързан|*: <b>{$v}</b>";
                    } else {
                        $okVouchers[] = (object)array('id' => $res['id'], 'referrer' => $referrerRec->id);
                    }
                }
            }

            if(countR($errors)){
                $errorString = implode("<br>", $errors);
                $form->setError('text', $errorString);
            }

            if(!$form->gotErrors()){
                $count = countR($okVouchers);
                $this->saveArray($okVouchers, 'id,referrer');

                followRetUrl(null, "Обвързани ваучери|*: {$count}");
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
     * Създаване на ваучери
     */
    public function act_Generate()
    {
        // Подготвяме форма за започване на нова тема
        $this->requireRightFor('generate');

        // Подготовка на формата
        $form = $this->getForm();
        $form->title = 'Създаване на ваучерни карти';
        $form->FLD('count', 'int(Min=1)', 'caption=Брой,mandatory,after=typeId');
        $form->setField('referrer', 'input=none');
        $form->input();

        // Ако е събмитната формата
        if ($form->isSubmitted()) {
            $fRec = $form->rec;
            $clone = (object)array('typeId' => $fRec->typeId);

            // Генериране на уникални номера за ваучерите
            foreach (range(1, $fRec->count) as $i){
                $c = clone $clone;
                $c->number = self::getNumber();
                while (self::fetch(array("#number = '[#1#]'", $c->number))) {
                    $c->number = self::getNumber();
                }
                $this->save($c);
            }

            followRetUrl(null, "Генерирани карти|*: {$fRec->count}");
        }

        $form->toolbar->addSbBtn('Създаване', 'save', 'ef_icon = img/16/star_2.png, title = Създаване на карти');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

        // Рендиране на опаковката
        $tpl = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);

        return $tpl;
    }


    /**
     * Връща информация за ваучъра от подадения стринг
     *
     * @param string $number - номер за проверка
     * @return null|stdClass - null, ако не е ваучер или обект с информация за него
     */
    public static function getByNumber($number)
    {
        $number = trim($number);
        if(!static::checkCheckSum($number)) return;

        $res = array('id' => null);
        $rec = static::fetch(array("#number = '[#1#]'", $number));

        if(is_object($rec)){
            $res['id'] = $rec->id;
            $res['referrer'] = $rec->referrer;
            if(!empty($rec->usedOn)){
                $res['status'] = self::STATUS_USED;
            } elseif($rec->state == 'closed') {
                $res['status'] = self::STATUS_INACTIVE;
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
        $hash = crc32($string + EF_SALT);
        $rest = $hash % 10000;

        return str_pad($rest, 4, "0",STR_PAD_LEFT);
    }


    /**
     * Подготовка на клиентските карти на избраното лице
     */
    public function prepareCards($data)
    {
        $masterRec = $data->masterData->rec;

        $query = $this->getQuery();
        $data->listFields = arr::make('number=Карта,typeId=Вид,usedOn=Употреба,state=Състояние', true);
        $query->where("#referrer = '{$masterRec->id}'");
        $query->orderBy("#state");
        while ($rec = $query->fetch()) {
            $row = $this->recToVerbal($rec);
            $data->rows[$rec->id] = $row;
        }

        if($this->haveRightFor('linkToReferrer', (object)array('referrer' => $data->masterId))) {
            $linkUrl = array($this, 'linkToReferrer', 'referrer' => $data->masterId, 'ret_url' => true);
            $data->linkBtn = ht::createLink('', $linkUrl, null, array('ef_icon' => 'img/16/add.png', 'class' => 'addSalecond', 'title' => 'Свързване към клиентски ваучери'));
        }
    }


    /**
     * Рендиране на клиентските карти на избрания клиент
     */
    public function renderCards($data)
    {
        $tpl = new core_ET('');
        $fieldset = new core_FieldSet("");
        $table = cls::get('core_TableView', array('mvc' => $fieldset));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        $details = $table->get($data->rows, $data->listFields);
        $tpl->append($details);

        if (isset($data->linkBtn)) {
            $tpl->append($data->linkBtn, 'addVoucherBtn');
        }

        return $tpl;
    }


    public static function getContoErrors($id, $products)
    {
        if(isset($id)){
            $rec = static::fetch($id);
            if(!empty($rec->usedOn)) return 'Ваучерът е вече използван|*!';
            if(!empty($rec->referrer)) return;
            if($rec->requireReferrer == 'no') return;
        }

        $productsWithoutRequiredParams = array();
        $requireReferrerId = cat_Params::force('requireReferrer', 'Изискуем препоръчител', 'cond_type_YesOrNo', null, '', false, false);
        foreach ($products as $productId){
            $requireReferrer = cat_Products::getParams($productId, $requireReferrerId);
            if($requireReferrer != 'yes') continue;
            $productsWithoutRequiredParams[] = cat_Products::getTitleById($productId);
        }

        if(countR($productsWithoutRequiredParams)){

            return 'Следните артикули изискват ваучър от препоръчител|* ' . implode(', ', $productsWithoutRequiredParams);
        }
    }


    public static function mark($voucherId, $type, $classId = null, $objectId = null)
    {
        $rec = static::fetch($voucherId);

        if($type == 'used'){
            $rec->usedOn = dt::now();
            $rec->classId = $classId;
            $rec->objectId = $objectId;
            $rec->state = 'closed';
        } else {
            $rec->usedOn = null;
            $rec->classId = null;
            $rec->objectId = null;
            $rec->state = 'active';
        }

        static::save($rec);
    }

    public static function getRestoreError($id)
    {
        $vRec = voucher_Cards::fetch($id);
        if(!empty($vRec->usedOn)) return "Ваучерът е вече използван|*!";

        if($vRec->state == 'closed') return "Ваучерът вече не е активен|*";
    }
}
