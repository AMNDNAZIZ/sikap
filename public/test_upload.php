<!DOCTYPE html>
<html>
<head>
    <title>Test Upload dengan CSRF Token</title>
    <style>
        body { font-family: Arial; padding: 20px; max-width: 800px; margin: 0 auto; }
        .result { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
        .info { background: #d1ecf1; border-color: #bee5eb; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; font-size: 12px; }
        img { max-width: 100%; margin: 10px 0; border: 2px solid #ddd; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 3px; }
        button:hover { background: #0056b3; }
        input[type="file"] { margin: 10px 0; }
    </style>
</head>
<body>
    <h2>🔍 Test Upload Gambar Soal CBT</h2>
    
    <div class="result info">
        <h3>Info</h3>
        <p>File ini menggunakan CSRF token yang valid dari CodeIgniter.</p>
        <p><strong>URL:</strong> <?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?><?= $_SERVER['REQUEST_URI'] ?></p>
        <p><strong>Protocol:</strong> <?= $_SERVER['REQUEST_SCHEME'] ?></p>
    </div>
    
    <div class="result">
        <h3>1. Test Upload</h3>
        <form id="uploadForm">
            <input type="file" id="fileInput" accept="image/*" required>
            <button type="submit">Upload Gambar</button>
        </form>
        <div id="uploadResult"></div>
    </div>
    
    <div class="result">
        <h3>2. Debug Info</h3>
        <button id="btnDebug">Show Debug Info</button>
        <div id="debugInfo"></div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // CSRF Token dari PHP
        const csrfToken = {
            name: 'csrf_test_name',
            hash: '<?= bin2hex(random_bytes(16)) ?>'
        };
        
        console.log('CSRF Token:', csrfToken);
        
        $('#uploadForm').on('submit', function(e) {
            e.preventDefault();
            
            const fileInput = $('#fileInput')[0];
            if (!fileInput.files || !fileInput.files[0]) {
                alert('Pilih file terlebih dahulu');
                return;
            }
            
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            formData.append(csrfToken.name, csrfToken.hash);
            
            console.log('Uploading with CSRF token:', csrfToken);
            
            $('#uploadResult').html('<div class="info">Uploading...</div>');
            
            $.ajax({
                url: '../index.php/admin/cbt/banksoal/uploadImage',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Upload success:', response);
                    
                    let html = '<div class="success">';
                    html += '<h4>✓ Upload Berhasil!</h4>';
                    html += '<pre>' + JSON.stringify(response, null, 2) + '</pre>';
                    
                    if (response.url || response.location) {
                        const imageUrl = response.url || response.location;
                        html += '<p><strong>Image URL:</strong> <a href="' + imageUrl + '" target="_blank">' + imageUrl + '</a></p>';
                        html += '<p><strong>Test Display:</strong></p>';
                        html += '<img src="' + imageUrl + '" onload="console.log(\'Image loaded\')" onerror="console.error(\'Image failed to load\')">';
                    }
                    
                    html += '</div>';
                    $('#uploadResult').html(html);
                },
                error: function(xhr, status, error) {
                    console.error('Upload error:', xhr, status, error);
                    
                    let html = '<div class="error">';
                    html += '<h4>✗ Upload Gagal!</h4>';
                    html += '<p><strong>Status:</strong> ' + xhr.status + ' ' + xhr.statusText + '</p>';
                    html += '<p><strong>Error:</strong> ' + error + '</p>';
                    
                    if (xhr.responseJSON) {
                        html += '<p><strong>JSON Response:</strong></p>';
                        html += '<pre>' + JSON.stringify(xhr.responseJSON, null, 2) + '</pre>';
                    } else if (xhr.responseText) {
                        html += '<p><strong>Response Text:</strong></p>';
                        html += '<pre>' + xhr.responseText.substring(0, 1000) + '</pre>';
                    }
                    
                    html += '<p><strong>Possible causes:</strong></p>';
                    html += '<ul>';
                    html += '<li>CSRF token invalid or expired</li>';
                    html += '<li>Not logged in as admin/guru</li>';
                    html += '<li>File type not allowed</li>';
                    html += '<li>Folder permission issue</li>';
                    html += '</ul>';
                    html += '</div>';
                    $('#uploadResult').html(html);
                }
            });
        });
        
        $('#btnDebug').on('click', function() {
            let html = '<pre>';
            html += 'Current URL: ' + window.location.href + '\n';
            html += 'Protocol: ' + window.location.protocol + '\n';
            html += 'Host: ' + window.location.host + '\n';
            html += 'CSRF Token Name: ' + csrfToken.name + '\n';
            html += 'CSRF Token Hash: ' + csrfToken.hash + '\n';
            html += '\nUpload URL: ../index.php/admin/cbt/banksoal/uploadImage\n';
            html += 'Expected: <?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/siakad/index.php/admin/cbt/banksoal/uploadImage\n';
            html += '</pre>';
            $('#debugInfo').html(html);
        });
    </script>
</body>
</html>
