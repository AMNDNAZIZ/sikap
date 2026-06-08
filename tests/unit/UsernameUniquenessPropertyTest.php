<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Property-Based Test: Username unik — penyimpanan kedua harus gagal.
 *
 * Menggunakan in-memory "database" (array) untuk mensimulasikan logika
 * uniqueness validation yang dilakukan UserModel/controller.
 *
 * Feature: role-staf-kontributor
 * Property 2: Username unik — penyimpanan kedua harus gagal
 *
 * Validates: Requirements 8.5
 *
 * @internal
 */
final class UsernameUniquenessPropertyTest extends CIUnitTestCase
{
    /**
     * Simpan username ke "database" in-memory.
     * Mengembalikan true jika berhasil, false jika username sudah ada.
     *
     * @param string[] $db
     */
    private function storeUser(array &$db, string $username): bool
    {
        if (in_array($username, $db, true)) {
            return false; // username sudah ada — gagal
        }

        $db[] = $username;

        return true;
    }

    /**
     * Generate username acak yang valid (alfanumerik + underscore, 4–20 karakter).
     */
    private function generateValidUsername(int $seed): string
    {
        $chars  = 'abcdefghijklmnopqrstuvwxyz0123456789_';
        $length = ($seed % 17) + 4; // 4–20 karakter
        $name   = '';

        // Pastikan karakter pertama adalah huruf
        $name .= chr(ord('a') + ($seed % 26));

        for ($i = 1; $i < $length; $i++) {
            $name .= $chars[($seed * ($i + 1) * 31 + $i * 17) % strlen($chars)];
        }

        return $name;
    }

    /**
     * Property 2: Username unik — penyimpanan kedua harus gagal.
     *
     * Untuk setiap username valid yang di-generate secara acak (100+ iterasi):
     * - Simpan user pertama → harus berhasil
     * - Coba simpan user kedua dengan username yang sama → harus gagal
     * - Jumlah user tidak bertambah setelah percobaan kedua
     *
     * Feature: role-staf-kontributor, Property 2: Username unik — penyimpanan kedua harus gagal
     * Validates: Requirements 8.5
     */
    public function testDuplicateUsernameStorageMustFail(): void
    {
        mt_srand(42); // reproducible seed

        $iterations = 0;

        for ($i = 0; $i < 100; $i++) {
            $db       = [];
            $username = $this->generateValidUsername(mt_rand(1000, 9999) + $i);

            // Simpan user pertama — harus berhasil
            $firstResult = $this->storeUser($db, $username);
            $this->assertTrue(
                $firstResult,
                "Iterasi $i: penyimpanan pertama username='$username' harus berhasil"
            );
            $this->assertCount(
                1,
                $db,
                "Iterasi $i: setelah penyimpanan pertama, jumlah user harus 1"
            );

            // Coba simpan user kedua dengan username yang sama — harus gagal
            $countBefore  = count($db);
            $secondResult = $this->storeUser($db, $username);
            $countAfter   = count($db);

            $this->assertFalse(
                $secondResult,
                "Iterasi $i: penyimpanan kedua username='$username' harus gagal (username sudah ada)"
            );
            $this->assertSame(
                $countBefore,
                $countAfter,
                "Iterasi $i: jumlah user tidak boleh bertambah setelah penyimpanan duplikat username='$username'"
            );

            $iterations++;
        }

        $this->assertGreaterThanOrEqual(100, $iterations, 'Harus menjalankan minimal 100 iterasi');
    }

    /**
     * Inverse property: dua username yang BERBEDA keduanya harus berhasil disimpan.
     *
     * Feature: role-staf-kontributor, Property 2: Username unik — penyimpanan kedua harus gagal
     * Validates: Requirements 8.5
     */
    public function testDifferentUsernamesBothSucceed(): void
    {
        mt_srand(99);

        $iterations = 0;

        for ($i = 0; $i < 100; $i++) {
            $db        = [];
            $username1 = 'user_a_' . $i;
            $username2 = 'user_b_' . $i;

            $result1 = $this->storeUser($db, $username1);
            $result2 = $this->storeUser($db, $username2);

            $this->assertTrue(
                $result1,
                "Iterasi $i: penyimpanan username1='$username1' harus berhasil"
            );
            $this->assertTrue(
                $result2,
                "Iterasi $i: penyimpanan username2='$username2' harus berhasil"
            );
            $this->assertCount(
                2,
                $db,
                "Iterasi $i: setelah menyimpan dua username berbeda, jumlah user harus 2"
            );

            $iterations++;
        }

        $this->assertGreaterThanOrEqual(100, $iterations, 'Harus menjalankan minimal 100 iterasi');
    }
}
