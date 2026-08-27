@extends('layouts.admin')

@section('title', 'Site Settings')

@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            @method('PUT')

            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="institute_name">Institute Name</label>
                    <input type="text" name="institute_name" id="institute_name" class="form-input" value="{{ old('institute_name', $settings['institute_name'] ?? '') }}">
                    @error('institute_name')
                        <p style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="tagline">Tagline</label>
                    <input type="text" name="tagline" id="tagline" class="form-input" value="{{ old('tagline', $settings['tagline'] ?? '') }}">
                    @error('tagline')
                        <p style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="phone">Phone</label>
                    <input type="text" name="phone" id="phone" class="form-input" value="{{ old('phone', $settings['phone'] ?? '') }}">
                    @error('phone')
                        <p style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" name="email" id="email" class="form-input" value="{{ old('email', $settings['email'] ?? '') }}">
                    @error('email')
                        <p style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Address</label>
                <textarea name="address" id="address" class="form-input" rows="3">{{ old('address', $settings['address'] ?? '') }}</textarea>
                @error('address')
                    <p style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="whatsapp">WhatsApp Number</label>
                <input type="text" name="whatsapp" id="whatsapp" class="form-input" value="{{ old('whatsapp', $settings['whatsapp'] ?? '') }}">
                @error('whatsapp')
                    <p style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid-3">
                <div class="form-group">
                    <label class="form-label" for="facebook">Facebook URL</label>
                    <input type="url" name="facebook" id="facebook" class="form-input" value="{{ old('facebook', $settings['facebook'] ?? '') }}">
                    @error('facebook')
                        <p style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="instagram">Instagram URL</label>
                    <input type="url" name="instagram" id="instagram" class="form-input" value="{{ old('instagram', $settings['instagram'] ?? '') }}">
                    @error('instagram')
                        <p style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="youtube">YouTube URL</label>
                    <input type="url" name="youtube" id="youtube" class="form-input" value="{{ old('youtube', $settings['youtube'] ?? '') }}">
                    @error('youtube')
                        <p style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>
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
