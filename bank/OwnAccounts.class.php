<?php

/**
 * Банкови сметки на фирмата
 */
class bank_OwnAccounts extends core_Manager {

    /**
     * Интерфайси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf, bank_OwnAccRegIntf';
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, bank_Wrapper, acc_plg_Registry,
                     plg_Sorting, plg_Selected';    
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'bankAccountId, tools=Пулт';


    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';    
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Банкови сметки на фирмата';

    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('bankAccountId', 'key(mvc=bank_Accounts,select=title)', 'caption=Сметка,mandatory');
    	$this->FNC('title',       'varchar(128)', 'caption=Наименование, input=none');
        $this->FLD('titulars',    'keylist(mvc=crm_Persons, select=name)', 'caption=Титуляри->Име');                
        $this->FLD('together',    'enum(no,yes)', 'caption=Титуляри->Заедно / поотделно');
        $this->FLD('operators',   'keylist(mvc=core_Users, select=nick)', 'caption=Оператори'); // type=User(role=fin)
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
        /*
    	$Companies = cls::get('crm_Companies');
        $ownCompanyId = $Companies->fetchField("#name='" . BGERP_OWN_COMPANY_NAME . "'", 'id'); 
        
        $BankAccounts = cls::get('bank_Accounts');
        $queryBankAccounts = $BankAccounts->getQuery();
        

        $where = "#contragentId = {$ownCompanyId}";
        */
        
        $BankAccounts = cls::get('bank_Accounts');
        $queryBankAccounts = $BankAccounts->getQuery();
        
        cls::load('crm_Companies');
        $where = "#contragentId = " . BGERP_OWN_COMPANY_ID;    	
    	
        $selectOptBankOwnAccounts = array();
        
	    while($rec = $queryBankAccounts->fetch($where)) {
	    	if (!$mvc->fetchField("#bankAccountId = " . $rec->id . "", 'id')) {
	    	  $selectOptBankOwnAccounts[$rec->id] = $rec->title;
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
    
    
    /**
     * Ако текущия потрбител е сред елементите на 'operators'
     * 
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec)
    {
      if($action == 'doselect')
      {
          $cu = core_Users::getCurrent();
          if(type_Keylist::isIn($cu, $rec->operators)) {
             $requiredRoles = 'every_one';
          } else {
             $requiredRoles = 'fin_master,admin';
          }
      }
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
                'title' => bank_Accounts::fetchField($rec->bankAccountId, 'title'),
                'features' => 'foobar' // @todo!
            );
            bp($result);
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
