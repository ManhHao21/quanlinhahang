<div>
    {{-- Ô tìm kiếm --}}
    <input type="text" class="form-control mb-3" placeholder="Tìm món..." wire:model.live="search">

    {{-- Danh sách món --}}
    <div class="row g-3 overflow-auto" style="height: calc(100vh - 120px);">
        @foreach ($menuItems as $item)
            @php
                $isChecked = in_array($item->id, $selectedItems ?? []);
            @endphp
            <div class="col-md-4" style="height: 350px">
                <div class="card h-100 selectable-card {{ $isChecked ? 'selected' : '' }}" style="cursor: pointer;"
                    wire:click="toggleItem({{ $item->id }})">

                    <div style="height: 150px; overflow: hidden;">
                        <img src="{{ asset('storage/' . $item->image) }}"
                            class="card-img-top {{ $item->image ? '' : 'd-none' }}" alt="{{ $item->name }}"
                            style="width: 100%; height: 100%; object-fit: cover;">
                    </div>

                    <div class="card-body">
                        <h5 class="card-title">{{ $item->name }}</h5>
                        <p class="card-text text-muted">
                            {{ number_format($item->price, 0, ',', '.') }} đ
                        </p>

                        {{-- Checkbox hiển thị trạng thái --}}
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="{{ $item->id }}"
                                wire:model="selectedItems" @click.stop>
                            <label class="form-check-label">
                                Chọn món
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        @if ($menuItems->isEmpty())
            <div class="text-center text-muted">Không tìm thấy món ăn</div>
        @endif
    </div>
    <style>
        /* Sử dụng biến CSS để dễ dàng tùy chỉnh */
        :root {
            --primary-color: #007bff;
            --border-color: #dee2e6;
            --hover-color: #6c757d;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --selected-shadow: rgba(0, 123, 255, 0.6);
        }

        .selectable-card {
            cursor: pointer;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            /* Sử dụng hàm easing để chuyển động mượt mà */
        }

        .selectable-card:hover {
            border-color: var(--hover-color);
            box-shadow: 0 4px 15px var(--shadow-color);
            transform: translateY(-4px);
            /* Tăng nhẹ độ nhấc lên */
        }

        .selectable-card.selected {
            border-color: var(--primary-color);
            box-shadow: 0 0 20px var(--selected-shadow);
            transform: translateY(-4px);
        }

        /* Ẩn checkbox mặc định để chỉ hiển thị trạng thái qua card */
        .selectable-card .form-check-input {
            display: none;
        }
    </style>
</div>
