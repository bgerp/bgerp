<?php


/**
 * Клас 'fileman_Upload' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    fileman
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class fileman_Upload extends core_Manager {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'Files=fileman_Files,fileman_DialogWrapper';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Качвания на файлове';
    
    
    /**
     *  @todo Чака за документация...
     */
    function act_Dialog()
    {
        if(Request::get('Upload')) {
            
            if($_FILES['ulfile']['name'] && $_FILES['ulfile']['tmp_name']) {
                
                // Вземаме параметрите от заявката
                $bucketId = Request::get('bucketId', 'int');
                $callback = Request::get('callback', 'identifier');
                
                if($bucketId) {
                    
                    // Вземаме инфото на обекта, който ще получи файла
                    $Buckets = cls::get('fileman_Buckets');
                    
                    // Ако файла е валиден по размер и разширение - добавяме го към собственика му
                    if( $Buckets->isValid(&$err, $bucketId, $_FILES['ulfile']['name'], $_FILES['ulfile']['tmp_name']) ) {
                        
                        // Създаваме файла
                        $fh = $this->Files->createDraftFile($_FILES['ulfile']['name'], $bucketId);
                        
                        // Записваме му съдържанието
                        $this->Files->setContent($fh, $_FILES['ulfile']['tmp_name']);
                        
                        $add = $Buckets->getInfoAfterAddingFile($fh);
                        
                        if($callback) {
                            $name = $this->Files->fetchByFh($fh, 'name');
                            $add->append("<script>  if(window.opener.{$callback}('{$fh}','{$name}') != true) self.close(); else   self.focus();  </script>");
                        }
                    }
                }
            } elseif($_FILES['ulfile']['error']) {
                // Ако са възникнали грешки при качването - записваме ги в променливата $err
                switch($_FILES['ulfile']['error']) {
                    case 1: $err[] = 'The uploaded file exceeds the upload_max_filesize directive in php.ini'; break;
                    case 2: $err[] = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'; break;
                    case 3: $err[] = 'The uploaded file was only partially uploaded.'; break;
                    case 4: $err[] = 'No file was uploaded.'; break;
                    case 6: $err[] = 'Missing a temporary folder.'; break;
                    case 7: $err[] = 'Failed to write file to disk.'; break;
                }
            }
            
            // Ако има грешки, показваме ги в прозореца за качване
            if(count($err)) {
                $add = new ET("<div style='border:dotted 1px red; background-color:#ffc;'><ul>[#ERR#]</ul></div>");
                
                foreach($err as $e) {
                    $add->append("<li>" . tr($e), 'ERR');
                }
            }
        }
        
        if(Mode::is('screenMode', 'narrow')) {
            $tpl = $this->getNormalTpl();
        } else {
            $tpl = $this->getProgressTpl();
        }
        
        $tpl->prepend($add);
        
        return $this->renderDialog($tpl);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function renderDialog_($tpl)
    {
        return $tpl;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function act_UploadProgress()
    {
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        
        if(function_exists('uploadprogress_get_info')) {
            if (@$_GET['id']) {
                echo json_encode(uploadprogress_get_info($_GET['id']));
            }
        }
        
        exit();
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function act_uploadFile()
    {
        //echo file_exists($_FILES['ulfile']['tmp_name']); 
        copy($_FILES['ulfile']['tmp_name'], 'C:\\xampp\\tmp\\' . $_FILES['ulfile']['name']);
        
        $this->Log( serialize($_FILES) . " post");
        
        //bp($_GET, $_FILES);
    }
    
    
    /**
     * Шаблон за формата, без прогрес бар
     */
    function getNormalTpl()
    {
        $tpl = new ET('
            <form id="uploadform" enctype="multipart/form-data" method="post">
             <input id="ulfile" name="ulfile" type="file" style="display:block; margin-top:10px;" />  
            <input type="submit" name="Upload" value="' . tr('Качване') . '" style="display:block; margin-top:10px;"/>
             <input name="Protected" type="hidden" value="[#Protected#]" />

            </form>' );
        
        $tpl->replace(Request::get('Protected'), 'Protected');
        
        return $tpl;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getProgressTpl()
    {
        $tpl = new ET('
            <style type="text/css">
                    .ui-progressbar-value {background-image: url(' . sbf('jquery/ui-1.8.2/css/custom-theme/images/pbar-ani.gif', '') . '); }
            </style>

            <form id="uploadform" enctype="multipart/form-data" method="post" style="width:100%;"  onsubmit="if(document.getElementById(\'ulfile\').value) { toggleDisplay(\'inputDiv\'); toggleDisplay(\'filename\'); document.getElementById(\'filename\').innerHTML = document.getElementById(\'ulfile\').value; beginUpload();  return true;} return false;">
            <input id="progress_key" name="UPLOAD_IDENTIFIER" type="hidden" value="[#ufid#]" />
            <div id="inputDiv">
                <input id="ulfile" name="ulfile" type="file" style="display:block; margin-top:10px;"> 
                <input type="submit" name="Upload" value="' . tr('Качване') . '" style="display:block; margin-top:10px;" />
            </div>
            <div id="filename" style="display:none;"></div>
            <div id="uploadprogressbar" class="progressbar" style="display:none;width:100%;height:12px;"></div>
            <input name="Protected" type="hidden" value="[#Protected#]" />

            </form>' );
        
        $ufid = str::getRand();
        
        $tpl->replace($ufid, 'ufid');
        
        $tpl->replace(Request::get('Protected'), 'Protected');
        
        $JQ = cls::get('jquery_Jquery');
        
        $JQ->enableUI($tpl);
        
        $url = toUrl(array($this, 'UploadProgress'));
        
        $tpl->appendOnce("
             
            // this sets up the progress bar
            $(document).ready(function() {
                $('#uploadprogressbar').progressbar();
            });
             
            // fades in the progress bar and starts polling the upload progress after 1.5seconds
            function beginUpload() { 
                $('#uploadprogressbar').fadeIn();
                document.getElementById('progress_key').value = document.getElementById('progress_key').value + makeid();
                setTimeout('showUpload(0)', 2000);
            }

            function makeid()
            {
                    var text = '';
                    var possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

                    for( var i=0; i < 3; i++ )
                            text += possible.charAt(Math.floor(Math.random() * possible.length));

                    return text;
            }
             
            // uses ajax to poll the uploadprogress.php page with the id
            // deserializes the json string, and computes the percentage (integer)
            // update the jQuery progress bar
            // sets a timer for the next poll in 750ms
            function showUpload(i) { 
                
                     var progress_key = document.getElementById('progress_key').value;
                 
                    $.get('{$url}?id=' + progress_key, function(data) {  
                    
                        if (!data)
                            return;

                        var response;
                        eval ('response = ' + data);
                 
                        if (!response)
                            return;
                                    
                        var percentage = Math.floor(100 * parseInt(response['bytes_uploaded']) / parseInt(response['bytes_total']));
                        
                        $('#uploadprogressbar').progressbar({ 'value' : percentage });
                 
                    });

                setTimeout('showUpload(' + i + ')', 2500);
            }

        ", 'SCRIPTS');
        
        return $tpl;
    }
}