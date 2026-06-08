<?php
/**
 * Fix Image URLs in Database
 * Memperbaiki URL gambar yang tersimpan di database akibat perubahan path
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection
$db = new mysqli(
    $_ENV['database.default.hostname'] ?? 'localhost',
    $_ENV['database.default.username'] ?? 'root',
    $_ENV['database.default.password'] ?? '',
    $_ENV['database.default.database'] ?? 'siakad'
);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== FIX IMAGE URLs IN DATABASE ===\n\n";

// 1. Analyze current URLs
echo "1. ANALYZING CURRENT URLs...\n";

$result = $db->query("
    SELECT id, question_text, raw_text, media_image, has_image 
    FROM cbt_questions 
    WHERE has_image = 1 
    ORDER BY id DESC 
    LIMIT 20
");

$issues = [];
$patterns = [
    'old_root' => '/uploads/soal_images/',
    'old_public_double' => 'public/public/uploads/soal_images/',
    'old_path_double' => '/path/to/public/uploads/soal_images/',
    'correct' => 'public/uploads/soal_images/',
];

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $hasIssue = false;
    $issueType = [];
    
    // Check question_text
    if (!empty($row['question_text'])) {
        foreach ($patterns as $key => $pattern) {
            if ($key !== 'correct' && strpos($row['question_text'], $pattern) !== false) {
                $hasIssue = true;
                $issueType[] = "question_text: $key";
            }
        }
    }
    
    // Check raw_text
    if (!empty($row['raw_text'])) {
        foreach ($patterns as $key => $pattern) {
            if ($key !== 'correct' && strpos($row['raw_text'], $pattern) !== false) {
                $hasIssue = true;
                $issueType[] = "raw_text: $key";
            }
        }
    }
    
    // Check media_image
    if (!empty($row['media_image'])) {
        $images = json_decode($row['media_image'], true);
        if (is_array($images)) {
            foreach ($images as $img) {
                foreach ($patterns as $key => $pattern) {
                    if ($key !== 'correct' && strpos($img, $pattern) !== false) {
                        $hasIssue = true;
                        $issueType[] = "media_image: $key";
                        break;
                    }
                }
            }
        }
    }
    
    if ($hasIssue) {
        $issues[$id] = $issueType;
    }
}

echo "   Found " . count($issues) . " questions with URL issues\n";
if (count($issues) > 0) {
    echo "   Sample issues:\n";
    $count = 0;
    foreach ($issues as $id => $types) {
        echo "     - Question ID $id: " . implode(', ', $types) . "\n";
        if (++$count >= 5) break;
    }
}

// 2. Fix URLs
echo "\n2. FIXING URLs...\n";

$baseUrl = $_ENV['app.baseURL'] ?? 'http://localhost/siakad/';
$baseUrl = rtrim($baseUrl, '/') . '/';
$correctUrl = $baseUrl . 'public/uploads/soal_images/';

echo "   Base URL: $baseUrl\n";
echo "   Correct image URL: $correctUrl\n\n";

// Get all questions with images
$result = $db->query("SELECT id, question_text, raw_text, media_image FROM cbt_questions WHERE has_image = 1");

$fixed = 0;
$errors = 0;

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $updated = false;
    
    $questionText = $row['question_text'];
    $rawText = $row['raw_text'];
    $mediaImage = $row['media_image'];
    
    // Fix question_text
    if (!empty($questionText)) {
        $newQuestionText = $questionText;
        
        // Replace old patterns
        $newQuestionText = preg_replace(
            '#(https?://[^/]+)?/uploads/soal_images/#',
            $correctUrl,
            $newQuestionText
        );
        $newQuestionText = str_replace('public/public/uploads/soal_images/', $correctUrl, $newQuestionText);
        
        if ($newQuestionText !== $questionText) {
            $questionText = $newQuestionText;
            $updated = true;
        }
    }
    
    // Fix raw_text
    if (!empty($rawText)) {
        $newRawText = $rawText;
        
        // Replace old patterns
        $newRawText = preg_replace(
            '#(https?://[^/]+)?/uploads/soal_images/#',
            $correctUrl,
            $newRawText
        );
        $newRawText = str_replace('public/public/uploads/soal_images/', $correctUrl, $newRawText);
        
        if ($newRawText !== $rawText) {
            $rawText = $newRawText;
            $updated = true;
        }
    }
    
    // Fix media_image
    if (!empty($mediaImage)) {
        $images = json_decode($mediaImage, true);
        if (is_array($images)) {
            $newImages = [];
            foreach ($images as $img) {
                $newImg = preg_replace(
                    '#(https?://[^/]+)?/uploads/soal_images/#',
                    $correctUrl,
                    $img
                );
                $newImg = str_replace('public/public/uploads/soal_images/', $correctUrl, $newImg);
                $newImages[] = $newImg;
            }
            
            $newMediaImage = json_encode($newImages);
            if ($newMediaImage !== $mediaImage) {
                $mediaImage = $newMediaImage;
                $updated = true;
            }
        }
    }
    
    // Update database
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
            $errors++;
            echo "   ✗ Error fixing question ID: $id - " . $stmt->error . "\n";
        }
        $stmt->close();
    }
}

echo "\n3. SUMMARY:\n";
echo "   Total questions checked: " . $result->num_rows . "\n";
echo "   Fixed: $fixed\n";
echo "   Errors: $errors\n";

// 4. Verify
echo "\n4. VERIFICATION:\n";
$result = $db->query("
    SELECT COUNT(*) as count 
    FROM cbt_questions 
    WHERE has_image = 1 
    AND (
        question_text LIKE '%/uploads/soal_images/%' 
        OR raw_text LIKE '%/uploads/soal_images/%'
        OR question_text LIKE '%public/public/%'
        OR raw_text LIKE '%public/public/%'
    )
");
$row = $result->fetch_assoc();
$remaining = $row['count'];

if ($remaining > 0) {
    echo "   ⚠ Still $remaining questions with potential URL issues\n";
} else {
    echo "   ✓ All URLs fixed!\n";
}

$db->close();

echo "\n=== DONE ===\n";
