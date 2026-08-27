@extends('layouts.admin')

@section('title', 'Edit Student')

@section('content')
<form method="POST" action="{{ route('admin.students.update', $student->id) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">person</span>
            <h3>Personal Information</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="name">Full Name *</label>
                    <input type="text" name="name" id="name" class="form-input" value="{{ old('name', $student->name) }}" required>
                    @error('name') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="father_name">Father's Name</label>
                    <input type="text" name="father_name" id="father_name" class="form-input" value="{{ old('father_name', $student->father_name) }}">
                    @error('father_name') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="mother_name">Mother's Name</label>
                    <input type="text" name="mother_name" id="mother_name" class="form-input" value="{{ old('mother_name', $student->mother_name) }}">
                    @error('mother_name') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="dob">Date of Birth</label>
                    <input type="date" name="dob" id="dob" class="form-input" value="{{ old('dob', $student->dob ? \Carbon\Carbon::parse($student->dob)->format('Y-m-d') : '') }}">
                    @error('dob') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label" for="gender">Gender</label>
                    <select name="gender" id="gender" class="form-input">
                        <option value="">Select</option>
                        <option value="male" {{ old('gender', $student->gender) === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender', $student->gender) === 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ old('gender', $student->gender) === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">Mobile *</label>
                    <input type="text" name="phone" id="phone" class="form-input" value="{{ old('phone', $student->phone) }}" required>
                    @error('phone') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="whatsapp">WhatsApp</label>
                    <input type="text" name="whatsapp" id="whatsapp" class="form-input" value="{{ old('whatsapp', $student->whatsapp) }}">
                    @error('whatsapp') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" name="email" id="email" class="form-input" value="{{ old('email', $student->email) }}">
                @error('email') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">home</span>
            <h3>Address</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label" for="address">Address</label>
                <textarea name="address" id="address" class="form-input" rows="2">{{ old('address', $student->address) }}</textarea>
                @error('address') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label" for="city">City</label>
                    <input type="text" name="city" id="city" class="form-input" value="{{ old('city', $student->city) }}">
                    @error('city') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="state">State</label>
                    <input type="text" name="state" id="state" class="form-input" value="{{ old('state', $student->state) }}">
                    @error('state') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="pin">PIN Code</label>
                    <input type="text" name="pin" id="pin" class="form-input" value="{{ old('pin', $student->pin) }}">
                    @error('pin') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">school</span>
            <h3>Education</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="qualification">Qualification</label>
                    <input type="text" name="qualification" id="qualification" class="form-input" value="{{ old('qualification', $student->qualification) }}">
                    @error('qualification') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="institution">Institution</label>
                    <input type="text" name="institution" id="institution" class="form-input" value="{{ old('institution', $student->institution) }}">
                    @error('institution') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="passing_year">Passing Year</label>
                    <input type="text" name="passing_year" id="passing_year" class="form-input" value="{{ old('passing_year', $student->passing_year) }}">
                    @error('passing_year') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="experience">Experience</label>
                    <input type="text" name="experience" id="experience" class="form-input" value="{{ old('experience', $student->experience) }}">
                    @error('experience') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">emergency</span>
            <h3>Emergency Contact</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label" for="emergency_name">Contact Name</label>
                    <input type="text" name="emergency_name" id="emergency_name" class="form-input" value="{{ old('emergency_name', $student->emergency_name) }}">
                    @error('emergency_name') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="emergency_relationship">Relationship</label>
                    <input type="text" name="emergency_relationship" id="emergency_relationship" class="form-input" value="{{ old('emergency_relationship', $student->emergency_relationship) }}">
                    @error('emergency_relationship') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="emergency_phone">Contact Number</label>
                    <input type="text" name="emergency_phone" id="emergency_phone" class="form-input" value="{{ old('emergency_phone', $student->emergency_phone) }}">
                    @error('emergency_phone') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">upload_file</span>
            <h3>Documents</h3>
        </div>
        <div class="card-body">
            @if($student->photo)
                <div style="margin-bottom:16px;">
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80'%3E%3Crect fill='%23f1f3f5' width='80' height='80'/%3E%3C/svg%3E" data-src="{{ asset('storage/'.$student->photo) }}" alt="{{ $student->name }}" loading="lazy" decoding="async" class="lazy-img avatar" style="width:80px;height:80px;object-fit:cover;border-radius:50%;border:2px solid var(--gray-200);">
                </div>
            @endif
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label" for="photo">Photo</label>
                    <input type="file" name="photo" id="photo" class="form-input" accept="image/*">
                    @error('photo') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="id_proof">ID Proof</label>
                    <input type="file" name="id_proof" id="id_proof" class="form-input" accept="image/*,.pdf">
                    @error('id_proof') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="qualification_proof">Qualification Proof</label>
                    <input type="file" name="qualification_proof" id="qualification_proof" class="form-input" accept="image/*,.pdf">
                    @error('qualification_proof') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">save</span> Update Student
        </button>
        <a href="{{ route('admin.students.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>
@endsection
