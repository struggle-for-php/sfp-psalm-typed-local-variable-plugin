<?php
declare(strict_types=1);

namespace Sfp\Psalm\TypedLocalVariablePlugin;

use PhpParser;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\Statements\Expression\SimpleTypeInferer;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type\Union;

class AssignAnalyzer
{
    public static function analyzeAssign(PhpParser\Node\Expr\Assign $expr, Union $firstDeclare, Codebase $codebase, StatementsSource $statements_source)
    {
        $originalTypes = [];
        foreach ($firstDeclare->getAtomicTypes() as $atomicType) {
            $originalTypes[] = (string)$atomicType;
        }

        $assignType = self::analyzeAssignmentType($expr, $codebase, $statements_source);

        if (!$assignType) {
            // could not analyzed
            return null;
        }

        $type_matched = false;
        $atomicTypes = [];
        foreach ($assignType->getAtomicTypes() as $k => $atomicType) {
            if ($atomicType->isObjectType()) {
                $class = (string) $atomicType;
                foreach ($originalTypes as $originalType) {
                    if ($class === $originalType) {
                        $type_matched = true;
                        break;
                    }

                    if (class_exists($originalType)) {
                        $atomicTypes[] = $class;
                        if ((new \ReflectionClass($class))->isSubclassOf($originalType)) {
                            $type_matched = true;
                        }
                    }
                }
            } else {

                $atomicTypes[] = (string) $atomicType;
                if (in_array((string) $atomicType, $originalTypes, true)) {
                    $type_matched = true;
                }
            }

        }

        if (!$type_matched) {
            if (IssueBuffer::accepts(
                new UnmatchedTypeIssue(
                    sprintf('original types are %s, but assigned types are %s', implode('|', $originalTypes), implode('|', $atomicTypes)),
                    new CodeLocation($statements_source, $expr->expr)
                ),
                $statements_source->getSuppressedIssues()
            )) {

            }
        }
    }


    /**
     * @psalm-return Union|false
     */
    private static function analyzeAssignmentType(PhpParser\Node\Expr\Assign $expr, Codebase $codebase, StatementsSource $statements_source)
    {

        if ($expr->expr instanceof \Psalm\Type\Union) {
            return $statements_source->getNodeTypeProvider()->getType($expr->expr);
        }

        if ($expr->expr instanceof PhpParser\Node\Expr) {

            return SimpleTypeInferer::infer(
                $codebase,
                new \Psalm\Internal\Provider\NodeDataProvider(),
                $expr->expr,
                $statements_source->getAliases()
            );
        }

        return false;
    }
}