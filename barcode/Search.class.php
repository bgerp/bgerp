<?php


/**
 * Клас 'barcode_Search' - Търсене на баркод в системата
 *
 *
 * @category bgerp
 * @package barcode
 *         
 * @author Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
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
    public $canList = 'user';


    /**
     * Действие по подразбиране
     */
    public function act_Default()
    {
        $useHtml5Camera = TRUE;
        $this->requireRightFor('list');
        
        $form = cls::get('core_Form');
        $isColab = core_Packs::isInstalled('colab') && core_Users::isContractor();
        $this->currentTab = 'Търсене';

        
        $form->title = 'Търсене по баркод';
        $form->FNC('search', 'varchar', 'caption=Баркод...,silent,input,recently,elementId=barcodeSearch');
        $form->name = 'barcode_search';
        $form->show = 'search';
        
        $form->input(null, true);
        $form->view = 'horizontal';
        
        $form->toolbar->addSbBtn('Търсене', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        if (!$useHtml5Camera) {
            $form->toolbar->addBtn('Сканирай', $this->getScannerActivateUrl(), 'id=scanBtn', 'ef_icon = img/16/barcode-icon.png, title=Сканиране на баркод');
        } else {
            $form->toolbar->addFnBtn('Сканирай', "openCamera();", 'id=scanBtn', "ef_icon = img/16/barcode-icon.png, title=Сканиране на баркод, class=hiddenBtn");
        }
        
        $form->formAttr['id'] = 'barcodeForm';
        
        $haveRes = null;
        
        if ($form->rec->search) {
            // Ако е сканиран баркод към линк към системата
            if (core_Url::isValidUrl2($form->rec->search)) {
                $cDomain = cms_Domains::getCurrent('domain', false);
                $dOpt = cms_Domains::getDomainOptions(true);

                if (core_Url::isLocal($form->rec->search)) {

                    return new Redirect($form->rec->search);
                }

                foreach ($dOpt as $dName) {
                    if (strtolower($dName) == 'localhost') {

                        continue;
                    }

                    if (strpos($form->rec->search, '://' . $dName) || strpos($form->rec->search, $dName)  === 0) {
                        if ($cDomain && ($cDomain != $dName)) {
                            $form->rec->search = preg_replace('/' . preg_quote($dName, '/') . '/', $cDomain, $form->rec->search, 1);
                        }

                        return new Redirect($form->rec->search);
                    }
                }
            }

            $isValidEan = cls::get('gs1_TypeEan')->isValid($form->rec->search);
            if (empty($isValidEan)) {
                $pRec = cat_Products::getByCode($form->rec->search);
                if ($pRec && $pRec->productId) {

                    if (cat_Products::haveRightFor('single', $pRec->productId)) {

                        return new Redirect(array('cat_Products', 'single', $pRec->productId));
                    }
                } else {
                    // Добавяме бутона
                    $form->toolbar->addBtn('Google',
                        '//google.com/search?q=' . $form->rec->search,
                        "id='btn-google', ef_icon=gdocs/img/google.png",
                        array('target' => '_blank', 'order' => '30', 'title' => 'Търсене в Google')
                    );
                }
            }

            $haveRes = false;
            
            $intfArr = core_Classes::getOptionsByInterface('barcode_SearchIntf');
            
            $tableTpl = new ET("<div class='barcodeSearchHolder' style='margin-top: 20px;'><table class='listTable barcodeSearch'>");
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

        $tpl = $form->renderHtml();

        if($useHtml5Camera) {
            $tpl->push('barcode/js/html5.js', 'JS');
            $tpl->push('barcode/js/html5-qrcode.min.js', 'JS');

            $h =  $form->rec->search ? ' class="hidden" ' : '';
            $img = sbf("img/32/camera.png", "");

            $a = "<style> .cameraSource { min-width: 40px;padding-left: 24px !important; padding-right: 7px !important; background: #ddd url({$img}) left center; background-size: 16px; } .narrow .cameraSource { min-width: 50px;padding-left: 25px !important;}.cameraSource.active {background-color: #bbb;}</style> 
            <div id='cameraHolder' {$h}>
                <div style='margin: 0 0 10px;' id='camera-buttons'></div>
                <div style= 'max-width: 500px; width: 100%' id='reader'></div>
            </div>";
            jquery_Jquery::run($tpl, "barcodeActions();");

        } else {
            $tpl->appendOnce('<script type="text/javascript" src="https://unpkg.com/@zxing/library@0.20.0/umd/index.min.js"></script>', 'HEAD');
            $tpl->push('barcode/js/scan.js', 'JS');


            $a = ' <style> .cameraSource.active {background-color: #bbb;}</style>

            <div id="scanTools" style="display:none">
                <div class="scanTools" style="display: none">
                    <a class="button" id="startButton">Start</a>
                    <a class="button" id="resetButton">Reset</a>
                </div>
                <div id="sourceSelectPanel" style="display:none; margin-bottom: 20px;">
                </div>
                <div id="camera" style="display: none">
                    <video id="video" style="border: 1px solid gray; width: 100%; height:100%; max-width: 600px; max-height: 600px;"></video>
                </div>
             </div>';
        }

        $tpl->append($a);

        if ($haveRes === false) {
            $tpl->append(tr('Няма открити съвпадения'));
        } else {
            $tpl->append($tableTpl);
        }

        if($isColab){
            plg_ProtoWrapper::changeWrapper($this, 'cms_ExternalWrapper');
        }

        // Фокусира елемента, ако се напише нещо и предава текста, ако не е фоксиран друг
//        $tpl->appendOnce("document.addEventListener('keydown', (event) => {
//  // Проверяваме дали потребителят вече не пише в някое поле
//  const activeElement = document.activeElement;
//  const isInputActive = activeElement.tagName === 'INPUT' ||
//                        activeElement.tagName === 'TEXTAREA' ||
//                        activeElement.isContentEditable;
//
//  if (!isInputActive) {
//    // Търсим първия textarea, а ако няма - първия input
//    const targetElement = document.querySelector('textarea') || document.querySelector('input[type=\"text\"]');
//
//    if (targetElement) {
//      targetElement.focus();
//      // Опционално: добавяме символа, който е натиснат първоначално
//      // (ако не искаме да се губи първата буква)
//    }
//  }
//});", 'SCRIPTS');

        // Пише в елемента, без да фокусира, ако се пише
        $tpl->appendOnce("document.addEventListener('keydown', (event) => {
                          const activeElement = document.activeElement;
                          const isInputActive = activeElement.tagName === 'INPUT' || 
                                                activeElement.tagName === 'TEXTAREA' || 
                                                activeElement.isContentEditable;
                        
                          if (!isInputActive) {
                            const target = document.querySelector('textarea') || document.querySelector('input[type=\"text\"]');
                        
                            if (target) {
                              if (event.key === 'Enter') {
                                if (target.tagName === 'TEXTAREA') {
                                  // В textarea Enter добавя нов ред
                                  target.value += '\\n';
                                } else if (target.tagName === 'INPUT') {
                                  // В input Enter прави submit на формата (ако има такава)
                                  if (target.form) {
                                    target.form.submit();
                                  }
                                }
                              } else if (event.key.length === 1 && !event.ctrlKey && !event.metaKey) {
                                // Добавяне на нормални символи
                                target.value += event.key;
                                
                                // Предотвратяваме скролване при интервал
                                if (event.key === ' ') {
                                  event.preventDefault();
                                }
                              }
                            }
                          }
                        });", 'SCRIPTS');

        return $this->renderWrapping($tpl);
    }


    /**
     * Връща URL, което пуска програмата за сканиране на баркод и връща управлението след това
     *
     * @param null|string $retUrl
     *
     * @return string
     */
    public static function getScannerActivateUrl($retUrl = null)
    {
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
    public function act_List()
    {
        $this->requireRightFor('list');
        $search = Request::get('search');
        
        $retUrl = array ($this, 'search' => $search);
        $userAgent = log_Browsers::getUserAgentOsName();
        
        if (! trim($search) && ($userAgent == 'Android')) {
            // $retUrl = $this->getScannerActivateUrl();
        }
        
        return new Redirect($retUrl);
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'list'){
            if(!core_Packs::isInstalled('colab')){
                if(!haveRole('powerUser', $userId)){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
}
