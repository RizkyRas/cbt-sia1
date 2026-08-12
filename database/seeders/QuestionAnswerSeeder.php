<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionAnswerSeeder extends Seeder
{
    private const TOTAL_PER_SUBJECT = 150;

    public function run(): void
    {
        mt_srand(2026); // agar hasil acak konsisten setiap kali seeder dijalankan

        $map = [
            'PJOK'       => 'pjokQuestions',
            'Matematika' => 'matematikaQuestions',
            'Sejarah'    => 'sejarahQuestions',
        ];

        foreach ($map as $subjectName => $method) {
            $subject = Subject::where('name', $subjectName)->first();

            if (! $subject) {
                $this->command?->warn("Mata pelajaran '{$subjectName}' tidak ditemukan, dilewati.");

                continue;
            }

            $pool = self::{$method}();
            shuffle($pool);
            $pool = array_slice($pool, 0, self::TOTAL_PER_SUBJECT);

            DB::transaction(function () use ($subject, $pool, $subjectName) {
                // Bersihkan soal lama milik pelajaran ini (jawabannya ikut terhapus via cascadeOnDelete)
                Question::withTrashed()->where('subject_id', $subject->id)->forceDelete();

                foreach ($pool as $item) {
                    $question = Question::create([
                        'subject_id'  => $subject->id,
                        'payload'     => $item['question'],
                        'score'       => 1,
                        'description' => null,
                        'is_active'   => true,
                    ]);

                    $letters = ['A', 'B', 'C', 'D'];
                    foreach ($item['options'] as $index => $option) {
                        Answer::create([
                            'question_id' => $question->id,
                            'option'      => $letters[$index] ?? null,
                            'text'        => $option['text'],
                            'is_correct'  => $option['correct'],
                            'is_active'   => true,
                        ]);
                    }
                }

                $this->command?->info("{$subjectName}: ".count($pool)." soal berhasil dibuat.");
            });
        }
    }

    // Bentuk satu soal pilihan ganda dari 1 jawaban benar + beberapa jawaban salah.

    private static function buildMcq(string $question, string $correct, array $wrongs): array
    {
        $options = [['text' => $correct, 'correct' => true]];

        foreach ($wrongs as $w) {
            $options[] = ['text' => $w, 'correct' => false];
        }

        shuffle($options);

        return ['question' => $question, 'options' => $options];
    }

    private static function pickDistractors(array $pairs, int|string $excludeKey, int $n, bool $wantValue, array $fallbackPool = []): array
    {
        $excludeValue = $wantValue ? ($pairs[$excludeKey] ?? '') : $excludeKey;
        $excludeNormalized = mb_strtolower(trim((string) $excludeValue));

        $seen = [$excludeNormalized => true];
        $result = [];

        $candidates = $pairs;
        unset($candidates[$excludeKey]);
        $keys = array_keys($candidates);
        shuffle($keys);

        foreach ($keys as $k) {
            $val = $wantValue ? (string) $candidates[$k] : (string) $k;
            $norm = mb_strtolower(trim($val));
            if (isset($seen[$norm])) {
                continue;
            }
            $seen[$norm] = true;
            $result[] = $val;
            if (count($result) >= $n) {
                break;
            }
        }

        if (count($result) < $n && ! empty($fallbackPool)) {
            $extra = $fallbackPool;
            shuffle($extra);
            foreach ($extra as $val) {
                $norm = mb_strtolower(trim($val));
                if (isset($seen[$norm])) {
                    continue;
                }
                $seen[$norm] = true;
                $result[] = $val;
                if (count($result) >= $n) {
                    break;
                }
            }
        }

        return $result;
    }

    private static function buildLookupQuestions(array $pairs, string $askValue, ?string $askKey = null, int $distractorCount = 3, array $valueFallbackPool = []): array
    {
        $items = [];

        foreach ($pairs as $key => $value) {
            $wrongValues = self::pickDistractors($pairs, $key, $distractorCount, true, $valueFallbackPool);
            if (count($wrongValues) < $distractorCount) {
                continue;
            }
            $items[] = self::buildMcq(sprintf($askValue, $key), (string) $value, $wrongValues);

            if ($askKey !== null) {
                $wrongKeys = self::pickDistractors($pairs, $key, $distractorCount, false);
                if (count($wrongKeys) < $distractorCount) {
                    continue;
                }
                $items[] = self::buildMcq(sprintf($askKey, $value), (string) $key, $wrongKeys);
            }
        }

        return $items;
    }

    private static function numericDistractors(int|float $correct, int $minDelta = 1, int $maxDeltaPercent = 30): array
    {
        $wrongs = [];
        $attempts = 0;

        while (count($wrongs) < 3 && $attempts < 50) {
            $attempts++;
            $deltaBase = max($minDelta, (int) round(abs($correct) * mt_rand(5, $maxDeltaPercent) / 100));
            $delta = mt_rand(1, max(1, $deltaBase)) * (mt_rand(0, 1) ? 1 : -1);
            $w = $correct + $delta;

            if ($w == $correct || $w < 0) {
                continue;
            }

            $wStr = (string) (is_float($correct) ? round($w, 2) : (int) $w);

            if (! in_array($wStr, $wrongs, true)) {
                $wrongs[] = $wStr;
            }
        }

        $i = 1;
        while (count($wrongs) < 3) {
            $w = (string) ($correct + $i);
            if ($w !== (string) $correct && ! in_array($w, $wrongs, true)) {
                $wrongs[] = $w;
            }
            $i++;
        }

        return $wrongs;
    }

    private static function gcd(int $a, int $b): int
    {
        return $b === 0 ? max($a, 1) : self::gcd($b, $a % $b);
    }

    // MATEMATIKA (150 soal, dihitung secara algoritmik agar jawaban pasti benar)

    private static function matematikaQuestions(): array
    {
        $items = [];
        $count = 0;
        for ($i = 0; $count < 40 && $i < 80; $i++) {
            $op = ['+', '-', 'x', ':'][$i % 4];

            switch ($op) {
                case '+':
                    $a = mt_rand(10, 500);
                    $b = mt_rand(10, 500);
                    $correct = $a + $b;
                    $question = "Hasil dari {$a} + {$b} adalah...";
                    break;
                case '-':
                    $a = mt_rand(100, 900);
                    $b = mt_rand(1, $a - 1);
                    $correct = $a - $b;
                    $question = "Hasil dari {$a} - {$b} adalah...";
                    break;
                case 'x':
                    $a = mt_rand(2, 50);
                    $b = mt_rand(2, 50);
                    $correct = $a * $b;
                    $question = "Hasil dari {$a} x {$b} adalah...";
                    break;
                default:
                    $b = mt_rand(2, 25);
                    $q = mt_rand(2, 50);
                    $a = $b * $q;
                    $correct = $q;
                    $question = "Hasil dari {$a} : {$b} adalah...";
                    break;
            }

            if (! isset($items[$question])) {
                $items[$question] = self::buildMcq($question, (string) $correct, self::numericDistractors($correct));
                $count++;
            }
        }

        $denoms = [2, 3, 4, 5, 6, 8, 10, 12];
        $count = 0;
        for ($i = 0; $count < 10 && $i < 30; $i++) {
            $d = $denoms[$i % count($denoms)];
            $n1 = mt_rand(1, $d - 1);
            $n2 = mt_rand(1, $d - 1);
            $sumN = $n1 + $n2;
            $g = self::gcd($sumN, $d);
            $correct = ($sumN / $g).'/'.($d / $g);
            $question = "Hasil dari {$n1}/{$d} + {$n2}/{$d} adalah...";

            $wrongs = [];
            while (count($wrongs) < 3) {
                $wn = max(1, $sumN + mt_rand(-2, 2));
                $cand = $wn.'/'.$d;
                if ($cand !== $correct && ! in_array($cand, $wrongs, true)) {
                    $wrongs[] = $cand;
                }
            }

            if (! isset($items[$question])) {
                $items[$question] = self::buildMcq($question, $correct, $wrongs);
                $count++;
            }
        }

        $fracToDecimal = [
            '1/2' => '0,5', '1/4' => '0,25', '3/4' => '0,75', '1/5' => '0,2',
            '2/5' => '0,4', '3/5' => '0,6', '4/5' => '0,8', '1/8' => '0,125',
            '1/10' => '0,1', '3/10' => '0,3',
        ];
        foreach ($fracToDecimal as $frac => $dec) {
            $decVal = (float) str_replace(',', '.', $dec);
            $wrongs = [];
            $guard = 0;
            while (count($wrongs) < 3 && $guard < 50) {
                $guard++;
                $w = round($decVal + (mt_rand(-30, 30) / 100), 3);
                if ($w <= 0) {
                    continue;
                }
                $wStr = rtrim(rtrim(number_format($w, 3, '.', ''), '0'), '.');
                $wStr = str_replace('.', ',', $wStr);
                if ($wStr !== $dec && ! in_array($wStr, $wrongs, true)) {
                    $wrongs[] = $wStr;
                }
            }
            $q = "Bentuk desimal dari pecahan {$frac} adalah...";
            $items[$q] = self::buildMcq($q, $dec, $wrongs);
        }

        $decToPercent = [
            '0,1' => '10%', '0,15' => '15%', '0,2' => '20%', '0,25' => '25%',
            '0,3' => '30%', '0,4' => '40%', '0,5' => '50%', '0,6' => '60%',
            '0,75' => '75%', '0,9' => '90%',
        ];
        foreach ($decToPercent as $dec => $persen) {
            $val = (int) rtrim($persen, '%');
            $wrongs = [];
            $guard = 0;
            while (count($wrongs) < 3 && $guard < 50) {
                $guard++;
                $w = $val + mt_rand(-3, 3) * 5;
                if ($w <= 0 || $w === $val) {
                    continue;
                }
                $wStr = $w.'%';
                if (! in_array($wStr, $wrongs, true)) {
                    $wrongs[] = $wStr;
                }
            }
            $q = "Bentuk persen dari {$dec} adalah...";
            $items[$q] = self::buildMcq($q, $persen, $wrongs);
        }

        $percents = [5, 10, 15, 20, 25, 30, 40, 50, 60, 70, 75, 80, 90];
        $count = 0;
        for ($i = 0; $count < 20 && $i < 50; $i++) {
            $p = $percents[$i % count($percents)];
            $base = 20 * mt_rand(1, 25);
            $correct = ($p * $base) / 100;
            $question = "Hasil dari {$p}% dari {$base} adalah...";
            if (! isset($items[$question])) {
                $items[$question] = self::buildMcq($question, (string) $correct, self::numericDistractors($correct));
                $count++;
            }
        }

        $count = 0;
        for ($i = 0; $count < 20 && $i < 50; $i++) {
            $a = mt_rand(2, 9);
            $x = mt_rand(1, 20);
            $b = mt_rand(1, 50);
            $sign = $i % 2 === 0 ? '+' : '-';
            $c = $sign === '+' ? ($a * $x + $b) : ($a * $x - $b);
            $question = "Jika {$a}x {$sign} {$b} = {$c}, maka nilai x yang memenuhi adalah...";
            if (! isset($items[$question])) {
                $items[$question] = self::buildMcq($question, (string) $x, self::numericDistractors($x, 1, 25));
                $count++;
            }
        }

        $count = 0;
        for ($i = 0; $count < 20 && $i < 50; $i++) {
            switch ($i % 4) {
                case 0:
                    $s = mt_rand(3, 30);
                    if ($i % 8 < 4) {
                        $correct = $s * $s;
                        $question = "Luas persegi dengan panjang sisi {$s} cm adalah...cm2";
                    } else {
                        $correct = 4 * $s;
                        $question = "Keliling persegi dengan panjang sisi {$s} cm adalah...cm";
                    }
                    break;
                case 1:
                    $p = mt_rand(6, 40);
                    $l = mt_rand(3, $p - 1);
                    if ($i % 8 < 4) {
                        $correct = $p * $l;
                        $question = "Luas persegi panjang dengan panjang {$p} cm dan lebar {$l} cm adalah...cm2";
                    } else {
                        $correct = 2 * ($p + $l);
                        $question = "Keliling persegi panjang dengan panjang {$p} cm dan lebar {$l} cm adalah...cm";
                    }
                    break;
                case 2:
                    $alas = 2 * mt_rand(3, 20);
                    $tinggi = mt_rand(3, 20);
                    $correct = ($alas * $tinggi) / 2;
                    $question = "Luas segitiga dengan alas {$alas} cm dan tinggi {$tinggi} cm adalah...cm2";
                    break;
                default:
                    $r = 7 * mt_rand(1, 10);
                    if ($i % 8 < 4) {
                        $correct = (22 / 7) * $r * $r;
                        $question = "Luas lingkaran dengan jari-jari {$r} cm (menggunakan pi=22/7) adalah...cm2";
                    } else {
                        $correct = 2 * (22 / 7) * $r;
                        $question = "Keliling lingkaran dengan jari-jari {$r} cm (menggunakan pi=22/7) adalah...cm";
                    }
                    break;
            }
            $correct = (int) round($correct);
            if (! isset($items[$question])) {
                $items[$question] = self::buildMcq($question, (string) $correct, self::numericDistractors($correct));
                $count++;
            }
        }

        $conversions = [
            ['dari' => 'km', 'ke' => 'm', 'faktor' => 1000],
            ['dari' => 'm', 'ke' => 'cm', 'faktor' => 100],
            ['dari' => 'kg', 'ke' => 'gram', 'faktor' => 1000],
            ['dari' => 'ton', 'ke' => 'kg', 'faktor' => 1000],
            ['dari' => 'jam', 'ke' => 'menit', 'faktor' => 60],
            ['dari' => 'menit', 'ke' => 'detik', 'faktor' => 60],
            ['dari' => 'liter', 'ke' => 'ml', 'faktor' => 1000],
        ];
        $count = 0;
        for ($i = 0; $count < 20 && $i < 50; $i++) {
            $c = $conversions[$i % count($conversions)];
            $val = mt_rand(2, 20);
            $correct = $val * $c['faktor'];
            $question = "{$val} {$c['dari']} = ... {$c['ke']}";
            if (! isset($items[$question])) {
                $items[$question] = self::buildMcq($question, (string) $correct, self::numericDistractors($correct));
                $count++;
            }
        }

        return array_values($items);
    }

    // SEJARAH (pool > 150 soal berbasis data fakta sejarah Indonesia, lalu diacak & diambil 150)

    private static function sejarahQuestions(): array
    {
        $items = [];

        // A. Tokoh proklamasi & perannya
        $tokohProklamasi = [
            'Ir. Soekarno' => 'membacakan teks proklamasi kemerdekaan Indonesia dan menjadi presiden pertama',
            'Drs. Mohammad Hatta' => 'mendampingi Soekarno saat proklamasi dan menjadi wakil presiden pertama',
            'Sayuti Melik' => 'mengetik naskah proklamasi kemerdekaan',
            'Fatmawati' => 'menjahit bendera Merah Putih yang dikibarkan saat proklamasi',
            'Ahmad Soebardjo' => 'menyumbangkan gagasan kalimat pertama dalam teks proklamasi',
            'Latief Hendraningrat' => 'mengibarkan bendera Merah Putih pada saat proklamasi',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $tokohProklamasi,
            'Tokoh proklamasi %s berperan dalam...',
            'Tokoh yang berperan %s adalah...'
        ));

        // B. Peristiwa penting & tanggalnya
        $peristiwaTanggal = [
            'Proklamasi kemerdekaan Indonesia' => '17 Agustus 1945',
            'Sumpah Pemuda' => '28 Oktober 1928',
            'Hari Kebangkitan Nasional (Boedi Oetomo)' => '20 Mei 1908',
            'Peristiwa Rengasdengklok' => '16 Agustus 1945',
            'Pertempuran 10 November di Surabaya' => '10 November 1945',
            'Konferensi Meja Bundar' => '23 Agustus 1949',
            'Kongres Perempuan Indonesia pertama' => '22 Desember 1928',
            'Serangan Umum 1 Maret' => '1 Maret 1949',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $peristiwaTanggal,
            'Peristiwa %s terjadi pada tanggal...',
            'Peristiwa yang terjadi pada tanggal %s adalah...'
        ));

        // C. Organisasi pergerakan nasional & tahun berdirinya
        $organisasiTahun = [
            'Boedi Oetomo' => '1908',
            'Sarekat Islam' => '1912',
            'Indische Partij' => '1912',
            'Perhimpunan Indonesia' => '1908',
            'Partai Nasional Indonesia' => '1927',
            'Muhammadiyah' => '1912',
            'Nahdlatul Ulama' => '1926',
            'Taman Siswa' => '1922',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $organisasiTahun,
            'Organisasi %s didirikan pada tahun...',
            'Organisasi pergerakan nasional yang berdiri pada tahun %s adalah...'
        ));

        // D. Tokoh pendiri organisasi
        $organisasiPendiri = [
            'Boedi Oetomo' => 'dr. Sutomo',
            'Sarekat Islam' => 'H. Samanhudi',
            'Indische Partij' => 'Douwes Dekker, Cipto Mangunkusumo, dan Suwardi Suryaningrat',
            'Muhammadiyah' => 'K.H. Ahmad Dahlan',
            'Nahdlatul Ulama' => 'K.H. Hasyim Asy\'ari',
            'Taman Siswa' => 'Ki Hajar Dewantara',
            'Partai Nasional Indonesia' => 'Ir. Soekarno',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $organisasiPendiri,
            'Organisasi %s didirikan oleh...',
            'Tokoh yang mendirikan organisasi tempat %s berperan adalah pendiri dari...'
        ));

        // E. Kerajaan Nusantara & ciri khasnya
        $kerajaanCiri = [
            'Kerajaan Sriwijaya' => 'kerajaan maritim bercorak Buddha yang menguasai jalur perdagangan di Selat Malaka',
            'Kerajaan Majapahit' => 'kerajaan Hindu terbesar di Nusantara yang mencapai puncak kejayaan pada masa Hayam Wuruk',
            'Kerajaan Kutai' => 'kerajaan bercorak Hindu tertua di Indonesia yang terletak di Kalimantan Timur',
            'Kerajaan Tarumanegara' => 'kerajaan Hindu tertua di Pulau Jawa yang terletak di daerah Jawa Barat',
            'Kerajaan Demak' => 'kerajaan Islam pertama di Pulau Jawa',
            'Kerajaan Samudera Pasai' => 'kerajaan Islam pertama di Nusantara yang terletak di Aceh',
            'Kerajaan Mataram Kuno' => 'kerajaan yang membangun Candi Borobudur dan Candi Prambanan',
            'Kerajaan Gowa-Tallo' => 'kerajaan Islam di Sulawesi Selatan yang dipimpin oleh Sultan Hasanuddin',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $kerajaanCiri,
            '%s dikenal sebagai...',
            'Kerajaan Nusantara yang %s adalah...'
        ));

        // F. Raja/tokoh kerajaan terkenal
        $kerajaanRaja = [
            'Kerajaan Majapahit' => 'Hayam Wuruk',
            'Kerajaan Sriwijaya' => 'Balaputradewa',
            'Kerajaan Demak' => 'Raden Patah',
            'Kerajaan Gowa-Tallo' => 'Sultan Hasanuddin',
            'Kerajaan Mataram Islam' => 'Sultan Agung',
            'Kerajaan Aceh' => 'Sultan Iskandar Muda',
            'Kerajaan Banten' => 'Sultan Ageng Tirtayasa',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $kerajaanRaja,
            'Raja terkenal dari %s adalah...',
            'Raja yang bernama %s memimpin kerajaan...'
        ));

        // G. Perlawanan terhadap penjajah & tokohnya
        $perlawananTokoh = [
            'Perang Diponegoro' => 'Pangeran Diponegoro',
            'Perang Padri' => 'Tuanku Imam Bonjol',
            'Perang Aceh' => 'Teuku Umar dan Cut Nyak Dhien',
            'Perlawanan rakyat Maluku (Perang Pattimura)' => 'Kapitan Pattimura',
            'Perang Puputan Margarana' => 'I Gusti Ngurah Rai',
            'Perang Banjar' => 'Pangeran Antasari',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $perlawananTokoh,
            'Tokoh yang memimpin %s adalah...',
            'Perlawanan yang dipimpin oleh %s dikenal dengan sebutan...'
        ));

        // H. Peristiwa penjajahan & masa pemerintahan
        $penjajahanMasa = [
            'Kedatangan bangsa Portugis di Nusantara' => 'tahun 1511',
            'Kedatangan bangsa Belanda pertama kali di Nusantara' => 'tahun 1596',
            'Berdirinya VOC' => 'tahun 1602',
            'Bubarnya VOC' => 'tahun 1799',
            'Masa penjajahan Jepang di Indonesia' => 'tahun 1942 sampai 1945',
            'Sistem Tanam Paksa (Cultuurstelsel)' => 'tahun 1830',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $penjajahanMasa,
            '%s terjadi pada...',
            'Peristiwa yang terjadi pada %s adalah...'
        ));

        // I. Istilah sejarah penting
        $istilahSejarah = [
            'Proklamasi' => 'pengumuman resmi kepada seluruh rakyat mengenai kemerdekaan suatu bangsa',
            'Kolonialisme' => 'paham atau praktik penguasaan suatu wilayah oleh bangsa asing',
            'Imperialisme' => 'politik untuk menguasai negara atau bangsa lain demi memperluas kekuasaan',
            'Nasionalisme' => 'paham kecintaan terhadap bangsa dan tanah air sendiri',
            'Romusha' => 'sistem kerja paksa yang diterapkan Jepang terhadap rakyat Indonesia',
            'Tanam Paksa' => 'kebijakan Belanda yang mewajibkan rakyat menanam tanaman ekspor',
            'Rengasdengklok' => 'peristiwa penculikan Soekarno-Hatta oleh golongan muda sebelum proklamasi',
            'Dasar negara Pancasila' => 'lima prinsip yang menjadi landasan negara Indonesia',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $istilahSejarah,
            'Istilah %s dalam sejarah Indonesia berarti...',
            'Istilah yang berarti %s adalah...'
        ));

        // J. Pahlawan nasional & asal daerahnya
        $pahlawanDaerah = [
            'Cut Nyak Dhien' => 'Aceh',
            'Pangeran Diponegoro' => 'Yogyakarta',
            'Kapitan Pattimura' => 'Maluku',
            'Sultan Hasanuddin' => 'Sulawesi Selatan',
            'Pangeran Antasari' => 'Kalimantan Selatan',
            'I Gusti Ngurah Rai' => 'Bali',
            'Tuanku Imam Bonjol' => 'Sumatera Barat',
            'Sisingamangaraja XII' => 'Sumatera Utara',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $pahlawanDaerah,
            'Pahlawan nasional %s berasal dari daerah...',
            'Pahlawan nasional yang berasal dari %s adalah...'
        ));

        // K. Peristiwa & tokoh sekitar kemerdekaan tambahan
        $peristiwaTokohTambahan = [
            'Golongan muda yang mendesak proklamasi segera dilaksanakan' => 'Wikana dan Chaerul Saleh',
            'Perumus naskah proklamasi bersama Soekarno dan Hatta' => 'Ahmad Soebardjo',
            'Tokoh yang mengusulkan dasar negara pada sidang BPUPKI' => 'Ir. Soekarno, Mohammad Yamin, dan Soepomo',
            'Ketua Badan Penyelidik Usaha Persiapan Kemerdekaan Indonesia (BPUPKI)' => 'dr. Radjiman Wedyodiningrat',
            'Tokoh yang menjadi ketua Panitia Persiapan Kemerdekaan Indonesia (PPKI)' => 'Ir. Soekarno',
            'Tempat dilaksanakannya rapat menjelang proklamasi kemerdekaan' => 'kediaman Laksamana Maeda',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $peristiwaTokohTambahan,
            '%s dikenal sebagai...',
            null
        ));

        // L. Perjanjian & kesepakatan penting
        $perjanjianTahun = [
            'Perjanjian Linggarjati' => 'tahun 1947',
            'Perjanjian Renville' => 'tahun 1948',
            'Perjanjian Roem-Royen' => 'tahun 1949',
            'Konferensi Meja Bundar' => 'tahun 1949',
            'Perjanjian Bongaya' => 'tahun 1667',
            'Perjanjian Giyanti' => 'tahun 1755',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $perjanjianTahun,
            '%s ditandatangani pada...',
            'Perjanjian yang ditandatangani pada %s adalah...'
        ));

        // M. Tokoh & peran dalam masa pergerakan/kemerdekaan
        $tokohPeran = [
            'Ki Hajar Dewantara' => 'Bapak Pendidikan Nasional Indonesia',
            'Douwes Dekker' => 'salah satu pendiri Indische Partij yang menyuarakan kesetaraan hak',
            'Cipto Mangunkusumo' => 'tokoh Tiga Serangkai yang aktif dalam pergerakan nasional',
            'R.A. Kartini' => 'tokoh emansipasi perempuan Indonesia',
            'Dewi Sartika' => 'tokoh pendidikan perempuan yang mendirikan Sekolah Isteri',
            'Wage Rudolf Supratman' => 'pencipta lagu Indonesia Raya',
            'Muhammad Yamin' => 'tokoh yang turut merumuskan dasar negara dan sumpah pemuda',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $tokohPeran,
            'Julukan atau peran dari %s adalah...',
            null
        ));

        return $items;
    }

    // PJOK (pool >= 150 soal berbasis data fakta, lalu diacak & diambil 150)

    private static function pjokQuestions(): array
    {
        $items = [];

        $cabangOlahraga = [
            'Sepak bola' => 'dimainkan oleh 11 pemain per tim dengan tujuan memasukkan bola ke gawang lawan',
            'Bola basket' => 'dimainkan oleh 5 pemain per tim dengan tujuan memasukkan bola ke dalam keranjang (ring)',
            'Bola voli' => 'dimainkan oleh 6 pemain per tim dan bola tidak boleh menyentuh lantai di area sendiri',
            'Futsal' => 'dimainkan oleh 5 pemain per tim di lapangan berukuran lebih kecil dari sepak bola',
            'Bulu tangkis' => 'dimainkan menggunakan raket dan shuttlecock',
            'Tenis meja' => 'dimainkan menggunakan bet dan bola pingpong di atas meja',
            'Kasti' => 'olahraga bola kecil beregu yang dimainkan menggunakan pemukul kayu',
            'Softball' => 'dimainkan oleh 9 pemain per tim menggunakan pemukul dan bola kecil',
            'Sepak takraw' => 'dimainkan menggunakan bola anyaman rotan dan dimainkan dengan kaki melewati net',
            'Bola tangan' => 'dimainkan dengan cara melempar dan menangkap bola menggunakan tangan menuju gawang lawan',
            'Hoki' => 'dimainkan menggunakan tongkat (stick) untuk menggiring bola menuju gawang lawan',
            'Golf' => 'bertujuan memasukkan bola ke dalam lubang menggunakan tongkat dengan pukulan sesedikit mungkin',
            'Panahan' => 'olahraga menembakkan anak panah ke arah sasaran menggunakan busur',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $cabangOlahraga,
            'Berikut ini adalah ciri dari cabang olahraga %s, yaitu...',
            'Cabang olahraga yang %s adalah...'
        ));

        $atletik = [
            'Lari jarak pendek (sprint)' => 'nomor lari yang menempuh jarak 100m, 200m, atau 400m dengan kecepatan maksimal',
            'Lari jarak menengah' => 'nomor lari dengan jarak tempuh 800m sampai 1500m',
            'Lari jarak jauh' => 'nomor lari dengan jarak tempuh di atas 3000m hingga marathon',
            'Lari estafet' => 'nomor lari beregu yang menggunakan tongkat yang diberikan secara berantai',
            'Lompat jauh' => 'nomor atletik yang mengutamakan jarak lompatan sejauh mungkin ke bak pasir',
            'Lompat tinggi' => 'nomor atletik yang mengutamakan ketinggian lompatan melewati mistar',
            'Lompat galah' => 'nomor lompat yang menggunakan bantuan tongkat/galah untuk melewati mistar',
            'Tolak peluru' => 'nomor atletik melempar peluru logam sejauh mungkin dengan cara ditolak dari bahu',
            'Lempar lembing' => 'nomor atletik melempar lembing sejauh mungkin',
            'Lempar cakram' => 'nomor atletik melempar cakram sejauh mungkin dengan cara diputar',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $atletik,
            'Nomor atletik yang disebut %s memiliki ciri...',
            'Nomor atletik yang %s disebut...'
        ));

        $kebugaran = [
            'Kekuatan (strength)' => 'kemampuan otot untuk menggunakan tenaga secara maksimal',
            'Daya tahan (endurance)' => 'kemampuan tubuh untuk bekerja dalam waktu lama tanpa mengalami kelelahan berarti',
            'Kecepatan (speed)' => 'kemampuan berpindah tempat dalam waktu sesingkat mungkin',
            'Kelentukan (flexibility)' => 'kemampuan tubuh untuk bergerak leluasa pada persendian',
            'Kelincahan (agility)' => 'kemampuan mengubah arah tubuh dengan cepat tanpa kehilangan keseimbangan',
            'Keseimbangan (balance)' => 'kemampuan mempertahankan posisi tubuh',
            'Koordinasi (coordination)' => 'kemampuan menggabungkan beberapa gerakan menjadi satu gerakan yang efektif',
            'Daya ledak (power)' => 'kemampuan menggunakan kekuatan maksimal dalam waktu sesingkat mungkin',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $kebugaran,
            'Komponen kebugaran jasmani %s adalah...',
            'Komponen kebugaran jasmani yang merupakan %s disebut...'
        ));

        $renang = [
            'Gaya bebas (crawl)' => 'gerakan tangan mengayuh ke depan secara bergantian dan kaki menendang naik-turun',
            'Gaya dada (breast stroke)' => 'gerakan tangan seperti membelah air ke samping dan kaki seperti katak',
            'Gaya punggung (back stroke)' => 'posisi punggung menghadap ke permukaan air saat berenang',
            'Gaya kupu-kupu (butterfly)' => 'kedua tangan digerakkan bersamaan ke depan dan kaki menendang seperti lumba-lumba',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $renang,
            'Ciri dari %s dalam renang adalah...',
            'Gaya renang yang memiliki ciri %s disebut...'
        ));

        $gizi = [
            'Karbohidrat' => 'zat gizi utama sebagai sumber tenaga bagi tubuh',
            'Protein' => 'zat gizi untuk pertumbuhan dan perbaikan sel tubuh',
            'Lemak' => 'zat gizi sebagai cadangan energi dan pelindung organ tubuh',
            'Vitamin' => 'zat gizi yang membantu menjaga daya tahan tubuh',
            'Mineral' => 'zat gizi yang membantu proses metabolisme dan pertumbuhan tulang',
            'Serat' => 'zat gizi yang membantu melancarkan pencernaan',
            'Vitamin C' => 'vitamin yang meningkatkan daya tahan tubuh dan banyak terdapat pada buah jeruk',
            'Vitamin A' => 'vitamin yang baik untuk kesehatan mata dan banyak terdapat pada wortel',
            'Vitamin D' => 'vitamin yang membantu penyerapan kalsium dan diperoleh dari sinar matahari pagi',
            'Kalsium' => 'mineral yang penting untuk pertumbuhan dan kekuatan tulang serta gigi',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $gizi,
            'Zat gizi %s berperan sebagai...',
            'Zat gizi yang berperan sebagai %s adalah...'
        ));

        $pencakSilat = [
            'Kuda-kuda' => 'sikap dasar posisi kaki dalam pencak silat untuk menjaga keseimbangan',
            'Pukulan' => 'teknik serangan menggunakan tangan dalam pencak silat',
            'Tendangan' => 'teknik serangan menggunakan kaki dalam pencak silat',
            'Elakan' => 'teknik menghindar dengan cara memindahkan posisi togok/badan',
            'Tangkisan' => 'teknik membendung atau menahan serangan lawan',
            'Bantingan' => 'teknik menjatuhkan lawan ke tanah',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $pencakSilat,
            'Istilah %s dalam pencak silat merupakan...',
            'Teknik yang merupakan %s dalam pencak silat disebut...'
        ));

        $peraturan = [
            'Pertandingan sepak bola' => 'berlangsung selama 2x45 menit',
            'Pertandingan bola basket' => 'berlangsung dalam 4 babak (kuarter)',
            'Satu set dalam bola voli' => 'dimenangkan oleh tim yang lebih dulu mencapai 25 poin dengan selisih minimal 2 poin',
            'Satu set dalam bulu tangkis' => 'menggunakan sistem 21 poin dengan total 3 set kemenangan',
            'Satu set dalam tenis meja' => 'menggunakan sistem 11 poin',
            'Perpanjangan waktu (extra time)' => 'dilakukan apabila skor imbang setelah waktu normal pada pertandingan sistem gugur',
            'Adu penalti' => 'dilakukan apabila skor masih imbang setelah perpanjangan waktu',
            'Kartu kuning' => 'diberikan wasit sebagai peringatan kepada pemain yang melakukan pelanggaran',
            'Kartu merah' => 'diberikan wasit kepada pemain yang harus dikeluarkan dari lapangan',
            'Wasit' => 'orang yang bertugas memimpin dan mengawasi jalannya pertandingan',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $peraturan,
            '%s memiliki aturan, yaitu...',
            'Aturan "%s" berlaku pada...'
        ));

        $keselamatan = [
            'Pemanasan (warming up)' => 'aktivitas sebelum berolahraga untuk mempersiapkan otot dan mencegah cedera',
            'Pendinginan (cooling down)' => 'aktivitas setelah berolahraga untuk mengembalikan kondisi tubuh secara bertahap',
            'Cedera keseleo' => 'cedera akibat pergeseran sendi yang melebihi batas normal',
            'P3K' => 'pertolongan pertama yang diberikan sebelum korban dibawa ke tenaga medis',
            'Kram otot' => 'kondisi otot yang menegang secara tiba-tiba akibat kelelahan',
            'Denyut nadi' => 'jumlah detak jantung yang dihitung untuk mengukur intensitas latihan',
            'Dehidrasi' => 'kekurangan cairan tubuh akibat berolahraga tanpa minum yang cukup',
            'Sportivitas' => 'sikap menjunjung tinggi kejujuran dan menghormati lawan dalam bertanding',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $keselamatan,
            'Istilah %s dalam olahraga berarti...',
            'Istilah yang berarti %s adalah...'
        ));

        $senam = [
            'Senam lantai' => 'senam yang dilakukan tanpa alat di atas matras',
            'Senam irama' => 'senam yang dilakukan mengikuti irama musik dengan atau tanpa alat',
            'Guling depan (forward roll)' => 'gerakan senam lantai berguling ke arah depan',
            'Guling belakang (backward roll)' => 'gerakan senam lantai berguling ke arah belakang',
            'Sikap lilin' => 'gerakan senam lantai dengan posisi tubuh terbalik ditopang oleh pundak dan tangan',
            'Kayang' => 'gerakan senam lantai membentuk badan seperti busur dengan bertumpu pada tangan dan kaki',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $senam,
            '%s merupakan gerakan senam yang...',
            'Gerakan senam yang %s disebut...'
        ));

        return $items;
    }
}
