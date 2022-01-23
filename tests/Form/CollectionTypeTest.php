<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Form;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Solido\Symfony\Form\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType as BaseType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollectionTypeTest extends TestCase
{
    use ProphecyTrait;

    private CollectionType $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->type = new CollectionType();
    }

    public function testGetParentShouldReturnBaseCollectionTypeFullyQualifiedClassName(): void
    {
        self::assertEquals(BaseType::class, $this->type->getParent());
    }

    public function testConfigureOptionsShouldConfigureDefaults(): void
    {
        $resolver = $this->prophesize(OptionsResolver::class);
        $resolver
            ->setDefaults([
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'error_bubbling' => false,
            ])
            ->shouldBeCalled()
            ->willReturn($resolver)
        ;

        $this->type->configureOptions($resolver->reveal());
    }
}
