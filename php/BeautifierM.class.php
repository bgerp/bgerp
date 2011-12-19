<?php

cls::load('php_Token');


/**
 *  Php code beautifier
 *  @author Milen Georgiev
 */
class php_BeautifierM
{
    /**
     * 
     * Масив с всички дефинирани функции
     * @var array
     */
    var $arr;
    
    
    /**
     * 
     * Масив с всички използвани функции
     * @var array
     */
    var $arrF;

    
    /**
     *  @param Символи за отместване между вложените блокове
     */
    var $ident = '    ';
    
    
    /**
     *  @todo Чака за документация...
     */
    static $r;
    
    
    /**
     *  @todo Чака за документация...
     */
    public function process($str)
    {
        $str = str_replace("\r\n", "\n", $str);
        $str = str_replace("\t", $this->ident, $str);
        
        $this->parse($str);
        
        $this->normalizeWhiteSpace();
        
        $this->normalizeDocComments();
        
        $this->commentsToLines();
        
        $this->addEmptyRows();
        
        $this->removeEmptyRows();
        
        $this->addIndent();
        
        return $this->generate();
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function parse($str)
    { $this->str = $str;
        $tokens = token_get_all($str);
        
        expect(is_array($tokens));
        
        for($i = 0; $i< $count = count($tokens); $i++) {
            
            $token = $tokens[$i];
            
            if(is_array($tokens[$i])) {
                $this->tokenArr[] = new php_Token($token[0], $token[1]); //?
            } else {
                $this->tokenArr[] = new php_Token($token, $token);
            }
        }
    }
    
    
    /**
     * Извършва нормализации
     */
    function normalizeWhiteSpace()
    {
        $tokenArr = &$this->tokenArr;
        
        foreach($tokenArr as $i => $c) { //?
            
            $next = $tokenArr[$i+1];
            $pos = $i+1;
            
            do{
                $nextE = $tokenArr[$pos];
                $pos++;
            } while ($nextE->type == T_WHITESPACE);
            
            $prev = $tokenArr[$i-1];
            $pos = $i-1;
            
            do{
                $prevE = $tokenArr[$pos];
                $pos--;
            } while ($prevE->type == T_WHITESPACE);
            
            if( $c->type == T_STRING ) {
                if( in_array( strtoupper($c->str), array('TRUE', 'FALSE', 'NULL')) && $prevE->type == '=') {
                    $c->str = strtoupper($c->str);
                }
            }
            
            if($c->type == T_WHITESPACE) {
                if($nl = (count(explode("\n", $c->str)) - 1) ) {
                    $c->str = str_repeat("\n", min($nl, 2));
                    expect(strlen($c->str));
                } else {
                    $c->str = ' ';
                }
            }
            
            $prev_ = $tokenArr[$i-2]->type;
            
            if($c->type == T_COMMENT) {
                if( $prev->type == ';' ||
                $prev->type == '}' ||
                $prev->type == T_WHITESPACE && (strpos($prev->str, "\n") === FALSE) && ($prev_ == ';' || $prev_ == '}')
                ) {
                    $c->type = '';
                    $c->insertAfter(T_WHITESPACE, "\n");
                    $c->str = trim($c->str, "\n ");
                    
                    if($next->type == T_WHITESPACE) {
                        $next->delete();
                    }
                }
            }
        }
        
        $this->flat();
        
        foreach($tokenArr as $i => $c) {
            
            $next = $tokenArr[$i+1];
            $pos = $i+1;
            
            do{
                $nextE = $tokenArr[$pos];
                $pos++;
            } while ($nextE->type == T_WHITESPACE);
            
            $prev = $tokenArr[$i-1];
            $pos = $i-1;
            
            do{
                $prevE = $tokenArr[$pos];
                $pos--;
            } while ($prevE->type == T_WHITESPACE);
            
            if($c->type == T_COMMENT && $next->type == T_WHITESPACE) {
                
                if($next->str == " ") $next->str = "";
            }
        }
    }




    /**
     *  @todo Чака за документация...
     */
    function normalizeDocComments()
    {
         
        $ta = &$this->tokenArr;
        
        // Правим масив с индексите само само на елементите, без whitespace
        foreach($ta as $i => $c) {
            if($c->type != T_WHITESPACE) {
                $e[] = $i;
            }
        }

        foreach($e as $id => $i) {
            
            unset($commentId, $type, $name, $comment); 

            // Разпознаваме специалните елементи на скрипта
            if( $ta[$e[$id]]->type == T_FUNCTION && in_array($ta[$e[$id-1]]->type, array(';', '}', '{', T_COMMENT, T_DOC_COMMENT)) ) {
                $commentId = $id-1;
                $type = 'function';
                $name = $ta[$e[$id+1]]->str; 
               	$this->arr[$name]++;
            }elseif( ($ta[$e[$id]]->type == T_STATIC) && ($ta[$e[$id+1]]->type == T_FUNCTION) && (in_array($ta[$e[$id-1]]->type, array(';', '}', '{', T_COMMENT, T_DOC_COMMENT))) ) {
                $commentId = $id-1;
                $type = 'static_function';
                $name = $ta[$e[$id+2]]->str;
                $this->arr[$name]++;
            }elseif( ($ta[$e[$id]]->type == T_PUBLIC) && ($ta[$e[$id+1]]->type == T_FUNCTION) && (in_array($ta[$e[$id-1]]->type, array(';', '}', '{', T_COMMENT, T_DOC_COMMENT))) ) {
                $commentId = $id-1;
                $type = 'public_function';
                $name = $ta[$e[$id+2]]->str;
                $this->arr[$name]++;
            }elseif( ($ta[$e[$id]]->type == T_PRIVATE) && ($ta[$e[$id+1]]->type == T_FUNCTION) && (in_array($ta[$e[$id-1]]->type, array(';', '}', '{', T_COMMENT, T_DOC_COMMENT))) ) {
                $commentId = $id-1;
                $type = 'private_function';
                $name = $ta[$e[$id+2]]->str;
                $this->arr[$name]++;
            }elseif( ($ta[$e[$id]]->type == T_PROTECTED) && ($ta[$e[$id+1]]->type == T_FUNCTION) && (in_array($ta[$e[$id-1]]->type, array(';', '}', '{', T_COMMENT, T_DOC_COMMENT))) ) {
                $commentId = $id-1;
                $type = 'protected_function';
                $name = $ta[$e[$id+2]]->str;
                $this->arr[$name]++;
            }elseif( ($ta[$e[$id]]->type == T_PUBLIC) && ($ta[$e[$id+1]]->type == T_STATIC) && ($ta[$e[$id+2]]->type == T_FUNCTION) && (in_array($ta[$e[$id-1]]->type, array(';', '}', '{', T_COMMENT, T_DOC_COMMENT))) ) {
                $commentId = $id-1;
                $type = 'public_static_function';
                $name = $ta[$e[$id+3]]->str;
                $this->arr[$name]++;
            }elseif( ($ta[$e[$id]]->type == T_STATIC) && ($ta[$e[$id+1]]->type == T_PUBLIC) && ($ta[$e[$id+2]]->type == T_FUNCTION) && (in_array($ta[$e[$id-1]]->type, array(';', '}', '{', T_COMMENT, T_DOC_COMMENT))) ) {
                $commentId = $id-1;
                $type = 'static_public_function';
                $name = $ta[$e[$id+3]]->str;
                $this->arr[$name]++;
            }elseif( ($ta[$e[$id]]->type == T_PRIVATE) && ($ta[$e[$id+1]]->type == T_STATIC) && ($ta[$e[$id+2]]->type == T_FUNCTION) && (in_array($ta[$e[$id-1]]->type, array(';', '}', '{', T_COMMENT, T_DOC_COMMENT))) ) {
                $commentId = $id-1;
                $type = 'private_static_function';
                $name = $ta[$e[$id+3]]->str;
                $this->arr[$name]++; 
            }elseif( ($ta[$e[$id]]->type == T_STATIC) && ($ta[$e[$id+1]]->type == T_PRIVATE) && ($ta[$e[$id+2]]->type == T_FUNCTION) && (in_array($ta[$e[$id-1]]->type, array(';', '}', '{', T_COMMENT, T_DOC_COMMENT))) ) {
                $commentId = $id-1;
                $type = 'static_private_function';
                $name = $ta[$e[$id+3]]->str;
                $this->arr[$name]++; 
            }elseif (($ta[$e[$id]]->type == T_STRING) && ($ta[$e[$id]]->str == 'defIfNot') && (in_array($ta[$e[$id-1]]->type, array(';', T_COMMENT, T_DOC_COMMENT))) ) {
                $commentId = $id-1;
                $type = 'defIfNot';
                $name = $ta[$e[$id+2]]->str;
            }elseif (($ta[$e[$id]]->type == T_STRING) && ( ($ta[$e[$id]]->str == 'define') || ($ta[$e[$id]]->str == 'DEFINE')) && (in_array($ta[$e[$id-1]]->type, array(';', T_COMMENT, T_DOC_COMMENT))) ) {
                $commentId = $id-1;
                $type = 'define';
                $name = $ta[$e[$id+2]]->str;
            }elseif ($ta[$e[$id]]->type == T_CLASS) {
                $commentId = $id-1;
                $type = 'class';
                $name = $ta[$e[$id+1]]->str;
            }elseif ($ta[$e[$id]]->type == T_VAR) {
                $commentId = $id-1;
                $type = 'var';
                $name = $ta[$e[$id+1]]->str;
            }elseif ($ta[$e[$id]]->type == T_CONST) {
                $commentId = $id-1;
                $type = 'const';
                $name = $ta[$e[$id+1]]->str;   
            } elseif (($ta[$e[$id]]->type == T_STRING)   && ($ta[$e[$id-1]]->type != T_FUNCTION )) {
            	
            	$this->arrF[$ta[$e[$id]]->str]++;
            	
            }
            
         
            
            // Опитваме се да извлечем коментарите
            if($commentId) {
                $last = &$ta[$e[$commentId]];
                if($last->type == T_DOC_COMMENT) {
                    
                    $lines = explode("\n", $last->str);
 
                    $singleComm = '';
                            
                    foreach($lines as $l) {
                        $l = trim($l);
                       // if($l == '*  @todo Чака за документация...') continue;
                        if(($l != '/**') && ($l != '*/')) {
                            $singleComm .=  ltrim($l, " *") . "\n";
                        }
                    }
                            
                    $comment = $singleComm . $comment;

                    $last->delete();

                } else {

                    while( $last->type == T_COMMENT ) {
                             
                        $lines = explode("\n", $last->str);
                            
                        $singleComm = '';
                            
                        foreach($lines as $l) {
                            $l = trim($l);
                            $singleComm .= trim($l, '/* ') . "\n";
                         }
                            
                        $comment = $singleComm . $comment;
                         
                        $last->delete();
                        
                        $commentId--;

                        $last = $ta[$e[$commentId]];
                    }
                }
                
                
                $newComment = $this->fetchComment($type, $name, $comment);
                
                // Ако сме получили някакъв коментар, опитваме се да го сложим
                if(trim($newComment)) {  
                    // Да направим docComment от $newComent
                    $docComment = "/**\n";

                    $lines = explode("\n", trim($newComment));

                    foreach($lines as $l) {
                        $docComment .= ' * ' . $l . "\n";
                    }

                    $docComment .= " */\n";

                    $ta[$e[$id]]->insertBefore(T_DOC_COMMENT, $docComment);
                }

            }
        }
           
		
        $this->flat();
    }

             
	
    
    
    /**
     *  @todo Чака за документация...
     */
    function normalizeDocComments1()
    {
        $eh = array (
            'on_AfterDescription' => 'Извиква се след описанието на модела',
            'on_BeforeGetVerbal' => 'Извиква се преди извличането на вербална стойност за поле от запис',
            'on_AfterPrepareListFields' => 'Извиква се след поготовката на колоните ($data->listFields)',
            'on_BeforeRenderInput' => 'Извиква се преди рендирането на HTML input',
            'on_AfterRenderInput' => 'Извиква се след рендирането на HTML input',
            'on_AfterPrepareListToolbar' => 'Извиква се след подготовката на toolbar-а за табличния изглед',
            'on_AfterSetupMVC' => 'Извиква се след SetUp-а на таблицата за модела',
            'on_BeforePrepareListRecs' => 'Извиква се преди подготовката на масивите $data->recs и $data->rows',
            'on_AfterPrepareEditForm' => 'Извиква се след подготовката на формата за редактиране/добавяне $data->form',
            'on_AfterRecToVerbal' => 'Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)',
            'on_AfterInputEditForm' => 'Извиква се след въвеждането на данните от Request във формата ($form->rec)',
            'on_BeforeSave' => 'Извиква се преди вкарване на запис в таблицата на модела',
            'on_AfterGetRequiredRoles' => 'Извиква се след изчисляването на необходимите роли за това действие',
            'on_AfterPrepareEditToolbar' => 'Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне',
            'on_BeforeAction' => 'Извиква се преди изпълняването на екшън',
            'on_AfterRenderInput' => 'Извиква се след рендирането на HTML input',
            'on_BeforeRenderInput' => 'Извиква се преди рендирането на HTML input',
            'on_AfterRenderWrapping' => 'Извиква се след рендирането на \'опаковката\' на мениджъра',
            'on_BeforeRenderListTable' => 'Извиква се след рендирането на таблицата от табличния изглед',
            'description' => 'Описание на модела (таблицата)',
            'install' => 'Инсталиране на пакета',
            'deinstall' => 'Де-инсталиране на пакета',
            'init' => 'Инициализиране на обекта',
        );
        
        foreach($eh as $key => $doc) {
            $ehKey[strToLower($key)] = $key;
        }
        
        $classDocTpl = new ET (
        " * Клас '[#class#]' - \n" .
        " * \n" .
        " * @todo: Да се документира този клас\n" .
        " * \n" .
        " * @category   Experta Framework\n" .
        " * @package    [#pack#]\n" .
        " * @author     \n" .
        " * @copyright  2006-[#year#] Experta OOD\n" .
        " * @license    GPL 2\n" .
        ' * @version    CVS: $Id:$\n' .
        " * @link\n" .
        " * @since      v 0.1\n" );
        
        $tokenArr = &$this->tokenArr;

        foreach($tokenArr as $i => $c) {
            
            $next = $tokenArr[$i+1];
            
            $pos = $i+1;
            do{
                $nextE = $tokenArr[$pos];
                $pos++;
            } while ($nextE->type == T_WHITESPACE);
            
            $prev = $tokenArr[$i-1];
            
            $pos = $i-1;
            do{
                $prevE = $tokenArr[$pos];
                $pos--;
            } while ($prevE->type == T_WHITESPACE);
            
            if( ($c->type == T_CLASS) ||
            
            ($c->type == T_FUNCTION && in_array($prevE->type, array(';', '}', '{', T_COMMENT, T_DOC_COMMENT))) ||
            
            ($c->type == T_FUNCTION && in_array($prevE->type, array(';', '}', '{', T_COMMENT, T_DOC_COMMENT))) ||
            
            ($c->type == T_CONST && in_array($prevE->type, array(';', '}', '{', T_COMMENT, T_DOC_COMMENT))) ||
            
            ($c->type == T_VAR && in_array($prevE->type, array(';', '}', '{', T_COMMENT, T_DOC_COMMENT))) ||
            
            ($c->type == T_STRING && ($c->str == 'defIfNot' || $c->str == 'DEFINE') && in_array($prevE->type, array(';', '}', '{', T_COMMENT, T_DOC_COMMENT)))
            
            ) {
                
                // Опитваме се в $docComment да сложим предходния коментар
                if($prevE->type != T_DOC_COMMENT) {
                    
                    // Вземаме каквито коментари има преди елемента
                    $pos = $i-1;
                    $docComm = '';
                    
                    while( $tokenArr[$pos]->type == T_COMMENT ||
                    ($tokenArr[$pos]->type == T_WHITESPACE ) ) {
                        
                        if($tokenArr[$pos]->type == T_COMMENT) {
                            
                            $lines = explode("\n", $tokenArr[$pos]->str);
                            
                            $singleComm = '';
                            
                            foreach($lines as $l) {
                                if(trim($l)) {
                                    $singleComm .= " * " . trim($l, '/* ') . "\n";
                                }
                            }
                            
                            $docComm = $singleComm . $docComm;
                        }
                        
                        $tokenArr[$pos]->delete();
                        
                        $pos--;
                    }

                    if($prevE->type != '{') {
                        $c->insertBefore(T_WHITESPACE, "\n\n");
                    }
                } else {
                   $docComm = $prevE->str;
                }

                    
                if($c->type == T_FUNCTION) {
                    if(!$docComm) {
                        if($fnName = $ehKey[strToLower($nextE->str)]) {
                            $docComm = " *  " . $eh[$fnName] . "\n";
                            $nextE->str = $fnName;
                        } else {
                            $this->res .= "<li> Няма doc comment за функциата <b>$nextE->str</b>";
                        }
                    }

                    $this->res .= "<table>
                    <tr>
                        <td>$this->lastClass - {$nextE->str}</td>
                        <td><pre>$docComm</pre></td>
                    </tr>
                    </table>";
                }
                
                if($c->type == T_CLASS) {
                   if(!$docComm) {
                       $class = $nextE->str;
                       $classArr = explode('_', $class);
                       
                       if($classArr >= 2) {
                           $docRec->class = $class;
                           $docRec->pack = $classArr[0];
                           $docRec->year = date('Y');
                           
                           $docTpl = clone($classDocTpl);
                           
                           $docTpl->placeObject($docRec);
                           
                           $docComm = $docTpl->getContent();
                       }
                   }

                   $this->lastClass = $nextE->str;

                   $this->classes[$class] = $docComm;
                }
                
                if(!$docComm) {
                    $docComm = " *  @todo Чака за документация...\n";
                }
                
                $c->insertBefore(T_DOC_COMMENT, "/**\n{$docComm} */\n");
            }
            
        }
        
        $this->flat();
    }



    /**
     * Връща новия коментар, който отговаря на езиковия ресурс
     */
    function fetchComment($type, $name, $oldComment)
    {
        if(!trim($oldComment)) $oldComment = NULL;

        $rec = php_Formater::fetch(array("#fileName = '[#1#]'  AND #type = '[#2#]' AND #name = '[#3#]'", $this->sourceFile, $type, $name));

        $rec->oldComment = $oldComment;
        
        if(!$rec->newComment) {
            $rec->newComment = $rec->oldComment;
        }

        if(!$rec->newComment) {
            $recCommon = php_Formater::fetch(array("#fileName = '*'  AND #type = '[#1#]' AND #name = '[#2#]'", $type, $name));
            $rec->newComment = $recCommon->newComment;
        }

        $rec->type = $type;
        $rec->fileName = $this->sourceFile;
        $rec->name = $name;

        php_Formater::save($rec);

        return $rec->newComment;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function commentsToLines()
    {
        $tokenArr = &$this->tokenArr;
        
        foreach($tokenArr as $i => $c) {
            
            if($c->type == T_COMMENT || $c->type == T_OPEN_TAG) {
                if($c->str{strlen($c->str)-1} == "\n") {
                    $c->insertAfter(T_WHITESPACE, "\n");
                    $c->str = substr($c->str, 0, strlen($c->str)-1);
                    expect(strlen($c->str));
                }
            }
            
            if($c->type == T_DOC_COMMENT) {
                $lines = explode("\n", $c->str);
                
                foreach($lines as $l) {
                    $c->insertAfter(T_WHITESPACE, "\n");
                    $l = trim($l);
                    
                    if($l && $l{0} != "/") $l = ' ' . $l;
                    $c->insertAfter(T_DOC_COMMENT, $l);
                }
                $c->delete();
            }
        }
        
        $this->flat();
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function addIndent()
    {
        $tokenArr = &$this->tokenArr;
        
        $ident = $this->ident;
        
        $level = 0;
        
        foreach($tokenArr as $i => $c) {
            
            $next = $tokenArr[$i+1];
            
            $pos = $i+1;
            
            do{
                $nextE = $tokenArr[$pos];
                $pos++;
            } while ($nextE->type == T_WHITESPACE);
            
            $prev = $tokenArr[$i-1];
            $pos = $i-1;
            
            do{
                $prevE = $tokenArr[$pos];
                $pos--;
            } while ($prevE->type == T_WHITESPACE);
            
            if($c->type == '{' || $c->type == T_CURLY_OPEN) {
                $level++;
                $close[$level] = array('}');
            }
            
            if($c->type == ':' && ($tokenArr[$i-4]->type == T_CASE || $tokenArr[$i-3]->type == T_CASE) ) {
                $level++;
                $close[$level] = array(T_CASE, T_DEFAULT);
            }
            
            if($c->type == '(' && $prevE->type == T_ARRAY) {
                $level++;
                $close[$level] = array(')');
            }
            
            if ($next->type == '}') {
                while($level > 0 && is_array($close[$level]) && !in_array('}', $close[$level])) {
                    unset($close[$level]);
                    $level--;
                }
            }
            
            if( is_array($close[$level]) && in_array($next->type, $close[$level])) {
                unset($close[$level]);
                $level--;
            }
            
            if(($c->type == T_WHITESPACE) && (strpos($c->str, "\n") !== FALSE)) {
                $c->str = str_replace("\n", "\n" . str_repeat($ident, $level), $c->str);
                expect(strlen($c->str));
            }
        }
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function addEmptyRows()
    {
        $tokenArr = &$this->tokenArr;
        
        foreach($tokenArr as $i => $c) {
            
            $next = $tokenArr[$i+1];
            
            $pos = $i+1;
            
            do{
                $nextE = $tokenArr[$pos];
                $pos++;
            } while ($nextE->type == T_WHITESPACE);
            
            $prev = $tokenArr[$i-1];
            $pos = $i-1;
            
            do{
                $prevE = $tokenArr[$pos];
                $pos--;
            } while ($prevE->type == T_WHITESPACE);
            
            if( in_array($c->type, array(T_IF, T_WHILE, T_DO, T_FOR, T_FOREACH, T_SWITCH, T_RETURN)) ) {
                if($prevE->type == '}' || $prevE->type == ';') {
                    if($prev->type == T_WHITESPACE && count(explode("\n", $prev->str)) == 2 ) {
                        $prev->str .= "\n";
                    }
                }
            }
        }
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function removeEmptyRows()
    {
        $tokenArr = &$this->tokenArr;
        
        foreach($tokenArr as $i => $c) {
            
            $next = $tokenArr[$i+1];
            
            $pos = $i+1;
            
            do{
                $nextE = $tokenArr[$pos];
                $pos++;
            } while ($nextE->type == T_WHITESPACE);
            
            $prev = $tokenArr[$i-1];
            $pos = $i-1;
            
            do{
                $prevE = $tokenArr[$pos];
                $pos--;
            } while ($prevE->type == T_WHITESPACE);
            
            if( $c->type == T_WHITESPACE &&
            ($prev->type == '}' || $prev->type == ';') &&
            $next->type == '}' ) {
                
                $c->str = "\n";
            }
        }
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function flat()
    {
        foreach($this->tokenArr as $i => $c) {
            
            if(count($c->insertBefore)) {
                foreach($c->insertBefore as $add) {
                    $res[] = $add;
                }
                
                unset($c->insertBefore);
            }
            
            if(!$c->delete ) $res[] = $c;
            
            if(count($c->insertAfter)) {
                foreach($c->insertAfter as $add) {
                    $res[] = $add;
                }
                
                unset($c->insertAfter);
            }
        }
        
        // Укропняване на white space
        foreach($res as $i => $c) {
            
            if($c->type == T_WHITESPACE && $res[$i-1]->type == T_WHITESPACE) {
                $last->str .= $c->str;
            } else {
                $res1[] = $c;
            }
            
            $last = $c;
        }
        
        $this->tokenArr = $res1;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    public static function test() {}
    
    private function generate()
    {
        foreach($this->tokenArr as $i => $c) {
            
            $res .= $c->str;
        }
        
        return $res;
    }
    
    
    /**
     * Преформатиране на php - файл
     */
    function file($source, $destination = NULL )
    {
        $this->sourceFile = $source;

        $str = file_get_contents( $source );
        
        $enc = $this->detectEncoding($str);
        
        if($enc != 'UTF-8') {
            $this->res .= "<li> Забележка: Файлът <b>" . str_replace("\\", "/", $source) . "</b> не е с UTF-8 кодиране.";
        }
        
        $str = $this->process($str);
        
        $str = str_replace("\n", "\r\n", $str);
        
        if ( empty($destination) ) {
            echo $str;
        } else {
            file_put_contents($destination, $str);
        }
        
        return $this->res;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function detectEncoding($string)
    {
        static $list = array('UTF-8', 'windows-1251');
        
        foreach ($list as $item) {
            $sample = iconv($item, $item, $string);
            
            if (md5($sample) == md5($string))
            return $item;
        }
        
        return null;
    }
}