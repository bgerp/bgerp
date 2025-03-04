<?php


/**
 * Екшън за споделяне на файлове чрез PWA
 * 
 * @package   pwa
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pwa_Share extends core_Mvc
{
    
    
    /**
     * Екшън за качване на файловете
     */
    public function act_Target()
    {
        expect(core_Packs::isInstalled('pwa'));
        
        $tpl = new ET('<div class="loader"></div><input type="file" name="ulfile[]" multiple style="display:none"><input type="text" name="link" style="display:none">');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $bucketId = fileman_Buckets::fetchByName('pwa');
            expect($bucketId);
            
            $res = new ET();
            
            $fhArr = array();
            if ($_FILES['ulfile']) {
                $fhArr = fileman_Upload::makeUpload(array('ulfile' => $_FILES['ulfile']), $bucketId, $res);
            }
            
            if ($link = Request::get('link')) {
                $lRec = new stdClass();
                $lRec->url = $link;
                $lRec->bucketId = $bucketId;
                $res = $err = null;
                
                $fh = fileman_Get::getFile($lRec, $res, $err);
                if ($fh) {
                    $fhArr[$fh] = $fh;
                }
                
                if ($err) {
                    status_Messages::newStatus('|*<div>|Грешка|*:</div>' . implode(', ', $err), 'warning');
                }
            }
            
            $fhArrCnt = countR($fhArr);
            if ($fhArrCnt) {
                $fStr = $fhArrCnt == 1 ? 'Файл' : 'Файлове';
                status_Messages::newStatus("|*<div>|{$fStr}|*:</div>" . $res->getContent());
            }
            
            if (core_Users::getCurrent() > 0) {
                foreach ($fhArr as $fh) {
                    fileman_Log::updateLogInfo($fh, 'upload');
                }
            }
            
            if ($_FILES['ulfile'] || $link) {
                
                return new Redirect(array('pwa_Share', 'SaveTargetFiles', 'fArr' => json_encode($fhArr)));
            }

            // Ако не се подава файл, или е линк или е текст
            if ($desc = Request::get('description')) {
                // Ако е линк и е html, опитваме се да вземем съдъжданието му
                if (core_Url::isValidUrl($desc) || core_Url::isValidUrl2($desc)) {
                    $headers = get_headers($desc, 1);
                    if (isset($headers['Content-Type']) && strpos($headers['Content-Type'], 'text/html') !== false) {
                        $urlContent = @file_get_contents($desc);
                        if ($urlContent) {
                            $urlContent = html2text_Converter::toRichText($urlContent);
                        }
                        if ($urlContent) {
                            $desc = $urlContent;
                        }
                    }
                }

                $name = Request::get('name');
                $name = $name ? $name : tr('Споделен текст');

                $key = md5(str::getRand() . $name . $desc);

                core_Cache::set('pwa_Share', $key, array('body' => $desc, 'subject' => $name));

                return new Redirect(array('pwa_Share', 'SaveTargetFiles', 'key' => $key));
            }
        }
         
        $script = "  navigator.serviceWorker.onmessage = (event) => {
                        window.location.href = event.data;
                    };";
        $tpl->append($script, 'SCRIPTS');
        
        $css = " .loader {
                      border: 16px solid #f3f3f3; /* Light grey */
                      border-top: 16px solid #3498db; /* Blue */
                      border-radius: 50%;
                      width: 120px;
                      height: 120px;
                      animation: spin 2s linear infinite;
                      margin: 100px auto;
                    }
                    
                    @keyframes spin {
                      0% { transform: rotate(0deg); }
                      100% { transform: rotate(360deg); }
                    }";
        $tpl->append($css, 'STYLES');
        
        return $tpl;
    }
    
    
    /**
     * Екшън,който добавя файловете в последни
     */
    public function act_SaveTargetFiles()
    {
        $fArr = Request::get('fArr');
        $key = Request::get('key');

        if (!haveRole('user')) {
            
            return new Redirect(array('core_Users', 'login', 'ret_url' => array('pwa_Share', 'SaveTargetFiles', 'fArr' => $fArr, 'key' => $key, 'force' => true)));
        }
        
        if (Request::get('force')) {
            $fArr = json_decode($fArr);

            if ($fArr) {
                foreach ($fArr as $fh) {
                    fileman_Log::updateLogInfo($fh, 'upload');
                }
            }
        }

        if (!$fArr) {
            $defFolder = doc_Folders::getDefaultFolder(core_Users::getCurrent());

            return new Redirect(array('doc_Notes', 'add', 'folderId' => $defFolder, 'key' => $key));
        }
        
        if (haveRole('powerUser')) {
            
            return new Redirect(array('doc_Files'));
        } else {
            
            return new Redirect(array('Index'));
        }
    }
    
    
    /**
     * Помощен екшън за редирект към портала
     * 
     * @return Redirect
     */
    function act_Portal()
    {
        $v = Request::get('v');
        if ($v) {
            $v = str::checkHash($v);
        }
        
        if (!$v) {
            wp(Request::get('v'));
        }
        
        Mode::setPermanent('isPWA', true);
        
        return new Redirect(array('Portal', 'Show'));
    }
}
