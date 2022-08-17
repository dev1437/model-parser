<?php

namespace Dev1437\ModelParser\Tests;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // additional setup
    }
  
    protected function getPackageProviders($app)
    {
        return [
            \Dev1437\ModelParser\ModelParserPackageServiceProvider::class,
        ];
    }
  
    protected function getEnvironmentSetUp($app)
    {
        // perform environment setup
    }

    protected function assertArrayHasManyKeys($required, $haystack)
    {
        $this->assertTrue(count(array_intersect_key(array_flip($required), $haystack)) === count($required));
    }

    protected function assertArraySubset($required, $haystack)
    {
        $intersection = array_intersect_key($required, $haystack);

        $this->assertGreaterThan(0, count($intersection));

        foreach ($intersection as $key => $value) {
            $this->assertEquals($required[$key], $haystack[$key]);
        }
    }
}
