<?php



/**
 * Мениджър на детайли на Мемориален ордер
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_ArticleDetails extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    var $title = "Мемориален ордер";
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'articleId';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, acc_Wrapper, plg_RowNumbering, plg_AlignDecimals,
        Accounts=acc_Accounts, Lists=acc_Lists, Items=acc_Items, plg_AlignDecimals, plg_SaveAndNew';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, debitAccId, debitQuantity=Дебит->К-во, debitPrice=Дебит->Цена, creditAccId, creditQuantity=Кредит->К-во, creditPrice=Кредит->Цена, amount=Сума';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * @todo Чака за документация...
     */
    var $currentTab = 'acc_Articles';
    
    
    /**
     * @var acc_Accounts
     */
    var $Accounts;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('articleId', 'key(mvc=acc_Articles)', 'column=none,input=hidden,silent');
        
        $this->FLD('debitAccId', 'acc_type_Account(remember)',
            'silent,caption=Дебит->Сметка и пера,mandatory,input');
        $this->FLD('debitEnt1', 'acc_type_Item(select=numTitleLink)', 'caption=Дебит->перо 1');
        $this->FLD('debitEnt2', 'acc_type_Item(select=numTitleLink)', 'caption=Дебит->перо 2');
        $this->FLD('debitEnt3', 'acc_type_Item(select=numTitleLink)', 'caption=Дебит->перо 3');
        $this->FLD('debitQuantity', 'double', 'width=120px,caption=Дебит->Количество');
        $this->FLD('debitPrice', 'double(minDecimals=2)', 'caption=Дебит->Цена');
        
        $this->FLD('creditAccId', 'acc_type_Account(remember)',
            'silent,caption=Кредит->Сметка и пера,mandatory,input');
        $this->FLD('creditEnt1', 'acc_type_Item(select=numTitleLink)', 'caption=Кредит->перо 1');
        $this->FLD('creditEnt2', 'acc_type_Item(select=numTitleLink)', 'caption=Кредит->перо 2');
        $this->FLD('creditEnt3', 'acc_type_Item(select=numTitleLink)', 'caption=Кредит->перо 3');
        $this->FLD('creditQuantity', 'double', 'width=120px,caption=Кредит->Количество');
        $this->FLD('creditPrice', 'double(minDecimals=2)', 'caption=Кредит->Цена');
        
        //        $this->FLD('quantity', 'double', 'caption=Обороти->Количество');
        //        $this->FLD('price', 'double(minDecimals=2)', 'caption=Обороти->Цена');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Оборот->Сума');
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
        
        if (count($recs)) {
            foreach ($recs as $id=>$rec) {
                $row = &$rows[$id];
                
                foreach (array('debit', 'credit') as $type) {
                    $ents = "";
                    $accRec = acc_Accounts::fetch($rec->{"{$type}AccId"});
                    
                    foreach (range(1, 3) as $i) {
                        $ent = "{$type}Ent{$i}";
                        
                        if ($rec->{$ent}) {
                            $row->{$ent} = $mvc->recToVerbal($rec, $ent)->{$ent};
                            $listGroupTitle = acc_Lists::fetchField($accRec->{"groupId{$i}"}, 'name');
                            
                            $ents .= '<li>' . $row->{$ent} . '</li>';
                        }
                    }
                    
                    $row->{"{$type}AccId"} = $accRec->num . '.&nbsp;' . acc_Accounts::getVerbal($accRec, 'title');
                    
                    if (!empty($ents)) {
                        $row->{"{$type}AccId"} .=
                        '<ul style="font-size: 0.8em; list-style: none; margin: 0.2em 0; padding-left: 1em;">' .
                        $ents .
                        '</ul>';
                    }
                }
            }
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (!$mvc->Master->haveRightFor('edit', $data->masterData->rec)) {
            
            $data->toolbar->removeBtn('btnAdd');
            
            return;
        }
        
        expect($data->masterId);
        $form = cls::get('core_Form');
        
        $form->method = 'GET';
        $form->action = array (
            $mvc, 'add',
        );
        $form->view = 'horizontal';
        $form->FLD('debitAccId', 'acc_type_Account(allowEmpty)',
            'silent,caption=Дебит,mandatory,width=300px');
        $form->FLD('creditAccId', 'acc_type_Account(allowEmpty)',
            'silent,caption=Кредит,mandatory,width=300px');
        
        $form->FLD('articleId', 'int', 'input=hidden');
        $form->setHidden('articleId', $data->masterId);
        
        $form->FLD('ret_url', 'varchar', 'input=hidden');
        $form->setHidden('ret_url', toUrl(getCurrentUrl(), 'local'));
        
        $form->title = 'Нов запис в журнала';
        
        $form->toolbar->addSbBtn('Нов', '', '', "id=btnAdd,class=btn-add");
        
        $data->accSelectToolbar = $form;
    }
    
    
    /**
     * Извиква се след рендиране на Toolbar-а
     */
    static function on_AfterRenderListToolbar($mvc, &$tpl, $data)
    {
        if ($data->accSelectToolbar) {
            $form = $data->accSelectToolbar->renderHtml();
            
            if($form) {
                $tpl = $form->getContent();
            }
        }
    }
    
    
    /**
     * @param acc_ArticleDetails $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        $dimensional = FALSE;
        $quantityOnly = FALSE;
        
        $form->setReadOnly('debitAccId');
        $form->setReadOnly('creditAccId');
        
        $form->setField('debitAccId', 'caption=Дебит->Сметка');
        $form->setField('creditAccId', 'caption=Кредит->Сметка');
        
        $debitAcc = $mvc->getAccountInfo($rec->debitAccId);
        $creditAcc = $mvc->getAccountInfo($rec->creditAccId);
        
        $dimensional = $debitAcc->isDimensional || $creditAcc->isDimensional;
        
        $quantityOnly = ($debitAcc->rec->type == 'passive' && $debitAcc->rec->strategy) ||
        ($creditAcc->rec->type == 'active' && $creditAcc->rec->strategy);
        
        foreach (array('debit' => 'Дебит', 'credit' => 'Кредит') as $type => $caption) {
            
            $acc = ${"{$type}Acc"};
            
            // Скриваме всички полета за пера, и после показваме само тези, за които съответната
            // (дебит или кредит) сметка има аналитичност.
            $form->setField("{$type}Ent1", 'input=none');
            $form->setField("{$type}Ent2", 'input=none');
            $form->setField("{$type}Ent3", 'input=none');
            
            foreach ($acc->groups as $i=>$list) {
                if (!$list->rec->itemsCnt) {
                    redirect(array('acc_Items', 'list', 'listId'=>$list->rec->id), FALSE, tr("Липсва избор за |* \"{$list->rec->name}\""));
                }
                $form->getField("{$type}Ent{$i}")->type->params['lists'] = $list->rec->num;
                $form->setField("{$type}Ent{$i}", "mandatory,input,caption={$caption}->" . $list->rec->name);
            }
            
            if (!$acc->isDimensional) {
                $form->setField("{$type}Quantity", 'input=none');
                $form->setField("{$type}Price", 'input=none');
            }
            
            if ($quantityOnly) {
                $form->setField("{$type}Price", 'input=none');
                $form->setField("{$type}Quantity", 'mandatory');
            }
        }
        
        if ($quantityOnly) {
            $form->setField('amount', 'input=none');
        }
        
        if (!$dimensional && !$quantityOnly) {
            $form->setField('amount', 'mandatory');
        }
    }
    
    
    /**
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
        if (!$form->isSubmitted()){
            return;
        }
        
        $rec = $form->rec;
        
        $accs = array(
            'debit' => $mvc->getAccountInfo($rec->debitAccId),
            'credit' => $mvc->getAccountInfo($rec->creditAccId),
        );
        
        $quantityOnly = ($accs['debit']->rec->type == 'passive' && $accs['debit']->rec->strategy) ||
        ($accs['credit']->rec->type == 'active' && $accs['credit']->rec->strategy);
        
        if ($quantityOnly) {
            
            /**
             * @TODO да се провери, че debitQuantity == creditQuantity в случай, че размерните
             * аналитичности на дебит и кредит сметките са едни и същи.
             */
        } else {
            foreach ($accs as $type=>$acc) {
                if ($acc->isDimensional) {
                    
                    /**
                     * @TODO За размерни сметки: проверка дали са въведени поне два от трите оборота.
                     * Изчисление на (евентуално) липсващия оборот.
                     */
                    $nEmpty = (int)empty($rec->{"{$type}Quantity"}) +
                    (int)empty($rec->{"{$type}Price"}) +
                    (int)empty($rec->amount);
                    
                    if ($nEmpty > 1) {
                        $form->setError("{$type}Quantity, {$type}Price, amount", 'Поне два от оборотите трябва да бъдат попълнени');
                    } else {
                        
                        /**
                         * Изчисление на {$type}Amount:
                         *
                         * За размерни сметки: {$type}Amount = {$type}Quantity & {$type}Price
                         * За безразмерни сметки: {$type}Amount = amount
                         *
                         */
                        switch (true) {
                            case empty($rec->{"{$type}Quantity"}) :
                            $rec->{"{$type}Quantity"} = $rec->amount / $rec->{"{$type}Price"};
                            break;
                            case empty($rec->{"{$type}Price"}) :
                            $rec->{"{$type}Price"} = $rec->amount / $rec->{"{$type}Quantity"};
                            break;
                            case empty($rec->amount) :
                            $rec->amount = $rec->{"{$type}Price"} * $rec->{"{$type}Quantity"};
                            break;
                        }
                        
                        $rec->{"{$type}Amount"} = $rec->amount;
                    }
                    
                    if ($rec->amount != $rec->{"{$type}Price"} * $rec->{"{$type}Quantity"}) {
                        $form->setError("{$type}Quantity, {$type}Price, amount", 'Невъзможни стойности на оборотите');
                    }
                } else {
                    $rec->{"{$type}Amount"} = $rec->amount;
                }
            }
            
            /**
             * Проверка дали debitAmount == debitAmount
             */
            if ($rec->debitAmount != $rec->creditAmount) {
                bp($rec);
                $form->setError('debitQuantity, debitPrice, creditQuantity, creditPrice, amount', 'Дебит и кредит страните са различни');
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    private function getAccountInfo($accountId)
    {
        $acc = (object)array(
            'rec' => acc_Accounts::fetch($accountId),
            'groups' => array(),
            'isDimensional' => false
        );
        
        // $acc->quantityOnly = ($acc->rec->type && $acc->rec->strategy);
        
        foreach (range(1, 3) as $i) {
            $listPart = "groupId{$i}";
            
            if (!empty($acc->rec->{$listPart})) {
                $listId = $acc->rec->{$listPart};
                $acc->groups[$i]->rec = acc_Lists::fetch($listId);
                $acc->isDimensional = acc_Lists::isDimensional($listId);
            }
        }
        
        return $acc;
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_AfterSave($mvc, $id, $rec)
    {
        $mvc->Master->detailsChanged($rec->{$mvc->masterKey}, $mvc, $rec);
    }
    
    
    /**
     * Преди изтриване на запис
     */
    static function on_BeforeDelete($mvc, &$res, &$query, $cond)
    {
        $_query = clone($query);
        $query->notifyMasterIds = array();
        
        while ($rec = $_query->fetch($cond)) {
            $query->notifyMasterIds[$rec->{$mvc->masterKey}] = TRUE;
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterDelete($mvc, &$res, $query, $cond)
    {
        foreach ($query->notifyMasterIds as $masterId=>$_) {
            $mvc->Master->detailsChanged($masterId, $mvc);
        }
    }
}
