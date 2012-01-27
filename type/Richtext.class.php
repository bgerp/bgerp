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
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $tpl = new ET("[#TEXTAREA#]<div class='richedit'>[#LEFT_TOOLBAR#]&nbsp;[#RIGHT_TOOLBAR#]</div>");
        
        if(Mode::is('screenMode', 'narrow')) {
            setIfNot($attr['rows'], $this->params['rows'], 7);
        } else {
            setIfNot($attr['rows'], $this->params['rows'], 10);
        }
        
        // Атрибута 'id' се сетва с уникален такъв, ако не е зададен
        ht::setUniqId($attr);
        
        $formId = $attr['id'];
        
        $attr['style'] = ' border: 1px solid #888; border-bottom: 1px solid white;overflow:auto;' . $attr['style'];
        $attr['onselect'] = 'sc(this);';
        $attr['onclick'] = 'sc(this);';
        $attr['onkeyup'] = 'sc(this);';
        $attr['onchange'] = 'sc(this);';
        
        $attr['style'] .= 'min-width:568px;';
        
        $tpl->append(ht::createTextArea($name, $value, $attr), 'TEXTAREA');
        
        $tpl->prepend("
            <a class='rtbutton1' title='Усмивка' onclick=\"rp('[em=smile]', document.getElementById('{$formId}'))\"><img src=" . sbf('img/em15/em.icon.smile.gif') . " height='15' width='15'  align='top' alt='smile'></a>
            <a class='rtbutton1' title='Широка усмивка' onclick=\"rp('[em=bigsmile]', document.getElementById('{$formId}'))\"><img src=" . sbf('img/em15/em.icon.bigsmile.gif') . " height='15' width='15'  align='top' alt='bigsmile'></a>
            <a class='rtbutton1' title='Супер!' onclick=\"rp('[em=cool]', document.getElementById('{$formId}'))\"><img src=" . sbf('img/em15/em.icon.cool.gif') . " height='15' width='15' align='top' alt='cool'></a>",
            'LEFT_TOOLBAR');
        
        if(!Mode::is('screenMode', 'narrow')) {
            
            $tpl->prepend("
                <a class='rtbutton1' title='Бира' onclick=\"rp('[em=beer]', document.getElementById('{$formId}'))\"><img alt='Бира' src=" . sbf('img/em15/em.icon.beer.gif') . " height='15' width='15'></a>
                <a class='rtbutton1' title='Въпрос?' onclick=\"rp('[em=question]', document.getElementById('{$formId}'))\"><img alt='Въпрос?' src=" . sbf('img/em15/em.icon.question.gif') . " height='15' width='15' ></a>
                <a class='rtbutton1' title='Сърце' onclick=\"rp('[em=heart]', document.getElementById('{$formId}'))\"><img alt='Сърце' src=" . sbf('img/em15/em.icon.heart.gif') . " height='15' width='15'></a>
                <a class='rtbutton1' title='OK' onclick=\"rp('[em=ok]', document.getElementById('{$formId}'))\"><img alt='OK' src=" . sbf('img/em15/em.icon.ok.gif') . " height='15' width='15'></a>
                <a class='rtbutton1' title='Мисля' onclick=\"rp('[em=think]', document.getElementById('{$formId}'))\"><img alt='Мисля' src=" . sbf('img/em15/em.icon.think.gif') . " height='15' width='15'></a>",
                'LEFT_TOOLBAR');
        }
        
        $tpl->append("
            <a class=rtbutton title='Линия' onclick=\"rp('[hr]', document.getElementById('{$formId}'))\">-</a>
            <a class=rtbutton style='font-weight:bold; color:blue' title='Сини букви' onclick=\"s('[color=blue]', '[/color]', document.getElementById('{$formId}'))\">A</a>
            <a class=rtbutton style='font-weight:bold; color:red' title='Червени букви' onclick=\"s('[color=red]', '[/color]', document.getElementById('{$formId}'))\">A</a>
            <a class=rtbutton style='font-weight:bold; background: yellow;' title='Жълт фон' onclick=\"s('[bg=yellow]', '[/bg]', document.getElementById('{$formId}'))\">A</a>
            <a class=rtbutton style='font-weight:bold; background: white;' title='Код' onclick=\"s('[code=php]', '[/code]', document.getElementById('{$formId}'))\">Код</a>",
            'RIGHT_TOOLBAR');
        
        if(Mode::is('screenMode', 'narrow')) {
            //    $tpl->append("<p style='margin-top:5px'>", 'RIGHT_TOOLBAR');
        }
        
        $id = $attr['id'];
        
        if($this->params['bucket']) {
            
            $callbackName = 'placeFile_' . $id;
            
            $callback = "function {$callbackName}(fh, fName) { 
                var ta = get$('{$id}');
                rp(\"\\n\" + '[file=' + fh + ']' + fName + '[/file]', ta);
                return true;
            }";
            
            $tpl->appendOnce($callback, 'SCRIPTS');
            
            if(Mode::is('screenMode', 'narrow')) {
                $args = 'resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
            } else {
                $args = 'width=400,height=320,resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
            }
            
            $bucketId = fileman_Buckets::fetchField("#name = '" . $this->params['bucket'] . "'", 'id');
            $url = fileman_Files::getUrLForAddFile($bucketId, $callbackName);
            $js = "openWindow('{$url}', '{$windowName}', '{$args}'); return false;";
            
            $fileUpload = "<a class=rtbutton title='Прикачен файл' onclick=\"{$js}\">файл</a>";
        }
        
        $tpl->append("
            <a class=rtbutton style='font-weight:bold;' title='Удебелен текст' onclick=\"s('[b]', '[/b]', document.getElementById('{$formId}'))\">b</a>
            <a class=rtbutton style='font-style:italic;' title='Наклонен текст' onclick=\"s('[i]', '[/i]', document.getElementById('{$formId}'))\">i</a>
            <a class=rtbutton style='text-decoration:underline;' title='Подчертан текст' onclick=\"s('[u]', '[/u]', document.getElementById('{$formId}'))\">u</a> 
            {$fileUpload}
            <a class=rtbutton title='Линк' onclick=\"s('[link=http://]', '[/link]', document.getElementById('{$formId}'))\">линк</a>",
            'RIGHT_TOOLBAR');
        
        if(!Mode::is('screenMode', 'narrow')) {
            
            $tpl->append("
            <a class=rtbutton title='Заглавие 1' onclick=\"s('[h1]', '[/h1]', document.getElementById('{$formId}'))\">H1</a>
            <a class=rtbutton title='Заглавие 2' onclick=\"s('[h2]', '[/h2]', document.getElementById('{$formId}'))\">H2</a>
            <a class=rtbutton title='Заглавие 3' onclick=\"s('[h3]', '[/h3]', document.getElementById('{$formId}'))\">H3</a>
            <a class=rtbutton title='Списък' onclick=\"rp('[li] ', document.getElementById('{$formId}'))\">LI</a>",
                'RIGHT_TOOLBAR');
        }
        
        return $tpl;
    }
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    function toVerbal($value) {
        if(!$value) return NULL;
        
        if (Mode::is('text', 'plain')) {
            return $this->richtext2text($value);
        }
        
        // TODO
        return $this->toHtml($value);
    }
    
    
    /**
     * Преобразува текст, форматиран с метатагове (BB) в HTML
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
     * o [file=fileHandler]upload_name[/file] - хипервръзка сочеща прикачен файл
     * o [em={code}] - емотикони
     *
     * @param string $richtext
     * @return string
     */
    function toHtml($html)
    {
        if(!$html) return new ET("");
        
        $md5 = md5($html);
        
        if($ret = core_Cache::get(RICHTEXT_CACHE_TYPE, $md5, 1000)) {
            
            //return $ret;
        }
        
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
        $html = preg_replace_callback("/\[code(=([^\]]*)|)\](.*?)\[\/code\]/is", array($this, '_catchCode'), $html);
        
        // Обработваме [file=?????] ... [/file] елементите, които  съдържат връзки към файлове
        $html = preg_replace_callback("/\[file(=([^\]]*)|)\](.*?)\[\/file\]/is", array($this, '_catchFile'), $html);
        
        // Обработваме [img=http://????] ... [/img] елементите, които представят картинки с надписи под тях
        $html = preg_replace_callback("/\[img(=([^\]]*)|)\](.*?)\[\/img\]/is", array($this, '_catchImage'), $html);
        
        // Обработваме [link=http://????] ... [/link] елементите, които задават фон за буквите на текста между тях
        $html = preg_replace_callback("/\[link(=([^\]]*)|)\](.*?)\[\/link\]/is", array($this, '_catchLink'), $html);
        
        // Даваме възможност други да правят обработки на текста
        $this->invoke('catchRichElements', array($this, &$html));
        
        // Обработваме хипервръзките, зададенив явен вид
        $html = preg_replace_callback("#((?:https?|ftp|ftps|nntp)://[^\s<>()]+)#i", array($this, '_catchHyperlinks'), $html);
        
        // Нормализираме знаците за край на ред и обработваме елементите без параметри
        $from = array("\r\n", "\n\r", "\r", "\n", "\t", '[/color]', '[/bg]', '[hr]', '[b]', '[/b]', '[u]', '[/u]', '[i]', '[/i]', '[h1]', '[h2]', '[h3]', '[h4]', '[/h1]', '[/h2]', '[/h3]', '[/h4]', '[/h5]', '[/h6]');
        $to = array("\n", "\n", "\n", "<br>\n", "&nbsp;&nbsp;&nbsp;&nbsp;", '</span>', '</span>', '<hr>', '<b>', '</b>', '<u>', '</u>', '<i>', '</i>', '<h1>', '<h2>', '<h3>', '<h4>', '</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>');
        $html = str_replace($from, $to, $html);
        
        // Обработваме елементите [color=????]  
        $html = preg_replace_callback("/\[color(=([^\]]*)|)\]\s*/si", array($this, '_catchColor'), $html);
        
        // Обработваме елементите [bg=????]  
        $html = preg_replace_callback("/\[bg(=([^\]]*)|)\]\s*/si", array($this, '_catchBg'), $html);
        
        // Обработваме елемента [li]
        $html = preg_replace_callback("/\[li](.*?)<br>/is", array($this, '_catchLi'), $html);
        $html = str_replace("[li]", "<li>", $html);
        
        // Поставяме емотиконите на местата с елемента [em=????]
        $html = preg_replace_callback("/\[em(=([^\]]+)|)\]/is", array($this, '_catchEmoticons'), $html);
        
        // Заменяме обикновените интервали в началото на всеки ред, с напрекъсваеми такива
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
        
        if(count($this->_htmlBoard)) {
            foreach($this->_htmlBoard as $place => $txt) {
                $html = str_replace("__{$place}__", $txt, $html);
            }
        }
        
        $html = new ET("<div class=\"richtext\">[#1#]</div>", $html);
        
        //$html->push('css/richtext.css', 'CSS');
        
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
        $place = $this->getPlace();
        
        $this->_htmlBoard[$place] = $match[1];
        
        return "__{$place}__";
    }
    
    
    /**
     * Заменя [html] ... [/html]
     */
    function _catchLi($match)
    {
        $text = $match[1];
        
        return "<li>$text</li>\n";
    }
    
    
    /**
     * Заменя [img=????] ... [/img]
     */
    function _catchImage($match)
    {
        $place = $this->getPlace();
        $url = $match[2];
        
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
        $lg = $match[2];
        
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
        $url = $match[2];
        
        $this->_htmlBoard[$place] = htmlentities($url);
        
        return "<a href=__{$place}__>{$title}</a>";
    }
    
    
    /**
     * Заменя елементите [file=?????]......[/link]
     */
    function _catchFile($match)
    {
        $title = $match[3];
        $fh = $match[2];
        
        return fileman_Download::getDownloadLink($fh);
    }
    
    
    /**
     * Връща коректното шестнадесетично представяне на зададения цвят
     */
    function toColor($color)
    {
        $color = trim(mb_strtolower(str_replace(" ", "", $color)));
        
        if (!preg_match('/([a-f0-9]{3}|[a-f0-9]{6})$/i', $color)) {
            // TO-DO
        }
        
        return $color;
    }
    
    
    /**
     * Замества [color=????] елементите
     */
    function _catchColor($match)
    {
        $color = $this->toColor($match[2]);
        
        if(!$color) $color = 'black';
        
        return "<span style=\"color:{$color}\">";
    }
    
    
    /**
     * Замества [bg=????] елементите
     */
    function _catchBg($match)
    {
        $color = $this->toColor($match[2]);
        
        if(!$color) $color = 'black';
        
        return "<span style=\"background-color:{$color}\">";
    }
    
    
    /**
     * Замества [em=????] елементите
     */
    function _catchEmoticons($match)
    {
        $em = $match[2];
        
        $iconFile = sbf("img/em15/em.icon.{$em}.gif");
        
        return "<img src={$iconFile} style='margin-left:1px; margin-right:1px;' height=15 width=15/>";
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function _catchHyperlinks($html)
    {
        $url = $html[0];
        
        $tpl = ht::createLink($url, $url);
        
        return $tpl->getContent();
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function _catchFiles($match) {
        
        return $text;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function richtext2text($richtext)
    {
        return strip_tags($this->toHtml($richtext));
        
        //return strip_tags(richtext2Html($richtext, TRUE));
    }
}
