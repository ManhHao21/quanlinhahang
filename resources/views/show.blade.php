@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                @livewire('menu-list', ['orderId' => $id, 'order' => $order])
            </div>
            <div class="col-md-4">
                @livewire('cart', ['orderId' => $id, 'order' => $order])
            </div>
        </div>
    </div>
@endsection
