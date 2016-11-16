<?php



/**
 * Клас 'doc_PrototypeSourceIntf' - Интерфейс за документи, които могат да стават на шаблони
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за документи, които могат да стават на шаблони
 */
class doc_PrototypeSourceIntf
{
	
	
	/**
	 * Дали документа може да бъде направен на шаблон
	 * 
	 * @param mixed $id
	 * @return boolean
	 */
	function canBeTemplate($id)
	{
		return $this->class->canBeTemplate($id);
	}
	
	
	/**
	 * Дали документа да се добави като шаблон автоматично след създаването му
	 *
	 * @param mixed $id
	 * @return boolean
	 */
	function addAsTemplateAfterCreation($id)
	{
		return $this->class->addAsTemplateAfterCreation($id);
	}
}