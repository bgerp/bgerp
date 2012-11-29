<?php



/**
 * Клас 'doc_RichTextPlg' - Добавя функционалност за поставяне handle на документи в type_RichText
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_RichTextPlg extends core_Plugin
{
    
    /**
     * Шаблон за намиране на линкове към документи
     * # (от 1 до 3 букви)(от 1 до 10 цифри). Без да се прави разлика за малки и големи букви.
     * Шаблона трябва да не започва и/или да не завършва с буква и/или цифра
     * 
     * @param begin    - Символа преди шаблона
     * @param dsName  - Името на шаблона, с # отпред
     * @param name     - Името на шаблона, без # отпред
     * @param abbr     - Абревиатурата на шаблона
     * @param id       - id' то на шаблона
     * @param end      - Символа след шаблона
     */
    static $pattern = "/(?'begin'[^a-z0-9а-я]|^){1}(?'dsName'\#(?'name'(?'abbr'[a-z]{1,3})(?'id'[0-9]{1,10})))(?'end'[^a-z0-9а-я]|$){1}/iu";
    
    
    /**
     * Обработваме елементите линковете, които сочат към докъментната система
     */
    function on_AfterCatchRichElements($mvc, &$html)
    {
        if (Request::get('Printing')) {
            return;
        }
        
        $this->mvc = $mvc;
        
        //Ако намери съвпадение на регулярния израз изпълнява функцията
        $html = preg_replace_callback(self::$pattern, array($this, '_catchFile'), $html);
    }
    
    
    /**
     * Заменяме линковете от система с абсолютни URL' та
     *
     * @param array $match - Масив с откритите резултати
     *
     * @return string $res - Ресурса, който ще се замества
     */
    function _catchFile($match)
    {
        //Име на файла
        $docName = $match['dsName'];

        if (!$docRec = static::matchedHandleToRec($match, $mvc)) {
            return $match[0];
        }
        
        //Създаваме линк към документа
        $link = bgerp_L::getDocLink($docRec->containerId, doc_DocumentPlg::getMidPlace());
        
        //Уникален стринг
        $place = $this->mvc->getPlace();
        
        //Ако сме в текстов режим
        if(Mode::is('text', 'plain')) {
            //Добавяме линк към системата
            $this->mvc->_htmlBoard[$place] = "{$docName} ( $link )";
        } else {
            
            //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
            $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
            
            $sbfIcon = sbf($mvc->getIcon($docRec->id), '"', $isAbsolute);
            
            $title = substr($docName, 1);
            
            if(Mode::is('text', 'xhtml') && !Mode::is('pdf')) {
                
                // Създаваме линк
                $href = ht::createLink($title, $link);
                
                // Добавяме линк и иконата
                $icon = "<img src={$sbfIcon} width='16' height='16' style='float:left;margin:3px 2px 4px 0px;' alt=''>";
                $this->mvc->_htmlBoard[$place] = "<div style='display:inline-block;'>{$icon}{$href}</div>";  
            } else {
                
                //Създаваме линк в html формат
                $style = "background-image:url({$sbfIcon});";
                
                // Атрибути на линка
                $attr['class'] = 'linkWithIcon';
                $attr['style'] = $style;
                
                // Ако изпращаме или принтираме документа
                if ($isAbsolute) {
                    
                    // Линка да се отваря на нова страница
                    $attr['target'] = '_blank';    
                }
                
                $href = ht::createLink($title, $link, NULL, $attr);
                
                //Добавяме href атрибута в уникалния стинг, който ще се замести по - късно
                $this->mvc->_htmlBoard[$place] = $href->getContent();
            }
        }

        //Стойността, която ще заместим в регулярния израз
        //Добавяме символите отркити от регулярниярния израз, за да не се развали текста
        $res = $match['begin'] . "[#{$place}#]" . $match['end'];

        return  $res;
    }


    /**
     * Намира всички цитирания на хендъли на документи в текст
     *
     * @param string $rt - Стринг, в който ще търсим.
     * @return array $docs - Масив с ключове - разпознатите хендъли и стойности - масиви от вида
     *                         array(
     *                             'name' => хендъл, също като ключа
     *                             'mvc'  => мениджър на документа с този хендъл
     *                             'rec'  => запис за документа с този хендъл
     *                         ) 
     */
    static function getAttachedDocs($rt)
    {
        //Ако сме открили нещо
        if (preg_match_all(self::$pattern, $rt, $matches, PREG_SET_ORDER)) {
            
            //Обхождаме всички намерени думи
            foreach ($matches as $match) {
                if (!$rec = static::matchedHandleToRec($match, $mvc)) {
                    continue;
                }
                
                //Името на документа
                $name = $mvc->getHandle($rec->id);
                
                $docs[$name] = compact('name', 'mvc', 'rec');
            }
            
            return $docs;
        }
    }
    
    
    /**
     * От името на файла намира класа и id' то на документа
     *
     * @param string $fileName - името на файла
     *
     * @return array $info - Информация за масива. $info['className'] - Името на класа. $info['id'] - id' то на документа
     */
    static function getFileInfo($fileName)
    {
        //Регулярен израз за определяне на всички думи, които могат да са линкове към наши документи
        preg_match("/(?'name'(?'abbr'[a-z]+)(?'id'[0-9]+))/i", $fileName, $matches);
        
        //Преобразуваме абревиатурата от намерения стринг в главни букви
        $abbr = strtoupper($matches['abbr']);
        
        // Вземаме всички класове и техните абревиатури от документната система
        $abbrArr = doc_Containers::getAbbr();
        
        //Името на класа
        $className = $abbrArr[$abbr];
        
        //id' то на класа
        $id = $matches['id'];
        
        //Провяряваме дали имаме права
        if (($className) && ($className::haveRightFor('single', $id))) {
            
            //Името на класа
            $info['className'] = $className;
            
            //id' то на класа
            $info['id'] = $id;
            
            return $info;
        }
    }
    
    
    /**
     * Помощен метод - извлича запис на документ според зададен хендъл
     * 
     * @param array $match парсиран хендъл на документа
     * @param core_Mvc $mvc мениджър на документа
     * @return stdClass запис от модела $mvc; FALSE при неуспех
     */
    protected static function matchedHandleToRec($match, &$mvc = NULL)
    {
        //Проверяваме дали сме открили клас. Ако не - връщаме FALSE
        if (!$mvc = doc_Containers::getClassByAbbr($match['abbr'])) {
            return FALSE;
        }
        
        //Ако нямаме запис за съответното $id връщаме FALSE
        if (!$docRec = $mvc::fetchByHandle($match)) {
            return FALSE;
        }
        
        //Проверяваме дали имаме права за single. Ако не - FALSE
        if (!$mvc->haveRightFor('single', $docRec)) {
            return FALSE;
        }

        // Връщаме намерения запис, съответстващ на хендъла
        return $docRec;
    }
}
