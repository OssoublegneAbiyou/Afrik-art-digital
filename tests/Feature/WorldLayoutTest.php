<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorldLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_page_uses_pont_world(): void
    {
        $this->get(route('public.legal'))
            ->assertOk()
            ->assertSee('data-world="pont"', false)
            ->assertSee('Mentions légales')
            ->assertDontSee('aria-label="Ambiance"', false);
    }
}
