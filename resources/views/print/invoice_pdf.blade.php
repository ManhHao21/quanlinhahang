<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            font-style: normal;
            src: url('{{ storage_path('fonts/DejaVuSans.ttf') }}') format('truetype');
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 5px;
            border: 1px solid #ddd;
        }
    </style>
</head>

<body>
    <h2>Hóa đơn #{{ $order['bill_code'] }}</h2>
    <table>
        <thead>
            <tr>
                <th>Tên món</th>
                <th>Đơn giá</th>
                <th>Số lượng</th>
                <th>Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order['order_items'] as $item)
                <tr>
                    <td>{{ $item['menu']['name'] }}</td>
                    <td>{{ number_format($item['price'], 0, ',', '.') }}đ</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}đ</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p><strong>Tổng cộng: {{ number_format($order['total'], 0, ',', '.') }}đ</strong></p>
</body>

</html>
