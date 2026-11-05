<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\ElearningPackageGeneratorService;

class ElearningPackageGeneratorServiceTest extends TestCase
{
    private ElearningPackageGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Instantiate without requiring active DB connection for pure logic tests
        $this->service = new class extends ElearningPackageGeneratorService {
            public function __construct() {
                // Avoid DB connect for isolated unit testing
            }
        };
    }

    public function testParseMeetingsFromHtmlWithValidStages()
    {
        $sampleHtml = '
        <div class="kegiatan-pembelajaran">
            <h4>Pertemuan 1: Mengenal Ekosistem dan Rantai Makanan (2 JP / 70 Menit)</h4>
            <p>Model Pembelajaran: Problem Based Learning</p>
            <table class="table">
                <thead>
                    <tr><th>Tahap</th><th>Sintaks / Kegiatan</th><th>Waktu</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Mindful</td>
                        <td>Guru mengajak siswa mengamati gambar hutan dan sungai di sekitar sekolah.</td>
                        <td>15 Menit</td>
                    </tr>
                    <tr>
                        <td>Meaningful</td>
                        <td>Siswa berdiskusi dalam kelompok menganalisis produsen, konsumen, dan dekomposer.</td>
                        <td>40 Menit</td>
                    </tr>
                    <tr>
                        <td>Joyful</td>
                        <td>Siswa bermain kartu peran organisme rantai makanan dan refleksi bersama.</td>
                        <td>15 Menit</td>
                    </tr>
                </tbody>
            </table>
        </div>
        ';

        $meetings = $this->service->parseMeetingsFromHtml($sampleHtml);

        $this->assertIsArray($meetings);
        $this->assertNotEmpty($meetings);
        $this->assertCount(1, $meetings);

        $m1 = $meetings[0];
        $this->assertEquals(1, $m1['pertemuan_ke']);
        $this->assertStringContainsString('Mengenal Ekosistem', $m1['judul']);
        $this->assertCount(3, $m1['tahap_list']);
        $this->assertEquals('Mindful', $m1['tahap_list'][0]['tahap']);
        $this->assertEquals('Meaningful', $m1['tahap_list'][1]['tahap']);
        $this->assertEquals('Joyful', $m1['tahap_list'][2]['tahap']);
    }

    public function testParseMeetingsFromHtmlEmptyOrMalformed()
    {
        $this->assertEmpty($this->service->parseMeetingsFromHtml(''));
        $this->assertEmpty($this->service->parseMeetingsFromHtml(null));
        $this->assertEmpty($this->service->parseMeetingsFromHtml('Pendek'));
    }

    public function testBuildFallbackPackageContainsFourComponents()
    {
        $meeting = [
            'pertemuan_ke' => 1,
            'judul'        => 'Pertemuan 1: Ekosistem Sawah',
            'model'        => 'Problem Based Learning',
            'durasi'       => '70 Menit',
            'tahap_list'   => [
                ['tahap' => 'Mindful', 'deskripsi' => 'Orientasi fenomena hama wereng di sawah.'],
                ['tahap' => 'Meaningful', 'deskripsi' => 'Penyelidikan peran rantai makanan di ekosistem sawah.'],
                ['tahap' => 'Joyful', 'deskripsi' => 'Simulasi interaktif jaring makanan.']
            ],
            'rencana_teks' => 'Rencana kegiatan lengkap.',
            'raw_text'     => 'Kegiatan belajar.'
        ];

        $package = $this->service->buildFallbackPackage(
            'IPAS',
            6,
            'Ekosistem dan Keseimbangan Alam',
            1,
            $meeting
        );

        $this->assertIsArray($package);

        // 1. Micro-learning
        $this->assertArrayHasKey('micro_learning', $package);
        $this->assertNotEmpty($package['micro_learning']['title']);
        $this->assertNotEmpty($package['micro_learning']['summary_html']);
        $this->assertIsArray($package['micro_learning']['key_points']);
        $this->assertGreaterThanOrEqual(1, count($package['micro_learning']['key_points']));

        // Verifikasi deskripsi Mindful & Meaningful diekstrak ke ringkasan bacaan
        $this->assertStringContainsString('Orientasi fenomena hama wereng', $package['micro_learning']['summary_html']);
        $this->assertStringContainsString('Penyelidikan peran rantai makanan', $package['micro_learning']['summary_html']);

        // 2. Diskusi Pemantik
        $this->assertArrayHasKey('discussion_prompt', $package);
        $this->assertNotEmpty($package['discussion_prompt']);
        $this->assertStringContainsString('Ekosistem dan Keseimbangan Alam', $package['discussion_prompt']);

        // 3. LKPD / Tugas
        $this->assertArrayHasKey('lkpd_task', $package);
        $this->assertNotEmpty($package['lkpd_task']['title']);
        $this->assertNotEmpty($package['lkpd_task']['instructions_html']);
        $this->assertNotEmpty($package['lkpd_task']['submission_guide']);

        // 4. Mini-Kuis Formatif
        $this->assertArrayHasKey('mini_quiz', $package);
        $this->assertIsArray($package['mini_quiz']);
        $this->assertCount(3, $package['mini_quiz']);

        foreach ($package['mini_quiz'] as $q) {
            $this->assertArrayHasKey('question', $q);
            $this->assertArrayHasKey('options', $q);
            $this->assertArrayHasKey('answer', $q);
            $this->assertArrayHasKey('explanation', $q);
            $this->assertArrayHasKey($q['answer'], $q['options']);
        }

        // 5. Segmen Pembelajaran Aksi Nyata (Multi-Media & Alokasi Waktu)
        $this->assertArrayHasKey('segments', $package);
        $this->assertIsArray($package['segments']);
        $this->assertCount(4, $package['segments']);

        $segTypes = array_column($package['segments'], 'content_type');
        $this->assertContains('video', $segTypes);
        $this->assertContains('text', $segTypes);
        $this->assertContains('html', $segTypes);

        $totalMinutes = array_sum(array_column($package['segments'], 'estimated_minutes'));
        $this->assertEquals(70, $totalMinutes);
    }

    public function testExtractMeetingData()
    {
        $html = '
        <div>
            <h3>Pertemuan 1: Pengantar Ekosistem</h3>
            <p>Siswa mengamati video...</p>
            <h3>Pertemuan 2: Rantai Makanan Sawah</h3>
            <p>Siswa menganalisis rantai makanan...</p>
        </div>
        ';

        $matched = $this->service->extractMeetingData($html, 2);
        $this->assertNotNull($matched);
        $this->assertEquals(2, $matched['pertemuan_ke']);
        $this->assertStringContainsString('Rantai Makanan', $matched['judul']);

        $fallbackFirst = $this->service->extractMeetingData($html, 99);
        $this->assertNotNull($fallbackFirst);
        $this->assertEquals(1, $fallbackFirst['pertemuan_ke']);
    }
}
