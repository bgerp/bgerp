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
        $this->FLD('debitItem1', 'key(mvc=acc_Items,select=titleLink)', 'caption=Дебит->перо 1');
        $this->FLD('debitItem2', 'key(mvc=acc_Items,select=titleLink)', 'caption=Дебит->перо 2');
        $this->FLD('debitItem3', 'key(mvc=acc_Items,select=titleLink)', 'caption=Дебит->перо 3');
        $this->FLD('debitQuantity', 'double', 'caption=Дебит->К-во');
        $this->FLD('debitPrice', 'double(minDecimals=2)', 'caption=Дебит->Цена');
        $this->FLD('creditItem1', 'key(mvc=acc_Items,select=titleLink)', 'caption=Кредит->перо 1');
        $this->FLD('creditItem2', 'key(mvc=acc_Items,select=titleLink)', 'caption=Кредит->перо 2');
        $this->FLD('creditItem3', 'key(mvc=acc_Items,select=titleLink)', 'caption=Кредит->перо 3');
        $this->FLD('creditQuantity', 'double', 'caption=Кредит->К-во');
        $this->FLD('creditPrice', 'double(minDecimals=2)', 'caption=Кредит->Цена');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Обороти->Сума');
    }
    
    public static function on_BeforeSave(acc_JournalDetails $mvc, &$id, $rec, $fields = NULL, $mode = NULL)
    {
        // Гарантира съществуването на всички пера, указани в реда
        if (!$mvc::forceItems($rec)) {
            return FALSE;
        }
    }
    
    
    public static function forceItems($entry, $type = NULL)
    {
        if (!isset($type)) {
            return static::forceItems($entry, 'debit')
                && static::forceItems($entry, 'credit');
        }
        
        foreach (range(1, 3) as $i) {
            $item = $entry->{"{$type}Item{$i}"};
            
            if (!isset($item)) {
                continue;
            }
            
            expect($item->cls && $item->id && $item->listId, $item);
            
            if (!$itemRec = acc_Lists::updateItem($item->cls, $item->id, $item->listId, TRUE)) {
                return FALSE;
            }
            
            $entry->{"{$type}Item{$i}"} = $itemRec->id;
        }
        
        return TRUE;
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
}