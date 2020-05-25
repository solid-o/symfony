<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\View\Serializer;

use Kcs\Serializer\Context;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;

class FooHandler implements SubscribingHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods(): iterable
    {
        yield [
            'type' => 'FooObject',
            'direction' => Direction::DIRECTION_SERIALIZATION,
            'method' => 'serialize',
        ];
    }

    /**
     * @param VisitorInterface $visitor
     * @param array            $data
     * @param Type             $type
     * @param Context          $context
     *
     * @return mixed
     */
    public function serialize(VisitorInterface $visitor, array $data, Type $type, Context $context)
    {
        $data['additional'] = 'foo';

        return $visitor->visitHash($data, $type, $context);
    }
}
