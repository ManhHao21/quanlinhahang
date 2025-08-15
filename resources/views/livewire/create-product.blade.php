<div class="container py-4">
    <div class="row">
        {{-- Cột bên trái: Danh sách món ăn --}}
        <div class="col-md-7">
            <h2 class="mb-4">Danh sách món ăn</h2>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <input type="text" class="form-control mb-3" placeholder="Tìm món..." wire:model.live="search">
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
                                <button type="button" wire:click="edit({{ $menu->id }})"
                                    class="btn btn-sm btn-primary">
                                    Sửa
                                </button>
                                <button wire:click="destroy({{ $menu->id }})"
                                    onclick="return confirm('Xóa món này?')" class="btn btn-sm btn-danger">
                                    Xóa
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Chưa có món ăn nào</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $menus->links() }}
        </div>

        {{-- Cột bên phải: Form thêm món ăn --}}
        <div class="col-md-5">
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
            <h2 class="mb-4">{{ $menuId ? 'Cập nhật món ăn' : 'Thêm món ăn mới' }}</h2>
            <form wire:submit.prevent="save" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="name" class="form-label">Tên món</label>
                    <input type="text" id="name" class="form-control" wire:model="name">
                    @error('name')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Giá</label>
                    <input type="number" id="price" class="form-control" wire:model="price">
                    @error('price')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Mô tả</label>
                    <textarea id="description" class="form-control" wire:model="description"></textarea>
                    @error('description')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">Ảnh</label>
                    <input type="file" id="image" class="form-control" wire:model="image">
                    @error('image')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Preview ảnh khi edit --}}
                @if ($image)
                    <div class="mt-2">
                        <img src="{{ $image->temporaryUrl() }}" class="img-thumbnail" style="max-height: 200px;">
                    </div>
                @elseif($imagePath)
                    <div class="mt-2">
                        <img src="{{ asset('storage/' . $imagePath) }}" class="img-thumbnail"
                            style="max-height: 200px;">
                    </div>
                @endif

                <button type="submit" class="btn btn-success">
                    {{ $menuId ? 'Cập nhật' : 'Lưu' }}
                </button>
                @if ($menuId)
                    <button type="button" wire:click="resetForm" class="btn btn-secondary">Hủy</button>
                @endif
            </form>
        </div>
    </div>
</div>
