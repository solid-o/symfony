<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\EventListener;

use Solido\Symfony\Tests\Fixtures\BodyConverter\AppKernel;
use Solido\Symfony\Tests\WebTestCaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class BodyConverterTest extends WebTestCase
{
    use WebTestCaseTrait;

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new AppKernel('test', true);
    }

    public function testShouldDecodeContentCorrectly(): void
    {
        $client = static::createClient();

        $client->request('POST', '/', [], [], ['CONTENT_TYPE' => 'application/json'], '{ "options": { "option": false } }');
        $response = $client->getResponse();

        $array = <<<EOF
array:1 [
  "options" => array:1 [
    "option" => "0"
  ]
]
EOF;

        self::assertEquals($array, $response->getContent());
    }

    public function testShouldDecodeNotOverwriteRequestParametersIfBodyIsEmpty(): void
    {
        $client = static::createClient();

        $client->request('POST', '/', ['options' => '1'], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $response = $client->getResponse();

        $array = <<<EOF
array:1 [
  "options" => "1"
]
EOF;

        self::assertEquals($array, $response->getContent());
    }
}
