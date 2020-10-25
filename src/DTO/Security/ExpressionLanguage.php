<?php

declare(strict_types=1);

namespace Solido\Symfony\DTO\Security;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;

use function Safe\sprintf;

class ExpressionLanguage extends BaseExpressionLanguage
{
    protected function registerFunctions(): void
    {
        $this->addFunction(ExpressionFunction::fromPhp('constant'));

        $this->register('is_granted', static function ($attributes, $object = 'null') {
            return sprintf('$auth_checker->isGranted(%s, %s)', $attributes, $object);
        }, static function (array $variables, $attributes, $object = null) {
            return $variables['auth_checker']->isGranted($attributes, $object);
        });
    }
}
