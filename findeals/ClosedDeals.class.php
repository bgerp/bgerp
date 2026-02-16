<?php
/**
 * Клас 'findeals_ClosedDeals'
 * Клас с който се приключва една финансова сделка
 *
 *
 *
 * @category  bgerp
 * @package   findeals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class findeals_ClosedDeals extends deals_ClosedDeals
{
    /**
     * Заглавие
     */
    public $title = 'Приключване на сделки';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'deals_ClosedDeals';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Dcd';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=findeals_transaction_CloseDeal';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'findeals_Wrapper, acc_plg_Contable, plg_RowTools, plg_Sorting,
                    doc_DocumentPlg, doc_plg_HidePrices, plg_Search';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,findeals';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,findeals';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, findeals, acc';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, findeals, acc';
    
    
    /**
     * Кой може да контира документите?
     */
    public $canConto = 'ceo,findeals';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Приключване на сделка';
    
    
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
    public $searchFields = 'notes,docId,classId';


    /**
     * Кои са сметките за изнвънредни приходи и разходи
     */
    protected $incomeAndCostAccounts = array('debit' => '6913', 'credit' => '7913');


    /**
     * След дефиниране на полетата на модела
     */
    public static function on_AfterDescription(core_Master &$mvc)
    {
        // Добавяме към модела, поле за избор на с коя сделка да се приключи
        $mvc->FLD('closeWith', 'key(mvc=findeals_Deals,allowEmpty)', 'caption=Приключи с,input=none');
    }


    /**
     * Имплементиране на интерфейсен метод
     *
     * @see deals_ClosedDeals::getDocumentRow()
     */
    public function getDocumentRow_($id)
    {
        $row = parent::getDocumentRow_($id);
        $title = "Приключване на сделка #{$row->saleId}";
        $row->title = $title;
        $row->recTitle = $title;
        
        return $row;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->DOC_NAME = tr('ФИНАНСОВА СДЕЛКА');
        if ($rec->amount > 0) {
            $row->type = tr('Приход');
        } elseif ($rec->amount < 0) {
            $row->type = tr('Разход');
        }
        
        //@TODO а ако е авансов отчет ??
        if ($rec->closeWith) {
            $dealState = findeals_Deals::fetchField($rec->closeWith, 'state');
            $row->closeWith = ht::createLink($row->closeWith, array('findeals_Deals', 'single', $rec->closeWith));
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
        
        if (!$firstDoc->isInstanceOf('findeals_Deals')) {
            
            return false;
        }
        
        return true;
    }


    /**
     * Преди оттегляне на документа за приключване
     */
    protected static function on_BeforeReject($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        if(dt::today() >= acc_Setup::getEurozoneDate() && $rec->valior < acc_Setup::getEurozoneDate()){
            $dealCurrencyId = cls::get($rec->docClassId)->fetchField($rec->docId, 'currencyId');
            if($dealCurrencyId == 'BGN'){
                core_Statuses::newStatus('|Не може да оттеглите приключване на финансова сделка в лева след приемането ни в еврозоната|*!', 'error');

                return false;
            }
        }
    }
}
