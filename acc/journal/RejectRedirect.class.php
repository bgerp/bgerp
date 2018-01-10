<?php
class acc_journal_RejectRedirect extends acc_journal_Exception
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
		$Exception = (!core_Users::isSystemUser()) ? 'acc_journal_RejectRedirect' : 'acc_journal_Exception';
		
		if (!(boolean)$condition) {
			throw new $Exception($message, $options);
		}
	}
	
}