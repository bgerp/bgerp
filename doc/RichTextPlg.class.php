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
     * # (от 1 до 3 букви)(от 1 до 10 цифри)
     */
    static $pattern = "/\#([a-z]{1,3})([0-9]{1,10})/i";
    
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
        $docName = $match[0];
        
        //Абревиатурата
        $abbr = strtoupper($match[1]);
        
        //id' то на файла
        $id = $match[2];
        
        
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
        $link = bgerp_L::getDocUrl($cid);
        
        //Уникален стринг
        $place = $this->mvc->getPlace();
        
        //Ако сме в текстов режим
        if(Mode::is('text', 'plain')) {
            //Добавяме линк към системата
            $this->mvc->_htmlBoard[$place] = "{$docName} ( $link )";
        } else {
            //Създаваме линк в html формат
            $style = 'background-image:url(' . sbf($mvc->singleIcon) . ');';
            $href = ht::createLink(substr($docName, 1), $link, NULL, array('target'=>'_blank', 'class' => 'linkWithIcon', 'style' => $style));
            
            //Добавяме href атрибута в уникалния стинг, който ще се замести по - късно
            $this->mvc->_htmlBoard[$place] = $href->getContent();
            
        }

        //Стойността, която ще заместим в регулярния израз
        $res = "__{$place}__";

        
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
        if (count($matches[0])) {
            
            //Вземаме всички класове и техните абревиатури от документната система
            self::setAbbr();
            
            //Обхождаме всички намерени думи
            foreach ($matches[1] as $key => $abbr) {
                
                //Преобразуваме абревиатурата от намерения стринг в главни букви
                $abbr = strtoupper($abbr);
                
                //Името на класа
                $className = self::$abbrArr[$abbr];
                
                //id' то на класа
                $id = $matches[2][$key];
                
                //Проверяваме дали имаме права за single. Ако нямаме - прескачаме
                if ((!$className) || (!$className::haveRightFor('single', $id))) continue;
                
                //Името на документа
                $name = $matches[1][$key] . $matches[2][$key];
                
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
        preg_match('/([a-z]+)([0-9]+)/i', $fileName, $matches);
        
        //Вземаме всички класове и техните абревиатури от документната система
        self::setAbbr();
        
        //Преобразуваме абревиатурата от намерения стринг в главни букви
        $abbr = strtoupper($matches[1]);
        
        //Името на класа
        $className = self::$abbrArr[$abbr];
        
        //id' то на класа
        $id = $matches[2];
        
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