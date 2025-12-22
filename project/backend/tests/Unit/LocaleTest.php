<?php

namespace Tests\Unit;

use Tests\TestCase;
use Carbon\Carbon;

class LocaleTest extends TestCase
{
    public function test_application_locale_and_timezone_are_fr_paris()
    {
        $this->assertEquals('fr', config('app.locale'));
        $this->assertEquals('Europe/Paris', config('app.timezone'));

        // Ensure Carbon uses the configured locale
        $this->assertEquals('fr', Carbon::getLocale());
    }
}
