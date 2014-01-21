<?php


/**
 * Клас  'type_ComplexType' - Тип рендиращ два инпута за рационални числа
 * на един ред. Записва ги като стринг с "|" разделяща ги
 * 
 * Параметри:
 * 		left  - placeholder на лявата част
 * 		right - placeholder на дясната част
 *
 *
 * @category  ef
 * @package   type
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_ComplexType extends type_Varchar {
    
    
    /**
     * MySQL тип на полето в базата данни
     */
    public $dbFieldType = 'varchar';
    
    
    /**
     * type_Double
     */
    private $double;
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        parent::init($params);
        
        // Инстанциране на type_Double
        $this->double = cls::get('type_Double', $params);
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        // Ширина по дефолт
    	setIfNot($attr['size'], '6em');
    	
    	// Разбиване на стойноста и извличане на лявата и дясната част
        if($value) {
        	extract(type_ComplexType::getParts($value));
        }
        
        // Подготовка на масива с атрибутите за лявата част
        setIfNot($attr['placeholder'], $this->params['left']);
        $attr['value'] = $left;
    	
        // Рендиране на Double поле за лявата част
        $inputLeft = $this->double->renderInput($name . '[cL]', NULL, $attr);
        
    	// Подготовка на масива с атрибутите за лявата част
    	unset($attr['placeholder']);
    	setIfNot($attr['placeholder'], $this->params['right']);
        $attr['value'] = $right;
        
        // Рендиране на Double поле за лявата част
    	$inputRight = " " . $this->double->renderInput($name . '[cR]', NULL, $attr);
        
    	// Добавяне на дясната част към лявата на полето
        $inputLeft->append($inputRight);
        
        // Връщане на готовото поле
        return $inputLeft;
    }
    
    
    /**
     * Конвертира от вербална стойност
     */
    function fromVerbal($value)
    {
        // Ако няма стойност
    	if(!is_array($value)) return NULL;
        
    	// Извличане на лявата и дясната част на полето
        $vLeft = ($value['cL']) ? trim($value['cL']) : NULL;
        $vRight = ($value['cR']) ? trim($value['cR']) : NULL;
        
        // Ако има поне едно сетнато поле
        if(isset($vLeft) || isset($vRight)){
        	
	        // Ако има поне едно празно поле, сетва се грешка
	        if(empty($vLeft) || empty($vRight)){
	        	$this->error = "Едно от двете полета е празно";
	        	
	        	return FALSE;
	        }
        	
        	// Преобразуване на числата в състояние подходящо за запис
	        $vLeft = $this->double->fromVerbal($vLeft);
	        $vRight = $this->double->fromVerbal($vRight);
	        
	        // Трябва да са въведени валидни double числа
	        if(empty($vLeft) || empty($vRight)){
	        	$this->error = "Не са въведени валидни числа";
	        	
	        	return FALSE;
	        }
	        
	        // В полето се записва стринга '[лява_част]|[дясна_част]'
	        return $vLeft . "|" . $vRight;
	    }

	    // Ако няма нито едно сетнато поле, не се прави нищо
	    return NULL;
    }
    
    
    /**
     * Форматира числото в удобна за четене форма
     */
    function toVerbal($value)
    {
    	// Ако няма стойност
        if(!strlen($value)) return NULL;
        
        // Извличане на лявата и дясната част на записа
        extract(type_ComplexType::getParts($value));
        
        $res = '';
        
        // Ако лявата част има има се показва
        if($this->params['left']){
        	$res .= $this->params['left'] . ": ";
        }
        
        // Показване на лявата част във вербален вид
        $res .= $this->getVerbalPart($left) . "; ";
        
        // Ако дясната част има има се показва
    	if($this->params['right']){
        	$res .= $this->params['right'] . ": ";
        }
        
        // Показване на дясната част във вербален вид
        $res .= $this->getVerbalPart($right);
        
        // Връщане на вебалното представяне
        return $res;
    }
    
    
    /**
     * Помощен метод връщащ вербалното представяне на лявата или дясната част
     * 
     * @param double $double  - стойността на лявата или дясната част
     * @return double - вербалното представяне
     */
    private function getVerbalPart($double)
    {
    	// Стойноста се закръгля до броя на числа след десетичната запетая
    	setIfNot($this->double->params['decimals'], $this->params['decimals'], strlen(substr(strrchr($double, "."), 1)));
    	
    	// Връщане на вербалното представяне
    	$verbal = $this->double->toVerbal($double);
    	unset($this->double->params['decimals']);
    	
    	return $verbal;
    }
    
    
    /**
     * Извличане на лявата и дясната част на стойността
     * 
     * @param varchar $value - запис от вида : "число|число"
     * @return array $parts - масив с извлечена лявата и дясната част
     */
    public static function getParts($value)
    {
    	//Тук ще се събират лявата ид ясната част
    	$parts = array();
    	
    	// Извличане на съответните стойностти
    	if(is_array($value)){
    		$parts['left'] = $value['cL'];
    		$parts['right'] = $value['cR'];
    	} else {
    		list($parts['left'], $parts['right']) = explode('|', $value);
    	}
    	
    	// Трябва да са точно '2'
    	expect(count($parts) == 2);
    	
    	// Връщане на масива
    	return $parts;
    }
}