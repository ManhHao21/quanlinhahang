<div class="container py-4">
    <div class="row">
        {{-- Cột bên trái: Danh sách món ăn --}}
        <div class="col-md-7">
            <h2 class="mb-4">Danh sách món ăn</h2>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Hình</th>
                        <th>Tên món</th>
                        <th>Giá</th>
                        <th>Mô tả</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($menus as $menu)
                        <tr>
                            <td>
                                @if ($menu->image)
                                    <img src="{{ asset('storage/' . $menu->image) }}" alt="{{ $menu->name }}"
                                        width="60" height="60" style="object-fit: cover;">
                                @else
                                    <span class="text-muted">Không có ảnh</span>
                                @endif
                            </td>
                            <td>{{ $menu->name }}</td>
                            <td>{{ number_format($menu->price, 0, ',', '.') }} đ</td>
                            <td>{{ $menu->description }}</td>
                            <td>
                                <a href="{{ route('menus.edit', $menu->id) }}" class="btn btn-sm btn-primary">Sửa</a>
                                <form action="{{ route('menus.destroy', $menu->id) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Xóa món này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" type="submit">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Chưa có món ăn nào</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Cột bên phải: Form thêm món ăn --}}
        <div class="col-md-5">
            <h2 class="mb-4">Thêm món ăn mới</h2>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('menus.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Tên món ăn</label>
                    <input type="text" name="name" class="form-control" id="name"
                        value="{{ old('name') }}" required>
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Giá</label>
                    <input type="number" step="0.01" name="price" class="form-control" id="price"
                        value="{{ old('price') }}" required>
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">Hình ảnh</label>
                    <input type="file" name="image" class="form-control" id="image" accept="image/*">
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control" id="description" rows="3">{{ old('description') }}</textarea>
                </div>

                <button type="submit" class="btn btn-success">Lưu</button>
                <a href="{{ route('menus.index') }}" class="btn btn-secondary">Hủy</a>
            </form>
        </div>
    </div>
</div>
