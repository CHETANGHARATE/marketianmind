<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailSubject ?? config('app.name', 'Marketian Mind') }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .header {
            text-align: center;
            padding-bottom: 24px;
        }
        .logo-box {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff;
            font-weight: 900;
            font-size: 16px;
            padding: 8px 14px;
            border-radius: 10px;
            letter-spacing: 0.5px;
            text-decoration: none;
        }
        .brand-name {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin-left: 8px;
            vertical-align: middle;
        }
        .card {
            background-color: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 36px 32px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            line-height: 1.6;
        }
        .card h1, .card h2, .card h3 {
            color: #0f172a;
            margin-top: 0;
        }
        .card p {
            color: #475569;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .btn-box {
            margin: 28px 0;
            text-align: center;
        }
        .btn {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff !important;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 10px;
            letter-spacing: 0.3px;
        }
        .footer {
            text-align: center;
            padding-top: 32px;
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.5;
        }
        .footer a {
            color: #64748b;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Brand Header -->
        <div class="header">
            <a href="{{ route('home') }}" style="text-decoration: none;">
                <span class="logo-box">MM</span>
                <span class="brand-name">Marketian Mind</span>
            </a>
        </div>

        <!-- Main Card Content -->
        <div class="card">
            {!! $bodyHtml !!}
        </div>

        <!-- Compliant Footer with Unsubscribe -->
        <div class="footer">
            <p>
                &copy; {{ date('Y') }} Marketian Mind. Practical Marketing Education for Founders & Business Owners.<br>
                All rights reserved.
            </p>
            <p>
                You are receiving this communication regarding your educational activity or inquiry on Marketian Mind.<br>
                @if(!empty($unsubscribeUrl))
                    <a href="{{ $unsubscribeUrl }}">Unsubscribe from marketing emails</a>
                @endif
            </p>
        </div>
    </div>
</body>
</html>
