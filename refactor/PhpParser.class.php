<?php


/**
 * Парсира PHP файл във масив от тоукъни
 *
 *
 * @category  bgerp
 * @package   refactor
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class refactor_PhpParser
{
    /**
     * @param string Символи за отместване между вложените блокове
     */
    public $ident = '    ';
    
    
    /**
     *  @todo Чака за документация...
     */
    public static $r;
    
    
    /**
     * @var array Масив с тоукън - константи
     */
    public $tConstArr = array();
    
 
    
    /**
     * Създава вътрешен масив от стринг
     */
    public function loadText($str)
    {
        // Подготвя масива с тоукън константите
        $tConstTxt = getFileContent('refactor/data/php_tokens.txt');
        $tConstLines = explode("\n", $tConstTxt);
        $tConstArr = array();
        foreach ($tConstLines as $tConst) {
            $tConst = trim($tConst);
            if (defined($tConst)) {
                $this->tConstArr[constant($tConst)] = $tConst;
            }
        }
        
        // Зареждаме кода
        $this->code = $str;
        $this->lines = explode("\n", $str);
        
        // Парсираме кода
        $tokens = token_get_all($str);
        expect(is_array($tokens));
        
        // Създаваме масива с тоукъни
        foreach ($tokens as $token) {
            if (is_array($token)) {
                $this->tokenArr[] = new refactor_Token($token[0], $this->tConstArr[$token[0]], $token[1]);
            } else {
                $this->tokenArr[] = new refactor_Token($token, $token, $token);
            }
        }
    }
    
    
    /**
     * Зарежда и парсира посочения файл
     */
    public function loadFile($filePath)
    {
        // Вземаме съдържанието на файла
        $fileStr = file_get_contents($filePath);
        
        $this->loadText($fileStr);
    }
    

    /**
     * Записва в аргумента си текстовете, които трябва да се превеждат
     */
    public function getTrTexts(&$res)
    {
        foreach ($this->tokenArr as $i => $t) {
            if ($t->type == T_CONSTANT_ENCAPSED_STRING) {
                $str = trim($t->str, $t->str{0});
                if (preg_match('/[А-Яа-я][А-Яа-я0-9A-Z\\-_ \\,\\.\\!\\?]+/u', $str, $matches)) {
                    if (count($matches)) {
                        foreach ($matches as $s) {
                            $s = trim($s, ' ,_-');
                            $s = mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
                            $res[$s]++;
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Проверява дали в оригиналния текст има стоящи една до друга
     * букви на латиница и кирилица
     */
    public function checkCyrLat()
    {
        foreach ($this->lines as $l) {
            if (preg_match('/([a-z][а-я]|[а-я][a-z])/iu', $l, $matches) && !preg_match("/(preg_|pattern|CyrLat|\-zа\-)/iu", $l)) {
                if ($matches[1]{0} == 'n' && strpos($l, '\\' . $matches[1]) !== false) {
                    continue;
                }
                
                bp($l, $matches);
            }
        }
    }

    
    /**
     * Подравняваме отляво с интервали всички локове от празни линии
     * с броя интервали на последната линия
     */
    public function padEmptyLines()
    {
        // Обикаляме по всички празни пространства
        foreach ($this->tokenArr as $i => $t) {
            if ($t->type == T_WHITESPACE) {
                // $t->str= str_replace(array("\n", ' '), array('n', '_'), $t->str);
                
                $prev = $this->tokenArr[$i - 1];
                $prev2 = $this->tokenArr[$i - 2];
                $next = $this->tokenArr[$i + 1];
                
                $lines = explode("\n", $t->str);
                $lastId = count($lines) - 1;
                $lastStr = $lines[$lastId] . '';
                
                $newCnt = min(3, $lastId);
                
                if ($prev && $prev->type == T_COMMENT || $prev->type == T_DOC_COMMENT) {
                    // Ако предходения елемент е коментар - не правим нищо
                } elseif ($prev && $prev->str == '{' && $prev2 && strpos($prev2->str, "\n") !== false) {
                    // Ако предходния таг е отваряща скоба - една празна линия
                    $newCnt = 1;
                } elseif ($prev && $prev->type == T_OPEN_TAG) {
                    // Ако предходния таг е начало на PHP - две празни линии
                    $newCnt = 2;
                } elseif ($next && $next->type == T_RETURN) {
                    // Ако следва return оставяме 2 празна линия преди него
                    $newCnt = 2;
                } elseif ($next && $next->type == T_COMMENT && $prev && $prev->str != '{') {
                    // Ако следва T_COMMENT оставяме 1 празна линия преди него
                    if ($newCnt == 1) {
                        $newCnt = 2;
                    }
                } elseif ($next && $next->type == T_DOC_COMMENT && $prev && $prev->str != '{') {
                    // Ако следва T_DOC_COMMENT - оставяме 2 празни линии преди него
                    $newCnt = 3;
                }
                
                $t->str = str_repeat("\n" . $lastStr, $newCnt);
                if ($newCnt == 0 || ($this->tokenArr[$i - 1] && substr($this->tokenArr[$i - 1]->str, -1) == "\n")) {
                    $t->str = $lastStr . $t->str;
                }
            }
        }
    }
    
    
    /**
     * Извършва нормализации
     */
    public function normalizeWhiteSpace()
    {
        $tokenArr = &$this->tokenArr;
        
        $operators = array('+', '.', '=', '/', '^', '*', '%', '?', ':', T_SL, T_SL_EQUAL, T_SR, T_SR_EQUAL, T_START_HEREDOC,
            T_XOR_EQUAL, T_LOGICAL_AND, T_LOGICAL_OR, T_LOGICAL_XOR, T_MINUS_EQUAL, T_ML_COMMENT, T_MOD_EQUAL,
            T_MUL_EQUAL, T_IS_EQUAL, T_IS_GREATER_OR_EQUAL, T_IS_IDENTICAL, T_IS_NOT_EQUAL, T_IS_NOT_IDENTICAL,
            T_IS_SMALLER_OR_EQUAL, T_BOOLEAN_AND, T_BOOLEAN_OR, T_AND_EQUAL);
        
        if (is_array($tokenArr)) {
            foreach ($tokenArr as $i => $c) { //?
                
                $next = $tokenArr[$i + 1];
                $pos = $i + 1;
                
                do {
                    $nextE = $tokenArr[$pos];
                    $pos++;
                } while ($nextE->type == T_WHITESPACE);
                
                $prev = $tokenArr[$i - 1];
                $pos = $i - 1;
                
                do {
                    $prevE = $tokenArr[$pos];
                    $pos--;
                } while ($prevE->type == T_WHITESPACE);
                
                if ($c->type == ',') {
                    if (in_array(strtoupper($c->str), array('TRUE', 'FALSE', 'NULL')) && $prevE->type == '=') {
                        $c->str = strtoupper($c->str);
                    }
                }
                
                // Подсигуряваме интервал след запетаята
                if (($c->type == ',') && ($c->str == ',') && ($next->type != T_WHITESPACE)) {
                    $c->insertAfter(T_WHITESPACE, ' ');
                }
                
                // Подсигуряваме интервал преди и след операндите
                if (in_array($c->type, $operators)) {
                    if ($next->type != T_WHITESPACE) {
                        $c->insertAfter(T_WHITESPACE, ' ');
                    }
                    
                    if ($prev->type != T_WHITESPACE) {
                        $prev->insertAfter(T_WHITESPACE, ' ');
                    }
                }
                
                // След отваряща скоба, махаме интервалите
                if (($c->type == '(') && ($next->type == T_WHITESPACE)) {
                    $next->str = ltrim($next->str, ' ');
                }
                
                // Преди затваряща скоба, махаме интервалите
                if (($c->type == ')') && ($prev->type == T_WHITESPACE)) {
                    $prev->str = rtrim($prev->str, ' ');
                }
                
                if ($c->type == T_WHITESPACE) {
                    if ($nl = (count(explode("\n", $c->str)) - 1)) {
                        $c->str = str_repeat("\n", min($nl, 2));
                        expect(strlen($c->str));
                    }
                    
                    // $c->str = ' ';
                }
                
                $prev_ = $tokenArr[$i - 2]->type;
                
                if ($c->type == T_COMMENT) {
                    if ($prev->type == ';' ||
                        $prev->type == '}' ||
                        $prev->type == T_WHITESPACE && (strpos($prev->str, "\n") === false) && ($prev_ == ';' || $prev_ == '}' || $prev_ == '(')
                    ) {
                        $c->type = '';
                        $c->insertAfter(T_WHITESPACE, "\n");
                        $c->insertBefore(T_WHITESPACE, ' ');
                        
                        $c->str = trim($c->str, "\n ");
                        
                        if ($next->type == T_WHITESPACE) {
                            $next->delete();
                        }
                    }
                }
            }
        }

        $this->flat();
        
        if (is_array($tokenArr)) {
            foreach ($tokenArr as $i => $c) {
                $next = $tokenArr[$i + 1];
                $pos = $i + 1;
                
                do {
                    $nextE = $tokenArr[$pos];
                    $pos++;
                } while ($nextE->type == T_WHITESPACE);
                
                $prev = $tokenArr[$i - 1];
                $pos = $i - 1;
                
                do {
                    $prevE = $tokenArr[$pos];
                    $pos--;
                } while ($prevE->type == T_WHITESPACE);
                
                if ($c->type == T_COMMENT && $next->type == T_WHITESPACE) {
                    if ($next->str == ' ') {
                        $next->str = '';
                    }
                }
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function normalizeDocComments()
    {
        $ta = &$this->tokenArr;
        
        // Правим масив с индексите само само на елементите, без whitespace
        if (is_array($ta)) {
            foreach ($ta as $i => $c) {
                if ($c->type != T_WHITESPACE) {
                    $e[] = $i;
                }
            }
        }
        
        if (is_array($e)) {
            foreach ($e as $id => $i) {
                unset($commentId, $type, $name, $comment);
                
                // Разпознаваме специалните елементи на скрипта
                if ($ta[$e[$id]]->type == T_FUNCTION &&
                    in_array(
                        $ta[$e[$id - 1]]->type,
                        array(';', '}', '{', T_COMMENT, T_DOC_COMMENT)
                    )) {
                    $commentId = $id - 1;
                    $type = 'function';
                    $name = $ta[$e[$id + 1]]->str;
                    
                    if ($name == '&') {
                        $name = $ta[$e[$id + 2]]->str . $ta[$e[$id + 3]]->str;
                    }
                    $this->arr[$name]++;
                } elseif (($ta[$e[$id]]->type == T_STATIC) &&
                    ($ta[$e[$id + 1]]->type == T_FUNCTION) &&
                    (in_array(
                        $ta[$e[$id - 1]]->type,
                            array(';', '}', '{', T_COMMENT, T_DOC_COMMENT)
                    ))) {
                    $commentId = $id - 1;
                    $type = 'static_function';
                    $name = $ta[$e[$id + 2]]->str;
                    
                    if ($name == '&') {
                        $name = $ta[$e[$id + 2]]->str . $ta[$e[$id + 3]]->str;
                    }
                    $this->arr[$name]++;
                } elseif (($ta[$e[$id]]->type == T_PUBLIC) &&
                    ($ta[$e[$id + 1]]->type == T_FUNCTION) &&
                    (in_array(
                        $ta[$e[$id - 1]]->type,
                            array(';', '}', '{', T_COMMENT, T_DOC_COMMENT)
                    ))) {
                    $commentId = $id - 1;
                    $type = 'public_function';
                    $name = $ta[$e[$id + 2]]->str;
                    
                    if ($name == '&') {
                        $name = $ta[$e[$id + 2]]->str . $ta[$e[$id + 3]]->str;
                    }
                    $this->arr[$name]++;
                } elseif (($ta[$e[$id]]->type == T_PRIVATE) &&
                    ($ta[$e[$id + 1]]->type == T_FUNCTION) &&
                    (in_array(
                        $ta[$e[$id - 1]]->type,
                            array(';', '}', '{', T_COMMENT, T_DOC_COMMENT)
                    ))) {
                    $commentId = $id - 1;
                    $type = 'private_function';
                    $name = $ta[$e[$id + 2]]->str;
                    
                    if ($name == '&') {
                        $name = $ta[$e[$id + 2]]->str . $ta[$e[$id + 3]]->str;
                    }
                    $this->arr[$name]++;
                } elseif (($ta[$e[$id]]->type == T_PROTECTED) &&
                    ($ta[$e[$id + 1]]->type == T_FUNCTION) &&
                    (in_array(
                        $ta[$e[$id - 1]]->type,
                            array(';', '}', '{', T_COMMENT, T_DOC_COMMENT)
                    ))) {
                    $commentId = $id - 1;
                    $type = 'protected_function';
                    $name = $ta[$e[$id + 2]]->str;
                    
                    if ($name == '&') {
                        $name = $ta[$e[$id + 2]]->str . $ta[$e[$id + 3]]->str;
                    }
                    $this->arr[$name]++;
                } elseif (($ta[$e[$id]]->type == T_PUBLIC) &&
                    ($ta[$e[$id + 1]]->type == T_STATIC) &&
                    ($ta[$e[$id + 2]]->type == T_FUNCTION) &&
                    (in_array(
                        $ta[$e[$id - 1]]->type,
                            array(';', '}', '{', T_COMMENT, T_DOC_COMMENT)
                    ))) {
                    $commentId = $id - 1;
                    $type = 'public_static_function';
                    $name = $ta[$e[$id + 3]]->str;
                    
                    if ($name == '&') {
                        $name = $ta[$e[$id + 2]]->str . $ta[$e[$id + 3]]->str;
                    }
                    $this->arr[$name]++;
                } elseif (($ta[$e[$id]]->type == T_STATIC) &&
                    ($ta[$e[$id + 1]]->type == T_PUBLIC) &&
                    ($ta[$e[$id + 2]]->type == T_FUNCTION) &&
                    (in_array(
                        $ta[$e[$id - 1]]->type,
                            array(';', '}', '{', T_COMMENT, T_DOC_COMMENT)
                    ))) {
                    $commentId = $id - 1;
                    $type = 'static_public_function';
                    $name = $ta[$e[$id + 3]]->str;
                    
                    if ($name == '&') {
                        $name = $ta[$e[$id + 2]]->str . $ta[$e[$id + 3]]->str;
                    }
                    $this->arr[$name]++;
                } elseif (($ta[$e[$id]]->type == T_PRIVATE) &&
                    ($ta[$e[$id + 1]]->type == T_STATIC) &&
                    ($ta[$e[$id + 2]]->type == T_FUNCTION) &&
                    (in_array(
                        $ta[$e[$id - 1]]->type,
                            array(';', '}', '{', T_COMMENT, T_DOC_COMMENT)
                    ))) {
                    $commentId = $id - 1;
                    $type = 'private_static_function';
                    $name = $ta[$e[$id + 3]]->str;
                    
                    if ($name == '&') {
                        $name = $ta[$e[$id + 2]]->str . $ta[$e[$id + 3]]->str;
                    }
                    $this->arr[$name]++;
                } elseif (($ta[$e[$id]]->type == T_STATIC) &&
                    ($ta[$e[$id + 1]]->type == T_PRIVATE) &&
                    ($ta[$e[$id + 2]]->type == T_FUNCTION) &&
                    (in_array(
                        $ta[$e[$id - 1]]->type,
                            array(';', '}', '{', T_COMMENT, T_DOC_COMMENT)
                    ))) {
                    $commentId = $id - 1;
                    $type = 'static_private_function';
                    $name = $ta[$e[$id + 3]]->str;
                    
                    if ($name == '&') {
                        $name = $ta[$e[$id + 2]]->str . $ta[$e[$id + 3]]->str;
                    }
                    $this->arr[$name]++;
                } elseif (($ta[$e[$id]]->type == T_STRING) &&
                    ($ta[$e[$id]]->str == 'defIfNot') &&
                    (in_array(
                        $ta[$e[$id - 1]]->type,
                            array(';', T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG)
                    ))) {
                    $commentId = $id - 1;
                    $type = 'defIfNot';
                    $name = $ta[$e[$id + 2]]->str;
                    $value = $ta[$e[$id + 4]]->str;
                    $i = 5;
                    
                    while ($ta[$e[$id + $i]]->str != ')') {
                        $value .= $ta[$e[$id + $i]]->str;
                        $i++;
                    }
                    
                    if ($ta[$e[$id + $i + 1]]->str == ')') {
                        $value = $value . ')';
                    }
                    
                    if (trim($value) == ';' || trim($ta[$e[$id + 4]]->str) == ';') {
                        unset($value);
                    }
                } elseif (($ta[$e[$id]]->type == T_STRING) &&
                    (($ta[$e[$id]]->str == 'define') ||
                        ($ta[$e[$id]]->str == 'DEFINE')) &&
                    (in_array(
                        $ta[$e[$id - 1]]->type,
                            array(';', T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG)
                    ))) {
                    $commentId = $id - 1;
                    $type = 'define';
                    $name = $ta[$e[$id + 2]]->str;
                } elseif ($ta[$e[$id]]->type == T_CLASS) {
                    $commentId = $id - 1;
                    $type = 'class';
                    $name = $ta[$e[$id + 1]]->str;
                } elseif ($ta[$e[$id]]->type == T_VAR) {
                    $commentId = $id - 1;
                    $type = 'var';
                    $name = $ta[$e[$id + 1]]->str;
                } elseif ($ta[$e[$id]]->type == T_CONST) {
                    $commentId = $id - 1;
                    $type = 'const';
                    $name = $ta[$e[$id + 1]]->str;
                } elseif (($ta[$e[$id]]->type == T_STRING) &&
                    ($ta[$e[$id - 1]]->type != T_FUNCTION)) {
                    $this->arrF[$ta[$e[$id]]->str]++;
                }
                
                // Опитваме се да извлечем коментарите
                if ($commentId) {
                    $last = $ta[$e[$commentId]];
                    
                    if ($last->type == T_DOC_COMMENT) {
                        $lines = explode("\n", $last->str);
                        
                        $singleComm = '';
                        
                        foreach ($lines as $l) {
                            $l = trim($l);
                            
                            //  if($l == '*  @todo Чака за документация...') continue;
                            
                            if (($l != '/**') && ($l != '*/')) {
                                $singleComm .= ltrim($l, ' *') . "\n";
                            }
                        }
                        
                        $comment = $singleComm . $comment;
                        
                        $last->delete();
                    } else {
                        while ($last->type == T_COMMENT) {
                            $lines = explode("\n", $last->str);
                            
                            $singleComm = '';
                            
                            foreach ($lines as $l) {
                                $l = trim($l);
                                $singleComm .= trim($l, '/* ') . "\n";
                            }
                            
                            $comment = $singleComm . $comment;
                            
                            $last->delete();
                            
                            $commentId--;
                            
                            $last = $ta[$e[$commentId]];
                        }
                    }
                    
                    $newComment = $this->fetchComment($type, $name, $comment, $value);
                    
                    // Ако сме получили някакъв коментар, опитваме се да го сложим
                    if (trim($newComment)) {
                        // Да направим docComment от $newComent
                        $docComment = "/**\n";
                        
                        $lines = explode("\n", trim($newComment));
                        
                        foreach ($lines as $l) {
                            $docComment .= ' * ' . $l . "\n";
                        }
                        
                        $docComment .= " */\n";
                        
                        $ta[$e[$id]]->insertBefore(T_DOC_COMMENT, $docComment);
                    } else {
                        $docComment = "/**\n";
                        $docComment .= "* @todo Чака за документация...\n";
                        $docComment .= " */\n";
                        $ta[$e[$id]]->insertBefore(T_DOC_COMMENT, $docComment);
                    }
                } else {
                    if (($ta[$e[$id]]->str == 'defIfNot') ||
                        ($ta[$e[$id]]->str == 'define') ||
                        ($ta[$e[$id]]->str == 'DEFINE') && (in_array(
                            
                            $ta[$e[$id - 1]]->type,
                            array(';', '}', '{', T_COMMENT, T_DOC_COMMENT)
                        
                        ))) {
                        $docComment = "\n/**\n";
                        $docComment .= "* @todo Чака за документация...\n";
                        $docComment .= " */\n";
                        if ((in_array(
                            $ta[$e[$id - 1]]->type,
                            array(';', '}', '{', T_COMMENT, T_DOC_COMMENT)
                        ))) {
                            $ta[$e[$id]]->insertBefore(T_DOC_COMMENT, $docComment);
                        }
                        
                        $comment = '@todo Чака за документация...';
                        $value = $ta[$e[$id + 4]]->str;
                        $i = 5;
                        
                        while ($ta[$e[$id + $i]]->str != ')') {
                            $value .= $ta[$e[$id + $i]]->str;
                            $i++;
                        }
                        
                        if ($ta[$e[$id + $i + 1]]->str == ')') {
                            $value = $value . ')';
                        }
                        
                        if (trim($value) == ';' || trim($ta[$e[$id + 4]]->str) == ';') {
                            unset($value);
                        }
                        
                        if (!$type) {
                            $type = '';
                        }
                        
                        if (!$name) {
                            $name = '';
                        }
                        
                        if (!$comment) {
                            $comment = '';
                        }
                        
                        if (!$value) {
                            $value = '';
                        }
                        $newComment = $this->fetchComment($type, $name, $comment, $value);
                    }
                }
            }
        }
        $this->flat();
    }
    
    
    /**
     * Връща новия коментар, който отговаря на езиковия ресурс
     */
    public function fetchComment($type, $name, $oldComment, $value)
    {
        if (!trim($oldComment)) {
            $oldComment = null;
        }
        
        $rec = refactor_Formater::fetch(array("#fileName = '[#1#]'  AND #type = '[#2#]' AND #name = '[#3#]'", $this->sourceFile, $type, $name));
        
        $rec->oldComment = $oldComment;
        
        if (!$rec->newComment) {
            $rec->newComment = $rec->oldComment;
        }
        
        if (!$rec->newComment) {
            $recCommon = refactor_Formater::fetch(array("#fileName = '*'  AND #type = '[#1#]' AND #name = '[#2#]'", $type, $name));
            $rec->newComment = $recCommon->newComment;
        }
        
        $rec->type = $type;
        $rec->fileName = $this->sourceFile;
        $rec->name = $name;
        
        if ($type == 'defIfNot') {
            $rec->value = $value;
            
            if (trim($value) == ';' || trim($ta[$e[$id + 4]]->str) == ';') {
                unset($value);
            }
        }
        
        refactor_Formater::save($rec);
        
        return $rec->newComment;
    }
        
    
    /**
     * @todo Чака за документация...
     */
    public function commentsToLines()
    {
        $tokenArr = &$this->tokenArr;
        
        if (is_array($tokenArr)) {
            foreach ($tokenArr as $i => $c) {
                if ($c->type == T_COMMENT || $c->type == T_OPEN_TAG) {
                    if ($c->str{strlen($c->str) - 1} == "\n") {
                        $c->insertAfter(T_WHITESPACE, "\n");
                        $c->str = substr($c->str, 0, strlen($c->str) - 1);
                        expect(strlen($c->str));
                    }
                }
                
                if ($c->type == T_DOC_COMMENT) {
                    $lines = explode("\n", $c->str);
                    
                    $flag = false;
                    
                    foreach ($lines as $l) {
                        if ($flag) {
                            $c->insertAfter(T_WHITESPACE, "\n");
                        }
                        $l = trim($l);
                        
                        if ($l && $l{0} != '/') {
                            $l = ' ' . $l;
                        }
                        $c->insertAfter(T_DOC_COMMENT, $l);
                        $flag = true;
                    }
                    $c->delete();
                }
            }
        }
        $this->flat();
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function addIndent()
    {
        $tokenArr = &$this->tokenArr;
        
        $ident = $this->ident;
        
        $level = 0;
        
        if (is_array($tokenArr)) {
            foreach ($tokenArr as $i => $c) {
                $next = $tokenArr[$i + 1];
                
                $pos = $i + 1;
                
                do {
                    $nextE = $tokenArr[$pos];
                    $pos++;
                } while ($nextE->type == T_WHITESPACE);
                
                $prev = $tokenArr[$i - 1];
                $pos = $i - 1;
                
                do {
                    $prevE = $tokenArr[$pos];
                    $pos--;
                } while ($prevE->type == T_WHITESPACE);
                
                if ($c->type == '{' || $c->type == T_CURLY_OPEN) {
                    $level++;
                    $close[$level] = array('}');
                }
                
                if ($c->type == ':' && ($tokenArr[$i - 4]->type == T_CASE || $tokenArr[$i - 3]->type == T_CASE)) {
                    $level++;
                    $close[$level] = array(T_CASE, T_DEFAULT);
                }
                
                if ($c->type == '(') {
                    $level++;
                    $close[$level] = array(')');
                }
                
                if ($next->type == '}') {
                    while ($level > 0 && is_array($close[$level]) && !in_array('}', $close[$level])) {
                        unset($close[$level]);
                        $level--;
                    }
                }
                
                if (is_array($close[$level]) && in_array($next->type, $close[$level])) {
                    unset($close[$level]);
                    $level--;
                }
                
                if (($c->type == T_WHITESPACE) && (strpos($c->str, "\n") !== false)) {
                    //$c->str = str_replace("\n", "\n" . str_repeat($ident, $level), $c->str);
                    $c->str = preg_replace("/\n */", "\n" . str_repeat($ident, $level), $c->str);
                    
                    expect(strlen($c->str));
                }
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function addEmptyRows()
    {
        $tokenArr = &$this->tokenArr;
        
        if (is_array($tokenArr)) {
            foreach ($tokenArr as $i => $c) {
                $next = $tokenArr[$i + 1];
                
                $pos = $i + 1;
                
                do {
                    $nextE = $tokenArr[$pos];
                    $pos++;
                } while ($nextE->type == T_WHITESPACE);
                
                $prev = $tokenArr[$i - 1];
                $pos = $i - 1;
                
                do {
                    $prevE = $tokenArr[$pos];
                    $pos--;
                } while ($prevE->type == T_WHITESPACE);
                
                if (in_array($c->type, array(T_IF, T_WHILE, T_DO, T_FOR, T_FOREACH, T_SWITCH, T_RETURN, T_DOC_COMMENT, T_COMMENT))) {
                    if ($prevE->type == '}' || $prevE->type == ';') {
                        if ($prev->type == T_WHITESPACE && count(explode("\n", $prev->str)) == 2) {
                            $prev->str .= "\n";
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function removeEmptyRows()
    {
        $tokenArr = &$this->tokenArr;
        
        if (is_array($tokenArr)) {
            foreach ($tokenArr as $i => $c) {
                $next = $tokenArr[$i + 1];
                
                $pos = $i + 1;
                
                do {
                    $nextE = $tokenArr[$pos];
                    $pos++;
                } while ($nextE->type == T_WHITESPACE);
                
                $prev = $tokenArr[$i - 1];
                $pos = $i - 1;
                
                do {
                    $prevE = $tokenArr[$pos];
                    $pos--;
                } while ($prevE->type == T_WHITESPACE);
                
                if ($c->type == T_WHITESPACE &&
                    ($prev->type == '}' || $prev->type == ';') &&
                    $next->type == '}') {
                    $c->str = "\n";
                }
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function flat()
    {
        if (is_array($this->tokenArr)) {
            foreach ($this->tokenArr as $i => $c) {
                if (count($c->insertBefore)) {
                    foreach ($c->insertBefore as $add) {
                        $res[] = $add;
                    }
                    
                    unset($c->insertBefore);
                }
                
                if (!$c->delete) {
                    $res[] = $c;
                }
                
                if (count($c->insertAfter)) {
                    foreach ($c->insertAfter as $add) {
                        $res[] = $add;
                    }
                    
                    unset($c->insertAfter);
                }
            }
        }
        
        // Укропняване на white space
        if (is_array($res)) {
            foreach ($res as $i => $c) {
                if ($c->type == T_WHITESPACE && $res[$i - 1]->type == T_WHITESPACE) {
                    $last->str .= $c->str;
                } else {
                    $res1[] = $c;
                }
                
                $last = $c;
            }
        }
        
        $this->tokenArr = $res1;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function getText()
    {
        $this->flat();
        if (is_array($this->tokenArr)) {
            foreach ($this->tokenArr as $i => $c) {
                $res .= $c->str;
            }
        }
        
        return $res;
    }

}
