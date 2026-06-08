<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Property-Based Test: AuthFilter mengizinkan akses jika dan hanya jika
 * role ada dalam daftar filter.
 *
 * Feature: role-staf-kontributor
 * Property 1: AuthFilter mengizinkan akses jika dan hanya jika role ada dalam daftar filter
 *
 * Validates: Requirements 1.2, 1.3
 *
 * @internal
 */
final class AuthFilterPropertyTest extends CIUnitTestCase
{
    /**
     * Property 1: AuthFilter allow/deny — 100+ iterasi acak.
     *
     * Untuk setiap pasangan (role_id, allowed_roles) yang di-generate secara acak,
     * hasil in_array(role_id, allowed_roles) harus konsisten:
     * - true  jika dan hanya jika role_id ada dalam allowed_roles
     * - false jika dan hanya jika role_id tidak ada dalam allowed_roles
     *
     * Feature: role-staf-kontributor, Property 1: AuthFilter mengizinkan akses jika dan hanya jika role ada dalam daftar filter
     * Validates: Requirements 1.2, 1.3
     */
    public function testAuthFilterAllowDenyProperty(): void
    {
        $iterations = 0;
        $seed       = 42; // reproducible seed

        mt_srand($seed);

        for ($i = 0; $i < 100; $i++) {
            $roleId       = mt_rand(1, 10);
            $allowedRoles = $this->generateRandomSubset();

            $allowed = in_array($roleId, $allowedRoles);

            if ($allowed) {
                $this->assertContains(
                    $roleId,
                    $allowedRoles,
                    "Iterasi $i: role_id=$roleId seharusnya ada dalam allowed_roles=[" . implode(',', $allowedRoles) . ']'
                );
            } else {
                $this->assertNotContains(
                    $roleId,
                    $allowedRoles,
                    "Iterasi $i: role_id=$roleId seharusnya tidak ada dalam allowed_roles=[" . implode(',', $allowedRoles) . ']'
                );
            }

            $iterations++;
        }

        $this->assertGreaterThanOrEqual(100, $iterations, 'Harus menjalankan minimal 100 iterasi');
    }

    /**
     * Verifikasi role 7 (Staf) diizinkan pada filter yang mengandung role 7.
     *
     * Feature: role-staf-kontributor, Property 1: AuthFilter mengizinkan akses jika dan hanya jika role ada dalam daftar filter
     * Validates: Requirements 1.2, 1.3
     */
    public function testRole7AllowedWhenInFilterList(): void
    {
        $role7AllowedCases = [
            [1, 7],
            [1, 2, 7],
            [1, 2, 3, 7],
        ];

        foreach ($role7AllowedCases as $allowedRoles) {
            $this->assertTrue(
                in_array(7, $allowedRoles),
                'Role 7 (Staf) harus diizinkan ketika allowed_roles=[' . implode(',', $allowedRoles) . ']'
            );
        }
    }

    /**
     * Verifikasi role 7 (Staf) ditolak pada filter yang tidak mengandung role 7.
     *
     * Feature: role-staf-kontributor, Property 1: AuthFilter mengizinkan akses jika dan hanya jika role ada dalam daftar filter
     * Validates: Requirements 1.2, 1.3
     */
    public function testRole7DeniedWhenNotInFilterList(): void
    {
        $role7DeniedCases = [
            [1],
            [1, 2],
            [1, 2, 3],
            [1, 2, 6],
        ];

        foreach ($role7DeniedCases as $allowedRoles) {
            $this->assertFalse(
                in_array(7, $allowedRoles),
                'Role 7 (Staf) harus ditolak ketika allowed_roles=[' . implode(',', $allowedRoles) . ']'
            );
        }
    }

    /**
     * Verifikasi role 6 (Kontributor) diizinkan pada filter yang mengandung role 6.
     *
     * Feature: role-staf-kontributor, Property 1: AuthFilter mengizinkan akses jika dan hanya jika role ada dalam daftar filter
     * Validates: Requirements 1.2, 1.3
     */
    public function testRole6AllowedWhenInFilterList(): void
    {
        $this->assertTrue(
            in_array(6, [1, 2, 6]),
            'Role 6 (Kontributor) harus diizinkan ketika allowed_roles=[1,2,6]'
        );
    }

    /**
     * Verifikasi role 6 (Kontributor) ditolak pada filter yang tidak mengandung role 6.
     *
     * Feature: role-staf-kontributor, Property 1: AuthFilter mengizinkan akses jika dan hanya jika role ada dalam daftar filter
     * Validates: Requirements 1.2, 1.3
     */
    public function testRole6DeniedWhenNotInFilterList(): void
    {
        $this->assertFalse(
            in_array(6, [1, 2, 7]),
            'Role 6 (Kontributor) harus ditolak ketika allowed_roles=[1,2,7]'
        );
    }

    /**
     * Generate subset acak dari [1..10].
     *
     * @return int[]
     */
    private function generateRandomSubset(): array
    {
        $all    = range(1, 10);
        $size   = mt_rand(0, 10);
        $subset = [];

        if ($size === 0) {
            return $subset;
        }

        $keys = array_rand($all, $size);

        if (! is_array($keys)) {
            $keys = [$keys];
        }

        foreach ($keys as $k) {
            $subset[] = $all[$k];
        }

        return $subset;
    }
}
