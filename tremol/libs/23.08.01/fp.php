<?php
namespace Tremol {
  require('FP_Core.php');
  class OptionBarcodeFormat extends EnumType {
    const NNNNcWWWWW = '0';
    const NNNNNWWWWW = '1';
  }
  
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
  
  class OptionPayType extends EnumType {
    const Cash = '0';
    const Currency = '11';
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
  
  class OptionDHCPEnabled extends EnumType {
    const Disabled = '0';
    const Enabled = '1';
  }
  
  class OptionTransferAmount extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionDhcpStatus extends EnumType {
    const Disabled = '0';
    const Enabled = '1';
  }
  
  class OptionAddressType extends EnumType {
    const DNS_address = '5';
    const Gateway_address = '4';
    const IP_address = '2';
    const Subnet_Mask = '3';
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
  
  class OptionTCPAutoStart extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionNBL extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionUsedModule extends EnumType {
    const LAN = '1';
    const WiFi = '2';
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
  
  class OptionDailyReport extends EnumType {
    const Generate_automatic_Z_report = '0';
    const Print_automatic_Z_report = '1';
  }
  
  class OptionLAN extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionWiFi extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionGPRS extends EnumType {
    const No = '0';
    const Yes = '1';
  }
  
  class OptionBT extends EnumType {
    const No = '0';
    const Yes = '1';
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
  
  class OptionZReportType extends EnumType {
    const Automatic = '1';
    const Manual = '0';
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
  
  class OptionBTstatus extends EnumType {
    const Disabled = '0';
    const Enabled = '1';
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

class Bluetooth_PasswordRes extends BaseResClass {
  protected $PassLength;
  protected $Password;
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

class TCP_AddressesRes extends BaseResClass {
  protected $OptionAddressType;
  protected $DeviceAddress;
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

class DeviceModuleSupportByFirmwareRes extends BaseResClass {
  protected $OptionLAN;
  protected $OptionWiFi;
  protected $OptionGPRS;
  protected $OptionBT;
}

class WiFi_PasswordRes extends BaseResClass {
  protected $PassLength;
  protected $Password;
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

class PaymentsPositionsRes extends BaseResClass {
  protected $PaymentPosition0;
  protected $PaymentPosition1;
  protected $PaymentPosition2;
  protected $PaymentPosition3;
  protected $PaymentPosition4;
  protected $PaymentPosition5;
  protected $PaymentPosition6;
  protected $PaymentPosition7;
  protected $PaymentPosition8;
  protected $PaymentPosition9;
  protected $PaymentPosition10;
  protected $PaymentPosition11;
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
  protected $Non_printed_copy;
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

class LastDailyReportAvailableAmountsRes extends BaseResClass {
  protected $OptionZReportType;
  protected $ZreportNum;
  protected $CashAvailableAmount;
  protected $CurrencyAvailableAmount;
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

class TCP_PasswordRes extends BaseResClass {
  protected $PassLength;
  protected $Password;
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

class DeviceModuleSupportRes extends BaseResClass {
  protected $OptionLAN;
  protected $OptionWiFi;
  protected $OptionGPRS;
  protected $OptionBT;
}

class WiFi_NetworkNameRes extends BaseResClass {
  protected $WiFiNameLength;
  protected $WiFiNetworkName;
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
    $this->timeStamp = 2308011616;
  }
  /**
   * Provides information about the amounts on hand by type of payment.
   * @return DailyAvailableAmountsRes
   */
  public function ReadDailyAvailableAmounts() {
    return new \Tremol\DailyAvailableAmountsRes($this->execute("ReadDailyAvailableAmounts"));
  }
  
  /**
   * Program weight barcode format.
   * @param OptionBarcodeFormat $OptionBarcodeFormat 1 symbol with value: 
   *  - '0' - NNNNcWWWWW 
   *  - '1' - NNNNNWWWWW
   */
  public function ProgramWeightBarcodeFormat($OptionBarcodeFormat) {
    $this->execute("ProgramWeightBarcodeFormat", "OptionBarcodeFormat", $OptionBarcodeFormat);
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
   * Start LAN test on the device and print out the result
   */
  public function StartTest_Lan() {
    $this->execute("StartTest_Lan");
  }
  
  /**
   * Provides information for the programmed data, the turnover from the stated department number
   * @param double $DepNum 3 symbols for department number in format ###
   * @return DepartmentRes
   */
  public function ReadDepartment($DepNum) {
    return new \Tremol\DepartmentRes($this->execute("ReadDepartment", "DepNum", $DepNum));
  }
  
  /**
   * Provide information about parameter for automatic transfer of daily available amounts.
   * @return string 1 symbol with value: 
   *  - '0' - No 
   *  - '1' - Yes
   */
  public function ReadTransferAmountParam_RA() {
    return $this->execute("ReadTransferAmountParam_RA");
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
   * Department range from 1 to 127
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
   * @param string $Name 10 symbols for payment type name. Only the first 6 are printable and only 
   * relevant for CodePayment '9' and ':'
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
  public function ProgPayment_Old($OptionNumber,$Name,$Rate,$OptionCodePayment) {
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
   * Provide information about weight barcode format.
   * @return OptionBarcodeFormat 1 symbol with value: 
   *  - '0' - NNNNcWWWWW 
   *  - '1' - NNNNNWWWWW
   */
  public function ReadWeightBarcodeFormat() {
    return $this->execute("ReadWeightBarcodeFormat");
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
   * Print a detailed FM payments report by initial and end Z report number.
   * @param double $StartZNum 4 symbols for initial FM report number included in report, format ####
   * @param double $EndZNum 4 symbols for final FM report number included in report, format ####
   */
  public function PrintDetailedFMPaymentsReportByZBlocks($StartZNum,$EndZNum) {
    $this->execute("PrintDetailedFMPaymentsReportByZBlocks", "StartZNum", $StartZNum, "EndZNum", $EndZNum);
  }
  
  /**
   * Program device's TCP password. To apply use - SaveNetworkSettings()
   * @param double $PassLength Up to 3 symbols for the password len
   * @param string $Password Up to 100 symbols for the TCP password
   */
  public function SetTCPpassword($PassLength,$Password) {
    $this->execute("SetTCPpassword", "PassLength", $PassLength, "Password", $Password);
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
   * Start WiFi test on the device and print out the result
   */
  public function StartTest_WiFi() {
    $this->execute("StartTest_WiFi");
  }
  
  /**
   * Read the number of the remaining free records for Z-report in the Fiscal Memory.
   * @return string 4 symbols for the number of free records for Z-report in the FM
   */
  public function ReadFMfreeRecords() {
    return $this->execute("ReadFMfreeRecords");
  }
  
  /**
   * Provides information about device's Bluetooth password.
   * @return Bluetooth_PasswordRes
   */
  public function ReadBluetooth_Password() {
    return new \Tremol\Bluetooth_PasswordRes($this->execute("ReadBluetooth_Password"));
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
   * Department range from 1 to 127
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
   * Provides information about device's idle timeout. This timeout is seconds in which the connection will be closed when there is an inactivity. This information is available if the device has LAN or WiFi. Maximal value - 7200, minimal value 0. 0 is for never close the connection.
   * @return double 4 symbols for password in format ####
   */
  public function Read_IdleTimeout() {
    return $this->execute("Read_IdleTimeout");
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
   * Program device's Bluetooth password. To apply use - SaveNetworkSettings()
   * @param double $PassLength Up to 3 symbols for the BT password len
   * @param string $Password Up to 100 symbols for the BT password
   */
  public function SetBluetooth_Password($PassLength,$Password) {
    $this->execute("SetBluetooth_Password", "PassLength", $PassLength, "Password", $Password);
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
   * Scan and print all available WiFi networks
   */
  public function ScanAndPrintWiFiNetworks() {
    $this->execute("ScanAndPrintWiFiNetworks");
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
   * @param OptionPayType $OptionPayType 1 symbol with value 
   *  - '0' - Cash 
   *  - '11' - Currency
   * @param double $Amount Up to 10 symbols for the amount lodged. Use minus sign for withdrawn
   * @param OptionPrintAvailability $OptionPrintAvailability 1 symbol with value: 
   *  - '0' - No 
   *  - '1' - Yes
   * @param string $Text TextLength-2 symbols. In the beginning and in the end of line symbol
   */
  public function ReceivedOnAccount_PaidOut($OperNum,$OperPass,$OptionPayType,$Amount,$OptionPrintAvailability=NULL,$Text=NULL) {
    $this->execute("ReceivedOnAccount_PaidOut", "OperNum", $OperNum, "OperPass", $OperPass, "OptionPayType", $OptionPayType, "Amount", $Amount, "OptionPrintAvailability", $OptionPrintAvailability, "Text", $Text);
  }
  
  /**
   * After every change on Idle timeout, LAN/WiFi/GPRS usage, LAN/WiFi/TCP/GPRS password or TCP auto start networks settings this Save command needs to be execute.
   */
  public function SaveNetworkSettings() {
    $this->execute("SaveNetworkSettings");
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
   * Print a brief FM Departments report by initial and end date.
   * @param DateTime $StartDate 6 symbols for initial date in the DDMMYY format
   * @param DateTime $EndDate 6 symbols for final date in the DDMMYY format
   */
  public function PrintBriefFMDepartmentsReportByDate($StartDate,$EndDate) {
    $this->execute("PrintBriefFMDepartmentsReportByDate", "StartDate", $StartDate, "EndDate", $EndDate);
  }
  
  /**
   * Provides information for the programmed data, the turnovers from the stated department number
   * @param double $DepNum 3 symbols for department number in format ###
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
   * Program device's WiFi network name where it will connect. To apply use -SaveNetworkSettings()
   * @param double $WiFiNameLength Up to 3 symbols for the WiFi network name len
   * @param string $WiFiNetworkName Up to 100 symbols for the device's WiFi ssid network name
   */
  public function SetWiFi_NetworkName($WiFiNameLength,$WiFiNetworkName) {
    $this->execute("SetWiFi_NetworkName", "WiFiNameLength", $WiFiNameLength, "WiFiNetworkName", $WiFiNetworkName);
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
   * Read a detailed FM Departments report by initial and end Z report number.
   * @param double $StartZNum 4 symbols for initial FM report number included in report, format ####
   * @param double $EndZNum 4 symbols for final FM report number included in report, format ####
   */
  public function ReadDetailedFMDepartmentsReportByZBlocks($StartZNum,$EndZNum) {
    $this->execute("ReadDetailedFMDepartmentsReportByZBlocks", "StartZNum", $StartZNum, "EndZNum", $EndZNum);
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
   * Program device's TCP network DHCP enabled or disabled. To apply use -SaveNetworkSettings()
   * @param OptionDHCPEnabled $OptionDHCPEnabled 1 symbol with value: 
   *  - '0' - Disabled 
   *  - '1' - Enabled
   */
  public function SetDHCP_Enabled($OptionDHCPEnabled) {
    $this->execute("SetDHCP_Enabled", "OptionDHCPEnabled", $OptionDHCPEnabled);
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
   * Program parameter for automatic transfer of daily available amounts.
   * @param OptionTransferAmount $OptionTransferAmount 1 symbol with value: 
   *  - '0' - No 
   *  - '1' - Yes
   */
  public function ProgramTransferAmountParam_RA($OptionTransferAmount) {
    $this->execute("ProgramTransferAmountParam_RA", "OptionTransferAmount", $OptionTransferAmount);
  }
  
  /**
   * Provides information about device's DHCP status
   * @return OptionDhcpStatus (DHCP Status) 1 symbol for device's DHCP status 
   * - '0' - Disabled 
   *  - '1' - Enabled
   */
  public function ReadDHCP_Status() {
    return $this->execute("ReadDHCP_Status");
  }
  
  /**
   * Provides information about device's IP address, subnet mask, gateway address, DNS address.
   * @param OptionAddressType $OptionAddressType 1 symbol with value: 
   *  - '2' - IP address 
   *  - '3' - Subnet Mask 
   *  - '4' - Gateway address 
   *  - '5' - DNS address
   * @return TCP_AddressesRes
   */
  public function ReadTCP_Addresses($OptionAddressType) {
    return new \Tremol\TCP_AddressesRes($this->execute("ReadTCP_Addresses", "OptionAddressType", $OptionAddressType));
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
   * Removes all paired devices. To apply use -SaveNetworkSettings()
   */
  public function UnpairAllDevices() {
    $this->execute("UnpairAllDevices");
  }
  
  /**
   * Shows the current date and time on the external display.
   */
  public function DisplayDateTime() {
    $this->execute("DisplayDateTime");
  }
  
  /**
   * Set device's TCP autostart . To apply use -SaveNetworkSettings()
   * @param OptionTCPAutoStart $OptionTCPAutoStart 1 symbol with value: 
   *  - '0' - No 
   *  - '1' - Yes
   */
  public function SetTCP_AutoStart($OptionTCPAutoStart) {
    $this->execute("SetTCP_AutoStart", "OptionTCPAutoStart", $OptionTCPAutoStart);
  }
  
  /**
   * Provide information about NBL parameter to be monitored by the fiscal device.
   * @return OptionNBL 1 symbol with value: 
   *  - '0' - No 
   *  - '1' - Yes
   */
  public function ReadNBLParameter() {
    return $this->execute("ReadNBLParameter");
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
   * Sets the used TCP module for communication - Lan or WiFi. To apply use -SaveNetworkSettings()
   * @param OptionUsedModule $OptionUsedModule 1 symbol with value: 
   *  - '1' - LAN 
   *  - '2' - WiFi
   */
  public function SetTCP_ActiveModule($OptionUsedModule) {
    $this->execute("SetTCP_ActiveModule", "OptionUsedModule", $OptionUsedModule);
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
   * Read the used TCP module for communication - Lan or WiFi
   * @return OptionUsedModule (Module) 1 symbol with value: 
   *  - '1' - LAN 
   *  - '2' - WiFi
   */
  public function ReadTCP_UsedModule() {
    return $this->execute("ReadTCP_UsedModule");
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
   * Department range from 1 to 127
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
   * Arrangement of payment positions according to NRA list: 0-Cash, 1- Check, 2-Talon, 3-V.Talon, 4-Packaging, 5-Service, 6-Damage, 7-Card, 8-Bank, 9- Programming Name 1, 10-Programming Name 2, 11-Currency.
   * @param double $PaymentPosition0 2 digits for payment position 0 in format ##.  
   * Values from '1' to '11' according to NRA payments list.
   * @param double $PaymentPosition1 2 digits for payment position 1 in format ##.  
   * Values from '1' to '11' according to NRA payments list.
   * @param double $PaymentPosition2 2 digits for payment position 2 in format ##.  
   * Values from '1' to '11' according to NRA payments list.
   * @param double $PaymentPosition3 2 digits for payment position 3 in format ##.  
   * Values from '1' to '11' according to NRA payments list.
   * @param double $PaymentPosition4 2 digits for payment position 4 in format ##.  
   * Values from '1' to '11' according to NRA payments list.
   * @param double $PaymentPosition5 2 digits for payment position 5 in format ##.  
   * Values from '1' to '11' according to NRA payments list.
   * @param double $PaymentPosition6 2 digits for payment position 6 in format ##.  
   * Values from '1' to '11' according to NRA payments list.
   * @param double $PaymentPosition7 2 digits for payment position 7 in format ##.  
   * Values from '1' to '11' according to NRA payments list.
   * @param double $PaymentPosition8 2 digits for payment position 8 in format ##.  
   * Values from '1' to '11' according to NRA payments list.
   * @param double $PaymentPosition9 2 digits for payment position 9 in format ##.  
   * Values from '1' to '11' according to NRA payments list.
   * @param double $PaymentPosition10 2 digits for payment position 10 in format ##.  
   * Values from '1' to '11' according to NRA payments list.
   * @param double $PaymentPosition11 2 digits for payment position 11 in format ##.  
   * Values from '1' to '11' according to NRA payments list.
   */
  public function ArrangePayments($PaymentPosition0,$PaymentPosition1,$PaymentPosition2,$PaymentPosition3,$PaymentPosition4,$PaymentPosition5,$PaymentPosition6,$PaymentPosition7,$PaymentPosition8,$PaymentPosition9,$PaymentPosition10,$PaymentPosition11) {
    $this->execute("ArrangePayments", "PaymentPosition0", $PaymentPosition0, "PaymentPosition1", $PaymentPosition1, "PaymentPosition2", $PaymentPosition2, "PaymentPosition3", $PaymentPosition3, "PaymentPosition4", $PaymentPosition4, "PaymentPosition5", $PaymentPosition5, "PaymentPosition6", $PaymentPosition6, "PaymentPosition7", $PaymentPosition7, "PaymentPosition8", $PaymentPosition8, "PaymentPosition9", $PaymentPosition9, "PaymentPosition10", $PaymentPosition10, "PaymentPosition11", $PaymentPosition11);
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
   * Prints barcode from type stated by CodeType and CodeLen and with data stated in CodeData field. Command works only for fiscal printer devices. ECR does not support this command. The command is not supported by KL ECRs!
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
   * Prints a brief Departments report from the FM.
   */
  public function PrintBriefFMDepartmentsReport() {
    $this->execute("PrintBriefFMDepartmentsReport");
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
   * Program device's WiFi network password where it will connect. To apply use -SaveNetworkSettings()
   * @param double $PassLength Up to 3 symbols for the WiFi password len
   * @param string $Password Up to 100 symbols for the device's WiFi password
   */
  public function SetWiFi_Password($PassLength,$Password) {
    $this->execute("SetWiFi_Password", "PassLength", $PassLength, "Password", $Password);
  }
  
  /**
   * Provides information about the current quantity measured by scale
   * @return double Up to 13 symbols for quantity
   */
  public function ReadScaleQuantity() {
    return $this->execute("ReadScaleQuantity");
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
   * Start Bluetooth test on the device and print out the result
   */
  public function StartTest_Bluetooth() {
    $this->execute("StartTest_Bluetooth");
  }
  
  /**
   * Read a brief FM Departments report by initial and end date.
   * @param DateTime $StartDate 6 symbols for initial date in the DDMMYY format
   * @param DateTime $EndDate 6 symbols for final date in the DDMMYY format
   */
  public function ReadBriefFMDepartmentsReportByDate($StartDate,$EndDate) {
    $this->execute("ReadBriefFMDepartmentsReportByDate", "StartDate", $StartDate, "EndDate", $EndDate);
  }
  
  /**
   * Erase all articles in PLU database.
   * @param string $Password 6 symbols for password
   */
  public function EraseAllPLUs($Password) {
    $this->execute("EraseAllPLUs", "Password", $Password);
  }
  
  /**
   * Print a detailed FM Departments report by initial and end date.
   * @param DateTime $StartDate 6 symbols for initial date in the DDMMYY format
   * @param DateTime $EndDate 6 symbols for final date in the DDMMYY format
   */
  public function PrintDetailedFMDepartmentsReportByDate($StartDate,$EndDate) {
    $this->execute("PrintDetailedFMDepartmentsReportByDate", "StartDate", $StartDate, "EndDate", $EndDate);
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
   * Provide information about automatic daily report printing or not printing parameter
   * @return OptionDailyReport 1 symbol with value: 
   *  - '1' - Print automatic Z report 
   *  - '0' - Generate automatic Z report
   */
  public function ReadDailyReportParameter() {
    return $this->execute("ReadDailyReportParameter");
  }
  
  /**
   * Start GPRS test on the device and print out the result
   */
  public function StartTest_GPRS() {
    $this->execute("StartTest_GPRS");
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
   * Provide an information about modules supported by device's firmware
   * @return DeviceModuleSupportByFirmwareRes
   */
  public function ReadDeviceModuleSupportByFirmware() {
    return new \Tremol\DeviceModuleSupportByFirmwareRes($this->execute("ReadDeviceModuleSupportByFirmware"));
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
   * Read device's connected WiFi network password
   * @return WiFi_PasswordRes
   */
  public function ReadWiFi_Password() {
    return new \Tremol\WiFi_PasswordRes($this->execute("ReadWiFi_Password"));
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
   * Department range from 1 to 127
   */
  public function SellFractQtyPLUwithSpecifiedVATfromDep($NamePLU,$OptionVATClass,$Price,$Quantity=NULL,$DiscAddP=NULL,$DiscAddV=NULL,$DepNum=NULL) {
    $this->execute("SellFractQtyPLUwithSpecifiedVATfromDep", "NamePLU", $NamePLU, "OptionVATClass", $OptionVATClass, "Price", $Price, "Quantity", $Quantity, "DiscAddP", $DiscAddP, "DiscAddV", $DiscAddV, "DepNum", $DepNum);
  }
  
  /**
   * Print a brief FM Departments report by initial and end Z report number.
   * @param double $StartZNum 4 symbols for the initial FM report number included in report, format ####
   * @param double $EndZNum 4 symbols for the final FM report number included in report, format ####
   */
  public function PrintBriefFMDepartmentsReportByZBlocks($StartZNum,$EndZNum) {
    $this->execute("PrintBriefFMDepartmentsReportByZBlocks", "StartZNum", $StartZNum, "EndZNum", $EndZNum);
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
   * Print a detailed FM Departments report by initial and end Z report number.
   * @param double $StartZNum 4 symbols for initial FM report number included in report, format ####
   * @param double $EndZNum 4 symbols for final FM report number included in report, format ####
   */
  public function PrintDetailedFMDepartmentsReportByZBlocks($StartZNum,$EndZNum) {
    $this->execute("PrintDetailedFMDepartmentsReportByZBlocks", "StartZNum", $StartZNum, "EndZNum", $EndZNum);
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
   * Program device's network IP address, subnet mask, gateway address, DNS address. To apply use -SaveNetworkSettings()
   * @param OptionAddressType $OptionAddressType 1 symbol with value: 
   *  - '2' - IP address 
   *  - '3' - Subnet Mask 
   *  - '4' - Gateway address 
   *  - '5' - DNS address
   * @param string $DeviceAddress 15 symbols for the selected address
   */
  public function SetDeviceTCP_Addresses($OptionAddressType,$DeviceAddress) {
    $this->execute("SetDeviceTCP_Addresses", "OptionAddressType", $OptionAddressType, "DeviceAddress", $DeviceAddress);
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
   * Provides information about arrangement of payment positions according to NRA list: 0-Cash, 1-Check, 2-Talon, 3-V.Talon, 4-Packaging, 5-Service, 6- Damage, 7-Card, 8-Bank, 9-Programming Name 1, 10-Programming Name 2, 11-Currency.
   * @return PaymentsPositionsRes
   */
  public function ReadPaymentsPositions() {
    return new \Tremol\PaymentsPositionsRes($this->execute("ReadPaymentsPositions"));
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
   * Program automatic daily report printing or not printing parameter.
   * @param OptionDailyReport $OptionDailyReport 1 symbol with value: 
   *  - '1' - Print automatic Z report 
   *  - '0' - Generate automatic Z report
   */
  public function ProgramDailyReportParameter($OptionDailyReport) {
    $this->execute("ProgramDailyReportParameter", "OptionDailyReport", $OptionDailyReport);
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
   * Provides information about daily available amounts in cash and currency, Z daily report type and Z daily report number
   * @return LastDailyReportAvailableAmountsRes
   */
  public function ReadLastDailyReportAvailableAmounts() {
    return new \Tremol\LastDailyReportAvailableAmountsRes($this->execute("ReadLastDailyReportAvailableAmounts"));
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
   * Department range from 1 to 127
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
   * Provides information about device's TCP password.
   * @return TCP_PasswordRes
   */
  public function ReadTCP_Password() {
    return new \Tremol\TCP_PasswordRes($this->execute("ReadTCP_Password"));
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
   * @param OptionStorageReport $OptionStorageReport 2 characters with value: 
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
   * Program device's Bluetooth module to be enabled or disabled. To apply use -SaveNetworkSettings()
   * @param OptionBTstatus $OptionBTstatus 1 symbol with value: 
   *  - '0' - Disabled 
   *  - '1' - Enabled
   */
  public function SetBluetooth_Status($OptionBTstatus) {
    $this->execute("SetBluetooth_Status", "OptionBTstatus", $OptionBTstatus);
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
   * @param double $DepNum Up to 3 symbols for department number
   */
  public function SellPLUwithSpecifiedVATfor200DepRangeDevice($NamePLU,$OptionVATClass,$Price,$Quantity=NULL,$DiscAddP=NULL,$DiscAddV=NULL,$DepNum=NULL) {
    $this->execute("SellPLUwithSpecifiedVATfor200DepRangeDevice", "NamePLU", $NamePLU, "OptionVATClass", $OptionVATClass, "Price", $Price, "Quantity", $Quantity, "DiscAddP", $DiscAddP, "DiscAddV", $DiscAddV, "DepNum", $DepNum);
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
   * @param double $Number 3 symbols for department number in format ###
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
   * Read a detailed FM payments report by initial and end date.
   * @param DateTime $StartDate 6 symbols for initial date in the DDMMYY format
   * @param DateTime $EndDate 6 symbols for final date in the DDMMYY format
   */
  public function ReadDetailedFMPaymentsReportByDate($StartDate,$EndDate) {
    $this->execute("ReadDetailedFMPaymentsReportByDate", "StartDate", $StartDate, "EndDate", $EndDate);
  }
  
  /**
   * Sets device's idle timeout setting. Set timeout for closing the connection if there is an inactivity. Maximal value - 7200, minimal value 0. 0 is for never close the connection. This option can be used only if the device has LAN or WiFi. To apply use - SaveNetworkSettings()
   * @param double $IdleTimeout 4 symbols for Idle timeout in format ####
   */
  public function SetIdle_Timeout($IdleTimeout) {
    $this->execute("SetIdle_Timeout", "IdleTimeout", $IdleTimeout);
  }
  
  /**
   * Read device TCP Auto Start status
   * @return OptionTCPAutoStart 1 symbol for TCP auto start status 
   * - '0' - No 
   *  - '1' - Yes
   */
  public function ReadTCP_AutoStartStatus() {
    return $this->execute("ReadTCP_AutoStartStatus");
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
   * Read a brief FM report by initial and end FM report number.
   * @param double $StartZNum 4 symbols for the initial FM report number included in report, format ####
   * @param double $EndZNum 4 symbols for the final FM report number included in report, format ####
   */
  public function ReadBriefFMReportByZBlocks($StartZNum,$EndZNum) {
    $this->execute("ReadBriefFMReportByZBlocks", "StartZNum", $StartZNum, "EndZNum", $EndZNum);
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
   * Generate Z-daily report without printing
   */
  public function ZDailyReportNoPrint() {
    $this->execute("ZDailyReportNoPrint");
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
   * Read a brief FM payments report by initial and end date.
   * @param DateTime $StartDate 6 symbols for initial date in the DDMMYY format
   * @param DateTime $EndDate 6 symbols for final date in the DDMMYY format
   */
  public function ReadBriefFMPaymentsReportByDate($StartDate,$EndDate) {
    $this->execute("ReadBriefFMPaymentsReportByDate", "StartDate", $StartDate, "EndDate", $EndDate);
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
   * Program NBL parameter to be monitored by the fiscal device.
   * @param OptionNBL $OptionNBL 1 symbol with value: 
   *  - '0' - No 
   *  - '1' - Yes
   */
  public function ProgramNBLParameter($OptionNBL) {
    $this->execute("ProgramNBLParameter", "OptionNBL", $OptionNBL);
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
   * Read a detailed FM report by initial and end FM report number.
   * @param double $StartZNum 4 symbols for the initial report number included in report, format ####
   * @param double $EndZNum 4 symbols for the final report number included in report, format ####
   */
  public function ReadDetailedFMReportByZBlocks($StartZNum,$EndZNum) {
    $this->execute("ReadDetailedFMReportByZBlocks", "StartZNum", $StartZNum, "EndZNum", $EndZNum);
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
   * Read a brief FM payments report by initial and end FM report number.
   * @param double $StartZNum 4 symbols for the initial FM report number included in report, format ####
   * @param double $EndZNum 4 symbols for the final FM report number included in report, format ####
   */
  public function ReadBriefFMPaymentsReportByZBlocks($StartZNum,$EndZNum) {
    $this->execute("ReadBriefFMPaymentsReportByZBlocks", "StartZNum", $StartZNum, "EndZNum", $EndZNum);
  }
  
  /**
   * Read a brief FM Departments report by initial and end Z report number.
   * @param double $StartZNum 4 symbols for the initial FM report number included in report, format ####
   * @param double $EndZNum 4 symbols for the final FM report number included in report, format ####
   */
  public function ReadBriefFMDepartmentsReportByZBlocks($StartZNum,$EndZNum) {
    $this->execute("ReadBriefFMDepartmentsReportByZBlocks", "StartZNum", $StartZNum, "EndZNum", $EndZNum);
  }
  
  /**
   * Read the Grand fiscal turnover sum and Grand fiscal VAT sum.
   * @return GrandFiscalSalesAndStornoAmountsRes
   */
  public function ReadGrandFiscalSalesAndStornoAmounts() {
    return new \Tremol\GrandFiscalSalesAndStornoAmountsRes($this->execute("ReadGrandFiscalSalesAndStornoAmounts"));
  }
  
  /**
   * Providing information about if the device's Bluetooth module is enabled or disabled.
   * @return OptionBTstatus (Status) 1 symbol with value: 
   *  - '0' - Disabled 
   *  - '1' - Enabled
   */
  public function ReadBluetooth_Status() {
    return $this->execute("ReadBluetooth_Status");
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
   * Department range from 1 to 127
   */
  public function SellFractQtyPLUfromDep($NamePLU,$Price,$Quantity=NULL,$DiscAddP=NULL,$DiscAddV=NULL,$DepNum=NULL) {
    $this->execute("SellFractQtyPLUfromDep", "NamePLU", $NamePLU, "Price", $Price, "Quantity", $Quantity, "DiscAddP", $DiscAddP, "DiscAddV", $DiscAddV, "DepNum", $DepNum);
  }
  
  /**
   * Provide an information about modules supported by the device
   * @return DeviceModuleSupportRes
   */
  public function ReadDeviceModuleSupport() {
    return new \Tremol\DeviceModuleSupportRes($this->execute("ReadDeviceModuleSupport"));
  }
  
  /**
   * Read device's connected WiFi network name
   * @return WiFi_NetworkNameRes
   */
  public function ReadWiFi_NetworkName() {
    return new \Tremol\WiFi_NetworkNameRes($this->execute("ReadWiFi_NetworkName"));
  }
  
  /**
   * Provides information about the number of POS, printing of logo, cash drawer opening, cutting permission, display mode, article report type, Enable/Disable currency in receipt, EJ font type and working operators counter.
   * @return ParametersRes
   */
  public function ReadParameters() {
    return new \Tremol\ParametersRes($this->execute("ReadParameters"));
  }
  
  /**
   * Read a detailed FM Departments report by initial and end date.
   * @param DateTime $StartDate 6 symbols for initial date in the DDMMYY format
   * @param DateTime $EndDate 6 symbols for final date in the DDMMYY format
   */
  public function ReadDetailedFMDepartmentsReportByDate($StartDate,$EndDate) {
    $this->execute("ReadDetailedFMDepartmentsReportByDate", "StartDate", $StartDate, "EndDate", $EndDate);
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
   * Department range from 1 to 127
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
   * Read a detailed FM payments report by initial and end Z report number.
   * @param double $StartZNum 4 symbols for initial FM report number included in report, format ####
   * @param double $EndZNum 4 symbols for final FM report number included in report, format ####
   */
  public function ReadDetailedFMPaymentsReportByZBlocks($StartZNum,$EndZNum) {
    $this->execute("ReadDetailedFMPaymentsReportByZBlocks", "StartZNum", $StartZNum, "EndZNum", $EndZNum);
  }
  
  /**
   * Read a brief FM report by initial and end date.
   * @param DateTime $StartDate 6 symbols for initial date in the DDMMYY format
   * @param DateTime $EndDate 6 symbols for final date in the DDMMYY format
   */
  public function ReadBriefFMReportByDate($StartDate,$EndDate) {
    $this->execute("ReadBriefFMReportByDate", "StartDate", $StartDate, "EndDate", $EndDate);
  }
  
  /**
   * Read a detailed FM report by initial and end date.
   * @param DateTime $StartDate 6 symbols for initial date in the DDMMYY format
   * @param DateTime $EndDate 6 symbols for final date in the DDMMYY format
   */
  public function ReadDetailedFMReportByDate($StartDate,$EndDate) {
    $this->execute("ReadDetailedFMReportByDate", "StartDate", $StartDate, "EndDate", $EndDate);
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
  
  /**
   * Applying client library definitions to ZFPLabServer for compatibility.
   */
  public function ApplyClientLibraryDefinitions() {
    $defs = "<Defs><ServerStartupSettings>  <Encoding CodePage=\"1251\" EncodingName=\"Cyrillic (Windows)\" />  <GenerationTimeStamp>2308011616</GenerationTimeStamp>  <SignalFD>0</SignalFD>  <SilentFindDevice>0</SilentFindDevice>  <EM>0</EM> </ServerStartupSettings><Command Name=\"ReadDailyAvailableAmounts\" CmdByte=\"0x6E\"><FPOperation>Provides information about the amounts on hand by type of payment.</FPOperation><Args><Arg Name=\"\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'0'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"AmountPayment0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name=\"AmountPayment1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name=\"AmountPayment2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name=\"AmountPayment3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name=\"AmountPayment4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><Res Name=\"AmountPayment5\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 5</Desc></Res><Res Name=\"AmountPayment6\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 6</Desc></Res><Res Name=\"AmountPayment7\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 7</Desc></Res><Res Name=\"AmountPayment8\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 8</Desc></Res><Res Name=\"AmountPayment9\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 9</Desc></Res><Res Name=\"AmountPayment10\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 10</Desc></Res><Res Name=\"AmountPayment11\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 11</Desc></Res><ResFormatRaw><![CDATA[<'0'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]> <;> <AmountPayment5[1..13]> <;> <AmountPayment6[1..13]> <;> <AmountPayment7[1..13]> <;> <AmountPayment8[1..13]> <;> <AmountPayment9[1..13]> <;> <AmountPayment10[1..13]> <;> <AmountPayment11[1..13]>]]></ResFormatRaw></Response></Command><Command Name=\"ProgramWeightBarcodeFormat\" CmdByte=\"0x4F\"><FPOperation>Program weight barcode format.</FPOperation><Args><Arg Name=\"\" Value=\"B\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"W\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionBarcodeFormat\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"NNNNcWWWWW\" Value=\"0\" /><Option Name=\"NNNNNWWWWW\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '0' - NNNNcWWWWW  - '1' - NNNNNWWWWW</Desc></Arg><ArgsFormatRaw><![CDATA[ <'B'> <;> <'W'> <;> <OptionBarcodeFormat[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadDailyPO_Old\" CmdByte=\"0x6E\"><FPOperation>Provides information about the PO amounts by type of payment and the total number of operations. Command works for KL version 2 devices.</FPOperation><Args><Arg Name=\"\" Value=\"3\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'3'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"3\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"AmountPayment0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name=\"AmountPayment1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name=\"AmountPayment2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name=\"AmountPayment3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name=\"AmountPayment4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><Res Name=\"PONum\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for the total number of operations</Desc></Res><Res Name=\"SumAllPayment\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols to sum all payments</Desc></Res><ResFormatRaw><![CDATA[<'3'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]> <;> <PONum[1..5]> <;> <SumAllPayment[1..13]>]]></ResFormatRaw></Response></Command><Command Name=\"PrintArticleReport\" CmdByte=\"0x7E\"><FPOperation>Prints an article report with or without zeroing ('Z' or 'X').</FPOperation><Args><Arg Name=\"OptionZeroing\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Without zeroing\" Value=\"X\" /><Option Name=\"Zeroing\" Value=\"Z\" /></Options><Desc>with following values:  - 'Z' - Zeroing  - 'X' - Without zeroing</Desc></Arg><ArgsFormatRaw><![CDATA[ <OptionZeroing[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadDecimalPoint\" CmdByte=\"0x63\"><FPOperation>Provides information about the current (the last value stored into the FM) decimal point format.</FPOperation><Response ACK=\"false\"><Res Name=\"OptionDecimalPointPosition\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Fractions\" Value=\"2\" /><Option Name=\"Whole numbers\" Value=\"0\" /></Options><Desc>1 symbol with values:  - '0'- Whole numbers  - '2' - Fractions</Desc></Res><ResFormatRaw><![CDATA[<DecimalPointPosition[1]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadElectronicReceipt_QR_BMP\" CmdByte=\"0x72\"><FPOperation>Starts session for reading electronic receipt by number with Base64 encoded BMP QR code.</FPOperation><Args><Arg Name=\"\" Value=\"E\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"RcpNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"000000\"><Desc>6 symbols with format ######</Desc></Arg><Arg Name=\"QRSymbol\" Value=\",\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'E'> <;> <RcpNum[6]> <;> <QRSymbol[',']> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"ProgParameters\" CmdByte=\"0x45\"><FPOperation>Programs the number of POS, printing of logo, cash drawer opening, cutting permission, external display management mode, article report type, enable or disable currency in receipt, EJ font type and working operators counter.</FPOperation><Args><Arg Name=\"POSNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for number of POS in format ####</Desc></Arg><Arg Name=\"OptionPrintLogo\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol of value:  - '1' - Yes  - '0' - No</Desc></Arg><Arg Name=\"OptionAutoOpenDrawer\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol of value:  - '1' - Yes  - '0' - No</Desc></Arg><Arg Name=\"OptionAutoCut\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol of value:  - '1' - Yes  - '0' - No</Desc></Arg><Arg Name=\"OptionExternalDispManagement\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Auto\" Value=\"0\" /><Option Name=\"Manual\" Value=\"1\" /></Options><Desc>1 symbol of value:  - '1' - Manual  - '0' - Auto</Desc></Arg><Arg Name=\"OptionArticleReportType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Brief\" Value=\"0\" /><Option Name=\"Detailed\" Value=\"1\" /></Options><Desc>1 symbol of value:  - '1' - Detailed  - '0' - Brief</Desc></Arg><Arg Name=\"OptionEnableCurrency\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol of value:  - '1' - Yes  - '0' - No</Desc></Arg><Arg Name=\"OptionEJFontType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Low Font\" Value=\"1\" /><Option Name=\"Normal Font\" Value=\"0\" /></Options><Desc>1 symbol of value:  - '1' - Low Font  - '0' - Normal Font</Desc></Arg><Arg Name=\"reserved\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionWorkOperatorCount\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"More\" Value=\"0\" /><Option Name=\"One\" Value=\"1\" /></Options><Desc>1 symbol of value:  - '1' - One  - '0' - More</Desc></Arg><ArgsFormatRaw><![CDATA[ <POSNum[4]> <;> <PrintLogo[1]> <;> <AutoOpenDrawer[1]> <;> <AutoCut[1]> <;> <ExternalDispManagement[1]> <;> <ArticleReportType[1]> <;> <EnableCurrency[1]> <;> <EJFontType[1]> <;> <reserved['0']> <;> <WorkOperatorCount[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"PrintEJByRcpNumCustom\" CmdByte=\"0x7C\"><FPOperation>Print Electronic Journal Report from receipt number to receipt number and selected documents content. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.</FPOperation><Args><Arg Name=\"\" Value=\"j1\" Type=\"OptionHardcoded\" MaxLen=\"2\" /><Arg Name=\"\" Value=\"X\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"FlagsReceipts\" Value=\"\" Type=\"Flags\" MaxLen=\"1\"><Desc>1 symbol for Receipts included in EJ: Flags.7=0 Flags.6=1 Flags.5=1 Yes, Flags.5=0 No (Include PO) Flags.4=1 Yes, Flags.4=0 No (Include RA) Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) Flags.1=1 Yes, Flags.1=0 No (Include Invoice) Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)</Desc></Arg><Arg Name=\"FlagsReports\" Value=\"\" Type=\"Flags\" MaxLen=\"1\"><Desc>1 symbol for Reports included in EJ: Flags.7=0 Flags.6=1 Flags.5=0 Flags.4=1 Yes, Flags.4=0 No (Include FM reports) Flags.3=1 Yes, Flags.3=0 No (Include Other reports) Flags.2=1 Yes, Flags.2=0 No (Include Daily X) Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)</Desc></Arg><Arg Name=\"\" Value=\"N\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"StartRcpNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"000000.\"><Desc>6 symbols for initial receipt number included in report in format ######.</Desc></Arg><Arg Name=\"EndRcpNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"000000.\"><Desc>6 symbols for final receipt number included in report in format ######.</Desc></Arg><ArgsFormatRaw><![CDATA[ <'j1'> <;> <'X'> <;> <FlagsReceipts [1]> <;> <FlagsReports [1]> <;> <'N'> <;> <StartRcpNum[6]> <;> <EndRcpNum[6]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"StartTest_Lan\" CmdByte=\"0x4E\"><FPOperation>Start LAN test on the device and print out the result</FPOperation><Args><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"T\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"T\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'R'><;><'T'><;><'T'> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadDepartment\" CmdByte=\"0x67\"><FPOperation>Provides information for the programmed data, the turnover from the stated department number</FPOperation><Args><Arg Name=\"DepNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"3\" Format=\"000\"><Desc>3 symbols for department number in format ###</Desc></Arg><ArgsFormatRaw><![CDATA[ <DepNum[3..3]> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"DepNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"3\" Format=\"000\"><Desc>3 symbols for department number in format ###</Desc></Res><Res Name=\"DepName\" Value=\"\" Type=\"Text\" MaxLen=\"20\"><Desc>20 symbols for department name</Desc></Res><Res Name=\"OptionVATClass\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Forbidden\" Value=\"*\" /><Option Name=\"VAT Class 0\" Value=\"А\" /><Option Name=\"VAT Class 1\" Value=\"Б\" /><Option Name=\"VAT Class 2\" Value=\"В\" /><Option Name=\"VAT Class 3\" Value=\"Г\" /><Option Name=\"VAT Class 4\" Value=\"Д\" /><Option Name=\"VAT Class 5\" Value=\"Е\" /><Option Name=\"VAT Class 6\" Value=\"Ж\" /><Option Name=\"VAT Class 7\" Value=\"З\" /></Options><Desc>1 character for VAT class:  - 'А' - VAT Class 0  - 'Б' - VAT Class 1  - 'В' - VAT Class 2  - 'Г' - VAT Class 3  - 'Д' - VAT Class 4  - 'Е' - VAT Class 5  - 'Ж' - VAT Class 6  - 'З' - VAT Class 7  - '*' - Forbidden</Desc></Res><Res Name=\"Turnover\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for accumulated turnover of the article</Desc></Res><Res Name=\"SoldQuantity\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for sold quantity of the department</Desc></Res><Res Name=\"LastZReportNumber\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for the number of last Z Report</Desc></Res><Res Name=\"LastZReportDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"dd-MM-yyyy HH:mm\"><Desc>16 symbols for date and hour on last Z Report in format  \"DD-MM-YYYY HH:MM\"</Desc></Res><ResFormatRaw><![CDATA[<DepNum[3..3]> <;> <DepName[20]> <;> <OptionVATClass[1]> <;> <Turnover[1..13]> <;> <SoldQuantity[1..13]> <;> <LastZReportNumber[1..5]> <;> <LastZReportDate \"DD-MM-YYYY HH:MM\">]]></ResFormatRaw></Response></Command><Command Name=\"ReadTransferAmountParam_RA\" CmdByte=\"0x4F\"><FPOperation>Provide information about parameter for automatic transfer of daily available amounts.</FPOperation><Args><Arg Name=\"\" Value=\"A\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'A'> <;> <'R'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"A\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OptionTransferAmount\" Value=\"\" Type=\"Text\" MaxLen=\"1\"><Desc>1 symbol with value:  - '0' - No  - '1' - Yes</Desc></Res><ResFormatRaw><![CDATA[<'A'> <;> <'R'> <;> <OptionTransferAmount[1]>]]></ResFormatRaw></Response></Command><Command Name=\"OpenElectronicInvoiceWithFreeCustomerData\" CmdByte=\"0x30\"><FPOperation>Opens an electronic fiscal invoice receipt with 1 minute timeout assigned to the specified operator number and operator password with free info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.</FPOperation><Args><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbol from 1 to 20 corresponding to operator's number</Desc></Arg><Arg Name=\"OperPass\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for operator's password</Desc></Arg><Arg Name=\"reserved\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"reserved\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"InvoicePrintType\" Value=\"9\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"Recipient\" Value=\"\" Type=\"Text\" MaxLen=\"26\"><Desc>26 symbols for Invoice recipient</Desc></Arg><Arg Name=\"Buyer\" Value=\"\" Type=\"Text\" MaxLen=\"16\"><Desc>16 symbols for Invoice buyer</Desc></Arg><Arg Name=\"VATNumber\" Value=\"\" Type=\"Text\" MaxLen=\"13\"><Desc>13 symbols for customer Fiscal number</Desc></Arg><Arg Name=\"UIC\" Value=\"\" Type=\"Text\" MaxLen=\"13\"><Desc>13 symbols for customer Unique Identification Code</Desc></Arg><Arg Name=\"Address\" Value=\"\" Type=\"Text\" MaxLen=\"30\"><Desc>30 symbols for Address</Desc></Arg><Arg Name=\"OptionUICType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Bulstat\" Value=\"0\" /><Option Name=\"EGN\" Value=\"1\" /><Option Name=\"Foreigner Number\" Value=\"2\" /><Option Name=\"NRA Official Number\" Value=\"3\" /></Options><Desc>1 symbol for type of Unique Identification Code:  - '0' - Bulstat  - '1' - EGN  - '2' - Foreigner Number  - '3' - NRA Official Number</Desc></Arg><Arg Name=\"UniqueReceiptNumber\" Value=\"\" Type=\"Text\" MaxLen=\"24\"><Desc>Up to 24 symbols for unique receipt number. NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, * YYYYYYY - 7 symbols [0-9] for next number of the receipt</Desc><Meta MinLen=\"24\" Compulsory=\"false\" ValIndicatingPresence=\"$\" /></Arg><ArgsFormatRaw><![CDATA[ <OperNum[1..2]> <;> <OperPass[6]> <;> <reserved['0']> <;> <reserved['0']> <;> <InvoicePrintType['9']> <;> <Recipient[26]> <;> <Buyer[16]> <;> <VATNumber[13]> <;> <UIC[13]> <;> <Address[30]> <;> <UICType[1]> { <'$'> <UniqueReceiptNumber[24]>} ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadLastReceiptNum\" CmdByte=\"0x71\"><FPOperation>Read the total counter of last issued receipt.</FPOperation><Response ACK=\"false\"><Res Name=\"TotalReceiptCounter\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"000000\"><Desc>6 symbols for the total receipt counter in format ######  up to current last issued receipt by FD</Desc></Res><ResFormatRaw><![CDATA[<TotalReceiptCounter[6]>]]></ResFormatRaw></Response></Command><Command Name=\"SetCustomerUIC\" CmdByte=\"0x41\"><FPOperation>Stores the Unique Identification Code (UIC) and UIC type into the operative memory.</FPOperation><Args><Arg Name=\"Password\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6-symbols string</Desc></Arg><Arg Name=\"\" Value=\"1\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"UIC\" Value=\"\" Type=\"Text\" MaxLen=\"13\"><Desc>13 symbols for UIC</Desc></Arg><Arg Name=\"OptionUICType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Bulstat\" Value=\"0\" /><Option Name=\"EGN\" Value=\"1\" /><Option Name=\"Foreigner Number\" Value=\"2\" /><Option Name=\"NRA Official Number\" Value=\"3\" /></Options><Desc>1 symbol for type of UIC number:  - '0' - Bulstat  - '1' - EGN  - '2' - Foreigner Number  - '3' - NRA Official Number</Desc></Arg><ArgsFormatRaw><![CDATA[ <Password[6]> <;> <'1'> <;> <UIC[13]> <;> <UICType[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadEJByReceiptNum\" CmdByte=\"0x7C\"><FPOperation>Read Electronic Journal Report from receipt number to receipt number.</FPOperation><Args><Arg Name=\"OptionReportFormat\" Value=\"\" Type=\"Option\" MaxLen=\"2\"><Options><Option Name=\"Brief EJ\" Value=\"J8\" /><Option Name=\"Detailed EJ\" Value=\"J0\" /></Options><Desc>1 character with value  - 'J0' - Detailed EJ  - 'J8' - Brief EJ</Desc></Arg><Arg Name=\"\" Value=\"N\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"StartRcpNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"000000\"><Desc>6 symbols for initial receipt number included in report in format ######</Desc></Arg><Arg Name=\"EndRcpNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"000000\"><Desc>6 symbols for final receipt number included in report in format ######</Desc></Arg><ArgsFormatRaw><![CDATA[ <ReportFormat[2]> <;> <'N'> <;> <StartRcpNum[6]> <;> <EndRcpNum[6]> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"ProgPLUgeneral\" CmdByte=\"0x4B\"><FPOperation>Programs the general data for a certain article in the internal database. The price may have variable length, while the name field is fixed.</FPOperation><Args><Arg Name=\"PLUNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"5\" Format=\"00000\"><Desc>5 symbols for article number in format: #####</Desc></Arg><Arg Name=\"Option\" Value=\"#@1+$\" Type=\"OptionHardcoded\" MaxLen=\"5\" /><Arg Name=\"Name\" Value=\"\" Type=\"Text\" MaxLen=\"34\"><Desc>34 symbols for article name</Desc></Arg><Arg Name=\"Price\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for article price</Desc></Arg><Arg Name=\"OptionPrice\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Free price is disable valid only programmed price\" Value=\"0\" /><Option Name=\"Free price is enable\" Value=\"1\" /><Option Name=\"Limited price\" Value=\"2\" /></Options><Desc>1 symbol for price flag with next value:  - '0'- Free price is disable valid only programmed price  - '1'- Free price is enable  - '2'- Limited price</Desc></Arg><Arg Name=\"OptionVATClass\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Forbidden\" Value=\"*\" /><Option Name=\"VAT Class 0\" Value=\"А\" /><Option Name=\"VAT Class 1\" Value=\"Б\" /><Option Name=\"VAT Class 2\" Value=\"В\" /><Option Name=\"VAT Class 3\" Value=\"Г\" /><Option Name=\"VAT Class 4\" Value=\"Д\" /><Option Name=\"VAT Class 5\" Value=\"Е\" /><Option Name=\"VAT Class 6\" Value=\"Ж\" /><Option Name=\"VAT Class 7\" Value=\"З\" /></Options><Desc>1 character for VAT class:  - 'А' - VAT Class 0  - 'Б' - VAT Class 1  - 'В' - VAT Class 2  - 'Г' - VAT Class 3  - 'Д' - VAT Class 4  - 'Е' - VAT Class 5  - 'Ж' - VAT Class 6  - 'З' - VAT Class 7  - '*' - Forbidden</Desc></Arg><Arg Name=\"BelongToDepNum\" Value=\"\" Type=\"Decimal_plus_80h\" MaxLen=\"2\"><Desc>BelongToDepNum + 80h, 1 symbol for article department attachment, formed in the following manner: BelongToDepNum[HEX] + 80h example: Dep01 = 81h, Dep02 = 82h … Dep19 = 93h Department range from 1 to 127</Desc></Arg><Arg Name=\"OptionSingleTransaction\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Active Single transaction in receipt\" Value=\"1\" /><Option Name=\"Inactive, default value\" Value=\"0\" /></Options><Desc>1 symbol with value:  - '0' - Inactive, default value  - '1' - Active Single transaction in receipt</Desc></Arg><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Option['#@1+$']> <;> <Name[34]> <;> <Price[1..10]> <;> <OptionPrice[1]> <;> <OptionVATClass[1]> <;> <BelongToDepNum[1]> <;> <SingleTransaction[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"PrintDiscountOrAddition\" CmdByte=\"0x3E\"><FPOperation>Percent or value discount/addition over sum of transaction or over subtotal sum specified by field \"Type\".</FPOperation><Args><Arg Name=\"OptionType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Defined from the device\" Value=\"2\" /><Option Name=\"Over subtotal\" Value=\"1\" /><Option Name=\"Over transaction sum\" Value=\"0\" /></Options><Desc>1 symbol with value  - '2' - Defined from the device  - '1' - Over subtotal - '0' - Over transaction sum</Desc></Arg><Arg Name=\"OptionSubtotal\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol with value  - '1' - Yes  - '0' - No</Desc></Arg><Arg Name=\"DiscAddV\" Value=\"\" Type=\"Decimal\" MaxLen=\"8\"><Desc>Up to 8 symbols for the value of the discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\":\" /></Arg><Arg Name=\"DiscAddP\" Value=\"\" Type=\"Decimal\" MaxLen=\"7\"><Desc>Up to 7 symbols for the percentage value of the discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\",\" /></Arg><ArgsFormatRaw><![CDATA[ <Type[1]> <;> <OptionSubtotal[1]> {<':'> <DiscAddV[1..8]>} {<','> <DiscAddP[1..7]>} ]]></ArgsFormatRaw></Args></Command><Command Name=\"ProgPayment_Old\" CmdByte=\"0x44\"><FPOperation>Preprogram the name of the type of payment. Command works for KL version 2 devices.</FPOperation><Args><Arg Name=\"OptionNumber\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Payment 1\" Value=\"1\" /><Option Name=\"Payment 2\" Value=\"2\" /><Option Name=\"Payment 3\" Value=\"3\" /><Option Name=\"Payment 4\" Value=\"4\" /></Options><Desc>1 symbol for payment type  - '1' - Payment 1  - '2' - Payment 2  - '3' - Payment 3  - '4' - Payment 4</Desc></Arg><Arg Name=\"Name\" Value=\"\" Type=\"Text\" MaxLen=\"10\"><Desc>10 symbols for payment type name. Only the first 6 are printable and only relevant for CodePayment '9' and ':'</Desc></Arg><Arg Name=\"Rate\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"10\" Format=\"0000.00000\"><Desc>Up to 10 symbols for exchange rate in format: ####.##### of the 4th payment type, maximal value 0420.00000</Desc></Arg><Arg Name=\"OptionCodePayment\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Bank\" Value=\"8\" /><Option Name=\"Card\" Value=\"7\" /><Option Name=\"Check\" Value=\"1\" /><Option Name=\"Damage\" Value=\"6\" /><Option Name=\"Packaging\" Value=\"4\" /><Option Name=\"Programming Name1\" Value=\"9\" /><Option Name=\"Programming Name2\" Value=\":\" /><Option Name=\"Service\" Value=\"5\" /><Option Name=\"Talon\" Value=\"2\" /><Option Name=\"V. Talon\" Value=\"3\" /></Options><Desc>1 symbol for code payment type with name:  - '1' - Check  - '2' - Talon  - '3' - V. Talon  - '4' - Packaging  - '5' - Service  - '6' - Damage  - '7' - Card  - '8' - Bank  - '9' - Programming Name1  - ':' - Programming Name2</Desc></Arg><ArgsFormatRaw><![CDATA[ <Number[1]><;><Name[10]><;><Rate[1..10]><;><CodePayment[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"PrintOrStoreEJ\" CmdByte=\"0x7C\"><FPOperation>Print or store Electronic Journal report with all documents.</FPOperation><Args><Arg Name=\"OptionReportStorage\" Value=\"\" Type=\"Option\" MaxLen=\"2\"><Options><Option Name=\"Printing\" Value=\"J1\" /><Option Name=\"SD card storage\" Value=\"J4\" /><Option Name=\"USB storage\" Value=\"J2\" /></Options><Desc>1 character with value:  - 'J1' - Printing  - 'J2' - USB storage  - 'J4' - SD card storage</Desc></Arg><Arg Name=\"\" Value=\"*\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <ReportStorage[2]> <;> <'*'> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadWeightBarcodeFormat\" CmdByte=\"0x4F\"><FPOperation>Provide information about weight barcode format.</FPOperation><Args><Arg Name=\"\" Value=\"B\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'B'> <;> <'R'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"B\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OptionBarcodeFormat\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"NNNNcWWWWW\" Value=\"0\" /><Option Name=\"NNNNNWWWWW\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '0' - NNNNcWWWWW  - '1' - NNNNNWWWWW</Desc></Res><ResFormatRaw><![CDATA[<'B'> <;> <'R'> <;> <OptionBarcodeFormat[1]>]]></ResFormatRaw></Response></Command><Command Name=\"CashDrawerOpen\" CmdByte=\"0x2A\"><FPOperation>Opens the cash drawer.</FPOperation></Command><Command Name=\"ReadPLU_Old\" CmdByte=\"0x6B\"><FPOperation>Provides information about the registers of the specified article.</FPOperation><Args><Arg Name=\"PLUNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"5\" Format=\"00000\"><Desc>5 symbols for article number in format #####</Desc></Arg><ArgsFormatRaw><![CDATA[ <PLUNum[5]> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"PLUNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"5\" Format=\"00000\"><Desc>5 symbols for article number format #####</Desc></Res><Res Name=\"PLUName\" Value=\"\" Type=\"Text\" MaxLen=\"20\"><Desc>20 symbols for article name</Desc></Res><Res Name=\"Price\" Value=\"\" Type=\"Decimal\" MaxLen=\"11\"><Desc>Up to 11 symbols for article price</Desc></Res><Res Name=\"OptionVATClass\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Forbidden\" Value=\"*\" /><Option Name=\"VAT Class 0\" Value=\"А\" /><Option Name=\"VAT Class 1\" Value=\"Б\" /><Option Name=\"VAT Class 2\" Value=\"В\" /><Option Name=\"VAT Class 3\" Value=\"Г\" /><Option Name=\"VAT Class 4\" Value=\"Д\" /><Option Name=\"VAT Class 5\" Value=\"Е\" /><Option Name=\"VAT Class 6\" Value=\"Ж\" /><Option Name=\"VAT Class 7\" Value=\"З\" /></Options><Desc>1 character for VAT class:  - 'А' - VAT Class 0  - 'Б' - VAT Class 1  - 'В' - VAT Class 2  - 'Г' - VAT Class 3  - 'Д' - VAT Class 4  - 'Е' - VAT Class 5  - 'Ж' - VAT Class 6  - 'З' - VAT Class 7  - '*' - Forbidden</Desc></Res><Res Name=\"Turnover\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for turnover by this article</Desc></Res><Res Name=\"QuantitySold\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for sold quantity</Desc></Res><Res Name=\"LastZReportNumber\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for the number of last Z Report</Desc></Res><Res Name=\"LastZReportDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"dd-MM-yyyy HH:mm\"><Desc>16 symbols for date and hour on last Z Report in format  DD-MM-YYYY HH:MM</Desc></Res><Res Name=\"BelongToDepNumber\" Value=\"\" Type=\"Decimal_plus_80h\" MaxLen=\"2\"><Desc>BelongToDepNumber + 80h, 1 symbol for article department attachment, formed in the following manner: BelongToDepNumber[HEX] + 80h example: Dep01 = 81h, Dep02 = 82h … Dep19 = 93h Department range from 1 to 127</Desc></Res><ResFormatRaw><![CDATA[<PLUNum[5]> <;> <PLUName[20]> <;> <Price[1..11]> <;> <OptionVATClass[1]> <;> <Turnover[1..13]> <;> <QuantitySold[1..13]> <;> <LastZReportNumber[1..5]> <;> <LastZReportDate \"DD-MM-YYYY HH:MM\"> <;> <BelongToDepNumber[1]>]]></ResFormatRaw></Response></Command><Command Name=\"PrintDetailedFMPaymentsReportByZBlocks\" CmdByte=\"0x78\"><FPOperation>Print a detailed FM payments report by initial and end Z report number.</FPOperation><Args><Arg Name=\"StartZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for initial FM report number included in report, format ####</Desc></Arg><Arg Name=\"EndZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for final FM report number included in report, format ####</Desc></Arg><Arg Name=\"Option\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <Option['P']> ]]></ArgsFormatRaw></Args></Command><Command Name=\"SetTCPpassword\" CmdByte=\"0x4E\"><FPOperation>Program device's TCP password. To apply use - SaveNetworkSettings()</FPOperation><Args><Arg Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"Z\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"1\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"PassLength\" Value=\"\" Type=\"Decimal\" MaxLen=\"3\"><Desc>Up to 3 symbols for the password len</Desc></Arg><Arg Name=\"Password\" Value=\"\" Type=\"Text\" MaxLen=\"100\"><Desc>Up to 100 symbols for the TCP password</Desc></Arg><ArgsFormatRaw><![CDATA[ <'P'><;><'Z'><;><'1'><;><PassLength[1..3]><;><Password[100]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"DisplayTextLines1and2\" CmdByte=\"0x27\"><FPOperation>Shows a 20-symbols text in the first line and last 20-symbols text in the second line of the external display lines.</FPOperation><Args><Arg Name=\"Text\" Value=\"\" Type=\"Text\" MaxLen=\"40\"><Desc>40 symbols text</Desc></Arg><ArgsFormatRaw><![CDATA[ <Text[40]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"SellPLUFromFD_DB\" CmdByte=\"0x32\"><FPOperation>Register the sell or correction with specified quantity of article from the internal FD database. The FD will perform a correction operation only if the same quantity of the article has already been sold.</FPOperation><Args><Arg Name=\"OptionSign\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Correction\" Value=\"-\" /><Option Name=\"Sale\" Value=\"+\" /></Options><Desc>1 symbol with optional value:  - '+' -Sale  - '-' - Correction</Desc><Meta MinLen=\"1\" Compulsory=\"true\" NoSemiColumnSeparatorAfterIt=\"true\" /></Arg><Arg Name=\"PLUNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"5\" Format=\"00000\"><Desc>5 symbols for PLU number of FD's database in format #####</Desc></Arg><Arg Name=\"Price\" Value=\"\" Type=\"Decimal\" MaxLen=\"8\"><Desc>Up to 10 symbols for sale price</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\"$\" /></Arg><Arg Name=\"Quantity\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for article's quantity sold</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\"*\" /></Arg><Arg Name=\"DiscAddP\" Value=\"\" Type=\"Decimal\" MaxLen=\"7\"><Desc>Up to 7 for percentage of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\",\" /></Arg><Arg Name=\"DiscAddV\" Value=\"\" Type=\"Decimal\" MaxLen=\"8\"><Desc>Up to 8 symbolsfor percentage of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\":\" /></Arg><ArgsFormatRaw><![CDATA[ <OptionSign[1]> <PLUNum[5]> {<'$'> <Price[1..8]>} {<'*'> <Quantity[1..10]>} {<','> <DiscAddP[1..7]>} {<':'> <DiscAddV[1..8]>} ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadDateTime\" CmdByte=\"0x68\"><FPOperation>Provides information about the current date and time.</FPOperation><Response ACK=\"false\"><Res Name=\"DateTime\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"dd-MM-yyyy HH:mm\"><Desc>Date Time parameter in format: DD-MM-YYYY HH:MM</Desc></Res><ResFormatRaw><![CDATA[<DateTime \"DD-MM-YYYY HH:MM\">]]></ResFormatRaw></Response></Command><Command Name=\"PayExactSum\" CmdByte=\"0x35\"><FPOperation>Register the payment in the receipt with specified type of payment and exact amount received.</FPOperation><Args><Arg Name=\"OptionPaymentType\" Value=\"\" Type=\"Option\" MaxLen=\"2\"><Options><Option Name=\"Payment 0\" Value=\"0\" /><Option Name=\"Payment 1\" Value=\"1\" /><Option Name=\"Payment 10\" Value=\"10\" /><Option Name=\"Payment 11\" Value=\"11\" /><Option Name=\"Payment 2\" Value=\"2\" /><Option Name=\"Payment 3\" Value=\"3\" /><Option Name=\"Payment 4\" Value=\"4\" /><Option Name=\"Payment 5\" Value=\"5\" /><Option Name=\"Payment 6\" Value=\"6\" /><Option Name=\"Payment 7\" Value=\"7\" /><Option Name=\"Payment 8\" Value=\"8\" /><Option Name=\"Payment 9\" Value=\"9\" /></Options><Desc>1 symbol for payment type:  - '0' - Payment 0  - '1' - Payment 1  - '2' - Payment 2  - '3' - Payment 3  - '4' - Payment 4  - '5' - Payment 5  - '6' - Payment 6  - '7' - Payment 7  - '8' - Payment 8  - '9' - Payment 9  - '10' - Payment 10  - '11' - Payment 11</Desc></Arg><Arg Name=\"Option\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"Amount\" Value=\"&quot;\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <PaymentType[1..2]> <;> <Option['0']> <;> <Amount['\"']> ]]></ArgsFormatRaw></Args></Command><Command Name=\"StartTest_WiFi\" CmdByte=\"0x4E\"><FPOperation>Start WiFi test on the device and print out the result</FPOperation><Args><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"W\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"T\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'R'><;><'W'><;><'T'> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadFMfreeRecords\" CmdByte=\"0x74\"><FPOperation>Read the number of the remaining free records for Z-report in the Fiscal Memory.</FPOperation><Response ACK=\"false\"><Res Name=\"FreeFMrecords\" Value=\"\" Type=\"Text\" MaxLen=\"4\"><Desc>4 symbols for the number of free records for Z-report in the FM</Desc></Res><ResFormatRaw><![CDATA[<FreeFMrecords[4]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadBluetooth_Password\" CmdByte=\"0x4E\"><FPOperation>Provides information about device's Bluetooth password.</FPOperation><Args><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"B\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'R'><;><'B'><;><'P'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"B\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"PassLength\" Value=\"\" Type=\"Decimal\" MaxLen=\"3\"><Desc>(Length) Up to 3 symbols for the BT password length</Desc></Res><Res Name=\"Password\" Value=\"\" Type=\"Text\" MaxLen=\"100\"><Desc>Up to 100 symbols for the BT password</Desc></Res><ResFormatRaw><![CDATA[<'R'><;><'B'><;><'P'><;><PassLength[1..3]><;><Password[100]>]]></ResFormatRaw></Response></Command><Command Name=\"CancelReceipt\" CmdByte=\"0x39\"><FPOperation>Available only if receipt is not closed. Void all sales in the receipt and close the fiscal receipt (Fiscal receipt, Invoice receipt, Storno receipt or Credit Note). If payment is started, then finish payment and close the receipt.</FPOperation></Command><Command Name=\"SellPLUfromDep\" CmdByte=\"0x31\"><FPOperation>Register the sell (for correction use minus sign in the price field) of article belonging to department with specified name, price, quantity and/or discount/addition on the transaction. The VAT of article got from department to which article belongs.</FPOperation><Args><Arg Name=\"NamePLU\" Value=\"\" Type=\"Text\" MaxLen=\"36\"><Desc>36 symbols for article's name. 34 symbols are printed on paper. Symbol 0x7C '|' is new line separator.</Desc></Arg><Arg Name=\"reserved\" Value=\" \" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"Price\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for article's price. Use minus sign '-' for correction</Desc></Arg><Arg Name=\"Quantity\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for quantity</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\"*\" /></Arg><Arg Name=\"DiscAddP\" Value=\"\" Type=\"Decimal\" MaxLen=\"7\"><Desc>Up to 7 symbols for percentage of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\",\" /></Arg><Arg Name=\"DiscAddV\" Value=\"\" Type=\"Decimal\" MaxLen=\"8\"><Desc>Up to 8 symbols for value of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\":\" /></Arg><Arg Name=\"DepNum\" Value=\"\" Type=\"Decimal_plus_80h\" MaxLen=\"2\"><Desc>1 symbol for article department attachment, formed in the following manner; example: Dep01=81h, Dep02=82h … Dep19=93h Department range from 1 to 127</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\"!\" /></Arg><ArgsFormatRaw><![CDATA[ <NamePLU[36]> <;> <reserved[' ']> <;> <Price[1..10]> {<'*'> <Quantity[1..10]>} {<','> <DiscAddP[1..7]>} {<':'> <DiscAddV[1..8]>} {<'!'> <DepNum[1]>} ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadInvoiceRange\" CmdByte=\"0x70\"><FPOperation>Provide information about invoice start and end numbers range.</FPOperation><Response ACK=\"false\"><Res Name=\"StartNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"10\" Format=\"0000000000\"><Desc>10 symbols for start No with leading zeroes in format ##########</Desc></Res><Res Name=\"EndNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"10\" Format=\"0000000000\"><Desc>10 symbols for end No with leading zeroes in format ##########</Desc></Res><ResFormatRaw><![CDATA[<StartNum[10]> <;> <EndNum[10]>]]></ResFormatRaw></Response></Command><Command Name=\"PrintSpecialEventsFMreport\" CmdByte=\"0x77\"><FPOperation>Print whole special FM events report.</FPOperation></Command><Command Name=\"Read_IdleTimeout\" CmdByte=\"0x4E\"><FPOperation>Provides information about device's idle timeout. This timeout is seconds in which the connection will be closed when there is an inactivity. This information is available if the device has LAN or WiFi. Maximal value - 7200, minimal value 0. 0 is for never close the connection.</FPOperation><Args><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"Z\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"I\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'R'><;><'Z'><;><'I'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"Z\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"I\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"IdleTimeout\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for password in format ####</Desc></Res><ResFormatRaw><![CDATA[<'R'><;><'Z'><;><'I'><;><IdleTimeout[4]>]]></ResFormatRaw></Response></Command><Command Name=\"OpenStornoReceipt\" CmdByte=\"0x30\"><FPOperation>Open a fiscal storno receipt assigned to the specified operator number and operator password, parameters for receipt format, print VAT, printing type and parameters for the related storno receipt.</FPOperation><Args><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Arg><Arg Name=\"OperPass\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for operator's password</Desc></Arg><Arg Name=\"OptionReceiptFormat\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Brief\" Value=\"0\" /><Option Name=\"Detailed\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '1' - Detailed  - '0' - Brief</Desc></Arg><Arg Name=\"OptionPrintVAT\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '1' - Yes  - '0' - No</Desc></Arg><Arg Name=\"OptionStornoRcpPrintType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Buffered Printing\" Value=\"D\" /><Option Name=\"Postponed Printing\" Value=\"B\" /><Option Name=\"Step by step printing\" Value=\"@\" /></Options><Desc>1 symbol with value: - '@' - Step by step printing - 'B' - Postponed Printing - 'D' - Buffered Printing</Desc></Arg><Arg Name=\"OptionStornoReason\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Goods Claim or Goods return\" Value=\"1\" /><Option Name=\"Operator error\" Value=\"0\" /><Option Name=\"Tax relief\" Value=\"2\" /></Options><Desc>1 symbol for reason of storno operation with value:  - '0' - Operator error  - '1' - Goods Claim or Goods return  - '2' - Tax relief</Desc></Arg><Arg Name=\"RelatedToRcpNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"6\"><Desc>Up to 6 symbols for issued receipt number</Desc></Arg><Arg Name=\"RelatedToRcpDateTime\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"dd-MM-yy HH:mm:ss\"><Desc>17 symbols for Date and Time of the issued receipt in format DD-MM-YY HH:MM:SS</Desc></Arg><Arg Name=\"FMNum\" Value=\"\" Type=\"Text\" MaxLen=\"8\"><Desc>8 symbols for number of the Fiscal Memory</Desc></Arg><Arg Name=\"RelatedToURN\" Value=\"\" Type=\"Text\" MaxLen=\"24\"><Desc>Up to 24 symbols for the issed receipt unique receipt number. NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, * YYYYYYY - 7 symbols [0-9] for next number of the receipt</Desc><Meta MinLen=\"24\" Compulsory=\"false\" ValIndicatingPresence=\";\" /></Arg><ArgsFormatRaw><![CDATA[<OperNum[1..2]> <;> <OperPass[6]> <;> <ReceiptFormat[1]> <;> <PrintVAT[1]> <;> <StornoRcpPrintType[1]> <;> <StornoReason[1]> <;> <RelatedToRcpNum[1..6]> <;> <RelatedToRcpDateTime \"DD-MM-YY HH:MM:SS\"> <;> <FMNum[8]> {<;> <RelatedToURN[24]>} ]]></ArgsFormatRaw></Args></Command><Command Name=\"ProgOperator\" CmdByte=\"0x4A\"><FPOperation>Programs the operator's name and password.</FPOperation><Args><Arg Name=\"Number\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from '1' to '20' corresponding to operator's number</Desc></Arg><Arg Name=\"Name\" Value=\"\" Type=\"Text\" MaxLen=\"20\"><Desc>20 symbols for operator's name</Desc></Arg><Arg Name=\"Password\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for operator's password</Desc></Arg><ArgsFormatRaw><![CDATA[ <Number[1..2]> <;> <Name[20]> <;> <Password[6]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadPayments_Old\" CmdByte=\"0x64\"><FPOperation>Provides information about all programmed types of payment. Command works for KL version 2 devices.</FPOperation><Response ACK=\"false\"><Res Name=\"NamePaym0\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for payment name type 0</Desc></Res><Res Name=\"NamePaym1\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for payment name type 1</Desc></Res><Res Name=\"NamePaym2\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for payment name type 2</Desc></Res><Res Name=\"NamePaym3\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for payment name type 3</Desc></Res><Res Name=\"NamePaym4\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for payment name type 4</Desc></Res><Res Name=\"ExRate\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"10\" Format=\"0000.00000\"><Desc>Up to10 symbols for exchange rate of payment type 4 in format: ####.#####</Desc></Res><Res Name=\"CodePaym0\" Value=\"\" Type=\"Text\" MaxLen=\"1\"><Desc>1 symbol for code of payment 0 = 0xFF (currency in cash)</Desc></Res><Res Name=\"CodePaym1\" Value=\"\" Type=\"Text\" MaxLen=\"1\"><Desc>1 symbol for code of payment 1 (default value is '7')</Desc></Res><Res Name=\"CodePaym2\" Value=\"\" Type=\"Text\" MaxLen=\"1\"><Desc>1 symbol for code of payment 2 (default value is '1')</Desc></Res><Res Name=\"CodePaym3\" Value=\"\" Type=\"Text\" MaxLen=\"1\"><Desc>1 symbol for code of payment 3 (default value is '2')</Desc></Res><Res Name=\"CodePaym4\" Value=\"\" Type=\"Text\" MaxLen=\"1\"><Desc>1 symbol for code of payment 4 = 0xFF (currency in cash)</Desc></Res><ResFormatRaw><![CDATA[<NamePaym0[6]> <;> <NamePaym1[6]> <;> <NamePaym2[6]> <;> <NamePaym3[6]> <;> <NamePaym4[6]><;><ExRate[1..10]> <;> <CodePaym0[1]><;> <CodePaym1[1]><;> <CodePaym2[1]><;> <CodePaym3[1]> <;> <CodePaym4[1]>]]></ResFormatRaw></Response></Command><Command Name=\"PrintEJByDateCustom\" CmdByte=\"0x7C\"><FPOperation>Print Electronic Journal Report by initial and end date, and selected documents content. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.</FPOperation><Args><Arg Name=\"\" Value=\"j1\" Type=\"OptionHardcoded\" MaxLen=\"2\" /><Arg Name=\"\" Value=\"X\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"FlagsReceipts\" Value=\"\" Type=\"Flags\" MaxLen=\"1\"><Desc>1 symbol for Receipts included in EJ: Flags.7=0 Flags.6=1 Flags.5=1 Yes, Flags.5=0 No (Include PO) Flags.4=1 Yes, Flags.4=0 No (Include RA) Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) Flags.1=1 Yes, Flags.1=0 No (Include Invoice) Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)</Desc></Arg><Arg Name=\"FlagsReports\" Value=\"\" Type=\"Flags\" MaxLen=\"1\"><Desc>1 symbol for Reports included in EJ: Flags.7=0 Flags.6=1 Flags.5=0 Flags.4=1 Yes, Flags.4=0 No (Include FM reports) Flags.3=1 Yes, Flags.3=0 No (Include Other reports) Flags.2=1 Yes, Flags.2=0 No (Include Daily X) Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)</Desc></Arg><Arg Name=\"\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"StartRepFromDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name=\"EndRepFromDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><ArgsFormatRaw><![CDATA[<'j1'> <;> <'X'> <;> <FlagsReceipts [1]> <;> <FlagsReports [1]> <;> <'D'> <;> <StartRepFromDate \"DDMMYY\"> <;> <EndRepFromDate \"DDMMYY\"> ]]></ArgsFormatRaw></Args></Command><Command Name=\"SetBluetooth_Password\" CmdByte=\"0x4E\"><FPOperation>Program device's Bluetooth password. To apply use - SaveNetworkSettings()</FPOperation><Args><Arg Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"B\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"PassLength\" Value=\"\" Type=\"Decimal\" MaxLen=\"3\"><Desc>Up to 3 symbols for the BT password len</Desc></Arg><Arg Name=\"Password\" Value=\"\" Type=\"Text\" MaxLen=\"100\"><Desc>Up to 100 symbols for the BT password</Desc></Arg><ArgsFormatRaw><![CDATA[ <'P'><;><'B'><;><'P'><;>< PassLength[1..3]><;><Password[100]>> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadPLUqty\" CmdByte=\"0x6B\"><FPOperation>Provides information about the quantity registers of the specified article.</FPOperation><Args><Arg Name=\"PLUNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"5\" Format=\"00000\"><Desc>5 symbols for article number with leading zeroes in format: #####</Desc></Arg><Arg Name=\"Option\" Value=\"2\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Option['2']> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"PLUNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"5\" Format=\"00000\"><Desc>5 symbols for article number with leading zeroes in format #####</Desc></Res><Res Name=\"Option\" Value=\"2\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"AvailableQuantity\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to13 symbols for quantity in stock</Desc></Res><Res Name=\"OptionQuantityType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Availability of PLU stock is not monitored\" Value=\"0\" /><Option Name=\"Disable negative quantity\" Value=\"1\" /><Option Name=\"Enable negative quantity\" Value=\"2\" /></Options><Desc>1 symbol for Quantity flag with next value:  - '0'- Availability of PLU stock is not monitored  - '1'- Disable negative quantity  - '2'- Enable negative quantity</Desc></Res><ResFormatRaw><![CDATA[<PLUNum[5]> <;> <Option['2']> <;> <AvailableQuantity[1..13]> <;> <OptionQuantityType[1]>]]></ResFormatRaw></Response></Command><Command Name=\"ScanAndPrintWiFiNetworks\" CmdByte=\"0x4E\"><FPOperation>Scan and print all available WiFi networks</FPOperation><Args><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"W\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"S\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'R'><;><'W'><;><'S'> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadSerialAndFiscalNums\" CmdByte=\"0x60\"><FPOperation>Provides information about the manufacturing number of the fiscal device and FM number.</FPOperation><Response ACK=\"false\"><Res Name=\"SerialNumber\" Value=\"\" Type=\"Text\" MaxLen=\"8\"><Desc>8 symbols for individual number of the fiscal device</Desc></Res><Res Name=\"FMNumber\" Value=\"\" Type=\"Text\" MaxLen=\"8\"><Desc>8 symbols for individual number of the fiscal memory</Desc></Res><ResFormatRaw><![CDATA[<SerialNumber[8]> <;> <FMNumber[8]>]]></ResFormatRaw></Response></Command><Command Name=\"ReceivedOnAccount_PaidOut\" CmdByte=\"0x3B\"><FPOperation>Registers cash received on account or paid out.</FPOperation><Args><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to the operator's number</Desc></Arg><Arg Name=\"OperPass\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for operator's password</Desc></Arg><Arg Name=\"OptionPayType\" Value=\"\" Type=\"Option\" MaxLen=\"2\"><Options><Option Name=\"Cash\" Value=\"0\" /><Option Name=\"Currency\" Value=\"11\" /></Options><Desc>1 symbol with value  - '0' - Cash  - '11' - Currency</Desc></Arg><Arg Name=\"Amount\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for the amount lodged. Use minus sign for withdrawn</Desc></Arg><Arg Name=\"OptionPrintAvailability\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '0' - No  - '1' - Yes</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\"$\" /></Arg><Arg Name=\"Text\" Value=\"\" Type=\"Text\" MaxLen=\"64\"><Desc>TextLength-2 symbols. In the beginning and in the end of line symbol</Desc><Meta MinLen=\"64\" Compulsory=\"false\" ValIndicatingPresence=\";\" /></Arg><ArgsFormatRaw><![CDATA[<OperNum[1..2]> <;> <OperPass[6]> <;> <PayType[1..2]> <;> <Amount[1..10]> {<'$'> <PrintAvailability[1]> } {<;> <Text[TextLength-2]>} ]]></ArgsFormatRaw></Args></Command><Command Name=\"SaveNetworkSettings\" CmdByte=\"0x4E\"><FPOperation>After every change on Idle timeout, LAN/WiFi/GPRS usage, LAN/WiFi/TCP/GPRS password or TCP auto start networks settings this Save command needs to be execute.</FPOperation><Args><Arg Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"A\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'P'><;><'A'> ]]></ArgsFormatRaw></Args></Command><Command Name=\"DirectCommand\" CmdByte=\"0xF1\"><FPOperation>Executes the direct command .</FPOperation><Args><Arg Name=\"Input\" Value=\"\" Type=\"Text\" MaxLen=\"200\"><Desc>Raw request to FP</Desc></Arg></Args><Response ACK=\"false\"><Res Name=\"Output\" Value=\"\" Type=\"Text\" MaxLen=\"200\"><Desc>FP raw response</Desc></Res></Response></Command><Command Name=\"ReadEJByZBlocks\" CmdByte=\"0x7C\"><FPOperation>Reading Electronic Journal Report by number of Z report blocks.</FPOperation><Args><Arg Name=\"OptionReportFormat\" Value=\"\" Type=\"Option\" MaxLen=\"2\"><Options><Option Name=\"Brief EJ\" Value=\"J8\" /><Option Name=\"Detailed EJ\" Value=\"J0\" /></Options><Desc>1 character with value  - 'J0' - Detailed EJ  - 'J8' - Brief EJ</Desc></Arg><Arg Name=\"\" Value=\"Z\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"StartNo\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for initial number report in format ####</Desc></Arg><Arg Name=\"EndNo\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for final number report in format ####</Desc></Arg><ArgsFormatRaw><![CDATA[ <ReportFormat[2]> <;> <'Z'> <;> <StartNo[4]> <;> <EndNo[4]> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"ReadDailyReturnedChangeAmountsByOperator_Old\" CmdByte=\"0x6F\"><FPOperation>Read the amounts returned as change by different payment types for the specified operator. Command works for KL version 2 devices.</FPOperation><Args><Arg Name=\"\" Value=\"6\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbol from 1 to 20 corresponding to operator's number</Desc></Arg><ArgsFormatRaw><![CDATA[ <'6'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"6\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Res><Res Name=\"ChangeAmountPayment0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by type of payment 0</Desc></Res><Res Name=\"ChangeAmountPayment1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by type of payment 1</Desc></Res><Res Name=\"ChangeAmountPayment2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by type of payment 2</Desc></Res><Res Name=\"ChangeAmountPayment3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by type of payment 3</Desc></Res><Res Name=\"ChangeAmountPayment4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by type of payment 4</Desc></Res><ResFormatRaw><![CDATA[<'6'> <;> <OperNum[1..2]> <;> <ChangeAmountPayment0[1..13]> <;> <ChangeAmountPayment1[1..13]> <;> <ChangeAmountPayment2[1..13]> <;> <ChangeAmountPayment3[1..13]> <;> <ChangeAmountPayment4[1..13]> <;>]]></ResFormatRaw></Response></Command><Command Name=\"ReadCurrentOrLastReceiptPaymentAmounts\" CmdByte=\"0x72\"><FPOperation>Provides information about the payments in current receipt. This command is valid after receipt closing also.</FPOperation><Args><Arg Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'P'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OptionIsReceiptOpened\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '0' - No  - '1' - Yes</Desc></Res><Res Name=\"Payment0Amount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for type 0 payment amount</Desc></Res><Res Name=\"Payment1Amount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for type 1 payment amount</Desc></Res><Res Name=\"Payment2Amount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for type 2 payment amount</Desc></Res><Res Name=\"Payment3Amount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for type 3 payment amount</Desc></Res><Res Name=\"Payment4Amount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for type 4 payment amount</Desc></Res><Res Name=\"Payment5Amount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for type 5 payment amount</Desc></Res><Res Name=\"Payment6Amount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for type 6 payment amount</Desc></Res><Res Name=\"Payment7Amount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for type 7 payment amount</Desc></Res><Res Name=\"Payment8Amount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for type 8 payment amount</Desc></Res><Res Name=\"Payment9Amount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for type 9 payment amount</Desc></Res><Res Name=\"Payment10Amount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for type 10 payment amount</Desc></Res><Res Name=\"Payment11Amount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for type 11 payment amount</Desc></Res><ResFormatRaw><![CDATA[<'P'> <;> <IsReceiptOpened[1]> <;> <Payment0Amount[1..13]> <;> <Payment1Amount[1..13]> <;> <Payment2Amount[1..13]> <;> <Payment3Amount[1..13]> <;> <Payment4Amount[1..13]> <;> <Payment5Amount[1..13]> <;> <Payment6Amount[1..13]> <;> <Payment7Amount[1..13]> <;> <Payment8Amount[1..13]> <;> <Payment9Amount[1..13]> <;> <Payment10Amount[1..13]> <;> <Payment11Amount[1..13]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadDailyReturnedChangeAmounts\" CmdByte=\"0x6E\"><FPOperation>Provides information about the amounts returned as change by type of payment.</FPOperation><Args><Arg Name=\"\" Value=\"6\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'6'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"6\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"AmountPayment0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name=\"AmountPayment1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name=\"AmountPayment2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name=\"AmountPayment3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name=\"AmountPayment4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><Res Name=\"AmountPayment5\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 5</Desc></Res><Res Name=\"AmountPayment6\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 6</Desc></Res><Res Name=\"AmountPayment7\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 7</Desc></Res><Res Name=\"AmountPayment8\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 8</Desc></Res><Res Name=\"AmountPayment9\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 9</Desc></Res><Res Name=\"AmountPayment10\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 10</Desc></Res><Res Name=\"AmountPayment11\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 11</Desc></Res><ResFormatRaw><![CDATA[<'6'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]> <;> <AmountPayment5[1..13]> <;> <AmountPayment6[1..13]> <;> <AmountPayment7[1..13]> <;> <AmountPayment8[1..13]> <;> <AmountPayment9[1..13]> <;> <AmountPayment10[1..13]> <;> <AmountPayment11[1..13]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadDailyReceivedSalesAmountsByOperator_Old\" CmdByte=\"0x6F\"><FPOperation>Read the amounts received from sales by type of payment and specified operator. Command works for KL version 2 devices.</FPOperation><Args><Arg Name=\"\" Value=\"4\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Arg><ArgsFormatRaw><![CDATA[ <'4'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"4\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Res><Res Name=\"ReceivedSalesAmountPayment0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by sales for payment 0</Desc></Res><Res Name=\"ReceivedSalesAmountPayment1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by sales for payment 1</Desc></Res><Res Name=\"ReceivedSalesAmountPayment2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by sales for payment 2</Desc></Res><Res Name=\"ReceivedSalesAmountPayment3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by sales for payment 3</Desc></Res><Res Name=\"ReceivedSalesAmountPayment4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by sales for payment 4</Desc></Res><ResFormatRaw><![CDATA[<'4'> <;> <OperNum[1..2]> <;> <ReceivedSalesAmountPayment0[1..13]> <;> <ReceivedSalesAmountPayment1[1..13]> <;> <ReceivedSalesAmountPayment2[1..13]> <;> <ReceivedSalesAmountPayment3[1..13]> <;> <ReceivedSalesAmountPayment4[1..13]>]]></ResFormatRaw></Response></Command><Command Name=\"PrintBriefFMDepartmentsReportByDate\" CmdByte=\"0x7B\"><FPOperation>Print a brief FM Departments report by initial and end date.</FPOperation><Args><Arg Name=\"StartDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name=\"EndDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name=\"Option\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartDate \"DDMMYY\"> <;> <EndDate \"DDMMYY\"> <;> <Option['D']> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadDepartmentAll\" CmdByte=\"0x67\"><FPOperation>Provides information for the programmed data, the turnovers from the stated department number</FPOperation><Args><Arg Name=\"DepNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"3\" Format=\"000\"><Desc>3 symbols for department number in format ###</Desc></Arg><Arg Name=\"reserved\" Value=\"&quot;\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <DepNum[3..3]> <;> <reserved['\"']> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"DepNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"3\" Format=\"000\"><Desc>3 symbols for department number in format ###</Desc></Res><Res Name=\"reserved\" Value=\"&quot;\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"DepName\" Value=\"\" Type=\"Text\" MaxLen=\"34\"><Desc>20 symbols for department name</Desc></Res><Res Name=\"OptionVATClass\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Forbidden\" Value=\"*\" /><Option Name=\"VAT Class 0\" Value=\"А\" /><Option Name=\"VAT Class 1\" Value=\"Б\" /><Option Name=\"VAT Class 2\" Value=\"В\" /><Option Name=\"VAT Class 3\" Value=\"Г\" /><Option Name=\"VAT Class 4\" Value=\"Д\" /><Option Name=\"VAT Class 5\" Value=\"Е\" /><Option Name=\"VAT Class 6\" Value=\"Ж\" /><Option Name=\"VAT Class 7\" Value=\"З\" /></Options><Desc>1 character for VAT class:  - 'А' - VAT Class 0  - 'Б' - VAT Class 1  - 'В' - VAT Class 2  - 'Г' - VAT Class 3  - 'Д' - VAT Class 4  - 'Е' - VAT Class 5  - 'Ж' - VAT Class 6  - 'З' - VAT Class 7  - '*' - Forbidden</Desc></Res><Res Name=\"Price\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for department price</Desc></Res><Res Name=\"OptionDepPrice\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Free price disabled\" Value=\"0\" /><Option Name=\"Free price disabled for single transaction\" Value=\"4\" /><Option Name=\"Free price enabled\" Value=\"1\" /><Option Name=\"Free price enabled for single transaction\" Value=\"5\" /><Option Name=\"Limited price\" Value=\"2\" /><Option Name=\"Limited price for single transaction\" Value=\"6\" /></Options><Desc>1 symbol for Department flags with next value:  - '0' - Free price disabled  - '1' - Free price enabled  - '2' - Limited price  - '4' - Free price disabled for single transaction  - '5' - Free price enabled for single transaction  - '6' - Limited price for single transaction</Desc></Res><Res Name=\"TurnoverAmount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for accumulated turnover of the article</Desc></Res><Res Name=\"SoldQuantity\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for sold quantity of the department</Desc></Res><Res Name=\"StornoAmount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for accumulated storno amount</Desc></Res><Res Name=\"StornoQuantity\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for accumulated storno quantiy</Desc></Res><Res Name=\"LastZReportNumber\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for the number of last Z Report</Desc></Res><Res Name=\"LastZReportDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"dd-MM-yyyy HH:mm\"><Desc>16 symbols for date and hour on last Z Report in format  \"DD-MM-YYYY HH:MM\"</Desc></Res><ResFormatRaw><![CDATA[<DepNum[3..3]> <;> <reserved['\"']> <;> <DepName[34]> <;> <OptionVATClass[1]> <;> <Price[1..10]> <;> <OptionDepPrice[1]> <;> <TurnoverAmount[1..13]> <;> <SoldQuantity[1..13]> <;> <StornoAmount[1..13]> <;> <StornoQuantity[1..13]> <;> <LastZReportNumber[1..5]> <;> <LastZReportDate \"DD-MM-YYYY HH:MM\">]]></ResFormatRaw></Response></Command><Command Name=\"PrintBriefFMPaymentsReportByZBlocks\" CmdByte=\"0x79\"><FPOperation>Print a brief FM payments report by initial and end FM report number.</FPOperation><Args><Arg Name=\"StartZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for the initial FM report number included in report, format ####</Desc></Arg><Arg Name=\"EndZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for the final FM report number included in report, format ####</Desc></Arg><Arg Name=\"Option\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <Option['P']> ]]></ArgsFormatRaw></Args></Command><Command Name=\"PrintBriefFMPaymentsReportByDate\" CmdByte=\"0x7B\"><FPOperation>Print a brief FM payments report by initial and end date.</FPOperation><Args><Arg Name=\"StartDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name=\"EndDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name=\"Option\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartDate \"DDMMYY\"> <;> <EndDate \"DDMMYY\"> <;> <Option['P']> ]]></ArgsFormatRaw></Args></Command><Command Name=\"SetWiFi_NetworkName\" CmdByte=\"0x4E\"><FPOperation>Program device's WiFi network name where it will connect. To apply use -SaveNetworkSettings()</FPOperation><Args><Arg Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"W\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"N\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"WiFiNameLength\" Value=\"\" Type=\"Decimal\" MaxLen=\"3\"><Desc>Up to 3 symbols for the WiFi network name len</Desc></Arg><Arg Name=\"WiFiNetworkName\" Value=\"\" Type=\"Text\" MaxLen=\"100\"><Desc>Up to 100 symbols for the device's WiFi ssid network name</Desc></Arg><ArgsFormatRaw><![CDATA[ <'P'><;><'W'><;><'N'><;><WiFiNameLength[1..3]><;><WiFiNetworkName[100]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ProgCustomerData\" CmdByte=\"0x52\"><FPOperation>Program customer in FD data base.</FPOperation><Args><Arg Name=\"Option\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"CustomerNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for customer number in format ####</Desc></Arg><Arg Name=\"CustomerCompanyName\" Value=\"\" Type=\"Text\" MaxLen=\"26\"><Desc>26 symbols for customer name</Desc></Arg><Arg Name=\"CustomerFullName\" Value=\"\" Type=\"Text\" MaxLen=\"16\"><Desc>16 symbols for Buyer name</Desc></Arg><Arg Name=\"VATNumber\" Value=\"\" Type=\"Text\" MaxLen=\"13\"><Desc>13 symbols for VAT number on customer</Desc></Arg><Arg Name=\"UIC\" Value=\"\" Type=\"Text\" MaxLen=\"13\"><Desc>13 symbols for customer Unique Identification Code</Desc></Arg><Arg Name=\"Address\" Value=\"\" Type=\"Text\" MaxLen=\"30\"><Desc>30 symbols for address on customer</Desc></Arg><Arg Name=\"OptionUICType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Bulstat\" Value=\"0\" /><Option Name=\"EGN\" Value=\"1\" /><Option Name=\"Foreigner Number\" Value=\"2\" /><Option Name=\"NRA Official Number\" Value=\"3\" /></Options><Desc>1 symbol for type of Unique Identification Code:  - '0' - Bulstat  - '1' - EGN  - '2' - Foreigner Number  - '3' - NRA Official Number</Desc></Arg><ArgsFormatRaw><![CDATA[ <Option['P']> <;> <CustomerNum[4]> <;> <CustomerCompanyName[26]> <;> <CustomerFullName[16]> <;> <VATNumber[13]> <;> <UIC[13]> <;> <Address[30]> <;> <UICType[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"SellPLUwithSpecifiedVAT\" CmdByte=\"0x31\"><FPOperation>Register the sell (for correction use minus sign in the price field) of article with specified name, price, quantity, VAT class and/or discount/addition on the transaction.</FPOperation><Args><Arg Name=\"NamePLU\" Value=\"\" Type=\"Text\" MaxLen=\"36\"><Desc>36 symbols for article's name. 34 symbols are printed on paper. Symbol 0x7C '|' is new line separator.</Desc></Arg><Arg Name=\"OptionVATClass\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Forbidden\" Value=\"*\" /><Option Name=\"VAT Class 0\" Value=\"А\" /><Option Name=\"VAT Class 1\" Value=\"Б\" /><Option Name=\"VAT Class 2\" Value=\"В\" /><Option Name=\"VAT Class 3\" Value=\"Г\" /><Option Name=\"VAT Class 4\" Value=\"Д\" /><Option Name=\"VAT Class 5\" Value=\"Е\" /><Option Name=\"VAT Class 6\" Value=\"Ж\" /><Option Name=\"VAT Class 7\" Value=\"З\" /></Options><Desc>1 character for VAT class:  - 'А' - VAT Class 0  - 'Б' - VAT Class 1  - 'В' - VAT Class 2  - 'Г' - VAT Class 3  - 'Д' - VAT Class 4  - 'Е' - VAT Class 5  - 'Ж' - VAT Class 6  - 'З' - VAT Class 7  - '*' - Forbidden</Desc></Arg><Arg Name=\"Price\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for article's price. Use minus sign '-' for correction</Desc></Arg><Arg Name=\"Quantity\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for quantity</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\"*\" /></Arg><Arg Name=\"DiscAddP\" Value=\"\" Type=\"Decimal\" MaxLen=\"7\"><Desc>Up to 7 symbols for percentage of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\",\" /></Arg><Arg Name=\"DiscAddV\" Value=\"\" Type=\"Decimal\" MaxLen=\"8\"><Desc>Up to 8 symbols for value of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\":\" /></Arg><ArgsFormatRaw><![CDATA[ <NamePLU[36]> <;> <OptionVATClass[1]> <;> <Price[1..10]> {<'*'> <Quantity[1..10]>} {<','> <DiscAddP[1..7]>} {<':'> <DiscAddV[1..8]>} ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadDetailedFMDepartmentsReportByZBlocks\" CmdByte=\"0x78\"><FPOperation>Read a detailed FM Departments report by initial and end Z report number.</FPOperation><Args><Arg Name=\"StartZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for initial FM report number included in report, format ####</Desc></Arg><Arg Name=\"EndZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for final FM report number included in report, format ####</Desc></Arg><Arg Name=\"Option\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionReading\" Value=\"8\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <Option['D']> <;> <OptionReading['8']> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"PrintBriefFMReportByDate\" CmdByte=\"0x7B\"><FPOperation>Print a brief FM report by initial and end date.</FPOperation><Args><Arg Name=\"StartDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name=\"EndDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><ArgsFormatRaw><![CDATA[ <StartDate \"DDMMYY\"> <;> <EndDate \"DDMMYY\"> ]]></ArgsFormatRaw></Args></Command><Command Name=\"DisplayTextLine1\" CmdByte=\"0x25\"><FPOperation>Shows a 20-symbols text in the upper external display line.</FPOperation><Args><Arg Name=\"Text\" Value=\"\" Type=\"Text\" MaxLen=\"20\"><Desc>20 symbols text</Desc></Arg><ArgsFormatRaw><![CDATA[ <Text[20]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadVATrates\" CmdByte=\"0x62\"><FPOperation>Provides information about the current VAT rates which are the last values stored into the FM.</FPOperation><Response ACK=\"false\"><Res Name=\"VATrate0\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"7\" Format=\"00.00%\"><Desc>Value of VAT rate А from 7 symbols in format ##.##%</Desc></Res><Res Name=\"VATrate1\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"7\" Format=\"00.00%\"><Desc>Value of VAT rate Б from 7 symbols in format ##.##%</Desc></Res><Res Name=\"VATrate2\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"7\" Format=\"00.00%\"><Desc>Value of VAT rate В from 7 symbols in format ##.##%</Desc></Res><Res Name=\"VATrate3\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"7\" Format=\"00.00%\"><Desc>Value of VAT rate Г from 7 symbols in format ##.##%</Desc></Res><Res Name=\"VATrate4\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"7\" Format=\"00.00%\"><Desc>Value of VAT rate Д from 7 symbols in format ##.##%</Desc></Res><Res Name=\"VATrate5\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"7\" Format=\"00.00%\"><Desc>Value of VAT rate Е from 7 symbols in format ##.##%</Desc></Res><Res Name=\"VATrate6\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"7\" Format=\"00.00%\"><Desc>Value of VAT rate Ж from 7 symbols in format ##.##%</Desc></Res><Res Name=\"VATrate7\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"7\" Format=\"00.00%\"><Desc>Value of VAT rate З from 7 symbols in format ##.##%</Desc></Res><ResFormatRaw><![CDATA[<VATrate0[7]> <;> <VATrate1[7]> <;> <VATrate2[7]> <;> <VATrate3[7]> <;> <VATrate4[7]> <;> <VATrate5[7]> <;> <VATrate6[7]> <;> <VATrate7[7]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadDailyReceivedSalesAmounts\" CmdByte=\"0x6E\"><FPOperation>Provides information about the amounts received from sales by type of payment.</FPOperation><Args><Arg Name=\"\" Value=\"4\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'4'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"4\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"AmountPayment0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name=\"AmountPayment1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name=\"AmountPayment2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name=\"AmountPayment3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name=\"AmountPayment4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><Res Name=\"AmountPayment5\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 5</Desc></Res><Res Name=\"AmountPayment6\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 6</Desc></Res><Res Name=\"AmountPayment7\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 7</Desc></Res><Res Name=\"AmountPayment8\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 8</Desc></Res><Res Name=\"AmountPayment9\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 9</Desc></Res><Res Name=\"AmountPayment10\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 10</Desc></Res><Res Name=\"AmountPayment11\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 11</Desc></Res><ResFormatRaw><![CDATA[<'4'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]> <;> <AmountPayment5[1..13]> <;> <AmountPayment6[1..13]> <;> <AmountPayment7[1..13]> <;> <AmountPayment8[1..13]> <;> <AmountPayment9[1..13]> <;> <AmountPayment10[1..13]> <;> <AmountPayment11[1..13]>]]></ResFormatRaw></Response></Command><Command Name=\"ProgPLUqty\" CmdByte=\"0x4B\"><FPOperation>Programs available quantity and Quantiy type for a certain article in the internal database.</FPOperation><Args><Arg Name=\"PLUNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"5\" Format=\"00000\"><Desc>5 symbols for article number in format: #####</Desc></Arg><Arg Name=\"Option\" Value=\"#@2+$\" Type=\"OptionHardcoded\" MaxLen=\"5\" /><Arg Name=\"AvailableQuantity\" Value=\"\" Type=\"Decimal\" MaxLen=\"11\"><Desc>Up to 11 symbols for available quantity in stock</Desc></Arg><Arg Name=\"OptionQuantityType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Availability of PLU stock is not monitored\" Value=\"0\" /><Option Name=\"Disable negative quantity\" Value=\"1\" /><Option Name=\"Enable negative quantity\" Value=\"2\" /></Options><Desc>1 symbol for Quantity flag with next value:  - '0'- Availability of PLU stock is not monitored  - '1'- Disable negative quantity  - '2'- Enable negative quantity</Desc></Arg><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Option['#@2+$']> <;> <AvailableQuantity[1..11]> <;> <OptionQuantityType[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadRegistrationInfo\" CmdByte=\"0x61\"><FPOperation>Provides information about the programmed VAT number, type of VAT number, register number in NRA and Date of registration in NRA.</FPOperation><Response ACK=\"false\"><Res Name=\"UIC\" Value=\"\" Type=\"Text\" MaxLen=\"13\"><Desc>13 symbols for Unique Identification Code</Desc></Res><Res Name=\"OptionUICType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Bulstat\" Value=\"0\" /><Option Name=\"EGN\" Value=\"1\" /><Option Name=\"Foreigner Number\" Value=\"2\" /><Option Name=\"NRA Official Number\" Value=\"3\" /></Options><Desc>1 symbol for type of Unique Identification Code:  - '0' - Bulstat  - '1' - EGN  - '2' - Foreigner Number  - '3' - NRA Official Number</Desc></Res><Res Name=\"NRARegistrationNumber\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>Register number on the Fiscal device from NRA</Desc></Res><Res Name=\"NRARegistrationDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"dd-MM-yyyy HH:mm\"><Desc>Date of registration in NRA</Desc></Res><ResFormatRaw><![CDATA[<UIC[13]> <;> <UICType[1]><;> <NRARegistrationNumber[6]><;> <NRARegistrationDate \"DD-MM-YYYY HH:MM\" >]]></ResFormatRaw></Response></Command><Command Name=\"ClearDisplay\" CmdByte=\"0x24\"><FPOperation>Clears the external display.</FPOperation></Command><Command Name=\"ProgPLU_Old\" CmdByte=\"0x4B\"><FPOperation>Programs the data for a certain article (item) in the internal database. The price may have variable length, while the name field is fixed.</FPOperation><Args><Arg Name=\"PLUNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"5\" Format=\"00000\"><Desc>5 symbols for article number in format: #####</Desc></Arg><Arg Name=\"Name\" Value=\"\" Type=\"Text\" MaxLen=\"20\"><Desc>20 symbols for article name</Desc></Arg><Arg Name=\"Price\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for article price</Desc></Arg><Arg Name=\"OptionVATClass\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Forbidden\" Value=\"*\" /><Option Name=\"VAT Class 0\" Value=\"А\" /><Option Name=\"VAT Class 1\" Value=\"Б\" /><Option Name=\"VAT Class 2\" Value=\"В\" /><Option Name=\"VAT Class 3\" Value=\"Г\" /><Option Name=\"VAT Class 4\" Value=\"Д\" /><Option Name=\"VAT Class 5\" Value=\"Е\" /><Option Name=\"VAT Class 6\" Value=\"Ж\" /><Option Name=\"VAT Class 7\" Value=\"З\" /></Options><Desc>1 character for VAT class:  - 'А' - VAT Class 0  - 'Б' - VAT Class 1  - 'В' - VAT Class 2  - 'Г' - VAT Class 3  - 'Д' - VAT Class 4  - 'Е' - VAT Class 5  - 'Ж' - VAT Class 6  - 'З' - VAT Class 7  - '*' - Forbidden</Desc></Arg><Arg Name=\"BelongToDepNum\" Value=\"\" Type=\"Decimal_plus_80h\" MaxLen=\"2\"><Desc>BelongToDepNum + 80h, 1 symbol for article department attachment, formed in the following manner:</Desc></Arg><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Name[20]> <;> <Price[1..10]> <;> <OptionVATClass[1]> <;> <BelongToDepNum[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"SellFractQtyPLUwithSpecifiedVAT\" CmdByte=\"0x3D\"><FPOperation>Register the sell (for correction use minus sign in the price field) of article with specified name, price, fractional quantity, VAT class and/or discount/addition on the transaction.</FPOperation><Args><Arg Name=\"NamePLU\" Value=\"\" Type=\"Text\" MaxLen=\"36\"><Desc>36 symbols for article's name. 34 symbols are printed on paper. Symbol 0x7C '|' is new line separator.</Desc></Arg><Arg Name=\"OptionVATClass\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Forbidden\" Value=\"*\" /><Option Name=\"VAT Class 0\" Value=\"А\" /><Option Name=\"VAT Class 1\" Value=\"Б\" /><Option Name=\"VAT Class 2\" Value=\"В\" /><Option Name=\"VAT Class 3\" Value=\"Г\" /><Option Name=\"VAT Class 4\" Value=\"Д\" /><Option Name=\"VAT Class 5\" Value=\"Е\" /><Option Name=\"VAT Class 6\" Value=\"Ж\" /><Option Name=\"VAT Class 7\" Value=\"З\" /></Options><Desc>1 character for VAT class:  - 'А' - VAT Class 0  - 'Б' - VAT Class 1  - 'В' - VAT Class 2  - 'Г' - VAT Class 3  - 'Д' - VAT Class 4  - 'Е' - VAT Class 5  - 'Ж' - VAT Class 6  - 'З' - VAT Class 7  - '*' - Forbidden</Desc></Arg><Arg Name=\"Price\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for article's price. Use minus sign '-' for correction</Desc></Arg><Arg Name=\"Quantity\" Value=\"\" Type=\"Text\" MaxLen=\"10\"><Desc>From 3 to 10 symbols for quantity in format fractional format, e.g. 1/3</Desc><Meta MinLen=\"10\" Compulsory=\"false\" ValIndicatingPresence=\"*\" /></Arg><Arg Name=\"DiscAddP\" Value=\"\" Type=\"Decimal\" MaxLen=\"7\"><Desc>1 to 7 symbols for percentage of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\",\" /></Arg><Arg Name=\"DiscAddV\" Value=\"\" Type=\"Decimal\" MaxLen=\"8\"><Desc>1 to 8 symbols for value of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\":\" /></Arg><ArgsFormatRaw><![CDATA[ <NamePLU[36]> <;> <OptionVATClass[1]> <;> <Price[1..10]> {<'*'> <Quantity[10]>} {<','> <DiscAddP[1..7]>} {<':'> <DiscAddV[1..8]>} ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadElectronicReceipt_QR_ASCII\" CmdByte=\"0x72\"><FPOperation>Starts session for reading electronic receipt by number with specified ASCII symbol for QR code block.</FPOperation><Args><Arg Name=\"\" Value=\"E\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"RcpNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"000000\"><Desc>6 symbols with format ######</Desc></Arg><Arg Name=\"QRSymbol\" Value=\"\" Type=\"Text\" MaxLen=\"1\"><Desc>1 symbol for QR code drawing image</Desc></Arg><ArgsFormatRaw><![CDATA[ <'E'> <;> <RcpNum[6]> <;> <QRSymbol[1]> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"SetDHCP_Enabled\" CmdByte=\"0x4E\"><FPOperation>Program device's TCP network DHCP enabled or disabled. To apply use -SaveNetworkSettings()</FPOperation><Args><Arg Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"T\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"1\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionDHCPEnabled\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Disabled\" Value=\"0\" /><Option Name=\"Enabled\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '0' - Disabled  - '1' - Enabled</Desc></Arg><ArgsFormatRaw><![CDATA[ <'P'><;><'T'><;><'1'><;><DHCPEnabled[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadDailyPObyOperator\" CmdByte=\"0x6F\"><FPOperation>Read the PO by type of payment and the total number of operations by specified operator</FPOperation><Args><Arg Name=\"\" Value=\"3\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Arg><ArgsFormatRaw><![CDATA[ <'3'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"3\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Res><Res Name=\"AmountPO_Payment0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the PO by type of payment 0</Desc></Res><Res Name=\"AmountPO_Payment1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the PO by type of payment 1</Desc></Res><Res Name=\"AmountPO_Payment2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the PO by type of payment 2</Desc></Res><Res Name=\"AmountPO_Payment3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the PO by type of payment 3</Desc></Res><Res Name=\"AmountPO_Payment4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the PO by type of payment 4</Desc></Res><Res Name=\"AmountPO_Payment5\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the PO by type of payment 5</Desc></Res><Res Name=\"AmountPO_Payment6\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the PO by type of payment 6</Desc></Res><Res Name=\"AmountPO_Payment7\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the PO by type of payment 7</Desc></Res><Res Name=\"AmountPO_Payment8\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the PO by type of payment 8</Desc></Res><Res Name=\"AmountPO_Payment9\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the PO by type of payment 9</Desc></Res><Res Name=\"AmountPO_Payment10\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the PO by type of payment 10</Desc></Res><Res Name=\"AmountPO_Payment11\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the PO by type of payment 11</Desc></Res><Res Name=\"NoPO\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>5 symbols for the total number of operations</Desc></Res><ResFormatRaw><![CDATA[<'3'> <;> <OperNum[1..2]> <;> <AmountPO_Payment0[1..13]> <;> <AmountPO_Payment1[1..13]> <;> <AmountPO_Payment2[1..13]> <;> <AmountPO_Payment3[1..13]> <;> <AmountPO_Payment4[1..13]> <;> <AmountPO_Payment5[1..13]> <;> <AmountPO_Payment6[1..13]> <;> <AmountPO_Payment7[1..13]> <;> <AmountPO_Payment8[1..13]> <;> <AmountPO_Payment9[1..13]> <;> <AmountPO_Payment10[1..13]> <;> <AmountPO_Payment11[1..13]> <;><NoPO[1..5]>]]></ResFormatRaw></Response></Command><Command Name=\"OpenElectronicReceipt\" CmdByte=\"0x30\"><FPOperation>Opens an postponed electronic fiscal receipt with 1 minute timeout assigned to the specified operator number and operator password, parameters for receipt format, print VAT, printing type and unique receipt number.</FPOperation><Args><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Arg><Arg Name=\"OperPass\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for operator's password</Desc></Arg><Arg Name=\"OptionReceiptFormat\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Brief\" Value=\"0\" /><Option Name=\"Detailed\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '1' - Detailed  - '0' - Brief</Desc></Arg><Arg Name=\"OptionPrintVAT\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '1' - Yes  - '0' - No</Desc></Arg><Arg Name=\"FiscalRcpPrintType\" Value=\"8\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"UniqueReceiptNumber\" Value=\"\" Type=\"Text\" MaxLen=\"24\"><Desc>Up to 24 symbols for unique receipt number. NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, * YYYYYYY - 7 symbols [0-9] for next number of the receipt</Desc><Meta MinLen=\"24\" Compulsory=\"false\" ValIndicatingPresence=\"$\" /></Arg><ArgsFormatRaw><![CDATA[<OperNum[1..2]> <;> <OperPass[6]> <;> <ReceiptFormat[1]> <;> <PrintVAT[1]> <;> <FiscalRcpPrintType['8']> {<'$'> <UniqueReceiptNumber[24]>} ]]></ArgsFormatRaw></Args></Command><Command Name=\"ProgHeaderUICprefix\" CmdByte=\"0x49\"><FPOperation>Program the content of the header UIC prefix.</FPOperation><Args><Arg Name=\"\" Value=\"9\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"HeaderUICprefix\" Value=\"\" Type=\"Text\" MaxLen=\"12\"><Desc>12 symbols for header UIC prefix</Desc></Arg><ArgsFormatRaw><![CDATA[<'9'> <;> <HeaderUICprefix[12]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ProgPLUprice\" CmdByte=\"0x4B\"><FPOperation>Programs price and price type for a certain article in the internal database.</FPOperation><Args><Arg Name=\"PLUNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"5\" Format=\"00000\"><Desc>5 symbols for article number in format: #####</Desc></Arg><Arg Name=\"Option\" Value=\"#@4+$\" Type=\"OptionHardcoded\" MaxLen=\"5\" /><Arg Name=\"Price\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for article price</Desc></Arg><Arg Name=\"OptionPrice\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Free price is disable valid only programmed price\" Value=\"0\" /><Option Name=\"Free price is enable\" Value=\"1\" /><Option Name=\"Limited price\" Value=\"2\" /></Options><Desc>1 symbol for price flag with next value:  - '0'- Free price is disable valid only programmed price  - '1'- Free price is enable  - '2'- Limited price</Desc></Arg><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Option['#@4+$']> <;> <Price[1..10]> <;> <OptionPrice[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadDailyReceivedSalesAmounts_Old\" CmdByte=\"0x6E\"><FPOperation>Provides information about the amounts received from sales by type of payment. Command works for KL version 2 devices.</FPOperation><Args><Arg Name=\"\" Value=\"4\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'4'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"4\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"AmountPayment0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name=\"AmountPayment1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name=\"AmountPayment2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name=\"AmountPayment3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name=\"AmountPayment4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><ResFormatRaw><![CDATA[<'4'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]>]]></ResFormatRaw></Response></Command><Command Name=\"RawRead\" CmdByte=\"0xFF\"><FPOperation> Reads raw bytes from FP.</FPOperation><Args><Arg Name=\"Count\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>How many bytes to read if EndChar is not specified</Desc></Arg><Arg Name=\"EndChar\" Value=\"\" Type=\"Text\" MaxLen=\"1\"><Desc>The character marking the end of the data. If present Count parameter is ignored.</Desc></Arg></Args><Response ACK=\"false\"><Res Name=\"Bytes\" Value=\"\" Type=\"Base64\" MaxLen=\"100000\"><Desc>FP raw response in BASE64 encoded string</Desc></Res></Response></Command><Command Name=\"ProgramTransferAmountParam_RA\" CmdByte=\"0x4F\"><FPOperation>Program parameter for automatic transfer of daily available amounts.</FPOperation><Args><Arg Name=\"\" Value=\"A\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"W\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionTransferAmount\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '0' - No  - '1' - Yes</Desc></Arg><ArgsFormatRaw><![CDATA[ <'A'> <;> <'W'> <;> <OptionTransferAmount[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadDHCP_Status\" CmdByte=\"0x4E\"><FPOperation>Provides information about device's DHCP status</FPOperation><Args><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"T\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"1\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'R'><;><'T'><;><'1'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"T\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"1\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OptionDhcpStatus\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Disabled\" Value=\"0\" /><Option Name=\"Enabled\" Value=\"1\" /></Options><Desc>(DHCP Status) 1 symbol for device's DHCP status - '0' - Disabled  - '1' - Enabled</Desc></Res><ResFormatRaw><![CDATA[<'R'><;><'T'><;><'1'><;><DhcpStatus[1]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadTCP_Addresses\" CmdByte=\"0x4E\"><FPOperation>Provides information about device's IP address, subnet mask, gateway address, DNS address.</FPOperation><Args><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"T\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionAddressType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"DNS address\" Value=\"5\" /><Option Name=\"Gateway address\" Value=\"4\" /><Option Name=\"IP address\" Value=\"2\" /><Option Name=\"Subnet Mask\" Value=\"3\" /></Options><Desc>1 symbol with value:  - '2' - IP address  - '3' - Subnet Mask  - '4' - Gateway address  - '5' - DNS address</Desc></Arg><ArgsFormatRaw><![CDATA[ <'R'><;><'T'><;><AddressType[1]> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"T\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OptionAddressType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"DNS address\" Value=\"5\" /><Option Name=\"Gateway address\" Value=\"4\" /><Option Name=\"IP address\" Value=\"2\" /><Option Name=\"Subnet Mask\" Value=\"3\" /></Options><Desc>(Address) 1 symbol with value:  - '2' - IP address  - '3' - Subnet Mask  - '4' - Gateway address  - '5' - DNS address</Desc></Res><Res Name=\"DeviceAddress\" Value=\"\" Type=\"Text\" MaxLen=\"15\"><Desc>15 symbols for the device's addresses</Desc></Res><ResFormatRaw><![CDATA[<'R'><;><'T'><;>< AddressType[1]><;><DeviceAddress[15]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadLastReceiptQRcodeData\" CmdByte=\"0x72\"><FPOperation>Provides information about the QR code data in last issued receipt.</FPOperation><Args><Arg Name=\"\" Value=\"B\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'B'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"QRcodeData\" Value=\"\" Type=\"Text\" MaxLen=\"60\"><Desc>Up to 60 symbols for last issued receipt QR code data separated by symbol '*' in format: FM Number*Receipt Number*Receipt Date*Receipt Hour*Receipt Amount</Desc></Res><ResFormatRaw><![CDATA[<QRcodeData[60]>]]></ResFormatRaw></Response></Command><Command Name=\"ProgHeader\" CmdByte=\"0x49\"><FPOperation>Program the contents of a header lines.</FPOperation><Args><Arg Name=\"OptionHeaderLine\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Header 1\" Value=\"1\" /><Option Name=\"Header 2\" Value=\"2\" /><Option Name=\"Header 3\" Value=\"3\" /><Option Name=\"Header 4\" Value=\"4\" /><Option Name=\"Header 5\" Value=\"5\" /><Option Name=\"Header 6\" Value=\"6\" /><Option Name=\"Header 7\" Value=\"7\" /></Options><Desc>1 symbol with value:  - '1' - Header 1  - '2' - Header 2  - '3' - Header 3  - '4' - Header 4  - '5' - Header 5  - '6' - Header 6  - '7' - Header 7</Desc></Arg><Arg Name=\"HeaderText\" Value=\"\" Type=\"Text\" MaxLen=\"64\"><Desc>TextLength symbols for header lines</Desc></Arg><ArgsFormatRaw><![CDATA[<OptionHeaderLine[1]> <;> <HeaderText[TextLength]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"SetActiveLogoNum\" CmdByte=\"0x23\"><FPOperation>Sets logo number, which is active and will be printed as logo in the receipt header. Print information about active number.</FPOperation><Args><Arg Name=\"LogoNumber\" Value=\"\" Type=\"Text\" MaxLen=\"1\"><Desc>1 character value from '0' to '9' or '?'. The number sets the active file, and the '?' invokes only printing of information</Desc></Arg><ArgsFormatRaw><![CDATA[ <LogoNumber[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"CloseNonFiscalReceipt\" CmdByte=\"0x2F\"><FPOperation>Closes the non-fiscal receipt.</FPOperation></Command><Command Name=\"UnpairAllDevices\" CmdByte=\"0x4E\"><FPOperation>Removes all paired devices. To apply use -SaveNetworkSettings()</FPOperation><Args><Arg Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"B\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'P'><;><'B'><;><'D'> ]]></ArgsFormatRaw></Args></Command><Command Name=\"DisplayDateTime\" CmdByte=\"0x28\"><FPOperation>Shows the current date and time on the external display.</FPOperation></Command><Command Name=\"SetTCP_AutoStart\" CmdByte=\"0x4E\"><FPOperation>Set device's TCP autostart . To apply use -SaveNetworkSettings()</FPOperation><Args><Arg Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"Z\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"2\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionTCPAutoStart\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '0' - No  - '1' - Yes</Desc></Arg><ArgsFormatRaw><![CDATA[ <'P'><;><'Z'><;><'2'><;><TCPAutoStart[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadNBLParameter\" CmdByte=\"0x4F\"><FPOperation>Provide information about NBL parameter to be monitored by the fiscal device.</FPOperation><Args><Arg Name=\"\" Value=\"N\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'N'> <;> <'R'>  ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"N\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OptionNBL\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '0' - No  - '1' - Yes</Desc></Res><ResFormatRaw><![CDATA[<'N'> <;> <'R'> <;> <OptionNBL[1]>]]></ResFormatRaw></Response></Command><Command Name=\"PrintOrStoreEJByDate\" CmdByte=\"0x7C\"><FPOperation>Print or store Electronic Journal Report by initial and end date.</FPOperation><Args><Arg Name=\"OptionReportStorage\" Value=\"\" Type=\"Option\" MaxLen=\"2\"><Options><Option Name=\"Printing\" Value=\"J1\" /><Option Name=\"SD card storage\" Value=\"J4\" /><Option Name=\"USB storage\" Value=\"J2\" /></Options><Desc>1 character with value:  - 'J1' - Printing  - 'J2' - USB storage  - 'J4' - SD card storage</Desc></Arg><Arg Name=\"\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"StartRepFromDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name=\"EndRepFromDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><ArgsFormatRaw><![CDATA[<ReportStorage[2]> <;> <'D'> <;> <StartRepFromDate \"DDMMYY\"> <;> <EndRepFromDate \"DDMMYY\"> ]]></ArgsFormatRaw></Args></Command><Command Name=\"SetTCP_ActiveModule\" CmdByte=\"0x4E\"><FPOperation>Sets the used TCP module for communication - Lan or WiFi. To apply use -SaveNetworkSettings()</FPOperation><Args><Arg Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"Z\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"U\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionUsedModule\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"LAN\" Value=\"1\" /><Option Name=\"WiFi\" Value=\"2\" /></Options><Desc>1 symbol with value:  - '1' - LAN  - '2' - WiFi</Desc></Arg><ArgsFormatRaw><![CDATA[ <'P'><;><'Z'><;><'U'><;><UsedModule[1]><;> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadDailyReturnedChangeAmounts_Old\" CmdByte=\"0x6E\"><FPOperation>Provides information about the amounts returned as change by type of payment. Command works for KL version 2 devices.</FPOperation><Args><Arg Name=\"\" Value=\"6\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'6'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"6\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"AmountPayment0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name=\"AmountPayment1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name=\"AmountPayment2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name=\"AmountPayment3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name=\"AmountPayment4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><ResFormatRaw><![CDATA[<'6'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]>]]></ResFormatRaw></Response></Command><Command Name=\"PrintEJByZBlocksCustom\" CmdByte=\"0x7C\"><FPOperation>Print Electronic Journal Report by number of Z report blocks and selected documents content. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.</FPOperation><Args><Arg Name=\"\" Value=\"j1\" Type=\"OptionHardcoded\" MaxLen=\"2\" /><Arg Name=\"\" Value=\"X\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"FlagsReceipts\" Value=\"\" Type=\"Flags\" MaxLen=\"1\"><Desc>1 symbol for Receipts included in EJ: Flags.7=0 Flags.6=1 Flags.5=1 Yes, Flags.5=0 No (Include PO) Flags.4=1 Yes, Flags.4=0 No (Include RA) Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) Flags.1=1 Yes, Flags.1=0 No (Include Invoice) Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)</Desc></Arg><Arg Name=\"FlagsReports\" Value=\"\" Type=\"Flags\" MaxLen=\"1\"><Desc>1 symbol for Reports included in EJ: Flags.7=0 Flags.6=1 Flags.5=0 Flags.4=1 Yes, Flags.4=0 No (Include FM reports) Flags.3=1 Yes, Flags.3=0 No (Include Other reports) Flags.2=1 Yes, Flags.2=0 No (Include Daily X) Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)</Desc></Arg><Arg Name=\"\" Value=\"Z\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"StartZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for initial number report in format ####</Desc></Arg><Arg Name=\"EndZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for final number report in format ####</Desc></Arg><ArgsFormatRaw><![CDATA[ <'j1'> <;> <'X'> <;> <FlagsReceipts [1]> <;> <FlagsReports [1]> <;> <'Z'> <;> <StartZNum[4]> <;> <EndZNum[4]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadTCP_UsedModule\" CmdByte=\"0x4E\"><FPOperation>Read the used TCP module for communication - Lan or WiFi</FPOperation><Args><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"Z\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"U\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'R'><;><'Z'><;><'U'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"Z\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"U\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OptionUsedModule\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"LAN\" Value=\"1\" /><Option Name=\"WiFi\" Value=\"2\" /></Options><Desc>(Module) 1 symbol with value:  - '1' - LAN  - '2' - WiFi</Desc></Res><ResFormatRaw><![CDATA[<'R'><;><'Z'><;><'U'><;><UsedModule[1]>]]></ResFormatRaw></Response></Command><Command Name=\"PaperFeed\" CmdByte=\"0x2B\"><FPOperation>Feeds one line of paper.</FPOperation></Command><Command Name=\"CloseReceipt\" CmdByte=\"0x38\"><FPOperation>Close the fiscal receipt (Fiscal receipt, Invoice receipt, Storno receipt, Credit Note or Non-fical receipt). When the payment is finished.</FPOperation></Command><Command Name=\"ReadSpecifiedReceiptQRcodeData\" CmdByte=\"0x72\"><FPOperation>Provides information about the QR code data in specified number issued receipt.</FPOperation><Args><Arg Name=\"\" Value=\"b\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"RcpNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"000000\"><Desc>6 symbols with format ######</Desc></Arg><ArgsFormatRaw><![CDATA[ <'b'><;><RcpNum[6]> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"QRcodeData\" Value=\"\" Type=\"Text\" MaxLen=\"60\"><Desc>Up to 60 symbols for last issued receipt QR code data separated by symbol '*' in format: FM Number*Receipt Number*Receipt Date*Receipt Hour*Receipt Amount</Desc></Res><ResFormatRaw><![CDATA[<QRcodeData[60]>]]></ResFormatRaw></Response></Command><Command Name=\"SellPLUfromDep_\" CmdByte=\"0x34\"><FPOperation>Registers the sell (for correction use minus sign in the price field) of article with specified department, name, price, quantity and/or discount/addition on the transaction.</FPOperation><Args><Arg Name=\"NamePLU\" Value=\"\" Type=\"Text\" MaxLen=\"36\"><Desc>36 symbols for name of sale. 34 symbols are printed on paper. Symbol 0x7C '|' is new line separator.</Desc></Arg><Arg Name=\"DepNum\" Value=\"\" Type=\"Decimal_plus_80h\" MaxLen=\"2\"><Desc>1 symbol for article department attachment, formed in the following manner: DepNum[HEX] + 80h example: Dep01 = 81h, Dep02 = 82h … Dep19 = 93h Department range from 1 to 127</Desc></Arg><Arg Name=\"Price\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for article's price. Use minus sign '-' for correction</Desc></Arg><Arg Name=\"Quantity\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10symbols for article's quantity sold</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\"*\" /></Arg><Arg Name=\"DiscAddP\" Value=\"\" Type=\"Decimal\" MaxLen=\"7\"><Desc>Up to 7 for percentage of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\",\" /></Arg><Arg Name=\"DiscAddV\" Value=\"\" Type=\"Decimal\" MaxLen=\"8\"><Desc>Up to 8 symbols for percentage of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\":\" /></Arg><ArgsFormatRaw><![CDATA[ <NamePLU[36]> <;> <DepNum[1]> <;> <Price[1..10]> {<'*'> <Quantity[1..10]>} {<','> <DiscAddP[1..7]>} {<':'> <DiscAddV[1..8]>} ]]></ArgsFormatRaw></Args></Command><Command Name=\"ArrangePayments\" CmdByte=\"0x44\"><FPOperation>Arrangement of payment positions according to NRA list: 0-Cash, 1- Check, 2-Talon, 3-V.Talon, 4-Packaging, 5-Service, 6-Damage, 7-Card, 8-Bank, 9- Programming Name 1, 10-Programming Name 2, 11-Currency.</FPOperation><Args><Arg Name=\"Option\" Value=\"*\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"PaymentPosition0\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 0 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Arg><Arg Name=\"PaymentPosition1\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 1 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Arg><Arg Name=\"PaymentPosition2\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 2 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Arg><Arg Name=\"PaymentPosition3\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 3 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Arg><Arg Name=\"PaymentPosition4\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 4 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Arg><Arg Name=\"PaymentPosition5\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 5 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Arg><Arg Name=\"PaymentPosition6\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 6 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Arg><Arg Name=\"PaymentPosition7\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 7 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Arg><Arg Name=\"PaymentPosition8\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 8 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Arg><Arg Name=\"PaymentPosition9\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 9 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Arg><Arg Name=\"PaymentPosition10\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 10 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Arg><Arg Name=\"PaymentPosition11\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 11 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Arg><ArgsFormatRaw><![CDATA[ <Option['*']> <;> <PaymentPosition0[2]> <;> <PaymentPosition1[2]> <;> <PaymentPosition2[2]> <;> <PaymentPosition3[2]> <;> <PaymentPosition4[2]> <;> <PaymentPosition5[2]> <;> <PaymentPosition6[2]> <;> <PaymentPosition7[2]> <;> <PaymentPosition8[2]> <;> <PaymentPosition9[2]> <;> <PaymentPosition10[2]> <;> <PaymentPosition11[2]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"OpenCreditNoteWithFreeCustomerData\" CmdByte=\"0x30\"><FPOperation>Opens a fiscal invoice credit note receipt assigned to the specified operator number and operator password with free info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.</FPOperation><Args><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbol from 1 to 20 corresponding to operator's number</Desc></Arg><Arg Name=\"OperPass\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for operator's password</Desc></Arg><Arg Name=\"reserved\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"reserved\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionInvoiceCreditNotePrintType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Buffered Printing\" Value=\"E\" /><Option Name=\"Postponed Printing\" Value=\"C\" /><Option Name=\"Step by step printing\" Value=\"A\" /></Options><Desc>1 symbol with value: - 'A' - Step by step printing - 'C' - Postponed Printing - 'E' - Buffered Printing</Desc></Arg><Arg Name=\"Recipient\" Value=\"\" Type=\"Text\" MaxLen=\"26\"><Desc>26 symbols for Invoice recipient</Desc></Arg><Arg Name=\"Buyer\" Value=\"\" Type=\"Text\" MaxLen=\"16\"><Desc>16 symbols for Invoice buyer</Desc></Arg><Arg Name=\"VATNumber\" Value=\"\" Type=\"Text\" MaxLen=\"13\"><Desc>13 symbols for customer Fiscal number</Desc></Arg><Arg Name=\"UIC\" Value=\"\" Type=\"Text\" MaxLen=\"13\"><Desc>13 symbols for customer Unique Identification Code</Desc></Arg><Arg Name=\"Address\" Value=\"\" Type=\"Text\" MaxLen=\"30\"><Desc>30 symbols for Address</Desc></Arg><Arg Name=\"OptionUICType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Bulstat\" Value=\"0\" /><Option Name=\"EGN\" Value=\"1\" /><Option Name=\"Foreigner Number\" Value=\"2\" /><Option Name=\"NRA Official Number\" Value=\"3\" /></Options><Desc>1 symbol for type of Unique Identification Code:  - '0' - Bulstat  - '1' - EGN  - '2' - Foreigner Number  - '3' - NRA Official Number</Desc></Arg><Arg Name=\"OptionStornoReason\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Goods Claim or Goods return\" Value=\"1\" /><Option Name=\"Operator error\" Value=\"0\" /><Option Name=\"Tax relief\" Value=\"2\" /></Options><Desc>1 symbol for reason of storno operation with value:  - '0' - Operator error  - '1' - Goods Claim or Goods return  - '2' - Tax relief</Desc></Arg><Arg Name=\"RelatedToInvoiceNum\" Value=\"\" Type=\"Text\" MaxLen=\"10\"><Desc>10 symbols for issued invoice number</Desc></Arg><Arg Name=\"RelatedToInvoiceDateTime\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"dd-MM-yy HH:mm:ss\"><Desc>17 symbols for issued invoice date and time in format</Desc></Arg><Arg Name=\"RelatedToRcpNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"6\"><Desc>Up to 6 symbols for issued receipt number</Desc></Arg><Arg Name=\"FMNum\" Value=\"\" Type=\"Text\" MaxLen=\"8\"><Desc>8 symbols for number of the Fiscal Memory</Desc></Arg><Arg Name=\"RelatedToURN\" Value=\"\" Type=\"Text\" MaxLen=\"24\"><Desc>Up to 24 symbols for the issed invoice receipt unique receipt number. NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, * YYYYYYY - 7 symbols [0-9] for next number of the receipt</Desc><Meta MinLen=\"24\" Compulsory=\"false\" ValIndicatingPresence=\";\" /></Arg><ArgsFormatRaw><![CDATA[ <OperNum[1..2]> <;> <OperPass[6]> <;> <reserved['0']> <;> <reserved['0']> <;> <InvoiceCreditNotePrintType[1]> <;> <Recipient[26]> <;> <Buyer[16]> <;> <VATNumber[13]> <;> <UIC[13]> <;> <Address[30]> <;> <UICType[1]> <;> <StornoReason[1]> <;> <RelatedToInvoiceNum[10]> <;> <RelatedToInvoiceDateTime\"DD-MM-YY HH:MM:SS\"> <;> <RelatedToRcpNum[1..6]> <;> <FMNum[8]> { <;> <RelatedToURN[24]> } ]]></ArgsFormatRaw></Args></Command><Command Name=\"PrintBarcode\" CmdByte=\"0x51\"><FPOperation>Prints barcode from type stated by CodeType and CodeLen and with data stated in CodeData field. Command works only for fiscal printer devices. ECR does not support this command. The command is not supported by KL ECRs!</FPOperation><Args><Arg Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionCodeType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"CODABAR\" Value=\"6\" /><Option Name=\"CODE 128\" Value=\"I\" /><Option Name=\"CODE 39\" Value=\"4\" /><Option Name=\"CODE 93\" Value=\"H\" /><Option Name=\"EAN 13\" Value=\"2\" /><Option Name=\"EAN 8\" Value=\"3\" /><Option Name=\"ITF\" Value=\"5\" /><Option Name=\"UPC A\" Value=\"0\" /><Option Name=\"UPC E\" Value=\"1\" /></Options><Desc>1 symbol with possible values:  - '0' - UPC A  - '1' - UPC E  - '2' - EAN 13  - '3' - EAN 8  - '4' - CODE 39  - '5' - ITF  - '6' - CODABAR  - 'H' - CODE 93  - 'I' - CODE 128</Desc></Arg><Arg Name=\"CodeLen\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Up to 2 bytes for number of bytes according to the table</Desc></Arg><Arg Name=\"CodeData\" Value=\"\" Type=\"Text\" MaxLen=\"100\"><Desc>Up to 100 bytes data in range according to the table</Desc></Arg><ArgsFormatRaw><![CDATA[ <'P'> <;> <CodeType[1]> <;> <CodeLen[1..2]> <;> <CodeData[100]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadDailySaleAndStornoAmountsByVAT\" CmdByte=\"0x6D\"><FPOperation>Provides information about the accumulated sale and storno amounts by VAT group.</FPOperation><Response ACK=\"false\"><Res Name=\"SaleAmountVATGr0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the amount accumulated from sales by VAT group А</Desc></Res><Res Name=\"SaleAmountVATGr1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the amount accumulated from sales by VAT group Б</Desc></Res><Res Name=\"SaleAmountVATGr2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the amount accumulated from sales by VAT group В</Desc></Res><Res Name=\"SaleAmountVATGr3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the amount accumulated from sales by VAT group Г</Desc></Res><Res Name=\"SaleAmountVATGr4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the amount accumulated from sales by VAT group Д</Desc></Res><Res Name=\"SaleAmountVATGr5\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the amount accumulated from sales by VAT group Е</Desc></Res><Res Name=\"SaleAmountVATGr6\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the amount accumulated from sales by VAT group Ж</Desc></Res><Res Name=\"SaleAmountVATGr7\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the amount accumulated from sales by VAT group З</Desc></Res><Res Name=\"SumAllVATGr\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for sum of all VAT groups</Desc></Res><Res Name=\"StornoAmountVATGr0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the amount accumulated from Storno by VAT group А</Desc></Res><Res Name=\"StornoAmountVATGr1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the amount accumulated from Storno by VAT group Б</Desc></Res><Res Name=\"StornoAmountVATGr2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the amount accumulated from Storno by VAT group В</Desc></Res><Res Name=\"StornoAmountVATGr3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the amount accumulated from Storno by VAT group Г</Desc></Res><Res Name=\"StornoAmountVATGr4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the amount accumulated from Storno by VAT group Д</Desc></Res><Res Name=\"StornoAmountVATGr5\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the amount accumulated from Storno by VAT group Е</Desc></Res><Res Name=\"StornoAmountVATGr6\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the amount accumulated from Storno by VAT group Ж</Desc></Res><Res Name=\"StornoAmountVATGr7\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the amount accumulated from Storno by VAT group З</Desc></Res><Res Name=\"StornoAllVATGr\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the amount accumulated from Storno by all groups</Desc></Res><ResFormatRaw><![CDATA[<SaleAmountVATGr0[1..13]> <;> <SaleAmountVATGr1[1..13]> <;> <SaleAmountVATGr2[1..13]> <;> <SaleAmountVATGr3[1..13]> <;> <SaleAmountVATGr4[1..13]> <;> <SaleAmountVATGr5[1..13]> <;> <SaleAmountVATGr6[1..13]> <;> <SaleAmountVATGr7[1..13]> <;><SumAllVATGr[1..13]><;> <StornoAmountVATGr0[1..13]> <;><StornoAmountVATGr1[1..13]> <;> <StornoAmountVATGr2[1..13]> <;><StornoAmountVATGr3[1..13]> <;> <StornoAmountVATGr4[1..13]> <;>< StornoAmountVATGr5[1..13]> <;> <StornoAmountVATGr6[1..13]> <;> <StornoAmountVATGr7[1..13]> <;> <StornoAllVATGr[1..13]>]]></ResFormatRaw></Response></Command><Command Name=\"PrintDepartmentReport\" CmdByte=\"0x76\"><FPOperation>Print a department report with or without zeroing ('Z' or 'X').</FPOperation><Args><Arg Name=\"OptionZeroing\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Without zeroing\" Value=\"X\" /><Option Name=\"Zeroing\" Value=\"Z\" /></Options><Desc>1 symbol with value:  - 'Z' - Zeroing  - 'X' - Without zeroing</Desc></Arg><ArgsFormatRaw><![CDATA[ <OptionZeroing[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadEJCustom\" CmdByte=\"0x7C\"><FPOperation>Read or Store Electronic Journal report by CSV format option and document content selecting. If CSV format is set the content can includes only fiscal receipts. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.</FPOperation><Args><Arg Name=\"OptionStorageReport\" Value=\"\" Type=\"Option\" MaxLen=\"2\"><Options><Option Name=\"To PC\" Value=\"j0\" /><Option Name=\"To SD card\" Value=\"j4\" /><Option Name=\"To USB Flash Drive\" Value=\"j2\" /></Options><Desc>1 character with value  - 'j0' - To PC  - 'j2' - To USB Flash Drive  - 'j4' - To SD card</Desc></Arg><Arg Name=\"OptionCSVformat\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"X\" /><Option Name=\"Yes\" Value=\"C\" /></Options><Desc>1 symbol with value:  - 'C' - Yes  - 'X' - No</Desc></Arg><Arg Name=\"FlagsReceipts\" Value=\"\" Type=\"Flags\" MaxLen=\"1\"><Desc>1 symbol for Receipts included in EJ: Flags.7=0 Flags.6=1 Flags.5=1 Yes, Flags.5=0 No (Include PO) Flags.4=1 Yes, Flags.4=0 No (Include RA) Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) Flags.1=1 Yes, Flags.1=0 No (Include Invoice) Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)</Desc></Arg><Arg Name=\"FlagsReports\" Value=\"\" Type=\"Flags\" MaxLen=\"1\"><Desc>1 symbol for Reports included in EJ: Flags.7=0 Flags.6=1 Flags.5=0 Flags.4=1 Yes, Flags.4=0 No (Include FM reports) Flags.3=1 Yes, Flags.3=0 No (Include Other reports) Flags.2=1 Yes, Flags.2=0 No (Include Daily X) Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)</Desc></Arg><Arg Name=\"\" Value=\"*\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StorageReport[2]> <;> <CSVformat[1]> <;> <FlagsReceipts[1]> <;> <FlagsReports[1]> <;> <'*'> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"PrintBriefFMDepartmentsReport\" CmdByte=\"0x77\"><FPOperation>Prints a brief Departments report from the FM.</FPOperation><Args><Arg Name=\"Option\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <Option['D']> ]]></ArgsFormatRaw></Args></Command><Command Name=\"DisplayTextLine2\" CmdByte=\"0x26\"><FPOperation>Shows a 20-symbols text in the lower external display line.</FPOperation><Args><Arg Name=\"Text\" Value=\"\" Type=\"Text\" MaxLen=\"20\"><Desc>20 symbols text</Desc></Arg><ArgsFormatRaw><![CDATA[ <Text[20]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadDailyCounters\" CmdByte=\"0x6E\"><FPOperation>Provides information about the current reading of the daily-report- with-zeroing counter, the number of the last block stored in FM, the number of EJ and the date and time of the last block storage in the FM.</FPOperation><Args><Arg Name=\"\" Value=\"5\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'5'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"5\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"LastReportNumFromReset\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for number of the last report from reset</Desc></Res><Res Name=\"LastFMBlockNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for number of the last FM report</Desc></Res><Res Name=\"EJNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for number of EJ</Desc></Res><Res Name=\"DateTime\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"dd-MM-yyyy HH:mm\"><Desc>16 symbols for date and time of the last block storage in FM in format \"DD-MM-YYYY HH:MM\"</Desc></Res><ResFormatRaw><![CDATA[<'5'> <;> <LastReportNumFromReset[1..5]> <;> <LastFMBlockNum[1..5]> <;> <EJNum[1..5]> <;> <DateTime \"DD-MM-YYYY HH:MM\">]]></ResFormatRaw></Response></Command><Command Name=\"SetWiFi_Password\" CmdByte=\"0x4E\"><FPOperation>Program device's WiFi network password where it will connect. To apply use -SaveNetworkSettings()</FPOperation><Args><Arg Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"W\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"PassLength\" Value=\"\" Type=\"Decimal\" MaxLen=\"3\"><Desc>Up to 3 symbols for the WiFi password len</Desc></Arg><Arg Name=\"Password\" Value=\"\" Type=\"Text\" MaxLen=\"100\"><Desc>Up to 100 symbols for the device's WiFi password</Desc></Arg><ArgsFormatRaw><![CDATA[ <'P'><;><'W'><;><'P'><;><PassLength[1..3]><;><Password[100]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadScaleQuantity\" CmdByte=\"0x5A\"><FPOperation>Provides information about the current quantity measured by scale</FPOperation><Args><Arg Name=\"Option\" Value=\"Q\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <Option['Q']> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"Quantity\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for quantity</Desc></Res><ResFormatRaw><![CDATA[<Quantity[1..13]>]]></ResFormatRaw></Response></Command><Command Name=\"PrintEJCustom\" CmdByte=\"0x7C\"><FPOperation>Print Electronic Journal report with selected documents content. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.</FPOperation><Args><Arg Name=\"\" Value=\"j1\" Type=\"OptionHardcoded\" MaxLen=\"2\" /><Arg Name=\"\" Value=\"X\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"FlagsReceipts\" Value=\"\" Type=\"Flags\" MaxLen=\"1\"><Desc>1 symbol for Receipts included in EJ: Flags.7=0 Flags.6=1 Flags.5=1 Yes, Flags.5=0 No (Include PO) Flags.4=1 Yes, Flags.4=0 No (Include RA) Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) Flags.1=1 Yes, Flags.1=0 No (Include Invoice) Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)</Desc></Arg><Arg Name=\"FlagsReports\" Value=\"\" Type=\"Flags\" MaxLen=\"1\"><Desc>1 symbol for Reports included in EJ: Flags.7=0 Flags.6=1 Flags.5=0 Flags.4=1 Yes, Flags.4=0 No (Include FM reports) Flags.3=1 Yes, Flags.3=0 No (Include Other reports) Flags.2=1 Yes, Flags.2=0 No (Include Daily X) Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)</Desc></Arg><Arg Name=\"\" Value=\"*\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'j1'> <;> <'X'> <;> <FlagsReceipts [1]> <;> <FlagsReports [1]> <;> <'*'> ]]></ArgsFormatRaw></Args></Command><Command Name=\"StartTest_Bluetooth\" CmdByte=\"0x4E\"><FPOperation>Start Bluetooth test on the device and print out the result</FPOperation><Args><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"B\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"T\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'R'><;><'B'><;><'T'> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadBriefFMDepartmentsReportByDate\" CmdByte=\"0x7B\"><FPOperation>Read a brief FM Departments report by initial and end date.</FPOperation><Args><Arg Name=\"StartDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name=\"EndDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name=\"Option\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionReading\" Value=\"8\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartDate \"DDMMYY\"> <;> <EndDate \"DDMMYY\"> <;> <Option['D']> <;> <OptionReading['8']> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"EraseAllPLUs\" CmdByte=\"0x4B\"><FPOperation>Erase all articles in PLU database.</FPOperation><Args><Arg Name=\"PLUNum\" Value=\"00000\" Type=\"OptionHardcoded\" MaxLen=\"5\" /><Arg Name=\"Option\" Value=\"#@$+$\" Type=\"OptionHardcoded\" MaxLen=\"5\" /><Arg Name=\"Password\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for password</Desc></Arg><ArgsFormatRaw><![CDATA[ <PLUNum['00000']> <;> <Option['#@$+$']> <;> <Password[6]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"PrintDetailedFMDepartmentsReportByDate\" CmdByte=\"0x7A\"><FPOperation>Print a detailed FM Departments report by initial and end date.</FPOperation><Args><Arg Name=\"StartDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name=\"EndDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name=\"Option\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartDate \"DDMMYY\"> <;> <EndDate \"DDMMYY\"> <;> <Option['D']> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ConfirmFiscalization\" CmdByte=\"0x41\"><FPOperation>Confirm Unique Identification Code (UIC) and UIC type into the operative memory.</FPOperation><Args><Arg Name=\"Password\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6-symbols string</Desc></Arg><Arg Name=\"\" Value=\"2\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <Password[6]> <;> <'2'> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadElectronicReceipt_QR_Data\" CmdByte=\"0x72\"><FPOperation>Starts session for reading electronic receipt by number with its QR code data in the end.</FPOperation><Args><Arg Name=\"\" Value=\"e\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"RcpNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"000000\"><Desc>6 symbols with format ######</Desc></Arg><ArgsFormatRaw><![CDATA[ <'e'><;><RcpNum[6]> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"ReadDailyRAbyOperator\" CmdByte=\"0x6F\"><FPOperation>Read the RA by type of payment and the total number of operations by specified operator.</FPOperation><Args><Arg Name=\"\" Value=\"2\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Arg><ArgsFormatRaw><![CDATA[ <'2'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"2\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Res><Res Name=\"AmountRA_Payment0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the RA by type of payment 0</Desc></Res><Res Name=\"AmountRA_Payment1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the RA by type of payment 1</Desc></Res><Res Name=\"AmountRA_Payment2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the RA by type of payment 2</Desc></Res><Res Name=\"AmountRA_Payment3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the RA by type of payment 3</Desc></Res><Res Name=\"AmountRA_Payment4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the RA by type of payment 4</Desc></Res><Res Name=\"AmountRA_Payment5\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the RA by type of payment 5</Desc></Res><Res Name=\"AmountRA_Payment6\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the RA by type of payment 6</Desc></Res><Res Name=\"AmountRA_Payment7\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the RA by type of payment 7</Desc></Res><Res Name=\"AmountRA_Payment8\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the RA by type of payment 8</Desc></Res><Res Name=\"AmountRA_Payment9\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the RA by type of payment 9</Desc></Res><Res Name=\"AmountRA_Payment10\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the RA by type of payment 10</Desc></Res><Res Name=\"AmountRA_Payment11\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the RA by type of payment 11</Desc></Res><Res Name=\"NoRA\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for the total number of operations</Desc></Res><ResFormatRaw><![CDATA[<'2'> <;> <OperNum[1..2]> <;> <AmountRA_Payment0[1..13]> <;> <AmountRA_Payment1[1..13]> <;> <AmountRA_Payment2[1..13]> <;> <AmountRA_Payment3[1..13]> <;> <AmountRA_Payment4[1..13]> <;> <AmountRA_Payment5[1..13]> <;><AmountRA_Payment6[1..13]> <;> <AmountRA_Payment7[1..13]> <;><AmountRA_Payment8[1..13]> <;> <AmountRA_Payment9[1..13]> <;><AmountRA_Payment10[1..13]> <;> <AmountRA_Payment11[1..13]> <;> <NoRA[1..5]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadDailyReportParameter\" CmdByte=\"0x4F\"><FPOperation>Provide information about automatic daily report printing or not printing parameter</FPOperation><Args><Arg Name=\"\" Value=\"H\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'H'> <;> <'R'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"H\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OptionDailyReport\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Generate automatic Z report\" Value=\"0\" /><Option Name=\"Print automatic Z report\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '1' - Print automatic Z report  - '0' - Generate automatic Z report</Desc></Res><ResFormatRaw><![CDATA[<'H'> <;> <'R'> <;> <OptionDailyReport[1]>]]></ResFormatRaw></Response></Command><Command Name=\"StartTest_GPRS\" CmdByte=\"0x4E\"><FPOperation>Start GPRS test on the device and print out the result</FPOperation><Args><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"G\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"T\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'R'><;><'G'><;><'T'> ]]></ArgsFormatRaw></Args></Command><Command Name=\"DisplayDailyTurnover\" CmdByte=\"0x6E\"><FPOperation>Provides information about daily turnover on the FD client display</FPOperation><Args><Arg Name=\"\" Value=\":\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <':'> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadHeader\" CmdByte=\"0x69\"><FPOperation>Provides the content of the header lines</FPOperation><Args><Arg Name=\"OptionHeaderLine\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Header 1\" Value=\"1\" /><Option Name=\"Header 2\" Value=\"2\" /><Option Name=\"Header 3\" Value=\"3\" /><Option Name=\"Header 4\" Value=\"4\" /><Option Name=\"Header 5\" Value=\"5\" /><Option Name=\"Header 6\" Value=\"6\" /><Option Name=\"Header 7\" Value=\"7\" /></Options><Desc>1 symbol with value:  - '1' - Header 1  - '2' - Header 2  - '3' - Header 3  - '4' - Header 4  - '5' - Header 5  - '6' - Header 6  - '7' - Header 7</Desc></Arg><ArgsFormatRaw><![CDATA[ <OptionHeaderLine[1]> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"OptionHeaderLine\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Header 1\" Value=\"1\" /><Option Name=\"Header 2\" Value=\"2\" /><Option Name=\"Header 3\" Value=\"3\" /><Option Name=\"Header 4\" Value=\"4\" /><Option Name=\"Header 5\" Value=\"5\" /><Option Name=\"Header 6\" Value=\"6\" /><Option Name=\"Header 7\" Value=\"7\" /></Options><Desc>(Line Number) 1 symbol with value:  - '1' - Header 1  - '2' - Header 2  - '3' - Header 3  - '4' - Header 4  - '5' - Header 5  - '6' - Header 6  - '7' - Header 7</Desc></Res><Res Name=\"HeaderText\" Value=\"\" Type=\"Text\" MaxLen=\"64\"><Desc>TextLength symbols for header lines</Desc></Res><ResFormatRaw><![CDATA[<OptionHeaderLine[1]> <;> <HeaderText[TextLength]>]]></ResFormatRaw></Response></Command><Command Name=\"CutPaper\" CmdByte=\"0x29\"><FPOperation>Start paper cutter. The command works only in fiscal printer devices.</FPOperation></Command><Command Name=\"ReadDeviceModuleSupportByFirmware\" CmdByte=\"0x4E\"><FPOperation>Provide an information about modules supported by device's firmware</FPOperation><Args><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"S\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'R'><;><'D'><;><'S'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"S\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OptionLAN\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol for LAN suppor - '0' - No  - '1' - Yes</Desc></Res><Res Name=\"OptionWiFi\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol for WiFi support - '0' - No  - '1' - Yes</Desc></Res><Res Name=\"OptionGPRS\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol for GPRS support - '0' - No  - '1' - Yes BT (Bluetooth) 1 symbol for Bluetooth support - '0' - No  - '1' - Yes</Desc></Res><Res Name=\"OptionBT\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>(Bluetooth) 1 symbol for Bluetooth support - '0' - No  - '1' - Yes</Desc></Res><ResFormatRaw><![CDATA[<'R'><;><'D'><;><'S'><;><LAN[1]><;><WiFi[1]><;><GPRS[1]><;><BT[1]>]]></ResFormatRaw></Response></Command><Command Name=\"SetInvoiceRange\" CmdByte=\"0x50\"><FPOperation>Set invoice start and end number range. To execute the command is necessary to grand following condition: the number range to be spent, not used, or not set after the last RAM reset.</FPOperation><Args><Arg Name=\"StartNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"10\" Format=\"0000000000\"><Desc>10 characters for start number in format: ##########</Desc></Arg><Arg Name=\"EndNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"10\" Format=\"0000000000\"><Desc>10 characters for end number in format: ##########</Desc></Arg><ArgsFormatRaw><![CDATA[ <StartNum[10]> <;> <EndNum[10]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadWiFi_Password\" CmdByte=\"0x4E\"><FPOperation>Read device's connected WiFi network password</FPOperation><Args><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"W\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'R'><;><'W'><;><'P'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"W\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"PassLength\" Value=\"\" Type=\"Decimal\" MaxLen=\"3\"><Desc>(Length) Up to 3 symbols for the WiFi password length</Desc></Res><Res Name=\"Password\" Value=\"\" Type=\"Text\" MaxLen=\"100\"><Desc>Up to 100 symbols for the device's WiFi password</Desc></Res><ResFormatRaw><![CDATA[<'R'><;><'W'><;><'P'><;><PassLength[1..3]><;><Password[100]>]]></ResFormatRaw></Response></Command><Command Name=\"ProgPLUbarcode\" CmdByte=\"0x4B\"><FPOperation>Programs Barcode of article in the internal database.</FPOperation><Args><Arg Name=\"PLUNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"5\" Format=\"00000\"><Desc>5 symbols for article number in format: #####</Desc></Arg><Arg Name=\"Option\" Value=\"#@3+$\" Type=\"OptionHardcoded\" MaxLen=\"5\" /><Arg Name=\"Barcode\" Value=\"\" Type=\"Text\" MaxLen=\"13\"><Desc>13 symbols for barcode</Desc></Arg><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Option['#@3+$']> <;> <Barcode[13]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"PrintDetailedFMReportByDate\" CmdByte=\"0x7A\"><FPOperation>Prints a detailed FM report by initial and end date.</FPOperation><Args><Arg Name=\"StartDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name=\"EndDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><ArgsFormatRaw><![CDATA[ <StartDate \"DDMMYY\"> <;> <EndDate \"DDMMYY\"> ]]></ArgsFormatRaw></Args></Command><Command Name=\"PrintOrStoreEJByZBlocks\" CmdByte=\"0x7C\"><FPOperation>Print or store Electronic Journal Report from by number of Z report blocks.</FPOperation><Args><Arg Name=\"OptionReportStorage\" Value=\"\" Type=\"Option\" MaxLen=\"2\"><Options><Option Name=\"Printing\" Value=\"J1\" /><Option Name=\"SD card storage\" Value=\"J4\" /><Option Name=\"USB storage\" Value=\"J2\" /></Options><Desc>1 character with value:  - 'J1' - Printing  - 'J2' - USB storage  - 'J4' - SD card storage</Desc></Arg><Arg Name=\"\" Value=\"Z\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"StartZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for initial number report in format ####</Desc></Arg><Arg Name=\"EndZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for final number report in format ####</Desc></Arg><ArgsFormatRaw><![CDATA[ <ReportStorage[2]> <;> <'Z'> <;> <StartZNum[4]> <;> <EndZNum[4]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"SellFractQtyPLUwithSpecifiedVATfromDep\" CmdByte=\"0x3D\"><FPOperation>Register the sell (for correction use minus sign in the price field) of article with specified VAT. If department is present the relevant accumulations are perfomed in its registers.</FPOperation><Args><Arg Name=\"NamePLU\" Value=\"\" Type=\"Text\" MaxLen=\"36\"><Desc>36 symbols for article's name. 34 symbols are printed on paper. Symbol 0x7C '|' is new line separator.</Desc></Arg><Arg Name=\"OptionVATClass\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Forbidden\" Value=\"*\" /><Option Name=\"VAT Class 0\" Value=\"А\" /><Option Name=\"VAT Class 1\" Value=\"Б\" /><Option Name=\"VAT Class 2\" Value=\"В\" /><Option Name=\"VAT Class 3\" Value=\"Г\" /><Option Name=\"VAT Class 4\" Value=\"Д\" /><Option Name=\"VAT Class 5\" Value=\"Е\" /><Option Name=\"VAT Class 6\" Value=\"Ж\" /><Option Name=\"VAT Class 7\" Value=\"З\" /></Options><Desc>1 character for VAT class:  - 'А' - VAT Class 0  - 'Б' - VAT Class 1  - 'В' - VAT Class 2  - 'Г' - VAT Class 3  - 'Д' - VAT Class 4  - 'Е' - VAT Class 5  - 'Ж' - VAT Class 6  - 'З' - VAT Class 7  - '*' - Forbidden</Desc></Arg><Arg Name=\"Price\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for article's price. Use minus sign '-' for correction</Desc></Arg><Arg Name=\"Quantity\" Value=\"\" Type=\"Text\" MaxLen=\"10\"><Desc>From 3 to 10 symbols for quantity in format fractional format, e.g. 1/3</Desc><Meta MinLen=\"10\" Compulsory=\"false\" ValIndicatingPresence=\"*\" /></Arg><Arg Name=\"DiscAddP\" Value=\"\" Type=\"Decimal\" MaxLen=\"7\"><Desc>Up to 7 symbols for percentage of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\",\" /></Arg><Arg Name=\"DiscAddV\" Value=\"\" Type=\"Decimal\" MaxLen=\"8\"><Desc>Up to 8 symbols for value of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\":\" /></Arg><Arg Name=\"DepNum\" Value=\"\" Type=\"Decimal_plus_80h\" MaxLen=\"2\"><Desc>1 symbol for article department attachment, formed in the following manner; example: Dep01 = 81h, Dep02 = 82h … Dep19 = 93h Department range from 1 to 127</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\"!\" /></Arg><ArgsFormatRaw><![CDATA[ <NamePLU[36]> <;> <OptionVATClass[1]> <;> <Price[1..10]> {<'*'> <Quantity[10]>} {<','> <DiscAddP[1..7]>} {<':'> <DiscAddV[1..8]>} {<'!'> <DepNum[1]>} ]]></ArgsFormatRaw></Args></Command><Command Name=\"PrintBriefFMDepartmentsReportByZBlocks\" CmdByte=\"0x79\"><FPOperation>Print a brief FM Departments report by initial and end Z report number.</FPOperation><Args><Arg Name=\"StartZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for the initial FM report number included in report, format ####</Desc></Arg><Arg Name=\"EndZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for the final FM report number included in report, format ####</Desc></Arg><Arg Name=\"Option\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <Option['D']> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadPLUgeneral\" CmdByte=\"0x6B\"><FPOperation>Provides information about the general registers of the specified article.</FPOperation><Args><Arg Name=\"PLUNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"5\" Format=\"00000\"><Desc>5 symbols for article number with leading zeroes in format: #####</Desc></Arg><Arg Name=\"Option\" Value=\"1\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Option['1']> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"PLUNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"5\" Format=\"00000\"><Desc>5 symbols for article number with leading zeroes in format #####</Desc></Res><Res Name=\"Option\" Value=\"1\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"PLUName\" Value=\"\" Type=\"Text\" MaxLen=\"34\"><Desc>34 symbols for article name, new line=0x7C.</Desc></Res><Res Name=\"Price\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for article price</Desc></Res><Res Name=\"OptionPrice\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Free price is disable valid only programmed price\" Value=\"0\" /><Option Name=\"Free price is enable\" Value=\"1\" /><Option Name=\"Limited price\" Value=\"2\" /></Options><Desc>1 symbol for price flag with next value:  - '0'- Free price is disable valid only programmed price  - '1'- Free price is enable  - '2'- Limited price</Desc></Res><Res Name=\"OptionVATClass\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Forbidden\" Value=\"*\" /><Option Name=\"VAT Class 0\" Value=\"А\" /><Option Name=\"VAT Class 1\" Value=\"Б\" /><Option Name=\"VAT Class 2\" Value=\"В\" /><Option Name=\"VAT Class 3\" Value=\"Г\" /><Option Name=\"VAT Class 4\" Value=\"Д\" /><Option Name=\"VAT Class 5\" Value=\"Е\" /><Option Name=\"VAT Class 6\" Value=\"Ж\" /><Option Name=\"VAT Class 7\" Value=\"З\" /></Options><Desc>1 character for VAT class:  - 'А' - VAT Class 0  - 'Б' - VAT Class 1  - 'В' - VAT Class 2  - 'Г' - VAT Class 3  - 'Д' - VAT Class 4  - 'Е' - VAT Class 5  - 'Ж' - VAT Class 6  - 'З' - VAT Class 7  - '*' - Forbidden</Desc></Res><Res Name=\"BelongToDepNumber\" Value=\"\" Type=\"Decimal_plus_80h\" MaxLen=\"2\"><Desc>BelongToDepNumber + 80h, 1 symbol for PLU department attachment= 0x80 … 0x93  Department range from 1 to 127</Desc></Res><Res Name=\"TurnoverAmount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for PLU accumulated turnover</Desc></Res><Res Name=\"SoldQuantity\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for Sales quantity of the article</Desc></Res><Res Name=\"StornoAmount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for accumulated storno amount</Desc></Res><Res Name=\"StornoQuantity\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for accumulated storno quantiy</Desc></Res><Res Name=\"LastZReportNumber\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for the number of the last article report with zeroing</Desc></Res><Res Name=\"LastZReportDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"dd-MM-yyyy HH:mm\"><Desc>16 symbols for the date and time of the last article report with zeroing in format DD-MM-YYYY HH:MM</Desc></Res><Res Name=\"OptionSingleTransaction\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Active Single transaction in receipt\" Value=\"1\" /><Option Name=\"Inactive, default value\" Value=\"0\" /></Options><Desc>1 symbol with value:  - '0' - Inactive, default value  - '1' - Active Single transaction in receipt</Desc></Res><ResFormatRaw><![CDATA[<PLUNum[5]> <;> <Option['1']> <;> <PLUName[34]> <;> <Price[1..10]> <;> <OptionPrice[1]> <;> <OptionVATClass[1]> <;> <BelongToDepNumber[1]> <;> <TurnoverAmount[1..13]> <;> <SoldQuantity[1..13]> <;> <StornoAmount[1..13]> <;> <StornoQuantity[1..13]> <;> <LastZReportNumber[1..5]> <;> <LastZReportDate \"DD-MM-YYYY HH:MM\"> <;> <SingleTransaction[1]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadDailyReceivedSalesAmountsByOperator\" CmdByte=\"0x6F\"><FPOperation>Read the amounts received from sales by type of payment and specified operator.</FPOperation><Args><Arg Name=\"\" Value=\"4\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Arg><ArgsFormatRaw><![CDATA[ <'4'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"4\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Res><Res Name=\"ReceivedSalesAmountPayment0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by sales for payment 0</Desc></Res><Res Name=\"ReceivedSalesAmountPayment1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by sales for payment 1</Desc></Res><Res Name=\"ReceivedSalesAmountPayment2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by sales for payment 2</Desc></Res><Res Name=\"ReceivedSalesAmountPayment3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by sales for payment 3</Desc></Res><Res Name=\"ReceivedSalesAmountPayment4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by sales for payment 4</Desc></Res><Res Name=\"ReceivedSalesAmountPayment5\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by sales for payment 5</Desc></Res><Res Name=\"ReceivedSalesAmountPayment6\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by sales for payment 6</Desc></Res><Res Name=\"ReceivedSalesAmountPayment7\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by sales for payment 7</Desc></Res><Res Name=\"ReceivedSalesAmountPayment8\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by sales for payment 8</Desc></Res><Res Name=\"ReceivedSalesAmountPayment9\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by sales for payment 9</Desc></Res><Res Name=\"ReceivedSalesAmountPayment10\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by sales for payment 10</Desc></Res><Res Name=\"ReceivedSalesAmountPayment11\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by sales for payment 11</Desc></Res><ResFormatRaw><![CDATA[<'4'> <;> <OperNum[1..2]> <;> <ReceivedSalesAmountPayment0[1..13]> <;> <ReceivedSalesAmountPayment1[1..13]> <;> <ReceivedSalesAmountPayment2[1..13]> <;> <ReceivedSalesAmountPayment3[1..13]> <;> <ReceivedSalesAmountPayment4[1..13]> <;> <ReceivedSalesAmountPayment5[1..13]> <;> <ReceivedSalesAmountPayment6[1..13]> <;> <ReceivedSalesAmountPayment7[1..13]> <;> <ReceivedSalesAmountPayment8[1..13]> <;> <ReceivedSalesAmountPayment9[1..13]> <;> <ReceivedSalesAmountPayment10[1..13]> <;> <ReceivedSalesAmountPayment11[1..13]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadCustomerData\" CmdByte=\"0x52\"><FPOperation>Provide information for specified customer from FD data base.</FPOperation><Args><Arg Name=\"Option\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"CustomerNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for customer number in format ####</Desc></Arg><ArgsFormatRaw><![CDATA[ <Option['R']> <;> <CustomerNum[4]> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"CustomerNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>(Customer Number) 4 symbols for customer number in format ####</Desc></Res><Res Name=\"CustomerCompanyName\" Value=\"\" Type=\"Text\" MaxLen=\"26\"><Desc>(Company name) 26 symbols for customer name</Desc></Res><Res Name=\"CustomerFullName\" Value=\"\" Type=\"Text\" MaxLen=\"16\"><Desc>(Buyer Name) 16 symbols for Buyer name</Desc></Res><Res Name=\"VATNumber\" Value=\"\" Type=\"Text\" MaxLen=\"13\"><Desc>13 symbols for VAT number on customer</Desc></Res><Res Name=\"UIC\" Value=\"\" Type=\"Text\" MaxLen=\"13\"><Desc>13 symbols for customer Unique Identification Code</Desc></Res><Res Name=\"Address\" Value=\"\" Type=\"Text\" MaxLen=\"30\"><Desc>30 symbols for address on customer</Desc></Res><Res Name=\"OptionUICType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Bulstat\" Value=\"0\" /><Option Name=\"EGN\" Value=\"1\" /><Option Name=\"Foreigner Number\" Value=\"2\" /><Option Name=\"NRA Official Number\" Value=\"3\" /></Options><Desc>1 symbol for type of Unique Identification Code:  - '0' - Bulstat  - '1' - EGN  - '2' - Foreigner Number  - '3' - NRA Official Number</Desc></Res><ResFormatRaw><![CDATA[<CustomerNum[4]> <;> <CustomerCompanyName[26]> <;> <CustomerFullName[16]> <;> <VATNumber[13]> <;> <UIC[13]> <;> <Address[30]> <;> <UICType[1]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadCurrentReceiptInfo\" CmdByte=\"0x72\"><FPOperation>Read the current status of the receipt.</FPOperation><Response ACK=\"false\"><Res Name=\"OptionIsReceiptOpened\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '0' - No  - '1' - Yes</Desc></Res><Res Name=\"SalesNumber\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"3\" Format=\"000\"><Desc>3 symbols for number of sales in format ###</Desc></Res><Res Name=\"SubtotalAmountVAT0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for subtotal by VAT group А</Desc></Res><Res Name=\"SubtotalAmountVAT1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for subtotal by VAT group Б</Desc></Res><Res Name=\"SubtotalAmountVAT2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for subtotal by VAT group В</Desc></Res><Res Name=\"OptionForbiddenVoid\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"allowed\" Value=\"0\" /><Option Name=\"forbidden\" Value=\"1\" /></Options><Desc>1 symbol with value: - '0' - allowed - '1' - forbidden</Desc></Res><Res Name=\"OptionVATinReceipt\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol with value: - '0' - No - '1' - Yes</Desc></Res><Res Name=\"OptionReceiptFormat\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Brief\" Value=\"0\" /><Option Name=\"Detailed\" Value=\"1\" /></Options><Desc>(Format) 1 symbol with value:  - '1' - Detailed  - '0' - Brief</Desc></Res><Res Name=\"OptionInitiatedPayment\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol with value: - '0' - No - '1' - Yes</Desc></Res><Res Name=\"OptionFinalizedPayment\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol with value: - '0' - No - '1' - Yes</Desc></Res><Res Name=\"OptionPowerDownInReceipt\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol with value: - '0' - No - '1' - Yes</Desc></Res><Res Name=\"OptionTypeReceipt\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Invoice Credit note receipt Postponed Printing\" Value=\"7\" /><Option Name=\"Invoice Credit note receipt printing step by step\" Value=\"5\" /><Option Name=\"Invoice sales receipt Postponed Printing\" Value=\"3\" /><Option Name=\"Invoice sales receipt printing step by step\" Value=\"1\" /><Option Name=\"Sales receipt Postponed Printing\" Value=\"2\" /><Option Name=\"Sales receipt printing step by step\" Value=\"0\" /><Option Name=\"Storno receipt Postponed Printing\" Value=\"6\" /><Option Name=\"Storno receipt printing step by step\" Value=\"4\" /></Options><Desc>(Receipt and Printing type) 1 symbol with value:  - '0' - Sales receipt printing step by step  - '2' - Sales receipt Postponed Printing  - '4' - Storno receipt printing step by step  - '6' - Storno receipt Postponed Printing  - '1' - Invoice sales receipt printing step by step  - '3' - Invoice sales receipt Postponed Printing  - '5' - Invoice Credit note receipt printing step by step  - '7' - Invoice Credit note receipt Postponed Printing</Desc></Res><Res Name=\"ChangeAmount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols the amount of the due change in the stated payment type</Desc></Res><Res Name=\"OptionChangeType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Change In Cash\" Value=\"0\" /><Option Name=\"Change In Currency\" Value=\"2\" /><Option Name=\"Same As The payment\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '0' - Change In Cash  - '1' - Same As The payment  - '2' - Change In Currency</Desc></Res><Res Name=\"SubtotalAmountVAT3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for subtotal by VAT group Г</Desc></Res><Res Name=\"SubtotalAmountVAT4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for subtotal by VAT group Д</Desc></Res><Res Name=\"SubtotalAmountVAT5\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for subtotal by VAT group Е</Desc></Res><Res Name=\"SubtotalAmountVAT6\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for subtotal by VAT group Ж</Desc></Res><Res Name=\"SubtotalAmountVAT7\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for subtotal by VAT group З</Desc></Res><Res Name=\"CurrentReceiptNumber\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"000000\"><Desc>6 symbols for fiscal receipt number in format ######</Desc></Res><ResFormatRaw><![CDATA[<IsReceiptOpened[1]> <;> <SalesNumber[3]> <;> <SubtotalAmountVAT0[1..13]> <;> <SubtotalAmountVAT1[1..13]> <;> <SubtotalAmountVAT2[1..13]> <;> <ForbiddenVoid[1]> <;> <VATinReceipt[1]> <;> <ReceiptFormat[1]> <;> <InitiatedPayment[1]> <;> <FinalizedPayment[1]> <;> <PowerDownInReceipt[1]> <;> <TypeReceipt[1]> <;> <ChangeAmount[1..13]> <;> <OptionChangeType[1]> <;> <SubtotalAmountVAT3[1..13]> <;> <SubtotalAmountVAT4[1..13]> <;> <SubtotalAmountVAT5[1..13]> <;> <SubtotalAmountVAT6[1..13]> <;> <SubtotalAmountVAT7[1..13]> <;> <CurrentReceiptNumber[6]>]]></ResFormatRaw></Response></Command><Command Name=\"OpenInvoiceWithFDCustomerDB\" CmdByte=\"0x30\"><FPOperation>Opens a fiscal invoice receipt assigned to the specified operator number and operator password with internal DB info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.</FPOperation><Args><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbol from 1 to 20 corresponding to operator's number</Desc></Arg><Arg Name=\"OperPass\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for operator's password</Desc></Arg><Arg Name=\"reserved\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"reserved\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionInvoicePrintType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Buffered Printing\" Value=\"5\" /><Option Name=\"Postponed Printing\" Value=\"3\" /><Option Name=\"Step by step printing\" Value=\"1\" /></Options><Desc>1 symbol with value: - '1' - Step by step printing - '3' - Postponed Printing - '5' - Buffered Printing</Desc></Arg><Arg Name=\"CustomerNum\" Value=\"\" Type=\"Text\" MaxLen=\"5\"><Desc>Symbol '#' and following up to 4 symbols for related customer ID number corresponding to the FD database</Desc></Arg><Arg Name=\"UniqueReceiptNumber\" Value=\"\" Type=\"Text\" MaxLen=\"24\"><Desc>Up to 24 symbols for unique receipt number. NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, * YYYYYYY - 7 symbols [0-9] for next number of the receipt</Desc><Meta MinLen=\"24\" Compulsory=\"false\" ValIndicatingPresence=\"$\" /></Arg><ArgsFormatRaw><![CDATA[ <OperNum[1..2]> <;> <OperPass[6]> <;> <reserved['0']> <;> <reserved['0']> <;> <InvoicePrintType[1]> <;> <CustomerNum[5]> { <'$'> <UniqueReceiptNumber[24]> } ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadPLUallData\" CmdByte=\"0x6B\"><FPOperation>Provides information about all the registers of the specified article.</FPOperation><Args><Arg Name=\"PLUNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"5\" Format=\"00000\"><Desc>5 symbols for article number with leading zeroes in format: #####</Desc></Arg><Arg Name=\"Option\" Value=\"&quot;\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Option['\"']> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"PLUNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"5\" Format=\"00000\"><Desc>5 symbols for article number with leading zeroes in format: #####</Desc></Res><Res Name=\"Option\" Value=\"&quot;\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"PLUName\" Value=\"\" Type=\"Text\" MaxLen=\"34\"><Desc>34 symbols for article name, new line=0x7C.</Desc></Res><Res Name=\"Price\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for article price</Desc></Res><Res Name=\"FlagsPricePLU\" Value=\"\" Type=\"Flags\" MaxLen=\"1\"><Desc>1 symbol for flags = 0x80 + FlagSinglTr + FlagQTY + OptionPrice Where  OptionPrice: 0x00 - for free price is disable valid only programmed price 0x01 - for free price is enable 0x02 - for limited price FlagQTY: 0x00 - for availability of PLU stock is not monitored 0x04 - for disable negative quantity 0x08 - for enable negative quantity FlagSingleTr: 0x00 - no single transaction 0x10 - single transaction is active</Desc></Res><Res Name=\"OptionVATClass\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Forbidden\" Value=\"*\" /><Option Name=\"VAT Class 0\" Value=\"А\" /><Option Name=\"VAT Class 1\" Value=\"Б\" /><Option Name=\"VAT Class 2\" Value=\"В\" /><Option Name=\"VAT Class 3\" Value=\"Г\" /><Option Name=\"VAT Class 4\" Value=\"Д\" /><Option Name=\"VAT Class 5\" Value=\"Е\" /><Option Name=\"VAT Class 6\" Value=\"Ж\" /><Option Name=\"VAT Class 7\" Value=\"З\" /></Options><Desc>1 character for VAT class:  - 'А' - VAT Class 0  - 'Б' - VAT Class 1  - 'В' - VAT Class 2  - 'Г' - VAT Class 3  - 'Д' - VAT Class 4  - 'Е' - VAT Class 5  - 'Ж' - VAT Class 6  - 'З' - VAT Class 7  - '*' - Forbidden</Desc></Res><Res Name=\"BelongToDepNumber\" Value=\"\" Type=\"Decimal_plus_80h\" MaxLen=\"2\"><Desc>BelongToDepNumber + 80h, 1 symbol for PLU department attachment = 0x80 … 0x93 Department range from 1 to 127</Desc></Res><Res Name=\"TurnoverAmount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for PLU accumulated turnover</Desc></Res><Res Name=\"SoldQuantity\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for Sales quantity of the article</Desc></Res><Res Name=\"StornoAmount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for accumulated storno amount</Desc></Res><Res Name=\"StornoQuantity\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for accumulated storno quantiy</Desc></Res><Res Name=\"LastZReportNumber\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for the number of the last article report with zeroing</Desc></Res><Res Name=\"LastZReportDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"dd-MM-yyyy HH:mm\"><Desc>16 symbols for the date and time of the last article report with zeroing in format DD-MM-YYYY HH:MM</Desc></Res><Res Name=\"AvailableQuantity\" Value=\"\" Type=\"Decimal\" MaxLen=\"11\"><Desc>(Available Quantity) Up to 11 symbols for quantity in stock</Desc></Res><Res Name=\"Barcode\" Value=\"\" Type=\"Text\" MaxLen=\"13\"><Desc>13 symbols for article barcode</Desc></Res><ResFormatRaw><![CDATA[<PLUNum[5]> <;> <Option['\"']> <;> <PLUName[34]> <;> <Price[1..10]> <;> <FlagsPricePLU[1]> <;> <OptionVATClass[1]> <;> <BelongToDepNumber[1]> <;> <TurnoverAmount[1..13]> <;> <SoldQuantity[1..13]> <;> <StornoAmount[1..13]> <;> <StornoQuantity[1..13]> <;> <LastZReportNumber[1..5]> <;> <LastZReportDate \"DD-MM-YYYY HH:MM\"> <;> <AvailableQuantity[1..11]> <;> <Barcode[13]>]]></ResFormatRaw></Response></Command><Command Name=\"PrintDetailedFMDepartmentsReportByZBlocks\" CmdByte=\"0x78\"><FPOperation>Print a detailed FM Departments report by initial and end Z report number.</FPOperation><Args><Arg Name=\"StartZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for initial FM report number included in report, format ####</Desc></Arg><Arg Name=\"EndZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for final FM report number included in report, format ####</Desc></Arg><Arg Name=\"Option\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <Option['D']> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadEJ\" CmdByte=\"0x7C\"><FPOperation>Read Electronic Journal report with all documents.</FPOperation><Args><Arg Name=\"OptionReportFormat\" Value=\"\" Type=\"Option\" MaxLen=\"2\"><Options><Option Name=\"Brief EJ\" Value=\"J8\" /><Option Name=\"Detailed EJ\" Value=\"J0\" /></Options><Desc>1 character with value  - 'J0' - Detailed EJ  - 'J8' - Brief EJ</Desc></Arg><Arg Name=\"\" Value=\"*\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <ReportFormat[2]> <;> <'*'> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"Payment\" CmdByte=\"0x35\"><FPOperation>Register the payment in the receipt with specified type of payment with amount received.</FPOperation><Args><Arg Name=\"OptionPaymentType\" Value=\"\" Type=\"Option\" MaxLen=\"2\"><Options><Option Name=\"Payment 0\" Value=\"0\" /><Option Name=\"Payment 1\" Value=\"1\" /><Option Name=\"Payment 10\" Value=\"10\" /><Option Name=\"Payment 11\" Value=\"11\" /><Option Name=\"Payment 2\" Value=\"2\" /><Option Name=\"Payment 3\" Value=\"3\" /><Option Name=\"Payment 4\" Value=\"4\" /><Option Name=\"Payment 5\" Value=\"5\" /><Option Name=\"Payment 6\" Value=\"6\" /><Option Name=\"Payment 7\" Value=\"7\" /><Option Name=\"Payment 8\" Value=\"8\" /><Option Name=\"Payment 9\" Value=\"9\" /></Options><Desc>1 symbol for payment type:  - '0' - Payment 0  - '1' - Payment 1  - '2' - Payment 2  - '3' - Payment 3  - '4' - Payment 4  - '5' - Payment 5  - '6' - Payment 6  - '7' - Payment 7  - '8' - Payment 8  - '9' - Payment 9  - '10' - Payment 10  - '11' - Payment 11</Desc></Arg><Arg Name=\"OptionChange\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"With Change\" Value=\"0\" /><Option Name=\"Without Change\" Value=\"1\" /></Options><Desc>Default value is 0, 1 symbol with value:  - '0 - With Change  - '1' - Without Change</Desc></Arg><Arg Name=\"Amount\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 characters for received amount</Desc></Arg><Arg Name=\"OptionChangeType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Change In Cash\" Value=\"0\" /><Option Name=\"Change In Currency\" Value=\"2\" /><Option Name=\"Same As The payment\" Value=\"1\" /></Options><Desc>1 symbols with value:  - '0' - Change In Cash  - '1' - Same As The payment  - '2' - Change In Currency</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\";\" /></Arg><ArgsFormatRaw><![CDATA[ <PaymentType[1..2]> <;> <OptionChange[1]> <;> <Amount[1..10]> { <;> <OptionChangeType[1]> } ]]></ArgsFormatRaw></Args></Command><Command Name=\"SetDeviceTCP_Addresses\" CmdByte=\"0x4E\"><FPOperation>Program device's network IP address, subnet mask, gateway address, DNS address. To apply use -SaveNetworkSettings()</FPOperation><Args><Arg Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"T\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionAddressType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"DNS address\" Value=\"5\" /><Option Name=\"Gateway address\" Value=\"4\" /><Option Name=\"IP address\" Value=\"2\" /><Option Name=\"Subnet Mask\" Value=\"3\" /></Options><Desc>1 symbol with value:  - '2' - IP address  - '3' - Subnet Mask  - '4' - Gateway address  - '5' - DNS address</Desc></Arg><Arg Name=\"DeviceAddress\" Value=\"\" Type=\"Text\" MaxLen=\"15\"><Desc>15 symbols for the selected address</Desc></Arg><ArgsFormatRaw><![CDATA[ <'P'><;><'T'><;><AddressType[1]> <;><DeviceAddress[15]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadLastDailyReportInfo\" CmdByte=\"0x73\"><FPOperation>Read date and number of last Z-report and last RAM reset event.</FPOperation><Response ACK=\"false\"><Res Name=\"LastZDailyReportDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"dd-MM-yyyy\"><Desc>10 symbols for last Z-report date in DD-MM-YYYY format</Desc></Res><Res Name=\"LastZDailyReportNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"4\"><Desc>Up to 4 symbols for the number of the last daily report</Desc></Res><Res Name=\"LastRAMResetNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"4\"><Desc>Up to 4 symbols for the number of the last RAM reset</Desc></Res><Res Name=\"TotalReceiptCounter\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"000000\"><Desc>6 symbols for the total number of receipts in format ######</Desc></Res><Res Name=\"DateTimeLastFiscRec\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"dd-MM-yyyy HH:mm\"><Desc>Date Time parameter in format: DD-MM-YYYY HH:MM</Desc></Res><Res Name=\"EJNum\" Value=\"\" Type=\"Text\" MaxLen=\"2\"><Desc>Up to 2 symbols for number of EJ</Desc></Res><Res Name=\"OptionLastReceiptType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Invoice Credit note\" Value=\"5\" /><Option Name=\"Invoice sales receipt\" Value=\"1\" /><Option Name=\"Non fiscal receipt\" Value=\"2\" /><Option Name=\"Sales receipt printing\" Value=\"0\" /><Option Name=\"Storno receipt\" Value=\"4\" /></Options><Desc>(Receipt and Printing type) 1 symbol with value:  - '0' - Sales receipt printing  - '2' - Non fiscal receipt  - '4' - Storno receipt  - '1' - Invoice sales receipt  - '5' - Invoice Credit note</Desc></Res><ResFormatRaw><![CDATA[<LastZDailyReportDate \"DD-MM-YYYY\"> <;> <LastZDailyReportNum[1..4]> <;> <LastRAMResetNum[1..4]> <;> <TotalReceiptCounter[6]> <;> <DateTimeLastFiscRec \"DD-MM-YYYY HH:MM\"> <;> <EJNum[2]> <;> <LastReceiptType[1]>]]></ResFormatRaw></Response></Command><Command Name=\"PrintText\" CmdByte=\"0x37\"><FPOperation>Print a free text. The command can be executed only if receipt is opened (Fiscal receipt, Invoice receipt, Storno receipt, Credit Note or Non-fical receipt). In the beginning and in the end of line symbol '#' is printed.</FPOperation><Args><Arg Name=\"Text\" Value=\"\" Type=\"Text\" MaxLen=\"64\"><Desc>TextLength-2 symbols</Desc></Arg><ArgsFormatRaw><![CDATA[ <Text[TextLength-2]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadPaymentsPositions\" CmdByte=\"0x64\"><FPOperation>Provides information about arrangement of payment positions according to NRA list: 0-Cash, 1-Check, 2-Talon, 3-V.Talon, 4-Packaging, 5-Service, 6- Damage, 7-Card, 8-Bank, 9-Programming Name 1, 10-Programming Name 2, 11-Currency.</FPOperation><Args><Arg Name=\"Option\" Value=\"*\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <Option['*']> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"Option\" Value=\"*\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"PaymentPosition0\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 0 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Res><Res Name=\"PaymentPosition1\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 1 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Res><Res Name=\"PaymentPosition2\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 2 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Res><Res Name=\"PaymentPosition3\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 3 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Res><Res Name=\"PaymentPosition4\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 4 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Res><Res Name=\"PaymentPosition5\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 5 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Res><Res Name=\"PaymentPosition6\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 6 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Res><Res Name=\"PaymentPosition7\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 7 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Res><Res Name=\"PaymentPosition8\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 8 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Res><Res Name=\"PaymentPosition9\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 9 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Res><Res Name=\"PaymentPosition10\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 10 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Res><Res Name=\"PaymentPosition11\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"2\" Format=\"00.\"><Desc>2 digits for payment position 11 in format ##.  Values from '1' to '11' according to NRA payments list.</Desc></Res><ResFormatRaw><![CDATA[<Option['*']> <;> <PaymentPosition0[2]> <;> <PaymentPosition1[2]> <;> <PaymentPosition2[2]> <;> <PaymentPosition3[2]> <;> <PaymentPosition4[2]> <;> <PaymentPosition5[2]> <;> <PaymentPosition6[2]> <;> <PaymentPosition7[2]> <;> <PaymentPosition8[2]> <;> <PaymentPosition9[2]> <;> <PaymentPosition10[2]> <;> <PaymentPosition11[2]>]]></ResFormatRaw></Response></Command><Command Name=\"OpenCreditNoteWithFDCustomerDB\" CmdByte=\"0x30\"><FPOperation>Opens a fiscal invoice credit note receipt assigned to the specified operator number and operator password with internal DB info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.</FPOperation><Args><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbol from 1 to 20 corresponding to operator's number</Desc></Arg><Arg Name=\"OperPass\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for operator's password</Desc></Arg><Arg Name=\"reserved\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"reserved\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionInvoiceCreditNotePrintType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Buffered Printing\" Value=\"E\" /><Option Name=\"Postponed Printing\" Value=\"C\" /><Option Name=\"Step by step printing\" Value=\"A\" /></Options><Desc>1 symbol with value: - 'A' - Step by step printing - 'C' - Postponed Printing - 'E' - Buffered Printing</Desc></Arg><Arg Name=\"CustomerNum\" Value=\"\" Type=\"Text\" MaxLen=\"5\"><Desc>Symbol '#' and following up to 4 symbols for related customer ID number corresponding to the FD database</Desc></Arg><Arg Name=\"OptionStornoReason\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Goods Claim or Goods return\" Value=\"1\" /><Option Name=\"Operator error\" Value=\"0\" /><Option Name=\"Tax relief\" Value=\"2\" /></Options><Desc>1 symbol for reason of storno operation with value:  - '0' - Operator error  - '1' - Goods Claim or Goods return  - '2' - Tax relief</Desc></Arg><Arg Name=\"RelatedToInvoiceNum\" Value=\"\" Type=\"Text\" MaxLen=\"10\"><Desc>10 symbols for issued invoice number</Desc></Arg><Arg Name=\"RelatedToInvoiceDateTime\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"dd-MM-yy HH:mm:ss\"><Desc>17 symbols for issued invoice date and time in format</Desc></Arg><Arg Name=\"RelatedToRcpNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"6\"><Desc>Up to 6 symbols for issued receipt number</Desc></Arg><Arg Name=\"FMNum\" Value=\"\" Type=\"Text\" MaxLen=\"8\"><Desc>8 symbols for number of the Fiscal Memory</Desc></Arg><Arg Name=\"RelatedToURN\" Value=\"\" Type=\"Text\" MaxLen=\"24\"><Desc>Up to 24 symbols for the issed invoice receipt unique receipt number. NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, * YYYYYYY - 7 symbols [0-9] for next number of the receipt</Desc><Meta MinLen=\"24\" Compulsory=\"false\" ValIndicatingPresence=\";\" /></Arg><ArgsFormatRaw><![CDATA[ <OperNum[1..2]> <;> <OperPass[6]> <;> <reserved['0']> <;> <reserved['0']> <;> <InvoiceCreditNotePrintType[1]> <;> <CustomerNum[5]> <;> <StornoReason[1]> <;> <RelatedToInvoiceNum[10]> <;> <RelatedToInvoiceDateTime \"DD-MM-YY HH:MM:SS\"> <;> <RelatedToRcpNum[1..6]> <;> <FMNum[8]> { <;> <RelatedToURN[24]> } ]]></ArgsFormatRaw></Args></Command><Command Name=\"ProgramDailyReportParameter\" CmdByte=\"0x4F\"><FPOperation>Program automatic daily report printing or not printing parameter.</FPOperation><Args><Arg Name=\"\" Value=\"H\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"W\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionDailyReport\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Generate automatic Z report\" Value=\"0\" /><Option Name=\"Print automatic Z report\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '1' - Print automatic Z report  - '0' - Generate automatic Z report</Desc></Arg><ArgsFormatRaw><![CDATA[ <'H'> <;> <'W'> <;> <OptionDailyReport[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"PrintOperatorReport\" CmdByte=\"0x7D\"><FPOperation>Prints an operator's report for a specified operator (0 = all operators) with or without zeroing ('Z' or 'X'). When a 'Z' value is specified the report should include all operators.</FPOperation><Args><Arg Name=\"OptionZeroing\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Without zeroing\" Value=\"X\" /><Option Name=\"Zeroing\" Value=\"Z\" /></Options><Desc>with following values:  - 'Z' - Zeroing  - 'X' - Without zeroing</Desc></Arg><Arg Name=\"Number\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 0 to 20 corresponding to operator's number ,0 for all operators</Desc></Arg><ArgsFormatRaw><![CDATA[ <OptionZeroing[1]> <;> <Number[1..2]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadStatus\" CmdByte=\"0x20\"><FPOperation>Provides detailed 7-byte information about the current status of the fiscal printer.</FPOperation><Response ACK=\"false\"><Res Name=\"FM_Read_only\" Value=\"\" Type=\"Status\" Byte=\"0\" Bit=\"0\"><Desc>FM Read only</Desc></Res><Res Name=\"Power_down_in_opened_fiscal_receipt\" Value=\"\" Type=\"Status\" Byte=\"0\" Bit=\"1\"><Desc>Power down in opened fiscal receipt</Desc></Res><Res Name=\"Printer_not_ready_overheat\" Value=\"\" Type=\"Status\" Byte=\"0\" Bit=\"2\"><Desc>Printer not ready - overheat</Desc></Res><Res Name=\"DateTime_not_set\" Value=\"\" Type=\"Status\" Byte=\"0\" Bit=\"3\"><Desc>DateTime not set</Desc></Res><Res Name=\"DateTime_wrong\" Value=\"\" Type=\"Status\" Byte=\"0\" Bit=\"4\"><Desc>DateTime wrong</Desc></Res><Res Name=\"RAM_reset\" Value=\"\" Type=\"Status\" Byte=\"0\" Bit=\"5\"><Desc>RAM reset</Desc></Res><Res Name=\"Hardware_clock_error\" Value=\"\" Type=\"Status\" Byte=\"0\" Bit=\"6\"><Desc>Hardware clock error</Desc></Res><Res Name=\"Printer_not_ready_no_paper\" Value=\"\" Type=\"Status\" Byte=\"1\" Bit=\"0\"><Desc>Printer not ready - no paper</Desc></Res><Res Name=\"Reports_registers_Overflow\" Value=\"\" Type=\"Status\" Byte=\"1\" Bit=\"1\"><Desc>Reports registers Overflow</Desc></Res><Res Name=\"Customer_report_is_not_zeroed\" Value=\"\" Type=\"Status\" Byte=\"1\" Bit=\"2\"><Desc>Customer report is not zeroed</Desc></Res><Res Name=\"Daily_report_is_not_zeroed\" Value=\"\" Type=\"Status\" Byte=\"1\" Bit=\"3\"><Desc>Daily report is not zeroed</Desc></Res><Res Name=\"Article_report_is_not_zeroed\" Value=\"\" Type=\"Status\" Byte=\"1\" Bit=\"4\"><Desc>Article report is not zeroed</Desc></Res><Res Name=\"Operator_report_is_not_zeroed\" Value=\"\" Type=\"Status\" Byte=\"1\" Bit=\"5\"><Desc>Operator report is not zeroed</Desc></Res><Res Name=\"Non_printed_copy\" Value=\"\" Type=\"Status\" Byte=\"1\" Bit=\"6\"><Desc>Non-printed copy</Desc></Res><Res Name=\"Opened_Non_fiscal_Receipt\" Value=\"\" Type=\"Status\" Byte=\"2\" Bit=\"0\"><Desc>Opened Non-fiscal Receipt</Desc></Res><Res Name=\"Opened_Fiscal_Receipt\" Value=\"\" Type=\"Status\" Byte=\"2\" Bit=\"1\"><Desc>Opened Fiscal Receipt</Desc></Res><Res Name=\"Opened_Fiscal_Detailed_Receipt\" Value=\"\" Type=\"Status\" Byte=\"2\" Bit=\"2\"><Desc>Opened Fiscal Detailed Receipt</Desc></Res><Res Name=\"Opened_Fiscal_Receipt_with_VAT\" Value=\"\" Type=\"Status\" Byte=\"2\" Bit=\"3\"><Desc>Opened Fiscal Receipt with VAT</Desc></Res><Res Name=\"Opened_Invoice_Fiscal_Receipt\" Value=\"\" Type=\"Status\" Byte=\"2\" Bit=\"4\"><Desc>Opened Invoice Fiscal Receipt</Desc></Res><Res Name=\"SD_card_near_full\" Value=\"\" Type=\"Status\" Byte=\"2\" Bit=\"5\"><Desc>SD card near full</Desc></Res><Res Name=\"SD_card_full\" Value=\"\" Type=\"Status\" Byte=\"2\" Bit=\"6\"><Desc>SD card full</Desc></Res><Res Name=\"No_FM_module\" Value=\"\" Type=\"Status\" Byte=\"3\" Bit=\"0\"><Desc>No FM module</Desc></Res><Res Name=\"FM_error\" Value=\"\" Type=\"Status\" Byte=\"3\" Bit=\"1\"><Desc>FM error</Desc></Res><Res Name=\"FM_full\" Value=\"\" Type=\"Status\" Byte=\"3\" Bit=\"2\"><Desc>FM full</Desc></Res><Res Name=\"FM_near_full\" Value=\"\" Type=\"Status\" Byte=\"3\" Bit=\"3\"><Desc>FM near full</Desc></Res><Res Name=\"Decimal_point\" Value=\"\" Type=\"Status\" Byte=\"3\" Bit=\"4\"><Desc>Decimal point (1=fract, 0=whole)</Desc></Res><Res Name=\"FM_fiscalized\" Value=\"\" Type=\"Status\" Byte=\"3\" Bit=\"5\"><Desc>FM fiscalized</Desc></Res><Res Name=\"FM_produced\" Value=\"\" Type=\"Status\" Byte=\"3\" Bit=\"6\"><Desc>FM produced</Desc></Res><Res Name=\"Printer_automatic_cutting\" Value=\"\" Type=\"Status\" Byte=\"4\" Bit=\"0\"><Desc>Printer: automatic cutting</Desc></Res><Res Name=\"External_display_transparent_display\" Value=\"\" Type=\"Status\" Byte=\"4\" Bit=\"1\"><Desc>External display: transparent display</Desc></Res><Res Name=\"Speed_is_9600\" Value=\"\" Type=\"Status\" Byte=\"4\" Bit=\"2\"><Desc>Speed is 9600</Desc></Res><Res Name=\"Drawer_automatic_opening\" Value=\"\" Type=\"Status\" Byte=\"4\" Bit=\"4\"><Desc>Drawer: automatic opening</Desc></Res><Res Name=\"Customer_logo_included_in_the_receipt\" Value=\"\" Type=\"Status\" Byte=\"4\" Bit=\"5\"><Desc>Customer logo included in the receipt</Desc></Res><Res Name=\"Wrong_SIM_card\" Value=\"\" Type=\"Status\" Byte=\"5\" Bit=\"0\"><Desc>Wrong SIM card</Desc></Res><Res Name=\"Blocking_3_days_without_mobile_operator\" Value=\"\" Type=\"Status\" Byte=\"5\" Bit=\"1\"><Desc>Blocking 3 days without mobile operator</Desc></Res><Res Name=\"No_task_from_NRA\" Value=\"\" Type=\"Status\" Byte=\"5\" Bit=\"2\"><Desc>No task from NRA</Desc></Res><Res Name=\"Wrong_SD_card\" Value=\"\" Type=\"Status\" Byte=\"5\" Bit=\"5\"><Desc>Wrong SD card</Desc></Res><Res Name=\"Deregistered\" Value=\"\" Type=\"Status\" Byte=\"5\" Bit=\"6\"><Desc>Deregistered</Desc></Res><Res Name=\"No_SIM_card\" Value=\"\" Type=\"Status\" Byte=\"6\" Bit=\"0\"><Desc>No SIM card</Desc></Res><Res Name=\"No_GPRS_Modem\" Value=\"\" Type=\"Status\" Byte=\"6\" Bit=\"1\"><Desc>No GPRS Modem</Desc></Res><Res Name=\"No_mobile_operator\" Value=\"\" Type=\"Status\" Byte=\"6\" Bit=\"2\"><Desc>No mobile operator</Desc></Res><Res Name=\"No_GPRS_service\" Value=\"\" Type=\"Status\" Byte=\"6\" Bit=\"3\"><Desc>No GPRS service</Desc></Res><Res Name=\"Near_end_of_paper\" Value=\"\" Type=\"Status\" Byte=\"6\" Bit=\"4\"><Desc>Near end of paper</Desc></Res><Res Name=\"Unsent_data_for_24_hours\" Value=\"\" Type=\"Status\" Byte=\"6\" Bit=\"5\"><Desc>Unsent data for 24 hours</Desc></Res><ResFormatRaw><![CDATA[<StatusBytes[7]>]]></ResFormatRaw></Response></Command><Command Name=\"OpenReceipt\" CmdByte=\"0x30\"><FPOperation>Opens a fiscal receipt assigned to the specified operator number and operator password, parameters for receipt format, print VAT, printing type and unique receipt number.</FPOperation><Args><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Arg><Arg Name=\"OperPass\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for operator's password</Desc></Arg><Arg Name=\"OptionReceiptFormat\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Brief\" Value=\"0\" /><Option Name=\"Detailed\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '1' - Detailed  - '0' - Brief</Desc></Arg><Arg Name=\"OptionPrintVAT\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '1' - Yes  - '0' - No</Desc></Arg><Arg Name=\"OptionFiscalRcpPrintType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Buffered printing\" Value=\"4\" /><Option Name=\"Postponed printing\" Value=\"2\" /><Option Name=\"Step by step printing\" Value=\"0\" /></Options><Desc>1 symbol with value: - '0' - Step by step printing - '2' - Postponed printing - '4' - Buffered printing</Desc></Arg><Arg Name=\"UniqueReceiptNumber\" Value=\"\" Type=\"Text\" MaxLen=\"24\"><Desc>Up to 24 symbols for unique receipt number. NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, * YYYYYYY - 7 symbols [0-9] for next number of the receipt</Desc><Meta MinLen=\"24\" Compulsory=\"false\" ValIndicatingPresence=\"$\" /></Arg><ArgsFormatRaw><![CDATA[<OperNum[1..2]> <;> <OperPass[6]> <;> <ReceiptFormat[1]> <;> <PrintVAT[1]> <;> <FiscalRcpPrintType[1]> {<'$'> <UniqueReceiptNumber[24]>} ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadEJByZBlocksCustom\" CmdByte=\"0x7C\"><FPOperation>Read or Store Electronic Journal Report by number of Z report blocks, CSV format option and document content. If CSV format is set the content can includes only fiscal receipts. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.</FPOperation><Args><Arg Name=\"OptionStorageReport\" Value=\"\" Type=\"Option\" MaxLen=\"2\"><Options><Option Name=\"To PC\" Value=\"j0\" /><Option Name=\"To SD card\" Value=\"j4\" /><Option Name=\"To USB Flash Drive\" Value=\"j2\" /></Options><Desc>1 character with value  - 'j0' - To PC  - 'j2' - To USB Flash Drive  - 'j4' - To SD card</Desc></Arg><Arg Name=\"OptionCSVformat\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"X\" /><Option Name=\"Yes\" Value=\"C\" /></Options><Desc>1 symbol with value:  - 'C' - Yes  - 'X' - No</Desc></Arg><Arg Name=\"FlagsReceipts\" Value=\"\" Type=\"Flags\" MaxLen=\"1\"><Desc>1 symbol for Receipts included in EJ: Flags.7=0 Flags.6=1 Flags.5=1 Yes, Flags.5=0 No (Include PO) Flags.4=1 Yes, Flags.4=0 No (Include RA) Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) Flags.1=1 Yes, Flags.1=0 No (Include Invoice) Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)</Desc></Arg><Arg Name=\"FlagsReports\" Value=\"\" Type=\"Flags\" MaxLen=\"1\"><Desc>1 symbol for Reports included in EJ: Flags.7=0 Flags.6=1 Flags.5=0 Flags.4=1 Yes, Flags.4=0 No (Include FM reports) Flags.3=1 Yes, Flags.3=0 No (Include Other reports) Flags.2=1 Yes, Flags.2=0 No (Include Daily X) Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)</Desc></Arg><Arg Name=\"\" Value=\"Z\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"StartZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for initial number report in format ####</Desc></Arg><Arg Name=\"EndZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for final number report in format ####</Desc></Arg><ArgsFormatRaw><![CDATA[ <StorageReport[2]> <;> <CSVformat[1]> <;> <FlagsReceipts[1]> <;> <FlagsReports[1]> <;> <'Z'> <;> <StartZNum[4]> <;> <EndZNum[4]> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"SetDateTime\" CmdByte=\"0x48\"><FPOperation>Sets the date and time and prints out the current values.</FPOperation><Args><Arg Name=\"DateTime\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"dd-MM-yy HH:mm:ss\"><Desc>Date Time parameter in format: DD-MM-YY HH:MM:SS</Desc></Arg><ArgsFormatRaw><![CDATA[ <DateTime \"DD-MM-YY HH:MM:SS\"> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ProgDecimalPointPosition\" CmdByte=\"0x43\"><FPOperation>Stores a block containing the number format into the fiscal memory. Print the current status on the printer.</FPOperation><Args><Arg Name=\"Password\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6-symbols string</Desc></Arg><Arg Name=\"OptionDecimalPointPosition\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Fractions\" Value=\"2\" /><Option Name=\"Whole numbers\" Value=\"0\" /></Options><Desc>1 symbol with values:  - '0'- Whole numbers  - '2' - Fractions</Desc></Arg><ArgsFormatRaw><![CDATA[ <Password[6]> <;> <DecimalPointPosition[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadLastDailySignature\" CmdByte=\"0x6E\"><FPOperation>Provides information about electronic signature of last daily report.</FPOperation><Args><Arg Name=\"\" Value=\"9\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'9'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"9\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"LastDailyReportSignature\" Value=\"\" Type=\"Text\" MaxLen=\"40\"><Desc>40 symbols electronic signature</Desc></Res><ResFormatRaw><![CDATA[<'9'> <;> <LastDailyReportSignature[40]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadDailyRA_Old\" CmdByte=\"0x6E\"><FPOperation>Provides information about the RA amounts by type of payment and the total number of operations. Command works for KL version 2 devices.</FPOperation><Args><Arg Name=\"\" Value=\"2\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'2'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"2\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"AmountPayment0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name=\"AmountPayment1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name=\"AmountPayment2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name=\"AmountPayment3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name=\"AmountPayment4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><Res Name=\"RANum\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for the total number of operations</Desc></Res><Res Name=\"SumAllPayment\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols to sum all payments</Desc></Res><ResFormatRaw><![CDATA[<'2'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]> <;> <RANum[1..5]> <;> <SumAllPayment[1..13]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadHeaderUICPrefix\" CmdByte=\"0x69\"><FPOperation>Provides the content of the header UIC prefix.</FPOperation><Args><Arg Name=\"\" Value=\"9\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'9'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"9\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"HeaderUICprefix\" Value=\"\" Type=\"Text\" MaxLen=\"12\"><Desc>12 symbols for Header UIC prefix</Desc></Res><ResFormatRaw><![CDATA[<'9'> <;> <HeaderUICprefix[12]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadPLUprice\" CmdByte=\"0x6B\"><FPOperation>Provides information about the price and price type of the specified article.</FPOperation><Args><Arg Name=\"PLUNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"5\" Format=\"00000\"><Desc>5 symbols for article number with leading zeroes in format: #####</Desc></Arg><Arg Name=\"Option\" Value=\"4\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Option['4']> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"PLUNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"5\" Format=\"00000\"><Desc>5 symbols for article number with leading zeroes in format #####</Desc></Res><Res Name=\"Option\" Value=\"4\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"Price\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for article price</Desc></Res><Res Name=\"OptionPrice\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Free price is disable valid only programmed price\" Value=\"0\" /><Option Name=\"Free price is enable\" Value=\"1\" /><Option Name=\"Limited price\" Value=\"2\" /></Options><Desc>1 symbol for price flag with next value:  - '0'- Free price is disable valid only programmed price  - '1'- Free price is enable  - '2'- Limited price</Desc></Res><ResFormatRaw><![CDATA[<PLUNum[5]> <;> <Option['4']> <;> <Price[1..10]> <;> <OptionPrice[1]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadOperatorNamePassword\" CmdByte=\"0x6A\"><FPOperation>Provides information about operator's name and password.</FPOperation><Args><Arg Name=\"Number\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbol from 1 to 20 corresponding to the number of operators.</Desc></Arg><ArgsFormatRaw><![CDATA[ <Number[1..2]> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"Number\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbol from 1 to 20 corresponding to the number of operator</Desc></Res><Res Name=\"Name\" Value=\"\" Type=\"Text\" MaxLen=\"20\"><Desc>20 symbols for operator's name</Desc></Res><Res Name=\"Password\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for operator's password</Desc></Res><ResFormatRaw><![CDATA[<Number[1..2]> <;> <Name[20]> <;> <Password[6]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadDailyCountersByOperator\" CmdByte=\"0x6F\"><FPOperation>Read the last operator's report number and date and time.</FPOperation><Args><Arg Name=\"\" Value=\"5\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Arg><ArgsFormatRaw><![CDATA[ <'5'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"5\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Res><Res Name=\"WorkOperatorsCounter\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for number of the work operators</Desc></Res><Res Name=\"LastOperatorReportDateTime\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"dd-MM-yyyy HH:mm\"><Desc>16 symbols for date and time of the last operator's report in format DD-MM-YYYY HH:MM</Desc></Res><ResFormatRaw><![CDATA[<'5'> <;> <OperNum[1..2]> <;> <WorkOperatorsCounter[1..5]> <;> <LastOperatorReportDateTime \"DD-MM-YYYY HH:MM\">]]></ResFormatRaw></Response></Command><Command Name=\"ReadLastDailyReportAvailableAmounts\" CmdByte=\"0x6E\"><FPOperation>Provides information about daily available amounts in cash and currency, Z daily report type and Z daily report number</FPOperation><Args><Arg Name=\"\" Value=\"Z\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'Z'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"Z\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OptionZReportType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Automatic\" Value=\"1\" /><Option Name=\"Manual\" Value=\"0\" /></Options><Desc>1 symbol with value:  - '0' - Manual  - '1' - Automatic ZReportNum 4 symbols for Z report number in format ####</Desc></Res><Res Name=\"ZreportNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for Z report number in format ####</Desc></Res><Res Name=\"CashAvailableAmount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for available amounts in cash payment</Desc></Res><Res Name=\"CurrencyAvailableAmount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for available amounts in currency payment</Desc></Res><ResFormatRaw><![CDATA[<'Z'> <;> <ZReportType[1]> <;> <ZreportNum[4]> <;> <CashAvailableAmount[1..13]> <;> <CurrencyAvailableAmount[1..13]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadPayments\" CmdByte=\"0x64\"><FPOperation>Provides information about all programmed types of payment, currency name and currency exchange rate.</FPOperation><Response ACK=\"false\"><Res Name=\"NamePayment0\" Value=\"\" Type=\"Text\" MaxLen=\"10\"><Desc>10 symbols for payment name type 0</Desc></Res><Res Name=\"NamePayment1\" Value=\"\" Type=\"Text\" MaxLen=\"10\"><Desc>10 symbols for payment name type 1</Desc></Res><Res Name=\"NamePayment2\" Value=\"\" Type=\"Text\" MaxLen=\"10\"><Desc>10 symbols for payment name type 2</Desc></Res><Res Name=\"NamePayment3\" Value=\"\" Type=\"Text\" MaxLen=\"10\"><Desc>10 symbols for payment name type 3</Desc></Res><Res Name=\"NamePayment4\" Value=\"\" Type=\"Text\" MaxLen=\"10\"><Desc>10 symbols for payment name type 4</Desc></Res><Res Name=\"NamePayment5\" Value=\"\" Type=\"Text\" MaxLen=\"10\"><Desc>10 symbols for payment name type 5</Desc></Res><Res Name=\"NamePayment6\" Value=\"\" Type=\"Text\" MaxLen=\"10\"><Desc>10 symbols for payment name type 6</Desc></Res><Res Name=\"NamePayment7\" Value=\"\" Type=\"Text\" MaxLen=\"10\"><Desc>10 symbols for payment name type 7</Desc></Res><Res Name=\"NamePayment8\" Value=\"\" Type=\"Text\" MaxLen=\"10\"><Desc>10 symbols for payment name type 8</Desc></Res><Res Name=\"NamePayment9\" Value=\"\" Type=\"Text\" MaxLen=\"10\"><Desc>10 symbols for payment name type 9</Desc></Res><Res Name=\"NamePayment10\" Value=\"\" Type=\"Text\" MaxLen=\"10\"><Desc>10 symbols for payment name type 10</Desc></Res><Res Name=\"NamePayment11\" Value=\"\" Type=\"Text\" MaxLen=\"10\"><Desc>10 symbols for payment name type 11</Desc></Res><Res Name=\"ExchangeRate\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"10\" Format=\"0000.00000\"><Desc>Up to 10 symbols for exchange rate of payment type 11 in format: ####.#####</Desc></Res><ResFormatRaw><![CDATA[<NamePayment0[10]> <;> <NamePayment1[10]> <;> <NamePayment2[10]> <;> <NamePayment3[10]> <;> <NamePayment4[10]> <;> <NamePayment5[10]> <;> <NamePayment6[10]> <;> <NamePayment7[10]> <;> <NamePayment8[10]> <;> <NamePayment9[10]> <;> <NamePayment10[10]> <;> <NamePayment11[10]> <;> <ExchangeRate[1..10]>]]></ResFormatRaw></Response></Command><Command Name=\"SellPLUwithSpecifiedVATfromDep\" CmdByte=\"0x31\"><FPOperation>Register the sell (for correction use minus sign in the price field) of article with specified VAT. If department is present the relevant accumulations are perfomed in its registers.</FPOperation><Args><Arg Name=\"NamePLU\" Value=\"\" Type=\"Text\" MaxLen=\"36\"><Desc>36 symbols for article's name. 34 symbols are printed on paper. Symbol 0x7C '|' is new line separator.</Desc></Arg><Arg Name=\"OptionVATClass\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Forbidden\" Value=\"*\" /><Option Name=\"VAT Class 0\" Value=\"А\" /><Option Name=\"VAT Class 1\" Value=\"Б\" /><Option Name=\"VAT Class 2\" Value=\"В\" /><Option Name=\"VAT Class 3\" Value=\"Г\" /><Option Name=\"VAT Class 4\" Value=\"Д\" /><Option Name=\"VAT Class 5\" Value=\"Е\" /><Option Name=\"VAT Class 6\" Value=\"Ж\" /><Option Name=\"VAT Class 7\" Value=\"З\" /></Options><Desc>1 character for VAT class:  - 'А' - VAT Class 0  - 'Б' - VAT Class 1  - 'В' - VAT Class 2  - 'Г' - VAT Class 3  - 'Д' - VAT Class 4  - 'Е' - VAT Class 5  - 'Ж' - VAT Class 6  - 'З' - VAT Class 7  - '*' - Forbidden</Desc></Arg><Arg Name=\"Price\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for article's price. Use minus sign '-' for correction</Desc></Arg><Arg Name=\"Quantity\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for quantity</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\"*\" /></Arg><Arg Name=\"DiscAddP\" Value=\"\" Type=\"Decimal\" MaxLen=\"7\"><Desc>Up to 7 symbols for percentage of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\",\" /></Arg><Arg Name=\"DiscAddV\" Value=\"\" Type=\"Decimal\" MaxLen=\"8\"><Desc>Up to 8 symbols for value of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\":\" /></Arg><Arg Name=\"DepNum\" Value=\"\" Type=\"Decimal_plus_80h\" MaxLen=\"2\"><Desc>1 symbol for article department attachment, formed in the following manner; example: Dep01 = 81h,  Dep02 = 82h … Dep19 = 93h Department range from 1 to 127</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\"!\" /></Arg><ArgsFormatRaw><![CDATA[ <NamePLU[36]> <;> <OptionVATClass[1]> <;> <Price[1..10]> {<'*'> <Quantity[1..10]>} {<','> <DiscAddP[1..7]>} {<':'> <DiscAddV[1..8]>} {<'!'> <DepNum[1]>} ]]></ArgsFormatRaw></Args></Command><Command Name=\"ProgPayment\" CmdByte=\"0x44\"><FPOperation>Preprogram the name of the payment type.</FPOperation><Args><Arg Name=\"OptionPaymentNum\" Value=\"\" Type=\"Option\" MaxLen=\"2\"><Options><Option Name=\"Payment 10\" Value=\"10\" /><Option Name=\"Payment 11\" Value=\"11\" /><Option Name=\"Payment 9\" Value=\"9\" /></Options><Desc>1 symbol for payment type  - '9' - Payment 9  - '10' - Payment 10  - '11' - Payment 11</Desc></Arg><Arg Name=\"Name\" Value=\"\" Type=\"Text\" MaxLen=\"10\"><Desc>10 symbols for payment type name</Desc></Arg><Arg Name=\"Rate\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"10\" Format=\"0000.00000\"><Desc>Up to 10 symbols for exchange rate in format: ####.#####  of the 11th payment type, maximal value 0420.00000</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\";\" /></Arg><ArgsFormatRaw><![CDATA[ <PaymentNum[1..2]> <;> <Name[10]> { <;> <Rate[1..10]> } ]]></ArgsFormatRaw></Args></Command><Command Name=\"PrintDiagnostics\" CmdByte=\"0x22\"><FPOperation>Prints out a diagnostic receipt.</FPOperation></Command><Command Name=\"ReadDetailedPrinterStatus\" CmdByte=\"0x66\"><FPOperation>Provides additional status information</FPOperation><Response ACK=\"false\"><Res Name=\"OptionExternalDisplay\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"N\" /><Option Name=\"Yes\" Value=\"Y\" /></Options><Desc>1 symbol - connection with external display  - 'Y' - Yes  - 'N' - No</Desc></Res><Res Name=\"StatPRN\" Value=\"\" Type=\"Text\" MaxLen=\"4\"><Desc>4 symbols for detailed status of printer (only for printers with ASB) N byte N bit status flag ST0 0 Reserved 1 Reserved 2 Signal level for drawer 3 Printer not ready 4 Reserved 5 Open cover 6 Paper feed status 7 Reserved   ST1 0 Reserved 1 Reserved 2 Reserved 3 Cutter error 4 Reserved 5 Fatal error</Desc></Res><Res Name=\"FlagServiceJumper\" Value=\"\" Type=\"Flags\" MaxLen=\"1\"><Desc>1 symbol with value:  - 'J' - Yes  - ' ' - No</Desc></Res><ResFormatRaw><![CDATA[<ExternalDisplay[1]> <;> <StatPRN[4]> <;> <FlagServiceJumper[1]>]]></ResFormatRaw></Response></Command><Command Name=\"OpenInvoiceWithFreeCustomerData\" CmdByte=\"0x30\"><FPOperation>Opens a fiscal invoice receipt assigned to the specified operator number and operator password with free info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.</FPOperation><Args><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbol from 1 to 20 corresponding to operator's number</Desc></Arg><Arg Name=\"OperPass\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for operator's password</Desc></Arg><Arg Name=\"reserved\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"reserved\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionInvoicePrintType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Buffered Printing\" Value=\"5\" /><Option Name=\"Postponed Printing\" Value=\"3\" /><Option Name=\"Step by step printing\" Value=\"1\" /></Options><Desc>1 symbol with value: - '1' - Step by step printing - '3' - Postponed Printing - '5' - Buffered Printing</Desc></Arg><Arg Name=\"Recipient\" Value=\"\" Type=\"Text\" MaxLen=\"26\"><Desc>26 symbols for Invoice recipient</Desc></Arg><Arg Name=\"Buyer\" Value=\"\" Type=\"Text\" MaxLen=\"16\"><Desc>16 symbols for Invoice buyer</Desc></Arg><Arg Name=\"VATNumber\" Value=\"\" Type=\"Text\" MaxLen=\"13\"><Desc>13 symbols for customer Fiscal number</Desc></Arg><Arg Name=\"UIC\" Value=\"\" Type=\"Text\" MaxLen=\"13\"><Desc>13 symbols for customer Unique Identification Code</Desc></Arg><Arg Name=\"Address\" Value=\"\" Type=\"Text\" MaxLen=\"30\"><Desc>30 symbols for Address</Desc></Arg><Arg Name=\"OptionUICType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Bulstat\" Value=\"0\" /><Option Name=\"EGN\" Value=\"1\" /><Option Name=\"Foreigner Number\" Value=\"2\" /><Option Name=\"NRA Official Number\" Value=\"3\" /></Options><Desc>1 symbol for type of Unique Identification Code:  - '0' - Bulstat  - '1' - EGN  - '2' - Foreigner Number  - '3' - NRA Official Number</Desc></Arg><Arg Name=\"UniqueReceiptNumber\" Value=\"\" Type=\"Text\" MaxLen=\"24\"><Desc>Up to 24 symbols for unique receipt number. NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, * YYYYYYY - 7 symbols [0-9] for next number of the receipt</Desc><Meta MinLen=\"24\" Compulsory=\"false\" ValIndicatingPresence=\"$\" /></Arg><ArgsFormatRaw><![CDATA[ <OperNum[1..2]> <;> <OperPass[6]> <;> <reserved['0']> <;> <reserved['0']> <;> <InvoicePrintType[1]> <;> <Recipient[26]> <;> <Buyer[16]> <;> <VATNumber[13]> <;> <UIC[13]> <;> <Address[30]> <;> <UICType[1]> { <'$'> <UniqueReceiptNumber[24]>} ]]></ArgsFormatRaw></Args></Command><Command Name=\"ProgFooter\" CmdByte=\"0x49\"><FPOperation>Program the contents of a footer lines.</FPOperation><Args><Arg Name=\"\" Value=\"8\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"FooterText\" Value=\"\" Type=\"Text\" MaxLen=\"64\"><Desc>TextLength symbols for footer line</Desc></Arg><ArgsFormatRaw><![CDATA[<'8'> <;> <FooterText[TextLength]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"PrintLastReceiptDuplicate\" CmdByte=\"0x3A\"><FPOperation>Print a copy of the last receipt issued. When FD parameter for duplicates is enabled.</FPOperation></Command><Command Name=\"ReadTCP_Password\" CmdByte=\"0x4E\"><FPOperation>Provides information about device's TCP password.</FPOperation><Args><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"Z\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"1\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'R'><;><'Z'><;><'1'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"Z\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"1\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"PassLength\" Value=\"\" Type=\"Decimal\" MaxLen=\"3\"><Desc>(Length) Up to 3 symbols for the password length</Desc></Res><Res Name=\"Password\" Value=\"\" Type=\"Text\" MaxLen=\"100\"><Desc>Up to 100 symbols for the TCP password</Desc></Res><ResFormatRaw><![CDATA[<'R'><;><'Z'><;><'1'><;><PassLength[1..3]><;><Password[100]>]]></ResFormatRaw></Response></Command><Command Name=\"ProgVATrates\" CmdByte=\"0x42\"><FPOperation>Stores a block containing the values of the VAT rates into the fiscal memory. Print the values on the printer.</FPOperation><Args><Arg Name=\"Password\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6-symbols string</Desc></Arg><Arg Name=\"VATrate0\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"00.00\"><Desc>Value of VAT rate А from 6 symbols in format ##.##</Desc></Arg><Arg Name=\"VATrate1\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"00.00\"><Desc>Value of VAT rate Б from 6 symbols in format ##.##</Desc></Arg><Arg Name=\"VATrate2\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"00.00\"><Desc>Value of VAT rate В from 6 symbols in format ##.##</Desc></Arg><Arg Name=\"VATrate3\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"00.00\"><Desc>Value of VAT rate Г from 6 symbols in format ##.##</Desc></Arg><Arg Name=\"VATrate4\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"00.00\"><Desc>Value of VAT rate Д from 6 symbols in format ##.##</Desc></Arg><Arg Name=\"VATrate5\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"00.00\"><Desc>Value of VAT rate Е from 6 symbols in format ##.##</Desc></Arg><Arg Name=\"VATrate6\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"00.00\"><Desc>Value of VAT rate Ж from 6 symbols in format ##.##</Desc></Arg><Arg Name=\"VATrate7\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"00.00\"><Desc>Value of VAT rate З from 6 symbols in format ##.##</Desc></Arg><ArgsFormatRaw><![CDATA[ <Password[6]> <;> <VATrate0[6]> <;> <VATrate1[6]> <;> <VATrate2[6]> <;> <VATrate3[6]> <;> <VATrate4[6]><;> <VATrate5[6]><;> <VATrate6[6]> <;> <VATrate7[6]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadEJByDateCustom\" CmdByte=\"0x7C\"><FPOperation>Read or Store Electronic Journal Report by initial to end date, CSV format option and document content. If CSV format is set the content can includes only fiscal receipts. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.</FPOperation><Args><Arg Name=\"OptionStorageReport\" Value=\"\" Type=\"Option\" MaxLen=\"2\"><Options><Option Name=\"To PC\" Value=\"j0\" /><Option Name=\"To SD card\" Value=\"j4\" /><Option Name=\"To USB Flash Drive\" Value=\"j2\" /></Options><Desc>2 characters with value:  - 'j0' - To PC  - 'j2' - To USB Flash Drive  - 'j4' - To SD card</Desc></Arg><Arg Name=\"OptionCSVformat\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"X\" /><Option Name=\"Yes\" Value=\"C\" /></Options><Desc>1 symbol with value:  - 'C' - Yes  - 'X' - No</Desc></Arg><Arg Name=\"FlagsReceipts\" Value=\"\" Type=\"Flags\" MaxLen=\"1\"><Desc>1 symbol for Receipts included in EJ: Flags.7=0 Flags.6=1, 0=w Flags.5=1 Yes, Flags.5=0 No (Include PO) Flags.4=1 Yes, Flags.4=0 No (Include RA) Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) Flags.1=1 Yes, Flags.1=0 No (Include Invoice) Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)</Desc></Arg><Arg Name=\"FlagsReports\" Value=\"\" Type=\"Flags\" MaxLen=\"1\"><Desc>1 symbol for Reports included in EJ: Flags.7=0 Flags.6=1, 0=w Flags.5=1, 0=w Flags.4=1 Yes, Flags.4=0 No (Include FM reports) Flags.3=1 Yes, Flags.3=0 No (Include Other reports) Flags.2=1 Yes, Flags.2=0 No (Include Daily X) Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)</Desc></Arg><Arg Name=\"\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"StartRepFromDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name=\"EndRepFromDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><ArgsFormatRaw><![CDATA[ <StorageReport[2]> <;> <CSVformat[1]> <;> <FlagsReceipts[1]> <;> <FlagsReports[1]> <;> <'D'> <;> <StartRepFromDate \"DDMMYY\"> <;> <EndRepFromDate \"DDMMYY\"> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"SetBluetooth_Status\" CmdByte=\"0x4E\"><FPOperation>Program device's Bluetooth module to be enabled or disabled. To apply use -SaveNetworkSettings()</FPOperation><Args><Arg Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"B\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"S\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionBTstatus\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Disabled\" Value=\"0\" /><Option Name=\"Enabled\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '0' - Disabled  - '1' - Enabled</Desc></Arg><ArgsFormatRaw><![CDATA[ <'P'><;><'B'><;><'S'><;><BTstatus[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"SellPLUwithSpecifiedVATfor200DepRangeDevice\" CmdByte=\"0x3C\"><FPOperation>Register the sell (for correction use minus sign in the price field) of article with specified VAT. If department is present the relevant accumulations are perfomed in its registers.</FPOperation><Args><Arg Name=\"NamePLU\" Value=\"\" Type=\"Text\" MaxLen=\"36\"><Desc>36 symbols for article's name. 34 symbols are printed on paper. Symbol 0x7C '|' is new line separator.</Desc></Arg><Arg Name=\"OptionVATClass\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Forbidden\" Value=\"*\" /><Option Name=\"VAT Class 0\" Value=\"А\" /><Option Name=\"VAT Class 1\" Value=\"Б\" /><Option Name=\"VAT Class 2\" Value=\"В\" /><Option Name=\"VAT Class 3\" Value=\"Г\" /><Option Name=\"VAT Class 4\" Value=\"Д\" /><Option Name=\"VAT Class 5\" Value=\"Е\" /><Option Name=\"VAT Class 6\" Value=\"Ж\" /><Option Name=\"VAT Class 7\" Value=\"З\" /></Options><Desc>1 character for VAT class:  - 'А' - VAT Class 0  - 'Б' - VAT Class 1  - 'В' - VAT Class 2  - 'Г' - VAT Class 3  - 'Д' - VAT Class 4  - 'Е' - VAT Class 5  - 'Ж' - VAT Class 6  - 'З' - VAT Class 7  - '*' - Forbidden</Desc></Arg><Arg Name=\"Price\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for article's price. Use minus sign '-' for correction</Desc></Arg><Arg Name=\"Quantity\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for quantity</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\"*\" /></Arg><Arg Name=\"DiscAddP\" Value=\"\" Type=\"Decimal\" MaxLen=\"7\"><Desc>Up to 7 symbols for percentage of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\",\" /></Arg><Arg Name=\"DiscAddV\" Value=\"\" Type=\"Decimal\" MaxLen=\"8\"><Desc>Up to 8 symbols for value of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\":\" /></Arg><Arg Name=\"DepNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"3\"><Desc>Up to 3 symbols for department number</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\"!\" /></Arg><ArgsFormatRaw><![CDATA[ <NamePLU[36]> <;> <OptionVATClass[1]> <;> <Price[1..10]> {<'*'> <Quantity[1..10]>} {<','> <DiscAddP[1..7]>} {<':'> <DiscAddV[1..8]>} {<'!'> <DepNum[1..3]>} ]]></ArgsFormatRaw></Args></Command><Command Name=\"PrintBriefFMReportByZBlocks\" CmdByte=\"0x79\"><FPOperation>Print a brief FM report by initial and end FM report number.</FPOperation><Args><Arg Name=\"StartZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for the initial FM report number included in report, format ####</Desc></Arg><Arg Name=\"EndZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for the final FM report number included in report, format ####</Desc></Arg><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"SetFiscalDeviceType\" CmdByte=\"0x56\"><FPOperation>Define Fiscal device type. The command is allowed only in non- fiscal mode, before fiscalization and after deregistration before the next fiscalization. The type of device can be read by Version command 0x21.</FPOperation><Args><Arg Name=\"\" Value=\"T\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionFDType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"ECR for online store type 11\" Value=\"2\" /><Option Name=\"FPr for Fuel type 3\" Value=\"0\" /><Option Name=\"FPr for online store type 21\" Value=\"3\" /><Option Name=\"Main FPr for Fuel system type 31\" Value=\"1\" /><Option Name=\"reset default type\" Value=\"*\" /></Options><Desc>1 symbol for fiscal device type with value:  - '0' - FPr for Fuel type 3  - '1' - Main FPr for Fuel system type 31  - '2' - ECR for online store type 11  - '3' - FPr for online store type 21  - '*' - reset default type</Desc></Arg><Arg Name=\"Password\" Value=\"\" Type=\"Text\" MaxLen=\"3\"><Desc>3-symbols string</Desc></Arg><ArgsFormatRaw><![CDATA[ <'T'> <;> <FDType[1]> <;> <Password[3]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadEJByDate\" CmdByte=\"0x7C\"><FPOperation>Read Electronic Journal Report by initial to end date.</FPOperation><Args><Arg Name=\"OptionReportFormat\" Value=\"\" Type=\"Option\" MaxLen=\"2\"><Options><Option Name=\"Brief EJ\" Value=\"J8\" /><Option Name=\"Detailed EJ\" Value=\"J0\" /></Options><Desc>1 character with value  - 'J0' - Detailed EJ  - 'J8' - Brief EJ</Desc></Arg><Arg Name=\"\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"StartRepFromDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name=\"EndRepFromDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><ArgsFormatRaw><![CDATA[ <ReportFormat[2]> <;> <'D'> <;> <StartRepFromDate \"DDMMYY\"> <;> <EndRepFromDate \"DDMMYY\"> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"ReadPLUbarcode\" CmdByte=\"0x6B\"><FPOperation>Provides information about the barcode of the specified article.</FPOperation><Args><Arg Name=\"PLUNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"5\" Format=\"00000\"><Desc>5 symbols for article number with leading zeroes in format: #####</Desc></Arg><Arg Name=\"Option\" Value=\"3\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <PLUNum[5]> <;> <Option['3']> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"PLUNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"5\" Format=\"00000\"><Desc>5 symbols for article number with leading zeroes in format #####</Desc></Res><Res Name=\"Option\" Value=\"3\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"Barcode\" Value=\"\" Type=\"Text\" MaxLen=\"13\"><Desc>13 symbols for article barcode</Desc></Res><ResFormatRaw><![CDATA[<PLUNum[5]> <;> <Option['3']> <;> <Barcode[13]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadDailyPObyOperator_Old\" CmdByte=\"0x6F\"><FPOperation>Read the PO by type of payment and the total number of operations by specified operator. Command works for KL version 2 devices.</FPOperation><Args><Arg Name=\"\" Value=\"3\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Arg><ArgsFormatRaw><![CDATA[ <'3'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"3\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Res><Res Name=\"AmountPO_Payment0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the PO by type of payment 0</Desc></Res><Res Name=\"AmountPO_Payment1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the PO by type of payment 1</Desc></Res><Res Name=\"AmountPO_Payment2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the PO by type of payment 2</Desc></Res><Res Name=\"AmountPO_Payment3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the PO by type of payment 3</Desc></Res><Res Name=\"AmountPO_Payment4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the PO by type of payment 4</Desc></Res><Res Name=\"NoPO\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for the total number of operations</Desc></Res><ResFormatRaw><![CDATA[<'3'> <;> <OperNum[1..2]> <;> <AmountPO_Payment0[1..13]> <;> <AmountPO_Payment1[1..13]> <;> <AmountPO_Payment2[1..13]> <;> <AmountPO_Payment3[1..13]> <;> <AmountPO_Payment4[1..13]> <;> <;><NoPO[1..5]>]]></ResFormatRaw></Response></Command><Command Name=\"ProgDepartment\" CmdByte=\"0x47\"><FPOperation>Set data for the state department number from the internal FD database. Parameters Price, OptionDepPrice and AdditionalName are not obligatory and require the previous not obligatory parameter.</FPOperation><Args><Arg Name=\"Number\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"3\" Format=\"000\"><Desc>3 symbols for department number in format ###</Desc></Arg><Arg Name=\"Name\" Value=\"\" Type=\"Text\" MaxLen=\"20\"><Desc>20 characters department name</Desc></Arg><Arg Name=\"OptionVATClass\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Forbidden\" Value=\"*\" /><Option Name=\"VAT Class 0\" Value=\"А\" /><Option Name=\"VAT Class 1\" Value=\"Б\" /><Option Name=\"VAT Class 2\" Value=\"В\" /><Option Name=\"VAT Class 3\" Value=\"Г\" /><Option Name=\"VAT Class 4\" Value=\"Д\" /><Option Name=\"VAT Class 5\" Value=\"Е\" /><Option Name=\"VAT Class 6\" Value=\"Ж\" /><Option Name=\"VAT Class 7\" Value=\"З\" /></Options><Desc>1 character for VAT class:  - 'А' - VAT Class 0  - 'Б' - VAT Class 1  - 'В' - VAT Class 2  - 'Г' - VAT Class 3  - 'Д' - VAT Class 4  - 'Е' - VAT Class 5  - 'Ж' - VAT Class 6  - 'З' - VAT Class 7  - '*' - Forbidden</Desc></Arg><Arg Name=\"Price\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for department price</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\";\" /></Arg><Arg Name=\"OptionDepPrice\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Free price disabled\" Value=\"0\" /><Option Name=\"Free price disabled for single transaction\" Value=\"4\" /><Option Name=\"Free price enabled\" Value=\"1\" /><Option Name=\"Free price enabled for single transaction\" Value=\"5\" /><Option Name=\"Limited price\" Value=\"2\" /><Option Name=\"Limited price for single transaction\" Value=\"6\" /></Options><Desc>1 symbol for Department price flags with next value:  - '0' - Free price disabled  - '1' - Free price enabled  - '2' - Limited price  - '4' - Free price disabled for single transaction  - '5' - Free price enabled for single transaction  - '6' - Limited price for single transaction</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\";\" /></Arg><Arg Name=\"AdditionalName\" Value=\"\" Type=\"Text\" MaxLen=\"14\"><Desc>14 characters additional department name</Desc><Meta MinLen=\"14\" Compulsory=\"false\" ValIndicatingPresence=\";\" /></Arg><ArgsFormatRaw><![CDATA[ <Number[3..3]> <;> <Name[20]> <;> <OptionVATClass[1]> { <;> <Price[1..10]> <;> <OptionDepPrice[1]> <;> <AdditionalName[14]> } ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadDetailedFMPaymentsReportByDate\" CmdByte=\"0x7A\"><FPOperation>Read a detailed FM payments report by initial and end date.</FPOperation><Args><Arg Name=\"StartDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name=\"EndDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name=\"Option\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionReading\" Value=\"8\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartDate \"DDMMYY\"> <;> <EndDate \"DDMMYY\"> <;> <Option['P']> <;> <OptionReading['8']> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"SetIdle_Timeout\" CmdByte=\"0x4E\"><FPOperation>Sets device's idle timeout setting. Set timeout for closing the connection if there is an inactivity. Maximal value - 7200, minimal value 0. 0 is for never close the connection. This option can be used only if the device has LAN or WiFi. To apply use - SaveNetworkSettings()</FPOperation><Args><Arg Name=\"\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"Z\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"I\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"IdleTimeout\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for Idle timeout in format ####</Desc></Arg><ArgsFormatRaw><![CDATA[ <'P'><;><'Z'><;><'I'><;><IdleTimeout[4]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadTCP_AutoStartStatus\" CmdByte=\"0x4E\"><FPOperation>Read device TCP Auto Start status</FPOperation><Args><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"Z\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"2\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'R'><;><'Z'><;><'2'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"Z\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"2\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OptionTCPAutoStart\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol for TCP auto start status - '0' - No  - '1' - Yes</Desc></Res><ResFormatRaw><![CDATA[<'R'><;><'Z'><;><'2'><;><TCPAutoStart[1]>]]></ResFormatRaw></Response></Command><Command Name=\"PrintLogo\" CmdByte=\"0x6C\"><FPOperation>Prints the programmed graphical logo with the stated number.</FPOperation><Args><Arg Name=\"Number\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Number of logo to be printed. If missing, prints logo with number 0</Desc></Arg><ArgsFormatRaw><![CDATA[ <Number[1..2]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadDailyGeneralRegistersByOperator\" CmdByte=\"0x6F\"><FPOperation>Read the total number of customers, discounts, additions, corrections and accumulated amounts by specified operator.</FPOperation><Args><Arg Name=\"\" Value=\"1\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Arg><ArgsFormatRaw><![CDATA[ <'1'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"1\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Res><Res Name=\"CustomersNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for number of customers</Desc></Res><Res Name=\"DiscountsNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for number of discounts</Desc></Res><Res Name=\"DiscountsAmount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for accumulated amount of discounts</Desc></Res><Res Name=\"AdditionsNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for number ofadditions</Desc></Res><Res Name=\"AdditionsAmount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for accumulated amount of additions</Desc></Res><Res Name=\"CorrectionsNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for number of corrections</Desc></Res><Res Name=\"CorrectionsAmount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for accumulated amount of corrections</Desc></Res><ResFormatRaw><![CDATA[<'1'> <;> <OperNum[1..2]> <;> <CustomersNum[1..5]> <;> <DiscountsNum[1..5]> <;> <DiscountsAmount[1..13]> <;> <AdditionsNum[1..5]> <;> <AdditionsAmount[1..13]> <;> <CorrectionsNum[1..5]> <;> <CorrectionsAmount[1..13]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadBriefFMReportByZBlocks\" CmdByte=\"0x79\"><FPOperation>Read a brief FM report by initial and end FM report number.</FPOperation><Args><Arg Name=\"StartZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for the initial FM report number included in report, format ####</Desc></Arg><Arg Name=\"EndZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for the final FM report number included in report, format ####</Desc></Arg><Arg Name=\"\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionReading\" Value=\"8\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <'0'> <;> <OptionReading['8']> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"ReadDailyRAbyOperator_Old\" CmdByte=\"0x6F\"><FPOperation>Read the RA by type of payment and the total number of operations by specified operator. Command works for KL version 2 devices.</FPOperation><Args><Arg Name=\"\" Value=\"2\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Arg><ArgsFormatRaw><![CDATA[ <'2'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"2\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Res><Res Name=\"AmountRA_Payment0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the RA by type of payment 0</Desc></Res><Res Name=\"AmountRA_Payment1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the RA by type of payment 1</Desc></Res><Res Name=\"AmountRA_Payment2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the RA by type of payment 2</Desc></Res><Res Name=\"AmountRA_Payment3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the RA by type of payment 3</Desc></Res><Res Name=\"AmountRA_Payment4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the RA by type of payment 4</Desc></Res><Res Name=\"NoRA\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for the total number of operations</Desc></Res><ResFormatRaw><![CDATA[<'2'> <;> <OperNum[1..2]> <;> <AmountRA_Payment0[1..13]> <;> <AmountRA_Payment1[1..13]> <;> <AmountRA_Payment2[1..13]> <;> <AmountRA_Payment3[1..13]> <;> <AmountRA_Payment4[1..13]> <;> <;> <NoRA[1..5]>]]></ResFormatRaw></Response></Command><Command Name=\"PrintDetailedFMReportByZBlocks\" CmdByte=\"0x78\"><FPOperation>Print a detailed FM report by initial and end FM report number.</FPOperation><Args><Arg Name=\"StartZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for the initial report number included in report, format ####</Desc></Arg><Arg Name=\"EndZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for the final report number included in report, format ####</Desc></Arg><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"PrintCustomerReport\" CmdByte=\"0x52\"><FPOperation>Print Customer X or Z report</FPOperation><Args><Arg Name=\"OptionZeroing\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Without zeroing\" Value=\"X\" /><Option Name=\"Zeroing\" Value=\"Z\" /></Options><Desc>with following values:  - 'Z' -Zeroing  - 'X' - Without zeroing</Desc></Arg><ArgsFormatRaw><![CDATA[ <OptionZeroing[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"PrintDailyReport\" CmdByte=\"0x7C\"><FPOperation>Depending on the parameter prints: − daily fiscal report with zeroing and fiscal memory record, preceded by Electronic Journal report print ('Z'); − daily fiscal report without zeroing ('X');</FPOperation><Args><Arg Name=\"OptionZeroing\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Without zeroing\" Value=\"X\" /><Option Name=\"Zeroing\" Value=\"Z\" /></Options><Desc>1 character with following values:  - 'Z' - Zeroing  - 'X' - Without zeroing</Desc></Arg><ArgsFormatRaw><![CDATA[ <OptionZeroing[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadFooter\" CmdByte=\"0x69\"><FPOperation>Provides the content of the footer line.</FPOperation><Args><Arg Name=\"\" Value=\"8\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'8'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"8\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"FooterText\" Value=\"\" Type=\"Text\" MaxLen=\"64\"><Desc>TextLength symbols for footer line</Desc></Res><ResFormatRaw><![CDATA[<'8'> <;> <FooterText[TextLength]>]]></ResFormatRaw></Response></Command><Command Name=\"ZDailyReportNoPrint\" CmdByte=\"0x7C\"><FPOperation>Generate Z-daily report without printing</FPOperation><Args><Arg Name=\"\" Value=\"Z\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"n\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'Z'><;><'n'> ]]></ArgsFormatRaw></Args></Command><Command Name=\"OpenNonFiscalReceipt\" CmdByte=\"0x2E\"><FPOperation>Opens a non-fiscal receipt assigned to the specified operator number, operator password and print type.</FPOperation><Args><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Arg><Arg Name=\"OperPass\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for operator's password</Desc></Arg><Arg Name=\"Reserved\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\"><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\";\" /></Arg><Arg Name=\"OptionNonFiscalPrintType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Postponed Printing\" Value=\"1\" /><Option Name=\"Step by step printing\" Value=\"0\" /></Options><Desc>1 symbol with value: - '0' - Step by step printing - '1' - Postponed Printing</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\";\" /></Arg><ArgsFormatRaw><![CDATA[ <OperNum[1..2]> <;> <OperPass[6]> {<;> <Reserved['0']> <;> <NonFiscalPrintType[1]>} ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadBriefFMPaymentsReportByDate\" CmdByte=\"0x7B\"><FPOperation>Read a brief FM payments report by initial and end date.</FPOperation><Args><Arg Name=\"StartDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name=\"EndDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name=\"Option\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionReading\" Value=\"8\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartDate \"DDMMYY\"> <;> <EndDate \"DDMMYY\"> <;> <Option['P']> <;> <OptionReading['8']> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"Subtotal\" CmdByte=\"0x33\"><FPOperation>Calculate the subtotal amount with printing and display visualization options. Provide information about values of the calculated amounts. If a percent or value discount/addition has been specified the subtotal and the discount/addition value will be printed regardless the parameter for printing.</FPOperation><Args><Arg Name=\"OptionPrinting\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '1' - Yes  - '0' - No</Desc></Arg><Arg Name=\"OptionDisplay\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '1' - Yes  - '0' - No</Desc></Arg><Arg Name=\"DiscAddV\" Value=\"\" Type=\"Decimal\" MaxLen=\"8\"><Desc>Up to 8 symbols for the value of the discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\":\" /></Arg><Arg Name=\"DiscAddP\" Value=\"\" Type=\"Decimal\" MaxLen=\"7\"><Desc>Up to 7 symbols for the percentage value of the discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\",\" /></Arg><ArgsFormatRaw><![CDATA[ <OptionPrinting[1]> <;> <OptionDisplay[1]> {<':'> <DiscAddV[1..8]>} {<','> <DiscAddP[1..7]>} ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"SubtotalValue\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for the value of the subtotal amount</Desc></Res><ResFormatRaw><![CDATA[<SubtotalValue[1..10]>]]></ResFormatRaw></Response></Command><Command Name=\"ProgramNBLParameter\" CmdByte=\"0x4F\"><FPOperation>Program NBL parameter to be monitored by the fiscal device.</FPOperation><Args><Arg Name=\"\" Value=\"N\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"W\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionNBL\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol with value:  - '0' - No  - '1' - Yes</Desc></Arg><ArgsFormatRaw><![CDATA[ <'N'> <;> <'W'> <;> <OptionNBL[1]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"PrintDetailedFMPaymentsReportByDate\" CmdByte=\"0x7A\"><FPOperation>Print a detailed FM payments report by initial and end date.</FPOperation><Args><Arg Name=\"StartDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name=\"EndDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name=\"Option\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartDate \"DDMMYY\"> <;> <EndDate \"DDMMYY\"> <;> <Option['P']> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadDailyRA\" CmdByte=\"0x6E\"><FPOperation>Provides information about the RA amounts by type of payment and the total number of operations.</FPOperation><Args><Arg Name=\"\" Value=\"2\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'2'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"2\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"AmountPayment0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name=\"AmountPayment1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name=\"AmountPayment2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name=\"AmountPayment3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name=\"AmountPayment4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><Res Name=\"AmountPayment5\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 5</Desc></Res><Res Name=\"AmountPayment6\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 6</Desc></Res><Res Name=\"AmountPayment7\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 7</Desc></Res><Res Name=\"AmountPayment8\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 8</Desc></Res><Res Name=\"AmountPayment9\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 9</Desc></Res><Res Name=\"AmountPayment10\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 10</Desc></Res><Res Name=\"AmountPayment11\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 11</Desc></Res><Res Name=\"RANum\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for the total number of operations</Desc></Res><Res Name=\"SumAllPayment\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols to sum all payments</Desc></Res><ResFormatRaw><![CDATA[<'2'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]> <;> <AmountPayment5[1..13]> <;> <AmountPayment6[1..13]> <;> <AmountPayment7[1..13]> <;> <AmountPayment8[1..13]> <;> <AmountPayment9[1..13]> <;> <AmountPayment10[1..13]> <;> <AmountPayment11[1..13]> <;> <RANum[1..5]> <;> <SumAllPayment[1..13]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadGeneralDailyRegisters\" CmdByte=\"0x6E\"><FPOperation>Provides information about the number of customers (number of fiscal receipt issued), number of discounts, additions and corrections made and the accumulated amounts.</FPOperation><Args><Arg Name=\"\" Value=\"1\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'1'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"1\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"CustomersNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for number of customers</Desc></Res><Res Name=\"DiscountsNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for number of discounts</Desc></Res><Res Name=\"DiscountsAmount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for accumulated amount of discounts</Desc></Res><Res Name=\"AdditionsNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for number of additions</Desc></Res><Res Name=\"AdditionsAmount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for accumulated amount of additions</Desc></Res><Res Name=\"CorrectionsNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for number of corrections</Desc></Res><Res Name=\"CorrectionsAmount\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for accumulated amount of corrections</Desc></Res><ResFormatRaw><![CDATA[<'1'> <;> <CustomersNum[1..5]> <;> <DiscountsNum[1..5]> <;> <DiscountsAmount[1..13]> <;> <AdditionsNum[1..5]> <;> <AdditionsAmount[1..13]> <;> <CorrectionsNum[1..5]> <;> <CorrectionsAmount[1..13]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadDisplayGreetingMessage\" CmdByte=\"0x69\"><FPOperation>Provides the content of the Display Greeting message.</FPOperation><Args><Arg Name=\"\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'0'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"DisplayGreetingText\" Value=\"\" Type=\"Text\" MaxLen=\"20\"><Desc>20 symbols for display greeting message</Desc></Res><ResFormatRaw><![CDATA[<'0'> <;> <DisplayGreetingText[20]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadDetailedFMReportByZBlocks\" CmdByte=\"0x78\"><FPOperation>Read a detailed FM report by initial and end FM report number.</FPOperation><Args><Arg Name=\"StartZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for the initial report number included in report, format ####</Desc></Arg><Arg Name=\"EndZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for the final report number included in report, format ####</Desc></Arg><Arg Name=\"\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionReading\" Value=\"8\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <'0'> <;> <OptionReading['8']> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"PrintBriefFMPaymentsReport\" CmdByte=\"0x77\"><FPOperation>Prints a brief payments from the FM.</FPOperation><Args><Arg Name=\"Option\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <Option['P']> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadDailyAvailableAmounts_Old\" CmdByte=\"0x6E\"><FPOperation>Provides information about the amounts on hand by type of payment. Command works for KL version 2 devices.</FPOperation><Args><Arg Name=\"\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'0'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"AmountPayment0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name=\"AmountPayment1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name=\"AmountPayment2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name=\"AmountPayment3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name=\"AmountPayment4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><ResFormatRaw><![CDATA[<'0'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadBriefFMPaymentsReportByZBlocks\" CmdByte=\"0x79\"><FPOperation>Read a brief FM payments report by initial and end FM report number.</FPOperation><Args><Arg Name=\"StartZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for the initial FM report number included in report, format ####</Desc></Arg><Arg Name=\"EndZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for the final FM report number included in report, format ####</Desc></Arg><Arg Name=\"Option\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionReading\" Value=\"8\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <Option['P']> <;> <OptionReading['8']> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"ReadBriefFMDepartmentsReportByZBlocks\" CmdByte=\"0x79\"><FPOperation>Read a brief FM Departments report by initial and end Z report number.</FPOperation><Args><Arg Name=\"StartZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for the initial FM report number included in report, format ####</Desc></Arg><Arg Name=\"EndZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for the final FM report number included in report, format ####</Desc></Arg><Arg Name=\"Option\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionReading\" Value=\"8\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <Option['D']> <;> <OptionReading['8']> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"ReadGrandFiscalSalesAndStornoAmounts\" CmdByte=\"0x6E\"><FPOperation>Read the Grand fiscal turnover sum and Grand fiscal VAT sum.</FPOperation><Args><Arg Name=\"\" Value=\"7\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'7'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"7\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"GrandFiscalTurnover\" Value=\"\" Type=\"Decimal\" MaxLen=\"14\"><Desc>Up to 14 symbols for sum of turnover in FD</Desc></Res><Res Name=\"GrandFiscalVAT\" Value=\"\" Type=\"Decimal\" MaxLen=\"14\"><Desc>Up to 14 symbols for sum of VAT value in FD</Desc></Res><Res Name=\"GrandFiscalStornoTurnover\" Value=\"\" Type=\"Decimal\" MaxLen=\"14\"><Desc>Up to 14 symbols for sum of STORNO turnover in FD</Desc></Res><Res Name=\"GrandFiscalStornoVAT\" Value=\"\" Type=\"Decimal\" MaxLen=\"14\"><Desc>Up to 14 symbols for sum of STORNO VAT value in FD</Desc></Res><ResFormatRaw><![CDATA[<'7'> <;> <GrandFiscalTurnover[1..14]> <;> <GrandFiscalVAT[1..14]> <;> <GrandFiscalStornoTurnover[1..14]> <;> <GrandFiscalStornoVAT[1..14]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadBluetooth_Status\" CmdByte=\"0x4E\"><FPOperation>Providing information about if the device's Bluetooth module is enabled or disabled.</FPOperation><Args><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"B\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"S\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'R'><;><'B'><;><'S'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"B\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"S\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OptionBTstatus\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Disabled\" Value=\"0\" /><Option Name=\"Enabled\" Value=\"1\" /></Options><Desc>(Status) 1 symbol with value:  - '0' - Disabled  - '1' - Enabled</Desc></Res><ResFormatRaw><![CDATA[<'R'><;><'B'><;><'S'><;><BTstatus[1]>]]></ResFormatRaw></Response></Command><Command Name=\"OpenElectronicInvoiceWithFDCustomerDB\" CmdByte=\"0x30\"><FPOperation>Opens an electronic fiscal invoice receipt with 1 minute timeout assigned to the specified operator number and operator password with internal DB info for customer data. The Invoice receipt can be issued only if the invoice range (start and end numbers) is set.</FPOperation><Args><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbol from 1 to 20 corresponding to operator's number</Desc></Arg><Arg Name=\"OperPass\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for operator's password</Desc></Arg><Arg Name=\"reserved\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"reserved\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"InvoicePrintType\" Value=\"9\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"CustomerNum\" Value=\"\" Type=\"Text\" MaxLen=\"5\"><Desc>Symbol '#' and following up to 4 symbols for related customer ID number corresponding to the FD database</Desc></Arg><Arg Name=\"UniqueReceiptNumber\" Value=\"\" Type=\"Text\" MaxLen=\"24\"><Desc>Up to 24 symbols for unique receipt number. NRA format: XXXХХХХХ-ZZZZ-YYYYYYY where: * ХХХХХХXX - 8 symbols [A-Z, a-z, 0-9] for individual device number, * ZZZZ - 4 symbols [A-Z, a-z, 0-9] for code of the operator, * YYYYYYY - 7 symbols [0-9] for next number of the receipt</Desc><Meta MinLen=\"24\" Compulsory=\"false\" ValIndicatingPresence=\"$\" /></Arg><ArgsFormatRaw><![CDATA[ <OperNum[1..2]> <;> <OperPass[6]> <;> <reserved['0']> <;> <reserved['0']> <;> <InvoicePrintType['9']> <;> <CustomerNum[5]> { <'$'> <UniqueReceiptNumber[24]> } ]]></ArgsFormatRaw></Args></Command><Command Name=\"SellFractQtyPLUfromDep\" CmdByte=\"0x3D\"><FPOperation>Register the sell (for correction use minus sign in the price field) of article belonging to department with specified name, price, fractional quantity and/or discount/addition on the transaction. The VAT of article got from department to which article belongs.</FPOperation><Args><Arg Name=\"NamePLU\" Value=\"\" Type=\"Text\" MaxLen=\"36\"><Desc>36 symbols for article's name. 34 symbols are printed on paper. Symbol 0x7C '|' is new line separator.</Desc></Arg><Arg Name=\"reserved\" Value=\" \" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"Price\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for article's price. Use minus sign '-' for correction</Desc></Arg><Arg Name=\"Quantity\" Value=\"\" Type=\"Text\" MaxLen=\"10\"><Desc>From 3 to 10 symbols for quantity in format fractional format, e.g. 1/3</Desc><Meta MinLen=\"10\" Compulsory=\"false\" ValIndicatingPresence=\"*\" /></Arg><Arg Name=\"DiscAddP\" Value=\"\" Type=\"Decimal\" MaxLen=\"7\"><Desc>1 to 7 symbols for percentage of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\",\" /></Arg><Arg Name=\"DiscAddV\" Value=\"\" Type=\"Decimal\" MaxLen=\"8\"><Desc>1 to 8 symbols for value of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\":\" /></Arg><Arg Name=\"DepNum\" Value=\"\" Type=\"Decimal_plus_80h\" MaxLen=\"2\"><Desc>1 symbol for article department attachment, formed in the following manner; example: Dep01 = 81h, Dep02 = 82h … Dep19 = 93h Department range from 1 to 127</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\"!\" /></Arg><ArgsFormatRaw><![CDATA[ <NamePLU[36]> <;> <reserved[' ']> <;> <Price[1..10]> {<'*'> <Quantity[10]>} {<','> <DiscAddP[1..7]>} {<':'> <DiscAddV[1..8]>} {<'!'> <DepNum[1]>} ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadDeviceModuleSupport\" CmdByte=\"0x4E\"><FPOperation>Provide an information about modules supported by the device</FPOperation><Args><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'R'><;><'D'><;><'D'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OptionLAN\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol for LAN suppor - '0' - No  - '1' - Yes</Desc></Res><Res Name=\"OptionWiFi\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol for WiFi support - '0' - No  - '1' - Yes</Desc></Res><Res Name=\"OptionGPRS\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>1 symbol for GPRS support - '0' - No  - '1' - Yes BT (Bluetooth) 1 symbol for Bluetooth support - '0' - No  - '1' - Yes</Desc></Res><Res Name=\"OptionBT\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>(Bluetooth) 1 symbol for Bluetooth support - '0' - No  - '1' - Yes</Desc></Res><ResFormatRaw><![CDATA[<'R'><;><'D'><;><'D'><;><LAN[1]><;><WiFi[1]><;><GPRS[1]><;><BT[1]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadWiFi_NetworkName\" CmdByte=\"0x4E\"><FPOperation>Read device's connected WiFi network name</FPOperation><Args><Arg Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"W\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"\" Value=\"N\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'R'><;><'W'><;><'N'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"R\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"W\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"\" Value=\"N\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"WiFiNameLength\" Value=\"\" Type=\"Decimal\" MaxLen=\"3\"><Desc>(Length) Up to 3 symbols for the WiFi name length</Desc></Res><Res Name=\"WiFiNetworkName\" Value=\"\" Type=\"Text\" MaxLen=\"100\"><Desc>(Name) Up to 100 symbols for the device's WiFi network name</Desc></Res><ResFormatRaw><![CDATA[<'R'><;><'W'><;><'N'><;><WiFiNameLength[1..3]><;><WiFiNetworkName[100]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadParameters\" CmdByte=\"0x65\"><FPOperation>Provides information about the number of POS, printing of logo, cash drawer opening, cutting permission, display mode, article report type, Enable/Disable currency in receipt, EJ font type and working operators counter.</FPOperation><Response ACK=\"false\"><Res Name=\"POSNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>(POS Number) 4 symbols for number of POS in format ####</Desc></Res><Res Name=\"OptionPrintLogo\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>(Print Logo) 1 symbol of value:  - '1' - Yes  - '0' - No</Desc></Res><Res Name=\"OptionAutoOpenDrawer\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>(Auto Open Drawer) 1 symbol of value:  - '1' - Yes  - '0' - No</Desc></Res><Res Name=\"OptionAutoCut\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>(Auto Cut) 1 symbol of value:  - '1' - Yes  - '0' - No</Desc></Res><Res Name=\"OptionExternalDispManagement\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Auto\" Value=\"0\" /><Option Name=\"Manual\" Value=\"1\" /></Options><Desc>(External Display Management) 1 symbol of value:  - '1' - Manual  - '0' - Auto</Desc></Res><Res Name=\"OptionArticleReportType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Brief\" Value=\"0\" /><Option Name=\"Detailed\" Value=\"1\" /></Options><Desc>(Article Report) 1 symbol of value:  - '1' - Detailed  - '0' - Brief</Desc></Res><Res Name=\"OptionEnableCurrency\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"0\" /><Option Name=\"Yes\" Value=\"1\" /></Options><Desc>(Enable Currency) 1 symbol of value:  - '1' - Yes  - '0' - No</Desc></Res><Res Name=\"OptionEJFontType\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Low Font\" Value=\"1\" /><Option Name=\"Normal Font\" Value=\"0\" /></Options><Desc>(EJ Font) 1 symbol of value:  - '1' - Low Font  - '0' - Normal Font</Desc></Res><Res Name=\"reserved\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OptionWorkOperatorCount\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"More\" Value=\"0\" /><Option Name=\"One\" Value=\"1\" /></Options><Desc>(Work Operator Count) 1 symbol of value:  - '1' - One  - '0' - More</Desc></Res><ResFormatRaw><![CDATA[<POSNum[4]> <;> <PrintLogo[1]> <;> <AutoOpenDrawer[1]> <;> <AutoCut[1]> <;> <ExternalDispManagement[1]> <;> <ArticleReportType[1]> <;> <EnableCurrency[1]> <;> <EJFontType[1]> <;> <reserved['0']> <;> <WorkOperatorCount[1]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadDetailedFMDepartmentsReportByDate\" CmdByte=\"0x7A\"><FPOperation>Read a detailed FM Departments report by initial and end date.</FPOperation><Args><Arg Name=\"StartDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name=\"EndDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name=\"Option\" Value=\"D\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionReading\" Value=\"8\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartDate \"DDMMYY\"> <;> <EndDate \"DDMMYY\"> <;> <Option['D']> <;> <OptionReading['8']> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"ReadVersion\" CmdByte=\"0x21\"><FPOperation>Provides information about the device type, Certificate number, Certificate date and time and Device model.</FPOperation><Response ACK=\"false\"><Res Name=\"OptionDeviceType\" Value=\"\" Type=\"Option\" MaxLen=\"2\"><Options><Option Name=\"ECR\" Value=\"1\" /><Option Name=\"ECR for online store\" Value=\"11\" /><Option Name=\"for FUVAS device\" Value=\"5\" /><Option Name=\"FPr\" Value=\"2\" /><Option Name=\"FPr for online store\" Value=\"21\" /><Option Name=\"Fuel\" Value=\"3\" /><Option Name=\"Fuel system\" Value=\"31\" /></Options><Desc>1 or 2 symbols for type of fiscal device: - '1' - ECR - '11' - ECR for online store - '2' - FPr - '21' - FPr for online store - '3' - Fuel - '31' - Fuel system - '5' - for FUVAS device</Desc></Res><Res Name=\"CertificateNum\" Value=\"\" Type=\"Text\" MaxLen=\"6\"><Desc>6 symbols for Certification Number of device model</Desc></Res><Res Name=\"CertificateDateTime\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"dd-MM-yyyy HH:mm\"><Desc>16 symbols for Certificate Date and time parameter  in format: DD-MM-YYYY HH:MM</Desc></Res><Res Name=\"Model\" Value=\"\" Type=\"Text\" MaxLen=\"50\"><Desc>Up to 50 symbols for Model name</Desc></Res><Res Name=\"Version\" Value=\"\" Type=\"Text\" MaxLen=\"20\"><Desc>Up to 20 symbols for Version name and Check sum</Desc></Res><ResFormatRaw><![CDATA[<DeviceType[1..2]> <;> <CertificateNum[6]> <;> <CertificateDateTime \"DD-MM-YYYY HH:MM\"> <;> <Model[50]> <;> <Version[20]>]]></ResFormatRaw></Response></Command><Command Name=\"RawWrite\" CmdByte=\"0xFE\"><FPOperation> Writes raw bytes to FP </FPOperation><Args><Arg Name=\"Bytes\" Value=\"\" Type=\"Base64\" MaxLen=\"5000\"><Desc>The bytes in BASE64 ecoded string to be written to FP</Desc></Arg></Args></Command><Command Name=\"SellPLUwithSpecifiedVATfromDep_\" CmdByte=\"0x34\"><FPOperation>Register the sell (for correction use minus sign in the price field) of article with specified department. If VAT is present the relevant accumulations are perfomed in its registers.</FPOperation><Args><Arg Name=\"NamePLU\" Value=\"\" Type=\"Text\" MaxLen=\"36\"><Desc>36 symbols for name of sale. 34 symbols are printed on paper. Symbol 0x7C '|' is new line separator.</Desc></Arg><Arg Name=\"DepNum\" Value=\"\" Type=\"Decimal_plus_80h\" MaxLen=\"2\"><Desc>1 symbol for article department attachment, formed in the following manner: DepNum[HEX] + 80h example: Dep01 = 81h, Dep02 = 82h … Dep19 = 93h Department range from 1 to 127</Desc></Arg><Arg Name=\"Price\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for article's price. Use minus sign '-' for correction</Desc></Arg><Arg Name=\"Quantity\" Value=\"\" Type=\"Decimal\" MaxLen=\"10\"><Desc>Up to 10 symbols for article's quantity sold</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\"*\" /></Arg><Arg Name=\"DiscAddP\" Value=\"\" Type=\"Decimal\" MaxLen=\"7\"><Desc>Up to 7 for percentage of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\",\" /></Arg><Arg Name=\"DiscAddV\" Value=\"\" Type=\"Decimal\" MaxLen=\"8\"><Desc>Up to 8 symbols for percentage of discount/addition. Use minus sign '-' for discount</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\":\" /></Arg><Arg Name=\"OptionVATClass\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Forbidden\" Value=\"*\" /><Option Name=\"VAT Class 0\" Value=\"А\" /><Option Name=\"VAT Class 1\" Value=\"Б\" /><Option Name=\"VAT Class 2\" Value=\"В\" /><Option Name=\"VAT Class 3\" Value=\"Г\" /><Option Name=\"VAT Class 4\" Value=\"Д\" /><Option Name=\"VAT Class 5\" Value=\"Е\" /><Option Name=\"VAT Class 6\" Value=\"Ж\" /><Option Name=\"VAT Class 7\" Value=\"З\" /></Options><Desc>1 character for VAT class:  - 'А' - VAT Class 0  - 'Б' - VAT Class 1  - 'В' - VAT Class 2  - 'Г' - VAT Class 3  - 'Д' - VAT Class 4  - 'Е' - VAT Class 5  - 'Ж' - VAT Class 6  - 'З' - VAT Class 7  - '*' - Forbidden</Desc><Meta MinLen=\"1\" Compulsory=\"false\" ValIndicatingPresence=\"!\" /></Arg><ArgsFormatRaw><![CDATA[ <NamePLU[36]> <;> <DepNum[1]> <;> <Price[1..10]> {<'*'> <Quantity[1..10]>} {<','> <DiscAddP[1..7]>} {<':'> <DiscAddV[1..8]>} {<'!'> <OptionVATClass[1]>} ]]></ArgsFormatRaw></Args></Command><Command Name=\"PrintOrStoreEJByRcpNum\" CmdByte=\"0x7C\"><FPOperation>Print or store Electronic Journal Report from receipt number to receipt number.</FPOperation><Args><Arg Name=\"OptionReportStorage\" Value=\"\" Type=\"Option\" MaxLen=\"2\"><Options><Option Name=\"Printing\" Value=\"J1\" /><Option Name=\"SD card storage\" Value=\"J4\" /><Option Name=\"USB storage\" Value=\"J2\" /></Options><Desc>1 character with value:  - 'J1' - Printing  - 'J2' - USB storage  - 'J4' - SD card storage</Desc></Arg><Arg Name=\"\" Value=\"N\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"StartRcpNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"000000.\"><Desc>6 symbols for initial receipt number included in report, in format ######.</Desc></Arg><Arg Name=\"EndRcpNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"000000.\"><Desc>6 symbols for final receipt number included in report in format ######.</Desc></Arg><ArgsFormatRaw><![CDATA[ <ReportStorage[2]> <;> <'N'> <;> <StartRcpNum[6]> <;> <EndRcpNum[6]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadDailyReturnedChangeAmountsByOperator\" CmdByte=\"0x6F\"><FPOperation>Read the amounts returned as change by different payment types for the specified operator.</FPOperation><Args><Arg Name=\"\" Value=\"6\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbol from 1 to 20 corresponding to operator's number</Desc></Arg><ArgsFormatRaw><![CDATA[ <'6'> <;> <OperNum[1..2]> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"6\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"OperNum\" Value=\"\" Type=\"Decimal\" MaxLen=\"2\"><Desc>Symbols from 1 to 20 corresponding to operator's number</Desc></Res><Res Name=\"ChangeAmountPayment0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by type of payment 0</Desc></Res><Res Name=\"ChangeAmountPayment1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by type of payment 1</Desc></Res><Res Name=\"ChangeAmountPayment2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by type of payment 2</Desc></Res><Res Name=\"ChangeAmountPayment3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by type of payment 3</Desc></Res><Res Name=\"ChangeAmountPayment4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by type of payment 4</Desc></Res><Res Name=\"ChangeAmountPayment5\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by type of payment 5</Desc></Res><Res Name=\"ChangeAmountPayment6\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by type of payment 6</Desc></Res><Res Name=\"ChangeAmountPayment7\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by type of payment 7</Desc></Res><Res Name=\"ChangeAmountPayment8\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by type of payment 8</Desc></Res><Res Name=\"ChangeAmountPayment9\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by type of payment 9</Desc></Res><Res Name=\"ChangeAmountPayment10\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by type of payment 10</Desc></Res><Res Name=\"ChangeAmountPayment11\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for amounts received by type of payment 11</Desc></Res><ResFormatRaw><![CDATA[<'6'> <;> <OperNum[1..2]> <;> <ChangeAmountPayment0[1..13]> <;> <ChangeAmountPayment1[1..13]> <;> <ChangeAmountPayment2[1..13]> <;> <ChangeAmountPayment3[1..13]> <;> <ChangeAmountPayment4[1..13]> <;> <ChangeAmountPayment5[1..13]> <;> <ChangeAmountPayment6[1..13]> <;> <ChangeAmountPayment7[1..13]> <;> <ChangeAmountPayment8[1..13]> <;> <ChangeAmountPayment9[1..13]> <;> <ChangeAmountPayment10[1..13]> <;> <ChangeAmountPayment11[1..13]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadEJByReceiptNumCustom\" CmdByte=\"0x7C\"><FPOperation>Read or Store Electronic Journal Report from receipt number to receipt number, CSV format option and document content. If CSV format is set the content can includes only fiscal receipts. FlagsReceipts is a char with bits representing the receipt types. FlagsReports is a char with bits representing the report type.</FPOperation><Args><Arg Name=\"OptionStorageReport\" Value=\"\" Type=\"Option\" MaxLen=\"2\"><Options><Option Name=\"To PC\" Value=\"j0\" /><Option Name=\"To SD card\" Value=\"j4\" /><Option Name=\"To USB Flash Drive\" Value=\"j2\" /></Options><Desc>1 character with value  - 'j0' - To PC  - 'j2' - To USB Flash Drive  - 'j4' - To SD card</Desc></Arg><Arg Name=\"OptionCSVformat\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"No\" Value=\"X\" /><Option Name=\"Yes\" Value=\"C\" /></Options><Desc>1 symbol with value:  - 'C' - Yes  - 'X' - No</Desc></Arg><Arg Name=\"FlagsReceipts\" Value=\"\" Type=\"Flags\" MaxLen=\"1\"><Desc>1 symbol for Receipts included in EJ: Flags.7=0 Flags.6=1 Flags.5=1 Yes, Flags.5=0 No (Include PO) Flags.4=1 Yes, Flags.4=0 No (Include RA) Flags.3=1 Yes, Flags.3=0 No (Include Credit Note) Flags.2=1 Yes, Flags.2=0 No (Include Storno Rcp) Flags.1=1 Yes, Flags.1=0 No (Include Invoice) Flags.0=1 Yes, Flags.0=0 No (Include Fiscal Rcp)</Desc></Arg><Arg Name=\"FlagsReports\" Value=\"\" Type=\"Flags\" MaxLen=\"1\"><Desc>1 symbol for Reports included in EJ: Flags.7=0 Flags.6=1 Flags.5=0 Flags.4=1 Yes, Flags.4=0 No (Include FM reports) Flags.3=1 Yes, Flags.3=0 No (Include Other reports) Flags.2=1 Yes, Flags.2=0 No (Include Daily X) Flags.1=1 Yes, Flags.1=0 No (Include Daily Z) Flags.0=1 Yes, Flags.0=0 No (Include Duplicates)</Desc></Arg><Arg Name=\"\" Value=\"N\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"StartRcpNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"000000.\"><Desc>6 symbols for initial receipt number included in report in format ######.</Desc></Arg><Arg Name=\"EndRcpNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"6\" Format=\"000000.\"><Desc>6 symbols for final receipt number included in report in format ######.</Desc></Arg><ArgsFormatRaw><![CDATA[ <StorageReport[2]> <;> <CSVformat[1]> <;> <FlagsReceipts[1]> <;> <FlagsReports[1]> <;> <'N'> <;> <StartRcpNum[6]> <;> <EndRcpNum[6]> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"CashPayCloseReceipt\" CmdByte=\"0x36\"><FPOperation>Paying the exact amount in cash and close the fiscal receipt.</FPOperation></Command><Command Name=\"ProgDisplayGreetingMessage\" CmdByte=\"0x49\"><FPOperation>Program the contents of a Display Greeting message.</FPOperation><Args><Arg Name=\"\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"DisplayGreetingText\" Value=\"\" Type=\"Text\" MaxLen=\"20\"><Desc>20 symbols for Display greeting message</Desc></Arg><ArgsFormatRaw><![CDATA[<'0'> <;> <DisplayGreetingText[20]> ]]></ArgsFormatRaw></Args></Command><Command Name=\"ReadDailyPO\" CmdByte=\"0x6E\"><FPOperation>Provides information about the PO amounts by type of payment and the total number of operations.</FPOperation><Args><Arg Name=\"\" Value=\"3\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <'3'> ]]></ArgsFormatRaw></Args><Response ACK=\"false\"><Res Name=\"\" Value=\"3\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Res Name=\"AmountPayment0\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 0</Desc></Res><Res Name=\"AmountPayment1\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 1</Desc></Res><Res Name=\"AmountPayment2\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 2</Desc></Res><Res Name=\"AmountPayment3\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 3</Desc></Res><Res Name=\"AmountPayment4\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 4</Desc></Res><Res Name=\"AmountPayment5\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 5</Desc></Res><Res Name=\"AmountPayment6\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 6</Desc></Res><Res Name=\"AmountPayment7\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 7</Desc></Res><Res Name=\"AmountPayment8\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 8</Desc></Res><Res Name=\"AmountPayment9\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 9</Desc></Res><Res Name=\"AmountPayment10\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 10</Desc></Res><Res Name=\"AmountPayment11\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols for the accumulated amount by payment type 11</Desc></Res><Res Name=\"PONum\" Value=\"\" Type=\"Decimal\" MaxLen=\"5\"><Desc>Up to 5 symbols for the total number of operations</Desc></Res><Res Name=\"SumAllPayment\" Value=\"\" Type=\"Decimal\" MaxLen=\"13\"><Desc>Up to 13 symbols to sum all payments</Desc></Res><ResFormatRaw><![CDATA[<'3'> <;> <AmountPayment0[1..13]> <;> <AmountPayment1[1..13]> <;> <AmountPayment2[1..13]> <;> <AmountPayment3[1..13]> <;> <AmountPayment4[1..13]> <;> <AmountPayment5[1..13]> <;> <AmountPayment6[1..13]> <;> <AmountPayment7[1..13]> <;> <AmountPayment8[1..13]> <;> <AmountPayment9[1..13]> <;> <AmountPayment10[1..13]> <;> <AmountPayment11[1..13]> <;> <PONum[1..5]> <;> <SumAllPayment[1..13]>]]></ResFormatRaw></Response></Command><Command Name=\"ReadDetailedFMPaymentsReportByZBlocks\" CmdByte=\"0x78\"><FPOperation>Read a detailed FM payments report by initial and end Z report number.</FPOperation><Args><Arg Name=\"StartZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for initial FM report number included in report, format ####</Desc></Arg><Arg Name=\"EndZNum\" Value=\"\" Type=\"Decimal_with_format\" MaxLen=\"4\" Format=\"0000\"><Desc>4 symbols for final FM report number included in report, format ####</Desc></Arg><Arg Name=\"Option\" Value=\"P\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionReading\" Value=\"8\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartZNum[4]> <;> <EndZNum[4]> <;> <Option['P']> <;> <OptionReading['8']> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"ReadBriefFMReportByDate\" CmdByte=\"0x7B\"><FPOperation>Read a brief FM report by initial and end date.</FPOperation><Args><Arg Name=\"StartDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name=\"EndDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name=\"\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionReading\" Value=\"8\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartDate \"DDMMYY\"> <;> <EndDate \"DDMMYY\"> <;> <'0'> <;> <OptionReading['8']> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"ReadDetailedFMReportByDate\" CmdByte=\"0x7A\"><FPOperation>Read a detailed FM report by initial and end date.</FPOperation><Args><Arg Name=\"StartDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for initial date in the DDMMYY format</Desc></Arg><Arg Name=\"EndDate\" Value=\"\" Type=\"DateTime\" MaxLen=\"10\" Format=\"ddMMyy\"><Desc>6 symbols for final date in the DDMMYY format</Desc></Arg><Arg Name=\"\" Value=\"0\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><Arg Name=\"OptionReading\" Value=\"8\" Type=\"OptionHardcoded\" MaxLen=\"1\" /><ArgsFormatRaw><![CDATA[ <StartDate \"DDMMYY\"> <;> <EndDate \"DDMMYY\"> <;> <'0'> <;> <OptionReading['8']> ]]></ArgsFormatRaw></Args><Response ACK=\"true\" ACK_PLUS=\"true\" /></Command><Command Name=\"PrintDetailedDailyReport\" CmdByte=\"0x7F\"><FPOperation>Prints an extended daily financial report (an article report followed by a daily financial report) with or without zeroing ('Z' or 'X').</FPOperation><Args><Arg Name=\"OptionZeroing\" Value=\"\" Type=\"Option\" MaxLen=\"1\"><Options><Option Name=\"Without zeroing\" Value=\"X\" /><Option Name=\"Zeroing\" Value=\"Z\" /></Options><Desc>with following values:  - 'Z' -Zeroing  - 'X' - Without zeroing</Desc></Arg><ArgsFormatRaw><![CDATA[ <OptionZeroing[1]> ]]></ArgsFormatRaw></Args></Command></Defs>";
    $this->serverSendDefs($defs);
  }
  
  }

}
?>