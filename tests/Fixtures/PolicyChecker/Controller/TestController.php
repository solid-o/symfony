<?php declare(strict_types=1);

namespace Solido\Symfony\Tests\Fixtures\PolicyChecker\Controller;

use Solido\PatchManager\Exception\FormInvalidException;
use Solido\Symfony\Tests\Fixtures\Proxy\SemVerModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Required;

class TestController extends AbstractController
{
    public function listFoo(): Response
    {
        return new JsonResponse([]);
    }

    public function invalidFoo(FormFactoryInterface $formFactory): Response
    {
        $form = $formFactory->create('')
            ->add('field_one', null, [ 'constraints' => [ new Required() ] ])
            ->add('field_two')
        ;

        $form->submit(['field_two' => 'great', 'field_three' => 'work!']);
        assert(!$form->isValid());

        throw new FormInvalidException($form);
    }
}
