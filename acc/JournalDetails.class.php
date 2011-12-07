<?php

/**
 * Мениджър Журнал детайли
 */
class acc_JournalDetails extends core_Detail
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Журнал детайли";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $masterKey = 'journalId';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, acc_Wrapper, plg_RowNumbering,
        Accounts=acc_Accounts, plg_AlignDecimals
    ';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'debitAccId, debitQuantity, debitPrice, creditAccId, creditQuantity, creditPrice, amount=Сума';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $currentTab = 'acc_Journal';
    
    
    /**
     * @var acc_Accounts
     */
    var $Accounts;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('journalId', 'key(mvc=acc_Journal)', 'column=none,input=hidden,silent');
        $this->FLD('debitAccId', 'key(mvc=acc_Accounts,select=title,remember)',
        'silent,caption=Дебит->Сметка и пера,mandatory,input=hidden');
        $this->FLD('creditAccId', 'key(mvc=acc_Accounts,select=title,remember)',
        'silent,caption=Кредит->Сметка и пера,mandatory,input=hidden');
        $this->FLD('debitEnt1', 'key(mvc=acc_Items,select=titleLink)', 'caption=Дебит->перо 1');
        $this->FLD('debitEnt2', 'key(mvc=acc_Items,select=titleLink)', 'caption=Дебит->перо 2');
        $this->FLD('debitEnt3', 'key(mvc=acc_Items,select=titleLink)', 'caption=Дебит->перо 3');
        $this->FLD('debitQuantity', 'double', 'caption=Дебит->К-во');
        $this->FLD('debitPrice', 'double(minDecimals=2)', 'caption=Дебит->Цена');
        $this->FLD('creditEnt1', 'key(mvc=acc_Items,select=titleLink)', 'caption=Кредит->перо 1');
        $this->FLD('creditEnt2', 'key(mvc=acc_Items,select=titleLink)', 'caption=Кредит->перо 2');
        $this->FLD('creditEnt3', 'key(mvc=acc_Items,select=titleLink)', 'caption=Кредит->перо 3');
        $this->FLD('creditQuantity', 'double', 'caption=Кредит->К-во');
        $this->FLD('creditPrice', 'double(minDecimals=2)', 'caption=Кредит->Цена');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Обороти->Сума');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_AfterPrepareListRows($mvc, &$res)
    {
        $rows = &$res->rows;
        $recs = &$res->recs;
        
        $Lists = &cls::get('acc_Lists');
        $Accounts = &cls::get('acc_Accounts');
        
        if (count($recs)) {
            foreach ($recs as $id=>$rec) {
                $row = &$rows[$id];
                
                foreach (array('debit','credit') as $type) {
                    $ents = "";
                    $accRec = $Accounts->fetch($rec->{"{$type}AccId"});
                    
                    foreach (range(1,3) as $i) {
                        $ent = "{$type}Ent{$i}";
                        
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
}