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

namespace Laudis\Neo4j\TestkitBackend\Handlers;

use ArrayIterator;
use Bolt\error\MessageException;
use Ds\Map;
use Ds\Vector;
use Laudis\Neo4j\TestkitBackend\Contracts\RequestHandlerInterface;
use Laudis\Neo4j\TestkitBackend\Contracts\TestkitResponseInterface;
use Laudis\Neo4j\TestkitBackend\MainRepository;
use Laudis\Neo4j\TestkitBackend\Requests\SessionRunRequest;
use Laudis\Neo4j\TestkitBackend\Responses\DriverErrorResponse;
use Laudis\Neo4j\TestkitBackend\Responses\ResultResponse;
use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\CypherMap;
use Symfony\Component\Uid\Uuid;

/**
 * @implements RequestHandlerInterface<SessionRunRequest>
 */
final class SessionRun implements RequestHandlerInterface
{
    private MainRepository $repository;

    public function __construct(MainRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param SessionRunRequest $request
     */
    public function handle($request): TestkitResponseInterface
    {
        $session = $this->repository->getSession($request->getSessionId());
        try {
            $params = $this->decodeToValue($request->getParams());
            $result = $session->run($request->getCypher(), $params);
        } catch (MessageException $exception) {
            return new DriverErrorResponse(
                $request->getSessionId(),
                'todo',
                $exception->getMessage(),
                'todo'
            );
        }
        $id = Uuid::v4();
        $this->repository->addRecords($id, new ArrayIterator($result->toArray()));

        return new ResultResponse($id, $result->isEmpty() ? [] : $result->first()->keys());
    }

    /**
     * @param SessionRunRequest $request
     */
    private function decodeToValue(array $params): array
    {
        $tbr = [];
        foreach ($params as $key => $param) {
            if ($param['name'] === 'CypherMap') {
                $tbr[$key] = new CypherMap(new Map($this->decodeToValue($param['data']['value'])));
            } elseif ($param['name'] === 'CypherList') {
                $tbr[$key] = new CypherList(new Vector($this->decodeToValue($param['data']['value'])));
            } else {
                $tbr[$key] = $param['data']['value'];
            }
        }

        return $tbr;
    }
}
