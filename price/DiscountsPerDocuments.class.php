<?php


/**
 * Общи отстъпки за документи
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Общи отстъпки за документи
 */
class price_DiscountsPerDocuments extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Общи отстъпки';


    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'documentId';


    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Обща отстъпка';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, price_Wrapper';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'documentId=Документ,amount,description';


    /**
     * Кой може да го промени?
     */
    public $canEdit = 'powerUser';

    /**
     * Кой може да изтрива?
     */
    public $canDelete = 'powerUser';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;


    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'documentClassId,documentId';


    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 20;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('documentClassId', 'class', 'column=none,notNull,silent,input=hidden,mandatory');
        $this->FLD('documentId', 'int', 'column=none,notNull,silent,input=hidden,mandatory,tdClass=leftCol');

        $this->FLD('amount', 'double(decimals=2,Min=0)', 'mandatory,caption=Сума');
        $this->FLD('description', 'varchar', 'mandatory,caption=Основание');

        $this->setDbIndex('documentClassId,documentId');
    }


    /**
     * Връща съответния мастер
     */
    public function getMasterMvc_($rec)
    {
        return cls::get($rec->documentClassId);
    }


    /**
     * Връща списъка от мастър-мениджъри на зададен детайл-запис.
     *
     * Обикновено детайлите имат точно един мастър. Използваме този метод в случаите на детайли
     * с повече от един мастър, който евентуално зависи и от данните в детайл-записа $rec.
     *
     * @param stdClass $rec
     *
     * @return array масив от core_Master-и. Ключа е името на полето на $rec, където се
     *               съхранява външния ключ към съотв. мастър
     */
    public function getMasters_($rec)
    {
        if(isset($rec)) return array('documentId' => cls::get($rec->documentClassId));

        return array();
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява изтриването на вече използвани сметки
     *
     * @param core_Mvc      $mvc
     * @param string        $requiredRoles
     * @param string        $action
     * @param stdClass|NULL $rec
     * @param int|NULL      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'add' && isset($rec)){
            if(empty($rec->documentClassId) || empty($rec->documentId)){
                $requiredRoles = 'no_one';
            } else {
                $Document = new core_ObjectReference($rec->documentClassId, $rec->documentId);
                if(!$Document->canHaveTotalDiscount()){
                    $requiredRoles = 'no_one';
                }
            }
        }

        if(in_array($action, array('add', 'delete', 'edit')) && isset($rec)){
            $Document = new core_ObjectReference($rec->documentClassId, $rec->documentId);
            if(!$Document->haveRightFor('edit')){
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $rec = $data->form->rec;
        $data->form->title = core_Detail::getEditTitle($rec->documentClassId, $rec->documentId, 'обща отстъпка', null);
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        $sourceData = cls::get($rec->documentClassId)->getTotalDiscountSourceData($rec->documentId);
        $vatUnit = ($sourceData->chargeVat == 'yes') ? tr('с ДДС') : tr('без ДДС');
        $form->setField('amount', array('unit' => "|*{$sourceData->currencyId}, {$vatUnit}"));

        // Зареждат се последните основания за този документ
        $query = $mvc->getQuery();
        $query->where("#documentClassId = {$rec->documentClassId}");
        $query->show('description');
        $descriptions = arr::extractValuesFromArray($query->fetchAll(), 'description');
        if(countR($descriptions)){
            $descriptions = array_combine($descriptions, $descriptions);
            $form->setSuggestions('description', array('' => '') + $descriptions);
        }
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
        $Class = cls::get($rec->documentClassId);
        $row->documentId = $Class->getLink($rec->documentId, 0);
        $sourceData = $Class->getTotalDiscountSourceData($rec->documentId);

        $row->amount = "<span class='cCode'>{$sourceData->currencyId}</span> <b class='lighterBold'>{$row->amount}</b>";
    }


    /**
     * След подготовка на детайла
     *
     * @param stdClass $data
     * @return void
     */
    public function prepareDetail($data)
    {
        $data->recs = $data->rows = array();
        $data->listFields = arr::make('description=Описание,amount=Обща отстъпка');

        $query = $this->getQuery();
        $query->where("#documentClassId = {$data->masterMvc->getClassId()} AND #documentId = {$data->masterId}");
        $query->orderBy('id', 'ASC');

        // Подготовка на детайлите
        while($rec = $query->fetch()){
            $data->recs[$rec->id] = $rec;
            $row = $this->recToVerbal($rec);
            $data->rows[$rec->id] = $row;
        }
    }


    /**
     * След подготовка на детайла
     *
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public function renderDetail($data)
    {
        $tpl = new core_ET("");
        if(!countR($data->rows)) return $tpl;

        $listTableMvc = clone $this;
        $table = cls::get('core_TableView', array('mvc' => $listTableMvc, 'thHide' => true));

        $listTableMvc->invoke('BeforeRenderListTable', array($tpl, &$data));
        $tableTpl = $table->get($data->rows, $data->listFields);
        $tpl->append($tableTpl);

        return $tpl;
    }


    /**
     * Връща твърдите отстъпки за документа
     *
     * @param mixed $documentClass
     * @param int|stdClass $documentId
     * @return float|int|null
     */
    public static function getDiscount4Document($documentClass, $documentId)
    {
        $Class = cls::get($documentClass);
        $documentId = is_numeric($documentId) ? $documentId : $documentId->id;

        $query = static::getQuery();
        $query->where("#documentClassId = {$Class->getClassId()} AND #documentId = {$documentId}");
        $query->XPR('sumAmount', 'double', 'SUM(#amount)');
        $rec = $query->fetch();
        $sum = $rec->sumAmount;
        if($sum){
            $sourceData = $Class->getTotalDiscountSourceData($documentId);

            return $sum * $sourceData->rate;
        }

        return null;
    }


    /**
     * След изтриване на запис
     */
    public static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        // Има ли останали документи с изтрити всички общи отстъпки
        $resetRecs = array();
        foreach ($query->getDeletedRecs() as $rec) {
            if(!price_DiscountsPerDocuments::count("#documentClassId = {$rec->documentClassId} AND #documentId = {$rec->documentId}")){
                $resetRecs[$rec->documentClassId][$rec->documentId] = $rec->documentId;
            }
        }

        // За всеки
        foreach ($resetRecs as $documentClass => $documentIds){
            $Class = cls::get($documentClass);
            if(!isset($Class->mainDetail)) continue;

            // Нулират се неговите автоматични отстпки
            $dRecs = array();
            $Detail = cls::get($Class->mainDetail);
            $dQuery = $Detail->getQuery();
            $dQuery->in($Detail->masterKey, $documentIds);
            $dQuery->where("#autoDiscount IS NOT NULL");
            while($dRec = $dQuery->fetch()){
                $dRec->autoDiscount = null;
                $dRecs[] = $dRec;
            }

            $Detail->saveArray($dRecs, 'id,autoDiscount');
        }
    }


    /**
     * Има ли общи отстъпки към документа
     *
     * @param mixed $mvc
     * @param int $id
     * @return bool
     */
    public static function haveDiscount($mvc, $id)
    {
        $mvc = cls::get($mvc);
        $rec = price_DiscountsPerDocuments::fetch("#documentClassId = {$mvc->getClassId()} AND #documentId = {$id}");

        return is_object($rec);
    }


    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->FLD('document', 'varchar(128)', 'silent,caption=Документ,placeholder=Хендлър');
        $data->listFilter->showFields = 'document';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();
        $data->query->orderBy('id', 'DESC');

        if ($fRec = $data->listFilter->rec) {
            if (isset($fRec->document)) {
                $document = doc_Containers::getDocumentByHandle($fRec->document);
                if (is_object($document)) {
                    $data->query->where("#documentClassId = {$document->getClassId()} AND #documentId = {$document->that}");
                }
            }
        }
    }
}