<?php


/**
 * Плъгин даващ възможност да се печатат етикети от документ
 * 
 * @category  bgerp
 * @package   label
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_plg_Print extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		setIfNot($mvc->canPrintlabel, 'label, admin, ceo');
	}
	
	
	/**
	 * След подготовка на тулбара на единичен изглед.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareSingleToolbar($mvc, &$data)
	{
		if($mvc->haveRightFor('printlabel', $data->rec)){
			$templates = label_Templates::getTemplatesByDocument($mvc, $data->rec->id, TRUE);
			$error = '';
			if(!count($templates)){
				$error = ",error=Няма наличен шаблон за етикети от \"{$mvc->title}\"";
			}
			
			core_Request::setProtected('class,objectId');
			$url = array('label_Labels', 'selectTemplate', 'class' => $mvc->className, 'objectId' => $data->rec->id, 'ret_url' => TRUE);
			$data->toolbar->addBtn('Етикетиране', toUrl($url), NULL, "target=_blank,ef_icon = img/16/price_tag_label.png,title=Разпечатване на етикети от {$mvc->title}|* №{$data->rec->id}{$error}");
			core_Request::removeProtected('class,objectId');
		}
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
	 *
	 * @param core_Mvc $mvc
	 * @param string $requiredRoles
	 * @param string $action
	 * @param stdClass $rec
	 * @param int $userId
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'printlabel' && isset($rec)){
			if(in_array($rec->state, array('rejected', 'draft', 'template'))){
				$requiredRoles = 'no_one';
			}
		}
	}
	
	
	/**
	 * Реализация по подразбиране
	 * 
	 * @see label_SequenceIntf::canSelectTemplate($id, $templateId)
	 */
	public static function on_AfterCanSelectTemplate($mvc, &$res, $objectId, $templateId)
	{
		if(is_null($res)) {
			$res = TRUE;
		}
	}
}