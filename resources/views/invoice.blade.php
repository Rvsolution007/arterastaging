<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $transaction->id }}</title>
    <style>
        body { font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif; color: #333; margin: 0; padding: 0; background: #f8f9fa; }
        .invoice-box { max-width: 800px; margin: 40px auto; padding: 40px; border: 1px solid #eee; background: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; border-bottom: 2px solid #0052cc; padding-bottom: 20px; }
        .header .logo { font-size: 32px; font-weight: 900; color: #0052cc; letter-spacing: -1px; }
        .header .title { text-align: right; }
        .header .title h1 { margin: 0; font-size: 36px; color: #333; text-transform: uppercase; }
        .header .title p { margin: 5px 0 0; color: #666; font-size: 14px; }
        .details { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .details .bill-to h3 { margin: 0 0 10px; font-size: 16px; color: #666; text-transform: uppercase; letter-spacing: 1px; }
        .details .bill-to p { margin: 0; font-size: 16px; font-weight: 600; }
        .details .invoice-info text-align: right; }
        .details .invoice-info p { margin: 5px 0; font-size: 15px; }
        .details .invoice-info span { font-weight: 600; color: #333; }
        .table-wrap { margin-bottom: 40px; }
        table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; }
        table th { padding: 12px 15px; background: #f4f7f6; border-bottom: 2px solid #ddd; font-weight: 700; color: #333; text-transform: uppercase; font-size: 13px; letter-spacing: 1px; }
        table td { padding: 15px; border-bottom: 1px solid #eee; font-size: 15px; }
        table td.amount { text-align: right; font-weight: 600; }
        table th.amount { text-align: right; }
        .total-section { display: flex; justify-content: flex-end; }
        .total-box { min-width: 250px; background: #f9f9f9; padding: 20px; border-radius: 8px; }
        .total-line { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 15px; }
        .total-line.final { font-size: 20px; font-weight: 800; color: #0052cc; margin-top: 10px; padding-top: 10px; border-top: 2px solid #eee; }
        .footer { text-align: center; color: #777; font-size: 13px; margin-top: 50px; border-top: 1px solid #eee; padding-top: 20px; }
        @media print {
            body { background: #fff; }
            .invoice-box { border: none; box-shadow: none; margin: 0; padding: 20px; }
            .print-btn { display: none !important; }
        }
        .print-btn { display: block; width: 200px; margin: 20px auto; padding: 12px; background: #0052cc; color: #fff; text-align: center; text-decoration: none; border-radius: 6px; font-weight: bold; cursor: pointer; border: none; }
    </style>
</head>
<body>

    <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>

    <div class="invoice-box">
        <div class="header">
            <div class="logo">Artera</div>
            <div class="title">
                <h1>Invoice</h1>
                <p>Date: {{ date('M d, Y', strtotime($transaction->date)) }}</p>
            </div>
        </div>

        <div class="details">
            <div class="bill-to">
                <h3>Billed To:</h3>
                <p>{{ $transaction->user ? $transaction->user->name : 'Customer' }}</p>
                <p style="font-weight: 400; color: #666; font-size: 14px;">{{ $transaction->user ? $transaction->user->email : '' }}</p>
            </div>
            <div class="invoice-info">
                <p>Invoice No: <span>#{{ str_pad($transaction->id, 6, '0', STR_PAD_LEFT) }}</span></p>
                <p>Status: <span style="color: {{ strtolower($transaction->status) == 'success' || strtolower($transaction->status) == 'completed' ? '#28a745' : '#ffc107' }}; text-transform: capitalize;">{{ $transaction->status ?? 'Success' }}</span></p>
                <p>Payment Method: <span style="text-transform: capitalize;">{{ $transaction->payment_type }}</span></p>
                <p>Transaction ID: <span>{{ $transaction->payment_id }}</span></p>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="amount">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>{{ $transaction->subscription ? $transaction->subscription->plan_name : 'Subscription Plan' }}</strong><br>
                            <small style="color: #666;">Plan Subscription Fee</small>
                        </td>
                        <td class="amount">{{ App\Models\AppSetting::getAppSetting('currency') }}{{ number_format($transaction->total_paid, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="total-section">
            <div class="total-box">
                <div class="total-line">
                    <span>Subtotal:</span>
                    <span>{{ App\Models\AppSetting::getAppSetting('currency') }}{{ number_format($transaction->total_paid, 2) }}</span>
                </div>
                <div class="total-line final">
                    <span>Total Paid:</span>
                    <span>{{ App\Models\AppSetting::getAppSetting('currency') }}{{ number_format($transaction->total_paid, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Thank you for choosing Artera!</p>
            <p>If you have any questions concerning this invoice, contact our support team.</p>
        </div>
    </div>

</body>
</html>
