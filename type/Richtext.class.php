<?php



/**
 * Тип на записите в кеша
 */
defIfNot('RICHTEXT_CACHE_TYPE', 'RichText');


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
class type_Richtext extends type_Text {
    
    static $emoticons = array(
        'smile' => ' :) ',
        'bigsmile' => ' :D ',
        'cool' => ' ;) ',
        'beer' => ' [beer] ',
        'question' => ' [?] ',
        'heart' => ' [love] ',
        'ok' => ' [ok] ',
        'think' => ' :-? '
    );
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $tpl = new ET("<span class='richEdit'>[#TEXTAREA#]<div class='richedit-toolbar'>[#TBL_GROUP1#]&nbsp;[#TBL_GROUP2#]&nbsp;[#TBL_GROUP3#]</div></span>");
        
        if(Mode::is('screenMode', 'narrow')) {
            setIfNot($attr['rows'], $this->params['rows'], 7);
        } else {
            setIfNot($attr['rows'], $this->params['rows'], 10);
        }
        
        // Атрибута 'id' се сетва с уникален такъв, ако не е зададен
        ht::setUniqId($attr);
        
        $attr['onselect'] = 'sc(this);';
        $attr['onclick'] = 'sc(this);';
        $attr['onkeyup'] = 'sc(this);';
        $attr['onchange'] = 'sc(this);';
        
        $attr['style'] .= 'min-width:568px;';
        
        $tpl->append(ht::createTextArea($name, $value, $attr), 'TEXTAREA');
        
        $toolbarArr = type_Richtext::getToolbar($attr);
        
        $toolbarArr->order();
        
        foreach($toolbarArr as $link) {
            $tpl->append($link->html, $link->place);
        }
        
        return $tpl;
    }
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    function toVerbal($value)
    {
        if(!$value) return NULL;
        
        if (Mode::is('text', 'plain')) {
            $res = strip_tags($this->toHtml($value));
        } else {
            $res = $this->toHtml($value);
        }
        
        return $res;
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
        if(!$html) return "";
        
        $textMode = Mode::get('text');
        
        $md5 = md5($html) . $textMode;

        //if($ret = core_Cache::get(RICHTEXT_CACHE_TYPE, $md5, 1000)) {
            
            //    return $ret;
        //}
        
        // Място, където съхраняваме нещата за субституция
        $this->htmlBoard = array();
        
        // Уникален маркер, който ще се използва за временните плейсхолдери
        $this->randMark = rand(1, 2000000000);
        
        // Задаваме достатъчно голям буфер за обработка на регулярните изрази
        ini_set('pcre.backtrack_limit', '2M');
        
        // Обработваме [html] ... [/html] елементите, които могат да съдържат чист HTML код
        $html = preg_replace_callback("/\[html](.*?)\[\/html\]/is", array($this, '_catchHtml'), $html);
        
        // Премахваме всичкото останало HTML форматиране
        $html = str_replace(array("&", "<"), array("&amp;", "&lt;"), $html);
        
        // Обработваме [code=????] ... [/code] елементите, които трябва да съдържат програмен код
        $html = preg_replace_callback("/\[code(=([a-z0-9]{1,32})|)\](.*?)\[\/code\]/is", array($this, '_catchCode'), $html);
        
        // Обработваме [img=http://????] ... [/img] елементите, които представят картинки с надписи под тях
        $html = preg_replace_callback("/\[img(=([^\]]*)|)\](.*?)\[\/img\]/is", array($this, '_catchImage'), $html);
        
        // Обработваме [link=http://????] ... [/link] елементите, които задават фон за буквите на текста между тях
        $html = preg_replace_callback("/\[link(=([^\]]*)|)\](.*?)\[\/link\]/is", array($this, '_catchLink'), $html);
        
        // Обработваме [hide=caption] ... [/hide] елементите, които скриват/откриват текст
        $html = preg_replace_callback("/\[hide(=([^\]]*)|)\](.*?)\[\/hide\]/is", array($this, '_catchHide'), $html);
        
        // Даваме възможност други да правят обработки на текста
        $this->invoke('AfterCatchRichElements', array(&$html));
        
        // Обработваме хипервръзките, зададени в явен вид
        $html = preg_replace_callback("#((?:https?|ftp|ftps|nntp)://[^\s<>()]+)#i", array($this, '_catchHyperlinks'), $html);
        
        // H!..6
        $html = preg_replace_callback("/\[h([1-6])\](.*?)\[\/h[1-6]\]/is", array($this, '_catchHeaders'), $html);
        
        // $html = preg_match_all("/\[([a-z]{2,9})(=([^\]]*)|)\](.*?)\[\/\\1\]/is", $html, $matches); bp($matches);
        
        
        // Нормализираме знаците за край на ред и обработваме елементите без параметри
        if($textMode != 'plain') {
            $from = array("\r\n", "\n\r", "\r", "\n", "\t", '[/color]', '[/bg]', '[hr]', '[b]', '[/b]', '[u]', '[/u]', '[i]', '[/i]');
            $to = array("\n", "\n", "\n", "<br>", "&nbsp;&nbsp;&nbsp;&nbsp;", '</span>', '</span>', '<hr>', '<b>', '</b>', '<u>', '</u>', '<i>', '</i>');
        } else {
            $from = array("\r\n", "\n\r", "\r",  "\t",   '[/color]', '[/bg]', '[b]', '[/b]', '[u]', '[/u]', '[i]', '[/i]', '[hr]');
            $to   = array("\n",   "\n",   "\n",  "    ", '',         '',      '*',   '*',    '',    '',     '',    '',     str_repeat('_', 84));
        }
        
        $html = str_replace($from, $to, $html);
        
        // Обработваме елементите [color=????]  
        $html = preg_replace_callback("/\[color(=([^\]]*)|)\]\s*/si", array($this, '_catchColor'), $html);
        
        // Обработваме елементите [bg=????]  
        $html = preg_replace_callback("/\[bg(=([^\]]*)|)\]\s*/si", array($this, '_catchBg'), $html);
        
        // Обработваме елемента [li]
        if(!Mode::is('text', 'plain')) {
            $html = preg_replace_callback("/\[li](.*?)<br>/is", array($this, '_catchLi'), $html);
            $html = str_replace("[li]", "<li>", $html);
        } else {
            $html = preg_replace_callback("/\[li](.*?)\n/is", array($this, '_catchLi'), $html);
            $html = str_replace("[li]", "\n o ", $html);
        }
        
        // Поставяме емотиконите на местата с елемента [em=????]
        $html = preg_replace_callback("/\[em(=([^\]]+)|)\]/is", array($this, '_catchEmoticons'), $html);
        
        if(!Mode::is('text', 'plain')) {
            
            // Заменяме обикновените интервали в началото на всеки ред, с непрекъсваеми такива
            $newLine = TRUE;
            $sp = "";
            
            for($i = 0; $i<strlen($html); $i++) {
                $c = substr($html, $i, 1);
                
                if ($c == "\n") {
                    $newLine = TRUE;
                } else {
                    if ($c == " ") $c = $newLine ? ("&nbsp;") : (" ");
                    else $newLine = FALSE;
                }
                $out .= $c;
            }
            
            $st1 = '';
            
            $lines = explode("<br>", $out);
            $empty = 0;
            
            foreach($lines as $l) {
                if(str::trim($l)) {
                    $empty = 0;
                } else {
                    $empty++;
                }
                
                if($empty <2) {
                    $st1 .= $l . "<br>\n";
                }
            }
            
            $html = $st1;
        }
        
        if(count($this->_htmlBoard)) {
            foreach($this->_htmlBoard as $place => $txt) {
                $html = str_replace("__{$place}__", $txt, $html);
            }
        }
        
        $html =  "<div class=\"richtext\">{$html}</div>";
        
         
        // core_Cache::set(RICHTEXT_CACHE_TYPE, $md5, $html, 1000);
        
        return $html;
    }
    
    
    /**
     * Връща уникален стринг, който се използва за име на плейсхолдер
     */
    function getPlace()
    {
        return 'richText' . $this->randMark++;
    }
    
    
    /**
     * Обработва [html] ... [/html]
     */
    function _catchHtml($match)
    {
        if(Mode::is('text', 'plain')) {
            $res = html2text_Converter::toRichText($match[1]);
        } else {
            $place = $this->getPlace();
            $this->_htmlBoard[$place] = hclean_Purifier::clean($match[1], 'UTF-8');
            $res = "__{$place}__";
        }
        
        return $res;
    }
    
    
    /**
     * Заменя [html] ... [/html]
     */
    function _catchLi($match)
    {
        $text = $match[1];
        
        if(!Mode::is('text', 'plain')) {
            $res = "<li>$text</li>\n";
        } else {
            $res = "\n o {$text}";
        }
        
        return $res;
    }
    
    
    /**
     * Заменя [img=????] ... [/img]
     */
    function _catchImage($match)
    {
        $place = $this->getPlace();
        $url = core_Url::escape($match[2]);
        
        $title = htmlentities($match[3], ENT_COMPAT, 'UTF-8');
        
        $this->_htmlBoard[$place] = "<div><img src=\"{$url}\" style='max-width:750px;' alt=\"{$title}\"><br><small>";
        
        return "__{$place}__{$title}</small></div>";
    }
    
    
    /**
     * Заменя елемента [code=???] .... [/code]
     */
    function _catchCode($match)
    {
        $place = $this->getPlace();
        $code = $match[3];
        
        if(!trim($code)) return "";
        
        if($lg) {
            $Geshi = cls::get('geshi_Import');
            $code1 = $Geshi->renderHtml(html_entity_decode(trim($code)), $lg) ;
        } else {
            $code1 = "<pre class='richtext'>" . trim($code) . "</pre>";;
        }
        
        $this->_htmlBoard[$place] = $code1;
        
        return "__{$place}__";
    }
    
    
    /**
     * Заменя елементите [link=?????]......[/link]
     */
    function _catchLink($match)
    {
        $place = $this->getPlace();
        $title = $match[3];
        $url = core_Url::escape($match[2]);
        
        $this->_htmlBoard[$place] = $url;
        
        return "<a href=\"__{$place}__\">{$title}</a>";
    }
    
    
    /**
     * Заменя елементите [hide=?????]......[/hide]
     */
    function _catchHide($match)
    {
        $place = $this->getPlace();
        $text = trim($match[3]);
        $title = $match[2];
        
        $id = 'hide' . rand(1, 1000000);
        
        $html = "<a href=\"javascript:toggleDisplay('{$id}')\"  style=\"font-weight:bold; background-image:url('http://www.unlimited-films.net/charte/plus.png');\" 
                   class=\"linkWithIcon\">{$title}</a><div class='clearfix21' id='{$id}' style='display:none;margin-left:4px;border-left:dotted 1px #ccc; padding:10px; backgroud-color:#f0f0f0;'>";
        
        $this->_htmlBoard[$place] =  $html;
        
        return "__{$place}__{$text}</div>";
    }
    
    
    /**
     * Замества [color=????] елементите
     */
    function _catchColor($match)
    {
        $color = core_Type::escape($match[2]);
        
        if(!$color) $color = 'black';
        
        return "<span style=\"color:{$color}\">";
    }
    
    
    /**
     * Замества [bg=????] елементите
     */
    function _catchBg($match)
    {
        $color = core_Type::escape($match[2]);
        
        if(!$color) $color = 'black';
        
        return "<span style=\"background-color:{$color}\">";
    }
    
    
    /**
     * Замества [em=????] елементите
     */
    function _catchEmoticons($match)
    {
        $em = type_Varchar::escape($match[2]);
        
        if(Mode::is('text', 'xhtml')) {
            $iconFile = sbf("img/em15/em.icon.{$em}.gif", '"', TRUE);
            $res = "<img src={$iconFile} style='margin-left:1px; margin-right:1px;' height=15 width=15/>";
        } elseif(Mode::is('text', 'plain')) {
            $res = self::$emoticons[$em];
        } else {
            $iconFile = sbf("img/em15/em.icon.{$em}.gif");
            $res = "<img src={$iconFile} style='margin-left:1px; margin-right:1px;' height=15 width=15/>";
        }
        
        return $res;
    }
    
    
    /**
     * Обработва хедъри-те [h1..6] ... [/h..]
     */
    function _catchHeaders($matches)
    {
        $text  = $matches[2];
        $level = $matches[1];
        
        if(!Mode::is('text', 'plain')) {
            $res = "<h{$level}>{$text}</h{$level}>";
        } else {
            $res =   mb_strtoupper($text) . "\n" . str_repeat('=', mb_strlen($text)) . "\n";
        }
        
        return $res;
    }
    
    
    /**
     * Прави субституция на хипервръзките
     */
    function _catchHyperlinks($html)
    {
        $url = core_Url::escape($html[0]);
        
        if(!Mode::is('text', 'plain')) {
            $tpl = ht::createLink($url, $url);
            $url = $tpl->getContent();
        }
        
        return $url;
    }


    /**
     * Връща масив с html код, съответстващ на бутоните на Richedit компонента
     */
    function getToolbar(&$attr)
    {
        $formId = $attr['id'];
        
        $toolbarArr = new core_ObjectCollection('html,place');
        
        $toolbarArr->add("<a class='rtbutton1' title='Усмивка' onclick=\"rp('[em=smile]', document.getElementById('{$formId}'))\"><img src=" . sbf('img/em15/em.icon.smile.gif') . " height='15' width='15'  align='top' alt='smile'></a>", 'TBL_GROUP1');
        
        $toolbarArr->add("<a class='rtbutton1' title='Широка усмивка' onclick=\"rp('[em=bigsmile]', document.getElementById('{$formId}'))\"><img src=" . sbf('img/em15/em.icon.bigsmile.gif') . " height='15' width='15'  align='top' alt='bigsmile'></a>", 'TBL_GROUP1');
        
        $toolbarArr->add("<a class='rtbutton1' title='Супер!' onclick=\"rp('[em=cool]', document.getElementById('{$formId}'))\"><img src=" . sbf('img/em15/em.icon.cool.gif') . " height='15' width='15' align='top' alt='cool'></a>", 'TBL_GROUP1');
        
        if(!Mode::is('screenMode', 'narrow')) {
            $toolbarArr->add("<a class='rtbutton1' title='Бира' onclick=\"rp('[em=beer]', document.getElementById('{$formId}'))\"><img alt='Бира' src=" . sbf('img/em15/em.icon.beer.gif') . " height='15' width='15'></a>", 'TBL_GROUP1');
            
            $toolbarArr->add("<a class='rtbutton1' title='Въпрос?' onclick=\"rp('[em=question]', document.getElementById('{$formId}'))\"><img alt='Въпрос?' src=" . sbf('img/em15/em.icon.question.gif') . " height='15' width='15' ></a>", 'TBL_GROUP1');
            
            $toolbarArr->add("<a class='rtbutton1' title='Сърце' onclick=\"rp('[em=heart]', document.getElementById('{$formId}'))\"><img alt='Сърце' src=" . sbf('img/em15/em.icon.heart.gif') . " height='15' width='15'></a>", 'TBL_GROUP1');
            
            $toolbarArr->add("<a class='rtbutton1' title='OK' onclick=\"rp('[em=ok]', document.getElementById('{$formId}'))\"><img alt='OK' src=" . sbf('img/em15/em.icon.ok.gif') . " height='15' width='15'></a>", 'TBL_GROUP1');
            
            $toolbarArr->add("<a class='rtbutton1' title='Мисля' onclick=\"rp('[em=think]', document.getElementById('{$formId}'))\"><img alt='Мисля' src=" . sbf('img/em15/em.icon.think.gif') . " height='15' width='15'></a>", 'TBL_GROUP1');
        }
        
        $toolbarArr->add("<a class=rtbutton title='Линия' onclick=\"rp('[hr]', document.getElementById('{$formId}'))\">-</a>", 'TBL_GROUP2');
        
        $toolbarArr->add("<a class=rtbutton style='font-weight:bold; color:blue' title='Сини букви' onclick=\"s('[color=blue]', '[/color]', document.getElementById('{$formId}'))\">A</a>", 'TBL_GROUP2');
        
        $toolbarArr->add("<a class=rtbutton style='font-weight:bold; color:red' title='Червени букви' onclick=\"s('[color=red]', '[/color]', document.getElementById('{$formId}'))\">A</a>", 'TBL_GROUP2');
        
        $toolbarArr->add("<a class=rtbutton style='font-weight:bold; background: yellow;' title='Жълт фон' onclick=\"s('[bg=yellow]', '[/bg]', document.getElementById('{$formId}'))\">A</a>", 'TBL_GROUP2');
        
        $toolbarArr->add("<a class=rtbutton style='font-weight:bold; background: white;' title='Код' onclick=\"s('[code=php]', '[/code]', document.getElementById('{$formId}'))\">Код</a>", 'TBL_GROUP2');
        
        $toolbarArr->add("<a class=rtbutton style='font-weight:bold;' title='Удебелен текст' onclick=\"s('[b]', '[/b]', document.getElementById('{$formId}'))\">b</a>", 'TBL_GROUP2');
        
        $toolbarArr->add("<a class=rtbutton style='font-style:italic;' title='Наклонен текст' onclick=\"s('[i]', '[/i]', document.getElementById('{$formId}'))\">i</a>", 'TBL_GROUP2');
        
        $toolbarArr->add("<a class=rtbutton style='text-decoration:underline;' title='Подчертан текст' onclick=\"s('[u]', '[/u]', document.getElementById('{$formId}'))\">u</a>", 'TBL_GROUP2');
        
        $toolbarArr->add("<a class=rtbutton title='Линк' onclick=\"s('[link=http://]', '[/link]', document.getElementById('{$formId}'))\">линк</a>", 'TBL_GROUP2');
        
        if(!Mode::is('screenMode', 'narrow')) {
            $toolbarArr->add("<a class=rtbutton title='Заглавие 1' onclick=\"s('[h1]', '[/h1]', document.getElementById('{$formId}'))\">H1</a>", 'TBL_GROUP3');
            $toolbarArr->add("<a class=rtbutton title='Заглавие 2' onclick=\"s('[h2]', '[/h2]', document.getElementById('{$formId}'))\">H2</a>", 'TBL_GROUP3');
            $toolbarArr->add("<a class=rtbutton title='Заглавие 3' onclick=\"s('[h3]', '[/h3]', document.getElementById('{$formId}'))\">H3</a>", 'TBL_GROUP3');
            $toolbarArr->add("<a class=rtbutton title='Списък' onclick=\"rp('[li] ', document.getElementById('{$formId}'))\">LI</a>", 'TBL_GROUP3');
        }
        
        $this->invoke('AfterGetToolbar', array(&$toolbarArr, &$attr));
        
        return $toolbarArr;
    }
}