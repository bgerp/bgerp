<?php



/**
 * Мениджър Журнал детайли
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_JournalDetails extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    var $title = "Журнал детайли";
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'journalId';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, acc_Wrapper, plg_RowNumbering, plg_StyleNumbers, Accounts=acc_Accounts, plg_AlignDecimals2';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'debitAccId, debitQuantity, debitPrice, creditAccId, creditQuantity, creditPrice, amount, reasonCode';
    
    
    /**
     * Кои полета да се извличат при изтриване
     */
    var $fetchFieldsBeforeDelete = 'debitItem1,debitItem2,debitItem3,creditItem1,creditItem2,creditItem3';
    
    
    /**
     *  Брой теми на страница
     */
    var $listItemsPerPage = "40";
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'reasonCode';
    
    
    /**
     * Работен кеш
     */
    protected static $baseCurrencyItems = array();
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        // Ключ към матера
        $this->FLD('journalId', 'key(mvc=acc_Journal)', 'column=none,input=hidden,silent');
        
        // Дебитна аналитична сметка
        $this->FLD('debitAccId', 'key(mvc=acc_Accounts,select=title)',
            'silent,caption=Дебит->Сметка и пера,mandatory,input=hidden');
        $this->FLD('debitItem1', 'key(mvc=acc_Items,select=titleLink)', 'caption=Дебит->Перо 1');
        $this->FLD('debitItem2', 'key(mvc=acc_Items,select=titleLink)', 'caption=Дебит->Перо 2');
        $this->FLD('debitItem3', 'key(mvc=acc_Items,select=titleLink)', 'caption=Дебит->Перо 3');
        $this->FLD('debitQuantity', 'double(minDecimals=0)', 'caption=Дебит->К-во');
        $this->FLD('debitPrice', 'double(decimals=5)', 'caption=Дебит->Цена');
        
        // Кредитна аналитична сметка
        $this->FLD('creditAccId', 'key(mvc=acc_Accounts,select=title)',
            'silent,caption=Кредит->Сметка и пера,mandatory,input=hidden');
        $this->FLD('creditItem1', 'key(mvc=acc_Items,select=titleLink)', 'caption=Кредит->Перо 1');
        $this->FLD('creditItem2', 'key(mvc=acc_Items,select=titleLink)', 'caption=Кредит->Перо 2');
        $this->FLD('creditItem3', 'key(mvc=acc_Items,select=titleLink)', 'caption=Кредит->Перо 3');
        $this->FLD('creditQuantity', 'double(minDecimals=0)', 'caption=Кредит->К-во');
        $this->FLD('creditPrice', 'double(decimals=5)', 'caption=Кредит->Цена');
        
        // Обща сума на транзакцията
        $this->FLD('reasonCode', 'key(mvc=acc_Operations,select=title)', 'input=none,caption=Операция');
        $this->FLD('amount', 'double(minDecimals=2)', 'caption=Сума');

        // Поставяне на индекси
        $this->setDbIndex('debitAccId, creditAccId');
        $this->setDbIndex('debitAccId');
        $this->setDbIndex('creditAccId');
        $this->setDbIndex('debitItem1');
        $this->setDbIndex('debitItem2');
        $this->setDbIndex('debitItem3');
        $this->setDbIndex('creditItem1');
        $this->setDbIndex('creditItem2');
        $this->setDbIndex('creditItem3');
    }
    
    
    /**
     * След подготовката на филтър формата
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy("#id", 'ASC');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $res - данни
     */
    static function on_AfterPrepareListRows($mvc, &$res)
    {
        $rows = &$res->rows;
        $recs = &$res->recs;
        
        if (count($recs)) {
            foreach ($recs as $id => $rec) {
                $row = &$rows[$id];
                
                foreach (array('debit', 'credit') as $type) {
                    $ents = "";
                    $accRec = acc_Accounts::fetch($rec->{"{$type}AccId"});
                    
                    foreach (range(1, 3) as $i) {
                        $ent = "{$type}Item{$i}";
                        
                        if ($rec->{$ent}) {
                            $row->{$ent} = $mvc->recToVerbal($rec, $ent)->{$ent};
                            $ents .= "<li><span style='margin-left:10px; font-size: 11px; color: #747474;'>{$i}.</span> " . $row->{$ent} . '</li>';
                        }
                    }
                    
                    if (!empty($ents)) {
                        $row->{"{$type}AccId"} .=
                        '<ul style="font-size: 0.8em; list-style: none; margin: 0.2em 0; padding-left: 1em;">' .
                        $ents .
                        '</ul>';
                    }
                    
                    if (!empty($ents1)) {
                        $row->{"{$type}AccId"} = $accRec->num . '.&nbsp;' . $accRec->title .
                        '<table style="font-size: 0.8em; border-collapse: collapse;">' .
                        $ents .
                        '</table>';
                    }
                }
            }
        }
    }
    
    
    /**
     * Филтрира заявка към модела за показване на определени данни
     *
     * @param core_Query $query - Заявка към модела
     * @param mixed $accs       - списък от систем ид-та на сметките
     * @param mixed $itemsAll   - списък от пера, за които може да са на произволна позиция
     * @param mixed $items1     - списък с пера, от които поне един може да е на първа позиция
     * @param mixed $items2     - списък с пера, от които поне един може да е на втора позиция
     * @param mixed $items3     - списък с пера, от които поне един може да е на трета позиция
     * @param boolean $strict   - ако перата са NULL да се търсят записи в журнала със стойност NULL,
     * иначе приема, че не трябва да се търсят пера
     */
    public static function filterQuery(core_Query &$query, $from, $to, $accs = NULL, $itemsAll = NULL, $items1 = NULL, $items2 = NULL, $items3 = NULL, $strict = FALSE)
    {
        expect($query->mvc instanceof acc_JournalDetails);
        
        $query->EXT('valior', 'acc_Journal', 'externalKey=journalId');
        $query->EXT('state', 'acc_Journal', 'externalKey=journalId');
        $query->EXT('docType', 'acc_Journal', 'externalKey=journalId');
        $query->EXT('docId', 'acc_Journal', 'externalKey=journalId');
        $query->EXT('reason', 'acc_Journal', 'externalKey=journalId');
        $query->EXT('jid', 'acc_Journal', 'externalName=id');
        $query->where("#state = 'active'");
        
        // Ако имаме зададена начална или крайна дата филтрираме по тях
        if(isset($from) || isset($to)){
        	$query->where("#valior BETWEEN '{$from}' AND '{$to}'");
        }
       
        // Трябва да има поне една зададена сметка
        $accounts = arr::make($accs);
        $itemsAll = arr::make($itemsAll);
        
        // Ако само се филтрират по сметки UNION по сметките, за бързодействие
        if(count($accounts) && !count($itemsAll) && empty($items1) && empty($items2) && empty($items3)){
        	
        	// Прави се UNION на сметките
        	foreach ($accounts as $sysId){
        		$accId = acc_Accounts::getRecBySystemId($sysId)->id;
        		$query->setUnion("#debitAccId = {$accId}");
        		$query->setUnion("#creditAccId = {$accId}");
        	}
        	
        	return;
        }
        
        // Ако само се търсят пера на всяка позиция, UNION за бързодействие
        if(count($itemsAll) && !count($accounts) && empty($items1) && empty($items2) && empty($items3)){
        	foreach ($itemsAll as $itemId){
        		
        		// Ако няма сметки се прави обикновен юнион на всичките варианти, на които може да е перото
        		$query->setUnion("#debitItem1 = {$itemId}");
        		$query->setUnion("#debitItem2 = {$itemId}");
        		$query->setUnion("#debitItem3 = {$itemId}");
        		$query->setUnion("#creditItem1 = {$itemId}");
        		$query->setUnion("#creditItem2 = {$itemId}");
        		$query->setUnion("#creditItem3 = {$itemId}");
        	}
        	
        	return;
        }
        
        if(count($accounts) >= 1){
            foreach ($accounts as $sysId){
                $acc = acc_Accounts::getRecBySystemId($sysId);
                $query->where("#debitAccId = {$acc->id}");
                $query->orWhere("#creditAccId = {$acc->id}");
            }
        }
        
        // Перата които може да са на произволна позиция
        $itemsAll = arr::make($itemsAll);
        
        if(count($itemsAll)){
            foreach ($itemsAll as $itemId){
                
                // Трябва да инт число
                expect(ctype_digit((string)$itemId));
                
                // .. и перото да участва на произволна позиция
                $query->where("#debitItem1 = {$itemId}");
                $query->orWhere("#debitItem2 = {$itemId}");
                $query->orWhere("#debitItem3 = {$itemId}");
                $query->orWhere("#creditItem1 = {$itemId}");
                $query->orWhere("#creditItem2 = {$itemId}");
                $query->orWhere("#creditItem3 = {$itemId}");
            }
        }
        
        // Проверка на останалите параметри от 1 до 3
        foreach (range(1, 3) as $i){
            $var = ${"items{$i}"};
            
            if(!$var){
                if($strict){
                    
                    // Ако търсенето е стриктно и стойността на перото е NULL се търси за запис с NULl
                    $query->where("#debitItem{$i} IS NULL");
                    $query->orWhere("#creditItem{$i} IS NULL");
                }
                continue;
            }
            
            $varArr = arr::make($var);
            
            // За перата се изисква поне едно от тях да е на текущата позиция
            foreach($varArr as $itemId){
                $query->where("#debitItem{$i} = {$itemId}");
                $query->orWhere("#creditItem{$i} = {$itemId}");
            }
        }
    }
    
    
    /**
     * Преди изтриване, се запомнят ид-та на перата
     */
    public static function on_AfterDelete($mvc, &$numRows, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $rec) {
            foreach (array('debitItem1', 'debitItem2', 'debitItem3', 'creditItem1', 'creditItem2', 'creditItem3') as $item){
                if(isset($rec->{$item})){
                    $mvc->Master->affectedItems[$rec->{$item}] = $rec->{$item};
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        // В кой баланс е влязъл записа
        $valior = $mvc->Master->fetchField($rec->journalId, 'valior');
        $balanceValior = acc_Balances::fetch("#fromDate <= '{$valior}' AND '{$valior}' <= #toDate");
        
        // Линкове към сметките в баланса
        $row->debitAccId = acc_Balances::getAccountLink($rec->debitAccId, $balanceValior);
        $row->creditAccId = acc_Balances::getAccountLink($rec->creditAccId, $balanceValior);
        
        if($rec->reasonCode){
        	$row->reasonCode = "<div style='color:#444;font-size:0.9em;margin-left:10px'>{$row->reasonCode}</div>";
        }
    }
    
    
    /**
     * Коя е основната валута за посочения период
     * 
     * @param date $valior - вальор
     * @return int
     */
    public static function getBaseCurrencyItemId($valior)
    {
    	$periodRec = acc_Periods::fetchByDate($valior);
    	
    	if(!array_key_exists("{$periodRec->id}", self::$baseCurrencyItems)){
    		self::$baseCurrencyItems["{$periodRec->id}"] = acc_Items::fetchItem('currency_Currencies', acc_Periods::getBaseCurrencyId($valior))->id;
    	}
    	
    	return self::$baseCurrencyItems["{$periodRec->id}"];
    }
    
    
    /**
     * Записва редът (записа) в таблицата
     */
    function save_(&$rec, $fields = NULL, $mode = NULL)
    {
    	if(empty($fields)){
    		
    		// Кое е перото на основната валута за периода
    		$valior = ($rec->valior) ? $rec->valior : acc_Journal::fetchField($rec->journalId, 'valior');
    		$baseCurrencyItemId = self::getBaseCurrencyItemId($valior);
    		 
    		$replaceAmount = FALSE;
    		
    		// Обикаляме дебита и кредита, гледа се имали перо на основната валута.
    		foreach (array('debit', 'credit') as $type){
    			foreach (range(3, 1) as $i){
    				$fld = $rec->{"{$type}Item{$i}"};
    				if(!empty($fld)){
    					
    					// Ако има перо на основната валута запомняме и количеството
    					if($fld === $baseCurrencyItemId){
    						$replaceAmount = $rec->{"{$type}Quantity"};
    						break;
    					}
    				}
    			}
    			
    			if($replaceAmount !== FALSE) break;
    		}
    		
    		// Ако е намерено к-во на основната валута
    		if($replaceAmount !== FALSE){
    			
    			// И то е различно от сумата на реда замества се
    			// Така се подсигуряваме че К-то и сумата на основната валута винаги ще са еднакви
    			if(trim($replaceAmount) != trim($rec->amount)){
    				$msg = "Replace amount '{$rec->amount}' with '{$replaceAmount}'";
    				$rec->amount = $replaceAmount;
    				acc_Journal::logDebug($msg, $rec->journalId);
    			}
    		}
    	}
    	
    	// Викане на ф-ята за запис от бащата на класа
    	$id = parent::save_($rec, $fields, $mode);
    	 
    	// Връщане на резултата от записа
    	return $id;
    }
}