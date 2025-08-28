<div>
    <!-- Order Information Section -->
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Thông tin đơn hàng</h5>
        </div>
        <div>
            @if (session()->has('notify'))
                <div class="alert alert-{{ session('notify.type') }} alert-dismissible fade show" role="alert">
                    {{ session('notify.message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        </div>
        @if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Số bàn</label>
                    <input type="text" class="form-control" wire:model="tableNumber">
                    @error('tableNumber')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tên bàn</label>
                    <input type="text" class="form-control" wire:model="tableName">
                    @error('tableName')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
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
                    @php
                        $total = $orders->orderItems->sum(function ($item) {
                            return $item->price * $item->quantity;
                        });
                    @endphp
                    @foreach ($orders->orderItems as $index => $item)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <strong>{{ $item->menu?->name }}</strong>
                                <br>
                                <span class="text-muted">{{ number_format($item->price, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <button wire:click="decreaseQty({{ $item->id }})"
                                    class="btn btn-sm btn-outline-secondary">-</button>
                                <span class="mx-2">
                                    <input type="number" class="form-control form-control-sm text-center"
                                        style="width: 60px;" wire:model.lazy="quantities.{{ $item->id }}">

                                </span>
                                <button wire:click="increaseQty({{ $item->id }})"
                                    class="btn btn-sm btn-outline-secondary">+</button>
                            </div>
                            <div class="text-end" style="min-width: 100px;">
                                <strong>{{ number_format($item->price * $item->quantity, 0, ',', '.') }}đ</strong>
                            </div>
                            <button wire:click="removeItem({{ $item->id }})"
                                class="btn btn-sm btn-outline-danger ms-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                    fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                    <path
                                        d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z" />
                                    <path
                                        d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z" />
                                </svg>
                            </button>
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
            {{-- <button class="btn btn-success w-150 mt-3 py-2 fs-10 {{ $orderId ? 'd-none' : '' }}"
                wire:click="tempOrder({{ $orders->id }})" {{ isset($orders->id) ? '' : 'disabled' }}>
                <i class="fas fa-print me-2"></i> Lưu tạm thời
            </button> --}}
            {{$orders}}
            <button class="btn btn-success w-150 mt-3 py-2 fs-10 {{$orders->status != \App\Models\Order::STATUS_TEMPOLARY ? 'd-none' : ''}}" wire:click="tempOrder({{ $orderId }})"
                {{ isset($orders->id) ? '' : 'disabled' }}>
                <i class="fas fa-print me-2"></i> Lưu tạm thời
            </button>
            <button class="btn btn-success w-150 mt-3 py-2 fs-10 {{$orders->status != \App\Models\Order::STATUS_SUCCESS ? '' : 'd-none'}}" wire:click="paymentSuccess({{ $orderId }})"
                {{ isset($orders->id) ? '' : 'disabled' }}>
                <i class="fas fa-print me-2"></i> Đã thanh toán
            </button>
            <button class="btn btn-success w-150 mt-3 py-2 fs-10" wire:click="printInvoice({{ $orderId }})"
                {{ isset($orders->id) ? '' : 'disabled' }}>
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
            // vẫn hiện toast/alert như trước
            alert(type.toUpperCase() + ': ' + message);
        });

        Livewire.on('reloadPage', () => {
            location.reload(); // reload toàn trang
        });
    });
</script>
