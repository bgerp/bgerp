<?php



/**
 * Клас 'doc_AddToFolderIntf' - Интерфейс за мениджърите на документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title:    Интерфейс за неща които да бъдат добавени към папките
 */
class doc_AddToFolderIntf
{
	/**
	 * Дали в посочена папка трябва да се добавя бутон за добавяне на нов документ от типа на мениджъра в папката
	 * 
	 * @param stdClass $folderRec - запис на папката
	 * @param int $userId - ид на потребителя, NULL ако е текущия
	 * @return boolean - да се добавили бутон за създаване на нов документ в тулбара на папката
	 */
	function mustShowButton($folderRec, $userId = NULL)
	{
		return $this->class->mustShowButton($folderRec, $userId);
	}
}