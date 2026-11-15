@extends('layouts.app')

@section('title', 'Giỏ hàng - ĐOÀN 6')

@section('content')
<div class="max-w-[960px] mx-auto px-3 py-6">
    <h1 class="text-xl font-extrabold text-gray-900 uppercase mb-4">Giỏ hàng của bạn</h1>

    <div class="bg-white rounded-2xl border border-gray-200 p-4 sm:p-6 shadow-sm space-y-4">
        @php $total = 0; @endphp
        @foreach($cartItems as $item)
            @php
                $p = $item['product'];
                $sub = $p['price'] * $item['qty'];
                $total += $sub;
            @endphp
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <img src="{{ asset($p['image']) }}" alt="{{ $p['name'] }}" class="w-20 h-20 object-cover rounded-lg border border-gray-200">
                    <div>
                        <a href="{{ route('products.show', $p['slug']) }}" class="text-sm font-bold text-gray-900 hover:text-gvn-red line-clamp-2">{{ $p['name'] }}</a>
                        <div class="text-xs text-gray-400 line-through mt-1">{{ number_format($p['old_price'], 0, ',', '.') }}đ</div>
                        <div class="text-sm font-extrabold text-gvn-red">{{ number_format($p['price'], 0, ',', '.') }}đ</div>
                    </div>
                </div>
                <div class="flex items-center gap-4 self-end sm:self-center">
                    <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden text-xs font-bold">
                        <button class="px-2.5 py-1.5 bg-gray-50 hover:bg-gray-200">-</button>
                        <span class="px-3 py-1.5">{{ $item['qty'] }}</span>
                        <button class="px-2.5 py-1.5 bg-gray-50 hover:bg-gray-200">+</button>
                    </div>
                    <button class="text-gray-400 hover:text-gvn-red text-sm" title="Xóa"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        @endforeach

        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
            <div>
                <span class="text-sm text-gray-600">Tổng tiền tạm tính:</span>
                <span class="text-2xl font-extrabold text-gvn-red ml-2">{{ number_format($total, 0, ',', '.') }}đ</span>
            </div>
            <button onclick="alert('Đặt hàng thành công! Cảm ơn bạn đã mua sắm tại ĐOÀN 6.')" class="w-full sm:w-auto bg-gvn-red hover:bg-gvn-redHover text-white font-extrabold px-8 py-3.5 rounded-xl uppercase tracking-wide transition shadow-md">
                ĐẶT HÀNG NGAY
            </button>
        </div>
    </div>
</div>
@endsection
