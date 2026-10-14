<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seeder_loads_master_data_without_demo_workflow_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'yunhasnawa@polinema.ac.id']);
        $this->assertDatabaseHas('units', ['nama' => 'D3 Administrasi Bisnis']);
        $this->assertDatabaseHas('users', ['email' => 'admin@spmi.test']);
        $this->assertDatabaseMissing('users', ['email' => 'demo.auditee@spmi.test']);
        $this->assertDatabaseCount('self_evaluations', 0);
    }
}
