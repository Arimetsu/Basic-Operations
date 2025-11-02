<?php

class CustomerController extends Controller {
    private $customerModel;

   public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Redirect to login if not logged in
        if (!isset($_SESSION['customer_id'])) {
            header('Location: ' . URLROOT . '/auth/login');
            exit();
        }

        parent::__construct();
        $this->customerModel = $this->model('Customer');
    }

    public function dashboard() {
        $data = [
            'title' => "Dashboard",
            'first_name' => $_SESSION['customer_first_name'],
            'last_name'  => $_SESSION['customer_last_name'],
            'customer_id' => $_SESSION['customer_id']  
        ];

        $this->view('customer/dashboard', $data);
    }

    // --- ACCOUNT ---

    public function account(){

        $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);

        $data = [
            'title' => "Accounts",
            'first_name' => $_SESSION['customer_first_name'],
            'last_name'  => $_SESSION['customer_last_name'],
            'accounts' => $accounts
        ];
        $this->view('customer/account', $data);
    }

    public function removeAccount()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $account_id = trim($_POST['account_id']);

            $data = [
                'customer_id'      => $_SESSION['customer_id'],
                'account_id'       => $account_id,
                'account_id_error' => '',
                'success_message'  => '',
            ];

            // Validate
            if (empty($account_id)) {
                $data['account_id_error'] = 'Please enter your account ID.';
            } else {
                // Check if account exists and belongs to the logged-in customer
                $account = $this->customerModel->getAccountById($account_id);

                if (!$account) {
                    $data['account_id_error'] = 'Account not found.';
                } elseif ($account->customer_id != $_SESSION['customer_id']) {
                    $data['account_id_error'] = 'You do not have permission to remove this account.';
                }
            }

            // If validation passes
            if (empty($data['account_id_error'])) {
                if ($this->customerModel->deleteAccountById($account_id)) {
                    $_SESSION['flash_success'] = 'Account removed successfully.';
                    header('Location: ' . URLROOT . '/customer/account');
                    exit();
                } else {
                    $data['account_id_error'] = 'Something went wrong while deleting the account.';
                }
            }

            // Get updated account list for view
            $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);

            $data = array_merge($data, [
                'title' => "Accounts",
                'first_name' => $_SESSION['customer_first_name'],
                'last_name'  => $_SESSION['customer_last_name'],
                'accounts' => $accounts
            ]);

            $this->view('customer/account', $data);

        } else {
            // Default data when page is first loaded
            $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);

            $data = [
                'customer_id' => $_SESSION['customer_id'],
                'account_id' => '',
                'account_id_error' => '',
                'success_message' => '',
                'title' => "Accounts",
                'first_name' => $_SESSION['customer_first_name'],
                'last_name'  => $_SESSION['customer_last_name'],
                'accounts' => $accounts
            ];

            $this->view('customer/account', $data);
        }
    }

    public function addAccount(){

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Get the user inputs
            $account_number = trim($_POST['account_number']);
            $account_type   = trim($_POST['account_type']);

            $data = [
                'customer_id'    => $_SESSION['customer_id'],
                'account_number' => $account_number,
                'account_type'   => $account_type,
                'account_number_error' => '',
                'account_type_error'   => '',
                'success_message'      => '',
            ];
            if (empty($account_number)) {
                $data['account_number_error'] = 'Please enter your account number.';
            }

            if (empty($account_type)) {
                $data['account_type_error'] = 'Please select your account type.';
            }

            // If no local errors, call the model
            if (empty($data['account_number_error']) && empty($data['account_type_error'])) {
                $result = $this->customerModel->addAccount($data);

                if ($result['success']) {
                    $_SESSION['flash_success'] = $result['message'];
                    header('Location: ' . URLROOT . '/customer/account');
                    exit;
                } else {
                    $data['account_number_error'] = $result['error'];
                }
            }

            $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);

            $data = array_merge($data, [
                'title' => "Accounts",
                'first_name' => $_SESSION['customer_first_name'],
                'last_name'  => $_SESSION['customer_last_name'],
                'accounts' => $accounts
            ]);

            $this->view('customer/account', $data);

        } else {
            $data = [
                'account_number' => '',
                'account_type'   => '',
                'account_number_error' => '',
                'account_type_error'   => '',
                'success_message'      => '',
            ];

            $this->view('customer/account', $data);
        }
    }


    // --- FUND TRANSFER ---

    public function fund_transfer(){

        if ($_SERVER['REQUEST_METHOD'] === 'POST'){
            
            $from_account = trim($_POST['from_account']);
            $recipient_number = trim($_POST['recipient_number']);
            $recipient_name = trim($_POST['recipient_name']);
            $amount = (float) trim($_POST['amount']);
            $message = trim($_POST['message']);

            $data = [
                'customer_id' => $_SESSION['customer_id'],
                'from_account' => $from_account,
                'recipient_number' => $recipient_number,
                'recipient_name' => $recipient_name,
                'amount' => $amount,
                'message' => $message,
                'from_account_error' => '',
                'recipient_number_error' => '',
                'recipient_name_error' => '',
                'amount_error' => '',
                'message_error' => '',
                'other_error' => '',
            ];

            if (empty($from_account)){
                $data['from_account_error'] = 'Please select your account number.';
            }

            $sender = $this->customerModel->getAccountByNumber($data['from_account']);

            if(!$sender){
                $data['from_account_error'] = 'Please select your own account number.';
            }

            if(empty($recipient_number)){
                $data['recipient_number_error'] = 'Please enter recipient account number.';
            }

            $recipient_validation = $this->customerModel->validateRecipient($data['recipient_number'], $data['recipient_name']);

            if(!$recipient_validation['status']){
                $data = array_merge($data, [
                    'recipient_number_error' => 'Invalid recipient account number or account name',
                    'recipient_name_error' => 'Invalid recipient account number or account name'
                ]);
            }

            $receiver = $this->customerModel->getAccountByNumber($data['recipient_number']);

            if(empty($recipient_name)){
                $data['recipient_name_error'] = 'Please enter recipient name.';
            }

            if(empty($amount)){
                $data['amount_error'] = 'Please enter an amount.';
            }
            $amount_validation = $this->customerModel->validateAmount($data['from_account']);
            $fee = 15.00;
            $total = $data['amount'] + $fee;

            if((float)$amount_validation->balance < $total){
                $data['amount_error'] = 'Insufficient Funds';
            }

            if(strlen($message) >= 100){
                $data['message_error'] = 'Pleaser enter 100 characters only';
            }

            if($data['from_account'] == $data['recipient_number']){
                $data['other_error'] = 'You cannot transfer money to the same account fool.';
            }

            if(empty($data['from_account_error']) && empty($data['recipient_number_error']) && empty($data['recipient_name_error']) && empty($data['amount_error']) && empty($data['message_error']) && empty($data['other_error'])){
                $temp_transaction_ref = 'TXN-PREVIEW-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $remaining_balance = (float)$amount_validation->balance - $total;
                $sender_name = $_SESSION['customer_first_name'] . ' ' . $_SESSION['customer_last_name'] ?? 'Sender Name Unknown';

                $data = array_merge($data, [
                    'temp_transaction_ref' => $temp_transaction_ref,
                    'fee' => $fee,
                    'total_payment' => $total,
                    'remaining_balance' => $remaining_balance,
                    'sender_name' => $sender_name,
                ]);

                $this->view('customer/receipt', $data);
            } else {
                $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);
                $data = array_merge($data, [
                    'title' => 'Fund Transfer',
                    'accounts' => $accounts
                ]);
                $this->view('customer/fund_transfer', $data);
            }
        } else {
             $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);
             $data = [
                'title' => 'Fund Transfer',
                'accounts' => $accounts
            ];
            $this->view('customer/fund_transfer', $data);
        }
    }

    public function receipt(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
         $from_account = trim($_POST['from_account']);
            $recipient_number = trim($_POST['recipient_number']);
            $recipient_name = trim($_POST['recipient_name']);
            $amount = (float) trim($_POST['amount']);
            $message = trim($_POST['message']);

            $data = [
                'customer_id' => $_SESSION['customer_id'],
                'from_account' => $from_account,
                'recipient_number' => $recipient_number,
                'recipient_name' => $recipient_name,
                'amount' => $amount,
                'message' => $message,
                'from_account_error' => '',
                'recipient_number_error' => '',
                'recipient_name_error' => '',
                'amount_error' => '',
                'message_error' => '',
                'other_error' => '',
            ];

            if (empty($from_account)){
                $data['from_account_error'] = 'Please select your account number.';
            }

            $sender = $this->customerModel->getAccountByNumber($data['from_account']);

            if(!$sender){
                $data['from_account_error'] = 'Please select your own account number.';
            }

            if(empty($recipient_number)){
                $data['recipient_number_error'] = 'Please enter recipient account number.';
            }

            $recipient_validation = $this->customerModel->validateRecipient($data['recipient_number'], $data['recipient_name']);

            if(!$recipient_validation['status']){
                $data = array_merge($data, [
                    'recipient_number_error' => 'Invalid recipient account number or account name',
                    'recipient_name_error' => 'Invalid recipient account number or account name'
                ]);
            }

            $receiver = $this->customerModel->getAccountByNumber($data['recipient_number']);

            if(empty($recipient_name)){
                $data['recipient_name_error'] = 'Please enter recipient name.';
            }

            if(empty($amount)){
                $data['amount_error'] = 'Please enter an amount.';
            }
            $amount_validation = $this->customerModel->validateAmount($data['from_account']);
            $fee = 15.00;
            $total = $data['amount'] + $fee;

            if((float)$amount_validation->balance < $total){
                $data['amount_error'] = 'Insufficient Funds';
            }

            if(strlen($message) >= 100){
                $data['message_error'] = 'Pleaser enter 100 characters only';
            }

            if($data['from_account'] == $data['recipient_number']){
                $data['other_error'] = 'You cannot transfer money to the same account fool.';
            }

            if(empty($data['from_account_error']) && empty($data['recipient_number_error']) && empty($data['recipient_name_error']) && empty($data['amount_error']) && empty($data['message_error']) && empty($data['other_error'])){
                $transaction_ref = 'TXN-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));

                $result = $this->customerModel->recordTransaction($transaction_ref, $sender->account_id, $receiver->account_id, $data['amount'], $fee, $data['message']);

                header('Location: ' . URLROOT . '/customer/dashboard');
                exit();
            } else {
                $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);
                $data = array_merge($data, [
                    'title' => 'Fund Transfer',
                    'accounts' => $accounts
                ]);
                $this->view('customer/fund_transfer', $data);
            }
        } else {
             $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);
             $data = [
                'title' => 'Fund Transfer',
                'accounts' => $accounts
            ];
            $this->view('customer/fund_transfer', $data);
        }
    }

    public function transaction_history() {
        if (!isset($_SESSION['customer_id'])) {
            header('Location: ' . URLROOT . '/customer/login');
            exit();
        }

        $customer_id = $_SESSION['customer_id'];
        $limit = 20;

        $current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $current_page = max(1, $current_page);
        $offset = ($current_page - 1) * $limit;

        $filters = [
            'account_id' => isset($_GET['account_id']) ? filter_var($_GET['account_id'], FILTER_SANITIZE_STRING) : 'all',
            'type_name' => isset($_GET['type_name']) ? filter_var($_GET['type_name'], FILTER_SANITIZE_STRING) : 'All',
            'start_date' => isset($_GET['start_date']) ? filter_var($_GET['start_date'], FILTER_SANITIZE_STRING) : '',
            'end_date' => isset($_GET['end_date']) ? filter_var($_GET['end_date'], FILTER_SANITIZE_STRING) : '',
        ];

        $accounts = $this->customerModel->getLinkedAccountsForFilter($customer_id);
        $rawTransactionTypes = $this->customerModel->getTransactionTypes();
        $transactionTypes = array_merge(['All'], array_column($rawTransactionTypes, 'type_name'));

        $transactionData = $this->customerModel->getAllTransactionsByCustomerId(
            $customer_id, 
            $filters,
            $limit, 
            $offset
        );

        $total_transactions = $transactionData['total'];
        $total_pages = ceil($total_transactions / $limit);

        $data = [
            'title' => 'Transaction History',
            'accounts' => $accounts,
            'transactions' => $transactionData['transactions'],
            'filters' => $filters,
            'transaction_types' => $transactionTypes,
            'pagination' => [
                'current_page' => $current_page,
                'total_pages' => $total_pages,
                'total_transactions' => $total_transactions,
                'limit' => $limit,
                'url_query' => http_build_query(array_filter($_GET, fn($key) => $key !== 'page', ARRAY_FILTER_USE_KEY))
            ]
        ];

        $this->view('customer/transaction_history', $data);
    }

    // -- FOR EXPORT --
    public function export_transactions() {
        if (!isset($_SESSION['customer_id'])) {
            header('Location: ' . URLROOT . '/customer/login');
            exit();
        }
        $customer_id = $_SESSION['customer_id'];

        // 1. Get the filter data (account_id, type_name, start_date, end_date)
        $filters = [
            'account_id' => $_GET['account_id'] ?? 'all',
            'type_name'  => $_GET['type_name'] ?? 'All',
            'start_date' => $_GET['start_date'] ?? '',
            'end_date'   => $_GET['end_date'] ?? '',
        ];
        $exportType = strtolower($_GET['type'] ?? 'csv');

        // 2. Call a model method to fetch ALL transactions matching the filters
        // Pass customer_id and filters
        $transactions = $this->customerModel->getAllFilteredTransactions($customer_id, $filters); 

        // 3. Generate and output the file based on $exportType
        if ($exportType === 'csv') {
            $this->generateCSV($transactions);
        } elseif ($exportType === 'pdf') {
            // You would need a PDF library integrated for this (TCPDF seems to be set up)
            $this->generatePDF($transactions);
        } else {
            // Handle invalid type
            header('Location: ' . URLROOT . '/customer/transaction_history');
            exit;
        }
    }
    
    protected function generateCSV($transactions) {
        // Set headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="transactions_' . date('Ymd_His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Open a temporary stream for output
        $output = fopen('php://output', 'w');

        // Define CSV Column Headers (adjust these to match your data structure)
        $headers = ['Date', 'Time', 'Description', 'Reference', 'Account Number', 'Type', 'Amount (PHP)'];
        fputcsv($output, $headers);

        // Write transaction data
        foreach ($transactions as $t) {
            $dateTime = strtotime($t->created_at);
            $amountSign = $t->signed_amount < 0 ? '-' : '+';

            $row = [
                date('Y-m-d', $dateTime),
                date('h:i:s A', $dateTime),
                $t->description,
                $t->transaction_ref,
                $t->account_number,
                $t->transaction_type,
                $amountSign . number_format(abs($t->signed_amount), 2, '.', ''), // Use plain format for export
            ];
            fputcsv($output, $row);
        }

        // Close the stream and terminate script
        fclose($output);
        exit;
    }

    protected function generatePDF($transactions) {
        require_once ROOT_PATH . '/vendor/autoload.php';
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // 2. Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Your Bank');
        $pdf->SetTitle('Transaction History');
        $pdf->SetSubject('Customer Transaction Report');

        // 3. Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // 4. Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // 5. Set default font and add a page
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();

        // --- Start PDF Content Generation ---

        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 15, 'Transaction History Report', 0, 1, 'C');
        
        // Summary
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Report Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'L');
        $pdf->Cell(0, 5, 'Total Transactions: ' . count($transactions), 0, 1, 'L');
        $pdf->Ln(5); // Line break

        // HTML Table for easy styling and structure
        $html = '<table cellspacing="0" cellpadding="5" border="1" style="border-collapse: collapse;">';
        
        // Table Header
        $html .= '<tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td width="15%">Date & Time</td>
                    <td width="30%">Details</td>
                    <td width="25%">Account & Type</td>
                    <td width="15%" align="right">Amount</td>
                    <td width="15%">Status</td>
                </tr>';

        // Table Body
        if (empty($transactions)) {
            $html .= '<tr><td colspan="5" align="center">No transactions found.</td></tr>';
        } else {
            foreach ($transactions as $t) {
                $isDebit = $t->signed_amount < 0;
                $amountSign = $isDebit ? '-' : '+';
                $amountColor = $isDebit ? 'color: #D9534F;' : 'color: #5CB85C;'; // CSS colors for red/green

                // Use the formatCurrency function (must be available in this scope)
                $formattedAmount = $amountSign . number_format(abs($t->signed_amount), 2, '.', '');

                $html .= '<tr>
                            <td>' . date('d M Y, H:i A', strtotime($t->created_at)) . '</td>
                            <td>' . htmlspecialchars($t->description) . '<br><span style="font-size: 8pt;">Ref: ' . htmlspecialchars($t->transaction_ref) . '</span></td>
                            <td>' . htmlspecialchars($t->account_number) . '<br><span style="font-size: 8pt;">' . htmlspecialchars($t->transaction_type) . '</span></td>
                            <td align="right" style="' . $amountColor . ' font-weight: bold;">' . $formattedAmount . '</td>
                            <td>' . ($isDebit ? 'Debit' : 'Credit') . '</td>
                        </tr>';
            }
        }

        $html .= '</table>';

        // Output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');

        // 6. Close and output PDF document
        // I = inline (to browser), D = download
        $pdf->Output('transaction_history_' . date('Ymd') . '.pdf', 'D');
        exit;
    }

    public function referral(){
        if (!isset($_SESSION['customer_id'])) {
            header('Location: ' . URLROOT . '/customer/login');
            exit();
        }

        $data = [
            'title' => 'Referral'
        ];

        $this->view('customer/referral', $data);
    }
}