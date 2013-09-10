<?php



/**
 * Клас 'fileman_Upload' -
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class fileman_Upload extends core_Manager {
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'Files=fileman_Files,fileman_DialogWrapper';
    
    
    /**
     * Заглавие
     */
    var $title = 'Качвания на файлове';
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Dialog()
    {
        // Дали ще качаваме много файлове едновременно
        $allowMultiUpload = FALSE;
        
        // Вземаме callBack'а
        if ($callback = Request::get('callback', 'identifier')) {
            
            // Ако файловете ще се добавят в richText
            if (stripos($callback, 'placeFile_') !== FALSE) {
                
                // Позволяваме множествено добавяне
                $allowMultiUpload = TRUE;
            } 
        }
        
        // Шаблона с качените файлове и грешките
        $add = new ET();
        
        // Ако е стартрино качването
        if(Request::get('Upload')) {
            
            // Вземаме параметрите от заявката
            $bucketId = Request::get('bucketId', 'int');
            
            // Обхождаме качените файлове
            foreach ((array)$_FILES as $inputName => $inputArr) {
                
                // Масив с грешките
                $err = array();
                
                // Ако файла е качен успешно
                if($_FILES[$inputName]['name'] && $_FILES[$inputName]['tmp_name']) {
                    
                    // Ако има кофа
                    if($bucketId) {
                        
                        // Вземаме инфото на обекта, който ще получи файла
                        $Buckets = cls::get('fileman_Buckets');
                        
                        // Ако файла е валиден по размер и разширение - добавяме го към собственика му
                        if($Buckets->isValid($err, $bucketId, $_FILES[$inputName]['name'], $_FILES[$inputName]['tmp_name'])) {
                            
                            // Създаваме файла
                            $fh = $this->Files->createDraftFile($_FILES[$inputName]['name'], $bucketId);
                            
                            // Записваме му съдържанието
                            $this->Files->setContent($fh, $_FILES[$inputName]['tmp_name']);
                            
                            $add->append($Buckets->getInfoAfterAddingFile($fh));
                            
                            if($callback && !$_FILES[$inputName]['error']) {
                                $name = $this->Files->fetchByFh($fh, 'name');
                                $add->append("<script>  if(window.opener.{$callback}('{$fh}','{$name}') != true) self.close(); else self.focus();</script>");
                            }
                        }
                    } else {
                        $err[] = 'Не е избрана кофа';
                    }
                }
                
                // Ако има грешка в $_FILES за съответния файл
                if($_FILES[$inputName]['error']) {
                    // Ако са възникнали грешки при качването - записваме ги в променливата $err
                    switch($_FILES[$inputName]['error']) {
                        case 1 : $err[] = 'The uploaded file exceeds the upload_max_filesize directive in php.ini'; break;
                        case 2 : $err[] = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'; break;
                        case 3 : $err[] = 'The uploaded file was only partially uploaded.'; break;
                        case 4 : $err[] = 'No file was uploaded.'; break;
                        case 6 : $err[] = 'Missing a temporary folder.'; break;
                        case 7 : $err[] = 'Failed to write file to disk.'; break;
                    }
                }
                
                // Ако има грешки, показваме ги в прозореца за качване
                if(count($err)) {
                    $error = new ET("<div class='upload-еrror'><ul>{$_FILES[$inputName]['name']}[#ERR#]</ul></div>");
                    
                    foreach($err as $e) {
                        $error->append("<li>" . tr($e) . "</li>", 'ERR');
                    }
                    $add->append($error);
                }
            }
        }
        
        $tpl = $this->getProgressTpl($allowMultiUpload);
        
        $tpl->prepend($add);
        
        return $this->renderDialog($tpl);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function renderDialog_($tpl)
    {
        return $tpl;
    }
    
    
    /**
     * Връща информация до къде е стигнал uploada на този файл
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
     * Шаблон за формата, без прогрес бар
     * 
     * @deprecated
     */
    function getNormalTpl()
    {
        $tpl = new ET('
            <form id="uploadform" enctype="multipart/form-data" method="post">
                <input id="ulfile" name="ulfile" type="file" style="display:block; margin-top:10px;"  [#ACCEPT#]/>
                <input id="ulfile1" name="ulfile1" type="file" style="display:block; margin-top:10px;"  [#ACCEPT#]/>  
                <input type="submit" name="Upload" value="' . tr('Качване') . '" style="display:block; margin-top:10px;" class="noicon"/>
                <input name="Protected" type="hidden" value="[#Protected#]" />
            </form>');
        
        $tpl->replace(Request::get('Protected'), 'Protected');
        
        return $tpl;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getProgressTpl($allowMultiUpload=FALSE)
    {
        $tpl = new ET('
            <style>
        		.btn-ulfile{background-image:url(' . sbf('img/16/paper_clip.png', '') . ');}
        		.ui-progressbar-value {background-image: url(' . sbf('jquery/ui-1.8.2/css/custom-theme/images/pbar-ani.gif', '') . '); }
        	</style>
            <form id="uploadform" enctype="multipart/form-data" method="post">
            
                <span class="uploaded-filenames"> </span>

                <input id="progress_key" name="UPLOAD_IDENTIFIER" type="hidden" value="[#ufid#]" />
                <div id="inputDiv">
                
                    <input id="ulfile" class="ulfile" name="ulfile" type="file" onchange="afterSelectFile(this, ' . (int)$allowMultiUpload . ');" [#ACCEPT#]>
                    <button id="btn-ulfile" class="linkWithIcon button btn-ulfile">' . tr('Файл') . '</button>
                    
                    <input type="submit" name="Upload" value="' . tr('Качване') . '" class="linkWithIcon button" id="uploadBtn" disabled="disabled"/>

                </div>
                
                <div id="filename"></div>
                
                <div id="uploadprogressbar" class="progressbar"></div>
                
                <input name="Protected" type="hidden" value="[#Protected#]" />
            </form>');
        
        $ufid = str::getRand();
        
        $tpl->replace($ufid, 'ufid');
        
        $tpl->replace(Request::get('Protected'), 'Protected');
        
        $JQ = cls::get('jquery_Jquery');
        
        $JQ->enableUI($tpl);
        
        $url = toUrl(array($this, 'UploadProgress'));
        
        $tpl->appendOnce("
             
            // След като се зареди
            $(document).ready(function() {
            	
            	// С новия начин (да се натиска скрития input) не би трябвало да се стига до тук
            	// Прихващаме натискането на бутона за избор на файл
            	$('.btn-ulfile').parent().on('click', '.btn-ulfile', function(e){
                	
            		// Спираме действието по подразбиране
                    e.preventDefault();
                    
                    // Вземаме id' то на файла
                    btnId = $(this).attr('id');
                    
                    // Разделяме името
                    var splited = btnId.split('-');
                    
                    // Определяме името на input
                    var inputId = '#' +(splited[1]);
                    
                    // Емулираме натискане на input
                    $(inputId).click();
				});
            	
				// Показва прогресбара
                $('#uploadprogressbar').progressbar();
                
                // След субмитване на формата
                $('#uploadform').submit(function() {
               		
                	// Масив с имената на файловете
               		var fileNameArr = new Array();
               		
               		// Броя
               		var elementId = 0;
               		
               		// Обхождаме всички инпути от зададения клас
               		$('.ulfile').each(function() {
               			
               			// Ако имат стойност
               			if ($(this).val()) {
               				
               				// Добавяме името на файла в div
               				fileNameDiv = '<div class=\"fileName\">' + getFileName($(this).val()) + '</div>';
               				
               				// Добавяме в масива
               				fileNameArr[elementId] = fileNameDiv;
               				
               				// Увеличаваме с единица
               				elementId++;
    					}	
    				});
    				
    				// Ако има избрани файлове
                   	if (fileNameArr.length) {
                   		
                   		// Скриваме инпута
                   		$('#inputDiv').hide();
                   		
                   		// Показваме имената на файловете
                   		$('#filename').show().append(fileNameArr.join(' '));
                   		
                   		// Премахваме текущия бутон и инпут
                   		var btnId = '#btn-ulfile' + btnCntId;
                   		var inputId = '#ulfile' + btnCntId;
                   		$(inputId).remove();
                   		$(btnId).remove();
                   		
                   		btnCntId--;
                   		
                   		// Стартираме качаването
                   		beginUpload();
                   		
                   		return true;
        			}
        			
        			// Ако няма избрани файлове, бутона не може да се натиска
        			return false;
               });
            });
            
            // Брояч на бутона, който е добавен
            var btnCntId = 0;
            
            // След избиране на файл, добавя бутон за нов файл и показва името на файла
            function afterSelectFile(inputInst, multiUpload) 
            {
            	// Името на файла
            	var fileName = getFileName($(inputInst).val());
            	
            	// id на инпута
            	var inputId = $(inputInst).attr('id');
            	
            	// Скриваме input за избор на файлове
            	$('#' + inputId).css('z-index', '-50').css('width', '0').css('height', '0').css('border', 'none');
            	
            	// id на бутона
                var btnId = '#btn-' + inputId;
                
                // Скриваме бутона
                $(btnId).hide();
                
                // Линк за премахване на файла
                var crossImg = '<img src=" .  sbf('img/16/cross.png') . " align=\"absmiddle\" border=\"0\">';
                
                // id на качения файл
                var uploadedFileId = 'uploaded-file';
                
                // Ако брояча е по - голям от нула
                if (btnCntId != 0) {
                	
                	// Добавяме номера след id' то
                	uploadedFileId += btnCntId;
    			}
                
    			// В държача за качени файлове добавяме името на файла и линк за премахване
                $('.uploaded-filenames').prepend('<span class=\"uploaded-file\" id=\"' + uploadedFileId + '\">' + fileName + ' <a style=\"color:red;\" href=\"#\" onclick=\"unsetFile(' + btnCntId + ', ' + multiUpload + ')\">' + crossImg + '</a> </span>');
                
                // Ако е зададен качване на много файлове едновременно
                if (multiUpload != 0) {
                
                	// Текста на бутона
                	var btnText = $(btnId).text();
                    
                	// Увеличаваме брояча
                	btnCntId++;
					
                	// Стойносста на accept
                	var accept = $(inputInst).attr(\"accept\");
                	
                	// Създаваме нов бутон
                	var newBtnInput = '<input class=\"ulfile\" id=\"ulfile' + btnCntId + '\" name=\"ulfile' + btnCntId + '\" type=\"file\" onchange=\"afterSelectFile(this, ' + multiUpload + ');\"';
                	
                	// Ако има accept
                	if (accept) {
                		
                		// Добавяме към бътоба
                		newBtnInput += 'accept=' + accept;
        			}
        			
        			// Добавяме бутона
                	newBtnInput += ' > <button class=\"btn-ulfile\" id=\"btn-ulfile' + btnCntId + '\">' + btnText + '</button>';
                	
                	// Добавяме новия бутон
                    $(inputInst).parent().prepend(newBtnInput);
                }
                
                // Даваме възможност на бутона да се натисне
                $('#uploadBtn').removeAttr('disabled');
            }
            
            // Премахва посочения файл
            function unsetFile(id, multiUpload)
            {
            	// id на променливите
            	var btnId = '#btn-ulfile';
           		var inputId = '#ulfile';
           		var uploadedFileId = '#uploaded-file';
           		
           		// Ако има id, добавяме номера след имената на константите
           		if (id != 0) {
           			btnId += id;
           			inputId += id;
           			uploadedFileId += id;
    			}
           		
    			// Скриваме бавно качения файл
    			$(uploadedFileId).hide('slow', function() { 
    				
    				// Ако е зададено множество качаване
    				if (multiUpload) {
    					
    					// Премахваме всучко за този бутон и файл
    					$(this).remove(); 
    					$(inputId).remove();
    					$(btnId).remove();
    				} else {
    					
    					// Показваме бутона
    					$(btnId).show();
    					
						// Премахваме стойността на input'а
    					$(inputId).val('');
    				}
    				
    				// Дали да се деактивира бутона
        			var disableBtn = 'yes';
        			
        			// Обхождаме всички инпути от зададения клас
               		$('.ulfile').each(function() {
               			
               			// Ако имат стойност
               			if ($(this).val()) {
               				
               				// Да не се деактивира бутоан
               				disableBtn = 'none';
               				
               				// Спираме цикъла
	           				return false;
    					}
    				});
    				
    				// Ако няма нито един избран файл
    				if (disableBtn == 'yes') {
    					
    					// Деактивираме бутона
    					$('#uploadBtn').attr('disabled', 'disabled');
        			}
    			});
            }
			
            // Връща името на файла от подадени път
            function getFileName(filePath)
            {
            	// Ако е подаден път
            	if (filePath) {
            		
            		// Разделяме името от пътя
            		var fileNameArray = filePath.split('\\\\');
            		
                	// Вземаме името на файл
                	var string = fileNameArray[fileNameArray.length-1];
                	
                	// Лимитираме дължината и връщаме
                	return limitLen(string, 32);
    			}
            }
            
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