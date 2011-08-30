<?php

/**
 * Мениджър на детайли на Мемориален ордер
 */
class acc_ArticleDetails extends core_Detail
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Мемориален ордер";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $masterKey = 'articleId';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools, acc_Wrapper,
        Accounts=acc_Accounts, Lists=acc_Lists, Items=acc_Items
    ';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, debitAccId, creditAccId, quantity=Обороти->Кол., price, amount, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     *  @todo Чака за документация...
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
        	'silent,caption=Дебит->Сметка,mandatory,input');
        $this->FLD('debitEnt1', 'acc_type_Item(select=numTitleLink)', 'caption=Дебит->перо 1');
        $this->FLD('debitEnt2', 'acc_type_Item(select=numTitleLink)', 'caption=Дебит->перо 2');
        $this->FLD('debitEnt3', 'acc_type_Item(select=numTitleLink)', 'caption=Дебит->перо 3');
        
        $this->FLD('creditAccId', 'acc_type_Account(remember)',
        	'silent,caption=Кредит->Сметка,mandatory,input');
        $this->FLD('creditEnt1', 'acc_type_Item(select=numTitleLink)', 'caption=Кредит->перо 1');
        $this->FLD('creditEnt2', 'acc_type_Item(select=numTitleLink)', 'caption=Кредит->перо 2');
        $this->FLD('creditEnt3', 'acc_type_Item(select=numTitleLink)', 'caption=Кредит->перо 3');
        
        $this->FLD('quantity', 'double', 'caption=Обороти->Количество');
        $this->FLD('price', 'double', 'caption=Обороти->Цена');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Обороти->Сума');
    }
    
    
    /**
     *
     */
    function on_AfterPrepareListRecs($mvc, &$res)
    {
        $rows = &$res->rows;
        $recs = &$res->recs;
        
        if (count($recs)) {
            foreach ($recs as $id=>$rec) {
                $row = &$rows[$id];
                
                foreach (array('debit','credit') as $type) {
                    $ents = "";
                    $accRec = acc_Accounts::fetch($rec->{"{$type}AccId"});
                    
                    foreach (range(1,3) as $i) {
                        $ent = "{$type}Ent{$i}";
                        
                        if ($rec->{$ent}) {
                            $row->{$ent} = $mvc->recToVerbal($rec, $ent)->{$ent};
                            $listGroupTitle = acc_Lists::fetchField($accRec->{"groupId{$i}"}, 'name');
                            
                            $ents .= '<li>' . $row->{$ent} . '</li>';
                        }
                    }
                    
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
     *
     */
    function on_AfterPrepareListToolbar($mvc, $data)
    {
        if (!$mvc->Master->haveRightFor('edit', $data->masterData->rec)) {
            return;
        }
        
        expect($data->masterId);
        $form = cls::get('core_Form');
        
        $form->method = 'GET';
        $form->action = array (
            $this, 'add',
        );
        $form->view = 'horizontal';
        $form->FLD('debitAccId', 'acc_type_Account(allowEmpty)',
        	'silent,caption=Дебит,mandatory,width=300px');
        $form->FLD('creditAccId', 'acc_type_Account(allowEmpty)',
        	'silent,caption=Кредит,mandatory,width=300px');
        $form->FLD('articleId', 'int', 'input=hidden,value='.$data->masterId);
        $form->FLD('ret_url', 'varchar', 'input=hidden,value=' .toUrl(getCurrentUrl(), 'local'));
        
        $form->title = 'Нов запис в журнала';
        
        $form->toolbar->addSbBtn('Нов', '', '', "id=btnAdd,class=btn-add");
        
        $data->accSelectToolbar = $form;
    }
    
    
    /**
     *
     */
    function on_AfterRenderListToolbar($mvc, $tpl, $data)
    {
        if ($data->accSelectToolbar) {
            $tpl = $data->accSelectToolbar->renderHtml();
        }
    }
    
    
    /**
     * @param acc_ArticleDetails $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;
        
        $Lists = &cls::get('acc_Lists');
        $Items = &cls::get('acc_Items');
        
        $dimensional = FALSE;
        $quantityOnly = FALSE;
        
        $form->setReadOnly('debitAccId');
        $form->setReadOnly('creditAccId');
        
        foreach (array('debit' => 'Дебит', 'credit' => 'Кредит') as $type => $caption) {
            
            $accId = "{$type}AccId";
            
            $acc = $this->getAccountInfo($form->rec->{$accId});
            $accRec = $acc->rec;
            
            expect($accRec);
            
            $quantityOnly = $quantityOnly || ($accRec->type && $accRec->strategy);
            
            $form->setField("{$type}Ent1", 'input=none');
            $form->setField("{$type}Ent2", 'input=none');
            $form->setField("{$type}Ent3", 'input=none');
            
            foreach ($acc->groups as $i=>$list) {
            	if (!$list->rec->itemsCnt) {
            		redirect(array('acc_Items', 'list', 'listId'=>$list->rec->id), FALSE, tr("Липсва избор за |* \"{$list->rec->name}\"") );
            	}
            	$form->getField("{$type}Ent{$i}")->type->params['listNum'] = $list->rec->num;
            	$form->setField("{$type}Ent{$i}", 'mandatory,input,caption=' . $list->rec->name); 
            }
        }
        
        if (!$dimensional) {
            $form->setField('quantity,price', 'input=none');
        }
        
        if ($quantityOnly) {
            $form->setField('amount,price', 'input=none');
            $form->setField('quantity', 'mandatory');
        }
    }
    
    
    /**
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        if (!$form->isSubmitted()){
            return;
        }
        
        $rec = $form->rec;
        
        $dimensional = FALSE;
        $quantityOnly = FALSE;
        
        foreach (array('debit', 'credit') as $type) {
            $accField = "{$type}AccId";
            expect($rec->{$accField});
            
            $acc = $this->getAccountInfo($rec->{$accField});
            
            $quantityOnly = $quantityOnly || ($accRec->type && $accRec->strategy);
            
            if (empty($acc->groups)) {
                continue;
            }
            
            foreach ($acc->groups as $group) {
                if ($dimensional = acc_Lists::isDimensional($group->rec->id)) {
                    break;
                }
            }
            
            if ($dimensional && $quantityOnly) {
                break;
            }
        }
        
        if ($dimensional || $quantityOnly) {
            if (!$quantityOnly) {
                $nEmpty = (int)empty($rec->quantity) +
                (int)empty($rec->price) +
                (int)empty($rec->amount);
                
                if ($nEmpty > 1) {
                    $form->setError('quantity, price, amount', 'Поне два от оборотите трябва да бъдат попълнени');
                } else {
                    switch (true) {
                        case empty($rec->quantity):
                        $rec->quantity = $rec->amount / $rec->price;
                        break;
                        case empty($rec->price):
                        $rec->price = $rec->amount / $rec->quantity;
                        break;
                        case empty($rec->amount):
                        $rec->amount = $rec->price * $rec->quantity;
                        break;
                    }
                }
                
                if ($rec->amount != $rec->price * $rec->quantity) {
                    $form->setError('quantity, price, amount', 'Невъзможни стойности на оборотите');
                }
            }
        } elseif (empty($rec->amount)) {
            $form->setError('amount', 'Полето "Сума" трябва да бъде попълнено');
        }
    }
    
    
    /**
     *
     */
    private function getAccountInfo($accountId)
    {
        $acc = (object)array(
            'rec' => acc_Accounts::fetch($accountId),
    	    'groups' => array()
        );
        
        foreach (range(1,3) as $i) {
            $listPart = "groupId{$i}";
            
            if (!empty($acc->rec->{$listPart})) {
                $listId = $acc->rec->{$listPart};
                $acc->groups[$i]->rec = acc_Lists::fetch($listId);
            }
        }
        
        return $acc;
    }
    
    
    /**
     *
     */
    function on_AfterSave($mvc, &$id, &$rec)
    {
        $mvc->Master->detailsChanged($rec->{$mvc->masterKey}, $mvc, $rec);
    }
    
    
    /**
     *
     */
    function on_BeforeDelete($mvc, &$res, &$query, $cond)
    {
        $_query = clone($query);
        $mvc->notifyMasterIds = array();
        
        while ($rec = $_query->fetch($cond)) {
            $query->notifyMasterIds[$rec->{$mvc->masterKey}] = true;
        }
    }
    
    
    /**
     *
     */
    function on_AfterDelete($mvc, $res, $query)
    {
        foreach ($query->notifyMasterIds as $masterId=>$_) {
            $mvc->Master->detailsChanged($masterId, $mvc);
        }
    }
}