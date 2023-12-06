<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\View\Controller;

use Solido\Symfony\Annotation\View;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

class TestController extends AbstractController
{
    #[View]
    public function indexAction(): array
    {
        return [
            'test_foo' => 'foo.test',
        ];
    }

    #[View]
    public function formInvalidExceptionAction(): void
    {
        /** @var FormInterface $form */
        $form = $this->createFormBuilder()
            ->add('first')
            ->add('second')
            ->getForm();

        $form->submit(['first' => 'one', 'second' => 'two']);
        $form['first']->addError(new FormError('Foo error.'));

        throw new FormInvalidException($form);
    }

    #[View]
    public function formNotSubmittedExceptionAction(): void
    {
        $form = $this->createFormBuilder()
            ->add('first')
            ->add('second')
            ->getForm()
        ;

        throw new FormNotSubmittedException($form);
    }

    #[View(serializationType: "stdClass")]
    public function invalidJsonExceptionAction(): void
    {
        throw new InvalidJSONException('Invalid.');
    }

    #[View(serializationType: "array<FooObject>")]
    public function customSerializationTypeAction(): array
    {
        return [
            ['data' => 'foobar'],
            ['test' => 'barbar'],
        ];
    }

    #[View(serializationType: "array<FooObject>")]
    public function customSerializationTypeWithIteratorAction(): iterable
    {
        return new \ArrayIterator([
            ['data' => 'foobar'],
            ['test' => 'barbar'],
        ]);
    }

    /**
     * @deprecated
     */
    #[View]
    public function deprecatedAction(): array
    {
        return ['foo' => 'bar'];
    }

    /**
     * @deprecated With Notice
     */
    #[View]
    public function deprecatedWithNoticeAction(): array
    {
        return ['foo' => 'bar'];
    }
}
