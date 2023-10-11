<?php

namespace Feature;

use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WithoutAcceptJobTest extends TestCase
{
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

    }

    public function setUp(): void
    {
        parent::setUp();

    }

    public function testWithoutAcceptSend(): void
    {
        Queue::fake();
    }
}