<?php


/**
 * Клас  'type_Urls' - Тип за много връзки
 *
 * Тип, който ще позволява въвеждането на много връзки в едно поле
 *
 *
 * @category  bgerp
 * @package   type
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class type_Urls extends type_Varchar
{

    /**
     * @var string
     */
    public static $defaultDelimiter = ', ';


    /**
     * Шаблон за разделяне на линковете
     */
    public static $pattern = '/\s?[,; ]\s?/';
    
    
    /**
     * Само валидни URL-та
     *
     * @var int
     */
    const VALID = 1;


    /**
     * Само невалидни URL-та
     *
     * @var int
     */
    const INVALID = 2;


    /**
     * Всички "URL-та" - валидни + невалидни
     *
     * @var int
     */
    const ALL = 0;
    
    
    /**
     * Инициализиране на типа
     *
     * @param array $params
     */
    public function init($params = array())
    {
        setIfNot($params['params']['ci'], 'ci');
        setIfNot($params['params']['inputmode'], 'url');
        setIfNot($params['params']['urlDelimiter'], static::$defaultDelimiter);
        parent::init($params);
    }

    
    /**
     * Проверява зададената стойност дали е допустима за този тип.
     *
     * @params string @value - Стойността, която ще се проверява
     *
     * @return array - Масив с грешките, които са открити
     */
    public function isValid($value)
    {
        //Ако няма въведено нищо връщаме резултата
        if (!trim($value)) {
            
            return;
        }
        
        //Проверяваме за грешки
        $res = parent::isValid($value);
        
        //Ако има грешки връщаме резултата
        if (countR($res)) {
            
            return $res;
        }
        
        if (countR($invalidUrls = self::getInvalidUrls($value))) {
            $res['error'] = parent::escape('Стойността не е валидно URL|*: ' . implode($this->params['urlDelimiter'], $invalidUrls));
            
            // Проверка за опити за хакване
            core_HackDetector::check($value, $this->params['hackTolerance'] ?? null);
        }
        
        return $res;
    }
    

    /**
     * Превръща вербална стойност с имейл към вътрешно представяне
     */
    public function fromVerbal($value)
    {
        $value = trim($value);
     
        if (empty($value)) {
            
            return;
        }
   
        $urlsArr = preg_split(self::$pattern, $value, null, PREG_SPLIT_NO_EMPTY);
        foreach($urlsArr as &$url) {
            if(stripos($url, '://') === false) {
                $url = 'http://' . $url;
            }
        }
        $value = implode(', ', $urlsArr);
   
        return parent::fromVerbal($value);
    }

    
    /**
     * Преобразува полетата за много URL-та в човешки вид
     *
     * @params strign $str - Стойността, която ще се преобразува
     *
     * @return null|string - Преобразуваната стойност
     */
    public function toVerbal_($str)
    {
        //Тримваме полето
        $str = trim($str);
        
        //ако е празен, връщаме NULL
        if (empty($str)) {
            
            return;
        }
        
        //Вземаме всички URL-та
        $urlsArr = self::toArray($str, self::ALL);

        $links = array();
        
        $typeUrl = cls::get('type_Url', array('params' => $this->params));
        foreach ($urlsArr as $url) {
            if (core_Url::isValidUrl($url)) {
                $links[] = $typeUrl->toVerbal($url);
            }
        }

        return implode($this->params['urlDelimiter'], $links);
    }


    /**
     * Преобразува стринг, съдържащ URL-та към масив от URL-та.
     *
     * @param string $str
     * @param int    $only - кои "URL-та" да върне:
     *                     o ALL     - всички;
     *                     o VALID   - само валидните;
     *                     o INVALID - само невалидните
     *
     * @return array масив от валидни URL-та
     */
    public static function toArray($str, $only = self::VALID)
    {
        //Масив с всички URL-та
        $urlsArr = preg_split(self::$pattern, $str, null, PREG_SPLIT_NO_EMPTY);

        if ($only != self::ALL) {
            foreach ($urlsArr as $i => $url) {
                if (core_Url::isValidUrl($url) != ($only == self::VALID)) {
                    unset($urlsArr[$i]);
                }
            }

            $urlsArr = array_values($urlsArr);
        }

        return $urlsArr;
    }
    
    
    /**
     * Превръща масива с URL-та в стринг
     *
     * @param array $arr - Масив с URL-та
     *
     * @return string - Стринг с URL-та
     */
    public static function fromArray($arr)
    {

        return implode(static::$defaultDelimiter, $arr);
    }
    
    
    /**
     * Връща всички невалидни URL-та в стринга
     *
     * @params string $str - Стринг с URL-та
     *
     * @return array - Масив с невалидни URL-та
     */
    public static function getInvalidUrls($str)
    {

        return self::toArray($str, self::INVALID);
    }
    
    
    /**
     * Добавя ново URL в края на списък с URL-та. Ако новия е в списъка - не го дублира.
     *
     * @param string $str - Стринг с URL-та
     * @param string $url - Ново URL
     *
     * @return string - Стринг с URL-та
     */
    public static function append($str, $url)
    {
        $urls = static::toArray($str, self::ALL);
        $urls[] = $url;

        $urls = array_unique($urls);
        
        return implode(static::$defaultDelimiter, $urls);
    }
    
    
    /**
     * Добавя ново URL към началото списък с URL-та. Ако новия е в списъка - не го дублира.
     *
     * @param string $str - Стринг с URL-та
     * @param string $url - Ново URL
     *
     * @return string - Стринг с URL-та
     */
    public static function prepend($str, $url)
    {
        $urls = static::toArray($str, self::ALL);
        array_unshift($urls, $url);

        $urls = array_unique($urls);
        
        return implode(static::$defaultDelimiter, $urls);
    }
}
