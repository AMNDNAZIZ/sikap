<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Fix License Tool</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .success {
            color: #28a745;
            background: #d4edda;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #28a745;
            margin: 20px 0;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #dc3545;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #0056b3;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗑️ Delete Fix License Tool</h1>
        <hr>

        <?php if (!empty($deleted)): ?>
            <div class="success">
                <h3>✅ File Berhasil Dihapus!</h3>
                <ul>
                    <?php foreach ($deleted as $file): ?>
                        <li><?= esc($file) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($failed)): ?>
            <div class="error">
                <h3>❌ Gagal Menghapus File!</h3>
                <p>Silakan hapus file berikut secara manual via FTP/cPanel:</p>
                <ul>
                    <?php foreach ($failed as $file): ?>
                        <li><code><?= esc($file) ?></code></li>
                    <?php endforeach; ?>
                </ul>
                <p><strong>Lokasi:</strong></p>
                <ul>
                    <li><code>app/Controllers/FixLicense.php</code></li>
                    <li><code>app/Views/fix_license_view.php</code></li>
                    <li><code>app/Views/fix_license_delete.php</code></li>
                </ul>
            </div>
        <?php endif; ?>

        <hr>
        <a href="<?= base_url() ?>" class="btn">← Kembali ke Aplikasi</a>

        <p style="text-align: center; color: #666; margin-top: 30px;">
            <small>License Hash Update Tool v1.0 | CBT Application</small>
        </p>
    </div>
</body>
</html>
