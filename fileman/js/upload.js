// Масив с премахнатите файлове от multiple поле
var ignoreFilePath = [];

// След като се зареди
$(document).ready(function() {
	
    // Прихваща натискането на бутона за качване
    $('#uploadBtn').on('click', function() {
    	
    	// Използваме плъгина simpleUpload за да качим файла по AJAX
        $('input[type=file]').simpleUpload(uploadUrl, {
    		
        	// Това се вика един път за всеки файл при стартиране на качването
        	start: function(file) {
        		
        		// Ако файлът съществува в масива с премахнатите, да не се качва
        		if (ignoreFilePath.length) {
        			var indexOf = ignoreFilePath.indexOf(file.name);
        			if (indexOf != -1) {
        				
        				ignoreFilePath.splice(indexOf, 1);
        				
        				return false;
        			}
        		}
        		
    			// Показваме стринга за качване на файла
                $('#uploadsTitle').css('display', 'block');
                
                // Премахваме всички инпут полета
                $('.uploaded-file').each(function() {
                    $(this).hide().remove();
                });
                
                // Деактивираме бутона за качване
                $('#uploadBtn').attr('disabled', 'disabled').addClass('btn-disabled');
                $('#inputDiv').hide();
                
                // Променлива за име на файл
                this.fileName = $('<div class="fileNameRow">' + file.name + '</div>');
                
                $('#uploads').append(this.fileName);
                
                // Променлива за прогрес бара
				this.progressBar = $('<div class="progressBarBlock"></div>');
				
				// Процентите на прогрес бара
                this.progressBarPercent = $('<span>0%</span>');
				this.progressBar.append(this.progressBarPercent);
                
				$('#uploads').append(this.progressBar);
        	},
        	
        	// Вика се всеки път, когато се върне прогрес/отговор от сървъра - за всеки файл самосотоятелно
	    	progress: function(progress) {
	    		// След като прогреса нарастне над 
	            if (progress >= 10) {
	                this.progressBar.width(progress + '%');
	            }
	            
	            // Показваме стрингово процентите на качения файл
	            this.progressBarPercent.html(Math.round(progress) + '%');
	    	},
	        
	    	// Вика се след приключване на качването на файла
	    	success: function(data) {
	    		
	    		// Премахваме прогрес бара и името на файла
	            this.progressBar.remove();
	            this.fileName.remove();
	            
	            // Показваме информация за качения файл
	            if (data.success) {
	                $('#add-success-info').append(data.res);
	            } else {
	                $('#add-error-info').append(data.res);
	                
	                $('#uploadBtn').attr('disabled', 'disabled').addClass('btn-disabled').removeClass('only-one-file');
		    		$('#ulfile').removeClass('hidden-input');
					$("#btn-ulfile").show();
	            }
	            
	            // Ако няма други файлове за качване, показваме бутона за добавяне на файл
	            if (!$('.progressBarBlock').length) {
	                $('#inputDiv').show();
	                $('#uploadsTitle').css('display', 'none');
	            }
	    	},
	        
	    	// При възникване на грешка в комуникацията - най-често при спиране на интернета
	    	error: function(error) {
	    		
	    		$('#uploadBtn').attr('disabled', 'disabled').addClass('btn-disabled').removeClass('only-one-file');
	    		$('#ulfile').removeClass('hidden-input');
				$("#btn-ulfile").show();
				
	            this.progressBar.remove();
	            this.fileName.remove();
	            
	            if (!$('.progressBarBlock').length) {
	                $('#inputDiv').show();
	                $('#uploadsTitle').css('display', 'none');
	            }
	            
	            $('#add-error-info').append('<div class="upload-еrror">' + uploadErrStr + '<div><b>' + this.fileName.html() + '</b></div></div>');
	    	}
        });
	});
});


// Брояч на бутона, който е добавен
var btnCntId = 0;


/**
 * След избиране на файл, добавя бутон за нов файл и показва името на файла
 * 
 * @param inputInst
 * @param multiUpload
 * @param maxFileSize
 */
function afterSelectFile(inputInst, multiUpload, maxFileSize) 
{
    // Нулираме предишните стойности
    $('#add-success-info').html('');
    $('#add-error-info').html('');
            
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
	
	// id на инпута
	var inputId = $(inputInst).attr('id');
	
	// Скриваме input за избор на файлове
	$('#' + inputId).addClass('hidden-input');
	
	// id на бутона
    var btnId = '#btn-' + inputId;
    
    // Скриваме бутона
    $(btnId).hide();
    
    // Линк за премахване на файла
    var crossImg = '<img src="' + crossImgPng + '" align="absmiddle" alt="">';
    
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
		
        var uploadedFileTitle = '';
        
        try {
        	// Ако браузъра поддъжа fileApi
        	if (typeof FileReader !== 'undefined') {
        		
        		// Размера на файла
                var fileSize = inputInst.files[index].size;
                
                // Ако размера на файла е над допусмите
                if (maxFileSize && fileSize > maxFileSize) {

        			// Добавяме класа за грешка
        			uploadedFileClass += ' error-filesize';
        			
        			// Титлата на спана
        			uploadedFileTitle = ' title="' + fileSizeErr + '"';
        		}
            }
        } catch(err) {
        	getEO().log(err);
        }
    	
		// Името на файла
		var fileName = getFileName(filePath);
		
		var filePathEsc = filePath.replace('"', '\"');
		var filePathEsc = filePath.replace("'", "\'");
		
		var crossImgLink = ' <a style="color:red;" href="#" onclick="unsetFile(' + btnCntId + ', ' + multiUpload + ', ' + filePathArr.length + ', \'' + filePathEsc + '\')">' + crossImg + '</a>';
		
		// В държача за качени файлове добавяме името на файла и линк за премахване
    	$('.uploaded-filenames').append('<span' + uploadedFileTitle + ' class="' + uploadedFileClass + '" id="' + uploadedFileId + '">' + fileName + crossImgLink +' </span>');
    	
    	if (multiUpload) {
    		btnCntId++;
		}
	});
	
    // Ако е зададен качване на много файлове едновременно
    if (multiUpload != 0) {
    
    	// Текста на бутона
    	var btnText = $(btnId).text();
        
    	// Стойносста на accept
    	var accept = $(inputInst).attr("accept");
    	
    	// Създаваме нов бутон
    	var newBtnInput = '<input class="ulfile" id="ulfile' + btnCntId + '" name="ulfile[]" multiple type="file" size="1"  onchange="afterSelectFile(this, ' + multiUpload + ', ' + maxFileSize + ');"';
    	
    	// Ако има accept
    	if (accept) {
    		
    		// Добавяме към бътона
    		newBtnInput += 'accept=' + accept;
		}
		
		// Добавяме бутона
    	newBtnInput += ' > <button class="btn-ulfile" id="btn-ulfile' + btnCntId + '">' + btnText + '</button>';
    	
    	// Добавяме новия бутон
        $(inputInst).parent().prepend(newBtnInput);
    } else {
    
    	// Добавяме класа
    	$('#uploadBtn').addClass('only-one-file');
	}
    
    // Даваме възможност на бутона да се натисне
    $('#uploadBtn').removeAttr('disabled').removeClass('btn-disabled');
    
    // Скролира до последния качен файл
	$('.uploaded-filenames').scrollTop($(this).height());
}


/**
 * Премахва посочения файл
 * 
 * @param id
 * @param multiUpload
 * @param len
 * @param filePath
 */
function unsetFile(id, multiUpload, len, filePath)
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
			
			$(btnId).remove();
			
			// Ако в един инпут има повече от един файл, не се премахва инпута
			// Добавяме файла в масив с игнориране, които няма да се качат
			if (len > 1) {
				ignoreFilePath.push(filePath);
			} else {
				$(inputId).remove();
			}
		} else {
			// Показваме бутона
			$(btnId).show();
			
			// Премахваме стойността на input'а и класа за скриване
			$(inputId).val('').removeClass('hidden-input');
		}
		
		// Ако няма нито един избран файл
		if (!$('.uploaded-file').length) {
			
			// Деактивираме бутона
			$('#uploadBtn').attr('disabled', 'disabled').addClass('btn-disabled').removeClass('only-one-file');
		}
	});
}


/**
 * Връща името на файла от подадени път
 * 
 * @param filePath
 */
function getFileName(filePath)
{
	// Ако е подаден път
	if (filePath) {
		
		// Разделяме името от пътя
		var fileNameArray = filePath.split('\\');
		
    	// Вземаме името на файл
    	var string = fileNameArray[fileNameArray.length-1];
    	
    	// Лимитираме дължината и връщаме
    	return limitLen(string, 32);
	}
}
        