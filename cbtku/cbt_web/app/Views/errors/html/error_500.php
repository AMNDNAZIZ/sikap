<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Kesalahan Server</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }
        
        .error-container {
            background: white;
            padding: 60px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
            width: 100%;
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #f5576c;
            margin: 0;
            line-height: 1;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .error-message {
            font-size: 24px;
            margin: 20px 0;
            color: #555;
            font-weight: 600;
        }
        
        .error-description {
            color: #777;
            margin-bottom: 30px;
            line-height: 1.6;
            font-size: 16px;
        }
        
        .btn-home {
            display: inline-block;
            padding: 14px 35px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.4);
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 87, 108, 0.6);
        }
        
        .btn-home:active {
            transform: translateY(0);
        }
        
        .error-details {
            margin-top: 30px;
            padding: 20px;
            background: #fff3f3;
            border-radius: 10px;
            border-left: 4px solid #f5576c;
        }
        
        .error-details h3 {
            font-size: 16px;
            color: #f5576c;
            margin-bottom: 10px;
        }
        
        .error-details p {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
        }
        
        .support-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #999;
        }
        
        @media (max-width: 600px) {
            .error-container {
                padding: 40px 30px;
            }
            
            .error-code {
                font-size: 80px;
            }
            
            .error-message {
                font-size: 20px;
            }
            
            .error-description {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1 class="error-code">500</h1>
        <h2 class="error-message">Kesalahan Server</h2>
        <p class="error-description">
            Maaf, terjadi kesalahan pada server kami.
            Tim teknis kami telah diberitahu dan sedang memperbaikinya.
        </p>
        <a href="<?= base_url() ?>" class="btn-home">🏠 Kembali ke Beranda</a>
        
        <div class="error-details">
            <h3>Apa yang bisa Anda lakukan?</h3>
            <p>
                • Tunggu beberapa saat dan coba lagi<br>
                • Refresh halaman ini<br>
                • Kembali ke halaman sebelumnya<br>
                • Hubungi administrator jika masalah berlanjut
            </p>
        </div>
        
        <div class="support-info">
            Error ID: <?= uniqid('ERR-') ?><br>
            Waktu: <?= date('Y-m-d H:i:s') ?>
        </div>
    </div>
</body>
</html>
