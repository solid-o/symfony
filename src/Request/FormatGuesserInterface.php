<?php

declare(strict_types=1);

namespace Solido\Symfony\Request;

use Symfony\Component\HttpFoundation\Request;

interface FormatGuesserInterface
{
    /**
     * Guess the best response format from Accept header.
     * Returns the mime type of the resulting format.
     */
    public function guess(Request $request): ?string;
}
