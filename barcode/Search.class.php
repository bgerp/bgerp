<?php


/**
 * Клас 'barcode_Search' - Търсене на баркод в системата
 *
 *
 * @category bgerp
 * @package barcode
 *         
 * @author Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license GPL 3
 *         
 * @since v 0.1
 */
class barcode_Search extends core_Manager
{


    /**
     * Заглавие
     */
    public $title = 'Търсене по баркод';


    /**
     * Зареждане на плъгини
     */
    public $loadList = 'doc_Wrapper, recently_Plugin';


    /**
     * Кой може да добавя
     */
    public $canAdd = 'no_one';


    /**
     * Кой има достъп до списъчния изглед
     */
    public $canList = 'powerUser';


    /**
     * Действие по подразбиране
     */
    public function act_Default() {
        $this->requireRightFor('list');
        
        $form = cls::get('core_Form');
        
        $this->currentTab = 'Търсене';
        
        $form->title = 'Търсене по баркод';
        
        $form->FNC('search', 'varchar', 'caption=Баркод...,silent,input,recently,elementId=barcodeSearch');
        
        $form->name = 'barcode_search';
        
        $form->show = 'search';
        
        $form->input(null, true);
        
        $form->view = 'horizontal';
        
        $form->toolbar->addSbBtn('Търсене', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $form->toolbar->addBtn('Сканирай', $this->getScannerActivateUrl(), 'id=scanBtn', 'ef_icon = img/16/barcode-icon.png, title=Сканиране на баркод');
        
        $form->formAttr['id'] = 'barcodeForm';
        
        $tpl = $form->renderHtml();
        
        $haveRes = null;
        
        if ($form->rec->search) {
            // Ако е сканиран баркод към линк към системата
            if (core_Url::isValidUrl2($form->rec->search)) {
                if (strpos($form->rec->search, '://' . $_SERVER['HTTP_HOST']) || strpos($form->rec->search, $_SERVER['HTTP_HOST'] === 0)) {
                    
                    return new Redirect($form->rec->search);
                }
            }
            
            $haveRes = false;
            
            $intfArr = core_Classes::getOptionsByInterface('barcode_SearchIntf');
            
            $tableTpl = new ET("<div class='barcodeSearchHolder'><table class='listTable barcodeSearch'>");
            $resArr = array ();
            
            foreach ($intfArr as $intfClsId => $intfCls) {
                if (! cls::load($intfClsId, true)) {
                    continue;
                }
                
                $clsInst = cls::get($intfClsId);
                
                $Intf = cls::getInterface('barcode_SearchIntf', $clsInst);
                
                $resArr = array_merge($resArr, $Intf->searchByCode($form->rec->search));
            }
            
            if (! empty($resArr)) {
                core_Array::sortObjects($resArr, 'priority', 'desc');
                $haveRes = true;
            }
            
            foreach ($resArr as $r) {
                $resTpl = new ET('<tr><td>[#title#]</td><td>[#comment#]</td></tr>');
                
                if (! $r->title) {
                    $r->title = tr('Липсва заглавие');
                }
                
                if ($r->url) {
                    $r->title = ht::createLink($r->title, $r->url);
                }
                $resTpl->placeObject($r);
                $resTpl->removeBlocksAndPlaces();
                $tableTpl->append($resTpl);
            }
            $tableTpl->append('</table></div>');
        }
        
        $tpl->appendOnce('<script type="text/javascript" src="https://unpkg.com/@zxing/library@latest"></script>', 'HEAD');
        $tpl->push('barcode/js/scan.js', 'JS');
        $a = '<div id="scanTools" style="display:none">
                <div class="scanTools" style="display: none">
                    <a class="button" id="startButton">Start</a>
                    <a class="button" id="resetButton">Reset</a>
                </div>
                <div id="sourceSelectPanel" style="display:none">
                    <select id="sourceSelect"></select>
                </div>
                <div id="camera" style="display: none">
                    <video id="video" width="300" height="200" style="border: 1px solid gray"></video>
                </div>
             </div>';
        $tpl->append($a);
        
        if ($haveRes === false) {
            $tpl->append(tr('Няма открити съвпадания в базата'));
        } else {
            $tpl->append($tableTpl);
        }
        
        return $this->renderWrapping($tpl);
    }


    /**
     * Връща URL, което пуска програмата за сканиране на баркод и връща управлението след това
     *
     * @param null|string $retUrl
     *
     * @return string
     */
    public static function getScannerActivateUrl($retUrl = null) {
        if (! $retUrl) {
            $retUrl = toUrl(array (
                    'barcode_Search',
                    'search' => '__CODE__' 
            ), true);
        }
        
        $retUrl = str_replace('__CODE__', '{CODE}', $retUrl);
        
        $retUrl = urlencode($retUrl);
        
        $scanUrl = 'https://zxing.appspot.com/scan?ret=' . $retUrl;
        
        return $scanUrl;
    }


    /**
     * Действие по подразбиране
     */
    public function act_List() {
        $this->requireRightFor('list');
        
        $search = Request::get('search');
        
        $retUrl = array (
                $this,
                'search' => $search 
        );
        
        $userAgent = log_Browsers::getUserAgentOsName();
        
        if (! trim($search) && ($userAgent == 'Android')) {
            // $retUrl = $this->getScannerActivateUrl();
        }
        
        return new Redirect($retUrl);
    }
}
