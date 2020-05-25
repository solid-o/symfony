<?php

declare(strict_types=1);

namespace Solido\Symfony\Request;

use Negotiation\Accept;
use Negotiation\Exception\InvalidMediaType;
use Negotiation\Negotiator;
use Symfony\Component\HttpFoundation\Request;
use function assert;
use function is_string;

class FormatGuesser implements FormatGuesserInterface
{
    /** @var string[] */
    private array $priorities;
    private ?string $defaultType;

    /**
     * @param string[] $priorities
     */
    public function __construct(array $priorities, ?string $defaultType)
    {
        $this->priorities = (static fn (string ...$v) => $v)(...$priorities);
        $this->defaultType = $defaultType;
    }

    public function guess(Request $request): ?string
    {
        if ($this->defaultType === null && ! $request->headers->has('Accept')) {
            return null;
        }

        $requestHeader = $request->headers->get('Accept', $this->defaultType);
        assert(is_string($requestHeader), 'Accept header is not a string');

        $negotiator = new Negotiator();
        try {
            $header = $negotiator->getBest($requestHeader, $this->priorities);
        } catch (InvalidMediaType $exception) {
            return null;
        }

        assert($header === null || $header instanceof Accept);
        if ($header === null) {
            return null;
        }

        return $header->getType();
    }
}
