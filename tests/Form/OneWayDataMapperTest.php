<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Form;

use Solido\DataTransformers\Exception\TransformationFailedException;
use Solido\Symfony\Form\CollectionType;
use Solido\Symfony\Form\FormTypeExtension;
use Solido\Symfony\Form\OneWayDataMapper;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\PropertyAccess\PropertyPath;

class OneWayDataMapperTest extends TypeTestCase
{
    use ValidatorExtensionTrait;

    private OneWayDataMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mapper = new OneWayDataMapper();
    }

    protected function getTypeExtensions(): array
    {
        return [
            new FormTypeExtension(new OneWayDataMapper()),
        ];
    }

    public function testShouldNotSetDataToNonCompoundForm(): void
    {
        $form = $this->factory
            ->createNamedBuilder('', FormType::class, [ 'foo' => 'bar', 'bar' => 'foo' ])
            ->add('foo')
            ->add('bar')
            ->getForm();

        self::assertNull($form->get('foo')->getData());
        self::assertNull($form->get('bar')->getData());
    }

    public function testShouldNotSetDataToCompoundForm(): void
    {
        $form = $this->factory
            ->createNamedBuilder('', FormType::class, [
                'foo' => 'bar',
                'bar' => 'foo',
                'bbuz' => [
                    'foobar',
                    'barbar'
                ],
                'baz' => [
                    'barbaz' => 0,
                ]
            ])
            ->add('foo')
            ->add('bar')
            ->add('bbuz', CollectionType::class, [
                'entry_type' => TextType::class,
            ])
            ->add($this->builder->create('baz', FormType::class)->add('barbaz'))
            ->getForm();

        self::assertNull($form->get('foo')->getData());
        self::assertNull($form->get('bar')->getData());
        self::assertEquals(['barbaz' => 0 ], $form->get('baz')->getData());
        self::assertNull($form->get('baz')->get('barbaz')->getData());
        self::assertEquals([
            'foobar',
            'barbar'
        ], $form->get('bbuz')->getData());
    }

    public function testMapFormsToDataWritesBackIfNotByReference(): void
    {
        $car = new \stdClass();
        $car->engine = new \stdClass();
        $engine = new \stdClass();
        $engine->brand = 'Rolls-Royce';
        $propertyPath = new PropertyPath('engine');

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(false);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $form = new SubmittedForm($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $car);

        self::assertEquals($engine, $car->engine);
        self::assertNotSame($engine, $car->engine);
    }

    public function testMapFormsToDataWritesBackIfByReferenceButNoReference(): void
    {
        $car = new \stdClass();
        $car->engine = new \stdClass();
        $engine = new \stdClass();
        $propertyPath = new PropertyPath('engine');

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $form = new SubmittedForm($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $car);

        self::assertSame($engine, $car->engine);
    }

    public function testMapFormsToDataWritesBackIfByReferenceAndReference(): void
    {
        $car = new \stdClass();
        $car->engine = 'BMW';
        $propertyPath = new PropertyPath('engine');

        $config = new FormConfigBuilder('engine', null, $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData('Rolls-Royce');
        $form = new SubmittedForm($config);

        $car->engine = 'Rolls-Royce';

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $car);

        self::assertSame('Rolls-Royce', $car->engine);
    }

    public function testMapFormsToDataIgnoresUnmapped(): void
    {
        $initialEngine = new \stdClass();
        $car = new \stdClass();
        $car->engine = $initialEngine;
        $engine = new \stdClass();
        $propertyPath = new PropertyPath('engine');

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $config->setMapped(false);
        $form = new SubmittedForm($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $car);

        self::assertSame($initialEngine, $car->engine);
    }

    public function testMapFormsToDataIgnoresUnsubmittedForms(): void
    {
        $initialEngine = new \stdClass();
        $car = new \stdClass();
        $car->engine = $initialEngine;
        $engine = new \stdClass();
        $propertyPath = new PropertyPath('engine');

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $form = new Form($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $car);

        self::assertSame($initialEngine, $car->engine);
    }

    public function testMapFormsToDataIgnoresEmptyData(): void
    {
        $initialEngine = new \stdClass();
        $car = new \stdClass();
        $car->engine = $initialEngine;
        $propertyPath = new PropertyPath('engine');

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData(null);
        $form = new Form($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $car);

        self::assertSame($initialEngine, $car->engine);
    }

    public function testMapFormsToDataIgnoresUnsynchronized(): void
    {
        $initialEngine = new \stdClass();
        $car = new \stdClass();
        $car->engine = $initialEngine;
        $engine = new \stdClass();
        $propertyPath = new PropertyPath('engine');

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $form = new NotSynchronizedForm($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $car);

        self::assertSame($initialEngine, $car->engine);
    }

    public function testMapFormsToDataIgnoresDisabled(): void
    {
        $initialEngine = new \stdClass();
        $car = new \stdClass();
        $car->engine = $initialEngine;
        $engine = new \stdClass();
        $propertyPath = new PropertyPath('engine');

        $config = new FormConfigBuilder('name', \stdClass::class, $this->dispatcher);
        $config->setByReference(true);
        $config->setPropertyPath($propertyPath);
        $config->setData($engine);
        $config->setDisabled(true);
        $form = new SubmittedForm($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $car);

        self::assertSame($initialEngine, $car->engine);
    }

    /**
     * @requires PHP 7.4
     */
    public function testMapFormsToUninitializedProperties(): void
    {
        $car = new TypehintedPropertiesCar();
        $config = new FormConfigBuilder('engine', null, $this->dispatcher);
        $config->setData('BMW');
        $form = new SubmittedForm($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $car);

        self::assertSame('BMW', $car->engine);
    }

    /**
     * @dataProvider provideDate
     */
    public function testMapFormsToDataDoesNotChangeEqualDateTimeInstance($date): void
    {
        $article = [];
        $publishedAt = $date;
        $publishedAtValue = clone $publishedAt;
        $article['publishedAt'] = $publishedAtValue;
        $propertyPath = new PropertyPath('[publishedAt]');

        $config = new FormConfigBuilder('publishedAt', \get_class($publishedAt), $this->dispatcher);
        $config->setByReference(false);
        $config->setPropertyPath($propertyPath);
        $config->setData($publishedAt);
        $form = new SubmittedForm($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $article);

        self::assertSame($publishedAtValue, $article['publishedAt']);
    }

    public function provideDate(): array
    {
        return [
            [new \DateTime()],
            [new \DateTimeImmutable()],
        ];
    }

    public function testMapFormsToDataUsingSetCallbackOption(): void
    {
        $person = new DummyPerson('John Doe');

        $config = new FormConfigBuilder('name', null, $this->dispatcher, [
            'setter' => static function (DummyPerson $person, $name) {
                $person->rename($name);
            },
        ]);
        $config->setData('Jane Doe');
        $form = new SubmittedForm($config);

        $this->mapper->mapFormsToData(new \ArrayIterator([$form]), $person);

        self::assertSame('Jane Doe', $person->myName());
    }

    public function testShouldMapTransformationExceptionToTheRightForm(): void
    {
        $person = new DummyPerson('John Doe');

        $form = $this->factory->createNamedBuilder('', FormType::class, $person, [
            'data_class' => DummyPerson::class
        ])
            ->add('name', null, [
                'setter' => static function (DummyPerson $person, $name) {
                    throw new TransformationFailedException();
                },
            ])
            ->getForm();
        $form->submit(['name' => 'Jane Doe']);

        self::assertCount(0, $form->getErrors());
        self::assertCount(1, $form->get('name')->getErrors());
    }
}

class SubmittedForm extends Form
{
    public function isSubmitted(): bool
    {
        return true;
    }
}

class NotSynchronizedForm extends SubmittedForm
{
    public function isSynchronized(): bool
    {
        return false;
    }
}

class DummyPerson
{
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function myName(): string
    {
        return $this->name;
    }

    public function rename($name): void
    {
        $this->name = $name;
    }
}

class TypehintedPropertiesCar
{
    public ?string $engine;
    public ?string $color;
}
