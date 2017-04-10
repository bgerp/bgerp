<?php



/**
 * Интерфейс за логистични данни
 *
 *
 * @category  bgerp
 * @package   trans
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за документи с логистични данни
 */
class trans_LogisticDataIntf
{
    
    
	/**
	 * Клас имплементиращ мениджъра
	 */
	public $class;
	
	
	/**
     * Информация за логистичните данни
     *
     * @param mixed $rec   - ид или запис на документ
     * @return array $data - логистичните данни
     *		
     *		string(2)     ['fromCountry']  - международното име на английски на държавата за натоварване
     * 		string|NULL   ['fromPCode']    - пощенски код на мястото за натоварване
     * 		string|NULL   ['fromPlace']    - град за натоварване
     * 		string|NULL   ['fromAddress']  - адрес за натоварване
     *  	string|NULL   ['fromCompany']  - фирма 
     *   	string|NULL   ['fromPerson']   - лице
     * 		datetime|NULL ['loadingTime']  - дата на натоварване
     * 		string(2)     ['toCountry']    - международното име на английски на държавата за разтоварване
     * 		string|NULL   ['toPCode']      - пощенски код на мястото за разтоварване
     * 		string|NULL   ['toPlace']      - град за разтоварване
     *  	string|NULL   ['toAddress']    - адрес за разтоварване
     *   	string|NULL   ['toCompany']    - фирма 
     *   	string|NULL   ['toPerson']     - лице
     * 		datetime|NULL ['deliveryTime'] - дата на разтоварване
     * 		text|NULL 	  ['conditions']   - други условия
     */
    function getLogisticData($rec)
    {
    	return $this->class->getLogisticData($rec);
    }
}