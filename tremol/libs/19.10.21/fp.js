var Tremol = Tremol || { };
Tremol.FP = Tremol.FP || function () { };
Tremol.FP.prototype.timeStamp = 1910211454;
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
 * @typedef {Object} SentRcpInfoStatusRes
 * @property {number} LastSentRcpNum Up to 6 symbols for the last sent receipt number to NRA server
 * @property {Date} LastSentRcpDateTime 16 symbols for the date and time of the last sent receipt to NRA 
server in format DD-MM-YYYY HH:MM
 * @property {number} FirstUnsentRcpNum Up to 6 symbols for the first unsent receipt number to NRA server
 * @property {Date} FirstUnsentRcpDateTime 16 symbols for the date and time of the first unsent receipt to
 * @property {string} NRA_ErrorMessage Up to 100 symbols for error message from NRA server, if exist
 */

/**
 * Provides information about last sent receipt number and date time to NRA server and first unsent receipt number and date time to NRA. If there is no unsent receipt the number will be 0 and date time will be 00-00-0000 00:00 Parameter NRA_ErrorMessage provide error message from NRA server if exist. Command is not allowed if device is deregistered, not fiscalized or in opened receipt.
 * @return {SentRcpInfoStatusRes}
 */
Tremol.FP.prototype.ReadSentRcpInfoStatus = function () {
	return this.do('ReadSentRcpInfoStatus');
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
 * @typedef {Object} DepartmentRes
 * @property {number} DepNum 2 symbols for department number in format: ##
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
 * @param {number} DepNum 2 symbols for department number in format: ##
 * @return {DepartmentRes}
 */
Tremol.FP.prototype.ReadDepartment = function (DepNum) {
	return this.do('ReadDepartment', 'DepNum', DepNum);
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
 * @param {string} Name 10 symbols for payment type name
 * @param {number=} Rate Up to 10 symbols for exchange rate in format: ####.##### 
of the 4th payment type, maximal value 0420.00000
 * @param {Tremol.Enums.OptionCodePayment=} OptionCodePayment 1 symbol for code payment type with name: 
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
 * Print a detailed FM payments report by initial and end FM report number.
 * @param {number} StartZNum 4 symbols for initial FM report number included in report, format ####
 * @param {number} EndZNum 4 symbols for final FM report number included in report, format ####
 */
Tremol.FP.prototype.PrintDetailedFMPaymentsReportByZBlocks = function (StartZNum, EndZNum) {
	return this.do('PrintDetailedFMPaymentsReportByZBlocks', 'StartZNum', StartZNum, 'EndZNum', EndZNum);
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
 * Read the number of the remaining free records for Z-report in the Fiscal Memory.
 * @return {string}
 */
Tremol.FP.prototype.ReadFMfreeRecords = function () {
	return this.do('ReadFMfreeRecords');
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
 * @property {string} NamePaym0 10 symbols for payment name type 0
 * @property {string} NamePaym1 10 symbols for payment name type 1
 * @property {string} NamePaym2 10 symbols for payment name type 2
 * @property {string} NamePaym3 10 symbols for payment name type 3
 * @property {string} NamePaym4 10 symbols for payment name type 4
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
 * @param {number} Amount Up to 10 symbols for the amount lodged. Use minus sign for withdrawn
 * @param {Tremol.Enums.OptionPrintAvailability=} OptionPrintAvailability 1 symbol with value: 
 - '0' - No 
 - '1' - Yes
 * @param {string=} Text TextLength-2 symbols. In the beginning and in the end of line symbol '#' 
is printed.
 */
Tremol.FP.prototype.ReceivedOnAccount_PaidOut = function (OperNum, OperPass, Amount, OptionPrintAvailability, Text) {
	return this.do('ReceivedOnAccount_PaidOut', 'OperNum', OperNum, 'OperPass', OperPass, 'Amount', Amount, 'OptionPrintAvailability', OptionPrintAvailability, 'Text', Text);
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
 * Stores in the memory the graphic file under stated number. Prints information about loaded in the printer graphic files.
 * @param {string} LogoNumber 1 character value from '0' to '9' setting the number where the logo will be saved.
 */
Tremol.FP.prototype.ProgLogoNum = function (LogoNumber) {
	return this.do('ProgLogoNum', 'LogoNumber', LogoNumber);
};

/**
 * @typedef {Object} DepartmentAllRes
 * @property {number} DepNum 2 symbols for department number in format: ##
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
 * @param {number} DepNum 2 symbols for department number in format: ##
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
 * Stores in the memory the graphic file under number 0. Prints information  about loaded in the printer graphic files.
 * @param {string} BMPfile *BMP file with fixed size 9022 bytes
 */
Tremol.FP.prototype.ProgLogo = function (BMPfile) {
	return this.do('ProgLogo', 'BMPfile', BMPfile);
};

/**
 * Shows the current date and time on the external display.
 */
Tremol.FP.prototype.DisplayDateTime = function () {
	return this.do('DisplayDateTime');
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
 * Prints barcode from type stated by CodeType and CodeLen and with data stated in CodeData field. Command works only for fiscal printer devices. ECR does not support this command.
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
 * Erase all articles in PLU database.
 * @param {string} Password 6 symbols for password
 */
Tremol.FP.prototype.EraseAllPLUs = function (Password) {
	return this.do('EraseAllPLUs', 'Password', Password);
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
 * Provide information about daily report parameter. If the parameter is set to 0 the status flag 4.6 will become 1 and the device will block all sales operation until daily report is printed. If the parameter is set to 1 the report will be generated automaticly without printout
 * @return {Tremol.Enums.OptionDailyReportSetting}
 */
Tremol.FP.prototype.ReadDailyReportParameter = function () {
	return this.do('ReadDailyReportParameter');
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
 * Set invoice start and end number range. To execute the command is necessary to grand following condition: the number range to be spent, not used, or not set after the last RAM reset.
 * @param {number} StartNum 10 characters for start number in format: ##########
 * @param {number} EndNum 10 characters for end number in format: ##########
 */
Tremol.FP.prototype.SetInvoiceRange = function (StartNum, EndNum) {
	return this.do('SetInvoiceRange', 'StartNum', StartNum, 'EndNum', EndNum);
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
 */
Tremol.FP.prototype.SellFractQtyPLUwithSpecifiedVATfromDep = function (NamePLU, OptionVATClass, Price, Quantity, DiscAddP, DiscAddV, DepNum) {
	return this.do('SellFractQtyPLUwithSpecifiedVATfromDep', 'NamePLU', NamePLU, 'OptionVATClass', OptionVATClass, 'Price', Price, 'Quantity', Quantity, 'DiscAddP', DiscAddP, 'DiscAddV', DiscAddV, 'DepNum', DepNum);
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
 * Provides consequently information about every single block stored in the FM starting with Acknowledgements and ending with end message.
 */
Tremol.FP.prototype.ReadFMcontent = function () {
	return this.do('ReadFMcontent');
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
 * @property {boolean} Duplicate_printed Duplicate printed
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
 * @property {number} ExchangeRate Up to 10 symbols for exchange rate of payment type 11 in format: 
####.#####
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
 * @param {Tremol.Enums.OptionStorageReport} OptionStorageReport 1 character with value 
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
 * @param {number} Number 2 symbols department number in format ##
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
 */
Tremol.FP.prototype.SellFractQtyPLUfromDep = function (NamePLU, Price, Quantity, DiscAddP, DiscAddV, DepNum) {
	return this.do('SellFractQtyPLUfromDep', 'NamePLU', NamePLU, 'Price', Price, 'Quantity', Quantity, 'DiscAddP', DiscAddP, 'DiscAddV', DiscAddV, 'DepNum', DepNum);
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
 * Prints an extended daily financial report (an article report followed by a daily financial report) with or without zeroing ('Z' or 'X').
 * @param {Tremol.Enums.OptionZeroing} OptionZeroing with following values: 
 - 'Z' -Zeroing 
 - 'X' - Without zeroing
 */
Tremol.FP.prototype.PrintDetailedDailyReport = function (OptionZeroing) {
	return this.do('PrintDetailedDailyReport', 'OptionZeroing', OptionZeroing);
};

Tremol.Enums = Tremol.Enums || {
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
	 * @typedef {Tremol.Enums.OptionDailyReportSetting} Tremol.Enums.OptionDailyReportSetting
	 * @readonly
	 * @enum
	 */
	OptionDailyReportSetting: {
		Automatic_Z_report_without_printing: '1',
		Z_report_with_printing: '0'
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