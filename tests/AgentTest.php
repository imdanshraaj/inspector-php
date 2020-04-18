<?php

namespace Inspector\Tests;


use Inspector\Inspector;
use Inspector\Configuration;
use Inspector\Models\Segment;
use PHPUnit\Framework\TestCase;

class AgentTest extends TestCase
{
    /**
     * @throws \Inspector\Exceptions\InspectorException
     */
    public function testInspectorInstance()
    {
        $configuration = new Configuration('example-key');
        $configuration->setEnabled(false);

        $this->assertInstanceOf(Inspector::class, new Inspector($configuration));
    }

    public function testAddEntry()
    {
        $configuration = new Configuration('example-key');
        $configuration->setEnabled(false);

        $inspector = new Inspector($configuration);
        $inspector->startTransaction('ttransaction-test');

        $this->assertInstanceOf(
            Inspector::class,
            $inspector->addEntries($inspector->startSegment('span-test'))
        );

        $this->assertInstanceOf(
            Inspector::class,
            $inspector->addEntries([$inspector->startSegment('span-test')])
        );
    }

    public function testTransport()
    {
        $configuration = new Configuration('example-key');
        $configuration->setEnabled(false)
        ->setTransport('async');

        $this->assertEquals('async', $configuration->getTransport());
    }

    public function testCallbackThrow()
    {
        $inspector = new Inspector(
            (new Configuration('example-key'))->setEnabled(false)
        );

        $inspector->startTransaction('transaction-test');

        $this->expectException(\Exception::class);

        $inspector->addSegment(function () {
            throw new \Exception();
        }, 'callback', 'test callback', true);
    }

    public function testCallbackReturn()
    {
        $configuration = new Configuration('example-key');
        $configuration->setEnabled(false);

        $inspector = new Inspector($configuration);
        $inspector->startTransaction('ttransaction-test');

        $return = $inspector->addSegment(function () {
            return 'Hello!';
        }, 'callback', 'test callback');

        $this->assertSame('Hello!', $return);
    }

    public function testAddSegmentWithInput()
    {
        $configuration = new Configuration('example-key');
        $configuration->setEnabled(false);

        $inspector = new Inspector($configuration);
        $inspector->startTransaction('ttransaction-test');

        $inspector->addSegment(function ($segment) {
            $this->assertInstanceOf(Segment::class, $segment);
        }, 'callback', 'test callback', true);
    }

    public function testAddSegmentWithInputContext()
    {
        $configuration = new Configuration('example-key');
        $configuration->setEnabled(false);

        $inspector = new Inspector($configuration);
        $inspector->startTransaction('ttransaction-test');

        $segment = $inspector->addSegment(function ($segment) {
            return $segment->setContext(['foo' => 'bar']);
        }, 'callback', 'test callback', true);

        $this->assertEquals(['foo' => 'bar'], $segment->context);
    }
}
