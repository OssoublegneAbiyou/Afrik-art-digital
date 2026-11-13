<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteParameterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * En production (PostgreSQL), un texte comparé à une colonne bigint provoquait une erreur 500.
     */
    public function test_non_numeric_ids_give_a_404_not_a_500(): void
    {
        foreach (['/artist/un-nom', '/writer/un-nom', '/illustrations/un-nom', '/artist/12abc'] as $url) {
            $this->get($url)->assertNotFound();
        }
    }
}
