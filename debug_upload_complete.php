<!DOCTYPE html>
<html>
<head>
    <title>Debug Upload Complete</title>
    <meta name="<?= csrf_token() ?>" content="<?= csrf_hash() ?>">
    <style>
        body { font-family: Arial; padding: 20px; max-width: 1200px; margin: 0 auto; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
        .info { background: #d1ecf1; border-color: #bee5eb; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        img { max-width: 500px; margin: 10px 0; }
        .log { max-height: 300px; overflow-y: auto; font-size: 12px; }
    </style>
</head>
<body>
    <h1>🔍 Debug Upload Complete</h1>
    
    <div class="section info">
        <h3>1. Configuration Check</h3>
        <div id="configCheck">Checking...</div>
    </div>
    
    <div class="section info">
        <h3>2. Test Upload</h3>
        <form id="uploadForm" enctype="multipart/form-data">
            <input type="file" name="file" id="fileInput" accept="image/*" required>
            <button type="submit">Upload Test</button>
        </form>
        <div id="uploadResult"></div>
    </div>
    
    <div class="section info">
        <h3>3. Test Save with Image</h3>
        <button id="btnTestSave">Test Save Process</button>
        <div id="saveResult"></div>
    </div>
    
    <div class="section info">
        <h3>4. Check Existing Images</h3>
        <button id="btnCheckImages">Check Database Images</button>
        <div id="imagesResult"></div>
    </div>
    
    <div class="section info">
        <h3>5. Error Log</h3>
        <button id="btnCheckLog">Check Error Log</button>
        <div id="logResult" class="log"></div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const baseUrl = '<?= base_url() ?>';
        const csrfName = $('meta[name]').attr('name');
        const csrfHash = $('meta[name]').attr('content');
        
        // 1. Config Check
        $.get('debug_upload_config.php', function(data) {
            $('#configCheck').html('<pre>' + data + '</pre>');
        });
        
        // 2. Upload Test
        $('#uploadForm').on('submit', function(e) {
            e.preventDefault();
            
            const fileInput = $('#fileInput')[0];
            if (!fileInput.files || !fileInput.files[0]) {
                alert('Pilih file terlebih dahulu');
                return;
            }
            
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            formData.append(csrfName, csrfHash);
            
            $('#uploadResult').html('<div class="info">Uploading...</div>');
            
            $.ajax({
                url: 'index.php/admin/cbt/banksoal/uploadImage',
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
                        html += '<p><strong>URL:</strong> ' + imageUrl + '</p>';
                        html += '<img src="' + imageUrl + '" alt="Uploaded image" onerror="this.style.border=\'2px solid red\'; this.alt=\'Image failed to load\'">';
                        
                        // Test if image is accessible
                        $.ajax({
                            url: imageUrl,
                            type: 'HEAD',
                            success: function() {
                                $('#uploadResult').append('<p class="success">✓ Image is accessible</p>');
                            },
                            error: function() {
                                $('#uploadResult').append('<p class="error">✗ Image is NOT accessible</p>');
                            }
                        });
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
                    
                    if (xhr.responseText) {
                        html += '<p><strong>Response:</strong></p>';
                        html += '<pre>' + xhr.responseText + '</pre>';
                    }
                    
                    if (xhr.responseJSON) {
                        html += '<p><strong>JSON Response:</strong></p>';
                        html += '<pre>' + JSON.stringify(xhr.responseJSON, null, 2) + '</pre>';
                    }
                    
                    html += '</div>';
                    $('#uploadResult').html(html);
                }
            });
        });
        
        // 3. Test Save
        $('#btnTestSave').on('click', function() {
            $('#saveResult').html('<div class="info">Testing save process...</div>');
            
            const testHtml = '<p>Test soal dengan gambar:</p><img src="' + baseUrl + 'public/uploads/soal_images/test.png">';
            
            $.post('debug_test_save.php', {
                raw_text: testHtml,
                [csrfName]: csrfHash
            }, function(data) {
                $('#saveResult').html('<pre>' + data + '</pre>');
            }).fail(function(xhr) {
                $('#saveResult').html('<div class="error"><pre>' + xhr.responseText + '</pre></div>');
            });
        });
        
        // 4. Check Images
        $('#btnCheckImages').on('click', function() {
            $('#imagesResult').html('<div class="info">Checking database...</div>');
            
            $.get('debug_check_images.php', function(data) {
                $('#imagesResult').html('<pre>' + data + '</pre>');
            });
        });
        
        // 5. Check Log
        $('#btnCheckLog').on('click', function() {
            $('#logResult').html('<div class="info">Loading log...</div>');
            
            $.get('debug_check_log.php', function(data) {
                $('#logResult').html('<pre>' + data + '</pre>');
            });
        });
    </script>
</body>
</html>

<?php
// Generate CSRF token for the page
function csrf_token() {
    return 'csrf_test_name';
}

function csrf_hash() {
    return 'test_hash_' . time();
}

function base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . '://' . $host . $script . '/';
}
?>
