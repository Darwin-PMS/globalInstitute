@extends('layouts.admin')

@section('title', 'Gallery Image')

@section('actions')
    <a href="{{ route('admin.gallery.index') }}" class="btn btn-outline btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Back to Gallery
    </a>
    <a href="{{ route('admin.gallery.edit', $galleryImage->id) }}" class="btn btn-primary btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">edit</span> Edit
    </a>
@endsection

@section('content')
<div class="card" style="max-width:800px;">
    <div class="card-header">
        <span class="material-symbols-outlined" style="color:var(--primary);">image</span>
        <h3>{{ $galleryImage->title }}</h3>
    </div>
    <div class="card-body">
        <div style="margin-bottom:24px;">
            @if($galleryImage->image)
                <img src="{{ asset('storage/' . $galleryImage->image) }}" alt="{{ $galleryImage->alt_text ?? $galleryImage->title }}" loading="eager" fetchpriority="high" decoding="sync" style="width:100%;max-height:500px;object-fit:contain;border-radius:8px;border:1px solid var(--gray-200);background:var(--gray-100);">
            @else
                <div style="width:100%;height:300px;border-radius:8px;background:var(--gray-100);display:flex;align-items:center;justify-content:center;border:1px solid var(--gray-200);">
                    <div style="text-align:center;">
                        <span class="material-symbols-outlined" style="font-size:64px;color:var(--gray-300);">image</span>
                        <p style="color:var(--gray-500);font-size:14px;margin-top:8px;">No image uploaded</p>
                    </div>
                </div>
            @endif
        </div>

        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <td style="padding:10px 0;color:var(--gray-500);font-size:13px;width:140px;">Title</td>
                <td style="padding:10px 0;color:var(--gray-900);font-weight:600;">{{ $galleryImage->title }}</td>
            </tr>
            @if($galleryImage->description)
                <tr>
                    <td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Description</td>
                    <td style="padding:10px 0;color:var(--gray-700);">{{ $galleryImage->description }}</td>
                </tr>
            @endif
            <tr>
                <td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Category</td>
                <td style="padding:10px 0;color:var(--gray-700);">{{ $galleryImage->galleryCategory?->name ?? 'Uncategorized' }}</td>
            </tr>
            <tr>
                <td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Status</td>
                <td style="padding:10px 0;">
                    @if($galleryImage->status === 'published')
                        <span class="badge badge-success">Published</span>
                    @else
                        <span class="badge badge-warning">Draft</span>
                    @endif
                    @if($galleryImage->is_featured)
                        <span class="badge badge-primary" style="margin-left:6px;">Featured</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Sort Order</td>
                <td style="padding:10px 0;color:var(--gray-700);">{{ $galleryImage->sort_order }}</td>
            </tr>
            <tr>
                <td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Created</td>
                <td style="padding:10px 0;color:var(--gray-700);">{{ $galleryImage->created_at->format('d M Y, h:i A') }}</td>
            </tr>
            <tr>
                <td style="padding:10px 0;color:var(--gray-500);font-size:13px;">Updated</td>
                <td style="padding:10px 0;color:var(--gray-700);">{{ $galleryImage->updated_at->format('d M Y, h:i A') }}</td>
            </tr>
        </table>
    </div>
</div>
@endsection
