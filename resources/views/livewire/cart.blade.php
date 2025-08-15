<div>
    <!-- Order Information Section -->
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Thông tin đơn hàng</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Số bàn</label>
                    <input type="text" class="form-control" wire:model="tableNumber"
                        wire:change.debounce="saveOrderInfo">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tên bàn</label>
                    <input type="text" class="form-control" wire:model="tableName"
                        wire:change.debounce="saveOrderInfo">
                </div>
            </div>
        </div>
    </div>

    <!-- Order Items Section -->
    @if ($orders->count() > 0)
        <div class="card ">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Chi tiết đơn hàng #{{ $orders->bill_code }}</h5>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <ul class="list-group mb-3">
                    @foreach ($orders->orderItems as $index => $item)
                        @php
                        @endphp
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <strong>{{ $item->menu?->name }}</strong>
                                <br>
                                <span class="text-muted">{{ number_format($item->price, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <button wire:click="decreaseQty({{ $item->id }})"
                                    class="btn btn-sm btn-outline-secondary">-</button>
                                <span class="mx-2">{{ $item->quantity }}</span>
                                <button wire:click="increaseQty({{ $item->id }})"
                                    class="btn btn-sm btn-outline-secondary">+</button>
                            </div>
                            <div class="text-end" style="min-width: 100px;">
                                <strong>{{ number_format($item->price * $item->quantity, 0, ',', '.') }}đ</strong>
                            </div>
                        </li>
                    @endforeach
                </ul>


            </div>
        </div>
        <div class="d-flex justify-content-between fw-bold fs-5 border-top pt-2">
            <span>Tổng cộng:</span>
            <span class="text-primary">{{ number_format($total, 0, ',', '.') }}đ</span>
        </div>
        <div class="d-flex" style="gap: 10px;">
            <button class="btn btn-success w-150 mt-3 py-2 fs-10" wire:click="printInvoice">
                <i class="fas fa-print me-2"></i> Lưu tạm thời
            </button>
            <button class="btn btn-success w-150 mt-3 py-2 fs-10" wire:click="printInvoice">
                <i class="fas fa-print me-2"></i> Đã thanh toán
            </button>
            <button class="btn btn-success w-150 mt-3 py-2 fs-10" wire:click="printInvoice">
                <i class="fas fa-print me-2"></i> In hóa đơn
            </button>

        </div>
    @else
        <div class="alert alert-info">Không có sản phẩm nào trong đơn hàng</div>
    @endif

</div>
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('notify', ({
            type,
            message
        }) => {
            alert(type.toUpperCase() + ': ' + message);
        });
    });
</script>
