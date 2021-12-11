<?php

declare(strict_types=1);

namespace Solido\Symfony\Form\DataAccessor;

use Solido\Symfony\Form\Exception\AccessException;
use Symfony\Component\Form\FormInterface;

use function assert;
use function is_callable;

/**
 * Writes and reads values to/from an object or array using callback functions.
 */
class CallbackAccessor implements DataAccessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getValue($data, FormInterface $form)
    {
        $getter = $form->getConfig()->getOption('getter');
        if ($getter === null) {
            throw new AccessException('Unable to read from the given form data as no getter is defined.');
        }

        assert(is_callable($getter));

        return ($getter)($data, $form);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(&$data, $value, FormInterface $form): void
    {
        $setter = $form->getConfig()->getOption('setter');
        if ($setter === null) {
            throw new AccessException('Unable to write the given value as no setter is defined.');
        }

        assert(is_callable($setter));
        ($setter)($data, $form->getData(), $form);
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable($data, FormInterface $form): bool
    {
        return $form->getConfig()->getOption('getter') !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable($data, FormInterface $form): bool
    {
        return $form->getConfig()->getOption('setter') !== null;
    }
}
