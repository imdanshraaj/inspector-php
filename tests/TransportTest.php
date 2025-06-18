<?php

namespace Inspector\Tests;

use Inspector\Configuration;
use Inspector\Models\Transaction;
use Inspector\Transports\CurlTransport;
use PHPUnit\Framework\TestCase;

class TransportTest extends TestCase
{
    public function testDefaultMaxItems()
    {
        $transport = new CurlTransport(new Configuration('foo'));

        $transaction = new Transaction('test');

        $transport->addEntry($transaction);

        $this->assertCount(1, $transport->getQueue());

        for ($i = 0; $i < 150; $i++) {
            $transport->addEntry($transaction);
        }

        // A transaction + 100 segments
        $this->assertCount(101, $transport->getQueue());
    }

    public function testIncreaseMaxItems()
    {
        $transport = new CurlTransport(
            (new Configuration('foo'))->setMaxItems(150)
        );

        $transaction = new Transaction('test');

        for ($i = 0; $i < 150; $i++) {
            $transport->addEntry($transaction);
        }

        $this->assertCount(150, $transport->getQueue());
    }
}
