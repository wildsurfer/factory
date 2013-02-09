<?php
namespace Mandango\Factory\Tests\Blueprint;
use Mandango\Tests\TestCase;
use Mandango\Factory\Factory;
use Mandango\Factory\Blueprint\Config;
use Mandango\Factory\Blueprint\Sequence;

class ConfigTest extends TestCase {
    protected $configClass;
    protected $instance;

    public function setUp() {
        parent::setUp();
        $factory = new Factory($this->mandango);
        $factory->setConfigClasses(self::$staticConfigClasses);

        $this->instance = new Config($factory, 'Model\Article');
    }   

    public function testHasKey()
    {
        $this->assertTrue($this->instance->hasField('line'));
        $this->assertFalse($this->instance->hasField('foo'));
    }

    public function testMandatory()
    {
        $this->assertTrue($this->instance->isMandatory('title'));
        $this->assertFalse($this->instance->isMandatory('line'));   

        $this->instance->setMandatory('line', true);
        $this->assertTrue($this->instance->isMandatory('line'));

    }

    public function testType()
    {
        $this->assertSame('date', $this->instance->getType('createdAt'));
    }


    public function testValue()
    {
        $value = 10;
        $this->instance->setValue('createdAt', $value);
        $this->assertSame($value, $this->instance->getValue('createdAt'));
    }

    public function testGetMandatory()
    {
        $this->assertTrue(count($this->instance->getMandatory()) > 0);
    }  

    public function testFixOverrides()
    {
        $overrides = Array('line' => 10);
        $return = $this->instance->fixOverrides($overrides);

        $this->assertTrue(isset($return['line']));

        $overrides = Array('line', 'text');
        $return = $this->instance->fixOverrides($overrides);

        $this->assertTrue(array_key_exists('text', $return));
        $this->assertTrue(array_key_exists('line', $return));

        $overrides = Array('line' => 'test', 'text');
        $return = $this->instance->fixOverrides($overrides);

        $this->assertTrue(array_key_exists('text', $return));
        $this->assertTrue(array_key_exists('line', $return));
    }  

    public function testGetDefaults()
    {
        $defaults = $this->instance->getDefaults();
        $this->assertTrue(count($defaults) > 0);

        foreach( $defaults as $closure ) {
            $this->assertInstanceOf('Closure', $closure);
            $this->assertTrue($closure(1) !== null);
        }
    }  

}