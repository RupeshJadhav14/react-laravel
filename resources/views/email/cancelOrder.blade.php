<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Cancel Order Email</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; font-size:16px;">

    @if ($mailData['userType'] == 'customer')
        <h1>Order Cancelled !!</h1>
        <h2>Your Order ORD-ID-{{ $mailData['order']->id }} has been cancelled successfully </h2>
    @else
        <h1>Order has been cancelled by the user !!</h1>
        <h2>Order Id Is : #{{ $mailData['order']->id }}</h2>
    @endif    

    <h2>Shipping Address</h2>
    <address>
        <strong>{{ $mailData['order']->first_name .' '. $mailData['order']->last_name }}</strong><br>
        {{ $mailData['order']->address }}<br>
        {{ $mailData['order']->city }}, {{ $mailData['order']->zip }},{{ getCountryInfo($mailData['order']->country_id)->name }}<br>
        Phone: {{ $mailData['order']->mobile }}<br>
        Email: {{ $mailData['order']->email }}
    </address>

    <h2>Products</h2>

    <table cellpadding="3" cellspacing="3" border="0" width="700">
        <thead>
            <tr style="background: #CCC;">
                <th>Product</th>
                <th>Price</th>
                <th>Qty</th>                                        
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($mailData['order']->items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>&#8377;{{ number_format($item->price,2) }}</td>
                    <td>{{ $item->qty }}</td>
                    <td>&#8377;{{ number_format($item->total,2) }}</td>
                </tr>
            @endforeach
            <tr>
                <th colspan="3" align="right">Subtotal:</th>
                <td>&#8377;{{ number_format($mailData['order']->subtotal,2) }}</td>
            </tr>
            <tr>
                <th colspan="3" align="right">Discount{{ (!empty($mailData['order']->coupon_code)) ? '(' . $mailData['order']->coupon_code . ')' : '' }}:</th>
                <td>&#8377;{{ number_format($mailData['order']->discount,2) }}</td>
            </tr>

            <tr>
                <th colspan="3" align="right">Shipping:</th>
                <td>&#8377;{{ number_format($mailData['order']->shipping,2) }}</td>
            </tr>
            <tr>
                <th colspan="3" align="right">Grand Total:</th>
                <td>&#8377;{{ number_format($mailData['order']->grand_total,2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>