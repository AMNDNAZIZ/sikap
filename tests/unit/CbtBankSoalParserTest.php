<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CbtBankSoalParserTest extends TestCase
{
    private function parseHtml($html)
    {
        // Copy of the robust parsing method
        preg_match_all('/<img[^>]+>/i', $html, $imgTags);
        $imgList = $imgTags[0] ?? [];
        $tmp = preg_replace('/<img[^>]+>/i', '[[IMG_TAG_PLACEHOLDER]]', $html);

        $normalized = preg_replace('/<(?:br|\/p|\/div|\/li|\/tr|\/td|\/h[1-6]|\/blockquote|\/section|\/article)[^>]*>/i', "\n", $tmp);
        $normalized = preg_replace('/<(?:p|div|li|tr|h[1-6]|blockquote|section|article)[^>]*>/i', "\n", $normalized);

        $clean = strip_tags($normalized, '');
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $clean = str_replace(["\r", "\xc2\xa0", "&nbsp;"], ' ', $clean);

        foreach ($imgList as $tag) {
            $clean = preg_replace('/\[\[IMG_TAG_PLACEHOLDER\]\]/', $tag, $clean, 1);
        }

        $clean = preg_replace('/(?<!\n)\s*(?=(?:Soal\s*[:.]?\s*\d+[\).:]?))/iu', "\n\n", $clean);
        // Lindungi baris Kunci/Tipe agar tidak terpecah jika kunci ditulis "B." atau "(B)"
        $clean = preg_replace_callback('/((?:Kunci\s*Jawaban|Kunci|Jawaban|Tipe|Key|Ans)\s*[:.)=][^\n]*)/iu', function($m) {
            return "\n[[KEY_PLACEHOLDER_" . base64_encode($m[1]) . "]]";
        }, $clean);
        $clean = preg_replace('/(?<!\n)(?<=\s|^|[>])(?=(?:[A-Ea-e]\s*[:.)]|(?:\([A-Ea-e]\))\s*[:.)]?))/iu', "\n", $clean);
        $clean = preg_replace_callback('/\[\[KEY_PLACEHOLDER_([^\]]+)\]\]/', function($m) {
            return base64_decode($m[1]);
        }, $clean);

        $rawLines = explode("\n", $clean);
        $lines = [];
        foreach ($rawLines as $l) {
            $t = trim($l);
            if ($t !== '') {
                $lines[] = $t;
            }
        }

        $questions = [];
        $current = null;
        $lastOptionKey = null;

        $flushQuestion = function() use (&$questions, &$current) {
            if (!$current) return;

            $qText = trim(preg_replace('/\s+/', ' ', implode(' ', $current['question_lines'])));
            if (strlen(strip_tags($qText)) < 3 && empty($current['options'])) {
                $current = null;
                return;
            }

            $type = $current['type'];
            $options = $current['options'];
            $key = $current['key'];

            if ($type === 'benar_salah') {
                // Keep
            } elseif (empty($options)) {
                $type = 'esai';
            } else {
                if (!empty($current['type_locked'])) {
                    $type = $current['type'];
                } elseif (!empty($key) && strpos($key, ',') !== false) {
                    $type = 'pg_kompleks';
                } else {
                    $type = 'pg';
                }
            }

            preg_match_all('/<img[^>]+src="([^">]+)"/i', $current['raw_html'] . ' ' . $qText . ' ' . implode(' ', $options), $imgMatches);
            $images = array_values(array_unique($imgMatches[1] ?? []));

            $questions[] = [
                'nomor'    => $current['nomor'],
                'question' => $qText,
                'options'  => $options,
                'key'      => $key,
                'type'     => $type,
                'images'   => $images,
                'raw_html' => $current['raw_html'],
            ];

            $current = null;
        };

        foreach ($lines as $line) {
            if (preg_match('/^(?:Soal\s*[:.]?\s*(\d+)[\).:]?|(\d{1,3})[\).])\s*(.*)/iu', $line, $m)) {
                $flushQuestion();
                $num = !empty($m[1]) ? $m[1] : $m[2];
                $soalText = trim($m[3]);
                $current = [
                    'nomor'          => (int) $num,
                    'question_lines' => $soalText !== '' ? [$soalText] : [],
                    'options'        => [],
                    'key'            => null,
                    'type'           => 'pg',
                    'raw_html'       => $line,
                ];
                $lastOptionKey = null;
                continue;
            }

            if ($current === null) {
                $current = [
                    'nomor'          => count($questions) + 1,
                    'question_lines' => [$line],
                    'options'        => [],
                    'key'            => null,
                    'type'           => 'pg',
                    'raw_html'       => $line,
                ];
                $lastOptionKey = null;
                continue;
            }

            $current['raw_html'] .= "\n" . $line;

            if (preg_match('/^(?:(?:\(([A-Ea-e])\))|([A-Ea-e]))\s*[:.)]\s*(.*)$/iu', $line, $m)) {
                $optLetter = strtoupper(!empty($m[1]) ? $m[1] : $m[2]);
                $optVal = trim($m[3]);
                $current['options'][$optLetter] = $optVal;
                $lastOptionKey = $optLetter;
                continue;
            }

            if (preg_match('/^Tipe\s*[:.)=]?\s*(.+)$/iu', $line, $mTipe)) {
                $tipeVal = strtolower(trim($mTipe[1]));
                if (preg_match('/(bs|benar\s*salah|benar\/salah)/', $tipeVal)) {
                    $current['type'] = 'benar_salah';
                    $current['type_locked'] = true;
                } elseif (preg_match('/(pgk|kompleks)/', $tipeVal)) {
                    $current['type'] = 'pg_kompleks';
                    $current['type_locked'] = true;
                } elseif (preg_match('/(esai|uraian)/', $tipeVal)) {
                    $current['type'] = 'esai';
                    $current['type_locked'] = true;
                } elseif (preg_match('/(pg|pilihan\s*ganda|tunggal|single)/', $tipeVal)) {
                    $current['type'] = 'pg';
                    $current['type_locked'] = true;
                }
                $lastOptionKey = null;
                continue;
            }

            if (preg_match('/^(?:Kunci\s*Jawaban|Kunci|Jawaban|Key|Ans)\s*[:.)=]?\s*(.*)$/iu', $line, $m)) {
                $rawKey = trim($m[1]);
                if (strtolower($rawKey) === 'esai') {
                    $current['type'] = 'esai';
                    $current['type_locked'] = true;
                    $current['key'] = null;
                } elseif ($current['type'] === 'benar_salah') {
                    $rawKey = strtoupper(preg_replace('/\s+/', '', $rawKey));
                    if (strpos($rawKey, ',') !== false) {
                        $current['key'] = $rawKey;
                    } else {
                        $chars = str_split($rawKey);
                        $current['key'] = implode(',', $chars);
                    }
                } else {
                    preg_match_all('/\b([A-Ea-e])\b/u', $rawKey, $keyMatches);
                    $extractedKeys = !empty($keyMatches[1]) ? array_values(array_unique(array_map('strtoupper', $keyMatches[1]))) : [];

                    if (empty($extractedKeys) && preg_match('/^[A-Ea-e]{1,5}$/u', $rawKey)) {
                        $extractedKeys = array_values(array_unique(str_split(strtoupper($rawKey))));
                    }

                    if (!empty($extractedKeys)) {
                        sort($extractedKeys);
                        if (empty($current['type_locked']) && count($extractedKeys) > 1) {
                            $current['type'] = 'pg_kompleks';
                        }
                        if (!empty($current['type_locked']) && $current['type'] === 'pg' && count($extractedKeys) > 1) {
                            $extractedKeys = [reset($extractedKeys)];
                        }
                        $current['key'] = implode(',', $extractedKeys);
                    } else {
                        $current['key'] = $rawKey !== '' ? substr($rawKey, 0, 10) : null;
                    }
                }
                $lastOptionKey = null;
                continue;
            }

            if ($lastOptionKey !== null && isset($current['options'][$lastOptionKey])) {
                $current['options'][$lastOptionKey] .= ' ' . $line;
            } else {
                $current['question_lines'][] = $line;
            }
        }

        $flushQuestion();
        return $questions;
    }

    public function testUserPromptWordPastePreservesPgAndMath(): void
    {
        $raw = "Soal:1)Pecahan yang senilai dengan $\\frac{3}{4}$ adalah...
A:$\\frac{6}{7}$
B:$\\frac{9}{12}$
C:$\\frac{12}{15}$
D:$\\frac{10}{16}$
Kunci:B";

        $res = $this->parseHtml($raw);
        $this->assertCount(1, $res);
        $this->assertEquals('pg', $res[0]['type']);
        $this->assertEquals('B', $res[0]['key']);
        $this->assertArrayHasKey('A', $res[0]['options']);
        $this->assertArrayHasKey('B', $res[0]['options']);
        $this->assertArrayHasKey('C', $res[0]['options']);
        $this->assertArrayHasKey('D', $res[0]['options']);
        $this->assertEquals('$\\frac{6}{7}$', $res[0]['options']['A']);
        $this->assertEquals('$\\frac{9}{12}$', $res[0]['options']['B']);
    }

    public function testWordSingleParagraphCollapseIsRobust(): void
    {
        // When word or browser merges into a single <p> without newline
        $html = '<p>Soal:1)Pecahan yang senilai dengan $\\frac{3}{4}$ adalah... A:$\\frac{6}{7}$ B:$\\frac{9}{12}$ C:$\\frac{12}{15}$ D:$\\frac{10}{16}$ Kunci:B</p>';
        $res = $this->parseHtml($html);

        $this->assertCount(1, $res);
        $this->assertEquals('pg', $res[0]['type']);
        $this->assertEquals('B', $res[0]['key']);
        $this->assertCount(4, $res[0]['options']);
        $this->assertEquals('$\\frac{9}{12}$', $res[0]['options']['B']);
    }

    public function testDivsAndNonBreakingSpacesFromWord(): void
    {
        $html = '<div class="MsoNormal">Soal:1)Pecahan yang senilai dengan $\\frac{3}{4}$ adalah...</div><div class="MsoNormal">A:&nbsp;$\\frac{6}{7}$</div><div class="MsoNormal">B:&nbsp;$\\frac{9}{12}$</div><div class="MsoNormal">C:&nbsp;$\\frac{12}{15}$</div><div class="MsoNormal">D:&nbsp;$\\frac{10}{16}$</div><div class="MsoNormal">Kunci:&nbsp;B</div>';
        $res = $this->parseHtml($html);

        $this->assertCount(1, $res);
        $this->assertEquals('pg', $res[0]['type']);
        $this->assertEquals('B', $res[0]['key']);
        $this->assertCount(4, $res[0]['options']);
    }

    public function testEsaiAndPgkAndBsDetection(): void
    {
        $html = '<p>Soal:1)Siapakah presiden pertama Indonesia? Kunci:esai</p><p>Soal:2)Pilihlah bilangan genap! A:1 B:2 C:3 D:4 Kunci:B,D</p><p>Soal:3)Matahari terbit dari barat. A:Pernyataan Benar B:Pernyataan Salah Tipe:BS Kunci:B,S</p>';
        $res = $this->parseHtml($html);

        $this->assertCount(3, $res);
        $this->assertEquals('esai', $res[0]['type']);
        $this->assertEquals('pg_kompleks', $res[1]['type']);
        $this->assertEquals('B,D', $res[1]['key']);
        $this->assertEquals('benar_salah', $res[2]['type']);
    }

    public function testSingleChoiceWithExplanatoryTextDoesNotBecomePgKompleks(): void
    {
        // "Kunci: B (benar)" contains word "benar" (letters b, e, a). It must NOT become pg_kompleks with key A,B,E!
        $html1 = "Soal: 1) Pecahan yang senilai dengan 3/4 adalah...
A: 6/7
B: 9/12
C: 12/15
D: 10/16
Kunci: B (benar)";
        $res1 = $this->parseHtml($html1);
        $this->assertCount(1, $res1);
        $this->assertEquals('pg', $res1[0]['type']);
        $this->assertEquals('B', $res1[0]['key']);

        // "Kunci: B. Jawaban yang tepat adalah B"
        $html2 = "Soal: 2) Apa warna langit cerah?
A: Merah
B: Biru
C: Hijau
D: Kuning
Kunci: B. Jawaban yang tepat adalah B";
        $res2 = $this->parseHtml($html2);
        $this->assertCount(1, $res2);
        $this->assertEquals('pg', $res2[0]['type']);
        $this->assertEquals('B', $res2[0]['key']);

        // "Kunci: B - 9/12 senilai"
        $html3 = "Soal: 3) Pecahan yang senilai...
A: 6/7
B: 9/12
C: 12/15
D: 10/16
Kunci: B - 9/12 senilai";
        $res3 = $this->parseHtml($html3);
        $this->assertCount(1, $res3);
        $this->assertEquals('pg', $res3[0]['type']);
        $this->assertEquals('B', $res3[0]['key']);
    }

    public function testExplicitTipePgLocksToPg(): void
    {
        $html = "Soal: 1) Pertanyaan pilihan ganda biasa
Tipe: PG
A: Pilihan satu
B: Pilihan dua
C: Pilihan tiga
D: Pilihan empat
Kunci: B";
        $res = $this->parseHtml($html);
        $this->assertCount(1, $res);
        $this->assertEquals('pg', $res[0]['type']);
        $this->assertEquals('B', $res[0]['key']);
    }
}
