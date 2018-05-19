<?php

namespace SchemaCrawler\Test;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class SchemaCrawlerServiceProviderTest extends TestCase
{
    /** @test */
    public function it_runs_the_migrations()
    {
        $this->artisan('migrate', ['--database' => 'testing']);
        $this->assertTrue(Schema::hasTable('invalid_schemas'));
    }

    /** @test */
    public function it_registers_the_commands()
    {
        $expectedCommands = [
            'crawler:start',
            'crawler:test',
            'make:adapter',
            'make:websource',
            'make:feedsource',
            'make:sourcetest',
            'make:feedtest',
        ];

        $commands = Artisan::all();
        foreach ($expectedCommands as $command) {
            $this->assertArrayHasKey($command, $commands);
        }
    }
}
