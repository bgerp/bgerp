<?php



/**
 * Плъгин позволяващ да се зададе само време за изпъление/срок на документ имащ такова поле
 * Документа трябва да има дефиниран $termDateFld
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_plg_SetTermDate extends core_Plugin
{
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		if(!isset($mvc->termDateFld)) return;
		if(!isset($fields['-single'])) return;
		if(Mode::isReadOnly()) return;
		
		// Ако има права показване на линка за редакция
		if($mvc->haveRightFor('settermdate', $rec)){
			$row->{$mvc->termDateFld} = $row->{$mvc->termDateFld} . "" . ht::createLink('', array($mvc, 'settermdate', $rec->id, 'ret_url' => TRUE), FALSE, 'ef_icon=img/16/edit.png,title=Задаване на нова дата');
		}
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'settermdate'){
			$clone = NULL;
			if(isset($rec)){
				if(!in_array($rec->state, array('draft', 'pending'))){
					$requiredRoles = 'no_one';
				} else {
					$clone = clone $rec;
					$clone->state = 'draft';
				}
			}
			
			if($requiredRoles != 'no_one'){
				$requiredRoles = $mvc->getRequiredRoles('pending', $clone, $userId);
			}
		}
	}
	
	
	/**
	 * Извиква се преди изпълняването на екшън
	 *
	 * @param core_Mvc $mvc
	 * @param mixed $res
	 * @param string $action
	 */
	public static function on_BeforeAction($mvc, &$res, $action)
	{
		if($action != 'settermdate') return;
		
		// Проверка
		$mvc->requireRightFor('settermdate');
		expect($id = Request::get('id', 'int'));
		expect($rec = $mvc->fetch($id));
		$mvc->requireRightFor('settermdate', $rec);
		
		// Показване на формата за смяна на срока
		$form = cls::get('core_Form');
		$Field = $mvc->getField($mvc->termDateFld);
		$form->title = core_Detail::getEditTitle($mvc, $id, $Field->caption, NULL);
		$form->FLD('newTermDate', 'datetime', "caption={$Field->caption}");
		$form->setDefault('newTermDate', $rec->{$mvc->termDateFld});
		$form->setDefault('newTermDate', date('Y-m-d H:i'));
		$form->input();
		
		// Ако е събмитнат
		if($form->isSubmitted()){
			
			// Обновява се срока
			$rec->{$mvc->termDateFld} = $form->rec->newTermDate;
			$mvc->save_($rec, $mvc->termDateFld);
			$mvc->touchRec($rec);
			
			followRetUrl(NULL, 'Промяната е направена успешно');
		}
		
		$form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/disk.png');
    	$form->toolbar->addBtn('Отказ', $mvc->getSingleUrlArray($id),  'ef_icon = img/16/close-red.png');
    		 
    	// Рендиране на формата
    	$res = $form->renderHtml();
    	$res = $mvc->renderWrapping($res);
    	
    	// ВАЖНО: спираме изпълнението на евентуални други плъгини
    	return FALSE;
	}
}