<?php


namespace LogEngine;


use LogEngine\Contracts\TransportInterface;
use LogEngine\Models\Error;
use LogEngine\Models\Span;
use LogEngine\Models\Transaction;
use LogEngine\Transport\AsyncTransport;
use LogEngine\Transport\CurlTransport;

class ApmAgent
{
    /**
     * Agent configuration.
     *
     * @var Configuration
     */
    protected $configuration;

    /**
     * Transport strategy.
     *
     * @var TransportInterface
     */
    protected $transport;

    /**
     * Current transaction.
     *
     * @var Transaction
     */
    protected $transaction;

    /**
     * Logger constructor.
     *
     * @param Configuration $configuration
     * @throws Exceptions\LogEngineApmException
     */
    public function __construct(Configuration $configuration)
    {
        switch (getenv('LOGENGINE_TRANSPORT')){
            case 'async':
                $this->transport = new AsyncTransport($configuration);
                break;
            default:
                $this->transport = new CurlTransport($configuration);
        }

        $this->configuration = $configuration;
        register_shutdown_function(array($this, 'flush'));
    }

    /**
     * Create and start new Transaction.
     *
     * @param string $name
     * @return Transaction
     * @throws \Exception
     */
    public function startTransaction($name)
    {
        $this->transaction = new Transaction($name);
        $this->transaction->start();
        $this->transport->addEntry($this->transaction);
        return $this->transaction;
    }

    /**
     * Get current transaction instance.
     *
     * @return Transaction
     */
    public function currentTransaction()
    {
        return $this->transaction;
    }

    /**
     * Add new span to the queue.
     *
     * @param $type
     * @return Span
     */
    public function startSpan($type)
    {
        $span = new Span($type, $this->transaction);
        $span->start();
        $this->transport->addEntry($span);
        return $span;
    }

    /**
     * Error reporting.
     *
     * @param \Throwable $exception
     * @return ApmAgent
     */
    public function reportException(\Throwable $exception)
    {
        if (!$exception instanceof \Exception && !$exception instanceof \Throwable) {
            throw new \InvalidArgumentException('$exception need to be an instance of Exception or Throwable.');
        }

        $this->transport->addEntry(new Error($exception, $this->transaction));
        return $this;
    }

    /**
     * Flush queue to the remote platform.
     *
     * @throws \Exception
     */
    public function flush()
    {
        if(isset($this->transaction)){
            $this->transaction->end();

            if($this->configuration->isEnabled()){
                $this->transport->flush();
            }
        }

    }
}