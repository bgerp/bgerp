var Tremol = Tremol || { };
Tremol.FP = Tremol.FP || function () { };
Tremol.FP.prototype.timeStamp = 2308011616;
/**
 * @typedef {Object} DailyAvailableAmountsRes
 * @property {number} AmountPayment0 Up to 13 symbols for the accumulated amount by payment type 0
 * @property {number} AmountPayment1 Up to 13 symbols for the accumulated amount by payment type 1
 * @property {number} AmountPayment2 Up to 13 symbols for the accumulated amount by payment type 2
 * @property {number} AmountPayment3 Up to 13 symbols for the accumulated amount by payment type 3
 * @property {number} AmountPayment4 Up to 13 symbols for the accumulated amount by payment type 4
 * @property {number} AmountPayment5 Up to 13 symbols for the accumulated amount by payment type 5
 * @property {number} AmountPayment6 Up to 13 symbols for the accumulated amount by payment type 6
 * @property {number} AmountPayment7 Up to 13 symbols for the accumulated amount by payment type 7
 * @property {number} AmountPayment8 Up to 13 symbols for the accumulated amount by payment type 8
 * @property {number} AmountPayment9 Up to 13 symbols for the accumulated amount by payment type 9
 * @property {number} AmountPayment10 Up to 13 symbols for the accumulated amount by payment type 10
 * @property {number} AmountPayment11 Up to 13 symbols for the accumulated amount by payment type 11
 */

/**
 * Provides information about the amounts on hand by type of payment.
 * @return {DailyAvailableAmountsRes}
 */
Tremol.FP.prototype.ReadDailyAvailableAmounts = function () {
	return this.do('ReadDailyAvailableAmounts');
};

/**
 * Program weight barcode format.
 * @param {Tremol.Enums.OptionBarcodeFormat} OptionBarcodeFormat 1 symbol with value: 
 - '0' - NNNNcWWWWW 
 - '1' - NNNNNWWWWW
 */
Tremol.FP.prototype.ProgramWeightBarcodeFormat = function (OptionBarcodeFormat) {
	return this.do('ProgramWeightBarcodeFormat', 'OptionBarcodeFormat', OptionBarcodeFormat);
};

/**
 * @typedef {Object} DailyPO_OldRes
 * @property {number} AmountPayment0 Up to 13 symbols for the accumulated amount by payment type 0
 * @property {number} AmountPayment1 Up to 13 symbols for the accumulated amount by payment type 1
 * @property {number} AmountPayment2 Up to 13 symbols for the accumulated amount by payment type 2
 * @property {number} AmountPayment3 Up to 13 symbols for the accumulated amount by payment type 3
 * @property {number} AmountPayment4 Up to 13 symbols for the accumulated amount by payment type 4
 * @property {number} PONum Up to 5 symbols for the total number of operations
 * @property {number} SumAllPayment Up to 13 symbols to sum all payments
 */

/**
 * Provides information about the PO amounts by type of payment and the total number of operations. Command works for KL version 2 devices.
 * @return {DailyPO_OldRes}
 */
Tremol.FP.prototype.ReadDailyPO_Old = function () {
	return this.do('ReadDailyPO_Old');
};

/**
 * Prints an article report with or without zeroing ('Z' or 'X').
 * @param {Tremol.Enums.OptionZeroing} OptionZeroing with following values: 
 - 'Z' - Zeroing 
 - 'X' - Without zeroing
 */
Tremol.FP.prototype.PrintArticleReport = function (OptionZeroing) {
	return this.do('PrintArticleReport', 'OptionZeroing', OptionZeroing);
};

/**
 * Provides information about the current (the last value stored into the FM) decimal point format.
 * @return {Tremol.Enums.OptionDecimalPointPosition}
 */
Tremol.FP.prototype.ReadDecimalPoint = function () {
	return this.do('ReadDecimalPoint');
};

/**
 * Starts session for reading electronic receipt by number with Base64 encoded BMP QR code.
 * @param {number} RcpNum 6 symbols with format ######
 */
Tremol.FP.prototype.ReadElectronicReceipt_QR_BMP = function (RcpNum) {
	return this.do('ReadElectronicReceipt_QR_BMP', 'RcpNum', RcpNum);
};

/**
 * Programs the number of POS, printing of logo, cash drawer opening, cutting permission, external display management mode, article report type, enable or disable currency in receipt, EJ font type and working operators counter.
 * @param {number} POSNum 4 symbols for number of POS in format ####
 * @param {Tremol.Enums.OptionPrintLogo} OptionPrintLogo 1 symbol of value: 
 - '1' - Yes 
 - '0' - No
 * @param {Tremol.Enums.OptionAutoOpenDrawer} OptionAutoOpenDrawer 1 symbol of value: 
 - '1' - Yes 
 - '0' - No
 * @param {Tremol.Enums.OptionAutoCut} OptionAutoCut 1 symbol of value: 
 - '1' - Yes 
 - '0' - No
 * @param {Tremol.Enums.OptionExternalDispManagement} OptionExternalDispManagement 1 symbol of value: 
 - '1' - Manual 
 - '0' - Auto
 * @param {Tremol.Enums.OptionArticleReportType} OptionArticleReportType 1 symbol of value: 
 - '1' - Detailed 
 - '0' - Brief
 * @param {Tremol.Enums.OptionEnableCurrency} OptionEnableCurrency 1 symbol of value: 
 - '1' - Yes 
 - '0' - No
 * @param {Tremol.Enums.OptionEJFontType} OptionEJFontType 1 symbol of value: 
 - '1' - Low Font 
 - '0' - Normal Font
 * @param {Tremol.Enums.OptionWorkOperatorCount} OptionWorkOperatorCount 1 symbol of value: 
 - '1' - One 
 - '0' - More
 */
Tremol.FP.prototype.ProgParameters = function (POSNum, OptionPrintLogo, OptionAutoOpenDrawer, OptionAutoCut, OptionExternalDispManagement, OptionArticleReportType, OptionEnableCurrency, OptionEJFontType, OptionWorkOperatorCount) {
	return this.do('ProgParameters', 'POSNum', POSNum, 'OptionPrintLogo', OptionPrintLogo, 'OptionAutoOpenDrawer', OptionAutoOpenDrawer, 'OptionAutoCut', OptionAutoCut, 'OptionExternalDispManagement', OptionExternalDispManagement, 'OptionArticleReportType', OptionArticleReportType, 'OptionEnableCurrency', OptionEnableCurrency, 'OptionEJFontType', OptionEJFontType, 'OptionWorkOperatorCount', OptionWorkOperatorCount);
};

/**
 * Print Electronic Journal Report from receipt number to receipt number and selected documents content. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.
 * @param {string} FlagsReceipts 1 symbol for Receipts included in EJ: 
Flags.7=0 
Flags.6=1 
Flags.5=1 Yes, Flags.5=0 No (Include PO) 
Flags.4=1 Yes, Flags.4=0 No (Include RA) 
Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) 
Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) 
Flags.1=1 Yes, Flags.1=0 No (Include Invoice) 
Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)
 * @param {string} FlagsReports 1 symbol for Reports included in EJ: 
Flags.7=0 
Flags.6=1 
Flags.5=0 
Flags.4=1 Yes, Flags.4=0 No (Include FM reports) 
Flags.3=1 Yes, Flags.3=0 No (Include Other reports) 
Flags.2=1 Yes, Flags.2=0 No (Include Daily X) 
Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) 
Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)
 * @param {number} StartRcpNum 6 symbols for initial receipt number included in report in format ######.
 * @param {number} EndRcpNum 6 symbols for final receipt number included in report in format ######.
 */
Tremol.FP.prototype.PrintEJByRcpNumCustom = function (FlagsReceipts, FlagsReports, StartRcpNum, EndRcpNum) {
	return this.do('PrintEJByRcpNumCustom', 'FlagsReceipts', FlagsReceipts, 'FlagsReports', FlagsReports, 'StartRcpNum', StartRcpNum, 'EndRcpNum', EndRcpNum);
};

/**
 * Start LAN test on the device and print out the result
 */
Tremol.FP.prototype.StartTest_Lan = function () {
	return this.do('StartTest_Lan');
};

/**
 * @typedef {Object} DepartmentRes
 * @property {number} DepNum 3 symbols for department number in format ###
 * @property {string} DepName 20 symbols for department name
 * @property {Tremol.Enums.OptionVATClass} OptionVATClass 1 character for VAT class: 
 - 'А' - VAT Class 0 
 - 'Б' - VAT Class 1 
 - 'В' - VAT Class 2 
 - 'Г' - VAT Class 3 
 - 'Д' - VAT Class 4 
 - 'Е' - VAT Class 5 
 - 'Ж' - VAT Class 6 
 - 'З' - VAT Class 7 
 - '*' - Forbidden
 * @property {number} Turnover Up to 13 symbols for accumulated turnover of the article
 * @property {number} SoldQuantity Up to 13 symbols for sold quantity of the department
 * @property {number} LastZReportNumber Up to 5 symbols for the number of last Z Report
 * @property {Date} LastZReportDate 16 symbols for date and hour on last Z Report in format  
"DD-MM-YYYY HH:MM"
 */

/**
 * Provides information for the programmed data, the turnover from the stated department number
 * @param {number} DepNum 3 symbols for department number in format ###
 * @return {DepartmentRes}
 */
Tremol.FP.prototype.ReadDepartment = function (DepNum) {
	return this.do('ReadDepartment', 'DepNum', DepNum);
};

/**
 * Provide information about parameter for automatic transfer of daily available amounts.
 * @return {string}
 */
Tremol.FP.prototype.ReadTransferAmountParam_RA = function () {
	return this.do('ReadTransferAmountParam_RA');
};

/**
 * Opens an electronic fiscal invoice receipt with 1 minute timeout assigned to the specified operator number and operator password with free info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.
 * @param {number} OperNum Symbol from 1 to 20 corresponding to operator's number
 * @param {string} OperPass 6 symbols for operator's password
 * @param {string} Recipient 26 symbols for Invoice recipient
 * @param {string} Buyer 16 symbols for Invoice buyer
 * @param {string} VATNumber 13 symbols for customer Fiscal number
 * @param {string} UIC 13 symbols for customer Unique Identification Code
 * @param {string} Address 30 symbols for Address
 * @param {Tremol.Enums.OptionUICType} OptionUICType 1 symbol for type of Unique Identification Code:  
 - '0' - Bulstat 
 - '1' - EGN 
 - '2' - Foreigner Number 
 - '3' - NRA Official Number
 * @param {string=} UniqueReceiptNumber Up to 24 symbols for unique receipt number. 
NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: 
* ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, 
* ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, 
* YYYYYYY - 7 symbols [0-9] for next number of the receipt
 */
Tremol.FP.prototype.OpenElectronicInvoiceWithFreeCustomerData = function (OperNum, OperPass, Recipient, Buyer, VATNumber, UIC, Address, OptionUICType, UniqueReceiptNumber) {
	return this.do('OpenElectronicInvoiceWithFreeCustomerData', 'OperNum', OperNum, 'OperPass', OperPass, 'Recipient', Recipient, 'Buyer', Buyer, 'VATNumber', VATNumber, 'UIC', UIC, 'Address', Address, 'OptionUICType', OptionUICType, 'UniqueReceiptNumber', UniqueReceiptNumber);
};

/**
 * Read the total counter of last issued receipt.
 * @return {number}
 */
Tremol.FP.prototype.ReadLastReceiptNum = function () {
	return this.do('ReadLastReceiptNum');
};

/**
 * Stores the Unique Identification Code (UIC) and UIC type into the operative memory.
 * @param {string} Password 6-symbols string
 * @param {string} UIC 13 symbols for UIC
 * @param {Tremol.Enums.OptionUICType} OptionUICType 1 symbol for type of UIC number:  
 - '0' - Bulstat 
 - '1' - EGN 
 - '2' - Foreigner Number 
 - '3' - NRA Official Number
 */
Tremol.FP.prototype.SetCustomerUIC = function (Password, UIC, OptionUICType) {
	return this.do('SetCustomerUIC', 'Password', Password, 'UIC', UIC, 'OptionUICType', OptionUICType);
};

/**
 * Read Electronic Journal Report from receipt number to receipt number.
 * @param {Tremol.Enums.OptionReportFormat} OptionReportFormat 1 character with value 
 - 'J0' - Detailed EJ 
 - 'J8' - Brief EJ
 * @param {number} StartRcpNum 6 symbols for initial receipt number included in report in format ######
 * @param {number} EndRcpNum 6 symbols for final receipt number included in report in format ######
 */
Tremol.FP.prototype.ReadEJByReceiptNum = function (OptionReportFormat, StartRcpNum, EndRcpNum) {
	return this.do('ReadEJByReceiptNum', 'OptionReportFormat', OptionReportFormat, 'StartRcpNum', StartRcpNum, 'EndRcpNum', EndRcpNum);
};

/**
 * Programs the general data for a certain article in the internal database. The price may have variable length, while the name field is fixed.
 * @param {number} PLUNum 5 symbols for article number in format: #####
 * @param {string} Name 34 symbols for article name
 * @param {number} Price Up to 10 symbols for article price
 * @param {Tremol.Enums.OptionPrice} OptionPrice 1 symbol for price flag with next value: 
 - '0'- Free price is disable valid only programmed price 
 - '1'- Free price is enable 
 - '2'- Limited price
 * @param {Tremol.Enums.OptionVATClass} OptionVATClass 1 character for VAT class: 
 - 'А' - VAT Class 0 
 - 'Б' - VAT Class 1 
 - 'В' - VAT Class 2 
 - 'Г' - VAT Class 3 
 - 'Д' - VAT Class 4 
 - 'Е' - VAT Class 5 
 - 'Ж' - VAT Class 6 
 - 'З' - VAT Class 7 
 - '*' - Forbidden
 * @param {number} BelongToDepNum BelongToDepNum + 80h, 1 symbol for article 
department attachment, formed in the following manner: 
BelongToDepNum[HEX] + 80h example: Dep01 = 81h, Dep02 = 82h … 
Dep19 = 93h 
Department range from 1 to 127
 * @param {Tremol.Enums.OptionSingleTransaction} OptionSingleTransaction 1 symbol with value: 
 - '0' - Inactive, default value 
 - '1' - Active Single transaction in receipt
 */
Tremol.FP.prototype.ProgPLUgeneral = function (PLUNum, Name, Price, OptionPrice, OptionVATClass, BelongToDepNum, OptionSingleTransaction) {
	return this.do('ProgPLUgeneral', 'PLUNum', PLUNum, 'Name', Name, 'Price', Price, 'OptionPrice', OptionPrice, 'OptionVATClass', OptionVATClass, 'BelongToDepNum', BelongToDepNum, 'OptionSingleTransaction', OptionSingleTransaction);
};

/**
 * Percent or value discount/addition over sum of transaction or over subtotal sum specified by field "Type".
 * @param {Tremol.Enums.OptionType} OptionType 1 symbol with value  
- '2' - Defined from the device  
- '1' - Over subtotal 
- '0' - Over transaction sum
 * @param {Tremol.Enums.OptionSubtotal} OptionSubtotal 1 symbol with value  
 - '1' - Yes  
 - '0' - No
 * @param {number=} DiscAddV Up to 8 symbols for the value of the discount/addition. 
Use minus sign '-' for discount
 * @param {number=} DiscAddP Up to 7 symbols for the percentage value of the 
discount/addition. Use minus sign '-' for discount
 */
Tremol.FP.prototype.PrintDiscountOrAddition = function (OptionType, OptionSubtotal, DiscAddV, DiscAddP) {
	return this.do('PrintDiscountOrAddition', 'OptionType', OptionType, 'OptionSubtotal', OptionSubtotal, 'DiscAddV', DiscAddV, 'DiscAddP', DiscAddP);
};

/**
 * Preprogram the name of the type of payment. Command works for KL version 2 devices.
 * @param {Tremol.Enums.OptionNumber} OptionNumber 1 symbol for payment type  
 - '1' - Payment 1 
 - '2' - Payment 2 
 - '3' - Payment 3 
 - '4' - Payment 4
 * @param {string} Name 10 symbols for payment type name. Only the first 6 are printable and only 
relevant for CodePayment '9' and ':'
 * @param {number} Rate Up to 10 symbols for exchange rate in format: ####.##### 
of the 4th payment type, maximal value 0420.00000
 * @param {Tremol.Enums.OptionCodePayment} OptionCodePayment 1 symbol for code payment type with name: 
 - '1' - Check  
 - '2' - Talon 
 - '3' - V. Talon 
 - '4' - Packaging 
 - '5' - Service 
 - '6' - Damage 
 - '7' - Card 
 - '8' - Bank 
 - '9' - Programming Name1 
 - ':' - Programming Name2
 */
Tremol.FP.prototype.ProgPayment_Old = function (OptionNumber, Name, Rate, OptionCodePayment) {
	return this.do('ProgPayment_Old', 'OptionNumber', OptionNumber, 'Name', Name, 'Rate', Rate, 'OptionCodePayment', OptionCodePayment);
};

/**
 * Print or store Electronic Journal report with all documents.
 * @param {Tremol.Enums.OptionReportStorage} OptionReportStorage 1 character with value: 
 - 'J1' - Printing 
 - 'J2' - USB storage 
 - 'J4' - SD card storage
 */
Tremol.FP.prototype.PrintOrStoreEJ = function (OptionReportStorage) {
	return this.do('PrintOrStoreEJ', 'OptionReportStorage', OptionReportStorage);
};

/**
 * Provide information about weight barcode format.
 * @return {Tremol.Enums.OptionBarcodeFormat}
 */
Tremol.FP.prototype.ReadWeightBarcodeFormat = function () {
	return this.do('ReadWeightBarcodeFormat');
};

/**
 * Opens the cash drawer.
 */
Tremol.FP.prototype.CashDrawerOpen = function () {
	return this.do('CashDrawerOpen');
};

/**
 * @typedef {Object} PLU_OldRes
 * @property {number} PLUNum 5 symbols for article number format #####
 * @property {string} PLUName 20 symbols for article name
 * @property {number} Price Up to 11 symbols for article price
 * @property {Tremol.Enums.OptionVATClass} OptionVATClass 1 character for VAT class: 
 - 'А' - VAT Class 0 
 - 'Б' - VAT Class 1 
 - 'В' - VAT Class 2 
 - 'Г' - VAT Class 3 
 - 'Д' - VAT Class 4 
 - 'Е' - VAT Class 5 
 - 'Ж' - VAT Class 6 
 - 'З' - VAT Class 7 
 - '*' - Forbidden
 * @property {number} Turnover Up to 13 symbols for turnover by this article
 * @property {number} QuantitySold Up to 13 symbols for sold quantity
 * @property {number} LastZReportNumber Up to 5 symbols for the number of last Z Report
 * @property {Date} LastZReportDate 16 symbols for date and hour on last Z Report in format 
 DD-MM-YYYY HH:MM
 * @property {number} BelongToDepNumber BelongToDepNumber + 80h, 1 symbol for article department 
attachment, formed in the following manner: 
BelongToDepNumber[HEX] + 80h example: Dep01 = 81h, Dep02 
= 82h … Dep19 = 93h 
Department range from 1 to 127
 */

/**
 * Provides information about the registers of the specified article.
 * @param {number} PLUNum 5 symbols for article number in format #####
 * @return {PLU_OldRes}
 */
Tremol.FP.prototype.ReadPLU_Old = function (PLUNum) {
	return this.do('ReadPLU_Old', 'PLUNum', PLUNum);
};

/**
 * Print a detailed FM payments report by initial and end Z report number.
 * @param {number} StartZNum 4 symbols for initial FM report number included in report, format ####
 * @param {number} EndZNum 4 symbols for final FM report number included in report, format ####
 */
Tremol.FP.prototype.PrintDetailedFMPaymentsReportByZBlocks = function (StartZNum, EndZNum) {
	return this.do('PrintDetailedFMPaymentsReportByZBlocks', 'StartZNum', StartZNum, 'EndZNum', EndZNum);
};

/**
 * Program device's TCP password. To apply use - SaveNetworkSettings()
 * @param {number} PassLength Up to 3 symbols for the password len
 * @param {string} Password Up to 100 symbols for the TCP password
 */
Tremol.FP.prototype.SetTCPpassword = function (PassLength, Password) {
	return this.do('SetTCPpassword', 'PassLength', PassLength, 'Password', Password);
};

/**
 * Shows a 20-symbols text in the first line and last 20-symbols text in the second line of the external display lines.
 * @param {string} Text 40 symbols text
 */
Tremol.FP.prototype.DisplayTextLines1and2 = function (Text) {
	return this.do('DisplayTextLines1and2', 'Text', Text);
};

/**
 * Register the sell or correction with specified quantity of article from the internal FD database. The FD will perform a correction operation only if the same quantity of the article has already been sold.
 * @param {Tremol.Enums.OptionSign} OptionSign 1 symbol with optional value: 
 - '+' -Sale 
 - '-' - Correction
 * @param {number} PLUNum 5 symbols for PLU number of FD's database in format #####
 * @param {number=} Price Up to 10 symbols for sale price
 * @param {number=} Quantity Up to 10 symbols for article's quantity sold
 * @param {number=} DiscAddP Up to 7 for percentage of discount/addition. Use minus 
sign '-' for discount
 * @param {number=} DiscAddV Up to 8 symbolsfor percentage of discount/addition. 
Use minus sign '-' for discount
 */
Tremol.FP.prototype.SellPLUFromFD_DB = function (OptionSign, PLUNum, Price, Quantity, DiscAddP, DiscAddV) {
	return this.do('SellPLUFromFD_DB', 'OptionSign', OptionSign, 'PLUNum', PLUNum, 'Price', Price, 'Quantity', Quantity, 'DiscAddP', DiscAddP, 'DiscAddV', DiscAddV);
};

/**
 * Provides information about the current date and time.
 * @return {Date}
 */
Tremol.FP.prototype.ReadDateTime = function () {
	return this.do('ReadDateTime');
};

/**
 * Register the payment in the receipt with specified type of payment and exact amount received.
 * @param {Tremol.Enums.OptionPaymentType} OptionPaymentType 1 symbol for payment type: 
 - '0' - Payment 0 
 - '1' - Payment 1 
 - '2' - Payment 2 
 - '3' - Payment 3 
 - '4' - Payment 4 
 - '5' - Payment 5 
 - '6' - Payment 6 
 - '7' - Payment 7 
 - '8' - Payment 8 
 - '9' - Payment 9 
 - '10' - Payment 10 
 - '11' - Payment 11
 */
Tremol.FP.prototype.PayExactSum = function (OptionPaymentType) {
	return this.do('PayExactSum', 'OptionPaymentType', OptionPaymentType);
};

/**
 * Start WiFi test on the device and print out the result
 */
Tremol.FP.prototype.StartTest_WiFi = function () {
	return this.do('StartTest_WiFi');
};

/**
 * Read the number of the remaining free records for Z-report in the Fiscal Memory.
 * @return {string}
 */
Tremol.FP.prototype.ReadFMfreeRecords = function () {
	return this.do('ReadFMfreeRecords');
};

/**
 * @typedef {Object} Bluetooth_PasswordRes
 * @property {number} PassLength (Length) Up to 3 symbols for the BT password length
 * @property {string} Password Up to 100 symbols for the BT password
 */

/**
 * Provides information about device's Bluetooth password.
 * @return {Bluetooth_PasswordRes}
 */
Tremol.FP.prototype.ReadBluetooth_Password = function () {
	return this.do('ReadBluetooth_Password');
};

/**
 * Available only if receipt is not closed. Void all sales in the receipt and close the fiscal receipt (Fiscal receipt, Invoice receipt, Storno receipt or Credit Note). If payment is started, then finish payment and close the receipt.
 */
Tremol.FP.prototype.CancelReceipt = function () {
	return this.do('CancelReceipt');
};

/**
 * Register the sell (for correction use minus sign in the price field) of article belonging to department with specified name, price, quantity and/or discount/addition on the transaction. The VAT of article got from department to which article belongs.
 * @param {string} NamePLU 36 symbols for article's name. 34 symbols are printed on paper. 
Symbol 0x7C '|' is new line separator.
 * @param {number} Price Up to 10 symbols for article's price. Use minus sign '-' for correction
 * @param {number=} Quantity Up to 10 symbols for quantity
 * @param {number=} DiscAddP Up to 7 symbols for percentage of discount/addition. 
Use minus sign '-' for discount
 * @param {number=} DiscAddV Up to 8 symbols for value of discount/addition. 
Use minus sign '-' for discount
 * @param {number=} DepNum 1 symbol for article department 
attachment, formed in the following manner; example: Dep01=81h, 
Dep02=82h … Dep19=93h 
Department range from 1 to 127
 */
Tremol.FP.prototype.SellPLUfromDep = function (NamePLU, Price, Quantity, DiscAddP, DiscAddV, DepNum) {
	return this.do('SellPLUfromDep', 'NamePLU', NamePLU, 'Price', Price, 'Quantity', Quantity, 'DiscAddP', DiscAddP, 'DiscAddV', DiscAddV, 'DepNum', DepNum);
};

/**
 * @typedef {Object} InvoiceRangeRes
 * @property {number} StartNum 10 symbols for start No with leading zeroes in format ##########
 * @property {number} EndNum 10 symbols for end No with leading zeroes in format ##########
 */

/**
 * Provide information about invoice start and end numbers range.
 * @return {InvoiceRangeRes}
 */
Tremol.FP.prototype.ReadInvoiceRange = function () {
	return this.do('ReadInvoiceRange');
};

/**
 * Print whole special FM events report.
 */
Tremol.FP.prototype.PrintSpecialEventsFMreport = function () {
	return this.do('PrintSpecialEventsFMreport');
};

/**
 * Provides information about device's idle timeout. This timeout is seconds in which the connection will be closed when there is an inactivity. This information is available if the device has LAN or WiFi. Maximal value - 7200, minimal value 0. 0 is for never close the connection.
 * @return {number}
 */
Tremol.FP.prototype.Read_IdleTimeout = function () {
	return this.do('Read_IdleTimeout');
};

/**
 * Open a fiscal storno receipt assigned to the specified operator number and operator password, parameters for receipt format, print VAT, printing type and parameters for the related storno receipt.
 * @param {number} OperNum Symbols from 1 to 20 corresponding to operator's 
number
 * @param {string} OperPass 6 symbols for operator's password
 * @param {Tremol.Enums.OptionReceiptFormat} OptionReceiptFormat 1 symbol with value: 
 - '1' - Detailed 
 - '0' - Brief
 * @param {Tremol.Enums.OptionPrintVAT} OptionPrintVAT 1 symbol with value:  
 - '1' - Yes 
 - '0' - No
 * @param {Tremol.Enums.OptionStornoRcpPrintType} OptionStornoRcpPrintType 1 symbol with value: 
- '@' - Step by step printing 
- 'B' - Postponed Printing 
- 'D' - Buffered Printing
 * @param {Tremol.Enums.OptionStornoReason} OptionStornoReason 1 symbol for reason of storno operation with value:  
- '0' - Operator error  
- '1' - Goods Claim or Goods return  
- '2' - Tax relief
 * @param {number} RelatedToRcpNum Up to 6 symbols for issued receipt number
 * @param {Date} RelatedToRcpDateTime 17 symbols for Date and Time of the issued receipt 
in format DD-MM-YY HH:MM:SS
 * @param {string} FMNum 8 symbols for number of the Fiscal Memory
 * @param {string=} RelatedToURN Up to 24 symbols for the issed receipt unique receipt number. 
NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: 
* ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, 
* ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, 
* YYYYYYY - 7 symbols [0-9] for next number of the receipt
 */
Tremol.FP.prototype.OpenStornoReceipt = function (OperNum, OperPass, OptionReceiptFormat, OptionPrintVAT, OptionStornoRcpPrintType, OptionStornoReason, RelatedToRcpNum, RelatedToRcpDateTime, FMNum, RelatedToURN) {
	return this.do('OpenStornoReceipt', 'OperNum', OperNum, 'OperPass', OperPass, 'OptionReceiptFormat', OptionReceiptFormat, 'OptionPrintVAT', OptionPrintVAT, 'OptionStornoRcpPrintType', OptionStornoRcpPrintType, 'OptionStornoReason', OptionStornoReason, 'RelatedToRcpNum', RelatedToRcpNum, 'RelatedToRcpDateTime', RelatedToRcpDateTime, 'FMNum', FMNum, 'RelatedToURN', RelatedToURN);
};

/**
 * Programs the operator's name and password.
 * @param {number} Number Symbols from '1' to '20' corresponding to operator's number
 * @param {string} Name 20 symbols for operator's name
 * @param {string} Password 6 symbols for operator's password
 */
Tremol.FP.prototype.ProgOperator = function (Number, Name, Password) {
	return this.do('ProgOperator', 'Number', Number, 'Name', Name, 'Password', Password);
};

/**
 * @typedef {Object} Payments_OldRes
 * @property {string} NamePaym0 6 symbols for payment name type 0
 * @property {string} NamePaym1 6 symbols for payment name type 1
 * @property {string} NamePaym2 6 symbols for payment name type 2
 * @property {string} NamePaym3 6 symbols for payment name type 3
 * @property {string} NamePaym4 6 symbols for payment name type 4
 * @property {number} ExRate Up to10 symbols for exchange rate of payment type 4 in format: ####.#####
 * @property {string} CodePaym0 1 symbol for code of payment 0 = 0xFF (currency in cash)
 * @property {string} CodePaym1 1 symbol for code of payment 1 (default value is '7')
 * @property {string} CodePaym2 1 symbol for code of payment 2 (default value is '1')
 * @property {string} CodePaym3 1 symbol for code of payment 3 (default value is '2')
 * @property {string} CodePaym4 1 symbol for code of payment 4 = 0xFF (currency in cash)
 */

/**
 * Provides information about all programmed types of payment. Command works for KL version 2 devices.
 * @return {Payments_OldRes}
 */
Tremol.FP.prototype.ReadPayments_Old = function () {
	return this.do('ReadPayments_Old');
};

/**
 * Print Electronic Journal Report by initial and end date, and selected documents content. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.
 * @param {string} FlagsReceipts 1 symbol for Receipts included in EJ: 
Flags.7=0 
Flags.6=1 
Flags.5=1 Yes, Flags.5=0 No (Include PO) 
Flags.4=1 Yes, Flags.4=0 No (Include RA) 
Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) 
Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) 
Flags.1=1 Yes, Flags.1=0 No (Include Invoice) 
Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)
 * @param {string} FlagsReports 1 symbol for Reports included in EJ: 
Flags.7=0 
Flags.6=1 
Flags.5=0 
Flags.4=1 Yes, Flags.4=0 No (Include FM reports) 
Flags.3=1 Yes, Flags.3=0 No (Include Other reports) 
Flags.2=1 Yes, Flags.2=0 No (Include Daily X) 
Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) 
Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)
 * @param {Date} StartRepFromDate 6 symbols for initial date in the DDMMYY format
 * @param {Date} EndRepFromDate 6 symbols for final date in the DDMMYY format
 */
Tremol.FP.prototype.PrintEJByDateCustom = function (FlagsReceipts, FlagsReports, StartRepFromDate, EndRepFromDate) {
	return this.do('PrintEJByDateCustom', 'FlagsReceipts', FlagsReceipts, 'FlagsReports', FlagsReports, 'StartRepFromDate', StartRepFromDate, 'EndRepFromDate', EndRepFromDate);
};

/**
 * Program device's Bluetooth password. To apply use - SaveNetworkSettings()
 * @param {number} PassLength Up to 3 symbols for the BT password len
 * @param {string} Password Up to 100 symbols for the BT password
 */
Tremol.FP.prototype.SetBluetooth_Password = function (PassLength, Password) {
	return this.do('SetBluetooth_Password', 'PassLength', PassLength, 'Password', Password);
};

/**
 * @typedef {Object} PLUqtyRes
 * @property {number} PLUNum 5 symbols for article number with leading zeroes in format #####
 * @property {number} AvailableQuantity Up to13 symbols for quantity in stock
 * @property {Tremol.Enums.OptionQuantityType} OptionQuantityType 1 symbol for Quantity flag with next value:  
- '0'- Availability of PLU stock is not monitored  
- '1'- Disable negative quantity  
- '2'- Enable negative quantity
 */

/**
 * Provides information about the quantity registers of the specified article.
 * @param {number} PLUNum 5 symbols for article number with leading zeroes in format: #####
 * @return {PLUqtyRes}
 */
Tremol.FP.prototype.ReadPLUqty = function (PLUNum) {
	return this.do('ReadPLUqty', 'PLUNum', PLUNum);
};

/**
 * Scan and print all available WiFi networks
 */
Tremol.FP.prototype.ScanAndPrintWiFiNetworks = function () {
	return this.do('ScanAndPrintWiFiNetworks');
};

/**
 * @typedef {Object} SerialAndFiscalNumsRes
 * @property {string} SerialNumber 8 symbols for individual number of the fiscal device
 * @property {string} FMNumber 8 symbols for individual number of the fiscal memory
 */

/**
 * Provides information about the manufacturing number of the fiscal device and FM number.
 * @return {SerialAndFiscalNumsRes}
 */
Tremol.FP.prototype.ReadSerialAndFiscalNums = function () {
	return this.do('ReadSerialAndFiscalNums');
};

/**
 * Registers cash received on account or paid out.
 * @param {number} OperNum Symbols from 1 to 20 corresponding to the operator's 
number
 * @param {string} OperPass 6 symbols for operator's password
 * @param {Tremol.Enums.OptionPayType} OptionPayType 1 symbol with value 
 - '0' - Cash 
 - '11' - Currency
 * @param {number} Amount Up to 10 symbols for the amount lodged. Use minus sign for withdrawn
 * @param {Tremol.Enums.OptionPrintAvailability=} OptionPrintAvailability 1 symbol with value: 
 - '0' - No 
 - '1' - Yes
 * @param {string=} Text TextLength-2 symbols. In the beginning and in the end of line symbol
 */
Tremol.FP.prototype.ReceivedOnAccount_PaidOut = function (OperNum, OperPass, OptionPayType, Amount, OptionPrintAvailability, Text) {
	return this.do('ReceivedOnAccount_PaidOut', 'OperNum', OperNum, 'OperPass', OperPass, 'OptionPayType', OptionPayType, 'Amount', Amount, 'OptionPrintAvailability', OptionPrintAvailability, 'Text', Text);
};

/**
 * After every change on Idle timeout, LAN/WiFi/GPRS usage, LAN/WiFi/TCP/GPRS password or TCP auto start networks settings this Save command needs to be execute.
 */
Tremol.FP.prototype.SaveNetworkSettings = function () {
	return this.do('SaveNetworkSettings');
};

/**
 * Executes the direct command .
 * @param {string} Input Raw request to FP
 * @return {string}
 */
Tremol.FP.prototype.DirectCommand = function (Input) {
	return this.do('DirectCommand', 'Input', Input);
};

/**
 * Reading Electronic Journal Report by number of Z report blocks.
 * @param {Tremol.Enums.OptionReportFormat} OptionReportFormat 1 character with value 
 - 'J0' - Detailed EJ 
 - 'J8' - Brief EJ
 * @param {number} StartNo 4 symbols for initial number report in format ####
 * @param {number} EndNo 4 symbols for final number report in format ####
 */
Tremol.FP.prototype.ReadEJByZBlocks = function (OptionReportFormat, StartNo, EndNo) {
	return this.do('ReadEJByZBlocks', 'OptionReportFormat', OptionReportFormat, 'StartNo', StartNo, 'EndNo', EndNo);
};

/**
 * @typedef {Object} DailyReturnedChangeAmountsByOperator_OldRes
 * @property {number} OperNum Symbols from 1 to 20 corresponding to operator's number
 * @property {number} ChangeAmountPayment0 Up to 13 symbols for amounts received by type of payment 0
 * @property {number} ChangeAmountPayment1 Up to 13 symbols for amounts received by type of payment 1
 * @property {number} ChangeAmountPayment2 Up to 13 symbols for amounts received by type of payment 2
 * @property {number} ChangeAmountPayment3 Up to 13 symbols for amounts received by type of payment 3
 * @property {number} ChangeAmountPayment4 Up to 13 symbols for amounts received by type of payment 4
 */

/**
 * Read the amounts returned as change by different payment types for the specified operator. Command works for KL version 2 devices.
 * @param {number} OperNum Symbol from 1 to 20 corresponding to operator's number
 * @return {DailyReturnedChangeAmountsByOperator_OldRes}
 */
Tremol.FP.prototype.ReadDailyReturnedChangeAmountsByOperator_Old = function (OperNum) {
	return this.do('ReadDailyReturnedChangeAmountsByOperator_Old', 'OperNum', OperNum);
};

/**
 * @typedef {Object} CurrentOrLastReceiptPaymentAmountsRes
 * @property {Tremol.Enums.OptionIsReceiptOpened} OptionIsReceiptOpened 1 symbol with value: 
 - '0' - No 
 - '1' - Yes
 * @property {number} Payment0Amount Up to 13 symbols for type 0 payment amount
 * @property {number} Payment1Amount Up to 13 symbols for type 1 payment amount
 * @property {number} Payment2Amount Up to 13 symbols for type 2 payment amount
 * @property {number} Payment3Amount Up to 13 symbols for type 3 payment amount
 * @property {number} Payment4Amount Up to 13 symbols for type 4 payment amount
 * @property {number} Payment5Amount Up to 13 symbols for type 5 payment amount
 * @property {number} Payment6Amount Up to 13 symbols for type 6 payment amount
 * @property {number} Payment7Amount Up to 13 symbols for type 7 payment amount
 * @property {number} Payment8Amount Up to 13 symbols for type 8 payment amount
 * @property {number} Payment9Amount Up to 13 symbols for type 9 payment amount
 * @property {number} Payment10Amount Up to 13 symbols for type 10 payment amount
 * @property {number} Payment11Amount Up to 13 symbols for type 11 payment amount
 */

/**
 * Provides information about the payments in current receipt. This command is valid after receipt closing also.
 * @return {CurrentOrLastReceiptPaymentAmountsRes}
 */
Tremol.FP.prototype.ReadCurrentOrLastReceiptPaymentAmounts = function () {
	return this.do('ReadCurrentOrLastReceiptPaymentAmounts');
};

/**
 * @typedef {Object} DailyReturnedChangeAmountsRes
 * @property {number} AmountPayment0 Up to 13 symbols for the accumulated amount by payment type 0
 * @property {number} AmountPayment1 Up to 13 symbols for the accumulated amount by payment type 1
 * @property {number} AmountPayment2 Up to 13 symbols for the accumulated amount by payment type 2
 * @property {number} AmountPayment3 Up to 13 symbols for the accumulated amount by payment type 3
 * @property {number} AmountPayment4 Up to 13 symbols for the accumulated amount by payment type 4
 * @property {number} AmountPayment5 Up to 13 symbols for the accumulated amount by payment type 5
 * @property {number} AmountPayment6 Up to 13 symbols for the accumulated amount by payment type 6
 * @property {number} AmountPayment7 Up to 13 symbols for the accumulated amount by payment type 7
 * @property {number} AmountPayment8 Up to 13 symbols for the accumulated amount by payment type 8
 * @property {number} AmountPayment9 Up to 13 symbols for the accumulated amount by payment type 9
 * @property {number} AmountPayment10 Up to 13 symbols for the accumulated amount by payment type 10
 * @property {number} AmountPayment11 Up to 13 symbols for the accumulated amount by payment type 11
 */

/**
 * Provides information about the amounts returned as change by type of payment.
 * @return {DailyReturnedChangeAmountsRes}
 */
Tremol.FP.prototype.ReadDailyReturnedChangeAmounts = function () {
	return this.do('ReadDailyReturnedChangeAmounts');
};

/**
 * @typedef {Object} DailyReceivedSalesAmountsByOperator_OldRes
 * @property {number} OperNum Symbols from 1 to 20 corresponding to operator's number
 * @property {number} ReceivedSalesAmountPayment0 Up to 13 symbols for amounts received by sales for payment 0
 * @property {number} ReceivedSalesAmountPayment1 Up to 13 symbols for amounts received by sales for payment 1
 * @property {number} ReceivedSalesAmountPayment2 Up to 13 symbols for amounts received by sales for payment 2
 * @property {number} ReceivedSalesAmountPayment3 Up to 13 symbols for amounts received by sales for payment 3
 * @property {number} ReceivedSalesAmountPayment4 Up to 13 symbols for amounts received by sales for payment 4
 */

/**
 * Read the amounts received from sales by type of payment and specified operator. Command works for KL version 2 devices.
 * @param {number} OperNum Symbols from 1 to 20 corresponding to operator's 
number
 * @return {DailyReceivedSalesAmountsByOperator_OldRes}
 */
Tremol.FP.prototype.ReadDailyReceivedSalesAmountsByOperator_Old = function (OperNum) {
	return this.do('ReadDailyReceivedSalesAmountsByOperator_Old', 'OperNum', OperNum);
};

/**
 * Print a brief FM Departments report by initial and end date.
 * @param {Date} StartDate 6 symbols for initial date in the DDMMYY format
 * @param {Date} EndDate 6 symbols for final date in the DDMMYY format
 */
Tremol.FP.prototype.PrintBriefFMDepartmentsReportByDate = function (StartDate, EndDate) {
	return this.do('PrintBriefFMDepartmentsReportByDate', 'StartDate', StartDate, 'EndDate', EndDate);
};

/**
 * @typedef {Object} DepartmentAllRes
 * @property {number} DepNum 3 symbols for department number in format ###
 * @property {string} DepName 20 symbols for department name
 * @property {Tremol.Enums.OptionVATClass} OptionVATClass 1 character for VAT class: 
 - 'А' - VAT Class 0 
 - 'Б' - VAT Class 1 
 - 'В' - VAT Class 2 
 - 'Г' - VAT Class 3 
 - 'Д' - VAT Class 4 
 - 'Е' - VAT Class 5 
 - 'Ж' - VAT Class 6 
 - 'З' - VAT Class 7 
 - '*' - Forbidden
 * @property {number} Price Up to 10 symbols for department price
 * @property {Tremol.Enums.OptionDepPrice} OptionDepPrice 1 symbol for Department flags with next value:  
- '0' - Free price disabled  
- '1' - Free price enabled  
- '2' - Limited price  
- '4' - Free price disabled for single transaction  
- '5' - Free price enabled for single transaction  
- '6' - Limited price for single transaction
 * @property {number} TurnoverAmount Up to 13 symbols for accumulated turnover of the article
 * @property {number} SoldQuantity Up to 13 symbols for sold quantity of the department
 * @property {number} StornoAmount Up to 13 symbols for accumulated storno amount
 * @property {number} StornoQuantity Up to 13 symbols for accumulated storno quantiy
 * @property {number} LastZReportNumber Up to 5 symbols for the number of last Z Report
 * @property {Date} LastZReportDate 16 symbols for date and hour on last Z Report in format  
"DD-MM-YYYY HH:MM"
 */

/**
 * Provides information for the programmed data, the turnovers from the stated department number
 * @param {number} DepNum 3 symbols for department number in format ###
 * @return {DepartmentAllRes}
 */
Tremol.FP.prototype.ReadDepartmentAll = function (DepNum) {
	return this.do('ReadDepartmentAll', 'DepNum', DepNum);
};

/**
 * Print a brief FM payments report by initial and end FM report number.
 * @param {number} StartZNum 4 symbols for the initial FM report number included in report, format ####
 * @param {number} EndZNum 4 symbols for the final FM report number included in report, format ####
 */
Tremol.FP.prototype.PrintBriefFMPaymentsReportByZBlocks = function (StartZNum, EndZNum) {
	return this.do('PrintBriefFMPaymentsReportByZBlocks', 'StartZNum', StartZNum, 'EndZNum', EndZNum);
};

/**
 * Print a brief FM payments report by initial and end date.
 * @param {Date} StartDate 6 symbols for initial date in the DDMMYY format
 * @param {Date} EndDate 6 symbols for final date in the DDMMYY format
 */
Tremol.FP.prototype.PrintBriefFMPaymentsReportByDate = function (StartDate, EndDate) {
	return this.do('PrintBriefFMPaymentsReportByDate', 'StartDate', StartDate, 'EndDate', EndDate);
};

/**
 * Program device's WiFi network name where it will connect. To apply use -SaveNetworkSettings()
 * @param {number} WiFiNameLength Up to 3 symbols for the WiFi network name len
 * @param {string} WiFiNetworkName Up to 100 symbols for the device's WiFi ssid network name
 */
Tremol.FP.prototype.SetWiFi_NetworkName = function (WiFiNameLength, WiFiNetworkName) {
	return this.do('SetWiFi_NetworkName', 'WiFiNameLength', WiFiNameLength, 'WiFiNetworkName', WiFiNetworkName);
};

/**
 * Program customer in FD data base.
 * @param {number} CustomerNum 4 symbols for customer number in format ####
 * @param {string} CustomerCompanyName 26 symbols for customer name
 * @param {string} CustomerFullName 16 symbols for Buyer name
 * @param {string} VATNumber 13 symbols for VAT number on customer
 * @param {string} UIC 13 symbols for customer Unique Identification Code
 * @param {string} Address 30 symbols for address on customer
 * @param {Tremol.Enums.OptionUICType} OptionUICType 1 symbol for type of Unique Identification Code:  
 - '0' - Bulstat 
 - '1' - EGN 
 - '2' - Foreigner Number 
 - '3' - NRA Official Number
 */
Tremol.FP.prototype.ProgCustomerData = function (CustomerNum, CustomerCompanyName, CustomerFullName, VATNumber, UIC, Address, OptionUICType) {
	return this.do('ProgCustomerData', 'CustomerNum', CustomerNum, 'CustomerCompanyName', CustomerCompanyName, 'CustomerFullName', CustomerFullName, 'VATNumber', VATNumber, 'UIC', UIC, 'Address', Address, 'OptionUICType', OptionUICType);
};

/**
 * Register the sell (for correction use minus sign in the price field) of article with specified name, price, quantity, VAT class and/or discount/addition on the transaction.
 * @param {string} NamePLU 36 symbols for article's name. 34 symbols are printed on paper. 
Symbol 0x7C '|' is new line separator.
 * @param {Tremol.Enums.OptionVATClass} OptionVATClass 1 character for VAT class: 
 - 'А' - VAT Class 0 
 - 'Б' - VAT Class 1 
 - 'В' - VAT Class 2 
 - 'Г' - VAT Class 3 
 - 'Д' - VAT Class 4 
 - 'Е' - VAT Class 5 
 - 'Ж' - VAT Class 6 
 - 'З' - VAT Class 7 
 - '*' - Forbidden
 * @param {number} Price Up to 10 symbols for article's price. Use minus sign '-' for correction
 * @param {number=} Quantity Up to 10 symbols for quantity
 * @param {number=} DiscAddP Up to 7 symbols for percentage of discount/addition. 
Use minus sign '-' for discount
 * @param {number=} DiscAddV Up to 8 symbols for value of discount/addition. 
Use minus sign '-' for discount
 */
Tremol.FP.prototype.SellPLUwithSpecifiedVAT = function (NamePLU, OptionVATClass, Price, Quantity, DiscAddP, DiscAddV) {
	return this.do('SellPLUwithSpecifiedVAT', 'NamePLU', NamePLU, 'OptionVATClass', OptionVATClass, 'Price', Price, 'Quantity', Quantity, 'DiscAddP', DiscAddP, 'DiscAddV', DiscAddV);
};

/**
 * Read a detailed FM Departments report by initial and end Z report number.
 * @param {number} StartZNum 4 symbols for initial FM report number included in report, format ####
 * @param {number} EndZNum 4 symbols for final FM report number included in report, format ####
 */
Tremol.FP.prototype.ReadDetailedFMDepartmentsReportByZBlocks = function (StartZNum, EndZNum) {
	return this.do('ReadDetailedFMDepartmentsReportByZBlocks', 'StartZNum', StartZNum, 'EndZNum', EndZNum);
};

/**
 * Print a brief FM report by initial and end date.
 * @param {Date} StartDate 6 symbols for initial date in the DDMMYY format
 * @param {Date} EndDate 6 symbols for final date in the DDMMYY format
 */
Tremol.FP.prototype.PrintBriefFMReportByDate = function (StartDate, EndDate) {
	return this.do('PrintBriefFMReportByDate', 'StartDate', StartDate, 'EndDate', EndDate);
};

/**
 * Shows a 20-symbols text in the upper external display line.
 * @param {string} Text 20 symbols text
 */
Tremol.FP.prototype.DisplayTextLine1 = function (Text) {
	return this.do('DisplayTextLine1', 'Text', Text);
};

/**
 * @typedef {Object} VATratesRes
 * @property {number} VATrate0 Value of VAT rate А from 7 symbols in format ##.##%
 * @property {number} VATrate1 Value of VAT rate Б from 7 symbols in format ##.##%
 * @property {number} VATrate2 Value of VAT rate В from 7 symbols in format ##.##%
 * @property {number} VATrate3 Value of VAT rate Г from 7 symbols in format ##.##%
 * @property {number} VATrate4 Value of VAT rate Д from 7 symbols in format ##.##%
 * @property {number} VATrate5 Value of VAT rate Е from 7 symbols in format ##.##%
 * @property {number} VATrate6 Value of VAT rate Ж from 7 symbols in format ##.##%
 * @property {number} VATrate7 Value of VAT rate З from 7 symbols in format ##.##%
 */

/**
 * Provides information about the current VAT rates which are the last values stored into the FM.
 * @return {VATratesRes}
 */
Tremol.FP.prototype.ReadVATrates = function () {
	return this.do('ReadVATrates');
};

/**
 * @typedef {Object} DailyReceivedSalesAmountsRes
 * @property {number} AmountPayment0 Up to 13 symbols for the accumulated amount by payment type 0
 * @property {number} AmountPayment1 Up to 13 symbols for the accumulated amount by payment type 1
 * @property {number} AmountPayment2 Up to 13 symbols for the accumulated amount by payment type 2
 * @property {number} AmountPayment3 Up to 13 symbols for the accumulated amount by payment type 3
 * @property {number} AmountPayment4 Up to 13 symbols for the accumulated amount by payment type 4
 * @property {number} AmountPayment5 Up to 13 symbols for the accumulated amount by payment type 5
 * @property {number} AmountPayment6 Up to 13 symbols for the accumulated amount by payment type 6
 * @property {number} AmountPayment7 Up to 13 symbols for the accumulated amount by payment type 7
 * @property {number} AmountPayment8 Up to 13 symbols for the accumulated amount by payment type 8
 * @property {number} AmountPayment9 Up to 13 symbols for the accumulated amount by payment type 9
 * @property {number} AmountPayment10 Up to 13 symbols for the accumulated amount by payment type 10
 * @property {number} AmountPayment11 Up to 13 symbols for the accumulated amount by payment type 11
 */

/**
 * Provides information about the amounts received from sales by type of payment.
 * @return {DailyReceivedSalesAmountsRes}
 */
Tremol.FP.prototype.ReadDailyReceivedSalesAmounts = function () {
	return this.do('ReadDailyReceivedSalesAmounts');
};

/**
 * Programs available quantity and Quantiy type for a certain article in the internal database.
 * @param {number} PLUNum 5 symbols for article number in format: #####
 * @param {number} AvailableQuantity Up to 11 symbols for available quantity in stock
 * @param {Tremol.Enums.OptionQuantityType} OptionQuantityType 1 symbol for Quantity flag with next value:  
 - '0'- Availability of PLU stock is not monitored  
 - '1'- Disable negative quantity  
 - '2'- Enable negative quantity
 */
Tremol.FP.prototype.ProgPLUqty = function (PLUNum, AvailableQuantity, OptionQuantityType) {
	return this.do('ProgPLUqty', 'PLUNum', PLUNum, 'AvailableQuantity', AvailableQuantity, 'OptionQuantityType', OptionQuantityType);
};

/**
 * @typedef {Object} RegistrationInfoRes
 * @property {string} UIC 13 symbols for Unique Identification Code
 * @property {Tremol.Enums.OptionUICType} OptionUICType 1 symbol for type of Unique Identification Code: 
 - '0' - Bulstat 
 - '1' - EGN 
 - '2' - Foreigner Number 
 - '3' - NRA Official Number
 * @property {string} NRARegistrationNumber Register number on the Fiscal device from NRA
 * @property {Date} NRARegistrationDate Date of registration in NRA
 */

/**
 * Provides information about the programmed VAT number, type of VAT number, register number in NRA and Date of registration in NRA.
 * @return {RegistrationInfoRes}
 */
Tremol.FP.prototype.ReadRegistrationInfo = function () {
	return this.do('ReadRegistrationInfo');
};

/**
 * Clears the external display.
 */
Tremol.FP.prototype.ClearDisplay = function () {
	return this.do('ClearDisplay');
};

/**
 * Programs the data for a certain article (item) in the internal database. The price may have variable length, while the name field is fixed.
 * @param {number} PLUNum 5 symbols for article number in format: #####
 * @param {string} Name 20 symbols for article name
 * @param {number} Price Up to 10 symbols for article price
 * @param {Tremol.Enums.OptionVATClass} OptionVATClass 1 character for VAT class: 
 - 'А' - VAT Class 0 
 - 'Б' - VAT Class 1 
 - 'В' - VAT Class 2 
 - 'Г' - VAT Class 3 
 - 'Д' - VAT Class 4 
 - 'Е' - VAT Class 5 
 - 'Ж' - VAT Class 6 
 - 'З' - VAT Class 7 
 - '*' - Forbidden
 * @param {number} BelongToDepNum BelongToDepNum + 80h, 1 symbol for article 
department attachment, formed in the following manner:
 */
Tremol.FP.prototype.ProgPLU_Old = function (PLUNum, Name, Price, OptionVATClass, BelongToDepNum) {
	return this.do('ProgPLU_Old', 'PLUNum', PLUNum, 'Name', Name, 'Price', Price, 'OptionVATClass', OptionVATClass, 'BelongToDepNum', BelongToDepNum);
};

/**
 * Register the sell (for correction use minus sign in the price field) of article with specified name, price, fractional quantity, VAT class and/or discount/addition on the transaction.
 * @param {string} NamePLU 36 symbols for article's name. 34 symbols are printed on paper. 
Symbol 0x7C '|' is new line separator.
 * @param {Tremol.Enums.OptionVATClass} OptionVATClass 1 character for VAT class: 
 - 'А' - VAT Class 0 
 - 'Б' - VAT Class 1 
 - 'В' - VAT Class 2 
 - 'Г' - VAT Class 3 
 - 'Д' - VAT Class 4 
 - 'Е' - VAT Class 5 
 - 'Ж' - VAT Class 6 
 - 'З' - VAT Class 7 
 - '*' - Forbidden
 * @param {number} Price Up to 10 symbols for article's price. Use minus sign '-' for correction
 * @param {string=} Quantity From 3 to 10 symbols for quantity in format fractional format, e.g. 1/3
 * @param {number=} DiscAddP 1 to 7 symbols for percentage of discount/addition. Use 
minus sign '-' for discount
 * @param {number=} DiscAddV 1 to 8 symbols for value of discount/addition. Use 
minus sign '-' for discount
 */
Tremol.FP.prototype.SellFractQtyPLUwithSpecifiedVAT = function (NamePLU, OptionVATClass, Price, Quantity, DiscAddP, DiscAddV) {
	return this.do('SellFractQtyPLUwithSpecifiedVAT', 'NamePLU', NamePLU, 'OptionVATClass', OptionVATClass, 'Price', Price, 'Quantity', Quantity, 'DiscAddP', DiscAddP, 'DiscAddV', DiscAddV);
};

/**
 * Starts session for reading electronic receipt by number with specified ASCII symbol for QR code block.
 * @param {number} RcpNum 6 symbols with format ######
 * @param {string} QRSymbol 1 symbol for QR code drawing image
 */
Tremol.FP.prototype.ReadElectronicReceipt_QR_ASCII = function (RcpNum, QRSymbol) {
	return this.do('ReadElectronicReceipt_QR_ASCII', 'RcpNum', RcpNum, 'QRSymbol', QRSymbol);
};

/**
 * Program device's TCP network DHCP enabled or disabled. To apply use -SaveNetworkSettings()
 * @param {Tremol.Enums.OptionDHCPEnabled} OptionDHCPEnabled 1 symbol with value: 
 - '0' - Disabled 
 - '1' - Enabled
 */
Tremol.FP.prototype.SetDHCP_Enabled = function (OptionDHCPEnabled) {
	return this.do('SetDHCP_Enabled', 'OptionDHCPEnabled', OptionDHCPEnabled);
};

/**
 * @typedef {Object} DailyPObyOperatorRes
 * @property {number} OperNum Symbols from 1 to 20 corresponding to operator's number
 * @property {number} AmountPO_Payment0 Up to 13 symbols for the PO by type of payment 0
 * @property {number} AmountPO_Payment1 Up to 13 symbols for the PO by type of payment 1
 * @property {number} AmountPO_Payment2 Up to 13 symbols for the PO by type of payment 2
 * @property {number} AmountPO_Payment3 Up to 13 symbols for the PO by type of payment 3
 * @property {number} AmountPO_Payment4 Up to 13 symbols for the PO by type of payment 4
 * @property {number} AmountPO_Payment5 Up to 13 symbols for the PO by type of payment 5
 * @property {number} AmountPO_Payment6 Up to 13 symbols for the PO by type of payment 6
 * @property {number} AmountPO_Payment7 Up to 13 symbols for the PO by type of payment 7
 * @property {number} AmountPO_Payment8 Up to 13 symbols for the PO by type of payment 8
 * @property {number} AmountPO_Payment9 Up to 13 symbols for the PO by type of payment 9
 * @property {number} AmountPO_Payment10 Up to 13 symbols for the PO by type of payment 10
 * @property {number} AmountPO_Payment11 Up to 13 symbols for the PO by type of payment 11
 * @property {number} NoPO 5 symbols for the total number of operations
 */

/**
 * Read the PO by type of payment and the total number of operations by specified operator
 * @param {number} OperNum Symbols from 1 to 20 corresponding to operator's number
 * @return {DailyPObyOperatorRes}
 */
Tremol.FP.prototype.ReadDailyPObyOperator = function (OperNum) {
	return this.do('ReadDailyPObyOperator', 'OperNum', OperNum);
};

/**
 * Opens an postponed electronic fiscal receipt with 1 minute timeout assigned to the specified operator number and operator password, parameters for receipt format, print VAT, printing type and unique receipt number.
 * @param {number} OperNum Symbols from 1 to 20 corresponding to operator's number
 * @param {string} OperPass 6 symbols for operator's password
 * @param {Tremol.Enums.OptionReceiptFormat} OptionReceiptFormat 1 symbol with value: 
 - '1' - Detailed 
 - '0' - Brief
 * @param {Tremol.Enums.OptionPrintVAT} OptionPrintVAT 1 symbol with value:  
 - '1' - Yes 
 - '0' - No
 * @param {string=} UniqueReceiptNumber Up to 24 symbols for unique receipt number. 
NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: 
* ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, 
* ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, 
* YYYYYYY - 7 symbols [0-9] for next number of the receipt
 */
Tremol.FP.prototype.OpenElectronicReceipt = function (OperNum, OperPass, OptionReceiptFormat, OptionPrintVAT, UniqueReceiptNumber) {
	return this.do('OpenElectronicReceipt', 'OperNum', OperNum, 'OperPass', OperPass, 'OptionReceiptFormat', OptionReceiptFormat, 'OptionPrintVAT', OptionPrintVAT, 'UniqueReceiptNumber', UniqueReceiptNumber);
};

/**
 * Program the content of the header UIC prefix.
 * @param {string} HeaderUICprefix 12 symbols for header UIC prefix
 */
Tremol.FP.prototype.ProgHeaderUICprefix = function (HeaderUICprefix) {
	return this.do('ProgHeaderUICprefix', 'HeaderUICprefix', HeaderUICprefix);
};

/**
 * Programs price and price type for a certain article in the internal database.
 * @param {number} PLUNum 5 symbols for article number in format: #####
 * @param {number} Price Up to 10 symbols for article price
 * @param {Tremol.Enums.OptionPrice} OptionPrice 1 symbol for price flag with next value: 
 - '0'- Free price is disable valid only programmed price 
 - '1'- Free price is enable 
 - '2'- Limited price
 */
Tremol.FP.prototype.ProgPLUprice = function (PLUNum, Price, OptionPrice) {
	return this.do('ProgPLUprice', 'PLUNum', PLUNum, 'Price', Price, 'OptionPrice', OptionPrice);
};

/**
 * @typedef {Object} DailyReceivedSalesAmounts_OldRes
 * @property {number} AmountPayment0 Up to 13 symbols for the accumulated amount by payment type 0
 * @property {number} AmountPayment1 Up to 13 symbols for the accumulated amount by payment type 1
 * @property {number} AmountPayment2 Up to 13 symbols for the accumulated amount by payment type 2
 * @property {number} AmountPayment3 Up to 13 symbols for the accumulated amount by payment type 3
 * @property {number} AmountPayment4 Up to 13 symbols for the accumulated amount by payment type 4
 */

/**
 * Provides information about the amounts received from sales by type of payment. Command works for KL version 2 devices.
 * @return {DailyReceivedSalesAmounts_OldRes}
 */
Tremol.FP.prototype.ReadDailyReceivedSalesAmounts_Old = function () {
	return this.do('ReadDailyReceivedSalesAmounts_Old');
};

/**
 *  Reads raw bytes from FP.
 * @param {number} Count How many bytes to read if EndChar is not specified
 * @param {string} EndChar The character marking the end of the data. If present Count parameter is ignored.
 * @return {Uint8Array}
 */
Tremol.FP.prototype.RawRead = function (Count, EndChar) {
	return this.do('RawRead', 'Count', Count, 'EndChar', EndChar);
};

/**
 * Program parameter for automatic transfer of daily available amounts.
 * @param {Tremol.Enums.OptionTransferAmount} OptionTransferAmount 1 symbol with value: 
 - '0' - No 
 - '1' - Yes
 */
Tremol.FP.prototype.ProgramTransferAmountParam_RA = function (OptionTransferAmount) {
	return this.do('ProgramTransferAmountParam_RA', 'OptionTransferAmount', OptionTransferAmount);
};

/**
 * Provides information about device's DHCP status
 * @return {Tremol.Enums.OptionDhcpStatus}
 */
Tremol.FP.prototype.ReadDHCP_Status = function () {
	return this.do('ReadDHCP_Status');
};

/**
 * @typedef {Object} TCP_AddressesRes
 * @property {Tremol.Enums.OptionAddressType} OptionAddressType (Address) 1 symbol with value: 
 - '2' - IP address 
 - '3' - Subnet Mask 
 - '4' - Gateway address 
 - '5' - DNS address
 * @property {string} DeviceAddress 15 symbols for the device's addresses
 */

/**
 * Provides information about device's IP address, subnet mask, gateway address, DNS address.
 * @param {Tremol.Enums.OptionAddressType} OptionAddressType 1 symbol with value: 
 - '2' - IP address 
 - '3' - Subnet Mask 
 - '4' - Gateway address 
 - '5' - DNS address
 * @return {TCP_AddressesRes}
 */
Tremol.FP.prototype.ReadTCP_Addresses = function (OptionAddressType) {
	return this.do('ReadTCP_Addresses', 'OptionAddressType', OptionAddressType);
};

/**
 * Provides information about the QR code data in last issued receipt.
 * @return {string}
 */
Tremol.FP.prototype.ReadLastReceiptQRcodeData = function () {
	return this.do('ReadLastReceiptQRcodeData');
};

/**
 * Program the contents of a header lines.
 * @param {Tremol.Enums.OptionHeaderLine} OptionHeaderLine 1 symbol with value: 
 - '1' - Header 1 
 - '2' - Header 2 
 - '3' - Header 3 
 - '4' - Header 4 
 - '5' - Header 5 
 - '6' - Header 6 
 - '7' - Header 7
 * @param {string} HeaderText TextLength symbols for header lines
 */
Tremol.FP.prototype.ProgHeader = function (OptionHeaderLine, HeaderText) {
	return this.do('ProgHeader', 'OptionHeaderLine', OptionHeaderLine, 'HeaderText', HeaderText);
};

/**
 * Sets logo number, which is active and will be printed as logo in the receipt header. Print information about active number.
 * @param {string} LogoNumber 1 character value from '0' to '9' or '?'. The number sets the active file, and 
the '?' invokes only printing of information
 */
Tremol.FP.prototype.SetActiveLogoNum = function (LogoNumber) {
	return this.do('SetActiveLogoNum', 'LogoNumber', LogoNumber);
};

/**
 * Closes the non-fiscal receipt.
 */
Tremol.FP.prototype.CloseNonFiscalReceipt = function () {
	return this.do('CloseNonFiscalReceipt');
};

/**
 * Removes all paired devices. To apply use -SaveNetworkSettings()
 */
Tremol.FP.prototype.UnpairAllDevices = function () {
	return this.do('UnpairAllDevices');
};

/**
 * Shows the current date and time on the external display.
 */
Tremol.FP.prototype.DisplayDateTime = function () {
	return this.do('DisplayDateTime');
};

/**
 * Set device's TCP autostart . To apply use -SaveNetworkSettings()
 * @param {Tremol.Enums.OptionTCPAutoStart} OptionTCPAutoStart 1 symbol with value: 
 - '0' - No 
 - '1' - Yes
 */
Tremol.FP.prototype.SetTCP_AutoStart = function (OptionTCPAutoStart) {
	return this.do('SetTCP_AutoStart', 'OptionTCPAutoStart', OptionTCPAutoStart);
};

/**
 * Provide information about NBL parameter to be monitored by the fiscal device.
 * @return {Tremol.Enums.OptionNBL}
 */
Tremol.FP.prototype.ReadNBLParameter = function () {
	return this.do('ReadNBLParameter');
};

/**
 * Print or store Electronic Journal Report by initial and end date.
 * @param {Tremol.Enums.OptionReportStorage} OptionReportStorage 1 character with value: 
 - 'J1' - Printing 
 - 'J2' - USB storage 
 - 'J4' - SD card storage
 * @param {Date} StartRepFromDate 6 symbols for initial date in the DDMMYY format
 * @param {Date} EndRepFromDate 6 symbols for final date in the DDMMYY format
 */
Tremol.FP.prototype.PrintOrStoreEJByDate = function (OptionReportStorage, StartRepFromDate, EndRepFromDate) {
	return this.do('PrintOrStoreEJByDate', 'OptionReportStorage', OptionReportStorage, 'StartRepFromDate', StartRepFromDate, 'EndRepFromDate', EndRepFromDate);
};

/**
 * Sets the used TCP module for communication - Lan or WiFi. To apply use -SaveNetworkSettings()
 * @param {Tremol.Enums.OptionUsedModule} OptionUsedModule 1 symbol with value: 
 - '1' - LAN 
 - '2' - WiFi
 */
Tremol.FP.prototype.SetTCP_ActiveModule = function (OptionUsedModule) {
	return this.do('SetTCP_ActiveModule', 'OptionUsedModule', OptionUsedModule);
};

/**
 * @typedef {Object} DailyReturnedChangeAmounts_OldRes
 * @property {number} AmountPayment0 Up to 13 symbols for the accumulated amount by payment type 0
 * @property {number} AmountPayment1 Up to 13 symbols for the accumulated amount by payment type 1
 * @property {number} AmountPayment2 Up to 13 symbols for the accumulated amount by payment type 2
 * @property {number} AmountPayment3 Up to 13 symbols for the accumulated amount by payment type 3
 * @property {number} AmountPayment4 Up to 13 symbols for the accumulated amount by payment type 4
 */

/**
 * Provides information about the amounts returned as change by type of payment. Command works for KL version 2 devices.
 * @return {DailyReturnedChangeAmounts_OldRes}
 */
Tremol.FP.prototype.ReadDailyReturnedChangeAmounts_Old = function () {
	return this.do('ReadDailyReturnedChangeAmounts_Old');
};

/**
 * Print Electronic Journal Report by number of Z report blocks and selected documents content. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.
 * @param {string} FlagsReceipts 1 symbol for Receipts included in EJ: 
Flags.7=0 
Flags.6=1 
Flags.5=1 Yes, Flags.5=0 No (Include PO) 
Flags.4=1 Yes, Flags.4=0 No (Include RA) 
Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) 
Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) 
Flags.1=1 Yes, Flags.1=0 No (Include Invoice) 
Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)
 * @param {string} FlagsReports 1 symbol for Reports included in EJ: 
Flags.7=0 
Flags.6=1 
Flags.5=0 
Flags.4=1 Yes, Flags.4=0 No (Include FM reports) 
Flags.3=1 Yes, Flags.3=0 No (Include Other reports) 
Flags.2=1 Yes, Flags.2=0 No (Include Daily X) 
Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) 
Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)
 * @param {number} StartZNum 4 symbols for initial number report in format ####
 * @param {number} EndZNum 4 symbols for final number report in format ####
 */
Tremol.FP.prototype.PrintEJByZBlocksCustom = function (FlagsReceipts, FlagsReports, StartZNum, EndZNum) {
	return this.do('PrintEJByZBlocksCustom', 'FlagsReceipts', FlagsReceipts, 'FlagsReports', FlagsReports, 'StartZNum', StartZNum, 'EndZNum', EndZNum);
};

/**
 * Read the used TCP module for communication - Lan or WiFi
 * @return {Tremol.Enums.OptionUsedModule}
 */
Tremol.FP.prototype.ReadTCP_UsedModule = function () {
	return this.do('ReadTCP_UsedModule');
};

/**
 * Feeds one line of paper.
 */
Tremol.FP.prototype.PaperFeed = function () {
	return this.do('PaperFeed');
};

/**
 * Close the fiscal receipt (Fiscal receipt, Invoice receipt, Storno receipt, Credit Note or Non-fical receipt). When the payment is finished.
 */
Tremol.FP.prototype.CloseReceipt = function () {
	return this.do('CloseReceipt');
};

/**
 * Provides information about the QR code data in specified number issued receipt.
 * @param {number} RcpNum 6 symbols with format ######
 * @return {string}
 */
Tremol.FP.prototype.ReadSpecifiedReceiptQRcodeData = function (RcpNum) {
	return this.do('ReadSpecifiedReceiptQRcodeData', 'RcpNum', RcpNum);
};

/**
 * Registers the sell (for correction use minus sign in the price field)  of article with specified department, name, price, quantity and/or discount/addition on  the transaction.
 * @param {string} NamePLU 36 symbols for name of sale. 34 symbols are printed on 
paper. Symbol 0x7C '|' is new line separator.
 * @param {number} DepNum 1 symbol for article department 
attachment, formed in the following manner: DepNum[HEX] + 80h 
example: Dep01 = 81h, Dep02 = 82h … Dep19 = 93h 
Department range from 1 to 127
 * @param {number} Price Up to 10 symbols for article's price. Use minus sign '-' for correction
 * @param {number=} Quantity Up to 10symbols for article's quantity sold
 * @param {number=} DiscAddP Up to 7 for percentage of discount/addition. Use 
minus sign '-' for discount
 * @param {number=} DiscAddV Up to 8 symbols for percentage of 
discount/addition. Use minus sign '-' for discount
 */
Tremol.FP.prototype.SellPLUfromDep_ = function (NamePLU, DepNum, Price, Quantity, DiscAddP, DiscAddV) {
	return this.do('SellPLUfromDep_', 'NamePLU', NamePLU, 'DepNum', DepNum, 'Price', Price, 'Quantity', Quantity, 'DiscAddP', DiscAddP, 'DiscAddV', DiscAddV);
};

/**
 * Arrangement of payment positions according to NRA list: 0-Cash, 1- Check, 2-Talon, 3-V.Talon, 4-Packaging, 5-Service, 6-Damage, 7-Card, 8-Bank, 9- Programming Name 1, 10-Programming Name 2, 11-Currency.
 * @param {number} PaymentPosition0 2 digits for payment position 0 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @param {number} PaymentPosition1 2 digits for payment position 1 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @param {number} PaymentPosition2 2 digits for payment position 2 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @param {number} PaymentPosition3 2 digits for payment position 3 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @param {number} PaymentPosition4 2 digits for payment position 4 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @param {number} PaymentPosition5 2 digits for payment position 5 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @param {number} PaymentPosition6 2 digits for payment position 6 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @param {number} PaymentPosition7 2 digits for payment position 7 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @param {number} PaymentPosition8 2 digits for payment position 8 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @param {number} PaymentPosition9 2 digits for payment position 9 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @param {number} PaymentPosition10 2 digits for payment position 10 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @param {number} PaymentPosition11 2 digits for payment position 11 in format ##.  
Values from '1' to '11' according to NRA payments list.
 */
Tremol.FP.prototype.ArrangePayments = function (PaymentPosition0, PaymentPosition1, PaymentPosition2, PaymentPosition3, PaymentPosition4, PaymentPosition5, PaymentPosition6, PaymentPosition7, PaymentPosition8, PaymentPosition9, PaymentPosition10, PaymentPosition11) {
	return this.do('ArrangePayments', 'PaymentPosition0', PaymentPosition0, 'PaymentPosition1', PaymentPosition1, 'PaymentPosition2', PaymentPosition2, 'PaymentPosition3', PaymentPosition3, 'PaymentPosition4', PaymentPosition4, 'PaymentPosition5', PaymentPosition5, 'PaymentPosition6', PaymentPosition6, 'PaymentPosition7', PaymentPosition7, 'PaymentPosition8', PaymentPosition8, 'PaymentPosition9', PaymentPosition9, 'PaymentPosition10', PaymentPosition10, 'PaymentPosition11', PaymentPosition11);
};

/**
 * Opens a fiscal invoice credit note receipt assigned to the specified operator number and operator password with free info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.
 * @param {number} OperNum Symbol from 1 to 20 corresponding to operator's 
number
 * @param {string} OperPass 6 symbols for operator's password
 * @param {Tremol.Enums.OptionInvoiceCreditNotePrintType} OptionInvoiceCreditNotePrintType 1 symbol with value: 
- 'A' - Step by step printing 
- 'C' - Postponed Printing 
- 'E' - Buffered Printing
 * @param {string} Recipient 26 symbols for Invoice recipient
 * @param {string} Buyer 16 symbols for Invoice buyer
 * @param {string} VATNumber 13 symbols for customer Fiscal number
 * @param {string} UIC 13 symbols for customer Unique Identification Code
 * @param {string} Address 30 symbols for Address
 * @param {Tremol.Enums.OptionUICType} OptionUICType 1 symbol for type of Unique Identification Code:  
 - '0' - Bulstat 
 - '1' - EGN 
 - '2' - Foreigner Number 
 - '3' - NRA Official Number
 * @param {Tremol.Enums.OptionStornoReason} OptionStornoReason 1 symbol for reason of storno operation with value:  
- '0' - Operator error  
- '1' - Goods Claim or Goods return  
- '2' - Tax relief
 * @param {string} RelatedToInvoiceNum 10 symbols for issued invoice number
 * @param {Date} RelatedToInvoiceDateTime 17 symbols for issued invoice date and time in format
 * @param {number} RelatedToRcpNum Up to 6 symbols for issued receipt number
 * @param {string} FMNum 8 symbols for number of the Fiscal Memory
 * @param {string=} RelatedToURN Up to 24 symbols for the issed invoice receipt unique receipt number. 
NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: 
* ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device 
number, 
* ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, 
* YYYYYYY - 7 symbols [0-9] for next number of the receipt
 */
Tremol.FP.prototype.OpenCreditNoteWithFreeCustomerData = function (OperNum, OperPass, OptionInvoiceCreditNotePrintType, Recipient, Buyer, VATNumber, UIC, Address, OptionUICType, OptionStornoReason, RelatedToInvoiceNum, RelatedToInvoiceDateTime, RelatedToRcpNum, FMNum, RelatedToURN) {
	return this.do('OpenCreditNoteWithFreeCustomerData', 'OperNum', OperNum, 'OperPass', OperPass, 'OptionInvoiceCreditNotePrintType', OptionInvoiceCreditNotePrintType, 'Recipient', Recipient, 'Buyer', Buyer, 'VATNumber', VATNumber, 'UIC', UIC, 'Address', Address, 'OptionUICType', OptionUICType, 'OptionStornoReason', OptionStornoReason, 'RelatedToInvoiceNum', RelatedToInvoiceNum, 'RelatedToInvoiceDateTime', RelatedToInvoiceDateTime, 'RelatedToRcpNum', RelatedToRcpNum, 'FMNum', FMNum, 'RelatedToURN', RelatedToURN);
};

/**
 * Prints barcode from type stated by CodeType and CodeLen and with data stated in CodeData field. Command works only for fiscal printer devices. ECR does not support this command. The command is not supported by KL ECRs!
 * @param {Tremol.Enums.OptionCodeType} OptionCodeType 1 symbol with possible values: 
 - '0' - UPC A 
 - '1' - UPC E 
 - '2' - EAN 13 
 - '3' - EAN 8 
 - '4' - CODE 39 
 - '5' - ITF 
 - '6' - CODABAR 
 - 'H' - CODE 93 
 - 'I' - CODE 128
 * @param {number} CodeLen Up to 2 bytes for number of bytes according to the table
 * @param {string} CodeData Up to 100 bytes data in range according to the table
 */
Tremol.FP.prototype.PrintBarcode = function (OptionCodeType, CodeLen, CodeData) {
	return this.do('PrintBarcode', 'OptionCodeType', OptionCodeType, 'CodeLen', CodeLen, 'CodeData', CodeData);
};

/**
 * @typedef {Object} DailySaleAndStornoAmountsByVATRes
 * @property {number} SaleAmountVATGr0 Up to 13 symbols for the amount accumulated from sales by VAT group А
 * @property {number} SaleAmountVATGr1 Up to 13 symbols for the amount accumulated from sales by VAT group Б
 * @property {number} SaleAmountVATGr2 Up to 13 symbols for the amount accumulated from sales by VAT group В
 * @property {number} SaleAmountVATGr3 Up to 13 symbols for the amount accumulated from sales by VAT group Г
 * @property {number} SaleAmountVATGr4 Up to 13 symbols for the amount accumulated from sales by VAT group Д
 * @property {number} SaleAmountVATGr5 Up to 13 symbols for the amount accumulated from sales by VAT group Е
 * @property {number} SaleAmountVATGr6 Up to 13 symbols for the amount accumulated from sales by VAT group Ж
 * @property {number} SaleAmountVATGr7 Up to 13 symbols for the amount accumulated from sales by VAT group З
 * @property {number} SumAllVATGr Up to 13 symbols for sum of all VAT groups
 * @property {number} StornoAmountVATGr0 Up to 13 symbols for the amount accumulated from Storno by VAT group А
 * @property {number} StornoAmountVATGr1 Up to 13 symbols for the amount accumulated from Storno by VAT group Б
 * @property {number} StornoAmountVATGr2 Up to 13 symbols for the amount accumulated from Storno by VAT group В
 * @property {number} StornoAmountVATGr3 Up to 13 symbols for the amount accumulated from Storno by VAT group Г
 * @property {number} StornoAmountVATGr4 Up to 13 symbols for the amount accumulated from Storno by VAT group Д
 * @property {number} StornoAmountVATGr5 Up to 13 symbols for the amount accumulated from Storno by VAT group Е
 * @property {number} StornoAmountVATGr6 Up to 13 symbols for the amount accumulated from Storno by VAT group Ж
 * @property {number} StornoAmountVATGr7 Up to 13 symbols for the amount accumulated from Storno by VAT group З
 * @property {number} StornoAllVATGr Up to 13 symbols for the amount accumulated from Storno by all groups
 */

/**
 * Provides information about the accumulated sale and storno amounts by VAT group.
 * @return {DailySaleAndStornoAmountsByVATRes}
 */
Tremol.FP.prototype.ReadDailySaleAndStornoAmountsByVAT = function () {
	return this.do('ReadDailySaleAndStornoAmountsByVAT');
};

/**
 * Print a department report with or without zeroing ('Z' or 'X').
 * @param {Tremol.Enums.OptionZeroing} OptionZeroing 1 symbol with value: 
 - 'Z' - Zeroing 
 - 'X' - Without zeroing
 */
Tremol.FP.prototype.PrintDepartmentReport = function (OptionZeroing) {
	return this.do('PrintDepartmentReport', 'OptionZeroing', OptionZeroing);
};

/**
 * Read or Store Electronic Journal report by CSV format option and document content selecting. If CSV format is set the content can includes only fiscal receipts. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.
 * @param {Tremol.Enums.OptionStorageReport} OptionStorageReport 1 character with value 
 - 'j0' - To PC 
 - 'j2' - To USB Flash Drive 
 - 'j4' - To SD card
 * @param {Tremol.Enums.OptionCSVformat} OptionCSVformat 1 symbol with value: 
 - 'C' - Yes 
 - 'X' - No
 * @param {string} FlagsReceipts 1 symbol for Receipts included in EJ: 
Flags.7=0 
Flags.6=1 
Flags.5=1 Yes, Flags.5=0 No (Include PO) 
Flags.4=1 Yes, Flags.4=0 No (Include RA) 
Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) 
Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) 
Flags.1=1 Yes, Flags.1=0 No (Include Invoice) 
Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)
 * @param {string} FlagsReports 1 symbol for Reports included in EJ: 
Flags.7=0 
Flags.6=1 
Flags.5=0 
Flags.4=1 Yes, Flags.4=0 No (Include FM reports) 
Flags.3=1 Yes, Flags.3=0 No (Include Other reports) 
Flags.2=1 Yes, Flags.2=0 No (Include Daily X) 
Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) 
Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)
 */
Tremol.FP.prototype.ReadEJCustom = function (OptionStorageReport, OptionCSVformat, FlagsReceipts, FlagsReports) {
	return this.do('ReadEJCustom', 'OptionStorageReport', OptionStorageReport, 'OptionCSVformat', OptionCSVformat, 'FlagsReceipts', FlagsReceipts, 'FlagsReports', FlagsReports);
};

/**
 * Prints a brief Departments report from the FM.
 */
Tremol.FP.prototype.PrintBriefFMDepartmentsReport = function () {
	return this.do('PrintBriefFMDepartmentsReport');
};

/**
 * Shows a 20-symbols text in the lower external display line.
 * @param {string} Text 20 symbols text
 */
Tremol.FP.prototype.DisplayTextLine2 = function (Text) {
	return this.do('DisplayTextLine2', 'Text', Text);
};

/**
 * @typedef {Object} DailyCountersRes
 * @property {number} LastReportNumFromReset Up to 5 symbols for number of the last report from reset
 * @property {number} LastFMBlockNum Up to 5 symbols for number of the last FM report
 * @property {number} EJNum Up to 5 symbols for number of EJ
 * @property {Date} DateTime 16 symbols for date and time of the last block storage in FM in 
format "DD-MM-YYYY HH:MM"
 */

/**
 * Provides information about the current reading of the daily-report- with-zeroing counter, the number of the last block stored in FM, the number of EJ and the date and time of the last block storage in the FM.
 * @return {DailyCountersRes}
 */
Tremol.FP.prototype.ReadDailyCounters = function () {
	return this.do('ReadDailyCounters');
};

/**
 * Program device's WiFi network password where it will connect. To apply use -SaveNetworkSettings()
 * @param {number} PassLength Up to 3 symbols for the WiFi password len
 * @param {string} Password Up to 100 symbols for the device's WiFi password
 */
Tremol.FP.prototype.SetWiFi_Password = function (PassLength, Password) {
	return this.do('SetWiFi_Password', 'PassLength', PassLength, 'Password', Password);
};

/**
 * Provides information about the current quantity measured by scale
 * @return {number}
 */
Tremol.FP.prototype.ReadScaleQuantity = function () {
	return this.do('ReadScaleQuantity');
};

/**
 * Print Electronic Journal report with selected documents content. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.
 * @param {string} FlagsReceipts 1 symbol for Receipts included in EJ: 
Flags.7=0 
Flags.6=1 
Flags.5=1 Yes, Flags.5=0 No (Include PO) 
Flags.4=1 Yes, Flags.4=0 No (Include RA) 
Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) 
Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) 
Flags.1=1 Yes, Flags.1=0 No (Include Invoice) 
Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)
 * @param {string} FlagsReports 1 symbol for Reports included in EJ: 
Flags.7=0 
Flags.6=1 
Flags.5=0 
Flags.4=1 Yes, Flags.4=0 No (Include FM reports) 
Flags.3=1 Yes, Flags.3=0 No (Include Other reports) 
Flags.2=1 Yes, Flags.2=0 No (Include Daily X) 
Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) 
Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)
 */
Tremol.FP.prototype.PrintEJCustom = function (FlagsReceipts, FlagsReports) {
	return this.do('PrintEJCustom', 'FlagsReceipts', FlagsReceipts, 'FlagsReports', FlagsReports);
};

/**
 * Start Bluetooth test on the device and print out the result
 */
Tremol.FP.prototype.StartTest_Bluetooth = function () {
	return this.do('StartTest_Bluetooth');
};

/**
 * Read a brief FM Departments report by initial and end date.
 * @param {Date} StartDate 6 symbols for initial date in the DDMMYY format
 * @param {Date} EndDate 6 symbols for final date in the DDMMYY format
 */
Tremol.FP.prototype.ReadBriefFMDepartmentsReportByDate = function (StartDate, EndDate) {
	return this.do('ReadBriefFMDepartmentsReportByDate', 'StartDate', StartDate, 'EndDate', EndDate);
};

/**
 * Erase all articles in PLU database.
 * @param {string} Password 6 symbols for password
 */
Tremol.FP.prototype.EraseAllPLUs = function (Password) {
	return this.do('EraseAllPLUs', 'Password', Password);
};

/**
 * Print a detailed FM Departments report by initial and end date.
 * @param {Date} StartDate 6 symbols for initial date in the DDMMYY format
 * @param {Date} EndDate 6 symbols for final date in the DDMMYY format
 */
Tremol.FP.prototype.PrintDetailedFMDepartmentsReportByDate = function (StartDate, EndDate) {
	return this.do('PrintDetailedFMDepartmentsReportByDate', 'StartDate', StartDate, 'EndDate', EndDate);
};

/**
 * Confirm Unique Identification Code (UIC) and UIC type into the operative memory.
 * @param {string} Password 6-symbols string
 */
Tremol.FP.prototype.ConfirmFiscalization = function (Password) {
	return this.do('ConfirmFiscalization', 'Password', Password);
};

/**
 * Starts session for reading electronic receipt by number with its QR code data in the end.
 * @param {number} RcpNum 6 symbols with format ######
 */
Tremol.FP.prototype.ReadElectronicReceipt_QR_Data = function (RcpNum) {
	return this.do('ReadElectronicReceipt_QR_Data', 'RcpNum', RcpNum);
};

/**
 * @typedef {Object} DailyRAbyOperatorRes
 * @property {number} OperNum Symbols from 1 to 20 corresponding to operator's number
 * @property {number} AmountRA_Payment0 Up to 13 symbols for the RA by type of payment 0
 * @property {number} AmountRA_Payment1 Up to 13 symbols for the RA by type of payment 1
 * @property {number} AmountRA_Payment2 Up to 13 symbols for the RA by type of payment 2
 * @property {number} AmountRA_Payment3 Up to 13 symbols for the RA by type of payment 3
 * @property {number} AmountRA_Payment4 Up to 13 symbols for the RA by type of payment 4
 * @property {number} AmountRA_Payment5 Up to 13 symbols for the RA by type of payment 5
 * @property {number} AmountRA_Payment6 Up to 13 symbols for the RA by type of payment 6
 * @property {number} AmountRA_Payment7 Up to 13 symbols for the RA by type of payment 7
 * @property {number} AmountRA_Payment8 Up to 13 symbols for the RA by type of payment 8
 * @property {number} AmountRA_Payment9 Up to 13 symbols for the RA by type of payment 9
 * @property {number} AmountRA_Payment10 Up to 13 symbols for the RA by type of payment 10
 * @property {number} AmountRA_Payment11 Up to 13 symbols for the RA by type of payment 11
 * @property {number} NoRA Up to 5 symbols for the total number of operations
 */

/**
 * Read the RA by type of payment and the total number of operations by specified operator.
 * @param {number} OperNum Symbols from 1 to 20 corresponding to operator's number
 * @return {DailyRAbyOperatorRes}
 */
Tremol.FP.prototype.ReadDailyRAbyOperator = function (OperNum) {
	return this.do('ReadDailyRAbyOperator', 'OperNum', OperNum);
};

/**
 * Provide information about automatic daily report printing or not printing parameter
 * @return {Tremol.Enums.OptionDailyReport}
 */
Tremol.FP.prototype.ReadDailyReportParameter = function () {
	return this.do('ReadDailyReportParameter');
};

/**
 * Start GPRS test on the device and print out the result
 */
Tremol.FP.prototype.StartTest_GPRS = function () {
	return this.do('StartTest_GPRS');
};

/**
 * Provides information about daily turnover on the FD client display
 */
Tremol.FP.prototype.DisplayDailyTurnover = function () {
	return this.do('DisplayDailyTurnover');
};

/**
 * @typedef {Object} HeaderRes
 * @property {Tremol.Enums.OptionHeaderLine} OptionHeaderLine (Line Number) 1 symbol with value: 
 - '1' - Header 1 
 - '2' - Header 2 
 - '3' - Header 3 
 - '4' - Header 4 
 - '5' - Header 5 
 - '6' - Header 6 
 - '7' - Header 7
 * @property {string} HeaderText TextLength symbols for header lines
 */

/**
 * Provides the content of the header lines
 * @param {Tremol.Enums.OptionHeaderLine} OptionHeaderLine 1 symbol with value: 
 - '1' - Header 1 
 - '2' - Header 2 
 - '3' - Header 3 
 - '4' - Header 4 
 - '5' - Header 5 
 - '6' - Header 6 
 - '7' - Header 7
 * @return {HeaderRes}
 */
Tremol.FP.prototype.ReadHeader = function (OptionHeaderLine) {
	return this.do('ReadHeader', 'OptionHeaderLine', OptionHeaderLine);
};

/**
 * Start paper cutter. The command works only in fiscal printer devices.
 */
Tremol.FP.prototype.CutPaper = function () {
	return this.do('CutPaper');
};

/**
 * @typedef {Object} DeviceModuleSupportByFirmwareRes
 * @property {Tremol.Enums.OptionLAN} OptionLAN 1 symbol for LAN suppor 
- '0' - No 
 - '1' - Yes
 * @property {Tremol.Enums.OptionWiFi} OptionWiFi 1 symbol for WiFi support 
- '0' - No 
 - '1' - Yes
 * @property {Tremol.Enums.OptionGPRS} OptionGPRS 1 symbol for GPRS support 
- '0' - No 
 - '1' - Yes 
BT (Bluetooth) 1 symbol for Bluetooth support 
- '0' - No 
 - '1' - Yes
 * @property {Tremol.Enums.OptionBT} OptionBT (Bluetooth) 1 symbol for Bluetooth support 
- '0' - No 
 - '1' - Yes
 */

/**
 * Provide an information about modules supported by device's firmware
 * @return {DeviceModuleSupportByFirmwareRes}
 */
Tremol.FP.prototype.ReadDeviceModuleSupportByFirmware = function () {
	return this.do('ReadDeviceModuleSupportByFirmware');
};

/**
 * Set invoice start and end number range. To execute the command is necessary to grand following condition: the number range to be spent, not used, or not set after the last RAM reset.
 * @param {number} StartNum 10 characters for start number in format: ##########
 * @param {number} EndNum 10 characters for end number in format: ##########
 */
Tremol.FP.prototype.SetInvoiceRange = function (StartNum, EndNum) {
	return this.do('SetInvoiceRange', 'StartNum', StartNum, 'EndNum', EndNum);
};

/**
 * @typedef {Object} WiFi_PasswordRes
 * @property {number} PassLength (Length) Up to 3 symbols for the WiFi password length
 * @property {string} Password Up to 100 symbols for the device's WiFi password
 */

/**
 * Read device's connected WiFi network password
 * @return {WiFi_PasswordRes}
 */
Tremol.FP.prototype.ReadWiFi_Password = function () {
	return this.do('ReadWiFi_Password');
};

/**
 * Programs Barcode of article in the internal database.
 * @param {number} PLUNum 5 symbols for article number in format: #####
 * @param {string} Barcode 13 symbols for barcode
 */
Tremol.FP.prototype.ProgPLUbarcode = function (PLUNum, Barcode) {
	return this.do('ProgPLUbarcode', 'PLUNum', PLUNum, 'Barcode', Barcode);
};

/**
 * Prints a detailed FM report by initial and end date.
 * @param {Date} StartDate 6 symbols for initial date in the DDMMYY format
 * @param {Date} EndDate 6 symbols for final date in the DDMMYY format
 */
Tremol.FP.prototype.PrintDetailedFMReportByDate = function (StartDate, EndDate) {
	return this.do('PrintDetailedFMReportByDate', 'StartDate', StartDate, 'EndDate', EndDate);
};

/**
 * Print or store Electronic Journal Report from by number of Z report blocks.
 * @param {Tremol.Enums.OptionReportStorage} OptionReportStorage 1 character with value: 
 - 'J1' - Printing 
 - 'J2' - USB storage 
 - 'J4' - SD card storage
 * @param {number} StartZNum 4 symbols for initial number report in format ####
 * @param {number} EndZNum 4 symbols for final number report in format ####
 */
Tremol.FP.prototype.PrintOrStoreEJByZBlocks = function (OptionReportStorage, StartZNum, EndZNum) {
	return this.do('PrintOrStoreEJByZBlocks', 'OptionReportStorage', OptionReportStorage, 'StartZNum', StartZNum, 'EndZNum', EndZNum);
};

/**
 * Register the sell (for correction use minus sign in the price field) of article with specified VAT. If department is present the relevant accumulations are perfomed in its registers.
 * @param {string} NamePLU 36 symbols for article's name. 34 symbols are printed on paper. 
Symbol 0x7C '|' is new line separator.
 * @param {Tremol.Enums.OptionVATClass} OptionVATClass 1 character for VAT class: 
 - 'А' - VAT Class 0 
 - 'Б' - VAT Class 1 
 - 'В' - VAT Class 2 
 - 'Г' - VAT Class 3 
 - 'Д' - VAT Class 4 
 - 'Е' - VAT Class 5 
 - 'Ж' - VAT Class 6 
 - 'З' - VAT Class 7 
 - '*' - Forbidden
 * @param {number} Price Up to 10 symbols for article's price. Use minus sign '-' for correction
 * @param {string=} Quantity From 3 to 10 symbols for quantity in format fractional format, e.g. 1/3
 * @param {number=} DiscAddP Up to 7 symbols for percentage of discount/addition. 
Use minus sign '-' for discount
 * @param {number=} DiscAddV Up to 8 symbols for value of discount/addition. 
Use minus sign '-' for discount
 * @param {number=} DepNum 1 symbol for article department 
attachment, formed in the following manner; example: Dep01 = 81h, Dep02 
= 82h … Dep19 = 93h 
Department range from 1 to 127
 */
Tremol.FP.prototype.SellFractQtyPLUwithSpecifiedVATfromDep = function (NamePLU, OptionVATClass, Price, Quantity, DiscAddP, DiscAddV, DepNum) {
	return this.do('SellFractQtyPLUwithSpecifiedVATfromDep', 'NamePLU', NamePLU, 'OptionVATClass', OptionVATClass, 'Price', Price, 'Quantity', Quantity, 'DiscAddP', DiscAddP, 'DiscAddV', DiscAddV, 'DepNum', DepNum);
};

/**
 * Print a brief FM Departments report by initial and end Z report number.
 * @param {number} StartZNum 4 symbols for the initial FM report number included in report, format ####
 * @param {number} EndZNum 4 symbols for the final FM report number included in report, format ####
 */
Tremol.FP.prototype.PrintBriefFMDepartmentsReportByZBlocks = function (StartZNum, EndZNum) {
	return this.do('PrintBriefFMDepartmentsReportByZBlocks', 'StartZNum', StartZNum, 'EndZNum', EndZNum);
};

/**
 * @typedef {Object} PLUgeneralRes
 * @property {number} PLUNum 5 symbols for article number with leading zeroes in format #####
 * @property {string} PLUName 34 symbols for article name, new line=0x7C.
 * @property {number} Price Up to 10 symbols for article price
 * @property {Tremol.Enums.OptionPrice} OptionPrice 1 symbol for price flag with next value: 
 - '0'- Free price is disable valid only programmed price 
 - '1'- Free price is enable 
 - '2'- Limited price
 * @property {Tremol.Enums.OptionVATClass} OptionVATClass 1 character for VAT class: 
 - 'А' - VAT Class 0 
 - 'Б' - VAT Class 1 
 - 'В' - VAT Class 2 
 - 'Г' - VAT Class 3 
 - 'Д' - VAT Class 4 
 - 'Е' - VAT Class 5 
 - 'Ж' - VAT Class 6 
 - 'З' - VAT Class 7 
 - '*' - Forbidden
 * @property {number} BelongToDepNumber BelongToDepNumber + 80h, 1 symbol for PLU department 
attachment= 0x80 … 0x93  
Department range from 1 to 127
 * @property {number} TurnoverAmount Up to 13 symbols for PLU accumulated turnover
 * @property {number} SoldQuantity Up to 13 symbols for Sales quantity of the article
 * @property {number} StornoAmount Up to 13 symbols for accumulated storno amount
 * @property {number} StornoQuantity Up to 13 symbols for accumulated storno quantiy
 * @property {number} LastZReportNumber Up to 5 symbols for the number of the last article report with zeroing
 * @property {Date} LastZReportDate 16 symbols for the date and time of the last article report with zeroing in 
format DD-MM-YYYY HH:MM
 * @property {Tremol.Enums.OptionSingleTransaction} OptionSingleTransaction 1 symbol with value: 
 - '0' - Inactive, default value 
 - '1' - Active Single transaction in receipt
 */

/**
 * Provides information about the general registers of the specified article.
 * @param {number} PLUNum 5 symbols for article number with leading zeroes in format: #####
 * @return {PLUgeneralRes}
 */
Tremol.FP.prototype.ReadPLUgeneral = function (PLUNum) {
	return this.do('ReadPLUgeneral', 'PLUNum', PLUNum);
};

/**
 * @typedef {Object} DailyReceivedSalesAmountsByOperatorRes
 * @property {number} OperNum Symbols from 1 to 20 corresponding to operator's number
 * @property {number} ReceivedSalesAmountPayment0 Up to 13 symbols for amounts received by sales for payment 0
 * @property {number} ReceivedSalesAmountPayment1 Up to 13 symbols for amounts received by sales for payment 1
 * @property {number} ReceivedSalesAmountPayment2 Up to 13 symbols for amounts received by sales for payment 2
 * @property {number} ReceivedSalesAmountPayment3 Up to 13 symbols for amounts received by sales for payment 3
 * @property {number} ReceivedSalesAmountPayment4 Up to 13 symbols for amounts received by sales for payment 4
 * @property {number} ReceivedSalesAmountPayment5 Up to 13 symbols for amounts received by sales for payment 5
 * @property {number} ReceivedSalesAmountPayment6 Up to 13 symbols for amounts received by sales for payment 6
 * @property {number} ReceivedSalesAmountPayment7 Up to 13 symbols for amounts received by sales for payment 7
 * @property {number} ReceivedSalesAmountPayment8 Up to 13 symbols for amounts received by sales for payment 8
 * @property {number} ReceivedSalesAmountPayment9 Up to 13 symbols for amounts received by sales for payment 9
 * @property {number} ReceivedSalesAmountPayment10 Up to 13 symbols for amounts received by sales for payment 10
 * @property {number} ReceivedSalesAmountPayment11 Up to 13 symbols for amounts received by sales for payment 11
 */

/**
 * Read the amounts received from sales by type of payment and specified operator.
 * @param {number} OperNum Symbols from 1 to 20 corresponding to operator's 
number
 * @return {DailyReceivedSalesAmountsByOperatorRes}
 */
Tremol.FP.prototype.ReadDailyReceivedSalesAmountsByOperator = function (OperNum) {
	return this.do('ReadDailyReceivedSalesAmountsByOperator', 'OperNum', OperNum);
};

/**
 * @typedef {Object} CustomerDataRes
 * @property {number} CustomerNum (Customer Number) 4 symbols for customer number in format ####
 * @property {string} CustomerCompanyName (Company name) 26 symbols for customer name
 * @property {string} CustomerFullName (Buyer Name) 16 symbols for Buyer name
 * @property {string} VATNumber 13 symbols for VAT number on customer
 * @property {string} UIC 13 symbols for customer Unique Identification Code
 * @property {string} Address 30 symbols for address on customer
 * @property {Tremol.Enums.OptionUICType} OptionUICType 1 symbol for type of Unique Identification Code:  
 - '0' - Bulstat 
 - '1' - EGN 
 - '2' - Foreigner Number 
 - '3' - NRA Official Number
 */

/**
 * Provide information for specified customer from FD data base.
 * @param {number} CustomerNum 4 symbols for customer number in format ####
 * @return {CustomerDataRes}
 */
Tremol.FP.prototype.ReadCustomerData = function (CustomerNum) {
	return this.do('ReadCustomerData', 'CustomerNum', CustomerNum);
};

/**
 * @typedef {Object} CurrentReceiptInfoRes
 * @property {Tremol.Enums.OptionIsReceiptOpened} OptionIsReceiptOpened 1 symbol with value: 
 - '0' - No 
 - '1' - Yes
 * @property {number} SalesNumber 3 symbols for number of sales in format ###
 * @property {number} SubtotalAmountVAT0 Up to 13 symbols for subtotal by VAT group А
 * @property {number} SubtotalAmountVAT1 Up to 13 symbols for subtotal by VAT group Б
 * @property {number} SubtotalAmountVAT2 Up to 13 symbols for subtotal by VAT group В
 * @property {Tremol.Enums.OptionForbiddenVoid} OptionForbiddenVoid 1 symbol with value: 
- '0' - allowed 
- '1' - forbidden
 * @property {Tremol.Enums.OptionVATinReceipt} OptionVATinReceipt 1 symbol with value: 
- '0' - No 
- '1' - Yes
 * @property {Tremol.Enums.OptionReceiptFormat} OptionReceiptFormat (Format) 1 symbol with value: 
 - '1' - Detailed 
 - '0' - Brief
 * @property {Tremol.Enums.OptionInitiatedPayment} OptionInitiatedPayment 1 symbol with value: 
- '0' - No 
- '1' - Yes
 * @property {Tremol.Enums.OptionFinalizedPayment} OptionFinalizedPayment 1 symbol with value: 
- '0' - No 
- '1' - Yes
 * @property {Tremol.Enums.OptionPowerDownInReceipt} OptionPowerDownInReceipt 1 symbol with value: 
- '0' - No 
- '1' - Yes
 * @property {Tremol.Enums.OptionTypeReceipt} OptionTypeReceipt (Receipt and Printing type) 1 symbol with value: 
 - '0' - Sales receipt printing step by step 
 - '2' - Sales receipt Postponed Printing 
 - '4' - Storno receipt printing step by step 
 - '6' - Storno receipt Postponed Printing 
 - '1' - Invoice sales receipt printing step by step 
 - '3' - Invoice sales receipt Postponed Printing 
 - '5' - Invoice Credit note receipt printing step by step 
 - '7' - Invoice Credit note receipt Postponed Printing
 * @property {number} ChangeAmount Up to 13 symbols the amount of the due change in the stated payment type
 * @property {Tremol.Enums.OptionChangeType} OptionChangeType 1 symbol with value: 
 - '0' - Change In Cash 
 - '1' - Same As The payment 
 - '2' - Change In Currency
 * @property {number} SubtotalAmountVAT3 Up to 13 symbols for subtotal by VAT group Г
 * @property {number} SubtotalAmountVAT4 Up to 13 symbols for subtotal by VAT group Д
 * @property {number} SubtotalAmountVAT5 Up to 13 symbols for subtotal by VAT group Е
 * @property {number} SubtotalAmountVAT6 Up to 13 symbols for subtotal by VAT group Ж
 * @property {number} SubtotalAmountVAT7 Up to 13 symbols for subtotal by VAT group З
 * @property {number} CurrentReceiptNumber 6 symbols for fiscal receipt number in format ######
 */

/**
 * Read the current status of the receipt.
 * @return {CurrentReceiptInfoRes}
 */
Tremol.FP.prototype.ReadCurrentReceiptInfo = function () {
	return this.do('ReadCurrentReceiptInfo');
};

/**
 * Opens a fiscal invoice receipt assigned to the specified operator number and operator password with internal DB info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.
 * @param {number} OperNum Symbol from 1 to 20 corresponding to operator's 
number
 * @param {string} OperPass 6 symbols for operator's password
 * @param {Tremol.Enums.OptionInvoicePrintType} OptionInvoicePrintType 1 symbol with value: 
- '1' - Step by step printing 
- '3' - Postponed Printing 
- '5' - Buffered Printing
 * @param {string} CustomerNum Symbol '#' and following up to 4 symbols for related customer ID number 
corresponding to the FD database
 * @param {string=} UniqueReceiptNumber Up to 24 symbols for unique receipt number. 
NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: 
* ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, 
* ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, 
* YYYYYYY - 7 symbols [0-9] for next number of the receipt
 */
Tremol.FP.prototype.OpenInvoiceWithFDCustomerDB = function (OperNum, OperPass, OptionInvoicePrintType, CustomerNum, UniqueReceiptNumber) {
	return this.do('OpenInvoiceWithFDCustomerDB', 'OperNum', OperNum, 'OperPass', OperPass, 'OptionInvoicePrintType', OptionInvoicePrintType, 'CustomerNum', CustomerNum, 'UniqueReceiptNumber', UniqueReceiptNumber);
};

/**
 * @typedef {Object} PLUallDataRes
 * @property {number} PLUNum 5 symbols for article number with leading zeroes in format: #####
 * @property {string} PLUName 34 symbols for article name, new line=0x7C.
 * @property {number} Price Up to 10 symbols for article price
 * @property {string} FlagsPricePLU 1 symbol for flags = 0x80 + FlagSinglTr + FlagQTY + OptionPrice 
Where  
OptionPrice: 
0x00 - for free price is disable valid only programmed price 
0x01 - for free price is enable 
0x02 - for limited price 
FlagQTY: 
0x00 - for availability of PLU stock is not monitored 
0x04 - for disable negative quantity 
0x08 - for enable negative quantity 
FlagSingleTr: 
0x00 - no single transaction 
0x10 - single transaction is active
 * @property {Tremol.Enums.OptionVATClass} OptionVATClass 1 character for VAT class: 
 - 'А' - VAT Class 0 
 - 'Б' - VAT Class 1 
 - 'В' - VAT Class 2 
 - 'Г' - VAT Class 3 
 - 'Д' - VAT Class 4 
 - 'Е' - VAT Class 5 
 - 'Ж' - VAT Class 6 
 - 'З' - VAT Class 7 
 - '*' - Forbidden
 * @property {number} BelongToDepNumber BelongToDepNumber + 80h, 1 symbol for PLU department 
attachment = 0x80 … 0x93 
Department range from 1 to 127
 * @property {number} TurnoverAmount Up to 13 symbols for PLU accumulated turnover
 * @property {number} SoldQuantity Up to 13 symbols for Sales quantity of the article
 * @property {number} StornoAmount Up to 13 symbols for accumulated storno amount
 * @property {number} StornoQuantity Up to 13 symbols for accumulated storno quantiy
 * @property {number} LastZReportNumber Up to 5 symbols for the number of the last article report with zeroing
 * @property {Date} LastZReportDate 16 symbols for the date and time of the last article report with zeroing 
in format DD-MM-YYYY HH:MM
 * @property {number} AvailableQuantity (Available Quantity) Up to 11 symbols for quantity in stock
 * @property {string} Barcode 13 symbols for article barcode
 */

/**
 * Provides information about all the registers of the specified article.
 * @param {number} PLUNum 5 symbols for article number with leading zeroes in format: #####
 * @return {PLUallDataRes}
 */
Tremol.FP.prototype.ReadPLUallData = function (PLUNum) {
	return this.do('ReadPLUallData', 'PLUNum', PLUNum);
};

/**
 * Print a detailed FM Departments report by initial and end Z report number.
 * @param {number} StartZNum 4 symbols for initial FM report number included in report, format ####
 * @param {number} EndZNum 4 symbols for final FM report number included in report, format ####
 */
Tremol.FP.prototype.PrintDetailedFMDepartmentsReportByZBlocks = function (StartZNum, EndZNum) {
	return this.do('PrintDetailedFMDepartmentsReportByZBlocks', 'StartZNum', StartZNum, 'EndZNum', EndZNum);
};

/**
 * Read Electronic Journal report with all documents.
 * @param {Tremol.Enums.OptionReportFormat} OptionReportFormat 1 character with value 
 - 'J0' - Detailed EJ 
 - 'J8' - Brief EJ
 */
Tremol.FP.prototype.ReadEJ = function (OptionReportFormat) {
	return this.do('ReadEJ', 'OptionReportFormat', OptionReportFormat);
};

/**
 * Register the payment in the receipt with specified type of payment with amount received.
 * @param {Tremol.Enums.OptionPaymentType} OptionPaymentType 1 symbol for payment type: 
 - '0' - Payment 0 
 - '1' - Payment 1 
 - '2' - Payment 2 
 - '3' - Payment 3 
 - '4' - Payment 4 
 - '5' - Payment 5 
 - '6' - Payment 6 
 - '7' - Payment 7 
 - '8' - Payment 8 
 - '9' - Payment 9 
 - '10' - Payment 10 
 - '11' - Payment 11
 * @param {Tremol.Enums.OptionChange} OptionChange Default value is 0, 1 symbol with value: 
 - '0 - With Change 
 - '1' - Without Change
 * @param {number} Amount Up to 10 characters for received amount
 * @param {Tremol.Enums.OptionChangeType=} OptionChangeType 1 symbols with value: 
 - '0' - Change In Cash 
 - '1' - Same As The payment 
 - '2' - Change In Currency
 */
Tremol.FP.prototype.Payment = function (OptionPaymentType, OptionChange, Amount, OptionChangeType) {
	return this.do('Payment', 'OptionPaymentType', OptionPaymentType, 'OptionChange', OptionChange, 'Amount', Amount, 'OptionChangeType', OptionChangeType);
};

/**
 * Program device's network IP address, subnet mask, gateway address, DNS address. To apply use -SaveNetworkSettings()
 * @param {Tremol.Enums.OptionAddressType} OptionAddressType 1 symbol with value: 
 - '2' - IP address 
 - '3' - Subnet Mask 
 - '4' - Gateway address 
 - '5' - DNS address
 * @param {string} DeviceAddress 15 symbols for the selected address
 */
Tremol.FP.prototype.SetDeviceTCP_Addresses = function (OptionAddressType, DeviceAddress) {
	return this.do('SetDeviceTCP_Addresses', 'OptionAddressType', OptionAddressType, 'DeviceAddress', DeviceAddress);
};

/**
 * @typedef {Object} LastDailyReportInfoRes
 * @property {Date} LastZDailyReportDate 10 symbols for last Z-report date in DD-MM-YYYY format
 * @property {number} LastZDailyReportNum Up to 4 symbols for the number of the last daily report
 * @property {number} LastRAMResetNum Up to 4 symbols for the number of the last RAM reset
 * @property {number} TotalReceiptCounter 6 symbols for the total number of receipts in format ######
 * @property {Date} DateTimeLastFiscRec Date Time parameter in format: DD-MM-YYYY HH:MM
 * @property {string} EJNum Up to 2 symbols for number of EJ
 * @property {Tremol.Enums.OptionLastReceiptType} OptionLastReceiptType (Receipt and Printing type) 1 symbol with value: 
 - '0' - Sales receipt printing 
 - '2' - Non fiscal receipt  
 - '4' - Storno receipt 
 - '1' - Invoice sales receipt 
 - '5' - Invoice Credit note
 */

/**
 * Read date and number of last Z-report and last RAM reset event.
 * @return {LastDailyReportInfoRes}
 */
Tremol.FP.prototype.ReadLastDailyReportInfo = function () {
	return this.do('ReadLastDailyReportInfo');
};

/**
 * Print a free text. The command can be executed only if receipt is opened (Fiscal receipt, Invoice receipt, Storno receipt, Credit Note or Non-fical receipt). In the beginning and in the end of line symbol '#' is printed.
 * @param {string} Text TextLength-2 symbols
 */
Tremol.FP.prototype.PrintText = function (Text) {
	return this.do('PrintText', 'Text', Text);
};

/**
 * @typedef {Object} PaymentsPositionsRes
 * @property {number} PaymentPosition0 2 digits for payment position 0 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @property {number} PaymentPosition1 2 digits for payment position 1 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @property {number} PaymentPosition2 2 digits for payment position 2 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @property {number} PaymentPosition3 2 digits for payment position 3 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @property {number} PaymentPosition4 2 digits for payment position 4 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @property {number} PaymentPosition5 2 digits for payment position 5 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @property {number} PaymentPosition6 2 digits for payment position 6 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @property {number} PaymentPosition7 2 digits for payment position 7 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @property {number} PaymentPosition8 2 digits for payment position 8 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @property {number} PaymentPosition9 2 digits for payment position 9 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @property {number} PaymentPosition10 2 digits for payment position 10 in format ##.  
Values from '1' to '11' according to NRA payments list.
 * @property {number} PaymentPosition11 2 digits for payment position 11 in format ##.  
Values from '1' to '11' according to NRA payments list.
 */

/**
 * Provides information about arrangement of payment positions according to NRA list: 0-Cash, 1-Check, 2-Talon, 3-V.Talon, 4-Packaging, 5-Service, 6- Damage, 7-Card, 8-Bank, 9-Programming Name 1, 10-Programming Name 2, 11-Currency.
 * @return {PaymentsPositionsRes}
 */
Tremol.FP.prototype.ReadPaymentsPositions = function () {
	return this.do('ReadPaymentsPositions');
};

/**
 * Opens a fiscal invoice credit note receipt assigned to the specified operator number and operator password with internal DB info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.
 * @param {number} OperNum Symbol from 1 to 20 corresponding to operator's 
number
 * @param {string} OperPass 6 symbols for operator's password
 * @param {Tremol.Enums.OptionInvoiceCreditNotePrintType} OptionInvoiceCreditNotePrintType 1 symbol with value: 
- 'A' - Step by step printing 
- 'C' - Postponed Printing 
- 'E' - Buffered Printing
 * @param {string} CustomerNum Symbol '#' and following up to 4 symbols for related customer ID 
number corresponding to the FD database
 * @param {Tremol.Enums.OptionStornoReason} OptionStornoReason 1 symbol for reason of storno operation with value:  
- '0' - Operator error  
- '1' - Goods Claim or Goods return  
- '2' - Tax relief
 * @param {string} RelatedToInvoiceNum 10 symbols for issued invoice number
 * @param {Date} RelatedToInvoiceDateTime 17 symbols for issued invoice date and time in format
 * @param {number} RelatedToRcpNum Up to 6 symbols for issued receipt number
 * @param {string} FMNum 8 symbols for number of the Fiscal Memory
 * @param {string=} RelatedToURN Up to 24 symbols for the issed invoice receipt unique receipt number. 
NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: 
* ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, 
* ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, 
* YYYYYYY - 7 symbols [0-9] for next number of the receipt
 */
Tremol.FP.prototype.OpenCreditNoteWithFDCustomerDB = function (OperNum, OperPass, OptionInvoiceCreditNotePrintType, CustomerNum, OptionStornoReason, RelatedToInvoiceNum, RelatedToInvoiceDateTime, RelatedToRcpNum, FMNum, RelatedToURN) {
	return this.do('OpenCreditNoteWithFDCustomerDB', 'OperNum', OperNum, 'OperPass', OperPass, 'OptionInvoiceCreditNotePrintType', OptionInvoiceCreditNotePrintType, 'CustomerNum', CustomerNum, 'OptionStornoReason', OptionStornoReason, 'RelatedToInvoiceNum', RelatedToInvoiceNum, 'RelatedToInvoiceDateTime', RelatedToInvoiceDateTime, 'RelatedToRcpNum', RelatedToRcpNum, 'FMNum', FMNum, 'RelatedToURN', RelatedToURN);
};

/**
 * Program automatic daily report printing or not printing parameter.
 * @param {Tremol.Enums.OptionDailyReport} OptionDailyReport 1 symbol with value: 
 - '1' - Print automatic Z report 
 - '0' - Generate automatic Z report
 */
Tremol.FP.prototype.ProgramDailyReportParameter = function (OptionDailyReport) {
	return this.do('ProgramDailyReportParameter', 'OptionDailyReport', OptionDailyReport);
};

/**
 * Prints an operator's report for a specified operator (0 = all operators) with or without zeroing ('Z' or 'X'). When a 'Z' value is specified the report should include all operators.
 * @param {Tremol.Enums.OptionZeroing} OptionZeroing with following values: 
 - 'Z' - Zeroing 
 - 'X' - Without zeroing
 * @param {number} Number Symbols from 0 to 20 corresponding to operator's number 
,0 for all operators
 */
Tremol.FP.prototype.PrintOperatorReport = function (OptionZeroing, Number) {
	return this.do('PrintOperatorReport', 'OptionZeroing', OptionZeroing, 'Number', Number);
};

/**
 * @typedef {Object} StatusRes
 * @property {boolean} FM_Read_only FM Read only
 * @property {boolean} Power_down_in_opened_fiscal_receipt Power down in opened fiscal receipt
 * @property {boolean} Printer_not_ready_overheat Printer not ready - overheat
 * @property {boolean} DateTime_not_set DateTime not set
 * @property {boolean} DateTime_wrong DateTime wrong
 * @property {boolean} RAM_reset RAM reset
 * @property {boolean} Hardware_clock_error Hardware clock error
 * @property {boolean} Printer_not_ready_no_paper Printer not ready - no paper
 * @property {boolean} Reports_registers_Overflow Reports registers Overflow
 * @property {boolean} Customer_report_is_not_zeroed Customer report is not zeroed
 * @property {boolean} Daily_report_is_not_zeroed Daily report is not zeroed
 * @property {boolean} Article_report_is_not_zeroed Article report is not zeroed
 * @property {boolean} Operator_report_is_not_zeroed Operator report is not zeroed
 * @property {boolean} Non_printed_copy Non-printed copy
 * @property {boolean} Opened_Non_fiscal_Receipt Opened Non-fiscal Receipt
 * @property {boolean} Opened_Fiscal_Receipt Opened Fiscal Receipt
 * @property {boolean} Opened_Fiscal_Detailed_Receipt Opened Fiscal Detailed Receipt
 * @property {boolean} Opened_Fiscal_Receipt_with_VAT Opened Fiscal Receipt with VAT
 * @property {boolean} Opened_Invoice_Fiscal_Receipt Opened Invoice Fiscal Receipt
 * @property {boolean} SD_card_near_full SD card near full
 * @property {boolean} SD_card_full SD card full
 * @property {boolean} No_FM_module No FM module
 * @property {boolean} FM_error FM error
 * @property {boolean} FM_full FM full
 * @property {boolean} FM_near_full FM near full
 * @property {boolean} Decimal_point Decimal point (1=fract, 0=whole)
 * @property {boolean} FM_fiscalized FM fiscalized
 * @property {boolean} FM_produced FM produced
 * @property {boolean} Printer_automatic_cutting Printer: automatic cutting
 * @property {boolean} External_display_transparent_display External display: transparent display
 * @property {boolean} Speed_is_9600 Speed is 9600
 * @property {boolean} Drawer_automatic_opening Drawer: automatic opening
 * @property {boolean} Customer_logo_included_in_the_receipt Customer logo included in the receipt
 * @property {boolean} Wrong_SIM_card Wrong SIM card
 * @property {boolean} Blocking_3_days_without_mobile_operator Blocking 3 days without mobile operator
 * @property {boolean} No_task_from_NRA No task from NRA
 * @property {boolean} Wrong_SD_card Wrong SD card
 * @property {boolean} Deregistered Deregistered
 * @property {boolean} No_SIM_card No SIM card
 * @property {boolean} No_GPRS_Modem No GPRS Modem
 * @property {boolean} No_mobile_operator No mobile operator
 * @property {boolean} No_GPRS_service No GPRS service
 * @property {boolean} Near_end_of_paper Near end of paper
 * @property {boolean} Unsent_data_for_24_hours Unsent data for 24 hours
 */

/**
 * Provides detailed 7-byte information about the current status of the fiscal printer.
 * @return {StatusRes}
 */
Tremol.FP.prototype.ReadStatus = function () {
	return this.do('ReadStatus');
};

/**
 * Opens a fiscal receipt assigned to the specified operator number and operator password, parameters for receipt format, print VAT, printing type and unique receipt number.
 * @param {number} OperNum Symbols from 1 to 20 corresponding to operator's number
 * @param {string} OperPass 6 symbols for operator's password
 * @param {Tremol.Enums.OptionReceiptFormat} OptionReceiptFormat 1 symbol with value: 
 - '1' - Detailed 
 - '0' - Brief
 * @param {Tremol.Enums.OptionPrintVAT} OptionPrintVAT 1 symbol with value:  
 - '1' - Yes 
 - '0' - No
 * @param {Tremol.Enums.OptionFiscalRcpPrintType} OptionFiscalRcpPrintType 1 symbol with value: 
- '0' - Step by step printing 
- '2' - Postponed printing 
- '4' - Buffered printing
 * @param {string=} UniqueReceiptNumber Up to 24 symbols for unique receipt number. 
NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: 
* ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, 
* ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, 
* YYYYYYY - 7 symbols [0-9] for next number of the receipt
 */
Tremol.FP.prototype.OpenReceipt = function (OperNum, OperPass, OptionReceiptFormat, OptionPrintVAT, OptionFiscalRcpPrintType, UniqueReceiptNumber) {
	return this.do('OpenReceipt', 'OperNum', OperNum, 'OperPass', OperPass, 'OptionReceiptFormat', OptionReceiptFormat, 'OptionPrintVAT', OptionPrintVAT, 'OptionFiscalRcpPrintType', OptionFiscalRcpPrintType, 'UniqueReceiptNumber', UniqueReceiptNumber);
};

/**
 * Read or Store Electronic Journal Report by number of Z report blocks, CSV format option and document content. If CSV format is set the content can includes only fiscal receipts. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.
 * @param {Tremol.Enums.OptionStorageReport} OptionStorageReport 1 character with value 
 - 'j0' - To PC 
 - 'j2' - To USB Flash Drive 
 - 'j4' - To SD card
 * @param {Tremol.Enums.OptionCSVformat} OptionCSVformat 1 symbol with value: 
 - 'C' - Yes 
 - 'X' - No
 * @param {string} FlagsReceipts 1 symbol for Receipts included in EJ: 
Flags.7=0 
Flags.6=1 
Flags.5=1 Yes, Flags.5=0 No (Include PO) 
Flags.4=1 Yes, Flags.4=0 No (Include RA) 
Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) 
Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) 
Flags.1=1 Yes, Flags.1=0 No (Include Invoice) 
Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)
 * @param {string} FlagsReports 1 symbol for Reports included in EJ: 
Flags.7=0 
Flags.6=1 
Flags.5=0 
Flags.4=1 Yes, Flags.4=0 No (Include FM reports) 
Flags.3=1 Yes, Flags.3=0 No (Include Other reports) 
Flags.2=1 Yes, Flags.2=0 No (Include Daily X) 
Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) 
Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)
 * @param {number} StartZNum 4 symbols for initial number report in format ####
 * @param {number} EndZNum 4 symbols for final number report in format ####
 */
Tremol.FP.prototype.ReadEJByZBlocksCustom = function (OptionStorageReport, OptionCSVformat, FlagsReceipts, FlagsReports, StartZNum, EndZNum) {
	return this.do('ReadEJByZBlocksCustom', 'OptionStorageReport', OptionStorageReport, 'OptionCSVformat', OptionCSVformat, 'FlagsReceipts', FlagsReceipts, 'FlagsReports', FlagsReports, 'StartZNum', StartZNum, 'EndZNum', EndZNum);
};

/**
 * Sets the date and time and prints out the current values.
 * @param {Date} DateTime Date Time parameter in format: DD-MM-YY HH:MM:SS
 */
Tremol.FP.prototype.SetDateTime = function (DateTime) {
	return this.do('SetDateTime', 'DateTime', DateTime);
};

/**
 * Stores a block containing the number format into the fiscal memory. Print the current status on the printer.
 * @param {string} Password 6-symbols string
 * @param {Tremol.Enums.OptionDecimalPointPosition} OptionDecimalPointPosition 1 symbol with values: 
 - '0'- Whole numbers 
 - '2' - Fractions
 */
Tremol.FP.prototype.ProgDecimalPointPosition = function (Password, OptionDecimalPointPosition) {
	return this.do('ProgDecimalPointPosition', 'Password', Password, 'OptionDecimalPointPosition', OptionDecimalPointPosition);
};

/**
 * Provides information about electronic signature of last daily report.
 * @return {string}
 */
Tremol.FP.prototype.ReadLastDailySignature = function () {
	return this.do('ReadLastDailySignature');
};

/**
 * @typedef {Object} DailyRA_OldRes
 * @property {number} AmountPayment0 Up to 13 symbols for the accumulated amount by payment type 0
 * @property {number} AmountPayment1 Up to 13 symbols for the accumulated amount by payment type 1
 * @property {number} AmountPayment2 Up to 13 symbols for the accumulated amount by payment type 2
 * @property {number} AmountPayment3 Up to 13 symbols for the accumulated amount by payment type 3
 * @property {number} AmountPayment4 Up to 13 symbols for the accumulated amount by payment type 4
 * @property {number} RANum Up to 5 symbols for the total number of operations
 * @property {number} SumAllPayment Up to 13 symbols to sum all payments
 */

/**
 * Provides information about the RA amounts by type of payment and the total number of operations. Command works for KL version 2 devices.
 * @return {DailyRA_OldRes}
 */
Tremol.FP.prototype.ReadDailyRA_Old = function () {
	return this.do('ReadDailyRA_Old');
};

/**
 * Provides the content of the header UIC prefix.
 * @return {string}
 */
Tremol.FP.prototype.ReadHeaderUICPrefix = function () {
	return this.do('ReadHeaderUICPrefix');
};

/**
 * @typedef {Object} PLUpriceRes
 * @property {number} PLUNum 5 symbols for article number with leading zeroes in format #####
 * @property {number} Price Up to 10 symbols for article price
 * @property {Tremol.Enums.OptionPrice} OptionPrice 1 symbol for price flag with next value: 
 - '0'- Free price is disable valid only programmed price 
 - '1'- Free price is enable 
 - '2'- Limited price
 */

/**
 * Provides information about the price and price type of the specified article.
 * @param {number} PLUNum 5 symbols for article number with leading zeroes in format: #####
 * @return {PLUpriceRes}
 */
Tremol.FP.prototype.ReadPLUprice = function (PLUNum) {
	return this.do('ReadPLUprice', 'PLUNum', PLUNum);
};

/**
 * @typedef {Object} OperatorNamePasswordRes
 * @property {number} Number Symbol from 1 to 20 corresponding to the number of operator
 * @property {string} Name 20 symbols for operator's name
 * @property {string} Password 6 symbols for operator's password
 */

/**
 * Provides information about operator's name and password.
 * @param {number} Number Symbol from 1 to 20 corresponding to the number of 
operators.
 * @return {OperatorNamePasswordRes}
 */
Tremol.FP.prototype.ReadOperatorNamePassword = function (Number) {
	return this.do('ReadOperatorNamePassword', 'Number', Number);
};

/**
 * @typedef {Object} DailyCountersByOperatorRes
 * @property {number} OperNum Symbols from 1 to 20 corresponding to operator's number
 * @property {number} WorkOperatorsCounter Up to 5 symbols for number of the work operators
 * @property {Date} LastOperatorReportDateTime 16 symbols for date and time of the last operator's report in 
format DD-MM-YYYY HH:MM
 */

/**
 * Read the last operator's report number and date and time.
 * @param {number} OperNum Symbols from 1 to 20 corresponding to operator's 
number
 * @return {DailyCountersByOperatorRes}
 */
Tremol.FP.prototype.ReadDailyCountersByOperator = function (OperNum) {
	return this.do('ReadDailyCountersByOperator', 'OperNum', OperNum);
};

/**
 * @typedef {Object} LastDailyReportAvailableAmountsRes
 * @property {Tremol.Enums.OptionZReportType} OptionZReportType 1 symbol with value: 
 - '0' - Manual 
 - '1' - Automatic 
ZReportNum 4 symbols for Z report number in format ####
 * @property {number} ZreportNum 4 symbols for Z report number in format ####
 * @property {number} CashAvailableAmount Up to 13 symbols for available amounts in cash payment
 * @property {number} CurrencyAvailableAmount Up to 13 symbols for available amounts in currency payment
 */

/**
 * Provides information about daily available amounts in cash and currency, Z daily report type and Z daily report number
 * @return {LastDailyReportAvailableAmountsRes}
 */
Tremol.FP.prototype.ReadLastDailyReportAvailableAmounts = function () {
	return this.do('ReadLastDailyReportAvailableAmounts');
};

/**
 * @typedef {Object} PaymentsRes
 * @property {string} NamePayment0 10 symbols for payment name type 0
 * @property {string} NamePayment1 10 symbols for payment name type 1
 * @property {string} NamePayment2 10 symbols for payment name type 2
 * @property {string} NamePayment3 10 symbols for payment name type 3
 * @property {string} NamePayment4 10 symbols for payment name type 4
 * @property {string} NamePayment5 10 symbols for payment name type 5
 * @property {string} NamePayment6 10 symbols for payment name type 6
 * @property {string} NamePayment7 10 symbols for payment name type 7
 * @property {string} NamePayment8 10 symbols for payment name type 8
 * @property {string} NamePayment9 10 symbols for payment name type 9
 * @property {string} NamePayment10 10 symbols for payment name type 10
 * @property {string} NamePayment11 10 symbols for payment name type 11
 * @property {number} ExchangeRate Up to 10 symbols for exchange rate of payment type 11 in format: ####.#####
 */

/**
 * Provides information about all programmed types of payment, currency name and currency exchange rate.
 * @return {PaymentsRes}
 */
Tremol.FP.prototype.ReadPayments = function () {
	return this.do('ReadPayments');
};

/**
 * Register the sell (for correction use minus sign in the price field) of article with specified VAT. If department is present the relevant accumulations are perfomed in its registers.
 * @param {string} NamePLU 36 symbols for article's name. 34 symbols are printed on paper. 
Symbol 0x7C '|' is new line separator.
 * @param {Tremol.Enums.OptionVATClass} OptionVATClass 1 character for VAT class: 
 - 'А' - VAT Class 0 
 - 'Б' - VAT Class 1 
 - 'В' - VAT Class 2 
 - 'Г' - VAT Class 3 
 - 'Д' - VAT Class 4 
 - 'Е' - VAT Class 5 
 - 'Ж' - VAT Class 6 
 - 'З' - VAT Class 7 
 - '*' - Forbidden
 * @param {number} Price Up to 10 symbols for article's price. Use minus sign '-' for correction
 * @param {number=} Quantity Up to 10 symbols for quantity
 * @param {number=} DiscAddP Up to 7 symbols for percentage of discount/addition. 
Use minus sign '-' for discount
 * @param {number=} DiscAddV Up to 8 symbols for value of discount/addition. 
Use minus sign '-' for discount
 * @param {number=} DepNum 1 symbol for article department 
attachment, formed in the following manner; example: Dep01 = 81h,  
Dep02 = 82h … Dep19 = 93h 
Department range from 1 to 127
 */
Tremol.FP.prototype.SellPLUwithSpecifiedVATfromDep = function (NamePLU, OptionVATClass, Price, Quantity, DiscAddP, DiscAddV, DepNum) {
	return this.do('SellPLUwithSpecifiedVATfromDep', 'NamePLU', NamePLU, 'OptionVATClass', OptionVATClass, 'Price', Price, 'Quantity', Quantity, 'DiscAddP', DiscAddP, 'DiscAddV', DiscAddV, 'DepNum', DepNum);
};

/**
 * Preprogram the name of the payment type.
 * @param {Tremol.Enums.OptionPaymentNum} OptionPaymentNum 1 symbol for payment type  
 - '9' - Payment 9 
 - '10' - Payment 10 
 - '11' - Payment 11
 * @param {string} Name 10 symbols for payment type name
 * @param {number=} Rate Up to 10 symbols for exchange rate in format: ####.#####  
of the 11th payment type, maximal value 0420.00000
 */
Tremol.FP.prototype.ProgPayment = function (OptionPaymentNum, Name, Rate) {
	return this.do('ProgPayment', 'OptionPaymentNum', OptionPaymentNum, 'Name', Name, 'Rate', Rate);
};

/**
 * Prints out a diagnostic receipt.
 */
Tremol.FP.prototype.PrintDiagnostics = function () {
	return this.do('PrintDiagnostics');
};

/**
 * @typedef {Object} DetailedPrinterStatusRes
 * @property {Tremol.Enums.OptionExternalDisplay} OptionExternalDisplay 1 symbol - connection with external display  
 - 'Y' - Yes 
 - 'N' - No
 * @property {string} StatPRN 4 symbols for detailed status of printer (only for printers with ASB) 
N 
byte 
N bit status flag 
ST0 
0 Reserved 
1 Reserved 
2 Signal level for drawer 
3 Printer not ready 
4 Reserved 
5 Open cover 
6 Paper feed status 
7 Reserved 
   
ST1 
0 Reserved 
1 Reserved 
2 Reserved 
3 Cutter error 
4 Reserved 
5 Fatal error
 * @property {string} FlagServiceJumper 1 symbol with value: 
 - 'J' - Yes  
 - ' ' - No
 */

/**
 * Provides additional status information
 * @return {DetailedPrinterStatusRes}
 */
Tremol.FP.prototype.ReadDetailedPrinterStatus = function () {
	return this.do('ReadDetailedPrinterStatus');
};

/**
 * Opens a fiscal invoice receipt assigned to the specified operator number and operator password with free info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.
 * @param {number} OperNum Symbol from 1 to 20 corresponding to operator's number
 * @param {string} OperPass 6 symbols for operator's password
 * @param {Tremol.Enums.OptionInvoicePrintType} OptionInvoicePrintType 1 symbol with value: 
- '1' - Step by step printing 
- '3' - Postponed Printing 
- '5' - Buffered Printing
 * @param {string} Recipient 26 symbols for Invoice recipient
 * @param {string} Buyer 16 symbols for Invoice buyer
 * @param {string} VATNumber 13 symbols for customer Fiscal number
 * @param {string} UIC 13 symbols for customer Unique Identification Code
 * @param {string} Address 30 symbols for Address
 * @param {Tremol.Enums.OptionUICType} OptionUICType 1 symbol for type of Unique Identification Code:  
 - '0' - Bulstat 
 - '1' - EGN 
 - '2' - Foreigner Number 
 - '3' - NRA Official Number
 * @param {string=} UniqueReceiptNumber Up to 24 symbols for unique receipt number. 
NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: 
* ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, 
* ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, 
* YYYYYYY - 7 symbols [0-9] for next number of the receipt
 */
Tremol.FP.prototype.OpenInvoiceWithFreeCustomerData = function (OperNum, OperPass, OptionInvoicePrintType, Recipient, Buyer, VATNumber, UIC, Address, OptionUICType, UniqueReceiptNumber) {
	return this.do('OpenInvoiceWithFreeCustomerData', 'OperNum', OperNum, 'OperPass', OperPass, 'OptionInvoicePrintType', OptionInvoicePrintType, 'Recipient', Recipient, 'Buyer', Buyer, 'VATNumber', VATNumber, 'UIC', UIC, 'Address', Address, 'OptionUICType', OptionUICType, 'UniqueReceiptNumber', UniqueReceiptNumber);
};

/**
 * Program the contents of a footer lines.
 * @param {string} FooterText TextLength symbols for footer line
 */
Tremol.FP.prototype.ProgFooter = function (FooterText) {
	return this.do('ProgFooter', 'FooterText', FooterText);
};

/**
 * Print a copy of the last receipt issued. When FD parameter for duplicates is enabled.
 */
Tremol.FP.prototype.PrintLastReceiptDuplicate = function () {
	return this.do('PrintLastReceiptDuplicate');
};

/**
 * @typedef {Object} TCP_PasswordRes
 * @property {number} PassLength (Length) Up to 3 symbols for the password length
 * @property {string} Password Up to 100 symbols for the TCP password
 */

/**
 * Provides information about device's TCP password.
 * @return {TCP_PasswordRes}
 */
Tremol.FP.prototype.ReadTCP_Password = function () {
	return this.do('ReadTCP_Password');
};

/**
 * Stores a block containing the values of the VAT rates into the fiscal memory. Print the values on the printer.
 * @param {string} Password 6-symbols string
 * @param {number} VATrate0 Value of VAT rate А from 6 symbols in format ##.##
 * @param {number} VATrate1 Value of VAT rate Б from 6 symbols in format ##.##
 * @param {number} VATrate2 Value of VAT rate В from 6 symbols in format ##.##
 * @param {number} VATrate3 Value of VAT rate Г from 6 symbols in format ##.##
 * @param {number} VATrate4 Value of VAT rate Д from 6 symbols in format ##.##
 * @param {number} VATrate5 Value of VAT rate Е from 6 symbols in format ##.##
 * @param {number} VATrate6 Value of VAT rate Ж from 6 symbols in format ##.##
 * @param {number} VATrate7 Value of VAT rate З from 6 symbols in format ##.##
 */
Tremol.FP.prototype.ProgVATrates = function (Password, VATrate0, VATrate1, VATrate2, VATrate3, VATrate4, VATrate5, VATrate6, VATrate7) {
	return this.do('ProgVATrates', 'Password', Password, 'VATrate0', VATrate0, 'VATrate1', VATrate1, 'VATrate2', VATrate2, 'VATrate3', VATrate3, 'VATrate4', VATrate4, 'VATrate5', VATrate5, 'VATrate6', VATrate6, 'VATrate7', VATrate7);
};

/**
 * Read or Store Electronic Journal Report by initial to end date, CSV format option and document content. If CSV format is set the content can includes only fiscal receipts. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.
 * @param {Tremol.Enums.OptionStorageReport} OptionStorageReport 2 characters with value: 
 - 'j0' - To PC 
 - 'j2' - To USB Flash Drive 
 - 'j4' - To SD card
 * @param {Tremol.Enums.OptionCSVformat} OptionCSVformat 1 symbol with value: 
 - 'C' - Yes 
 - 'X' - No
 * @param {string} FlagsReceipts 1 symbol for Receipts included in EJ: 
Flags.7=0 
Flags.6=1, 0=w 
Flags.5=1 Yes, Flags.5=0 No (Include PO) 
Flags.4=1 Yes, Flags.4=0 No (Include RA) 
Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) 
Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) 
Flags.1=1 Yes, Flags.1=0 No (Include Invoice) 
Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)
 * @param {string} FlagsReports 1 symbol for Reports included in EJ: 
Flags.7=0 
Flags.6=1, 0=w 
Flags.5=1, 0=w 
Flags.4=1 Yes, Flags.4=0 No (Include FM reports) 
Flags.3=1 Yes, Flags.3=0 No (Include Other reports) 
Flags.2=1 Yes, Flags.2=0 No (Include Daily X) 
Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) 
Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)
 * @param {Date} StartRepFromDate 6 symbols for initial date in the DDMMYY format
 * @param {Date} EndRepFromDate 6 symbols for final date in the DDMMYY format
 */
Tremol.FP.prototype.ReadEJByDateCustom = function (OptionStorageReport, OptionCSVformat, FlagsReceipts, FlagsReports, StartRepFromDate, EndRepFromDate) {
	return this.do('ReadEJByDateCustom', 'OptionStorageReport', OptionStorageReport, 'OptionCSVformat', OptionCSVformat, 'FlagsReceipts', FlagsReceipts, 'FlagsReports', FlagsReports, 'StartRepFromDate', StartRepFromDate, 'EndRepFromDate', EndRepFromDate);
};

/**
 * Program device's Bluetooth module to be enabled or disabled. To apply use -SaveNetworkSettings()
 * @param {Tremol.Enums.OptionBTstatus} OptionBTstatus 1 symbol with value: 
 - '0' - Disabled 
 - '1' - Enabled
 */
Tremol.FP.prototype.SetBluetooth_Status = function (OptionBTstatus) {
	return this.do('SetBluetooth_Status', 'OptionBTstatus', OptionBTstatus);
};

/**
 * Register the sell (for correction use minus sign in the price field) of article with specified VAT. If department is present the relevant accumulations are perfomed in its registers.
 * @param {string} NamePLU 36 symbols for article's name. 34 symbols are printed on paper. 
Symbol 0x7C '|' is new line separator.
 * @param {Tremol.Enums.OptionVATClass} OptionVATClass 1 character for VAT class: 
 - 'А' - VAT Class 0 
 - 'Б' - VAT Class 1 
 - 'В' - VAT Class 2 
 - 'Г' - VAT Class 3 
 - 'Д' - VAT Class 4 
 - 'Е' - VAT Class 5 
 - 'Ж' - VAT Class 6 
 - 'З' - VAT Class 7 
 - '*' - Forbidden
 * @param {number} Price Up to 10 symbols for article's price. Use minus sign '-' for correction
 * @param {number=} Quantity Up to 10 symbols for quantity
 * @param {number=} DiscAddP Up to 7 symbols for percentage of discount/addition. 
Use minus sign '-' for discount
 * @param {number=} DiscAddV Up to 8 symbols for value of discount/addition. 
Use minus sign '-' for discount
 * @param {number=} DepNum Up to 3 symbols for department number
 */
Tremol.FP.prototype.SellPLUwithSpecifiedVATfor200DepRangeDevice = function (NamePLU, OptionVATClass, Price, Quantity, DiscAddP, DiscAddV, DepNum) {
	return this.do('SellPLUwithSpecifiedVATfor200DepRangeDevice', 'NamePLU', NamePLU, 'OptionVATClass', OptionVATClass, 'Price', Price, 'Quantity', Quantity, 'DiscAddP', DiscAddP, 'DiscAddV', DiscAddV, 'DepNum', DepNum);
};

/**
 * Print a brief FM report by initial and end FM report number.
 * @param {number} StartZNum 4 symbols for the initial FM report number included in report, format ####
 * @param {number} EndZNum 4 symbols for the final FM report number included in report, format ####
 */
Tremol.FP.prototype.PrintBriefFMReportByZBlocks = function (StartZNum, EndZNum) {
	return this.do('PrintBriefFMReportByZBlocks', 'StartZNum', StartZNum, 'EndZNum', EndZNum);
};

/**
 * Define Fiscal device type. The command is allowed only in non- fiscal mode, before fiscalization and after deregistration before the next fiscalization. The type of device can be read by Version command 0x21.
 * @param {Tremol.Enums.OptionFDType} OptionFDType 1 symbol for fiscal device type with value: 
 - '0' - FPr for Fuel type 3 
 - '1' - Main FPr for Fuel system type 31 
 - '2' - ECR for online store type 11 
 - '3' - FPr for online store type 21  
 - '*' - reset default type
 * @param {string} Password 3-symbols string
 */
Tremol.FP.prototype.SetFiscalDeviceType = function (OptionFDType, Password) {
	return this.do('SetFiscalDeviceType', 'OptionFDType', OptionFDType, 'Password', Password);
};

/**
 * Read Electronic Journal Report by initial to end date.
 * @param {Tremol.Enums.OptionReportFormat} OptionReportFormat 1 character with value 
 - 'J0' - Detailed EJ 
 - 'J8' - Brief EJ
 * @param {Date} StartRepFromDate 6 symbols for initial date in the DDMMYY format
 * @param {Date} EndRepFromDate 6 symbols for final date in the DDMMYY format
 */
Tremol.FP.prototype.ReadEJByDate = function (OptionReportFormat, StartRepFromDate, EndRepFromDate) {
	return this.do('ReadEJByDate', 'OptionReportFormat', OptionReportFormat, 'StartRepFromDate', StartRepFromDate, 'EndRepFromDate', EndRepFromDate);
};

/**
 * @typedef {Object} PLUbarcodeRes
 * @property {number} PLUNum 5 symbols for article number with leading zeroes in format #####
 * @property {string} Barcode 13 symbols for article barcode
 */

/**
 * Provides information about the barcode of the specified article.
 * @param {number} PLUNum 5 symbols for article number with leading zeroes in format: #####
 * @return {PLUbarcodeRes}
 */
Tremol.FP.prototype.ReadPLUbarcode = function (PLUNum) {
	return this.do('ReadPLUbarcode', 'PLUNum', PLUNum);
};

/**
 * @typedef {Object} DailyPObyOperator_OldRes
 * @property {number} OperNum Symbols from 1 to 20 corresponding to operator's number
 * @property {number} AmountPO_Payment0 Up to 13 symbols for the PO by type of payment 0
 * @property {number} AmountPO_Payment1 Up to 13 symbols for the PO by type of payment 1
 * @property {number} AmountPO_Payment2 Up to 13 symbols for the PO by type of payment 2
 * @property {number} AmountPO_Payment3 Up to 13 symbols for the PO by type of payment 3
 * @property {number} AmountPO_Payment4 Up to 13 symbols for the PO by type of payment 4
 * @property {number} NoPO Up to 5 symbols for the total number of operations
 */

/**
 * Read the PO by type of payment and the total number of operations by specified operator. Command works for KL version 2 devices.
 * @param {number} OperNum Symbols from 1 to 20 corresponding to operator's number
 * @return {DailyPObyOperator_OldRes}
 */
Tremol.FP.prototype.ReadDailyPObyOperator_Old = function (OperNum) {
	return this.do('ReadDailyPObyOperator_Old', 'OperNum', OperNum);
};

/**
 * Set data for the state department number from the internal FD database. Parameters Price, OptionDepPrice and AdditionalName are not obligatory and require the previous not obligatory parameter.
 * @param {number} Number 3 symbols for department number in format ###
 * @param {string} Name 20 characters department name
 * @param {Tremol.Enums.OptionVATClass} OptionVATClass 1 character for VAT class: 
 - 'А' - VAT Class 0 
 - 'Б' - VAT Class 1 
 - 'В' - VAT Class 2 
 - 'Г' - VAT Class 3 
 - 'Д' - VAT Class 4 
 - 'Е' - VAT Class 5 
 - 'Ж' - VAT Class 6 
 - 'З' - VAT Class 7 
 - '*' - Forbidden
 * @param {number=} Price Up to 10 symbols for department price
 * @param {Tremol.Enums.OptionDepPrice=} OptionDepPrice 1 symbol for Department price flags with next value:  
- '0' - Free price disabled  
- '1' - Free price enabled  
- '2' - Limited price  
- '4' - Free price disabled for single transaction  
- '5' - Free price enabled for single transaction  
- '6' - Limited price for single transaction
 * @param {string=} AdditionalName 14 characters additional department name
 */
Tremol.FP.prototype.ProgDepartment = function (Number, Name, OptionVATClass, Price, OptionDepPrice, AdditionalName) {
	return this.do('ProgDepartment', 'Number', Number, 'Name', Name, 'OptionVATClass', OptionVATClass, 'Price', Price, 'OptionDepPrice', OptionDepPrice, 'AdditionalName', AdditionalName);
};

/**
 * Read a detailed FM payments report by initial and end date.
 * @param {Date} StartDate 6 symbols for initial date in the DDMMYY format
 * @param {Date} EndDate 6 symbols for final date in the DDMMYY format
 */
Tremol.FP.prototype.ReadDetailedFMPaymentsReportByDate = function (StartDate, EndDate) {
	return this.do('ReadDetailedFMPaymentsReportByDate', 'StartDate', StartDate, 'EndDate', EndDate);
};

/**
 * Sets device's idle timeout setting. Set timeout for closing the connection if there is an inactivity. Maximal value - 7200, minimal value 0. 0 is for never close the connection. This option can be used only if the device has LAN or WiFi. To apply use - SaveNetworkSettings()
 * @param {number} IdleTimeout 4 symbols for Idle timeout in format ####
 */
Tremol.FP.prototype.SetIdle_Timeout = function (IdleTimeout) {
	return this.do('SetIdle_Timeout', 'IdleTimeout', IdleTimeout);
};

/**
 * Read device TCP Auto Start status
 * @return {Tremol.Enums.OptionTCPAutoStart}
 */
Tremol.FP.prototype.ReadTCP_AutoStartStatus = function () {
	return this.do('ReadTCP_AutoStartStatus');
};

/**
 * Prints the programmed graphical logo with the stated number.
 * @param {number} Number Number of logo to be printed. If missing, prints logo with number 0
 */
Tremol.FP.prototype.PrintLogo = function (Number) {
	return this.do('PrintLogo', 'Number', Number);
};

/**
 * @typedef {Object} DailyGeneralRegistersByOperatorRes
 * @property {number} OperNum Symbols from 1 to 20 corresponding to operator's number
 * @property {number} CustomersNum Up to 5 symbols for number of customers
 * @property {number} DiscountsNum Up to 5 symbols for number of discounts
 * @property {number} DiscountsAmount Up to 13 symbols for accumulated amount of discounts
 * @property {number} AdditionsNum Up to 5 symbols for number ofadditions
 * @property {number} AdditionsAmount Up to 13 symbols for accumulated amount of additions
 * @property {number} CorrectionsNum Up to 5 symbols for number of corrections
 * @property {number} CorrectionsAmount Up to 13 symbols for accumulated amount of corrections
 */

/**
 * Read the total number of customers, discounts, additions, corrections and accumulated amounts by specified operator.
 * @param {number} OperNum Symbols from 1 to 20 corresponding to operator's number
 * @return {DailyGeneralRegistersByOperatorRes}
 */
Tremol.FP.prototype.ReadDailyGeneralRegistersByOperator = function (OperNum) {
	return this.do('ReadDailyGeneralRegistersByOperator', 'OperNum', OperNum);
};

/**
 * Read a brief FM report by initial and end FM report number.
 * @param {number} StartZNum 4 symbols for the initial FM report number included in report, format ####
 * @param {number} EndZNum 4 symbols for the final FM report number included in report, format ####
 */
Tremol.FP.prototype.ReadBriefFMReportByZBlocks = function (StartZNum, EndZNum) {
	return this.do('ReadBriefFMReportByZBlocks', 'StartZNum', StartZNum, 'EndZNum', EndZNum);
};

/**
 * @typedef {Object} DailyRAbyOperator_OldRes
 * @property {number} OperNum Symbols from 1 to 20 corresponding to operator's number
 * @property {number} AmountRA_Payment0 Up to 13 symbols for the RA by type of payment 0
 * @property {number} AmountRA_Payment1 Up to 13 symbols for the RA by type of payment 1
 * @property {number} AmountRA_Payment2 Up to 13 symbols for the RA by type of payment 2
 * @property {number} AmountRA_Payment3 Up to 13 symbols for the RA by type of payment 3
 * @property {number} AmountRA_Payment4 Up to 13 symbols for the RA by type of payment 4
 * @property {number} NoRA Up to 5 symbols for the total number of operations
 */

/**
 * Read the RA by type of payment and the total number of operations by specified operator. Command works for KL version 2 devices.
 * @param {number} OperNum Symbols from 1 to 20 corresponding to operator's number
 * @return {DailyRAbyOperator_OldRes}
 */
Tremol.FP.prototype.ReadDailyRAbyOperator_Old = function (OperNum) {
	return this.do('ReadDailyRAbyOperator_Old', 'OperNum', OperNum);
};

/**
 * Print a detailed FM report by initial and end FM report number.
 * @param {number} StartZNum 4 symbols for the initial report number included in report, format ####
 * @param {number} EndZNum 4 symbols for the final report number included in report, format ####
 */
Tremol.FP.prototype.PrintDetailedFMReportByZBlocks = function (StartZNum, EndZNum) {
	return this.do('PrintDetailedFMReportByZBlocks', 'StartZNum', StartZNum, 'EndZNum', EndZNum);
};

/**
 * Print Customer X or Z report
 * @param {Tremol.Enums.OptionZeroing} OptionZeroing with following values: 
 - 'Z' -Zeroing 
 - 'X' - Without zeroing
 */
Tremol.FP.prototype.PrintCustomerReport = function (OptionZeroing) {
	return this.do('PrintCustomerReport', 'OptionZeroing', OptionZeroing);
};

/**
 * Depending on the parameter prints:  − daily fiscal report with zeroing and fiscal memory record, preceded by Electronic Journal report print ('Z'); − daily fiscal report without zeroing ('X');
 * @param {Tremol.Enums.OptionZeroing} OptionZeroing 1 character with following values: 
 - 'Z' - Zeroing 
 - 'X' - Without zeroing
 */
Tremol.FP.prototype.PrintDailyReport = function (OptionZeroing) {
	return this.do('PrintDailyReport', 'OptionZeroing', OptionZeroing);
};

/**
 * Provides the content of the footer line.
 * @return {string}
 */
Tremol.FP.prototype.ReadFooter = function () {
	return this.do('ReadFooter');
};

/**
 * Generate Z-daily report without printing
 */
Tremol.FP.prototype.ZDailyReportNoPrint = function () {
	return this.do('ZDailyReportNoPrint');
};

/**
 * Opens a non-fiscal receipt assigned to the specified operator number, operator password and print type.
 * @param {number} OperNum Symbols from 1 to 20 corresponding to operator's 
number
 * @param {string} OperPass 6 symbols for operator's password
 * @param {Tremol.Enums.OptionNonFiscalPrintType=} OptionNonFiscalPrintType 1 symbol with value: 
- '0' - Step by step printing 
- '1' - Postponed Printing
 */
Tremol.FP.prototype.OpenNonFiscalReceipt = function (OperNum, OperPass, OptionNonFiscalPrintType) {
	return this.do('OpenNonFiscalReceipt', 'OperNum', OperNum, 'OperPass', OperPass, 'OptionNonFiscalPrintType', OptionNonFiscalPrintType);
};

/**
 * Read a brief FM payments report by initial and end date.
 * @param {Date} StartDate 6 symbols for initial date in the DDMMYY format
 * @param {Date} EndDate 6 symbols for final date in the DDMMYY format
 */
Tremol.FP.prototype.ReadBriefFMPaymentsReportByDate = function (StartDate, EndDate) {
	return this.do('ReadBriefFMPaymentsReportByDate', 'StartDate', StartDate, 'EndDate', EndDate);
};

/**
 * Calculate the subtotal amount with printing and display visualization options. Provide information about values of the calculated amounts. If a percent or value discount/addition has been specified the subtotal and the discount/addition value will be printed regardless the parameter for printing.
 * @param {Tremol.Enums.OptionPrinting} OptionPrinting 1 symbol with value: 
 - '1' - Yes 
 - '0' - No
 * @param {Tremol.Enums.OptionDisplay} OptionDisplay 1 symbol with value: 
 - '1' - Yes 
 - '0' - No
 * @param {number=} DiscAddV Up to 8 symbols for the value of the 
discount/addition. Use minus sign '-' for discount
 * @param {number=} DiscAddP Up to 7 symbols for the percentage value of the 
discount/addition. Use minus sign '-' for discount
 * @return {number}
 */
Tremol.FP.prototype.Subtotal = function (OptionPrinting, OptionDisplay, DiscAddV, DiscAddP) {
	return this.do('Subtotal', 'OptionPrinting', OptionPrinting, 'OptionDisplay', OptionDisplay, 'DiscAddV', DiscAddV, 'DiscAddP', DiscAddP);
};

/**
 * Program NBL parameter to be monitored by the fiscal device.
 * @param {Tremol.Enums.OptionNBL} OptionNBL 1 symbol with value: 
 - '0' - No 
 - '1' - Yes
 */
Tremol.FP.prototype.ProgramNBLParameter = function (OptionNBL) {
	return this.do('ProgramNBLParameter', 'OptionNBL', OptionNBL);
};

/**
 * Print a detailed FM payments report by initial and end date.
 * @param {Date} StartDate 6 symbols for initial date in the DDMMYY format
 * @param {Date} EndDate 6 symbols for final date in the DDMMYY format
 */
Tremol.FP.prototype.PrintDetailedFMPaymentsReportByDate = function (StartDate, EndDate) {
	return this.do('PrintDetailedFMPaymentsReportByDate', 'StartDate', StartDate, 'EndDate', EndDate);
};

/**
 * @typedef {Object} DailyRARes
 * @property {number} AmountPayment0 Up to 13 symbols for the accumulated amount by payment type 0
 * @property {number} AmountPayment1 Up to 13 symbols for the accumulated amount by payment type 1
 * @property {number} AmountPayment2 Up to 13 symbols for the accumulated amount by payment type 2
 * @property {number} AmountPayment3 Up to 13 symbols for the accumulated amount by payment type 3
 * @property {number} AmountPayment4 Up to 13 symbols for the accumulated amount by payment type 4
 * @property {number} AmountPayment5 Up to 13 symbols for the accumulated amount by payment type 5
 * @property {number} AmountPayment6 Up to 13 symbols for the accumulated amount by payment type 6
 * @property {number} AmountPayment7 Up to 13 symbols for the accumulated amount by payment type 7
 * @property {number} AmountPayment8 Up to 13 symbols for the accumulated amount by payment type 8
 * @property {number} AmountPayment9 Up to 13 symbols for the accumulated amount by payment type 9
 * @property {number} AmountPayment10 Up to 13 symbols for the accumulated amount by payment type 10
 * @property {number} AmountPayment11 Up to 13 symbols for the accumulated amount by payment type 11
 * @property {number} RANum Up to 5 symbols for the total number of operations
 * @property {number} SumAllPayment Up to 13 symbols to sum all payments
 */

/**
 * Provides information about the RA amounts by type of payment and the total number of operations.
 * @return {DailyRARes}
 */
Tremol.FP.prototype.ReadDailyRA = function () {
	return this.do('ReadDailyRA');
};

/**
 * @typedef {Object} GeneralDailyRegistersRes
 * @property {number} CustomersNum Up to 5 symbols for number of customers
 * @property {number} DiscountsNum Up to 5 symbols for number of discounts
 * @property {number} DiscountsAmount Up to 13 symbols for accumulated amount of discounts
 * @property {number} AdditionsNum Up to 5 symbols for number of additions
 * @property {number} AdditionsAmount Up to 13 symbols for accumulated amount of additions
 * @property {number} CorrectionsNum Up to 5 symbols for number of corrections
 * @property {number} CorrectionsAmount Up to 13 symbols for accumulated amount of corrections
 */

/**
 * Provides information about the number of customers (number of fiscal receipt issued), number of discounts, additions and corrections made and the accumulated amounts.
 * @return {GeneralDailyRegistersRes}
 */
Tremol.FP.prototype.ReadGeneralDailyRegisters = function () {
	return this.do('ReadGeneralDailyRegisters');
};

/**
 * Provides the content of the Display Greeting message.
 * @return {string}
 */
Tremol.FP.prototype.ReadDisplayGreetingMessage = function () {
	return this.do('ReadDisplayGreetingMessage');
};

/**
 * Read a detailed FM report by initial and end FM report number.
 * @param {number} StartZNum 4 symbols for the initial report number included in report, format ####
 * @param {number} EndZNum 4 symbols for the final report number included in report, format ####
 */
Tremol.FP.prototype.ReadDetailedFMReportByZBlocks = function (StartZNum, EndZNum) {
	return this.do('ReadDetailedFMReportByZBlocks', 'StartZNum', StartZNum, 'EndZNum', EndZNum);
};

/**
 * Prints a brief payments from the FM.
 */
Tremol.FP.prototype.PrintBriefFMPaymentsReport = function () {
	return this.do('PrintBriefFMPaymentsReport');
};

/**
 * @typedef {Object} DailyAvailableAmounts_OldRes
 * @property {number} AmountPayment0 Up to 13 symbols for the accumulated amount by payment type 0
 * @property {number} AmountPayment1 Up to 13 symbols for the accumulated amount by payment type 1
 * @property {number} AmountPayment2 Up to 13 symbols for the accumulated amount by payment type 2
 * @property {number} AmountPayment3 Up to 13 symbols for the accumulated amount by payment type 3
 * @property {number} AmountPayment4 Up to 13 symbols for the accumulated amount by payment type 4
 */

/**
 * Provides information about the amounts on hand by type of payment. Command works for KL version 2 devices.
 * @return {DailyAvailableAmounts_OldRes}
 */
Tremol.FP.prototype.ReadDailyAvailableAmounts_Old = function () {
	return this.do('ReadDailyAvailableAmounts_Old');
};

/**
 * Read a brief FM payments report by initial and end FM report number.
 * @param {number} StartZNum 4 symbols for the initial FM report number included in report, format ####
 * @param {number} EndZNum 4 symbols for the final FM report number included in report, format ####
 */
Tremol.FP.prototype.ReadBriefFMPaymentsReportByZBlocks = function (StartZNum, EndZNum) {
	return this.do('ReadBriefFMPaymentsReportByZBlocks', 'StartZNum', StartZNum, 'EndZNum', EndZNum);
};

/**
 * Read a brief FM Departments report by initial and end Z report number.
 * @param {number} StartZNum 4 symbols for the initial FM report number included in report, format ####
 * @param {number} EndZNum 4 symbols for the final FM report number included in report, format ####
 */
Tremol.FP.prototype.ReadBriefFMDepartmentsReportByZBlocks = function (StartZNum, EndZNum) {
	return this.do('ReadBriefFMDepartmentsReportByZBlocks', 'StartZNum', StartZNum, 'EndZNum', EndZNum);
};

/**
 * @typedef {Object} GrandFiscalSalesAndStornoAmountsRes
 * @property {number} GrandFiscalTurnover Up to 14 symbols for sum of turnover in FD
 * @property {number} GrandFiscalVAT Up to 14 symbols for sum of VAT value in FD
 * @property {number} GrandFiscalStornoTurnover Up to 14 symbols for sum of STORNO turnover in FD
 * @property {number} GrandFiscalStornoVAT Up to 14 symbols for sum of STORNO VAT value in FD
 */

/**
 * Read the Grand fiscal turnover sum and Grand fiscal VAT sum.
 * @return {GrandFiscalSalesAndStornoAmountsRes}
 */
Tremol.FP.prototype.ReadGrandFiscalSalesAndStornoAmounts = function () {
	return this.do('ReadGrandFiscalSalesAndStornoAmounts');
};

/**
 * Providing information about if the device's Bluetooth module is enabled or disabled.
 * @return {Tremol.Enums.OptionBTstatus}
 */
Tremol.FP.prototype.ReadBluetooth_Status = function () {
	return this.do('ReadBluetooth_Status');
};

/**
 * Opens an electronic fiscal invoice receipt with 1 minute timeout assigned to the specified operator number and operator password with internal DB info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.
 * @param {number} OperNum Symbol from 1 to 20 corresponding to operator's 
number
 * @param {string} OperPass 6 symbols for operator's password
 * @param {string} CustomerNum Symbol '#' and following up to 4 symbols for related customer ID number 
corresponding to the FD database
 * @param {string=} UniqueReceiptNumber Up to 24 symbols for unique receipt number. 
NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: 
* ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, 
* ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, 
* YYYYYYY - 7 symbols [0-9] for next number of the receipt
 */
Tremol.FP.prototype.OpenElectronicInvoiceWithFDCustomerDB = function (OperNum, OperPass, CustomerNum, UniqueReceiptNumber) {
	return this.do('OpenElectronicInvoiceWithFDCustomerDB', 'OperNum', OperNum, 'OperPass', OperPass, 'CustomerNum', CustomerNum, 'UniqueReceiptNumber', UniqueReceiptNumber);
};

/**
 * Register the sell (for correction use minus sign in the price field) of article belonging to department with specified name, price, fractional quantity and/or discount/addition on the transaction. The VAT of article got from department to which article belongs.
 * @param {string} NamePLU 36 symbols for article's name. 34 symbols are printed on paper. 
Symbol 0x7C '|' is new line separator.
 * @param {number} Price Up to 10 symbols for article's price. Use minus sign '-' for correction
 * @param {string=} Quantity From 3 to 10 symbols for quantity in format fractional format, e.g. 1/3
 * @param {number=} DiscAddP 1 to 7 symbols for percentage of discount/addition. Use 
minus sign '-' for discount
 * @param {number=} DiscAddV 1 to 8 symbols for value of discount/addition. Use 
minus sign '-' for discount
 * @param {number=} DepNum 1 symbol for article department 
attachment, formed in the following manner; example: Dep01 = 81h, Dep02 
= 82h … Dep19 = 93h 
Department range from 1 to 127
 */
Tremol.FP.prototype.SellFractQtyPLUfromDep = function (NamePLU, Price, Quantity, DiscAddP, DiscAddV, DepNum) {
	return this.do('SellFractQtyPLUfromDep', 'NamePLU', NamePLU, 'Price', Price, 'Quantity', Quantity, 'DiscAddP', DiscAddP, 'DiscAddV', DiscAddV, 'DepNum', DepNum);
};

/**
 * @typedef {Object} DeviceModuleSupportRes
 * @property {Tremol.Enums.OptionLAN} OptionLAN 1 symbol for LAN suppor 
- '0' - No 
 - '1' - Yes
 * @property {Tremol.Enums.OptionWiFi} OptionWiFi 1 symbol for WiFi support 
- '0' - No 
 - '1' - Yes
 * @property {Tremol.Enums.OptionGPRS} OptionGPRS 1 symbol for GPRS support 
- '0' - No 
 - '1' - Yes 
BT (Bluetooth) 1 symbol for Bluetooth support 
- '0' - No 
 - '1' - Yes
 * @property {Tremol.Enums.OptionBT} OptionBT (Bluetooth) 1 symbol for Bluetooth support 
- '0' - No 
 - '1' - Yes
 */

/**
 * Provide an information about modules supported by the device
 * @return {DeviceModuleSupportRes}
 */
Tremol.FP.prototype.ReadDeviceModuleSupport = function () {
	return this.do('ReadDeviceModuleSupport');
};

/**
 * @typedef {Object} WiFi_NetworkNameRes
 * @property {number} WiFiNameLength (Length) Up to 3 symbols for the WiFi name length
 * @property {string} WiFiNetworkName (Name) Up to 100 symbols for the device's WiFi network name
 */

/**
 * Read device's connected WiFi network name
 * @return {WiFi_NetworkNameRes}
 */
Tremol.FP.prototype.ReadWiFi_NetworkName = function () {
	return this.do('ReadWiFi_NetworkName');
};

/**
 * @typedef {Object} ParametersRes
 * @property {number} POSNum (POS Number) 4 symbols for number of POS in format ####
 * @property {Tremol.Enums.OptionPrintLogo} OptionPrintLogo (Print Logo) 1 symbol of value: 
 - '1' - Yes 
 - '0' - No
 * @property {Tremol.Enums.OptionAutoOpenDrawer} OptionAutoOpenDrawer (Auto Open Drawer) 1 symbol of value: 
 - '1' - Yes 
 - '0' - No
 * @property {Tremol.Enums.OptionAutoCut} OptionAutoCut (Auto Cut) 1 symbol of value: 
 - '1' - Yes 
 - '0' - No
 * @property {Tremol.Enums.OptionExternalDispManagement} OptionExternalDispManagement (External Display Management) 1 symbol of value: 
 - '1' - Manual 
 - '0' - Auto
 * @property {Tremol.Enums.OptionArticleReportType} OptionArticleReportType (Article Report) 1 symbol of value: 
 - '1' - Detailed 
 - '0' - Brief
 * @property {Tremol.Enums.OptionEnableCurrency} OptionEnableCurrency (Enable Currency) 1 symbol of value: 
 - '1' - Yes 
 - '0' - No
 * @property {Tremol.Enums.OptionEJFontType} OptionEJFontType (EJ Font) 1 symbol of value: 
 - '1' - Low Font 
 - '0' - Normal Font
 * @property {Tremol.Enums.OptionWorkOperatorCount} OptionWorkOperatorCount (Work Operator Count) 1 symbol of value: 
 - '1' - One 
 - '0' - More
 */

/**
 * Provides information about the number of POS, printing of logo, cash drawer opening, cutting permission, display mode, article report type, Enable/Disable currency in receipt, EJ font type and working operators counter.
 * @return {ParametersRes}
 */
Tremol.FP.prototype.ReadParameters = function () {
	return this.do('ReadParameters');
};

/**
 * Read a detailed FM Departments report by initial and end date.
 * @param {Date} StartDate 6 symbols for initial date in the DDMMYY format
 * @param {Date} EndDate 6 symbols for final date in the DDMMYY format
 */
Tremol.FP.prototype.ReadDetailedFMDepartmentsReportByDate = function (StartDate, EndDate) {
	return this.do('ReadDetailedFMDepartmentsReportByDate', 'StartDate', StartDate, 'EndDate', EndDate);
};

/**
 * @typedef {Object} VersionRes
 * @property {Tremol.Enums.OptionDeviceType} OptionDeviceType 1 or 2 symbols for type of fiscal device: 
- '1' - ECR 
- '11' - ECR for online store 
- '2' - FPr 
- '21' - FPr for online store 
- '3' - Fuel 
- '31' - Fuel system 
- '5' - for FUVAS device
 * @property {string} CertificateNum 6 symbols for Certification Number of device model
 * @property {Date} CertificateDateTime 16 symbols for Certificate Date and time parameter  
in format: DD-MM-YYYY HH:MM
 * @property {string} Model Up to 50 symbols for Model name
 * @property {string} Version Up to 20 symbols for Version name and Check sum
 */

/**
 * Provides information about the device type, Certificate number, Certificate date and time and Device model.
 * @return {VersionRes}
 */
Tremol.FP.prototype.ReadVersion = function () {
	return this.do('ReadVersion');
};

/**
 *  Writes raw bytes to FP 
 * @param {Uint8Array} Bytes The bytes in BASE64 ecoded string to be written to FP
 */
Tremol.FP.prototype.RawWrite = function (Bytes) {
	return this.do('RawWrite', 'Bytes', Bytes);
};

/**
 * Register the sell (for correction use minus sign in the price field) of article  with specified department. If VAT is present the relevant accumulations are perfomed in its  registers.
 * @param {string} NamePLU 36 symbols for name of sale. 34 symbols are printed on 
paper. Symbol 0x7C '|' is new line separator.
 * @param {number} DepNum 1 symbol for article department 
attachment, formed in the following manner: DepNum[HEX] + 80h 
example: Dep01 = 81h, Dep02 = 82h … Dep19 = 93h 
Department range from 1 to 127
 * @param {number} Price Up to 10 symbols for article's price. Use minus sign '-' for correction
 * @param {number=} Quantity Up to 10 symbols for article's quantity sold
 * @param {number=} DiscAddP Up to 7 for percentage of discount/addition. Use 
minus sign '-' for discount
 * @param {number=} DiscAddV Up to 8 symbols for percentage of 
discount/addition. Use minus sign '-' for discount
 * @param {Tremol.Enums.OptionVATClass=} OptionVATClass 1 character for VAT class: 
 - 'А' - VAT Class 0 
 - 'Б' - VAT Class 1 
 - 'В' - VAT Class 2 
 - 'Г' - VAT Class 3 
 - 'Д' - VAT Class 4 
 - 'Е' - VAT Class 5 
 - 'Ж' - VAT Class 6 
 - 'З' - VAT Class 7 
 - '*' - Forbidden
 */
Tremol.FP.prototype.SellPLUwithSpecifiedVATfromDep_ = function (NamePLU, DepNum, Price, Quantity, DiscAddP, DiscAddV, OptionVATClass) {
	return this.do('SellPLUwithSpecifiedVATfromDep_', 'NamePLU', NamePLU, 'DepNum', DepNum, 'Price', Price, 'Quantity', Quantity, 'DiscAddP', DiscAddP, 'DiscAddV', DiscAddV, 'OptionVATClass', OptionVATClass);
};

/**
 * Print or store Electronic Journal Report from receipt number to receipt number.
 * @param {Tremol.Enums.OptionReportStorage} OptionReportStorage 1 character with value: 
 - 'J1' - Printing 
 - 'J2' - USB storage 
 - 'J4' - SD card storage
 * @param {number} StartRcpNum 6 symbols for initial receipt number included in report, in format ######.
 * @param {number} EndRcpNum 6 symbols for final receipt number included in report in format ######.
 */
Tremol.FP.prototype.PrintOrStoreEJByRcpNum = function (OptionReportStorage, StartRcpNum, EndRcpNum) {
	return this.do('PrintOrStoreEJByRcpNum', 'OptionReportStorage', OptionReportStorage, 'StartRcpNum', StartRcpNum, 'EndRcpNum', EndRcpNum);
};

/**
 * @typedef {Object} DailyReturnedChangeAmountsByOperatorRes
 * @property {number} OperNum Symbols from 1 to 20 corresponding to operator's number
 * @property {number} ChangeAmountPayment0 Up to 13 symbols for amounts received by type of payment 0
 * @property {number} ChangeAmountPayment1 Up to 13 symbols for amounts received by type of payment 1
 * @property {number} ChangeAmountPayment2 Up to 13 symbols for amounts received by type of payment 2
 * @property {number} ChangeAmountPayment3 Up to 13 symbols for amounts received by type of payment 3
 * @property {number} ChangeAmountPayment4 Up to 13 symbols for amounts received by type of payment 4
 * @property {number} ChangeAmountPayment5 Up to 13 symbols for amounts received by type of payment 5
 * @property {number} ChangeAmountPayment6 Up to 13 symbols for amounts received by type of payment 6
 * @property {number} ChangeAmountPayment7 Up to 13 symbols for amounts received by type of payment 7
 * @property {number} ChangeAmountPayment8 Up to 13 symbols for amounts received by type of payment 8
 * @property {number} ChangeAmountPayment9 Up to 13 symbols for amounts received by type of payment 9
 * @property {number} ChangeAmountPayment10 Up to 13 symbols for amounts received by type of payment 10
 * @property {number} ChangeAmountPayment11 Up to 13 symbols for amounts received by type of payment 11
 */

/**
 * Read the amounts returned as change by different payment types for the specified operator.
 * @param {number} OperNum Symbol from 1 to 20 corresponding to operator's number
 * @return {DailyReturnedChangeAmountsByOperatorRes}
 */
Tremol.FP.prototype.ReadDailyReturnedChangeAmountsByOperator = function (OperNum) {
	return this.do('ReadDailyReturnedChangeAmountsByOperator', 'OperNum', OperNum);
};

/**
 * Read or Store Electronic Journal Report from receipt number to receipt number, CSV format option and document content. If CSV format is set the content can includes only fiscal receipts. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.
 * @param {Tremol.Enums.OptionStorageReport} OptionStorageReport 1 character with value 
 - 'j0' - To PC 
 - 'j2' - To USB Flash Drive 
 - 'j4' - To SD card
 * @param {Tremol.Enums.OptionCSVformat} OptionCSVformat 1 symbol with value: 
 - 'C' - Yes 
 - 'X' - No
 * @param {string} FlagsReceipts 1 symbol for Receipts included in EJ: 
Flags.7=0 
Flags.6=1 
Flags.5=1 Yes, Flags.5=0 No (Include PO) 
Flags.4=1 Yes, Flags.4=0 No (Include RA) 
Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) 
Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) 
Flags.1=1 Yes, Flags.1=0 No (Include Invoice) 
Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)
 * @param {string} FlagsReports 1 symbol for Reports included in EJ: 
Flags.7=0 
Flags.6=1 
Flags.5=0 
Flags.4=1 Yes, Flags.4=0 No (Include FM reports) 
Flags.3=1 Yes, Flags.3=0 No (Include Other reports) 
Flags.2=1 Yes, Flags.2=0 No (Include Daily X) 
Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) 
Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)
 * @param {number} StartRcpNum 6 symbols for initial receipt number included in report in format ######.
 * @param {number} EndRcpNum 6 symbols for final receipt number included in report in format ######.
 */
Tremol.FP.prototype.ReadEJByReceiptNumCustom = function (OptionStorageReport, OptionCSVformat, FlagsReceipts, FlagsReports, StartRcpNum, EndRcpNum) {
	return this.do('ReadEJByReceiptNumCustom', 'OptionStorageReport', OptionStorageReport, 'OptionCSVformat', OptionCSVformat, 'FlagsReceipts', FlagsReceipts, 'FlagsReports', FlagsReports, 'StartRcpNum', StartRcpNum, 'EndRcpNum', EndRcpNum);
};

/**
 * Paying the exact amount in cash and close the fiscal receipt.
 */
Tremol.FP.prototype.CashPayCloseReceipt = function () {
	return this.do('CashPayCloseReceipt');
};

/**
 * Program the contents of a Display Greeting message.
 * @param {string} DisplayGreetingText 20 symbols for Display greeting message
 */
Tremol.FP.prototype.ProgDisplayGreetingMessage = function (DisplayGreetingText) {
	return this.do('ProgDisplayGreetingMessage', 'DisplayGreetingText', DisplayGreetingText);
};

/**
 * @typedef {Object} DailyPORes
 * @property {number} AmountPayment0 Up to 13 symbols for the accumulated amount by payment type 0
 * @property {number} AmountPayment1 Up to 13 symbols for the accumulated amount by payment type 1
 * @property {number} AmountPayment2 Up to 13 symbols for the accumulated amount by payment type 2
 * @property {number} AmountPayment3 Up to 13 symbols for the accumulated amount by payment type 3
 * @property {number} AmountPayment4 Up to 13 symbols for the accumulated amount by payment type 4
 * @property {number} AmountPayment5 Up to 13 symbols for the accumulated amount by payment type 5
 * @property {number} AmountPayment6 Up to 13 symbols for the accumulated amount by payment type 6
 * @property {number} AmountPayment7 Up to 13 symbols for the accumulated amount by payment type 7
 * @property {number} AmountPayment8 Up to 13 symbols for the accumulated amount by payment type 8
 * @property {number} AmountPayment9 Up to 13 symbols for the accumulated amount by payment type 9
 * @property {number} AmountPayment10 Up to 13 symbols for the accumulated amount by payment type 10
 * @property {number} AmountPayment11 Up to 13 symbols for the accumulated amount by payment type 11
 * @property {number} PONum Up to 5 symbols for the total number of operations
 * @property {number} SumAllPayment Up to 13 symbols to sum all payments
 */

/**
 * Provides information about the PO amounts by type of payment and the total number of operations.
 * @return {DailyPORes}
 */
Tremol.FP.prototype.ReadDailyPO = function () {
	return this.do('ReadDailyPO');
};

/**
 * Read a detailed FM payments report by initial and end Z report number.
 * @param {number} StartZNum 4 symbols for initial FM report number included in report, format ####
 * @param {number} EndZNum 4 symbols for final FM report number included in report, format ####
 */
Tremol.FP.prototype.ReadDetailedFMPaymentsReportByZBlocks = function (StartZNum, EndZNum) {
	return this.do('ReadDetailedFMPaymentsReportByZBlocks', 'StartZNum', StartZNum, 'EndZNum', EndZNum);
};

/**
 * Read a brief FM report by initial and end date.
 * @param {Date} StartDate 6 symbols for initial date in the DDMMYY format
 * @param {Date} EndDate 6 symbols for final date in the DDMMYY format
 */
Tremol.FP.prototype.ReadBriefFMReportByDate = function (StartDate, EndDate) {
	return this.do('ReadBriefFMReportByDate', 'StartDate', StartDate, 'EndDate', EndDate);
};

/**
 * Read a detailed FM report by initial and end date.
 * @param {Date} StartDate 6 symbols for initial date in the DDMMYY format
 * @param {Date} EndDate 6 symbols for final date in the DDMMYY format
 */
Tremol.FP.prototype.ReadDetailedFMReportByDate = function (StartDate, EndDate) {
	return this.do('ReadDetailedFMReportByDate', 'StartDate', StartDate, 'EndDate', EndDate);
};

/**
 * Prints an extended daily financial report (an article report followed by a daily financial report) with or without zeroing ('Z' or 'X').
 * @param {Tremol.Enums.OptionZeroing} OptionZeroing with following values: 
 - 'Z' -Zeroing 
 - 'X' - Without zeroing
 */
Tremol.FP.prototype.PrintDetailedDailyReport = function (OptionZeroing) {
	return this.do('PrintDetailedDailyReport', 'OptionZeroing', OptionZeroing);
};

/**
* Sends client definitions to the server for compatibillity.
*/
Tremol.FP.prototype.ApplyClientLibraryDefinitions = function () {
	var defs = '<Defs><ServerStartupSettings>   <Encoding CodePage="1251" EncodingName="Cyrillic (Windows)" />   <GenerationTimeStamp>2308011616</GenerationTimeStamp>   <SignalFD>0</SignalFD>   <SilentFindDevice>0</SilentFindDevice>   <EM>0</EM> </ServerStartupSettings><Command Name="ReadDailyAvailableAmounts" CmdByte="0x6E"><FPOperation>Provides information about the amounts on hand by type of payment.</FPOperation><Args><Arg Name="" Value="0" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'0\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="0" Type="OptionHardcoded" MaxLen="1" /><Res Name="AmountPayment0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name="AmountPayment1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name="AmountPayment2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name="AmountPayment3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name="AmountPayment4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><Res Name="AmountPayment5" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 5</Desc></Res><Res Name="AmountPayment6" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 6</Desc></Res><Res Name="AmountPayment7" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 7</Desc></Res><Res Name="AmountPayment8" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 8</Desc></Res><Res Name="AmountPayment9" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 9</Desc></Res><Res Name="AmountPayment10" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 10</Desc></Res><Res Name="AmountPayment11" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 11</Desc></Res><ResFormatRaw><![CDATA[<\'0\'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]> <;> <AmountPayment5[1..13]> <;> <AmountPayment6[1..13]> <;> <AmountPayment7[1..13]> <;> <AmountPayment8[1..13]> <;> <AmountPayment9[1..13]> <;> <AmountPayment10[1..13]> <;> <AmountPayment11[1..13]>]]></ResFormatRaw></Response></Command><Command Name="ProgramWeightBarcodeFormat" CmdByte="0x4F"><FPOperation>Program weight barcode format.</FPOperation><Args><Arg Name="" Value="B" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="W" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionBarcodeFormat" Value="" Type="Option" MaxLen="1"><Options><Option Name="NNNNcWWWWW" Value="0" /><Option Name="NNNNNWWWWW" Value="1" /></Options><Desc>1 symbol with value:   - \'0\' - NNNNcWWWWW   - \'1\' - NNNNNWWWWW</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'B\'> <;> <\'W\'> <;> <OptionBarcodeFormat[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadDailyPO_Old" CmdByte="0x6E"><FPOperation>Provides information about the PO amounts by type of payment and the total number of operations. Command works for KL version 2 devices.</FPOperation><Args><Arg Name="" Value="3" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'3\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="3" Type="OptionHardcoded" MaxLen="1" /><Res Name="AmountPayment0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name="AmountPayment1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name="AmountPayment2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name="AmountPayment3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name="AmountPayment4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><Res Name="PONum" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for the total number of operations</Desc></Res><Res Name="SumAllPayment" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols to sum all payments</Desc></Res><ResFormatRaw><![CDATA[<\'3\'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]> <;> <PONum[1..5]> <;> <SumAllPayment[1..13]>]]></ResFormatRaw></Response></Command><Command Name="PrintArticleReport" CmdByte="0x7E"><FPOperation>Prints an article report with or without zeroing (\'Z\' or \'X\').</FPOperation><Args><Arg Name="OptionZeroing" Value="" Type="Option" MaxLen="1"><Options><Option Name="Without zeroing" Value="X" /><Option Name="Zeroing" Value="Z" /></Options><Desc>with following values:   - \'Z\' - Zeroing   - \'X\' - Without zeroing</Desc></Arg><ArgsFormatRaw><![CDATA[ <OptionZeroing[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadDecimalPoint" CmdByte="0x63"><FPOperation>Provides information about the current (the last value stored into the FM) decimal point format.</FPOperation><Response ACK="false"><Res Name="OptionDecimalPointPosition" Value="" Type="Option" MaxLen="1"><Options><Option Name="Fractions" Value="2" /><Option Name="Whole numbers" Value="0" /></Options><Desc>1 symbol with values:   - \'0\'- Whole numbers   - \'2\' - Fractions</Desc></Res><ResFormatRaw><![CDATA[<DecimalPointPosition[1]>]]></ResFormatRaw></Response></Command><Command Name="ReadElectronicReceipt_QR_BMP" CmdByte="0x72"><FPOperation>Starts session for reading electronic receipt by number with Base64 encoded BMP QR code.</FPOperation><Args><Arg Name="" Value="E" Type="OptionHardcoded" MaxLen="1" /><Arg Name="RcpNum" Value="" Type="Decimal_with_format" MaxLen="6" Format="000000"><Desc>6 symbols with format ######</Desc></Arg><Arg Name="QRSymbol" Value="," Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'E\'> <;> <RcpNum[6]> <;> <QRSymbol[\',\']> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="ProgParameters" CmdByte="0x45"><FPOperation>Programs the number of POS, printing of logo, cash drawer opening, cutting permission, external display management mode, article report type, enable or disable currency in receipt, EJ font type and working operators counter.</FPOperation><Args><Arg Name="POSNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for number of POS in format ####</Desc></Arg><Arg Name="OptionPrintLogo" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol of value:   - \'1\' - Yes   - \'0\' - No</Desc></Arg><Arg Name="OptionAutoOpenDrawer" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol of value:   - \'1\' - Yes   - \'0\' - No</Desc></Arg><Arg Name="OptionAutoCut" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol of value:   - \'1\' - Yes   - \'0\' - No</Desc></Arg><Arg Name="OptionExternalDispManagement" Value="" Type="Option" MaxLen="1"><Options><Option Name="Auto" Value="0" /><Option Name="Manual" Value="1" /></Options><Desc>1 symbol of value:   - \'1\' - Manual   - \'0\' - Auto</Desc></Arg><Arg Name="OptionArticleReportType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Brief" Value="0" /><Option Name="Detailed" Value="1" /></Options><Desc>1 symbol of value:   - \'1\' - Detailed   - \'0\' - Brief</Desc></Arg><Arg Name="OptionEnableCurrency" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol of value:   - \'1\' - Yes   - \'0\' - No</Desc></Arg><Arg Name="OptionEJFontType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Low Font" Value="1" /><Option Name="Normal Font" Value="0" /></Options><Desc>1 symbol of value:   - \'1\' - Low Font   - \'0\' - Normal Font</Desc></Arg><Arg Name="reserved" Value="0" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionWorkOperatorCount" Value="" Type="Option" MaxLen="1"><Options><Option Name="More" Value="0" /><Option Name="One" Value="1" /></Options><Desc>1 symbol of value:   - \'1\' - One   - \'0\' - More</Desc></Arg><ArgsFormatRaw><![CDATA[ <POSNum[4]> <;> <PrintLogo[1]> <;> <AutoOpenDrawer[1]> <;> <AutoCut[1]> <;> <ExternalDispManagement[1]> <;> <ArticleReportType[1]> <;> <EnableCurrency[1]> <;> <EJFontType[1]> <;> <reserved[\'0\']> <;> <WorkOperatorCount[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="PrintEJByRcpNumCustom" CmdByte="0x7C"><FPOperation>Print Electronic Journal Report from receipt number to receipt number and selected documents content. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.</FPOperation><Args><Arg Name="" Value="j1" Type="OptionHardcoded" MaxLen="2" /><Arg Name="" Value="X" Type="OptionHardcoded" MaxLen="1" /><Arg Name="FlagsReceipts" Value="" Type="Flags" MaxLen="1"><Desc>1 symbol for Receipts included in EJ:  Flags.7=0  Flags.6=1  Flags.5=1 Yes, Flags.5=0 No (Include PO)  Flags.4=1 Yes, Flags.4=0 No (Include RA)  Flags.3=1 Yes, Flags.3=0 No (Include Credit Note)  Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp)  Flags.1=1 Yes, Flags.1=0 No (Include Invoice)  Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)</Desc></Arg><Arg Name="FlagsReports" Value="" Type="Flags" MaxLen="1"><Desc>1 symbol for Reports included in EJ:  Flags.7=0  Flags.6=1  Flags.5=0  Flags.4=1 Yes, Flags.4=0 No (Include FM reports)  Flags.3=1 Yes, Flags.3=0 No (Include Other reports)  Flags.2=1 Yes, Flags.2=0 No (Include Daily X)  Flags.1=1 Yes, Flags.1=0 No (Include Daily Z)  Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)</Desc></Arg><Arg Name="" Value="N" Type="OptionHardcoded" MaxLen="1" /><Arg Name="StartRcpNum" Value="" Type="Decimal_with_format" MaxLen="6" Format="000000."><Desc>6 symbols for initial receipt number included in report in format ######.</Desc></Arg><Arg Name="EndRcpNum" Value="" Type="Decimal_with_format" MaxLen="6" Format="000000."><Desc>6 symbols for final receipt number included in report in format ######.</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'j1\'> <;> <\'X\'> <;> <FlagsReceipts [1]> <;> <FlagsReports [1]> <;> <\'N\'> <;> <StartRcpNum[6]> <;> <EndRcpNum[6]> ]]></ArgsFormatRaw></Args></Command><Command Name="StartTest_Lan" CmdByte="0x4E"><FPOperation>Start LAN test on the device and print out the result</FPOperation><Args><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="T" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="T" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'R\'><;><\'T\'><;><\'T\'> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadDepartment" CmdByte="0x67"><FPOperation>Provides information for the programmed data, the turnover from the stated department number</FPOperation><Args><Arg Name="DepNum" Value="" Type="Decimal_with_format" MaxLen="3" Format="000"><Desc>3 symbols for department number in format ###</Desc></Arg><ArgsFormatRaw><![CDATA[ <DepNum[3..3]> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="DepNum" Value="" Type="Decimal_with_format" MaxLen="3" Format="000"><Desc>3 symbols for department number in format ###</Desc></Res><Res Name="DepName" Value="" Type="Text" MaxLen="20"><Desc>20 symbols for department name</Desc></Res><Res Name="OptionVATClass" Value="" Type="Option" MaxLen="1"><Options><Option Name="Forbidden" Value="*" /><Option Name="VAT Class 0" Value="А" /><Option Name="VAT Class 1" Value="Б" /><Option Name="VAT Class 2" Value="В" /><Option Name="VAT Class 3" Value="Г" /><Option Name="VAT Class 4" Value="Д" /><Option Name="VAT Class 5" Value="Е" /><Option Name="VAT Class 6" Value="Ж" /><Option Name="VAT Class 7" Value="З" /></Options><Desc>1 character for VAT class:   - \'А\' - VAT Class 0   - \'Б\' - VAT Class 1   - \'В\' - VAT Class 2   - \'Г\' - VAT Class 3   - \'Д\' - VAT Class 4   - \'Е\' - VAT Class 5   - \'Ж\' - VAT Class 6   - \'З\' - VAT Class 7   - \'*\' - Forbidden</Desc></Res><Res Name="Turnover" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for accumulated turnover of the article</Desc></Res><Res Name="SoldQuantity" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for sold quantity of the department</Desc></Res><Res Name="LastZReportNumber" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for the number of last Z Report</Desc></Res><Res Name="LastZReportDate" Value="" Type="DateTime" MaxLen="10" Format="dd-MM-yyyy HH:mm"><Desc>16 symbols for date and hour on last Z Report in format   "DD-MM-YYYY HH:MM"</Desc></Res><ResFormatRaw><![CDATA[<DepNum[3..3]> <;> <DepName[20]> <;> <OptionVATClass[1]> <;> <Turnover[1..13]> <;> <SoldQuantity[1..13]> <;> <LastZReportNumber[1..5]> <;> <LastZReportDate "DD-MM-YYYY HH:MM">]]></ResFormatRaw></Response></Command><Command Name="ReadTransferAmountParam_RA" CmdByte="0x4F"><FPOperation>Provide information about parameter for automatic transfer of daily available amounts.</FPOperation><Args><Arg Name="" Value="A" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'A\'> <;> <\'R\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="A" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Res Name="OptionTransferAmount" Value="" Type="Text" MaxLen="1"><Desc>1 symbol with value:   - \'0\' - No   - \'1\' - Yes</Desc></Res><ResFormatRaw><![CDATA[<\'A\'> <;> <\'R\'> <;> <OptionTransferAmount[1]>]]></ResFormatRaw></Response></Command><Command Name="OpenElectronicInvoiceWithFreeCustomerData" CmdByte="0x30"><FPOperation>Opens an electronic fiscal invoice receipt with 1 minute timeout assigned to the specified operator number and operator password with free info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.</FPOperation><Args><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbol from 1 to 20 corresponding to operator\'s number</Desc></Arg><Arg Name="OperPass" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for operator\'s password</Desc></Arg><Arg Name="reserved" Value="0" Type="OptionHardcoded" MaxLen="1" /><Arg Name="reserved" Value="0" Type="OptionHardcoded" MaxLen="1" /><Arg Name="InvoicePrintType" Value="9" Type="OptionHardcoded" MaxLen="1" /><Arg Name="Recipient" Value="" Type="Text" MaxLen="26"><Desc>26 symbols for Invoice recipient</Desc></Arg><Arg Name="Buyer" Value="" Type="Text" MaxLen="16"><Desc>16 symbols for Invoice buyer</Desc></Arg><Arg Name="VATNumber" Value="" Type="Text" MaxLen="13"><Desc>13 symbols for customer Fiscal number</Desc></Arg><Arg Name="UIC" Value="" Type="Text" MaxLen="13"><Desc>13 symbols for customer Unique Identification Code</Desc></Arg><Arg Name="Address" Value="" Type="Text" MaxLen="30"><Desc>30 symbols for Address</Desc></Arg><Arg Name="OptionUICType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Bulstat" Value="0" /><Option Name="EGN" Value="1" /><Option Name="Foreigner Number" Value="2" /><Option Name="NRA Official Number" Value="3" /></Options><Desc>1 symbol for type of Unique Identification Code:    - \'0\' - Bulstat   - \'1\' - EGN   - \'2\' - Foreigner Number   - \'3\' - NRA Official Number</Desc></Arg><Arg Name="UniqueReceiptNumber" Value="" Type="Text" MaxLen="24"><Desc>Up to 24 symbols for unique receipt number.  NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where:  * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number,  * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator,  * YYYYYYY - 7 symbols [0-9] for next number of the receipt</Desc><Meta MinLen="24" Compulsory="false" ValIndicatingPresence="$" /></Arg><ArgsFormatRaw><![CDATA[ <OperNum[1..2]> <;> <OperPass[6]> <;> <reserved[\'0\']> <;> <reserved[\'0\']> <;> <InvoicePrintType[\'9\']> <;> <Recipient[26]> <;> <Buyer[16]> <;> <VATNumber[13]> <;> <UIC[13]> <;> <Address[30]> <;> <UICType[1]> { <\'$\'> <UniqueReceiptNumber[24]>} ]]></ArgsFormatRaw></Args></Command><Command Name="ReadLastReceiptNum" CmdByte="0x71"><FPOperation>Read the total counter of last issued receipt.</FPOperation><Response ACK="false"><Res Name="TotalReceiptCounter" Value="" Type="Decimal_with_format" MaxLen="6" Format="000000"><Desc>6 symbols for the total receipt counter in format ######   up to current last issued receipt by FD</Desc></Res><ResFormatRaw><![CDATA[<TotalReceiptCounter[6]>]]></ResFormatRaw></Response></Command><Command Name="SetCustomerUIC" CmdByte="0x41"><FPOperation>Stores the Unique Identification Code (UIC) and UIC type into the operative memory.</FPOperation><Args><Arg Name="Password" Value="" Type="Text" MaxLen="6"><Desc>6-symbols string</Desc></Arg><Arg Name="" Value="1" Type="OptionHardcoded" MaxLen="1" /><Arg Name="UIC" Value="" Type="Text" MaxLen="13"><Desc>13 symbols for UIC</Desc></Arg><Arg Name="OptionUICType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Bulstat" Value="0" /><Option Name="EGN" Value="1" /><Option Name="Foreigner Number" Value="2" /><Option Name="NRA Official Number" Value="3" /></Options><Desc>1 symbol for type of UIC number:    - \'0\' - Bulstat   - \'1\' - EGN   - \'2\' - Foreigner Number   - \'3\' - NRA Official Number</Desc></Arg><ArgsFormatRaw><![CDATA[ <Password[6]> <;> <\'1\'> <;> <UIC[13]> <;> <UICType[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadEJByReceiptNum" CmdByte="0x7C"><FPOperation>Read Electronic Journal Report from receipt number to receipt number.</FPOperation><Args><Arg Name="OptionReportFormat" Value="" Type="Option" MaxLen="2"><Options><Option Name="Brief EJ" Value="J8" /><Option Name="Detailed EJ" Value="J0" /></Options><Desc>1 character with value   - \'J0\' - Detailed EJ   - \'J8\' - Brief EJ</Desc></Arg><Arg Name="" Value="N" Type="OptionHardcoded" MaxLen="1" /><Arg Name="StartRcpNum" Value="" Type="Decimal_with_format" MaxLen="6" Format="000000"><Desc>6 symbols for initial receipt number included in report in format ######</Desc></Arg><Arg Name="EndRcpNum" Value="" Type="Decimal_with_format" MaxLen="6" Format="000000"><Desc>6 symbols for final receipt number included in report in format ######</Desc></Arg><ArgsFormatRaw><![CDATA[ <ReportFormat[2]> <;> <\'N\'> <;> <StartRcpNum[6]> <;> <EndRcpNum[6]> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="ProgPLUgeneral" CmdByte="0x4B"><FPOperation>Programs the general data for a certain article in the internal database. The price may have variable length, while the name field is fixed.</FPOperation><Args><Arg Name="PLUNum" Value="" Type="Decimal_with_format" MaxLen="5" Format="00000"><Desc>5 symbols for article number in format: #####</Desc></Arg><Arg Name="Option" Value="#@1+$" Type="OptionHardcoded" MaxLen="5" /><Arg Name="Name" Value="" Type="Text" MaxLen="34"><Desc>34 symbols for article name</Desc></Arg><Arg Name="Price" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for article price</Desc></Arg><Arg Name="OptionPrice" Value="" Type="Option" MaxLen="1"><Options><Option Name="Free price is disable valid only programmed price" Value="0" /><Option Name="Free price is enable" Value="1" /><Option Name="Limited price" Value="2" /></Options><Desc>1 symbol for price flag with next value:   - \'0\'- Free price is disable valid only programmed price   - \'1\'- Free price is enable   - \'2\'- Limited price</Desc></Arg><Arg Name="OptionVATClass" Value="" Type="Option" MaxLen="1"><Options><Option Name="Forbidden" Value="*" /><Option Name="VAT Class 0" Value="А" /><Option Name="VAT Class 1" Value="Б" /><Option Name="VAT Class 2" Value="В" /><Option Name="VAT Class 3" Value="Г" /><Option Name="VAT Class 4" Value="Д" /><Option Name="VAT Class 5" Value="Е" /><Option Name="VAT Class 6" Value="Ж" /><Option Name="VAT Class 7" Value="З" /></Options><Desc>1 character for VAT class:   - \'А\' - VAT Class 0   - \'Б\' - VAT Class 1   - \'В\' - VAT Class 2   - \'Г\' - VAT Class 3   - \'Д\' - VAT Class 4   - \'Е\' - VAT Class 5   - \'Ж\' - VAT Class 6   - \'З\' - VAT Class 7   - \'*\' - Forbidden</Desc></Arg><Arg Name="BelongToDepNum" Value="" Type="Decimal_plus_80h" MaxLen="2"><Desc>BelongToDepNum + 80h, 1 symbol for article  department attachment, formed in the following manner:  BelongToDepNum[HEX] + 80h example: Dep01 = 81h, Dep02 = 82h …  Dep19 = 93h  Department range from 1 to 127</Desc></Arg><Arg Name="OptionSingleTransaction" Value="" Type="Option" MaxLen="1"><Options><Option Name="Active Single transaction in receipt" Value="1" /><Option Name="Inactive, default value" Value="0" /></Options><Desc>1 symbol with value:   - \'0\' - Inactive, default value   - \'1\' - Active Single transaction in receipt</Desc></Arg><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Option[\'#@1+$\']> <;> <Name[34]> <;> <Price[1..10]> <;> <OptionPrice[1]> <;> <OptionVATClass[1]> <;> <BelongToDepNum[1]> <;> <SingleTransaction[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="PrintDiscountOrAddition" CmdByte="0x3E"><FPOperation>Percent or value discount/addition over sum of transaction or over subtotal sum specified by field "Type".</FPOperation><Args><Arg Name="OptionType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Defined from the device" Value="2" /><Option Name="Over subtotal" Value="1" /><Option Name="Over transaction sum" Value="0" /></Options><Desc>1 symbol with value   - \'2\' - Defined from the device   - \'1\' - Over subtotal  - \'0\' - Over transaction sum</Desc></Arg><Arg Name="OptionSubtotal" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol with value    - \'1\' - Yes    - \'0\' - No</Desc></Arg><Arg Name="DiscAddV" Value="" Type="Decimal" MaxLen="8"><Desc>Up to 8 symbols for the value of the discount/addition.  Use minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence=":" /></Arg><Arg Name="DiscAddP" Value="" Type="Decimal" MaxLen="7"><Desc>Up to 7 symbols for the percentage value of the  discount/addition. Use minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="," /></Arg><ArgsFormatRaw><![CDATA[ <Type[1]> <;> <OptionSubtotal[1]> {<\':\'> <DiscAddV[1..8]>} {<\',\'> <DiscAddP[1..7]>} ]]></ArgsFormatRaw></Args></Command><Command Name="ProgPayment_Old" CmdByte="0x44"><FPOperation>Preprogram the name of the type of payment. Command works for KL version 2 devices.</FPOperation><Args><Arg Name="OptionNumber" Value="" Type="Option" MaxLen="1"><Options><Option Name="Payment 1" Value="1" /><Option Name="Payment 2" Value="2" /><Option Name="Payment 3" Value="3" /><Option Name="Payment 4" Value="4" /></Options><Desc>1 symbol for payment type    - \'1\' - Payment 1   - \'2\' - Payment 2   - \'3\' - Payment 3   - \'4\' - Payment 4</Desc></Arg><Arg Name="Name" Value="" Type="Text" MaxLen="10"><Desc>10 symbols for payment type name. Only the first 6 are printable and only  relevant for CodePayment \'9\' and \':\'</Desc></Arg><Arg Name="Rate" Value="" Type="Decimal_with_format" MaxLen="10" Format="0000.00000"><Desc>Up to 10 symbols for exchange rate in format: ####.#####  of the 4th payment type, maximal value 0420.00000</Desc></Arg><Arg Name="OptionCodePayment" Value="" Type="Option" MaxLen="1"><Options><Option Name="Bank" Value="8" /><Option Name="Card" Value="7" /><Option Name="Check" Value="1" /><Option Name="Damage" Value="6" /><Option Name="Packaging" Value="4" /><Option Name="Programming Name1" Value="9" /><Option Name="Programming Name2" Value=":" /><Option Name="Service" Value="5" /><Option Name="Talon" Value="2" /><Option Name="V. Talon" Value="3" /></Options><Desc>1 symbol for code payment type with name:   - \'1\' - Check    - \'2\' - Talon   - \'3\' - V. Talon   - \'4\' - Packaging   - \'5\' - Service   - \'6\' - Damage   - \'7\' - Card   - \'8\' - Bank   - \'9\' - Programming Name1   - \':\' - Programming Name2</Desc></Arg><ArgsFormatRaw><![CDATA[ <Number[1]><;><Name[10]><;><Rate[1..10]><;><CodePayment[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="PrintOrStoreEJ" CmdByte="0x7C"><FPOperation>Print or store Electronic Journal report with all documents.</FPOperation><Args><Arg Name="OptionReportStorage" Value="" Type="Option" MaxLen="2"><Options><Option Name="Printing" Value="J1" /><Option Name="SD card storage" Value="J4" /><Option Name="USB storage" Value="J2" /></Options><Desc>1 character with value:   - \'J1\' - Printing   - \'J2\' - USB storage   - \'J4\' - SD card storage</Desc></Arg><Arg Name="" Value="*" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <ReportStorage[2]> <;> <\'*\'> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadWeightBarcodeFormat" CmdByte="0x4F"><FPOperation>Provide information about weight barcode format.</FPOperation><Args><Arg Name="" Value="B" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'B\'> <;> <\'R\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="B" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Res Name="OptionBarcodeFormat" Value="" Type="Option" MaxLen="1"><Options><Option Name="NNNNcWWWWW" Value="0" /><Option Name="NNNNNWWWWW" Value="1" /></Options><Desc>1 symbol with value:   - \'0\' - NNNNcWWWWW   - \'1\' - NNNNNWWWWW</Desc></Res><ResFormatRaw><![CDATA[<\'B\'> <;> <\'R\'> <;> <OptionBarcodeFormat[1]>]]></ResFormatRaw></Response></Command><Command Name="CashDrawerOpen" CmdByte="0x2A"><FPOperation>Opens the cash drawer.</FPOperation></Command><Command Name="ReadPLU_Old" CmdByte="0x6B"><FPOperation>Provides information about the registers of the specified article.</FPOperation><Args><Arg Name="PLUNum" Value="" Type="Decimal_with_format" MaxLen="5" Format="00000"><Desc>5 symbols for article number in format #####</Desc></Arg><ArgsFormatRaw><![CDATA[ <PLUNum[5]> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="PLUNum" Value="" Type="Decimal_with_format" MaxLen="5" Format="00000"><Desc>5 symbols for article number format #####</Desc></Res><Res Name="PLUName" Value="" Type="Text" MaxLen="20"><Desc>20 symbols for article name</Desc></Res><Res Name="Price" Value="" Type="Decimal" MaxLen="11"><Desc>Up to 11 symbols for article price</Desc></Res><Res Name="OptionVATClass" Value="" Type="Option" MaxLen="1"><Options><Option Name="Forbidden" Value="*" /><Option Name="VAT Class 0" Value="А" /><Option Name="VAT Class 1" Value="Б" /><Option Name="VAT Class 2" Value="В" /><Option Name="VAT Class 3" Value="Г" /><Option Name="VAT Class 4" Value="Д" /><Option Name="VAT Class 5" Value="Е" /><Option Name="VAT Class 6" Value="Ж" /><Option Name="VAT Class 7" Value="З" /></Options><Desc>1 character for VAT class:   - \'А\' - VAT Class 0   - \'Б\' - VAT Class 1   - \'В\' - VAT Class 2   - \'Г\' - VAT Class 3   - \'Д\' - VAT Class 4   - \'Е\' - VAT Class 5   - \'Ж\' - VAT Class 6   - \'З\' - VAT Class 7   - \'*\' - Forbidden</Desc></Res><Res Name="Turnover" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for turnover by this article</Desc></Res><Res Name="QuantitySold" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for sold quantity</Desc></Res><Res Name="LastZReportNumber" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for the number of last Z Report</Desc></Res><Res Name="LastZReportDate" Value="" Type="DateTime" MaxLen="10" Format="dd-MM-yyyy HH:mm"><Desc>16 symbols for date and hour on last Z Report in format   DD-MM-YYYY HH:MM</Desc></Res><Res Name="BelongToDepNumber" Value="" Type="Decimal_plus_80h" MaxLen="2"><Desc>BelongToDepNumber + 80h, 1 symbol for article department  attachment, formed in the following manner:  BelongToDepNumber[HEX] + 80h example: Dep01 = 81h, Dep02  = 82h … Dep19 = 93h  Department range from 1 to 127</Desc></Res><ResFormatRaw><![CDATA[<PLUNum[5]> <;> <PLUName[20]> <;> <Price[1..11]> <;> <OptionVATClass[1]> <;> <Turnover[1..13]> <;> <QuantitySold[1..13]> <;> <LastZReportNumber[1..5]> <;> <LastZReportDate "DD-MM-YYYY HH:MM"> <;> <BelongToDepNumber[1]>]]></ResFormatRaw></Response></Command><Command Name="PrintDetailedFMPaymentsReportByZBlocks" CmdByte="0x78"><FPOperation>Print a detailed FM payments report by initial and end Z report number.</FPOperation><Args><Arg Name="StartZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for initial FM report number included in report, format ####</Desc></Arg><Arg Name="EndZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for final FM report number included in report, format ####</Desc></Arg><Arg Name="Option" Value="P" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <Option[\'P\']> ]]></ArgsFormatRaw></Args></Command><Command Name="SetTCPpassword" CmdByte="0x4E"><FPOperation>Program device\'s TCP password. To apply use - SaveNetworkSettings()</FPOperation><Args><Arg Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="Z" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="1" Type="OptionHardcoded" MaxLen="1" /><Arg Name="PassLength" Value="" Type="Decimal" MaxLen="3"><Desc>Up to 3 symbols for the password len</Desc></Arg><Arg Name="Password" Value="" Type="Text" MaxLen="100"><Desc>Up to 100 symbols for the TCP password</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'P\'><;><\'Z\'><;><\'1\'><;><PassLength[1..3]><;><Password[100]> ]]></ArgsFormatRaw></Args></Command><Command Name="DisplayTextLines1and2" CmdByte="0x27"><FPOperation>Shows a 20-symbols text in the first line and last 20-symbols text in the second line of the external display lines.</FPOperation><Args><Arg Name="Text" Value="" Type="Text" MaxLen="40"><Desc>40 symbols text</Desc></Arg><ArgsFormatRaw><![CDATA[ <Text[40]> ]]></ArgsFormatRaw></Args></Command><Command Name="SellPLUFromFD_DB" CmdByte="0x32"><FPOperation>Register the sell or correction with specified quantity of article from the internal FD database. The FD will perform a correction operation only if the same quantity of the article has already been sold.</FPOperation><Args><Arg Name="OptionSign" Value="" Type="Option" MaxLen="1"><Options><Option Name="Correction" Value="-" /><Option Name="Sale" Value="+" /></Options><Desc>1 symbol with optional value:   - \'+\' -Sale   - \'-\' - Correction</Desc><Meta MinLen="1" Compulsory="true" NoSemiColumnSeparatorAfterIt="true" /></Arg><Arg Name="PLUNum" Value="" Type="Decimal_with_format" MaxLen="5" Format="00000"><Desc>5 symbols for PLU number of FD\'s database in format #####</Desc></Arg><Arg Name="Price" Value="" Type="Decimal" MaxLen="8"><Desc>Up to 10 symbols for sale price</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="$" /></Arg><Arg Name="Quantity" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for article\'s quantity sold</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="*" /></Arg><Arg Name="DiscAddP" Value="" Type="Decimal" MaxLen="7"><Desc>Up to 7 for percentage of discount/addition. Use minus  sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="," /></Arg><Arg Name="DiscAddV" Value="" Type="Decimal" MaxLen="8"><Desc>Up to 8 symbolsfor percentage of discount/addition.  Use minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence=":" /></Arg><ArgsFormatRaw><![CDATA[ <OptionSign[1]> <PLUNum[5]> {<\'$\'> <Price[1..8]>} {<\'*\'> <Quantity[1..10]>} {<\',\'> <DiscAddP[1..7]>} {<\':\'> <DiscAddV[1..8]>} ]]></ArgsFormatRaw></Args></Command><Command Name="ReadDateTime" CmdByte="0x68"><FPOperation>Provides information about the current date and time.</FPOperation><Response ACK="false"><Res Name="DateTime" Value="" Type="DateTime" MaxLen="10" Format="dd-MM-yyyy HH:mm"><Desc>Date Time parameter in format: DD-MM-YYYY HH:MM</Desc></Res><ResFormatRaw><![CDATA[<DateTime "DD-MM-YYYY HH:MM">]]></ResFormatRaw></Response></Command><Command Name="PayExactSum" CmdByte="0x35"><FPOperation>Register the payment in the receipt with specified type of payment and exact amount received.</FPOperation><Args><Arg Name="OptionPaymentType" Value="" Type="Option" MaxLen="2"><Options><Option Name="Payment 0" Value="0" /><Option Name="Payment 1" Value="1" /><Option Name="Payment 10" Value="10" /><Option Name="Payment 11" Value="11" /><Option Name="Payment 2" Value="2" /><Option Name="Payment 3" Value="3" /><Option Name="Payment 4" Value="4" /><Option Name="Payment 5" Value="5" /><Option Name="Payment 6" Value="6" /><Option Name="Payment 7" Value="7" /><Option Name="Payment 8" Value="8" /><Option Name="Payment 9" Value="9" /></Options><Desc>1 symbol for payment type:   - \'0\' - Payment 0   - \'1\' - Payment 1   - \'2\' - Payment 2   - \'3\' - Payment 3   - \'4\' - Payment 4   - \'5\' - Payment 5   - \'6\' - Payment 6   - \'7\' - Payment 7   - \'8\' - Payment 8   - \'9\' - Payment 9   - \'10\' - Payment 10   - \'11\' - Payment 11</Desc></Arg><Arg Name="Option" Value="0" Type="OptionHardcoded" MaxLen="1" /><Arg Name="Amount" Value="&quot;" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <PaymentType[1..2]> <;> <Option[\'0\']> <;> <Amount[\'"\']>  ]]></ArgsFormatRaw></Args></Command><Command Name="StartTest_WiFi" CmdByte="0x4E"><FPOperation>Start WiFi test on the device and print out the result</FPOperation><Args><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="W" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="T" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'R\'><;><\'W\'><;><\'T\'> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadFMfreeRecords" CmdByte="0x74"><FPOperation>Read the number of the remaining free records for Z-report in the Fiscal Memory.</FPOperation><Response ACK="false"><Res Name="FreeFMrecords" Value="" Type="Text" MaxLen="4"><Desc>4 symbols for the number of free records for Z-report in the FM</Desc></Res><ResFormatRaw><![CDATA[<FreeFMrecords[4]>]]></ResFormatRaw></Response></Command><Command Name="ReadBluetooth_Password" CmdByte="0x4E"><FPOperation>Provides information about device\'s Bluetooth password.</FPOperation><Args><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="B" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'R\'><;><\'B\'><;><\'P\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="B" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><Res Name="PassLength" Value="" Type="Decimal" MaxLen="3"><Desc>(Length) Up to 3 symbols for the BT password length</Desc></Res><Res Name="Password" Value="" Type="Text" MaxLen="100"><Desc>Up to 100 symbols for the BT password</Desc></Res><ResFormatRaw><![CDATA[<\'R\'><;><\'B\'><;><\'P\'><;><PassLength[1..3]><;><Password[100]>]]></ResFormatRaw></Response></Command><Command Name="CancelReceipt" CmdByte="0x39"><FPOperation>Available only if receipt is not closed. Void all sales in the receipt and close the fiscal receipt (Fiscal receipt, Invoice receipt, Storno receipt or Credit Note). If payment is started, then finish payment and close the receipt.</FPOperation></Command><Command Name="SellPLUfromDep" CmdByte="0x31"><FPOperation>Register the sell (for correction use minus sign in the price field) of article belonging to department with specified name, price, quantity and/or discount/addition on the transaction. The VAT of article got from department to which article belongs.</FPOperation><Args><Arg Name="NamePLU" Value="" Type="Text" MaxLen="36"><Desc>36 symbols for article\'s name. 34 symbols are printed on paper.  Symbol 0x7C \'|\' is new line separator.</Desc></Arg><Arg Name="reserved" Value=" " Type="OptionHardcoded" MaxLen="1" /><Arg Name="Price" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for article\'s price. Use minus sign \'-\' for correction</Desc></Arg><Arg Name="Quantity" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for quantity</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="*" /></Arg><Arg Name="DiscAddP" Value="" Type="Decimal" MaxLen="7"><Desc>Up to 7 symbols for percentage of discount/addition.  Use minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="," /></Arg><Arg Name="DiscAddV" Value="" Type="Decimal" MaxLen="8"><Desc>Up to 8 symbols for value of discount/addition.  Use minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence=":" /></Arg><Arg Name="DepNum" Value="" Type="Decimal_plus_80h" MaxLen="2"><Desc>1 symbol for article department  attachment, formed in the following manner; example: Dep01=81h,  Dep02=82h … Dep19=93h  Department range from 1 to 127</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="!" /></Arg><ArgsFormatRaw><![CDATA[ <NamePLU[36]> <;> <reserved[\' \']> <;> <Price[1..10]> {<\'*\'> <Quantity[1..10]>} {<\',\'> <DiscAddP[1..7]>} {<\':\'> <DiscAddV[1..8]>} {<\'!\'> <DepNum[1]>} ]]></ArgsFormatRaw></Args></Command><Command Name="ReadInvoiceRange" CmdByte="0x70"><FPOperation>Provide information about invoice start and end numbers range.</FPOperation><Response ACK="false"><Res Name="StartNum" Value="" Type="Decimal_with_format" MaxLen="10" Format="0000000000"><Desc>10 symbols for start No with leading zeroes in format ##########</Desc></Res><Res Name="EndNum" Value="" Type="Decimal_with_format" MaxLen="10" Format="0000000000"><Desc>10 symbols for end No with leading zeroes in format ##########</Desc></Res><ResFormatRaw><![CDATA[<StartNum[10]> <;> <EndNum[10]>]]></ResFormatRaw></Response></Command><Command Name="PrintSpecialEventsFMreport" CmdByte="0x77"><FPOperation>Print whole special FM events report.</FPOperation></Command><Command Name="Read_IdleTimeout" CmdByte="0x4E"><FPOperation>Provides information about device\'s idle timeout. This timeout is seconds in which the connection will be closed when there is an inactivity. This information is available if the device has LAN or WiFi. Maximal value - 7200, minimal value 0. 0 is for never close the connection.</FPOperation><Args><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="Z" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="I" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'R\'><;><\'Z\'><;><\'I\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="Z" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="I" Type="OptionHardcoded" MaxLen="1" /><Res Name="IdleTimeout" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for password in format ####</Desc></Res><ResFormatRaw><![CDATA[<\'R\'><;><\'Z\'><;><\'I\'><;><IdleTimeout[4]>]]></ResFormatRaw></Response></Command><Command Name="OpenStornoReceipt" CmdByte="0x30"><FPOperation>Open a fiscal storno receipt assigned to the specified operator number and operator password, parameters for receipt format, print VAT, printing type and parameters for the related storno receipt.</FPOperation><Args><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s  number</Desc></Arg><Arg Name="OperPass" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for operator\'s password</Desc></Arg><Arg Name="OptionReceiptFormat" Value="" Type="Option" MaxLen="1"><Options><Option Name="Brief" Value="0" /><Option Name="Detailed" Value="1" /></Options><Desc>1 symbol with value:   - \'1\' - Detailed   - \'0\' - Brief</Desc></Arg><Arg Name="OptionPrintVAT" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol with value:    - \'1\' - Yes   - \'0\' - No</Desc></Arg><Arg Name="OptionStornoRcpPrintType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Buffered Printing" Value="D" /><Option Name="Postponed Printing" Value="B" /><Option Name="Step by step printing" Value="@" /></Options><Desc>1 symbol with value:  - \'@\' - Step by step printing  - \'B\' - Postponed Printing  - \'D\' - Buffered Printing</Desc></Arg><Arg Name="OptionStornoReason" Value="" Type="Option" MaxLen="1"><Options><Option Name="Goods Claim or Goods return" Value="1" /><Option Name="Operator error" Value="0" /><Option Name="Tax relief" Value="2" /></Options><Desc>1 symbol for reason of storno operation with value:   - \'0\' - Operator error   - \'1\' - Goods Claim or Goods return   - \'2\' - Tax relief</Desc></Arg><Arg Name="RelatedToRcpNum" Value="" Type="Decimal" MaxLen="6"><Desc>Up to 6 symbols for issued receipt number</Desc></Arg><Arg Name="RelatedToRcpDateTime" Value="" Type="DateTime" MaxLen="10" Format="dd-MM-yy HH:mm:ss"><Desc>17 symbols for Date and Time of the issued receipt  in format DD-MM-YY HH:MM:SS</Desc></Arg><Arg Name="FMNum" Value="" Type="Text" MaxLen="8"><Desc>8 symbols for number of the Fiscal Memory</Desc></Arg><Arg Name="RelatedToURN" Value="" Type="Text" MaxLen="24"><Desc>Up to 24 symbols for the issed receipt unique receipt number.  NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where:  * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number,  * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator,  * YYYYYYY - 7 symbols [0-9] for next number of the receipt</Desc><Meta MinLen="24" Compulsory="false" ValIndicatingPresence=";" /></Arg><ArgsFormatRaw><![CDATA[<OperNum[1..2]> <;> <OperPass[6]> <;> <ReceiptFormat[1]> <;> <PrintVAT[1]> <;> <StornoRcpPrintType[1]> <;> <StornoReason[1]> <;> <RelatedToRcpNum[1..6]> <;> <RelatedToRcpDateTime "DD-MM-YY HH:MM:SS"> <;> <FMNum[8]> {<;> <RelatedToURN[24]>} ]]></ArgsFormatRaw></Args></Command><Command Name="ProgOperator" CmdByte="0x4A"><FPOperation>Programs the operator\'s name and password.</FPOperation><Args><Arg Name="Number" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from \'1\' to \'20\' corresponding to operator\'s number</Desc></Arg><Arg Name="Name" Value="" Type="Text" MaxLen="20"><Desc>20 symbols for operator\'s name</Desc></Arg><Arg Name="Password" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for operator\'s password</Desc></Arg><ArgsFormatRaw><![CDATA[ <Number[1..2]> <;> <Name[20]> <;> <Password[6]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadPayments_Old" CmdByte="0x64"><FPOperation>Provides information about all programmed types of payment. Command works for KL version 2 devices.</FPOperation><Response ACK="false"><Res Name="NamePaym0" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for payment name type 0</Desc></Res><Res Name="NamePaym1" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for payment name type 1</Desc></Res><Res Name="NamePaym2" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for payment name type 2</Desc></Res><Res Name="NamePaym3" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for payment name type 3</Desc></Res><Res Name="NamePaym4" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for payment name type 4</Desc></Res><Res Name="ExRate" Value="" Type="Decimal_with_format" MaxLen="10" Format="0000.00000"><Desc>Up to10 symbols for exchange rate of payment type 4 in format: ####.#####</Desc></Res><Res Name="CodePaym0" Value="" Type="Text" MaxLen="1"><Desc>1 symbol for code of payment 0 = 0xFF (currency in cash)</Desc></Res><Res Name="CodePaym1" Value="" Type="Text" MaxLen="1"><Desc>1 symbol for code of payment 1 (default value is \'7\')</Desc></Res><Res Name="CodePaym2" Value="" Type="Text" MaxLen="1"><Desc>1 symbol for code of payment 2 (default value is \'1\')</Desc></Res><Res Name="CodePaym3" Value="" Type="Text" MaxLen="1"><Desc>1 symbol for code of payment 3 (default value is \'2\')</Desc></Res><Res Name="CodePaym4" Value="" Type="Text" MaxLen="1"><Desc>1 symbol for code of payment 4 = 0xFF (currency in cash)</Desc></Res><ResFormatRaw><![CDATA[<NamePaym0[6]> <;> <NamePaym1[6]> <;> <NamePaym2[6]> <;> <NamePaym3[6]> <;> <NamePaym4[6]><;><ExRate[1..10]> <;> <CodePaym0[1]><;> <CodePaym1[1]><;> <CodePaym2[1]><;> <CodePaym3[1]> <;> <CodePaym4[1]>]]></ResFormatRaw></Response></Command><Command Name="PrintEJByDateCustom" CmdByte="0x7C"><FPOperation>Print Electronic Journal Report by initial and end date, and selected documents content. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.</FPOperation><Args><Arg Name="" Value="j1" Type="OptionHardcoded" MaxLen="2" /><Arg Name="" Value="X" Type="OptionHardcoded" MaxLen="1" /><Arg Name="FlagsReceipts" Value="" Type="Flags" MaxLen="1"><Desc>1 symbol for Receipts included in EJ:  Flags.7=0  Flags.6=1  Flags.5=1 Yes, Flags.5=0 No (Include PO)  Flags.4=1 Yes, Flags.4=0 No (Include RA)  Flags.3=1 Yes, Flags.3=0 No (Include Credit Note)  Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp)  Flags.1=1 Yes, Flags.1=0 No (Include Invoice)  Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)</Desc></Arg><Arg Name="FlagsReports" Value="" Type="Flags" MaxLen="1"><Desc>1 symbol for Reports included in EJ:  Flags.7=0  Flags.6=1  Flags.5=0  Flags.4=1 Yes, Flags.4=0 No (Include FM reports)  Flags.3=1 Yes, Flags.3=0 No (Include Other reports)  Flags.2=1 Yes, Flags.2=0 No (Include Daily X)  Flags.1=1 Yes, Flags.1=0 No (Include Daily Z)  Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)</Desc></Arg><Arg Name="" Value="D" Type="OptionHardcoded" MaxLen="1" /><Arg Name="StartRepFromDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name="EndRepFromDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><ArgsFormatRaw><![CDATA[<\'j1\'> <;> <\'X\'> <;> <FlagsReceipts [1]> <;> <FlagsReports [1]> <;> <\'D\'> <;> <StartRepFromDate "DDMMYY"> <;> <EndRepFromDate "DDMMYY"> ]]></ArgsFormatRaw></Args></Command><Command Name="SetBluetooth_Password" CmdByte="0x4E"><FPOperation>Program device\'s Bluetooth password. To apply use - SaveNetworkSettings()</FPOperation><Args><Arg Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="B" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="PassLength" Value="" Type="Decimal" MaxLen="3"><Desc>Up to 3 symbols for the BT password len</Desc></Arg><Arg Name="Password" Value="" Type="Text" MaxLen="100"><Desc>Up to 100 symbols for the BT password</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'P\'><;><\'B\'><;><\'P\'><;>< PassLength[1..3]><;><Password[100]>> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadPLUqty" CmdByte="0x6B"><FPOperation>Provides information about the quantity registers of the specified article.</FPOperation><Args><Arg Name="PLUNum" Value="" Type="Decimal_with_format" MaxLen="5" Format="00000"><Desc>5 symbols for article number with leading zeroes in format: #####</Desc></Arg><Arg Name="Option" Value="2" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Option[\'2\']> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="PLUNum" Value="" Type="Decimal_with_format" MaxLen="5" Format="00000"><Desc>5 symbols for article number with leading zeroes in format #####</Desc></Res><Res Name="Option" Value="2" Type="OptionHardcoded" MaxLen="1" /><Res Name="AvailableQuantity" Value="" Type="Decimal" MaxLen="13"><Desc>Up to13 symbols for quantity in stock</Desc></Res><Res Name="OptionQuantityType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Availability of PLU stock is not monitored" Value="0" /><Option Name="Disable negative quantity" Value="1" /><Option Name="Enable negative quantity" Value="2" /></Options><Desc>1 symbol for Quantity flag with next value:   - \'0\'- Availability of PLU stock is not monitored   - \'1\'- Disable negative quantity   - \'2\'- Enable negative quantity</Desc></Res><ResFormatRaw><![CDATA[<PLUNum[5]> <;> <Option[\'2\']> <;> <AvailableQuantity[1..13]> <;> <OptionQuantityType[1]>]]></ResFormatRaw></Response></Command><Command Name="ScanAndPrintWiFiNetworks" CmdByte="0x4E"><FPOperation>Scan and print all available WiFi networks</FPOperation><Args><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="W" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="S" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'R\'><;><\'W\'><;><\'S\'> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadSerialAndFiscalNums" CmdByte="0x60"><FPOperation>Provides information about the manufacturing number of the fiscal device and FM number.</FPOperation><Response ACK="false"><Res Name="SerialNumber" Value="" Type="Text" MaxLen="8"><Desc>8 symbols for individual number of the fiscal device</Desc></Res><Res Name="FMNumber" Value="" Type="Text" MaxLen="8"><Desc>8 symbols for individual number of the fiscal memory</Desc></Res><ResFormatRaw><![CDATA[<SerialNumber[8]> <;> <FMNumber[8]>]]></ResFormatRaw></Response></Command><Command Name="ReceivedOnAccount_PaidOut" CmdByte="0x3B"><FPOperation>Registers cash received on account or paid out.</FPOperation><Args><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to the operator\'s  number</Desc></Arg><Arg Name="OperPass" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for operator\'s password</Desc></Arg><Arg Name="OptionPayType" Value="" Type="Option" MaxLen="2"><Options><Option Name="Cash" Value="0" /><Option Name="Currency" Value="11" /></Options><Desc>1 symbol with value   - \'0\' - Cash   - \'11\' - Currency</Desc></Arg><Arg Name="Amount" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for the amount lodged. Use minus sign for withdrawn</Desc></Arg><Arg Name="OptionPrintAvailability" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol with value:   - \'0\' - No   - \'1\' - Yes</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="$" /></Arg><Arg Name="Text" Value="" Type="Text" MaxLen="64"><Desc>TextLength-2 symbols. In the beginning and in the end of line symbol</Desc><Meta MinLen="64" Compulsory="false" ValIndicatingPresence=";" /></Arg><ArgsFormatRaw><![CDATA[<OperNum[1..2]> <;> <OperPass[6]> <;> <PayType[1..2]> <;> <Amount[1..10]> {<\'$\'> <PrintAvailability[1]> } {<;> <Text[TextLength-2]>} ]]></ArgsFormatRaw></Args></Command><Command Name="SaveNetworkSettings" CmdByte="0x4E"><FPOperation>After every change on Idle timeout, LAN/WiFi/GPRS usage, LAN/WiFi/TCP/GPRS password or TCP auto start networks settings this Save command needs to be execute.</FPOperation><Args><Arg Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="A" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'P\'><;><\'A\'> ]]></ArgsFormatRaw></Args></Command><Command Name="DirectCommand" CmdByte="0xF1"><FPOperation>Executes the direct command .</FPOperation><Args><Arg Name="Input" Value="" Type="Text" MaxLen="200"><Desc>Raw request to FP</Desc></Arg></Args><Response ACK="false"><Res Name="Output" Value="" Type="Text" MaxLen="200"><Desc>FP raw response</Desc></Res></Response></Command><Command Name="ReadEJByZBlocks" CmdByte="0x7C"><FPOperation>Reading Electronic Journal Report by number of Z report blocks.</FPOperation><Args><Arg Name="OptionReportFormat" Value="" Type="Option" MaxLen="2"><Options><Option Name="Brief EJ" Value="J8" /><Option Name="Detailed EJ" Value="J0" /></Options><Desc>1 character with value   - \'J0\' - Detailed EJ   - \'J8\' - Brief EJ</Desc></Arg><Arg Name="" Value="Z" Type="OptionHardcoded" MaxLen="1" /><Arg Name="StartNo" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for initial number report in format ####</Desc></Arg><Arg Name="EndNo" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for final number report in format ####</Desc></Arg><ArgsFormatRaw><![CDATA[ <ReportFormat[2]> <;> <\'Z\'> <;> <StartNo[4]> <;> <EndNo[4]> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="ReadDailyReturnedChangeAmountsByOperator_Old" CmdByte="0x6F"><FPOperation>Read the amounts returned as change by different payment types for the specified operator. Command works for KL version 2 devices.</FPOperation><Args><Arg Name="" Value="6" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbol from 1 to 20 corresponding to operator\'s number</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'6\'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="6" Type="OptionHardcoded" MaxLen="1" /><Res Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s number</Desc></Res><Res Name="ChangeAmountPayment0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by type of payment 0</Desc></Res><Res Name="ChangeAmountPayment1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by type of payment 1</Desc></Res><Res Name="ChangeAmountPayment2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by type of payment 2</Desc></Res><Res Name="ChangeAmountPayment3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by type of payment 3</Desc></Res><Res Name="ChangeAmountPayment4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by type of payment 4</Desc></Res><ResFormatRaw><![CDATA[<\'6\'> <;> <OperNum[1..2]> <;> <ChangeAmountPayment0[1..13]> <;> <ChangeAmountPayment1[1..13]> <;> <ChangeAmountPayment2[1..13]> <;> <ChangeAmountPayment3[1..13]> <;> <ChangeAmountPayment4[1..13]> <;>]]></ResFormatRaw></Response></Command><Command Name="ReadCurrentOrLastReceiptPaymentAmounts" CmdByte="0x72"><FPOperation>Provides information about the payments in current receipt. This command is valid after receipt closing also.</FPOperation><Args><Arg Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'P\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><Res Name="OptionIsReceiptOpened" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol with value:   - \'0\' - No   - \'1\' - Yes</Desc></Res><Res Name="Payment0Amount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for type 0 payment amount</Desc></Res><Res Name="Payment1Amount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for type 1 payment amount</Desc></Res><Res Name="Payment2Amount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for type 2 payment amount</Desc></Res><Res Name="Payment3Amount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for type 3 payment amount</Desc></Res><Res Name="Payment4Amount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for type 4 payment amount</Desc></Res><Res Name="Payment5Amount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for type 5 payment amount</Desc></Res><Res Name="Payment6Amount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for type 6 payment amount</Desc></Res><Res Name="Payment7Amount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for type 7 payment amount</Desc></Res><Res Name="Payment8Amount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for type 8 payment amount</Desc></Res><Res Name="Payment9Amount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for type 9 payment amount</Desc></Res><Res Name="Payment10Amount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for type 10 payment amount</Desc></Res><Res Name="Payment11Amount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for type 11 payment amount</Desc></Res><ResFormatRaw><![CDATA[<\'P\'> <;> <IsReceiptOpened[1]> <;> <Payment0Amount[1..13]> <;> <Payment1Amount[1..13]> <;> <Payment2Amount[1..13]> <;> <Payment3Amount[1..13]> <;> <Payment4Amount[1..13]> <;> <Payment5Amount[1..13]> <;> <Payment6Amount[1..13]> <;> <Payment7Amount[1..13]> <;> <Payment8Amount[1..13]> <;> <Payment9Amount[1..13]> <;> <Payment10Amount[1..13]> <;> <Payment11Amount[1..13]>]]></ResFormatRaw></Response></Command><Command Name="ReadDailyReturnedChangeAmounts" CmdByte="0x6E"><FPOperation>Provides information about the amounts returned as change by type of payment.</FPOperation><Args><Arg Name="" Value="6" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'6\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="6" Type="OptionHardcoded" MaxLen="1" /><Res Name="AmountPayment0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name="AmountPayment1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name="AmountPayment2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name="AmountPayment3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name="AmountPayment4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><Res Name="AmountPayment5" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 5</Desc></Res><Res Name="AmountPayment6" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 6</Desc></Res><Res Name="AmountPayment7" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 7</Desc></Res><Res Name="AmountPayment8" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 8</Desc></Res><Res Name="AmountPayment9" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 9</Desc></Res><Res Name="AmountPayment10" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 10</Desc></Res><Res Name="AmountPayment11" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 11</Desc></Res><ResFormatRaw><![CDATA[<\'6\'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]> <;> <AmountPayment5[1..13]> <;> <AmountPayment6[1..13]> <;> <AmountPayment7[1..13]> <;> <AmountPayment8[1..13]> <;> <AmountPayment9[1..13]> <;> <AmountPayment10[1..13]> <;> <AmountPayment11[1..13]>]]></ResFormatRaw></Response></Command><Command Name="ReadDailyReceivedSalesAmountsByOperator_Old" CmdByte="0x6F"><FPOperation>Read the amounts received from sales by type of payment and specified operator. Command works for KL version 2 devices.</FPOperation><Args><Arg Name="" Value="4" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s  number</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'4\'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="4" Type="OptionHardcoded" MaxLen="1" /><Res Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s number</Desc></Res><Res Name="ReceivedSalesAmountPayment0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by sales for payment 0</Desc></Res><Res Name="ReceivedSalesAmountPayment1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by sales for payment 1</Desc></Res><Res Name="ReceivedSalesAmountPayment2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by sales for payment 2</Desc></Res><Res Name="ReceivedSalesAmountPayment3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by sales for payment 3</Desc></Res><Res Name="ReceivedSalesAmountPayment4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by sales for payment 4</Desc></Res><ResFormatRaw><![CDATA[<\'4\'> <;> <OperNum[1..2]> <;> <ReceivedSalesAmountPayment0[1..13]> <;> <ReceivedSalesAmountPayment1[1..13]> <;> <ReceivedSalesAmountPayment2[1..13]> <;> <ReceivedSalesAmountPayment3[1..13]> <;> <ReceivedSalesAmountPayment4[1..13]>]]></ResFormatRaw></Response></Command><Command Name="PrintBriefFMDepartmentsReportByDate" CmdByte="0x7B"><FPOperation>Print a brief FM Departments report by initial and end date.</FPOperation><Args><Arg Name="StartDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name="EndDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name="Option" Value="D" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartDate "DDMMYY"> <;> <EndDate "DDMMYY"> <;> <Option[\'D\']> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadDepartmentAll" CmdByte="0x67"><FPOperation>Provides information for the programmed data, the turnovers from the stated department number</FPOperation><Args><Arg Name="DepNum" Value="" Type="Decimal_with_format" MaxLen="3" Format="000"><Desc>3 symbols for department number in format ###</Desc></Arg><Arg Name="reserved" Value="&quot;" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <DepNum[3..3]> <;> <reserved[\'"\']> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="DepNum" Value="" Type="Decimal_with_format" MaxLen="3" Format="000"><Desc>3 symbols for department number in format ###</Desc></Res><Res Name="reserved" Value="&quot;" Type="OptionHardcoded" MaxLen="1" /><Res Name="DepName" Value="" Type="Text" MaxLen="34"><Desc>20 symbols for department name</Desc></Res><Res Name="OptionVATClass" Value="" Type="Option" MaxLen="1"><Options><Option Name="Forbidden" Value="*" /><Option Name="VAT Class 0" Value="А" /><Option Name="VAT Class 1" Value="Б" /><Option Name="VAT Class 2" Value="В" /><Option Name="VAT Class 3" Value="Г" /><Option Name="VAT Class 4" Value="Д" /><Option Name="VAT Class 5" Value="Е" /><Option Name="VAT Class 6" Value="Ж" /><Option Name="VAT Class 7" Value="З" /></Options><Desc>1 character for VAT class:   - \'А\' - VAT Class 0   - \'Б\' - VAT Class 1   - \'В\' - VAT Class 2   - \'Г\' - VAT Class 3   - \'Д\' - VAT Class 4   - \'Е\' - VAT Class 5   - \'Ж\' - VAT Class 6   - \'З\' - VAT Class 7   - \'*\' - Forbidden</Desc></Res><Res Name="Price" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for department price</Desc></Res><Res Name="OptionDepPrice" Value="" Type="Option" MaxLen="1"><Options><Option Name="Free price disabled" Value="0" /><Option Name="Free price disabled for single transaction" Value="4" /><Option Name="Free price enabled" Value="1" /><Option Name="Free price enabled for single transaction" Value="5" /><Option Name="Limited price" Value="2" /><Option Name="Limited price for single transaction" Value="6" /></Options><Desc>1 symbol for Department flags with next value:   - \'0\' - Free price disabled   - \'1\' - Free price enabled   - \'2\' - Limited price   - \'4\' - Free price disabled for single transaction   - \'5\' - Free price enabled for single transaction   - \'6\' - Limited price for single transaction</Desc></Res><Res Name="TurnoverAmount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for accumulated turnover of the article</Desc></Res><Res Name="SoldQuantity" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for sold quantity of the department</Desc></Res><Res Name="StornoAmount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for accumulated storno amount</Desc></Res><Res Name="StornoQuantity" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for accumulated storno quantiy</Desc></Res><Res Name="LastZReportNumber" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for the number of last Z Report</Desc></Res><Res Name="LastZReportDate" Value="" Type="DateTime" MaxLen="10" Format="dd-MM-yyyy HH:mm"><Desc>16 symbols for date and hour on last Z Report in format   "DD-MM-YYYY HH:MM"</Desc></Res><ResFormatRaw><![CDATA[<DepNum[3..3]> <;> <reserved[\'"\']> <;> <DepName[34]> <;> <OptionVATClass[1]> <;> <Price[1..10]> <;> <OptionDepPrice[1]> <;> <TurnoverAmount[1..13]> <;> <SoldQuantity[1..13]> <;> <StornoAmount[1..13]> <;> <StornoQuantity[1..13]> <;> <LastZReportNumber[1..5]> <;> <LastZReportDate "DD-MM-YYYY HH:MM">]]></ResFormatRaw></Response></Command><Command Name="PrintBriefFMPaymentsReportByZBlocks" CmdByte="0x79"><FPOperation>Print a brief FM payments report by initial and end FM report number.</FPOperation><Args><Arg Name="StartZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for the initial FM report number included in report, format ####</Desc></Arg><Arg Name="EndZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for the final FM report number included in report, format ####</Desc></Arg><Arg Name="Option" Value="P" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <Option[\'P\']> ]]></ArgsFormatRaw></Args></Command><Command Name="PrintBriefFMPaymentsReportByDate" CmdByte="0x7B"><FPOperation>Print a brief FM payments report by initial and end date.</FPOperation><Args><Arg Name="StartDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name="EndDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name="Option" Value="P" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartDate "DDMMYY"> <;> <EndDate "DDMMYY"> <;> <Option[\'P\']> ]]></ArgsFormatRaw></Args></Command><Command Name="SetWiFi_NetworkName" CmdByte="0x4E"><FPOperation>Program device\'s WiFi network name where it will connect. To apply use -SaveNetworkSettings()</FPOperation><Args><Arg Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="W" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="N" Type="OptionHardcoded" MaxLen="1" /><Arg Name="WiFiNameLength" Value="" Type="Decimal" MaxLen="3"><Desc>Up to 3 symbols for the WiFi network name len</Desc></Arg><Arg Name="WiFiNetworkName" Value="" Type="Text" MaxLen="100"><Desc>Up to 100 symbols for the device\'s WiFi ssid network name</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'P\'><;><\'W\'><;><\'N\'><;><WiFiNameLength[1..3]><;><WiFiNetworkName[100]> ]]></ArgsFormatRaw></Args></Command><Command Name="ProgCustomerData" CmdByte="0x52"><FPOperation>Program customer in FD data base.</FPOperation><Args><Arg Name="Option" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="CustomerNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for customer number in format ####</Desc></Arg><Arg Name="CustomerCompanyName" Value="" Type="Text" MaxLen="26"><Desc>26 symbols for customer name</Desc></Arg><Arg Name="CustomerFullName" Value="" Type="Text" MaxLen="16"><Desc>16 symbols for Buyer name</Desc></Arg><Arg Name="VATNumber" Value="" Type="Text" MaxLen="13"><Desc>13 symbols for VAT number on customer</Desc></Arg><Arg Name="UIC" Value="" Type="Text" MaxLen="13"><Desc>13 symbols for customer Unique Identification Code</Desc></Arg><Arg Name="Address" Value="" Type="Text" MaxLen="30"><Desc>30 symbols for address on customer</Desc></Arg><Arg Name="OptionUICType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Bulstat" Value="0" /><Option Name="EGN" Value="1" /><Option Name="Foreigner Number" Value="2" /><Option Name="NRA Official Number" Value="3" /></Options><Desc>1 symbol for type of Unique Identification Code:    - \'0\' - Bulstat   - \'1\' - EGN   - \'2\' - Foreigner Number   - \'3\' - NRA Official Number</Desc></Arg><ArgsFormatRaw><![CDATA[ <Option[\'P\']> <;> <CustomerNum[4]> <;> <CustomerCompanyName[26]> <;> <CustomerFullName[16]> <;> <VATNumber[13]> <;> <UIC[13]> <;> <Address[30]> <;> <UICType[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="SellPLUwithSpecifiedVAT" CmdByte="0x31"><FPOperation>Register the sell (for correction use minus sign in the price field) of article with specified name, price, quantity, VAT class and/or discount/addition on the transaction.</FPOperation><Args><Arg Name="NamePLU" Value="" Type="Text" MaxLen="36"><Desc>36 symbols for article\'s name. 34 symbols are printed on paper.  Symbol 0x7C \'|\' is new line separator.</Desc></Arg><Arg Name="OptionVATClass" Value="" Type="Option" MaxLen="1"><Options><Option Name="Forbidden" Value="*" /><Option Name="VAT Class 0" Value="А" /><Option Name="VAT Class 1" Value="Б" /><Option Name="VAT Class 2" Value="В" /><Option Name="VAT Class 3" Value="Г" /><Option Name="VAT Class 4" Value="Д" /><Option Name="VAT Class 5" Value="Е" /><Option Name="VAT Class 6" Value="Ж" /><Option Name="VAT Class 7" Value="З" /></Options><Desc>1 character for VAT class:   - \'А\' - VAT Class 0   - \'Б\' - VAT Class 1   - \'В\' - VAT Class 2   - \'Г\' - VAT Class 3   - \'Д\' - VAT Class 4   - \'Е\' - VAT Class 5   - \'Ж\' - VAT Class 6   - \'З\' - VAT Class 7   - \'*\' - Forbidden</Desc></Arg><Arg Name="Price" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for article\'s price. Use minus sign \'-\' for correction</Desc></Arg><Arg Name="Quantity" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for quantity</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="*" /></Arg><Arg Name="DiscAddP" Value="" Type="Decimal" MaxLen="7"><Desc>Up to 7 symbols for percentage of discount/addition.  Use minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="," /></Arg><Arg Name="DiscAddV" Value="" Type="Decimal" MaxLen="8"><Desc>Up to 8 symbols for value of discount/addition.  Use minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence=":" /></Arg><ArgsFormatRaw><![CDATA[ <NamePLU[36]> <;> <OptionVATClass[1]> <;> <Price[1..10]> {<\'*\'> <Quantity[1..10]>} {<\',\'> <DiscAddP[1..7]>} {<\':\'> <DiscAddV[1..8]>} ]]></ArgsFormatRaw></Args></Command><Command Name="ReadDetailedFMDepartmentsReportByZBlocks" CmdByte="0x78"><FPOperation>Read a detailed FM Departments report by initial and end Z report number.</FPOperation><Args><Arg Name="StartZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for initial FM report number included in report, format ####</Desc></Arg><Arg Name="EndZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for final FM report number included in report, format ####</Desc></Arg><Arg Name="Option" Value="D" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionReading" Value="8" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <Option[\'D\']> <;> <OptionReading[\'8\']> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="PrintBriefFMReportByDate" CmdByte="0x7B"><FPOperation>Print a brief FM report by initial and end date.</FPOperation><Args><Arg Name="StartDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name="EndDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><ArgsFormatRaw><![CDATA[ <StartDate "DDMMYY"> <;> <EndDate "DDMMYY"> ]]></ArgsFormatRaw></Args></Command><Command Name="DisplayTextLine1" CmdByte="0x25"><FPOperation>Shows a 20-symbols text in the upper external display line.</FPOperation><Args><Arg Name="Text" Value="" Type="Text" MaxLen="20"><Desc>20 symbols text</Desc></Arg><ArgsFormatRaw><![CDATA[ <Text[20]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadVATrates" CmdByte="0x62"><FPOperation>Provides information about the current VAT rates which are the last values stored into the FM.</FPOperation><Response ACK="false"><Res Name="VATrate0" Value="" Type="Decimal_with_format" MaxLen="7" Format="00.00%"><Desc>Value of VAT rate А from 7 symbols in format ##.##%</Desc></Res><Res Name="VATrate1" Value="" Type="Decimal_with_format" MaxLen="7" Format="00.00%"><Desc>Value of VAT rate Б from 7 symbols in format ##.##%</Desc></Res><Res Name="VATrate2" Value="" Type="Decimal_with_format" MaxLen="7" Format="00.00%"><Desc>Value of VAT rate В from 7 symbols in format ##.##%</Desc></Res><Res Name="VATrate3" Value="" Type="Decimal_with_format" MaxLen="7" Format="00.00%"><Desc>Value of VAT rate Г from 7 symbols in format ##.##%</Desc></Res><Res Name="VATrate4" Value="" Type="Decimal_with_format" MaxLen="7" Format="00.00%"><Desc>Value of VAT rate Д from 7 symbols in format ##.##%</Desc></Res><Res Name="VATrate5" Value="" Type="Decimal_with_format" MaxLen="7" Format="00.00%"><Desc>Value of VAT rate Е from 7 symbols in format ##.##%</Desc></Res><Res Name="VATrate6" Value="" Type="Decimal_with_format" MaxLen="7" Format="00.00%"><Desc>Value of VAT rate Ж from 7 symbols in format ##.##%</Desc></Res><Res Name="VATrate7" Value="" Type="Decimal_with_format" MaxLen="7" Format="00.00%"><Desc>Value of VAT rate З from 7 symbols in format ##.##%</Desc></Res><ResFormatRaw><![CDATA[<VATrate0[7]> <;> <VATrate1[7]> <;> <VATrate2[7]> <;> <VATrate3[7]> <;> <VATrate4[7]> <;> <VATrate5[7]> <;> <VATrate6[7]> <;> <VATrate7[7]>]]></ResFormatRaw></Response></Command><Command Name="ReadDailyReceivedSalesAmounts" CmdByte="0x6E"><FPOperation>Provides information about the amounts received from sales by type of payment.</FPOperation><Args><Arg Name="" Value="4" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'4\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="4" Type="OptionHardcoded" MaxLen="1" /><Res Name="AmountPayment0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name="AmountPayment1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name="AmountPayment2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name="AmountPayment3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name="AmountPayment4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><Res Name="AmountPayment5" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 5</Desc></Res><Res Name="AmountPayment6" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 6</Desc></Res><Res Name="AmountPayment7" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 7</Desc></Res><Res Name="AmountPayment8" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 8</Desc></Res><Res Name="AmountPayment9" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 9</Desc></Res><Res Name="AmountPayment10" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 10</Desc></Res><Res Name="AmountPayment11" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 11</Desc></Res><ResFormatRaw><![CDATA[<\'4\'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]> <;> <AmountPayment5[1..13]> <;> <AmountPayment6[1..13]> <;> <AmountPayment7[1..13]> <;> <AmountPayment8[1..13]> <;> <AmountPayment9[1..13]> <;> <AmountPayment10[1..13]> <;> <AmountPayment11[1..13]>]]></ResFormatRaw></Response></Command><Command Name="ProgPLUqty" CmdByte="0x4B"><FPOperation>Programs available quantity and Quantiy type for a certain article in the internal database.</FPOperation><Args><Arg Name="PLUNum" Value="" Type="Decimal_with_format" MaxLen="5" Format="00000"><Desc>5 symbols for article number in format: #####</Desc></Arg><Arg Name="Option" Value="#@2+$" Type="OptionHardcoded" MaxLen="5" /><Arg Name="AvailableQuantity" Value="" Type="Decimal" MaxLen="11"><Desc>Up to 11 symbols for available quantity in stock</Desc></Arg><Arg Name="OptionQuantityType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Availability of PLU stock is not monitored" Value="0" /><Option Name="Disable negative quantity" Value="1" /><Option Name="Enable negative quantity" Value="2" /></Options><Desc>1 symbol for Quantity flag with next value:    - \'0\'- Availability of PLU stock is not monitored    - \'1\'- Disable negative quantity    - \'2\'- Enable negative quantity</Desc></Arg><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Option[\'#@2+$\']> <;> <AvailableQuantity[1..11]> <;> <OptionQuantityType[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadRegistrationInfo" CmdByte="0x61"><FPOperation>Provides information about the programmed VAT number, type of VAT number, register number in NRA and Date of registration in NRA.</FPOperation><Response ACK="false"><Res Name="UIC" Value="" Type="Text" MaxLen="13"><Desc>13 symbols for Unique Identification Code</Desc></Res><Res Name="OptionUICType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Bulstat" Value="0" /><Option Name="EGN" Value="1" /><Option Name="Foreigner Number" Value="2" /><Option Name="NRA Official Number" Value="3" /></Options><Desc>1 symbol for type of Unique Identification Code:   - \'0\' - Bulstat   - \'1\' - EGN   - \'2\' - Foreigner Number   - \'3\' - NRA Official Number</Desc></Res><Res Name="NRARegistrationNumber" Value="" Type="Text" MaxLen="6"><Desc>Register number on the Fiscal device from NRA</Desc></Res><Res Name="NRARegistrationDate" Value="" Type="DateTime" MaxLen="10" Format="dd-MM-yyyy HH:mm"><Desc>Date of registration in NRA</Desc></Res><ResFormatRaw><![CDATA[<UIC[13]> <;> <UICType[1]><;> <NRARegistrationNumber[6]><;> <NRARegistrationDate "DD-MM-YYYY HH:MM" >]]></ResFormatRaw></Response></Command><Command Name="ClearDisplay" CmdByte="0x24"><FPOperation>Clears the external display.</FPOperation></Command><Command Name="ProgPLU_Old" CmdByte="0x4B"><FPOperation>Programs the data for a certain article (item) in the internal database. The price may have variable length, while the name field is fixed.</FPOperation><Args><Arg Name="PLUNum" Value="" Type="Decimal_with_format" MaxLen="5" Format="00000"><Desc>5 symbols for article number in format: #####</Desc></Arg><Arg Name="Name" Value="" Type="Text" MaxLen="20"><Desc>20 symbols for article name</Desc></Arg><Arg Name="Price" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for article price</Desc></Arg><Arg Name="OptionVATClass" Value="" Type="Option" MaxLen="1"><Options><Option Name="Forbidden" Value="*" /><Option Name="VAT Class 0" Value="А" /><Option Name="VAT Class 1" Value="Б" /><Option Name="VAT Class 2" Value="В" /><Option Name="VAT Class 3" Value="Г" /><Option Name="VAT Class 4" Value="Д" /><Option Name="VAT Class 5" Value="Е" /><Option Name="VAT Class 6" Value="Ж" /><Option Name="VAT Class 7" Value="З" /></Options><Desc>1 character for VAT class:   - \'А\' - VAT Class 0   - \'Б\' - VAT Class 1   - \'В\' - VAT Class 2   - \'Г\' - VAT Class 3   - \'Д\' - VAT Class 4   - \'Е\' - VAT Class 5   - \'Ж\' - VAT Class 6   - \'З\' - VAT Class 7   - \'*\' - Forbidden</Desc></Arg><Arg Name="BelongToDepNum" Value="" Type="Decimal_plus_80h" MaxLen="2"><Desc>BelongToDepNum + 80h, 1 symbol for article  department attachment, formed in the following manner:</Desc></Arg><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Name[20]> <;> <Price[1..10]> <;> <OptionVATClass[1]> <;> <BelongToDepNum[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="SellFractQtyPLUwithSpecifiedVAT" CmdByte="0x3D"><FPOperation>Register the sell (for correction use minus sign in the price field) of article with specified name, price, fractional quantity, VAT class and/or discount/addition on the transaction.</FPOperation><Args><Arg Name="NamePLU" Value="" Type="Text" MaxLen="36"><Desc>36 symbols for article\'s name. 34 symbols are printed on paper.  Symbol 0x7C \'|\' is new line separator.</Desc></Arg><Arg Name="OptionVATClass" Value="" Type="Option" MaxLen="1"><Options><Option Name="Forbidden" Value="*" /><Option Name="VAT Class 0" Value="А" /><Option Name="VAT Class 1" Value="Б" /><Option Name="VAT Class 2" Value="В" /><Option Name="VAT Class 3" Value="Г" /><Option Name="VAT Class 4" Value="Д" /><Option Name="VAT Class 5" Value="Е" /><Option Name="VAT Class 6" Value="Ж" /><Option Name="VAT Class 7" Value="З" /></Options><Desc>1 character for VAT class:   - \'А\' - VAT Class 0   - \'Б\' - VAT Class 1   - \'В\' - VAT Class 2   - \'Г\' - VAT Class 3   - \'Д\' - VAT Class 4   - \'Е\' - VAT Class 5   - \'Ж\' - VAT Class 6   - \'З\' - VAT Class 7   - \'*\' - Forbidden</Desc></Arg><Arg Name="Price" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for article\'s price. Use minus sign \'-\' for correction</Desc></Arg><Arg Name="Quantity" Value="" Type="Text" MaxLen="10"><Desc>From 3 to 10 symbols for quantity in format fractional format, e.g. 1/3</Desc><Meta MinLen="10" Compulsory="false" ValIndicatingPresence="*" /></Arg><Arg Name="DiscAddP" Value="" Type="Decimal" MaxLen="7"><Desc>1 to 7 symbols for percentage of discount/addition. Use  minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="," /></Arg><Arg Name="DiscAddV" Value="" Type="Decimal" MaxLen="8"><Desc>1 to 8 symbols for value of discount/addition. Use  minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence=":" /></Arg><ArgsFormatRaw><![CDATA[ <NamePLU[36]> <;> <OptionVATClass[1]> <;> <Price[1..10]> {<\'*\'> <Quantity[10]>} {<\',\'> <DiscAddP[1..7]>} {<\':\'> <DiscAddV[1..8]>} ]]></ArgsFormatRaw></Args></Command><Command Name="ReadElectronicReceipt_QR_ASCII" CmdByte="0x72"><FPOperation>Starts session for reading electronic receipt by number with specified ASCII symbol for QR code block.</FPOperation><Args><Arg Name="" Value="E" Type="OptionHardcoded" MaxLen="1" /><Arg Name="RcpNum" Value="" Type="Decimal_with_format" MaxLen="6" Format="000000"><Desc>6 symbols with format ######</Desc></Arg><Arg Name="QRSymbol" Value="" Type="Text" MaxLen="1"><Desc>1 symbol for QR code drawing image</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'E\'> <;> <RcpNum[6]> <;> <QRSymbol[1]> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="SetDHCP_Enabled" CmdByte="0x4E"><FPOperation>Program device\'s TCP network DHCP enabled or disabled. To apply use -SaveNetworkSettings()</FPOperation><Args><Arg Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="T" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="1" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionDHCPEnabled" Value="" Type="Option" MaxLen="1"><Options><Option Name="Disabled" Value="0" /><Option Name="Enabled" Value="1" /></Options><Desc>1 symbol with value:   - \'0\' - Disabled   - \'1\' - Enabled</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'P\'><;><\'T\'><;><\'1\'><;><DHCPEnabled[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadDailyPObyOperator" CmdByte="0x6F"><FPOperation>Read the PO by type of payment and the total number of operations by specified operator</FPOperation><Args><Arg Name="" Value="3" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s number</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'3\'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="3" Type="OptionHardcoded" MaxLen="1" /><Res Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s number</Desc></Res><Res Name="AmountPO_Payment0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the PO by type of payment 0</Desc></Res><Res Name="AmountPO_Payment1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the PO by type of payment 1</Desc></Res><Res Name="AmountPO_Payment2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the PO by type of payment 2</Desc></Res><Res Name="AmountPO_Payment3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the PO by type of payment 3</Desc></Res><Res Name="AmountPO_Payment4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the PO by type of payment 4</Desc></Res><Res Name="AmountPO_Payment5" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the PO by type of payment 5</Desc></Res><Res Name="AmountPO_Payment6" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the PO by type of payment 6</Desc></Res><Res Name="AmountPO_Payment7" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the PO by type of payment 7</Desc></Res><Res Name="AmountPO_Payment8" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the PO by type of payment 8</Desc></Res><Res Name="AmountPO_Payment9" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the PO by type of payment 9</Desc></Res><Res Name="AmountPO_Payment10" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the PO by type of payment 10</Desc></Res><Res Name="AmountPO_Payment11" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the PO by type of payment 11</Desc></Res><Res Name="NoPO" Value="" Type="Decimal" MaxLen="5"><Desc>5 symbols for the total number of operations</Desc></Res><ResFormatRaw><![CDATA[<\'3\'> <;> <OperNum[1..2]> <;> <AmountPO_Payment0[1..13]> <;> <AmountPO_Payment1[1..13]> <;> <AmountPO_Payment2[1..13]> <;> <AmountPO_Payment3[1..13]> <;> <AmountPO_Payment4[1..13]> <;> <AmountPO_Payment5[1..13]> <;> <AmountPO_Payment6[1..13]> <;> <AmountPO_Payment7[1..13]> <;> <AmountPO_Payment8[1..13]> <;> <AmountPO_Payment9[1..13]> <;> <AmountPO_Payment10[1..13]> <;> <AmountPO_Payment11[1..13]> <;><NoPO[1..5]>]]></ResFormatRaw></Response></Command><Command Name="OpenElectronicReceipt" CmdByte="0x30"><FPOperation>Opens an postponed electronic fiscal receipt with 1 minute timeout assigned to the specified operator number and operator password, parameters for receipt format, print VAT, printing type and unique receipt number.</FPOperation><Args><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s number</Desc></Arg><Arg Name="OperPass" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for operator\'s password</Desc></Arg><Arg Name="OptionReceiptFormat" Value="" Type="Option" MaxLen="1"><Options><Option Name="Brief" Value="0" /><Option Name="Detailed" Value="1" /></Options><Desc>1 symbol with value:   - \'1\' - Detailed   - \'0\' - Brief</Desc></Arg><Arg Name="OptionPrintVAT" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol with value:    - \'1\' - Yes   - \'0\' - No</Desc></Arg><Arg Name="FiscalRcpPrintType" Value="8" Type="OptionHardcoded" MaxLen="1" /><Arg Name="UniqueReceiptNumber" Value="" Type="Text" MaxLen="24"><Desc>Up to 24 symbols for unique receipt number.  NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where:  * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number,  * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator,  * YYYYYYY - 7 symbols [0-9] for next number of the receipt</Desc><Meta MinLen="24" Compulsory="false" ValIndicatingPresence="$" /></Arg><ArgsFormatRaw><![CDATA[<OperNum[1..2]> <;> <OperPass[6]> <;> <ReceiptFormat[1]> <;> <PrintVAT[1]> <;> <FiscalRcpPrintType[\'8\']> {<\'$\'> <UniqueReceiptNumber[24]>} ]]></ArgsFormatRaw></Args></Command><Command Name="ProgHeaderUICprefix" CmdByte="0x49"><FPOperation>Program the content of the header UIC prefix.</FPOperation><Args><Arg Name="" Value="9" Type="OptionHardcoded" MaxLen="1" /><Arg Name="HeaderUICprefix" Value="" Type="Text" MaxLen="12"><Desc>12 symbols for header UIC prefix</Desc></Arg><ArgsFormatRaw><![CDATA[<\'9\'> <;> <HeaderUICprefix[12]> ]]></ArgsFormatRaw></Args></Command><Command Name="ProgPLUprice" CmdByte="0x4B"><FPOperation>Programs price and price type for a certain article in the internal database.</FPOperation><Args><Arg Name="PLUNum" Value="" Type="Decimal_with_format" MaxLen="5" Format="00000"><Desc>5 symbols for article number in format: #####</Desc></Arg><Arg Name="Option" Value="#@4+$" Type="OptionHardcoded" MaxLen="5" /><Arg Name="Price" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for article price</Desc></Arg><Arg Name="OptionPrice" Value="" Type="Option" MaxLen="1"><Options><Option Name="Free price is disable valid only programmed price" Value="0" /><Option Name="Free price is enable" Value="1" /><Option Name="Limited price" Value="2" /></Options><Desc>1 symbol for price flag with next value:   - \'0\'- Free price is disable valid only programmed price   - \'1\'- Free price is enable   - \'2\'- Limited price</Desc></Arg><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Option[\'#@4+$\']> <;> <Price[1..10]> <;> <OptionPrice[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadDailyReceivedSalesAmounts_Old" CmdByte="0x6E"><FPOperation>Provides information about the amounts received from sales by type of payment. Command works for KL version 2 devices.</FPOperation><Args><Arg Name="" Value="4" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'4\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="4" Type="OptionHardcoded" MaxLen="1" /><Res Name="AmountPayment0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name="AmountPayment1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name="AmountPayment2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name="AmountPayment3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name="AmountPayment4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><ResFormatRaw><![CDATA[<\'4\'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]>]]></ResFormatRaw></Response></Command><Command Name="RawRead" CmdByte="0xFF"><FPOperation> Reads raw bytes from FP.</FPOperation><Args><Arg Name="Count" Value="" Type="Decimal" MaxLen="5"><Desc>How many bytes to read if EndChar is not specified</Desc></Arg><Arg Name="EndChar" Value="" Type="Text" MaxLen="1"><Desc>The character marking the end of the data. If present Count parameter is ignored.</Desc></Arg></Args><Response ACK="false"><Res Name="Bytes" Value="" Type="Base64" MaxLen="100000"><Desc>FP raw response in BASE64 encoded string</Desc></Res></Response></Command><Command Name="ProgramTransferAmountParam_RA" CmdByte="0x4F"><FPOperation>Program parameter for automatic transfer of daily available amounts.</FPOperation><Args><Arg Name="" Value="A" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="W" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionTransferAmount" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol with value:   - \'0\' - No   - \'1\' - Yes</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'A\'> <;> <\'W\'> <;> <OptionTransferAmount[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadDHCP_Status" CmdByte="0x4E"><FPOperation>Provides information about device\'s DHCP status</FPOperation><Args><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="T" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="1" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'R\'><;><\'T\'><;><\'1\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="T" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="1" Type="OptionHardcoded" MaxLen="1" /><Res Name="OptionDhcpStatus" Value="" Type="Option" MaxLen="1"><Options><Option Name="Disabled" Value="0" /><Option Name="Enabled" Value="1" /></Options><Desc>(DHCP Status) 1 symbol for device\'s DHCP status  - \'0\' - Disabled   - \'1\' - Enabled</Desc></Res><ResFormatRaw><![CDATA[<\'R\'><;><\'T\'><;><\'1\'><;><DhcpStatus[1]>]]></ResFormatRaw></Response></Command><Command Name="ReadTCP_Addresses" CmdByte="0x4E"><FPOperation>Provides information about device\'s IP address, subnet mask, gateway address, DNS address.</FPOperation><Args><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="T" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionAddressType" Value="" Type="Option" MaxLen="1"><Options><Option Name="DNS address" Value="5" /><Option Name="Gateway address" Value="4" /><Option Name="IP address" Value="2" /><Option Name="Subnet Mask" Value="3" /></Options><Desc>1 symbol with value:   - \'2\' - IP address   - \'3\' - Subnet Mask   - \'4\' - Gateway address   - \'5\' - DNS address</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'R\'><;><\'T\'><;><AddressType[1]> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="T" Type="OptionHardcoded" MaxLen="1" /><Res Name="OptionAddressType" Value="" Type="Option" MaxLen="1"><Options><Option Name="DNS address" Value="5" /><Option Name="Gateway address" Value="4" /><Option Name="IP address" Value="2" /><Option Name="Subnet Mask" Value="3" /></Options><Desc>(Address) 1 symbol with value:   - \'2\' - IP address   - \'3\' - Subnet Mask   - \'4\' - Gateway address   - \'5\' - DNS address</Desc></Res><Res Name="DeviceAddress" Value="" Type="Text" MaxLen="15"><Desc>15 symbols for the device\'s addresses</Desc></Res><ResFormatRaw><![CDATA[<\'R\'><;><\'T\'><;>< AddressType[1]><;><DeviceAddress[15]>]]></ResFormatRaw></Response></Command><Command Name="ReadLastReceiptQRcodeData" CmdByte="0x72"><FPOperation>Provides information about the QR code data in last issued receipt.</FPOperation><Args><Arg Name="" Value="B" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'B\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="QRcodeData" Value="" Type="Text" MaxLen="60"><Desc>Up to 60 symbols for last issued receipt QR code data separated by  symbol \'*\' in format: FM Number*Receipt Number*Receipt  Date*Receipt Hour*Receipt Amount</Desc></Res><ResFormatRaw><![CDATA[<QRcodeData[60]>]]></ResFormatRaw></Response></Command><Command Name="ProgHeader" CmdByte="0x49"><FPOperation>Program the contents of a header lines.</FPOperation><Args><Arg Name="OptionHeaderLine" Value="" Type="Option" MaxLen="1"><Options><Option Name="Header 1" Value="1" /><Option Name="Header 2" Value="2" /><Option Name="Header 3" Value="3" /><Option Name="Header 4" Value="4" /><Option Name="Header 5" Value="5" /><Option Name="Header 6" Value="6" /><Option Name="Header 7" Value="7" /></Options><Desc>1 symbol with value:   - \'1\' - Header 1   - \'2\' - Header 2   - \'3\' - Header 3   - \'4\' - Header 4   - \'5\' - Header 5   - \'6\' - Header 6   - \'7\' - Header 7</Desc></Arg><Arg Name="HeaderText" Value="" Type="Text" MaxLen="64"><Desc>TextLength symbols for header lines</Desc></Arg><ArgsFormatRaw><![CDATA[<OptionHeaderLine[1]> <;> <HeaderText[TextLength]> ]]></ArgsFormatRaw></Args></Command><Command Name="SetActiveLogoNum" CmdByte="0x23"><FPOperation>Sets logo number, which is active and will be printed as logo in the receipt header. Print information about active number.</FPOperation><Args><Arg Name="LogoNumber" Value="" Type="Text" MaxLen="1"><Desc>1 character value from \'0\' to \'9\' or \'?\'. The number sets the active file, and  the \'?\' invokes only printing of information</Desc></Arg><ArgsFormatRaw><![CDATA[ <LogoNumber[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="CloseNonFiscalReceipt" CmdByte="0x2F"><FPOperation>Closes the non-fiscal receipt.</FPOperation></Command><Command Name="UnpairAllDevices" CmdByte="0x4E"><FPOperation>Removes all paired devices. To apply use -SaveNetworkSettings()</FPOperation><Args><Arg Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="B" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="D" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'P\'><;><\'B\'><;><\'D\'> ]]></ArgsFormatRaw></Args></Command><Command Name="DisplayDateTime" CmdByte="0x28"><FPOperation>Shows the current date and time on the external display.</FPOperation></Command><Command Name="SetTCP_AutoStart" CmdByte="0x4E"><FPOperation>Set device\'s TCP autostart . To apply use -SaveNetworkSettings()</FPOperation><Args><Arg Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="Z" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="2" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionTCPAutoStart" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol with value:   - \'0\' - No   - \'1\' - Yes</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'P\'><;><\'Z\'><;><\'2\'><;><TCPAutoStart[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadNBLParameter" CmdByte="0x4F"><FPOperation>Provide information about NBL parameter to be monitored by the fiscal device.</FPOperation><Args><Arg Name="" Value="N" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'N\'> <;> <\'R\'>   ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="N" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Res Name="OptionNBL" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol with value:   - \'0\' - No   - \'1\' - Yes</Desc></Res><ResFormatRaw><![CDATA[<\'N\'> <;> <\'R\'> <;> <OptionNBL[1]>]]></ResFormatRaw></Response></Command><Command Name="PrintOrStoreEJByDate" CmdByte="0x7C"><FPOperation>Print or store Electronic Journal Report by initial and end date.</FPOperation><Args><Arg Name="OptionReportStorage" Value="" Type="Option" MaxLen="2"><Options><Option Name="Printing" Value="J1" /><Option Name="SD card storage" Value="J4" /><Option Name="USB storage" Value="J2" /></Options><Desc>1 character with value:   - \'J1\' - Printing   - \'J2\' - USB storage   - \'J4\' - SD card storage</Desc></Arg><Arg Name="" Value="D" Type="OptionHardcoded" MaxLen="1" /><Arg Name="StartRepFromDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name="EndRepFromDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><ArgsFormatRaw><![CDATA[<ReportStorage[2]> <;> <\'D\'> <;> <StartRepFromDate "DDMMYY"> <;>  <EndRepFromDate "DDMMYY"> ]]></ArgsFormatRaw></Args></Command><Command Name="SetTCP_ActiveModule" CmdByte="0x4E"><FPOperation>Sets the used TCP module for communication - Lan or WiFi. To apply use -SaveNetworkSettings()</FPOperation><Args><Arg Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="Z" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="U" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionUsedModule" Value="" Type="Option" MaxLen="1"><Options><Option Name="LAN" Value="1" /><Option Name="WiFi" Value="2" /></Options><Desc>1 symbol with value:   - \'1\' - LAN   - \'2\' - WiFi</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'P\'><;><\'Z\'><;><\'U\'><;><UsedModule[1]><;> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadDailyReturnedChangeAmounts_Old" CmdByte="0x6E"><FPOperation>Provides information about the amounts returned as change by type of payment. Command works for KL version 2 devices.</FPOperation><Args><Arg Name="" Value="6" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'6\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="6" Type="OptionHardcoded" MaxLen="1" /><Res Name="AmountPayment0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name="AmountPayment1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name="AmountPayment2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name="AmountPayment3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name="AmountPayment4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><ResFormatRaw><![CDATA[<\'6\'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]>]]></ResFormatRaw></Response></Command><Command Name="PrintEJByZBlocksCustom" CmdByte="0x7C"><FPOperation>Print Electronic Journal Report by number of Z report blocks and selected documents content. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.</FPOperation><Args><Arg Name="" Value="j1" Type="OptionHardcoded" MaxLen="2" /><Arg Name="" Value="X" Type="OptionHardcoded" MaxLen="1" /><Arg Name="FlagsReceipts" Value="" Type="Flags" MaxLen="1"><Desc>1 symbol for Receipts included in EJ:  Flags.7=0  Flags.6=1  Flags.5=1 Yes, Flags.5=0 No (Include PO)  Flags.4=1 Yes, Flags.4=0 No (Include RA)  Flags.3=1 Yes, Flags.3=0 No (Include Credit Note)  Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp)  Flags.1=1 Yes, Flags.1=0 No (Include Invoice)  Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)</Desc></Arg><Arg Name="FlagsReports" Value="" Type="Flags" MaxLen="1"><Desc>1 symbol for Reports included in EJ:  Flags.7=0  Flags.6=1  Flags.5=0  Flags.4=1 Yes, Flags.4=0 No (Include FM reports)  Flags.3=1 Yes, Flags.3=0 No (Include Other reports)  Flags.2=1 Yes, Flags.2=0 No (Include Daily X)  Flags.1=1 Yes, Flags.1=0 No (Include Daily Z)  Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)</Desc></Arg><Arg Name="" Value="Z" Type="OptionHardcoded" MaxLen="1" /><Arg Name="StartZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for initial number report in format ####</Desc></Arg><Arg Name="EndZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for final number report in format ####</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'j1\'> <;> <\'X\'> <;> <FlagsReceipts [1]> <;> <FlagsReports [1]> <;> <\'Z\'> <;> <StartZNum[4]> <;> <EndZNum[4]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadTCP_UsedModule" CmdByte="0x4E"><FPOperation>Read the used TCP module for communication - Lan or WiFi</FPOperation><Args><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="Z" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="U" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'R\'><;><\'Z\'><;><\'U\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="Z" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="U" Type="OptionHardcoded" MaxLen="1" /><Res Name="OptionUsedModule" Value="" Type="Option" MaxLen="1"><Options><Option Name="LAN" Value="1" /><Option Name="WiFi" Value="2" /></Options><Desc>(Module) 1 symbol with value:   - \'1\' - LAN   - \'2\' - WiFi</Desc></Res><ResFormatRaw><![CDATA[<\'R\'><;><\'Z\'><;><\'U\'><;><UsedModule[1]>]]></ResFormatRaw></Response></Command><Command Name="PaperFeed" CmdByte="0x2B"><FPOperation>Feeds one line of paper.</FPOperation></Command><Command Name="CloseReceipt" CmdByte="0x38"><FPOperation>Close the fiscal receipt (Fiscal receipt, Invoice receipt, Storno receipt, Credit Note or Non-fical receipt). When the payment is finished.</FPOperation></Command><Command Name="ReadSpecifiedReceiptQRcodeData" CmdByte="0x72"><FPOperation>Provides information about the QR code data in specified number issued receipt.</FPOperation><Args><Arg Name="" Value="b" Type="OptionHardcoded" MaxLen="1" /><Arg Name="RcpNum" Value="" Type="Decimal_with_format" MaxLen="6" Format="000000"><Desc>6 symbols with format ######</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'b\'><;><RcpNum[6]>  ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="QRcodeData" Value="" Type="Text" MaxLen="60"><Desc>Up to 60 symbols for last issued receipt QR code data separated by  symbol \'*\' in format: FM Number*Receipt Number*Receipt  Date*Receipt Hour*Receipt Amount</Desc></Res><ResFormatRaw><![CDATA[<QRcodeData[60]>]]></ResFormatRaw></Response></Command><Command Name="SellPLUfromDep_" CmdByte="0x34"><FPOperation>Registers the sell (for correction use minus sign in the price field)  of article with specified department, name, price, quantity and/or discount/addition on  the transaction.</FPOperation><Args><Arg Name="NamePLU" Value="" Type="Text" MaxLen="36"><Desc>36 symbols for name of sale. 34 symbols are printed on  paper. Symbol 0x7C \'|\' is new line separator.</Desc></Arg><Arg Name="DepNum" Value="" Type="Decimal_plus_80h" MaxLen="2"><Desc>1 symbol for article department  attachment, formed in the following manner: DepNum[HEX] + 80h  example: Dep01 = 81h, Dep02 = 82h … Dep19 = 93h  Department range from 1 to 127</Desc></Arg><Arg Name="Price" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for article\'s price. Use minus sign \'-\' for correction</Desc></Arg><Arg Name="Quantity" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10symbols for article\'s quantity sold</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="*" /></Arg><Arg Name="DiscAddP" Value="" Type="Decimal" MaxLen="7"><Desc>Up to 7 for percentage of discount/addition. Use  minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="," /></Arg><Arg Name="DiscAddV" Value="" Type="Decimal" MaxLen="8"><Desc>Up to 8 symbols for percentage of  discount/addition. Use minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence=":" /></Arg><ArgsFormatRaw><![CDATA[ <NamePLU[36]> <;> <DepNum[1]> <;> <Price[1..10]> {<\'*\'> <Quantity[1..10]>} {<\',\'> <DiscAddP[1..7]>} {<\':\'> <DiscAddV[1..8]>} ]]></ArgsFormatRaw></Args></Command><Command Name="ArrangePayments" CmdByte="0x44"><FPOperation>Arrangement of payment positions according to NRA list: 0-Cash, 1- Check, 2-Talon, 3-V.Talon, 4-Packaging, 5-Service, 6-Damage, 7-Card, 8-Bank, 9- Programming Name 1, 10-Programming Name 2, 11-Currency.</FPOperation><Args><Arg Name="Option" Value="*" Type="OptionHardcoded" MaxLen="1" /><Arg Name="PaymentPosition0" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 0 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Arg><Arg Name="PaymentPosition1" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 1 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Arg><Arg Name="PaymentPosition2" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 2 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Arg><Arg Name="PaymentPosition3" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 3 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Arg><Arg Name="PaymentPosition4" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 4 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Arg><Arg Name="PaymentPosition5" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 5 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Arg><Arg Name="PaymentPosition6" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 6 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Arg><Arg Name="PaymentPosition7" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 7 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Arg><Arg Name="PaymentPosition8" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 8 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Arg><Arg Name="PaymentPosition9" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 9 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Arg><Arg Name="PaymentPosition10" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 10 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Arg><Arg Name="PaymentPosition11" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 11 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Arg><ArgsFormatRaw><![CDATA[ <Option[\'*\']> <;> <PaymentPosition0[2]> <;> <PaymentPosition1[2]> <;> <PaymentPosition2[2]> <;> <PaymentPosition3[2]> <;> <PaymentPosition4[2]> <;> <PaymentPosition5[2]> <;> <PaymentPosition6[2]> <;> <PaymentPosition7[2]> <;> <PaymentPosition8[2]> <;> <PaymentPosition9[2]> <;> <PaymentPosition10[2]> <;> <PaymentPosition11[2]> ]]></ArgsFormatRaw></Args></Command><Command Name="OpenCreditNoteWithFreeCustomerData" CmdByte="0x30"><FPOperation>Opens a fiscal invoice credit note receipt assigned to the specified operator number and operator password with free info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.</FPOperation><Args><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbol from 1 to 20 corresponding to operator\'s  number</Desc></Arg><Arg Name="OperPass" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for operator\'s password</Desc></Arg><Arg Name="reserved" Value="0" Type="OptionHardcoded" MaxLen="1" /><Arg Name="reserved" Value="0" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionInvoiceCreditNotePrintType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Buffered Printing" Value="E" /><Option Name="Postponed Printing" Value="C" /><Option Name="Step by step printing" Value="A" /></Options><Desc>1 symbol with value:  - \'A\' - Step by step printing  - \'C\' - Postponed Printing  - \'E\' - Buffered Printing</Desc></Arg><Arg Name="Recipient" Value="" Type="Text" MaxLen="26"><Desc>26 symbols for Invoice recipient</Desc></Arg><Arg Name="Buyer" Value="" Type="Text" MaxLen="16"><Desc>16 symbols for Invoice buyer</Desc></Arg><Arg Name="VATNumber" Value="" Type="Text" MaxLen="13"><Desc>13 symbols for customer Fiscal number</Desc></Arg><Arg Name="UIC" Value="" Type="Text" MaxLen="13"><Desc>13 symbols for customer Unique Identification Code</Desc></Arg><Arg Name="Address" Value="" Type="Text" MaxLen="30"><Desc>30 symbols for Address</Desc></Arg><Arg Name="OptionUICType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Bulstat" Value="0" /><Option Name="EGN" Value="1" /><Option Name="Foreigner Number" Value="2" /><Option Name="NRA Official Number" Value="3" /></Options><Desc>1 symbol for type of Unique Identification Code:    - \'0\' - Bulstat   - \'1\' - EGN   - \'2\' - Foreigner Number   - \'3\' - NRA Official Number</Desc></Arg><Arg Name="OptionStornoReason" Value="" Type="Option" MaxLen="1"><Options><Option Name="Goods Claim or Goods return" Value="1" /><Option Name="Operator error" Value="0" /><Option Name="Tax relief" Value="2" /></Options><Desc>1 symbol for reason of storno operation with value:   - \'0\' - Operator error   - \'1\' - Goods Claim or Goods return   - \'2\' - Tax relief</Desc></Arg><Arg Name="RelatedToInvoiceNum" Value="" Type="Text" MaxLen="10"><Desc>10 symbols for issued invoice number</Desc></Arg><Arg Name="RelatedToInvoiceDateTime" Value="" Type="DateTime" MaxLen="10" Format="dd-MM-yy HH:mm:ss"><Desc>17 symbols for issued invoice date and time in format</Desc></Arg><Arg Name="RelatedToRcpNum" Value="" Type="Decimal" MaxLen="6"><Desc>Up to 6 symbols for issued receipt number</Desc></Arg><Arg Name="FMNum" Value="" Type="Text" MaxLen="8"><Desc>8 symbols for number of the Fiscal Memory</Desc></Arg><Arg Name="RelatedToURN" Value="" Type="Text" MaxLen="24"><Desc>Up to 24 symbols for the issed invoice receipt unique receipt number.  NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where:  * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device  number,  * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator,  * YYYYYYY - 7 symbols [0-9] for next number of the receipt</Desc><Meta MinLen="24" Compulsory="false" ValIndicatingPresence=";" /></Arg><ArgsFormatRaw><![CDATA[ <OperNum[1..2]> <;> <OperPass[6]> <;> <reserved[\'0\']> <;> <reserved[\'0\']> <;> <InvoiceCreditNotePrintType[1]> <;> <Recipient[26]> <;> <Buyer[16]> <;> <VATNumber[13]> <;> <UIC[13]> <;> <Address[30]> <;> <UICType[1]> <;> <StornoReason[1]> <;> <RelatedToInvoiceNum[10]> <;> <RelatedToInvoiceDateTime"DD-MM-YY HH:MM:SS"> <;> <RelatedToRcpNum[1..6]> <;> <FMNum[8]> { <;> <RelatedToURN[24]> } ]]></ArgsFormatRaw></Args></Command><Command Name="PrintBarcode" CmdByte="0x51"><FPOperation>Prints barcode from type stated by CodeType and CodeLen and with data stated in CodeData field. Command works only for fiscal printer devices. ECR does not support this command. The command is not supported by KL ECRs!</FPOperation><Args><Arg Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionCodeType" Value="" Type="Option" MaxLen="1"><Options><Option Name="CODABAR" Value="6" /><Option Name="CODE 128" Value="I" /><Option Name="CODE 39" Value="4" /><Option Name="CODE 93" Value="H" /><Option Name="EAN 13" Value="2" /><Option Name="EAN 8" Value="3" /><Option Name="ITF" Value="5" /><Option Name="UPC A" Value="0" /><Option Name="UPC E" Value="1" /></Options><Desc>1 symbol with possible values:   - \'0\' - UPC A   - \'1\' - UPC E   - \'2\' - EAN 13   - \'3\' - EAN 8   - \'4\' - CODE 39   - \'5\' - ITF   - \'6\' - CODABAR   - \'H\' - CODE 93   - \'I\' - CODE 128</Desc></Arg><Arg Name="CodeLen" Value="" Type="Decimal" MaxLen="2"><Desc>Up to 2 bytes for number of bytes according to the table</Desc></Arg><Arg Name="CodeData" Value="" Type="Text" MaxLen="100"><Desc>Up to 100 bytes data in range according to the table</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'P\'> <;> <CodeType[1]> <;> <CodeLen[1..2]> <;> <CodeData[100]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadDailySaleAndStornoAmountsByVAT" CmdByte="0x6D"><FPOperation>Provides information about the accumulated sale and storno amounts by VAT group.</FPOperation><Response ACK="false"><Res Name="SaleAmountVATGr0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the amount accumulated from sales by VAT group А</Desc></Res><Res Name="SaleAmountVATGr1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the amount accumulated from sales by VAT group Б</Desc></Res><Res Name="SaleAmountVATGr2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the amount accumulated from sales by VAT group В</Desc></Res><Res Name="SaleAmountVATGr3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the amount accumulated from sales by VAT group Г</Desc></Res><Res Name="SaleAmountVATGr4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the amount accumulated from sales by VAT group Д</Desc></Res><Res Name="SaleAmountVATGr5" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the amount accumulated from sales by VAT group Е</Desc></Res><Res Name="SaleAmountVATGr6" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the amount accumulated from sales by VAT group Ж</Desc></Res><Res Name="SaleAmountVATGr7" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the amount accumulated from sales by VAT group З</Desc></Res><Res Name="SumAllVATGr" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for sum of all VAT groups</Desc></Res><Res Name="StornoAmountVATGr0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the amount accumulated from Storno by VAT group А</Desc></Res><Res Name="StornoAmountVATGr1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the amount accumulated from Storno by VAT group Б</Desc></Res><Res Name="StornoAmountVATGr2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the amount accumulated from Storno by VAT group В</Desc></Res><Res Name="StornoAmountVATGr3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the amount accumulated from Storno by VAT group Г</Desc></Res><Res Name="StornoAmountVATGr4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the amount accumulated from Storno by VAT group Д</Desc></Res><Res Name="StornoAmountVATGr5" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the amount accumulated from Storno by VAT group Е</Desc></Res><Res Name="StornoAmountVATGr6" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the amount accumulated from Storno by VAT group Ж</Desc></Res><Res Name="StornoAmountVATGr7" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the amount accumulated from Storno by VAT group З</Desc></Res><Res Name="StornoAllVATGr" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the amount accumulated from Storno by all groups</Desc></Res><ResFormatRaw><![CDATA[<SaleAmountVATGr0[1..13]> <;> <SaleAmountVATGr1[1..13]> <;> <SaleAmountVATGr2[1..13]> <;> <SaleAmountVATGr3[1..13]> <;> <SaleAmountVATGr4[1..13]> <;> <SaleAmountVATGr5[1..13]> <;> <SaleAmountVATGr6[1..13]> <;> <SaleAmountVATGr7[1..13]> <;><SumAllVATGr[1..13]><;> <StornoAmountVATGr0[1..13]> <;><StornoAmountVATGr1[1..13]> <;> <StornoAmountVATGr2[1..13]> <;><StornoAmountVATGr3[1..13]> <;> <StornoAmountVATGr4[1..13]> <;>< StornoAmountVATGr5[1..13]> <;> <StornoAmountVATGr6[1..13]> <;> <StornoAmountVATGr7[1..13]> <;> <StornoAllVATGr[1..13]>]]></ResFormatRaw></Response></Command><Command Name="PrintDepartmentReport" CmdByte="0x76"><FPOperation>Print a department report with or without zeroing (\'Z\' or \'X\').</FPOperation><Args><Arg Name="OptionZeroing" Value="" Type="Option" MaxLen="1"><Options><Option Name="Without zeroing" Value="X" /><Option Name="Zeroing" Value="Z" /></Options><Desc>1 symbol with value:   - \'Z\' - Zeroing   - \'X\' - Without zeroing</Desc></Arg><ArgsFormatRaw><![CDATA[ <OptionZeroing[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadEJCustom" CmdByte="0x7C"><FPOperation>Read or Store Electronic Journal report by CSV format option and document content selecting. If CSV format is set the content can includes only fiscal receipts. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.</FPOperation><Args><Arg Name="OptionStorageReport" Value="" Type="Option" MaxLen="2"><Options><Option Name="To PC" Value="j0" /><Option Name="To SD card" Value="j4" /><Option Name="To USB Flash Drive" Value="j2" /></Options><Desc>1 character with value   - \'j0\' - To PC   - \'j2\' - To USB Flash Drive   - \'j4\' - To SD card</Desc></Arg><Arg Name="OptionCSVformat" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="X" /><Option Name="Yes" Value="C" /></Options><Desc>1 symbol with value:   - \'C\' - Yes   - \'X\' - No</Desc></Arg><Arg Name="FlagsReceipts" Value="" Type="Flags" MaxLen="1"><Desc>1 symbol for Receipts included in EJ:  Flags.7=0  Flags.6=1  Flags.5=1 Yes, Flags.5=0 No (Include PO)  Flags.4=1 Yes, Flags.4=0 No (Include RA)  Flags.3=1 Yes, Flags.3=0 No (Include Credit Note)  Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp)  Flags.1=1 Yes, Flags.1=0 No (Include Invoice)  Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)</Desc></Arg><Arg Name="FlagsReports" Value="" Type="Flags" MaxLen="1"><Desc>1 symbol for Reports included in EJ:  Flags.7=0  Flags.6=1  Flags.5=0  Flags.4=1 Yes, Flags.4=0 No (Include FM reports)  Flags.3=1 Yes, Flags.3=0 No (Include Other reports)  Flags.2=1 Yes, Flags.2=0 No (Include Daily X)  Flags.1=1 Yes, Flags.1=0 No (Include Daily Z)  Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)</Desc></Arg><Arg Name="" Value="*" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StorageReport[2]> <;> <CSVformat[1]> <;> <FlagsReceipts[1]> <;> <FlagsReports[1]> <;> <\'*\'> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="PrintBriefFMDepartmentsReport" CmdByte="0x77"><FPOperation>Prints a brief Departments report from the FM.</FPOperation><Args><Arg Name="Option" Value="D" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <Option[\'D\']>  ]]></ArgsFormatRaw></Args></Command><Command Name="DisplayTextLine2" CmdByte="0x26"><FPOperation>Shows a 20-symbols text in the lower external display line.</FPOperation><Args><Arg Name="Text" Value="" Type="Text" MaxLen="20"><Desc>20 symbols text</Desc></Arg><ArgsFormatRaw><![CDATA[ <Text[20]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadDailyCounters" CmdByte="0x6E"><FPOperation>Provides information about the current reading of the daily-report- with-zeroing counter, the number of the last block stored in FM, the number of EJ and the date and time of the last block storage in the FM.</FPOperation><Args><Arg Name="" Value="5" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'5\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="5" Type="OptionHardcoded" MaxLen="1" /><Res Name="LastReportNumFromReset" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for number of the last report from reset</Desc></Res><Res Name="LastFMBlockNum" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for number of the last FM report</Desc></Res><Res Name="EJNum" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for number of EJ</Desc></Res><Res Name="DateTime" Value="" Type="DateTime" MaxLen="10" Format="dd-MM-yyyy HH:mm"><Desc>16 symbols for date and time of the last block storage in FM in  format "DD-MM-YYYY HH:MM"</Desc></Res><ResFormatRaw><![CDATA[<\'5\'> <;> <LastReportNumFromReset[1..5]> <;> <LastFMBlockNum[1..5]> <;> <EJNum[1..5]> <;> <DateTime "DD-MM-YYYY HH:MM">]]></ResFormatRaw></Response></Command><Command Name="SetWiFi_Password" CmdByte="0x4E"><FPOperation>Program device\'s WiFi network password where it will connect. To apply use -SaveNetworkSettings()</FPOperation><Args><Arg Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="W" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="PassLength" Value="" Type="Decimal" MaxLen="3"><Desc>Up to 3 symbols for the WiFi password len</Desc></Arg><Arg Name="Password" Value="" Type="Text" MaxLen="100"><Desc>Up to 100 symbols for the device\'s WiFi password</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'P\'><;><\'W\'><;><\'P\'><;><PassLength[1..3]><;><Password[100]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadScaleQuantity" CmdByte="0x5A"><FPOperation>Provides information about the current quantity measured by scale</FPOperation><Args><Arg Name="Option" Value="Q" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <Option[\'Q\']> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="Quantity" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for quantity</Desc></Res><ResFormatRaw><![CDATA[<Quantity[1..13]>]]></ResFormatRaw></Response></Command><Command Name="PrintEJCustom" CmdByte="0x7C"><FPOperation>Print Electronic Journal report with selected documents content. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.</FPOperation><Args><Arg Name="" Value="j1" Type="OptionHardcoded" MaxLen="2" /><Arg Name="" Value="X" Type="OptionHardcoded" MaxLen="1" /><Arg Name="FlagsReceipts" Value="" Type="Flags" MaxLen="1"><Desc>1 symbol for Receipts included in EJ:  Flags.7=0  Flags.6=1  Flags.5=1 Yes, Flags.5=0 No (Include PO)  Flags.4=1 Yes, Flags.4=0 No (Include RA)  Flags.3=1 Yes, Flags.3=0 No (Include Credit Note)  Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp)  Flags.1=1 Yes, Flags.1=0 No (Include Invoice)  Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)</Desc></Arg><Arg Name="FlagsReports" Value="" Type="Flags" MaxLen="1"><Desc>1 symbol for Reports included in EJ:  Flags.7=0  Flags.6=1  Flags.5=0  Flags.4=1 Yes, Flags.4=0 No (Include FM reports)  Flags.3=1 Yes, Flags.3=0 No (Include Other reports)  Flags.2=1 Yes, Flags.2=0 No (Include Daily X)  Flags.1=1 Yes, Flags.1=0 No (Include Daily Z)  Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)</Desc></Arg><Arg Name="" Value="*" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'j1\'> <;> <\'X\'> <;> <FlagsReceipts [1]> <;> <FlagsReports [1]> <;> <\'*\'> ]]></ArgsFormatRaw></Args></Command><Command Name="StartTest_Bluetooth" CmdByte="0x4E"><FPOperation>Start Bluetooth test on the device and print out the result</FPOperation><Args><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="B" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="T" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'R\'><;><\'B\'><;><\'T\'> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadBriefFMDepartmentsReportByDate" CmdByte="0x7B"><FPOperation>Read a brief FM Departments report by initial and end date.</FPOperation><Args><Arg Name="StartDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name="EndDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name="Option" Value="D" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionReading" Value="8" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartDate "DDMMYY"> <;> <EndDate "DDMMYY"> <;> <Option[\'D\']> <;> <OptionReading[\'8\']> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="EraseAllPLUs" CmdByte="0x4B"><FPOperation>Erase all articles in PLU database.</FPOperation><Args><Arg Name="PLUNum" Value="00000" Type="OptionHardcoded" MaxLen="5" /><Arg Name="Option" Value="#@$+$" Type="OptionHardcoded" MaxLen="5" /><Arg Name="Password" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for password</Desc></Arg><ArgsFormatRaw><![CDATA[ <PLUNum[\'00000\']> <;> <Option[\'#@$+$\']> <;> <Password[6]> ]]></ArgsFormatRaw></Args></Command><Command Name="PrintDetailedFMDepartmentsReportByDate" CmdByte="0x7A"><FPOperation>Print a detailed FM Departments report by initial and end date.</FPOperation><Args><Arg Name="StartDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name="EndDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name="Option" Value="D" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartDate "DDMMYY"> <;> <EndDate "DDMMYY"> <;> <Option[\'D\']> ]]></ArgsFormatRaw></Args></Command><Command Name="ConfirmFiscalization" CmdByte="0x41"><FPOperation>Confirm Unique Identification Code (UIC) and UIC type into the operative memory.</FPOperation><Args><Arg Name="Password" Value="" Type="Text" MaxLen="6"><Desc>6-symbols string</Desc></Arg><Arg Name="" Value="2" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <Password[6]> <;> <\'2\'> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadElectronicReceipt_QR_Data" CmdByte="0x72"><FPOperation>Starts session for reading electronic receipt by number with its QR code data in the end.</FPOperation><Args><Arg Name="" Value="e" Type="OptionHardcoded" MaxLen="1" /><Arg Name="RcpNum" Value="" Type="Decimal_with_format" MaxLen="6" Format="000000"><Desc>6 symbols with format ######</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'e\'><;><RcpNum[6]> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="ReadDailyRAbyOperator" CmdByte="0x6F"><FPOperation>Read the RA by type of payment and the total number of operations by specified operator.</FPOperation><Args><Arg Name="" Value="2" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s number</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'2\'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="2" Type="OptionHardcoded" MaxLen="1" /><Res Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s number</Desc></Res><Res Name="AmountRA_Payment0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the RA by type of payment 0</Desc></Res><Res Name="AmountRA_Payment1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the RA by type of payment 1</Desc></Res><Res Name="AmountRA_Payment2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the RA by type of payment 2</Desc></Res><Res Name="AmountRA_Payment3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the RA by type of payment 3</Desc></Res><Res Name="AmountRA_Payment4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the RA by type of payment 4</Desc></Res><Res Name="AmountRA_Payment5" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the RA by type of payment 5</Desc></Res><Res Name="AmountRA_Payment6" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the RA by type of payment 6</Desc></Res><Res Name="AmountRA_Payment7" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the RA by type of payment 7</Desc></Res><Res Name="AmountRA_Payment8" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the RA by type of payment 8</Desc></Res><Res Name="AmountRA_Payment9" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the RA by type of payment 9</Desc></Res><Res Name="AmountRA_Payment10" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the RA by type of payment 10</Desc></Res><Res Name="AmountRA_Payment11" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the RA by type of payment 11</Desc></Res><Res Name="NoRA" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for the total number of operations</Desc></Res><ResFormatRaw><![CDATA[<\'2\'> <;> <OperNum[1..2]> <;> <AmountRA_Payment0[1..13]> <;> <AmountRA_Payment1[1..13]> <;> <AmountRA_Payment2[1..13]> <;> <AmountRA_Payment3[1..13]> <;> <AmountRA_Payment4[1..13]> <;> <AmountRA_Payment5[1..13]> <;><AmountRA_Payment6[1..13]> <;> <AmountRA_Payment7[1..13]> <;><AmountRA_Payment8[1..13]> <;> <AmountRA_Payment9[1..13]> <;><AmountRA_Payment10[1..13]> <;> <AmountRA_Payment11[1..13]> <;> <NoRA[1..5]>]]></ResFormatRaw></Response></Command><Command Name="ReadDailyReportParameter" CmdByte="0x4F"><FPOperation>Provide information about automatic daily report printing or not printing parameter</FPOperation><Args><Arg Name="" Value="H" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'H\'> <;> <\'R\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="H" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Res Name="OptionDailyReport" Value="" Type="Option" MaxLen="1"><Options><Option Name="Generate automatic Z report" Value="0" /><Option Name="Print automatic Z report" Value="1" /></Options><Desc>1 symbol with value:   - \'1\' - Print automatic Z report   - \'0\' - Generate automatic Z report</Desc></Res><ResFormatRaw><![CDATA[<\'H\'> <;> <\'R\'> <;> <OptionDailyReport[1]>]]></ResFormatRaw></Response></Command><Command Name="StartTest_GPRS" CmdByte="0x4E"><FPOperation>Start GPRS test on the device and print out the result</FPOperation><Args><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="G" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="T" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'R\'><;><\'G\'><;><\'T\'> ]]></ArgsFormatRaw></Args></Command><Command Name="DisplayDailyTurnover" CmdByte="0x6E"><FPOperation>Provides information about daily turnover on the FD client display</FPOperation><Args><Arg Name="" Value=":" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\':\'> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadHeader" CmdByte="0x69"><FPOperation>Provides the content of the header lines</FPOperation><Args><Arg Name="OptionHeaderLine" Value="" Type="Option" MaxLen="1"><Options><Option Name="Header 1" Value="1" /><Option Name="Header 2" Value="2" /><Option Name="Header 3" Value="3" /><Option Name="Header 4" Value="4" /><Option Name="Header 5" Value="5" /><Option Name="Header 6" Value="6" /><Option Name="Header 7" Value="7" /></Options><Desc>1 symbol with value:   - \'1\' - Header 1   - \'2\' - Header 2   - \'3\' - Header 3   - \'4\' - Header 4   - \'5\' - Header 5   - \'6\' - Header 6   - \'7\' - Header 7</Desc></Arg><ArgsFormatRaw><![CDATA[ <OptionHeaderLine[1]>  ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="OptionHeaderLine" Value="" Type="Option" MaxLen="1"><Options><Option Name="Header 1" Value="1" /><Option Name="Header 2" Value="2" /><Option Name="Header 3" Value="3" /><Option Name="Header 4" Value="4" /><Option Name="Header 5" Value="5" /><Option Name="Header 6" Value="6" /><Option Name="Header 7" Value="7" /></Options><Desc>(Line Number) 1 symbol with value:   - \'1\' - Header 1   - \'2\' - Header 2   - \'3\' - Header 3   - \'4\' - Header 4   - \'5\' - Header 5   - \'6\' - Header 6   - \'7\' - Header 7</Desc></Res><Res Name="HeaderText" Value="" Type="Text" MaxLen="64"><Desc>TextLength symbols for header lines</Desc></Res><ResFormatRaw><![CDATA[<OptionHeaderLine[1]> <;> <HeaderText[TextLength]>]]></ResFormatRaw></Response></Command><Command Name="CutPaper" CmdByte="0x29"><FPOperation>Start paper cutter. The command works only in fiscal printer devices.</FPOperation></Command><Command Name="ReadDeviceModuleSupportByFirmware" CmdByte="0x4E"><FPOperation>Provide an information about modules supported by device\'s firmware</FPOperation><Args><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="D" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="S" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'R\'><;><\'D\'><;><\'S\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="D" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="S" Type="OptionHardcoded" MaxLen="1" /><Res Name="OptionLAN" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol for LAN suppor  - \'0\' - No   - \'1\' - Yes</Desc></Res><Res Name="OptionWiFi" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol for WiFi support  - \'0\' - No   - \'1\' - Yes</Desc></Res><Res Name="OptionGPRS" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol for GPRS support  - \'0\' - No   - \'1\' - Yes  BT (Bluetooth) 1 symbol for Bluetooth support  - \'0\' - No   - \'1\' - Yes</Desc></Res><Res Name="OptionBT" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>(Bluetooth) 1 symbol for Bluetooth support  - \'0\' - No   - \'1\' - Yes</Desc></Res><ResFormatRaw><![CDATA[<\'R\'><;><\'D\'><;><\'S\'><;><LAN[1]><;><WiFi[1]><;><GPRS[1]><;><BT[1]>]]></ResFormatRaw></Response></Command><Command Name="SetInvoiceRange" CmdByte="0x50"><FPOperation>Set invoice start and end number range. To execute the command is necessary to grand following condition: the number range to be spent, not used, or not set after the last RAM reset.</FPOperation><Args><Arg Name="StartNum" Value="" Type="Decimal_with_format" MaxLen="10" Format="0000000000"><Desc>10 characters for start number in format: ##########</Desc></Arg><Arg Name="EndNum" Value="" Type="Decimal_with_format" MaxLen="10" Format="0000000000"><Desc>10 characters for end number in format: ##########</Desc></Arg><ArgsFormatRaw><![CDATA[ <StartNum[10]> <;> <EndNum[10]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadWiFi_Password" CmdByte="0x4E"><FPOperation>Read device\'s connected WiFi network password</FPOperation><Args><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="W" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'R\'><;><\'W\'><;><\'P\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="W" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><Res Name="PassLength" Value="" Type="Decimal" MaxLen="3"><Desc>(Length) Up to 3 symbols for the WiFi password length</Desc></Res><Res Name="Password" Value="" Type="Text" MaxLen="100"><Desc>Up to 100 symbols for the device\'s WiFi password</Desc></Res><ResFormatRaw><![CDATA[<\'R\'><;><\'W\'><;><\'P\'><;><PassLength[1..3]><;><Password[100]>]]></ResFormatRaw></Response></Command><Command Name="ProgPLUbarcode" CmdByte="0x4B"><FPOperation>Programs Barcode of article in the internal database.</FPOperation><Args><Arg Name="PLUNum" Value="" Type="Decimal_with_format" MaxLen="5" Format="00000"><Desc>5 symbols for article number in format: #####</Desc></Arg><Arg Name="Option" Value="#@3+$" Type="OptionHardcoded" MaxLen="5" /><Arg Name="Barcode" Value="" Type="Text" MaxLen="13"><Desc>13 symbols for barcode</Desc></Arg><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Option[\'#@3+$\']> <;> <Barcode[13]> ]]></ArgsFormatRaw></Args></Command><Command Name="PrintDetailedFMReportByDate" CmdByte="0x7A"><FPOperation>Prints a detailed FM report by initial and end date.</FPOperation><Args><Arg Name="StartDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name="EndDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><ArgsFormatRaw><![CDATA[ <StartDate "DDMMYY"> <;> <EndDate "DDMMYY"> ]]></ArgsFormatRaw></Args></Command><Command Name="PrintOrStoreEJByZBlocks" CmdByte="0x7C"><FPOperation>Print or store Electronic Journal Report from by number of Z report blocks.</FPOperation><Args><Arg Name="OptionReportStorage" Value="" Type="Option" MaxLen="2"><Options><Option Name="Printing" Value="J1" /><Option Name="SD card storage" Value="J4" /><Option Name="USB storage" Value="J2" /></Options><Desc>1 character with value:   - \'J1\' - Printing   - \'J2\' - USB storage   - \'J4\' - SD card storage</Desc></Arg><Arg Name="" Value="Z" Type="OptionHardcoded" MaxLen="1" /><Arg Name="StartZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for initial number report in format ####</Desc></Arg><Arg Name="EndZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for final number report in format ####</Desc></Arg><ArgsFormatRaw><![CDATA[ <ReportStorage[2]> <;> <\'Z\'> <;> <StartZNum[4]> <;> <EndZNum[4]> ]]></ArgsFormatRaw></Args></Command><Command Name="SellFractQtyPLUwithSpecifiedVATfromDep" CmdByte="0x3D"><FPOperation>Register the sell (for correction use minus sign in the price field) of article with specified VAT. If department is present the relevant accumulations are perfomed in its registers.</FPOperation><Args><Arg Name="NamePLU" Value="" Type="Text" MaxLen="36"><Desc>36 symbols for article\'s name. 34 symbols are printed on paper.  Symbol 0x7C \'|\' is new line separator.</Desc></Arg><Arg Name="OptionVATClass" Value="" Type="Option" MaxLen="1"><Options><Option Name="Forbidden" Value="*" /><Option Name="VAT Class 0" Value="А" /><Option Name="VAT Class 1" Value="Б" /><Option Name="VAT Class 2" Value="В" /><Option Name="VAT Class 3" Value="Г" /><Option Name="VAT Class 4" Value="Д" /><Option Name="VAT Class 5" Value="Е" /><Option Name="VAT Class 6" Value="Ж" /><Option Name="VAT Class 7" Value="З" /></Options><Desc>1 character for VAT class:   - \'А\' - VAT Class 0   - \'Б\' - VAT Class 1   - \'В\' - VAT Class 2   - \'Г\' - VAT Class 3   - \'Д\' - VAT Class 4   - \'Е\' - VAT Class 5   - \'Ж\' - VAT Class 6   - \'З\' - VAT Class 7   - \'*\' - Forbidden</Desc></Arg><Arg Name="Price" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for article\'s price. Use minus sign \'-\' for correction</Desc></Arg><Arg Name="Quantity" Value="" Type="Text" MaxLen="10"><Desc>From 3 to 10 symbols for quantity in format fractional format, e.g. 1/3</Desc><Meta MinLen="10" Compulsory="false" ValIndicatingPresence="*" /></Arg><Arg Name="DiscAddP" Value="" Type="Decimal" MaxLen="7"><Desc>Up to 7 symbols for percentage of discount/addition.  Use minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="," /></Arg><Arg Name="DiscAddV" Value="" Type="Decimal" MaxLen="8"><Desc>Up to 8 symbols for value of discount/addition.  Use minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence=":" /></Arg><Arg Name="DepNum" Value="" Type="Decimal_plus_80h" MaxLen="2"><Desc>1 symbol for article department  attachment, formed in the following manner; example: Dep01 = 81h, Dep02  = 82h … Dep19 = 93h  Department range from 1 to 127</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="!" /></Arg><ArgsFormatRaw><![CDATA[ <NamePLU[36]> <;> <OptionVATClass[1]> <;> <Price[1..10]> {<\'*\'> <Quantity[10]>} {<\',\'> <DiscAddP[1..7]>} {<\':\'> <DiscAddV[1..8]>} {<\'!\'> <DepNum[1]>} ]]></ArgsFormatRaw></Args></Command><Command Name="PrintBriefFMDepartmentsReportByZBlocks" CmdByte="0x79"><FPOperation>Print a brief FM Departments report by initial and end Z report number.</FPOperation><Args><Arg Name="StartZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for the initial FM report number included in report, format ####</Desc></Arg><Arg Name="EndZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for the final FM report number included in report, format ####</Desc></Arg><Arg Name="Option" Value="D" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <Option[\'D\']> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadPLUgeneral" CmdByte="0x6B"><FPOperation>Provides information about the general registers of the specified article.</FPOperation><Args><Arg Name="PLUNum" Value="" Type="Decimal_with_format" MaxLen="5" Format="00000"><Desc>5 symbols for article number with leading zeroes in format: #####</Desc></Arg><Arg Name="Option" Value="1" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Option[\'1\']> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="PLUNum" Value="" Type="Decimal_with_format" MaxLen="5" Format="00000"><Desc>5 symbols for article number with leading zeroes in format #####</Desc></Res><Res Name="Option" Value="1" Type="OptionHardcoded" MaxLen="1" /><Res Name="PLUName" Value="" Type="Text" MaxLen="34"><Desc>34 symbols for article name, new line=0x7C.</Desc></Res><Res Name="Price" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for article price</Desc></Res><Res Name="OptionPrice" Value="" Type="Option" MaxLen="1"><Options><Option Name="Free price is disable valid only programmed price" Value="0" /><Option Name="Free price is enable" Value="1" /><Option Name="Limited price" Value="2" /></Options><Desc>1 symbol for price flag with next value:   - \'0\'- Free price is disable valid only programmed price   - \'1\'- Free price is enable   - \'2\'- Limited price</Desc></Res><Res Name="OptionVATClass" Value="" Type="Option" MaxLen="1"><Options><Option Name="Forbidden" Value="*" /><Option Name="VAT Class 0" Value="А" /><Option Name="VAT Class 1" Value="Б" /><Option Name="VAT Class 2" Value="В" /><Option Name="VAT Class 3" Value="Г" /><Option Name="VAT Class 4" Value="Д" /><Option Name="VAT Class 5" Value="Е" /><Option Name="VAT Class 6" Value="Ж" /><Option Name="VAT Class 7" Value="З" /></Options><Desc>1 character for VAT class:   - \'А\' - VAT Class 0   - \'Б\' - VAT Class 1   - \'В\' - VAT Class 2   - \'Г\' - VAT Class 3   - \'Д\' - VAT Class 4   - \'Е\' - VAT Class 5   - \'Ж\' - VAT Class 6   - \'З\' - VAT Class 7   - \'*\' - Forbidden</Desc></Res><Res Name="BelongToDepNumber" Value="" Type="Decimal_plus_80h" MaxLen="2"><Desc>BelongToDepNumber + 80h, 1 symbol for PLU department  attachment= 0x80 … 0x93   Department range from 1 to 127</Desc></Res><Res Name="TurnoverAmount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for PLU accumulated turnover</Desc></Res><Res Name="SoldQuantity" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for Sales quantity of the article</Desc></Res><Res Name="StornoAmount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for accumulated storno amount</Desc></Res><Res Name="StornoQuantity" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for accumulated storno quantiy</Desc></Res><Res Name="LastZReportNumber" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for the number of the last article report with zeroing</Desc></Res><Res Name="LastZReportDate" Value="" Type="DateTime" MaxLen="10" Format="dd-MM-yyyy HH:mm"><Desc>16 symbols for the date and time of the last article report with zeroing in  format DD-MM-YYYY HH:MM</Desc></Res><Res Name="OptionSingleTransaction" Value="" Type="Option" MaxLen="1"><Options><Option Name="Active Single transaction in receipt" Value="1" /><Option Name="Inactive, default value" Value="0" /></Options><Desc>1 symbol with value:   - \'0\' - Inactive, default value   - \'1\' - Active Single transaction in receipt</Desc></Res><ResFormatRaw><![CDATA[<PLUNum[5]> <;> <Option[\'1\']> <;> <PLUName[34]> <;> <Price[1..10]> <;> <OptionPrice[1]> <;> <OptionVATClass[1]> <;> <BelongToDepNumber[1]> <;> <TurnoverAmount[1..13]> <;> <SoldQuantity[1..13]> <;> <StornoAmount[1..13]> <;> <StornoQuantity[1..13]> <;> <LastZReportNumber[1..5]> <;> <LastZReportDate "DD-MM-YYYY HH:MM"> <;> <SingleTransaction[1]>]]></ResFormatRaw></Response></Command><Command Name="ReadDailyReceivedSalesAmountsByOperator" CmdByte="0x6F"><FPOperation>Read the amounts received from sales by type of payment and specified operator.</FPOperation><Args><Arg Name="" Value="4" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s  number</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'4\'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="4" Type="OptionHardcoded" MaxLen="1" /><Res Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s number</Desc></Res><Res Name="ReceivedSalesAmountPayment0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by sales for payment 0</Desc></Res><Res Name="ReceivedSalesAmountPayment1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by sales for payment 1</Desc></Res><Res Name="ReceivedSalesAmountPayment2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by sales for payment 2</Desc></Res><Res Name="ReceivedSalesAmountPayment3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by sales for payment 3</Desc></Res><Res Name="ReceivedSalesAmountPayment4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by sales for payment 4</Desc></Res><Res Name="ReceivedSalesAmountPayment5" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by sales for payment 5</Desc></Res><Res Name="ReceivedSalesAmountPayment6" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by sales for payment 6</Desc></Res><Res Name="ReceivedSalesAmountPayment7" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by sales for payment 7</Desc></Res><Res Name="ReceivedSalesAmountPayment8" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by sales for payment 8</Desc></Res><Res Name="ReceivedSalesAmountPayment9" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by sales for payment 9</Desc></Res><Res Name="ReceivedSalesAmountPayment10" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by sales for payment 10</Desc></Res><Res Name="ReceivedSalesAmountPayment11" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by sales for payment 11</Desc></Res><ResFormatRaw><![CDATA[<\'4\'> <;> <OperNum[1..2]> <;> <ReceivedSalesAmountPayment0[1..13]> <;> <ReceivedSalesAmountPayment1[1..13]> <;> <ReceivedSalesAmountPayment2[1..13]> <;> <ReceivedSalesAmountPayment3[1..13]> <;> <ReceivedSalesAmountPayment4[1..13]> <;> <ReceivedSalesAmountPayment5[1..13]> <;> <ReceivedSalesAmountPayment6[1..13]> <;> <ReceivedSalesAmountPayment7[1..13]> <;> <ReceivedSalesAmountPayment8[1..13]> <;> <ReceivedSalesAmountPayment9[1..13]> <;> <ReceivedSalesAmountPayment10[1..13]> <;> <ReceivedSalesAmountPayment11[1..13]>]]></ResFormatRaw></Response></Command><Command Name="ReadCustomerData" CmdByte="0x52"><FPOperation>Provide information for specified customer from FD data base.</FPOperation><Args><Arg Name="Option" Value="R" Type="OptionHardcoded" MaxLen="1" /><Arg Name="CustomerNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for customer number in format ####</Desc></Arg><ArgsFormatRaw><![CDATA[ <Option[\'R\']> <;> <CustomerNum[4]> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="CustomerNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>(Customer Number) 4 symbols for customer number in format ####</Desc></Res><Res Name="CustomerCompanyName" Value="" Type="Text" MaxLen="26"><Desc>(Company name) 26 symbols for customer name</Desc></Res><Res Name="CustomerFullName" Value="" Type="Text" MaxLen="16"><Desc>(Buyer Name) 16 symbols for Buyer name</Desc></Res><Res Name="VATNumber" Value="" Type="Text" MaxLen="13"><Desc>13 symbols for VAT number on customer</Desc></Res><Res Name="UIC" Value="" Type="Text" MaxLen="13"><Desc>13 symbols for customer Unique Identification Code</Desc></Res><Res Name="Address" Value="" Type="Text" MaxLen="30"><Desc>30 symbols for address on customer</Desc></Res><Res Name="OptionUICType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Bulstat" Value="0" /><Option Name="EGN" Value="1" /><Option Name="Foreigner Number" Value="2" /><Option Name="NRA Official Number" Value="3" /></Options><Desc>1 symbol for type of Unique Identification Code:    - \'0\' - Bulstat   - \'1\' - EGN   - \'2\' - Foreigner Number   - \'3\' - NRA Official Number</Desc></Res><ResFormatRaw><![CDATA[<CustomerNum[4]> <;> <CustomerCompanyName[26]> <;> <CustomerFullName[16]> <;> <VATNumber[13]> <;> <UIC[13]> <;> <Address[30]> <;> <UICType[1]>]]></ResFormatRaw></Response></Command><Command Name="ReadCurrentReceiptInfo" CmdByte="0x72"><FPOperation>Read the current status of the receipt.</FPOperation><Response ACK="false"><Res Name="OptionIsReceiptOpened" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol with value:   - \'0\' - No   - \'1\' - Yes</Desc></Res><Res Name="SalesNumber" Value="" Type="Decimal_with_format" MaxLen="3" Format="000"><Desc>3 symbols for number of sales in format ###</Desc></Res><Res Name="SubtotalAmountVAT0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for subtotal by VAT group А</Desc></Res><Res Name="SubtotalAmountVAT1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for subtotal by VAT group Б</Desc></Res><Res Name="SubtotalAmountVAT2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for subtotal by VAT group В</Desc></Res><Res Name="OptionForbiddenVoid" Value="" Type="Option" MaxLen="1"><Options><Option Name="allowed" Value="0" /><Option Name="forbidden" Value="1" /></Options><Desc>1 symbol with value:  - \'0\' - allowed  - \'1\' - forbidden</Desc></Res><Res Name="OptionVATinReceipt" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol with value:  - \'0\' - No  - \'1\' - Yes</Desc></Res><Res Name="OptionReceiptFormat" Value="" Type="Option" MaxLen="1"><Options><Option Name="Brief" Value="0" /><Option Name="Detailed" Value="1" /></Options><Desc>(Format) 1 symbol with value:   - \'1\' - Detailed   - \'0\' - Brief</Desc></Res><Res Name="OptionInitiatedPayment" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol with value:  - \'0\' - No  - \'1\' - Yes</Desc></Res><Res Name="OptionFinalizedPayment" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol with value:  - \'0\' - No  - \'1\' - Yes</Desc></Res><Res Name="OptionPowerDownInReceipt" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol with value:  - \'0\' - No  - \'1\' - Yes</Desc></Res><Res Name="OptionTypeReceipt" Value="" Type="Option" MaxLen="1"><Options><Option Name="Invoice Credit note receipt Postponed Printing" Value="7" /><Option Name="Invoice Credit note receipt printing step by step" Value="5" /><Option Name="Invoice sales receipt Postponed Printing" Value="3" /><Option Name="Invoice sales receipt printing step by step" Value="1" /><Option Name="Sales receipt Postponed Printing" Value="2" /><Option Name="Sales receipt printing step by step" Value="0" /><Option Name="Storno receipt Postponed Printing" Value="6" /><Option Name="Storno receipt printing step by step" Value="4" /></Options><Desc>(Receipt and Printing type) 1 symbol with value:   - \'0\' - Sales receipt printing step by step   - \'2\' - Sales receipt Postponed Printing   - \'4\' - Storno receipt printing step by step   - \'6\' - Storno receipt Postponed Printing   - \'1\' - Invoice sales receipt printing step by step   - \'3\' - Invoice sales receipt Postponed Printing   - \'5\' - Invoice Credit note receipt printing step by step   - \'7\' - Invoice Credit note receipt Postponed Printing</Desc></Res><Res Name="ChangeAmount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols the amount of the due change in the stated payment type</Desc></Res><Res Name="OptionChangeType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Change In Cash" Value="0" /><Option Name="Change In Currency" Value="2" /><Option Name="Same As The payment" Value="1" /></Options><Desc>1 symbol with value:   - \'0\' - Change In Cash   - \'1\' - Same As The payment   - \'2\' - Change In Currency</Desc></Res><Res Name="SubtotalAmountVAT3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for subtotal by VAT group Г</Desc></Res><Res Name="SubtotalAmountVAT4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for subtotal by VAT group Д</Desc></Res><Res Name="SubtotalAmountVAT5" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for subtotal by VAT group Е</Desc></Res><Res Name="SubtotalAmountVAT6" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for subtotal by VAT group Ж</Desc></Res><Res Name="SubtotalAmountVAT7" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for subtotal by VAT group З</Desc></Res><Res Name="CurrentReceiptNumber" Value="" Type="Decimal_with_format" MaxLen="6" Format="000000"><Desc>6 symbols for fiscal receipt number in format ######</Desc></Res><ResFormatRaw><![CDATA[<IsReceiptOpened[1]> <;> <SalesNumber[3]> <;> <SubtotalAmountVAT0[1..13]> <;> <SubtotalAmountVAT1[1..13]> <;> <SubtotalAmountVAT2[1..13]> <;> <ForbiddenVoid[1]> <;> <VATinReceipt[1]> <;> <ReceiptFormat[1]> <;> <InitiatedPayment[1]> <;> <FinalizedPayment[1]> <;> <PowerDownInReceipt[1]> <;> <TypeReceipt[1]> <;> <ChangeAmount[1..13]> <;> <OptionChangeType[1]> <;> <SubtotalAmountVAT3[1..13]> <;> <SubtotalAmountVAT4[1..13]> <;> <SubtotalAmountVAT5[1..13]> <;> <SubtotalAmountVAT6[1..13]> <;> <SubtotalAmountVAT7[1..13]> <;> <CurrentReceiptNumber[6]>]]></ResFormatRaw></Response></Command><Command Name="OpenInvoiceWithFDCustomerDB" CmdByte="0x30"><FPOperation>Opens a fiscal invoice receipt assigned to the specified operator number and operator password with internal DB info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.</FPOperation><Args><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbol from 1 to 20 corresponding to operator\'s  number</Desc></Arg><Arg Name="OperPass" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for operator\'s password</Desc></Arg><Arg Name="reserved" Value="0" Type="OptionHardcoded" MaxLen="1" /><Arg Name="reserved" Value="0" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionInvoicePrintType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Buffered Printing" Value="5" /><Option Name="Postponed Printing" Value="3" /><Option Name="Step by step printing" Value="1" /></Options><Desc>1 symbol with value:  - \'1\' - Step by step printing  - \'3\' - Postponed Printing  - \'5\' - Buffered Printing</Desc></Arg><Arg Name="CustomerNum" Value="" Type="Text" MaxLen="5"><Desc>Symbol \'#\' and following up to 4 symbols for related customer ID number  corresponding to the FD database</Desc></Arg><Arg Name="UniqueReceiptNumber" Value="" Type="Text" MaxLen="24"><Desc>Up to 24 symbols for unique receipt number.  NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where:  * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number,  * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator,  * YYYYYYY - 7 symbols [0-9] for next number of the receipt</Desc><Meta MinLen="24" Compulsory="false" ValIndicatingPresence="$" /></Arg><ArgsFormatRaw><![CDATA[ <OperNum[1..2]> <;> <OperPass[6]> <;> <reserved[\'0\']> <;> <reserved[\'0\']> <;> <InvoicePrintType[1]> <;> <CustomerNum[5]> { <\'$\'> <UniqueReceiptNumber[24]> } ]]></ArgsFormatRaw></Args></Command><Command Name="ReadPLUallData" CmdByte="0x6B"><FPOperation>Provides information about all the registers of the specified article.</FPOperation><Args><Arg Name="PLUNum" Value="" Type="Decimal_with_format" MaxLen="5" Format="00000"><Desc>5 symbols for article number with leading zeroes in format: #####</Desc></Arg><Arg Name="Option" Value="&quot;" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Option[\'"\']> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="PLUNum" Value="" Type="Decimal_with_format" MaxLen="5" Format="00000"><Desc>5 symbols for article number with leading zeroes in format: #####</Desc></Res><Res Name="Option" Value="&quot;" Type="OptionHardcoded" MaxLen="1" /><Res Name="PLUName" Value="" Type="Text" MaxLen="34"><Desc>34 symbols for article name, new line=0x7C.</Desc></Res><Res Name="Price" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for article price</Desc></Res><Res Name="FlagsPricePLU" Value="" Type="Flags" MaxLen="1"><Desc>1 symbol for flags = 0x80 + FlagSinglTr + FlagQTY + OptionPrice  Where   OptionPrice:  0x00 - for free price is disable valid only programmed price  0x01 - for free price is enable  0x02 - for limited price  FlagQTY:  0x00 - for availability of PLU stock is not monitored  0x04 - for disable negative quantity  0x08 - for enable negative quantity  FlagSingleTr:  0x00 - no single transaction  0x10 - single transaction is active</Desc></Res><Res Name="OptionVATClass" Value="" Type="Option" MaxLen="1"><Options><Option Name="Forbidden" Value="*" /><Option Name="VAT Class 0" Value="А" /><Option Name="VAT Class 1" Value="Б" /><Option Name="VAT Class 2" Value="В" /><Option Name="VAT Class 3" Value="Г" /><Option Name="VAT Class 4" Value="Д" /><Option Name="VAT Class 5" Value="Е" /><Option Name="VAT Class 6" Value="Ж" /><Option Name="VAT Class 7" Value="З" /></Options><Desc>1 character for VAT class:   - \'А\' - VAT Class 0   - \'Б\' - VAT Class 1   - \'В\' - VAT Class 2   - \'Г\' - VAT Class 3   - \'Д\' - VAT Class 4   - \'Е\' - VAT Class 5   - \'Ж\' - VAT Class 6   - \'З\' - VAT Class 7   - \'*\' - Forbidden</Desc></Res><Res Name="BelongToDepNumber" Value="" Type="Decimal_plus_80h" MaxLen="2"><Desc>BelongToDepNumber + 80h, 1 symbol for PLU department  attachment = 0x80 … 0x93  Department range from 1 to 127</Desc></Res><Res Name="TurnoverAmount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for PLU accumulated turnover</Desc></Res><Res Name="SoldQuantity" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for Sales quantity of the article</Desc></Res><Res Name="StornoAmount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for accumulated storno amount</Desc></Res><Res Name="StornoQuantity" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for accumulated storno quantiy</Desc></Res><Res Name="LastZReportNumber" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for the number of the last article report with zeroing</Desc></Res><Res Name="LastZReportDate" Value="" Type="DateTime" MaxLen="10" Format="dd-MM-yyyy HH:mm"><Desc>16 symbols for the date and time of the last article report with zeroing  in format DD-MM-YYYY HH:MM</Desc></Res><Res Name="AvailableQuantity" Value="" Type="Decimal" MaxLen="11"><Desc>(Available Quantity) Up to 11 symbols for quantity in stock</Desc></Res><Res Name="Barcode" Value="" Type="Text" MaxLen="13"><Desc>13 symbols for article barcode</Desc></Res><ResFormatRaw><![CDATA[<PLUNum[5]> <;> <Option[\'"\']> <;> <PLUName[34]> <;> <Price[1..10]> <;> <FlagsPricePLU[1]> <;> <OptionVATClass[1]> <;> <BelongToDepNumber[1]> <;> <TurnoverAmount[1..13]> <;> <SoldQuantity[1..13]> <;> <StornoAmount[1..13]> <;> <StornoQuantity[1..13]> <;> <LastZReportNumber[1..5]> <;> <LastZReportDate "DD-MM-YYYY HH:MM"> <;> <AvailableQuantity[1..11]> <;> <Barcode[13]>]]></ResFormatRaw></Response></Command><Command Name="PrintDetailedFMDepartmentsReportByZBlocks" CmdByte="0x78"><FPOperation>Print a detailed FM Departments report by initial and end Z report number.</FPOperation><Args><Arg Name="StartZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for initial FM report number included in report, format ####</Desc></Arg><Arg Name="EndZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for final FM report number included in report, format ####</Desc></Arg><Arg Name="Option" Value="D" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <Option[\'D\']> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadEJ" CmdByte="0x7C"><FPOperation>Read Electronic Journal report with all documents.</FPOperation><Args><Arg Name="OptionReportFormat" Value="" Type="Option" MaxLen="2"><Options><Option Name="Brief EJ" Value="J8" /><Option Name="Detailed EJ" Value="J0" /></Options><Desc>1 character with value   - \'J0\' - Detailed EJ   - \'J8\' - Brief EJ</Desc></Arg><Arg Name="" Value="*" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <ReportFormat[2]> <;> <\'*\'> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="Payment" CmdByte="0x35"><FPOperation>Register the payment in the receipt with specified type of payment with amount received.</FPOperation><Args><Arg Name="OptionPaymentType" Value="" Type="Option" MaxLen="2"><Options><Option Name="Payment 0" Value="0" /><Option Name="Payment 1" Value="1" /><Option Name="Payment 10" Value="10" /><Option Name="Payment 11" Value="11" /><Option Name="Payment 2" Value="2" /><Option Name="Payment 3" Value="3" /><Option Name="Payment 4" Value="4" /><Option Name="Payment 5" Value="5" /><Option Name="Payment 6" Value="6" /><Option Name="Payment 7" Value="7" /><Option Name="Payment 8" Value="8" /><Option Name="Payment 9" Value="9" /></Options><Desc>1 symbol for payment type:   - \'0\' - Payment 0   - \'1\' - Payment 1   - \'2\' - Payment 2   - \'3\' - Payment 3   - \'4\' - Payment 4   - \'5\' - Payment 5   - \'6\' - Payment 6   - \'7\' - Payment 7   - \'8\' - Payment 8   - \'9\' - Payment 9   - \'10\' - Payment 10   - \'11\' - Payment 11</Desc></Arg><Arg Name="OptionChange" Value="" Type="Option" MaxLen="1"><Options><Option Name="With Change" Value="0" /><Option Name="Without Change" Value="1" /></Options><Desc>Default value is 0, 1 symbol with value:   - \'0 - With Change   - \'1\' - Without Change</Desc></Arg><Arg Name="Amount" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 characters for received amount</Desc></Arg><Arg Name="OptionChangeType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Change In Cash" Value="0" /><Option Name="Change In Currency" Value="2" /><Option Name="Same As The payment" Value="1" /></Options><Desc>1 symbols with value:   - \'0\' - Change In Cash   - \'1\' - Same As The payment   - \'2\' - Change In Currency</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence=";" /></Arg><ArgsFormatRaw><![CDATA[ <PaymentType[1..2]> <;> <OptionChange[1]> <;> <Amount[1..10]> { <;> <OptionChangeType[1]> } ]]></ArgsFormatRaw></Args></Command><Command Name="SetDeviceTCP_Addresses" CmdByte="0x4E"><FPOperation>Program device\'s network IP address, subnet mask, gateway address, DNS address. To apply use -SaveNetworkSettings()</FPOperation><Args><Arg Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="T" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionAddressType" Value="" Type="Option" MaxLen="1"><Options><Option Name="DNS address" Value="5" /><Option Name="Gateway address" Value="4" /><Option Name="IP address" Value="2" /><Option Name="Subnet Mask" Value="3" /></Options><Desc>1 symbol with value:   - \'2\' - IP address   - \'3\' - Subnet Mask   - \'4\' - Gateway address   - \'5\' - DNS address</Desc></Arg><Arg Name="DeviceAddress" Value="" Type="Text" MaxLen="15"><Desc>15 symbols for the selected address</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'P\'><;><\'T\'><;><AddressType[1]> <;><DeviceAddress[15]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadLastDailyReportInfo" CmdByte="0x73"><FPOperation>Read date and number of last Z-report and last RAM reset event.</FPOperation><Response ACK="false"><Res Name="LastZDailyReportDate" Value="" Type="DateTime" MaxLen="10" Format="dd-MM-yyyy"><Desc>10 symbols for last Z-report date in DD-MM-YYYY format</Desc></Res><Res Name="LastZDailyReportNum" Value="" Type="Decimal" MaxLen="4"><Desc>Up to 4 symbols for the number of the last daily report</Desc></Res><Res Name="LastRAMResetNum" Value="" Type="Decimal" MaxLen="4"><Desc>Up to 4 symbols for the number of the last RAM reset</Desc></Res><Res Name="TotalReceiptCounter" Value="" Type="Decimal_with_format" MaxLen="6" Format="000000"><Desc>6 symbols for the total number of receipts in format ######</Desc></Res><Res Name="DateTimeLastFiscRec" Value="" Type="DateTime" MaxLen="10" Format="dd-MM-yyyy HH:mm"><Desc>Date Time parameter in format: DD-MM-YYYY HH:MM</Desc></Res><Res Name="EJNum" Value="" Type="Text" MaxLen="2"><Desc>Up to 2 symbols for number of EJ</Desc></Res><Res Name="OptionLastReceiptType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Invoice Credit note" Value="5" /><Option Name="Invoice sales receipt" Value="1" /><Option Name="Non fiscal receipt" Value="2" /><Option Name="Sales receipt printing" Value="0" /><Option Name="Storno receipt" Value="4" /></Options><Desc>(Receipt and Printing type) 1 symbol with value:   - \'0\' - Sales receipt printing   - \'2\' - Non fiscal receipt    - \'4\' - Storno receipt   - \'1\' - Invoice sales receipt   - \'5\' - Invoice Credit note</Desc></Res><ResFormatRaw><![CDATA[<LastZDailyReportDate "DD-MM-YYYY"> <;> <LastZDailyReportNum[1..4]> <;> <LastRAMResetNum[1..4]> <;> <TotalReceiptCounter[6]> <;> <DateTimeLastFiscRec "DD-MM-YYYY HH:MM"> <;> <EJNum[2]> <;> <LastReceiptType[1]>]]></ResFormatRaw></Response></Command><Command Name="PrintText" CmdByte="0x37"><FPOperation>Print a free text. The command can be executed only if receipt is opened (Fiscal receipt, Invoice receipt, Storno receipt, Credit Note or Non-fical receipt). In the beginning and in the end of line symbol \'#\' is printed.</FPOperation><Args><Arg Name="Text" Value="" Type="Text" MaxLen="64"><Desc>TextLength-2 symbols</Desc></Arg><ArgsFormatRaw><![CDATA[ <Text[TextLength-2]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadPaymentsPositions" CmdByte="0x64"><FPOperation>Provides information about arrangement of payment positions according to NRA list: 0-Cash, 1-Check, 2-Talon, 3-V.Talon, 4-Packaging, 5-Service, 6- Damage, 7-Card, 8-Bank, 9-Programming Name 1, 10-Programming Name 2, 11-Currency.</FPOperation><Args><Arg Name="Option" Value="*" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <Option[\'*\']>  ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="Option" Value="*" Type="OptionHardcoded" MaxLen="1" /><Res Name="PaymentPosition0" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 0 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Res><Res Name="PaymentPosition1" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 1 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Res><Res Name="PaymentPosition2" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 2 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Res><Res Name="PaymentPosition3" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 3 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Res><Res Name="PaymentPosition4" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 4 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Res><Res Name="PaymentPosition5" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 5 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Res><Res Name="PaymentPosition6" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 6 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Res><Res Name="PaymentPosition7" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 7 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Res><Res Name="PaymentPosition8" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 8 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Res><Res Name="PaymentPosition9" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 9 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Res><Res Name="PaymentPosition10" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 10 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Res><Res Name="PaymentPosition11" Value="" Type="Decimal_with_format" MaxLen="2" Format="00."><Desc>2 digits for payment position 11 in format ##.   Values from \'1\' to \'11\' according to NRA payments list.</Desc></Res><ResFormatRaw><![CDATA[<Option[\'*\']> <;> <PaymentPosition0[2]> <;> <PaymentPosition1[2]> <;> <PaymentPosition2[2]> <;> <PaymentPosition3[2]> <;> <PaymentPosition4[2]> <;> <PaymentPosition5[2]> <;> <PaymentPosition6[2]> <;> <PaymentPosition7[2]> <;> <PaymentPosition8[2]> <;> <PaymentPosition9[2]> <;> <PaymentPosition10[2]> <;> <PaymentPosition11[2]>]]></ResFormatRaw></Response></Command><Command Name="OpenCreditNoteWithFDCustomerDB" CmdByte="0x30"><FPOperation>Opens a fiscal invoice credit note receipt assigned to the specified operator number and operator password with internal DB info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.</FPOperation><Args><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbol from 1 to 20 corresponding to operator\'s  number</Desc></Arg><Arg Name="OperPass" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for operator\'s password</Desc></Arg><Arg Name="reserved" Value="0" Type="OptionHardcoded" MaxLen="1" /><Arg Name="reserved" Value="0" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionInvoiceCreditNotePrintType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Buffered Printing" Value="E" /><Option Name="Postponed Printing" Value="C" /><Option Name="Step by step printing" Value="A" /></Options><Desc>1 symbol with value:  - \'A\' - Step by step printing  - \'C\' - Postponed Printing  - \'E\' - Buffered Printing</Desc></Arg><Arg Name="CustomerNum" Value="" Type="Text" MaxLen="5"><Desc>Symbol \'#\' and following up to 4 symbols for related customer ID  number corresponding to the FD database</Desc></Arg><Arg Name="OptionStornoReason" Value="" Type="Option" MaxLen="1"><Options><Option Name="Goods Claim or Goods return" Value="1" /><Option Name="Operator error" Value="0" /><Option Name="Tax relief" Value="2" /></Options><Desc>1 symbol for reason of storno operation with value:   - \'0\' - Operator error   - \'1\' - Goods Claim or Goods return   - \'2\' - Tax relief</Desc></Arg><Arg Name="RelatedToInvoiceNum" Value="" Type="Text" MaxLen="10"><Desc>10 symbols for issued invoice number</Desc></Arg><Arg Name="RelatedToInvoiceDateTime" Value="" Type="DateTime" MaxLen="10" Format="dd-MM-yy HH:mm:ss"><Desc>17 symbols for issued invoice date and time in format</Desc></Arg><Arg Name="RelatedToRcpNum" Value="" Type="Decimal" MaxLen="6"><Desc>Up to 6 symbols for issued receipt number</Desc></Arg><Arg Name="FMNum" Value="" Type="Text" MaxLen="8"><Desc>8 symbols for number of the Fiscal Memory</Desc></Arg><Arg Name="RelatedToURN" Value="" Type="Text" MaxLen="24"><Desc>Up to 24 symbols for the issed invoice receipt unique receipt number.  NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where:  * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number,  * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator,  * YYYYYYY - 7 symbols [0-9] for next number of the receipt</Desc><Meta MinLen="24" Compulsory="false" ValIndicatingPresence=";" /></Arg><ArgsFormatRaw><![CDATA[ <OperNum[1..2]> <;> <OperPass[6]> <;> <reserved[\'0\']> <;> <reserved[\'0\']> <;> <InvoiceCreditNotePrintType[1]> <;> <CustomerNum[5]> <;> <StornoReason[1]> <;> <RelatedToInvoiceNum[10]> <;> <RelatedToInvoiceDateTime "DD-MM-YY HH:MM:SS"> <;> <RelatedToRcpNum[1..6]> <;> <FMNum[8]> { <;> <RelatedToURN[24]> } ]]></ArgsFormatRaw></Args></Command><Command Name="ProgramDailyReportParameter" CmdByte="0x4F"><FPOperation>Program automatic daily report printing or not printing parameter.</FPOperation><Args><Arg Name="" Value="H" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="W" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionDailyReport" Value="" Type="Option" MaxLen="1"><Options><Option Name="Generate automatic Z report" Value="0" /><Option Name="Print automatic Z report" Value="1" /></Options><Desc>1 symbol with value:   - \'1\' - Print automatic Z report   - \'0\' - Generate automatic Z report</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'H\'> <;> <\'W\'> <;> <OptionDailyReport[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="PrintOperatorReport" CmdByte="0x7D"><FPOperation>Prints an operator\'s report for a specified operator (0 = all operators) with or without zeroing (\'Z\' or \'X\'). When a \'Z\' value is specified the report should include all operators.</FPOperation><Args><Arg Name="OptionZeroing" Value="" Type="Option" MaxLen="1"><Options><Option Name="Without zeroing" Value="X" /><Option Name="Zeroing" Value="Z" /></Options><Desc>with following values:   - \'Z\' - Zeroing   - \'X\' - Without zeroing</Desc></Arg><Arg Name="Number" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 0 to 20 corresponding to operator\'s number  ,0 for all operators</Desc></Arg><ArgsFormatRaw><![CDATA[ <OptionZeroing[1]> <;> <Number[1..2]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadStatus" CmdByte="0x20"><FPOperation>Provides detailed 7-byte information about the current status of the fiscal printer.</FPOperation><Response ACK="false"><Res Name="FM_Read_only" Value="" Type="Status" Byte="0" Bit="0"><Desc>FM Read only</Desc></Res><Res Name="Power_down_in_opened_fiscal_receipt" Value="" Type="Status" Byte="0" Bit="1"><Desc>Power down in opened fiscal receipt</Desc></Res><Res Name="Printer_not_ready_overheat" Value="" Type="Status" Byte="0" Bit="2"><Desc>Printer not ready - overheat</Desc></Res><Res Name="DateTime_not_set" Value="" Type="Status" Byte="0" Bit="3"><Desc>DateTime not set</Desc></Res><Res Name="DateTime_wrong" Value="" Type="Status" Byte="0" Bit="4"><Desc>DateTime wrong</Desc></Res><Res Name="RAM_reset" Value="" Type="Status" Byte="0" Bit="5"><Desc>RAM reset</Desc></Res><Res Name="Hardware_clock_error" Value="" Type="Status" Byte="0" Bit="6"><Desc>Hardware clock error</Desc></Res><Res Name="Printer_not_ready_no_paper" Value="" Type="Status" Byte="1" Bit="0"><Desc>Printer not ready - no paper</Desc></Res><Res Name="Reports_registers_Overflow" Value="" Type="Status" Byte="1" Bit="1"><Desc>Reports registers Overflow</Desc></Res><Res Name="Customer_report_is_not_zeroed" Value="" Type="Status" Byte="1" Bit="2"><Desc>Customer report is not zeroed</Desc></Res><Res Name="Daily_report_is_not_zeroed" Value="" Type="Status" Byte="1" Bit="3"><Desc>Daily report is not zeroed</Desc></Res><Res Name="Article_report_is_not_zeroed" Value="" Type="Status" Byte="1" Bit="4"><Desc>Article report is not zeroed</Desc></Res><Res Name="Operator_report_is_not_zeroed" Value="" Type="Status" Byte="1" Bit="5"><Desc>Operator report is not zeroed</Desc></Res><Res Name="Non_printed_copy" Value="" Type="Status" Byte="1" Bit="6"><Desc>Non-printed copy</Desc></Res><Res Name="Opened_Non_fiscal_Receipt" Value="" Type="Status" Byte="2" Bit="0"><Desc>Opened Non-fiscal Receipt</Desc></Res><Res Name="Opened_Fiscal_Receipt" Value="" Type="Status" Byte="2" Bit="1"><Desc>Opened Fiscal Receipt</Desc></Res><Res Name="Opened_Fiscal_Detailed_Receipt" Value="" Type="Status" Byte="2" Bit="2"><Desc>Opened Fiscal Detailed Receipt</Desc></Res><Res Name="Opened_Fiscal_Receipt_with_VAT" Value="" Type="Status" Byte="2" Bit="3"><Desc>Opened Fiscal Receipt with VAT</Desc></Res><Res Name="Opened_Invoice_Fiscal_Receipt" Value="" Type="Status" Byte="2" Bit="4"><Desc>Opened Invoice Fiscal Receipt</Desc></Res><Res Name="SD_card_near_full" Value="" Type="Status" Byte="2" Bit="5"><Desc>SD card near full</Desc></Res><Res Name="SD_card_full" Value="" Type="Status" Byte="2" Bit="6"><Desc>SD card full</Desc></Res><Res Name="No_FM_module" Value="" Type="Status" Byte="3" Bit="0"><Desc>No FM module</Desc></Res><Res Name="FM_error" Value="" Type="Status" Byte="3" Bit="1"><Desc>FM error</Desc></Res><Res Name="FM_full" Value="" Type="Status" Byte="3" Bit="2"><Desc>FM full</Desc></Res><Res Name="FM_near_full" Value="" Type="Status" Byte="3" Bit="3"><Desc>FM near full</Desc></Res><Res Name="Decimal_point" Value="" Type="Status" Byte="3" Bit="4"><Desc>Decimal point (1=fract, 0=whole)</Desc></Res><Res Name="FM_fiscalized" Value="" Type="Status" Byte="3" Bit="5"><Desc>FM fiscalized</Desc></Res><Res Name="FM_produced" Value="" Type="Status" Byte="3" Bit="6"><Desc>FM produced</Desc></Res><Res Name="Printer_automatic_cutting" Value="" Type="Status" Byte="4" Bit="0"><Desc>Printer: automatic cutting</Desc></Res><Res Name="External_display_transparent_display" Value="" Type="Status" Byte="4" Bit="1"><Desc>External display: transparent display</Desc></Res><Res Name="Speed_is_9600" Value="" Type="Status" Byte="4" Bit="2"><Desc>Speed is 9600</Desc></Res><Res Name="Drawer_automatic_opening" Value="" Type="Status" Byte="4" Bit="4"><Desc>Drawer: automatic opening</Desc></Res><Res Name="Customer_logo_included_in_the_receipt" Value="" Type="Status" Byte="4" Bit="5"><Desc>Customer logo included in the receipt</Desc></Res><Res Name="Wrong_SIM_card" Value="" Type="Status" Byte="5" Bit="0"><Desc>Wrong SIM card</Desc></Res><Res Name="Blocking_3_days_without_mobile_operator" Value="" Type="Status" Byte="5" Bit="1"><Desc>Blocking 3 days without mobile operator</Desc></Res><Res Name="No_task_from_NRA" Value="" Type="Status" Byte="5" Bit="2"><Desc>No task from NRA</Desc></Res><Res Name="Wrong_SD_card" Value="" Type="Status" Byte="5" Bit="5"><Desc>Wrong SD card</Desc></Res><Res Name="Deregistered" Value="" Type="Status" Byte="5" Bit="6"><Desc>Deregistered</Desc></Res><Res Name="No_SIM_card" Value="" Type="Status" Byte="6" Bit="0"><Desc>No SIM card</Desc></Res><Res Name="No_GPRS_Modem" Value="" Type="Status" Byte="6" Bit="1"><Desc>No GPRS Modem</Desc></Res><Res Name="No_mobile_operator" Value="" Type="Status" Byte="6" Bit="2"><Desc>No mobile operator</Desc></Res><Res Name="No_GPRS_service" Value="" Type="Status" Byte="6" Bit="3"><Desc>No GPRS service</Desc></Res><Res Name="Near_end_of_paper" Value="" Type="Status" Byte="6" Bit="4"><Desc>Near end of paper</Desc></Res><Res Name="Unsent_data_for_24_hours" Value="" Type="Status" Byte="6" Bit="5"><Desc>Unsent data for 24 hours</Desc></Res><ResFormatRaw><![CDATA[<StatusBytes[7]>]]></ResFormatRaw></Response></Command><Command Name="OpenReceipt" CmdByte="0x30"><FPOperation>Opens a fiscal receipt assigned to the specified operator number and operator password, parameters for receipt format, print VAT, printing type and unique receipt number.</FPOperation><Args><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s number</Desc></Arg><Arg Name="OperPass" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for operator\'s password</Desc></Arg><Arg Name="OptionReceiptFormat" Value="" Type="Option" MaxLen="1"><Options><Option Name="Brief" Value="0" /><Option Name="Detailed" Value="1" /></Options><Desc>1 symbol with value:   - \'1\' - Detailed   - \'0\' - Brief</Desc></Arg><Arg Name="OptionPrintVAT" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol with value:    - \'1\' - Yes   - \'0\' - No</Desc></Arg><Arg Name="OptionFiscalRcpPrintType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Buffered printing" Value="4" /><Option Name="Postponed printing" Value="2" /><Option Name="Step by step printing" Value="0" /></Options><Desc>1 symbol with value:  - \'0\' - Step by step printing  - \'2\' - Postponed printing  - \'4\' - Buffered printing</Desc></Arg><Arg Name="UniqueReceiptNumber" Value="" Type="Text" MaxLen="24"><Desc>Up to 24 symbols for unique receipt number.  NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where:  * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number,  * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator,  * YYYYYYY - 7 symbols [0-9] for next number of the receipt</Desc><Meta MinLen="24" Compulsory="false" ValIndicatingPresence="$" /></Arg><ArgsFormatRaw><![CDATA[<OperNum[1..2]> <;> <OperPass[6]> <;> <ReceiptFormat[1]> <;> <PrintVAT[1]> <;> <FiscalRcpPrintType[1]> {<\'$\'> <UniqueReceiptNumber[24]>} ]]></ArgsFormatRaw></Args></Command><Command Name="ReadEJByZBlocksCustom" CmdByte="0x7C"><FPOperation>Read or Store Electronic Journal Report by number of Z report blocks, CSV format option and document content. If CSV format is set the content can includes only fiscal receipts. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.</FPOperation><Args><Arg Name="OptionStorageReport" Value="" Type="Option" MaxLen="2"><Options><Option Name="To PC" Value="j0" /><Option Name="To SD card" Value="j4" /><Option Name="To USB Flash Drive" Value="j2" /></Options><Desc>1 character with value   - \'j0\' - To PC   - \'j2\' - To USB Flash Drive   - \'j4\' - To SD card</Desc></Arg><Arg Name="OptionCSVformat" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="X" /><Option Name="Yes" Value="C" /></Options><Desc>1 symbol with value:   - \'C\' - Yes   - \'X\' - No</Desc></Arg><Arg Name="FlagsReceipts" Value="" Type="Flags" MaxLen="1"><Desc>1 symbol for Receipts included in EJ:  Flags.7=0  Flags.6=1  Flags.5=1 Yes, Flags.5=0 No (Include PO)  Flags.4=1 Yes, Flags.4=0 No (Include RA)  Flags.3=1 Yes, Flags.3=0 No (Include Credit Note)  Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp)  Flags.1=1 Yes, Flags.1=0 No (Include Invoice)  Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)</Desc></Arg><Arg Name="FlagsReports" Value="" Type="Flags" MaxLen="1"><Desc>1 symbol for Reports included in EJ:  Flags.7=0  Flags.6=1  Flags.5=0  Flags.4=1 Yes, Flags.4=0 No (Include FM reports)  Flags.3=1 Yes, Flags.3=0 No (Include Other reports)  Flags.2=1 Yes, Flags.2=0 No (Include Daily X)  Flags.1=1 Yes, Flags.1=0 No (Include Daily Z)  Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)</Desc></Arg><Arg Name="" Value="Z" Type="OptionHardcoded" MaxLen="1" /><Arg Name="StartZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for initial number report in format ####</Desc></Arg><Arg Name="EndZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for final number report in format ####</Desc></Arg><ArgsFormatRaw><![CDATA[ <StorageReport[2]> <;> <CSVformat[1]> <;> <FlagsReceipts[1]> <;> <FlagsReports[1]> <;> <\'Z\'> <;> <StartZNum[4]> <;> <EndZNum[4]> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="SetDateTime" CmdByte="0x48"><FPOperation>Sets the date and time and prints out the current values.</FPOperation><Args><Arg Name="DateTime" Value="" Type="DateTime" MaxLen="10" Format="dd-MM-yy HH:mm:ss"><Desc>Date Time parameter in format: DD-MM-YY HH:MM:SS</Desc></Arg><ArgsFormatRaw><![CDATA[ <DateTime "DD-MM-YY HH:MM:SS"> ]]></ArgsFormatRaw></Args></Command><Command Name="ProgDecimalPointPosition" CmdByte="0x43"><FPOperation>Stores a block containing the number format into the fiscal memory. Print the current status on the printer.</FPOperation><Args><Arg Name="Password" Value="" Type="Text" MaxLen="6"><Desc>6-symbols string</Desc></Arg><Arg Name="OptionDecimalPointPosition" Value="" Type="Option" MaxLen="1"><Options><Option Name="Fractions" Value="2" /><Option Name="Whole numbers" Value="0" /></Options><Desc>1 symbol with values:   - \'0\'- Whole numbers   - \'2\' - Fractions</Desc></Arg><ArgsFormatRaw><![CDATA[ <Password[6]> <;> <DecimalPointPosition[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadLastDailySignature" CmdByte="0x6E"><FPOperation>Provides information about electronic signature of last daily report.</FPOperation><Args><Arg Name="" Value="9" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'9\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="9" Type="OptionHardcoded" MaxLen="1" /><Res Name="LastDailyReportSignature" Value="" Type="Text" MaxLen="40"><Desc>40 symbols electronic signature</Desc></Res><ResFormatRaw><![CDATA[<\'9\'> <;> <LastDailyReportSignature[40]>]]></ResFormatRaw></Response></Command><Command Name="ReadDailyRA_Old" CmdByte="0x6E"><FPOperation>Provides information about the RA amounts by type of payment and the total number of operations. Command works for KL version 2 devices.</FPOperation><Args><Arg Name="" Value="2" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'2\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="2" Type="OptionHardcoded" MaxLen="1" /><Res Name="AmountPayment0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name="AmountPayment1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name="AmountPayment2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name="AmountPayment3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name="AmountPayment4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><Res Name="RANum" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for the total number of operations</Desc></Res><Res Name="SumAllPayment" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols to sum all payments</Desc></Res><ResFormatRaw><![CDATA[<\'2\'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]> <;> <RANum[1..5]> <;> <SumAllPayment[1..13]>]]></ResFormatRaw></Response></Command><Command Name="ReadHeaderUICPrefix" CmdByte="0x69"><FPOperation>Provides the content of the header UIC prefix.</FPOperation><Args><Arg Name="" Value="9" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'9\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="9" Type="OptionHardcoded" MaxLen="1" /><Res Name="HeaderUICprefix" Value="" Type="Text" MaxLen="12"><Desc>12 symbols for Header UIC prefix</Desc></Res><ResFormatRaw><![CDATA[<\'9\'> <;> <HeaderUICprefix[12]>]]></ResFormatRaw></Response></Command><Command Name="ReadPLUprice" CmdByte="0x6B"><FPOperation>Provides information about the price and price type of the specified article.</FPOperation><Args><Arg Name="PLUNum" Value="" Type="Decimal_with_format" MaxLen="5" Format="00000"><Desc>5 symbols for article number with leading zeroes in format: #####</Desc></Arg><Arg Name="Option" Value="4" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Option[\'4\']> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="PLUNum" Value="" Type="Decimal_with_format" MaxLen="5" Format="00000"><Desc>5 symbols for article number with leading zeroes in format #####</Desc></Res><Res Name="Option" Value="4" Type="OptionHardcoded" MaxLen="1" /><Res Name="Price" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for article price</Desc></Res><Res Name="OptionPrice" Value="" Type="Option" MaxLen="1"><Options><Option Name="Free price is disable valid only programmed price" Value="0" /><Option Name="Free price is enable" Value="1" /><Option Name="Limited price" Value="2" /></Options><Desc>1 symbol for price flag with next value:   - \'0\'- Free price is disable valid only programmed price   - \'1\'- Free price is enable   - \'2\'- Limited price</Desc></Res><ResFormatRaw><![CDATA[<PLUNum[5]> <;> <Option[\'4\']> <;> <Price[1..10]> <;> <OptionPrice[1]>]]></ResFormatRaw></Response></Command><Command Name="ReadOperatorNamePassword" CmdByte="0x6A"><FPOperation>Provides information about operator\'s name and password.</FPOperation><Args><Arg Name="Number" Value="" Type="Decimal" MaxLen="2"><Desc>Symbol from 1 to 20 corresponding to the number of  operators.</Desc></Arg><ArgsFormatRaw><![CDATA[ <Number[1..2]> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="Number" Value="" Type="Decimal" MaxLen="2"><Desc>Symbol from 1 to 20 corresponding to the number of operator</Desc></Res><Res Name="Name" Value="" Type="Text" MaxLen="20"><Desc>20 symbols for operator\'s name</Desc></Res><Res Name="Password" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for operator\'s password</Desc></Res><ResFormatRaw><![CDATA[<Number[1..2]> <;> <Name[20]> <;> <Password[6]>]]></ResFormatRaw></Response></Command><Command Name="ReadDailyCountersByOperator" CmdByte="0x6F"><FPOperation>Read the last operator\'s report number and date and time.</FPOperation><Args><Arg Name="" Value="5" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s  number</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'5\'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="5" Type="OptionHardcoded" MaxLen="1" /><Res Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s number</Desc></Res><Res Name="WorkOperatorsCounter" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for number of the work operators</Desc></Res><Res Name="LastOperatorReportDateTime" Value="" Type="DateTime" MaxLen="10" Format="dd-MM-yyyy HH:mm"><Desc>16 symbols for date and time of the last operator\'s report in  format DD-MM-YYYY HH:MM</Desc></Res><ResFormatRaw><![CDATA[<\'5\'> <;> <OperNum[1..2]> <;> <WorkOperatorsCounter[1..5]> <;> <LastOperatorReportDateTime "DD-MM-YYYY HH:MM">]]></ResFormatRaw></Response></Command><Command Name="ReadLastDailyReportAvailableAmounts" CmdByte="0x6E"><FPOperation>Provides information about daily available amounts in cash and currency, Z daily report type and Z daily report number</FPOperation><Args><Arg Name="" Value="Z" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'Z\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="Z" Type="OptionHardcoded" MaxLen="1" /><Res Name="OptionZReportType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Automatic" Value="1" /><Option Name="Manual" Value="0" /></Options><Desc>1 symbol with value:   - \'0\' - Manual   - \'1\' - Automatic  ZReportNum 4 symbols for Z report number in format ####</Desc></Res><Res Name="ZreportNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for Z report number in format ####</Desc></Res><Res Name="CashAvailableAmount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for available amounts in cash payment</Desc></Res><Res Name="CurrencyAvailableAmount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for available amounts in currency payment</Desc></Res><ResFormatRaw><![CDATA[<\'Z\'> <;> <ZReportType[1]> <;> <ZreportNum[4]> <;> <CashAvailableAmount[1..13]> <;> <CurrencyAvailableAmount[1..13]>]]></ResFormatRaw></Response></Command><Command Name="ReadPayments" CmdByte="0x64"><FPOperation>Provides information about all programmed types of payment, currency name and currency exchange rate.</FPOperation><Response ACK="false"><Res Name="NamePayment0" Value="" Type="Text" MaxLen="10"><Desc>10 symbols for payment name type 0</Desc></Res><Res Name="NamePayment1" Value="" Type="Text" MaxLen="10"><Desc>10 symbols for payment name type 1</Desc></Res><Res Name="NamePayment2" Value="" Type="Text" MaxLen="10"><Desc>10 symbols for payment name type 2</Desc></Res><Res Name="NamePayment3" Value="" Type="Text" MaxLen="10"><Desc>10 symbols for payment name type 3</Desc></Res><Res Name="NamePayment4" Value="" Type="Text" MaxLen="10"><Desc>10 symbols for payment name type 4</Desc></Res><Res Name="NamePayment5" Value="" Type="Text" MaxLen="10"><Desc>10 symbols for payment name type 5</Desc></Res><Res Name="NamePayment6" Value="" Type="Text" MaxLen="10"><Desc>10 symbols for payment name type 6</Desc></Res><Res Name="NamePayment7" Value="" Type="Text" MaxLen="10"><Desc>10 symbols for payment name type 7</Desc></Res><Res Name="NamePayment8" Value="" Type="Text" MaxLen="10"><Desc>10 symbols for payment name type 8</Desc></Res><Res Name="NamePayment9" Value="" Type="Text" MaxLen="10"><Desc>10 symbols for payment name type 9</Desc></Res><Res Name="NamePayment10" Value="" Type="Text" MaxLen="10"><Desc>10 symbols for payment name type 10</Desc></Res><Res Name="NamePayment11" Value="" Type="Text" MaxLen="10"><Desc>10 symbols for payment name type 11</Desc></Res><Res Name="ExchangeRate" Value="" Type="Decimal_with_format" MaxLen="10" Format="0000.00000"><Desc>Up to 10 symbols for exchange rate of payment type 11 in format: ####.#####</Desc></Res><ResFormatRaw><![CDATA[<NamePayment0[10]> <;> <NamePayment1[10]> <;> <NamePayment2[10]> <;> <NamePayment3[10]> <;> <NamePayment4[10]> <;> <NamePayment5[10]> <;> <NamePayment6[10]> <;> <NamePayment7[10]> <;> <NamePayment8[10]> <;> <NamePayment9[10]> <;> <NamePayment10[10]> <;> <NamePayment11[10]> <;> <ExchangeRate[1..10]>]]></ResFormatRaw></Response></Command><Command Name="SellPLUwithSpecifiedVATfromDep" CmdByte="0x31"><FPOperation>Register the sell (for correction use minus sign in the price field) of article with specified VAT. If department is present the relevant accumulations are perfomed in its registers.</FPOperation><Args><Arg Name="NamePLU" Value="" Type="Text" MaxLen="36"><Desc>36 symbols for article\'s name. 34 symbols are printed on paper.  Symbol 0x7C \'|\' is new line separator.</Desc></Arg><Arg Name="OptionVATClass" Value="" Type="Option" MaxLen="1"><Options><Option Name="Forbidden" Value="*" /><Option Name="VAT Class 0" Value="А" /><Option Name="VAT Class 1" Value="Б" /><Option Name="VAT Class 2" Value="В" /><Option Name="VAT Class 3" Value="Г" /><Option Name="VAT Class 4" Value="Д" /><Option Name="VAT Class 5" Value="Е" /><Option Name="VAT Class 6" Value="Ж" /><Option Name="VAT Class 7" Value="З" /></Options><Desc>1 character for VAT class:   - \'А\' - VAT Class 0   - \'Б\' - VAT Class 1   - \'В\' - VAT Class 2   - \'Г\' - VAT Class 3   - \'Д\' - VAT Class 4   - \'Е\' - VAT Class 5   - \'Ж\' - VAT Class 6   - \'З\' - VAT Class 7   - \'*\' - Forbidden</Desc></Arg><Arg Name="Price" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for article\'s price. Use minus sign \'-\' for correction</Desc></Arg><Arg Name="Quantity" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for quantity</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="*" /></Arg><Arg Name="DiscAddP" Value="" Type="Decimal" MaxLen="7"><Desc>Up to 7 symbols for percentage of discount/addition.  Use minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="," /></Arg><Arg Name="DiscAddV" Value="" Type="Decimal" MaxLen="8"><Desc>Up to 8 symbols for value of discount/addition.  Use minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence=":" /></Arg><Arg Name="DepNum" Value="" Type="Decimal_plus_80h" MaxLen="2"><Desc>1 symbol for article department  attachment, formed in the following manner; example: Dep01 = 81h,   Dep02 = 82h … Dep19 = 93h  Department range from 1 to 127</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="!" /></Arg><ArgsFormatRaw><![CDATA[ <NamePLU[36]> <;> <OptionVATClass[1]> <;> <Price[1..10]> {<\'*\'> <Quantity[1..10]>} {<\',\'> <DiscAddP[1..7]>} {<\':\'> <DiscAddV[1..8]>} {<\'!\'> <DepNum[1]>} ]]></ArgsFormatRaw></Args></Command><Command Name="ProgPayment" CmdByte="0x44"><FPOperation>Preprogram the name of the payment type.</FPOperation><Args><Arg Name="OptionPaymentNum" Value="" Type="Option" MaxLen="2"><Options><Option Name="Payment 10" Value="10" /><Option Name="Payment 11" Value="11" /><Option Name="Payment 9" Value="9" /></Options><Desc>1 symbol for payment type    - \'9\' - Payment 9   - \'10\' - Payment 10   - \'11\' - Payment 11</Desc></Arg><Arg Name="Name" Value="" Type="Text" MaxLen="10"><Desc>10 symbols for payment type name</Desc></Arg><Arg Name="Rate" Value="" Type="Decimal_with_format" MaxLen="10" Format="0000.00000"><Desc>Up to 10 symbols for exchange rate in format: ####.#####   of the 11th payment type, maximal value 0420.00000</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence=";" /></Arg><ArgsFormatRaw><![CDATA[ <PaymentNum[1..2]> <;> <Name[10]> { <;> <Rate[1..10]> } ]]></ArgsFormatRaw></Args></Command><Command Name="PrintDiagnostics" CmdByte="0x22"><FPOperation>Prints out a diagnostic receipt.</FPOperation></Command><Command Name="ReadDetailedPrinterStatus" CmdByte="0x66"><FPOperation>Provides additional status information</FPOperation><Response ACK="false"><Res Name="OptionExternalDisplay" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="N" /><Option Name="Yes" Value="Y" /></Options><Desc>1 symbol - connection with external display    - \'Y\' - Yes   - \'N\' - No</Desc></Res><Res Name="StatPRN" Value="" Type="Text" MaxLen="4"><Desc>4 symbols for detailed status of printer (only for printers with ASB)  N  byte  N bit status flag  ST0  0 Reserved  1 Reserved  2 Signal level for drawer  3 Printer not ready  4 Reserved  5 Open cover  6 Paper feed status  7 Reserved      ST1  0 Reserved  1 Reserved  2 Reserved  3 Cutter error  4 Reserved  5 Fatal error</Desc></Res><Res Name="FlagServiceJumper" Value="" Type="Flags" MaxLen="1"><Desc>1 symbol with value:   - \'J\' - Yes    - \' \' - No</Desc></Res><ResFormatRaw><![CDATA[<ExternalDisplay[1]> <;> <StatPRN[4]> <;> <FlagServiceJumper[1]>]]></ResFormatRaw></Response></Command><Command Name="OpenInvoiceWithFreeCustomerData" CmdByte="0x30"><FPOperation>Opens a fiscal invoice receipt assigned to the specified operator number and operator password with free info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.</FPOperation><Args><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbol from 1 to 20 corresponding to operator\'s number</Desc></Arg><Arg Name="OperPass" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for operator\'s password</Desc></Arg><Arg Name="reserved" Value="0" Type="OptionHardcoded" MaxLen="1" /><Arg Name="reserved" Value="0" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionInvoicePrintType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Buffered Printing" Value="5" /><Option Name="Postponed Printing" Value="3" /><Option Name="Step by step printing" Value="1" /></Options><Desc>1 symbol with value:  - \'1\' - Step by step printing  - \'3\' - Postponed Printing  - \'5\' - Buffered Printing</Desc></Arg><Arg Name="Recipient" Value="" Type="Text" MaxLen="26"><Desc>26 symbols for Invoice recipient</Desc></Arg><Arg Name="Buyer" Value="" Type="Text" MaxLen="16"><Desc>16 symbols for Invoice buyer</Desc></Arg><Arg Name="VATNumber" Value="" Type="Text" MaxLen="13"><Desc>13 symbols for customer Fiscal number</Desc></Arg><Arg Name="UIC" Value="" Type="Text" MaxLen="13"><Desc>13 symbols for customer Unique Identification Code</Desc></Arg><Arg Name="Address" Value="" Type="Text" MaxLen="30"><Desc>30 symbols for Address</Desc></Arg><Arg Name="OptionUICType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Bulstat" Value="0" /><Option Name="EGN" Value="1" /><Option Name="Foreigner Number" Value="2" /><Option Name="NRA Official Number" Value="3" /></Options><Desc>1 symbol for type of Unique Identification Code:    - \'0\' - Bulstat   - \'1\' - EGN   - \'2\' - Foreigner Number   - \'3\' - NRA Official Number</Desc></Arg><Arg Name="UniqueReceiptNumber" Value="" Type="Text" MaxLen="24"><Desc>Up to 24 symbols for unique receipt number.  NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where:  * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number,  * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator,  * YYYYYYY - 7 symbols [0-9] for next number of the receipt</Desc><Meta MinLen="24" Compulsory="false" ValIndicatingPresence="$" /></Arg><ArgsFormatRaw><![CDATA[ <OperNum[1..2]> <;> <OperPass[6]> <;> <reserved[\'0\']> <;> <reserved[\'0\']> <;> <InvoicePrintType[1]> <;> <Recipient[26]> <;> <Buyer[16]> <;> <VATNumber[13]> <;> <UIC[13]> <;> <Address[30]> <;> <UICType[1]> { <\'$\'> <UniqueReceiptNumber[24]>} ]]></ArgsFormatRaw></Args></Command><Command Name="ProgFooter" CmdByte="0x49"><FPOperation>Program the contents of a footer lines.</FPOperation><Args><Arg Name="" Value="8" Type="OptionHardcoded" MaxLen="1" /><Arg Name="FooterText" Value="" Type="Text" MaxLen="64"><Desc>TextLength symbols for footer line</Desc></Arg><ArgsFormatRaw><![CDATA[<\'8\'> <;> <FooterText[TextLength]> ]]></ArgsFormatRaw></Args></Command><Command Name="PrintLastReceiptDuplicate" CmdByte="0x3A"><FPOperation>Print a copy of the last receipt issued. When FD parameter for duplicates is enabled.</FPOperation></Command><Command Name="ReadTCP_Password" CmdByte="0x4E"><FPOperation>Provides information about device\'s TCP password.</FPOperation><Args><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="Z" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="1" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'R\'><;><\'Z\'><;><\'1\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="Z" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="1" Type="OptionHardcoded" MaxLen="1" /><Res Name="PassLength" Value="" Type="Decimal" MaxLen="3"><Desc>(Length) Up to 3 symbols for the password length</Desc></Res><Res Name="Password" Value="" Type="Text" MaxLen="100"><Desc>Up to 100 symbols for the TCP password</Desc></Res><ResFormatRaw><![CDATA[<\'R\'><;><\'Z\'><;><\'1\'><;><PassLength[1..3]><;><Password[100]>]]></ResFormatRaw></Response></Command><Command Name="ProgVATrates" CmdByte="0x42"><FPOperation>Stores a block containing the values of the VAT rates into the fiscal memory. Print the values on the printer.</FPOperation><Args><Arg Name="Password" Value="" Type="Text" MaxLen="6"><Desc>6-symbols string</Desc></Arg><Arg Name="VATrate0" Value="" Type="Decimal_with_format" MaxLen="6" Format="00.00"><Desc>Value of VAT rate А from 6 symbols in format ##.##</Desc></Arg><Arg Name="VATrate1" Value="" Type="Decimal_with_format" MaxLen="6" Format="00.00"><Desc>Value of VAT rate Б from 6 symbols in format ##.##</Desc></Arg><Arg Name="VATrate2" Value="" Type="Decimal_with_format" MaxLen="6" Format="00.00"><Desc>Value of VAT rate В from 6 symbols in format ##.##</Desc></Arg><Arg Name="VATrate3" Value="" Type="Decimal_with_format" MaxLen="6" Format="00.00"><Desc>Value of VAT rate Г from 6 symbols in format ##.##</Desc></Arg><Arg Name="VATrate4" Value="" Type="Decimal_with_format" MaxLen="6" Format="00.00"><Desc>Value of VAT rate Д from 6 symbols in format ##.##</Desc></Arg><Arg Name="VATrate5" Value="" Type="Decimal_with_format" MaxLen="6" Format="00.00"><Desc>Value of VAT rate Е from 6 symbols in format ##.##</Desc></Arg><Arg Name="VATrate6" Value="" Type="Decimal_with_format" MaxLen="6" Format="00.00"><Desc>Value of VAT rate Ж from 6 symbols in format ##.##</Desc></Arg><Arg Name="VATrate7" Value="" Type="Decimal_with_format" MaxLen="6" Format="00.00"><Desc>Value of VAT rate З from 6 symbols in format ##.##</Desc></Arg><ArgsFormatRaw><![CDATA[ <Password[6]> <;> <VATrate0[6]> <;> <VATrate1[6]> <;> <VATrate2[6]> <;> <VATrate3[6]> <;> <VATrate4[6]><;> <VATrate5[6]><;> <VATrate6[6]> <;> <VATrate7[6]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadEJByDateCustom" CmdByte="0x7C"><FPOperation>Read or Store Electronic Journal Report by initial to end date, CSV format option and document content. If CSV format is set the content can includes only fiscal receipts. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.</FPOperation><Args><Arg Name="OptionStorageReport" Value="" Type="Option" MaxLen="2"><Options><Option Name="To PC" Value="j0" /><Option Name="To SD card" Value="j4" /><Option Name="To USB Flash Drive" Value="j2" /></Options><Desc>2 characters with value:   - \'j0\' - To PC   - \'j2\' - To USB Flash Drive   - \'j4\' - To SD card</Desc></Arg><Arg Name="OptionCSVformat" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="X" /><Option Name="Yes" Value="C" /></Options><Desc>1 symbol with value:   - \'C\' - Yes   - \'X\' - No</Desc></Arg><Arg Name="FlagsReceipts" Value="" Type="Flags" MaxLen="1"><Desc>1 symbol for Receipts included in EJ:  Flags.7=0  Flags.6=1, 0=w  Flags.5=1 Yes, Flags.5=0 No (Include PO)  Flags.4=1 Yes, Flags.4=0 No (Include RA)  Flags.3=1 Yes, Flags.3=0 No (Include Credit Note)  Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp)  Flags.1=1 Yes, Flags.1=0 No (Include Invoice)  Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)</Desc></Arg><Arg Name="FlagsReports" Value="" Type="Flags" MaxLen="1"><Desc>1 symbol for Reports included in EJ:  Flags.7=0  Flags.6=1, 0=w  Flags.5=1, 0=w  Flags.4=1 Yes, Flags.4=0 No (Include FM reports)  Flags.3=1 Yes, Flags.3=0 No (Include Other reports)  Flags.2=1 Yes, Flags.2=0 No (Include Daily X)  Flags.1=1 Yes, Flags.1=0 No (Include Daily Z)  Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)</Desc></Arg><Arg Name="" Value="D" Type="OptionHardcoded" MaxLen="1" /><Arg Name="StartRepFromDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name="EndRepFromDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><ArgsFormatRaw><![CDATA[ <StorageReport[2]> <;> <CSVformat[1]> <;> <FlagsReceipts[1]> <;> <FlagsReports[1]> <;> <\'D\'> <;> <StartRepFromDate "DDMMYY"> <;>  <EndRepFromDate "DDMMYY"> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="SetBluetooth_Status" CmdByte="0x4E"><FPOperation>Program device\'s Bluetooth module to be enabled or disabled. To apply use -SaveNetworkSettings()</FPOperation><Args><Arg Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="B" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="S" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionBTstatus" Value="" Type="Option" MaxLen="1"><Options><Option Name="Disabled" Value="0" /><Option Name="Enabled" Value="1" /></Options><Desc>1 symbol with value:   - \'0\' - Disabled   - \'1\' - Enabled</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'P\'><;><\'B\'><;><\'S\'><;><BTstatus[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="SellPLUwithSpecifiedVATfor200DepRangeDevice" CmdByte="0x3C"><FPOperation>Register the sell (for correction use minus sign in the price field) of article with specified VAT. If department is present the relevant accumulations are perfomed in its registers.</FPOperation><Args><Arg Name="NamePLU" Value="" Type="Text" MaxLen="36"><Desc>36 symbols for article\'s name. 34 symbols are printed on paper.  Symbol 0x7C \'|\' is new line separator.</Desc></Arg><Arg Name="OptionVATClass" Value="" Type="Option" MaxLen="1"><Options><Option Name="Forbidden" Value="*" /><Option Name="VAT Class 0" Value="А" /><Option Name="VAT Class 1" Value="Б" /><Option Name="VAT Class 2" Value="В" /><Option Name="VAT Class 3" Value="Г" /><Option Name="VAT Class 4" Value="Д" /><Option Name="VAT Class 5" Value="Е" /><Option Name="VAT Class 6" Value="Ж" /><Option Name="VAT Class 7" Value="З" /></Options><Desc>1 character for VAT class:   - \'А\' - VAT Class 0   - \'Б\' - VAT Class 1   - \'В\' - VAT Class 2   - \'Г\' - VAT Class 3   - \'Д\' - VAT Class 4   - \'Е\' - VAT Class 5   - \'Ж\' - VAT Class 6   - \'З\' - VAT Class 7   - \'*\' - Forbidden</Desc></Arg><Arg Name="Price" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for article\'s price. Use minus sign \'-\' for correction</Desc></Arg><Arg Name="Quantity" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for quantity</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="*" /></Arg><Arg Name="DiscAddP" Value="" Type="Decimal" MaxLen="7"><Desc>Up to 7 symbols for percentage of discount/addition.  Use minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="," /></Arg><Arg Name="DiscAddV" Value="" Type="Decimal" MaxLen="8"><Desc>Up to 8 symbols for value of discount/addition.  Use minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence=":" /></Arg><Arg Name="DepNum" Value="" Type="Decimal" MaxLen="3"><Desc>Up to 3 symbols for department number</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="!" /></Arg><ArgsFormatRaw><![CDATA[ <NamePLU[36]> <;> <OptionVATClass[1]> <;> <Price[1..10]> {<\'*\'> <Quantity[1..10]>} {<\',\'> <DiscAddP[1..7]>} {<\':\'> <DiscAddV[1..8]>} {<\'!\'> <DepNum[1..3]>} ]]></ArgsFormatRaw></Args></Command><Command Name="PrintBriefFMReportByZBlocks" CmdByte="0x79"><FPOperation>Print a brief FM report by initial and end FM report number.</FPOperation><Args><Arg Name="StartZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for the initial FM report number included in report, format ####</Desc></Arg><Arg Name="EndZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for the final FM report number included in report, format ####</Desc></Arg><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> ]]></ArgsFormatRaw></Args></Command><Command Name="SetFiscalDeviceType" CmdByte="0x56"><FPOperation>Define Fiscal device type. The command is allowed only in non- fiscal mode, before fiscalization and after deregistration before the next fiscalization. The type of device can be read by Version command 0x21.</FPOperation><Args><Arg Name="" Value="T" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionFDType" Value="" Type="Option" MaxLen="1"><Options><Option Name="ECR for online store type 11" Value="2" /><Option Name="FPr for Fuel type 3" Value="0" /><Option Name="FPr for online store type 21" Value="3" /><Option Name="Main FPr for Fuel system type 31" Value="1" /><Option Name="reset default type" Value="*" /></Options><Desc>1 symbol for fiscal device type with value:   - \'0\' - FPr for Fuel type 3   - \'1\' - Main FPr for Fuel system type 31   - \'2\' - ECR for online store type 11   - \'3\' - FPr for online store type 21    - \'*\' - reset default type</Desc></Arg><Arg Name="Password" Value="" Type="Text" MaxLen="3"><Desc>3-symbols string</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'T\'> <;> <FDType[1]> <;> <Password[3]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadEJByDate" CmdByte="0x7C"><FPOperation>Read Electronic Journal Report by initial to end date.</FPOperation><Args><Arg Name="OptionReportFormat" Value="" Type="Option" MaxLen="2"><Options><Option Name="Brief EJ" Value="J8" /><Option Name="Detailed EJ" Value="J0" /></Options><Desc>1 character with value   - \'J0\' - Detailed EJ   - \'J8\' - Brief EJ</Desc></Arg><Arg Name="" Value="D" Type="OptionHardcoded" MaxLen="1" /><Arg Name="StartRepFromDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name="EndRepFromDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><ArgsFormatRaw><![CDATA[ <ReportFormat[2]> <;> <\'D\'> <;> <StartRepFromDate "DDMMYY"> <;> <EndRepFromDate "DDMMYY"> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="ReadPLUbarcode" CmdByte="0x6B"><FPOperation>Provides information about the barcode of the specified article.</FPOperation><Args><Arg Name="PLUNum" Value="" Type="Decimal_with_format" MaxLen="5" Format="00000"><Desc>5 symbols for article number with leading zeroes in format: #####</Desc></Arg><Arg Name="Option" Value="3" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Option[\'3\']> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="PLUNum" Value="" Type="Decimal_with_format" MaxLen="5" Format="00000"><Desc>5 symbols for article number with leading zeroes in format #####</Desc></Res><Res Name="Option" Value="3" Type="OptionHardcoded" MaxLen="1" /><Res Name="Barcode" Value="" Type="Text" MaxLen="13"><Desc>13 symbols for article barcode</Desc></Res><ResFormatRaw><![CDATA[<PLUNum[5]> <;> <Option[\'3\']> <;> <Barcode[13]>]]></ResFormatRaw></Response></Command><Command Name="ReadDailyPObyOperator_Old" CmdByte="0x6F"><FPOperation>Read the PO by type of payment and the total number of operations by specified operator. Command works for KL version 2 devices.</FPOperation><Args><Arg Name="" Value="3" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s number</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'3\'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="3" Type="OptionHardcoded" MaxLen="1" /><Res Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s number</Desc></Res><Res Name="AmountPO_Payment0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the PO by type of payment 0</Desc></Res><Res Name="AmountPO_Payment1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the PO by type of payment 1</Desc></Res><Res Name="AmountPO_Payment2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the PO by type of payment 2</Desc></Res><Res Name="AmountPO_Payment3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the PO by type of payment 3</Desc></Res><Res Name="AmountPO_Payment4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the PO by type of payment 4</Desc></Res><Res Name="NoPO" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for the total number of operations</Desc></Res><ResFormatRaw><![CDATA[<\'3\'> <;> <OperNum[1..2]> <;> <AmountPO_Payment0[1..13]> <;> <AmountPO_Payment1[1..13]> <;> <AmountPO_Payment2[1..13]> <;> <AmountPO_Payment3[1..13]> <;> <AmountPO_Payment4[1..13]> <;> <;><NoPO[1..5]>]]></ResFormatRaw></Response></Command><Command Name="ProgDepartment" CmdByte="0x47"><FPOperation>Set data for the state department number from the internal FD database. Parameters Price, OptionDepPrice and AdditionalName are not obligatory and require the previous not obligatory parameter.</FPOperation><Args><Arg Name="Number" Value="" Type="Decimal_with_format" MaxLen="3" Format="000"><Desc>3 symbols for department number in format ###</Desc></Arg><Arg Name="Name" Value="" Type="Text" MaxLen="20"><Desc>20 characters department name</Desc></Arg><Arg Name="OptionVATClass" Value="" Type="Option" MaxLen="1"><Options><Option Name="Forbidden" Value="*" /><Option Name="VAT Class 0" Value="А" /><Option Name="VAT Class 1" Value="Б" /><Option Name="VAT Class 2" Value="В" /><Option Name="VAT Class 3" Value="Г" /><Option Name="VAT Class 4" Value="Д" /><Option Name="VAT Class 5" Value="Е" /><Option Name="VAT Class 6" Value="Ж" /><Option Name="VAT Class 7" Value="З" /></Options><Desc>1 character for VAT class:   - \'А\' - VAT Class 0   - \'Б\' - VAT Class 1   - \'В\' - VAT Class 2   - \'Г\' - VAT Class 3   - \'Д\' - VAT Class 4   - \'Е\' - VAT Class 5   - \'Ж\' - VAT Class 6   - \'З\' - VAT Class 7   - \'*\' - Forbidden</Desc></Arg><Arg Name="Price" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for department price</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence=";" /></Arg><Arg Name="OptionDepPrice" Value="" Type="Option" MaxLen="1"><Options><Option Name="Free price disabled" Value="0" /><Option Name="Free price disabled for single transaction" Value="4" /><Option Name="Free price enabled" Value="1" /><Option Name="Free price enabled for single transaction" Value="5" /><Option Name="Limited price" Value="2" /><Option Name="Limited price for single transaction" Value="6" /></Options><Desc>1 symbol for Department price flags with next value:   - \'0\' - Free price disabled   - \'1\' - Free price enabled   - \'2\' - Limited price   - \'4\' - Free price disabled for single transaction   - \'5\' - Free price enabled for single transaction   - \'6\' - Limited price for single transaction</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence=";" /></Arg><Arg Name="AdditionalName" Value="" Type="Text" MaxLen="14"><Desc>14 characters additional department name</Desc><Meta MinLen="14" Compulsory="false" ValIndicatingPresence=";" /></Arg><ArgsFormatRaw><![CDATA[ <Number[3..3]> <;> <Name[20]> <;> <OptionVATClass[1]> { <;> <Price[1..10]> <;> <OptionDepPrice[1]> <;> <AdditionalName[14]> } ]]></ArgsFormatRaw></Args></Command><Command Name="ReadDetailedFMPaymentsReportByDate" CmdByte="0x7A"><FPOperation>Read a detailed FM payments report by initial and end date.</FPOperation><Args><Arg Name="StartDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name="EndDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name="Option" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionReading" Value="8" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartDate "DDMMYY"> <;> <EndDate "DDMMYY"> <;> <Option[\'P\']> <;> <OptionReading[\'8\']> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="SetIdle_Timeout" CmdByte="0x4E"><FPOperation>Sets device\'s idle timeout setting. Set timeout for closing the connection if there is an inactivity. Maximal value - 7200, minimal value 0. 0 is for never close the connection. This option can be used only if the device has LAN or WiFi. To apply use - SaveNetworkSettings()</FPOperation><Args><Arg Name="" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="Z" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="I" Type="OptionHardcoded" MaxLen="1" /><Arg Name="IdleTimeout" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for Idle timeout in format ####</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'P\'><;><\'Z\'><;><\'I\'><;><IdleTimeout[4]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadTCP_AutoStartStatus" CmdByte="0x4E"><FPOperation>Read device TCP Auto Start status</FPOperation><Args><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="Z" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="2" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'R\'><;><\'Z\'><;><\'2\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="Z" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="2" Type="OptionHardcoded" MaxLen="1" /><Res Name="OptionTCPAutoStart" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol for TCP auto start status  - \'0\' - No   - \'1\' - Yes</Desc></Res><ResFormatRaw><![CDATA[<\'R\'><;><\'Z\'><;><\'2\'><;><TCPAutoStart[1]>]]></ResFormatRaw></Response></Command><Command Name="PrintLogo" CmdByte="0x6C"><FPOperation>Prints the programmed graphical logo with the stated number.</FPOperation><Args><Arg Name="Number" Value="" Type="Decimal" MaxLen="2"><Desc>Number of logo to be printed. If missing, prints logo with number 0</Desc></Arg><ArgsFormatRaw><![CDATA[ <Number[1..2]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadDailyGeneralRegistersByOperator" CmdByte="0x6F"><FPOperation>Read the total number of customers, discounts, additions, corrections and accumulated amounts by specified operator.</FPOperation><Args><Arg Name="" Value="1" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s number</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'1\'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="1" Type="OptionHardcoded" MaxLen="1" /><Res Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s number</Desc></Res><Res Name="CustomersNum" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for number of customers</Desc></Res><Res Name="DiscountsNum" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for number of discounts</Desc></Res><Res Name="DiscountsAmount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for accumulated amount of discounts</Desc></Res><Res Name="AdditionsNum" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for number ofadditions</Desc></Res><Res Name="AdditionsAmount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for accumulated amount of additions</Desc></Res><Res Name="CorrectionsNum" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for number of corrections</Desc></Res><Res Name="CorrectionsAmount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for accumulated amount of corrections</Desc></Res><ResFormatRaw><![CDATA[<\'1\'> <;> <OperNum[1..2]> <;> <CustomersNum[1..5]> <;> <DiscountsNum[1..5]> <;> <DiscountsAmount[1..13]> <;> <AdditionsNum[1..5]> <;> <AdditionsAmount[1..13]> <;> <CorrectionsNum[1..5]> <;> <CorrectionsAmount[1..13]>]]></ResFormatRaw></Response></Command><Command Name="ReadBriefFMReportByZBlocks" CmdByte="0x79"><FPOperation>Read a brief FM report by initial and end FM report number.</FPOperation><Args><Arg Name="StartZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for the initial FM report number included in report, format ####</Desc></Arg><Arg Name="EndZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for the final FM report number included in report, format ####</Desc></Arg><Arg Name="" Value="0" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionReading" Value="8" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <\'0\'> <;> <OptionReading[\'8\']> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="ReadDailyRAbyOperator_Old" CmdByte="0x6F"><FPOperation>Read the RA by type of payment and the total number of operations by specified operator. Command works for KL version 2 devices.</FPOperation><Args><Arg Name="" Value="2" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s number</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'2\'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="2" Type="OptionHardcoded" MaxLen="1" /><Res Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s number</Desc></Res><Res Name="AmountRA_Payment0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the RA by type of payment 0</Desc></Res><Res Name="AmountRA_Payment1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the RA by type of payment 1</Desc></Res><Res Name="AmountRA_Payment2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the RA by type of payment 2</Desc></Res><Res Name="AmountRA_Payment3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the RA by type of payment 3</Desc></Res><Res Name="AmountRA_Payment4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the RA by type of payment 4</Desc></Res><Res Name="NoRA" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for the total number of operations</Desc></Res><ResFormatRaw><![CDATA[<\'2\'> <;> <OperNum[1..2]> <;> <AmountRA_Payment0[1..13]> <;> <AmountRA_Payment1[1..13]> <;> <AmountRA_Payment2[1..13]> <;> <AmountRA_Payment3[1..13]> <;> <AmountRA_Payment4[1..13]> <;> <;> <NoRA[1..5]>]]></ResFormatRaw></Response></Command><Command Name="PrintDetailedFMReportByZBlocks" CmdByte="0x78"><FPOperation>Print a detailed FM report by initial and end FM report number.</FPOperation><Args><Arg Name="StartZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for the initial report number included in report, format ####</Desc></Arg><Arg Name="EndZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for the final report number included in report, format ####</Desc></Arg><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> ]]></ArgsFormatRaw></Args></Command><Command Name="PrintCustomerReport" CmdByte="0x52"><FPOperation>Print Customer X or Z report</FPOperation><Args><Arg Name="OptionZeroing" Value="" Type="Option" MaxLen="1"><Options><Option Name="Without zeroing" Value="X" /><Option Name="Zeroing" Value="Z" /></Options><Desc>with following values:   - \'Z\' -Zeroing   - \'X\' - Without zeroing</Desc></Arg><ArgsFormatRaw><![CDATA[ <OptionZeroing[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="PrintDailyReport" CmdByte="0x7C"><FPOperation>Depending on the parameter prints:  − daily fiscal report with zeroing and fiscal memory record, preceded by Electronic Journal report print (\'Z\'); − daily fiscal report without zeroing (\'X\');</FPOperation><Args><Arg Name="OptionZeroing" Value="" Type="Option" MaxLen="1"><Options><Option Name="Without zeroing" Value="X" /><Option Name="Zeroing" Value="Z" /></Options><Desc>1 character with following values:   - \'Z\' - Zeroing   - \'X\' - Without zeroing</Desc></Arg><ArgsFormatRaw><![CDATA[ <OptionZeroing[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadFooter" CmdByte="0x69"><FPOperation>Provides the content of the footer line.</FPOperation><Args><Arg Name="" Value="8" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'8\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="8" Type="OptionHardcoded" MaxLen="1" /><Res Name="FooterText" Value="" Type="Text" MaxLen="64"><Desc>TextLength symbols for footer line</Desc></Res><ResFormatRaw><![CDATA[<\'8\'> <;> <FooterText[TextLength]>]]></ResFormatRaw></Response></Command><Command Name="ZDailyReportNoPrint" CmdByte="0x7C"><FPOperation>Generate Z-daily report without printing</FPOperation><Args><Arg Name="" Value="Z" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="n" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'Z\'><;><\'n\'> ]]></ArgsFormatRaw></Args></Command><Command Name="OpenNonFiscalReceipt" CmdByte="0x2E"><FPOperation>Opens a non-fiscal receipt assigned to the specified operator number, operator password and print type.</FPOperation><Args><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s  number</Desc></Arg><Arg Name="OperPass" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for operator\'s password</Desc></Arg><Arg Name="Reserved" Value="0" Type="OptionHardcoded" MaxLen="1"><Meta MinLen="1" Compulsory="false" ValIndicatingPresence=";" /></Arg><Arg Name="OptionNonFiscalPrintType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Postponed Printing" Value="1" /><Option Name="Step by step printing" Value="0" /></Options><Desc>1 symbol with value:  - \'0\' - Step by step printing  - \'1\' - Postponed Printing</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence=";" /></Arg><ArgsFormatRaw><![CDATA[ <OperNum[1..2]> <;> <OperPass[6]> {<;> <Reserved[\'0\']> <;> <NonFiscalPrintType[1]>} ]]></ArgsFormatRaw></Args></Command><Command Name="ReadBriefFMPaymentsReportByDate" CmdByte="0x7B"><FPOperation>Read a brief FM payments report by initial and end date.</FPOperation><Args><Arg Name="StartDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name="EndDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name="Option" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionReading" Value="8" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartDate "DDMMYY"> <;> <EndDate "DDMMYY"> <;> <Option[\'P\']> <;> <OptionReading[\'8\']> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="Subtotal" CmdByte="0x33"><FPOperation>Calculate the subtotal amount with printing and display visualization options. Provide information about values of the calculated amounts. If a percent or value discount/addition has been specified the subtotal and the discount/addition value will be printed regardless the parameter for printing.</FPOperation><Args><Arg Name="OptionPrinting" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol with value:   - \'1\' - Yes   - \'0\' - No</Desc></Arg><Arg Name="OptionDisplay" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol with value:   - \'1\' - Yes   - \'0\' - No</Desc></Arg><Arg Name="DiscAddV" Value="" Type="Decimal" MaxLen="8"><Desc>Up to 8 symbols for the value of the  discount/addition. Use minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence=":" /></Arg><Arg Name="DiscAddP" Value="" Type="Decimal" MaxLen="7"><Desc>Up to 7 symbols for the percentage value of the  discount/addition. Use minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="," /></Arg><ArgsFormatRaw><![CDATA[ <OptionPrinting[1]> <;> <OptionDisplay[1]> {<\':\'> <DiscAddV[1..8]>} {<\',\'> <DiscAddP[1..7]>} ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="SubtotalValue" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for the value of the subtotal amount</Desc></Res><ResFormatRaw><![CDATA[<SubtotalValue[1..10]>]]></ResFormatRaw></Response></Command><Command Name="ProgramNBLParameter" CmdByte="0x4F"><FPOperation>Program NBL parameter to be monitored by the fiscal device.</FPOperation><Args><Arg Name="" Value="N" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="W" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionNBL" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol with value:   - \'0\' - No   - \'1\' - Yes</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'N\'> <;> <\'W\'> <;> <OptionNBL[1]> ]]></ArgsFormatRaw></Args></Command><Command Name="PrintDetailedFMPaymentsReportByDate" CmdByte="0x7A"><FPOperation>Print a detailed FM payments report by initial and end date.</FPOperation><Args><Arg Name="StartDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name="EndDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name="Option" Value="P" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartDate "DDMMYY"> <;> <EndDate "DDMMYY"> <;> <Option[\'P\']> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadDailyRA" CmdByte="0x6E"><FPOperation>Provides information about the RA amounts by type of payment and the total number of operations.</FPOperation><Args><Arg Name="" Value="2" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'2\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="2" Type="OptionHardcoded" MaxLen="1" /><Res Name="AmountPayment0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name="AmountPayment1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name="AmountPayment2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name="AmountPayment3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name="AmountPayment4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><Res Name="AmountPayment5" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 5</Desc></Res><Res Name="AmountPayment6" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 6</Desc></Res><Res Name="AmountPayment7" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 7</Desc></Res><Res Name="AmountPayment8" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 8</Desc></Res><Res Name="AmountPayment9" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 9</Desc></Res><Res Name="AmountPayment10" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 10</Desc></Res><Res Name="AmountPayment11" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 11</Desc></Res><Res Name="RANum" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for the total number of operations</Desc></Res><Res Name="SumAllPayment" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols to sum all payments</Desc></Res><ResFormatRaw><![CDATA[<\'2\'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]> <;> <AmountPayment5[1..13]> <;> <AmountPayment6[1..13]> <;> <AmountPayment7[1..13]> <;> <AmountPayment8[1..13]> <;> <AmountPayment9[1..13]> <;> <AmountPayment10[1..13]> <;> <AmountPayment11[1..13]> <;> <RANum[1..5]> <;> <SumAllPayment[1..13]>]]></ResFormatRaw></Response></Command><Command Name="ReadGeneralDailyRegisters" CmdByte="0x6E"><FPOperation>Provides information about the number of customers (number of fiscal receipt issued), number of discounts, additions and corrections made and the accumulated amounts.</FPOperation><Args><Arg Name="" Value="1" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'1\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="1" Type="OptionHardcoded" MaxLen="1" /><Res Name="CustomersNum" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for number of customers</Desc></Res><Res Name="DiscountsNum" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for number of discounts</Desc></Res><Res Name="DiscountsAmount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for accumulated amount of discounts</Desc></Res><Res Name="AdditionsNum" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for number of additions</Desc></Res><Res Name="AdditionsAmount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for accumulated amount of additions</Desc></Res><Res Name="CorrectionsNum" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for number of corrections</Desc></Res><Res Name="CorrectionsAmount" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for accumulated amount of corrections</Desc></Res><ResFormatRaw><![CDATA[<\'1\'> <;> <CustomersNum[1..5]> <;> <DiscountsNum[1..5]> <;> <DiscountsAmount[1..13]> <;> <AdditionsNum[1..5]> <;> <AdditionsAmount[1..13]> <;> <CorrectionsNum[1..5]> <;> <CorrectionsAmount[1..13]>]]></ResFormatRaw></Response></Command><Command Name="ReadDisplayGreetingMessage" CmdByte="0x69"><FPOperation>Provides the content of the Display Greeting message.</FPOperation><Args><Arg Name="" Value="0" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'0\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="0" Type="OptionHardcoded" MaxLen="1" /><Res Name="DisplayGreetingText" Value="" Type="Text" MaxLen="20"><Desc>20 symbols for display greeting message</Desc></Res><ResFormatRaw><![CDATA[<\'0\'> <;> <DisplayGreetingText[20]>]]></ResFormatRaw></Response></Command><Command Name="ReadDetailedFMReportByZBlocks" CmdByte="0x78"><FPOperation>Read a detailed FM report by initial and end FM report number.</FPOperation><Args><Arg Name="StartZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for the initial report number included in report, format ####</Desc></Arg><Arg Name="EndZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for the final report number included in report, format ####</Desc></Arg><Arg Name="" Value="0" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionReading" Value="8" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <\'0\'> <;> <OptionReading[\'8\']> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="PrintBriefFMPaymentsReport" CmdByte="0x77"><FPOperation>Prints a brief payments from the FM.</FPOperation><Args><Arg Name="Option" Value="P" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <Option[\'P\']>  ]]></ArgsFormatRaw></Args></Command><Command Name="ReadDailyAvailableAmounts_Old" CmdByte="0x6E"><FPOperation>Provides information about the amounts on hand by type of payment. Command works for KL version 2 devices.</FPOperation><Args><Arg Name="" Value="0" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'0\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="0" Type="OptionHardcoded" MaxLen="1" /><Res Name="AmountPayment0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name="AmountPayment1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name="AmountPayment2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name="AmountPayment3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name="AmountPayment4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><ResFormatRaw><![CDATA[<\'0\'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]>]]></ResFormatRaw></Response></Command><Command Name="ReadBriefFMPaymentsReportByZBlocks" CmdByte="0x79"><FPOperation>Read a brief FM payments report by initial and end FM report number.</FPOperation><Args><Arg Name="StartZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for the initial FM report number included in report, format ####</Desc></Arg><Arg Name="EndZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for the final FM report number included in report, format ####</Desc></Arg><Arg Name="Option" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionReading" Value="8" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <Option[\'P\']> <;> <OptionReading[\'8\']> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="ReadBriefFMDepartmentsReportByZBlocks" CmdByte="0x79"><FPOperation>Read a brief FM Departments report by initial and end Z report number.</FPOperation><Args><Arg Name="StartZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for the initial FM report number included in report, format ####</Desc></Arg><Arg Name="EndZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for the final FM report number included in report, format ####</Desc></Arg><Arg Name="Option" Value="D" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionReading" Value="8" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <Option[\'D\']> <;> <OptionReading[\'8\']> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="ReadGrandFiscalSalesAndStornoAmounts" CmdByte="0x6E"><FPOperation>Read the Grand fiscal turnover sum and Grand fiscal VAT sum.</FPOperation><Args><Arg Name="" Value="7" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'7\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="7" Type="OptionHardcoded" MaxLen="1" /><Res Name="GrandFiscalTurnover" Value="" Type="Decimal" MaxLen="14"><Desc>Up to 14 symbols for sum of turnover in FD</Desc></Res><Res Name="GrandFiscalVAT" Value="" Type="Decimal" MaxLen="14"><Desc>Up to 14 symbols for sum of VAT value in FD</Desc></Res><Res Name="GrandFiscalStornoTurnover" Value="" Type="Decimal" MaxLen="14"><Desc>Up to 14 symbols for sum of STORNO turnover in FD</Desc></Res><Res Name="GrandFiscalStornoVAT" Value="" Type="Decimal" MaxLen="14"><Desc>Up to 14 symbols for sum of STORNO VAT value in FD</Desc></Res><ResFormatRaw><![CDATA[<\'7\'> <;> <GrandFiscalTurnover[1..14]> <;> <GrandFiscalVAT[1..14]> <;> <GrandFiscalStornoTurnover[1..14]> <;> <GrandFiscalStornoVAT[1..14]>]]></ResFormatRaw></Response></Command><Command Name="ReadBluetooth_Status" CmdByte="0x4E"><FPOperation>Providing information about if the device\'s Bluetooth module is enabled or disabled.</FPOperation><Args><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="B" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="S" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'R\'><;><\'B\'><;><\'S\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="B" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="S" Type="OptionHardcoded" MaxLen="1" /><Res Name="OptionBTstatus" Value="" Type="Option" MaxLen="1"><Options><Option Name="Disabled" Value="0" /><Option Name="Enabled" Value="1" /></Options><Desc>(Status) 1 symbol with value:   - \'0\' - Disabled   - \'1\' - Enabled</Desc></Res><ResFormatRaw><![CDATA[<\'R\'><;><\'B\'><;><\'S\'><;><BTstatus[1]>]]></ResFormatRaw></Response></Command><Command Name="OpenElectronicInvoiceWithFDCustomerDB" CmdByte="0x30"><FPOperation>Opens an electronic fiscal invoice receipt with 1 minute timeout assigned to the specified operator number and operator password with internal DB info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.</FPOperation><Args><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbol from 1 to 20 corresponding to operator\'s  number</Desc></Arg><Arg Name="OperPass" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for operator\'s password</Desc></Arg><Arg Name="reserved" Value="0" Type="OptionHardcoded" MaxLen="1" /><Arg Name="reserved" Value="0" Type="OptionHardcoded" MaxLen="1" /><Arg Name="InvoicePrintType" Value="9" Type="OptionHardcoded" MaxLen="1" /><Arg Name="CustomerNum" Value="" Type="Text" MaxLen="5"><Desc>Symbol \'#\' and following up to 4 symbols for related customer ID number  corresponding to the FD database</Desc></Arg><Arg Name="UniqueReceiptNumber" Value="" Type="Text" MaxLen="24"><Desc>Up to 24 symbols for unique receipt number.  NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where:  * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number,  * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator,  * YYYYYYY - 7 symbols [0-9] for next number of the receipt</Desc><Meta MinLen="24" Compulsory="false" ValIndicatingPresence="$" /></Arg><ArgsFormatRaw><![CDATA[ <OperNum[1..2]> <;> <OperPass[6]> <;> <reserved[\'0\']> <;> <reserved[\'0\']> <;> <InvoicePrintType[\'9\']> <;> <CustomerNum[5]> { <\'$\'> <UniqueReceiptNumber[24]> } ]]></ArgsFormatRaw></Args></Command><Command Name="SellFractQtyPLUfromDep" CmdByte="0x3D"><FPOperation>Register the sell (for correction use minus sign in the price field) of article belonging to department with specified name, price, fractional quantity and/or discount/addition on the transaction. The VAT of article got from department to which article belongs.</FPOperation><Args><Arg Name="NamePLU" Value="" Type="Text" MaxLen="36"><Desc>36 symbols for article\'s name. 34 symbols are printed on paper.  Symbol 0x7C \'|\' is new line separator.</Desc></Arg><Arg Name="reserved" Value=" " Type="OptionHardcoded" MaxLen="1" /><Arg Name="Price" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for article\'s price. Use minus sign \'-\' for correction</Desc></Arg><Arg Name="Quantity" Value="" Type="Text" MaxLen="10"><Desc>From 3 to 10 symbols for quantity in format fractional format, e.g. 1/3</Desc><Meta MinLen="10" Compulsory="false" ValIndicatingPresence="*" /></Arg><Arg Name="DiscAddP" Value="" Type="Decimal" MaxLen="7"><Desc>1 to 7 symbols for percentage of discount/addition. Use  minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="," /></Arg><Arg Name="DiscAddV" Value="" Type="Decimal" MaxLen="8"><Desc>1 to 8 symbols for value of discount/addition. Use  minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence=":" /></Arg><Arg Name="DepNum" Value="" Type="Decimal_plus_80h" MaxLen="2"><Desc>1 symbol for article department  attachment, formed in the following manner; example: Dep01 = 81h, Dep02  = 82h … Dep19 = 93h  Department range from 1 to 127</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="!" /></Arg><ArgsFormatRaw><![CDATA[ <NamePLU[36]> <;> <reserved[\' \']> <;> <Price[1..10]> {<\'*\'> <Quantity[10]>} {<\',\'> <DiscAddP[1..7]>} {<\':\'> <DiscAddV[1..8]>} {<\'!\'> <DepNum[1]>} ]]></ArgsFormatRaw></Args></Command><Command Name="ReadDeviceModuleSupport" CmdByte="0x4E"><FPOperation>Provide an information about modules supported by the device</FPOperation><Args><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="D" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="D" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'R\'><;><\'D\'><;><\'D\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="D" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="D" Type="OptionHardcoded" MaxLen="1" /><Res Name="OptionLAN" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol for LAN suppor  - \'0\' - No   - \'1\' - Yes</Desc></Res><Res Name="OptionWiFi" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol for WiFi support  - \'0\' - No   - \'1\' - Yes</Desc></Res><Res Name="OptionGPRS" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>1 symbol for GPRS support  - \'0\' - No   - \'1\' - Yes  BT (Bluetooth) 1 symbol for Bluetooth support  - \'0\' - No   - \'1\' - Yes</Desc></Res><Res Name="OptionBT" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>(Bluetooth) 1 symbol for Bluetooth support  - \'0\' - No   - \'1\' - Yes</Desc></Res><ResFormatRaw><![CDATA[<\'R\'><;><\'D\'><;><\'D\'><;><LAN[1]><;><WiFi[1]><;><GPRS[1]><;><BT[1]>]]></ResFormatRaw></Response></Command><Command Name="ReadWiFi_NetworkName" CmdByte="0x4E"><FPOperation>Read device\'s connected WiFi network name</FPOperation><Args><Arg Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="W" Type="OptionHardcoded" MaxLen="1" /><Arg Name="" Value="N" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'R\'><;><\'W\'><;><\'N\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="R" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="W" Type="OptionHardcoded" MaxLen="1" /><Res Name="" Value="N" Type="OptionHardcoded" MaxLen="1" /><Res Name="WiFiNameLength" Value="" Type="Decimal" MaxLen="3"><Desc>(Length) Up to 3 symbols for the WiFi name length</Desc></Res><Res Name="WiFiNetworkName" Value="" Type="Text" MaxLen="100"><Desc>(Name) Up to 100 symbols for the device\'s WiFi network name</Desc></Res><ResFormatRaw><![CDATA[<\'R\'><;><\'W\'><;><\'N\'><;><WiFiNameLength[1..3]><;><WiFiNetworkName[100]>]]></ResFormatRaw></Response></Command><Command Name="ReadParameters" CmdByte="0x65"><FPOperation>Provides information about the number of POS, printing of logo, cash drawer opening, cutting permission, display mode, article report type, Enable/Disable currency in receipt, EJ font type and working operators counter.</FPOperation><Response ACK="false"><Res Name="POSNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>(POS Number) 4 symbols for number of POS in format ####</Desc></Res><Res Name="OptionPrintLogo" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>(Print Logo) 1 symbol of value:   - \'1\' - Yes   - \'0\' - No</Desc></Res><Res Name="OptionAutoOpenDrawer" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>(Auto Open Drawer) 1 symbol of value:   - \'1\' - Yes   - \'0\' - No</Desc></Res><Res Name="OptionAutoCut" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>(Auto Cut) 1 symbol of value:   - \'1\' - Yes   - \'0\' - No</Desc></Res><Res Name="OptionExternalDispManagement" Value="" Type="Option" MaxLen="1"><Options><Option Name="Auto" Value="0" /><Option Name="Manual" Value="1" /></Options><Desc>(External Display Management) 1 symbol of value:   - \'1\' - Manual   - \'0\' - Auto</Desc></Res><Res Name="OptionArticleReportType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Brief" Value="0" /><Option Name="Detailed" Value="1" /></Options><Desc>(Article Report) 1 symbol of value:   - \'1\' - Detailed   - \'0\' - Brief</Desc></Res><Res Name="OptionEnableCurrency" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="0" /><Option Name="Yes" Value="1" /></Options><Desc>(Enable Currency) 1 symbol of value:   - \'1\' - Yes   - \'0\' - No</Desc></Res><Res Name="OptionEJFontType" Value="" Type="Option" MaxLen="1"><Options><Option Name="Low Font" Value="1" /><Option Name="Normal Font" Value="0" /></Options><Desc>(EJ Font) 1 symbol of value:   - \'1\' - Low Font   - \'0\' - Normal Font</Desc></Res><Res Name="reserved" Value="0" Type="OptionHardcoded" MaxLen="1" /><Res Name="OptionWorkOperatorCount" Value="" Type="Option" MaxLen="1"><Options><Option Name="More" Value="0" /><Option Name="One" Value="1" /></Options><Desc>(Work Operator Count) 1 symbol of value:   - \'1\' - One   - \'0\' - More</Desc></Res><ResFormatRaw><![CDATA[<POSNum[4]> <;> <PrintLogo[1]> <;> <AutoOpenDrawer[1]> <;> <AutoCut[1]> <;> <ExternalDispManagement[1]> <;> <ArticleReportType[1]> <;> <EnableCurrency[1]> <;> <EJFontType[1]> <;> <reserved[\'0\']> <;> <WorkOperatorCount[1]>]]></ResFormatRaw></Response></Command><Command Name="ReadDetailedFMDepartmentsReportByDate" CmdByte="0x7A"><FPOperation>Read a detailed FM Departments report by initial and end date.</FPOperation><Args><Arg Name="StartDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name="EndDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name="Option" Value="D" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionReading" Value="8" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartDate "DDMMYY"> <;> <EndDate "DDMMYY"> <;> <Option[\'D\']> <;> <OptionReading[\'8\']> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="ReadVersion" CmdByte="0x21"><FPOperation>Provides information about the device type, Certificate number, Certificate date and time and Device model.</FPOperation><Response ACK="false"><Res Name="OptionDeviceType" Value="" Type="Option" MaxLen="2"><Options><Option Name="ECR" Value="1" /><Option Name="ECR for online store" Value="11" /><Option Name="for FUVAS device" Value="5" /><Option Name="FPr" Value="2" /><Option Name="FPr for online store" Value="21" /><Option Name="Fuel" Value="3" /><Option Name="Fuel system" Value="31" /></Options><Desc>1 or 2 symbols for type of fiscal device:  - \'1\' - ECR  - \'11\' - ECR for online store  - \'2\' - FPr  - \'21\' - FPr for online store  - \'3\' - Fuel  - \'31\' - Fuel system  - \'5\' - for FUVAS device</Desc></Res><Res Name="CertificateNum" Value="" Type="Text" MaxLen="6"><Desc>6 symbols for Certification Number of device model</Desc></Res><Res Name="CertificateDateTime" Value="" Type="DateTime" MaxLen="10" Format="dd-MM-yyyy HH:mm"><Desc>16 symbols for Certificate Date and time parameter   in format: DD-MM-YYYY HH:MM</Desc></Res><Res Name="Model" Value="" Type="Text" MaxLen="50"><Desc>Up to 50 symbols for Model name</Desc></Res><Res Name="Version" Value="" Type="Text" MaxLen="20"><Desc>Up to 20 symbols for Version name and Check sum</Desc></Res><ResFormatRaw><![CDATA[<DeviceType[1..2]> <;> <CertificateNum[6]> <;> <CertificateDateTime "DD-MM-YYYY HH:MM"> <;> <Model[50]> <;> <Version[20]>]]></ResFormatRaw></Response></Command><Command Name="RawWrite" CmdByte="0xFE"><FPOperation> Writes raw bytes to FP </FPOperation><Args><Arg Name="Bytes" Value="" Type="Base64" MaxLen="5000"><Desc>The bytes in BASE64 ecoded string to be written to FP</Desc></Arg></Args></Command><Command Name="SellPLUwithSpecifiedVATfromDep_" CmdByte="0x34"><FPOperation>Register the sell (for correction use minus sign in the price field) of article  with specified department. If VAT is present the relevant accumulations are perfomed in its  registers.</FPOperation><Args><Arg Name="NamePLU" Value="" Type="Text" MaxLen="36"><Desc>36 symbols for name of sale. 34 symbols are printed on  paper. Symbol 0x7C \'|\' is new line separator.</Desc></Arg><Arg Name="DepNum" Value="" Type="Decimal_plus_80h" MaxLen="2"><Desc>1 symbol for article department  attachment, formed in the following manner: DepNum[HEX] + 80h  example: Dep01 = 81h, Dep02 = 82h … Dep19 = 93h  Department range from 1 to 127</Desc></Arg><Arg Name="Price" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for article\'s price. Use minus sign \'-\' for correction</Desc></Arg><Arg Name="Quantity" Value="" Type="Decimal" MaxLen="10"><Desc>Up to 10 symbols for article\'s quantity sold</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="*" /></Arg><Arg Name="DiscAddP" Value="" Type="Decimal" MaxLen="7"><Desc>Up to 7 for percentage of discount/addition. Use  minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="," /></Arg><Arg Name="DiscAddV" Value="" Type="Decimal" MaxLen="8"><Desc>Up to 8 symbols for percentage of  discount/addition. Use minus sign \'-\' for discount</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence=":" /></Arg><Arg Name="OptionVATClass" Value="" Type="Option" MaxLen="1"><Options><Option Name="Forbidden" Value="*" /><Option Name="VAT Class 0" Value="А" /><Option Name="VAT Class 1" Value="Б" /><Option Name="VAT Class 2" Value="В" /><Option Name="VAT Class 3" Value="Г" /><Option Name="VAT Class 4" Value="Д" /><Option Name="VAT Class 5" Value="Е" /><Option Name="VAT Class 6" Value="Ж" /><Option Name="VAT Class 7" Value="З" /></Options><Desc>1 character for VAT class:   - \'А\' - VAT Class 0   - \'Б\' - VAT Class 1   - \'В\' - VAT Class 2   - \'Г\' - VAT Class 3   - \'Д\' - VAT Class 4   - \'Е\' - VAT Class 5   - \'Ж\' - VAT Class 6   - \'З\' - VAT Class 7   - \'*\' - Forbidden</Desc><Meta MinLen="1" Compulsory="false" ValIndicatingPresence="!" /></Arg><ArgsFormatRaw><![CDATA[ <NamePLU[36]> <;> <DepNum[1]> <;> <Price[1..10]> {<\'*\'> <Quantity[1..10]>} {<\',\'> <DiscAddP[1..7]>} {<\':\'> <DiscAddV[1..8]>} {<\'!\'> <OptionVATClass[1]>} ]]></ArgsFormatRaw></Args></Command><Command Name="PrintOrStoreEJByRcpNum" CmdByte="0x7C"><FPOperation>Print or store Electronic Journal Report from receipt number to receipt number.</FPOperation><Args><Arg Name="OptionReportStorage" Value="" Type="Option" MaxLen="2"><Options><Option Name="Printing" Value="J1" /><Option Name="SD card storage" Value="J4" /><Option Name="USB storage" Value="J2" /></Options><Desc>1 character with value:   - \'J1\' - Printing   - \'J2\' - USB storage   - \'J4\' - SD card storage</Desc></Arg><Arg Name="" Value="N" Type="OptionHardcoded" MaxLen="1" /><Arg Name="StartRcpNum" Value="" Type="Decimal_with_format" MaxLen="6" Format="000000."><Desc>6 symbols for initial receipt number included in report, in format ######.</Desc></Arg><Arg Name="EndRcpNum" Value="" Type="Decimal_with_format" MaxLen="6" Format="000000."><Desc>6 symbols for final receipt number included in report in format ######.</Desc></Arg><ArgsFormatRaw><![CDATA[ <ReportStorage[2]> <;> <\'N\'> <;> <StartRcpNum[6]> <;> <EndRcpNum[6]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadDailyReturnedChangeAmountsByOperator" CmdByte="0x6F"><FPOperation>Read the amounts returned as change by different payment types for the specified operator.</FPOperation><Args><Arg Name="" Value="6" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbol from 1 to 20 corresponding to operator\'s number</Desc></Arg><ArgsFormatRaw><![CDATA[ <\'6\'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="6" Type="OptionHardcoded" MaxLen="1" /><Res Name="OperNum" Value="" Type="Decimal" MaxLen="2"><Desc>Symbols from 1 to 20 corresponding to operator\'s number</Desc></Res><Res Name="ChangeAmountPayment0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by type of payment 0</Desc></Res><Res Name="ChangeAmountPayment1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by type of payment 1</Desc></Res><Res Name="ChangeAmountPayment2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by type of payment 2</Desc></Res><Res Name="ChangeAmountPayment3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by type of payment 3</Desc></Res><Res Name="ChangeAmountPayment4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by type of payment 4</Desc></Res><Res Name="ChangeAmountPayment5" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by type of payment 5</Desc></Res><Res Name="ChangeAmountPayment6" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by type of payment 6</Desc></Res><Res Name="ChangeAmountPayment7" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by type of payment 7</Desc></Res><Res Name="ChangeAmountPayment8" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by type of payment 8</Desc></Res><Res Name="ChangeAmountPayment9" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by type of payment 9</Desc></Res><Res Name="ChangeAmountPayment10" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by type of payment 10</Desc></Res><Res Name="ChangeAmountPayment11" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for amounts received by type of payment 11</Desc></Res><ResFormatRaw><![CDATA[<\'6\'> <;> <OperNum[1..2]> <;> <ChangeAmountPayment0[1..13]> <;> <ChangeAmountPayment1[1..13]> <;> <ChangeAmountPayment2[1..13]> <;> <ChangeAmountPayment3[1..13]> <;> <ChangeAmountPayment4[1..13]> <;> <ChangeAmountPayment5[1..13]> <;> <ChangeAmountPayment6[1..13]> <;> <ChangeAmountPayment7[1..13]> <;> <ChangeAmountPayment8[1..13]> <;> <ChangeAmountPayment9[1..13]> <;> <ChangeAmountPayment10[1..13]> <;> <ChangeAmountPayment11[1..13]>]]></ResFormatRaw></Response></Command><Command Name="ReadEJByReceiptNumCustom" CmdByte="0x7C"><FPOperation>Read or Store Electronic Journal Report from receipt number to receipt number, CSV format option and document content. If CSV format is set the content can includes only fiscal receipts. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.</FPOperation><Args><Arg Name="OptionStorageReport" Value="" Type="Option" MaxLen="2"><Options><Option Name="To PC" Value="j0" /><Option Name="To SD card" Value="j4" /><Option Name="To USB Flash Drive" Value="j2" /></Options><Desc>1 character with value   - \'j0\' - To PC   - \'j2\' - To USB Flash Drive   - \'j4\' - To SD card</Desc></Arg><Arg Name="OptionCSVformat" Value="" Type="Option" MaxLen="1"><Options><Option Name="No" Value="X" /><Option Name="Yes" Value="C" /></Options><Desc>1 symbol with value:   - \'C\' - Yes   - \'X\' - No</Desc></Arg><Arg Name="FlagsReceipts" Value="" Type="Flags" MaxLen="1"><Desc>1 symbol for Receipts included in EJ:  Flags.7=0  Flags.6=1  Flags.5=1 Yes, Flags.5=0 No (Include PO)  Flags.4=1 Yes, Flags.4=0 No (Include RA)  Flags.3=1 Yes, Flags.3=0 No (Include Credit Note)  Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp)  Flags.1=1 Yes, Flags.1=0 No (Include Invoice)  Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)</Desc></Arg><Arg Name="FlagsReports" Value="" Type="Flags" MaxLen="1"><Desc>1 symbol for Reports included in EJ:  Flags.7=0  Flags.6=1  Flags.5=0  Flags.4=1 Yes, Flags.4=0 No (Include FM reports)  Flags.3=1 Yes, Flags.3=0 No (Include Other reports)  Flags.2=1 Yes, Flags.2=0 No (Include Daily X)  Flags.1=1 Yes, Flags.1=0 No (Include Daily Z)  Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)</Desc></Arg><Arg Name="" Value="N" Type="OptionHardcoded" MaxLen="1" /><Arg Name="StartRcpNum" Value="" Type="Decimal_with_format" MaxLen="6" Format="000000."><Desc>6 symbols for initial receipt number included in report in format ######.</Desc></Arg><Arg Name="EndRcpNum" Value="" Type="Decimal_with_format" MaxLen="6" Format="000000."><Desc>6 symbols for final receipt number included in report in format ######.</Desc></Arg><ArgsFormatRaw><![CDATA[ <StorageReport[2]> <;> <CSVformat[1]> <;> <FlagsReceipts[1]> <;> <FlagsReports[1]> <;> <\'N\'> <;> <StartRcpNum[6]> <;> <EndRcpNum[6]> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="CashPayCloseReceipt" CmdByte="0x36"><FPOperation>Paying the exact amount in cash and close the fiscal receipt.</FPOperation></Command><Command Name="ProgDisplayGreetingMessage" CmdByte="0x49"><FPOperation>Program the contents of a Display Greeting message.</FPOperation><Args><Arg Name="" Value="0" Type="OptionHardcoded" MaxLen="1" /><Arg Name="DisplayGreetingText" Value="" Type="Text" MaxLen="20"><Desc>20 symbols for Display greeting message</Desc></Arg><ArgsFormatRaw><![CDATA[<\'0\'> <;> <DisplayGreetingText[20]> ]]></ArgsFormatRaw></Args></Command><Command Name="ReadDailyPO" CmdByte="0x6E"><FPOperation>Provides information about the PO amounts by type of payment and the total number of operations.</FPOperation><Args><Arg Name="" Value="3" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <\'3\'> ]]></ArgsFormatRaw></Args><Response ACK="false"><Res Name="" Value="3" Type="OptionHardcoded" MaxLen="1" /><Res Name="AmountPayment0" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name="AmountPayment1" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name="AmountPayment2" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name="AmountPayment3" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name="AmountPayment4" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><Res Name="AmountPayment5" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 5</Desc></Res><Res Name="AmountPayment6" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 6</Desc></Res><Res Name="AmountPayment7" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 7</Desc></Res><Res Name="AmountPayment8" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 8</Desc></Res><Res Name="AmountPayment9" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 9</Desc></Res><Res Name="AmountPayment10" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 10</Desc></Res><Res Name="AmountPayment11" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols for the accumulated amount by payment type 11</Desc></Res><Res Name="PONum" Value="" Type="Decimal" MaxLen="5"><Desc>Up to 5 symbols for the total number of operations</Desc></Res><Res Name="SumAllPayment" Value="" Type="Decimal" MaxLen="13"><Desc>Up to 13 symbols to sum all payments</Desc></Res><ResFormatRaw><![CDATA[<\'3\'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]> <;> <AmountPayment5[1..13]> <;> <AmountPayment6[1..13]> <;> <AmountPayment7[1..13]> <;> <AmountPayment8[1..13]> <;> <AmountPayment9[1..13]> <;> <AmountPayment10[1..13]> <;> <AmountPayment11[1..13]> <;> <PONum[1..5]> <;> <SumAllPayment[1..13]>]]></ResFormatRaw></Response></Command><Command Name="ReadDetailedFMPaymentsReportByZBlocks" CmdByte="0x78"><FPOperation>Read a detailed FM payments report by initial and end Z report number.</FPOperation><Args><Arg Name="StartZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for initial FM report number included in report, format ####</Desc></Arg><Arg Name="EndZNum" Value="" Type="Decimal_with_format" MaxLen="4" Format="0000"><Desc>4 symbols for final FM report number included in report, format ####</Desc></Arg><Arg Name="Option" Value="P" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionReading" Value="8" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <Option[\'P\']> <;> <OptionReading[\'8\']> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="ReadBriefFMReportByDate" CmdByte="0x7B"><FPOperation>Read a brief FM report by initial and end date.</FPOperation><Args><Arg Name="StartDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name="EndDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name="" Value="0" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionReading" Value="8" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartDate "DDMMYY"> <;> <EndDate "DDMMYY"> <;> <\'0\'> <;> <OptionReading[\'8\']> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="ReadDetailedFMReportByDate" CmdByte="0x7A"><FPOperation>Read a detailed FM report by initial and end date.</FPOperation><Args><Arg Name="StartDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name="EndDate" Value="" Type="DateTime" MaxLen="10" Format="ddMMyy"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name="" Value="0" Type="OptionHardcoded" MaxLen="1" /><Arg Name="OptionReading" Value="8" Type="OptionHardcoded" MaxLen="1" /><ArgsFormatRaw><![CDATA[ <StartDate "DDMMYY"> <;> <EndDate "DDMMYY"> <;> <\'0\'> <;> <OptionReading[\'8\']> ]]></ArgsFormatRaw></Args><Response ACK="true" ACK_PLUS="true" /></Command><Command Name="PrintDetailedDailyReport" CmdByte="0x7F"><FPOperation>Prints an extended daily financial report (an article report followed by a daily financial report) with or without zeroing (\'Z\' or \'X\').</FPOperation><Args><Arg Name="OptionZeroing" Value="" Type="Option" MaxLen="1"><Options><Option Name="Without zeroing" Value="X" /><Option Name="Zeroing" Value="Z" /></Options><Desc>with following values:   - \'Z\' -Zeroing   - \'X\' - Without zeroing</Desc></Arg><ArgsFormatRaw><![CDATA[ <OptionZeroing[1]> ]]></ArgsFormatRaw></Args></Command></Defs>';
	return this.ServerSendDefs(defs);
}

Tremol.Enums = Tremol.Enums || {
	/**
	 * @typedef {Tremol.Enums.OptionBarcodeFormat} Tremol.Enums.OptionBarcodeFormat
	 * @readonly
	 * @enum
	 */
	OptionBarcodeFormat: {
		NNNNcWWWWW: '0',
		NNNNNWWWWW: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionZeroing} Tremol.Enums.OptionZeroing
	 * @readonly
	 * @enum
	 */
	OptionZeroing: {
		Without_zeroing: 'X',
		Zeroing: 'Z'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionDecimalPointPosition} Tremol.Enums.OptionDecimalPointPosition
	 * @readonly
	 * @enum
	 */
	OptionDecimalPointPosition: {
		Fractions: '2',
		Whole_numbers: '0'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionPrintLogo} Tremol.Enums.OptionPrintLogo
	 * @readonly
	 * @enum
	 */
	OptionPrintLogo: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionAutoOpenDrawer} Tremol.Enums.OptionAutoOpenDrawer
	 * @readonly
	 * @enum
	 */
	OptionAutoOpenDrawer: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionAutoCut} Tremol.Enums.OptionAutoCut
	 * @readonly
	 * @enum
	 */
	OptionAutoCut: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionExternalDispManagement} Tremol.Enums.OptionExternalDispManagement
	 * @readonly
	 * @enum
	 */
	OptionExternalDispManagement: {
		Auto: '0',
		Manual: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionArticleReportType} Tremol.Enums.OptionArticleReportType
	 * @readonly
	 * @enum
	 */
	OptionArticleReportType: {
		Brief: '0',
		Detailed: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionEnableCurrency} Tremol.Enums.OptionEnableCurrency
	 * @readonly
	 * @enum
	 */
	OptionEnableCurrency: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionEJFontType} Tremol.Enums.OptionEJFontType
	 * @readonly
	 * @enum
	 */
	OptionEJFontType: {
		Low_Font: '1',
		Normal_Font: '0'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionWorkOperatorCount} Tremol.Enums.OptionWorkOperatorCount
	 * @readonly
	 * @enum
	 */
	OptionWorkOperatorCount: {
		More: '0',
		One: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionVATClass} Tremol.Enums.OptionVATClass
	 * @readonly
	 * @enum
	 */
	OptionVATClass: {
		Forbidden: '*',
		VAT_Class_0: 'А',
		VAT_Class_1: 'Б',
		VAT_Class_2: 'В',
		VAT_Class_3: 'Г',
		VAT_Class_4: 'Д',
		VAT_Class_5: 'Е',
		VAT_Class_6: 'Ж',
		VAT_Class_7: 'З'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionUICType} Tremol.Enums.OptionUICType
	 * @readonly
	 * @enum
	 */
	OptionUICType: {
		Bulstat: '0',
		EGN: '1',
		Foreigner_Number: '2',
		NRA_Official_Number: '3'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionReportFormat} Tremol.Enums.OptionReportFormat
	 * @readonly
	 * @enum
	 */
	OptionReportFormat: {
		Brief_EJ: 'J8',
		Detailed_EJ: 'J0'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionPrice} Tremol.Enums.OptionPrice
	 * @readonly
	 * @enum
	 */
	OptionPrice: {
		Free_price_is_disable_valid_only_programmed_price: '0',
		Free_price_is_enable: '1',
		Limited_price: '2'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionSingleTransaction} Tremol.Enums.OptionSingleTransaction
	 * @readonly
	 * @enum
	 */
	OptionSingleTransaction: {
		Active_Single_transaction_in_receipt: '1',
		Inactive_default_value: '0'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionType} Tremol.Enums.OptionType
	 * @readonly
	 * @enum
	 */
	OptionType: {
		Defined_from_the_device: '2',
		Over_subtotal: '1',
		Over_transaction_sum: '0'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionSubtotal} Tremol.Enums.OptionSubtotal
	 * @readonly
	 * @enum
	 */
	OptionSubtotal: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionNumber} Tremol.Enums.OptionNumber
	 * @readonly
	 * @enum
	 */
	OptionNumber: {
		Payment_1: '1',
		Payment_2: '2',
		Payment_3: '3',
		Payment_4: '4'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionCodePayment} Tremol.Enums.OptionCodePayment
	 * @readonly
	 * @enum
	 */
	OptionCodePayment: {
		Bank: '8',
		Card: '7',
		Check: '1',
		Damage: '6',
		Packaging: '4',
		Programming_Name1: '9',
		Programming_Name2: ':',
		Service: '5',
		Talon: '2',
		V_Talon: '3'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionReportStorage} Tremol.Enums.OptionReportStorage
	 * @readonly
	 * @enum
	 */
	OptionReportStorage: {
		Printing: 'J1',
		SD_card_storage: 'J4',
		USB_storage: 'J2'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionSign} Tremol.Enums.OptionSign
	 * @readonly
	 * @enum
	 */
	OptionSign: {
		Correction: '-',
		Sale: '+'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionPaymentType} Tremol.Enums.OptionPaymentType
	 * @readonly
	 * @enum
	 */
	OptionPaymentType: {
		Payment_0: '0',
		Payment_1: '1',
		Payment_10: '10',
		Payment_11: '11',
		Payment_2: '2',
		Payment_3: '3',
		Payment_4: '4',
		Payment_5: '5',
		Payment_6: '6',
		Payment_7: '7',
		Payment_8: '8',
		Payment_9: '9'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionReceiptFormat} Tremol.Enums.OptionReceiptFormat
	 * @readonly
	 * @enum
	 */
	OptionReceiptFormat: {
		Brief: '0',
		Detailed: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionPrintVAT} Tremol.Enums.OptionPrintVAT
	 * @readonly
	 * @enum
	 */
	OptionPrintVAT: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionStornoRcpPrintType} Tremol.Enums.OptionStornoRcpPrintType
	 * @readonly
	 * @enum
	 */
	OptionStornoRcpPrintType: {
		Buffered_Printing: 'D',
		Postponed_Printing: 'B',
		Step_by_step_printing: '@'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionStornoReason} Tremol.Enums.OptionStornoReason
	 * @readonly
	 * @enum
	 */
	OptionStornoReason: {
		Goods_Claim_or_Goods_return: '1',
		Operator_error: '0',
		Tax_relief: '2'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionQuantityType} Tremol.Enums.OptionQuantityType
	 * @readonly
	 * @enum
	 */
	OptionQuantityType: {
		Availability_of_PLU_stock_is_not_monitored: '0',
		Disable_negative_quantity: '1',
		Enable_negative_quantity: '2'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionPayType} Tremol.Enums.OptionPayType
	 * @readonly
	 * @enum
	 */
	OptionPayType: {
		Cash: '0',
		Currency: '11'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionPrintAvailability} Tremol.Enums.OptionPrintAvailability
	 * @readonly
	 * @enum
	 */
	OptionPrintAvailability: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionIsReceiptOpened} Tremol.Enums.OptionIsReceiptOpened
	 * @readonly
	 * @enum
	 */
	OptionIsReceiptOpened: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionDepPrice} Tremol.Enums.OptionDepPrice
	 * @readonly
	 * @enum
	 */
	OptionDepPrice: {
		Free_price_disabled: '0',
		Free_price_disabled_for_single_transaction: '4',
		Free_price_enabled: '1',
		Free_price_enabled_for_single_transaction: '5',
		Limited_price: '2',
		Limited_price_for_single_transaction: '6'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionDHCPEnabled} Tremol.Enums.OptionDHCPEnabled
	 * @readonly
	 * @enum
	 */
	OptionDHCPEnabled: {
		Disabled: '0',
		Enabled: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionTransferAmount} Tremol.Enums.OptionTransferAmount
	 * @readonly
	 * @enum
	 */
	OptionTransferAmount: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionDhcpStatus} Tremol.Enums.OptionDhcpStatus
	 * @readonly
	 * @enum
	 */
	OptionDhcpStatus: {
		Disabled: '0',
		Enabled: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionAddressType} Tremol.Enums.OptionAddressType
	 * @readonly
	 * @enum
	 */
	OptionAddressType: {
		DNS_address: '5',
		Gateway_address: '4',
		IP_address: '2',
		Subnet_Mask: '3'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionHeaderLine} Tremol.Enums.OptionHeaderLine
	 * @readonly
	 * @enum
	 */
	OptionHeaderLine: {
		Header_1: '1',
		Header_2: '2',
		Header_3: '3',
		Header_4: '4',
		Header_5: '5',
		Header_6: '6',
		Header_7: '7'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionTCPAutoStart} Tremol.Enums.OptionTCPAutoStart
	 * @readonly
	 * @enum
	 */
	OptionTCPAutoStart: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionNBL} Tremol.Enums.OptionNBL
	 * @readonly
	 * @enum
	 */
	OptionNBL: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionUsedModule} Tremol.Enums.OptionUsedModule
	 * @readonly
	 * @enum
	 */
	OptionUsedModule: {
		LAN: '1',
		WiFi: '2'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionInvoiceCreditNotePrintType} Tremol.Enums.OptionInvoiceCreditNotePrintType
	 * @readonly
	 * @enum
	 */
	OptionInvoiceCreditNotePrintType: {
		Buffered_Printing: 'E',
		Postponed_Printing: 'C',
		Step_by_step_printing: 'A'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionCodeType} Tremol.Enums.OptionCodeType
	 * @readonly
	 * @enum
	 */
	OptionCodeType: {
		CODABAR: '6',
		CODE_128: 'I',
		CODE_39: '4',
		CODE_93: 'H',
		EAN_13: '2',
		EAN_8: '3',
		ITF: '5',
		UPC_A: '0',
		UPC_E: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionStorageReport} Tremol.Enums.OptionStorageReport
	 * @readonly
	 * @enum
	 */
	OptionStorageReport: {
		To_PC: 'j0',
		To_SD_card: 'j4',
		To_USB_Flash_Drive: 'j2'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionCSVformat} Tremol.Enums.OptionCSVformat
	 * @readonly
	 * @enum
	 */
	OptionCSVformat: {
		No: 'X',
		Yes: 'C'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionDailyReport} Tremol.Enums.OptionDailyReport
	 * @readonly
	 * @enum
	 */
	OptionDailyReport: {
		Generate_automatic_Z_report: '0',
		Print_automatic_Z_report: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionLAN} Tremol.Enums.OptionLAN
	 * @readonly
	 * @enum
	 */
	OptionLAN: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionWiFi} Tremol.Enums.OptionWiFi
	 * @readonly
	 * @enum
	 */
	OptionWiFi: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionGPRS} Tremol.Enums.OptionGPRS
	 * @readonly
	 * @enum
	 */
	OptionGPRS: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionBT} Tremol.Enums.OptionBT
	 * @readonly
	 * @enum
	 */
	OptionBT: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionForbiddenVoid} Tremol.Enums.OptionForbiddenVoid
	 * @readonly
	 * @enum
	 */
	OptionForbiddenVoid: {
		allowed: '0',
		forbidden: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionVATinReceipt} Tremol.Enums.OptionVATinReceipt
	 * @readonly
	 * @enum
	 */
	OptionVATinReceipt: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionInitiatedPayment} Tremol.Enums.OptionInitiatedPayment
	 * @readonly
	 * @enum
	 */
	OptionInitiatedPayment: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionFinalizedPayment} Tremol.Enums.OptionFinalizedPayment
	 * @readonly
	 * @enum
	 */
	OptionFinalizedPayment: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionPowerDownInReceipt} Tremol.Enums.OptionPowerDownInReceipt
	 * @readonly
	 * @enum
	 */
	OptionPowerDownInReceipt: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionTypeReceipt} Tremol.Enums.OptionTypeReceipt
	 * @readonly
	 * @enum
	 */
	OptionTypeReceipt: {
		Invoice_Credit_note_receipt_Postponed_Printing: '7',
		Invoice_Credit_note_receipt_printing_step_by_step: '5',
		Invoice_sales_receipt_Postponed_Printing: '3',
		Invoice_sales_receipt_printing_step_by_step: '1',
		Sales_receipt_Postponed_Printing: '2',
		Sales_receipt_printing_step_by_step: '0',
		Storno_receipt_Postponed_Printing: '6',
		Storno_receipt_printing_step_by_step: '4'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionChangeType} Tremol.Enums.OptionChangeType
	 * @readonly
	 * @enum
	 */
	OptionChangeType: {
		Change_In_Cash: '0',
		Change_In_Currency: '2',
		Same_As_The_payment: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionInvoicePrintType} Tremol.Enums.OptionInvoicePrintType
	 * @readonly
	 * @enum
	 */
	OptionInvoicePrintType: {
		Buffered_Printing: '5',
		Postponed_Printing: '3',
		Step_by_step_printing: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionChange} Tremol.Enums.OptionChange
	 * @readonly
	 * @enum
	 */
	OptionChange: {
		With_Change: '0',
		Without_Change: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionLastReceiptType} Tremol.Enums.OptionLastReceiptType
	 * @readonly
	 * @enum
	 */
	OptionLastReceiptType: {
		Invoice_Credit_note: '5',
		Invoice_sales_receipt: '1',
		Non_fiscal_receipt: '2',
		Sales_receipt_printing: '0',
		Storno_receipt: '4'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionFiscalRcpPrintType} Tremol.Enums.OptionFiscalRcpPrintType
	 * @readonly
	 * @enum
	 */
	OptionFiscalRcpPrintType: {
		Buffered_printing: '4',
		Postponed_printing: '2',
		Step_by_step_printing: '0'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionZReportType} Tremol.Enums.OptionZReportType
	 * @readonly
	 * @enum
	 */
	OptionZReportType: {
		Automatic: '1',
		Manual: '0'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionPaymentNum} Tremol.Enums.OptionPaymentNum
	 * @readonly
	 * @enum
	 */
	OptionPaymentNum: {
		Payment_10: '10',
		Payment_11: '11',
		Payment_9: '9'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionExternalDisplay} Tremol.Enums.OptionExternalDisplay
	 * @readonly
	 * @enum
	 */
	OptionExternalDisplay: {
		No: 'N',
		Yes: 'Y'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionBTstatus} Tremol.Enums.OptionBTstatus
	 * @readonly
	 * @enum
	 */
	OptionBTstatus: {
		Disabled: '0',
		Enabled: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionFDType} Tremol.Enums.OptionFDType
	 * @readonly
	 * @enum
	 */
	OptionFDType: {
		ECR_for_online_store_type_11: '2',
		FPr_for_Fuel_type_3: '0',
		FPr_for_online_store_type_21: '3',
		Main_FPr_for_Fuel_system_type_31: '1',
		reset_default_type: '*'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionNonFiscalPrintType} Tremol.Enums.OptionNonFiscalPrintType
	 * @readonly
	 * @enum
	 */
	OptionNonFiscalPrintType: {
		Postponed_Printing: '1',
		Step_by_step_printing: '0'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionPrinting} Tremol.Enums.OptionPrinting
	 * @readonly
	 * @enum
	 */
	OptionPrinting: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionDisplay} Tremol.Enums.OptionDisplay
	 * @readonly
	 * @enum
	 */
	OptionDisplay: {
		No: '0',
		Yes: '1'
	},
	
	/**
	 * @typedef {Tremol.Enums.OptionDeviceType} Tremol.Enums.OptionDeviceType
	 * @readonly
	 * @enum
	 */
	OptionDeviceType: {
		ECR: '1',
		ECR_for_online_store: '11',
		for_FUVAS_device: '5',
		FPr: '2',
		FPr_for_online_store: '21',
		Fuel: '3',
		Fuel_system: '31'
	}
};