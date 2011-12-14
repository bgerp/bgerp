<?php

/**
 * Банкови сметки
 */
class bank_Accounts extends core_Manager {

    /**
     * Интерфайси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf';

    /**
     *  @todo Чака за документация...
     */
    var $title = 'Банкови сметки';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'BankAccountTypes=bank_AccountTypes, plg_RowTools, bank_Wrapper, plg_Rejected';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {   
        $this->FLD('contragentCls', 'class', 'caption=Контрагент->Клас,mandatory,input=hidden,silent');
        $this->FLD('contragentId', 'int', 'caption=Контрагент->Обект,mandatory,input=hidden,silent');
        $this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,mandatory');
        $this->FLD('iban', 'iban_Type', 'caption=IBAN'); // Макс. IBAN дължина е 34 символа (http://www.nordea.dk/Erhverv/Betalinger%2bog%2bkort/Betalinger/IBAN/40532.html)
        $this->FLD('bic', 'varchar(16)', 'caption=BIC');
        $this->FNC('title', 'html', 'caption=Наименование'); // Да се смята на on_BeforeSave() ако е празно.
        $this->FLD('bank', 'varchar(64)', 'caption=Банка');
        $this->FLD('typeId', 'key(mvc=bank_AccountTypes,select=name)', 'caption=Тип,oldFieldName=type');
        $this->FLD('comment', 'varchar', 'caption=Коментар,width=100%');

        // Задаваме индексите и уникалните полета за модела
        $this->setDbIndex('contragentCls,contragentId');
        $this->setDbUnique('iban');
    }



    /**
     *
     */
    function on_CalcTitle($mvc, $rec)
    {
        $cCode  = currency_Currencies::fetchField($rec->currencyId, 'code');
        $rec->title = "<span style='border:solid 1px #ccc;background-color:#eee; padding:2px; font-size:0.7em;'>{$cCode}</span>&nbsp;";
        $rec->title .= iban_Type::toVerbal($rec->iban);
        if($rec->bank) {
            $rec->title .= " ({$rec->bank})";
        }
    }
    

    /**
     *
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {   
        $rec = $data->form->rec;
        $cls = cls::get($rec->contragentCls);
        expect($cls instanceof core_Master);
        $details = arr::make($cls->details);
        expect($details['BankDetails'] == 'bank_Accounts');

    }

    
    /**
     * След зареждане на форма от заявката. (@see core_Form::input())
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    function on_AfterInputEditForm($mvc, &$form)
    {
        if (!$form->isSubmitted()) {
            return;
        }
        
        $rec = &$form->rec;
        
 
 
    }


    /**
     *
     */
    function prepareBankDetails($data)
    {
        expect($data->contragentCls = core_Classes::fetchIdByName($data->masterMvc));
        expect($data->masterId);
        $query = $this->getQuery();
        $query->where("#contragentCls = {$data->contragentCls} AND #contragentId = {$data->masterId}");
        while($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $row = $data->rows[$rec->id] = $this->recToVerbal($rec);
        }
    }

    /**
     * Рендира данните
     */
    function renderBankDetails($data)
    {
        
        if(count($data->rows)) {
            $tpl = new ET("<fieldset class='detail-info'>
                            <legend class='groupTitle'>" . tr('Банкови сметки') . " [#plus#]</legend>
                                <div class='groupList,clearfix21'>
                                 [#accounts#]
                                </div>
                        </fieldset>");

            foreach($data->rows as $id => $row) {
                $tpl->append("<div style='padding:3px;'>", 'accounts');

                $tpl->append("{$row->title}", 'accounts');
                
                if($this->haveRightFor('edit', $id)) {
                    // Добавяне на линк за редактиране
                    $tpl->append("<span style='margin-left:5px;'>", 'accounts');
                    $url = array($this, 'edit', $id, 'ret_url' => TRUE);
                    $img = "<img src=" . sbf('img/16/edit-icon.png') . " width='16' valign=bottom  height='16'>";
                    $tpl->append(ht::createLink($img, $url, FALSE, 'title=' . tr('Редактиране на банкова сметка')), 'accounts');
                    $tpl->append('</span>', 'accounts');
                }
                
                if($this->haveRightFor('delete', $id)) {
                    // Добавяне на линк за изтриване
                    $tpl->append("<span style='margin-left:5px;'>", 'accounts');
                    $url = array($this, 'delete', $id, 'ret_url' => TRUE);
                    $img = "<img src=" . sbf('img/16/delete-icon.png') . " width='16' valign=bottom  height='16'>";
                    $tpl->append(ht::createLink($img, $url, 'Наистина ли желаете да изтриете сметката?', 'title=' . tr('Изтриване на банкова сметка')), 'accounts');
                    $tpl->append('</span>', 'accounts');
                }

                $tpl->append("</div>", 'accounts');

            }
        } else {
            $tpl = new ET("<fieldset class='detail-info' style='border:none;'>
                            <legend class='groupTitle'>" . tr('Банкови сметки') . " [#plus#]</legend>
                                
                           </fieldset>");

        }
        
        $url = array($this, 'add', 'contragentCls' => $data->contragentCls, 'contragentId' => $data->masterId, 'ret_url' => TRUE);
        $img = "<img src=" . sbf('img/16/add.png') . " width='16' valign=absmiddle  height='16'>";
        $tpl->append(ht::createLink($img, $url, FALSE, 'title=' . tr('Добавяне на нова банкова сметка')), 'plus');

        return $tpl;
    }


}