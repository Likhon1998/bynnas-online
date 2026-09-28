@extends('website.layout')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <p class="text-sm text-slate-400 mb-2"><a href="{{ route('home') }}" class="hover:text-slate-700">Home</a> / Page</p>
    <h1 class="text-3xl font-black text-slate-900 tracking-tight">{{ $page->title }}</h1>
    @if($page->excerpt)
        <p class="mt-3 text-slate-500">{{ $page->excerpt }}</p>
    @endif
    <div class="prose prose-slate mt-8 max-w-none whitespace-pre-line text-slate-700 leading-relaxed">{{ $page->body }}</div>
</div>
@endsection
