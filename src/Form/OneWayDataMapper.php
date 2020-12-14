<?php

declare(strict_types=1);

namespace Solido\Symfony\Form;

use DateTimeInterface;
use Solido\DataTransformers\Exception\TransformationFailedException;
use Solido\Symfony\Form\DataAccessor\CallbackAccessor;
use Solido\Symfony\Form\DataAccessor\ChainAccessor;
use Solido\Symfony\Form\DataAccessor\DataAccessorInterface;
use Solido\Symfony\Form\DataAccessor\PropertyPathAccessor;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormError;

use function get_debug_type;
use function is_array;
use function is_object;
use function is_scalar;
use function Safe\array_replace;

class OneWayDataMapper implements DataMapperInterface
{
    private DataAccessorInterface $dataAccessor;

    public function __construct(?DataAccessorInterface $dataAccessor = null)
    {
        $this->dataAccessor = $dataAccessor ?? new ChainAccessor([
            new CallbackAccessor(),
            new PropertyPathAccessor(),
        ]);
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
            $config = $form->getConfig();

            if ($empty || ! $config->getMapped() || ! $this->dataAccessor->isReadable($data, $form)) {
                $form->setData($config->getData());
            } elseif ($config->getCompound()) {
                $form->setData($this->dataAccessor->getValue($data, $form));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mapFormsToData(iterable $forms, &$data): void
    {
        if ($data === null) {
            return;
        }

        if (! is_array($data) && ! is_object($data)) {
            throw new UnexpectedTypeException($data, 'object, array or empty');
        }

        foreach ($forms as $form) {
            $config = $form->getConfig();

            // Write-back is disabled if the form is not synchronized (transformation failed),
            // if the form was not submitted and if the form is disabled (modification not allowed)
            if (! $config->getMapped() || ! $form->isSubmitted() || ! $form->isSynchronized() || $form->isDisabled() || ! $this->dataAccessor->isWritable($data, $form)) {
                continue;
            }

            $propertyValue = $form->getData();

            // If the field is of type DateTimeInterface and the data is the same skip the update to
            // keep the original object hash
            // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
            if ($propertyValue instanceof DateTimeInterface && $propertyValue == $this->dataAccessor->getValue($data, $form)) {
                continue;
            }

            try {
                $this->dataAccessor->setValue($data, $form->getData(), $form);
            } catch (TransformationFailedException $e) {
                $viewData = $form->getViewData();
                $dataAsString = is_scalar($viewData) ? (string) $viewData : get_debug_type($viewData);

                $form->addError(new FormError(
                    $config->getOption('invalid_message'),
                    $config->getOption('invalid_message'),
                    array_replace(['{{ value }}' => $dataAsString], $config->getOption('invalid_message_parameters') ?? []),
                    null,
                    $e
                ));
            }
        }
    }
}
