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
            throw new Error("Текущата версия на библиотеката и сървърните дефиниции се различават!");
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
	if ((operNum < 1) || (operNum > 20)) {
		throw new Error("Номер на оператор може да е от 1 до 20");
	}
	
	if (operPass.length > 6) {
		throw new Error("Паролата на оператора не трябва да е над 6 символа");
	}
	
	var receiptFormat = Tremol.Enums.OptionReceiptFormat.Brief;
	if (isDetailed) {
		receiptFormat = Tremol.Enums.OptionReceiptFormat.Detailed;
	}
	
	var printVat = Tremol.Enums.OptionPrintVAT.No;
	if (isPrintVat) {
		printVat = Tremol.Enums.OptionPrintVAT.Yes;
	}
	
	if (printTypeStr == 'postponed') {
		var printType = Tremol.Enums.OptionFiscalRcpPrintType.Postponed_printing;
	} else if (printTypeStr == 'buffered') {
		var printType = Tremol.Enums.OptionFiscalRcpPrintType.Buffered_printing;
	} else if (printTypeStr == 'stepByStep') {
		var printType = Tremol.Enums.OptionFiscalRcpPrintType.Step_by_step_printing;
	} else {
		throw new Error("Непозволен тип за принтиране");
	}
	
	if (!rcpNum.match(/[a-z0-9]{8}-[a-z0-9]{4}-[0-9]{7}/gi)) {
		throw new Error("Невалиден номер за касаво бележка");
	}
	
    try {
        fp.OpenReceipt(operNum, operPass, receiptFormat, printVat, printType, rcpNum);
    } catch(ex) {
        handleException(ex);
    }
};


/**
 * Добавя артикул към бележката
 * 
 * @param name
 * @param vatClass
 * @param price
 * @param qty
 * @param discAddP
 * @param discAddV
 */
function fpSalePLU(name, vatClass, price, qty, discAddP, discAddV)
{
	if (name) {
		name = name.substring(0, 32);
	}
	
	if ((vatClass < 0) || (vatClass > 3)) {
		throw new Error("Непозволен клас за VAT");
	}
	
    try {
        fp.SellPLUwithSpecifiedVAT(name, Tremol.Enums.OptionVATClass['VAT_Class_' + vatClass], price, qty, discAddP, discAddV);
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
 * Отпечатва X отчета
 */
function fpXReport()
{
    try {
        fp.PrintDailyReport(Tremol.Enums.OptionZeroing.Without_zeroing);
    } catch(ex) {
        handleException(ex);
    }
};


/**
 * Отпечатва Z отчета
 */
function fpZReport()
{
    try {
        fp.PrintDailyReport(Tremol.Enums.OptionZeroing.Zeroing);
    } catch(ex) {
        handleException(ex);
    }
};


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
			msg = "Сървъра не може да се свърже с ФУ";
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
