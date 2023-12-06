<?php

declare(strict_types=1);

namespace Solido\Symfony\Request;

use Negotiation\Accept;
use Negotiation\Exception\InvalidMediaType;
use Negotiation\Negotiator;
use Symfony\Component\HttpFoundation\Request;

use function assert;
use function is_string;
use function preg_replace;

class FormatGuesser implements FormatGuesserInterface
{
    /** @var string[] */
    private array $priorities;

    /** @param string[] $priorities */
    public function __construct(array $priorities, private string|null $defaultType)
    {
        $this->priorities = (static fn (string ...$v) => $v)(...$priorities);
    }

    public function guess(Request $request): string|null
    {
        if ($this->defaultType === null && ! $request->headers->has('Accept')) {
            return null;
        }

        $requestHeader = $request->headers->get('Accept', $this->defaultType);
        assert(is_string($requestHeader), 'Accept header is not a string');

        $requestHeader = preg_replace('/;\s*version=.+?(?=;|$)/', '', $requestHeader);
        assert($requestHeader !== null);

        $negotiator = new Negotiator();
        try {
            $header = $negotiator->getBest($requestHeader, $this->priorities);
        } catch (InvalidMediaType) {
            return null;
        }

        assert($header === null || $header instanceof Accept);
        if ($header === null) {
            return null;
        }

        return $header->getType();
    }
}
