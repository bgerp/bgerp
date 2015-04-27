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
	public function exportCsv($mvc, &$rec)
    {

        $conf = core_Packs::getConfig('core');

        if (count($rec->data->recs) > $conf->EF_MAX_EXPORT_CNT) {
            redirect(array($mvc), FALSE, "Броят на заявените записи за експорт надвишава максимално разрешения|* - " . $conf->EF_MAX_EXPORT_CNT, 'error');
        }

        // Масива с избраните полета за export
        //$exportFields = $mvc->selectFields("#export");
        //bp($exportFields);

        /* за всеки ред */
        foreach ($rec->data->recs as $rec) {


            // Всеки нов ред ва началото е празен
            $rCsv = '';

            /* за всяка колона */
            foreach ($rec as $field => $caption) {
                $type = $mvc->fields[$field]->type;

                if ($type instanceof type_Key) {
                    $value = $mvc->getVerbal($rec, $field);
                } else {
                    $value = $rec->{$field};


                    // escape
                    if (preg_match('/\\r|\\n|,|"/', $value)) {
                        $value = '"' . str_replace('"', '""', $value) . '"';
                    }

                    $rCsv .= "," . $value;
                }

                /* END за всяка колона */

                $csv .= $rCsv . "\n";
            }

            echo $csv;
        }
    }
}