<?php


/**
 * Клас 'voucher_Types'
 *
 * Мениджър за Групи ваучери
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
class voucher_Types extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Групи ваучери';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Група ваучер';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, plg_Sorting, voucher_Wrapper, plg_State2, label_plg_Print';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, voucher';


    /**
     * Кой има право да разглежда?
     */
    public $canSingle = 'ceo, voucher';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, voucher';


    /**
     * Кой има право да разглежда?
     */
    public $canList = 'ceo, voucher';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, voucher';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name=Група,count=Карти,referrer,priceListId,validTo=Валидност,state,createdOn,createdBy';


    /**
     * Интерфейсни методи
     */
    public $interfaces = 'label_SequenceIntf=voucher_interface_TypeLabelSource,bgerp_PersonalizationSourceIntf=voucher_interface_BlastPersonalizationSourceImpl';


    /**
     * Детайла, на модела
     */
    public $details = 'voucher_Cards';


    /**
     * Работен кеш
     */
    protected $generateOnShutdown = array();


    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';


    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'voucher/tpl/SingleLayoutType.shtml';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Име,mandatory');
        $this->FLD('referrer', 'enum(,no=Без,yes=Да)', 'caption=Препоръчител,mandatory');
        $this->FLD('priceListId', 'key(mvc=price_Lists,select=title,allowEmpty)', 'caption=Ценова политика');
        $this->FLD('groupId', 'key(mvc=crm_Groups,select=name,allowEmpty)', 'caption=Генериране на ваучери->За всяко лице в,input=hidden');
        $this->FNC('count', 'int', 'single=none');
        $this->FLD('validTo', 'date', 'caption=Генериране на ваучери->Валидни до,input=none');

        $this->setdbUnique('name');
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
        $rec = $form->rec;

        $parentOptions = price_Lists::getAccessibleOptions();
        $form->setOptions('priceListId', array('' => '') + $parentOptions);

        if(empty($rec->id)){
            $form->FLD('createCount', 'int(min=1)', 'caption=Генериране на ваучери->Брой,mandatory,after=priceListId');
            $form->setField('groupId', 'input');
            $form->setField('validTo', 'input');
        } else {
            if(voucher_Cards::count("#typeId = {$rec->id}")){
                $form->setReadOnly('referrer');
            }
        }
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = $form->rec;

            if(isset($rec->validTo)){
                if($rec->validTo <= dt::today()){
                    $form->setError('validTo', 'Трябва да е с бъдеща дата');
                }
            }

            if(!empty($rec->groupId) && !empty($rec->createCount)){
                $personCount = crm_Persons::count("#state != 'rejected' AND LOCATE('|{$rec->groupId}|', #groupList)");
                if(!$personCount){
                    $form->setError('groupId', 'В групата няма лица, на които да се генерира ваучер');
                } else {
                    $form->setWarning('groupId', "В групата има|* <b>{$personCount}</b> |лица|*. |Наистина ли искате за всяко лице да генерирате по|* <b>{$rec->createCount}</b> |ваучера|*?");
                }
            }
        }
    }


    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        $mvc->generateOnShutdown[$rec->id] = $rec;
    }


    /**
     * Изчиства записите, заопашени за запис
     *
     * @param acc_Items $mvc
     */
    public static function on_Shutdown($mvc)
    {
        if(countR($mvc->generateOnShutdown)){
            foreach ($mvc->generateOnShutdown as $rec){
                voucher_Cards::generateCards($rec);
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
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(isset($rec->priceListId)){
            $row->priceListId = price_Lists::getHyperlink($rec->priceListId, true);
        }

        $row->count = core_Type::getByName('int')->toVerbal(voucher_Cards::count("#typeId = {$rec->id}"));

        if(isset($fields['-single'])){
            $row->blasts = array();
            $bQuery = blast_Emails::getQuery();
            $bQuery->where("#perSrcClassId = {$mvc->getClassId()} AND #perSrcObjectId = {$rec->id} AND #state != 'rejected'");
            while($bRec = $bQuery->fetch()){
                $blastHandle = blast_Emails::getLink($bRec->id, 0)->getContent();
                $row->blasts[] =  "<div class='state-{$rec->state} document-handler'>{$blastHandle}</div>";
            }
            $row->blasts = implode(' ', $row->blasts);
        }
    }


    /**
     * Заглавие на източника на етикета
     */
    public function getLabelSourceLink($id)
    {
        return static::fetchRec($id)->name;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'delete' && isset($rec)){
            if(voucher_Cards::count("#typeId = {$rec->id}")){
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * След подготовка на тулбара на единичния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = &$data->rec;

        if (blast_Emails::haveRightFor('add') ) {
            if(voucher_Cards::count("#typeId = {$rec->id} AND #referrer IS NOT NULL")){
                Request::setProtected(array('perSrcObjectId', 'perSrcClassId'));
                $data->toolbar->addBtn('Циркулярен имейл', array('blast_Emails', 'add', 'perSrcClassId' => core_Classes::getId($mvc), 'perSrcObjectId' => $rec->id), 'id=btnEmails', 'ef_icon = img/16/emails.png,title=Създаване на циркулярен имейл');
                if(!static::getReferrersCountHavingField($rec->id, 'email,buzEmail')){
                    $data->toolbar->setError('btnEmails', 'Няма свързани лица с имейли, на които да се разпратят ваучерите|*!');
                }
            }
        }
    }


    /**
     * Ф-я връщаща колко лица има със стойност на съответното поле обвързано с ваучерът
     *
     * @param int $id
     * @param array|string $personFields
     * @return int
     */
    public static function getReferrersCountHavingField($id, $personFields = array())
    {
        $personFields = arr::make($personFields, true);
        $query = voucher_Cards::getQuery();
        $query->where("(#typeId = {$id} AND #referrer IS NOT NULL)");
        $where = "";
        foreach ($personFields as $fld){
            $query->EXT($fld, 'crm_Persons', "externalName={$fld},externalKey=referrer");
            $where .= (!empty($where) ? ' OR ' : '') . "#{$fld} IS NOT NULL";
        }
        $query->where($where);

        return $query->count();
    }


    /**
     * Подготовка на филтър формата
     *
     * @param core_Mvc $mvc
     * @param StdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();
        $data->listFilter->showFields = 'priceListId,referrer';
        $data->query->orderBy('id', 'ASC');

        if($filter = $data->listFilter->rec){
            if(isset($filter->referrer)){
                $data->query->where("#referrer = '{$filter->referrer}'");
            }

            if(isset($filter->priceListId)){
                $data->query->where("#priceListId = {$filter->priceListId}");
            }
        }
    }
}
