<?php


/**
 * Замества абсолютните линкове в richText полетата, които сочат към системата с тяхното заглавие и икона на файла
 *
 * Замества следните URL-та:
 *
 *     o doc_Containers/list/?threadId=??????
 *     o doc_Threads/list/?folderId=??????
 *     o [mvc]/single/[id]
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
     * Заместваме абсолютните линкове, които сочат към системата, с титлата на документа
     */
    function on_AfterCatchRichElements1($mvc, &$html)
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
    function _1catchUrl($match)
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
    

    function on_BeforeInternalUrl($rt, &$res, $url, $title, $rest)
    {
         // bp($rt, $res, $url, $title, $rest);

        $rest = trim($rest, '/');
        
        $restArr = explode('/', $rest);

        $params = array();
        
        $lastPart = $restArr[count($restArr)-1];

        if($lastPart{0} == '?') {
           $lastPart = ltrim($lastPart, '?'); 
           $lastPart = str_replace('&amp;', '&', $lastPart);
           parse_str($lastPart, $params);
           unset($restArr[count($restArr)-1]);
        }

        setIfNot($params['Ctr'], $restArr[0]);

        setIfNot($params['Act'], $restArr[1], 'default');

        if(count($restArr) % 2) {
            setIfNot($params['id'], $restArr[2]);
            $pId = 3;
        } else {
            $pId = 2;
        }
        
        // Добавяме останалите параметри, които са в часта "път"
        while($restArr[$pId]) {
            $params[$restArr[$pId]] = $params[$restArr[$pId+1]];
            $pId++;
        }

        // Папки
        if($params['Ctr'] == 'doc_Threads' && ($params['Act'] == 'list' || $params['Act'] == 'default')) {

        }

        // Нишки
        // Сингле

       // $res = ' #EML1 ';
        $img = sbf('img/16/folder.png', '');
        
       // $res = ht::createLink('Papka', array('doc_Folders'), NULL, 'ef_icon=img/16/folder.png');

       // return FALSE;
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
        
        //Уникален стринг
        $place = $this->mvc->getPlace(); 
        
        //Записите
        $rec = $Class->fetch($match['id']);
        
        //Ескейпваме заглавието
        $title = core_Type::escape($rec->title);
        
        //Ако не сме в текстов режим
        if (!Mode::is('text', 'plain')) {
                     
            //Инстанция на cover класа
            $coverClassInst = cls::get($rec->coverClass);
                            
            //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
            $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
            
            //Ако мода е xhtml
            if (Mode::get('text') == 'xhtml') {
                
                //Добаваме span с иконата и заглавиетео 
                //TODO класа да не е linkWithIcon
                $this->mvc->_htmlBoard[$place] = "<span class='linkWithIcon' style='background-image:url(" . sbf($coverClassInst->singleIcon, '"', $isAbsolute) .");'> {$title} </span>";    
            } else {
                
                //Линка
                $link = $match['link'] . '?folderId=' . $match['id'];
                
                //Атрибути на линка
                $attr1['class'] = 'linkWithIcon';
                $attr1['style'] = 'background-image:url(' . sbf($coverClassInst->singleIcon, '"', $isAbsolute) . ');';    
                $attr1['target'] = '_blank'; 
                
                //Създаваме линк
                $folderLink = ht::createLink($title, $link, NULL, $attr1); 
                
                //Добавяме href атрибута в уникалния стинг, който ще се замести по - късно
                $this->mvc->_htmlBoard[$place] = $folderLink->getContent();    
            }
        } else {
            
            //Добавяме линка без ret_url в текстовата част
            $this->mvc->_htmlBoard[$place] = $title; 
        }
        
        //Линка със символа в началото
        $res = $match['begin'] . "[#{$place}#]"; 
         
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
        
        //id' то на първия документ в системата
        $firstContainerId = $Class->fetchField($match['id'], 'firstContainerId');
            
        //Инстанция на първия документ
        $docProxy = doc_Containers::getDocument($firstContainerId);
        
        //Вземаме колоните на документа
        $docRow = $docProxy->getDocumentRow();
        
        //Ескейпваме заглавието
        $title = core_Type::escape($docRow->title);
        
        //Уникален стринг
        $place = $this->mvc->getPlace();
        
        //Ако не сме в текстов режим
        if (!Mode::is('text', 'plain')) {
            
            //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
            $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
            
            //Ако мода е xhtml
            if (Mode::get('text') == 'xhtml') {
                
                //Добаваме span с иконата и заглавиетео 
                //TODO класа да не е linkWithIcon
                $this->mvc->_htmlBoard[$place] = "<span class='linkWithIcon' style='background-image:url(" . sbf($docProxy->instance->singleIcon, '"', $isAbsolute) .");'> {$title} </span>";    
            } else {
                
                //Линка
                $link = $match['link'] . '?threadId=' . $match['id'];
                
                //Атрибути на линка
                $attr1['class'] = 'linkWithIcon';
                $attr1['style'] = 'background-image:url(' . sbf($docProxy->instance->singleIcon, '"', $isAbsolute) . ');';    
                $attr1['target'] = '_blank'; 
                
                //Създаваме линк
                $threadLink = ht::createLink($title, $link, NULL, $attr1);  
    
                //Добавяме href атрибута в уникалния стинг, който ще се замести по - късно
                $this->mvc->_htmlBoard[$place] = $threadLink->getContent();    
            }
        } else {
                
            //Добавяме линка без ret_url
            $this->mvc->_htmlBoard[$place] = $title;        
        }
        
        //Линка със символа в началото
        $res = $match['begin'] . "[#{$place}#]";
         
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

		$ptr = toUrl(array("([a-zA-Z0-9]{1,64})", "single"), 'absolute') . "([0-9]{1,10})";

		$ptr = str_replace("/", "\\/", $ptr) . "\i";


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

            if (!$Class)  return $match['0'];
            
			$rec = $Class->fetch($id);

            //Проверяваме за права
            if (!$rec || !$Class->haveRightFor('single', $rec))  return $match['0'];
            
            //Кое поле е избрано да се показва, като текст
            $field = $Class->rowToolsSingleField;
            
            //Ако няма, прескачаме
            if (!$field) continue;
            
            //Стойността на полето на текстовата част
            $rowField = $Class->fetchField($id, $field);
            
            //Ескейпваме заглавието
            $title = core_Type::escape($rowField);

            //Уникален стринг
            $place = $this->mvc->getPlace(); 
            
            //Ако не сме в текстов режим
            if (!Mode::is('text', 'plain')) {
                //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
                $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
                
                 //Ако мода е xhtml
                if (Mode::get('text') == 'xhtml') {
                    
                    //Добаваме span с иконата и заглавиетео 
                    //TODO класа да не е linkWithIcon
                    $this->mvc->_htmlBoard[$place] = "<span class='linkWithIcon' style='background-image:url(" . sbf($Class->singleIcon, '"', $isAbsolute) .");'> {$title} </span>";    
                } else {
                    
                    //URL към документа
                    $singleUrl = toUrl(array(
                        $Class,
                        'single',
                        'id' => $id,
                        'ret_url' => FALSE
                    ), 'absolute');
                    
                    //Атрибути на линка
                    $attr1['class'] = 'linkWithIcon';
                    $attr1['style'] = 'background-image:url(' . sbf($Class->singleIcon, '"', $isAbsolute) . ');';    
                    $attr1['target'] = '_blank';    
                    
                    //Създаваме линк
                    $singleLink = ht::createLink($title, $singleUrl, NULL, $attr1); 
                    
                    //Добавяме href атрибута в уникалния стинг, който ще се замести по - късно
                    $this->mvc->_htmlBoard[$place] = $singleLink->getContent();    
                }
            } else {
                
                //Добавяме линка без ret_url
                $this->mvc->_htmlBoard[$place] = $title;        
            }
            
            //Линка със символа в началото
            $res = $match['begin'] . "[#{$place}#]";
             
            //Връщаме линка
            return $res;
        }
        
        return $match['0'];
    }
}