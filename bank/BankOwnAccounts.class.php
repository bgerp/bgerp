<?php

/**
 * Банкови сметки на фирмата
 */
class bank_BankOwnAccounts extends core_Manager {

    /**
     * Интерфайси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf, bank_BankOwnAccountsAccRegIntf';
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, bank_Wrapper, acc_plg_Registry,
                     plg_Sorting, plg_State2';    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Банкови сметки на фирмата';

    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('bankAccountId', 'key(mvc=bank_BankAccounts,select=iban)', 'caption=Сметка,mandatory');
    	
        $this->FNC('title',       'varchar(128)', 'caption=Наименование, input=none');
        $this->FLD('titulars',    'keylist(mvc=crm_Persons, select=name)', 'caption=Титуляри->Име');                
        $this->FLD('together',    'enum(no,yes)', 'caption=Титуляри->Заедно / поотделно');
        $this->FLD('operators',   'keylist(mvc=core_Users, select=nick)', 'caption=Оператори'); // type=User(role=fin)
        $this->FNC('selected',    'varchar(255)', 'caption=Избор');
    }

    
    /**
     *  
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $bankAccounts = cls::get('bank_BankAccounts');
        $row->title = $bankAccounts->fetchField("#id = {$rec->id}", 'title');
        
    	
    	$selectedAccountId = Mode::get('selectedAccountId');
        
        if ($selectedAccountId && $selectedAccountId == $rec->id) {
           $row->selected =  'Избрана, ';
           $row->selected .= Ht::createLink('Откажи', array($this, 'UnselectAccount', $rec->id));
           $row->ROW_ATTR .= new ET(' style="background-color: #f0fff0;"');
        } else {
            $row->selected = Ht::createLink('Избери', array($this, 'SelectAccount', $rec->id));
            $row->ROW_ATTR .= new ET(' style="background-color: #dddddd;"');            
        }
    }

    
    function act_SelectAccount()
    {
        $id = Request::get('id');
        Mode::setPermanent('selectedAccountId', $id);
        
        return new Redirect(array($this, 'list'));        
    }
    
    
    function act_UnselectAccount()
    {
        $id = Request::get('id');
        Mode::setPermanent('selectedAccountId', NULL);
        
        return new Redirect(array($this, 'list'));        
    }

    
    /**
     *
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $Companies = cls::get('crm_Companies');
        $ownCompanyId = $Companies->fetchField("#name='" . BGERP_OWN_COMPANY_NAME . "'", 'id'); 
        
        $BankAccounts = cls::get('bank_BankAccounts');
        $queryBankAccounts = $BankAccounts->getQuery();
        

        $where = "#contragentId = {$ownCompanyId}";
        
	    while($rec = $queryBankAccounts->fetch($where)) {
	    	if (!$this->fetchField("#bankAccountId = " . $rec->id . "", 'id')) {
	    	  $selectOptBankOwnAccounts[$rec->id] = $rec->iban;
	    	}
	    }
	    

		$data->form->setField('bankAccountId', 'caption=Сметка');
		$data->form->setOptions('bankAccountId', $selectOptBankOwnAccounts);
		
		// set 'operators'
		$Users = cls::get('core_Users');
		
		if ($data->form->rec->id) {
	        $usersArr = explode("|", $data->form->rec->operators);
	        
	        foreach($usersArr as $userId) {
	            if ($userId) {
	                $selectOptOperators[$userId] = $Users->fetchField("#id = {$userId}", 'names');   
	            }  
	        }
		} else {
		    $queryUsers = $Users->getQuery();

		    $where = "1=1";
		    while($rec = $queryUsers->fetch($where)) {
                $selectOptOperators[$rec->id] = $rec->names;
            }		    
		    
		}

		$data->form->setField('operators', 'caption=Оператори<br/>(име от core_Users)');
        $data->form->setSuggestions('operators', $selectOptOperators);		
    }

    
    /*******************************************************************************************
     * 
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     * 
     ******************************************************************************************/
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = null;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->id,
                'title' => $rec->name,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    /**
     * @see crm_ContragentAccRegIntf::getLinkToObj
     * @param int $objectId
     */
    static function getLinkToObj($objectId)
    {
        $self = cls::get(__CLASS__);
        
        if ($rec  = $self->fetch($objectId)) {
            $result = ht::createLink($rec->name, array($self, 'Single', $objectId)); 
        } else {
            $result = '<i>неизвестно</i>';
        }
        
        return $result;
    }
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
    }
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */    
    
}