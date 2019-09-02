<?php
namespace Tremol {
  require('FP_Core.php');
  class OptionZeroing extends EnumType {
    const Without_zeroing = 'X';
    const Zeroing = 'Z';
  }
  
  class OptionDecimalPointPosition extends EnumType {
    const Fractions = '2';
    const Whole_numbers = '0';
  }
  
  class OptionPrintLogo extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionAutoOpenDrawer extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionAutoCut extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionExternalDispManagement extends EnumType {
    const Auto = '0';
    const Manual = '1';
  }
  
  class OptionArticleReportType extends EnumType {
    const Brief = '0';
    const Detailed = '1';
  }
  
  class OptionEnableCurrency extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionEJFontType extends EnumType {
    const Low_Font = '1';
    const Normal_Font = '0';
  }
  
  class OptionWorkOperatorCount extends EnumType {
    const More = '0';
    const One = '1';
  }
  
  class OptionVATClass extends EnumType {
    const Forbidden = '*';
    const VAT_Class_0 = 'А';
    const VAT_Class_1 = 'Б';
    const VAT_Class_2 = 'В';
    const VAT_Class_3 = 'Г';
    const VAT_Class_4 = 'Д';
    const VAT_Class_5 = 'Е';
    const VAT_Class_6 = 'Ж';
    const VAT_Class_7 = 'З';
  }
  
  class OptionUICType extends EnumType {
    const Bulstat = '0';
    const EGN = '1';
    const Foreigner_Number = '2';
    const NRA_Official_Number = '3';
  }
  
  class OptionReportFormat extends EnumType {
    const Brief_EJ = 'J8';
    const Detailed_EJ = 'J0';
  }
  
  class OptionPrice extends EnumType {
    const Free_price_is_disable_valid_only_programmed_price = '0';
    const Free_price_is_enable = '1';
    const Limited_price = '2';
  }
  
  class OptionSingleTransaction extends EnumType {
    const Active_Single_transaction_in_receipt = '1';
    const Inactive_default_value = '0';
  }
  
  class OptionType extends EnumType {
    const Defined_from_the_device = '2';
    const Over_subtotal = '1';
    const Over_transaction_sum = '0';
  }
  
  class OptionSubtotal extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionNumber extends EnumType {
    const Payment_1 = '1';
    const Payment_2 = '2';
    const Payment_3 = '3';
    const Payment_4 = '4';
  }
  
  class OptionCodePayment extends EnumType {
    const Bank = '8';
    const Card = '7';
    const Check = '1';
    const Damage = '6';
    const Packaging = '4';
    const Programming_Name1 = '9';
    const Programming_Name2 = ':';
    const Service = '5';
    const Talon = '2';
    const V_Talon = '3';
  }
  
  class OptionReportStorage extends EnumType {
    const Printing = 'J1';
    const SD_card_storage = 'J4';
    const USB_storage = 'J2';
  }
  
  class OptionSign extends EnumType {
    const Correction = '-';
    const Sale = '+';
  }
  
  class OptionPaymentType extends EnumType {
    const Payment_0 = '0';
    const Payment_1 = '1';
    const Payment_10 = '10';
    const Payment_11 = '11';
    const Payment_2 = '2';
    const Payment_3 = '3';
    const Payment_4 = '4';
    const Payment_5 = '5';
    const Payment_6 = '6';
    const Payment_7 = '7';
    const Payment_8 = '8';
    const Payment_9 = '9';
  }
  
  class OptionReceiptFormat extends EnumType {
    const Brief = '0';
    const Detailed = '1';
  }
  
  class OptionPrintVAT extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionStornoRcpPrintType extends EnumType {
    const Buffered_Printing = 'D';
    const Postponed_Printing = 'B';
    const Step_by_step_printing = '@';
  }
  
  class OptionStornoReason extends EnumType {
    const Goods_Claim_or_Goods_return = '1';
    const Operator_error = '0';
    const Tax_relief = '2';
  }
  
  class OptionQuantityType extends EnumType {
    const Availability_of_PLU_stock_is_not_monitored = '0';
    const Disable_negative_quantity = '1';
    const Enable_negative_quantity = '2';
  }
  
  class OptionPrintAvailability extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionIsReceiptOpened extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionDepPrice extends EnumType {
    const Free_price_disabled = '0';
    const Free_price_disabled_for_single_transaction = '4';
    const Free_price_enabled = '1';
    const Free_price_enabled_for_single_transaction = '5';
    const Limited_price = '2';
    const Limited_price_for_single_transaction = '6';
  }
  
  class OptionHeaderLine extends EnumType {
    const Header_1 = '1';
    const Header_2 = '2';
    const Header_3 = '3';
    const Header_4 = '4';
    const Header_5 = '5';
    const Header_6 = '6';
    const Header_7 = '7';
  }
  
  class OptionInvoiceCreditNotePrintType extends EnumType {
    const Buffered_Printing = 'E';
    const Postponed_Printing = 'C';
    const Step_by_step_printing = 'A';
  }
  
  class OptionCodeType extends EnumType {
    const CODABAR = '6';
    const CODE_128 = 'I';
    const CODE_39 = '4';
    const CODE_93 = 'H';
    const EAN_13 = '2';
    const EAN_8 = '3';
    const ITF = '5';
    const UPC_A = '0';
    const UPC_E = '1';
  }
  
  class OptionStorageReport extends EnumType {
    const To_PC = 'j0';
    const To_SD_card = 'j4';
    const To_USB_Flash_Drive = 'j2';
  }
  
  class OptionCSVformat extends EnumType {
    const No = 'X';
    const Yes = 'C';
  }
  
  class OptionDailyReportSetting extends EnumType {
    const Automatic_Z_report_without_printing = '1';
    const Z_report_with_printing = '0';
  }
  
  class OptionForbiddenVoid extends EnumType {
    const allowed = '0';
    const forbidden = '1';
  }
  
  class OptionVATinReceipt extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionInitiatedPayment extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionFinalizedPayment extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionPowerDownInReceipt extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionTypeReceipt extends EnumType {
    const Invoice_Credit_note_receipt_Postponed_Printing = '7';
    const Invoice_Credit_note_receipt_printing_step_by_step = '5';
    const Invoice_sales_receipt_Postponed_Printing = '3';
    const Invoice_sales_receipt_printing_step_by_step = '1';
    const Sales_receipt_Postponed_Printing = '2';
    const Sales_receipt_printing_step_by_step = '0';
    const Storno_receipt_Postponed_Printing = '6';
    const Storno_receipt_printing_step_by_step = '4';
  }
  
  class OptionChangeType extends EnumType {
    const Change_In_Cash = '0';
    const Change_In_Currency = '2';
    const Same_As_The_payment = '1';
  }
  
  class OptionInvoicePrintType extends EnumType {
    const Buffered_Printing = '5';
    const Postponed_Printing = '3';
    const Step_by_step_printing = '1';
  }
  
  class OptionChange extends EnumType {
    const With_Change = '0';
    const Without_Change = '1';
  }
  
  class OptionLastReceiptType extends EnumType {
    const Invoice_Credit_note = '5';
    const Invoice_sales_receipt = '1';
    const Non_fiscal_receipt = '2';
    const Sales_receipt_printing = '0';
    const Storno_receipt = '4';
  }
  
  class OptionFiscalRcpPrintType extends EnumType {
    const Buffered_printing = '4';
    const Postponed_printing = '2';
    const Step_by_step_printing = '0';
  }
  
  class OptionPaymentNum extends EnumType {
    const Payment_10 = '10';
    const Payment_11 = '11';
    const Payment_9 = '9';
  }
  
  class OptionExternalDisplay extends EnumType {
    const No = 'N';
    const Yes = 'Y';
  }
  
  class OptionFDType extends EnumType {
    const ECR_for_online_store_type_11 = '2';
    const FPr_for_Fuel_type_3 = '0';
    const FPr_for_online_store_type_21 = '3';
    const Main_FPr_for_Fuel_system_type_31 = '1';
    const reset_default_type = '*';
  }
  
  class OptionNonFiscalPrintType extends EnumType {
    const Postponed_Printing = '1';
    const Step_by_step_printing = '0';
  }
  
  class OptionPrinting extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionDisplay extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionDeviceType extends EnumType {
    const ECR = '1';
    const ECR_for_online_store = '11';
    const for_FUVAS_device = '5';
    const FPr = '2';
    const FPr_for_online_store = '21';
    const Fuel = '3';
    const Fuel_system = '31';
  }
  
  class DailyAvailableAmountsRes extends BaseResClass {
  protected $AmountPayment0;
  protected $AmountPayment1;
  protected $AmountPayment2;
  protected $AmountPayment3;
  protected $AmountPayment4;
  protected $AmountPayment5;
  protected $AmountPayment6;
  protected $AmountPayment7;
  protected $AmountPayment8;
  protected $AmountPayment9;
  protected $AmountPayment10;
  protected $AmountPayment11;
}

class DailyPO_OldRes extends BaseResClass {
  protected $AmountPayment0;
  protected $AmountPayment1;
  protected $AmountPayment2;
  protected $AmountPayment3;
  protected $AmountPayment4;
  protected $PONum;
  protected $SumAllPayment;
}

class SentRcpInfoStatusRes extends BaseResClass {
  protected $LastSentRcpNum;
  protected $LastSentRcpDateTime;
  protected $FirstUnsentRcpNum;
  protected $FirstUnsentRcpDateTime;
  protected $NRA_ErrorMessage;
}

class DepartmentRes extends BaseResClass {
  protected $DepNum;
  protected $DepName;
  protected $OptionVATClass;
  protected $Turnover;
  protected $SoldQuantity;
  protected $LastZReportNumber;
  protected $LastZReportDate;
}

class PLU_OldRes extends BaseResClass {
  protected $PLUNum;
  protected $PLUName;
  protected $Price;
  protected $OptionVATClass;
  protected $Turnover;
  protected $QuantitySold;
  protected $LastZReportNumber;
  protected $LastZReportDate;
  protected $BelongToDepNumber;
}

class InvoiceRangeRes extends BaseResClass {
  protected $StartNum;
  protected $EndNum;
}

class Payments_OldRes extends BaseResClass {
  protected $NamePaym0;
  protected $NamePaym1;
  protected $NamePaym2;
  protected $NamePaym3;
  protected $NamePaym4;
  protected $ExRate;
  protected $CodePaym0;
  protected $CodePaym1;
  protected $CodePaym2;
  protected $CodePaym3;
  protected $CodePaym4;
}

class PLUqtyRes extends BaseResClass {
  protected $PLUNum;
  protected $AvailableQuantity;
  protected $OptionQuantityType;
}

class SerialAndFiscalNumsRes extends BaseResClass {
  protected $SerialNumber;
  protected $FMNumber;
}

class DailyReturnedChangeAmountsByOperator_OldRes extends BaseResClass {
  protected $OperNum;
  protected $ChangeAmountPayment0;
  protected $ChangeAmountPayment1;
  protected $ChangeAmountPayment2;
  protected $ChangeAmountPayment3;
  protected $ChangeAmountPayment4;
}

class CurrentOrLastReceiptPaymentAmountsRes extends BaseResClass {
  protected $OptionIsReceiptOpened;
  protected $Payment0Amount;
  protected $Payment1Amount;
  protected $Payment2Amount;
  protected $Payment3Amount;
  protected $Payment4Amount;
  protected $Payment5Amount;
  protected $Payment6Amount;
  protected $Payment7Amount;
  protected $Payment8Amount;
  protected $Payment9Amount;
  protected $Payment10Amount;
  protected $Payment11Amount;
}

class DailyReturnedChangeAmountsRes extends BaseResClass {
  protected $AmountPayment0;
  protected $AmountPayment1;
  protected $AmountPayment2;
  protected $AmountPayment3;
  protected $AmountPayment4;
  protected $AmountPayment5;
  protected $AmountPayment6;
  protected $AmountPayment7;
  protected $AmountPayment8;
  protected $AmountPayment9;
  protected $AmountPayment10;
  protected $AmountPayment11;
}

class DailyReceivedSalesAmountsByOperator_OldRes extends BaseResClass {
  protected $OperNum;
  protected $ReceivedSalesAmountPayment0;
  protected $ReceivedSalesAmountPayment1;
  protected $ReceivedSalesAmountPayment2;
  protected $ReceivedSalesAmountPayment3;
  protected $ReceivedSalesAmountPayment4;
}

class DepartmentAllRes extends BaseResClass {
  protected $DepNum;
  protected $DepName;
  protected $OptionVATClass;
  protected $Price;
  protected $OptionDepPrice;
  protected $TurnoverAmount;
  protected $SoldQuantity;
  protected $StornoAmount;
  protected $StornoQuantity;
  protected $LastZReportNumber;
  protected $LastZReportDate;
}

class VATratesRes extends BaseResClass {
  protected $VATrate0;
  protected $VATrate1;
  protected $VATrate2;
  protected $VATrate3;
  protected $VATrate4;
  protected $VATrate5;
  protected $VATrate6;
  protected $VATrate7;
}

class DailyReceivedSalesAmountsRes extends BaseResClass {
  protected $AmountPayment0;
  protected $AmountPayment1;
  protected $AmountPayment2;
  protected $AmountPayment3;
  protected $AmountPayment4;
  protected $AmountPayment5;
  protected $AmountPayment6;
  protected $AmountPayment7;
  protected $AmountPayment8;
  protected $AmountPayment9;
  protected $AmountPayment10;
  protected $AmountPayment11;
}

class RegistrationInfoRes extends BaseResClass {
  protected $UIC;
  protected $OptionUICType;
  protected $NRARegistrationNumber;
  protected $NRARegistrationDate;
}

class DailyPObyOperatorRes extends BaseResClass {
  protected $OperNum;
  protected $AmountPO_Payment0;
  protected $AmountPO_Payment1;
  protected $AmountPO_Payment2;
  protected $AmountPO_Payment3;
  protected $AmountPO_Payment4;
  protected $AmountPO_Payment5;
  protected $AmountPO_Payment6;
  protected $AmountPO_Payment7;
  protected $AmountPO_Payment8;
  protected $AmountPO_Payment9;
  protected $AmountPO_Payment10;
  protected $AmountPO_Payment11;
  protected $NoPO;
}

class DailyReceivedSalesAmounts_OldRes extends BaseResClass {
  protected $AmountPayment0;
  protected $AmountPayment1;
  protected $AmountPayment2;
  protected $AmountPayment3;
  protected $AmountPayment4;
}

class DailyReturnedChangeAmounts_OldRes extends BaseResClass {
  protected $AmountPayment0;
  protected $AmountPayment1;
  protected $AmountPayment2;
  protected $AmountPayment3;
  protected $AmountPayment4;
}

class DailySaleAndStornoAmountsByVATRes extends BaseResClass {
  protected $SaleAmountVATGr0;
  protected $SaleAmountVATGr1;
  protected $SaleAmountVATGr2;
  protected $SaleAmountVATGr3;
  protected $SaleAmountVATGr4;
  protected $SaleAmountVATGr5;
  protected $SaleAmountVATGr6;
  protected $SaleAmountVATGr7;
  protected $SumAllVATGr;
  protected $StornoAmountVATGr0;
  protected $StornoAmountVATGr1;
  protected $StornoAmountVATGr2;
  protected $StornoAmountVATGr3;
  protected $StornoAmountVATGr4;
  protected $StornoAmountVATGr5;
  protected $StornoAmountVATGr6;
  protected $StornoAmountVATGr7;
  protected $StornoAllVATGr;
}

class DailyCountersRes extends BaseResClass {
  protected $LastReportNumFromReset;
  protected $LastFMBlockNum;
  protected $EJNum;
  protected $DateTime;
}

class DailyRAbyOperatorRes extends BaseResClass {
  protected $OperNum;
  protected $AmountRA_Payment0;
  protected $AmountRA_Payment1;
  protected $AmountRA_Payment2;
  protected $AmountRA_Payment3;
  protected $AmountRA_Payment4;
  protected $AmountRA_Payment5;
  protected $AmountRA_Payment6;
  protected $AmountRA_Payment7;
  protected $AmountRA_Payment8;
  protected $AmountRA_Payment9;
  protected $AmountRA_Payment10;
  protected $AmountRA_Payment11;
  protected $NoRA;
}

class HeaderRes extends BaseResClass {
  protected $OptionHeaderLine;
  protected $HeaderText;
}

class PLUgeneralRes extends BaseResClass {
  protected $PLUNum;
  protected $PLUName;
  protected $Price;
  protected $OptionPrice;
  protected $OptionVATClass;
  protected $BelongToDepNumber;
  protected $TurnoverAmount;
  protected $SoldQuantity;
  protected $StornoAmount;
  protected $StornoQuantity;
  protected $LastZReportNumber;
  protected $LastZReportDate;
  protected $OptionSingleTransaction;
}

class DailyReceivedSalesAmountsByOperatorRes extends BaseResClass {
  protected $OperNum;
  protected $ReceivedSalesAmountPayment0;
  protected $ReceivedSalesAmountPayment1;
  protected $ReceivedSalesAmountPayment2;
  protected $ReceivedSalesAmountPayment3;
  protected $ReceivedSalesAmountPayment4;
  protected $ReceivedSalesAmountPayment5;
  protected $ReceivedSalesAmountPayment6;
  protected $ReceivedSalesAmountPayment7;
  protected $ReceivedSalesAmountPayment8;
  protected $ReceivedSalesAmountPayment9;
  protected $ReceivedSalesAmountPayment10;
  protected $ReceivedSalesAmountPayment11;
}

class CustomerDataRes extends BaseResClass {
  protected $CustomerNum;
  protected $CustomerCompanyName;
  protected $CustomerFullName;
  protected $VATNumber;
  protected $UIC;
  protected $Address;
  protected $OptionUICType;
}

class CurrentReceiptInfoRes extends BaseResClass {
  protected $OptionIsReceiptOpened;
  protected $SalesNumber;
  protected $SubtotalAmountVAT0;
  protected $SubtotalAmountVAT1;
  protected $SubtotalAmountVAT2;
  protected $OptionForbiddenVoid;
  protected $OptionVATinReceipt;
  protected $OptionReceiptFormat;
  protected $OptionInitiatedPayment;
  protected $OptionFinalizedPayment;
  protected $OptionPowerDownInReceipt;
  protected $OptionTypeReceipt;
  protected $ChangeAmount;
  protected $OptionChangeType;
  protected $SubtotalAmountVAT3;
  protected $SubtotalAmountVAT4;
  protected $SubtotalAmountVAT5;
  protected $SubtotalAmountVAT6;
  protected $SubtotalAmountVAT7;
  protected $CurrentReceiptNumber;
}

class PLUallDataRes extends BaseResClass {
  protected $PLUNum;
  protected $PLUName;
  protected $Price;
  protected $FlagsPricePLU;
  protected $OptionVATClass;
  protected $BelongToDepNumber;
  protected $TurnoverAmount;
  protected $SoldQuantity;
  protected $StornoAmount;
  protected $StornoQuantity;
  protected $LastZReportNumber;
  protected $LastZReportDate;
  protected $AvailableQuantity;
  protected $Barcode;
}

class LastDailyReportInfoRes extends BaseResClass {
  protected $LastZDailyReportDate;
  protected $LastZDailyReportNum;
  protected $LastRAMResetNum;
  protected $TotalReceiptCounter;
  protected $DateTimeLastFiscRec;
  protected $EJNum;
  protected $OptionLastReceiptType;
}

class StatusRes extends BaseResClass {
  protected $FM_Read_only;
  protected $Power_down_in_opened_fiscal_receipt;
  protected $Printer_not_ready_overheat;
  protected $DateTime_not_set;
  protected $DateTime_wrong;
  protected $RAM_reset;
  protected $Hardware_clock_error;
  protected $Printer_not_ready_no_paper;
  protected $Reports_registers_Overflow;
  protected $Customer_report_is_not_zeroed;
  protected $Daily_report_is_not_zeroed;
  protected $Article_report_is_not_zeroed;
  protected $Operator_report_is_not_zeroed;
  protected $Duplicate_printed;
  protected $Opened_Non_fiscal_Receipt;
  protected $Opened_Fiscal_Receipt;
  protected $Opened_Fiscal_Detailed_Receipt;
  protected $Opened_Fiscal_Receipt_with_VAT;
  protected $Opened_Invoice_Fiscal_Receipt;
  protected $SD_card_near_full;
  protected $SD_card_full;
  protected $No_FM_module;
  protected $FM_error;
  protected $FM_full;
  protected $FM_near_full;
  protected $Decimal_point;
  protected $FM_fiscalized;
  protected $FM_produced;
  protected $Printer_automatic_cutting;
  protected $External_display_transparent_display;
  protected $Speed_is_9600;
  protected $Drawer_automatic_opening;
  protected $Customer_logo_included_in_the_receipt;
  protected $Wrong_SIM_card;
  protected $Blocking_3_days_without_mobile_operator;
  protected $No_task_from_NRA;
  protected $Wrong_SD_card;
  protected $Deregistered;
  protected $No_SIM_card;
  protected $No_GPRS_Modem;
  protected $No_mobile_operator;
  protected $No_GPRS_service;
  protected $Near_end_of_paper;
  protected $Unsent_data_for_24_hours;
}

class DailyRA_OldRes extends BaseResClass {
  protected $AmountPayment0;
  protected $AmountPayment1;
  protected $AmountPayment2;
  protected $AmountPayment3;
  protected $AmountPayment4;
  protected $RANum;
  protected $SumAllPayment;
}

class PLUpriceRes extends BaseResClass {
  protected $PLUNum;
  protected $Price;
  protected $OptionPrice;
}

class OperatorNamePasswordRes extends BaseResClass {
  protected $Number;
  protected $Name;
  protected $Password;
}

class DailyCountersByOperatorRes extends BaseResClass {
  protected $OperNum;
  protected $WorkOperatorsCounter;
  protected $LastOperatorReportDateTime;
}

class PaymentsRes extends BaseResClass {
  protected $NamePayment0;
  protected $NamePayment1;
  protected $NamePayment2;
  protected $NamePayment3;
  protected $NamePayment4;
  protected $NamePayment5;
  protected $NamePayment6;
  protected $NamePayment7;
  protected $NamePayment8;
  protected $NamePayment9;
  protected $NamePayment10;
  protected $NamePayment11;
  protected $ExchangeRate;
}

class DetailedPrinterStatusRes extends BaseResClass {
  protected $OptionExternalDisplay;
  protected $StatPRN;
  protected $FlagServiceJumper;
}

class PLUbarcodeRes extends BaseResClass {
  protected $PLUNum;
  protected $Barcode;
}

class DailyPObyOperator_OldRes extends BaseResClass {
  protected $OperNum;
  protected $AmountPO_Payment0;
  protected $AmountPO_Payment1;
  protected $AmountPO_Payment2;
  protected $AmountPO_Payment3;
  protected $AmountPO_Payment4;
  protected $NoPO;
}

class DailyGeneralRegistersByOperatorRes extends BaseResClass {
  protected $OperNum;
  protected $CustomersNum;
  protected $DiscountsNum;
  protected $DiscountsAmount;
  protected $AdditionsNum;
  protected $AdditionsAmount;
  protected $CorrectionsNum;
  protected $CorrectionsAmount;
}

class DailyRAbyOperator_OldRes extends BaseResClass {
  protected $OperNum;
  protected $AmountRA_Payment0;
  protected $AmountRA_Payment1;
  protected $AmountRA_Payment2;
  protected $AmountRA_Payment3;
  protected $AmountRA_Payment4;
  protected $NoRA;
}

class DailyRARes extends BaseResClass {
  protected $AmountPayment0;
  protected $AmountPayment1;
  protected $AmountPayment2;
  protected $AmountPayment3;
  protected $AmountPayment4;
  protected $AmountPayment5;
  protected $AmountPayment6;
  protected $AmountPayment7;
  protected $AmountPayment8;
  protected $AmountPayment9;
  protected $AmountPayment10;
  protected $AmountPayment11;
  protected $RANum;
  protected $SumAllPayment;
}

class GeneralDailyRegistersRes extends BaseResClass {
  protected $CustomersNum;
  protected $DiscountsNum;
  protected $DiscountsAmount;
  protected $AdditionsNum;
  protected $AdditionsAmount;
  protected $CorrectionsNum;
  protected $CorrectionsAmount;
}

class DailyAvailableAmounts_OldRes extends BaseResClass {
  protected $AmountPayment0;
  protected $AmountPayment1;
  protected $AmountPayment2;
  protected $AmountPayment3;
  protected $AmountPayment4;
}

class GrandFiscalSalesAndStornoAmountsRes extends BaseResClass {
  protected $GrandFiscalTurnover;
  protected $GrandFiscalVAT;
  protected $GrandFiscalStornoTurnover;
  protected $GrandFiscalStornoVAT;
}

class ParametersRes extends BaseResClass {
  protected $POSNum;
  protected $OptionPrintLogo;
  protected $OptionAutoOpenDrawer;
  protected $OptionAutoCut;
  protected $OptionExternalDispManagement;
  protected $OptionArticleReportType;
  protected $OptionEnableCurrency;
  protected $OptionEJFontType;
  protected $OptionWorkOperatorCount;
}

class VersionRes extends BaseResClass {
  protected $OptionDeviceType;
  protected $CertificateNum;
  protected $CertificateDateTime;
  protected $Model;
  protected $Version;
}

class DailyReturnedChangeAmountsByOperatorRes extends BaseResClass {
  protected $OperNum;
  protected $ChangeAmountPayment0;
  protected $ChangeAmountPayment1;
  protected $ChangeAmountPayment2;
  protected $ChangeAmountPayment3;
  protected $ChangeAmountPayment4;
  protected $ChangeAmountPayment5;
  protected $ChangeAmountPayment6;
  protected $ChangeAmountPayment7;
  protected $ChangeAmountPayment8;
  protected $ChangeAmountPayment9;
  protected $ChangeAmountPayment10;
  protected $ChangeAmountPayment11;
}

class DailyPORes extends BaseResClass {
  protected $AmountPayment0;
  protected $AmountPayment1;
  protected $AmountPayment2;
  protected $AmountPayment3;
  protected $AmountPayment4;
  protected $AmountPayment5;
  protected $AmountPayment6;
  protected $AmountPayment7;
  protected $AmountPayment8;
  protected $AmountPayment9;
  protected $AmountPayment10;
  protected $AmountPayment11;
  protected $PONum;
  protected $SumAllPayment;
}

class FP extends FP_Core {
  function __construct() {
    $this->timeStamp = 1908131009;
  }
  /**
   * Provides information about the amounts on hand by type of payment.
   * @return DailyAvailableAmountsRes
   */
  public function ReadDailyAvailableAmounts() {
    return new \Tremol\DailyAvailableAmountsRes($this->execute("ReadDailyAvailableAmounts"));
  }
  
  /**
   * Provides information about the PO amounts by type of payment and the total number of operations. Command works for KL version 2 devices.
   * @return DailyPO_OldRes
   */
  public function ReadDailyPO_Old() {
    return new \Tremol\DailyPO_OldRes($this->execute("ReadDailyPO_Old"));
  }
  
  /**
   * Prints an article report with or without zeroing ('Z' or 'X').
   * @param OptionZeroing $OptionZeroing with following values: 
   *  - 'Z' - Zeroing 
   *  - 'X' - Without zeroing
   */
  public function PrintArticleReport($OptionZeroing) {
    $this->execute("PrintArticleReport", "OptionZeroing", $OptionZeroing);
  }
  
  /**
   * Provides information about the current (the last value stored into the FM) decimal point format.
   * @return OptionDecimalPointPosition 1 symbol with values: 
   *  - '0'- Whole numbers 
   *  - '2' - Fractions
   */
  public function ReadDecimalPoint() {
    return $this->execute("ReadDecimalPoint");
  }
  
  /**
   * Starts session for reading electronic receipt by number with Base64 encoded BMP QR code.
   * @param double $RcpNum 6 symbols with format ######
   */
  public function ReadElectronicReceipt_QR_BMP($RcpNum) {
    $this->execute("ReadElectronicReceipt_QR_BMP", "RcpNum", $RcpNum);
  }
  
  /**
   * Programs the number of POS, printing of logo, cash drawer opening, cutting permission, external display management mode, article report type, enable or disable currency in receipt, EJ font type and working operators counter.
   * @param double $POSNum 4 symbols for number of POS in format ####
   * @param OptionPrintLogo $OptionPrintLogo 1 symbol of value: 
   *  - '1' - Yes 
   *  - '0' - No
   * @param OptionAutoOpenDrawer $OptionAutoOpenDrawer 1 symbol of value: 
   *  - '1' - Yes 
   *  - '0' - No
   * @param OptionAutoCut $OptionAutoCut 1 symbol of value: 
   *  - '1' - Yes 
   *  - '0' - No
   * @param OptionExternalDispManagement $OptionExternalDispManagement 1 symbol of value: 
   *  - '1' - Manual 
   *  - '0' - Auto
   * @param OptionArticleReportType $OptionArticleReportType 1 symbol of value: 
   *  - '1' - Detailed 
   *  - '0' - Brief
   * @param OptionEnableCurrency $OptionEnableCurrency 1 symbol of value: 
   *  - '1' - Yes 
   *  - '0' - No
   * @param OptionEJFontType $OptionEJFontType 1 symbol of value: 
   *  - '1' - Low Font 
   *  - '0' - Normal Font
   * @param OptionWorkOperatorCount $OptionWorkOperatorCount 1 symbol of value: 
   *  - '1' - One 
   *  - '0' - More
   */
  public function ProgParameters($POSNum,$OptionPrintLogo,$OptionAutoOpenDrawer,$OptionAutoCut,$OptionExternalDispManagement,$OptionArticleReportType,$OptionEnableCurrency,$OptionEJFontType,$OptionWorkOperatorCount) {
    $this->execute("ProgParameters", "POSNum", $POSNum, "OptionPrintLogo", $OptionPrintLogo, "OptionAutoOpenDrawer", $OptionAutoOpenDrawer, "OptionAutoCut", $OptionAutoCut, "OptionExternalDispManagement", $OptionExternalDispManagement, "OptionArticleReportType", $OptionArticleReportType, "OptionEnableCurrency", $OptionEnableCurrency, "OptionEJFontType", $OptionEJFontType, "OptionWorkOperatorCount", $OptionWorkOperatorCount);
  }
  
  /**
   * Provides information about last sent receipt number and date time to NRA server and first unsent receipt number and date time to NRA. If there is no unsent receipt the number will be 0 and date time will be 00-00-0000 00:00 Parameter NRA_ErrorMessage provide error message from NRA server if exist. Command is not allowed if device is deregistered, not fiscalized or in opened receipt.
   * @return SentRcpInfoStatusRes
   */
  public function ReadSentRcpInfoStatus() {
    return new \Tremol\SentRcpInfoStatusRes($this->execute("ReadSentRcpInfoStatus"));
  }
  
  /**
   * Print Electronic Journal Report from receipt number to receipt number and selected documents content. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.
   * @param string $FlagsReceipts 1 symbol for Receipts included in EJ: 
   * Flags.7=0 
   * Flags.6=1 
   * Flags.5=1 Yes, Flags.5=0 No (Include PO) 
   * Flags.4=1 Yes, Flags.4=0 No (Include RA) 
   * Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) 
   * Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) 
   * Flags.1=1 Yes, Flags.1=0 No (Include Invoice) 
   * Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)
   * @param string $FlagsReports 1 symbol for Reports included in EJ: 
   * Flags.7=0 
   * Flags.6=1 
   * Flags.5=0 
   * Flags.4=1 Yes, Flags.4=0 No (Include FM reports) 
   * Flags.3=1 Yes, Flags.3=0 No (Include Other reports) 
   * Flags.2=1 Yes, Flags.2=0 No (Include Daily X) 
   * Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) 
   * Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)
   * @param double $StartRcpNum 6 symbols for initial receipt number included in report in format ######.
   * @param double $EndRcpNum 6 symbols for final receipt number included in report in format ######.
   */
  public function PrintEJByRcpNumCustom($FlagsReceipts,$FlagsReports,$StartRcpNum,$EndRcpNum) {
    $this->execute("PrintEJByRcpNumCustom", "FlagsReceipts", $FlagsReceipts, "FlagsReports", $FlagsReports, "StartRcpNum", $StartRcpNum, "EndRcpNum", $EndRcpNum);
  }
  
  /**
   * Provides information for the programmed data, the turnover from the stated department number
   * @param double $DepNum 2 symbols for department number in format: ##
   * @return DepartmentRes
   */
  public function ReadDepartment($DepNum) {
    return new \Tremol\DepartmentRes($this->execute("ReadDepartment", "DepNum", $DepNum));
  }
  
  /**
   * Opens an electronic fiscal invoice receipt with 1 minute timeout assigned to the specified operator number and operator password with free info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.
   * @param double $OperNum Symbol from 1 to 20 corresponding to operator's number
   * @param string $OperPass 6 symbols for operator's password
   * @param string $Recipient 26 symbols for Invoice recipient
   * @param string $Buyer 16 symbols for Invoice buyer
   * @param string $VATNumber 13 symbols for customer Fiscal number
   * @param string $UIC 13 symbols for customer Unique Identification Code
   * @param string $Address 30 symbols for Address
   * @param OptionUICType $OptionUICType 1 symbol for type of Unique Identification Code:  
   *  - '0' - Bulstat 
   *  - '1' - EGN 
   *  - '2' - Foreigner Number 
   *  - '3' - NRA Official Number
   * @param string $UniqueReceiptNumber Up to 24 symbols for unique receipt number. 
   * NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: 
   * * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, 
   * * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, 
   * * YYYYYYY - 7 symbols [0-9] for next number of the receipt
   */
  public function OpenElectronicInvoiceWithFreeCustomerData($OperNum,$OperPass,$Recipient,$Buyer,$VATNumber,$UIC,$Address,$OptionUICType,$UniqueReceiptNumber=NULL) {
    $this->execute("OpenElectronicInvoiceWithFreeCustomerData", "OperNum", $OperNum, "OperPass", $OperPass, "Recipient", $Recipient, "Buyer", $Buyer, "VATNumber", $VATNumber, "UIC", $UIC, "Address", $Address, "OptionUICType", $OptionUICType, "UniqueReceiptNumber", $UniqueReceiptNumber);
  }
  
  /**
   * Read the total counter of last issued receipt.
   * @return double 6 symbols for the total receipt counter in format ######  
   * up to current last issued receipt by FD
   */
  public function ReadLastReceiptNum() {
    return $this->execute("ReadLastReceiptNum");
  }
  
  /**
   * Stores the Unique Identification Code (UIC) and UIC type into the operative memory.
   * @param string $Password 6-symbols string
   * @param string $UIC 13 symbols for UIC
   * @param OptionUICType $OptionUICType 1 symbol for type of UIC number:  
   *  - '0' - Bulstat 
   *  - '1' - EGN 
   *  - '2' - Foreigner Number 
   *  - '3' - NRA Official Number
   */
  public function SetCustomerUIC($Password,$UIC,$OptionUICType) {
    $this->execute("SetCustomerUIC", "Password", $Password, "UIC", $UIC, "OptionUICType", $OptionUICType);
  }
  
  /**
   * Read Electronic Journal Report from receipt number to receipt number.
   * @param OptionReportFormat $OptionReportFormat 1 character with value 
   *  - 'J0' - Detailed EJ 
   *  - 'J8' - Brief EJ
   * @param double $StartRcpNum 6 symbols for initial receipt number included in report in format ######
   * @param double $EndRcpNum 6 symbols for final receipt number included in report in format ######
   */
  public function ReadEJByReceiptNum($OptionReportFormat,$StartRcpNum,$EndRcpNum) {
    $this->execute("ReadEJByReceiptNum", "OptionReportFormat", $OptionReportFormat, "StartRcpNum", $StartRcpNum, "EndRcpNum", $EndRcpNum);
  }
  
  /**
   * Programs the general data for a certain article in the internal database. The price may have variable length, while the name field is fixed.
   * @param double $PLUNum 5 symbols for article number in format: #####
   * @param string $Name 34 symbols for article name
   * @param double $Price Up to 10 symbols for article price
   * @param OptionPrice $OptionPrice 1 symbol for price flag with next value: 
   *  - '0'- Free price is disable valid only programmed price 
   *  - '1'- Free price is enable 
   *  - '2'- Limited price
   * @param OptionVATClass $OptionVATClass 1 character for VAT class: 
   *  - 'А' - VAT Class 0 
   *  - 'Б' - VAT Class 1 
   *  - 'В' - VAT Class 2 
   *  - 'Г' - VAT Class 3 
   *  - 'Д' - VAT Class 4 
   *  - 'Е' - VAT Class 5 
   *  - 'Ж' - VAT Class 6 
   *  - 'З' - VAT Class 7 
   *  - '*' - Forbidden
   * @param double $BelongToDepNum BelongToDepNum + 80h, 1 symbol for article 
   * department attachment, formed in the following manner: 
   * BelongToDepNum[HEX] + 80h example: Dep01 = 81h, Dep02 = 82h … 
   * Dep19 = 93h
   * @param OptionSingleTransaction $OptionSingleTransaction 1 symbol with value: 
   *  - '0' - Inactive, default value 
   *  - '1' - Active Single transaction in receipt
   */
  public function ProgPLUgeneral($PLUNum,$Name,$Price,$OptionPrice,$OptionVATClass,$BelongToDepNum,$OptionSingleTransaction) {
    $this->execute("ProgPLUgeneral", "PLUNum", $PLUNum, "Name", $Name, "Price", $Price, "OptionPrice", $OptionPrice, "OptionVATClass", $OptionVATClass, "BelongToDepNum", $BelongToDepNum, "OptionSingleTransaction", $OptionSingleTransaction);
  }
  
  /**
   * Percent or value discount/addition over sum of transaction or over subtotal sum specified by field "Type".
   * @param OptionType $OptionType 1 symbol with value  
   * - '2' - Defined from the device  
   * - '1' - Over subtotal 
   * - '0' - Over transaction sum
   * @param OptionSubtotal $OptionSubtotal 1 symbol with value  
   *  - '1' - Yes  
   *  - '0' - No
   * @param double $DiscAddV Up to 8 symbols for the value of the discount/addition. 
   * Use minus sign '-' for discount
   * @param double $DiscAddP Up to 7 symbols for the percentage value of the 
   * discount/addition. Use minus sign '-' for discount
   */
  public function PrintDiscountOrAddition($OptionType,$OptionSubtotal,$DiscAddV=NULL,$DiscAddP=NULL) {
    $this->execute("PrintDiscountOrAddition", "OptionType", $OptionType, "OptionSubtotal", $OptionSubtotal, "DiscAddV", $DiscAddV, "DiscAddP", $DiscAddP);
  }
  
  /**
   * Preprogram the name of the type of payment. Command works for KL version 2 devices.
   * @param OptionNumber $OptionNumber 1 symbol for payment type  
   *  - '1' - Payment 1 
   *  - '2' - Payment 2 
   *  - '3' - Payment 3 
   *  - '4' - Payment 4
   * @param string $Name 10 symbols for payment type name
   * @param double $Rate Up to 10 symbols for exchange rate in format: ####.##### 
   * of the 4th payment type, maximal value 0420.00000
   * @param OptionCodePayment $OptionCodePayment 1 symbol for code payment type with name: 
   *  - '1' - Check  
   *  - '2' - Talon 
   *  - '3' - V. Talon 
   *  - '4' - Packaging 
   *  - '5' - Service 
   *  - '6' - Damage 
   *  - '7' - Card 
   *  - '8' - Bank 
   *  - '9' - Programming Name1 
   *  - ':' - Programming Name2
   */
  public function ProgPayment_Old($OptionNumber,$Name,$Rate=NULL,$OptionCodePayment=NULL) {
    $this->execute("ProgPayment_Old", "OptionNumber", $OptionNumber, "Name", $Name, "Rate", $Rate, "OptionCodePayment", $OptionCodePayment);
  }
  
  /**
   * Print or store Electronic Journal report with all documents.
   * @param OptionReportStorage $OptionReportStorage 1 character with value: 
   *  - 'J1' - Printing 
   *  - 'J2' - USB storage 
   *  - 'J4' - SD card storage
   */
  public function PrintOrStoreEJ($OptionReportStorage) {
    $this->execute("PrintOrStoreEJ", "OptionReportStorage", $OptionReportStorage);
  }
  
  /**
   * Opens the cash drawer.
   */
  public function CashDrawerOpen() {
    $this->execute("CashDrawerOpen");
  }
  
  /**
   * Provides information about the registers of the specified article.
   * @param double $PLUNum 5 symbols for article number in format #####
   * @return PLU_OldRes
   */
  public function ReadPLU_Old($PLUNum) {
    return new \Tremol\PLU_OldRes($this->execute("ReadPLU_Old", "PLUNum", $PLUNum));
  }
  
  /**
   * Print a detailed FM payments report by initial and end FM report number.
   * @param double $StartZNum 4 symbols for initial FM report number included in report, format ####
   * @param double $EndZNum 4 symbols for final FM report number included in report, format ####
   */
  public function PrintDetailedFMPaymentsReportByZBlocks($StartZNum,$EndZNum) {
    $this->execute("PrintDetailedFMPaymentsReportByZBlocks", "StartZNum", $StartZNum, "EndZNum", $EndZNum);
  }
  
  /**
   * Shows a 20-symbols text in the first line and last 20-symbols text in the second line of the external display lines.
   * @param string $Text 40 symbols text
   */
  public function DisplayTextLines1and2($Text) {
    $this->execute("DisplayTextLines1and2", "Text", $Text);
  }
  
  /**
   * Register the sell or correction with specified quantity of article from the internal FD database. The FD will perform a correction operation only if the same quantity of the article has already been sold.
   * @param OptionSign $OptionSign 1 symbol with optional value: 
   *  - '+' -Sale 
   *  - '-' - Correction
   * @param double $PLUNum 5 symbols for PLU number of FD's database in format #####
   * @param double $Price Up to 10 symbols for sale price
   * @param double $Quantity Up to 10 symbols for article's quantity sold
   * @param double $DiscAddP Up to 7 for percentage of discount/addition. Use minus 
   * sign '-' for discount
   * @param double $DiscAddV Up to 8 symbolsfor percentage of discount/addition. 
   * Use minus sign '-' for discount
   */
  public function SellPLUFromFD_DB($OptionSign,$PLUNum,$Price=NULL,$Quantity=NULL,$DiscAddP=NULL,$DiscAddV=NULL) {
    $this->execute("SellPLUFromFD_DB", "OptionSign", $OptionSign, "PLUNum", $PLUNum, "Price", $Price, "Quantity", $Quantity, "DiscAddP", $DiscAddP, "DiscAddV", $DiscAddV);
  }
  
  /**
   * Provides information about the current date and time.
   * @return DateTime Date Time parameter in format: DD-MM-YYYY HH:MM
   */
  public function ReadDateTime() {
    return $this->execute("ReadDateTime");
  }
  
  /**
   * Register the payment in the receipt with specified type of payment and exact amount received.
   * @param OptionPaymentType $OptionPaymentType 1 symbol for payment type: 
   *  - '0' - Payment 0 
   *  - '1' - Payment 1 
   *  - '2' - Payment 2 
   *  - '3' - Payment 3 
   *  - '4' - Payment 4 
   *  - '5' - Payment 5 
   *  - '6' - Payment 6 
   *  - '7' - Payment 7 
   *  - '8' - Payment 8 
   *  - '9' - Payment 9 
   *  - '10' - Payment 10 
   *  - '11' - Payment 11
   */
  public function PayExactSum($OptionPaymentType) {
    $this->execute("PayExactSum", "OptionPaymentType", $OptionPaymentType);
  }
  
  /**
   * Read the number of the remaining free records for Z-report in the Fiscal Memory.
   * @return string 4 symbols for the number of free records for Z-report in the FM
   */
  public function ReadFMfreeRecords() {
    return $this->execute("ReadFMfreeRecords");
  }
  
  /**
   * Available only if receipt is not closed. Void all sales in the receipt and close the fiscal receipt (Fiscal receipt, Invoice receipt, Storno receipt or Credit Note). If payment is started, then finish payment and close the receipt.
   */
  public function CancelReceipt() {
    $this->execute("CancelReceipt");
  }
  
  /**
   * Register the sell (for correction use minus sign in the price field) of article belonging to department with specified name, price, quantity and/or discount/addition on the transaction. The VAT of article got from department to which article belongs.
   * @param string $NamePLU 36 symbols for article's name. 34 symbols are printed on paper. 
   * Symbol 0x7C '|' is new line separator.
   * @param double $Price Up to 10 symbols for article's price. Use minus sign '-' for correction
   * @param double $Quantity Up to 10 symbols for quantity
   * @param double $DiscAddP Up to 7 symbols for percentage of discount/addition. 
   * Use minus sign '-' for discount
   * @param double $DiscAddV Up to 8 symbols for value of discount/addition. 
   * Use minus sign '-' for discount
   * @param double $DepNum 1 symbol for article department 
   * attachment, formed in the following manner; example: Dep01=81h, 
   * Dep02=82h … Dep19=93h
   */
  public function SellPLUfromDep($NamePLU,$Price,$Quantity=NULL,$DiscAddP=NULL,$DiscAddV=NULL,$DepNum=NULL) {
    $this->execute("SellPLUfromDep", "NamePLU", $NamePLU, "Price", $Price, "Quantity", $Quantity, "DiscAddP", $DiscAddP, "DiscAddV", $DiscAddV, "DepNum", $DepNum);
  }
  
  /**
   * Provide information about invoice start and end numbers range.
   * @return InvoiceRangeRes
   */
  public function ReadInvoiceRange() {
    return new \Tremol\InvoiceRangeRes($this->execute("ReadInvoiceRange"));
  }
  
  /**
   * Print whole special FM events report.
   */
  public function PrintSpecialEventsFMreport() {
    $this->execute("PrintSpecialEventsFMreport");
  }
  
  /**
   * Open a fiscal storno receipt assigned to the specified operator number and operator password, parameters for receipt format, print VAT, printing type and parameters for the related storno receipt.
   * @param double $OperNum Symbols from 1 to 20 corresponding to operator's 
   * number
   * @param string $OperPass 6 symbols for operator's password
   * @param OptionReceiptFormat $OptionReceiptFormat 1 symbol with value: 
   *  - '1' - Detailed 
   *  - '0' - Brief
   * @param OptionPrintVAT $OptionPrintVAT 1 symbol with value:  
   *  - '1' - Yes 
   *  - '0' - No
   * @param OptionStornoRcpPrintType $OptionStornoRcpPrintType 1 symbol with value: 
   * - '@' - Step by step printing 
   * - 'B' - Postponed Printing 
   * - 'D' - Buffered Printing
   * @param OptionStornoReason $OptionStornoReason 1 symbol for reason of storno operation with value:  
   * - '0' - Operator error  
   * - '1' - Goods Claim or Goods return  
   * - '2' - Tax relief
   * @param double $RelatedToRcpNum Up to 6 symbols for issued receipt number
   * @param DateTime $RelatedToRcpDateTime 17 symbols for Date and Time of the issued receipt 
   * in format DD-MM-YY HH:MM:SS
   * @param string $FMNum 8 symbols for number of the Fiscal Memory
   * @param string $RelatedToURN Up to 24 symbols for the issed receipt unique receipt number. 
   * NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: 
   * * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, 
   * * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, 
   * * YYYYYYY - 7 symbols [0-9] for next number of the receipt
   */
  public function OpenStornoReceipt($OperNum,$OperPass,$OptionReceiptFormat,$OptionPrintVAT,$OptionStornoRcpPrintType,$OptionStornoReason,$RelatedToRcpNum,$RelatedToRcpDateTime,$FMNum,$RelatedToURN=NULL) {
    $this->execute("OpenStornoReceipt", "OperNum", $OperNum, "OperPass", $OperPass, "OptionReceiptFormat", $OptionReceiptFormat, "OptionPrintVAT", $OptionPrintVAT, "OptionStornoRcpPrintType", $OptionStornoRcpPrintType, "OptionStornoReason", $OptionStornoReason, "RelatedToRcpNum", $RelatedToRcpNum, "RelatedToRcpDateTime", $RelatedToRcpDateTime, "FMNum", $FMNum, "RelatedToURN", $RelatedToURN);
  }
  
  /**
   * Programs the operator's name and password.
   * @param double $Number Symbols from '1' to '20' corresponding to operator's number
   * @param string $Name 20 symbols for operator's name
   * @param string $Password 6 symbols for operator's password
   */
  public function ProgOperator($Number,$Name,$Password) {
    $this->execute("ProgOperator", "Number", $Number, "Name", $Name, "Password", $Password);
  }
  
  /**
   * Provides information about all programmed types of payment. Command works for KL version 2 devices.
   * @return Payments_OldRes
   */
  public function ReadPayments_Old() {
    return new \Tremol\Payments_OldRes($this->execute("ReadPayments_Old"));
  }
  
  /**
   * Print Electronic Journal Report by initial and end date, and selected documents content. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.
   * @param string $FlagsReceipts 1 symbol for Receipts included in EJ: 
   * Flags.7=0 
   * Flags.6=1 
   * Flags.5=1 Yes, Flags.5=0 No (Include PO) 
   * Flags.4=1 Yes, Flags.4=0 No (Include RA) 
   * Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) 
   * Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) 
   * Flags.1=1 Yes, Flags.1=0 No (Include Invoice) 
   * Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)
   * @param string $FlagsReports 1 symbol for Reports included in EJ: 
   * Flags.7=0 
   * Flags.6=1 
   * Flags.5=0 
   * Flags.4=1 Yes, Flags.4=0 No (Include FM reports) 
   * Flags.3=1 Yes, Flags.3=0 No (Include Other reports) 
   * Flags.2=1 Yes, Flags.2=0 No (Include Daily X) 
   * Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) 
   * Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)
   * @param DateTime $StartRepFromDate 6 symbols for initial date in the DDMMYY format
   * @param DateTime $EndRepFromDate 6 symbols for final date in the DDMMYY format
   */
  public function PrintEJByDateCustom($FlagsReceipts,$FlagsReports,$StartRepFromDate,$EndRepFromDate) {
    $this->execute("PrintEJByDateCustom", "FlagsReceipts", $FlagsReceipts, "FlagsReports", $FlagsReports, "StartRepFromDate", $StartRepFromDate, "EndRepFromDate", $EndRepFromDate);
  }
  
  /**
   * Provides information about the quantity registers of the specified article.
   * @param double $PLUNum 5 symbols for article number with leading zeroes in format: #####
   * @return PLUqtyRes
   */
  public function ReadPLUqty($PLUNum) {
    return new \Tremol\PLUqtyRes($this->execute("ReadPLUqty", "PLUNum", $PLUNum));
  }
  
  /**
   * Provides information about the manufacturing number of the fiscal device and FM number.
   * @return SerialAndFiscalNumsRes
   */
  public function ReadSerialAndFiscalNums() {
    return new \Tremol\SerialAndFiscalNumsRes($this->execute("ReadSerialAndFiscalNums"));
  }
  
  /**
   * Registers cash received on account or paid out.
   * @param double $OperNum Symbols from 1 to 20 corresponding to the operator's 
   * number
   * @param string $OperPass 6 symbols for operator's password
   * @param double $Amount Up to 10 symbols for the amount lodged. Use minus sign for withdrawn
   * @param OptionPrintAvailability $OptionPrintAvailability 1 symbol with value: 
   *  - '0' - No 
   *  - '1' - Yes
   * @param string $Text TextLength-2 symbols. In the beginning and in the end of line symbol '#' 
   * is printed.
   */
  public function ReceivedOnAccount_PaidOut($OperNum,$OperPass,$Amount,$OptionPrintAvailability=NULL,$Text=NULL) {
    $this->execute("ReceivedOnAccount_PaidOut", "OperNum", $OperNum, "OperPass", $OperPass, "Amount", $Amount, "OptionPrintAvailability", $OptionPrintAvailability, "Text", $Text);
  }
  
  /**
   * Executes the direct command .
   * @param string $Input Raw request to FP
   * @return string FP raw response
   */
  public function DirectCommand($Input) {
    return $this->execute("DirectCommand", "Input", $Input);
  }
  
  /**
   * Reading Electronic Journal Report by number of Z report blocks.
   * @param OptionReportFormat $OptionReportFormat 1 character with value 
   *  - 'J0' - Detailed EJ 
   *  - 'J8' - Brief EJ
   * @param double $StartNo 4 symbols for initial number report in format ####
   * @param double $EndNo 4 symbols for final number report in format ####
   */
  public function ReadEJByZBlocks($OptionReportFormat,$StartNo,$EndNo) {
    $this->execute("ReadEJByZBlocks", "OptionReportFormat", $OptionReportFormat, "StartNo", $StartNo, "EndNo", $EndNo);
  }
  
  /**
   * Read the amounts returned as change by different payment types for the specified operator. Command works for KL version 2 devices.
   * @param double $OperNum Symbol from 1 to 20 corresponding to operator's number
   * @return DailyReturnedChangeAmountsByOperator_OldRes
   */
  public function ReadDailyReturnedChangeAmountsByOperator_Old($OperNum) {
    return new \Tremol\DailyReturnedChangeAmountsByOperator_OldRes($this->execute("ReadDailyReturnedChangeAmountsByOperator_Old", "OperNum", $OperNum));
  }
  
  /**
   * Provides information about the payments in current receipt. This command is valid after receipt closing also.
   * @return CurrentOrLastReceiptPaymentAmountsRes
   */
  public function ReadCurrentOrLastReceiptPaymentAmounts() {
    return new \Tremol\CurrentOrLastReceiptPaymentAmountsRes($this->execute("ReadCurrentOrLastReceiptPaymentAmounts"));
  }
  
  /**
   * Provides information about the amounts returned as change by type of payment.
   * @return DailyReturnedChangeAmountsRes
   */
  public function ReadDailyReturnedChangeAmounts() {
    return new \Tremol\DailyReturnedChangeAmountsRes($this->execute("ReadDailyReturnedChangeAmounts"));
  }
  
  /**
   * Read the amounts received from sales by type of payment and specified operator. Command works for KL version 2 devices.
   * @param double $OperNum Symbols from 1 to 20 corresponding to operator's 
   * number
   * @return DailyReceivedSalesAmountsByOperator_OldRes
   */
  public function ReadDailyReceivedSalesAmountsByOperator_Old($OperNum) {
    return new \Tremol\DailyReceivedSalesAmountsByOperator_OldRes($this->execute("ReadDailyReceivedSalesAmountsByOperator_Old", "OperNum", $OperNum));
  }
  
  /**
   * Stores in the memory the graphic file under stated number. Prints information about loaded in the printer graphic files.
   * @param string $LogoNumber 1 character value from '0' to '9' setting the number where the logo will be saved.
   */
  public function ProgLogoNum($LogoNumber) {
    $this->execute("ProgLogoNum", "LogoNumber", $LogoNumber);
  }
  
  /**
   * Provides information for the programmed data, the turnovers from the stated department number
   * @param double $DepNum 2 symbols for department number in format: ##
   * @return DepartmentAllRes
   */
  public function ReadDepartmentAll($DepNum) {
    return new \Tremol\DepartmentAllRes($this->execute("ReadDepartmentAll", "DepNum", $DepNum));
  }
  
  /**
   * Print a brief FM payments report by initial and end FM report number.
   * @param double $StartZNum 4 symbols for the initial FM report number included in report, format ####
   * @param double $EndZNum 4 symbols for the final FM report number included in report, format ####
   */
  public function PrintBriefFMPaymentsReportByZBlocks($StartZNum,$EndZNum) {
    $this->execute("PrintBriefFMPaymentsReportByZBlocks", "StartZNum", $StartZNum, "EndZNum", $EndZNum);
  }
  
  /**
   * Print a brief FM payments report by initial and end date.
   * @param DateTime $StartDate 6 symbols for initial date in the DDMMYY format
   * @param DateTime $EndDate 6 symbols for final date in the DDMMYY format
   */
  public function PrintBriefFMPaymentsReportByDate($StartDate,$EndDate) {
    $this->execute("PrintBriefFMPaymentsReportByDate", "StartDate", $StartDate, "EndDate", $EndDate);
  }
  
  /**
   * Program customer in FD data base.
   * @param double $CustomerNum 4 symbols for customer number in format ####
   * @param string $CustomerCompanyName 26 symbols for customer name
   * @param string $CustomerFullName 16 symbols for Buyer name
   * @param string $VATNumber 13 symbols for VAT number on customer
   * @param string $UIC 13 symbols for customer Unique Identification Code
   * @param string $Address 30 symbols for address on customer
   * @param OptionUICType $OptionUICType 1 symbol for type of Unique Identification Code:  
   *  - '0' - Bulstat 
   *  - '1' - EGN 
   *  - '2' - Foreigner Number 
   *  - '3' - NRA Official Number
   */
  public function ProgCustomerData($CustomerNum,$CustomerCompanyName,$CustomerFullName,$VATNumber,$UIC,$Address,$OptionUICType) {
    $this->execute("ProgCustomerData", "CustomerNum", $CustomerNum, "CustomerCompanyName", $CustomerCompanyName, "CustomerFullName", $CustomerFullName, "VATNumber", $VATNumber, "UIC", $UIC, "Address", $Address, "OptionUICType", $OptionUICType);
  }
  
  /**
   * Register the sell (for correction use minus sign in the price field) of article with specified name, price, quantity, VAT class and/or discount/addition on the transaction.
   * @param string $NamePLU 36 symbols for article's name. 34 symbols are printed on paper. 
   * Symbol 0x7C '|' is new line separator.
   * @param OptionVATClass $OptionVATClass 1 character for VAT class: 
   *  - 'А' - VAT Class 0 
   *  - 'Б' - VAT Class 1 
   *  - 'В' - VAT Class 2 
   *  - 'Г' - VAT Class 3 
   *  - 'Д' - VAT Class 4 
   *  - 'Е' - VAT Class 5 
   *  - 'Ж' - VAT Class 6 
   *  - 'З' - VAT Class 7 
   *  - '*' - Forbidden
   * @param double $Price Up to 10 symbols for article's price. Use minus sign '-' for correction
   * @param double $Quantity Up to 10 symbols for quantity
   * @param double $DiscAddP Up to 7 symbols for percentage of discount/addition. 
   * Use minus sign '-' for discount
   * @param double $DiscAddV Up to 8 symbols for value of discount/addition. 
   * Use minus sign '-' for discount
   */
  public function SellPLUwithSpecifiedVAT($NamePLU,$OptionVATClass,$Price,$Quantity=NULL,$DiscAddP=NULL,$DiscAddV=NULL) {
    $this->execute("SellPLUwithSpecifiedVAT", "NamePLU", $NamePLU, "OptionVATClass", $OptionVATClass, "Price", $Price, "Quantity", $Quantity, "DiscAddP", $DiscAddP, "DiscAddV", $DiscAddV);
  }
  
  /**
   * Print a brief FM report by initial and end date.
   * @param DateTime $StartDate 6 symbols for initial date in the DDMMYY format
   * @param DateTime $EndDate 6 symbols for final date in the DDMMYY format
   */
  public function PrintBriefFMReportByDate($StartDate,$EndDate) {
    $this->execute("PrintBriefFMReportByDate", "StartDate", $StartDate, "EndDate", $EndDate);
  }
  
  /**
   * Shows a 20-symbols text in the upper external display line.
   * @param string $Text 20 symbols text
   */
  public function DisplayTextLine1($Text) {
    $this->execute("DisplayTextLine1", "Text", $Text);
  }
  
  /**
   * Provides information about the current VAT rates which are the last values stored into the FM.
   * @return VATratesRes
   */
  public function ReadVATrates() {
    return new \Tremol\VATratesRes($this->execute("ReadVATrates"));
  }
  
  /**
   * Provides information about the amounts received from sales by type of payment.
   * @return DailyReceivedSalesAmountsRes
   */
  public function ReadDailyReceivedSalesAmounts() {
    return new \Tremol\DailyReceivedSalesAmountsRes($this->execute("ReadDailyReceivedSalesAmounts"));
  }
  
  /**
   * Programs available quantity and Quantiy type for a certain article in the internal database.
   * @param double $PLUNum 5 symbols for article number in format: #####
   * @param double $AvailableQuantity Up to 11 symbols for available quantity in stock
   * @param OptionQuantityType $OptionQuantityType 1 symbol for Quantity flag with next value:  
   *  - '0'- Availability of PLU stock is not monitored  
   *  - '1'- Disable negative quantity  
   *  - '2'- Enable negative quantity
   */
  public function ProgPLUqty($PLUNum,$AvailableQuantity,$OptionQuantityType) {
    $this->execute("ProgPLUqty", "PLUNum", $PLUNum, "AvailableQuantity", $AvailableQuantity, "OptionQuantityType", $OptionQuantityType);
  }
  
  /**
   * Provides information about the programmed VAT number, type of VAT number, register number in NRA and Date of registration in NRA.
   * @return RegistrationInfoRes
   */
  public function ReadRegistrationInfo() {
    return new \Tremol\RegistrationInfoRes($this->execute("ReadRegistrationInfo"));
  }
  
  /**
   * Clears the external display.
   */
  public function ClearDisplay() {
    $this->execute("ClearDisplay");
  }
  
  /**
   * Programs the data for a certain article (item) in the internal database. The price may have variable length, while the name field is fixed.
   * @param double $PLUNum 5 symbols for article number in format: #####
   * @param string $Name 20 symbols for article name
   * @param double $Price Up to 10 symbols for article price
   * @param OptionVATClass $OptionVATClass 1 character for VAT class: 
   *  - 'А' - VAT Class 0 
   *  - 'Б' - VAT Class 1 
   *  - 'В' - VAT Class 2 
   *  - 'Г' - VAT Class 3 
   *  - 'Д' - VAT Class 4 
   *  - 'Е' - VAT Class 5 
   *  - 'Ж' - VAT Class 6 
   *  - 'З' - VAT Class 7 
   *  - '*' - Forbidden
   * @param double $BelongToDepNum BelongToDepNum + 80h, 1 symbol for article 
   * department attachment, formed in the following manner:
   */
  public function ProgPLU_Old($PLUNum,$Name,$Price,$OptionVATClass,$BelongToDepNum) {
    $this->execute("ProgPLU_Old", "PLUNum", $PLUNum, "Name", $Name, "Price", $Price, "OptionVATClass", $OptionVATClass, "BelongToDepNum", $BelongToDepNum);
  }
  
  /**
   * Register the sell (for correction use minus sign in the price field) of article with specified name, price, fractional quantity, VAT class and/or discount/addition on the transaction.
   * @param string $NamePLU 36 symbols for article's name. 34 symbols are printed on paper. 
   * Symbol 0x7C '|' is new line separator.
   * @param OptionVATClass $OptionVATClass 1 character for VAT class: 
   *  - 'А' - VAT Class 0 
   *  - 'Б' - VAT Class 1 
   *  - 'В' - VAT Class 2 
   *  - 'Г' - VAT Class 3 
   *  - 'Д' - VAT Class 4 
   *  - 'Е' - VAT Class 5 
   *  - 'Ж' - VAT Class 6 
   *  - 'З' - VAT Class 7 
   *  - '*' - Forbidden
   * @param double $Price Up to 10 symbols for article's price. Use minus sign '-' for correction
   * @param string $Quantity From 3 to 10 symbols for quantity in format fractional format, e.g. 1/3
   * @param double $DiscAddP 1 to 7 symbols for percentage of discount/addition. Use 
   * minus sign '-' for discount
   * @param double $DiscAddV 1 to 8 symbols for value of discount/addition. Use 
   * minus sign '-' for discount
   */
  public function SellFractQtyPLUwithSpecifiedVAT($NamePLU,$OptionVATClass,$Price,$Quantity=NULL,$DiscAddP=NULL,$DiscAddV=NULL) {
    $this->execute("SellFractQtyPLUwithSpecifiedVAT", "NamePLU", $NamePLU, "OptionVATClass", $OptionVATClass, "Price", $Price, "Quantity", $Quantity, "DiscAddP", $DiscAddP, "DiscAddV", $DiscAddV);
  }
  
  /**
   * Starts session for reading electronic receipt by number with specified ASCII symbol for QR code block.
   * @param double $RcpNum 6 symbols with format ######
   * @param string $QRSymbol 1 symbol for QR code drawing image
   */
  public function ReadElectronicReceipt_QR_ASCII($RcpNum,$QRSymbol) {
    $this->execute("ReadElectronicReceipt_QR_ASCII", "RcpNum", $RcpNum, "QRSymbol", $QRSymbol);
  }
  
  /**
   * Read the PO by type of payment and the total number of operations by specified operator
   * @param double $OperNum Symbols from 1 to 20 corresponding to operator's number
   * @return DailyPObyOperatorRes
   */
  public function ReadDailyPObyOperator($OperNum) {
    return new \Tremol\DailyPObyOperatorRes($this->execute("ReadDailyPObyOperator", "OperNum", $OperNum));
  }
  
  /**
   * Opens an postponed electronic fiscal receipt with 1 minute timeout assigned to the specified operator number and operator password, parameters for receipt format, print VAT, printing type and unique receipt number.
   * @param double $OperNum Symbols from 1 to 20 corresponding to operator's number
   * @param string $OperPass 6 symbols for operator's password
   * @param OptionReceiptFormat $OptionReceiptFormat 1 symbol with value: 
   *  - '1' - Detailed 
   *  - '0' - Brief
   * @param OptionPrintVAT $OptionPrintVAT 1 symbol with value:  
   *  - '1' - Yes 
   *  - '0' - No
   * @param string $UniqueReceiptNumber Up to 24 symbols for unique receipt number. 
   * NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: 
   * * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, 
   * * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, 
   * * YYYYYYY - 7 symbols [0-9] for next number of the receipt
   */
  public function OpenElectronicReceipt($OperNum,$OperPass,$OptionReceiptFormat,$OptionPrintVAT,$UniqueReceiptNumber=NULL) {
    $this->execute("OpenElectronicReceipt", "OperNum", $OperNum, "OperPass", $OperPass, "OptionReceiptFormat", $OptionReceiptFormat, "OptionPrintVAT", $OptionPrintVAT, "UniqueReceiptNumber", $UniqueReceiptNumber);
  }
  
  /**
   * Program the content of the header UIC prefix.
   * @param string $HeaderUICprefix 12 symbols for header UIC prefix
   */
  public function ProgHeaderUICprefix($HeaderUICprefix) {
    $this->execute("ProgHeaderUICprefix", "HeaderUICprefix", $HeaderUICprefix);
  }
  
  /**
   * Programs price and price type for a certain article in the internal database.
   * @param double $PLUNum 5 symbols for article number in format: #####
   * @param double $Price Up to 10 symbols for article price
   * @param OptionPrice $OptionPrice 1 symbol for price flag with next value: 
   *  - '0'- Free price is disable valid only programmed price 
   *  - '1'- Free price is enable 
   *  - '2'- Limited price
   */
  public function ProgPLUprice($PLUNum,$Price,$OptionPrice) {
    $this->execute("ProgPLUprice", "PLUNum", $PLUNum, "Price", $Price, "OptionPrice", $OptionPrice);
  }
  
  /**
   * Provides information about the amounts received from sales by type of payment. Command works for KL version 2 devices.
   * @return DailyReceivedSalesAmounts_OldRes
   */
  public function ReadDailyReceivedSalesAmounts_Old() {
    return new \Tremol\DailyReceivedSalesAmounts_OldRes($this->execute("ReadDailyReceivedSalesAmounts_Old"));
  }
  
  /**
   *  Reads raw bytes from FP.
   * @param double $Count How many bytes to read if EndChar is not specified
   * @param string $EndChar The character marking the end of the data. If present Count parameter is ignored.
   * @return array FP raw response in BASE64 encoded string
   */
  public function RawRead($Count,$EndChar) {
    return $this->execute("RawRead", "Count", $Count, "EndChar", $EndChar);
  }
  
  /**
   * Provides information about the QR code data in last issued receipt.
   * @return string Up to 60 symbols for last issued receipt QR code data separated by 
   * symbol '*' in format: FM Number*Receipt Number*Receipt 
   * Date*Receipt Hour*Receipt Amount
   */
  public function ReadLastReceiptQRcodeData() {
    return $this->execute("ReadLastReceiptQRcodeData");
  }
  
  /**
   * Program the contents of a header lines.
   * @param OptionHeaderLine $OptionHeaderLine 1 symbol with value: 
   *  - '1' - Header 1 
   *  - '2' - Header 2 
   *  - '3' - Header 3 
   *  - '4' - Header 4 
   *  - '5' - Header 5 
   *  - '6' - Header 6 
   *  - '7' - Header 7
   * @param string $HeaderText TextLength symbols for header lines
   */
  public function ProgHeader($OptionHeaderLine,$HeaderText) {
    $this->execute("ProgHeader", "OptionHeaderLine", $OptionHeaderLine, "HeaderText", $HeaderText);
  }
  
  /**
   * Sets logo number, which is active and will be printed as logo in the receipt header. Print information about active number.
   * @param string $LogoNumber 1 character value from '0' to '9' or '?'. The number sets the active file, and 
   * the '?' invokes only printing of information
   */
  public function SetActiveLogoNum($LogoNumber) {
    $this->execute("SetActiveLogoNum", "LogoNumber", $LogoNumber);
  }
  
  /**
   * Closes the non-fiscal receipt.
   */
  public function CloseNonFiscalReceipt() {
    $this->execute("CloseNonFiscalReceipt");
  }
  
  /**
   * Stores in the memory the graphic file under number 0. Prints information  about loaded in the printer graphic files.
   * @param string $BMPfile *BMP file with fixed size 9022 bytes
   */
  public function ProgLogo($BMPfile) {
    $this->execute("ProgLogo", "BMPfile", $BMPfile);
  }
  
  /**
   * Shows the current date and time on the external display.
   */
  public function DisplayDateTime() {
    $this->execute("DisplayDateTime");
  }
  
  /**
   * Print or store Electronic Journal Report by initial and end date.
   * @param OptionReportStorage $OptionReportStorage 1 character with value: 
   *  - 'J1' - Printing 
   *  - 'J2' - USB storage 
   *  - 'J4' - SD card storage
   * @param DateTime $StartRepFromDate 6 symbols for initial date in the DDMMYY format
   * @param DateTime $EndRepFromDate 6 symbols for final date in the DDMMYY format
   */
  public function PrintOrStoreEJByDate($OptionReportStorage,$StartRepFromDate,$EndRepFromDate) {
    $this->execute("PrintOrStoreEJByDate", "OptionReportStorage", $OptionReportStorage, "StartRepFromDate", $StartRepFromDate, "EndRepFromDate", $EndRepFromDate);
  }
  
  /**
   * Provides information about the amounts returned as change by type of payment. Command works for KL version 2 devices.
   * @return DailyReturnedChangeAmounts_OldRes
   */
  public function ReadDailyReturnedChangeAmounts_Old() {
    return new \Tremol\DailyReturnedChangeAmounts_OldRes($this->execute("ReadDailyReturnedChangeAmounts_Old"));
  }
  
  /**
   * Print Electronic Journal Report by number of Z report blocks and selected documents content. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.
   * @param string $FlagsReceipts 1 symbol for Receipts included in EJ: 
   * Flags.7=0 
   * Flags.6=1 
   * Flags.5=1 Yes, Flags.5=0 No (Include PO) 
   * Flags.4=1 Yes, Flags.4=0 No (Include RA) 
   * Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) 
   * Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) 
   * Flags.1=1 Yes, Flags.1=0 No (Include Invoice) 
   * Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)
   * @param string $FlagsReports 1 symbol for Reports included in EJ: 
   * Flags.7=0 
   * Flags.6=1 
   * Flags.5=0 
   * Flags.4=1 Yes, Flags.4=0 No (Include FM reports) 
   * Flags.3=1 Yes, Flags.3=0 No (Include Other reports) 
   * Flags.2=1 Yes, Flags.2=0 No (Include Daily X) 
   * Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) 
   * Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)
   * @param double $StartZNum 4 symbols for initial number report in format ####
   * @param double $EndZNum 4 symbols for final number report in format ####
   */
  public function PrintEJByZBlocksCustom($FlagsReceipts,$FlagsReports,$StartZNum,$EndZNum) {
    $this->execute("PrintEJByZBlocksCustom", "FlagsReceipts", $FlagsReceipts, "FlagsReports", $FlagsReports, "StartZNum", $StartZNum, "EndZNum", $EndZNum);
  }
  
  /**
   * Feeds one line of paper.
   */
  public function PaperFeed() {
    $this->execute("PaperFeed");
  }
  
  /**
   * Close the fiscal receipt (Fiscal receipt, Invoice receipt, Storno receipt, Credit Note or Non-fical receipt). When the payment is finished.
   */
  public function CloseReceipt() {
    $this->execute("CloseReceipt");
  }
  
  /**
   * Provides information about the QR code data in specified number issued receipt.
   * @param double $RcpNum 6 symbols with format ######
   * @return string Up to 60 symbols for last issued receipt QR code data separated by 
   * symbol '*' in format: FM Number*Receipt Number*Receipt 
   * Date*Receipt Hour*Receipt Amount
   */
  public function ReadSpecifiedReceiptQRcodeData($RcpNum) {
    return $this->execute("ReadSpecifiedReceiptQRcodeData", "RcpNum", $RcpNum);
  }
  
  /**
   * Registers the sell (for correction use minus sign in the price field)  of article with specified department, name, price, quantity and/or discount/addition on  the transaction.
   * @param string $NamePLU 36 symbols for name of sale. 34 symbols are printed on 
   * paper. Symbol 0x7C '|' is new line separator.
   * @param double $DepNum 1 symbol for article department 
   * attachment, formed in the following manner: DepNum[HEX] + 80h 
   * example: Dep01 = 81h, Dep02 = 82h … Dep19 = 93h
   * @param double $Price Up to 10 symbols for article's price. Use minus sign '-' for correction
   * @param double $Quantity Up to 10symbols for article's quantity sold
   * @param double $DiscAddP Up to 7 for percentage of discount/addition. Use 
   * minus sign '-' for discount
   * @param double $DiscAddV Up to 8 symbols for percentage of 
   * discount/addition. Use minus sign '-' for discount
   */
  public function SellPLUfromDep_($NamePLU,$DepNum,$Price,$Quantity=NULL,$DiscAddP=NULL,$DiscAddV=NULL) {
    $this->execute("SellPLUfromDep_", "NamePLU", $NamePLU, "DepNum", $DepNum, "Price", $Price, "Quantity", $Quantity, "DiscAddP", $DiscAddP, "DiscAddV", $DiscAddV);
  }
  
  /**
   * Opens a fiscal invoice credit note receipt assigned to the specified operator number and operator password with free info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.
   * @param double $OperNum Symbol from 1 to 20 corresponding to operator's 
   * number
   * @param string $OperPass 6 symbols for operator's password
   * @param OptionInvoiceCreditNotePrintType $OptionInvoiceCreditNotePrintType 1 symbol with value: 
   * - 'A' - Step by step printing 
   * - 'C' - Postponed Printing 
   * - 'E' - Buffered Printing
   * @param string $Recipient 26 symbols for Invoice recipient
   * @param string $Buyer 16 symbols for Invoice buyer
   * @param string $VATNumber 13 symbols for customer Fiscal number
   * @param string $UIC 13 symbols for customer Unique Identification Code
   * @param string $Address 30 symbols for Address
   * @param OptionUICType $OptionUICType 1 symbol for type of Unique Identification Code:  
   *  - '0' - Bulstat 
   *  - '1' - EGN 
   *  - '2' - Foreigner Number 
   *  - '3' - NRA Official Number
   * @param OptionStornoReason $OptionStornoReason 1 symbol for reason of storno operation with value:  
   * - '0' - Operator error  
   * - '1' - Goods Claim or Goods return  
   * - '2' - Tax relief
   * @param string $RelatedToInvoiceNum 10 symbols for issued invoice number
   * @param DateTime $RelatedToInvoiceDateTime 17 symbols for issued invoice date and time in format
   * @param double $RelatedToRcpNum Up to 6 symbols for issued receipt number
   * @param string $FMNum 8 symbols for number of the Fiscal Memory
   * @param string $RelatedToURN Up to 24 symbols for the issed invoice receipt unique receipt number. 
   * NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: 
   * * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device 
   * number, 
   * * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, 
   * * YYYYYYY - 7 symbols [0-9] for next number of the receipt
   */
  public function OpenCreditNoteWithFreeCustomerData($OperNum,$OperPass,$OptionInvoiceCreditNotePrintType,$Recipient,$Buyer,$VATNumber,$UIC,$Address,$OptionUICType,$OptionStornoReason,$RelatedToInvoiceNum,$RelatedToInvoiceDateTime,$RelatedToRcpNum,$FMNum,$RelatedToURN=NULL) {
    $this->execute("OpenCreditNoteWithFreeCustomerData", "OperNum", $OperNum, "OperPass", $OperPass, "OptionInvoiceCreditNotePrintType", $OptionInvoiceCreditNotePrintType, "Recipient", $Recipient, "Buyer", $Buyer, "VATNumber", $VATNumber, "UIC", $UIC, "Address", $Address, "OptionUICType", $OptionUICType, "OptionStornoReason", $OptionStornoReason, "RelatedToInvoiceNum", $RelatedToInvoiceNum, "RelatedToInvoiceDateTime", $RelatedToInvoiceDateTime, "RelatedToRcpNum", $RelatedToRcpNum, "FMNum", $FMNum, "RelatedToURN", $RelatedToURN);
  }
  
  /**
   * Prints barcode from type stated by CodeType and CodeLen and with data stated in CodeData field. Command works only for fiscal printer devices. ECR does not support this command.
   * @param OptionCodeType $OptionCodeType 1 symbol with possible values: 
   *  - '0' - UPC A 
   *  - '1' - UPC E 
   *  - '2' - EAN 13 
   *  - '3' - EAN 8 
   *  - '4' - CODE 39 
   *  - '5' - ITF 
   *  - '6' - CODABAR 
   *  - 'H' - CODE 93 
   *  - 'I' - CODE 128
   * @param double $CodeLen Up to 2 bytes for number of bytes according to the table
   * @param string $CodeData Up to 100 bytes data in range according to the table
   */
  public function PrintBarcode($OptionCodeType,$CodeLen,$CodeData) {
    $this->execute("PrintBarcode", "OptionCodeType", $OptionCodeType, "CodeLen", $CodeLen, "CodeData", $CodeData);
  }
  
  /**
   * Provides information about the accumulated sale and storno amounts by VAT group.
   * @return DailySaleAndStornoAmountsByVATRes
   */
  public function ReadDailySaleAndStornoAmountsByVAT() {
    return new \Tremol\DailySaleAndStornoAmountsByVATRes($this->execute("ReadDailySaleAndStornoAmountsByVAT"));
  }
  
  /**
   * Print a department report with or without zeroing ('Z' or 'X').
   * @param OptionZeroing $OptionZeroing 1 symbol with value: 
   *  - 'Z' - Zeroing 
   *  - 'X' - Without zeroing
   */
  public function PrintDepartmentReport($OptionZeroing) {
    $this->execute("PrintDepartmentReport", "OptionZeroing", $OptionZeroing);
  }
  
  /**
   * Read or Store Electronic Journal report by CSV format option and document content selecting. If CSV format is set the content can includes only fiscal receipts. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.
   * @param OptionStorageReport $OptionStorageReport 1 character with value 
   *  - 'j0' - To PC 
   *  - 'j2' - To USB Flash Drive 
   *  - 'j4' - To SD card
   * @param OptionCSVformat $OptionCSVformat 1 symbol with value: 
   *  - 'C' - Yes 
   *  - 'X' - No
   * @param string $FlagsReceipts 1 symbol for Receipts included in EJ: 
   * Flags.7=0 
   * Flags.6=1 
   * Flags.5=1 Yes, Flags.5=0 No (Include PO) 
   * Flags.4=1 Yes, Flags.4=0 No (Include RA) 
   * Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) 
   * Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) 
   * Flags.1=1 Yes, Flags.1=0 No (Include Invoice) 
   * Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)
   * @param string $FlagsReports 1 symbol for Reports included in EJ: 
   * Flags.7=0 
   * Flags.6=1 
   * Flags.5=0 
   * Flags.4=1 Yes, Flags.4=0 No (Include FM reports) 
   * Flags.3=1 Yes, Flags.3=0 No (Include Other reports) 
   * Flags.2=1 Yes, Flags.2=0 No (Include Daily X) 
   * Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) 
   * Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)
   */
  public function ReadEJCustom($OptionStorageReport,$OptionCSVformat,$FlagsReceipts,$FlagsReports) {
    $this->execute("ReadEJCustom", "OptionStorageReport", $OptionStorageReport, "OptionCSVformat", $OptionCSVformat, "FlagsReceipts", $FlagsReceipts, "FlagsReports", $FlagsReports);
  }
  
  /**
   * Shows a 20-symbols text in the lower external display line.
   * @param string $Text 20 symbols text
   */
  public function DisplayTextLine2($Text) {
    $this->execute("DisplayTextLine2", "Text", $Text);
  }
  
  /**
   * Provides information about the current reading of the daily-report- with-zeroing counter, the number of the last block stored in FM, the number of EJ and the date and time of the last block storage in the FM.
   * @return DailyCountersRes
   */
  public function ReadDailyCounters() {
    return new \Tremol\DailyCountersRes($this->execute("ReadDailyCounters"));
  }
  
  /**
   * Print Electronic Journal report with selected documents content. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.
   * @param string $FlagsReceipts 1 symbol for Receipts included in EJ: 
   * Flags.7=0 
   * Flags.6=1 
   * Flags.5=1 Yes, Flags.5=0 No (Include PO) 
   * Flags.4=1 Yes, Flags.4=0 No (Include RA) 
   * Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) 
   * Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) 
   * Flags.1=1 Yes, Flags.1=0 No (Include Invoice) 
   * Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)
   * @param string $FlagsReports 1 symbol for Reports included in EJ: 
   * Flags.7=0 
   * Flags.6=1 
   * Flags.5=0 
   * Flags.4=1 Yes, Flags.4=0 No (Include FM reports) 
   * Flags.3=1 Yes, Flags.3=0 No (Include Other reports) 
   * Flags.2=1 Yes, Flags.2=0 No (Include Daily X) 
   * Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) 
   * Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)
   */
  public function PrintEJCustom($FlagsReceipts,$FlagsReports) {
    $this->execute("PrintEJCustom", "FlagsReceipts", $FlagsReceipts, "FlagsReports", $FlagsReports);
  }
  
  /**
   * Erase all articles in PLU database.
   * @param string $Password 6 symbols for password
   */
  public function EraseAllPLUs($Password) {
    $this->execute("EraseAllPLUs", "Password", $Password);
  }
  
  /**
   * Confirm Unique Identification Code (UIC) and UIC type into the operative memory.
   * @param string $Password 6-symbols string
   */
  public function ConfirmFiscalization($Password) {
    $this->execute("ConfirmFiscalization", "Password", $Password);
  }
  
  /**
   * Starts session for reading electronic receipt by number with its QR code data in the end.
   * @param double $RcpNum 6 symbols with format ######
   */
  public function ReadElectronicReceipt_QR_Data($RcpNum) {
    $this->execute("ReadElectronicReceipt_QR_Data", "RcpNum", $RcpNum);
  }
  
  /**
   * Read the RA by type of payment and the total number of operations by specified operator.
   * @param double $OperNum Symbols from 1 to 20 corresponding to operator's number
   * @return DailyRAbyOperatorRes
   */
  public function ReadDailyRAbyOperator($OperNum) {
    return new \Tremol\DailyRAbyOperatorRes($this->execute("ReadDailyRAbyOperator", "OperNum", $OperNum));
  }
  
  /**
   * Provide information about daily report parameter. If the parameter is set to 0 the status flag 4.6 will become 1 and the device will block all sales operation until daily report is printed. If the parameter is set to 1 the report will be generated automaticly without printout
   * @return OptionDailyReportSetting 1 symbol with value: 
   *  - '0' -Z report with printing 
   *  - '1' - Automatic Z report without printing
   */
  public function ReadDailyReportParameter() {
    return $this->execute("ReadDailyReportParameter");
  }
  
  /**
   * Provides information about daily turnover on the FD client display
   */
  public function DisplayDailyTurnover() {
    $this->execute("DisplayDailyTurnover");
  }
  
  /**
   * Provides the content of the header lines
   * @param OptionHeaderLine $OptionHeaderLine 1 symbol with value: 
   *  - '1' - Header 1 
   *  - '2' - Header 2 
   *  - '3' - Header 3 
   *  - '4' - Header 4 
   *  - '5' - Header 5 
   *  - '6' - Header 6 
   *  - '7' - Header 7
   * @return HeaderRes
   */
  public function ReadHeader($OptionHeaderLine) {
    return new \Tremol\HeaderRes($this->execute("ReadHeader", "OptionHeaderLine", $OptionHeaderLine));
  }
  
  /**
   * Start paper cutter. The command works only in fiscal printer devices.
   */
  public function CutPaper() {
    $this->execute("CutPaper");
  }
  
  /**
   * Set invoice start and end number range. To execute the command is necessary to grand following condition: the number range to be spent, not used, or not set after the last RAM reset.
   * @param double $StartNum 10 characters for start number in format: ##########
   * @param double $EndNum 10 characters for end number in format: ##########
   */
  public function SetInvoiceRange($StartNum,$EndNum) {
    $this->execute("SetInvoiceRange", "StartNum", $StartNum, "EndNum", $EndNum);
  }
  
  /**
   * Programs Barcode of article in the internal database.
   * @param double $PLUNum 5 symbols for article number in format: #####
   * @param string $Barcode 13 symbols for barcode
   */
  public function ProgPLUbarcode($PLUNum,$Barcode) {
    $this->execute("ProgPLUbarcode", "PLUNum", $PLUNum, "Barcode", $Barcode);
  }
  
  /**
   * Prints a detailed FM report by initial and end date.
   * @param DateTime $StartDate 6 symbols for initial date in the DDMMYY format
   * @param DateTime $EndDate 6 symbols for final date in the DDMMYY format
   */
  public function PrintDetailedFMReportByDate($StartDate,$EndDate) {
    $this->execute("PrintDetailedFMReportByDate", "StartDate", $StartDate, "EndDate", $EndDate);
  }
  
  /**
   * Print or store Electronic Journal Report from by number of Z report blocks.
   * @param OptionReportStorage $OptionReportStorage 1 character with value: 
   *  - 'J1' - Printing 
   *  - 'J2' - USB storage 
   *  - 'J4' - SD card storage
   * @param double $StartZNum 4 symbols for initial number report in format ####
   * @param double $EndZNum 4 symbols for final number report in format ####
   */
  public function PrintOrStoreEJByZBlocks($OptionReportStorage,$StartZNum,$EndZNum) {
    $this->execute("PrintOrStoreEJByZBlocks", "OptionReportStorage", $OptionReportStorage, "StartZNum", $StartZNum, "EndZNum", $EndZNum);
  }
  
  /**
   * Register the sell (for correction use minus sign in the price field) of article with specified VAT. If department is present the relevant accumulations are perfomed in its registers.
   * @param string $NamePLU 36 symbols for article's name. 34 symbols are printed on paper. 
   * Symbol 0x7C '|' is new line separator.
   * @param OptionVATClass $OptionVATClass 1 character for VAT class: 
   *  - 'А' - VAT Class 0 
   *  - 'Б' - VAT Class 1 
   *  - 'В' - VAT Class 2 
   *  - 'Г' - VAT Class 3 
   *  - 'Д' - VAT Class 4 
   *  - 'Е' - VAT Class 5 
   *  - 'Ж' - VAT Class 6 
   *  - 'З' - VAT Class 7 
   *  - '*' - Forbidden
   * @param double $Price Up to 10 symbols for article's price. Use minus sign '-' for correction
   * @param string $Quantity From 3 to 10 symbols for quantity in format fractional format, e.g. 1/3
   * @param double $DiscAddP Up to 7 symbols for percentage of discount/addition. 
   * Use minus sign '-' for discount
   * @param double $DiscAddV Up to 8 symbols for value of discount/addition. 
   * Use minus sign '-' for discount
   * @param double $DepNum 1 symbol for article department 
   * attachment, formed in the following manner; example: Dep01 = 81h, Dep02 
   * = 82h … Dep19 = 93h
   */
  public function SellFractQtyPLUwithSpecifiedVATfromDep($NamePLU,$OptionVATClass,$Price,$Quantity=NULL,$DiscAddP=NULL,$DiscAddV=NULL,$DepNum=NULL) {
    $this->execute("SellFractQtyPLUwithSpecifiedVATfromDep", "NamePLU", $NamePLU, "OptionVATClass", $OptionVATClass, "Price", $Price, "Quantity", $Quantity, "DiscAddP", $DiscAddP, "DiscAddV", $DiscAddV, "DepNum", $DepNum);
  }
  
  /**
   * Provides information about the general registers of the specified article.
   * @param double $PLUNum 5 symbols for article number with leading zeroes in format: #####
   * @return PLUgeneralRes
   */
  public function ReadPLUgeneral($PLUNum) {
    return new \Tremol\PLUgeneralRes($this->execute("ReadPLUgeneral", "PLUNum", $PLUNum));
  }
  
  /**
   * Read the amounts received from sales by type of payment and specified operator.
   * @param double $OperNum Symbols from 1 to 20 corresponding to operator's 
   * number
   * @return DailyReceivedSalesAmountsByOperatorRes
   */
  public function ReadDailyReceivedSalesAmountsByOperator($OperNum) {
    return new \Tremol\DailyReceivedSalesAmountsByOperatorRes($this->execute("ReadDailyReceivedSalesAmountsByOperator", "OperNum", $OperNum));
  }
  
  /**
   * Provide information for specified customer from FD data base.
   * @param double $CustomerNum 4 symbols for customer number in format ####
   * @return CustomerDataRes
   */
  public function ReadCustomerData($CustomerNum) {
    return new \Tremol\CustomerDataRes($this->execute("ReadCustomerData", "CustomerNum", $CustomerNum));
  }
  
  /**
   * Read the current status of the receipt.
   * @return CurrentReceiptInfoRes
   */
  public function ReadCurrentReceiptInfo() {
    return new \Tremol\CurrentReceiptInfoRes($this->execute("ReadCurrentReceiptInfo"));
  }
  
  /**
   * Opens a fiscal invoice receipt assigned to the specified operator number and operator password with internal DB info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.
   * @param double $OperNum Symbol from 1 to 20 corresponding to operator's 
   * number
   * @param string $OperPass 6 symbols for operator's password
   * @param OptionInvoicePrintType $OptionInvoicePrintType 1 symbol with value: 
   * - '1' - Step by step printing 
   * - '3' - Postponed Printing 
   * - '5' - Buffered Printing
   * @param string $CustomerNum Symbol '#' and following up to 4 symbols for related customer ID number 
   * corresponding to the FD database
   * @param string $UniqueReceiptNumber Up to 24 symbols for unique receipt number. 
   * NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: 
   * * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, 
   * * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, 
   * * YYYYYYY - 7 symbols [0-9] for next number of the receipt
   */
  public function OpenInvoiceWithFDCustomerDB($OperNum,$OperPass,$OptionInvoicePrintType,$CustomerNum,$UniqueReceiptNumber=NULL) {
    $this->execute("OpenInvoiceWithFDCustomerDB", "OperNum", $OperNum, "OperPass", $OperPass, "OptionInvoicePrintType", $OptionInvoicePrintType, "CustomerNum", $CustomerNum, "UniqueReceiptNumber", $UniqueReceiptNumber);
  }
  
  /**
   * Provides information about all the registers of the specified article.
   * @param double $PLUNum 5 symbols for article number with leading zeroes in format: #####
   * @return PLUallDataRes
   */
  public function ReadPLUallData($PLUNum) {
    return new \Tremol\PLUallDataRes($this->execute("ReadPLUallData", "PLUNum", $PLUNum));
  }
  
  /**
   * Read Electronic Journal report with all documents.
   * @param OptionReportFormat $OptionReportFormat 1 character with value 
   *  - 'J0' - Detailed EJ 
   *  - 'J8' - Brief EJ
   */
  public function ReadEJ($OptionReportFormat) {
    $this->execute("ReadEJ", "OptionReportFormat", $OptionReportFormat);
  }
  
  /**
   * Register the payment in the receipt with specified type of payment with amount received.
   * @param OptionPaymentType $OptionPaymentType 1 symbol for payment type: 
   *  - '0' - Payment 0 
   *  - '1' - Payment 1 
   *  - '2' - Payment 2 
   *  - '3' - Payment 3 
   *  - '4' - Payment 4 
   *  - '5' - Payment 5 
   *  - '6' - Payment 6 
   *  - '7' - Payment 7 
   *  - '8' - Payment 8 
   *  - '9' - Payment 9 
   *  - '10' - Payment 10 
   *  - '11' - Payment 11
   * @param OptionChange $OptionChange Default value is 0, 1 symbol with value: 
   *  - '0 - With Change 
   *  - '1' - Without Change
   * @param double $Amount Up to 10 characters for received amount
   * @param OptionChangeType $OptionChangeType 1 symbols with value: 
   *  - '0' - Change In Cash 
   *  - '1' - Same As The payment 
   *  - '2' - Change In Currency
   */
  public function Payment($OptionPaymentType,$OptionChange,$Amount,$OptionChangeType=NULL) {
    $this->execute("Payment", "OptionPaymentType", $OptionPaymentType, "OptionChange", $OptionChange, "Amount", $Amount, "OptionChangeType", $OptionChangeType);
  }
  
  /**
   * Provides consequently information about every single block stored in the FM starting with Acknowledgements and ending with end message.
   */
  public function ReadFMcontent() {
    $this->execute("ReadFMcontent");
  }
  
  /**
   * Read date and number of last Z-report and last RAM reset event.
   * @return LastDailyReportInfoRes
   */
  public function ReadLastDailyReportInfo() {
    return new \Tremol\LastDailyReportInfoRes($this->execute("ReadLastDailyReportInfo"));
  }
  
  /**
   * Print a free text. The command can be executed only if receipt is opened (Fiscal receipt, Invoice receipt, Storno receipt, Credit Note or Non-fical receipt). In the beginning and in the end of line symbol '#' is printed.
   * @param string $Text TextLength-2 symbols
   */
  public function PrintText($Text) {
    $this->execute("PrintText", "Text", $Text);
  }
  
  /**
   * Opens a fiscal invoice credit note receipt assigned to the specified operator number and operator password with internal DB info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.
   * @param double $OperNum Symbol from 1 to 20 corresponding to operator's 
   * number
   * @param string $OperPass 6 symbols for operator's password
   * @param OptionInvoiceCreditNotePrintType $OptionInvoiceCreditNotePrintType 1 symbol with value: 
   * - 'A' - Step by step printing 
   * - 'C' - Postponed Printing 
   * - 'E' - Buffered Printing
   * @param string $CustomerNum Symbol '#' and following up to 4 symbols for related customer ID 
   * number corresponding to the FD database
   * @param OptionStornoReason $OptionStornoReason 1 symbol for reason of storno operation with value:  
   * - '0' - Operator error  
   * - '1' - Goods Claim or Goods return  
   * - '2' - Tax relief
   * @param string $RelatedToInvoiceNum 10 symbols for issued invoice number
   * @param DateTime $RelatedToInvoiceDateTime 17 symbols for issued invoice date and time in format
   * @param double $RelatedToRcpNum Up to 6 symbols for issued receipt number
   * @param string $FMNum 8 symbols for number of the Fiscal Memory
   * @param string $RelatedToURN Up to 24 symbols for the issed invoice receipt unique receipt number. 
   * NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: 
   * * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, 
   * * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, 
   * * YYYYYYY - 7 symbols [0-9] for next number of the receipt
   */
  public function OpenCreditNoteWithFDCustomerDB($OperNum,$OperPass,$OptionInvoiceCreditNotePrintType,$CustomerNum,$OptionStornoReason,$RelatedToInvoiceNum,$RelatedToInvoiceDateTime,$RelatedToRcpNum,$FMNum,$RelatedToURN=NULL) {
    $this->execute("OpenCreditNoteWithFDCustomerDB", "OperNum", $OperNum, "OperPass", $OperPass, "OptionInvoiceCreditNotePrintType", $OptionInvoiceCreditNotePrintType, "CustomerNum", $CustomerNum, "OptionStornoReason", $OptionStornoReason, "RelatedToInvoiceNum", $RelatedToInvoiceNum, "RelatedToInvoiceDateTime", $RelatedToInvoiceDateTime, "RelatedToRcpNum", $RelatedToRcpNum, "FMNum", $FMNum, "RelatedToURN", $RelatedToURN);
  }
  
  /**
   * Prints an operator's report for a specified operator (0 = all operators) with or without zeroing ('Z' or 'X'). When a 'Z' value is specified the report should include all operators.
   * @param OptionZeroing $OptionZeroing with following values: 
   *  - 'Z' - Zeroing 
   *  - 'X' - Without zeroing
   * @param double $Number Symbols from 0 to 20 corresponding to operator's number 
   * ,0 for all operators
   */
  public function PrintOperatorReport($OptionZeroing,$Number) {
    $this->execute("PrintOperatorReport", "OptionZeroing", $OptionZeroing, "Number", $Number);
  }
  
  /**
   * Provides detailed 7-byte information about the current status of the fiscal printer.
   * @return StatusRes
   */
  public function ReadStatus() {
    return new \Tremol\StatusRes($this->execute("ReadStatus"));
  }
  
  /**
   * Opens a fiscal receipt assigned to the specified operator number and operator password, parameters for receipt format, print VAT, printing type and unique receipt number.
   * @param double $OperNum Symbols from 1 to 20 corresponding to operator's number
   * @param string $OperPass 6 symbols for operator's password
   * @param OptionReceiptFormat $OptionReceiptFormat 1 symbol with value: 
   *  - '1' - Detailed 
   *  - '0' - Brief
   * @param OptionPrintVAT $OptionPrintVAT 1 symbol with value:  
   *  - '1' - Yes 
   *  - '0' - No
   * @param OptionFiscalRcpPrintType $OptionFiscalRcpPrintType 1 symbol with value: 
   * - '0' - Step by step printing 
   * - '2' - Postponed printing 
   * - '4' - Buffered printing
   * @param string $UniqueReceiptNumber Up to 24 symbols for unique receipt number. 
   * NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: 
   * * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, 
   * * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, 
   * * YYYYYYY - 7 symbols [0-9] for next number of the receipt
   */
  public function OpenReceipt($OperNum,$OperPass,$OptionReceiptFormat,$OptionPrintVAT,$OptionFiscalRcpPrintType,$UniqueReceiptNumber=NULL) {
    $this->execute("OpenReceipt", "OperNum", $OperNum, "OperPass", $OperPass, "OptionReceiptFormat", $OptionReceiptFormat, "OptionPrintVAT", $OptionPrintVAT, "OptionFiscalRcpPrintType", $OptionFiscalRcpPrintType, "UniqueReceiptNumber", $UniqueReceiptNumber);
  }
  
  /**
   * Read or Store Electronic Journal Report by number of Z report blocks, CSV format option and document content. If CSV format is set the content can includes only fiscal receipts. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.
   * @param OptionStorageReport $OptionStorageReport 1 character with value 
   *  - 'j0' - To PC  
   * 
   *  - 'j2' - To USB Flash Drive 
   *  - 'j4' - To SD card
   * @param OptionCSVformat $OptionCSVformat 1 symbol with value: 
   *  - 'C' - Yes 
   *  - 'X' - No
   * @param string $FlagsReceipts 1 symbol for Receipts included in EJ: 
   * Flags.7=0 
   * Flags.6=1 
   * Flags.5=1 Yes, Flags.5=0 No (Include PO) 
   * Flags.4=1 Yes, Flags.4=0 No (Include RA) 
   * Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) 
   * Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) 
   * Flags.1=1 Yes, Flags.1=0 No (Include Invoice) 
   * Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)
   * @param string $FlagsReports 1 symbol for Reports included in EJ: 
   * Flags.7=0 
   * Flags.6=1 
   * Flags.5=0 
   * Flags.4=1 Yes, Flags.4=0 No (Include FM reports) 
   * Flags.3=1 Yes, Flags.3=0 No (Include Other reports) 
   * Flags.2=1 Yes, Flags.2=0 No (Include Daily X) 
   * Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) 
   * Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)
   * @param double $StartZNum 4 symbols for initial number report in format ####
   * @param double $EndZNum 4 symbols for final number report in format ####
   */
  public function ReadEJByZBlocksCustom($OptionStorageReport,$OptionCSVformat,$FlagsReceipts,$FlagsReports,$StartZNum,$EndZNum) {
    $this->execute("ReadEJByZBlocksCustom", "OptionStorageReport", $OptionStorageReport, "OptionCSVformat", $OptionCSVformat, "FlagsReceipts", $FlagsReceipts, "FlagsReports", $FlagsReports, "StartZNum", $StartZNum, "EndZNum", $EndZNum);
  }
  
  /**
   * Sets the date and time and prints out the current values.
   * @param DateTime $DateTime Date Time parameter in format: DD-MM-YY HH:MM:SS
   */
  public function SetDateTime($DateTime) {
    $this->execute("SetDateTime", "DateTime", $DateTime);
  }
  
  /**
   * Stores a block containing the number format into the fiscal memory. Print the current status on the printer.
   * @param string $Password 6-symbols string
   * @param OptionDecimalPointPosition $OptionDecimalPointPosition 1 symbol with values: 
   *  - '0'- Whole numbers 
   *  - '2' - Fractions
   */
  public function ProgDecimalPointPosition($Password,$OptionDecimalPointPosition) {
    $this->execute("ProgDecimalPointPosition", "Password", $Password, "OptionDecimalPointPosition", $OptionDecimalPointPosition);
  }
  
  /**
   * Provides information about electronic signature of last daily report.
   * @return string 40 symbols electronic signature
   */
  public function ReadLastDailySignature() {
    return $this->execute("ReadLastDailySignature");
  }
  
  /**
   * Provides information about the RA amounts by type of payment and the total number of operations. Command works for KL version 2 devices.
   * @return DailyRA_OldRes
   */
  public function ReadDailyRA_Old() {
    return new \Tremol\DailyRA_OldRes($this->execute("ReadDailyRA_Old"));
  }
  
  /**
   * Provides the content of the header UIC prefix.
   * @return string 12 symbols for Header UIC prefix
   */
  public function ReadHeaderUICPrefix() {
    return $this->execute("ReadHeaderUICPrefix");
  }
  
  /**
   * Provides information about the price and price type of the specified article.
   * @param double $PLUNum 5 symbols for article number with leading zeroes in format: #####
   * @return PLUpriceRes
   */
  public function ReadPLUprice($PLUNum) {
    return new \Tremol\PLUpriceRes($this->execute("ReadPLUprice", "PLUNum", $PLUNum));
  }
  
  /**
   * Provides information about operator's name and password.
   * @param double $Number Symbol from 1 to 20 corresponding to the number of 
   * operators.
   * @return OperatorNamePasswordRes
   */
  public function ReadOperatorNamePassword($Number) {
    return new \Tremol\OperatorNamePasswordRes($this->execute("ReadOperatorNamePassword", "Number", $Number));
  }
  
  /**
   * Read the last operator's report number and date and time.
   * @param double $OperNum Symbols from 1 to 20 corresponding to operator's 
   * number
   * @return DailyCountersByOperatorRes
   */
  public function ReadDailyCountersByOperator($OperNum) {
    return new \Tremol\DailyCountersByOperatorRes($this->execute("ReadDailyCountersByOperator", "OperNum", $OperNum));
  }
  
  /**
   * Provides information about all programmed types of payment, currency name and currency exchange rate.
   * @return PaymentsRes
   */
  public function ReadPayments() {
    return new \Tremol\PaymentsRes($this->execute("ReadPayments"));
  }
  
  /**
   * Register the sell (for correction use minus sign in the price field) of article with specified VAT. If department is present the relevant accumulations are perfomed in its registers.
   * @param string $NamePLU 36 symbols for article's name. 34 symbols are printed on paper. 
   * Symbol 0x7C '|' is new line separator.
   * @param OptionVATClass $OptionVATClass 1 character for VAT class: 
   *  - 'А' - VAT Class 0 
   *  - 'Б' - VAT Class 1 
   *  - 'В' - VAT Class 2 
   *  - 'Г' - VAT Class 3 
   *  - 'Д' - VAT Class 4 
   *  - 'Е' - VAT Class 5 
   *  - 'Ж' - VAT Class 6 
   *  - 'З' - VAT Class 7 
   *  - '*' - Forbidden
   * @param double $Price Up to 10 symbols for article's price. Use minus sign '-' for correction
   * @param double $Quantity Up to 10 symbols for quantity
   * @param double $DiscAddP Up to 7 symbols for percentage of discount/addition. 
   * Use minus sign '-' for discount
   * @param double $DiscAddV Up to 8 symbols for value of discount/addition. 
   * Use minus sign '-' for discount
   * @param double $DepNum 1 symbol for article department 
   * attachment, formed in the following manner; example: Dep01 = 81h,  
   * Dep02 = 82h … Dep19 = 93h
   */
  public function SellPLUwithSpecifiedVATfromDep($NamePLU,$OptionVATClass,$Price,$Quantity=NULL,$DiscAddP=NULL,$DiscAddV=NULL,$DepNum=NULL) {
    $this->execute("SellPLUwithSpecifiedVATfromDep", "NamePLU", $NamePLU, "OptionVATClass", $OptionVATClass, "Price", $Price, "Quantity", $Quantity, "DiscAddP", $DiscAddP, "DiscAddV", $DiscAddV, "DepNum", $DepNum);
  }
  
  /**
   * Preprogram the name of the payment type.
   * @param OptionPaymentNum $OptionPaymentNum 1 symbol for payment type  
   *  - '9' - Payment 9 
   *  - '10' - Payment 10 
   *  - '11' - Payment 11
   * @param string $Name 10 symbols for payment type name
   * @param double $Rate Up to 10 symbols for exchange rate in format: ####.#####  
   * of the 11th payment type, maximal value 0420.00000
   */
  public function ProgPayment($OptionPaymentNum,$Name,$Rate=NULL) {
    $this->execute("ProgPayment", "OptionPaymentNum", $OptionPaymentNum, "Name", $Name, "Rate", $Rate);
  }
  
  /**
   * Prints out a diagnostic receipt.
   */
  public function PrintDiagnostics() {
    $this->execute("PrintDiagnostics");
  }
  
  /**
   * Provides additional status information
   * @return DetailedPrinterStatusRes
   */
  public function ReadDetailedPrinterStatus() {
    return new \Tremol\DetailedPrinterStatusRes($this->execute("ReadDetailedPrinterStatus"));
  }
  
  /**
   * Opens a fiscal invoice receipt assigned to the specified operator number and operator password with free info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.
   * @param double $OperNum Symbol from 1 to 20 corresponding to operator's number
   * @param string $OperPass 6 symbols for operator's password
   * @param OptionInvoicePrintType $OptionInvoicePrintType 1 symbol with value: 
   * - '1' - Step by step printing 
   * - '3' - Postponed Printing 
   * - '5' - Buffered Printing
   * @param string $Recipient 26 symbols for Invoice recipient
   * @param string $Buyer 16 symbols for Invoice buyer
   * @param string $VATNumber 13 symbols for customer Fiscal number
   * @param string $UIC 13 symbols for customer Unique Identification Code
   * @param string $Address 30 symbols for Address
   * @param OptionUICType $OptionUICType 1 symbol for type of Unique Identification Code:  
   *  - '0' - Bulstat 
   *  - '1' - EGN 
   *  - '2' - Foreigner Number 
   *  - '3' - NRA Official Number
   * @param string $UniqueReceiptNumber Up to 24 symbols for unique receipt number. 
   * NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: 
   * * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, 
   * * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, 
   * * YYYYYYY - 7 symbols [0-9] for next number of the receipt
   */
  public function OpenInvoiceWithFreeCustomerData($OperNum,$OperPass,$OptionInvoicePrintType,$Recipient,$Buyer,$VATNumber,$UIC,$Address,$OptionUICType,$UniqueReceiptNumber=NULL) {
    $this->execute("OpenInvoiceWithFreeCustomerData", "OperNum", $OperNum, "OperPass", $OperPass, "OptionInvoicePrintType", $OptionInvoicePrintType, "Recipient", $Recipient, "Buyer", $Buyer, "VATNumber", $VATNumber, "UIC", $UIC, "Address", $Address, "OptionUICType", $OptionUICType, "UniqueReceiptNumber", $UniqueReceiptNumber);
  }
  
  /**
   * Program the contents of a footer lines.
   * @param string $FooterText TextLength symbols for footer line
   */
  public function ProgFooter($FooterText) {
    $this->execute("ProgFooter", "FooterText", $FooterText);
  }
  
  /**
   * Print a copy of the last receipt issued. When FD parameter for duplicates is enabled.
   */
  public function PrintLastReceiptDuplicate() {
    $this->execute("PrintLastReceiptDuplicate");
  }
  
  /**
   * Stores a block containing the values of the VAT rates into the fiscal memory. Print the values on the printer.
   * @param string $Password 6-symbols string
   * @param double $VATrate0 Value of VAT rate А from 6 symbols in format ##.##
   * @param double $VATrate1 Value of VAT rate Б from 6 symbols in format ##.##
   * @param double $VATrate2 Value of VAT rate В from 6 symbols in format ##.##
   * @param double $VATrate3 Value of VAT rate Г from 6 symbols in format ##.##
   * @param double $VATrate4 Value of VAT rate Д from 6 symbols in format ##.##
   * @param double $VATrate5 Value of VAT rate Е from 6 symbols in format ##.##
   * @param double $VATrate6 Value of VAT rate Ж from 6 symbols in format ##.##
   * @param double $VATrate7 Value of VAT rate З from 6 symbols in format ##.##
   */
  public function ProgVATrates($Password,$VATrate0,$VATrate1,$VATrate2,$VATrate3,$VATrate4,$VATrate5,$VATrate6,$VATrate7) {
    $this->execute("ProgVATrates", "Password", $Password, "VATrate0", $VATrate0, "VATrate1", $VATrate1, "VATrate2", $VATrate2, "VATrate3", $VATrate3, "VATrate4", $VATrate4, "VATrate5", $VATrate5, "VATrate6", $VATrate6, "VATrate7", $VATrate7);
  }
  
  /**
   * Read or Store Electronic Journal Report by initial to end date, CSV format option and document content. If CSV format is set the content can includes only fiscal receipts. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.
   * @param OptionStorageReport $OptionStorageReport 1 character with value 
   *  - 'j0' - To PC 
   *  - 'j2' - To USB Flash Drive 
   *  - 'j4' - To SD card
   * @param OptionCSVformat $OptionCSVformat 1 symbol with value: 
   *  - 'C' - Yes 
   *  - 'X' - No
   * @param string $FlagsReceipts 1 symbol for Receipts included in EJ: 
   * Flags.7=0 
   * Flags.6=1, 0=w 
   * Flags.5=1 Yes, Flags.5=0 No (Include PO) 
   * Flags.4=1 Yes, Flags.4=0 No (Include RA) 
   * Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) 
   * Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) 
   * Flags.1=1 Yes, Flags.1=0 No (Include Invoice) 
   * Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)
   * @param string $FlagsReports 1 symbol for Reports included in EJ: 
   * Flags.7=0 
   * Flags.6=1, 0=w 
   * Flags.5=1, 0=w 
   * Flags.4=1 Yes, Flags.4=0 No (Include FM reports) 
   * Flags.3=1 Yes, Flags.3=0 No (Include Other reports) 
   * Flags.2=1 Yes, Flags.2=0 No (Include Daily X) 
   * Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) 
   * Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)
   * @param DateTime $StartRepFromDate 6 symbols for initial date in the DDMMYY format
   * @param DateTime $EndRepFromDate 6 symbols for final date in the DDMMYY format
   */
  public function ReadEJByDateCustom($OptionStorageReport,$OptionCSVformat,$FlagsReceipts,$FlagsReports,$StartRepFromDate,$EndRepFromDate) {
    $this->execute("ReadEJByDateCustom", "OptionStorageReport", $OptionStorageReport, "OptionCSVformat", $OptionCSVformat, "FlagsReceipts", $FlagsReceipts, "FlagsReports", $FlagsReports, "StartRepFromDate", $StartRepFromDate, "EndRepFromDate", $EndRepFromDate);
  }
  
  /**
   * Print a brief FM report by initial and end FM report number.
   * @param double $StartZNum 4 symbols for the initial FM report number included in report, format ####
   * @param double $EndZNum 4 symbols for the final FM report number included in report, format ####
   */
  public function PrintBriefFMReportByZBlocks($StartZNum,$EndZNum) {
    $this->execute("PrintBriefFMReportByZBlocks", "StartZNum", $StartZNum, "EndZNum", $EndZNum);
  }
  
  /**
   * Define Fiscal device type. The command is allowed only in non- fiscal mode, before fiscalization and after deregistration before the next fiscalization. The type of device can be read by Version command 0x21.
   * @param OptionFDType $OptionFDType 1 symbol for fiscal device type with value: 
   *  - '0' - FPr for Fuel type 3 
   *  - '1' - Main FPr for Fuel system type 31 
   *  - '2' - ECR for online store type 11 
   *  - '3' - FPr for online store type 21  
   *  - '*' - reset default type
   * @param string $Password 3-symbols string
   */
  public function SetFiscalDeviceType($OptionFDType,$Password) {
    $this->execute("SetFiscalDeviceType", "OptionFDType", $OptionFDType, "Password", $Password);
  }
  
  /**
   * Read Electronic Journal Report by initial to end date.
   * @param OptionReportFormat $OptionReportFormat 1 character with value 
   *  - 'J0' - Detailed EJ 
   *  - 'J8' - Brief EJ
   * @param DateTime $StartRepFromDate 6 symbols for initial date in the DDMMYY format
   * @param DateTime $EndRepFromDate 6 symbols for final date in the DDMMYY format
   */
  public function ReadEJByDate($OptionReportFormat,$StartRepFromDate,$EndRepFromDate) {
    $this->execute("ReadEJByDate", "OptionReportFormat", $OptionReportFormat, "StartRepFromDate", $StartRepFromDate, "EndRepFromDate", $EndRepFromDate);
  }
  
  /**
   * Provides information about the barcode of the specified article.
   * @param double $PLUNum 5 symbols for article number with leading zeroes in format: #####
   * @return PLUbarcodeRes
   */
  public function ReadPLUbarcode($PLUNum) {
    return new \Tremol\PLUbarcodeRes($this->execute("ReadPLUbarcode", "PLUNum", $PLUNum));
  }
  
  /**
   * Read the PO by type of payment and the total number of operations by specified operator. Command works for KL version 2 devices.
   * @param double $OperNum Symbols from 1 to 20 corresponding to operator's number
   * @return DailyPObyOperator_OldRes
   */
  public function ReadDailyPObyOperator_Old($OperNum) {
    return new \Tremol\DailyPObyOperator_OldRes($this->execute("ReadDailyPObyOperator_Old", "OperNum", $OperNum));
  }
  
  /**
   * Set data for the state department number from the internal FD database. Parameters Price, OptionDepPrice and AdditionalName are not obligatory and require the previous not obligatory parameter.
   * @param double $Number 2 symbols department number in format ##
   * @param string $Name 20 characters department name
   * @param OptionVATClass $OptionVATClass 1 character for VAT class: 
   *  - 'А' - VAT Class 0 
   *  - 'Б' - VAT Class 1 
   *  - 'В' - VAT Class 2 
   *  - 'Г' - VAT Class 3 
   *  - 'Д' - VAT Class 4 
   *  - 'Е' - VAT Class 5 
   *  - 'Ж' - VAT Class 6 
   *  - 'З' - VAT Class 7 
   *  - '*' - Forbidden
   * @param double $Price Up to 10 symbols for department price
   * @param OptionDepPrice $OptionDepPrice 1 symbol for Department price flags with next value:  
   * - '0' - Free price disabled  
   * - '1' - Free price enabled  
   * - '2' - Limited price  
   * - '4' - Free price disabled for single transaction  
   * - '5' - Free price enabled for single transaction  
   * - '6' - Limited price for single transaction
   * @param string $AdditionalName 14 characters additional department name
   */
  public function ProgDepartment($Number,$Name,$OptionVATClass,$Price=NULL,$OptionDepPrice=NULL,$AdditionalName=NULL) {
    $this->execute("ProgDepartment", "Number", $Number, "Name", $Name, "OptionVATClass", $OptionVATClass, "Price", $Price, "OptionDepPrice", $OptionDepPrice, "AdditionalName", $AdditionalName);
  }
  
  /**
   * Prints the programmed graphical logo with the stated number.
   * @param double $Number Number of logo to be printed. If missing, prints logo with number 0
   */
  public function PrintLogo($Number) {
    $this->execute("PrintLogo", "Number", $Number);
  }
  
  /**
   * Read the total number of customers, discounts, additions, corrections and accumulated amounts by specified operator.
   * @param double $OperNum Symbols from 1 to 20 corresponding to operator's number
   * @return DailyGeneralRegistersByOperatorRes
   */
  public function ReadDailyGeneralRegistersByOperator($OperNum) {
    return new \Tremol\DailyGeneralRegistersByOperatorRes($this->execute("ReadDailyGeneralRegistersByOperator", "OperNum", $OperNum));
  }
  
  /**
   * Read the RA by type of payment and the total number of operations by specified operator. Command works for KL version 2 devices.
   * @param double $OperNum Symbols from 1 to 20 corresponding to operator's number
   * @return DailyRAbyOperator_OldRes
   */
  public function ReadDailyRAbyOperator_Old($OperNum) {
    return new \Tremol\DailyRAbyOperator_OldRes($this->execute("ReadDailyRAbyOperator_Old", "OperNum", $OperNum));
  }
  
  /**
   * Print a detailed FM report by initial and end FM report number.
   * @param double $StartZNum 4 symbols for the initial report number included in report, format ####
   * @param double $EndZNum 4 symbols for the final report number included in report, format ####
   */
  public function PrintDetailedFMReportByZBlocks($StartZNum,$EndZNum) {
    $this->execute("PrintDetailedFMReportByZBlocks", "StartZNum", $StartZNum, "EndZNum", $EndZNum);
  }
  
  /**
   * Print Customer X or Z report
   * @param OptionZeroing $OptionZeroing with following values: 
   *  - 'Z' -Zeroing 
   *  - 'X' - Without zeroing
   */
  public function PrintCustomerReport($OptionZeroing) {
    $this->execute("PrintCustomerReport", "OptionZeroing", $OptionZeroing);
  }
  
  /**
   * Depending on the parameter prints:  − daily fiscal report with zeroing and fiscal memory record, preceded by Electronic Journal report print ('Z'); − daily fiscal report without zeroing ('X');
   * @param OptionZeroing $OptionZeroing 1 character with following values: 
   *  - 'Z' - Zeroing 
   *  - 'X' - Without zeroing
   */
  public function PrintDailyReport($OptionZeroing) {
    $this->execute("PrintDailyReport", "OptionZeroing", $OptionZeroing);
  }
  
  /**
   * Provides the content of the footer line.
   * @return string TextLength symbols for footer line
   */
  public function ReadFooter() {
    return $this->execute("ReadFooter");
  }
  
  /**
   * Opens a non-fiscal receipt assigned to the specified operator number, operator password and print type.
   * @param double $OperNum Symbols from 1 to 20 corresponding to operator's 
   * number
   * @param string $OperPass 6 symbols for operator's password
   * @param OptionNonFiscalPrintType $OptionNonFiscalPrintType 1 symbol with value: 
   * - '0' - Step by step printing 
   * - '1' - Postponed Printing
   */
  public function OpenNonFiscalReceipt($OperNum,$OperPass,$OptionNonFiscalPrintType=NULL) {
    $this->execute("OpenNonFiscalReceipt", "OperNum", $OperNum, "OperPass", $OperPass, "OptionNonFiscalPrintType", $OptionNonFiscalPrintType);
  }
  
  /**
   * Calculate the subtotal amount with printing and display visualization options. Provide information about values of the calculated amounts. If a percent or value discount/addition has been specified the subtotal and the discount/addition value will be printed regardless the parameter for printing.
   * @param OptionPrinting $OptionPrinting 1 symbol with value: 
   *  - '1' - Yes 
   *  - '0' - No
   * @param OptionDisplay $OptionDisplay 1 symbol with value: 
   *  - '1' - Yes 
   *  - '0' - No
   * @param double $DiscAddV Up to 8 symbols for the value of the 
   * discount/addition. Use minus sign '-' for discount
   * @param double $DiscAddP Up to 7 symbols for the percentage value of the 
   * discount/addition. Use minus sign '-' for discount
   * @return double Up to 10 symbols for the value of the subtotal amount
   */
  public function Subtotal($OptionPrinting,$OptionDisplay,$DiscAddV=NULL,$DiscAddP=NULL) {
    return $this->execute("Subtotal", "OptionPrinting", $OptionPrinting, "OptionDisplay", $OptionDisplay, "DiscAddV", $DiscAddV, "DiscAddP", $DiscAddP);
  }
  
  /**
   * Print a detailed FM payments report by initial and end date.
   * @param DateTime $StartDate 6 symbols for initial date in the DDMMYY format
   * @param DateTime $EndDate 6 symbols for final date in the DDMMYY format
   */
  public function PrintDetailedFMPaymentsReportByDate($StartDate,$EndDate) {
    $this->execute("PrintDetailedFMPaymentsReportByDate", "StartDate", $StartDate, "EndDate", $EndDate);
  }
  
  /**
   * Provides information about the RA amounts by type of payment and the total number of operations.
   * @return DailyRARes
   */
  public function ReadDailyRA() {
    return new \Tremol\DailyRARes($this->execute("ReadDailyRA"));
  }
  
  /**
   * Provides information about the number of customers (number of fiscal receipt issued), number of discounts, additions and corrections made and the accumulated amounts.
   * @return GeneralDailyRegistersRes
   */
  public function ReadGeneralDailyRegisters() {
    return new \Tremol\GeneralDailyRegistersRes($this->execute("ReadGeneralDailyRegisters"));
  }
  
  /**
   * Provides the content of the Display Greeting message.
   * @return string 20 symbols for display greeting message
   */
  public function ReadDisplayGreetingMessage() {
    return $this->execute("ReadDisplayGreetingMessage");
  }
  
  /**
   * Prints a brief payments from the FM.
   */
  public function PrintBriefFMPaymentsReport() {
    $this->execute("PrintBriefFMPaymentsReport");
  }
  
  /**
   * Provides information about the amounts on hand by type of payment. Command works for KL version 2 devices.
   * @return DailyAvailableAmounts_OldRes
   */
  public function ReadDailyAvailableAmounts_Old() {
    return new \Tremol\DailyAvailableAmounts_OldRes($this->execute("ReadDailyAvailableAmounts_Old"));
  }
  
  /**
   * Read the Grand fiscal turnover sum and Grand fiscal VAT sum.
   * @return GrandFiscalSalesAndStornoAmountsRes
   */
  public function ReadGrandFiscalSalesAndStornoAmounts() {
    return new \Tremol\GrandFiscalSalesAndStornoAmountsRes($this->execute("ReadGrandFiscalSalesAndStornoAmounts"));
  }
  
  /**
   * Opens an electronic fiscal invoice receipt with 1 minute timeout assigned to the specified operator number and operator password with internal DB info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.
   * @param double $OperNum Symbol from 1 to 20 corresponding to operator's 
   * number
   * @param string $OperPass 6 symbols for operator's password
   * @param string $CustomerNum Symbol '#' and following up to 4 symbols for related customer ID number 
   * corresponding to the FD database
   * @param string $UniqueReceiptNumber Up to 24 symbols for unique receipt number. 
   * NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: 
   * * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, 
   * * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, 
   * * YYYYYYY - 7 symbols [0-9] for next number of the receipt
   */
  public function OpenElectronicInvoiceWithFDCustomerDB($OperNum,$OperPass,$CustomerNum,$UniqueReceiptNumber=NULL) {
    $this->execute("OpenElectronicInvoiceWithFDCustomerDB", "OperNum", $OperNum, "OperPass", $OperPass, "CustomerNum", $CustomerNum, "UniqueReceiptNumber", $UniqueReceiptNumber);
  }
  
  /**
   * Register the sell (for correction use minus sign in the price field) of article belonging to department with specified name, price, fractional quantity and/or discount/addition on the transaction. The VAT of article got from department to which article belongs.
   * @param string $NamePLU 36 symbols for article's name. 34 symbols are printed on paper. 
   * Symbol 0x7C '|' is new line separator.
   * @param double $Price Up to 10 symbols for article's price. Use minus sign '-' for correction
   * @param string $Quantity From 3 to 10 symbols for quantity in format fractional format, e.g. 1/3
   * @param double $DiscAddP 1 to 7 symbols for percentage of discount/addition. Use 
   * minus sign '-' for discount
   * @param double $DiscAddV 1 to 8 symbols for value of discount/addition. Use 
   * minus sign '-' for discount
   * @param double $DepNum 1 symbol for article department 
   * attachment, formed in the following manner; example: Dep01 = 81h, Dep02 
   * = 82h … Dep19 = 93h
   */
  public function SellFractQtyPLUfromDep($NamePLU,$Price,$Quantity=NULL,$DiscAddP=NULL,$DiscAddV=NULL,$DepNum=NULL) {
    $this->execute("SellFractQtyPLUfromDep", "NamePLU", $NamePLU, "Price", $Price, "Quantity", $Quantity, "DiscAddP", $DiscAddP, "DiscAddV", $DiscAddV, "DepNum", $DepNum);
  }
  
  /**
   * Provides information about the number of POS, printing of logo, cash drawer opening, cutting permission, display mode, article report type, Enable/Disable currency in receipt, EJ font type and working operators counter.
   * @return ParametersRes
   */
  public function ReadParameters() {
    return new \Tremol\ParametersRes($this->execute("ReadParameters"));
  }
  
  /**
   * Provides information about the device type, Certificate number, Certificate date and time and Device model.
   * @return VersionRes
   */
  public function ReadVersion() {
    return new \Tremol\VersionRes($this->execute("ReadVersion"));
  }
  
  /**
   *  Writes raw bytes to FP 
   * @param array $Bytes The bytes in BASE64 ecoded string to be written to FP
   */
  public function RawWrite($Bytes) {
    $this->execute("RawWrite", "Bytes", $Bytes);
  }
  
  /**
   * Register the sell (for correction use minus sign in the price field) of article  with specified department. If VAT is present the relevant accumulations are perfomed in its  registers.
   * @param string $NamePLU 36 symbols for name of sale. 34 symbols are printed on 
   * paper. Symbol 0x7C '|' is new line separator.
   * @param double $DepNum 1 symbol for article department 
   * attachment, formed in the following manner: DepNum[HEX] + 80h 
   * example: Dep01 = 81h, Dep02 = 82h … Dep19 = 93h
   * @param double $Price Up to 10 symbols for article's price. Use minus sign '-' for correction
   * @param double $Quantity Up to 10 symbols for article's quantity sold
   * @param double $DiscAddP Up to 7 for percentage of discount/addition. Use 
   * minus sign '-' for discount
   * @param double $DiscAddV Up to 8 symbols for percentage of 
   * discount/addition. Use minus sign '-' for discount
   * @param OptionVATClass $OptionVATClass 1 character for VAT class: 
   *  - 'А' - VAT Class 0 
   *  - 'Б' - VAT Class 1 
   *  - 'В' - VAT Class 2 
   *  - 'Г' - VAT Class 3 
   *  - 'Д' - VAT Class 4 
   *  - 'Е' - VAT Class 5 
   *  - 'Ж' - VAT Class 6 
   *  - 'З' - VAT Class 7 
   *  - '*' - Forbidden
   */
  public function SellPLUwithSpecifiedVATfromDep_($NamePLU,$DepNum,$Price,$Quantity=NULL,$DiscAddP=NULL,$DiscAddV=NULL,$OptionVATClass=NULL) {
    $this->execute("SellPLUwithSpecifiedVATfromDep_", "NamePLU", $NamePLU, "DepNum", $DepNum, "Price", $Price, "Quantity", $Quantity, "DiscAddP", $DiscAddP, "DiscAddV", $DiscAddV, "OptionVATClass", $OptionVATClass);
  }
  
  /**
   * Print or store Electronic Journal Report from receipt number to receipt number.
   * @param OptionReportStorage $OptionReportStorage 1 character with value: 
   *  - 'J1' - Printing 
   *  - 'J2' - USB storage 
   *  - 'J4' - SD card storage
   * @param double $StartRcpNum 6 symbols for initial receipt number included in report, in format ######.
   * @param double $EndRcpNum 6 symbols for final receipt number included in report in format ######.
   */
  public function PrintOrStoreEJByRcpNum($OptionReportStorage,$StartRcpNum,$EndRcpNum) {
    $this->execute("PrintOrStoreEJByRcpNum", "OptionReportStorage", $OptionReportStorage, "StartRcpNum", $StartRcpNum, "EndRcpNum", $EndRcpNum);
  }
  
  /**
   * Read the amounts returned as change by different payment types for the specified operator.
   * @param double $OperNum Symbol from 1 to 20 corresponding to operator's number
   * @return DailyReturnedChangeAmountsByOperatorRes
   */
  public function ReadDailyReturnedChangeAmountsByOperator($OperNum) {
    return new \Tremol\DailyReturnedChangeAmountsByOperatorRes($this->execute("ReadDailyReturnedChangeAmountsByOperator", "OperNum", $OperNum));
  }
  
  /**
   * Read or Store Electronic Journal Report from receipt number to receipt number, CSV format option and document content. If CSV format is set the content can includes only fiscal receipts. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.
   * @param OptionStorageReport $OptionStorageReport 1 character with value 
   *  - 'j0' - To PC 
   *  - 'j2' - To USB Flash Drive 
   *  - 'j4' - To SD card
   * @param OptionCSVformat $OptionCSVformat 1 symbol with value: 
   *  - 'C' - Yes  
   * 
   *  - 'X' - No
   * @param string $FlagsReceipts 1 symbol for Receipts included in EJ: 
   * Flags.7=0 
   * Flags.6=1 
   * Flags.5=1 Yes, Flags.5=0 No (Include PO) 
   * Flags.4=1 Yes, Flags.4=0 No (Include RA) 
   * Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) 
   * Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) 
   * Flags.1=1 Yes, Flags.1=0 No (Include Invoice) 
   * Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)
   * @param string $FlagsReports 1 symbol for Reports included in EJ: 
   * Flags.7=0 
   * Flags.6=1 
   * Flags.5=0 
   * Flags.4=1 Yes, Flags.4=0 No (Include FM reports) 
   * Flags.3=1 Yes, Flags.3=0 No (Include Other reports) 
   * Flags.2=1 Yes, Flags.2=0 No (Include Daily X) 
   * Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) 
   * Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)
   * @param double $StartRcpNum 6 symbols for initial receipt number included in report in format ######.
   * @param double $EndRcpNum 6 symbols for final receipt number included in report in format ######.
   */
  public function ReadEJByReceiptNumCustom($OptionStorageReport,$OptionCSVformat,$FlagsReceipts,$FlagsReports,$StartRcpNum,$EndRcpNum) {
    $this->execute("ReadEJByReceiptNumCustom", "OptionStorageReport", $OptionStorageReport, "OptionCSVformat", $OptionCSVformat, "FlagsReceipts", $FlagsReceipts, "FlagsReports", $FlagsReports, "StartRcpNum", $StartRcpNum, "EndRcpNum", $EndRcpNum);
  }
  
  /**
   * Paying the exact amount in cash and close the fiscal receipt.
   */
  public function CashPayCloseReceipt() {
    $this->execute("CashPayCloseReceipt");
  }
  
  /**
   * Program the contents of a Display Greeting message.
   * @param string $DisplayGreetingText 20 symbols for Display greeting message
   */
  public function ProgDisplayGreetingMessage($DisplayGreetingText) {
    $this->execute("ProgDisplayGreetingMessage", "DisplayGreetingText", $DisplayGreetingText);
  }
  
  /**
   * Provides information about the PO amounts by type of payment and the total number of operations.
   * @return DailyPORes
   */
  public function ReadDailyPO() {
    return new \Tremol\DailyPORes($this->execute("ReadDailyPO"));
  }
  
  /**
   * Prints an extended daily financial report (an article report followed by a daily financial report) with or without zeroing ('Z' or 'X').
   * @param OptionZeroing $OptionZeroing with following values: 
   *  - 'Z' -Zeroing 
   *  - 'X' - Without zeroing
   */
  public function PrintDetailedDailyReport($OptionZeroing) {
    $this->execute("PrintDetailedDailyReport", "OptionZeroing", $OptionZeroing);
  }
  
  }

}
?>