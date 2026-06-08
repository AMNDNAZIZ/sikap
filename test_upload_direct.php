<!DOCTYPE html>
<html>
<head>
    <title>Test Upload Direct</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .result { margin-top: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
        img { max-width: 500px; margin-top: 10px; }
    </style>
</head>
<body>
    <h2>Test Upload Gambar Soal CBT</h2>
    
    <form id="uploadForm" enctype="multipart/form-data">
        <div>
            <label>Pilih Gambar:</label><br>
            <input type="file" name="file" id="fileInput" accept="image/*" required>
        </div>
        <div style="margin-top: 10px;">
            <button type="submit">Upload</button>
        </div>
    </form>
    
    <div id="result"></div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $('#uploadForm').on('submit', function(e) {
            e.preventDefault();
            
            const fileInput = $('#fileInput')[0];
            if (!fileInput.files || !fileInput.files[0]) {
                alert('Pilih file terlebih dahulu');
                return;
            }
            
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            
            $('#result').html('<div class="result">Uploading...</div>');
            
            $.ajax({
                url: 'index.php/admin/cbt/banksoal/uploadImage',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Success:', response);
                    
                    let html = '<div class="result success">';
                    html += '<h3>✓ Upload Berhasil!</h3>';
                    html += '<p><strong>Response:</strong></p>';
                    html += '<pre>' + JSON.stringify(response, null, 2) + '</pre>';
                    
                    if (response.url || response.location) {
                        const imageUrl = response.url || response.location;
                        html += '<p><strong>URL:</strong> ' + imageUrl + '</p>';
                        html += '<img src="' + imageUrl + '" alt="Uploaded image">';
                    }
                    
                    html += '</div>';
                    $('#result').html(html);
                },
                error: function(xhr, status, error) {
                    console.error('Error:', xhr, status, error);
                    
                    let html = '<div class="result error">';
                    html += '<h3>✗ Upload Gagal!</h3>';
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
                    $('#result').html(html);
                }
            });
        });
    </script>
</body>
</html>
