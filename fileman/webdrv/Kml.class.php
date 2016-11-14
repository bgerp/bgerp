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
        
        $content = mb_strcut($content, 0, 1000000);
        
        $content = i18n_Charset::convertToUtf8($content);
        
        $valArr = self::parseKmlString($content);
        
        $attr = self::getPreviewWidthAndHeight();
        $attr['height'] = $attr['width'] / 1.618;
        
        $tpl = location_Paths::renderView($valArr, $attr);
        
        return $tpl;
    }
    
    
    /**
     * 
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
        
        $cArr = array();
        
        if ($coordsArr = $xml->Placemark->Point->coordinates) {
            
            // Това е когато има само една точка
            
            foreach ((array)$coordsArr as $lineCoord) {
                $cArr[] = $lineCoord;
            }
            $cArr = self::prepareCoordsArr($cArr);
            $valArr = array(array('coords' => $cArr, 'info' => trim((string)$xml->Placemark->name)));
        } else {
            if ($xml->Document->Folder) {
                
                // Това е когато има няколко точки от един обект
                
                $placemarks = $xml->Document->Folder->Placemark;
                foreach ($placemarks as $pl) {
                    foreach ((array)$pl->Point->coordinates as $lineCoord) {
                        $cArr[] = (string)$lineCoord;
                    }
                }
                
                $cArr = self::prepareCoordsArr($cArr);
                $valArr = array(array('coords' => $cArr, 'info' => trim((string)$xml->Document->Folder->Placemark->name)));
            } else {
                
                // Това е в случаите, когато имаме няколко различни координати за няколко различни устройства
                
                $placemarks = $xml->Document->Placemark;
                
                foreach ($placemarks as $pl) {
                    $info = trim((string)$pl->name);
                    foreach ((array)$pl->MultiGeometry as $lineCoord) {
                        foreach ((array)$lineCoord as $lc) {
                            $coordStr = (string)$lc->coordinates;
                            
                            $cExplodArr = explode("\n", $coordStr);
                            
                            $cArr = array();
                            foreach ($cExplodArr as $v) {
                                if (!trim($v)) continue;
                                $cArr[] = $v;
                            }
                            $cArr = self::prepareCoordsArr($cArr);
                            
                            $valArr[] = array('coords' => $cArr, 'info' => $info);
                        }
                    }
                }
            }
        }
        
        return $valArr;
    }
    
    
    /**
     * Помощна функция за подготвяне на координатите.
     * Получава масив от стрингове, преобразува ги в масив и ако е необходимо, ги обръща
     * 
     * @param array $cArr
     * @param boolean $reverse
     * 
     * @return array
     */
    protected static function prepareCoordsArr($cArr, $reverse = TRUE)
    {
        foreach ($cArr as &$c) {
            $eArr = explode(',', $c);
            if ($reverse) {
                $c = array($eArr[1], $eArr[0]);
            } else {
                $c = array($eArr[0], $eArr[1]);
            }
        }
        
        return $cArr;
    }
}
