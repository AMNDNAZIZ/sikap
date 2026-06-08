<?php
/**
 * Fix Image URLs in Database - Simple Version
 * Memperbaiki URL gambar yang tersimpan di database
 */

// Database config - sesuaikan dengan .env Anda
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'siakad';
$baseURL = 'http://localhost/siakad/';

// Connect
$db = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== FIX IMAGE URLs IN DATABASE ===\n\n";

// 1. Check current state
echo "1. CHECKING CURRENT STATE...\n";
$result = $db->query("
    SELECT COUNT(*) as total FROM cbt_questions WHERE has_image = 1
");
$row = $result->fetch_assoc();
echo "   Total questions with images: " . $row['total'] . "\n";

// Check for problematic URLs
$result = $db->query("
    SELECT id, question_text, raw_text, media_image 
    FROM cbt_questions 
    WHERE has_image = 1 
    LIMIT 5
");

echo "\n   Sample URLs (first 5 questions):\n";
while ($row = $result->fetch_assoc()) {
    echo "   - Question ID " . $row['id'] . ":\n";
    
    // Extract URLs from raw_text
    if (preg_match_all('#src="([^"]+soal_images/[^"]+)"#', $row['raw_text'], $matches)) {
        foreach ($matches[1] as $url) {
            echo "     URL: $url\n";
        }
    }
    
    // Check media_image
    if (!empty($row['media_image'])) {
        $images = json_decode($row['media_image'], true);
        if (is_array($images) && count($images) > 0) {
            echo "     media_image: " . $images[0] . "\n";
        }
    }
}

// 2. Fix URLs
echo "\n2. FIXING URLs...\n";
$correctUrl = rtrim($baseURL, '/') . '/public/uploads/soal_images/';
echo "   Target URL pattern: $correctUrl\n\n";

$result = $db->query("SELECT id, question_text, raw_text, media_image FROM cbt_questions WHERE has_image = 1");

$fixed = 0;
$skipped = 0;

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $updated = false;
    
    $questionText = $row['question_text'];
    $rawText = $row['raw_text'];
    $mediaImage = $row['media_image'];
    
    // Fix question_text - replace any old URL patterns
    if (!empty($questionText)) {
        $newText = $questionText;
        
        // Pattern 1: /uploads/soal_images/ (old root path)
        $newText = preg_replace(
            '#(https?://[^/]+/siakad)?/uploads/soal_images/#',
            $correctUrl,
            $newText
        );
        
        // Pattern 2: public/public/uploads (path ganda)
        $newText = str_replace('public/public/uploads/soal_images/', $correctUrl, $newText);
        
        // Pattern 3: Relative path without domain
        $newText = preg_replace(
            '#(?<!http:|https:)//localhost/siakad/uploads/soal_images/#',
            $correctUrl,
            $newText
        );
        
        if ($newText !== $questionText) {
            $questionText = $newText;
            $updated = true;
        }
    }
    
    // Fix raw_text
    if (!empty($rawText)) {
        $newText = $rawText;
        
        $newText = preg_replace(
            '#(https?://[^/]+/siakad)?/uploads/soal_images/#',
            $correctUrl,
            $newText
        );
        $newText = str_replace('public/public/uploads/soal_images/', $correctUrl, $newText);
        $newText = preg_replace(
            '#(?<!http:|https:)//localhost/siakad/uploads/soal_images/#',
            $correctUrl,
            $newText
        );
        
        if ($newText !== $rawText) {
            $rawText = $newText;
            $updated = true;
        }
    }
    
    // Fix media_image JSON
    if (!empty($mediaImage)) {
        $images = json_decode($mediaImage, true);
        if (is_array($images)) {
            $newImages = [];
            $changed = false;
            
            foreach ($images as $img) {
                $newImg = preg_replace(
                    '#(https?://[^/]+/siakad)?/uploads/soal_images/#',
                    $correctUrl,
                    $img
                );
                $newImg = str_replace('public/public/uploads/soal_images/', $correctUrl, $newImg);
                
                if ($newImg !== $img) {
                    $changed = true;
                }
                $newImages[] = $newImg;
            }
            
            if ($changed) {
                $mediaImage = json_encode($newImages);
                $updated = true;
            }
        }
    }
    
    // Update if needed
    if ($updated) {
        $stmt = $db->prepare("
            UPDATE cbt_questions 
            SET question_text = ?, raw_text = ?, media_image = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param('sssi', $questionText, $rawText, $mediaImage, $id);
        
        if ($stmt->execute()) {
            $fixed++;
            echo "   ✓ Fixed question ID: $id\n";
        } else {
            echo "   ✗ Error fixing question ID: $id\n";
        }
        $stmt->close();
    } else {
        $skipped++;
    }
}

echo "\n3. SUMMARY:\n";
echo "   Fixed: $fixed questions\n";
echo "   Skipped (already correct): $skipped questions\n";

// 4. Verify
echo "\n4. VERIFICATION:\n";
$result = $db->query("
    SELECT id, raw_text, media_image 
    FROM cbt_questions 
    WHERE has_image = 1 
    LIMIT 3
");

echo "   Sample URLs after fix:\n";
while ($row = $result->fetch_assoc()) {
    echo "   - Question ID " . $row['id'] . ":\n";
    
    if (preg_match_all('#src="([^"]+soal_images/[^"]+)"#', $row['raw_text'], $matches)) {
        foreach ($matches[1] as $url) {
            echo "     URL: $url\n";
        }
    }
}

$db->close();

echo "\n✓ DONE!\n";
echo "\nNext steps:\n";
echo "1. Test halaman detail bank soal\n";
echo "2. Test ujian siswa\n";
echo "3. Test cetak laporan\n";
