<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    public function test_unauthenticated_dashboard_redirects_to_the_public_about_page(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirectToRoute('about');
    }
}
