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
        
        //Абревиатурата
        $abbr = strtoupper($match['abbr']);
        
        //id' то на файла
        $id = $match['id'];
        
        // Вземаме всички класове и техните абревиатури от документната система
        $abbrArr = doc_Containers::getAbbr();

        //Името на класа
        $className = $abbrArr[$abbr];
        
        //Проверяваме дали дали сме открили клас или имаме права за single. Ако нямаме - връщаме името без да го заместваме
        if ((!$className) || (!$className::haveRightFor('single', $id))) return $match[0];
        
        //containerId' то на документа
        $cid = $className::fetchField($id, 'containerId');
        
        //Ако нямаме запис за съответното $id връщаме името без да го заместваме
        if (!$cid) return $match[0];
        
        $mvc = cls::get($className);
        
        //Създаваме линк към документа
        $link = bgerp_L::getDocLink($cid, doc_DocumentPlg::getMidPlace());
        
        //Уникален стринг
        $place = $this->mvc->getPlace();
        
        //Ако сме в текстов режим
        if(Mode::is('text', 'plain')) {
            //Добавяме линк към системата
            $this->mvc->_htmlBoard[$place] = "{$docName} ( $link )";
        } else {
            
            //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
            $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
            
            //Създаваме линк в html формат
            $style = 'background-image:url(' . sbf($mvc->singleIcon, '"', $isAbsolute) . ');';
            
            // Атрибути на линка
            $attr['class'] = 'linkWithIcon';
            $attr['style'] = $style;
            
            // Ако изпращаме или принтираме документа
            if (Mode::is('text', 'xhtml') || Mode::is('printing')) {
                
                // Линка да се отваря на нова страница
                $attr['target'] = '_blank';    
            }
            
            $href = ht::createLink(substr($docName, 1), $link, NULL, $attr);
            
            //Добавяме href атрибута в уникалния стинг, който ще се замести по - късно
            $this->mvc->_htmlBoard[$place] = $href->getContent();
        }

        //Стойността, която ще заместим в регулярния израз
        //Добавяме символите отркити от регулярниярния израз, за да не се развали текста
        $res = $match['begin'] . "[#{$place}#]" . $match['end'];

        return  $res;
    }


    /**
     * Намира всички документи към системата.
     *
     * @param string $rt - Стринг, в който ще търсим.
     *
     * @return array $docs - Масив с имената на намерените документи
     */
    static function getAttachedDocs($rt)
    {
        //Регулярен израз за определяне на всички думи, които могат да са линкове към наши документи
        preg_match_all(self::$pattern, $rt, $matches);
        
        //Ако сме открили нещо
        if (count($matches['dsName'])) {
            
            //Обхождаме всички намерени думи
            foreach ($matches['abbr'] as $key => $abbr) {
                
                //Преобразуваме абревиатурата от намерения стринг в главни букви
                $abbr = strtoupper($abbr);
                
                // Вземаме всички класове и техните абревиатури от документната система
                $abbrArr = doc_Containers::getAbbr();
                
                //Името на класа
                $className = $abbrArr[$abbr];
                
                //id' то на класа
                $id = $matches['id'][$key];
                
                //Проверяваме дали имаме права за single. Ако нямаме - прескачаме
                if ((!$className) || (!$className::haveRightFor('single', $id))) continue;
                
                //Ако няма такъв документ, не се връща името му за прикачване
                if (!$className::fetch($id)) continue;
                
                //Името на документа
                $name = $matches['name'][$key];
                
                $docs[$name] = $name;
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
     * Разбива манипулатор на документ (docHandle) на име на клас-мениджър и ид на документ.
     * 
     * @param string $handle
     * @return array|boolean масив с два елемента - [docClass] и [docId]; FALSE при неуспех.
     */
    static function parseDocHandle($handle)
    {
        $info = static::getFileInfo($handle);
        
        if (empty($info)) {
            return FALSE;
        }
        
        $res  = array(
            'docClass' => $info['className'],
            'docId' => $info['id'],
        );
        
        return $res;
    }
}
