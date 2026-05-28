<?php

declare(strict_types=1);

namespace Gando\Partner\Scripts\Rector;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use Rector\Rector\AbstractRector;

final class MakeGeneratedDtoFinalReadonlyRector extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof Class_ || $node->isAnonymous()) {
            return null;
        }

        $fullyQualifiedName = $node->namespacedName?->toString() ?? '';
        if (
            ! str_starts_with($fullyQualifiedName, 'Gando\\Partner\\Models\\Components\\')
            && ! str_starts_with($fullyQualifiedName, 'Gando\\Partner\\Models\\Operations\\')
        ) {
            return null;
        }

        $className = $node->name?->toString() ?? '';
        if ($className === '' || str_ends_with($className, 'Response')) {
            return null;
        }

        if ($this->isMutableRuntimeModel($node)) {
            return null;
        }

        $node->flags |= Class_::MODIFIER_FINAL;
        $node->flags |= Class_::MODIFIER_READONLY;

        return $node;
    }

    private function isMutableRuntimeModel(Class_ $class): bool
    {
        foreach ($class->getMethods() as $method) {
            if ($method->name->toString() === '__call') {
                return true;
            }
        }

        foreach ($class->getProperties() as $property) {
            if (! $property instanceof Property) {
                continue;
            }

            foreach ($property->props as $prop) {
                if (in_array($prop->name->toString(), ['next', 'rawResponse'], true)) {
                    return true;
                }
            }
        }

        $constructor = $class->getMethod('__construct');
        if (! $constructor instanceof ClassMethod) {
            return false;
        }

        foreach ($constructor->params as $param) {
            if ($param->var->name === 'rawResponse') {
                return true;
            }
        }

        return false;
    }
}
