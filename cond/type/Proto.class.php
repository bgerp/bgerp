<?php


/**
 * Базов драйвер за типове на параметри
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Базов тип параметри
 */
abstract class cond_type_Proto extends core_BaseClass
{
	
	
	/**
	 * Интерфейси които имплементира
	 */
	public $interfaces = 'cond_ParamTypeIntf';
	
	
	/**
	 * Кой базов тип наследява
	 */
	protected $baseType;
	
	
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
    
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param cond_type_Proto $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    public static function on_AfterInputEditForm(cond_type_Proto $Driver, embed_Manager $Embedder, &$form)
    {
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
    		
    		// Проверка дали дефолтната стойност е допустима за типа
    		if(!empty($rec->default)){
    			$Type = $Driver->getType($rec);
    			$Type->fromVerbal($rec->default);
    			
    			if(strlen($Type->error)){
    				$form->setError('default', 'Стойността по подразбиране не е от допустимите опции');
    			}
    		}
    	}
    }
    
    
    /**
     * Кой може да избере драйвера
     */
    public function canSelectDriver($userId = NULL)
    {
    	return TRUE;
    }
    
    
    /**
     * Връща инстанция на типа
     *
     * @param int $paramId - ид на параметър
     * @return core_Type - готовия тип
     */
    public function getType($paramId)
    {
    	if(isset($this->baseType)){
    		$type = cls::get($this->baseType);
    	}
    	
    	return $type;
    }
    
    
    /**
     * Обръща подадени опции в подходящ текст за вътрешно съхранение
     * 
     * @param array|string $options - масив или текст от опции
     * @return string               - текстовия вид, в който ще се съхраняват
     */
    public static function options2text($options)
    {
    	$options = arr::make($options);
    	expect(count($options));
    
    	$opts = '';
    	foreach ($options as $k => $v){
    		$opts .= "{$k}={$v}" . PHP_EOL;
    	}
    
    	return trim($opts);
    }
    
    
    /**
     * Подготвя опциите на типа от вътрешен формат
     * 
     * @param string $text - опциите във вътрешен вид
     * @return array $res  - обработените опции
     */
    public static function text2options($text)
    {
    	$res = array();
    	
    	if(!empty($text)) {
    		$options = explode(PHP_EOL, trim($text));
    		foreach ($options as $val){
    			list($k, $v) = explode('=', $val);
    			$res[trim($k)] = trim($v);
    		}
    	}
    
    	return $res;
    }
}
