<?php
namespace Tests\unit;


class ApplicationTest extends \Codeception\Test\Unit
{
    /**
     * @var \Tests\
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testApplicationEmptyConfig()
    {
        $this->expectOutputString('{"error":{"code":100,"text":"Invalid application config"}}');
        \IVAgafonov\System\Application::init([]);
    }
}