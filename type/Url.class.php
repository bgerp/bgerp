<?php


/**
 * Клас  'type_Url' - Тип за URL адреси
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class type_Url extends type_Varchar
{
    /**
     * Дължина на полето в mySql таблица
     */
    public $dbFieldLen = 255;
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    public function toVerbal($value)
    {
        // Когато стойността е празна, трябва да върнем NULL
        $value = trim($value);
        
        if (empty($value)) {
            
            return;
        }
        
        if (!Mode::is('text', 'plain')) {
            $attr = array();
            $attr['target'] = '_blank';
            $attr['class'] = 'out';
            if (!strpos($value, '://')) {
                $url = 'http://' . $value;
            } else {
                $url = $value;
            }
            
            $value = HT::createLink(core_Url::decodeUrl($value), $url, false, $attr);
        }
        
        return $value;
    }
    
    
    /**
     * Добавя атрибут за тип = url, ако изгледа е мобилен
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * Превръща URL-то от вербално представяне, към вътрешно представяне
     */
    public function fromVerbal($value)
    {
        if (!trim($value)) {
            
            return;
        }
        
        if (strpos($value, '://') === false) {
            $value = 'http://' . $value;
        }
        
        $res = $this->isValid($value);
        
        return $value;
    }
    
    
    /**
     * Проверява и коригира въведеното URL
     */
    public function isValid($value)
    {
        $value = trim($value);
        $value = strtolower($value);
        
        if (!$value) {
            
            return;
        }
        
        $value = $this->findScheme($value);
        
        $res = parent::isValid($value);
        
        if (count($res)) {
            
            return $res;
        }
        
        if (!core_Url::isValidUrl($value)) {
            $res['error'] = 'Невалидно URL.';
            
            return $res;
        }
    }
    
    
    /**
     * Връща цялото URL
     */
    public function findScheme($value)
    {
        $pattern = '/^\b[a-z]*\b:\/\//';
        preg_match($pattern, $value, $match);
        
        if (!count($match)) {
            $pattern = '/^\b[a-z]*\b./';
            preg_match($pattern, $value, $matchSub);
            $scheme = 'http';
            
            if (count($matchSub)) {
                $subDom = $matchSub[0];
                
                if ($subDom == 'ftp.') {
                    $scheme = 'ftp';
                }
            }
            $scheme = $scheme . '://';
            $value = $scheme . $value;
        }
        
        return $value;
    }
    
    
    /**
     * Ако е зададен параметър, тогава валидираме URL-to
     */
    public function validate($url, &$result)
    {
        //Проверяваме дали URL' то е ftp
        if (stripos($url, 'ftp://') !== false) {
            
            //Правим опит да се свържем с FTP акаунта.
            $parsedFtp = parse_url($url);
            $ftp = $parsedFtp['scheme'] . '://' . $parsedFtp['host'];
            $ftpId = @ftp_connect($parsedFtp['host'], false, 3);
        } else {
            
            //Правим опит да се свържем с http акаунта
            $arr = array('http' => array(
                'timeout' => 2)
            );
            stream_context_set_default($arr);
            $headers = @get_headers($url);
        }
        
        //Проверяваме дали има грешки при валидиране
        if ((!$headers) && (!$ftpId)) {
            $result['warning'] = "URL' то, което сте въвели не може да бъде валидиран.";
        }
        
        //Проверяваме хедъри-те за върнатия резултат
        if ($headers) {
            $explode = explode(' ', $headers[0], 3);
        }
        
        //Ако страницата върне 404, тогава показва warning
        $number = substr(trim($explode[1]), 0, 1);
        
        if ($number == 4) {
            $result['warning'] = 'Възможен проблем с това URL.';
        }
    }
}
