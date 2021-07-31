<?php

declare(strict_types=1);

/*
 * This file is part of the Laudis Neo4j package.
 *
 * (c) Laudis technologies <http://laudis.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Laudis\Neo4j\TestkitBackend\Input;

use Symfony\Component\Uid\Uuid;

final class SessionReadTransactionInput
{
    private Uuid $sessionId;
    private array $txMeta;
    private int $timeout;

    public function __construct(
        Uuid $sessionId,
        array $txMeta,
        int $timeout
    ) {
        $this->sessionId = $sessionId;
        $this->txMeta = $txMeta;
        $this->timeout = $timeout;
    }

    public function getSessionId(): Uuid
    {
        return $this->sessionId;
    }

    public function getTxMeta(): array
    {
        return $this->txMeta;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
