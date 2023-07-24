<?php

declare(strict_types=1);

namespace TomasVotruba\UnusedPublic\Collectors\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Reflection\ClassReflection;
use TomasVotruba\UnusedPublic\CallReferece\CallReferencesFlatter;
use TomasVotruba\UnusedPublic\ClassMethodCallReferenceResolver;
use TomasVotruba\UnusedPublic\ClassTypeDetector;
use TomasVotruba\UnusedPublic\Configuration;

/**
 * @implements Collector<MethodCallableNode, array<string>|null>
 */
final class MethodCallableCollector implements Collector
{
    public function __construct(
        private readonly ClassMethodCallReferenceResolver $classMethodCallReferenceResolver,
        private readonly Configuration $configuration,
        private readonly ClassTypeDetector $classTypeDetector,
        private readonly CallReferencesFlatter $callReferencesFlatter,
    ) {
    }

    public function getNodeType(): string
    {
        return MethodCallableNode::class;
    }

    /**
     * @param MethodCallableNode $node
     * @return string[]|null
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        if (! $this->configuration->shouldCollectMethods()) {
            return null;
        }

        // unable to resolve method name
        if ($node->getName() instanceof Expr) {
            return null;
        }

        $classReflection = $scope->getClassReflection();
        if (! $classReflection instanceof ClassReflection) {
            return null;
        }

        // skip calls in tests, as they are not used in production
        if ($this->classTypeDetector->isTestClass($classReflection)) {
            return null;
        }

        $classMethodCallReferences = $this->classMethodCallReferenceResolver->resolve($node->getOriginalNode(), $scope);

        return $this->callReferencesFlatter->flatten($classMethodCallReferences);
    }
}