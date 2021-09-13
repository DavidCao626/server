<?php
return [
    'can_not_get_invoice_cloud' => 'Invoice cloud integration is not enabled, please go to the integration center - invoice integration open',
    'valid_not_open' => 'The verification function is not enabled. Please contact the administrator to [Settings]-[Valid Settings] to enable the verification function of invoices.',
    'user_sync_failed' => 'Some employee information synchronization failed',
    'user_sync_all_failed' => 'Sync failed',
    'get_upload_photo_failed' => 'Failed to get the uploaded image',
    'not_get_flow_data' => 'No invoice cloud information was found from the process data',
    'config_param_error' => 'Configuration parameter error: please pass in interface address, appKey, appSecret',
    'user_not_sync_invoice_cloud' => 'Personnel not synchronized to the invoice cloud cannot use the invoice cloud function,please go to the personnel synchronization menu to synchronize',
    'get_token_error' => 'Failed to get the authentication TOKEN. The invoice cloud function cannot be used',
    'get_flow_config_data_failed' => 'Failed to get configuration information',
    'already_integrate' => 'The current process is integrated and configured',
    'create_failed' => 'New failed',
    'edit_failed' => 'Edit failed',
    'get_config_failed' => 'The current configuration was not detected',
    'delete_failed' => 'Delete failed',
    'return_msg_error' => 'Did not get the error message returned',
    'invoice_cloud' => [
        // 全局错误码
        0   =>  'success',
        '-2'   =>  'Invalid token',
        '-3'   =>  'Failed to decrypt the parameter',
        '-4'   =>  'Cannot use token call, please use appKey encryption call',
        '-5'   =>  'Cannot use appKey encryption call, please use TOKEN call',
        1  =>  'Parameter formatting error json format error, contact product customer service before',
        3  =>  'Failed to get user information. Please operate later: userId is invalid',
        4  =>  'Get user enterprise id failed: cid is invalid',
        5  =>  'File upload failed: File upload error',
        6  =>  'User does not exist',
        7  =>  'Data does not exist',
        8  =>  'Unsupported interface call',
        400  =>  'Wrong parameter',
        405  =>  'Without permission, you need to enter the personnel list of [Setting - Personnel Synchronization], and set the user "Invoice clustering into user roles" as the administrator by setting it as the administrator operation',
        414  =>  'Insufficient balance',
        500  =>  'Server error',
        409  =>  'Wrong id',
        // 邮箱设置错误码
        410  =>  'Mailbox password error',
        411  =>  'Email error',
        412  =>  'Email format error',
        413  =>  'Email format error',
        // 基础统一错误：
        1014  =>  'Server internal error',
        1016  =>  'Wrong parameter',
        1024  =>  'Server internal data handling exception',
        1036  =>  'Server temporarily does not support',
        // 1210  =>  'Without permission',
        1210  =>  'Without permission, you need to enter the personnel list of [Setting - Personnel Synchronization], and set the user "Invoice clustering into user roles" as the administrator by setting it as the administrator operation',
        1502  =>  'No user information',
        1601  =>  'Robot does not exist',
        1650  =>  'The push message is closed',
        1651  =>  'Failed to get team members',
        1652  =>  'Get cids list failed',
        1653  =>  'Data does not exist',
        1654  =>  'cid uid为0',
        1655  =>  'Request timeout',
        11000 =>  'Primary key conflict',
        // 发票识别、验真相关错误：
        1660  =>  'Invoice does not exist',
        1662  =>  'Invoice identification failed. please contact the administrator',
        1663  =>  'Invoice validation failed',
        1664  =>  'Invalid company cid',
        1666  =>  'This type of invoice has not been supported by the server',
        1672  =>  'User invoice is added repeatedly',
        1673  =>  'Duplicate identification of invoice',
        1674  =>  'Verification of truth is not supported',
        1678  =>  'Fuzzy invoice',
        1679  =>  'Invoice pre-tax amount cannot be blank',
        1680  =>  'Total invoice amount cannot be blank',
        1681  =>  'Invoice check code cannot be blank',
        1682  =>  'Invoice type cannot be blank',
        1683  =>  'Invoice basic information cannot be blank code number date',
        1686  =>  'The same invoice can only be entered once in the same team',
        1688  =>  'Invoice id does not exist',
        1689  =>  'Invoice is locked and cannot be deleted',
        1690  =>  'Duplicate reimbursement of invoice',
        1691  =>  'Invoice is not being reimbursed, and cannot be cancelled',
        1692  =>  'Invoice is not being reimbursed, and reimbursement cannot be completed directly',
        1693  =>  'The amount is less than the verifiable amount. Inspection is not supported',
        1694  =>  'User does not have inspection authority',
        2000  =>  'Wrong network request parameters',
        2001  =>  'Json parsing failed',
        2017  =>  'The service encountered an unknown error, please try again later',
        2035  =>  'Guoxin verification parameter is wrong, please try again later (client prompt: invoice is abnormal)',
        2036  =>  'Invoice does not exist',
        2037  =>  'Check code error',
        2038  =>  'Error in pre-tax amount',
        2039  =>  'Check timeout',
        2040  =>  'Access denied, please try again later',
        2041  =>  'Error in checking account information',
        2042  =>  'Check operation is not supported',
        2043  =>  'Query invoice is not standard',
        2044  =>  'Inconsistent invoice after successful inspection',
        2045  =>  'Wrong invoice number',
        2046  =>  'Exceeding the number of inspections of the ticket on the same day',
        2047  =>  'Exception in checking, please try again later',
        2048  =>  'Exceeding the service validity limit',
        2049  =>  'Check that the number of invoices exceeds the upper limit',
        2050  =>  'Wrong invoicing date',
        2103  =>  'Invalid picture',
        2104  =>  'No traffic packets available',
        2110  =>  'The interface is called too frequently',
        2111  =>  'Error in checking parameters',
        2112  =>  'Wrong invoice number',
        2113  =>  'The tax bureau is upgrading and stopping maintenance. Please call again after upgrading and maintenance',
        2114  =>  'Exception in calling tax bureau. Please try again later.',
        2115  =>  'Invoicing for more than one year',
        2116  =>  'Exceed the number of checks for this account',
        2117  =>  'Exceeded the maximum number of server requests',
        2118  =>  'The request is illegal',
        2119  =>  'The maximum number of inspections has been exceeded',
        2120  =>  'The request is irregular',
        2121  =>  'Is not in the query ip address range',
        // 发票同步错误：
        1677  =>  'Invoice reimbursement time conflicts with the selected time',
        // 发票抬头错误：
        1661  =>  'Invoice header does not exist',
        // 企业票夹错误
        1675  =>  'Invoice does not exist under company header',
        // 第三方对接支付宝微信错误：
        1687  =>  'Invalid appkey',
        2150  =>  'Failed to get token',
        2151  =>  'Wechat failed to obtain data',
        2162  =>  'Alipay failed to obtain data',
        'other' => 'Operation failed, error code is ',
        99999 => 'Request Failed'
    ],
    'upload_photo_size_too_large' => 'The uploaded image is too big, up to 10MB',
    'invoice_id_error' => 'Invoice identification error',
    'has_integrate' => 'The current process has configured invoice reimbursement',
    'no_invoice' => 'No invoice',
    'source_error' => 'Error in tripartite source identification',
    'param_error_appkey' => 'Parameter error to get appKey',
    'appkey_not_equal' => 'The parameter AppKey is inconsistent',
    'get_third_token_error' => 'Get three-way token failed',
    'user_already_sync' => 'All personnel have been synchronized to the invoice cloud',
    'setting_not_enable' => "Workflow integration configuration is not enabled. Please go to Invoice Management-Bill Process Configuration Enable",
    'get_platform_failed' => 'Platform source is not obtained',
    'recognition_succeeded' => 'Recognition successful',
    'recognition_fail' => 'Recognition failed',
    'some' => 'strip',
    'recognition_failed' => ', Recognition failed',
    'failed_reason' => ', the reason for failure: ',
    "upload_file_to_teamsyun_failed" => 'Upload file to invoice cloud failed, please try again later',
    "user_sync_yet" => ', the user is synchronized',
    'invoice_code' => 'Invoice code',
    'invoice_number' => 'Invoice number',
    'invoice_type' => 'Invoice type',
    'invoice_date' => 'Invoice date',
    'payer_company' => 'Payer company',
    'buyer_company' => 'Buyer company',
    'total' => 'Total',
    'valid' => 'Valid',
    'invoice_status' => 'Status',
    'invoice_source' => 'Source',
    'create_time' => 'Create time',
    'not_valid' => 'Unchecked',
    'is_valid' => 'Valid',
    'valid_checked' => 'Valid checked',
    'valid_unchecked' => 'Valid unchecked',
    'valid_failed' => 'Check failed',
    'invalid' => 'Invalid',
    'check_failure' => 'Verification failed',
    'unreimbursed' => 'Not reimbursed',
    'reimbursing' => 'Reimbursement',
    'reimbursed' => 'Reimbursed',
    'red_flush' => 'Invoice red flush',
    'occupied' => 'Invoice is occupied',
    'normal' => 'Normal',
    'out_of_work' => 'Become invalid',
    'obsolete' => 'Void',
    'red_letter' => 'Scarlet letter',
    'abnormal' => 'Abnormal',
    'forwarded' => 'Forwarded',
    "request_error" => 'The request failed, please confirm whether the API service address is correct',
    'invalid_url' => 'API service address is wrong, please confirm',
    'operate_failed' => 'Operate failed',
    'invoice_id'=> 'Invoice ID',
    'invoice_normal_status' => 'Invoice normal status',
    'creator' => 'Invoice owner',
    'user_is_sync' => 'Staff synchronization, please staff synchronization, please operate later',
    'please_select_share_user' => 'The shared user is not selected or the shared user is unchanged',
    'id' => 'Unique value of invoice',
    'type_code' => 'Invoice type code',
    'check_code' => 'Invoicing check code',
    'kind' => 'Invoice consumption type',
    'reim_status' => 'Invoice reimbursement status',
    'ccy' => 'Currency type',
    'amount' => 'Excluding tax',
    'payer_tcode' => 'Seller’s taxpayer identification number',
    'payer_addr' => 'Seller\'s address and phone number',
    'payer_bank' => 'Seller\'s bank and account number',
    'buyer_tcode' => 'Purchaser\'s taxpayer identification number',
    'buyer_addr' => 'Purchaser\'s address and phone number',
    'buyer_bank' => 'The buyer\'s bank and account number',
    'furl' => 'Invoice image link',
    'pdf' => 'Invoice PDF link',
    'attr' => 'Invoice attributes',
    'purp' => 'Invoice remarks',
    'verr_msg' => 'Reason for failure',
    'no_inspection' => 'Unchecked',
    'check_failed' => 'Verification failed',
    'tax_rate' => 'tax rate',
    'content'=> 'Name of goods or services',
    'aperiod' => 'Accounting period',
    'corp_seal' => 'A company seal',
    'form_name' => 'Invoice Coupon',
    'agent_mark' => 'Whether to open',
    'acquisition' => 'Whether to acquire',
    'block_chain' => 'Blockchain token',
    'city' => 'City',
    'province' => 'Province',
    'service_name' => 'Service type',
    'reviewer' => 'Reviewer',
    'receiptor' => 'Receiptor',
    'issuer' => 'Issuer',
    'transit' => 'Transit',
    'oil_mark' => 'Oil_mark',
    'machine_code' => 'Machinary code',
    'ciphertext' => 'Password area',
    'category' => 'Species',
    'high_way' => 'High-speed marking',
    'code_confirm' => 'Machine-printed invoice code',
    'number_confirm'=> 'Machine invoice number',
    'stax' => 'Travel tax',
    'comment' => 'Remarks',
    'ttax' => 'tax',
    'trate' => 'tax rate',
    'pcontact' => 'Seller\'s address and phone number',
    'pbank' => 'Seller\'s bank and account number',
    'bcontact' => 'Buyer\'s address and phone number',
    'bbank' => 'Buyer\'s bank and account number',
    'machine_number' => 'Machine number',
    'vehicleDetail_idCode' => 'Organization Code',
    'vehicleDetail_carType' => 'Vehicle Type',
    'vehicleDetail_brankNumber' => 'Brand Model',
    'vehicleDetail_certificateNumber' => 'Certificate number',
    'vehicleDetail_commodityInspectionNumber' => 'Commodity inspection order number',
    'vehicleDetail_engineCode' => 'Engine number',
    'vehicleDetail_importationNumber' => 'Import certificate number',
    'vehicleDetail_seatingCapacity' => 'Maximum number of passengers',
    'vehicleDetail_vehicleIdentificationCode' => 'Vehicle Identification Number',
    'vehicleDetail_taxOfficeCode' => 'Competent tax authority code',
    'vehicleDetail_taxOfficeName' => 'Name of the competent tax authority',
    'vehicleDetail_dutyPaidNumber' => 'Tax Payment Voucher Number',
    'vehicleDetail_origin' => 'Origin',
    'sellAddr' => 'Seller\'s address',
    'sellTel' => 'Seller\'s phone',
    'buyAddr' => 'Purchaser\'s address',
    'buyTel' => 'Buyer\'s phone',
    'numberOrderError' => 'Invoice Coupon',
    'usedVehicle_carType' => 'Vehicle Type',
    'usedVehicle_brankNumber' => 'Brand Model',
    'usedVehicle_vehicleIdentificationCode' => 'Vehicle Identification Number',
    'usedVehicle_licensePlate' => 'number plate',
    'usedVehicle_registrationNumber' => 'registration code',
    'usedVehicle_auctionTaxCode' => 'Taxpayer identification number of business and auction unit',
    'usedVehicle_auctionCompany' => 'Name of business and auction unit',
    'usedVehicle_auctionTelephone' => 'Operating and auction unit telephone',
    'usedVehicle_auctionAddress' => 'Address of business and auction unit',
    'usedVehicle_auctionBankAccount' => 'Account opening bank and account number of operating and auction units',
    'usedVehicle_marketTaxCode' => 'Used car market identification number',
    'usedVehicle_marketCompany' => 'Used car market name',
    'usedVehicle_marketAddress' => 'Used car market address',
    'usedVehicle_marketTelephone' => 'Used car market phone',
    'usedVehicle_marketBankAccount' => 'Used car market opening bank, account number',
    'usedVehicle_transferVehicleManagement' => 'Name of transfer vehicle management office',
    'time_geton' => 'Boarding time',
    'time_getoff' => 'Get off time',
    'mileage' => 'Mileage',
    'place' => 'Invoice location',
    'license_plate' => 'Number plate',
    'fare' => 'Fuel costs',
    'surcharge' => 'Additional fee',
    'companySeal' => 'Is there a company seal',
    'time' => 'Time',
    'name' => 'Passenger\'s name',
    'station_geton' => 'Boarding station',
    'station_getoff' => 'Drop off station',
    'train_number' => 'Train number',
    'seat' => 'Seat type',
    'serial_number' => 'Serial number',
    'user_id' => 'Identity number',
    'ticket_gate' => 'Ticket gate',
    'entrance' => 'Entrance',
    'exit' => 'Exit',
    'highway_flag' => 'High speed sign',
    'user_name' => 'Passenger\'s name',
    'agentcode' => 'Sales unit code',
    'issue_by' => 'Fill in the unit',
    'fare1' => 'Fare',
    'fuel_surcharge' => 'Fuel Surcharge',
    'caac_development_fund' => 'Civil Aviation Development Fund',
    'insurance' => 'Insurance',
    'international_flag' => 'Domestic and international labels',
    'print_number' => 'Printing serial number',
    'store_name' => 'Shop name',
    'tax' => 'Taxes',
    'discount' => 'Discount',
    'tips' => 'Tips',
    'date_start' => 'Start time of the trip',
    'date_end' => 'End of the trip',
    'phone' => 'Traveler\'s mobile phone number',
    'ctm' => 'Add time',
    'yes' => 'Yse',
    'no' => 'No',
    'attr_corp' => 'Enterprise',
    'attr_person' => 'Personal',
    'reiming_money' => 'Amount in reimbursement',
    'reimed_money' => 'Amount reimbursed',
    'reim_money_greater_than_can_reim' => 'the reimbursement amount is greater than the reimbursable amount',
    'delete_failed_reason' => 'The following invoice deletion failed:'
];