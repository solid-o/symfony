<?php

declare(strict_types=1);

namespace Solido\Symfony\Serialization\View;

use Iterator;
use IteratorAggregate;
use Refugis\DoctrineExtra\ObjectIteratorInterface;
use Solido\DataMapper\MappingResultInterface;
use Solido\Pagination\PagerIterator;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;

use function iterator_to_array;

/**
 * Value holder, to be handled by ViewHandler and serialized.
 */
final class View
{
    /** @var array<string, string|int> */
    public array $headers;
    public string|null $serializationType;

    /** @var string[]|null */
    public array|null $serializationGroups;
    public bool $serializeNull = true;
    public bool $enableMaxDepthChecks = false;

    public function __construct(public mixed $result, public int $statusCode = Response::HTTP_OK)
    {
        $this->headers = [];
        $this->serializationGroups = null;
        $this->serializationType = null;

        if ($result instanceof Form) {
            if (! $result->isSubmitted()) {
                $result->submit(null);
            }

            if (! $result->isValid()) {
                $this->statusCode = Response::HTTP_BAD_REQUEST;
            }

            return;
        }

        if ($result instanceof MappingResultInterface) {
            $this->statusCode = Response::HTTP_BAD_REQUEST;

            return;
        }

        if ($result instanceof IteratorAggregate) {
            $result = $result->getIterator();
        }

        if ($result instanceof ObjectIteratorInterface) {
            $count = $result->count();
            if ($count !== null) {
                $this->headers['X-Total-Count'] = (string) $count;
            }
        }

        if ($result instanceof PagerIterator) {
            $this->headers['X-Continuation-Token'] = (string) $result->getNextPageToken();
        }

        if (! ($result instanceof Iterator)) {
            return;
        }

        $this->result = iterator_to_array($result);
    }
}
