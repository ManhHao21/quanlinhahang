@extends('layouts.app')

@section('content')
<div class="container mt-4">
        <div class="row">
            {{-- Bên trái: Danh sách món ăn --}}
            <div class="col-md-12">
                @livewire('ingredient-purchase-history')
            </div>
        </div>
    </div>
@endsection
