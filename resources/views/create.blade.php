@extends('layouts.app')

@section('content')
    <h1>Thêm nguyên liệu mới</h1>
     <div class="container mt-4">
        <div class="row">
            {{-- Bên trái: Danh sách món ăn --}}
            <div class="col-md-8">
                    @livewire('ingredient')
            </div>


            <div class="col-md-4">
                @livewire('ingredient-purchase')
            </div>

        </div>
    </div>
@endsection
