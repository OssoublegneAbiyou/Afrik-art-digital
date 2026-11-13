<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    public function test_cron_empties_the_queue_and_releases_chapters_every_minute(): void
    {
        $events = collect($this->app->make(Schedule::class)->events());

        $queue = $events->first(fn ($event) => str_contains($event->command, 'queue:work'));
        $this->assertNotNull($queue, 'queue:work doit être planifié (pas de worker permanent sur le mutualisé).');
        $this->assertStringContainsString('--stop-when-empty', $queue->command);
        $this->assertSame('* * * * *', $queue->expression);
        $this->assertTrue($queue->withoutOverlapping);

        $comics = $events->first(fn ($event) => str_contains($event->command, 'comics:release-scheduled'));
        $this->assertNotNull($comics);
        $this->assertSame('* * * * *', $comics->expression);
    }
}
