<?php



/**
 * Банкови сметки на фирмата
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_OwnAccounts extends core_Manager {
    
    
    /**
     * Интерфайси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf, bank_OwnAccRegIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, bank_Wrapper, acc_plg_Registry,
                     plg_Sorting, plg_Current, plg_LastUsedKeys';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'bankAccountId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'bankAccountId, tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Заглавие
     */
    var $title = 'Банкови сметки на фирмата';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Банкова сметка';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('bankAccountId', 'key(mvc=bank_Accounts,select=title)', 'caption=Сметка,mandatory');
        $this->FNC('title', 'varchar(128)', 'caption=Наименование, input=none');
        $this->FLD('titulars', 'keylist(mvc=crm_Persons, select=name)', 'caption=Титуляри->Име');
        $this->FLD('together', 'enum(no,yes)', 'caption=Титуляри->Заедно / поотделно');
        $this->FLD('operators', 'keylist(mvc=core_Users, select=nick)', 'caption=Оператори');  // type=User(role=fin)
    }
    
    
    /**
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
        $where .= ' AND #contragentCls = ' . core_Classes::fetchIdByName('crm_Companies');
        
        $selectOptBankOwnAccounts = array();
        
        while($rec = $queryBankAccounts->fetch($where)) {
            if (!$mvc->fetchField("#bankAccountId = " . $rec->id . " AND #id != '{$data->form->rec->id}'", 'id')) {
                $selectOptBankOwnAccounts[$rec->id] = $rec->title;
            }
        }
        
        //
        
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
        $result = NULL;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->id,
                'title' => strip_tags(bank_Accounts::fetchField($rec->bankAccountId, 'title')),
                'features' => 'foobar' // @todo!
            );
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
