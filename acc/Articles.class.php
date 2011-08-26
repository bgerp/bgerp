<?php

/**
 * Мениджър на мемориални ордери (преди "счетоводни статии")
 */
class acc_Articles extends core_Master 
{
    /**
     * Какви интерфайси поддържа този мениджър
     */
    var $interfaces = 'acc_TransactionSourceIntf';

    /**
     *  Заглавие на мениджъра
     */
    var $title = "Мемориални Ордери";
    
    
    /**
     *  Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_State, plg_RowTools, plg_Printing,
                     acc_Wrapper, plg_Sorting, acc_plg_Contable';
    
    
    /**
     *  Полета, които ще се показват в листов изглед
     */
    var $listFields = "id, reason, valior, totalAmount, tools=Пулт";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $details = 'Entries=acc_ArticleDetails';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $singleTitle = 'Мемориален ордер';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'acc,admin';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canWrite = 'acc,admin';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'acc,admin';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canConto = 'acc,admin';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canReject = 'acc,admin';
    
    
    /**
     * @var acc_ArticleDetails
     */
    var $Entries;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('reason', 'varchar(128)', 'caption=Основание,mandatory');
        $this->FLD('valior', 'date', 'caption=Вальор,mandatory');
        $this->FLD('totalAmount', 'double(decimals=2)', 'caption=Оборот,input=none');
        $this->FLD('state', 'enum(draft=Чернова,active=Контиран,rejected=Сторниран)', 'caption=Състояние,input=none');
        $this->XPR('isRejected', 'int', "#state = 'rejected'", 'column=none,input=none');
        $this->FNC('isContable', 'int', 'column=none');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_CalcIsContable($mvc, $rec)
    {
        $rec->isContable =
        ($rec->state == 'draft');
    }
    
    
    
    /**
     *  Извиква се след изчисляването на необходимите роли за това действие
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
        } elseif ($action == 'conto') {
            if ($rec->id && !isset($rec->isContable)) {
                $rec = $mvc->fetch($rec->id);
            }
            
            if (!$rec->isContable) {
                $requiredRoles = 'no_one';
            }
        } elseif ($action == 'reject') {
            if ($rec->id && !isset($rec->state)) {
                $rec = $mvc->fetch($rec->id);
            }
            
            if ($rec->state != 'active') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     *  Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->reason = Ht::createLink($mvc->getVerbal($rec, 'reason'), array($this, 'single', $rec->id));
        $row->totalAmount = '<strong>' . $row->totalAmount . '</strong>';
        
        if ($rec->state != 'rejected') {
            $row->rejectedOn = $row->rejectedBy = NULL;
        }
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function renderSingleLayout_($data)
    {
        if( count($this->details) ) {
            foreach($this->details as $var => $className) {
                $detailsTpl .= "[#Detail{$var}#]";
            }
        }
        
        $fieldsHtml = "";
        
        $fieldsHtml .=
        "<tr><td class=\"quiet\" align=\"right\">{$data->singleFields['id']}</td><td>[#id#]</td></tr>";
        $fieldsHtml .=
        "<tr><td class=\"quiet\" align=\"right\">{$data->singleFields['valior']}</td><td>[#valior#]</td></tr>";
        $fieldsHtml .=
        "<tr><td class=\"quiet\" align=\"right\">{$data->singleFields['reason']}</td><td>[#reason#]</td></tr>";
        $fieldsHtml .=
        "<tr><td class=\"quiet\" align=\"right\">Създадена</td><td><span class=\"quiet\">на</span>[#createdOn#] <span class=\"quiet\">от</span> [#createdBy#]</td></tr>";
        $fieldsHtml .=
        '<!--ET_BEGIN rejectedOn-->' .
        "<tr><td class=\"quiet\" align=\"right\">Оттеглена</td><td><span class=\"quiet\">на</span>[#rejectedOn#] <span class=\"quiet\">от</span> [#rejectedBy#]</td></tr>" .
        '<!--ET_END rejectedOn-->';
        
        $res = new ET(
        "[#SingleToolbar#]" .
        "<h2>[#SingleTitle#] ([#state#])</h2>" .
        '<table>' .
        '<tr>'.
        '<td valign="top" style="padding-right: 5em;">' .
        "<table>{$fieldsHtml}</table>".
        '</td>' .
        '<td valign="top">' .
        '<div class="amounts">' .
        'Оборот: [#totalAmount#]' .
        '</div>' .
        '</td>' .
        '</tr>' .
        '</table>' .
        "{$detailsTpl}" .
        ''
        );
        
        return $res;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function detailsChanged_() { }
    
    
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
        
        $result = null;
        
        if ($r = $query->fetch("#articleId = {$id}")) {
        	$rec = (object) array(
        		'id'          => $r->articleId,
        		'totalAmount' => $r->sumAmount
        	);
        	
        	$result = self::save($rec);
        }
        
        return $result;
    }
    
    
    /**
     *  @todo Чака за документация...
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
                'quantity' => $entry->quantity,
                'price' => $entry->price,
                'amount' => $entry->amount,
            	'debitAccId' => $entry->debitAccId,
            	'debitEnt1' => $entry->debitEnt1,
            	'debitEnt2' => $entry->debitEnt2,
            	'debitEnt3' => $entry->debitEnt3,
            	'creditAccId' => $entry->creditAccId,
            	'creditEnt1' => $entry->creditEnt1,
            	'creditEnt2' => $entry->creditEnt2,
            	'creditEnt3' => $entry->creditEnt3,
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
     * 	Имплементация на интерфейса `acc_TransactionSourceIntf`
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
        expect($rec); // @todo да връща грешка
        
        $result = (object)array(
            'reason'      => $rec->reason,
            'valior'      => $rec->valior,
            'totalAmount' => $rec->totalAmount,
        	'entries'     => array()
        );
        
        // Извличаме детайл-записите на документа. В случая просто копираме полетата, тъй-като
        // детайл-записите на мемориалните ордери имат същата структура, каквато е и на 
        // детайлите на журнала.
        $query = acc_ArticleDetails::getQuery();
        
        while ($entry = $query->fetch("#articleId = {$id}")) {
            $result->entries[] = (object)array(
                'quantity'    => $entry->quantity,
                'price'       => $entry->price,
                'amount'      => $entry->amount,
            	'debitAccId'  => $entry->debitAccId,
            	'debitEnt1'   => $entry->debitEnt1,
            	'debitEnt2'   => $entry->debitEnt2,
            	'debitEnt3'   => $entry->debitEnt3,
            	'creditAccId' => $entry->creditAccId,
            	'creditEnt1'  => $entry->creditEnt1,
            	'creditEnt2'  => $entry->creditEnt2,
            	'creditEnt3'  => $entry->creditEnt3,
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
        $rec = self::fetch($id, 'id,state');
        
        if ($rec) {
	        if ($rec->state == 'draft') {
	            // Записа не е контиран
	            return self::delete($id);
	        } else {
		        $rec->state = 'rejected';
		        self::save($rec);
	        }
        }
    }
}