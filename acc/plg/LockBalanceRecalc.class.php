<?php



/**
 * Плъгин за документи, който при оттегляне/възстановяване/контиране заключва процеса
 * на изчисляването на баланса, а ако е вече заключен се показва статус с предупреждение,
 * След оттегляне/възстановяване/контиране забранява документа да му се промени състоянието
 * докато не се преизчисли баланса
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_plg_LockBalanceRecalc extends core_Plugin
{



	/**
	 * Помощна ф-я проверяваща дали действието с документа може да стане
	 *
	 * @param stdClass $rec - запис на обекта
	 * @return Ambigous <FALSE, string> - съобщението за грешка, или FALSE ако може да се продължи
	 */
	private static function stopAction($rec)
	{
		$msg = FALSE;
		 
		// Ако баланса се преизчислява в момента, забраняваме действието
		if(!core_Locks::get('RecalcBalances', 600, 1)) {
			$msg = "Балансът се преизчислява в момента. Опитайте след малко!";
		} else {
			
			// Ако баланса трябва да се преизчисли също, забраняваме действието
			$bRec = acc_Balances::getLastBalance();
			if(acc_Balances::isValid($bRec) === FALSE){
				$msg = "Преди да продължите, балансът трябва да се преизчисли";
			}
		}
		 
		return $msg;
	}
	
	
	/**
	 * Изпълнява се преди контиране на документа
	 */
	public static function on_BeforeConto(core_Mvc $mvc, &$res, $id)
	{
		if($msg = self::stopAction($mvc->fetchRec($id))){
			core_Statuses::newStatus('|' . $msg, 'warning');
	
			return FALSE;
		}
	}
	
	
	/**
	 * Изпълнява се преди възстановяването на документа
	 */
	public static function on_BeforeRestore(core_Mvc $mvc, &$res, $id)
	{
		if($msg = self::stopAction($mvc->fetchRec($id))){
			core_Statuses::newStatus('|' . $msg, 'warning');
	
			return FALSE;
		}
	}
	
	
	/**
	 * Изпълнява се преди оттеглянето на документа
	 */
	public static function on_BeforeReject(core_Mvc $mvc, &$res, $id)
	{
		if($msg = self::stopAction($mvc->fetchRec($id))){
			core_Statuses::newStatus('|' . $msg, 'warning');
	
			return FALSE;
		}
		 
		$rec = $mvc->fetchRec($id);
		 
		$jRec = acc_Journal::fetchByDoc($mvc->getClassId(), $rec->id);
		if($jRec){
			$jCount = acc_JournalDetails::count("#journalId = {$jRec->id}");
	
			// При оттегляне вдигаме времето за изпълнение спрямо записите в журнала
			$timeLimit = ceil($jCount / 3000) * 30;
			if($timeLimit >= 30){
				core_App::setTimeLimit($timeLimit);
			}
		}
	}
	
	
	/**
	 * Оттегляне на документа
	 */
	public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
	{
		core_Locks::release('RecalcBalances');
	}
	
	
	/**
	 * Контиране на счетоводен документ
	 */
	public static function on_AfterConto(core_Mvc $mvc, &$res, $id)
	{
		core_Locks::release('RecalcBalances');
	}
	
	
	/**
	 * Възстановяване на документа
	 */
	public static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
	{
		core_Locks::release('RecalcBalances');
	}
}
