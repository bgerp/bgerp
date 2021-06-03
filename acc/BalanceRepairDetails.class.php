<?php


/**
 * Мениджър на детайли на счетоводните разлики
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acc_BalanceRepairDetails extends doc_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Детайл на документа за корекция на закръглянията';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Разлика';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'repairId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, acc_Wrapper, plg_RowNumbering, plg_StyleNumbers, plg_AlignDecimals,plg_SaveAndNew, plg_PrevAndNext';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'accountId, blQuantity=Поправка на количеството->Салдо под, blRoundQuantity=Поправка на количеството->Закръгляне, blAmount=Поправка на сумата->Салдо под, blRoundAmount=Поправка на сумата->Закръгляне';


    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Активен таб
     */
    public $currentTab = 'Операции->Разлики';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,acc';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,accMaster';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,acc';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,acc';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,accMaster';
    
    
    /**
     * Над каква сума да показва предупреждение
     */
    public $allowedLimit = 1;
    
    
    /**
     * Сметките от кои групи да могат да се показват за избор от сметкоплана
     *
     * @var string
     */
    public $selectAccountsFromByNum = '3,4,5,6,7';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('repairId', 'key(mvc=acc_BalanceRepairs)', 'column=none,input=hidden,silent,mandatory');
        $this->FLD('accountId', 'acc_type_Account(allowEmpty)', 'caption=Сметка,mandatory');
        $this->FLD('reason', 'varchar', 'caption=Информация');
        $this->FLD('blRoundQuantity', 'enum(,1,2,3,4,5)', 'caption=Поправка на стойността на количеството->Закръгляне,silent,removeAndRefreshForm=blQuantity');
        $this->FLD('blQuantity', 'double', 'caption=Поправка на стойността на количеството->Зануляване на крайно салдо');
        $this->FLD('blRoundAmount', 'enum(,1,2,3,4,5)', 'caption=Поправка на стойността на сумата->Закръгляне,silent,removeAndRefreshForm=blAmount');
        $this->FLD('blAmount', 'double', 'caption=Поправка на стойността на сумата->Зануляване на крайно салдо');
        
        $this->setDbUnique('repairId,accountId');
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        if ($form->isSubmitted()) {
            if(!empty($rec->blRoundQuantity) && !empty($rec->blQuantity)){
                $form->setWarning('blRoundQuantity,blQuantity', "За количеството трябва да е попълнено само едно от двете полета");
            }

            if(!empty($rec->blRoundAmount) && !empty($rec->blAmount)){
                $form->setWarning('blRoundAmount,blAmount', "За сумата трябва да е попълнено само едно от двете полета");
            }

            if ($rec->blQuantity > $mvc->allowedLimit ) {
                $form->setWarning('blQuantity', "Въведеното к-ва е над '{$mvc->allowedLimit}'");
            }

            if ($form->rec->blAmount > $mvc->allowedLimit) {
                $form->setWarning('blAmount', "Въведената сума е над '{$mvc->allowedLimit}'");
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
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $balanceId = $mvc->Master->fetchField($rec->repairId, 'balanceId');
        $row->accountId = acc_Balances::getAccountLink($rec->accountId, $balanceId, true, true);
        
        if(!empty($rec->reason)){
            $reason = $mvc->getVerbal($rec, 'reason');
            $row->accountId .= "<br><span class='quiet'>{$reason}</span>";
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'edit' || $action == 'delete' || $action == 'add') && isset($rec)) {
            if ($mvc->Master->fetchField($rec->{$mvc->masterKey}, 'state') != 'draft') {
                $requiredRoles = 'no_one';
            }
        }
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

        // Извличаме само сметките с посочените номера
        $nums = arr::make($mvc->selectAccountsFromByNum);
        $options = array();
        foreach ($nums as $num) {
            $options += cls::get('acc_Accounts')->makeArray4Select('title', array("#num LIKE '[#1#]%' AND state NOT IN ('closed')", $num));
        }
        
        $form->setOptions('accountId', $options);
        
        // Задаване на дефолти при нужда
        $useDefaults = acc_Setup::get('BALANCE_REPAIR_NO_DEFAULTS');
        if($useDefaults != 'yes'){
            if(empty($rec->blRoundQuantity)){
                $form->setDefault('blQuantity', acc_Setup::get('BALANCE_REPAIR_QUANITITY_BELLOW'));
            }

            if(empty($rec->blRoundAmount)){
                $form->setDefault('blAmount', acc_Setup::get('BALANCE_REPAIR_AMOUNT_BELLOW'));
            }
        }
    }
}
