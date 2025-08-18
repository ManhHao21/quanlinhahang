<div class="p-4 bg-white rounded shadow">
    {{-- Bộ lọc tìm kiếm --}}
    <div class="row g-3 align-items-center mb-4">
        <div class="col-md-6">
            <input type="text" wire:model.defer="search" placeholder="🔍 Tìm kiếm theo mã hóa đơn, bàn..."
                class="form-control">
        </div>

        <div class="col-md-3">
            <input type="date" wire:model.defer="date" class="form-control">
        </div>

        <div class="col-md-auto">
            <button wire:click="applyFilters" class="btn btn-primary shadow-sm">
                Lọc
            </button>
            <a href="{{ route('order.history') }}" class="btn btn-primary shadow-sm">
                Clear </a>
        </div>
    </div>

    {{-- Doanh thu --}}
    <div class="mb-4 p-3 bg-light border rounded">
        <p class="fw-semibold text-dark mb-1">
            📅 Doanh thu ngày:
            <span class="text-success">{{ number_format($totalDaily, 0, ',', '.') }} đ</span>
        </p>
        <p class="fw-semibold text-dark mb-0">
            📆 Doanh thu tháng:
            <span class="text-primary">{{ number_format($totalMonthly, 0, ',', '.') }} đ</span>
        </p>
    </div>

    {{-- Bảng dữ liệu --}}
    <div class="table-responsive border rounded shadow-sm">
        <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="table-primary">
                <tr>
                    <th class="border px-3 py-2">Mã hóa đơn</th>
                    <th class="border px-3 py-2">Bàn</th>
                    <th class="border px-3 py-2">Ngày đặt</th>
                    <th class="border px-3 py-2">Tổng</th>
                    <th class="border px-3 py-2">Tình trạng</th>
                    <th class="border px-3 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="border px-3 py-2">{{ $order->bill_code }}</td>
                        <td class="border px-3 py-2">{{ $order->table_number }}</td>
                        <td class="border px-3 py-2">
                            {{ \Carbon\Carbon::parse($order->date_ordered)->format('d/m/Y') }}
                        </td>
                        <td class="border px-3 py-2">
                            {{ number_format($order->total, 0, ',', '.') }} đ
                        </td>
                        <td class="border px-3 py-2">
                            @if ($order->status === 'success')
                                <span class="bg-green-100 text-green-600 px-2 py-1 rounded text-sm">Đã thanh toán</span>
                            @elseif($order->status === 'pending')
                                <span class="bg-yellow-100 text-yellow-600 px-2 py-1 rounded text-sm">Chờ thanh
                                    toán</span>
                            @else
                                <span class="bg-red-100 text-red-600 px-2 py-1 rounded text-sm">Đã hủy</span>
                            @endif
                        </td>
                        <td class="border px-3 py-2">
                            <button wire:click="viewOrderDetails({{ $order->id }})"
                                class="btn btn-sm btn-info shadow-sm me-1">
                                Xem chi tiết
                            </button>
                            @if ($order->status !== 'success')
                                <button wire:click="markAsPaid({{ $order->id }})"
                                    class="btn btn-sm btn-success shadow-sm me-1">
                                    Đã thanh toán
                                </button>
                            @endif
                            <button wire:click="printInvoice({{ $order->id }})"
                                class="btn btn-sm btn-secondary shadow-sm">
                                In hóa đơn
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted fst-italic py-3">
                            Không có dữ liệu
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Phân trang --}}
    <div class="mt-3 d-flex justify-content-center">
        {{ $orders->links('pagination::bootstrap-5') }}
    </div>


</div>
