<?php


/**
 * Замества абсолютните линкове в richText полетата, които сочат към системата с тяхното заглавие и икона на файла
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_plg_InternalLinkReplacement extends core_Plugin
{
    
    
    /**
     * Шаблон за намиране на URL' та към single' а на документите.
     * Шаблона трябва да не започва с буква и/или цифра.
     * Шаблона трябва да завършва с празен символ.
     * 
     * @param begin - Символа преди шаблона
     * @param link  - Целия линк
     * @param get   - GET параметрите след линка, (ако има такива)
     */
    static $pattern = "/(?'begin'[^a-z0-9а-я]|^){1}(?'link'(http|https):\/\/([^\s]*\/single\/[^(\s)|(\/\?)]*))((\/\?(?'get'[^\s]*))|\/)?/iu";
    
    
    /**
     * Заместваме абсолютните линкове, които сочат към системата, с титлата на документа
     */
    function on_AfterCatchRichElements($mvc, &$html)
    {
        //Ако сме в текстов режим не правим обработка
        if(Mode::is('text', 'plain')) return;
        
        $this->mvc = $mvc;
        
        //Ако намери съвпадение на регулярния израз изпълнява функцията
        $html = preg_replace_callback(self::$pattern, array($this, '_catchUrl'), $html);
    }


    /**
     * Заменяме линковете от система с абсолютни URL' та
     *
     * @param array $match - Масив с откритите резултати
     *
     * @return string $res - Ресурса, който ще се замества
     */
    function _catchUrl($match)
    {
        //Превръщаме линка в масив   
        $linkArr = explode('/', $match['link']);
        
        //Търсим в масива 'single'
        foreach ($linkArr as $key => $value) {
            
            //Ако стойността не е single прескачаме
            if ($value != 'single') continue;
            
            //Името на класа
            $className = $linkArr[$key - 1];

            //id' то на записа
            $id = $linkArr[$key + 1];

            //Създаваме инстанция на класа
            $Class = core_Cls::createObject($className);

            if (!$Class) continue;
            
            //Проверяваме за права
            if (!$Class->haveRightFor('single', $id)) continue;
            
            //Кое поле е избрано да се показва, като текст
            $field = $Class->rowToolsSingleField;
            
            //Ако няма, прескачаме
            if (!$field) continue;

            //URL към документа
            $singleUrl = toUrl(array(
                $Class,
                'single',
                'id' => $id,
                'ret_url' => FALSE
            ), 'absolute');

            //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
            $isAbsolute = Mode::is('text', 'xtml') || Mode::is('printing');

            //Атрибути на линка
            $attr1['class'] = 'linkWithIcon';
            $attr1['style'] = 'background-image:url(' . sbf($Class->singleIcon, '"', $isAbsolute) . ');';    
            $attr1['target'] = '_blank';    
            
            //Стойността на полето на текстовата част
            $rowField = $Class->fetchField($id, $field);
            
            //Създаваме линк
            $singleLink = ht::createLink($rowField, $singleUrl, NULL, $attr1); 
            
            //Уникален стринг
            $place = $this->mvc->getPlace();
    
            //Добавяме href атрибута в уникалния стинг, който ще се замести по - късно
            $this->mvc->_htmlBoard[$place] = $singleLink->getContent();
            
            $res = $match['begin'] . "__{$place}__";
             
            //Връщаме линка
            return $res;
        }
        
        return $match['0'];
    }
}