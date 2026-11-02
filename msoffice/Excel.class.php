<?php

/**
 * Общ адаптер за версиите на SimpleXLSX
 *
 * @category  vendors
 * @package   msoffice
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
class msoffice_Excel extends core_BaseClass {
    
    /** Чете първия лист и запазва досегашното премахване на водещите празни клетки. */
    public static function getRows($file, $fromType = 'fileman', $trim = false)
    {
        $reader = self::getReaderClass();
        
        if($fromType == 'fileman') {
            $path = fileman::extract($file);
        } elseif($fromType == 'path') {
            $path = $file;
        } else {
            expect(in_array($fromType, array('fileman', 'path')), $fromType);
        }

        $xlsx = $reader::parse($path);
        expect($xlsx, 'Грешка при четене на XLSX: ' . $reader::parseError());
        expect($xlsx->worksheet(0) !== false, 'Първият XLSX лист не може да бъде прочетен');
        $rows = $xlsx->rows();
        expect(is_array($rows), 'Първият XLSX лист не може да бъде прочетен');

        // Параметърът $trim е запазен; досегашният адаптер винаги тримва матрицата.
        self::trimMatrix($rows);

        return $rows;
    }


    /** Зарежда само избраната библиотека и връща класа за всички нейни потребители. */
    public static function getReaderClass()
    {
        if (msoffice_Setup::get('SIMPLEXLSX_VERSION') === '1.1.19') {
            expect(!cls::get('msoffice_Setup')->checkConfig(), 'SimpleXLSX 1.1.19 изисква libxml, SimpleXML, zlib и mbstring');
            require_once __DIR__ . '/1.1.19/src/SimpleXLSX.php';

            return 'Shuchkin\\SimpleXLSX';
        }
        require_once getFullPath('msoffice/lib/simplexlsx.php');

        return 'SimpleXLSX';
    }


    /**
     * Премахване на водещи празни колонки и редове
     */
    public static function trimMatrix(&$rows)
    {
        $minJ = false;
        $notContent = true;
        if(is_array($rows)) {
            foreach($rows as $i => $row) {
                if(is_array($row)) {
                    $emptyRow = $notContent;
                    foreach($row as $j => $cell) {
                        if($cell !== '' && $cell !== null) {
                            $emptyRow = false;
                            $notContent = false;
                            if($minJ === false) {
                                $minJ = $j;
                            } else {
                                $minJ = min($j, $minJ);
                            }
                            break;
                        }
                    }
                    if($emptyRow) {
                        unset($rows[$i]);
                    }
                }
            }
            // Тримване на първите колонки
            if($minJ > 0) {
                foreach($rows as $i => $row) {
                    if(is_array($row)) {
                        foreach($row as $j => $cell) {
                            if($j < $minJ) {
                                unset($rows[$i][$j]);
                            } else {
                                break;
                            }
                        }
                    }
                }

            }
        }
    }



}
