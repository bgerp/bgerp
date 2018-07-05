<?php

/**
 * Клас 'plg_ExportCsv' - Дава възможност за експорт към CSV на избрани полета от модела, които имат атрибут'export=Csv'
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_ExportCsv extends core_Plugin
{
    
    
    /**
     * Извиква се преди подготовката на колоните
     */
    public static function on_BeforePrepareListFields($mvc, &$res, $data)
    {
        /* Ако в url-то на заявката има Export=Csv */
        if (Request::get('Export') == 'csv') {
            $mvc->requireRightFor('export');
            
            // Масива с избраните полета за export
            $exportFields = $mvc->selectFields('#export');
            
            // Ако има избрани полета за export
            if (count($exportFields)) {
                foreach ($exportFields as $name => $field) {
                    $data->listFields[$name] = tr($field->caption);
                }
            }
            
            return false;
        }
        
        /* END Ако в url-то на заявката има Export=Csv */
    }
    
    
    /**
     * Добавяме бутон за експорт в Csv
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        // Ако има избрани полета за export
        if (count($mvc->selectFields('#export'))) {
            $url = getCurrentUrl();
            $url['Export'] = 'csv';
            
            $data->toolbar->addBtn('Експорт в CSV', $url, null, 'ef_icon = img/16/file_extension_xls.png, title = Сваляне на записите в CSV формат,row=2');
        }
    }
    
    
    /**
     * Игнорираме pager-а
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_BeforePrepareListPager($mvc, &$res, $data)
    {
        if (Request::get('Export') == 'csv') {
            $mvc->requireRightFor('export');
            
            return false;
        }
    }
    
    
    /**
     * Ако имаме в url-то Export=csv създаваме csv файл с данните
     *
     * @param core_Mvc   $mvc
     * @param core_Table $table
     * @param stdClass   $data
     */
    public static function on_BeforeRenderListTable($mvc, &$table, $data)
    {
        /* Ако в url-то на заявката има Export=Csv */
        if (Request::get('Export') == 'csv') {
            $mvc->requireRightFor('export');

            $conf = core_Packs::getConfig('core');
            
            if (count($data->recs) > $conf->EF_MAX_EXPORT_CNT) {
                redirect(array($mvc), false, '|Броят на заявените записи за експорт надвишава максимално разрешения|* - ' . $conf->EF_MAX_EXPORT_CNT, 'error');
            }
            
            /* за всеки ред */
            if (count($data->recs)) {
                $mvc->invoke('BeforeExportCsv', array($data->recs));
                foreach ($data->recs as $rec) {
                     
                    // Всеки нов ред ва началото е празен
                    $rCsv = '';
                
                    /* за всяка колона */
                    foreach ($data->listFields as $field => $caption) {
                        $type = $mvc->fields[$field]->type;
                
                        if (($type instanceof type_Key) || ($type instanceof type_Key2)) {
                            $value = $mvc->getVerbal($rec, $field);
                        } else {
                            $value = $rec->{$field};
                        }
                
                        // escape
                        if (preg_match('/\\r|\\n|\,|"/', $value)) {
                            $value = '"' . str_replace('"', '""', $value) . '"';
                        }
                
                        $rCsv .= ($rCsv ?  ',' : ' ') . $value;
                    }
                
                    /* END за всяка колона */
                
                    $csv .= $rCsv . "\n";
                }
            }
            
            /* END за всеки ред */
            
            /* Prepare CSV file */
            $fileName = str_replace(' ', '_', Str::utf2ascii($mvc->title));
            
            header('Content-type: application/csv');
            header("Content-Disposition: attachment; filename={$fileName}.csv");
            header('Pragma: no-cache');
            header('Expires: 0');
            
            echo $csv;
            
            /* END Prepare CSV file */
            
            exit;
        }
        
        /* END Ако в url-то на заявката има Export=Csv */
    }
    
    
    /**
     * След подготовка на филтъра
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // Ако експортираме CSV викаме събитие, с което мениджъра може да допълни филтер-заявката
        if (Request::get('Export') == 'csv') {
            $mvc->invoke('AfterPrepareExportQuery', array($data->query));
        }
    }
}
