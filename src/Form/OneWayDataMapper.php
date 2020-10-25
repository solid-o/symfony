<?php

declare(strict_types=1);

namespace Solido\Symfony\Form;

use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use function is_array;
use function is_object;

class OneWayDataMapper extends PropertyPathMapper
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(?PropertyAccessorInterface $propertyAccessor = null)
    {
        parent::__construct($propertyAccessor);
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function mapDataToForms($data, iterable $forms): void
    {
        $empty = $data === null || $data === [];

        if (! $empty && ! is_array($data) && ! is_object($data)) {
            throw new UnexpectedTypeException($data, 'object, array or empty');
        }

        foreach ($forms as $form) {
            $propertyPath = $form->getPropertyPath();
            $config = $form->getConfig();

            if ($empty || $propertyPath === null || ! $config->getMapped()) {
                $form->setData($config->getData());
            } elseif ($config->getCompound()) {
                $form->setData($this->propertyAccessor->getValue($data, $propertyPath));
            }
        }
    }
}
