<?php

/**
 * Базов драйвер за драйвер на артикул
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Базов драйвер за драйвер на артикул
 */
abstract class cat_ProductDriver extends core_BaseClass
{
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectDriver = 'ceo, cat, sales';
	
	
	/**
	 * Интерфейси които имплементира
	 */
	public $interfaces = 'cat_ProductDriverIntf';

	
	/**
	 * Мета данни по подразбиране
	 * 
	 * @param strint $defaultMetaData
	 */
	protected $defaultMetaData;
	
	
	/**
     * Икона за единичния изглед
     */
    protected $icon = 'img/16/wooden-box.png';
	
	
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
    
    }
    
    
    /**
     * Кой може да избере драйвера
     */
    public function canSelectDriver($userId = NULL)
    {
    	return core_Users::haveRole($this->canSelectDriver, $userId);
    }
    
    
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param cat_ProductDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm(cat_ProductDriver $Driver, embed_Manager $Embedder, &$data)
	{
		$form = &$data->form;
		
		// Намираме полетата на формата
		$fields = $form->selectFields();
		
		if(is_array($data->driverParams) && count($data->driverParams)){
			
			// Ако в параметрите има стойност за поле, което е във формата задаваме му стойността
			foreach ($fields as $name => $fld){
				if(isset($data->driverParams[$name])){
					$form->setDefault($name, $data->driverParams[$name]);
				}
			}
		}
		
		// Ако има полета
		if(count($fields)){
			
			// За всички полета
			foreach ($fields as $name => $fld){
					
				// Ако има атрибут display
				$display = $form->getFieldParam($name, 'display');
					
				// Ако е 'hidden' и има зададена стойност, правим полето скрито
				if($display === 'hidden'){
					if(!is_null($form->rec->$name)){
						$form->setField($name, 'input=hidden');
					}
				} elseif($display === 'readOnly'){
			
					// Ако е 'readOnly' и има зададена стойност, правим го 'само за четене'
					if(!is_null($form->rec->$name)){
						$form->setReadOnly($name);
					}
				}
			}
		}
	}
	
	
	/**
	 * Връща счетоводните свойства на обекта
	 */
	public function getFeatures($productId)
	{
		return array();
	}

	
	/**
	 * Кои опаковки поддържа продукта
	 *
	 * @param array $metas - кои са дефолтните мета данни от ембедъра
	 * @return array $metas - кои са дефолтните мета данни
	 */
	public function getDefaultMetas($metas)
	{
		// Взимаме дефолтните мета данни от ембедъра
		$metas = arr::make($metas, TRUE);
	
		// Ако за драйвера има дефолтни мета данни, добавяме ги към тези от ембедъра
		if(!empty($this->defaultMetaData)){
			$metas = $metas + arr::make($this->defaultMetaData, TRUE);
		}
	
		return $metas;
	}
	

	/**
	 * Връща стойността на параметъра с това име
	 * 
	 * @param string $name - име на параметъра
	 * @param string $id   - ид на записа
	 * @return mixed - стойност или FALSE ако няма
	 */
	public function getParamValue($name, $id)
	{
		return FALSE;
	}
	
	
	/**
	 * Подготовка за рендиране на единичния изглед
	 *
	 * @param cat_ProductDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $res
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareSingle(cat_ProductDriver $Driver, embed_Manager $Embedder, &$res, &$data)
	{
		$data->Embedder = $Embedder;
		$data->isSingle = TRUE;
		$data->documentType = 'internal';
		$Driver->prepareProductDescription($data);
		
        if($Embedder->className == 'cat_Products') {
            $data->components = array();
            cat_Boms::prepareComponents($data->rec->id, $data->components, $data->documentType);
        }
	}
	
	
	/**
	 * Подготвя данните за показване на описанието на драйвера
	 *
	 * @param stdClass $data
	 * @return void
	 */
	public function prepareProductDescription(&$data)
	{
	}
	
	
	/**
	 * Кои документи са използвани в полетата на драйвера
	 */
	public function getUsedDocs()
	{
		return FALSE;
	}
	
	
	/**
	 * Връща дефолтната основна мярка, специфична за технолога
	 *
	 * @param string $measureName - име на мярка
	 * @return FALSE|int - ид на мярката
	 */
	public function getDefaultUom($measureName = NULL)
	{
		return FALSE;
	}
	
	
	/**
	 * Връща иконата на драйвера
	 * 
	 * @return string - пътя към иконата
	 */
	public function getIcon()
	{
		return $this->icon;
	}


	/**
	 * След рендиране на единичния изглед
	 *
	 * @param cat_ProductDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param core_ET $tpl
	 * @param stdClass $data
	 */
	public static function on_AfterRenderSingle(cat_ProductDriver $Driver, embed_Manager $Embedder, &$tpl, $data)
	{
		$nTpl = $Driver->renderProductDescription($data);
		$tpl->append($nTpl, 'innerState');
		
		if(count($data->components)){
			$componentTpl = cat_Products::renderComponents($data->components);
			$tpl->append($componentTpl, 'COMPONENTS');
		}
	}
	
	
	/**
	 * Рендиране на описанието на драйвера
	 *
	 * @param stdClass $data
	 * @return core_ET $tpl
	 */
	protected function renderProductDescription($data)
	{
		$tpl = new ET(tr("|*
                    <div class='groupList'>
                        <div class='richtext' style='margin-top: 5px;'>{$this->singleTitle}</div>
						<table class = 'no-border small-padding' style='margin-bottom: 5px;'>
							[#INFO#]
						</table>
					<div>
					[#ROW_AFTER#]
					[#COMPONENTS#]
				"));
		
        $form = cls::get('core_Form');
        $this->addFields($form);
		$driverFields = $form->fields;

		if(is_array($driverFields)){
			foreach ($driverFields as $name => $field){
				if(isset($data->row->{$name})){

                    $caption = $field->caption;

                    if(strpos($caption, '->')) {
                        list($group, $caption) = explode('->', $caption);
                        if($group != $lastGroup) {
                            $group = tr($group);
                            $dhtml = "<tr><td colspan='3' style='padding-left:0px;padding-top:5px;'>{$group}</td></td</tr>";
                            $tpl->append($dhtml, 'INFO');
                        }

                        $lastGroup = $group;
                    }

                    $caption = tr($caption);
					
					$dhtml = "<tr><td>&nbsp;-&nbsp;</td> <td> {$caption}:</td><td style='padding-left:5px'>{$data->row->$name} {$field->unit}</td</tr>";
					$tpl->append($dhtml, 'INFO');
				}
			}
		}
		
		return $tpl;
	}
	
	
	/**
	 * Как да се казва дефолт папката където ще отиват заданията за артикулите с този драйвер
	 */
	public function getJobFolderName()
	{
		$title = core_Classes::fetchField($this->getClassId(), 'title');
		
		return "Задания за " . mb_strtolower($title);
	}
	
	
	/**
	 * Връща информация за какви дефолт задачи могат да се задават към заданието за производство
	 * 
	 * @return array $drivers - масив с информация за драйверите, с ключ името на масива
	 * 				    -> title        - дефолт име на задачата
	 * 					-> driverClass  - драйвър на задача
	 * 					-> priority     - приоритет (low=Нисък, normal=Нормален, high=Висок, critical)
	 */
	public function getDefaultJobTasks()
	{
		return array();
	}
	
	
	/**
	 * Връща дефолтното име на артикула
	 * 
	 * @param stdClass $rec
	 * @return NULL|string
	 */
	public function getProductTitle($rec)
	{
		return NULL;
	}
	
	
	/**
	 * Връща данни за дефолтната рецепта за артикула
	 * 
	 * @param stdClass $rec - запис
	 * @return FALSE|array
	 * 			['quantity'] - К-во за което е рецептата
	 * 			['expenses'] - % режийни разходи
	 * 			['materials'] array
	 * 				 o code          string  - Код на материала
     * 				 o baseQuantity  double  - Начално количество на вложения материал
     * 				 o propQuantity  double  - Пропорционално количество на вложения материал
     * 				 o waste         boolean - Дали материала е отпадък
     * 				 o stageName']   string  - Име на производствения етап
	 * 				
	 */
	public function getDefaultBom($rec)
	{
		return FALSE;
	}
}