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
        $tpl = new ET("<span class='richEdit' style='width:100%;'>[#TEXTAREA#]<div class='richedit-toolbar'>[#TBL_GROUP1#]&nbsp;[#TBL_GROUP2#]&nbsp;[#TBL_GROUP3#]</div></span>");
        
        if(Mode::is('screenMode', 'narrow')) {
            $attr['style'] .= 'min-width:380px;width:100%;';
            setIfNot($attr['rows'], $this->params['rows'], 7);
        } else {
            $attr['style'] .= 'width:100%;';
            setIfNot($attr['rows'], $this->params['rows'], 10);
        }
        
        // Атрибута 'id' се сетва с уникален такъв, ако не е зададен
        ht::setUniqId($attr);
        
        $attr['onselect'] = 'sc(this);';
        $attr['onclick'] = 'sc(this);';
        $attr['onkeyup'] = 'sc(this);';
        $attr['onchange'] = 'sc(this);';
        
        
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

        // if($ret = core_Cache::get(RICHTEXT_CACHE_TYPE, $md5, 1000)) {
        //     return $ret;
        // }
        
        // Място, където съхраняваме нещата за субституция
        $this->_htmlBoard = array();
        
        // Уникален маркер, който ще се използва за временните плейсхолдери
        $this->randMark = rand(1, 2000000000);
        
        // Задаваме достатъчно голям буфер за обработка на регулярните изрази
        ini_set('pcre.backtrack_limit', '2M');
        
        
        // Обработваме [html] ... [/html] елементите, които могат да съдържат чист HTML код
        $html = preg_replace_callback("/\[html](.*?)\[\/html\]([\r\n]{0,2})/is", array($this, '_catchHtml'), $html);
        
        // Премахваме всичкото останало HTML форматиране
        $html = str_replace(array("&", "<"), array("&amp;", "&lt;"), $html);
        
        $html = core_ET::escape($html);

		if(count($this->_htmlBoard)) {
			foreach($this->_htmlBoard as $place => $cnt) {
				$replaceFrom[] = core_ET::escape("[#$place#]");
				$replaceTo[] = "[#$place#]";
			}
			
			// Възстановяваме началното състояние
			$html = str_replace($replaceFrom, $replaceTo, $html);
		}
        
        // Обработваме [code=????] ... [/code] елементите, които трябва да съдържат програмен код
        $html = preg_replace_callback("/\[code(=([a-z0-9]{1,32})|)\](.*?)\[\/code\]([\r\n]{0,2})/is", array($this, '_catchCode'), $html);
        
        // Обработваме [img=http://????] ... [/img] елементите, които представят картинки с надписи под тях
        $html = preg_replace_callback("/\[img(=([^\]]*)|)\](.*?)\[\/img\]/is", array($this, '_catchImage'), $html);
        
        // Обработваме [gread=http://????] ... [/gread] елементите, които представят картинки с надписи под тях
        $html = preg_replace_callback("/\[gread(=([^\]]*)|)\](.*?)\[\/gread\]/is", array($this, '_catchGread'), $html);
        
        // Обработваме [link=http://????] ... [/link] елементите, които задават фон за буквите на текста между тях
        $html = preg_replace_callback("/\[link(=([^\]]*)|)\](.*?)\[\/link\]/is", array($this, '_catchLink'), $html);
        
        // Обработваме [hide=caption] ... [/hide] елементите, които скриват/откриват текст
        $html = preg_replace_callback("/\[hide(=([^\]]*)|)\](.*?)\[\/hide\]/is", array($this, '_catchHide'), $html);
        
        
        // Обработваме хипервръзките, зададени в явен вид
        $html = preg_replace_callback("#((www\.|http://|https://|ftp://|ftps://|nntp://)[^\s<>()]+)#i", array($this, '_catchUrls'), $html);
        
        // Обработваме имейлите, зададени в явен вид
        $html = preg_replace_callback("/(\S+@\S+\.\S+)/i", array($this, '_catchEmails'), $html);
        
        // H!..6
        $html = preg_replace_callback("/\[h([1-6])\](.*?)\[\/h[1-6]\]([\r\n]{0,2})/is", array($this, '_catchHeaders'), $html);
        
        // Даваме възможност други да правят обработки на текста
        $this->invoke('AfterCatchRichElements', array(&$html));

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
        $html = preg_replace_callback("/\[li](.*?)((<br>)|(\n))/is", array($this, '_catchLi'), $html);
        
        // Обработваме елемента [lio]
        $html = preg_replace_callback("/\[lio](.*?)((<br>)|(\n))/is", array($this, '_catchLio'), $html);
        
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
                if(trim($l)) {
                    $empty = 0;
                } else {
                    $empty++;
                }
                
                if($empty <2) {
                    $st1 .= $l . "<br>\n";
                }
            }
            
            $html = $st1;
            
            $html = str_replace(array('<b></b>', '<i></i>', '<u></u>'), array('', '', ''), $html);
        }
        

        $html =  new ET("<div class=\"richtext\">{$html}</div>");

        if(count($this->_htmlBoard)) {
           $html->placeArray($this->_htmlBoard);
        }

         
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
            $this->_htmlBoard[$place] = $match[1];
            $res = "[#{$place}#]";
			$this->_htmlBoard['html1'] = TRUE;
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
            $res = " o {$text}\n";
        }
        
        return $res;
    }
    
    
    /**
     * Заменя [html] ... [/html]
     */
    function _catchLio($match)
    {
        $text = $match[1];
        
        if(!Mode::is('text', 'plain')) {
            $res = "<li class='lio' type='circle'>$text</li>\n";
        } else {
            $res = "   - {$text}\n";
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
        
        $this->_htmlBoard[$place] = "<div><img src=\"{$url}\" 1style='12max-width:750px;' alt=\"{$title}\"><br><small>";
        
        return "[#{$place}#]{$title}</small></div>";
    }
    
    
    /**
     * Заменя [gread=????] ... [/gread]
     */
    function _catchGread($match)
    {
        $place = $this->getPlace();
        $url = urlencode(core_Url::escape($match[2]));
        
        $title = htmlentities($match[3], ENT_COMPAT, 'UTF-8');
        
        $this->_htmlBoard[$place] = "<div><iframe src=\"http://docs.google.com/gview?url={$url}&embedded=true\" style=\"width:600px; height:500px;\" frameborder=\"0\"></iframe><br><small>";
        
        return "[#{$place}#]{$title}</small></div>";
    }
    
    
    /**
     * Заменя елемента [code=???] .... [/code]
     */
    function _catchCode($match)
    {
        $place = $this->getPlace();
        $code = $match[3];
        
        if(!trim($code)) return "";
        $lg = $match[2];
        if($lg) {
            // $Geshi = cls::get('geshi_Import');
            // $code1 = $Geshi->renderHtml(html_entity_decode(trim($code)), $lg) ;
            
            $code1 = "<pre class='richtext code'>" . trim($code) . "</pre>";;
        } else {
            $code1 = "<pre class='richtext'>" . trim($code) . "</pre>";;
        }
        
        $this->_htmlBoard[$place] = $code1;
        
        return "[#{$place}#]";
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
         
        if(core_Url::isLocal($url, $rest)) {
            $link = $this->internalLink($url, $title, $place, $rest);
        } else {
            $link = $this->externalLink($url, $title, $place);
        }
        
        return $link;
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
        $link = "<a href=\"[#{$place}#]\">{$title}</a>";

        return $link;
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
        $titlePlace = $this->getPlace();
        $this->_htmlBoard[$titlePlace] = $title;
        if($title{0} != ' ') {
            $urlArr = parse_url($url);
            $domain = $urlArr['host'];
            $bgPlace = $this->getPlace();
            $this->_htmlBoard[$bgPlace] = "background-image:url('http://www.google.com/s2/u/0/favicons?domain={$domain}');";
            $link = "<a href=\"[#{$place}#]\" target=\"_blank\" class=\"out linkWithIcon\" style=\"[#{$bgPlace}#]\">[#{$titlePlace}#]</a>";
        } else {
            $link = "<a href=\"[#{$place}#]\" target=\"_blank\" class=\"out\">[#{$titlePlace}#]</a>";
        }

        return $link;
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
        
        $html = "<a href=\"javascript:toggleDisplay('{$id}')\"  style=\"font-weight:bold; background-image:url(" . sbf('img/16/plus.png', "'") . ");\" 
                   class=\"linkWithIcon\">{$title}</a><div class='clearfix21 richtextHide' id='{$id}'>";
        
        $this->_htmlBoard[$place] =  $html;
        
        return "[#{$place}#]{$text}</div>";
    }
    
    
    /**
     * Замества [color=????] елементите
     */
    function _catchColor($match)
    {
        $color = parent::escape($match[2]);
        
        if(!$color) $color = 'black';
        
        return "<span style=\"color:{$color}\">";
    }
    
    
    /**
     * Замества [bg=????] елементите
     */
    function _catchBg($match)
    {
        $color = parent::escape($match[2]);
        
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
    function _catchUrls($html)
    {   
        $url = rtrim($html[0], ',.;');

        if(!stripos($url, '://') && (stripos($url, 'www.') === 0)) {
            $url = 'http://' . $url;
        }

        $result = core_Url::escape($url);
        
        if(!Mode::is('text', 'plain')) {
            if( core_Url::isLocal($url, $rest) ) {
                $result = $this->internalUrl($url, str::limitLen($url,120), $rest);
            } else {
                $result = $this->externalUrl($url, str::limitLen($url,120));
            }
        }
        
        return $result;
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
        return "<a href=\"{$url}\">{$title}</a>";
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
        $link = "<a href=\"{$url}\" target='_blank' class='out'>{$title}</a>";
        
        return $link;        
    }


    /**
     * Прави субституция на имейлите
     */
    function _catchEmails($match)
    {
        $email = $match[0];
        
        $emlType = cls::get('type_Email');

        if($emlType->isValidEmail($email)) { 
            $email = $emlType->toVerbal($email);
        }

        return $email;
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