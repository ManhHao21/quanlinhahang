<div>
    <h4>Lịch sử mua nguyên liệu</h4>

    {{-- Ô chọn ngày --}}
    <div class="mb-3 row">
        <div class="col-md-4">
            <label for="searchDate">Chọn ngày:</label>
            <input type="date" id="searchDate" wire:model.lazy="searchDate" class="form-control"
                value="{{ $searchDate }}">

        </div>
    </div>

    {{-- Thông tin tổng --}}
    <div class="alert alert-info">
        <strong>Tổng ngày {{ \Carbon\Carbon::parse($searchDate)->format('d/m/Y') }}:</strong>
        {{ number_format($totalDay, 0, ',', '.') }} đ <br>
        <strong>Tổng tháng {{ \Carbon\Carbon::parse($searchDate)->format('m/Y') }}:</strong>
        {{ number_format($totalMonth, 0, ',', '.') }} đ
    </div>

    {{-- Bảng dữ liệu --}}
    <div style="max-height: 400px; overflow-y: auto;">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Nguyên liệu</th>
                    <th>Đơn vị</th>
                    <th>Giá nhập</th>
                    <th>Ngày mua</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchases as $index => $purchase)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $purchase->ingredient->name }}</td>
                        <td>{{ $purchase->ingredient->unit }}</td>
                        <td>{{ number_format($purchase->price, 0, ',', '.') }} đ</td>
                        <td>{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d/m/Y') }}</td>
                        <td>
                            <button class="btn btn-danger btn-sm" wire:click="delete({{ $purchase->id }})">Xóa</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Không có dữ liệu cho ngày này</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($purchases->count() > 0)
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Tổng cộng trong ngày:</strong></td>
                        <td colspan="3"><strong>{{ number_format($totalDay, 0, ',', '.') }} đ</strong></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
