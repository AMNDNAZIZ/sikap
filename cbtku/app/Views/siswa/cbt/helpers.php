<?php
$test = $test ?? [];
$questions = $questions ?? [];
$savedAnswers = $savedAnswers ?? [];
$isNewSession = !empty($isNewSession);
$serverStartTs = session('active_test.started_at_ts') ?? null; // unix seconds

// Helper function to clean Tipe: and Kunci: from question text display
function cleanQuestionDisplay($html)
{
    if (empty($html))
        return '';
    // Remove Tipe:XX pattern
    $html = preg_replace('/Tipe\s*:\s*[A-Za-z0-9_]+/i', '', $html);
    // Remove Kunci:XX pattern  
    $html = preg_replace('/Kunci\s*:\s*[A-Za-z0-9,\s]+/i', '', $html);
    // Clean up extra spaces and empty tags
    $html = preg_replace('/\s{2,}/', ' ', $html);
    $html = preg_replace('/<p>\s*<\/p>/', '', $html);
    return trim($html);
}
?>