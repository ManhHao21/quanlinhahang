@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                @livewire('menu-list', ['orderId' => $id])
            </div>
            <div class="col-md-4">
                @livewire('cart', ['orderId' => $id])
            </div>
        </div>
    </div>
@endsection
