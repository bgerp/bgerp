<?php
/**
 * Клас 'sales_ClosedDeals'
 * Клас с който се приключва една продажба
 *
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_ClosedDeals extends deals_ClosedDeals
{
    /**
     * Заглавие
     */
    public $title = 'Документи за приключване на продажба';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Scd';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=sales_transaction_CloseDeal';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper, acc_plg_Contable, plg_RowTools, plg_Sorting,doc_DocumentPlg, doc_plg_HidePrices, plg_Search';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRestore = 'ceo,sales';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,sales';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,sales';
    
    
    /**
     * Кой може да контира документите?
     */
    public $canConto = 'ceo,sales';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Приключване на продажба';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.9|Търговия';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'costAmount, incomeAmount';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'notes,docId,classId, id';
    
    
    /**
     * Списък с роли на потребители, при действията на които с дадения документ
     * абонираните потребители не се нотифицират
     */
    public $muteNotificationsBy = 'system';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'valior';


    /**
     * Кои са сметките за изнвънредни приходи и разходи
     */
    protected $incomeAndCostAccounts = array('debit' => '6911', 'credit' => '7911');


    /**
     * След дефиниране на полетата на модела
     */
    public static function on_AfterDescription(core_Master &$mvc)
    {
        // Добавяме към модела, поле за избор на с коя сделка да се приключи
        $mvc->FLD('closeWith', 'key(mvc=sales_Sales,allowEmpty)', 'caption=Приключи с,input=none');
    }
    
    
    /**
     * Имплементиране на интерфейсен метод
     *
     * @see deals_ClosedDeals::getDocumentRow()
     */
    public function getDocumentRow_($id)
    {
        $row = parent::getDocumentRow_($id);
        $title = "Приключване на продажба #{$row->saleId}";
        $row->title = $title;
        $row->recTitle = $title;
        
        return $row;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->DOC_NAME = tr('ПРОДАЖБА');
        
        if ($rec->closeWith) {
            $dealState = sales_Sales::fetchField($rec->closeWith, 'state');
            $row->closeWith = ht::createLink($row->closeWith, array('sales_Sales', 'single', $rec->closeWith))->getContent();
            $row->closeWith = "<span class= 'state-{$dealState} document-handler'>{$row->closeWith}</span>";
        }
    }
    
    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'search';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list', 'show' => Request::get('show')), 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->input(null, 'silent');
    }
    
    
    /**
     * Дали разликата на доставеното - платеното е в допустимите граници
     */
    public static function isSaleDiffAllowed($saleRec)
    {
        $diff = round($saleRec->amountBl, 2);
        $conf = core_Packs::getConfig('acc');
        $res = ($diff >= -1 * $conf->ACC_MONEY_TOLERANCE && $diff <= $conf->ACC_MONEY_TOLERANCE);
        
        return $res;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($res == 'no_one') {
            
            return;
        }
        
        if (($action == 'add' || $action == 'conto' || $action == 'restore') && isset($rec)) {
            
            // Ако има ориджин
            if ($origin = $mvc->getOrigin($rec)) {
                $originRec = $origin->fetch();
                
                // Ако няма сч. движения по сделката не може да се приключи
                if ($originRec->state == 'active' && $origin->isInstanceOf('sales_Sales')) {
                    
                    // Ако разликата между доставеното/платеното е по голяма, се изисква
                    // потребителя да има по-големи права за да създаде документа
                    if (!self::isSaleDiffAllowed($originRec)) {
                        $res = 'ceo,salesMaster';
                    }
                }
            }
        }
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     */
    public static function canAddToThread($threadId)
    {
        // Може ли да се добави към нишката
        $res = parent::canAddToThread($threadId);
        if (!$res) {
            
            return false;
        }
        
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        
        // Може само към нишка, породена от продажба
        if (!$firstDoc->isInstanceOf('sales_Sales')) {
            
            return false;
        }
        
        return true;
    }
}
