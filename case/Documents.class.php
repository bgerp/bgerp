<?php

/**
 * Касови документи
 */
class case_Documents extends core_Manager {


    /**
     *  @todo Чака за документация...
     */
    var $title = 'Касови документи';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, case_Wrapper, expert_Plugin';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('docType',    'enum(ПКО=Приходен касов ордер,РКО=Разходен касов ордер,ВБ=Вносна бележка)', 'caption=Тип');

        // Дебитна сметка
        $this->FLD('dtAcc',      'varchar(255)', 'caption=ДТ сметка');
        $this->FLD('dtPero',     'key(mvc=acc_Items,select=title)', 'caption=ДТ перо');

        // Кредитна сметка
        $this->FLD('ctAcc',      'key(mvc=acc_Accounts,select=title)', 'caption=КТ сметка');
        $this->FLD('ctPero',     'key(mvc=acc_Items,select=title)', 'caption=КТ перо');

        // Параметри
        $this->FLD('amount',     'double(decimals=2)', 'caption=Сума');
        $this->FLD('quantity',   'double(decimals=2)', 'caption=Количество');                
        $this->FLD('currencyId', 'key(mvc=common_Currencies, select=code)', 'caption=Валута,mandatory');
        
        $this->FLD('originId',     'key(mvc=docThreadDocuments)', 'caption=Към документ');
    	$this->FLD('reason',     'varchar(255)', 'caption=Основание');
    }
    
    
    /**
     *
     */
    function on_BeforeRenderListToolbar($mvc, $tpl, $data)
    {
        
        if(Request::get('Ajax')) {
            $tpl = expert_Expert::getButton('Приход', array($this, 'Debit', 'ret_url' => TRUE));

            $tpl->append(expert_Expert::getButton('Разход', array($this, 'Credit', 'ret_url' => TRUE)));

            expert_Expert::enableAjax($tpl);

            return FALSE;
        } else {
            $tpl = ht::createBtn('Приход', array($this, 'Debit', 'ret_url' => TRUE));

            $tpl->append(ht::createBtn('Разход', array($this, 'Credit', 'ret_url' => TRUE)));

 
            return FALSE;
        }
    }
    

    /**
     *
     */
    function exp_Debit($exp)
    {
        $exp->functions['accfetchfield'] = 'acc_Accounts::fetchField';

        $exp->DEF('kind=Вид', 'enum(ПК=Приход от клиент, 
                                    ВД=Връщане от доставчик,
                                    ВПЛ=Връщене от подотчетно лице,
                                    ПДИ=Приход от друг източник)', 'maxRadio=4,columns=1', 'value=ПК');



        $exp->question("#kind", "Моля, посочете вида на прихода:", TRUE, 'title=Кой внася парите?');
        
        $exp->DEF('ctAccNum=Кредит сметка', 'int');

        // Прихода в касата винаги става с PKO
        $exp->rule('#docType', "'ПКО'");

        // Клиент
        $exp->DEF('customerPero=Клиент', 'type_AccItem(listNum=103)');
        $exp->question('#customerPero', 'Посочете клиента:', "#kind=='ПК'", "title=Клиент");
        $exp->rule('#ctAccNum', "411", "#kind=='ПК'");

        // Доставчик
        $exp->DEF('supplierPero=Доставчик', 'type_AccItem(listNum=102)');
        $exp->question('#supplierPero', 'Посочете доставчика:', "#kind=='ВД'", "title=Доставчик");
        $exp->rule('#ctAccNum', "4011", "#kind=='ВД'");

        // Подотчетно лице
        $exp->DEF('plPero=Служител', 'type_AccItem(listNum=106)');
        $exp->question('#plPero', 'Изберете подотчетното лице:', "#kind=='ВПЛ'", "title=Подотчетно лице");
        $exp->rule('#ctAccNum', "422", "#kind=='ВПЛ'");



        // Приход от друг източник
        $exp->DEF('#ctAcc=Разчетна сметка', 'type_Account(root=4)', 'width=300px');
        $exp->question('#ctAcc', 'Изберете сметката, източник на прихода:', "#kind=='ПДИ'", "title=Разчетна сметка");
        $exp->rule('#ctAccNum', "accFetchField(#ctAcc, 'num')");

        // Само за ДЕМО как се прави предупреждение
        $exp->warning('Наистина ли прихода не е от клиент, доставчик или подотчетно лице?', "#kind=='ПДИ'" );

        $exp->rule('#ctAccTitle', "accFetchField('#num =' . #ctAccNum, 'title')");

        $exp->INFO("='Сметката, която ще бъде кредитирана е ' . #ctAccNum . ' - ' . #ctAccTitle", "#ctAccNum != ''");
        
        $exp->rule('#ctPero', "#customerPero");
        $exp->rule('#ctPero', "#supplierPero");
        $exp->rule('#ctPero', "#plPero");


        return $exp->solve('#kind,#ctAccNum,#ctPero');
    }



}