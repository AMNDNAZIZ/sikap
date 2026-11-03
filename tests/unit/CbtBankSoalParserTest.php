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
        $clean = preg_replace('/(?<!\n)\s*(?=(?:Kunci\s*Jawaban|Kunci|Jawaban|Tipe|Key|Ans)\s*[:.)=])/iu', "\n", $clean);
        $clean = preg_replace('/(?<!\n)(?<=\s|^|[>])(?=(?:[A-Ea-e]\s*[:.)]|(?:\([A-Ea-e]\))\s*[:.)]?))/iu', "\n", $clean);

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
                if ($type !== 'pg_kompleks') {
                    $type = 'pg';
                }
                if (!empty($key) && strpos($key, ',') !== false) {
                    $type = 'pg_kompleks';
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
                } elseif (preg_match('/(pgk|kompleks)/', $tipeVal)) {
                    $current['type'] = 'pg_kompleks';
                } elseif (preg_match('/(esai|uraian)/', $tipeVal)) {
                    $current['type'] = 'esai';
                }
                $lastOptionKey = null;
                continue;
            }

            if (preg_match('/^(?:Kunci\s*Jawaban|Kunci|Jawaban|Key|Ans)\s*[:.)=]?\s*(.*)$/iu', $line, $m)) {
                $rawKey = trim($m[1]);
                if (strtolower($rawKey) === 'esai') {
                    $current['type'] = 'esai';
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
                    preg_match_all('/[A-E]/i', $rawKey, $keyMatches);
                    if (!empty($keyMatches[0])) {
                        $uniqueKeys = array_unique(array_map('strtoupper', $keyMatches[0]));
                        sort($uniqueKeys);
                        $current['key'] = implode(',', $uniqueKeys);
                        if (count($uniqueKeys) > 1) {
                            $current['type'] = 'pg_kompleks';
                        }
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
}
