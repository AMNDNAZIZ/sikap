<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 30px;
            border: 1px solid #dee2e6;
        }
        .credentials {
            background-color: white;
            padding: 20px;
            border-left: 4px solid #007bff;
            margin: 20px 0;
        }
        .credentials strong {
            color: #007bff;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2><?= esc($schoolName) ?></h2>
            <p>Sistem Informasi Akademik</p>
        </div>
        
        <div class="content">
            <p>Halo <?= esc($name) ?>,</p>
            
            <p>Akun SIAKAD Anda telah dibuat. Berikut adalah informasi login Anda:</p>
            
            <div class="credentials">
                <p><strong>Username:</strong> <?= esc($username) ?></p>
                <p><strong>Password:</strong> <code><?= esc($password) ?></code></p>
            </p>
            </div>
            
            <div class="warning">
                <strong>⚠️ PENTING:</strong>
                <ul>
                    <li>Simpan password ini dengan aman</li>
                    <li>Jangan bagikan password kepada siapapun</li>
                    <li>Segera ganti password setelah login pertama kali</li>
                </ul>
            </div>
            
            <center>
                <a href="<?= esc($loginUrl) ?>" class="button">Login Sekarang</a>
            </center>
            
            <p>Jika Anda mengalami kesulitan login, silakan hubungi administrator sekolah.</p>
        </div>
        
        <div class="footer">
            <p>Email ini dikirim secara otomatis oleh sistem. Mohon tidak membalas email ini.</p>
            <p>&copy; <?= date('Y') ?> <?= esc($schoolName) ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
