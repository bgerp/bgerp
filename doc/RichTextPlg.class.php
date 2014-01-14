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

        if (!$doc = doc_Containers::getDocumentByHandle($match)) {
            return $match[0];
        }
        
        $mvc    = $doc->instance;
        $docRec = $doc->rec();
        
        //Създаваме линк към документа
        $link = bgerp_L::getDocLink($docRec->containerId, doc_DocumentPlg::getMidPlace());
        
        //Уникален стринг
        $place = $this->mvc->getPlace();
        
        //Ако сме в текстов режим
        if(Mode::is('text', 'plain')) {
            //Добавяме линк към системата
            $this->mvc->_htmlBoard[$place] = "{$docName} ( $link )";
        } else {
            
            //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
            $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
            
            $sbfIcon = sbf($doc->getIcon(), '"', $isAbsolute);
            
            $title = substr($docName, 1);
            
            if(Mode::is('text', 'xhtml') && !Mode::is('pdf')) {
                
                // Създаваме линк
                $href = ht::createLink($title, $link);
                
                // Добавяме линк и иконата
                $icon = "<img src={$sbfIcon} width='16' height='16' style='float:left;margin:3px 2px 4px 0px;' alt=''>";
                $this->mvc->_htmlBoard[$place] = "<div style='display:inline-block;'>{$icon}{$href}</div>";  
            } else {
                
                //Създаваме линк в html формат
                $style = "background-image:url({$sbfIcon});";
                
                // Атрибути на линка
                $attr['class'] = 'linkWithIcon';
                $attr['style'] = $style;
                
                // Ако изпращаме или принтираме документа
                if ($isAbsolute) {
                    
                    // Линка да се отваря на нова страница
                    $attr['target'] = '_blank';    
                }
                
                $href = ht::createLink($title, $link, NULL, $attr);
                
                //Добавяме href атрибута в уникалния стинг, който ще се замести по - късно
                $this->mvc->_htmlBoard[$place] = $href->getContent();
            }
        }

        //Стойността, която ще заместим в регулярния израз
        //Добавяме символите отркити от регулярниярния израз, за да не се развали текста
        $res = $match['begin'] . "[#{$place}#]" . $match['end'];

        return  $res;
    }


    /**
     * Намира всички цитирания на хендъли на документи в текст
     *
     * @param string $rt - Стринг, в който ще търсим.
     * @return array $docs - Масив с ключове - разпознатите хендъли и стойности - масиви от вида
     *                         array(
     *                             'name' => хендъл, също като ключа
     *                             'mvc'  => мениджър на документа с този хендъл
     *                             'rec'  => запис за документа с този хендъл
     *                         ) 
     */
    static function getAttachedDocs($rt)
    {
        $docs = array();
        
        //Ако сме открили нещо
        if (preg_match_all(self::$pattern, $rt, $matches, PREG_SET_ORDER)) {
            
            //Обхождаме всички намерени думи
            foreach ($matches as $match) {
                if (!$doc = doc_Containers::getDocumentByHandle($match)) {
                    continue;
                }
                
                //Името на документа
                $name = $doc->getHandle();
                $mvc  = $doc->getInstance();
                $rec  = $doc->rec();
                
                $docs[$name] = compact('name', 'mvc', 'rec');
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
        // Ако не е подадено нищо
        if (!trim($fileName)) return ;
        
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
     * Прихваща извикването на getInfoFromDocHandle
     * Връща информация за документа, от манипулатора му
     */
    function on_GetInfoFromDocHandle($mvc, &$res, $fileHnd)
    {
        // Вземаме информация за файла
        $fileInfo = static::getFileInfo($fileHnd);
        
        // Ако няма, връщаме
        if (!$fileInfo) return ;
        
        // Вземаме инстанция на класа
        $class = cls::get($fileInfo['className']);
        
        // Вземаме записа от контейнера на съответния документ
        $cRec = $class->getContainer($fileInfo['id']);
        
        // Добавяме датата
        $res['date'] = dt::mysql2verbal($cRec->createdOn);
        
        // Ако има създател
        if ($cRec->createdBy > 0) {
            
            // Добавяме имената на автора
            $res['author'] = core_Users::getVerbal($cRec->createdBy, 'names');
        } else {
            
            // Ако няма създател или е системата
            
            // Ако има клас и id на документ
            if ($class && $fileInfo['id']) {
                
                // Вземаме данните за документа
                $dRow = $class->getDocumentRow($fileInfo['id']);
                
                // Добавяме автора
                $res['author'] = $dRow->author;
                
                // Добавяме имейла, ако има такъв
                $res['authorEmail'] = $dRow->authorEmail;
            }
        }
    }
    
    
    /**
     * Връща всички документи които са цитирани във всички richtext полета
     * на даден мениджър
     * @param core_Mvc $mvc - мениджър
     * @param stdClass $rec - запис, за който проверяваме
     * @return array - Масив с ключове - разпознатите хендъли и стойности - масиви от вида
     *                       	array(
     *                             'name' => хендъл, също като ключа
     *                             'mvc'  => мениджър на документа с този хендъл
     *                             'rec'  => запис за документа с този хендъл
     *                          ) 
     */
    public static function getDocsInRichtextFields(core_Mvc $mvc, $rec)
    {
    	$all = '';
    	$rec = $mvc->fetch($rec->id);
    	$fields = $mvc->selectFields();
    	foreach ($fields as $name => $fld){
    		if($fld->type instanceof type_Richtext){
    			$all .= $rec->{$name};
    		}
    	}
    	
    	// Намират се всички цитирания на документи в поле richtext
    	return static::getAttachedDocs($all);
    }
    
    
    /**
     * Добавя бутон за качване на документ
     * 
     * @param core_Mvc $mvc
     * @param core_Toolbar $toolbarArr
     * @param array $attr
     */
    function on_AfterGetToolbar($mvc, &$toolbarArr, &$attr)
    {
        // id
        $id = $attr['id'];
        
        // Име на функцията и на прозореца
        $windowName = $callbackName = 'placeDoc_' . $id;
        
        // Ако е мобилен/тесем режим
        if(Mode::is('screenMode', 'narrow')) {
            
            // Парамтери към отварянето на прозореца
            $args = 'resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
        } else {
            $args = 'width=600,height=600,resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
        }
        
        // URL за добавяне на документи
        $url = $mvc->getUrLForAddDoc($callbackName);
        
        // JS фунцкията, която отваря прозореца
        $js = "openWindow('{$url}', '{$windowName}', '{$args}'); return false;";
        
        // Бутон за отвяряне на прозореца
        $documentUpload = new ET("<a class=rtbutton title='" . tr("Добавяне на документ") . "' onclick=\"{$js}\">" . tr("Документ") . "</a>");
        
        
        // JS функцията
        $callback = "function {$callbackName}(docHnd) {
            var ta = get$('{$id}');
            rp(\"\\n\" + docHnd, ta);
            return true;
        }";
        
        // Добавяме скрипта
        $documentUpload->appendOnce($callback, 'SCRIPTS');
        
        // Добавяне в групата за добавяне на документ
        $toolbarArr->add($documentUpload, 'filesAndDoc', 1000.055);
    }
    
    
	/**
     * Връща URL за добавяне на документи
     * 
     * @param core_Mvc $mvc
     * @param core_Et $res
     * @param string $callback
     */
    function on_AfterGetUrLForAddDoc($mvc, &$res, $callback)
    {
        // Защитаваме променливите
        Request::setProtected('callback');
        
        // Създаваме URL' то
        $res = toUrl(array($mvc, 'addDocDialog', 'callback' => $callback));
    }
	
	
	/**
     * Извиква се преди изпълняването на екшън
     * 
     * @param core_Mvc $mvc
     * @param core_Et $tpl
     * @param string $action
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
        // Ако екшъна не е дилогов прозорец за добавяне на документи, да не се изпълнява
        if (strtolower($action) != 'adddocdialog') return ;
        
        // Очакваме да е логнат потребител
        requireRole('user');
        
        // Задаваме врапера
        Mode::set('wrapper', 'page_Dialog');
        
        // Обект с данните
        $data = new stdClass();
        
        // Вземаме променливите
        $data->callback = Request::get('callback', 'identifier');
        $data->PerPage = Request::get('PerPage', 'int');
        
        // Подготваме страницирането
        $mvc->prepareAddDocDialogPager($data);
        
        // Подготвяме данните
        $mvc->prepareAddDocDialog($data);
        
        // Рендираме диалоговия прозорец
        $tpl = $mvc->renderAddDocDialog($data);
        
        return FALSE;
    }
    
    
	/**
	 * Подготвя навигацията по страници
	 * 
	 * @param core_Mvc $mvc
	 * @param mixed $res
	 * @param object $data
	 */
    function on_AfterPrepareAddDocDialogPager($mvc, &$res, &$data)
    {
        // Ако е сетнат
        if ($perPage = $data->PerPage) {
            
            // Трябва да е между 0-100
            if ($perPage > 100) {
                $perPage = 100;
            } elseif ($perPage < 0) {
                $perPage = 0;
            }
        }
        
        // Ако няма
        if (!$perPage) {
            
            // Задаваме стойността
            $perPage = ($mvc->dialogItemsPerPage) ? $mvc->dialogItemsPerPage : 8;
        }
        
        // Ако има зададен брой
        if($perPage) {
            
            // Ако все още не е сетнат
            if (!$data->dialogPager) {
                
                // Сетваме пейджъра
                $data->dialogPager = & cls::get('core_Pager', array('pageVar' => 'P_' . get_called_class()));
            }
            // Добавяме страниците към пейджъра
            $data->dialogPager->itemsPerPage = $perPage;
        }
    }
    
    
    /**
     * Подготвя необходимите данни
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param object $data
     */
	function on_AfterPrepareAddDocDialog($mvc, $res, &$data)
	{
	    // Вземаме документите от последните 3 нишки за съответния потребител
	    $threadsArr = bgerp_Recently::getLastThreadsId(3);
	    
	    // Вземаме всички документи от нишката, до които потебителя има достъп
        $docIdsArr = doc_Containers::getAllDocIdFromThread($threadsArr, NULL, 'DESC');
        
        // Задаваме броя на документите
        $data->itemsCnt = count((array)$docIdsArr);
        
        // Зададаваме лимита за странициране
        $mvc->setLimitAddDocDialogPager($data);
        
        // Брояча
        $c = 0;
        
        // Масив с всички документи
        $resArr = array();
        
        // Обхождаме документите
        foreach ((array)$docIdsArr as $docId => $docRec) {
            
            // Ако сме в границита на брояча
            if (($c >= $data->dialogPager->rangeStart) && ($c < $data->dialogPager->rangeEnd)) {
                
                // Увеличаваме брояча
                $c++;
            } else {
                
                // Увеличаваме брояча
                $c++;
                
                // Ако сме достигнали горната граница, да се прекъсне
                if ($data->dialogPager->rangeEnd < $c) break;
                
                // Прескачаме
                continue;
            }
            
            
            try {
                
                // Вземаме документа
                $document = doc_Containers::getDocument($docId, 'doc_DocumentIntf');
                
                // Вземаме полетата
                $docRow = $document->getDocumentRow();
                
                // Масив за вземане на уникалното id
                $attrId = array();
                
                // Вземаме уникалното id
                ht::setUniqId($attrId);
                
                // id на реда
                $resArr[$docId]['ROW_ATTR']['id'] = $attrId['id']; 
                
                // Заглавие на документа
                $resArr[$docId]['title'] = str::limitLen($docRow->title, 35);
                
                // Манипулатор на докуемнта
                $resArr[$docId]['handle'] = $document->getHandle();
                
                // Данни за създаването на документа
                $resArr[$docId]['createdOn'] = doc_Containers::getVerbal($docRec, 'createdOn');
                $resArr[$docId]['createdBy'] = doc_Containers::getVerbal($docRec, 'createdBy');
                $resArr[$docId]['created'] = $resArr[$docId]['createdOn'] . ' ' . tr('от') . ' ' . $resArr[$docId]['createdBy'];
                $resArr[$docId]['created'] = "<div class='upload-doc-created'>" . $resArr[$docId]['created'] . "</div>";
                
                // Манипулатора, който ще се добавя
                $handle = '#' . $resArr[$docId]['handle'];
                
                // Атрибутите на линковете
                $attr = array('onclick' => "flashDocInterpolation('{$attrId['id']}'); if(window.opener.{$data->callback}('{$handle}') != true) self.close(); else self.focus();", "class" => "file-log-link");
                
                // Името на документа да се добави към текста, при натискане на линка
                $resArr[$docId]['title'] = ht::createLink($resArr[$docId]['title'], '#', NULL, $attr); 
            } catch (Exception $e) {
                continue;
            }
        }
        
        // Добавяме резултатите
        $data->docIdsArr = $resArr;
	}
    
    
	/**
	 * Задаваме броя на всички елементи
     * 
	 * @param core_Mvc $mvc
	 * @param mixed $res
	 * @param oject $data
	 */
    function on_AfterSetLimitAddDocDialogPager($mvc, &$res, &$data)
    {
        // Задаваме броя на страниците
        $data->dialogPager->itemsCount = $data->itemsCnt;
        
        // Изчисляваме
        $data->dialogPager->calc();
    }
	
	
    /**
     * Извиква се след рендиране на диалоговия прозорец
     * 
     * @param core_Mvc $mvc
     * @param core_ET $tpl
     * @param object $data
     */
	function on_AfterRenderAddDocDialog($mvc, &$tpl, $data)
	{
	    // Ако няма шаблон
	    if (!$tpl) {
	        
	        // Вземаме шаблона
            $tpl = getTplFromFile('doc/tpl/DialogAddDoc.shtml');
	    }
        
        // Инстанция на класа за създаване на таблици
        $inst = cls::get('core_TableView');
        
        // Полета в таблицата
        $tableCaptionArr = array('handle' => 'Хендлър', 'title' => 'Заглавие', 'created' => 'Създадено');
        
        // Вземаме таблицата с попълнени данни
        $tableTpl = $inst->get($data->docIdsArr, $tableCaptionArr);
        
        // Заместваме в главния шаблон за детайлите
        $tpl->append($tableTpl, 'tableContent');
        
        // Заместваме страницирането
        $tpl->append($mvc->RenderDialogAddDocPager($data), 'pager');
        
        // Конфигурация на ядрото
        $conf = core_Packs::getConfig('core');
        
        // Добавяме титлата
        $tpl->prepend(tr("Документи") . " « " . $conf->EF_APP_TITLE, 'PAGE_TITLE');
        
        // Добавяме css-файла
       	$tpl->push('doc/css/dialogDoc.css','CSS');
	}
    
    
	/**
	 * Рендира  навигация по страници
	 * 
	 * @param core_Mvc $mvc
	 * @param core_ET $res
	 * @param object $data
	 */
    function on_AfterRenderDialogAddDocPager($mvc, &$res, $data)
    {
        // Ако има странициране
        if ($data->dialogPager) {
            
            // Рендираме
            $res = $data->dialogPager->getHtml();
        }
    }
}
