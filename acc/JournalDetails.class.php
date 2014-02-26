<?php



/**
 * Мениджър Журнал детайли
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
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
    var $loadList = 'plg_Created, acc_Wrapper, plg_RowNumbering, Accounts=acc_Accounts, plg_AlignDecimals';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'debitAccId, debitQuantity, debitPrice, creditAccId, creditQuantity, creditPrice, amount';


    /**
     * @var acc_Accounts
     */
    var $Accounts;
    
    
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
        $this->FLD('debitQuantity', 'double', 'caption=Дебит->К-во');
        $this->FLD('debitPrice', 'double(minDecimals=2)', 'caption=Дебит->Цена');
        
        // Кредитна аналитична сметка
        $this->FLD('creditAccId', 'key(mvc=acc_Accounts,select=title)',
            'silent,caption=Кредит->Сметка и пера,mandatory,input=hidden');
        $this->FLD('creditItem1', 'key(mvc=acc_Items,select=titleLink)', 'caption=Кредит->Перо 1');
        $this->FLD('creditItem2', 'key(mvc=acc_Items,select=titleLink)', 'caption=Кредит->Перо 2');
        $this->FLD('creditItem3', 'key(mvc=acc_Items,select=titleLink)', 'caption=Кредит->Перо 3');
        $this->FLD('creditQuantity', 'double', 'caption=Кредит->К-во');
        $this->FLD('creditPrice', 'double(minDecimals=2)', 'caption=Кредит->Цена');

        // Обща сума на транзакцията
        $this->FLD('amount', 'double(decimals=2)', 'caption=Сума');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterPrepareListRows($mvc, &$res)
    {
        $rows = &$res->rows;
        $recs = &$res->recs;
        
        $Lists = &cls::get('acc_Lists');
        $Accounts = &cls::get('acc_Accounts');
        
        if (count($recs)) {
            foreach ($recs as $id=>$rec) {
                $row = &$rows[$id];
                
                foreach (array('debit', 'credit') as $type) {
                    $ents = "";
                    $accRec = $Accounts->fetch($rec->{"{$type}AccId"});
                    
                    foreach (range(1, 3) as $i) {
                        $ent = "{$type}Item{$i}";
                        
                        if ($rec->{$ent}) {
                            $row->{$ent} = $mvc->recToVerbal($rec, $ent)->{$ent};
                            $listGroupTitle = $Lists->fetchField($accRec->{"groupId{$i}"}, 'name');
                            
                            $ents .= '<li>' . $row->{$ent} . '</li>';
                        }
                    }
                    
                    $row->{"{$type}AccId"} = $accRec->num . '.&nbsp;' . $accRec->title;
                    
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
     * @param mixed $items1     - списък с пера, от които поне един може да е на първа позиция
     * @param mixed $items2     - списък с пера, от които поне един може да е на втора позиция
     * @param mixed $items3     - списък с пера, от които поне един може да е на трета позиция
     */
	public static function filterQuery(core_Query &$query, $from, $to, $accs = NULL, $items1 = NULL, $items2 = NULL, $items3 = NULL)
    {
    	expect($query->mvc instanceof acc_JournalDetails);
    	
    	$query->EXT('valior', 'acc_Journal', 'externalKey=journalId');
        $query->EXT('state', 'acc_Journal', 'externalKey=journalId');
        $query->EXT('docType', 'acc_Journal', 'externalKey=journalId');
        $query->EXT('docId', 'acc_Journal', 'externalKey=journalId');
        $query->EXT('reason', 'acc_Journal', 'externalKey=journalId');
        $query->EXT('jid', 'acc_Journal', 'externalName=id');
        $query->where("#state = 'active'");
        $query->where("#valior BETWEEN '{$from}' AND '{$to}'");
        
        $query->orderBy('valior,id', 'ASC');
    	
    	// Трябва да има поне една зададена сметка
    	$accounts = arr::make($accs);
    	
    	if(count($accounts) >= 1){
	    	foreach ($accounts as $sysId){
	    		$acc = acc_Accounts::getRecBySystemId($sysId);
		    	$query->where("#debitAccId = {$acc->id}");
		    	$query->orWhere("#creditAccId = {$acc->id}");
		    }
    	}
    	
    	// Проверка на останалите параметри от 1 до 3
    	foreach (range(1, 3) as $i){
    		$var = ${"items{$i}"};
    		
    		// Ако е NULL продалжаваме
    		if(!$var) continue;
    		$varArr = arr::make($var);
    		
    		// За перата се изисква поне едно от тях да е на текущата позиция
    		foreach($varArr as $itemId){
    			$query->where("#debitItem{$i} = {$itemId}");
    			$query->orWhere("#creditItem{$i} = {$itemId}");
    		}
    	}
    }
}