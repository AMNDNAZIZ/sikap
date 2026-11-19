<?php
/**
 * Test Suite EAO-096: Standard XSS Output Escaping Verification (SEC-06)
 * Validates remediation of 21 standard escaping locations (8 HIGH + 13 MEDIUM).
 */

define('APPPATH', dirname(__DIR__) . '/app/');
define('ROOTPATH', dirname(__DIR__) . '/');
define('SYSTEMPATH', dirname(__DIR__) . '/system/');

require_once ROOTPATH . 'vendor/autoload.php';
require_once SYSTEMPATH . 'Common.php';

$totalTests = 0;
$passedTests = 0;

function assert_test($description, $condition) {
    global $totalTests, $passedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [PASS] $description\n";
    } else {
        echo "  [FAIL] $description\n";
    }
}

echo "====================================================================\n";
echo "TEST SUITE EAO-096: STANDARD XSS OUTPUT ESCAPING REMEDIATION\n";
echo "====================================================================\n\n";

// --- 1. VERIFIKASI ESCAPING FUNCTION RUNTIME (CODEIGNITER 4) ---
echo "--- 1. UJI FUNGSI ESCAPING BAWAAN CODEIGNITER 4 ---\n";

$xssPayload = '<script>alert("XSS")</script>';
$escapedHtml = esc($xssPayload, 'html');
assert_test("esc(payload, 'html') mengubah < dan > menjadi entities", 
    str_contains($escapedHtml, '&lt;script&gt;') && !str_contains($escapedHtml, '<script>'));

$attrPayload = 'John " onfocus="alert(1)" autofocus="';
$escapedAttr = esc($attrPayload, 'attr');
assert_test("esc(payload, 'attr') mengubah tanda kutip ganda menjadi &quot;", 
    str_contains($escapedAttr, '&quot;') && !str_contains($escapedAttr, '" onfocus='));

$singleQuotePayload = "O'Connor <img src=x onerror=alert(1)>";
$escapedSingle = esc($singleQuotePayload, 'attr');
assert_test("esc(payload, 'attr') menetralkan event handler onerror dan tag img", 
    !str_contains($escapedSingle, '<img') && str_contains($escapedSingle, '&lt;img'));

$normalText = 'Kelas 7-A (Semester Ganjil 2026/2027)';
assert_test("Teks normal tidak rusak dan tidak mengalami double-escaping", 
    esc($normalText, 'html') === $normalText);

// --- 2. AUDIT STATIS KODE SUMBER PADA 10 FILE VIEW YANG DIREMEDIASI ---
echo "\n--- 2. AUDIT STATIS KODE SUMBER 21 TITIK HASIL REMEDIASI ---\n";

// File 1: admin/teachers/edit.php (4 titik HIGH)
$content = file_get_contents(APPPATH . 'Views/admin/teachers/edit.php');
assert_test("admin/teachers/edit.php: name menggunakan esc(..., 'attr')",
    str_contains($content, 'name="name" value="<?= esc($teacher[\'name\'], \'attr\') ?>"'));
assert_test("admin/teachers/edit.php: mother_name menggunakan esc(..., 'attr')",
    str_contains($content, 'name="mother_name" value="<?= esc($teacher[\'mother_name\'], \'attr\') ?>"'));
assert_test("admin/teachers/edit.php: tr['name'] menggunakan esc()",
    str_contains($content, '<td><?= esc($tr[\'name\']) ?></td>'));
assert_test("admin/teachers/edit.php: cr['assignment_description'] menggunakan esc()",
    str_contains($content, '<td><?= esc($cr[\'assignment_description\']) ?></td>'));

// File 2: admin/teaching_assignments/edit.php (1 HIGH + 2 MEDIUM)
$content = file_get_contents(APPPATH . 'Views/admin/teaching_assignments/edit.php');
assert_test("teaching_assignments/edit.php: t['name'] menggunakan esc()",
    str_contains($content, '<?= esc($t[\'name\']) ?>'));
assert_test("teaching_assignments/edit.php: c['name'] menggunakan esc()",
    str_contains($content, '<?= esc($c[\'name\']) ?>'));
assert_test("teaching_assignments/edit.php: s['name'] menggunakan esc()",
    str_contains($content, '<?= esc($s[\'name\']) ?>'));

// File 3, 4, 5: Kokurikuler Dynamic Title (3 HIGH)
$c1 = file_get_contents(APPPATH . 'Views/admin/kokurikuler/pelaksanaan_detail.php');
assert_test("kokurikuler/pelaksanaan_detail.php: title menggunakan esc()",
    str_contains($c1, '<h5 class="mb-0"><?= esc($title) ?></h5>'));

$c2 = file_get_contents(APPPATH . 'Views/admin/kokurikuler/penilaian_deskripsi.php');
assert_test("kokurikuler/penilaian_deskripsi.php: title menggunakan esc()",
    str_contains($c2, '<h5 class="mb-0"><?= esc($title) ?></h5>'));

$c3 = file_get_contents(APPPATH . 'Views/admin/kokurikuler/penilaian_detail.php');
assert_test("kokurikuler/penilaian_detail.php: title menggunakan esc()",
    str_contains($c3, '<h5 class="mb-0"><?= esc($title) ?></h5>'));

// File 6: admin/cbt/test_status/index.php (2 MEDIUM)
$content = file_get_contents(APPPATH . 'Views/admin/cbt/test_status/index.php');
assert_test("cbt/test_status/index.php: value atribut c['name'] select2-add menggunakan esc(..., 'attr')",
    str_contains($content, '<option value="<?= esc($c[\'name\'], \'attr\') ?>"><?= esc($c[\'name\']) ?></option>'));

// File 7: admin/cms/links/index.php (1 MEDIUM)
$content = file_get_contents(APPPATH . 'Views/admin/cms/links/index.php');
assert_test("cms/links/index.php: link['title'] menggunakan esc()",
    str_contains($content, '<?= esc($link[\'title\']); ?>'));

// File 8: admin/grades/class_report.php (1 MEDIUM)
$content = file_get_contents(APPPATH . 'Views/admin/grades/class_report.php');
assert_test("grades/class_report.php: subject['name'] menggunakan esc()",
    str_contains($content, '<?= esc($subject[\'name\']) ?>'));

// File 9: admin/grades/student_report.php (1 MEDIUM)
$content = file_get_contents(APPPATH . 'Views/admin/grades/student_report.php');
assert_test("grades/student_report.php: student['name'] menggunakan esc()",
    str_contains($content, '<?= esc($student[\'name\']) ?>'));

// File 10: profile/teacher_edit.php (6 MEDIUM)
$content = file_get_contents(APPPATH . 'Views/profile/teacher_edit.php');
assert_test("profile/teacher_edit.php: teacher['name'] menggunakan esc(..., 'attr')",
    str_contains($content, 'name="name" value="<?= esc($teacher[\'name\'], \'attr\') ?>"'));
assert_test("profile/teacher_edit.php: teacher['mother_name'] menggunakan esc(..., 'attr')",
    str_contains($content, 'name="mother_name" value="<?= esc($teacher[\'mother_name\'], \'attr\') ?>"'));
assert_test("profile/teacher_edit.php: tr['name'] menggunakan esc()",
    str_contains($content, '<?= esc($tr[\'name\']) ?>'));
assert_test("profile/teacher_edit.php: cr['assignment_description'] menggunakan esc()",
    str_contains($content, '<?= esc($cr[\'assignment_description\']) ?>'));
assert_test("profile/teacher_edit.php: user['username'] menggunakan esc(..., 'attr')",
    str_contains($content, 'value="<?= esc($user[\'username\'], \'attr\') ?>" class="form-control" readonly'));
assert_test("profile/teacher_edit.php: user['email'] menggunakan esc(..., 'attr')",
    str_contains($content, 'value="<?= esc($user[\'email\'], \'attr\') ?>" class="form-control" readonly'));

// --- 3. VERIFIKASI REMEDIASI SUMMERNOTE (EAO-103 PURIFY_HTML) ---
echo "\n--- 3. VERIFIKASI REMEDIASI SUMMERNOTE (EAO-103 PURIFY_HTML) ---\n";
$agendaAdmin = file_get_contents(APPPATH . 'Views/admin/agendas/show.php');
assert_test("admin/agendas/show.php menggunakan purify_html()",
    str_contains($agendaAdmin, '<div><?= purify_html($agenda[\'description\']) ?></div>'));

$agendaSiswa = file_get_contents(APPPATH . 'Views/siswa/agendas/show.php');
assert_test("siswa/agendas/show.php menggunakan purify_html()",
    str_contains($agendaSiswa, '<?= purify_html($agenda[\'description\']) ?>'));

// --- 4. VERIFIKASI INTEGRITAS LISENSI ---
echo "\n--- 4. INTEGRITAS LISENSI & BERKAS TERLINDUNG ---\n";
require_once APPPATH . 'Libraries/SecurityGuard.php';
$secPass = \App\Libraries\SecurityGuard::verifyCriticalFiles();
assert_test("SecurityGuard::verifyCriticalFiles() menghasilkan SEC_PASS:1", $secPass === true);

echo "\n====================================================================\n";
echo "HASIL PENGUJIAN: $passedTests / $totalTests BERHASIL\n";
echo "====================================================================\n";

if ($passedTests === $totalTests) {
    exit(0);
} else {
    exit(1);
}
