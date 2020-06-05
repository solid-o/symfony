<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Request;

use Solido\Symfony\Request\FormatGuesser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class FormatGuesserTest extends TestCase
{
    private FormatGuesser $guesser;

    protected function setUp(): void
    {
        $this->guesser = new FormatGuesser([ 'application/json', 'application/xml', 'text/html' ], 'application/json');
    }

    public function testGuesserShouldReturnDefaultFormatIfAcceptHeaderIsNotPresent(): void
    {
        self::assertEquals('application/json', $this->guesser->guess(new Request()));
    }

    public function testGuesserShouldReturnTheBestFormatForTheRequest(): void
    {
        $request = new Request();
        $request->headers->set('Accept', 'text/html; q=1.0, application/json; q=5.0, application/xml; q=15.0');

        self::assertEquals('application/xml', $this->guesser->guess($request));
    }

    public function testGuesserShouldSkipVersionParameter(): void
    {
        $request = new Request();
        $request->headers->set('Accept', 'application/xml; version=1.3; q=15.0, text/html; q=5.0; version=12');

        self::assertEquals('application/xml', $this->guesser->guess($request));
    }
}
