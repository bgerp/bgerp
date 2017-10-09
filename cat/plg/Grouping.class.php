<?php



/**
 * Клас 'bgerp_plg_Groups' - Поддръжка на групи и групиране
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cat_plg_Grouping extends core_Plugin
{
	
	
	/**
	 * Извиква се след описанието на модела
	 */
	public static function on_AfterDescription(&$mvc)
	{
		$mvc->doWithSelected = arr::make($mvc->doWithSelected) + array('changemeta' => 'Свойства');
	}
	
	
	/**
	 * Смяна свойствата
	 *
	 * @return core_Redirect
	 */
	public static function on_BeforeAction(core_Manager $mvc, &$res, $action)
	{
		if ($action == 'changemeta') {
			
			$mvc->requireRightFor('edit');
			
			// Създаване на формата
			$form = cls::get('core_Form');
			$form->FNC('id', 'int', 'input=hidden,silent');
			$form->FNC('Selected', 'text', 'input=hidden,silent');
			$form->FNC('ret_url', 'varchar(1024)', 'input=hidden,silent');
			$form->input(NULL, 'silent');
			$rec = $form->rec;
			
			expect($rec->id || $rec->Selected, $rec);
			
			$selArr = arr::make($rec->Selected);
			if($id) {
				$selArr[] = $id;
			}
			
			$metas = $mvc->getFieldType('meta')->suggestions;
			$canDelMetas = $canAddMetas = array();
			
			// Премахване на лишите или недостъпните id-та
			foreach($selArr as $i => $ind) {
				$obj = (object) array('id' => $ind);
			
				if(!is_numeric($ind) || !$mvc->haveRightFor('edit', $obj)) {
					unset($selArr[$i]);
				}
			
				$metaArr = type_Set::toArray($mvc->fetchField($ind, 'meta'));
				foreach($metaArr as $m) {
					if($metas[$m]) {
						$canDelMetas[$m]++;
					}
				}
			
				foreach($metas as $m => $caption) {
					if(!$metaArr[$m]) {
						$canAddMetas[$m]++;
					}
				}
			}
			
			$selArrCnt = count($selArr);
			expect($selArrCnt);
			reset($selArr);
			
			if($selArrCnt == 1) {
				$selOneKey = key($selArr);
			}
			
			if($selArrCnt == 1) {
				$id = $selArr[$selOneKey];
				$metas = $mvc->fetchField($id, 'meta');
				$form->title = 'Промяна в свойствата на |*<i style="color:#ffffaa">' .  $mvc->getTitleById($selArr[0]) . '</i>';
				$form->FNC('meta', $mvc->getFieldType('meta'), 'caption=Свойства,input');
				$form->setDefault('meta', $metas);
			} else {
				$form->title = 'Промяна на свойствата на |*' . $selArrCnt . '| ' . mb_strtolower($mvc->title);
			
				if(count($canAddMetas)) {
					$addType = cls::get('type_Set');
			
					foreach($canAddMetas as $g => $cnt) {
						$addType->suggestions[$g] = $metas[$g] . " ({$cnt})";
					}
					$form->FNC('addMetas', $addType, 'caption=Добавяне->Свойства,input');
				}
			
				if(count($canDelMetas)) {
					$delType = cls::get('type_Set');
					foreach($canDelMetas as $g => $cnt) {
						$delType->suggestions[$g] = $metas[$g] . " ({$cnt})";
					}
					$form->FNC('delMetas', $delType, 'caption=Премахване->Свойства,input');
				}
			}
			
			$form->toolbar->addSbBtn('Запис');
			if($selArrCnt == 1) {
				$retUrl = array($mvc, 'single', $selArr[$selOneKey]);
			} else {
				$retUrl = array($mvc, 'list');
			}
			
			$form->toolbar->addBtn('Отказ', $retUrl);
			
			$form->input();
			
			if($form->isSubmitted()) {
				$rec = $form->rec;
			
				$changed = 0;
			
				if($selArrCnt == 1) {
					$obj = new stdClass();
					$obj->id = $id;
					$obj->meta = $rec->meta;
			
					if($groups != $rec->meta) {
						$mvc->save($obj, 'meta');
						$changed = 1;
					}
				} else {
					foreach($selArr as $id) {
						$exGroups = $groups = type_Set::toArray($mvc->fetchField($id, 'meta'));
							
						$groups = array_merge($groups, arr::make($rec->addMetas, TRUE));
						$groups = array_diff($groups, arr::make($rec->delMetas, TRUE));
							
						$obj = new stdClass();
						$obj->id = $id;
						$obj->meta = cls::get('type_Set')->fromVerbal($groups);
							
						if($groups != $exGroups) {
							$mvc->save($obj, 'meta');
							$changed++;
						}
					}
				}
			
				if(!$changed) {
					$msg = "|Не бяха променени свойства";
				} elseif($changed == 1) {
					$msg = "|Бяха променени свойствата на|* 1 " . mb_strtolower($mvc->singleTitle);
				} else {
					$msg = "|Бяха променени свойствата на|* {$changed} "  . mb_strtolower($mvc->title);
				}
			
				$res = new Redirect($retUrl, $msg);
			} else {
				$res = $mvc->renderWrapping($form->renderHtml());
				core_Form::preventDoubleSubmission($res, $form);
			}
			 
			return FALSE;
		}
	}
}