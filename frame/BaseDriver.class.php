<?php



/**
 * Базов клас за наследяване от другите драйвери
 *
 *
 * @category  bgerp
 * @package   frame
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class frame_BaseDriver extends core_BaseClass
{
	
	
	/**
	 * Вътрешната форма
	 * 
	 * @param mixed $innerForm
	 */
	protected $innerForm;
	
	
	/**
	 * Вътрешното състояние
	 *
	 * @param mixed $innerState
	 */
	protected $innerState;
	
	
	
	/**
	 * Задава вътрешната форма
	 * 
	 * @param mixed $innerForm
	 */
	public function setInnerForm($innerForm)
	{
		$this->innerForm = $innerForm;
	}
	
	
	/**
	 * Задава вътрешното състояние
	 * 
	 * @param mixed $innerState
	 */
	public function setInnerState($innerState)
	{
		$this->innerState = $innerState;
	}
	
	
	/**
	 * След активация на репорта
	 */
	public static function on_AfterActivation($mvc, &$is, &$rec)
	{
		$is = $mvc->prepareInnerState();
		frame_Reports::save($rec);
	}
	
	
	/**
	 * След оттегляне на репорта
	 */
	public static function on_AfterReject($mvc, &$is, &$rec)
	{
		$is = $mvc->prepareInnerState();
		frame_Reports::save($rec);
	}
	
	
	/**
	 * След възстановяване на репорта
	 */
	public static function on_AfterRestore($mvc, &$is, &$rec)
	{
		if($rec->state == 'draft' || $rec->state == 'pending'){
			unset($rec->data);
			frame_Reports::save($rec);
		}
	}
	
	
	/**
	 * Можели вградения обект да се избере
	 */
	public function canSelectInnerObject($userId = NULL)
	{
		return core_Users::haveRole($this->canSelectSource, $userId);
	}


	/**
	 * Подготвя данните необходими за показването на вградения обект
	 *
	 * @param core_Form $innerForm
	 * @param stdClass $innerState
	 */
	public function prepareEmbeddedData_()
	{
		// Ако има вътрешно състояние него връщаме
		if(!empty($this->innerState)){
			return $this->innerState;
		}
		 
		return $this->prepareInnerState();
	}
	
	
	/**
	 * Връща дефолт заглавието на репорта
	 */
	public function getReportTitle()
	{
		$titleArr = explode('»', $this->title);
		if(count($titleArr) == 2){
			
			return $titleArr[1];
		}
		
		return $this->title;
	}
	
	
	/**
	 * Променя ключовите думи
	 * 
	 * @param string $searchKeywords
	 */
	public function alterSearchKeywords(&$searchKeywords)
	{
		
	}
	
	
	/**
	 * Скрива полетата, които потребител с ниски права не може да вижда
	 */
	public function hidePriceFields()
	{
		
	}
	
	
	/**
	 * Коя е най-ранната дата когато може да се активира отчета
	 * 
	 * @return datetime
	 */
	public function getEarlyActivation()
	{
		return dt::now();
	}
	
	
	/**
	 * Рендира вътрешната форма като статична форма в подадения шаблон
	 * 
	 * @param core_ET $tpl - шаблон
	 * @param string $placeholder - плейсхолдър
	 */
	protected function prependStaticForm(core_ET &$tpl, $placeholder = NULL)
	{
		$form = cls::get('core_Form');
		
		$this->addEmbeddedFields($form);
		$form->rec = $this->innerForm;
		$this->prepareEmbeddedForm($form);
		
		$form->class = 'simpleForm';
		 
		$tpl->prepend($form->renderStaticHtml(), $placeholder);
	}


	/**
	 * Подготвя формата за въвеждане на данни за вътрешния обект
	 *
	 * @param core_Form $form
	 */
	public function prepareEmbeddedForm(core_Form &$form)
	{
		
	}


	/**
	 * Ако имаме в url-то export създаваме csv файл с данните
	 * 
	 * @param core_Mvc $mvc
	 * @param stdClass $rec
	 */
	public function exportCsv()
    {

    }

}