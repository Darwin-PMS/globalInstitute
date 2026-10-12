@extends('layouts.admin')

@section('title', 'Site Settings')

@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            @method('PUT')

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="site_name">Institute Name</label>
                    <input type="text" name="site_name" id="site_name" class="form-input" value="{{ old('site_name', $settings['site_name'] ?? '') }}">
                    @error('site_name')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="site_tagline">Tagline</label>
                    <input type="text" name="site_tagline" id="site_tagline" class="form-input" value="{{ old('site_tagline', $settings['site_tagline'] ?? '') }}">
                    @error('site_tagline')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="contact_phone">Phone</label>
                    <input type="text" name="contact_phone" id="contact_phone" class="form-input" value="{{ old('contact_phone', $settings['contact_phone'] ?? '') }}">
                    @error('contact_phone')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="contact_phone_2">Secondary Phone</label>
                    <input type="text" name="contact_phone_2" id="contact_phone_2" class="form-input" value="{{ old('contact_phone_2', $settings['contact_phone_2'] ?? '') }}">
                    @error('contact_phone_2')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="contact_email">Email</label>
                    <input type="email" name="contact_email" id="contact_email" class="form-input" value="{{ old('contact_email', $settings['contact_email'] ?? '') }}">
                    @error('contact_email')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="whatsapp_number">WhatsApp Number</label>
                    <input type="text" name="whatsapp_number" id="whatsapp_number" class="form-input" value="{{ old('whatsapp_number', $settings['whatsapp_number'] ?? '') }}" placeholder="e.g. 918789961355">
                    @error('whatsapp_number')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="contact_address">Address</label>
                <textarea name="contact_address" id="contact_address" class="form-input" rows="3">{{ old('contact_address', $settings['contact_address'] ?? '') }}</textarea>
                @error('contact_address')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label" for="facebook_url">Facebook URL</label>
                    <input type="url" name="facebook_url" id="facebook_url" class="form-input" value="{{ old('facebook_url', $settings['facebook_url'] ?? '') }}">
                    @error('facebook_url')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="instagram_url">Instagram URL</label>
                    <input type="url" name="instagram_url" id="instagram_url" class="form-input" value="{{ old('instagram_url', $settings['instagram_url'] ?? '') }}">
                    @error('instagram_url')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="youtube_url">YouTube URL</label>
                    <input type="url" name="youtube_url" id="youtube_url" class="form-input" value="{{ old('youtube_url', $settings['youtube_url'] ?? '') }}">
                    @error('youtube_url')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="footer_text">Footer Text</label>
                <input type="text" name="footer_text" id="footer_text" class="form-input" value="{{ old('footer_text', $settings['footer_text'] ?? '') }}">
                @error('footer_text')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="meta_title">Meta Title</label>
                <input type="text" name="meta_title" id="meta_title" class="form-input" value="{{ old('meta_title', $settings['meta_title'] ?? '') }}">
                @error('meta_title')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="meta_description">Meta Description</label>
                <textarea name="meta_description" id="meta_description" class="form-input" rows="2">{{ old('meta_description', $settings['meta_description'] ?? '') }}</textarea>
                @error('meta_description')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div style="display:flex;gap:12px;margin-top:24px;">
                <button type="submit" class="btn btn-primary">
                    <span class="material-symbols-outlined" style="font-size:18px;">save</span> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
