<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardPengolahanTest extends TestCase
{
    public function test_dashboard_pengolahan_returns_successful_response(): void
    {
        $response = $this->get('/dashboard-pengolahan');

        $response->assertStatus(200);
        $response->assertSee('Tabel Petugas SE2026');
        $response->assertSee('Ranking Kinerja');
        $response->assertSee('TARGET HARIAN STANDAR');
        $response->assertSee('BANGUNAN KOSONG / LAINNYA');
        $response->assertSee('Bangunan Kosong / Lainnya');
        $response->assertSee('PROGRESS SEHARUSNYA');
    }
}
