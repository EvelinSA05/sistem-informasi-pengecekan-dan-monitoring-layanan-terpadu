<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            color: #334155;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .header {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.05em;
        }
        .header p {
            color: #a7f3d0;
            margin: 6px 0 0;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .content {
            padding: 40px 30px;
        }
        .content h2 {
            color: #0f172a;
            font-size: 20px;
            margin-top: 0;
        }
        .content p {
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .button-container {
            text-align: center;
            margin: 35px 0;
        }
        .button {
            display: inline-block;
            background-color: #059669;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            box-shadow: 0 4px 6px -1px rgba(5, 150, 105, 0.3);
        }
        .button:hover {
            background-color: #047857;
        }
        .footer {
            background-color: #f0fdf4;
            padding: 24px 30px;
            text-align: center;
            border-top: 1px solid #d1fae5;
        }
        .footer p {
            font-size: 13px;
            color: #64748b;
            margin: 0;
            line-height: 1.5;
        }
        .sub-text {
            font-size: 13px;
            color: #64748b;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="font-size: 18px; line-height: 1.3;">SISTEM INFORMASI PENGECEKAN DAN MONITORING LAYANAN TERPADU</h1>
            <p>Sistem Monitoring &amp; Checking Layanan Terpadu</p>
        </div>
        <div class="content">
            <h2>Halo!</h2>
            <p>Anda menerima email ini karena kami menerima permintaan atur ulang kata sandi (reset password) untuk akun Anda di sistem.</p>
            
            <div class="button-container">
                <a href="{{ $url }}" class="button">Atur Ulang Kata Sandi</a>
            </div>
            
            <p>Tautan atur ulang kata sandi ini akan kedaluwarsa dalam waktu 60 menit.</p>
            <p>Jika Anda merasa tidak pernah meminta pengaturan ulang kata sandi, Anda dapat mengabaikan email ini dan tidak ada tindakan lebih lanjut yang perlu dilakukan.</p>
            
            <p>Salam Hormat,<br><strong>Tim Pengelola Sistem Informasi Pengecekan dan Monitoring Layanan Terpadu</strong></p>

            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 30px 0;">
            
            <p class="sub-text">
                Jika Anda kesulitan mengklik tombol "Atur Ulang Kata Sandi", copy dan paste URL di bawah ini ke browser Anda:<br>
                <a href="{{ $url }}" style="color: #059669;">{{ $url }}</a>
            </p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Sistem Informasi Pengecekan dan Monitoring Layanan Terpadu. Hak Cipta Dilindungi.</p>
        </div>
    </div>
</body>
</html>
