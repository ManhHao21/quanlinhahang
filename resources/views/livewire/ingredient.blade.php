<div class="container mt-4">
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Thêm nguyên liệu mới</h5>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="save" novalidate>
                <div class="mb-3">
                    <label for="name" class="form-label">Tên nguyên liệu</label>
                    <input type="text" id="name" wire:model="name" 
                           class="form-control @error('name') is-invalid @enderror">
                    @error('name') 
                        <div class="invalid-feedback">{{ $message }}</div> 
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="unit" class="form-label">Đơn vị tính</label>
                    <input type="text" id="unit" wire:model="unit" 
                           class="form-control @error('unit') is-invalid @enderror">
                    @error('unit') 
                        <div class="invalid-feedback">{{ $message }}</div> 
                    @enderror
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Thêm nguyên liệu
                </button>
            </form>
        </div>
    </div>
</div>
