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
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools, acc_Wrapper,
        Accounts=acc_Accounts
    ';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, debitAccId, creditAccId, quantity=Обороти->Кол., price, amount';
    
    
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
        'silent,caption=Дебит,mandatory,input=hidden');
        $this->FLD('creditAccId', 'key(mvc=acc_Accounts,select=title,remember)',
        'silent,caption=Кредит,mandatory,input=hidden');
        $this->FLD('debitEnt1', 'key(mvc=acc_Items,select=titleLink)', 'caption=Дебит->перо 1');
        $this->FLD('debitEnt2', 'key(mvc=acc_Items,select=titleLink)', 'caption=Дебит->перо 2');
        $this->FLD('debitEnt3', 'key(mvc=acc_Items,select=titleLink)', 'caption=Дебит->перо 3');
        $this->FLD('creditEnt1', 'key(mvc=acc_Items,select=titleLink)', 'caption=Кредит->перо 1');
        $this->FLD('creditEnt2', 'key(mvc=acc_Items,select=titleLink)', 'caption=Кредит->перо 2');
        $this->FLD('creditEnt3', 'key(mvc=acc_Items,select=titleLink)', 'caption=Кредит->перо 3');
        $this->FLD('quantity', 'double', 'caption=Обороти->Количество');
        $this->FLD('price', 'double', 'caption=Обороти->Цена');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Обороти->Сума');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_AfterPrepareListRecs($mvc, &$res)
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
                            
                            $ents .=
                            '<tr>' .
                            '<td class="quiet">' . $listGroupTitle .':</td>' .
                            '<td>' . $row->{$ent} . '</td>' .
                            '</tr>';
                        }
                    }
                    
                    if (!empty($ents)) {
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