<?php


/**
 * Тип на записите в кеша
 */
defIfNot('RICHTEXT_CACHE_TYPE', 'RichText');


/**
 * Текстове, които ще се удебеляват автоматично
 * @type type_Set
 */
defIfNot('RICHTEXT_BOLD_TEXT', 'За,Отн,Относно,回复,转发,SV,VS,VS,VL,RE,FW,FRW,TR,AW,WG,ΑΠ,ΣΧΕΤ,ΠΡΘ,R,RIF,I,SV,FS,SV,VB,RE,RV,RES,ENC,Odp,PD,YNT,İLT');


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
class type_Richtext extends type_Blob 
{
    
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
     * Шаблон за болдване на текст
     */
    static $boldPattern = NULL;
    
    
    /**
     * Максимална дължина на едноредов коментар
     */
    const ONE_LINE_CODE_LENGTH = 120;
    
    
    /**
     * Шаблон за намиране на линкове в текст
     */
    const URL_PATTERN = "/(((http(s?)|ftp(s?)):\/\/)|(www\.))([^<>\x{A0}\s\"\']+[a-z0-9_\/\#\%\+\*\$\@\-\=\\\\:])/i";
    

    /**
     * Шаблон за намиране на цитати в текст
     */
    const QUOTE_PATTERN = "#\[bQuote(=([^\]]+)|)\]((?:[^[]|\[(?!/?bQuote(=([^\]]+)|)\])|(?R))+)\[\/bQuote\]#misu";
    
    
    /**
     * Заместител на [bQuote=???]
     */
    const BQUOTE_DIV_BEGIN = "<div class='richtext-quote no-spell-check'>";
    
    
	/**
     * Инициализиране на типа
     * Задава, че да се компресира
     */
    function init($params = array())
    {
        // По подразбиране да се компресира
        setIfNot($params['params']['compress'], 'compress');
        
        // По подразбиране е средно голямо
        setIfNot($params['params']['size'], 1000000);

        // Ако е зададено да не се компресира
        if ($params['params']['compress'] == 'no') {
            
            // Премахваме от масива
            unset($params['params']['compress']);
        }
        
        parent::init($params);
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $tpl = new ET("<div class='richEdit'>[#TEXTAREA#]<div class='richedit-toolbar {$attr['errorClass']}'>[#TBL_GROUP1#][#TBL_GROUP2#][#TBL_GROUP3#]</div></div>");
        
        if(Mode::is('screenMode', 'narrow')) {
            setIfNot($attr['rows'], $this->params['rows'], 7);
        } else {
            setIfNot($attr['rows'], $this->params['rows'], 10);
        }
        
        // Атрибута 'id' се сетва с уникален такъв, ако не е зададен
        ht::setUniqId($attr);
        
        $attr['onselect'] .= ($attr['onselect']) ? ' ' : '';
        $attr['onclick'] .= ($attr['onclick']) ? ' ' : '';
        $attr['onkeyup'] .= ($attr['onkeyup']) ? ' ' : '';
        $attr['onchange'] .= ($attr['onchange']) ? ' ' : '';
        $attr['onfocus'] .= ($attr['onfocus']) ? ' ' : '';
        $attr['onblur'] .= ($attr['onblur']) ? ' ' : '';
        
        $attr['onselect'] .= 'sc(this);';
        $attr['onclick'] .= 'sc(this);';
        $attr['onkeyup'] .= 'sc(this);';
        $attr['onchange'] .= 'sc(this);';
        $attr['onfocus'] .= "getEO().textareaFocus('{$attr['id']}');";
        $attr['onblur'] .= "getEO().textareaBlur('{$attr['id']}');";
        
        // Сигнализиране на потребителя, ако въведе по-дълъг текст от допустимото
        setIfNot($size, $this->params['size'], $this->params[0]);
        if($size > 0) {
             $attr['onblur'] .= "colorByLen(this, {$size}, true); if(this.value.length > {$size}) alert('" . 
                 tr("Въведената стойност е дълга") . " ' + this.value.length + ' " . tr("символа, което е над допустимите") . " $size " . tr('символа') . "');";
             $attr['onkeyup'] .= "colorByLen(this, {$size});";
        }

        $attr['name'] = $name;

        $tpl->append(ht::createElement('textarea', $attr, $value, TRUE), 'TEXTAREA');
        
        $toolbarArr = type_Richtext::getToolbar($attr);
        
        $toolbarArr->order();
        
        foreach($toolbarArr as $link) {
            $tpl->append($link->html, $link->place);
        }
        
        // Ако е зададено да се аппендва маркирания текст, като цитата
        if ($this->params['appendQuote']) {
            
            $line = is_numeric($this->params['appendQuote']) ? $this->params['appendQuote'] : 0;
            
            // Добавяме функцията за апендване на цитата
            jquery_Jquery::run($tpl, "appendQuote('{$attr['id']}', {$line});");
        }
        
    	jquery_Jquery::run($tpl, "hideRichtextEditGroups();");

        jquery_Jquery::run($tpl, "prepareRichtextAddElements();");
    	
    	jquery_Jquery::run($tpl, "getEO().saveSelTextInTextarea('{$attr['id']}');");
    	
    	jquery_Jquery::run($tpl, "bindEnterOnRicheditTableForm(document.getElementById('{$attr['id']}'));");
    	 
    	if(Mode::is('screenMode', 'wide')) {
    		jquery_Jquery::run($tpl, "setRicheditWidth('{$attr['id']}');");
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    function toVerbal($value)
    {
        if (!strlen($value)) return NULL;
        
        if (Mode::is('text', 'plain')) {
            $res = $this->toHtml($value);
            $res = html_entity_decode($res, ENT_QUOTES, 'UTF-8');
            $res = self::stripTags($res);
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
        Debug::startTimer('RichtextToHtml');
        
        if (!strlen($html)) return "";
        
        $textMode = Mode::get('text');

        if(!$textMode) {
            $textMode = 'html';
        }
        
        // Място, където съхраняваме нещата за субституция
        $this->_htmlBoard = array();
        
        // Уникален маркер, който ще се използва за временните плейсхолдери
        $this->randMark = rand(1, 2000000000);
        
        // Задаваме достатъчно голям буфер за обработка на регулярните изрази
        ini_set('pcre.backtrack_limit', '2M');
        
        // Намаляме стойността за да не гърми по-лош начин
        if (core_Os::isWindows()) {
            ini_set('pcre.recursion_limit', '500');
        } else {
            ini_set('pcre.recursion_limit', '16000');
        }
        
        // Заместваме й с ѝ
        $html = preg_replace('/(\ )(й)([\ \.\,\?\!]){1}/u', '${1}ѝ${3}', $html);
        
        // Обработваме [html] ... [/html] елементите, които могат да съдържат чист HTML код
        $html = preg_replace_callback("/\[html](.*?)\[\/html\]([\r\n]{0,2})/is", array($this, '_catchHtml'), $html);
        
        // Ако има лошо форматирани линкове, които започват с <http
        $html = preg_replace('/(<http[s]?\:\/\/)/i', ' ${1}', $html);
        
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
        
        // Даваме възможност други да правят обработки на текста
        $this->invoke('BeforeCatchRichElements', array(&$html));
              
        // Обработваме [img=http://????] ... [/img] елементите, които представят картинки с надписи под тях
        $html = preg_replace_callback("/\[img(=([^#][^\]]*)|)\](.*?)\[\/img\]/is", array($this, '_catchImage'), $html);
        
        // Обработваме [gread=http://????] ... [/gread] елементите, които ифрейм на google read
        $html = preg_replace_callback("/\[gread(=([^\]]*)|)\](.*?)\[\/gread\]/is", array($this, '_catchGread'), $html);
        
        // Обработваме [link=http://????] ... [/link] елементите, които представляват описания на хипервръзки
        $html = preg_replace_callback("/\[link(=([^\]]*)|)\](.*?)\[\/link\]/is", array($this, '_catchLink'), $html);
        
        // Обработваме [hide=caption] ... [/hide] елементите, които скриват/откриват текст
        $html = preg_replace_callback("/\[hide(=([^\]]*)|)\](.*?)\[\/hide\]/is", array($this, '_catchHide'), $html);
        
        // Обработваме едноредовите кодове: стрингове
        $html = preg_replace_callback("/(?'ap'\`)(?'text'.{1," . static::ONE_LINE_CODE_LENGTH . "}?)(\k<ap>)/u", array($this, '_catchOneLineCode'), $html);
        
        // H!..6
        $html = preg_replace_callback("/\[h([1-6])\](.*?)\[\/h[1-6]\]([\r\n]{0,2})/is", array($this, '_catchHeaders'), $html);
        
        // Даваме възможност други да правят обработки на текста
        $this->invoke('AfterCatchRichElements', array(&$html));

        // Вземаме шаблона за намиране на текста, който ще се болдва
        $patternBold = static::getRichTextPatternForBold();
        
        // Ако има шаблон
        if ($patternBold) {
            
            // Търсим в шаблона
            $html = preg_replace_callback($patternBold, array($this, '_catchBold'), $html);   
        }
        
        // Заменя текстово описаните таблици с вертикални черти с HTML таблици 
        $html = $this->replaceTables($html);
        
        //Ако няма параметър noTrim, тогава тримваме стойността
        if (!$this->params['noTrim']) {
            
            //Тримвано стойността
            $html = trim($html);
        }
        
        // Заменя таговете с HTML такива
        $html = $this->replaceTags($html);   

        // Обработваме елементите [color=????]  
        $html = preg_replace_callback("/\[color(=([^\]]*)|)\]\s*/si", array($this, '_catchColor'), $html);
        
        // Обработваме елементите [bg=????]  
        $html = preg_replace_callback("/\[bg(=([^\]]*)|)\]\s*/si", array($this, '_catchBg'), $html);
        
        // Поставяме емотиконите на местата с елемента [em=????]
        $html = preg_replace_callback("/\[em(=([^\]]+)|)\]/is", array($this, '_catchEmoticons'), $html);

        // Обработваме елемента [li]
        $html = self::replaceList($html);
        
        // Обработваме [bQuote=????] ... [/bQuote] елементите, които трябва да съдържат програмен код \[bQuote
        // Ако възникне грешка при обработката, да не се прави никаква обработка
        if ($bQHtml = preg_replace_callback(self::QUOTE_PATTERN, array($this, '_catchBQuote'), $html)) {
            $html = $bQHtml;
        } else if ($html) {
            
            // Опитваме се поне да заместим цитатите
            $html = str_replace('[/bQuote]', "</div>", $html);
            $html = preg_replace_callback("/\[bQuote(=([^\]]+)){0,1}\]/i", array($this, '_catchBQuoteSingle'), $html);
        }
        
        $from = array("[bQuote]", "[/bQuote]");
        if(!Mode::is('text', 'plain')) {
            $to = array(self::BQUOTE_DIV_BEGIN, "</div>");
        } else {
            $to = array("", "");
        }
       // $html = str_replace($from, $to, $html);
        
        // Обработваме хипервръзките, зададени в явен вид
        $html = preg_replace_callback(self::URL_PATTERN, array($this, '_catchUrls'), $html);
        $tdlPtr = core_Url::getTldPtr();
        
        // Обработваме имейлите, зададени в явен вид
        $html = preg_replace_callback("/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.({$tdlPtr})\b/i", array($this, '_catchEmails'), $html);

        if(!Mode::is('text', 'plain')) {
            Debug::startTimer('RichtextReplaceIntervals');
            // Заменяме обикновените интервали в началото на всеки ред, с непрекъсваеми такива
            $newLine = TRUE;
            $sp = "";
            $htmlLen = strlen($html);
            for($i = 0; $i < $htmlLen; $i++) {
                
                $c = substr($html, $i, 1);
                
                if ($c == "\n") {
                    $newLine = TRUE;
                } else {
                    if ($c == " ") {
                        $c = $newLine ? ("<nbsp>") : (" ");
                    } else {
                        $newLine = FALSE;
                    }
                }
                $out .= $c;
            }
                        
            $html = str_replace(array('<b></b>', '<i></i>', '<u></u>'), array('', '', ''), $out);
            
            $html = str_replace('<nbsp>', '&nbsp;', $html);
            Debug::stopTimer('RichtextReplaceIntervals');
        }
        
        if(!Mode::is('text', 'plain')) {
            $html =  new ET("<div class=\"richtext\">{$html}</div>");
        } else {
            $html =  new ET($html);
        }

        // Подготовка и заместване на плейсхолдерите
        foreach($this->_htmlBoard as $place => $text) {
            $this->_htmlBoard[$place] = new ET($text);
        }
 
        if(count($this->_htmlBoard)) {
           $html->placeArray($this->_htmlBoard);
           $html->placeArray($this->_htmlBoard);
        }
        
        // Ако инстанция на core_ET
        if ($html instanceof core_ET) {
            
            // Вземаме съдържанието
            $cHtml = $html->getContent();
        } else {
            $cHtml = $html;
        }
        
        // Хифенира текста
        $this->invoke('AfterToHtml', array(&$cHtml));
        
        $cHtml = $this->removeEmptyLineAfterBlock($cHtml);
        
        // Ако е инстанция на core_ET
        if ($html instanceof core_ET) {
            
            // Променяме съдържанието
            $html->setContent($cHtml);
        } else {
            $html = $cHtml;
        }
        
        Debug::stopTimer('RichtextToHtml');
        
        return $html;
    }
    
    
    /**
     * Премахва 1 празен ред след блоковите елементи
     * 
     * @param string $text
     * 
     * @return string
     */
    function removeEmptyLineAfterBlock($text)
    {
        // Ако сме в текстов режим, да не се променя
        if (Mode::is('text', 'plain')) return $text;
        
        // Новият стринг, който ще се връща
        $newStr = '';
        
        // Масив с блоковите елементи, след които няма да има празен интервал
        $blockElementsArr = array('</div>', '</pre>', '</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>', '</p>', '</table>', '</pre>', '</fieldset>', '</form>', '<hr>', '</dl>', '</dd>', '</ul>', '</ol>', '</pre>');
        
        // Разделяме текста
        $textArr = explode("<br>", $text);
        
        // Броя на редовете
        $cnt = count((array)$textArr);
        
        // Обхождаме всеки ред
        foreach ((array)$textArr as $n => $line) {
            
            // Флаг, дали има блоков елемент в реда
            $haveBlock = FALSE;
            
            // За всеки блоков елемент проверяваме дали се съдържа в реда
            foreach ((array)$blockElementsArr as $blockElement) {
                
                // Ако се съдържа, прекъсваме цикъла и вдигаме флага
                if (stripos($line, $blockElement) !== FALSE) {
                    
                    $haveBlock = TRUE;
                    
                    break;
                }
            }
            
            // Ако е блоков елемент или е последния ред, добавяме стринга без нов редс
            if ($haveBlock || ($cnt == ($n-1))) {
                $newStr .= $line;
            } else {
                $newStr .= $line . "<br>";
            }
        }
        
        return $newStr;
    }
    
    
    /**
     * Функция за заместване на [li] елементите
     */
    static function replaceList($text)
    {
        Debug::startTimer('RichtextReplaceList');
        $lines = explode("\n", $text);
        $lines[] = '';
        
        $state = array();
        
        $linesCnt = count($lines);
        
        for($i = 0; $i < $linesCnt; $i++) {

            $l = $lines[$i];

            $type = '';
            $level = 0;
            if($matches = self::getListMatches($l)) {

                $indent = mb_strlen($l, 'UTF8') - mb_strlen(ltrim($matches['text']), 'UTF8');  
                 while(isset($lines[$i+1]) && (($indent == (mb_strlen($lines[$i+1]) - mb_strlen(ltrim($lines[$i+1], ' ')))) || (trim($lines[$i+1]) == '<br>'))) {
                    
                    if(trim($lines[$i+1]) == '<br>') {
                        $matches['text'] .= "<br>" . "<span style='height:5px; display:block;'></span>";
                    } else {
                        
                        // Ако следващият ред е друго 'li'
                        if (self::getListMatches($lines[$i+1])) break;
                        $matches['text'] .= ltrim($lines[$i+1]);
                    }
                    $i++;
                 }

                $level = round((strlen($matches['begin']))/2);
                $level = max($level, 1);
                                // 1,2,3,4,
                if ($matches['list']{0} == '%') {
                    $type = 'ol';
                } else {
                    $type = 'ul';
                }

                $l = "<li> " . $matches['text'] . "</li>";
            }

            while(($oldLevel = count($state)) < $level) {
                $state[$oldLevel] = $type;
                $res .= "<{$type}>";
            }
            
            while(($oldLevel = count($state)) > $level) {  
                $oldType = $state[$oldLevel-1]; 
                unset($state[$oldLevel-1]);
                $res .= "</{$oldType}>" . "<br>";
            }

            if($level == $oldLevel) {
                if($type != ($oldType = $state[$oldLevel-1])) {
                    if($oldType) {  
                        $res .= "</{$oldType}>" . "<br>";
                    }
                    if($type) {
                        $res .= "<{$type}>";
                    }
                }
                
                if($oldType && $type  ) {
                    $state[$oldLevel-1] = $type;
                }
            }

            $res .= "{$l}\n";

            $debug[] = array($l, $state, $level, $oldLevel);
        }
        
        Debug::stopTimer('RichtextReplaceList');
        
        return $res;
    }
    
    
    /**
     * Връща резултата от регулярния израз
     * 
     * @param string $line
     * 
     * @return array
     */
    protected static function getListMatches($line)
    {
        static $matchedLinesArr = array();
        
        $hash = md5($line);
        
        if (isset($matchedLinesArr[$hash])) return $matchedLinesArr[$hash];
        
        $pattern = "/^(?'begin'\ *)(?'list'\[li\]|\*\ |%\.)(?'text'.+)/i";
        
        preg_match($pattern, $line, $matches);
            
        $matchedLinesArr[$hash] = $matches;
        
        return $matchedLinesArr[$hash];
    }
    
    
    /**
     * 
     * 
     * @param unknown_type $html
     */
    function replaceTags($html)
    {
        // Уникод UTF-8 символ за неприкъсваем интервал
        $nbspUtf8 = chr(0xC2) . chr(0xA0);
 
        // Нормализираме знаците за край на ред и обработваме елементите без параметри
        $from = array("\r\n", "\n\r", "\r", "\n", "\t", $nbspUtf8, '[/color]', '[/bg]', '[b]', '[/b]', '[u]', '[/u]', '[i]', '[/i]', '[hr]', '[ul]', '[/ul]', '[ol]', '[/ol]', 
            
        '[bInfo]', '[/bInfo]', '[bTip]', '[/bTip]', '[bOk]', '[/bOk]', '[bWarn]', '[/bWarn]', '[bQuestion]', '[/bQuestion]', '[bError]', '[/bError]', '[bText]', '[/bText]', '[s]', '[/s]', '[small]', '[/small]'); 
         
        $textMode = Mode::get('text');
        
        if($textMode != 'plain') { 
            $to = array("\n", "\n", "\n", "<br>\n", "<nbsp><nbsp><nbsp><nbsp>", '<nbsp>', '</span>', '</span>', '<b>', '</b>', '<u>', '</u>', '<i>', '</i>', '<hr>', '<ul>', '</ul>', '<ol>', '</ol>', '<div class="richtext-message richtext-info">', '</div>' , '<div class="richtext-message richtext-tip">', '</div>' , '<div class="richtext-message richtext-success">', '</div>', '<div class="richtext-message richtext-warning">', '</div>', '<div class="richtext-message richtext-question">', '</div>', '<div class="richtext-message richtext-error">', '</div>', '<div class="richtext-message richtext-text">', '</div>', '<span class="strike-text">', '</span>', '<small>', '</small>');
               // '[table>', '[/table>', '[tr>', '[/tr>', '[td>', '[/td>', '[th>', '[/th>');
        } elseif(Mode::is('ClearFormat')) {
           $to   = array("\n",   "\n",   "\n",  "\n", "    ", $nbspUtf8, '',  '',  '',  '',  '',  '',  '',  '', "\n", '', '', '', '', "\n", "\n" , "\n", "\n", "\n", "\n" , "\n", "\n", "\n", "\n" , "\n", "\n", "\n", "\n", '', '');
            // "", "", "\n", "\n", "\t", ' ', "\t", ' ');
        } else {
            $to   = array("\n",   "\n",   "\n",  "\n", "    ", $nbspUtf8, '',  '',  '*',  '*',  '',  '',  '',  '', str_repeat('_', 84), '', '', '', '', "\n", "\n" , "\n", "\n", "\n", "\n" , "\n", "\n", "\n", "\n" , "\n", "\n", "\n", "\n", '', '');
            // "", "", "\n", "\n", "\t", ' ', "\t", ' ');
        }

        $html = str_replace($from, $to, $html);

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
            if(Mode::is('htmlEntity', 'none')) {
                $res = $match[1];  
                //$res = self::stripTags($match[1]);
                $res = html_entity_decode($res, ENT_QUOTES, 'UTF-8');
            } else {
                $res = html2text_Converter::toRichText($match[1]);
            }
        } else {
            $place = $this->getPlace();
            $this->_htmlBoard[$place] = $match[1];
            $res = "[#{$place}#]";
			$this->_htmlBoard['html1'] = TRUE;
        }

		return $res;
    }


    /**
     * Премахва таговете, като добавя нови редове пред някои от тях
     */
    public static function stripTags($html)
    {
        $res = str_ireplace(array('<br', '<div', '<p', '<table'),  array("\n<br", "\n<div", "\n<p", "\n<table"), $html); 
        $res = strip_tags($res);

        return $res;
    }
    
        
    /**
     * Шаблон за вкарване даден текст, в richText [b] [/b] тагове
     * нов ред или начало на текст и/или интервали един от текстовете RICHTEXT_BOLD_TEXT две точки интервал произволен текст нов ред или край на текст
     * 
     */
    static function getRichTextPatternForBold()
    {
        // Ако не е сетнат шаблона
        if (!isset(self::$boldPattern)) {
            
            // Разбиваме текстовете на масив
            $boldTextTypeArr = type_Set::toArray(RICHTEXT_BOLD_TEXT);
            
            // Обхождаме масива
            foreach ($boldTextTypeArr as $boldTextType) {
                
                // Ако е празен стринг прескачаме
                if (!($boldTextType = trim($boldTextType))) continue;
                
                // Ескейпваме текста
                $boldTextType = preg_quote($boldTextType, '/');
                
                // Добавяме към шаблона
                $boldTextPattern .= ($boldTextPattern) ? '|' . $boldTextType : $boldTextType;
            }
            
            // Ако има текст за шаблона
            if ($boldTextPattern) {
                
                // Добавяме текста в шаблона
                self::$boldPattern = "/(?'begin'([\r\n]|^){1}[\ \t]*){1}(?'text'(?'leftText'({$boldTextPattern}))(?'sign'\:\ )(?'rightText'[^\r|^\n]+))/ui";    
            } else {
                
                // Добавяме FALSE, за да не се опитваме да го определим пак
                self::$boldPattern = FALSE;
            }
        }
        
        // Връщаме резултата
        return self::$boldPattern;
    }
    
    
    /**
     * Вкарва текста който е в следната последователност: 
     * \n и/или интервали \n или в началото[Главна буква][една или повече малки букви и или интервали и или големи букви]:[интервал][произволен текст]\n или край на текста
     * в болд таг на richText
     */
    function _catchBold($match)
    {
        $res = $match['begin'] . '[b]' . $match['text'] . '[/b]';
        
        return $res;
    }
    
    
    /**
     * Заменя [img=????] ... [/img]
     */
    function _catchImage($match)
    {
        $place = $this->getPlace();
        
        if ($match[2]) {
            $url = core_Url::escape($match[2]);
            $title = htmlentities($match[3], ENT_COMPAT, 'UTF-8');
        } else {
            $url = sbf('img/error.gif', '', TRUE);
            $title = tr('Невалиден идентификатор на картинка');
        }
        
        $this->_htmlBoard[$place] = "<div><img src=\"{$url}\" alt=\"{$title}\"><br><small>";
        
        return "[#{$place}#]{$title} </small></div>";
    }
    
    
    /**
     * Заменя [gread=????] ... [/gread]
     */
    function _catchGread($match)
    {
        $place = $this->getPlace();
        $url = urlencode(core_Url::escape($match[2]));
        
        $title = htmlentities($match[3], ENT_COMPAT, 'UTF-8');
        
        $this->_htmlBoard[$place] = "<div><iframe src=\"//docs.google.com/gview?url={$url}&embedded=true\" style=\"width:600px; height:500px;\" frameborder=\"0\"></iframe><br><small>";
        
        return "[#{$place}#]{$title}</small></div>";
    }
    
    
    /**
     * Заменя елемента [code=???] .... [/code]
     */
    function _catchCode($match)
    {
        $place = $this->getPlace();
        $code = $match[3];
        $end = $match[4];
        
        $code = str_replace("\r\n", "\n", $code);

        if($code{0} == "\n") {
            $code = substr($code, 1);
        }

        if(substr($code, -1) == "\n") {
            $code = substr($code, 0, strlen($code) - 1);
        }

        if(!trim($code)) return "";

        $lg = $match[2];

        if($lg && $lg != 'text') {
            if ($lg != 'auto') {
                $classLg = " {$lg}";
            }
            $res = "<pre class='rich-text code{$classLg} no-spell-check'><code>[#{$place}#]</code></pre>" . $end; 
        } else {
           // $code = str_replace("\n", "<br>", $code);
            $res = "<pre class='rich-text no-spell-check'>[#{$place}#]</pre>" . $end;
        }
        
        $this->_htmlBoard[$place] = rtrim($code);
        
        // Инвокваме кода за highlight
        $this->invoke('AfterHighLightCode');
        
        return $res;
    }
    

	/**
     * Заменя елемента [bQuote=???] .... [/bQuote]
     */
    function _catchBQuote($match)
    {  
        // Мястото
        $place = $this->getPlace();

        // Цитата
        $quote = trim($match[3]);

        // Рекурсивно извикване
        if(stripos($quote, '[bQuote') !== FALSE) {
             $quote = preg_replace_callback(self::QUOTE_PATTERN, array($this, '_catchBQuote'), $quote);
              
        }  
        
        // Премахваме водещия празен ред
        $quote = trim($quote);
        if(stripos($quote, '<br>') === 0) {
            $quote = substr($quote, 4);
        }

        // Тримваме цитата
        $quote = trim($quote);
        
        // Ако няма цитата, връщаме
        if(!strlen($quote)) return "";
        
        // Ако сме в текстов режим
        if (Mode::is('text', 'plain')) {
            
            // Стринга за цитата
            $quoteStr = "> ";
            
            // Добавяме в начлоато на всеки ред стринга за цитат
            $quote = str_ireplace(array( "\r\n", "\n\r", "\n"), array("\r\n{$quoteStr}", "\n\r{$quoteStr}", "\n{$quoteStr}"), $quote);
            $quote = "\n{$quoteStr}" . $quote; 
        } else {
            
            // Добавяме в цитата, ако не сме в текстов режим
            $quote = self::BQUOTE_DIV_BEGIN . $quote . "</div>";
        }
        
        $this->invoke('afterCatchBQuote', array(&$quote, $match[2]));
        
        return $quote;
    }
    
    
    /**
     * Заменя елемента [bQuote=???]
     * Алтернатива на _catchBQuote. Когато гръме регулярния израз. Замества само [bQuote=???] със съответния div
     */
    function _catchBQuoteSingle($match)
    {
        $quote = '';
        $this->invoke('afterCatchBQuote', array(&$quote, $match[2]));
        
        $quote .= self::BQUOTE_DIV_BEGIN;
        
        return $quote;
    }
    
    
	/**
     * За едноредови коментари между апострофите
     */
    function _catchOneLineCode($match)
    {
        // Ако има част от плейсхолдер
        // За да не се вкарва в инлайн блоковите елементи
        if (strpos($match['text'], '#')) {
            
            // Обхождаме масива с дъските
            foreach ((array)$this->_htmlBoard as $htmlBoard => $dummy) {
                
                // Вземаме плейсхолдера
                $placeBoard = core_ET::toPlace($htmlBoard);
                
                // Ако се съдржа в текста
                if (strpos($match['text'], $placeBoard) !== FALSE) {
                    
                    // Връщаме текст
                    return $match[0];
                }
            }
        }
        
        // Мястото
        $place = $this->getPlace();
        
        // Кода между апострофите
        $code = $match['text'];
        
        // Тримваме текста
        $code = trim($code);
        
        // Ако е празен стринг
        if(!strlen($code)) return $match[0];
        
        // Добавяме кода в блок
        $code1 = "<span class='oneLineCode no-spell-check'>{$code}</span>";
        
        // Доабавяме в масива
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
        
        // URL' то 
        $url = trim($match[2]);
        
        // Ако сме в текстов режим
        if (Mode::is('text', 'plain')) {
            
            // Изчистваме празните интервали в началото и края
            $title = trim($title);
            
            // В зависимост от това дали имаме заглавие на линка, определяме текста
            if(substr($title, 0, 1) == '[' && substr($title, -1) == ']') {
                $text = $title;
            } else {
                $text = ($title)? "({$title}) - {$url}" : $url;
            }
            
            return $text;
        }
        
        // Ако имаме само http:// значи линка е празен
        if($url == 'http://' || $url == 'https://') {
            $url = '';
        }
        
        // Ако нямаме схема на URL-то
        if(!preg_match("/^[a-z0-9]{0,12}\:\/\//i", $url) ) {
            if($url{0} == '/') {                
                $httpBoot = getBoot(TRUE);
                if (EF_APP_NAME_FIXED !== TRUE) {
                    $app = Request::get('App');
                    $httpBoot .= '/' . ($app ? $app : EF_APP_NAME);
                }

                $url = $httpBoot . $url;
            } else {
                $url = "http://{$url}";
            }
        }
        
        if(core_Url::isLocal($url, $rest)) {
            $link = $this->internalLink($url, $title, $place, $rest);
            list($url1, $url2) = explode('#', $url, 2);
            if($url2) {
                $url2 = str::canonize($url2);
                $url = $url1 . '#' . $url2;
            } else {
                $url = $url1; 
            }
        } else {
            $link = $this->externalLink($url, $title, $place);
        }
        
        $url = core_Url::escape($url);

        $this->_htmlBoard[$place] = $url;
        
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
        if (!trim($title)) {
            $urlArr = @parse_url($url);
            $title = $urlArr['host'];
        }
        
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
        
        // Парсираме URL' то 
        $urlArr = @parse_url($url);
        
        // Домейна
        $domain = $urlArr['host'];

        // Ако няма заглавие
        if (!trim($title)) {
            
            // Използваме домейна за заглавие
            $this->_htmlBoard[$titlePlace] = $domain;
            $title = $domain;
        } else {
            // Правим обработка на елементите, които може да са вътре в линка
            $title = $this->replaceTags($title);
            $title = str_replace(
                array('[h1]', '[h2]', '[h3]', '[h4]', '[h5]', '[h6]', '[/h1]', '[/h2]', '[/h3]', '[/h4]', '[/h5]', '[/h6]'), 
                array('<b>', '<b>', '<b>', '<b>', '<b>', '<b>', '</b>', '</b>', '</b>', '</b>', '</b>', '</b>'), 
                $title);
            // Обработваме [img=http://????] ... [/img] елементите, които представят картинки с надписи под тях
            $title = preg_replace_callback("/\[img(=([^#][^\]]*)|)\](.*?)\[\/img\]/is", array($this, '_catchImage'), $title);

            $this->_htmlBoard[$titlePlace] = $title;    
        }
            
        if($title{0} != ' ') {
            
            $bgPlace = $this->getPlace();
            $thumb = new thumb_Img(array("https://plus.google.com/_/favicon?domain={$domain}", 16, 16, 'url', 'isAbsolute' => Mode::isReadOnly()));
            $iconUrl = $thumb->getUrl();
            $this->_htmlBoard[$bgPlace] = "background-image:url('{$iconUrl}');";
            
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

        if(Mode::is('text', 'plain')) {
            
            return "\n{$title}\n{$text}";
        }

        $id = 'hide' . rand(1, 1000000);
        
        $html = "<a href=\"javascript:toggleDisplay('{$id}')\"  class= 'more-btn linkWithIcon nojs' style=\"font-weight:bold; background-image:url(" . sbf('img/16/toggle1.png', "'") . ");\"
                   >{$title}</a><div class='clearfix21 richtextHide' id='{$id}'>";
        
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
        
        $path = "img/16/emotion_{$em}.png";
        
        if(!getFullPath($path)) {
            $path = "img/16/emotion_smile.png";
        }

        if(Mode::is('text', 'xhtml')) {
            $iconFile = sbf($path, '"', TRUE);
            $res = "<img src={$iconFile} style='margin-left:1px; margin-right:1px;position: relative;top: 2px;' height=16 width=16>";
        } elseif(Mode::is('text', 'plain')) {
            
            $res = self::$emoticons[$em] ? self::$emoticons[$em] : "[{$em}]";
        } else {
            $iconFile = sbf($path);
            $res = "<img src={$iconFile} style='margin-left:1px; margin-right:1px; position: relative;top: 2px;' height=16 width=16>";
        }
        
        $place = $this->getPlace();
            
        $this->_htmlBoard[$place] = $res;
        
        return "[#{$place}#]";
    }
    
    
    /**
     * Обработва хедъри-те [h1..6] ... [/h..]
     */
    function _catchHeaders($matches)
    { 
        $text  = $matches[2];
        $level = $matches[1];
        $end = $matches[3];
        
        if(!Mode::is('text', 'plain')) {
            $name = str::canonize($text);
            $res = "<h{$level} id=\"{$name}\">{$text}</h{$level}>{$end}";
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
        $html[0] = str_replace("&amp;", "&", $html[0]); 

        $url = $html[0];
        
        if ($tLen = (strlen($html[0]) - strlen($url))) {
            $trim = substr($html[0], 0 - $tLen);
        }
        
        if (!stripos($url, '://') && (stripos($url, 'www.') === 0)) {
            $url = 'http://' . $url;
        }
        
        if(!stripos($url, '://')) return $url;
        
        if(core_Url::isLocal($url, $rest)) {
            $result = $this->internalUrl($url, str::limitLen(decodeUrl($url), 120), $rest);
        } else {
            $result = $this->externalUrl($url, str::limitLen(decodeUrl($url), 120));
        }
        
        return $result . $trim;
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
        $link = $url;
        
        if(!Mode::is('text', 'plain')) {
            
            $title = type_Varchar::escape($title);
            
            $link = "<a href=\"{$url}\">{$title}</a>";    
        }
        
        $place = $this->getPlace();
            
        $this->_htmlBoard[$place] = $link;
        
        return "[#{$place}#]";
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
        $link = $url;
        
        if(!Mode::is('text', 'plain')) {
            
            $title = type_Varchar::escape($title);
            
            $link = "<a href=\"{$url}\" target='_blank' class='out'>{$title}</a>";
        }
        
        $place = $this->getPlace();
            
        $this->_htmlBoard[$place] = $link;
        
        return "[#{$place}#]";
    }


    /**
     * Прави субституция на имейлите
     */
    function _catchEmails($match)
    {
        $email = $match[0];
        
        $emlType = cls::get('type_Email');

        if($emlType->isValidEmail($email)) {
            
            $place = $this->getPlace();
            
            $this->_htmlBoard[$place] = $emlType->toVerbal($email);
            
            return "[#{$place}#]";
        }

        return $email;
    }


    /**
     * Заменя текстово описаните таблици с вертикални черти с HTML таблици 
     */
    function replaceTables($html)
    {
        if(Mode::is('text', 'plain')) {

            return $html;
        }
        
        $html = str_replace( array("\r\n", "\n\r", "\r", "\n"), array("\n", "\n", "\n", "\n"), $html);

        $lines = explode("\n", $html);
        
        $table = FALSE;
        
        foreach($lines as $l) {
            if($l{0} == '|') {
                if(!$table) {
                    $out .= "\n<div class='overflow-scroll'><table class='inlineRichTable listTable'>";
                    $table = TRUE;
                }
                $l = trim($l, " \t");
                $l = trim($l, "|");
                $out .= "<tr><td>" . str_replace('|', '</td><td>', $l) . "</td></tr>";
            } else {
                if($table) {
                    $out .= "</table></div>";
                    $table = FALSE;
                }
                
                $out .= (isset($out)) ? "\n" . $l : $l;
            }
        }
        if($table) {
            $out .= "</table>";
            $table = FALSE;
        }

        return $out;
    }


    /**
     * Връща масив с html код, съответстващ на бутоните на Richedit компонента
     */
    function getToolbar(&$attr)
    {
        $formId = $attr['id'];
        
        $toolbarArr = new core_ObjectCollection('html,place,order');


        // Ако е логнат потребител
        if (core_Users::haveRole('user')) {

            $size = log_Browsers::isRetina() ? 32 : 16;

            $toolbarArr->add("<span class='richtext-relative-group'>", 'TBL_GROUP1');
           	$toolbarArr->add("<a class='rtbutton richtext-group-title'  title='" . tr('Усмивки') .  "' onclick=\"toggleRichtextGroups('{$attr['id']}-group1', event)\"><img src=" . sbf("img/{$size}/emotion_smile.png") . " height='15' width='15'  alt='smile'></a>", 'TBL_GROUP1');
            $emot1 = 'richtext-holder-group-after';
            $toolbarArr->add("<span id='{$attr['id']}-group1' class='richtext-emoticons richtext-holder-group {$emot1}'>", 'TBL_GROUP1');
           	$toolbarArr->add("<a class='rtbutton' title='" . tr('Усмивка') .  "' onclick=\"rp('[em=smile]', document.getElementById('{$formId}'),0)\"><img src=" . sbf("img/{$size}/emotion_smile.png") . " height='15' width='15'  alt='smile'></a>", 'TBL_GROUP1');
    	    $toolbarArr->add("<a class='rtbutton' title='" . tr('Широка усмивка') .  "' onclick=\"rp('[em=bigsmile]', document.getElementById('{$formId}'),0)\"><img src=" . sbf("img/{$size}/emotion_bigsmile.png") . " height='15' width='15' alt='bigsmile'></a>", 'TBL_GROUP1');
    	    $toolbarArr->add("<a class='rtbutton' title='" . tr('Супер!') .  "' onclick=\"rp('[em=cool]', document.getElementById('{$formId}'),0)\"><img src=" . sbf("img/{$size}/emotion_cool.png") . " height='15' width='15' alt='cool'></a>", 'TBL_GROUP1');
            $toolbarArr->add("<a class='rtbutton' title='" . tr('Тъжен') .  "' onclick=\"rp('[em=sad]', document.getElementById('{$formId}'),0)\"><img src=" . sbf("img/{$size}/emotion_sad.png") . " height='15' width='15' alt='Тъжен'></a>", 'TBL_GROUP1');
    	    $toolbarArr->add("<a class='rtbutton' title='" . tr('Бира') .  "' onclick=\"rp('[em=beer]', document.getElementById('{$formId}'),0)\"><img alt='Бира' src=" . sbf("img/{$size}/emotion_beer.png") . " height='15' width='15'></a><span class='clearfix21'></span>", 'TBL_GROUP1');
    	   	$toolbarArr->add("<a class='rtbutton' title='" . tr('Въпрос?') .  "' onclick=\"rp('[em=question]', document.getElementById('{$formId}'),0)\"><img alt='Въпрос?' src=" . sbf("img/{$size}/emotion_question.png") . " height='15' width='15' ></a>", 'TBL_GROUP1');
    	    $toolbarArr->add("<a class='rtbutton' title='" . tr('Сърце') .  "' onclick=\"rp('[em=heart]', document.getElementById('{$formId}'),0)\"><img alt='Сърце' src=" . sbf("img/{$size}/emotion_heart.png")  . " height='15' width='15'></a>", 'TBL_GROUP1');
            $toolbarArr->add("<a class='rtbutton' title='" . tr('OK') .  "' onclick=\"rp('[em=ok]', document.getElementById('{$formId}'),0)\"><img alt='OK' src=" . sbf("img/{$size}/emotion_ok.png")  . " height='15' width='15'></a>", 'TBL_GROUP1');
            $toolbarArr->add("<a class='rtbutton' title='" . tr('Предупреждение') .  "' onclick=\"rp('[em=alert]', document.getElementById('{$formId}'),0)\"><img alt='Предупреждение' src=" . sbf("img/{$size}/emotion_alert.png")  . " height='15' width='15'></a>", 'TBL_GROUP1');
            $toolbarArr->add("<a class='rtbutton' title='" . tr('Мисля') .  "' onclick=\"rp('[em=think]', document.getElementById('{$formId}'),0)\"><img alt='Мисля' src=" . sbf("img/{$size}/emotion_think.png")  . " height='15' width='15'></a>", 'TBL_GROUP1');

            $toolbarArr->add("</span>", 'TBL_GROUP1');
           	$toolbarArr->add("</span>", 'TBL_GROUP1');
            
            $toolbarArr->add("<a class=rtbutton style='font-weight:bold;text-indent:1px' title='" . tr('Удебелен текст') .  "' onclick=\"s('[b]', '[/b]', document.getElementById('{$formId}'))\">B</a>", 'TBL_GROUP2');
             
            $toolbarArr->add("<a class=rtbutton style='font-weight:bold;font-style:italic;text-indent:2px;' title='" . tr('Наклонен текст') .  "' onclick=\"s('[i]', '[/i]', document.getElementById('{$formId}'))\">I</a>", 'TBL_GROUP2');
             
            $toolbarArr->add("<a class=rtbutton style='font-weight:bold;text-decoration:underline;text-indent:2px;' title='" . tr('Подчертан текст') .  "' onclick=\"s('[u]', '[/u]', document.getElementById('{$formId}'))\">U</a>", 'TBL_GROUP2');
            
            $toolbarArr->add("<span class='richtext-relative-group'>", 'TBL_GROUP2');
            $toolbarArr->add("<a class='rtbutton richtext-group-title' style='font-weight:bold; color:blue' title='" . tr('Цвят на буквите') .  "' onclick=\"toggleRichtextGroups('{$attr['id']}-group2', event)\">А</a>", 'TBL_GROUP2');
            $emot2 = 'richtext-holder-group-after';
            $toolbarArr->add("<span id='{$attr['id']}-group2' class='richtext-emoticons2 richtext-holder-group {$emot2}'>", 'TBL_GROUP2');
    	    $toolbarArr->add("<a class=rtbutton style='font-weight:bold; color:blue' title='" . tr('Сини букви') .  "' onclick=\"s('[color=blue]', '[/color]', document.getElementById('{$formId}'))\">A</a>", 'TBL_GROUP2');
    		$toolbarArr->add("<a class=rtbutton style='font-weight:bold; color:red' title='" . tr('Червени букви') .  "' onclick=\"s('[color=red]', '[/color]', document.getElementById('{$formId}'))\">A</a>", 'TBL_GROUP2');
    		$toolbarArr->add("<a class=rtbutton style='font-weight:bold; color:green' title='" . tr('Зелени букви') .  "' onclick=\"s('[color=green]', '[/color]', document.getElementById('{$formId}'))\">A</a>", 'TBL_GROUP2');
    		$toolbarArr->add("<a class=rtbutton style='font-weight:bold; color:#888' title='" . tr('Сиви букви') .  "' onclick=\"s('[color=#888]', '[/color]', document.getElementById('{$formId}'))\">A</a>", 'TBL_GROUP2');
    		$toolbarArr->add("<a class=rtbutton style='font-weight:bold; text-decoration: line-through; color:#666' title='" . tr('Сиви задраскани букви ') .  "' onclick=\"s('[s]', '[/s]', document.getElementById('{$formId}'))\">S</a>", 'TBL_GROUP2');
    		$toolbarArr->add("</span>", 'TBL_GROUP2');
            $toolbarArr->add("</span>", 'TBL_GROUP2');
            
            $toolbarArr->add("<span class='richtext-relative-group'>", 'TBL_GROUP2');
            $toolbarArr->add("<a class=rtbutton style='font-weight:bold;' title='" . tr('Цвят на фона') .  "' onclick=\"toggleRichtextGroups('{$attr['id']}-group3', event)\"><span style=' background: yellow;'>A</span></a>", 'TBL_GROUP2');
            $emot3 = 'richtext-holder-group-after';
            $toolbarArr->add("<span id='{$attr['id']}-group3' class='richtext-emoticons3 richtext-holder-group {$emot3}'>", 'TBL_GROUP2');
            $toolbarArr->add("<a class=rtbutton style='font-weight:bold;' title='" . tr('Жълт фон') .  "' onclick=\"s('[bg=yellow]', '[/bg]', document.getElementById('{$formId}'))\"><span style=' background: yellow;'>A</span></a>", 'TBL_GROUP2');
    	    $toolbarArr->add("<a class=rtbutton style='font-weight:bold;' title='" . tr('Зелен фон') .  "' onclick=\"s('[bg=lightgreen]', '[/bg]', document.getElementById('{$formId}'))\"><span style='background: lightgreen;'>A</span></a>", 'TBL_GROUP2');
    	    $toolbarArr->add("<a class=rtbutton style='font-weight:bold' title='" . tr('Червен фон') .  "' onclick=\"s('[bg=red][color=white]', '[/color][/bg]', document.getElementById('{$formId}'))\"><span style='background: red; color: white'>A</span></a>", 'TBL_GROUP2');
    	    $toolbarArr->add("<a class=rtbutton style='font-weight:bold;' title='" . tr('Черен фон') .  "' onclick=\"s('[bg=black][color=white]', '[/color][/bg]', document.getElementById('{$formId}'))\"><span style='background: black; color: white'>A</span></a>", 'TBL_GROUP2');
            $toolbarArr->add("</span>", 'TBL_GROUP2');
            $toolbarArr->add("</span>", 'TBL_GROUP2');
            
            $toolbarArr->add("<span class='richtext-relative-group'>", 'TBL_GROUP2');
            $toolbarArr->add("<a class='rtbutton richtext-group-title' title='" . tr('Заглавия') . "' onclick=\"toggleRichtextGroups('{$attr['id']}-group4', event)\"><b>H</b></a>", 'TBL_GROUP2');
            $emot4 = 'richtext-holder-group-after';
            $toolbarArr->add("<span id='{$attr['id']}-group4' class='richtext-emoticons4 richtext-holder-group {$emot4}'>", 'TBL_GROUP2');
    	    $toolbarArr->add("<a class=rtbutton title='" . tr('Заглавие') . " 1" .  "' onclick=\"s('[h1]', '[/h1]', document.getElementById('{$formId}'),1)\">H1</a>", 'TBL_GROUP2');
    	    $toolbarArr->add("<a class=rtbutton title='" . tr('Заглавие') . " 2" .  "' onclick=\"s('[h2]', '[/h2]', document.getElementById('{$formId}'),1)\">H2</a>", 'TBL_GROUP2');
    	    $toolbarArr->add("<a class=rtbutton title='" . tr('Заглавие') . " 3" .  "' onclick=\"s('[h3]', '[/h3]', document.getElementById('{$formId}'),1)\">H3</a><span class='clearfix21'></span>", 'TBL_GROUP2');
    	    $toolbarArr->add("<a class=rtbutton title='" . tr('Заглавие') . " 4" .  "' onclick=\"s('[h4]', '[/h4]', document.getElementById('{$formId}'),1)\">H4</a>", 'TBL_GROUP2');
    	    $toolbarArr->add("<a class=rtbutton title='" . tr('Заглавие') . " 5" .  "' onclick=\"s('[h5]', '[/h5]', document.getElementById('{$formId}'),1)\">H5</a>", 'TBL_GROUP2');
    	    $toolbarArr->add("<a class=rtbutton title='" . tr('Заглавие') . " 6" .  "' onclick=\"s('[h6]', '[/h6]', document.getElementById('{$formId}'),1)\">H6</a>", 'TBL_GROUP2');
    	    $toolbarArr->add("</span>", 'TBL_GROUP2');
            $toolbarArr->add("</span>", 'TBL_GROUP2');
            
                
            $toolbarArr->add("<a class=rtbutton  title='" . tr('Списък') .  "' onclick=\"s('* ','', document.getElementById('{$formId}'), 1,0,0,1)\">&#9679;</a>", 'TBL_GROUP2');
			$emot7 = 'richtext-holder-group-after';
        	$toolbarArr->add("<span class='richtext-relative-group'>", 'TBL_GROUP2');
            $toolbarArr->add("<a class='open-popup-link rtbutton'  title='" . tr('Таблица') .  "' onclick=\"toggleRichtextGroups('{$attr['id']}-group7', event); \"><img src=" . sbf("img/{$size}/table.png") . " height='15' width='15' alt='Table'></a>", 'TBL_GROUP2');
            $toolbarArr->add("<span id='{$attr['id']}-group7' class='richtext-emoticons7 richtext-holder-group {$emot7}'>", 'TBL_GROUP2');
            $toolbarArr->add("<span class='popup-table-info'><span class='popupBlock'>" . tr('Колони') . ": <br><input type = 'text' value='5' id='colTable'></span><span class='popupBlock'>" . tr('Редове') .":<br> <input type = 'text' value='3' id='rowTable'/></span><input type='button' id='getTableInfo' onclick=\"createRicheditTable(document.getElementById('{$formId}'), 1, document.getElementById('colTable').value, document.getElementById('rowTable').value );\" value='OK' /> </span>", 'TBL_GROUP2');
            $toolbarArr->add("</span>", 'TBL_GROUP2');
            $toolbarArr->add("</span>", 'TBL_GROUP2');
            
            $toolbarArr->add("<span class='richtext-relative-group'>", 'TBL_GROUP2');
            $toolbarArr->add("<a class='rtbutton richtext-group-title' title='" . tr('Блок') .  "' onclick=\"toggleRichtextGroups('{$attr['id']}-group5', event)\"> <img src=" . sbf("img/{$size}/quote.png") . " height='15' width='15' alt='Blocks'></a>", 'TBL_GROUP2');
            $emot5 = 'richtext-holder-group-after';
          	$toolbarArr->add("<span id='{$attr['id']}-group5' class='richtext-emoticons5 richtext-holder-group {$emot5}'>", 'TBL_GROUP2');
          	
          	$i=0;
            $maxBlockElementInLine = 4;
            
            // Вземаме всички блокови елементи
            $blockeElementsArr = static::getBlockElements();
          	foreach ((array)$blockeElementsArr as $name => $blockeElement) {
          	    
          	    $i++;
          	    
          	    // Начало и край на блока
          	    $begin = $blockeElement['begin'] ? $blockeElement['begin'] : $blockeElement['text'];
          	    $end = $blockeElement['end'] ? $blockeElement['end'] : $blockeElement['text'];
          	    
          	    // Нагласяме параметрите необходими за фунцкията s()
          	    $newLine = 1;
          	    $multiline = $blockeElement['multiline'] ? $blockeElement['multiline'] : 0;
          	    $maxOneLine = $blockeElement['maxOneLine'] ? $blockeElement['maxOneLine'] : 0;
          	    
          	    // Генерираме текста
                $toolbarTxt = "<a class='rtbutton' title='" . $blockeElement['title'] .
          	    		"' onclick=\"s('[{$begin}]', '[/{$end}]', document.getElementById('{$formId}'),{$newLine},{$multiline},{$maxOneLine})\">
          	    		<img src=" . $blockeElement['icon'] . " height='15' width='15' alt='" .$blockeElement['title'] . "'></a>";
          	    
                // Ако трябва да се добави разделител за нов ред
          	    if (!($i % $maxBlockElementInLine)) {
          	         $toolbarTxt .= "<span class='clearfix21'></span>";
          	    }
          	    $toolbarArr->add($toolbarTxt, 'TBL_GROUP2');
          	}
          	
    	    $toolbarArr->add("</span>", 'TBL_GROUP2');
            $toolbarArr->add("</span>", 'TBL_GROUP2');
            
            $toolbarArr->add("<span class='richtext-relative-group'>", 'TBL_GROUP3');
            $toolbarArr->add("<a class='rtbutton richtext-group-title' title='" . tr('Добавяне на файлове/документи') .  "' onclick=\"toggleRichtextGroups('{$attr['id']}-group6', event);\"><img src=" . sbf("img/{$size}/paper-clip.png") . " height='15' width='15' alt='Files'></a>", 'TBL_GROUP3');
           
            $emot6 = 'richtext-holder-group-after';
            $toolbarArr->add("<span id='{$attr['id']}-group6' class='richtext-emoticons6 richtext-holder-group {$emot6} addElements left'>", 'TBL_GROUP3');
        	$toolbarArr->add(new ET("[#filesAndDoc#]"), 'TBL_GROUP3');
    	    $toolbarArr->add("<a class=rtbutton title='" . tr("Добавяне на линк") . "' onclick=\"var linkTo = prompt('" . tr("Добавете линк") . "','http://'); if(linkTo) { s('[link=' + linkTo + ']', '[/link]', document.getElementById('{$formId}'))}\">" . tr("Линк") . "</a>", 'filesAndDoc', 1000.010);
    	    $toolbarArr->add("<a class=rtbutton title='" . tr('Добавяне на линия') .  "' onclick=\"rp('[hr]\\n', document.getElementById('{$formId}'), true)\">" . tr("Линия") . "</a>", 'filesAndDoc', 1000.030);

            $toolbarArr->add("</span>", 'TBL_GROUP3');
            $toolbarArr->add("</span><div class='clearfix21'></div>", 'TBL_GROUP3');
        } else {
            $toolbarArr->add("<span class='richtext-relative-group simple-toolbar'>", 'TBL_GROUP1');
            $toolbarArr->add(new ET("[#simpleToolbar#]"), 'TBL_GROUP1');
            $toolbarArr->add("</span><div class='clearfix21'></div>", 'TBL_GROUP1');
        }
        
        $this->invoke('AfterGetToolbar', array(&$toolbarArr, &$attr));
        
        return $toolbarArr;
    }
    
    
    /**
     * Всички блокови елементи и техните стойности
     * 
     * @param core_Mvc $mvc
     * @param array $resArr
     * @param boolean $isAbsolute
     */
    function on_AfterGetBlockElements($mvc, &$resArr, $qt='"', $isAbsolute=FALSE)
    {
        $resArr = arr::make($resArr);
        $size = log_Browsers::isRetina() ? 32 : 16;

        // Цитат
        $resArr['bQuote']['text'] = 'bQuote';
        $resArr['bQuote']['title'] = tr('Цитат');
        $resArr['bQuote']['icon'] = sbf("img/{$size}/quote.png", $qt, $isAbsolute);
        $resArr['bQuote']['maxOneLine'] = static::ONE_LINE_CODE_LENGTH;
        
        // Код
        $resArr['code']['text'] = 'code';
        $resArr['code']['begin'] = 'code=auto';
        $resArr['code']['end'] = 'code';
        $resArr['code']['title'] = tr('Код');
        $resArr['code']['icon'] =  sbf("img/{$size}/script_code_red.png", $qt, $isAbsolute);
        $resArr['code']['multiline'] = 1;
        $resArr['code']['maxOneLine'] = static::ONE_LINE_CODE_LENGTH;
        
        // Грешка
        $resArr['bError']['text'] = 'bError';
        $resArr['bError']['title'] = tr('Грешка');
        $resArr['bError']['icon'] = sbf("img/{$size}/dialog_error.png", $qt, $isAbsolute);
        
        // Успех
        $resArr['bOk']['text'] = 'bOk';
        $resArr['bOk']['title'] = tr('Успех');
        $resArr['bOk']['icon'] = sbf("img/{$size}/dialog_ok.png", $qt, $isAbsolute);
        
        // Съвет
        $resArr['bTip']['text'] = 'bTip';
        $resArr['bTip']['title'] = tr('Съвет');
        $resArr['bTip']['icon'] = sbf("img/{$size}/dialog_hint.png", $qt, $isAbsolute);
        
        // Информация
        $resArr['bInfo']['text'] = 'bInfo';
        $resArr['bInfo']['title'] = tr('Информация');
        $resArr['bInfo']['icon'] = sbf("img/{$size}/dialog_info.png", $qt, $isAbsolute);
        
        // Предупреждение
        $resArr['bWarn']['text'] = 'bWarn';
        $resArr['bWarn']['title'] = tr('Предупреждение');
        $resArr['bWarn']['icon'] = sbf("img/{$size}/dialog_warning.png", $qt, $isAbsolute);
        
        // Въпрос
        $resArr['bQuestion']['text'] = 'bQuestion';
        $resArr['bQuestion']['title'] = tr('Въпрос');
        $resArr['bQuestion']['icon'] = sbf("img/{$size}/dialog_help.png", $qt, $isAbsolute);
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
        $rest = trim($rest, '/');
        
        $restArr = explode('/', $rest);

        $params = array();
        $anchor = '';
        
        $lastPart = $restArr[count($restArr)-1];
        
        if ($lastPart && (strpos($lastPart, '#') !== FALSE)) {
            $explodeArr = explode('#', $lastPart);
            $anchor = array_pop($explodeArr);
            $lastPart = implode('#', $explodeArr);
        }
        
        $haveLastPart = FALSE;
        
        if($lastPart{0} == '?') {
           $haveLastPart = TRUE;
           $lastPart = ltrim($lastPart, '?');
           $lastPart = str_replace('&amp;', '&', $lastPart);
           parse_str($lastPart, $params);
        }
        
        if ($anchor) {
           $params['#'] = $anchor;
        }
        
        if ($haveLastPart || $anchor) {
            unset($restArr[count($restArr)-1]);
        }
        
        setIfNot($params['Ctr'], $restArr[0]);
        
        // Ако екшъна е SBF
        if (strtolower($params['Ctr']) == 'sbf') return FALSE;
        
        setIfNot($params['Act'], $restArr[1], 'default');

        if(count($restArr) % 2) {
            setIfNot($params['id'], $restArr[2]);
            $pId = 3;
        } else {
            $pId = 2;
        }
        
        // Добавяме останалите параметри, които са в часта "път"
        while(isset($restArr[$pId]) && isset($restArr[$pId+1])) {
            $params[$restArr[$pId]] = $restArr[$pId+1];
            $pId++;
        }
        
        // Декодира защитеното id
        if(($id = $params['id']) && ($ctr = $params['Ctr'])) {
            $id = core_Request::unprotectId($id, $ctr);
            $params['id'] = $id;
        }
        
        return $params;
    }


    /**
     * Премахва празните линии, които се срещат последователно и са повече от посочения параметър
     *
     * @param string $richText
     * @param int    $maxLines
     *
     * @return string 
     */
    static function removeEmptyLines($richText, $maxEmptyLines = 2)
    {
        $lines = explode("\n", $richText);
        $empty = 0;
        $newRichText = '';
        foreach($lines as $l) {
            if(trim(str_replace(array('[b]', '[/b]', '[i]', '[/i]', '[u]', '[/u]', chr(0xC2) . chr(0xA0)), array(), $l))) {
                $empty = 0;
            } else {
                $empty++;
            }
                        
            if($empty <2) {
                $newRichText .= $l . "\n";
            }
        }

        return $newRichText;
    }
    
    
    /**
     * Съобщението, което ще се показва ако нямаме достъп до обекта
     */
    static function getNotAccessMsg()
    {
        $text = tr('Липсващ обект');
        if (Mode::is('text', 'plain')) {
            
            // 
            $str = $text;
            
        } else {
            // Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
            $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
            
            // Иконата за линка
            $sbfIcon = sbf('img/16/link_break.png','"', $isAbsolute);
            
            // Съобщението
            $str = "<span class='linkWithIcon' style='background-image:url({$sbfIcon});'> {$text} </span>"; 
                
        }
        
        return $str;
    }
    
    
    /**
     * Връща масив със всички предложения за този списък
     */
    public function getSuggestions()
    {
        if(!$this->suggestions) {
            $this->prepareSuggestions();
        }

        return $this->suggestions;
    }
    
    
    /**
     * Подготвя предложенията за списъка
     */
    private function prepareSuggestions()
    {
        $this->suggestions = arr::make($this->suggestions);
        
        if ($this->invoke('BeforePrepareSuggestions', array(&$this->suggestions, $this)) === FALSE) return ;
        
        // Добавяме 
        $suggestionsStr = str_replace('|', ',', $this->params['suggestions']);
        $this->suggestions = arr::make($suggestionsStr, TRUE);
        
        $this->invoke('AfterPrepareSuggestions', array(&$this->suggestions, $this));
    }
}
