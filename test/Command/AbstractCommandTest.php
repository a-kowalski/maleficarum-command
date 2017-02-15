<?php

namespace Maleficarum\Command\Tests;

class AbstractCommandTest extends \PHPUnit\Framework\TestCase
{
    /* ------------------------------------ Method: setUp START ---------------------------------------- */
    protected function setUp() {
        parent::setUp();

        $isRegistered = \Maleficarum\Ioc\Container::isRegistered('Command\Baz');

        if (false === $isRegistered) {
            \Maleficarum\Ioc\Container::register('Command\Baz', function () {
                return $this
                    ->getMockBuilder('Maleficarum\Command\AbstractCommand')
                    ->disableOriginalConstructor()
                    ->getMockForAbstractClass();
            });
        }
    }
    /* ------------------------------------ Method: setUp END ------------------------------------------ */

    /* ------------------------------------ Method: __construct START ---------------------------------- */
    public function testConstruct() {
        $mock = $this
            ->getMockBuilder('Maleficarum\Command\AbstractCommand')
            ->setMethods(['getType'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $mock
            ->expects($this->once())
            ->method('getType')
            ->willReturn('foo');

        $mock->__construct();

        $data = $this->getProperty($mock, 'data');

        $this->assertArrayHasKey('__type', $data);
        $this->assertSame('foo', $data['__type']);
    }
    /* ------------------------------------ Method: __construct END ------------------------------------ */

    /* ------------------------------------ Method: __toString START ----------------------------------- */
    public function testToString() {
        $mock = $this
            ->getMockBuilder('Maleficarum\Command\AbstractCommand')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->setProperty($mock, 'data', ['__type' => 'foo', '__parentHandlerId' => 'bar', 'bar' => 'baz']);

        $this->assertSame('{"bar":"baz"}', $mock->__toString());
    }
    /* ------------------------------------ Method: __toString END ------------------------------------- */

    /* ------------------------------------ Method: toJSON START --------------------------------------- */
    /**
     * @expectedException \RuntimeException
     */
    public function testToJsonInvalidData() {
        $mock = $this
            ->getMockBuilder('Maleficarum\Command\AbstractCommand')
            ->setMethods(['validate'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $mock
            ->expects($this->once())
            ->method('validate')
            ->willReturn(false);

        $mock->toJSON();
    }

    public function testToJsonCorrect() {
        $mock = $this
            ->getMockBuilder('Maleficarum\Command\AbstractCommand')
            ->setMethods(['validate'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $mock
            ->expects($this->once())
            ->method('validate')
            ->willReturn(true);

        $this->setProperty($mock, 'data', ['foo' => 'bar']);

        $this->assertSame('{"foo":"bar"}', $mock->toJSON());
    }
    /* ------------------------------------ Method: toJSON END ----------------------------------------- */

    /* ------------------------------------ Method: fromJSON START ------------------------------------- */
    public function testFromJsonMalformedData() {
        $mock = $this
            ->getMockBuilder('Maleficarum\Command\AbstractCommand')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $mock
            ->expects($this->once())
            ->method('getType')
            ->willReturn('foo');

        $mock->fromJSON('foo');

        $data = $this->getProperty($mock, 'data');

        $this->assertSame(['__type' => 'foo'], $data);
    }

    public function testFromJsonCorrect() {
        $mock = $this
            ->getMockBuilder('Maleficarum\Command\AbstractCommand')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $mock->fromJSON('{"foo":"bar"}');

        $data = $this->getProperty($mock, 'data');

        $this->assertSame(['foo' => 'bar'], $data);
    }
    /* ------------------------------------ Method: fromJSON END --------------------------------------- */

    /* ------------------------------------ Method: decode START --------------------------------------- */
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDecodeMalformedData() {
        \Maleficarum\Command\AbstractCommand::decode('foo');
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testDecodeInvalid($data) {
        $command = \Maleficarum\Command\AbstractCommand::decode($data);

        $this->assertNull($command);
    }
    
    public function invalidDataProvider() {
        return [
            ['{"foo":"bar"}'],
            ['{"__type":"Foo"}'],
            ['{"__type":"Bar"}']
        ];
    }

    public function testDecodeCorrect() {
        $command = \Maleficarum\Command\AbstractCommand::decode('{"__type":"Baz"}');

        $this->assertInstanceOf('Maleficarum\Command\AbstractCommand', $command);
    }
    /* ------------------------------------ Method: decode END ----------------------------------------- */

    /* ------------------------------------ Helper methods START --------------------------------------- */
    /**
     * Set object property value
     *
     * @param object $object
     * @param string $property
     * @param mixed $value
     */
    private function setProperty($object, string $property, $value) {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
        $reflection->setAccessible(false);
    }

    /**
     * Get object property value
     *
     * @param object $object
     * @param string $property
     *
     * @return mixed
     */
    private function getProperty($object, string $property) {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        $value = $reflection->getValue($object);
        $reflection->setAccessible(false);

        return $value;
    }
    /* ------------------------------------ Helper methods END ----------------------------------------- */
}
