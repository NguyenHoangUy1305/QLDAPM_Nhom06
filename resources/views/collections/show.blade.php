@extends('layouts.app')

@section('title', ($currentCat['name'] ?? 'Sản phẩm') . ' - ĐOÀN 6')

@section('content')
<div class="max-w-[1280px] mx-auto px-3 py-5">
    <nav class="text-xs text-gray-500 mb-4 flex items-center gap-2">
        <a href="{{ route('home') }}" class="hover:text-gvn-red"><i class="fa-solid fa-house"></i> Trang chủ</a>
        <span>/</span>
        <span class="text-gray-800 font-semibold">{{ $currentCat['name'] }}</span>
    </nav>

    <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
            <h1 class="text-xl font-extrabold text-gray-900 uppercase">{{ $currentCat['name'] }}</h1>
            <span class="text-xs text-gray-500">Hiển thị {{ count($products) }} sản phẩm</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
            @foreach($products as $product)
                @include('components.product-card', ['product' => $product])
            @endforeach
        </div>
    </div>
</div>
@endsection
