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
     * Масив с всички абревиатури и съответните им класове
     */
    static $abbrArr = NULL;
    
    
    /**
     * Обработваме елементите линковете, които сочат към докъментната система
     */
    function on_AfterCatchRichElements($mvc, &$html)
    {
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

        //Вземаме всички класове и техните абревиатури от документната система
        self::setAbbr();
        
        //Името на класа
        $className = self::$abbrArr[$abbr];
        
        //Проверяваме дали дали сме открили клас или имаме права за single. Ако нямаме - връщаме името без да го заместваме
        if ((!$className) || (!$className::haveRightFor('single', $id))) return $docName;
        
        $mvc = cls::get($className);

        //containerId' то на документа
        $cid = $className::fetchField($id, 'containerId');
        
        //Създаваме линк към документа
        $link = bgerp_L::getDocLink($cid);
        
        //Уникален стринг
        $place = $this->mvc->getPlace();
        
        //Ако сме в текстов режим
        if(Mode::is('text', 'plain')) {
            //Добавяме линк към системата
            $this->mvc->_htmlBoard[$place] = "{$docName} ( $link )";
        } else {
            
            //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
            $isAbsolute = Mode::is('text', 'xtml') || Mode::is('printing');
            
            //Създаваме линк в html формат
            $style = 'background-image:url(' . sbf($mvc->singleIcon, '"', $isAbsolute) . ');';
            $href = ht::createLink(substr($docName, 1), $link, NULL, array('target'=>'_blank', 'class' => 'linkWithIcon', 'style' => $style));
            
            //Добавяме href атрибута в уникалния стинг, който ще се замести по - късно
            $this->mvc->_htmlBoard[$place] = $href->getContent();
        }

        //Стойността, която ще заместим в регулярния израз
        //Добавяме символите отркити от регулярниярния израз, за да не се развали текста
        $res = $match['begin'] . "__{$place}__" . $match['end'];

        return  $res;
    }


    /**
     * Сетва всички класове и техните абревиатури от документната система
     */
    function setAbbr()
    {
        //Ако не е сетната
        if (!self::$abbrArr) {
            //Вземаме всички класове и техните абревиатури от документната система
            self::$abbrArr = doc_Containers::getAbrr();
        }
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
            
            //Вземаме всички класове и техните абревиатури от документната система
            self::setAbbr();
            
            //Обхождаме всички намерени думи
            foreach ($matches['abbr'] as $key => $abbr) {
                
                //Преобразуваме абревиатурата от намерения стринг в главни букви
                $abbr = strtoupper($abbr);
                
                //Името на класа
                $className = self::$abbrArr[$abbr];
                
                //id' то на класа
                $id = $matches['id'][$key];
                
                //Проверяваме дали имаме права за single. Ако нямаме - прескачаме
                if ((!$className) || (!$className::haveRightFor('single', $id))) continue;
                
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
        
        //Вземаме всички класове и техните абревиатури от документната система
        self::setAbbr();
        
        //Преобразуваме абревиатурата от намерения стринг в главни букви
        $abbr = strtoupper($matches['abbr']);
        
        //Името на класа
        $className = self::$abbrArr[$abbr];
        
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
}