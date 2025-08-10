@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row">
            {{-- Bên trái: Danh sách món ăn --}}
            <div class="col-md-8">
                @livewire('menu-list')
            </div>


            <div class="col-md-4">
                @livewire('cart')
            </div>

        </div>
    </div>
@endsection
