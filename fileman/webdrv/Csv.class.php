<?php


/**
 * Драйвер за работа с .csv файлове.
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Csv extends fileman_webdrv_Code
{
    
    
    
    /**
     * Кой таб да е избран по подразбиране
     * @Override
     * @see fileman_webdrv_Generic::$defaultTab
     */
    public static $defaultTab = 'view';
    
    
    /**
     * Връща всички табове, които ги има за съответния файл
     *
     * @param object $fRec - Записите за файла
     *
     * @return array
     *
     * @Override
     * @see fileman_webdrv_Generic::getTabs
     */
    public static function getTabs($fRec)
    {
        // Вземаме табовете от родителя
        $tabsArr = parent::getTabs($fRec);
        
        // Вземаме съдържанието
        $view = static::getView($fRec);
        
        // Таб за съдържанието
        $tabsArr['view'] = (object)
        array(
                'title' => 'Изглед',
                'html' => "<div class='webdrvTabBody' style='white-space:pre-wrap;'><div class='webdrvFieldset'><div class='legend'>" . tr('Съдържание') . "</div>{$view}</div></div>",
                'order' => 6,
                'tpl' => $view,
        );
        
        return $tabsArr;
    }
    
    
    /**
     * Връща изгледа на файла
     *
     * @param object $fRec - Запис на архива
     *
     * @return string - Съдържанието на файла, като код
     */
    public static function getView($fRec)
    {
        // Вземаме съдържанието на файла
        $content = fileman_Files::getContent($fRec->fileHnd);
        
        $res = csv_Lib::getCsvRowsFromFile($content);
        
        if ($res === false) {
            
            return parent::getContent($fRec);
        }
        
        $rows = array();

        if (isset($res['firstRow'])) {
            foreach ($res['firstRow'] as $col) {
                if (strpos($col, '<') !== false) {
                    $col = hclean_Purifier::clean($col, 'UTF-8');
                }
                $rows[-1] .= "<th style='background-color:#eee;'>" . $col . '</th>';
            }
        }
        
        $formats = csv_Lib::getColumnTypes($res['data']);

        $eml = cls::get('type_Email');
        $emls = cls::get('type_Emails');

        foreach ($res['data'] as $i => $r) {
            if (!$cnt) {
                $cnt = count($r);
            }
            foreach ($r as $j => $col) {
                if (strpos($col, '<') !== false) {
                    $col = hclean_Purifier::clean($col, 'UTF-8');
                }
                if ($formats['fixed_'.$j]) {
                    $rows[$i] .= "<td align='center'>" . $col . '</td>';
                } elseif ($formats[$j] && in_array($formats[$j], array('unsigned', 'int', 'money', 'percent', 'number'))) {
                    $rows[$i] .= "<td align='right' nowrap>" . $col . '</td>';
                } elseif ($formats[$j] && $formats[$j] == 'emails') {
                    $rows[$i] .= "<td style='color:blue'>" . $emls->toVerbal($col) . '</td>';
                } elseif ($formats[$j] && $formats[$j] == 'email') {
                    $rows[$i] .= "<td style='color:blue'>" . $eml->toVerbal($col) . '</td>';
                } else {
                    $rows[$i] .= "<td clsss='mightOverflow'>" . $col . '</td>';
                }
            }
        }

        $html = new ET("<table class='csv'><tr>" . implode("</tr>\n<tr>", $rows) . '</tr></table>');
        
        if (Mode::is('screenMode', 'narrow')) {
            $maxWidth = 600;
        } else {
            $maxWidth = 1600;
        }

        if ($cnt > 0) {
            $maxWidt = round(max(120, $maxWidth / $cnt));
        }

        $html->appendOnce(".csv td {
                        max-width: {$maxWidt}px;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                        font-size:0.9em;
                        padding-left:5px !important;
                        padding-right:5px !important;
                        border:solid 1px #669;
                        }
                        .csv td:hover, .csv td:active {
                            text-overflow: clip;
                            white-space: normal;
                            word-break: break-all;
                            background-color:#ffc;
                        }
                        .csv th {
                            border:solid 1px #669;
                            font-size:0.9em;
                        }

        ", 'STYLES');

   
        return $html;
    }
}
