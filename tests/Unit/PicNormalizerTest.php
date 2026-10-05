<?php

namespace Tests\Unit;

use App\Support\PicNormalizer;
use PHPUnit\Framework\TestCase;

class PicNormalizerTest extends TestCase
{
    public function test_dosen_and_mahasiswa_are_not_mapped_to_an_spmi_position(): void
    {
        $config = require __DIR__.'/../../config/spmi.php';
        $normalizer = new PicNormalizer($config['pic_rules'], $config['jenjang_rules']);

        $this->assertSame([
            'jabatan' => [],
            'unit' => [],
            'jenjang' => [],
            'tidak_pasti' => [],
        ], $normalizer->normalize('Dosen / Mahasiswa'));
    }
}
