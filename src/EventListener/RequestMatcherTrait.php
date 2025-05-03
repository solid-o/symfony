<?php

declare(strict_types=1);

namespace Solido\Symfony\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

trait RequestMatcherTrait
{
    private RequestMatcherInterface|null $requestMatcher;

    public function setRequestMatcher(RequestMatcherInterface|null $requestMatcher): void
    {
        $this->requestMatcher = $requestMatcher;
    }

    private function requestMatches(Request $request): bool
    {
        return $this->requestMatcher?->matches($request) ?? true;
    }
}
