<?php


/**
 * Клас  'type_Richtext' - Тип за форматиран (като BBCode) текст
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_tests_Richtext extends unit_Class
{
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    function test_ToVerbal()
    {
       $rt = new type_Richtext();
        
       $sample1 = 'Test: tasdasdasd';
       
       $res = $rt->toVerbal($sample1);
       
       UT::expectEqual(stripos(" ". $res, '<b>' . $sample1 . '</b>'), TRUE);
    }

    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
    }
    
    
    /**
     * Преобразува текст, форматиран с мета тагове (BB) в HTML
     *
     * Преобразованията са следните:
     * o Новите редове ("\n") се заменят с <br/>
     * o Интервалите в началото на реда се заменят с &nbsp;
     * o BB таговете се заменят според значението си
     *
     * Таговете, които се поддържат са:
     *
     * o [b]...[/b],
     * [i]...[/i],
     * [u]...[/u],
     * [h1-4]...[/h1-4]
     * [hr] - както съответните HTML тагове
     * o [strike]...[/strike] - задраскан текст
     * o [color=#XXX]...[/color] - цвят на текста
     * o [bg=#XXX]...[/bg] - цвят на фона
     * o [img{=caption}]url[/img] - изображение с опционално заглавие
     * o [code{=syntax}]...[/code] - преформатиран текст с опционално езиково оцветяване
     * o [em={code}] - емотикони
     *
     * @param string $richtext
     * @return string
     */
    function toHtml($html)
    {
    }
    
    
    /**
     * Връща уникален стринг, който се използва за име на плейсхолдер
     */
    function getPlace()
    {
    }
    
    
    /**
     * Обработва [html] ... [/html]
     */
    function _catchHtml($match)
    {
    }
    
    
    /**
     * Заменя [html] ... [/html]
     */
    function _catchLi($match)
    {
    }
    
    
    /**
     * Вкарва текста който е в следната последователност: 
     * \n и/или интервали \n или в началото[Главна буква][една или повече малки букви и или интервали и или големи букви]:[интервал][произволен текст]\n или край на текста
     * в болд таг на richText
     */
    function _catchBold($match)
    {
    }
    
    
    /**
     * Заменя [img=????] ... [/img]
     */
    function _catchImage($match)
    {
    }
    
    
    /**
     * Заменя [gread=????] ... [/gread]
     */
    function _catchGread($match)
    {
    }
    
    
    /**
     * Заменя елемента [code=???] .... [/code]
     */
    function _catchCode($match)
    {
    }
    
    
    /**
     * Заменя елементите [link=?????]......[/link]
     */
    function _catchLink($match)
    {
    }
    
    
    /**
     * Конвертира към HTML елементите [link=...]...[/link], сочещи към вътрешни URL
     * 
     * @param string $url URL, къдетo трябва да сочи връзката
     * @param string $text текст под връзката
     * @param string $place
     * @return string HTML елемент <a href="...">...</a>
     */
    public function internalLink_($url, $title, $place, $rest)
    {
    }


    /**
     * Конвертира към HTML елементите [link=...]...[/link], сочещи към външни URL
     * 
     * Може да бъде прихванат в плъгин на `type_Richtext` с on_AfterExternalLink()
     * 
     * @param string $url URL, къдетo трябва да сочи връзката
     * @param string $text текст под връзката
     * @param string $place
     * @return string HTML елемент <a href="...">...</a>
     */
    public function externalLink_($url, $title, $place)
    {
    }


    /**
     * Заменя елементите [hide=?????]......[/hide]
     */
    function _catchHide($match)
    {
    }
    
    
    /**
     * Замества [color=????] елементите
     */
    function _catchColor($match)
    {
    }
    
    
    /**
     * Замества [bg=????] елементите
     */
    function _catchBg($match)
    {
    }
    
    
    /**
     * Замества [em=????] елементите
     */
    function _catchEmoticons($match)
    {
    }
    
    
    /**
     * Обработва хедъри-те [h1..6] ... [/h..]
     */
    function _catchHeaders($matches)
    { 
    }
    
    
    /**
     * Прави субституция на хипервръзките
     */
    function _catchUrls($html)
    {   
    }
    
    
    /**
     * Конвертира вътрешен URL към подходящо HTML представяне.
     * 
     * @param string $url
     * @param string $title
     * @return string HTML елемент <a href="...">...</a>
     */
    public function internalUrl_($url, $title, $rest)
    {
    }
    

    /**
     * Конвертира въшнен URL към подходящо HTML представяне
     * 
     * @param string $url
     * @param string $title
     * @param string HTML код
     */
    public function externalUrl_($url, $title)
    {
    }


    /**
     * Прави субституция на имейлите
     */
    function _catchEmails($match)
    {
    }


    /**
     * Връща масив с html код, съответстващ на бутоните на Richedit компонента
     */
    function getToolbar(&$attr)
    {
    }
    
    
    /**
     * Парсира вътрешното URL
     * 
     * @param URL $res - Вътрешното URL, което ще парсираме
     * 
     * @return array $params - Масив с парсираното URL
     */
    static function parseInternalUrl($rest)
    {
    }
}
