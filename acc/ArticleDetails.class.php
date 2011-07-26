<?php

/**
 * Мениджър на детайли на счетоводна статия
 */
class acc_ArticleDetails extends core_Detail
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Записи в статия";
    
    
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
        $this->FLD('debitAccId', 'key(mvc=acc_Accounts,select=title,remember)',
        'silent,caption=Дебит,mandatory,input=hidden');
        $this->FLD('creditAccId', 'key(mvc=acc_Accounts,select=title,remember)',
        'silent,caption=Кредит,mandatory,input=hidden');
        $this->FLD('debitEnt1', 'key(mvc=acc_Items,select=numTitleLink)', 'caption=Дебит->перо 1');
        $this->FLD('debitEnt2', 'key(mvc=acc_Items,select=numTitleLink)', 'caption=Дебит->перо 2');
        $this->FLD('debitEnt3', 'key(mvc=acc_Items,select=numTitleLink)', 'caption=Дебит->перо 3');
        $this->FNC('debitEnt_', 'int', 'input=none');
        $this->FLD('creditEnt1', 'key(mvc=acc_Items,select=numTitleLink)', 'caption=Кредит->перо 1');
        $this->FLD('creditEnt2', 'key(mvc=acc_Items,select=numTitleLink)', 'caption=Кредит->перо 2');
        $this->FLD('creditEnt3', 'key(mvc=acc_Items,select=numTitleLink)', 'caption=Кредит->перо 3');
        $this->FNC('creditEnt_', 'enum', 'input=none');
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
        
        $Lists = &cls::get('acc_Lists');
        $Accounts = &cls::get('acc_Accounts');
        $Items = &cls::get('acc_Items');
        
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
        $form->FLD('debitAccId', 'key(mvc=acc_Accounts,select=title)',
        'silent,caption=Дебит,mandatory,width=300px');
        $form->FLD('creditAccId', 'key(mvc=acc_Accounts,select=title)',
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
        
        foreach (array('debit' => 'Дебит', 'credit' => 'Кредит') as $type => $caption) {
            
            $accId = "{$type}AccId";
            
            expect($form->rec->{$accId}, $form);
            
            $acc = $this->getAccountInfo($form->rec->{$accId});
            
            $quantityOnly = $quantityOnly || ($acc->rec->type && $acc->rec->strategy);
            
            $form->setField("{$type}Ent1", 'input=none');
            $form->setField("{$type}Ent2", 'input=none');
            $form->setField("{$type}Ent3", 'input=none');
            
            if (!empty($acc->groups)) {
                foreach ($acc->groups as $i=>$group) {
                    $entField = "{$type}Ent{$i}";
                    
                    $form->setField($entField, 'input,mandatory,caption=' . $caption . ': ' . $acc->rec->title . "->" . $group->rec->caption . "");
                    
                    if (!empty($group->options)) {
                        $form->setOptions($entField, $group->options);
                        
                        if ($form->cmd == 'refresh') {
                            $form->cmd = NULL; // За да не запише формата;
                        }
                        // променяме ид-то на перото да включва и "@ид–на-списък", за да бъде
                        // коректно селектирано във формата.
                        foreach (array_keys($group->options) as $extId) {
                            if (strpos($extId, $form->rec->{$entField} . '@') === 0) {
                                $form->rec->{$entField} = $extId;
                                break;
                            }
                        }
                    } else {
                        //                        $form->setField($entField, 'input=none');
                        $form->setOptions($entField, array(''=>'Няма пера в номенклатурата!'));
                    }
                    $dimensional = $dimensional || ($group->rec->dimensional == 'yes');
                }
            } else {
                $form->setField($type.'Ent_',
                array(
                    'input'=>'input',
                    'caption'=>$type . ': ' . $acc->rec->title .'->',
                )
                );
                $form->setOptions($type.'Ent_', array(''=>'Няма разбивка'));
            }
        }
        
        if (!$dimensional) {
            $form->setField('quantity,price', 'input=none');
        }
        
        if (false && $quantityOnly) {
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
            
            if ($acc->rec->type && $acc->rec->strategy) {
                //                $quantityOnly = true;
            }
            
            if (empty($acc->groups)) {
                continue;
            }
            
            foreach ($acc->groups as $group) {
                if ($group->rec->dimensional == 'yes') {
                    $dimensional = TRUE;
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
            'rec' => $this->Accounts->fetch($accountId),
        'groups' => array()
        );
        
        foreach (range(1,3) as $i) {
            $listPart = "groupId{$i}";
            
            if (!empty($acc->rec->{$listPart})) {
                $listId = $acc->rec->{$listPart};
                $acc->groups[$i]->rec = $this->Lists->fetch($listId);
                $acc->groups[$i]->options = $this->Items->fetchOptions($listId);;
            }
        }
        
        return $acc;
    }
    
    
    /**
     *
     */
    private function getGroupInfo($groupId)
    {
        $Lists = &cls::get('acc_Lists');
        
        $rec = $Lists->fetch($groupId);
        
        $options = $Lists->getItems($rec);
        
        $result = (object) compact('rec', 'options');
        
        return $result;
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
    function on_BeforeDelete($mvc, $res, &$query, $cond)
    {
        $_query = clone($query);
        $query->deletedRecs = array();
        
        while ($rec = $_query->fetch($cond)) {
            $query->deletedRecs[] = $rec;
        }
    }
    
    
    /**
     *
     */
    function on_AfterDelete($mvc, $res, $query, $cond)
    {
        foreach ($query->deletedRecs as $rec) {
            $mvc->Master->detailsChanged($rec->{$mvc->masterKey}, $mvc, $rec);
        }
    }
}