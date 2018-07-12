<?php


/**
 * Мениджър на Каси
 *
 *
 * @category  bgerp
 * @package   cash
 *
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cash_Cases extends core_Master
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'acc_RegisterIntf, cash_CaseAccRegIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Фирмени каси';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Каса';
    
    
    /**
     * Икона за единичен изглед
     */
    public $singleIcon = 'img/16/safe-icon.png';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name,cashiers,activateRoles,selectUsers,selectRoles,blAmount=Сума';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, acc_plg_Registry, cash_Wrapper, bgerp_plg_FLB, plg_Current, doc_FolderPlg, plg_Created, plg_Rejected, plg_State, plg_Modified';
    
    
    /**
     * Кой може да пише
     */
    public $canWrite = 'ceo, admin';
    
    
    /**
     * Кой може да пише
     */
    public $canReject = 'ceo, admin';
    
    
    /**
     * Кой може да пише
     */
    public $canRestore = 'ceo, admin';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    public $canReports = 'ceo,cash,acc';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    public $canAddacclimits = 'ceo,cashMaster,accMaster,accLimits';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, cash';
    
    
    /**
     * Кой може да активира?
     */
    public $canActivate = 'ceo, cash';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,cash';
    
    
    /**
     * Детайли на този мастър обект
     *
     * @var string|array
     */
    public $details = 'AccReports=acc_ReportDetails';
    
    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '501';
    
    
    /**
     * По кой итнерфейс ще се групират сметките
     */
    public $balanceRefGroupBy = 'cash_CaseAccRegIntf';
    
    
    /**
     * Всички записи на този мениджър автоматично стават пера в номенклатурата със системно име
     * $autoList.
     *
     * @see acc_plg_Registry
     *
     * @var string
     */
    public $autoList = 'case';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'cash/tpl/SingleLayoutCases.shtml';
    
    
    /**
     * Да се създаде папка при създаване на нов запис
     */
    public $autoCreateFolder = 'instant';
    
    
    /**
     * Поле за избор на потребителите, които могат да активират обекта
     *
     * @see bgerp_plg_FLB
     */
    public $canActivateUserFld = 'cashiers';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(255)', 'caption=Наименование,oldFiled=Title,mandatory');
        $this->FLD('cashiers', 'userList(roles=cash|ceo)', 'caption=Контиране на документи->Потребители');
        $this->FLD('autoShare', 'enum(yes=Да,no=Не)', 'caption=Споделяне на сделките с другите отговорници->Избор,notNull,default=yes,maxRadio=2');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Изпълнява се преди преобразуването към вербални стойности на полетата на записа
     */
    protected static function on_BeforeRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (is_object($rec)) {
            if (isset($fields['-list'])) {
                $rec->name = $mvc->singleTitle . " \"{$rec->name}\"";
            }
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal(&$mvc, &$row, &$rec, $fields = array())
    {
        $row->STATE_CLASS .= ($rec->state == 'rejected') ? ' state-rejected' : ' state-active';
        
        if (isset($fields['-list'])) {
            if ($mvc->haveRightFor('select', $rec)) {
                $caseItem = acc_Items::fetchItem($mvc->getClassId(), $rec->id);
                $rec->blAmount = 0;
                
                // Намираме всички записи от текущия баланс за това перо
                if ($balRec = acc_Balances::getLastBalance()) {
                    $bQuery = acc_BalanceDetails::getQuery();
                    acc_BalanceDetails::filterQuery($bQuery, $balRec->id, $mvc->balanceRefAccounts, null, $caseItem->id);
                    
                    // Събираме ги да намерим крайното салдо на перото
                    while ($bRec = $bQuery->fetch()) {
                        $rec->blAmount += $bRec->blAmount;
                    }
                }
                
                // Обръщаме го във четим за хората вид
                $Double = cls::get('type_Double');
                $Double->params['decimals'] = 2;
                $row->blAmount = "<span style='float:right'>" . $Double->toVerbal($rec->blAmount) . '</span>';
                if ($rec->blAmount < 0) {
                    $row->blAmount = "<span style='color:red'>{$row->blAmount}</span>";
                }
            }
        }
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    protected static function on_AfterPrepareListFields($mvc, $data)
    {
        $data->listFields['blAmount'] .= ', ' . acc_Periods::getBaseCurrencyCode();
    }
    
    
    /**
     * Подготвя и осъществява търсене по каса, изпозлва се в касовите документи
     *
     * @param stdClass $data
     * @param array    $fields - масив от полета в полета в които ще се
     *                         търси по caseId
     */
    public static function prepareCaseFilter(&$data, $fields = array())
    {
        $data->listFilter->FNC('case', 'key(mvc=cash_Cases,select=name,allowEmpty)', 'caption=Каса,width=10em,silent');
        $data->listFilter->showFields .= ',case';
        $data->listFilter->setDefault('case', static::getCurrent('id', false));
        $data->listFilter->input();
        
        if ($filter = $data->listFilter->rec) {
            if ($filter->case) {
                foreach ($fields as $i => $fld) {
                    $or = ($i === 0) ? false : true;
                    $data->query->where("#{$fld} = {$filter->case}", $or);
                }
            }
        }
    }
    
    
    /**
     * След рендиране на лист таблицата
     */
    protected static function on_AfterRenderListTable($mvc, &$tpl, &$data)
    {
        if (!count($data->rows)) {
            
            return;
        }
        
        foreach ($data->recs as $rec) {
            $total += $rec->blAmount;
        }
        
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $total = $Double->toVerbal($total);
        if ($total < 0) {
            $total = "<span style='color:red'>{$total}</span>";
        }
        
        $currencyId = acc_Periods::getBaseCurrencyCode();
        $state = (Request::get('Rejected', 'int')) ? 'rejected' : 'closed';
        $colspan = count($data->listFields) - 1;
        $lastRow = new ET("<tr style='text-align:right' class='state-{$state}'><td colspan='{$colspan}'>[#caption#]: &nbsp;<span class='cCode'>{$currencyId}</span> <b>[#total#]</b> </td><td>&nbsp;</td></tr>");
        $lastRow->replace(tr('Общо'), 'caption');
        $lastRow->replace($total, 'total');
        
        $tpl->append($lastRow, 'ROW_AFTER');
    }
    
    
    /*******************************************************************************************
     *
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see cash_CaseAccRegIntf
     *
     ******************************************************************************************/
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     *
     * @param int $objectId
     */
    public static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = null;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object) array(
                'num' => $rec->id . ' cs',
                'title' => $rec->name,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     *
     * @param int $objectId
     */
    public static function itemInUse($objectId)
    {
        // @todo!
    }
    
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */
}
