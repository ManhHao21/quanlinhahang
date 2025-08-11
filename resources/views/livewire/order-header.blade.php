<div class="container-fluid bg-light border-bottom">
    <div class="d-flex align-items-center justify-content-between py-2">
        <div class="d-flex align-items-center">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" style="height: 40px;">
            <span class="ms-2 fw-bold fs-5">Tên Quán</span>
        </div>

        {{-- 3 nút bên phải --}}
        <div class="d-flex gap-2">
            <a href="/create/product" class="btn btn-primary">Thêm món</a>
            <button class="btn btn-primary">Lịch sử</button>
            <button class="btn btn-primary">Cấu hình</button>
        </div>
    </div>
</div>