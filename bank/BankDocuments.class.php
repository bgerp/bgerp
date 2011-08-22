<?php

/**
 * Банкови документи
 */
class bank_BankDocuments extends core_Manager {


    /**
     *  @todo Чака за документация...
     */
    var $title = 'Банкови документи';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, bank_Wrapper';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('docType',    'enum(НР=нареждане разписка, PN=преводно нареждане)', 'caption=Тип документ');
        $this->FLD('dtAcc',      'varchar(255)', 'caption=ДТ сметка');
        $this->FLD('dtPero',     'varchar(255)', 'caption=ДТ перо');
        $this->FLD('ctAcc',      'varchar(255)', 'caption=КТ сметка');
        $this->FLD('ctPero',     'varchar(255)', 'caption=КТ перо');
        $this->FLD('amount',     'double(decimals=2)', 'caption=Сума');                
        $this->FLD('currencyId', 'key(mvc=common_Currencies, select=code)', 'caption=Валута,mandatory');
    	$this->FLD('reason',     'varchar(255)', 'caption=Основание');
    }
    
    
    /**
     *
     */
    function on_AfterPrepareListToolbar($mvc, $data, $rec)
    {
        $data->toolbar->addBtn('Нареждане разписка', array('Ctr' => $this,
                                                           'Act' => 'Razpiska',
                                                           'ret_url' => TRUE));
        
        $data->toolbar->addBtn('Вносна бележка', array('Ctr' => $this,
                                                       'Act' => 'Vnosna',
                                                       'ret_url' => TRUE));

        $data->toolbar->addBtn('Преводно нареждане', array('Ctr' => $this,
                                                           'Act' => 'Prevodno',
                                                           'ret_url' => TRUE));        
    }
    
    
    function act_Razpiska()
    {
        $viewRazpiska = cls::get('bank_tpl_SingleRazpiskaLayout', array('data' => $data));
        
        $razpiska['execBank']                = 'ОБЕДИНЕНА БЪЛГАРСКА БАНКА';
        $razpiska['issuePlaceAndDate']       = 'Варна, 20.08.2011';
        $razpiska['execBranch']              = 'Варна, офис - Център';
        $razpiska['execBranchAddress']       = 'бул. България, 141';
        $razpiska['ordererName']             = 'Петър Петров Петров';
        $razpiska['ordererIban']             = 'BG23 BCBC 1233 1233 1233 01';
        $razpiska['ordererBank']             = 'Пощенска Банка';
        $razpiska['currencyId']              = 'BGN';
        $razpiska['amount']                  = '120.50';
        $razpiska['sayWords']                = 'Сто и двадесет лева и петдесет стотинки';
        $razpiska['proxyName']               = 'Иван иванов Иванов';
        $razpiska['proxyIdentityCardNumber'] = '00138795';
        $razpiska['proxyEgn']                = '7603031111';
        
        foreach ($razpiska as $k => $v) {
            if (!$razpiska[$k] ) {
                $razpiska[$k] = '&nbsp;';                 
            }
            
            $viewRazpiska->replace($razpiska[$k], $k);
        }
        
        return $viewRazpiska;
    }
    
    
    function act_Vnosna()
    {
        $viewVnosna = cls::get('bank_tpl_SingleVnosnaLayout', array('data' => $data));
        
        $vnosna['execBank']                = 'ОБЕДИНЕНА БЪЛГАРСКА БАНКА';
        $vnosna['issuePlaceAndDate']       = 'Варна, 20.08.2011';
        $vnosna['execBranch']              = 'Варна, офис - Център';
        $vnosna['execBranchAddress']       = 'бул. България, 141';
        $vnosna['beneficiaryName']         = 'Петър Петров Петров';
        $vnosna['beneficiaryIban']         = 'BG23 BCBC 1233 1233 1233 01';
        $vnosna['beneficiaryBank']         = 'Пощенска Банка';
        $vnosna['currencyId']              = 'BGN';
        $vnosna['amount']                  = '120.50';
        $vnosna['sayWords']                = 'Сто и двадесет лева и петдесет стотинки';
        $vnosna['depositorName']           = 'Иван иванов Иванов';
        $vnosna['reason']                  = 'Захранване';
        
        foreach ($vnosna as $k => $v) {
            if (!$vnosna[$k] ) {
                $vnosna[$k] = '&nbsp;';                 
            }
            
            $viewVnosna->replace($vnosna[$k], $k);
        }
        
        return $viewVnosna;
    }

    
    function act_Prevodno()
    {
        $viewPrevodno = cls::get('bank_tpl_SinglePrevodnoLayout', array('data' => $data));
        
        $prevodno['execBank']                = 'ОБЕДИНЕНА БЪЛГАРСКА БАНКА';
        $prevodno['execBranch']              = 'Варна, офис - Център';
        $prevodno['issueDate']               = '20.08.2011';
        $prevodno['execBranchAddress']       = 'бул. България, 141';
        $prevodno['beneficiaryName']         = 'Петър Петров Петров';
        $prevodno['beneficiaryIban']         = 'BG23 BCBC 1233 1233 1233 01';
        $prevodno['beneficiaryBic']          = 'BIC2323023';
        $prevodno['beneficiaryBank']         = 'Пощенска Банка';
        $prevodno['currencyId']              = 'BGN';
        $prevodno['amount']                  = '120.50';
        $prevodno['reason']                  = 'Захранване';
        $prevodno['moreReason']              = '';
        $prevodno['ordererName']             = 'Иван иванов Иванов';
        $prevodno['ordererIban']             = 'BG43 MNMN 2342 2323 2323 34';
        $prevodno['ordererBic']              = 'BIC1323011';
        $prevodno['paymentSystem']           = 'SWIFT';
        
        foreach ($prevodno as $k => $v) {
            if (!$prevodno[$k] ) {
                $prevodno[$k] = '&nbsp;';                 
            }
            
            $viewPrevodno->replace($prevodno[$k], $k);
        }
        
        return $viewPrevodno;
    }    
       
}