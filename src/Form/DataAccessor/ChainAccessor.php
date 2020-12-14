<?php

declare(strict_types=1);

namespace Solido\Symfony\Form\DataAccessor;

use Solido\Symfony\Form\Exception\AccessException;
use Symfony\Component\Form\FormInterface;

class ChainAccessor implements DataAccessorInterface
{
    /** @var iterable<DataAccessorInterface> */
    private iterable $accessors;

    /**
     * @param iterable<DataAccessorInterface> $accessors
     */
    public function __construct(iterable $accessors)
    {
        $this->accessors = $accessors;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($data, FormInterface $form)
    {
        foreach ($this->accessors as $accessor) {
            if ($accessor->isReadable($data, $form)) {
                return $accessor->getValue($data, $form);
            }
        }

        throw new AccessException('Unable to read from the given form data as no accessor in the chain is able to read the data.');
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(&$data, $value, FormInterface $form): void
    {
        foreach ($this->accessors as $accessor) {
            if ($accessor->isWritable($data, $form)) {
                $accessor->setValue($data, $value, $form);

                return;
            }
        }

        throw new AccessException('Unable to write the given value as no accessor in the chain is able to set the data.');
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable($data, FormInterface $form): bool
    {
        foreach ($this->accessors as $accessor) {
            if ($accessor->isReadable($data, $form)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable($data, FormInterface $form): bool
    {
        foreach ($this->accessors as $accessor) {
            if ($accessor->isWritable($data, $form)) {
                return true;
            }
        }

        return false;
    }
}
