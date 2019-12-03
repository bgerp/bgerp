<?php

class msoffice_Excel extends core_BaseClass {
    
    public function getRows($file, $fromType = 'fileman', $trim = false)
    {
        require_once(getFullPath('msoffice/lib/simplexlsx.php'));
        
        if($fromType == 'fileman') {
            $path = fileman::extract($file);
        } elseif($fromType == 'path') {
            $path = $file;
        } else {
            expect(in_array($fromType, array('fileman', 'path')), $fromType);
        }

        if ( $xlsx = SimpleXLSX::parse($path) ) {
            $rows = $xlsx->rows();
        } else {
            echo SimpleXLSX::parseError();
        }

        if($trim || 1) {
            self::trimMatrix($rows);
        }

        return $rows;
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
