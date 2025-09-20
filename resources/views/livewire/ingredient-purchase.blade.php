<div class="container mt-4">
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Thêm phiếu mua nguyên liệu</h5>
        </div>
        <div class="card-body">
            <div>
                <form wire:submit.prevent="save">
                    <div class="mb-3">
                        <label for="ingredient_id">Nguyên liệu</label>
                        <select wire:model="ingredient_id" id="ingredient_id" class="form-control">
                            <option value="">-- Chọn nguyên liệu --</option>
                            @foreach ($ingredients as $ingredient)
                                <option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit }})
                                </option>
                            @endforeach
                        </select>
                        @error('ingredient_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="price">Giá nhập</label>
                        <input type="number" wire:model="price" id="price" class="form-control" step="0.01">
                        @error('price')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="purchase_date">Ngày mua</label>
                        <input type="date" wire:model="purchase_date" id="purchase_date" class="form-control">
                        @error('purchase_date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">Lưu</button>
                </form>
            </div>

        </div>
    </div>
</div>
