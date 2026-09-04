<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Restricted</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; color: #1a1a2e; }
        .container { text-align: center; max-width: 480px; padding: 40px; }
        .icon { width: 80px; height: 80px; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; }
        .icon svg { width: 40px; height: 40px; color: #dc2626; }
        h1 { font-size: 24px; font-weight: 700; margin-bottom: 12px; }
        p { font-size: 15px; color: #64748b; line-height: 1.6; margin-bottom: 32px; }
        .btn { display: inline-block; padding: 12px 28px; background: #0f172a; color: #fff; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 500; transition: background 0.2s; }
        .btn:hover { background: #1e293b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
            </svg>
        </div>
        <h1>Access Restricted</h1>
        <p>{{ $message ?? "You don't have permission to access this page. Contact your administrator if you believe this is an error." }}</p>
        <a href="{{ url('/admin') }}" class="btn">Go to Dashboard</a>
    </div>
</body>
</html>
