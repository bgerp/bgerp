<?php


/**
 * Клас 'doc_RichTextPlg' - Добавя функционалност за поставяне handle на документи в type_RichText
 *
 *
 * @category  doc
 * @package   bgerp
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
        
        //Уникален стринг
        $place = $this->mvc->getPlace();

        //Вземаме всички класове и техните абревиатури от документната система
        self::setAbbr();
        
        //Името на класа
        $className = self::$abbrArr[$abbr];
        
        //Проверяваме дали дали сме открили клас или имаме права за single. Ако нямаме - връщаме името без да го заместваме
        if ((!$className) || (!$className::haveRightFor('single', $id))) return $docName;
        
        //containerId' то на документа
        $cid = $className::fetchField($id, 'containerId');
        
        //Създаваме линк към документа
        $link = self::createDocLink($cid, '[#mid#]');

        //Ако сме в текстов режим
        if(Mode::is('text', 'plain')) {
            //Добавяме линк към системата
            $res = "Link: $link";
        } else {
            //Създаваме линк в html формат
            $href = ht::createLink($docName, $link, NULL,array('target'=>'_blank'));
            
            //Добавяме href атрибута в уникалния стинг, който ще се замести по - късно
            $this->mvc->_htmlBoard[$place] = $href->getContent();
            
            //Стойността, която ще заместим в регулярния израз
            $res = "__{$place}__";
        }
        
        return  $res;
    }
    
    
    /**
     * Връща линкнатите файлове от RichText-а
     * 
     * @param string $rt - Текста, в който ще се търсят линковете към файловете.
     */
    static function getPdfs($rt)
    {
        //Регулярен израз за определяне на всички думи, които могат да са линкове към наши документи
        preg_match_all(self::$pattern, $rt, $matches);
        
        //Ако сме открили нещо
        if (count($matches[0])) {
            //Вземаме всички класове и техните абревиатури от документната система
            self::setAbbr();
            
            //Емулираме режим 'printing', за да махнем singleToolbar при рендирането на документа
            Mode::push('printing', TRUE);
            
            //Емулираме режим 'xhtml', за да покажем статичните изображения
            Mode::push('text', 'xhtml');
            
            //Обхождаме всички намерени думи
            foreach ($matches[1] as $key => $abbr) {
                //Преобразуваме абревиатурата от намерения стринг в главни букви
                $abbr = strtoupper($abbr);
                
                //Името на класа
                $className = self::$abbrArr[$abbr];
                
                //id' то на класа
                $id = $matches[2][$key];
                
                //Ако нямаме клас - прескачаме
                if (!$className) continue;
                
                //Проверяваме дали имаме права за single. Ако нямаме - прескачаме
                if (!$className::haveRightFor('single', $id)) continue;
                
                //Вземаме containerId' то на документа
                $containerId = $className::fetchField($id, 'containerId');
                
                //Ако няма containerId - прескачаме
                if (!$containerId) continue;
                
                //Вземаме документа
                $document = doc_Containers::getDocument($containerId);
                
                //Данните на документа
                $data = $document->prepareDocument();
                
                //Рендираме документа
                $doc = $document->renderDocument($data);
                
                //Името на документа
                $name = $matches[1][$key] . $matches[2][$key];
                
                //Манипулатора на новосъздадения pdf файл
                $fh = doc_PdfCreator::convert($doc, $name);
                
                //масив с всички pdf документи и имената им
                $files[$fh] = $name;
            }
            
            //Връщаме старата стойност на 'text'
            Mode::pop('text');
            
            //Връщаме старата стойност на 'printing'
            Mode::pop('printing');
            
            return $files;
        }
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
     * Създава линк към класа, който се занимава с показването на документите
     * 
     * @param integer $cid - containerId
     * @param inreger $mid - Шаблона, който ще се замества
     * 
     * @return string $link - Линк към вювъра на документите
     */
    static function createDocLink($cid, $mid)
    {
        $link = toUrl(array('D', 'S', 'cid' => $cid, 'mid' => $mid), 'absolute');
        
        return $link;
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