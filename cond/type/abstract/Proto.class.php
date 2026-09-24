<?php


/**
 * Базов драйвер за типове на параметри
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Базов тип параметри
 */
abstract class cond_type_abstract_Proto extends core_BaseClass
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
     * Референция към домейна
     *
     * @var core_ObjectReference
     */
    protected $domainObjectReference;


    /**
     * Поле за дефолтна стойност
     */
    protected $defaultField;


    /**
     * Как се индексира стойността за филтриране (num, key, text), празно - не се индексира
     *
     * @see cat_products_ParamIndex
     */
    protected $indexKind;


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
     * @param cond_type_abstract_Proto $Driver
     * @param embed_Manager $Embedder
     * @param $form
     * @return void
     */
    public static function on_AfterInputEditForm(cond_type_abstract_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            
            // Проверка дали дефолтната стойност е допустима за типа
            if (!empty($rec->default)) {
                $Type = $Driver->getType($rec);
                $Type->fromVerbal($rec->default);
                
                if (strlen($Type->error)) {
                    $form->setError('default', 'Стойността по подразбиране не е от допустимите опции');
                }
            }
        }
    }
    
    
    /**
     * Кой може да избере драйвера
     */
    public function canSelectDriver($userId = null)
    {
        return true;
    }
    
    
    /**
     * Връща инстанция на типа
     *
     * @param stdClass    $rec         - запис на параметъра
     * @param mixed       $domainClass - клас на домейна
     * @param mixed       $domainId    - ид на домейна
     * @param NULL|string $value       - стойност
     *
     * @return core_Type - готовия тип
     */
    public function getType($rec, $domainClass = null, $domainId = null, $value = null)
    {
        if (isset($this->baseType)) {
            $type = cls::get($this->baseType);
        }
        
        return $type;
    }


    /**
     * Връща дефолтната стойност на параметъра
     *
     * @param stdClass    $rec         - запис на параметъра
     * @param mixed       $domainClass - клас на домейна
     * @param mixed       $domainId    - ид на домейна
     * @param NULL|string $value       - стойност
     *
     * @return mixed                   - дефолтната стойност (ако има)
     */
    public function getDefaultValue($rec, $domainClass = null, $domainId = null, $value = null)
    {
        if(isset($this->defaultField)){
            if(isset($rec->{$this->defaultField})){

                return $rec->{$this->defaultField};
            }
        }

        return null;
    }


    /**
     * Обръща подадени опции в подходящ текст за вътрешно съхранение
     *
     * @param array|string $options - масив или текст от опции
     *
     * @return string - текстовия вид, в който ще се съхраняват
     */
    public static function options2text($options)
    {
        $options = arr::make($options);
        expect(countR($options));
        
        $opts = '';
        foreach ($options as $k => $v) {
            $opts .= "{$k}={$v}" . PHP_EOL;
        }
        
        return trim($opts);
    }
    
    
    /**
     * Подготвя опциите на типа от вътрешен формат
     *
     * @param string $text - опциите във вътрешен вид
     *
     * @return array $res  - обработените опции
     */
    public static function text2options($text)
    {
        $res = array();
        
        if (!empty($text)) {
            $options = explode(PHP_EOL, trim($text));
            
            foreach ($options as $val) {
                $parts = explode('=', $val, 2);
                $k = $parts[0];
                $v = $parts[1] ?? null;
                if (!isset($v)) {
                    $v = $k;
                }
                
                $res[trim($k)] = trim($v);
            }
        }
        
        return $res;
    }
    
    
    /**
     * Задаване на домейна
     *
     * @param mixed $class
     * @param int   $id
     *
     * @return void;
     */
    public function setObject($class, $id)
    {
        $this->domainObjectReference = new core_ObjectReference($class, $id);
    }


    /**
     * Вербално представяне на стойноста
     *
     * @param stdClass $rec
     * @param mixed    $domainClass - клас на домейна
     * @param mixed    $domainId    - ид на домейна
     * @param string   $value
     *
     * @return mixed
     */
    public function toVerbal($rec, $domainClass, $domainId, $value)
    {
        // Ако има тип, вербалното представяне според него
        $Type = $this->getType($rec, $domainClass, $domainId, $value);
        if ($Type) {
           
            return $Type->toVerbal(trim($value));
        }
        
        return false;
    }


    /**
     * Обработка на стойността при клониране
     *
     * @param stdClass $rec
     * @param mixed $domainClass - клас на домейна
     * @param mixed $domainId - ид на домейна
     * @return string
     */
    public function getReplacementValueOnClone($rec, $domainClass = null, $domainId = null, $value = null)
    {
        return $value;
    }


    /**
     * Параметри функция за вербализиране
     *
     * @param stdClass   $rec    - запис на параметър
     * @param mixed $domainClass - клас на домейна на параметъра
     * @param int   $domainId    - ид на домейна на параметъра
     * @param mixed $newValue    - нова стойност на параметъра
     * @param mixed $oldValue    - стара стойност
     *
     * @return array $res
     */
    public function onParamChanged($rec, $domainClass, $domainId, $newValue, $oldValue) : array
    {
        return array('msg' => null, 'error' => null);
    }


    /**
     * Може ли параметър от този тип да се индексира за филтриране
     *
     * @return bool
     */
    public function canBeIndexed()
    {
        return !empty($this->indexKind);
    }


    /**
     * Връща редовете за индекса на параметрите (@see cat_products_ParamIndex)
     *
     * @param stdClass $rec         - запис на параметъра
     * @param mixed    $domainClass - клас на домейна
     * @param int      $domainId    - ид на домейна
     * @param mixed    $value       - стойност
     * @param array    $langs       - езиците, за които се индексира текст
     *
     * @return array - масив от обекти с полета lg, valueNum, valueKey, valueVerbal, valueId
     */
    public function getIndexValues($rec, $domainClass, $domainId, $value, $langs)
    {
        $value = trim((string) $value);
        if (!strlen($value)) {

            return array();
        }

        switch ($this->indexKind) {
            case 'num':
                return is_numeric($value) ? array($this->makeIndexRow(array('valueNum' => (float) $value))) : array();
            case 'key':
                $res = array();
                foreach ($this->getIndexKeys($value) as $key) {
                    $res[] = $this->makeIndexRow(array('valueKey' => mb_substr($key, 0, 255)));
                }

                return $res;
            case 'text':
                return $this->makeTextIndexRows($rec, $domainClass, $domainId, $value, $langs);
        }

        return array();
    }


    /**
     * Вербално представяне на индексиран ред
     *
     * @param stdClass $rec         - запис на параметъра
     * @param mixed    $domainClass - клас на домейна
     * @param int      $domainId    - ид на домейна
     * @param stdClass $iRec        - запис от индекса
     *
     * @return mixed
     */
    public function getIndexVerbal($rec, $domainClass, $domainId, $iRec)
    {
        if (isset($iRec->valueVerbal)) {

            return type_Varchar::escape($iRec->valueVerbal);
        }

        $value = $iRec->valueKey ?? $iRec->valueNum ?? null;
        if (!isset($value)) {

            return '';
        }

        return $this->toVerbal($rec, $domainClass, $domainId, $value);
    }


    /**
     * Ключовете, които се индексират за стойността
     *
     * @param string $value
     *
     * @return array
     */
    protected function getIndexKeys($value)
    {
        return array($value);
    }


    /**
     * Текстови редове за индекса - по един за език или един общ, ако не зависи от езика
     *
     * @param stdClass $rec         - запис на параметъра
     * @param mixed    $domainClass - клас на домейна
     * @param int      $domainId    - ид на домейна
     * @param mixed    $value       - стойност
     * @param array    $langs       - езици
     * @param int|null $valueId     - ид на обекта, ако стойността е обект
     *
     * @return array
     */
    protected function makeTextIndexRows($rec, $domainClass, $domainId, $value, $langs, $valueId = null)
    {
        $texts = array();
        foreach ($langs as $lg) {
            core_Lg::push($lg);
            Mode::push('text', 'plain');
            try {
                $verbal = $this->toVerbal($rec, $domainClass, $domainId, $value);
            } finally {
                Mode::pop('text');
                core_Lg::pop();
            }

            $verbal = html_entity_decode(strip_tags((string) $verbal), ENT_QUOTES, 'UTF-8');
            $verbal = trim(preg_replace('/\s+/u', ' ', $verbal));
            if (strlen($verbal)) {
                $texts[$lg] = $verbal;
            }
        }

        // Еднаквият текст на всички езици се пази веднъж
        $uniqueTexts = array_unique($texts);
        if (countR($uniqueTexts) == 1 && countR($texts) == countR($langs)) {
            $texts = array('' => reset($uniqueTexts));
        }

        // Ключът е нормализиран като в plg_Search - „Котка“ и „kotka“ дават един ключ
        $res = array();
        foreach ($texts as $lg => $verbal) {
            $key = plg_Search::normalizeText($verbal);
            if (!strlen($key)) continue;

            $res[] = $this->makeIndexRow(array('lg' => $lg, 'valueKey' => mb_substr($key, 0, 255), 'valueVerbal' => mb_substr($verbal, 0, 255), 'valueId' => $valueId));
        }

        return $res;
    }


    /**
     * Ред за индекса с празни полета по подразбиране
     *
     * @param array $fields
     *
     * @return stdClass
     */
    protected function makeIndexRow($fields)
    {
        return (object) ($fields + array('lg' => '', 'valueNum' => null, 'valueKey' => null, 'valueVerbal' => null, 'valueId' => null));
    }
}
