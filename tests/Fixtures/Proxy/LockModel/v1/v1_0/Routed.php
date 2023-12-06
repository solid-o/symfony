<?php

declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\Proxy\LockModel\v1\v1_0;

use Kcs\Serializer\Annotation\Exclude;
use Solido\Symfony\Annotation\Lock;
use Solido\Symfony\Tests\Fixtures\Proxy\LockModel\Contracts\RoutedInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Lock\LockFactory;

class Routed implements RoutedInterface
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var bool
     */
    public $locked;

    #[Exclude]
    private LockFactory $lockFactory;

    public function __construct(LockFactory $lockFactory)
    {
        $this->lockFactory = $lockFactory;
    }

    #[Lock("'lock_' ~ request.getClientIp()")]
    public function routed(Request $request): self
    {
        $this->id = 'what_a_nice_id';

        $lock = $this->lockFactory->createLock('lock_' . $request->getClientIp());

        // Do not block. If "isAcquired" returns true, then the extension has failed to acquire the lock.
        $lock->acquire(false);
        $this->locked = $lock->isAcquired();

        return $this;
    }
}
