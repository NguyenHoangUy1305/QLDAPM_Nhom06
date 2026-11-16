@extends('layouts.app')

@section('title', $product['name'] . ' - ĐOÀN 6')

@section('content')
<div class="max-w-[1280px] mx-auto px-3 py-5 space-y-6">
    <nav class="text-xs text-gray-500 flex items-center gap-2">
        <a href="{{ route('home') }}" class="hover:text-gvn-red"><i class="fa-solid fa-house"></i> Trang chủ</a>
        <span>/</span>
        <a href="{{ route('collections.show', $product['category']) }}" class="hover:text-gvn-red uppercase">{{ $product['category'] }}</a>
        <span>/</span>
        <span class="text-gray-800 font-semibold truncate">{{ $product['name'] }}</span>
    </nav>

    <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Product Image -->
        <div class="lg:col-span-5">
            <div class="aspect-square rounded-xl overflow-hidden border border-gray-100 bg-gray-50">
                <img src="{{ asset($product['image']) }}" alt="{{ $product['name'] }}" class="w-full h-full object-cover">
            </div>
        </div>

        <!-- Product Info -->
        <div class="lg:col-span-7 flex flex-col">
            <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900 mb-2">{{ $product['name'] }}</h1>
            <div class="flex items-center gap-3 text-xs text-gray-500 mb-4">
                <span>Thương hiệu: <strong class="text-gvn-red">{{ $product['brand'] }}</strong></span>
                <span>|</span>
                <span class="text-emerald-600 font-semibold"><i class="fa-solid fa-circle-check"></i> Còn hàng</span>
            </div>

            <div class="bg-gray-50 rounded-xl p-4 mb-4 flex items-baseline gap-3">
                <span class="text-2xl sm:text-3xl font-extrabold text-gvn-red">{{ number_format($product['price'], 0, ',', '.') }}đ</span>
                @if(!empty($product['old_price']))
                    <span class="text-sm text-gray-400 line-through">{{ number_format($product['old_price'], 0, ',', '.') }}đ</span>
                    <span class="bg-gvn-red text-white text-xs font-bold px-2 py-0.5 rounded">-{{ $product['discount'] }}%</span>
                @endif
            </div>

            @if(!empty($product['specs']))
                <div class="mb-4">
                    <h3 class="text-xs font-bold uppercase text-gray-700 mb-2">Thông số nổi bật:</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($product['specs'] as $spec)
                            <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-1 rounded-md">{{ $spec }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(!empty($product['promo']))
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-3.5 mb-5 text-xs text-blue-800">
                    <div class="font-bold mb-1 flex items-center gap-1.5">
                        <i class="fa-solid fa-gift text-blue-600"></i> Quà tặng & Khuyến mãi:
                    </div>
                    <p>{{ $product['promo'] }}</p>
                </div>
            @endif

            <div class="mt-auto flex flex-col sm:flex-row gap-3">
                <a href="{{ route('cart.index') }}" class="flex-1 bg-gvn-red hover:bg-gvn-redHover text-white font-extrabold py-3.5 px-6 rounded-xl text-center uppercase tracking-wide transition shadow-md">
                    MUA NGAY
                    <div class="text-[11px] font-normal opacity-90">Giao tận nơi hoặc nhận tại Showroom</div>
                </a>
                <a href="{{ route('cart.index') }}" class="bg-white border-2 border-gvn-red text-gvn-red hover:bg-red-50 font-bold py-3.5 px-6 rounded-xl text-center flex items-center justify-center gap-2 transition">
                    <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ
                </a>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
        <h2 class="text-lg font-extrabold text-gray-900 uppercase mb-4">Sản phẩm tương tự</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach($related as $item)
                @include('components.product-card', ['product' => $item])
            @endforeach
        </div>
    </div>
</div>
@endsection
