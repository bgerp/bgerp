<?php


/**
 * Драйвер за работа с .kml файлове.
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Kml extends fileman_webdrv_Xml
{
    
    
    /**
     * Кой таб да е избран по подразбиране
     * @Override
     * @see fileman_webdrv_Generic::$defaultTab
     */
    static $defaultTab = 'preview';
    
    
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
    static function getTabs($fRec)
    {
        // Вземаме табовете от родителя
        $tabsArr = parent::getTabs($fRec);
        
        // Вземаме съдържанието
        $view = static::renderView($fRec);
        
        // Таб за съдържанието
		$tabsArr['preview'] = (object) 
			array(
				'title'   => 'Изглед',
				'html'    => "<div class='webdrvTabBody' style='white-space:pre-wrap;'><div class='webdrvFieldset'><div class='legend'>" . tr("Преглед") . "</div>{$view}</div></div>",
				'order' => 6,
				'tpl' => $view,
			);
        
        return $tabsArr;
    }
    
    
    /**
     * Преглед на kml файла
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return ET
     */
    public static function renderView($fRec)
    {
        $content = fileman_Files::getContent($fRec->fileHnd);
        
        $content = mb_strcut($content, 0, 20000000);
        
        $content = i18n_Charset::convertToUtf8($content, array('UTF-8' => 2, 'CP1251' => 0.5), TRUE);
        
        $content = trim($content);
        
        $valArr = self::parseKmlString($content);
        
        $attr = self::getPreviewWidthAndHeight();
        $attr['height'] = $attr['width'] / 1.618;
        
        $tpl = location_Paths::renderView($valArr, $attr);
        
        return $tpl;
    }
    
    
    /**
     * Подготвя данните от подадения kml файл
     * 
     * @param string $str
     * 
     * @return array - Масив, който може да се подаде към location_Paths::renderView
     */
    public static function parseKmlString($str)
    {
        $valArr = array();
        
        $xml = @simplexml_load_string($str);
        
        if (!$xml) return $valArr;
        
        $valArr = self::prepareXml($xml);
        
        return $valArr;
    }
    
    
    /**
     * Опитва се да извлече данние от xml обекта и да ги подготви във формата на location_Path
     * 
     * @param SimpleXMLElement $xml
     * @return array
     */
    protected static function prepareXml($xml)
    {
        $placemark = array();
        
        if ($xml->Placemark) {
            $placemark[] = $xml->Placemark;
        }
        
        $have = FALSE;
        if ($xml->Document->Placemark) {
            if (count($xml->Document)) {
                foreach ($xml->Document as $xmlDoc) {
                    if ($xmlDoc->Placemark) {
                        $placemark[] = $xmlDoc->Placemark;
                        $have = TRUE;
                    }
                }
            }
            
            if (!$have) {
                $placemark[] = $xml->Document->Placemark;
            }
        }
        
        
        $have = FALSE;
        if ($xml->Document->Folder) {
            
            if (count($xml->Document->Folder)) {
                foreach ($xml->Document->Folder as $xmlDocFolder) {
                    if ($xmlDocFolder->Placemark) {
                        $placemark[] = $xmlDocFolder->Placemark;
                        $have = TRUE;
                    }
                }
            }
            
            if (!$have) {
                $placemark[] = $xml->Document->Folder->Placemark;
            }
        }
        
        $have = FALSE;
        if ($xml->Document->Folder->Folder) {
            if (count($xml->Document->Folder->Folder)) {
                foreach ($xml->Document->Folder->Folder as $xmlDocFolderFolder) {
                    if ($xmlDocFolderFolder->Placemark) {
                        $placemark[] = $xmlDocFolderFolder->Placemark;
                        $have = TRUE;
                    }
                }
                
                if (!$have) {
                    $placemark[] = $xml->Document->Folder->Folder->Placemark;
                }
            }
        }
        
        // Информация по-подразбиране
        $info = '';
        $info = (string)$xml->Document->Placemark->name;
        if (!$info) {
            $info = (string)$xml->Placemark->name;
        }
        if (!$info) {
            $info = (string)$xml->Document->name;
        }
        
        $coordinates = $infoArr = array();
        
        $hashArr = array();
        foreach ($placemark as $k => $plMaster) {
            
            $hash = md5($plMaster);
            
            if ($hashArr[$hash]) continue;
            
            $hashArr[$hash] = TRUE;
            
            foreach ($plMaster as $pl) {
                if ($pl->Point) {
                    // В този случай се отнасят за един обект
                    $coordinates[] = (string)$pl->Point->coordinates;
                    
                    // Опитваме се да намерим по-точна информация
                    $info2 = '';
                    $info2 = (string)$pl->Point->name;
                    if (!$info2) {
                        $info2 = (string)$pl->name;
                    }
                    $infoArr[] = $info2 ? $info2 : $info;
                } 
                if ($pl->MultiGeometry->LineString) {
                    foreach ((array)$pl->MultiGeometry as $ls) {
                        foreach ((array)$ls as $lc) {
                            $coordinates[] = (string)$lc->coordinates;
                            
                            // Опитваме се да намерим по-точна информация
                            $info2 = '';
                            $info2 = (string)$lc->comment;
                            if (!$info2) {
                                $info2 = (string)$pl->name;
                            }
                            $infoArr[] = $info2 ? $info2 : $info;
                        }
                    }
                } 
                if ($pl->LineString) {
                    $coordinates[] = (string)$pl->LineString->coordinates;
                    
                    // Опитваме се да намерим по-точна информация
                    $info2 = '';
                    $info2 = (string)$pl->LineString->comment;
                    if (!$info2) {
                        $info2 = (string)$pl->LineString->name;
                    }
                    $infoArr[] = $info2 ? $info2 : $info;
                } elseif ($pl->Polygon) {
                    if (!($boundary = $pl->Polygon->outerBoundaryIs)) {
                        $boundary = $pl->Polygon->innerBoundaryIs;
                    }
                    $coordinates[] = (string)$boundary->LinearRing->coordinates;
                    
                    // Опитваме се да намерим по-точна информация
                    $info2 = '';
                    $info2 = (string)$boundary->name;
                    if (!$info2) {
                        $info2 = (string)$pl->Polygon->name;
                    }
                    if (!$info2) {
                        $info2 = (string)$pl->name;
                    }
                    $infoArr[] = $info2 ? $info2 : $info;
                }
            }
        }
        
        $cArr = array();
        
        // Преобразуваме масива с координати и информация във формата на location_Path
        foreach ($coordinates as $i => $c) {
            $c = trim($c);
            $cExplode = explode("\n", $c);
            
            foreach ($cExplode as $cStr) {
                
                if (!$cStr) continue;
                
                $cStrArr = explode(' ', $cStr);
                
                foreach ($cStrArr as $cStr2) {
                    $cStr2Arr = explode(',', $cStr2);
                    
                    if (!isset($cStr2Arr[0]) || !isset($cStr2Arr[1])) continue;
                    
                    $cArr[$i]['coords'][] = array($cStr2Arr[1], $cStr2Arr[0], $cStr2Arr[2]);
                }
                
                $cArr[$i]['info'] = $infoArr[$i];
                
            }
        }
        
        return $cArr;
    }
}
