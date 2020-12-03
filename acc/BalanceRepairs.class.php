<?php


/**
 * Мениджър на документ за обиране на счетоводна разлика
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acc_BalanceRepairs extends core_Master
{
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'acc_TransactionSourceIntf=acc_transaction_BalanceRepair';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Корекция на грешки от закръгляния';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn';
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Clone, plg_Printing,acc_Wrapper, plg_Sorting, acc_plg_Contable,
                     doc_DocumentPlg, acc_plg_DocumentSummary, bgerp_plg_Blank, doc_plg_SelectFolder';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title=Документ,balanceId,state,createdOn,createdBy';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'balanceId';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'acc_BalanceRepairDetails';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Корекция на грешки от закръгляне';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/blog.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Brp';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'acc,ceo';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'acc,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,acc';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,acc';
    
    
    /**
     * Може ли да се контира въпреки, че има приключени пера в транзакцията
     */
    public $canUseClosedItems = true;
    
    
    /**
     * Дали при възстановяване/контиране/оттегляне да се заключва баланса
     *
     * @var bool TRUE/FALSE
     */
    public $lockBalances = true;
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'acc/tpl/SingleLayoutBalanceRepair.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '6.4|Счетоводни';
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'doc_UnsortedFolders';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'acc_BalanceRepairDetails';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('balanceId', 'key(mvc=acc_Balances,select=periodId)', 'caption=Баланс,mandatory');
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
        $form->setDefault('valior', dt::today());
        $form->setOptions('balanceId', array('' => '') + acc_Balances::getSelectOptions());
        
        if (!empty($form->rec->threadId)) {
            if ($origin = doc_Threads::getFirstDocument($form->rec->threadId)) {
                if ($origin->isInstanceOf('acc_ClosePeriods')) {
                    $periodId = $origin->fetchField('periodId');
                    $bId = acc_Balances::fetchField("#periodId = {$periodId}");
                    $form->setDefault('balanceId', $bId);
                }
            }
        }
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     *
     * @return bool
     */
    public static function canAddToThread($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        
        // Може да се добавя само към нишка с начало документ 'Приключване на период'
        if ($firstDoc->isInstanceOf('acc_ClosePeriods')) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $self = cls::get(get_called_class());
        
        return tr($self->singleTitle) . " №{$rec->id}";
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        $row->title = $this->getRecTitle($rec);
        
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->recTitle = $row->title;
        $row->state = $rec->state;
        
        return $row;
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
        if (acc_Balances::haveRightFor('single', $rec->balanceId)) {
            $row->balanceId = ht::createLink($row->balanceId, array('acc_Balances', 'single', $rec->balanceId), null, "ef_icon=img/16/table_sum.png, title=Оборотна ведомост {$row->balanceId}");
        }
        
        $row->title = $mvc->getLink($rec->id, 0);
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        if($rec->_isClone === true) return;
        
        $useDefaults = acc_Setup::get('BALANCE_REPAIR_NO_DEFAULTS');
        if($useDefaults != 'yes'){
            $defaultAccounts = keylist::toArray(acc_Setup::get('BALANCE_REPAIR_ACCOUNTS'));
            $defaultAmount = acc_Setup::get('BALANCE_REPAIR_AMOUNT_BELLOW');
            $defaultQuantity = acc_Setup::get('BALANCE_REPAIR_QUANITITY_BELLOW');
            
            foreach ($defaultAccounts as $accountId){
                $dRec = (object)array('repairId' => $rec->id, 'accountId' => $accountId, 'blQuantity' => $defaultQuantity, 'blAmount' => $defaultAmount);
                acc_BalanceRepairDetails::save($dRec);
            }
        }
    }
    
    
    /**
     * Дали преди да се извърши действие с документа, трябва да се изчака да се преизчисли баланса
     *
     * @see acc_plg_LockBalanceRecalc
     * @param mixed $rec
     *
     * @return boolean
     */
    public function doesRequireBalanceToBeRecalced_($rec)
    {
        $alternateWindow = acc_setup::get('ALTERNATE_WINDOW');
        
        if($alternateWindow) {
            $rec = $this->fetchRec($rec);
            $balanceRec = acc_Balances::fetch($rec->balanceId);
            
            $periodEnd = acc_Periods::fetchField($balanceRec->toDate, 'end');
            $windowStart = dt::addSecs(-$alternateWindow, null, false);
            
            return $periodEnd >= $windowStart;
        }
        
        return true;
    }
}
