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
     * Интерфейси, поддържани от този мениджър
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
     * Кой има право да чете?
     */
    var $canRead = 'bank, ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'bank, ceo';
    
    
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
        $this->FLD('bankAccountId', 'key(mvc=bank_Accounts,select=iban)', 'caption=Сметка,mandatory');
        $this->FNC('title', 'varchar(128)', 'caption=Наименование, input=none');
        $this->FLD('titulars', 'keylist(mvc=crm_Persons, select=name)', 'caption=Титуляри->Име');
        $this->FLD('together', 'enum(together=Заедно,separate=Поотделно)', 'caption=Титуляри->Представляват');
        $this->FLD('operators', 'keylist(mvc=core_Users, select=names)', 'caption=Оператори');
    }
    
    
    /**
     * Обработка по формата
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
    	$optionAccounts = static::getPossibleBankAccounts($data->form);
    	$operators = static::getOperators();
        
        $data->form->setOptions('bankAccountId', $optionAccounts);
        $data->form->setSuggestions('operators', $operators);
    }
    
    
    /**
     * Подготовка на списъка от банкови сметки, между които можем да избираме
     * @return array $options масив от потребители
     */
    static function getPossibleBankAccounts($form)
    {
    	$conf = core_Packs::getConfig('crm');
    	$bankAccounts = cls::get('bank_Accounts');
    	
    	// Извличаме само онези сметки, които са на нашата фирма и не са
        // записани в OwnAccounts класа
        $queryBankAccounts = $bankAccounts->getQuery();
        $queryBankAccounts->where("#contragentId = {$conf->BGERP_OWN_COMPANY_ID}");
        $queryBankAccounts->where("#contragentCls = " . core_Classes::getId('crm_Companies'));
        $options = array();
        
        while($rec = $queryBankAccounts->fetch()) {
           if (!static::fetchField("#bankAccountId = " . $rec->id . " AND #id != '{$form->rec->id}'", 'id')) {
               $options[$rec->id] = $bankAccounts->getVerbal($rec, 'iban');
           }
        }
        
        return $options;
    }
    
    
    /**
     * Извличаме само потребителите с роли bank, ceo
     * @return array $suggestions масив от потребители
     */
    static function getOperators()
    {
    	$suggestions = array();
    	$query = core_Users::getQuery();
    	while($rec = $query->fetch()) {
    		if(core_Users::haveRole('bank', $rec->id)) {
    			$row = core_Users::recToVerbal($rec);
    			$suggestions[$rec->id] = $row->names;
    		}
    	}
    
    	return $suggestions;
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
        $result = NULL;
        
        if ($rec = static::fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->id,
				'title' => bank_Accounts::fetchField($rec->bankAccountId, 'iban'),
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
