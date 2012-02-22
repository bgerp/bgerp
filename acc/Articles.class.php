<?php



/**
 * Мениджър на мемориални ордери (преди "счетоводни статии")
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Articles extends core_Master
{
    
    
    /**
     * Какви интерфайси поддържа този мениджър
     */
    var $interfaces = 'acc_TransactionSourceIntf';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Мемориални Ордери";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, plg_Printing,
                     acc_Wrapper, plg_Sorting, acc_plg_Contable,doc_DocumentPlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id, reason, valior, totalAmount, tools=Пулт";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'reason';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'acc_ArticleDetails';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Мемориален ордер';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/blog.png';
    
    
    /**
     * Абривиатура
     */
    var $abbr = "MO";
    

    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'acc,admin';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'acc,admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'acc,admin';
    
    
    /**
     * Кой може да го контира?
     */
    var $canConto = 'acc,admin';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'acc,admin';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'acc/tpl/SingleArticle.shtml';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('reason', 'varchar(128)', 'caption=Основание,mandatory');
        $this->FLD('valior', 'date', 'caption=Вальор,mandatory');
        $this->FLD('totalAmount', 'double(decimals=2)', 'caption=Оборот,input=none');
        $this->FLD('state', 'enum(draft=Чернова,active=Контиран,rejected=Оттеглен)', 'caption=Състояние,input=none');
        
        //  $this->XPR('isRejected', 'int', "#state = 'rejected'", 'column=none,input=none');
        $this->FNC('isContable', 'int', 'column=none');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function on_CalcIsContable($mvc, $rec)
    {
        $rec->isContable =
        ($rec->state == 'draft');
    }
    
    
    /**
     * Прави заглавие на МО от данните в записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        $valior = self::getVerbal($rec, 'valior');
        
        return "{$rec->id}&nbsp;/&nbsp;{$valior}";
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($action == 'delete' || $action == 'edit') {
            if ($rec->id && !$rec->state) {
                $rec = $mvc->fetch($rec->id);
            }
            
            if ($rec->state != 'draft') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->totalAmount = '<strong>' . $row->totalAmount . '</strong>';
    }
    
    
    /**
     * Изпълнява се след подготовката на титлата в единичния изглед
     */
    function on_AfterPrepareSingleTitle($mvc, $res, $data)
    {
        $data->title .= " (" . $mvc->getVerbal($data->rec, 'state') . ")";
    }
    
    
    /**
     * Извиква се при промяна на някой от записите в детайл-модел
     *
     * @param core_Master $mvc
     * @param int $masterId първичен ключ на мастрър записа, чиито детайли са се променили
     * @param core_Detail $detailsMvc
     * @param stdClass $detailsRec данните на детайл записа, който е причинил промяната (ако има)
     */
    function on_AfterDetailsChanged($mvc, $res, $masterId, $detailsMvc, $detailsRec = NULL)
    {
        $mvc::updateAmount($masterId);
    }
    
    
    /**
     * Преизчислява дебитнито и кредитното салдо на статия
     *
     * @param int $id първичен ключ на статия
     */
    private static function updateAmount($id)
    {
        $query = acc_ArticleDetails::getQuery();
        $query->XPR('sumAmount', 'double', 'SUM(#amount)', array('dependFromFields'=>'amount'));
        $query->show('articleId, sumAmount');
        
        $result = NULL;
        
        if ($r = $query->fetch("#articleId = {$id}")) {
            $rec = (object) array(
                'id' => $r->articleId,
                'totalAmount' => $r->sumAmount
            );
            
            $result = self::save($rec);
        }
        
        return $result;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function conto_($recId)
    {
        $rec = $this->fetch($recId);
        
        $this->updateAmount($rec->id);
        
        $query = acc_ArticleDetails::getQuery();
        $query->where("#articleId = {$rec->id}");
        
        $entries = array();
        
        while ($entry = $query->fetch()) {
            $entries[] = (object)array(
                'amount' => $entry->amount,
                'debitAccId' => $entry->debitAccId,
                'debitEnt1' => $entry->debitEnt1,
                'debitEnt2' => $entry->debitEnt2,
                'debitEnt3' => $entry->debitEnt3,
                'debitQuantity' => $entry->debitQuantity,
                'debitPrice' => $entry->debitPrice,
                'creditAccId' => $entry->creditAccId,
                'creditEnt1' => $entry->creditEnt1,
                'creditEnt2' => $entry->creditEnt2,
                'creditEnt3' => $entry->creditEnt3,
                'creditQuantity' => $entry->creditQuantity,
                'creditPrice' => $entry->creditPrice,
            );
        }
        
        $res = acc_Journal::recordTransaction(
            $this,
            (object)array(
                'reason' => $rec->reason,
                'valior' => $rec->valior,
                'docId' => $rec->id,
                'totalAmount' => $rec->totalAmount,
            ),
            $entries
        );
        
        if ($res !== false) {
            $rec->state = 'active';
            $this->save($rec);
        }
        
        return $res;
    }
    
    /*******************************************************************************************
     * 
     *     Имплементация на интерфейса `acc_TransactionSourceIntf`
     * 
     ******************************************************************************************/
    
    
    /**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public static function getTransaction($id)
    {
        // Преизчислява сумата в мастър-записа. Опционална стъпка, може да се махне при нужда.
        self::updateAmount($id);
        
        // Извличаме мастър-записа
        $rec = self::fetch($id);
        expect($rec);   // @todo да връща грешка
        $result = (object)array(
            'reason' => $rec->reason,
            'valior' => $rec->valior,
            'totalAmount' => $rec->totalAmount,
            'entries' => array()
        );
        
        // Извличаме детайл-записите на документа. В случая просто копираме полетата, тъй-като
        // детайл-записите на мемориалните ордери имат същата структура, каквато е и на 
        // детайлите на журнала.
        $query = acc_ArticleDetails::getQuery();
        
        while ($entry = $query->fetch("#articleId = {$id}")) {
            $result->entries[] = (object)array(
                'amount' => $entry->amount,
                'debitAccId' => $entry->debitAccId,
                'debitEnt1' => $entry->debitEnt1,
                'debitEnt2' => $entry->debitEnt2,
                'debitEnt3' => $entry->debitEnt3,
                'debitQuantity' => $entry->debitQuantity,
                'debitPrice' => $entry->debitPrice,
                'creditAccId' => $entry->creditAccId,
                'creditEnt1' => $entry->creditEnt1,
                'creditEnt2' => $entry->creditEnt2,
                'creditEnt3' => $entry->creditEnt3,
                'creditQuantity' => $entry->creditQuantity,
                'creditPrice' => $entry->creditPrice,
            );
        }
        
        return $result;
    }
    
    
    /**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public static function finalizeTransaction($id)
    {
        $rec = (object)array(
            'id' => $id,
            'state' => 'active'
        );
        
        return self::save($rec);
    }
    
    
    /**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::rejectTransaction
     */
    public static function rejectTransaction($id)
    {
        $rec = self::fetch($id, 'id,state,valior');
        
        if ($rec) {
            if ($rec->state == 'draft') {
                // Записа не е контиран
                return self::delete($id);
            } elseif($rec->state == 'active') {
                
                $periodRec = acc_Periods::fetchByDate($rec->valior);
                
                if($periodRec->state == 'closed') {
                    $rec->state = 'revert';
                } else {
                    $rec->state = 'rejected';
                }
                
                self::save($rec);
            }
        }
    }
    
    /****************************************************************************************
     *                                                                                      *
     *  ИМПЛЕМЕНТАЦИЯ НА @link doc_DocumentIntf                                             *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row->title = $rec->reason;
        
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        $row->state = $rec->state;
        
        $row->status = $this->getVerbal($rec, 'totalAmount');
        
        return $row;
    }
}
