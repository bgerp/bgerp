<?php


/**
 * Клас 'core_Tree' - Изглед за дърво
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Tree extends core_BaseClass
{
    /**
     * Масив в елементите на дървото
     */
    public $nodes = array();
    
    
    /**
     * Инициализиране на обекта
     */
    public function init($params = array())
    {
        parent::init($params);
        
        setIfNot($this->htmlClass, 'tree-control');
    }
    
    
    /**
     * Добавя елемент в дървото
     */
    public function addNode($path, $url, $onlyLastUrl = false)
    {
        $nodes = explode('->', $path);
        
        $pid = -1;
        
        $nodesCnt = countR($nodes);
        
        foreach ($nodes as $key => $node) {
            $currentPath .= ($currentPath ? '->' : '') . $node;
            
            if (!isset($this->nodes[$currentPath])) {
                $n = new stdClass();
                
                $n->id = countR($this->nodes);
                $n->pid = $pid;
                $pid = $n->id;
                $n->title = $node;
                
                // Ако е задедено само на последния nod да се добавя URL
                if (!$onlyLastUrl || ($onlyLastUrl && ($key == $nodesCnt - 1))) {
                    if ($url) {
                        $n->url = toUrl($url);
                    }
                }
                
                $this->nodes[$currentPath] = $n;
            }
            
            $pid = $this->nodes[$currentPath]->id;
        }
    }
    
    
    /**
     * Рендира дървото
     */
    public function renderHtml_($body, $selected = null)
    {
        // Ако нямаме дърво - връщаме съдържанието без промяна
        if (!countR($this->nodes)) {
            
            return $body;
        }
        
        //  @тодо
//        if (!$selectedNode) {
            $selectedId = 0;
//        }

        $tpl = new ET("
         <div class='dtree' id='dtreeBlock' style='float:left;'>
            
         </div>
         <div style='float:left;margin-left:10px;'> [#body#]</div>  
         <div style='clear:both'></div>
        ");

        $name = $this->name;

        $jsTpl = new ET("[#treeName#] = new dTree('[#treeName#]'); [#treeDesciption#] 
                                 document.getElementById('dtreeBlock').innerHTML = [#treeName#];");

        $jsTpl->append("\n{$name}.icon.root = " . sbf('img/dtree/base.gif', "'") . ';', 'treeDesciption');
        $jsTpl->append("\n{$name}.icon.folder = " . sbf('img/dtree/folder.gif', "'") . ';', 'treeDesciption');
        $jsTpl->append("\n{$name}.icon.folderOpen = " . sbf('img/dtree/folderopen.gif', "'") . ';', 'treeDesciption');
        $jsTpl->append("\n{$name}.icon.node = " . sbf('img/dtree/page.gif', "'") . ';', 'treeDesciption');
        $jsTpl->append("\n{$name}.icon.empty = " . sbf('img/dtree/empty.gif', "'") . ';', 'treeDesciption');
        $jsTpl->append("\n{$name}.icon.line = " . sbf('img/dtree/line.gif', "'") . ';', 'treeDesciption');
        $jsTpl->append("\n{$name}.icon.join = " . sbf('img/dtree/join.gif', "'") . ';', 'treeDesciption');
        $jsTpl->append("\n{$name}.icon.joinBottom = " . sbf('img/dtree/joinbottom.gif', "'") . ';', 'treeDesciption');
        $jsTpl->append("\n{$name}.icon.plus = " . sbf('img/dtree/plus.gif', "'") . ';', 'treeDesciption');
        $jsTpl->append("\n{$name}.icon.plusBottom = " . sbf('img/dtree/plusbottom.gif', "'") . ';', 'treeDesciption');
        $jsTpl->append("\n{$name}.icon.minus = " . sbf('img/dtree/minus.gif', "'") . ';', 'treeDesciption');
        $jsTpl->append("\n{$name}.icon.minusBottom = " . sbf('img/dtree/minusbottom.gif', "'") . ';', 'treeDesciption');
        $jsTpl->append("\n{$name}.icon.nlPlus = " . sbf('img/dtree/nolines_plus.gif', "'") . ';', 'treeDesciption');
        $jsTpl->append("\n{$name}.icon.nlMinus = " . sbf('img/dtree/nolines_minus.gif', "'") . ';', 'treeDesciption');
        
        foreach ($this->nodes as $path => $n) {
            $n->title = json_encode($n->title);
            $n->url = json_encode($n->url);
            
            // Генерираме стринга
            $treeDescription .= "\n{$name}.add({$n->id}, {$n->pid}, {$n->title}, {$n->url});";
        }
        
        // Аппендваме стринга
        $jsTpl->append($treeDescription, 'treeDesciption');
        
        if ($selectedId) {
            // $tpl->append("\n{$name}.openTo({$selectedId}, true);", 'treeDesciption');
        }

        $jsTpl->replace($name, 'treeName');
        
        $tpl->replace($body, 'body');
        
        $tpl->push('css/dtree.css', 'CSS');
        $tpl->push('js/dtree.js', 'JS');

        jquery_Jquery::run($tpl, "\n {$jsTpl}", true);

        return $tpl;
    }
}
