<?php

declare(strict_types=1);

namespace Rector\Doctrine\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\Type\ArrayType;
use Rector\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\Core\NodeManipulator\ClassInsertManipulator;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\ValueObject\MethodName;
use Rector\Doctrine\NodeAnalyzer\AssignPropertyFetchAnalyzer;
use Rector\Doctrine\NodeFactory\ConstructClassMethodFactory;
use Rector\Doctrine\NodeFactory\ConstructorClassMethodAssignFactory;
use Rector\Doctrine\NodeFactory\ParamFactory;
use Rector\Doctrine\NodeManipulator\IssetDimFetchCleaner;
use Rector\Doctrine\ValueObject\AssignToPropertyFetch;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @changelog https://github.com/doctrine/annotations/blob/1.13.x/docs/en/custom.rst#optional-constructors-with-named-parameters
 *
 * @see \Rector\Doctrine\Tests\Rector\Class_\ClassAnnotationToNamedArgumentConstructorRector\ClassAnnotationToNamedArgumentConstructorRectorTest
 */
final class ClassAnnotationToNamedArgumentConstructorRector extends AbstractRector
{
    public function __construct(
        private readonly ParamFactory $paramFactory,
        private readonly ConstructClassMethodFactory $constructClassMethodFactory,
        private readonly ClassInsertManipulator $classInsertManipulator,
        private readonly AssignPropertyFetchAnalyzer $assignPropertyFetchAnalyzer,
        private readonly IssetDimFetchCleaner $issetDimFetchCleaner,
        private readonly ConstructorClassMethodAssignFactory $constructorClassMethodAssignFactory
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Decorate classic array-based class annotation with named parameters', [
            new CodeSample(
                <<<'CODE_SAMPLE'
/**
 * @Annotation
 */
class SomeAnnotation
{
    /**
     * @var string
     */
    private $foo;

    public function __construct(array $values)
    {
        $this->foo = $values['foo'];
    }
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor
 */
class SomeAnnotation
{
    /**
     * @var string
     */
    private $foo;

    public function __construct(string $foo)
    {
        $this->foo = $foo;
    }
}
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (! $phpDocInfo instanceof PhpDocInfo) {
            return null;
        }

        if ($this->shouldSkipPhpDocInfo($phpDocInfo)) {
            return null;
        }

        $doctrineAnnotationTagValueNode = new DoctrineAnnotationTagValueNode(
            new IdentifierTypeNode('Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor')
        );
        $phpDocInfo->addTagValueNode($doctrineAnnotationTagValueNode);

        $classMethod = $node->getMethod(MethodName::CONSTRUCT);
        if (! $classMethod instanceof ClassMethod) {
            return $this->decorateClassWithAssignClassMethod($node);
        }

        if (! $this->hasSingleArrayParam($classMethod)) {
            return null;
        }

        /** @var Variable $paramVariable */
        $paramVariable = $classMethod->params[0]->var;

        $optionalParamNames = $this->issetDimFetchCleaner->resolveOptionalParamNames($classMethod, $paramVariable);

        $this->issetDimFetchCleaner->removeArrayDimFetchIssets($classMethod, $paramVariable);

        $assignsToPropertyFetch = $this->assignPropertyFetchAnalyzer->resolveAssignToPropertyFetch($classMethod);
        $this->replaceAssignsByParam($assignsToPropertyFetch);

        $classMethod->params = $this->paramFactory->createFromAssignsToPropertyFetch(
            $assignsToPropertyFetch,
            $optionalParamNames
        );

        // include assigns for optional params - these do not have assign in the root, as they're hidden in if isset/check
        // so we have to add them
        $assigns = $this->constructorClassMethodAssignFactory->createFromParamNames($optionalParamNames);
        if ($assigns !== []) {
            $classMethod->stmts = array_merge((array) $classMethod->stmts, $assigns);
        }

        return $node;
    }

    private function shouldSkipPhpDocInfo(PhpDocInfo $phpDocInfo): bool
    {
        if (! $phpDocInfo->hasByNames(['annotation', 'Annotation'])) {
            return true;
        }

        return $phpDocInfo->hasByAnnotationClass('Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor');
    }

    private function hasSingleArrayParam(ClassMethod $classMethod): bool
    {
        if (count($classMethod->params) !== 1) {
            return false;
        }

        $onlyParam = $classMethod->params[0];

        // change array to properites
        if (! $onlyParam->type instanceof Node) {
            return false;
        }

        $paramType = $this->nodeTypeResolver->getType($onlyParam);
        // we have a match
        return $paramType instanceof ArrayType;
    }

    /**
     * @param AssignToPropertyFetch[] $assignsToPropertyFetch
     */
    private function replaceAssignsByParam(array $assignsToPropertyFetch): void
    {
        foreach ($assignsToPropertyFetch as $assignToPropertyFetch) {
            $assign = $assignToPropertyFetch->getAssign();
            $assign->expr = new Variable($assignToPropertyFetch->getPropertyName());
        }
    }

    private function decorateClassWithAssignClassMethod(Class_ $class): Class_ | null
    {
        // complete public properties
        $constructClassMethod = $this->constructClassMethodFactory->createFromPublicClassProperties($class);
        if (! $constructClassMethod instanceof ClassMethod) {
            return null;
        }

        $this->classInsertManipulator->addAsFirstMethod($class, $constructClassMethod);
        return $class;
    }
}
