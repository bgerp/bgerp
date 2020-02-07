var fp = new Tremol.FP();


/**
 * Задава настройките на сървъра
 * 
 * @param ip
 * @param port
 */
function fpServerSetSettings(ip, port)
{
    try {
        fp.ServerSetSettings(ip, port);
    } catch(ex) {
        handleException(ex);
    }
}


/**
 * Задава настройките на сървъра
 * 
 * @param useFound
 */
function fpServerFindDevice(useFound)
{
    try {
        res = fp.ServerFindDevice(useFound);
    } catch(ex) {
        handleException(ex);
    }
    
    return res;
}


/**
 * Задава настройките на устройствота
 * 
 * @param ip
 * @param tcpPort
 * @param password
 * @param serialPort
 * @param baudRate
 * @param keepPortOpen
 */
function fpServerSetDeviceSettings(ip, tcpPort, password, serialPort, baudRate, keepPortOpen)
{
    try {
    	if (!ip && !serialPort) {
    		throw new Error("Няма IP или сериен порт за връзка");
    	}
    	
        if (ip) {
            fp.ServerSetDeviceTcpSettings(ip, tcpPort, password);
        } else {
            fp.ServerSetDeviceSerialSettings(serialPort, baudRate, keepPortOpen);
        }
        
        if (!fp.IsCompatible()) {
        	console.log("Текущата версия на библиотеката и сървърните дефиниции се различават!");
        }
    } catch(ex) {
        handleException(ex);
    }
};


/**
 * Отваря бележка за печат
 * 
 * @param operNum
 * @param operPass
 * @param isDetailed
 * @param isPrintVat
 * @param printTypeStr
 * @param rcpNum
 */
function fpOpenFiscReceipt(operNum, operPass, isDetailed, isPrintVat, printTypeStr, rcpNum)
{
	checkOperNum(operNum);
	
	checkOperPass(operPass);
	
	var receiptFormat = getReceipFormat(isDetailed);
	
	var printVat = getPrintVat(isPrintVat);
	
	if (printTypeStr == 'postponed') {
		var printType = Tremol.Enums.OptionFiscalRcpPrintType.Postponed_printing;
	} else if (printTypeStr == 'buffered') {
		var printType = Tremol.Enums.OptionFiscalRcpPrintType.Buffered_printing;
	} else if (printTypeStr == 'stepByStep') {
		var printType = Tremol.Enums.OptionFiscalRcpPrintType.Step_by_step_printing;
	} else {
		throw new Error("Непозволен тип за принтиране");
	}
	
	checkRcpNum(rcpNum);
	
    try {
        fp.OpenReceipt(operNum, operPass, receiptFormat, printVat, printType, rcpNum);
    } catch(ex) {
        handleException(ex);
    }
};


/**
 * Сторниране на ФБ
 * 
 * @param operNum
 * @param operPass
 * @param isDetailed
 * @param isPrintVat
 * @param printTypeStr
 * @param stornoReason
 * @param relatedToRcpNum
 * @param relatedToRcpDateTime
 * @param FMNum
 * @param relatedToURN
 */
function fpOpenStornoReceipt(operNum, operPass, isDetailed, isPrintVat, printTypeStr, stornoReason, relatedToRcpNum, relatedToRcpDateTime, FMNum, relatedToURN)
{
	checkOperNum(operNum);
	
	checkOperPass(operPass);
	
	var receiptFormat = getReceipFormat(isDetailed);
	
	var printVat = getPrintVat(isPrintVat);
	
	if (printTypeStr == 'postponed') {
		var printType = Tremol.Enums.OptionStornoRcpPrintType.Postponed_Printing;
	} else if (printTypeStr == 'buffered') {
		var printType = Tremol.Enums.OptionStornoRcpPrintType.Buffered_Printing;
	} else if (printTypeStr == 'stepByStep') {
		var printType = Tremol.Enums.OptionStornoRcpPrintType.Step_by_step_printing;
	} else {
		throw new Error("Непозволен тип за принтиране");
	}
	
	if ((stornoReason < 0) || (stornoReason > 2)) {
		throw new Error("Непозволена причина за сторно");
	}
	
	if (relatedToRcpNum.length > 6) {
		throw new Error("Дължината на номера на ФБ трябва да е до 6 символа");
	}
	
	if (relatedToRcpDateTime.length > 19) {
		throw new Error("Времето на ФБ не може да е над 19 символа");
	}
	
	if (FMNum.length != 8) {
		throw new Error("Номера на фискалната памет трябва да е 8 символа");
	}
	
	if (relatedToURN) {
		checkRcpNum(relatedToURN);
	}
	
	try {
		fp.OpenStornoReceipt(operNum, operPass, receiptFormat, printVat, printType, stornoReason, relatedToRcpNum, relatedToRcpDateTime, FMNum, relatedToURN);
	} catch(ex) {
	    handleException(ex);
	}
}


/**
 * Кредитно известие
 * 
 * @param operNum
 * @param operPass
 * @param printTypeStr
 * @param recipient
 * @param buyer
 * @param VATNumber
 * @param UIC
 * @param address
 * @param UICTypeStr
 * @param stornoReason
 * @param relatedToInvNum
 * @param relatedToInvDateTime
 * @param relatedToRcpNum
 * @param FMNum
 * @param relatedToURN
 */
function fpOpenCreditNoteWithFreeCustomerData(operNum, operPass, printTypeStr, recipient, buyer, VATNumber, UIC, address, UICTypeStr, stornoReason, relatedToInvNum, relatedToInvDateTime, relatedToRcpNum, FMNum, relatedToURN)
{
	checkOperNum(operNum);
	
	checkOperPass(operPass);
	
	if (printTypeStr == 'postponed') {
		var printType = Tremol.Enums.OptionInvoiceCreditNotePrintType.Postponed_Printing;
	} else if (printTypeStr == 'buffered') {
		var printType = Tremol.Enums.OptionInvoiceCreditNotePrintType.Buffered_Printing;
	} else if (printTypeStr == 'stepByStep') {
		var printType = Tremol.Enums.OptionInvoiceCreditNotePrintType.Step_by_step_printing;
	} else {
		throw new Error("Непозволен тип за принтиране");
	}
	
	if (recipient && recipient.length > 26) {
		throw new Error("Максималната дължина на получателя е 26 символа");
	}
	
	if (buyer && buyer.length > 16) {
		throw new Error("Максималната дължина на купувача е 16 символа");
	}
	
	if (VATNumber && VATNumber.length > 13) {
		throw new Error("Максималната дължина на VAT номера е 13 символа");
	}
	
	if (UIC && UIC.length > 13) {
		throw new Error("Максималната дължина на UIC номера е 13 символа");
	}
	
	if (address && address.length > 30) {
		throw new Error("Максималната дължина на адрес номера е 30 символа");
	}
	
	if (UICTypeStr == 'bulstat') {
		var UICType = 0;
	} else if (UICTypeStr == 'EGN') {
		var UICType = 1;
	} else if (UICTypeStr == 'FN') {
		var UICType = 2;
	} else if (UICTypeStr == 'NRA') {
		var UICType = 3;
	} else {
		throw new Error("Непозволен тип за UIC тип");
	}
	
	if ((stornoReason < 0) || (stornoReason > 2)) {
		throw new Error("Непозволена причина за сторно");
	}
	
	if (relatedToInvNum && relatedToInvNum.length > 10) {
		throw new Error("Дължината на номера на фактурата трябва да е до 10 символа");
	}
	
	if (relatedToInvDateTime && relatedToInvDateTime.length > 19) {
		throw new Error("Времето на фактурата не може да е над 19 символа");
	}
	
	if (relatedToRcpNum && relatedToRcpNum.length > 6) {
		throw new Error("Дължината на номера на ФБ трябва да е до 6 символа");
	}
	
	if (FMNum.length != 8) {
		throw new Error("Номера на фискалната памет трябва да е 8 символа");
	}
	
	if (relatedToURN) {
		checkRcpNum(relatedToURN);
	}
	
	try {
		fp.OpenCreditNoteWithFreeCustomerData(operNum, operPass, printType, recipient, buyer, VATNumber, UIC, address, UICType, stornoReason, relatedToInvNum, relatedToInvDateTime, relatedToRcpNum, FMNum, relatedToURN);
	} catch(ex) {
	    handleException(ex);
	}
}


/**
 * Помощна фунцкия за проверка на номера на оператор
 * 
 * @param operNum
 */
function checkOperNum(operNum)
{
	if ((operNum < 1) || (operNum > 20)) {
		throw new Error("Номер на оператор може да е от 1 до 20");
	}
}


/**
 * Помощна фунцкия за проверка на паролата на оператора
 * 
 * @param operPass
 */
function checkOperPass(operPass)
{
	if (operPass.length > 6) {
		throw new Error("Паролата на оператора не трябва да е над 6 символа");
	}
}


/**
 * Помощна фунцкия за проверка на УНП
 * 
 * @param rcpNum
 * @returns
 */
function checkRcpNum(rcpNum)
{
	if (!rcpNum.match(/[a-z0-9]{8}-[a-z0-9]{4}-[0-9]{7}/gi)) {
		throw new Error("Невалиден номер за касаво бележка");
	}
}


/**
 * Помощна функция за връща дали бележката да е детайлна
 * 
 * @param isDetailed
 */
function getReceipFormat(isDetailed)
{
	var receiptFormat = Tremol.Enums.OptionReceiptFormat.Brief;
	if (isDetailed) {
		receiptFormat = Tremol.Enums.OptionReceiptFormat.Detailed;
	}
	
	return receiptFormat;
}


/**
 * Помощна функция - дали да се отпечата детайлно ДДС
 * 
 * @param isPrintVat
 */
function getPrintVat(isPrintVat)
{
	var printVat = Tremol.Enums.OptionPrintVAT.No;
	if (isPrintVat) {
		printVat = Tremol.Enums.OptionPrintVAT.Yes;
	}
	
	return printVat;
}


/**
 * Добавя артикул към бележката
 * 
 * @param name
 * @param vatClass
 * @param price
 * @param qty
 * @param discAddP
 * @param discAddV
 * @param depNum
 */
function fpSalePLU(name, vatClass, price, qty, discAddP, discAddV, depNum)
{
	if ((vatClass < 0) || (vatClass > 3)) {
		throw new Error("Непозволен клас за VAT");
	}
	
    try {
    	if (depNum === false) {
    		fp.SellPLUwithSpecifiedVAT(name, Tremol.Enums.OptionVATClass['VAT_Class_' + vatClass], price, qty, discAddP, discAddV);
    	} else {
    		fp.SellPLUwithSpecifiedVATfromDep(name, Tremol.Enums.OptionVATClass['VAT_Class_' + vatClass], price, qty, discAddP, discAddV, depNum);
    	}
    } catch(ex) {
        handleException(ex);
    }
};


/**
 * Приключва фискалния бон
 */
function fpCloseReceiptInCash()
{
    try {
       fp.CashPayCloseReceipt();
    } catch(ex) {
        handleException(ex);
    }
};


/**
 * Добавяне на плащане
 * 
 * @param paymentType
 * @param change
 * @param amount
 * @param changeType
 */
function fpPayment(paymentType, change, amount, changeType)
{
	if ((paymentType < 0) || (paymentType > 11)) {
		throw new Error("Типа на плащането може да е от 0 до 11");
	}
	
	if ((change != 0) && (change != 1)) {
		throw new Error("Рестото може да е 0 или 1");
	}
	
	if ((changeType != 0) && (changeType != 1) && (changeType != 2)) {
		throw new Error("Непозволен параметър за типа на плащането");
	}
	
    try {
    	fp.Payment(paymentType, change, amount, changeType);
    } catch(ex) {
        handleException(ex);
    }
};


/**
 * Добавяне на плащане
 * 
 * @param paymentType
 * @param change
 * @param amount
 * @param changeType
 */
function fpPayExactSum(paymentType)
{
	if ((paymentType < 0) || (paymentType > 11)) {
		throw new Error("Типа на плащането може да е от 0 до 11");
	}
	
    try {
    	fp.PayExactSum(paymentType);
    } catch(ex) {
        handleException(ex);
    }
};


/**
 * Прекратява фискалния бон
 */
function fpCancelFiscalReceipt()
{
    try {
       fp.CancelReceipt();
    } catch(ex) {
        handleException(ex);
    }
};


/**
 * Задава времето на ФП
 * 
 * @param dateTime
 */
function fpSetDateTime(dateTime)
{
	try {
       fp.SetDateTime(dateTime);
    } catch(ex) {
        handleException(ex);
    }
};


/**
 * Връща серийния номер на устройството
 * 
 * @return string
 */
function fpSerialNumber()
{
	var res = '';
    try {
        var res = fp.ReadSerialAndFiscalNums().SerialNumber;
    } catch(ex) {
        handleException(ex);
    }
    
    return res;
};


/**
 * Връща паролата от ФУ на потребителя
 * 
 * @param integer
 * 
 * @return string
 */
function fpGetOperPass(operator)
{
	if (!operator) {
		operator = 1;
	}
	var res = '';
    try {
        var res = fp.ReadOperatorNamePassword(operator).Password;
    } catch(ex) {
        handleException(ex);
    }
    
    return res;
};


/**
 * Връща паролата от ФУ на потребителя
 * 
 * @return array
 */
function fpGetDefPayments()
{
    try {
    	var defPaymArr = {};
    	var exRate = 0;
    	
        if (fpIsNew()) {
        	var paymRes = fp.ReadPayments();
        	var i = 0;
        	for (var key in paymRes) {
        		try {
        			val = paymRes[key];
            		if (key == 'ExchangeRate') {
            			exRate = val.trim();
            		} else {
            			key = key.trim();
            			val = val.trim();
            			defPaymArr[val] = key.replace('NamePayment', '');
            		}
        		} catch(ex) { }
        	}
        } else {
        	var paymRes = fp.ReadPayments_Old();
        	for (i = 0; i <= 4; i++) {
        		var namePayment = "NamePaym" + i;
                var codePayment = "CodePaym" + i;
        		try {
                    var paymResStr = paymRes[namePayment];
                    paymResStr = paymResStr.trim();
                    defPaymArr[paymResStr] = i;
        		} catch(ex) { }
    		}
        	
        	try {
    			exRate = paymRes['ExRate'];
    		} catch(ex) { }
        }
    } catch(ex) {
        handleException(ex);
    }
    
    res = {defPaymArr: defPaymArr};
    
    if (exRate) {
    	res.exRate = exRate;
    }
    
    return res;
};


/**
 * Връща департаментите от ФУ
 * 
 * @return array
 */
function fpGetDepArr()
{
	res = {};
	
    try {
    	
    	for(depNum=0;depNum<100;depNum++) {
            dep = fp.ReadDepartment(depNum);
            
            depNumPad = dep.DepNum.toString().padStart(2, '0');
            
            depName = dep.DepName.trim();
            
            // Да избегнем дефолтно зададените
            if (depName == 'Деп ' + depNumPad) {
                continue;
            }
            
            res[dep.DepNum] = depName;
        }
    } catch(ex) {
        handleException(ex);
    }
    
    return res;
};


/**
 * Проверява дали принтера е нов или е обновена версия на стар
 * 
 * @return boolean
 */
function fpIsNew()
{
	var model = fp.ReadVersion().Model;
	
	if (!model) return true;
	
	var len = model.length;
	
	if (!len) return true;
	
	if (model.slice(-2) != 'V2') return true;
	
	return false;
}


/**
 * Отпечатване на дубликат
 */
function fpPrintLastReceiptDuplicate()
{
    try {
        fp.PrintLastReceiptDuplicate();
    } catch(ex) {
        handleException(ex);
    }
};


/**
 * Връща серийния номер на устройството
 * 
 * @return string
 */
function fpProgramHeader(text, fPos)
{
    try {
        fp.ProgHeader(fPos, text);
    } catch(ex) {
        handleException(ex);
    }
};


/**
 * Връща серийния номер на устройството
 * 
 * @return string
 */
function fpProgramFooter(text)
{
    try {
        fp.ProgFooter(text);
    } catch(ex) {
        handleException(ex);
    }
};


/**
 * Проверява дали има отворена бележка
 * 
 * @return boolean
 */
function fpCheckOpenedFiscalReceipt()
{
	try {
		var status = fp.ReadStatus();
	} catch(ex) {
		handleException(ex);
		
		return false;
	}
        
    return status['Opened_Fiscal_Receipt'];
};


/**
 * Проверява серийния номер на ФП
 * 
 * @param serNumber
 */
function fpCheckSerialNumber(serNumber)
{
    try {
        var res = fpSerialNumber();
        
        if (res != serNumber) {
        	throw new Error('Некоректен сериен номер: ' + res);
        }
    } catch(ex) {
        handleException(ex);
    }
    
    return res;
};


/**
 * Връща стойността на последно отпечатаната ФБ - това, което се показва в QR кода
 * FM Number*Receipt Number*Receipt Date*Receipt Hour*Receipt Amount
 */
function fpReadLastReceiptQRcodeData()
{
    try {
        var res = fp.ReadLastReceiptQRcodeData();
    } catch(ex) {
        handleException(ex);
    }
    
    return res;
};


/**
 * Отпечатва подадения текст
 * 
 * @param text
 */
function fpPrintText(text)
{
	try {
        var res = fp.PrintText(text);
    } catch(ex) {
        handleException(ex);
    }
}


/**
 * Отпечатва X и Z отчета
 * Дневен отчет с и без нулиране. Може да е детайлен или не.
 */
function fpDayReport(isZeroing, isDetailed)
{
    try {
    	var zeroing = Tremol.Enums.OptionZeroing.Without_zeroing;
    	if (isZeroing) {
    		zeroing = Tremol.Enums.OptionZeroing.Zeroing;
    	}
    	
    	if (isDetailed) {
    		fp.PrintDetailedDailyReport(zeroing);
    	} else {
    		fp.PrintDailyReport(zeroing);
    	}
    } catch(ex) {
        handleException(ex);
    }
};


/**
 * Отпечатва операторски отчет - дневен с и без нулиране
 */
function fpOperatorReport(isZeroing, number)
{
    try {
    	var zeroing = Tremol.Enums.OptionZeroing.Without_zeroing;
    	if (isZeroing) {
    		zeroing = Tremol.Enums.OptionZeroing.Zeroing;
    	}
    	fp.PrintOperatorReport(zeroing, number);
    } catch(ex) {
        handleException(ex);
    }
};


/**
 * Отпечатва отчет за съответния период - с и без нулиране
 */
function fpPeriodReport(startDate, endDate, isDetailed)
{
    try {
    	if (isDetailed) {
    		fp.PrintDetailedFMReportByDate(startDate, endDate);
    	} else {
    		fp.PrintBriefFMReportByDate(startDate, endDate);
    	}
    } catch(ex) {
        handleException(ex);
    }
};


/**
 * Отпечатва/записва КЛЕН отчет за съответния период - с и без нулиране
 */
function fpOutputKLEN(outType, startDate, endDate, isDetailed)
{
    try {
    	if (outType == 'pc') {
    		var detailType = Tremol.Enums.OptionReportFormat.Detailed_EJ;
    		if (!isDetailed) {
    			detailType = Tremol.Enums.OptionReportFormat.Brief_EJ;
    		}
    		
    		fp.ReadEJByDate(detailType, startDate, endDate);
    		
    		printToPc();
    	} else {
    		var reportStorage = Tremol.Enums.OptionReportStorage.Printing;
    		
    		if (outType == 'sd') {
    			reportStorage = Tremol.Enums.OptionReportStorage.SD_card_storage;
    		}
    		
    		if (outType == 'usb') {
    			reportStorage = Tremol.Enums.OptionReportStorage.USB_storage;
    		}
			
    		fp.PrintOrStoreEJByDate(reportStorage, startDate, endDate);
    	}
    } catch(ex) {
        handleException(ex);
    }
};


/**
 * Отпечатва/записва CSV отчет за съответния период - с и без нулиране
 */
function fpOutputCSV(outType, startDate, endDate, csvFormat, flagReceipts, flagReports)
{
	try {
		var outTypeStr = Tremol.Enums.OptionStorageReport.To_PC;
		
		if (outType == 'sd') {
			outTypeStr = Tremol.Enums.OptionStorageReport.To_SD_card;
		} else if (outType == 'usb') {
			outTypeStr = Tremol.Enums.OptionStorageReport.To_USB_Flash_Drive;
		}
		
		var csvFormatStr = Tremol.Enums.OptionCSVformat.Yes;
		if (csvFormat == 'no') {
			csvFormatStr = Tremol.Enums.OptionCSVformat.No;
		}
		
		fp.ReadEJByDateCustom(outTypeStr, csvFormatStr, flagReceipts, flagReports, startDate, endDate);
		
		if (outType == 'pc') {
			printToPc();
		}
		
	} catch(ex) {
        handleException(ex);
    }
};


/**
 * Отпечатва/записва бележките по номер
 */
function fpPrintOrStoreEJByRcpNum(outType, startNum, endNum)
{
	try {
		var reportStorage = Tremol.Enums.OptionReportStorage.Printing;
		
		if (outType == 'sd') {
			reportStorage = Tremol.Enums.OptionReportStorage.SD_card_storage;
		}
		
		if (outType == 'usb') {
			reportStorage = Tremol.Enums.OptionReportStorage.USB_storage;
		}
		
		fp.PrintOrStoreEJByRcpNum(reportStorage, startNum, endNum);
	} catch(ex) {
        handleException(ex);
    }
};


/**
 * Помощна фунцкия за отпечатване на резултата в екрана
 */
function printToPc()
{
	var lRes = fp.RawRead(0, "@");
	var string = new TextDecoder("windows-1251").decode(lRes);
	splitStr = string.split("\n");
	resStr = "";
	splitStr.forEach(function(l) { 
	    resStr += "<tr><td>" + l.substring(4, l.length - 2) + "</td></tr>";
	});
	resStr = resStr.trim();
	resStr = resStr.replace(/\t/g, '</td><td>');
	
	resStr = "<table>" + resStr + "</table>";
	
	openWindow('', 'klenReport', 'width=1000,height=700');
	var popWin = popupWindows['klenReport'];
	
	if (popWin) {
		popWin.document.documentElement.innerHTML = resStr;
	} else {
		$('.tab-page').append(resStr);
	}
}


/**
 * Обработвач на съобщеният за грешки
 * 
 * @param exception sx
 * 
 * @return null|exception
 */
function handleException(sx) {

	var msg = '';
	
	if (sx instanceof Tremol.ServerError) {
		if (sx.isFpException) {
			/**
			Possible reasons:  
			sx.STE1 =                                              sx.STE2 =
			    0x30 OK                                                   0x30 OK                                 
			    0x31 Out of paper, printer failure                        0x31 Invalid command
			    0x32 Registers overflow                                   0x32 Illegal command
			    0x33 Clock failure or incorrect date&time                 0x33 Z daily report is not zero
			    0x34 Opened fiscal receipt                                0x34 Syntax error
			    0x35 Payment residue account                              0x35 Input registers overflow
			    0x36 Opened non-fiscal receipt                            0x36 Zero input registers
			    0x37 Registered payment but receipt is not closed         0x37 Unavailable transaction for correction
			    0x38 Fiscal memory failure                                0x38 Insufficient amount on hand
			    0x39 Incorrect password                                   0x3A No access
			    0x3a Missing external display
			    0x3b 24hours block – missing Z report
			    0x3c Overheated printer thermal head.
			    0x3d Interrupt power supply in fiscal receipt (one time until status is read)
			    0x3e Overflow EJ
			    0x3f Insufficient conditions
			**/
			if (sx.ste1 === 0x30 && sx.ste2 === 0x32) {
				msg = "sx.STE1 === 0x30 - Командата е ОК и sx.STE2 === 0x32 - Командата е непозволена в текущото състояние на ФУ";
			} else if (sx.ste1 === 0x30 && sx.ste2 === 0x33) {
				msg = "sx.STE1 === 0x30 - Командата е ОК и sx.STE2 === 0x33 - Направете Z отчет";
			} else if (sx.ste1 === 0x34 && sx.ste2 === 0x32) {
				msg = "sx.STE1 === 0x34 - Отворен фискален бон и sx.STE2 === 0x32 - Командата е непозволена в текущото състояние на ФУ";
			} else if (sx.ste1 === 0x39 && sx.ste2 === 0x32) {
				msg = "sx.STE1 === 0x39 - Грешна парола и sx.STE2 === 0x32 - Командата е непозволена";
			} else {
				msg = sx.message + "\nSTE1=" + sx.ste1 + ", STE2=" + sx.ste2;
			}
		} else if (sx.type === Tremol.ServerErrorType.ServerDefsMismatch) {
			msg = "Текущата версия на библиотеката и сървърните дефиниции се различават.";
		} else if (sx.type === Tremol.ServerErrorType.ServMismatchBetweenDefinitionAndFPResult) {
			msg = "Текущата версия на библиотеката и фърмуера на ФУ са несъвместими";
		} else if (sx.type === Tremol.ServerErrorType.ServerAddressNotSet) {
			msg = "Не е зададен адрес на сървъра!";
		} else if (sx.type === Tremol.ServerErrorType.ServerConnectionError) {
			msg = "Не може да се осъществи връзка със ZfpLab сървъра";
		} else if (sx.type === Tremol.ServerErrorType.ServSockConnectionFailed) {
			msg = "Сървърът не може да се свърже с ФУ";
		} else if (sx.type === Tremol.ServerErrorType.ServTCPAuth) {
			msg = "Грешна TCP парола на устройството";
		} else if (sx.type === Tremol.ServerErrorType.ServWaitOtherClientCmdProcessingTimeOut) {
			msg = "Обработката на другите клиенти на сървъра отнема много време";
		} else {
			msg = sx.message;
		}
	} else {
		msg = sx.message;
	}
	
	if (msg) {
		throw new Error(msg);
	}
};
