<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        /* Sử dụng font hỗ trợ tiếng Việt */
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            width: 80mm;
            /* Kích thước phù hợp máy in nhiệt */
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 3px;
            border-bottom: 1px dashed #000;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="text-center">
        <h3>HÓA ĐƠN #{{ $order->bill_code }}</h3>
        <p>Ngày: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tên món</th>
                <th class="text-right">Đơn giá</th>
                <th>SL</th>
                <th class="text-right">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->orderItems as $item)
                <tr>
                    <td>{{ $item['menu']['name'] }}</td>
                    <td class="text-right">{{ number_format($item['price'], 0, ',', '.') }}đ</td>
                    <td class="text-center">{{ $item['quantity'] }}</td>
                    <td class="text-right">{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}đ</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="text-right">
        <p><strong>Tổng cộng: {{ number_format($order['total'], 0, ',', '.') }}đ</strong></p>
    </div>

    <div class="text-center">
        <p>Cảm ơn quý khách!</p>
    </div>
</body>

</html>
