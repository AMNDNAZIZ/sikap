<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        }
        
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #667eea;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .btn-home:active {
            transform: translateY(0);
        }
        
        .suggestions {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }
        
        .suggestions h3 {
            font-size: 16px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .suggestions ul {
            list-style: none;
            padding: 0;
        }
        
        .suggestions li {
            margin: 8px 0;
        }
        
        .suggestions a {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .suggestions a:hover {
            color: #764ba2;
            text-decoration: underline;
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
        <div class="error-icon">🔍</div>
        <h1 class="error-code">404</h1>
        <h2 class="error-message">Halaman Tidak Ditemukan</h2>
        <p class="error-description">
            Maaf, halaman yang Anda cari tidak dapat ditemukan.
            Mungkin halaman telah dipindahkan, dihapus, atau URL yang Anda masukkan salah.
        </p>
        <a href="<?= base_url() ?>" class="btn-home">🏠 Kembali ke Beranda</a>
        
        <div class="suggestions">
            <h3>Mungkin Anda mencari:</h3>
            <ul>
                <li><a href="<?= base_url() ?>">🏠 Beranda</a></li>
                <li><a href="<?= base_url('login') ?>">🔐 Login</a></li>
                <li><a href="<?= base_url('siswa/cbt') ?>">📝 Ujian Siswa</a></li>
                <li><a href="<?= base_url('admin') ?>">⚙️ Admin Panel</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
