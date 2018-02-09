<?php 



/**
 * Ексепшън при печатане на етикети
 * 
 * @category  bgerp
 * @package   label
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_exception_Redirect extends acc_journal_Exception
{
	
	
	/**
	 * Генерира exception от съотв. клас, в случай че зададеното условие не е изпълнено
	 *
	 * @param boolean $condition
	 * @param string $message
	 * @param array $options
	 * @throws static
	 */
	public static function expect($condition, $message, $options = array())
	{
		$Exception = (!core_Users::isSystemUser()) ? 'label_exception_Redirect' : 'acc_journal_Exception';
		
		if (!(boolean)$condition) throw new $Exception($message, $options);
	}
}