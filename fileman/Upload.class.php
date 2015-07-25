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
     * 
     */
    var $canAdd = 'every_one';
    
    
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
        
        // Вземаме id' то на кофата
        $bucketId = Request::get('bucketId', 'int');
        
        // Шаблона с качените файлове и грешките
        $add = new ET('<div id="add-file-info"><div id="add-error-info">[#ERR#]</div><div id="add-success-info">[#ADD#]</div></div>');
        
        // Ако е стартрино качването
        if(Request::get('Upload')) {
            
            // Обхождаме качените файлове
            foreach ((array)$_FILES as $inputName => $inputArr) {
                
                // Масив с грешките
                $err = array();
                
                foreach ((array)$inputArr['name'] as $id => $inpName) {
                    
                    // Ако файла е качен успешно
                    if($_FILES[$inputName]['name'][$id] && $_FILES[$inputName]['tmp_name'][$id]) {
                        
                        // Ако има кофа
                        if($bucketId) {
                            
                            // Вземаме инфото на обекта, който ще получи файла
                            $Buckets = cls::get('fileman_Buckets');
                            
                            // Ако файла е валиден по размер и разширение - добавяме го към собственика му
                            if($Buckets->isValid($err, $bucketId, $_FILES[$inputName]['name'][$id], $_FILES[$inputName]['tmp_name'][$id])) {
                                
                                // Създаваме файла
                                $fh = $this->Files->createDraftFile($_FILES[$inputName]['name'][$id], $bucketId);
                                
                                // Записваме му съдържанието
                                $this->Files->setContent($fh, $_FILES[$inputName]['tmp_name'][$id]);
                                
                                $add->append($Buckets->getInfoAfterAddingFile($fh), 'ADD');
                                
                                if($callback && !$_FILES[$inputName]['error'][$id]) {
                                    $name = $this->Files->fetchByFh($fh, 'name');
                                    $add->append("<script>  if(window.opener.{$callback}('{$fh}','{$name}') != true) self.close(); else self.focus();</script>", 'ADD');
                                }
                            }
                        } else {
                            $err[] = 'Не е избрана кофа';
                        }
                    }
                    
                    // Ако има грешка в $_FILES за съответния файл
                    if($_FILES[$inputName]['error'][$id]) {
                        // Ако са възникнали грешки при качването - записваме ги в променливата $err
                        switch($_FILES[$inputName]['error'][$id]) {
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
                        $error = new ET("<div class='upload-еrror'><ul>{$_FILES[$inputName]['name'][$id]}[#ERR#]</ul></div>");
                        
                        foreach($err as $e) {
                            $error->append("<li>" . tr($e) . "</li>", 'ERR');
                        }
                        $add->append($error, 'ERR');
                    }
                }
            }
        }
        
        // Ако има id на кофата
        if ($bucketId) {
            
            // Вземаме максималния размер за файл в кофата
            $maxAllowedFileSize = fileman_Buckets::fetchField($bucketId, 'maxSize');
        }
        
        $tpl = $this->getProgressTpl($allowMultiUpload, $maxAllowedFileSize);
        
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
            if (Request::get('progressKey')) {
                echo json_encode(uploadprogress_get_info(Request::get('progressKey')));
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
    function getProgressTpl($allowMultiUpload=FALSE, $maxAllowedFileSize=0)
    {
        $tpl = new ET('
            <style>
        		.uploaded-title{background-image:url(' . sbf('img/16/tick-circle-frame.png', '') . ');}
        		.btn-ulfile{background-image:url(' . sbf('img/16/paper_clip.png', '') . ');}
        		.ui-progressbar-value {background-image: url(' . sbf('jqueryui/1.8.2/css/custom-theme/images/pbar-ani.gif', '') . '); }
        	</style>
            <form id="uploadform" enctype="multipart/form-data" method="post">
            
                <span class="uploaded-filenames"> </span>

                <input id="progress_key" name="UPLOAD_IDENTIFIER" type="hidden" value="[#ufid#]" />
                <div id="inputDiv">
                
                    <input id="ulfile" class="ulfile" name="ulfile[]" [#MULTIPLE#] type="file" size="1" onchange="afterSelectFile(this, ' . (int)$allowMultiUpload . ', ' . (int)$maxAllowedFileSize . ');" [#ACCEPT#]>
                    <button id="btn-ulfile" class="linkWithIcon button btn-ulfile">' . tr('Файл') . '</button>
                    
                    <input type="submit" name="Upload" value="' . tr('Качване') . '" class="linkWithIcon button btn-disabled" id="uploadBtn" disabled="disabled"/>

                </div>
                
                <div id="filename"></div>
                
                <div id="uploadprogressbar" class="progressbar"></div>
                
                <input name="Protected" type="hidden" value="[#Protected#]" />
            </form>');
        
        $ufid = str::getRand();
        
        if ($allowMultiUpload) {
            $tpl->replace('multiple', 'MULTIPLE');
        } else {
            $tpl->replace('', 'MULTIPLE');
        }
        
        $tpl->replace($ufid, 'ufid');
        
        $tpl->replace(Request::get('Protected'), 'Protected');
        
        jqueryui_Ui::enable($tpl);
        
        $url = toUrl(array($this, 'UploadProgress'));
        
        $tpl->appendOnce("
            // След като се зареди
            $(document).ready(function() {
				
            	// Добавя стилове за по - стара версия на FF < 21
            	addStyleForOldFf($('#ulfile'));
            
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
                   		
                   		// Стартираме качаването
                   		beginUpload();
                   		
                   		// Скриваме имената на файловете
                   		$('.uploaded-filenames').hide('slow');
                   		
                   		// Скриваме информацията за файла
                   		$('#add-file-info').hide('slow');
                   		
                   		return true;
        			}
        			
        			// Ако няма избрани файлове, бутона не може да се натиска
        			return false;
               });
            });
            
            // Брояч на бутона, който е добавен
            var btnCntId = 0;
            
            // Добавя необходимите стилове за старите браузъри
            function addStyleForOldFf(inst)
        	{
        		// Вземаме версията на браузъра
        		var ffVerMatch = navigator.userAgent.match(/Firefox\\/(.*)$/);
            	
        		// Версията на FF
        		var ffVersion=null;
            	
        		// Ако има открит резултат
            	if (ffVerMatch && ffVerMatch.length >1) {
            		
            		// Вземаем версията
            		var ffVersion = ffVerMatch[1];
    			}
    			
    			// Ако има версия и е под 21
    			if (ffVersion && ffVersion < 21) {
    				
    				// Добавяме нужния CSS
    				inst.css('font-size', '0.58em').css('left', '-23px');
    			}
			}
            
            // След избиране на файл, добавя бутон за нов файл и показва името на файла
            function afterSelectFile(inputInst, multiUpload, maxFileSize) 
            {
            	// Грешките за файла
            	var fileError = new Object();
            	
            	// Пътя до файла
            	var filePath = $(inputInst).val();
            	
            	var filesArr = $(inputInst)[0]['files'];
            	
            	var filePathArr = [];
            	
            	if (filesArr) {
            		$(filesArr).each(function(index, fileVal) {
                   			
               			filePath = fileVal['name'];
               			
               			if (!filePath.length) return true;
               			
               			filePathArr.push(filePath);
    				});
    			} else {
    				// Ако няма път
                	if (!filePath.length) {
                		
                		// Връщаме
                		return;
        			}
        			
    				filePathArr.push(filePath);
    			}
            	
    			if (!filePathArr.length) return ;
    			
    			// Ако браузъра поддъжа fileApi
    			if (typeof FileReader !== 'undefined') {
    				
    				// Размера на файла
                    var fileSize = inputInst.files[0].size;
                    
                    // Ако размера на файла е над допусмите
                    if (maxFileSize && fileSize > maxFileSize) {
                    	
                    	// Сетваме грешката
                    	fileError.fileSize = true;
    				}
                }
    			
            	// id на инпута
            	var inputId = $(inputInst).attr('id');
            	
            	// Скриваме input за избор на файлове
            	$('#' + inputId).addClass('hidden-input');
            	
            	// id на бутона
                var btnId = '#btn-' + inputId;
                
                // Скриваме бутона
                $(btnId).hide();
                
                // Линк за премахване на файла
                var crossImg = '<img src=" .  sbf('img/16/cross.png') . " align=\"absmiddle\" alt=\"\">';
                
                var show = true;
                
                $(filePathArr).each(function(index, filePath) {
            		
                	// id на качения файл
                	var uploadedFileId = 'uploaded-file';
                
                    // Ако брояча е по - голям от нула
                    if (btnCntId != 0) {
                    	
                    	// Добавяме номера след id' то
                    	uploadedFileId += btnCntId;
        			}
        			
        			// Името на класа за качения файл
    				var uploadedFileClass = 'uploaded-file';
        			
    				// Ако размера е над допусмите
    				if (fileError.fileSize) {
    					
    					// Добавяме класа за грешка
        				uploadedFileClass += ' error-filesize';
        				
        				// Титлата на спана
        				var uploadedFileTitle = 'title=\"File size exceeded the maximum size\"';
        			}
    				
    				// Името на файла
            		var fileName = getFileName(filePath);
            		
            		var crossImgLink = ' <a style=\"color:red;\" href=\"#\" onclick=\"unsetFile(' + btnCntId + ', ' + multiUpload + ', ' + filePathArr.length + ')\">' + crossImg + '</a>';
            		
            		if (!show) {
            			crossImgLink = '';
            		}
            		
            		// В държача за качени файлове добавяме името на файла и линк за премахване
                	$('.uploaded-filenames').append('<span ' + uploadedFileTitle + ' class=\"' + uploadedFileClass + '\" id=\"' + uploadedFileId + '\">' + fileName + crossImgLink +' </span>');
                	
                	if (multiUpload) {
                		btnCntId++;
    				}
                	
                	show = false;
    			});
    			
                // Ако е зададен качване на много файлове едновременно
                if (multiUpload != 0) {
                
                	// Текста на бутона
                	var btnText = $(btnId).text();
                    
                	// Стойносста на accept
                	var accept = $(inputInst).attr(\"accept\");
                	
                	// Създаваме нов бутон
                	var newBtnInput = '<input class=\"ulfile\" id=\"ulfile' + btnCntId + '\" name=\"ulfile[]\" multiple type=\"file\" size=\"1\"  onchange=\"afterSelectFile(this, ' + multiUpload + ', ' + maxFileSize + ');\"';
                	
                	// Ако има accept
                	if (accept) {
                		
                		// Добавяме към бътона
                		newBtnInput += 'accept=' + accept;
        			}
        			
        			// Добавяме бутона
                	newBtnInput += ' > <button class=\"btn-ulfile\" id=\"btn-ulfile' + btnCntId + '\">' + btnText + '</button>';
                	
                	// Добавяме новия бутон
                    $(inputInst).parent().prepend(newBtnInput);
                    
                    // Добавя стилове за по - стара версия на FF < 21
                    addStyleForOldFf($('#ulfile' + btnCntId));
                } else {
                
                	// Добавяме класа
                	$('#uploadBtn').addClass('only-one-file');
    			}
                
                // Даваме възможност на бутона да се натисне
                $('#uploadBtn').removeAttr('disabled').removeClass('btn-disabled');
                
                // Скролира до последния качен файл
				$('.uploaded-filenames').scrollTop($(this).height());
            }
            
            // Премахва посочения файл
            function unsetFile(id, multiUpload, len)
            {
            	var btnIdName = '#btn-ulfile';
            	var inputIdName = '#ulfile';
            	var uploadedFileIdName = '#uploaded-file';
            	
            	// id на променливите
            	var btnId = btnIdName;
           		var inputId = inputIdName;
           		var uploadedFileId = uploadedFileIdName;
           		
           		// Ако има id, добавяме номера след имената на константите
           		if (id != 0) {
           			btnId += id;
           			inputId += id;
           			uploadedFileId += id;
    			}
           		
    			// Скриваме бавно качения файл
    			$(uploadedFileId).hide('slow', function() { 
    				
    				$(this).remove(); 
    				
    				// Дали да се деактивира бутона
        			var disableBtn = 'yes';
    				
    				// Ако е зададено множество качаване
    				if (multiUpload) {
    					
    					if (len > 1) {
    						for(var i=1; i<len; i++) {
    							var ii = id + i;
    							
    							// Премахваме и другите файлове, които са качени заедно
    							$(btnIdName + ii).remove();
    							$(inputIdName + ii).remove();
    							$(uploadedFileIdName + ii).remove();
    						}
    					}
						
    					
    					// Премахваме всучко за този бутон и файл
    					$(inputId).remove();
    					$(btnId).remove();
    					
    					// Обхождаме всички инпути от зададения клас
                   		$('.ulfile').each(function() {
                   			
                   			// Ако имат стойност
                   			if ($(this).val()) {
                   				
                   				// Да не се деактивира бутона
                   				disableBtn = 'none';
                   				
                   				// Спираме цикъла
    	           				return false;
        					}
        				});
    				} else {
    					
    					// Показваме бутона
    					$(btnId).show();
    					
						// Премахваме стойността на input'а и класа за скриване
    					$(inputId).val('').removeClass('hidden-input');
    				}
    				
    				// Ако няма нито един избран файл
    				if (disableBtn == 'yes') {
    					
    					// Деактивираме бутона
    					$('#uploadBtn').attr('disabled', 'disabled').addClass('btn-disabled').removeClass('only-one-file');
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
                setTimeout(function(){showUpload(0);}, 2000);
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

                    $.get('{$url}?progressKey=' + progress_key, function(data) {  
                    
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
