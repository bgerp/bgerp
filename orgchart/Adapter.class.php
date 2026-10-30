<?php


/**
 * Организациони структурии
 *
 * @category  vendors
 * @package   orgchart
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class orgchart_Adapter extends core_BaseClass
{
    /**
     * начертаване на организациони структури по даден двумерен масив
     *
     * @param array $orgData - двумерен масив, от който вземаме данните за структурата
     *
     * $orgData трябва да има следната структура:
     * $orgData = array(
     *		  	'0' => array (
     *   					'id' => 1,
     *   					'title' => "Title",
     *  					'parent_id' => 'NULL'
     *   			)
     * id - id на елемента
     * title - текста, които ще се показва за всеки елемент
     * parent_id - id на родителя
     */
    public static function render_($orgData)
    {
        static $orgChartCnt;
        
        if (!$orgChartCnt) {
            $orgChartCnt = 0;
        }
        
        $orgChartCnt++;
        
        $idChart = 'orgChart' . $orgChartCnt;
        $sourceId = $idChart . 'Source';
        
        $level = 'NULL';
        $nestedLists = static::transformArrayToNestedLists($orgData, $level);
        
        // Създаваме шаблона
        $tpl = new ET();
        
        // Генерираме необходимия маркъп за плъгина
        $tpl->append("<div class='organisation' id='{$sourceId}'>{$nestedLists}</div><div id='{$idChart}'></div>");
        
        $version = orgchart_Setup::get('VERSION') === '1.0.0' ? '1.0.0' : 'lib';
        $tpl->push("orgchart/{$version}/jquery.orgchart.css", 'CSS');
        $tpl->push("orgchart/{$version}/jquery.orgchart.js", 'JS');
        
        jquery_Jquery::run($tpl, "$('#{$sourceId} > ul').orgChart({container: $('#{$idChart}'), interactive: true});", true);
        
        return $tpl;
    }
    
    
    /**
     * рекурсивна функция, която от дадения масив генерира хтмл за вложени списъци
     */
    public static function transformArrayToNestedLists($array, $level)
    {
        $html = '' ;
        
        foreach ((array) $array as $currentArr) {
            if ($currentArr['parent_id'] == $level) {
                $html = $html . "\n<li>" . $currentArr['title'] . static::transformArrayToNestedLists($array, $currentArr['id']) . "</li>\n";
            }
        }
        
        return ($html == '' ? '' : "\n<ul>" . $html . "</ul>\n");
    }
}
