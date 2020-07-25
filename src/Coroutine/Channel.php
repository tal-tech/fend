<?php

namespace Fend\Coroutine;

use Swoole\Coroutine\Channel as CoChannel;

class Channel
{
    protected $size;

    /**
     * @var CoChannel
     */
    protected $channel;

    /**
     * @var \SplQueue
     */
    protected $queue;

    public function __construct(int $size)
    {
        $this->size = $size;
        if ($this->isCoroutine()) {
            $this->channel = new CoChannel($size);
        } else {
            $this->queue = new \SplQueue();
        }
    }

    public function pop(float $timeout)
    {
        if ($this->isCoroutine()) {
            return $this->channel->pop($timeout);
        }
        return $this->queue->shift();
    }

    public function push($data)
    {
        if ($this->isCoroutine()) {
            $this->channel->push($data);
        } else {
            $this->queue->push($data);
        }
    }

    public function length(): int
    {
        if ($this->isCoroutine()) {
            return $this->channel->length();
        }
        return $this->queue->count();
    }

    protected function isCoroutine(): bool
    {
        return Coroutine::getCid() > 0;
    }
}
