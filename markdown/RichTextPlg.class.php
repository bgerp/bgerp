<?php


/**
 * Плъгин за работа с markdown текстове
 *
 * Прихваща и конвертира markdown текстовете
 *
 * @category  vendors
 * @package   markdown
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class markdown_RichTextPlg extends core_Plugin
{
    /**
     * Добавя бутон за добавяне на markdown текст
     */
    public function on_AfterGetToolbar($mvc, &$toolbarArr, &$attr)
    {
//        $toolbarArr->add("<a class=rtbutton style='font-weight:bold; background: white;' title='MD' onclick=\"s('[md]', '[/md]', document.getElementById('{$attr['id']}'))\">MD</a>", 'TBL_GROUP2');
    }
    
    
    public function on_AfterCatchRichElements($mvc, &$html)
    {
        // Обработваме [md].......[/md] елементите, които  съдържат връзки към файлове
        $pattern = "/(?'begin'\[md\])(?'text'.*?)(?'end'\[\/md\])/is";
        
        $this->mvc = $mvc;
        
        //Ако намери съвпадение на регулярния израз изпълнява функцията
        $html = preg_replace_callback($pattern, array($this, '_catchMarkdown'), $html);
    }
    
    
    /**
     * Замества намарения markdown текст с конвертирания текст
     *
     * @param array $match - Масив с откритите резултати
     *
     * @return string $res - Ресурса, който ще се замества
     */
    public function _catchMarkdown($match)
    {
        if ($match['text']) {
            $text = $match['text'];
            
            //Шаблон за намиране на линк към изображения в текста
            $pattern = "/!\[(?'picName'.*?)\]\s?\(\s*(?'url'.*?)\s*\)/";
            
            //Ако намери съвпадение на регулярния израз изпълнява функцията
            $text = preg_replace_callback($pattern, array($this, '_encodeSpacesInUrl'), $text);
            
            //Конвертираме markdown текста
            $text = markdown_Render::Convert($text);
            
            //Уникален стринг
            $place = $this->mvc->getPlace();
            
            //Добавяме конвертирания текст в уникалния стинг, който ще се замести по - късно
            $this->mvc->_htmlBoard[$place] = $text;
            
            //Кое да се замести
            $res = "[#{$place}#]";
            
            return $res;
        }
    }
    
    
    /**
     * Замества интервалите в URL с %20
     */
    public function _encodeSpacesInUrl($match)
    {
        //Заместваме всички интервали с %20 в частта с URL
        $url = str_ireplace(' ', '%20', $match['url']);
        
        //Заместваме URL' то с поправаната му стойност в цялото съвпадение
        $match[0] = str_ireplace($match['url'], $url, $match[0]);
        
        return $match[0];
    }
}
