<?php



/**
 * Клас 'core_Tree' - Изглед за дърво
 *
 *
 * @category  all
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Tree extends core_BaseClass
{
    
    
    /**
     * Масив в елементите на дървото
     */
    var $nodes = array();
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params)
    {
        parent::init($params);
        
        setIfNot($this->htmlClass, 'tree-control');
    }
    
    
    /**
     * Добавя елемент в дървото
     */
    function addNode($path, $url)
    {
        $nodes = explode("->", $path);
        
        $pid = -1;
        
        foreach($nodes as $node) {
            
            $currentPath .= ($currentPath ? "->" : "") . $node;
            
            if(!isset($this->nodes[$currentPath])) {
                
                $n = new stdClass();
                
                $n->id = count($this->nodes);
                $n->pid = $pid;
                $pid = $n->id;
                $n->title = $node;
                $n->url = toUrl($url);
                
                $this->nodes[$currentPath] = $n;
            }
            
            $pid = $this->nodes[$currentPath]->id;
        }
    }
    
    
    /**
     * Рендира дървото
     */
    function renderHtml_($body, $selected = NULL)
    {
        // Ако нямаме дърво - връщаме съдържанието без промяна
        if (!count($this->nodes)) {
            return $body;
        }
        
        //  @тодо    
        if (!$selectedNode) {
            $selectedId = 0;
        }
        
        $tpl = new ET("
         <div class='dtree' style='float:left;'>

 
        <script type='text/javascript'>
            <!--

            [#treeName#] = new dTree('[#treeName#]');

            [#treeDesciption#]

            document.write([#treeName#]);

            //-->
        </script>

        </div>
         <div style='float:left;margin-left:10px;'> [#body#]</div>  
         <div style='clear:both'></div>
        ");
        
        $name = $this->name;
        
        $tpl->append("\n{$name}.icon.root = " . sbf('img/dtree/base.gif', "'") . ";", 'treeDesciption');
        $tpl->append("\n{$name}.icon.folder = " . sbf('img/dtree/folder.gif', "'") . ";", 'treeDesciption');
        $tpl->append("\n{$name}.icon.folderOpen = " . sbf('img/dtree/folderopen.gif', "'") . ";", 'treeDesciption');
        $tpl->append("\n{$name}.icon.node = " . sbf('img/dtree/page.gif', "'") . ";", 'treeDesciption');
        $tpl->append("\n{$name}.icon.empty = " . sbf('img/dtree/empty.gif', "'") . ";", 'treeDesciption');
        $tpl->append("\n{$name}.icon.line = " . sbf('img/dtree/line.gif', "'") . ";", 'treeDesciption');
        $tpl->append("\n{$name}.icon.join = " . sbf('img/dtree/join.gif', "'") . ";", 'treeDesciption');
        $tpl->append("\n{$name}.icon.joinBottom = " . sbf('img/dtree/joinbottom.gif', "'") . ";", 'treeDesciption');
        $tpl->append("\n{$name}.icon.plus = " . sbf('img/dtree/plus.gif', "'") . ";", 'treeDesciption');
        $tpl->append("\n{$name}.icon.plusBottom = " . sbf('img/dtree/plusbottom.gif', "'") . ";", 'treeDesciption');
        $tpl->append("\n{$name}.icon.minus = " . sbf('img/dtree/minus.gif', "'") . ";", 'treeDesciption');
        $tpl->append("\n{$name}.icon.minusBottom = " . sbf('img/dtree/minusbottom.gif', "'") . ";", 'treeDesciption');
        $tpl->append("\n{$name}.icon.nlPlus = " . sbf('img/dtree/nolines_plus.gif', "'") . ";", 'treeDesciption');
        $tpl->append("\n{$name}.icon.nlMinus = " . sbf('img/dtree/nolines_minus.gif', "'") . ";", 'treeDesciption');
        
        foreach ($this->nodes as $path => $n) {
            $tpl->append("\n{$name}.add({$n->id}, {$n->pid}, '{$n->title}', '{$n->url}');", 'treeDesciption');
        }
        
        if($selectedId) {
            // $tpl->append("\n{$name}.openTo({$selectedId}, true);", 'treeDesciption');
        }
        
        $tpl->replace($name, 'treeName');
        
        $tpl->replace($body, 'body');
        
        $tpl->push('css/dtree.css', 'CSS');
        $tpl->push('js/dtree.js', 'JS');
        
        return $tpl;
    }
}