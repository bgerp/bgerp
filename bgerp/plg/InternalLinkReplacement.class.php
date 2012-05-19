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
    static $patternSinle = "/(?'begin'[^a-z0-9а-я]|^){1}(?'link'(http|https):\/\/([^\s]*\/single\/[^(\s)|(\/\?)]*))((\/\?(?'get'[^\s]*))|\/)?/iu";
    
    
    /**
     * Шаблон за намиране на URL' та към folderId и threadId на документите.
     * Шаблона трябва да не започва с буква и/или цифра.
     * Шаблона трябва да завършва с празен символ.
     * 
     * @param begin - Символа преди шаблона
     * @param link  - Целия линк
     * @param get   - GET параметрите след линка, (ако има такива)
     * @param type  - Типа на първия get параметър (threadid или folderid)
     * @param id    - id' то на съответния документ
     */
    static $patternGet = "/(?'begin'[^a-z0-9а-я]|^){1}(?'link'(http|https):\/\/([^\s]*\/doc_[^(\s)|(\?)]*))(\?(?'get'((?'type'threadid|folderid)=(?'id'[0-9]*))[^\s]*)|\/)?/iu";
    
    
    /**
     * Заместваме абсолютните линкове, които сочат към системата, с титлата на документа
     */
    function on_AfterCatchRichElements($mvc, &$html)
    {
        $this->mvc = $mvc;
        $html = preg_replace_callback(array(self::$patternSinle, self::$patternGet), array($this, '_catchUrl'), $html);
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
        //Ако регулярния израз е открил поле type
        if ($match['type']) {
            
            //Ако типа е нишка
            if (strtolower($match['type']) == 'threadid') {
                
                //Вземаме линка на нишката
                $link = $this->getThreadLink($match);
                
            } elseif (strtolower($match['type']) == 'folderid') {
                //Ако типа е папка
                //Вземаме линка на папката
                $link = $this->getFolderLink($match);

            } else {
                
                //Ако не е нито едно от двете
                $link = $match[0];    
            }
        } else {
            
            //Ако няма тип, следователно открития линк е от шаблона за 'single'
            //Вземаме линка към сингъла на документа
            $link = $this->getSingleLink($match);
        }
        
        return $link;
    }
    
    
    /**
     * Връща линка на папката във вербален вид
     * 
     * @param array $match - Масив с данните 
     * 
     * @return $res - Линк
     */
    function getFolderLink($match)
    {
        $Class = cls::get('doc_Folders');

        //Проверяваме за права
        if (!$Class->haveRightFor('single', $match['id'])) return $match['0'];
        
        //Линка
        $link = $match['link'] . '?folderId=' . $match['id'];
        
        //Уникален стринг
        $place = $this->mvc->getPlace(); 
        
        //Ако не сме в текстов режим
        if (!Mode::is('text', 'plain')) {
            
            //Записите
            $rec = $Class->fetch($match['id']);
                        
            //Инстанция на cover класа
            $coverClassInst = cls::get($rec->coverClass);
                            
            //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
            $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
    
            //Атрибути на линка
            $attr1['class'] = 'linkWithIcon';
            $attr1['style'] = 'background-image:url(' . sbf($coverClassInst->singleIcon, '"', $isAbsolute) . ');';    
            $attr1['target'] = '_blank'; 
            
            //Създаваме линк
            $folderLink = ht::createLink(core_Type::escape($rec->title), $link, NULL, $attr1); 
            
            //Добавяме href атрибута в уникалния стинг, който ще се замести по - късно
            $this->mvc->_htmlBoard[$place] = $folderLink->getContent();
             
        } else {
            //Добавяме линка без ret_url
            $this->mvc->_htmlBoard[$place] = $link; 
        }
        
        //Линка със символа в началото
        $res = $match['begin'] . "__{$place}__"; 
         
        //Връщаме линка
        return $res;
    }
    
    
    /**
     * Връща линка на нишката във вербален вид
     * 
     * @param array $match - Масив с данните 
     * 
     * @return $res - Линк
     */
    function getThreadLink($match)
    {
        $Class = cls::get('doc_Threads');
        
        //Проверяваме за права
        if (!$Class->haveRightFor('single', $match['id'])) return $match['0'];
        
        //Линка
        $link = $match['link'] . '?threadId=' . $match['id'];
        
        //Уникален стринг
        $place = $this->mvc->getPlace();
        
        //Ако не сме в текстов режим
        if (!Mode::is('text', 'plain')) {
            
            //id' то на първия документ в системата
            $firstContainerId = $Class->fetchField($match['id'], 'firstContainerId');
            
            //Инстанция на първия документ
            $docProxy = doc_Containers::getDocument($firstContainerId);
            
            //Вземаме колоните на документа
            $docRow = $docProxy->getDocumentRow();
            
            //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
            $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');

            //Атрибути на линка
            $attr1['class'] = 'linkWithIcon';
            $attr1['style'] = 'background-image:url(' . sbf($docProxy->instance->singleIcon, '"', $isAbsolute) . ');';    
            $attr1['target'] = '_blank'; 
            
            //Създаваме линк
            $threadLink = ht::createLink(core_Type::escape($docRow->title), $link, NULL, $attr1);  

            //Добавяме href атрибута в уникалния стинг, който ще се замести по - късно
            $this->mvc->_htmlBoard[$place] = $threadLink->getContent();
        } else {
                
            //Добавяме линка без ret_url
            $this->mvc->_htmlBoard[$place] = $link;        
        }
        
        //Линка със символа в началото
        $res = $match['begin'] . "__{$place}__";
         
        //Връщаме линка
        return $res;
    }
    
    
    /**
     * Връща линка на сингъл' а към документа във вербален вид
     * 
     * @param array $match - Масив с данните 
     * 
     * @return $res - Линк
     */
    function getSingleLink($match)
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

            //Уникален стринг
            $place = $this->mvc->getPlace(); 
            
            //Ако не сме в текстов режим
            if (!Mode::is('text', 'plain')) {
                //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
                $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
    
                //Атрибути на линка
                $attr1['class'] = 'linkWithIcon';
                $attr1['style'] = 'background-image:url(' . sbf($Class->singleIcon, '"', $isAbsolute) . ');';    
                $attr1['target'] = '_blank';    
                
                //Стойността на полето на текстовата част
                $rowField = $Class->fetchField($id, $field);
                
                //Създаваме линк
                $singleLink = ht::createLink(core_Type::escape($rowField), $singleUrl, NULL, $attr1); 
                
                //Добавяме href атрибута в уникалния стинг, който ще се замести по - късно
                $this->mvc->_htmlBoard[$place] = $singleLink->getContent();
                   
            } else {
                
                //Добавяме линка без ret_url
                $this->mvc->_htmlBoard[$place] = $singleUrl;        
            }
            
            //Линка със символа в началото
            $res = $match['begin'] . "__{$place}__";
             
            //Връщаме линка
            return $res;
        }
        
        return $match['0'];
    }
}