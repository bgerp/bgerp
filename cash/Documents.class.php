<?php


/**
 * Касови документи
 *
 *
 * @category  bgerp
 * @package   cash
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_Documents extends core_Manager {
    
    
    
    /**
     * Заглавие
     */
    var $title = 'Касови документи';
    
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, cash_Wrapper, expert_Plugin';
    
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('docType', 'enum(ПКО=Приходен касов ордер,РКО=Разходен касов ордер,ВБ=Вносна бележка)', 'caption=Тип');
        
        // Дебитна сметка
        $this->FLD('dtAcc', 'varchar(255)', 'caption=ДТ сметка');
        $this->FLD('dtPero', 'key(mvc=acc_Items,select=title)', 'caption=ДТ перо');
        
        // Кредитна сметка
        $this->FLD('ctAcc', 'key(mvc=acc_Accounts,select=title)', 'caption=КТ сметка');
        $this->FLD('ctPero', 'key(mvc=acc_Items,select=title)', 'caption=КТ перо');
        
        // Параметри
        $this->FLD('amount', 'double(decimals=2)', 'caption=Сума,mandatory');
        $this->FLD('quantity', 'double(decimals=2)', 'caption=Количество');
        $this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,mandatory');
        
        $this->FLD('originId', 'key(mvc=doc_Containers)', 'caption=Към документ');
        $this->FLD('reason', 'varchar(255)', 'caption=Основание');
    }
    
    
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
    
    
    function exp_Debit($exp)
    {
        $exp->functions['accfetchfield'] = 'acc_Accounts::fetchField';
        $exp->functions['listfetchfield'] = 'acc_Lists::fetchField';
        $exp->functions['itemfetchfield'] = 'acc_Items::fetchField';
        
        $exp->DEF('kind=Вид', 'enum(ПК=Приход от клиент, 
                                    ВД=Връщане от доставчик,
                                    ВПЛ=Връщене от подотчетно лице,
                                    ПДИ=Приход от друг източник)', 'maxRadio=4,columns=1', 'value=ПК');
        
        $exp->question("#kind", "Моля, посочете вида на прихода:", TRUE, 'title=Кой внася парите?');
        
        $exp->DEF('ctAccNum=Кредит сметка', 'int');
        
        // Прихода в касата винаги става с PKO
        $exp->rule('#docType', "'ПКО'");
        
        // Клиент
        $exp->CDEF('ctPero=Клиент', 'acc_type_Item(lists=clients)', "#kind=='ПК'");
        $exp->question('#ctPero', 'Посочете клиента:', "#kind=='ПК'", "title=Клиент");
        $exp->rule('#ctAccNum', "411", "#kind=='ПК'");
        
        // Доставчик
        $exp->CDEF('#ctPero=Доставчик', 'acc_type_Item(lists=suppliers)', "#kind=='ВД'");
        $exp->question('#ctPero', 'Посочете доставчика:', "#kind=='ВД'", "title=Доставчик");
        $exp->rule('#ctAccNum', "4011", "#kind=='ВД'");
        
        // Подотчетно лице
        $exp->CDEF('#ctPero=Служител', 'acc_type_Item(lists=accountableInd)', "#kind=='ВПЛ'");
        $exp->question('#ctPero', 'Изберете подотчетното лице:', "#kind=='ВПЛ'", "title=Подотчетно лице");
        $exp->rule('#ctAccNum', "422", "#kind=='ВПЛ'");
        
        $exp->rule('#ctPeroListId', "accFetchField('#num =' . #ctAccNum, 'groupId1')");
        $exp->rule('#ctPeroListName', "listFetchField(#ctPeroListId, 'name')", '#ctPeroListId >0 ');
        $exp->rule('#ctPeroListSysId', "listFetchField(#ctPeroListId, 'systemId')", '#ctPeroListId >0 ');
        $exp->rule('#ctPero', "0", ' !(#ctPeroListId >0) ');
        
        // Приход от друг източник
        $exp->DEF('#ctAcc=Разчетна сметка', 'acc_type_Account(root=4)', 'width=400px');
        $exp->question('#ctAcc', 'Изберете сметката, източник на прихода:', "#kind=='ПДИ'", "title=Разчетна сметка");
        $exp->rule('#ctAccNum', "accFetchField(#ctAcc, 'num')");
        $exp->rule('#ctAcc', "accFetchField(#ctAccNum . '= #num', 'id')");
        $exp->rule('#ctAccTitle', "accFetchField(#ctAcc, 'title')");
        $exp->rule('#ctPeroTitle', "itemFetchField(#ctPero, 'numTitleLink')");
        
        // Перо от друг източник
        $exp->CDEF('#ctPero', "='acc_type_Item(lists=' . #ctPeroListSysId . ')'", "#kind=='ПДИ'", array('caption' => '=#ctPeroListName'));
        $exp->question('#ctPero', "='Изберете от \"' . #ctPeroListName . '\"'", "#kind=='ПДИ' && #ctPeroListId>0 ", "title=Избор");
        
        // Само за ДЕМО как се прави предупреждение
        $exp->question('#amount,currencyId', "Въведете количеството и посочете валутата", TRUE, "title=Пари");
        
        $exp->INFO("='<ul>' .
                     '<li>Кредит: ' . #ctAccTitle . ' / ' . #ctPeroTitle .  
                     '<li>Дебит: ' . ' ....' .
                     '<li>Сума: ' . #amount . ' - ' . #currencyId .
                     '</ul>'" , '#amount', 'title=Информация за счетоводството');
        
        return $exp->solve('#kind,#ctAccNum,#ctPero,#amount,currencyId');
    }
}
