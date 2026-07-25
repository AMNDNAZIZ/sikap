<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Hash Update Tool</title>
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
        .warning {
            color: #856404;
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
        }
        .file-list {
            list-style: none;
            padding: 0;
        }
        .file-list li {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            background: #f8f9fa;
        }
        .file-list li.success {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .file-list li.error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .hash {
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            color: #666;
            display: block;
            margin-top: 5px;
        }
        .next-steps {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
            margin-top: 30px;
        }
        .next-steps ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .next-steps li {
            margin: 10px 0;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #c82333;
        }
        .btn-primary {
            background: #007bff;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 License Hash Update Tool</h1>
        <p>Tool ini akan memperbarui hash file untuk sistem lisensi.</p>
        <hr>

        <h2>📝 Generating Hashes...</h2>
        <ul class="file-list">
            <?php foreach ($hashes as $file => $hash): ?>
                <li class="success">
                    ✓ <strong><?= esc($file) ?></strong>
                    <span class="hash">Hash: <?= esc($hash) ?></span>
                </li>
            <?php endforeach; ?>
            
            <?php foreach ($errors as $file): ?>
                <li class="error">
                    ✗ <strong><?= esc($file) ?></strong> (FILE NOT FOUND)
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if (!empty($errors)): ?>
            <div class="warning">
                <h3>⚠️ Warning: Some files were not found</h3>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p>Aplikasi mungkin tidak berfungsi dengan baik jika file-file ini hilang.</p>
            </div>
        <?php endif; ?>

        <?php if ($success && $result !== null): ?>
            <div class="success">
                <h3>✅ Hash File Berhasil Diperbarui!</h3>
                <p><strong>File:</strong> <code><?= esc($hashFile) ?></code></p>
                <p><strong>Total files:</strong> <?= count($hashes) ?></p>
                <p><strong>Size:</strong> <?= number_format($result) ?> bytes</p>
            </div>

            <div class="next-steps">
                <h3>🎯 Langkah Selanjutnya:</h3>
                <ol>
                    <li><strong>HAPUS controller ini</strong> untuk keamanan!</li>
                    <li>Clear cache browser Anda (Ctrl+Shift+Delete)</li>
                    <li>Clear cache aplikasi (hapus folder <code>writable/cache/</code>)</li>
                    <li>Coba akses aplikasi lagi</li>
                    <li>Jika masih error, jalankan: <code>php spark license:fix-checksum</code></li>
                </ol>
            </div>

            <div class="warning">
                <h3>⚠️ PENTING!</h3>
                <p><strong>Segera hapus controller ini setelah selesai!</strong></p>
                <p>Controller ini bisa menjadi celah keamanan jika dibiarkan di server.</p>
                <a href="<?= base_url('fixlicense/delete') ?>" class="btn" onclick="return confirm('Yakin ingin menghapus controller ini?')">🗑️ Hapus Controller Ini Sekarang</a>
                <a href="<?= base_url() ?>" class="btn btn-primary">← Kembali ke Aplikasi</a>
            </div>

        <?php elseif (!$success): ?>
            <div class="error">
                <h3>❌ Gagal Memperbarui Hash File!</h3>
                <?php if (isset($error_message)): ?>
                    <p><?= esc($error_message) ?></p>
                <?php endif; ?>
                <p>Kemungkinan masalah permission. Coba:</p>
                <ol>
                    <li>Set permission folder <code>app/Config/</code> menjadi 755</li>
                    <li>Set permission file <code>.lic_hash</code> menjadi 666</li>
                    <li>Pastikan PHP memiliki akses write ke folder tersebut</li>
                </ol>
            </div>
        <?php endif; ?>

        <hr>
        <p style="text-align: center; color: #666; margin-top: 30px;">
            <small>License Hash Update Tool v1.0 | CBT Application</small>
        </p>
    </div>
</body>
</html>
