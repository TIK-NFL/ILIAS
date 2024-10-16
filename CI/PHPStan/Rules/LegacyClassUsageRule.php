<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

namespace ILIAS\CI\PHPStan\Rules;

use PhpParser\Node\Expr\CallLike;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

abstract class LegacyClassUsageRule implements Rule
{
    protected ReflectionProvider $reflectionProvider;
    protected \PHPStan\Rules\Generics\GenericAncestorsCheck $genericAncestorsCheck;
    private array $forbidden_classes = [];
    private array $ancestor_cache = [];

    public function __construct(
        ReflectionProvider $reflectionProvider,
        \PHPStan\Rules\Generics\GenericAncestorsCheck $genericAncestorsCheck
    ) {
        $this->reflectionProvider = $reflectionProvider;
        $this->genericAncestorsCheck = $genericAncestorsCheck;

        // Determine possible class-names (parents and children) of the forbidden classes
        $forbidden_classes = [];

        foreach ($this->getForbiddenClasses() as $forbidden_class) {
            $ancestors = $this->getClassAncestors($forbidden_class);
            $this->cacheAncestors($forbidden_class, $ancestors);
            $forbidden_classes = array_merge(
                $forbidden_classes,
                $ancestors
            );
        }

        $this->forbidden_classes = array_unique($forbidden_classes);
    }

    private function getClassAncestors(string $class_name): array
    {
        if (isset($this->ancestor_cache[$class_name])) {
            return $this->ancestor_cache[$class_name];
        }

        $ancestors[] = $class_name;

        try {
            $reflection = $this->reflectionProvider->getClass($class_name);
            $ancestors = array_merge($ancestors, $reflection->getParentClassesNames());
        } catch (\PHPStan\Broker\ClassNotFoundException $e) {
            // Do nothing
        } finally {
            unset($reflection);
        }
        return array_unique($ancestors);
    }

    private function cacheAncestors($class_name, array $ancestor_classes): void
    {
        $this->ancestor_cache[$class_name] = $ancestor_classes;
    }

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    protected function findInstanceCreation(): bool
    {
        return true;
    }

    protected function findMethodUsages(): bool
    {
        return false;
    }

    abstract protected function getForbiddenClasses(): array;

    abstract protected function getHumanReadableRuleName(): string;

    abstract protected function getRelevantILIASVersion(): int;

    private function instantiatesForbiddenClasses(Node $node): bool
    {
        if ($node->class instanceof Node\Name) {
            $class_name = $node->class->toString();
        } else {
            return false;
        }
        $class_names_to_test = $this->getClassAncestors($class_name);

        $array_intersect = array_intersect($class_names_to_test, $this->forbidden_classes);
        if ($array_intersect !== []) {
            $this->cacheAncestors($class_name, $class_names_to_test);
            return true;
        }
        return false;
    }

    private function usesMethodsOfForbiddenClass(Node $node, Scope $scope): bool
    {
        if (!$node->name instanceof Node\Identifier) {
            return false;
        }
        $method_name = $node->name->name;
        $called_classes = $scope->getType($node->var)->getObjectClassNames();
        $forbidden_classes = $this->getForbiddenClasses();

        return array_intersect($called_classes, $forbidden_classes) !== [];
    }

    final public function processNode(Node $node, Scope $scope): array
    {
        // Find new Instance Creation or Static Method Calls
        if ($this->findInstanceCreation()
            && ($node instanceof Node\Expr\StaticCall || $node instanceof Node\Expr\New_)
            && $this->instantiatesForbiddenClasses($node)
        ) {
            $class_name = $node->class->toString();
            return [
                RuleErrorBuilder::message("Usage of $class_name is forbidden.")
                                ->metadata([
                                    'rule' => $this->getHumanReadableRuleName(),
                                    'version' => $this->getRelevantILIASVersion(),
                                ])
                                ->build()
            ];
        }

        // Find Method Calls to forbidden classes
        if ($node instanceof Node\Expr\MethodCall
            && $this->findMethodUsages()
            && $this->usesMethodsOfForbiddenClass($node, $scope)
        ) {
            $method_name = $node->name->name;
            $class_name = $scope->getType($node->var)->getObjectClassNames()[0];
            return [
                RuleErrorBuilder::message("Usage of $class_name::$method_name is forbidden.")
                                ->metadata([
                                    'rule' => $this->getHumanReadableRuleName(),
                                    'version' => $this->getRelevantILIASVersion(),
                                ])
                                ->build()
            ];
        }

        return [];
    }
}
