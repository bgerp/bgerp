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
    var $loadList = 'plg_Created, acc_Wrapper, plg_RowNumbering,
        Accounts=acc_Accounts, plg_AlignDecimals
    ';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'debitAccId, debitQuantity, debitPrice, creditAccId, creditQuantity, creditPrice, amount=Сума';


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