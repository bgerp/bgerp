<?php


/**
 * Клас  'type_ComplexType' - Тип рендиращ два инпута за рационални числа
 * на един ред. Записва ги като стринг с "|" разделяща ги
 *
 * Параметри:
 * left         - placeholder на лявата част
 * right        - placeholder на дясната част
 * require enum(left,right,both) - изискване коя част задължително да е попълнена
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
class type_ComplexType extends type_Varchar
{
    
    
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
    public function init($params = array())
    {
        parent::init($params);
        
        setIfNot($this->params['require'], 'left');
        
        // Инстанциране на type_Double
        $this->double = cls::get('type_Double', $params);
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        // Разбиване на стойноста и извличане на лявата и дясната част
        if ($value) {
            extract(type_ComplexType::getParts($value));
        }
        
        // Подготовка на масива с атрибутите за лявата част
        setIfNot($attr['placeholder'], $this->params['left']);
        $attr['value'] = $left;
        
        // Рендиране на Double поле за лявата част
        $inputLeft = $this->double->renderInput($name . '[cL]', null, $attr);
        
        // Подготовка на масива с атрибутите за лявата част
        unset($attr['placeholder']);
        setIfNot($attr['placeholder'], $this->params['right']);
        $attr['value'] = $right;
        
        // Рендиране на Double поле за лявата част
        $inputRight = ' ' . $this->double->renderInput($name . '[cR]', null, $attr);
        
        // Добавяне на дясната част към лявата на полето
        $inputLeft->append($inputRight);
        
        // Връщане на готовото поле
        return $inputLeft;
    }
    
    
    /**
     * Конвертира от вербална стойност
     */
    public function fromVerbal($value)
    {
        // Ако няма стойност
        if (!is_array($value)) {
            return;
        }
        
        // Извличане на лявата и дясната част на полето
        $vLeft = (strlen($value['cL'])) ? trim($value['cL']) : null;
        $vRight = (strlen($value['cR'])) ? trim($value['cR']) : null;
        
        // Ако има поне едно сетнато поле
        if (isset($vLeft) || isset($vRight)) {
            
            // Взависимост от параметъра require се проверява попълнени ли са полетата
            switch ($this->params['require']) {
                case 'left':
                    if (empty($vLeft)) {
                        $this->error = 'Лявото поле трябва да е попълнено';
                    }
                    break;
                case 'right':
                    if (empty($vRight)) {
                        $this->error = 'Дясното поле трябва да е попълнено';
                    }
                    break;
                case 'both':
                    if (empty($vLeft) || empty($vRight)) {
                        $this->error = 'Двете полета трябва да са попълнени';
                    }
                    // no break
                case 'one':
                    break;
            }
            
            // Ако има грешка, се излиза от ф-ята
            if ($this->error) {
                return false;
            }
            
            // Преобразуване на числата в състояние подходящо за запис
            $vLeft = $this->double->fromVerbal($vLeft);
            $vRight = $this->double->fromVerbal($vRight);
            
            // Трябва да са въведени валидни double числа, или празен стринг
            if ($vLeft === false || $vRight === false) {
                $this->error = 'Не са въведени валидни числа';
                
                return false;
            }
            
            // В полето се записва стринга '[лява_част]|[дясна_част]'
            return $vLeft . '|' . $vRight;
        }

        // Ако няма нито едно сетнато поле, не се прави нищо
    }
    
    
    /**
     * Форматира числото в удобна за четене форма
     */
    public function toVerbal($value)
    {
        // Ако няма стойност
        if (!strlen($value)) {
            return;
        }
        
        // Извличане на лявата и дясната част на записа
        extract(type_ComplexType::getParts($value));
        
        $res = '';
        
        // Ако лявата част има стойност
        if (strlen($left)) {
            // Ако лявата част има има се показва
            if ($this->params['left']) {
                $res .= $this->params['left'] . ': ';
            }
            
            $res .= $this->getVerbalPart($left);
        }
        
        // Ако дясната част има стойност
        if (strlen($right)) {
            $res .= (strlen($left)) ? '; ' : '';
            
            // Ако дясната част има има се показва
            if ($this->params['right']) {
                $res .= $this->params['right'] . ': ';
            }
            
            $res .= $this->getVerbalPart($right);
        }
        
        // Връщане на вебалното представяне
        return $res;
    }
    
    
    /**
     * Помощен метод връщащ вербалното представяне на лявата или дясната част
     *
     * @param  double $double - стойността на лявата или дясната част
     * @return double - вербалното представяне
     */
    private function getVerbalPart($double)
    {
        // Стойноста се закръгля до броя на числа след десетичната запетая
        setIfNot($this->double->params['decimals'], $this->params['decimals'], strlen(substr(strrchr($double, '.'), 1)));
        
        // Връщане на вербалното представяне
        $verbal = $this->double->toVerbal($double);
        unset($this->double->params['decimals']);
        
        return $verbal;
    }
    
    
    /**
     * Извличане на лявата и дясната част на стойността
     *
     * @param  string $value - запис от вида : "число|число"
     * @return array  $parts - масив с извлечена лявата и дясната част
     */
    public static function getParts($value)
    {
        // Тук ще се събират лявата и дясната част
        $parts = array();
        
        // Извличане на съответните стойностти
        if (is_array($value)) {
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
