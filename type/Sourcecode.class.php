<?php



/**
 * Клас  'type_Sourcecode' - Тип за софтуерен код
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
class type_Sourcecode extends type_Html {
    
    
    /**
     * Връща шаблон за textarea поле, в което може да се редактира сорс-код
     * Поддържа оцветяване на синтаксиса и някои други екстри
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        static $SourceCodeEditors;
        
        if(!$SourceCodeEditors) {
            $SourceCodeEditors = array('CodeMirror' => 'codemirror_Import', 'EditArea' => 'editarea_Import');
        } else {
            reset($SourceCodeEditors);
        }
        
        // Само параметъра 'syntax' се взема от форматера, т.е. може да се задава 
        // при създаването на типа на полето
        $attr['#syntax'] = $attr['#syntax'] ? $attr['#syntax'] : $this->params['syntax'];
        $attr['#editor'] = $attr['#editor'] ? $attr['#editor'] : $this->params['editor'];
        
        // Ако имаме зададен редактор, използваме него
        if($attr['#editor']) {
            $editor = cls::get($SourceCodeEditors[$attr['#editor']]);
        } elseif($attr['#syntax']) {
            // Иначе, зако имаме зададен синтаксис, използваме първия редактор, който го поддържа
            foreach($SourceCodeEditors as $className) {
                $editor = cls::get($className);
                
                if($editor->isSupportLang($attr['#syntax'])) break;
                unset($editor);
            }
        }
        
        if(!$editor) {
            // Ако не е намерен редактор, използваме първия редактор от списъка
            reset($SourceCodeEditors);
            $editor = cls::get(current($SourceCodeEditors));
        }
        
        // Рендира редактора
        $method = "render" . $name;
        
        return $editor->$method($value, $attr);
    }
    
    
    /**
     * Връща форматиран кода, като поставя, ако може синтактично оцветяване
     */
    function toVerbal($value)
    {
        if(!$value) return NULL;
        
        $GeSHi = cls::get('geshi_Import');
        
        return $GeSHi->renderHtml($value, $this->params['syntax']);
    }
}