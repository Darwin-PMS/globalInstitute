<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Global Institute CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0a0d14;
            min-height: 100vh;
            display: flex;
        }
        .login-split {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }
        .login-branding {
            flex: 1;
            background: linear-gradient(135deg, #00081e 0%, #0d1229 50%, #111833 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px;
            position: relative;
            overflow: hidden;
        }
        .login-branding::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 20% 30%, rgba(206,167,0,0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(107,254,156,0.05) 0%, transparent 50%);
        }
        .login-branding::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 40px 40px;
        }
        .brand-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 400px;
        }
        .brand-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            background: rgba(206,167,0,0.12);
            border: 1px solid rgba(206,167,0,0.2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 32px;
        }
        .brand-icon .material-symbols-outlined {
            font-size: 40px;
            font-variation-settings: 'FILL' 1;
            color: #cea700;
        }
        .brand-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 32px;
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
            margin-bottom: 12px;
        }
        .brand-subtitle {
            font-size: 16px;
            color: rgba(255,255,255,0.5);
            line-height: 1.6;
            margin-bottom: 40px;
        }
        .brand-features {
            display: flex;
            flex-direction: column;
            gap: 16px;
            text-align: left;
        }
        .brand-feature {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            transition: all 0.2s;
        }
        .brand-feature:hover {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.1);
        }
        .feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .feature-icon.green { background: rgba(107,254,156,0.1); }
        .feature-icon.green .material-symbols-outlined { color: #6bfe9c; font-size: 20px; }
        .feature-icon.gold { background: rgba(206,167,0,0.1); }
        .feature-icon.gold .material-symbols-outlined { color: #cea700; font-size: 20px; }
        .feature-icon.blue { background: rgba(99,162,255,0.1); }
        .feature-icon.blue .material-symbols-outlined { color: #63a2ff; font-size: 20px; }
        .feature-text h4 {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 2px;
        }
        .feature-text p {
            font-size: 12px;
            color: rgba(255,255,255,0.4);
        }
        .login-form-side {
            width: 480px;
            min-width: 480px;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px;
            position: relative;
        }
        .form-wrapper {
            width: 100%;
            max-width: 360px;
        }
        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .form-header h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: #00081e;
            margin-bottom: 8px;
        }
        .form-header p {
            font-size: 14px;
            color: #6b7280;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap .material-symbols-outlined {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #9ca3af;
            pointer-events: none;
        }
        .input-wrap input {
            width: 100%;
            padding: 12px 14px 12px 44px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: #111827;
            background: #f9fafb;
            transition: all 0.2s;
            outline: none;
        }
        .input-wrap input:focus {
            border-color: #00081e;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0,8,30,0.08);
        }
        .input-wrap input::placeholder { color: #9ca3af; }
        .form-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }
        .remember-check {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .remember-check input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #00081e;
            cursor: pointer;
        }
        .remember-check label {
            font-size: 13px;
            color: #6b7280;
            cursor: pointer;
        }
        .btn-submit {
            width: 100%;
            padding: 13px 24px;
            background: #00081e;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s;
        }
        .btn-submit:hover {
            background: #0d1229;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,8,30,0.3);
        }
        .btn-submit:active { transform: translateY(0); }
        .btn-submit .material-symbols-outlined { font-size: 20px; }
        .form-footer {
            text-align: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #f3f4f6;
        }
        .form-footer a {
            font-size: 13px;
            color: #6b7280;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }
        .form-footer a:hover { color: #00081e; }
        .form-footer a .material-symbols-outlined { font-size: 16px; }
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-error .material-symbols-outlined { font-size: 18px; flex-shrink: 0; }
        @media (max-width: 900px) {
            .login-branding { display: none; }
            .login-form-side { width: 100%; min-width: 0; }
        }
    </style>
</head>
<body>
    <div class="login-split">
        <div class="login-branding">
            <div class="brand-content">
                <div class="brand-icon">
                    <span class="material-symbols-outlined">precision_manufacturing</span>
                </div>
                <h1 class="brand-title">Global Institute<br/>& CAD CAM</h1>
                <p class="brand-subtitle">Youth Skill Development & Research Institute — Admin Content Management System</p>
                <div class="brand-features">
                    <div class="brand-feature">
                        <div class="feature-icon green"><span class="material-symbols-outlined">school</span></div>
                        <div class="feature-text">
                            <h4>Course Management</h4>
                            <p>Create, edit, and manage all institute courses</p>
                        </div>
                    </div>
                    <div class="brand-feature">
                        <div class="feature-icon gold"><span class="material-symbols-outlined">contact_page</span></div>
                        <div class="feature-text">
                            <h4>Enquiry Tracking</h4>
                            <p>Track and convert student enquiries</p>
                        </div>
                    </div>
                    <div class="brand-feature">
                        <div class="feature-icon blue"><span class="material-symbols-outlined">analytics</span></div>
                        <div class="feature-text">
                            <h4>Analytics Dashboard</h4>
                            <p>Monitor website performance and engagement</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="login-form-side">
            <div class="form-wrapper">
                <div class="form-header">
                    <h2>Welcome back</h2>
                    <p>Sign in to your admin account</p>
                </div>

                @if($errors->any())
                    <div class="alert-error">
                        <span class="material-symbols-outlined">error</span>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.submit') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <div class="input-wrap">
                            <span class="material-symbols-outlined">mail</span>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus placeholder="admin@global.com">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-wrap">
                            <span class="material-symbols-outlined">lock</span>
                            <input type="password" name="password" id="password" required placeholder="Enter your password">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="remember-check">
                            <input type="checkbox" name="remember" id="remember">
                            <label for="remember">Remember me</label>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">
                        <span class="material-symbols-outlined">login</span> Sign In
                    </button>
                </form>

                <div class="form-footer">
                    <a href="/" target="_blank">
                        <span class="material-symbols-outlined">open_in_new</span>
                        Back to website
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
