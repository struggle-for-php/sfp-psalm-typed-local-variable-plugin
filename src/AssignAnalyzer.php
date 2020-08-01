<?php

declare(strict_types=1);

namespace Sfp\Psalm\TypedLocalVariablePlugin;

use PhpParser;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\Statements\Expression\SimpleTypeInferer;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\ArgumentTypeCoercion;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\InvalidScalarArgument;
use Psalm\Issue\MixedArgumentTypeCoercion;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;
use Sfp\Psalm\TypedLocalVariablePlugin\Issue\InvalidScalarTypedLocalVariableIssue;
use Sfp\Psalm\TypedLocalVariablePlugin\Issue\InvalidTypedLocalVariableIssue;

class AssignAnalyzer
{
    public static function analyzeAssign(PhpParser\Node\Expr\Assign $expr, Union $firstDeclare, Codebase $codebase, StatementsSource $statements_source): void
    {
        $assignType = self::analyzeAssignmentType($expr, $codebase, $statements_source);

        if (! $assignType) {
            // could not analyzed
            return;
        }

        $lower_bound_type = $firstDeclare;
        if (! $firstDeclare->from_docblock) {
            $lower_bound_type = self::rollupLiteral($firstDeclare);
        }

        self::typeComparison($assignType, $lower_bound_type, $statements_source, new CodeLocation($statements_source, $expr->expr), null);
    }

    private static function rollupLiteral(Union $firstDeclare): Union
    {
        $types = [];
        foreach ($firstDeclare->getAtomicTypes() as $atomicType) {
            if ($atomicType instanceof TLiteralString) {
                $types[] = new TString();
            } elseif ($atomicType instanceof TLiteralInt) {
                $types[] = new TInt();
            } elseif ($atomicType instanceof TLiteralFloat) {
                $types[] = new TFloat();
            } else {
                $types[] = $atomicType;
            }
        }

        return new Union($types);
    }

    private static function analyzeAssignmentType(PhpParser\Node\Expr\Assign $expr, Codebase $codebase, StatementsSource $statements_source): ?Union
    {
        if ($expr->expr instanceof PhpParser\Node\Expr\ConstFetch) {
            return SimpleTypeInferer::infer(
                $codebase,
                new NodeDataProvider(),
                $expr->expr,
                $statements_source->getAliases()
            );
        }

        return $statements_source->getNodeTypeProvider()->getType($expr->expr);
    }

    private static function typeComparison(Union $upper_bound_type, Union $lower_bound_type, StatementsSource $statements_analyzer, CodeLocation $code_location, ?string $function_id): void
    {
        $union_comparison_result = new TypeComparisonResult();

        if (UnionTypeComparator::isContainedBy(
            $statements_analyzer->getCodebase(),
            $upper_bound_type,
            $lower_bound_type,
            false,
            false,
            $union_comparison_result
        )
        ) {
            return;
        }

        if ($union_comparison_result->type_coerced) {
            if ($union_comparison_result->type_coerced_from_mixed) {
                if (IssueBuffer::accepts(
                    new MixedArgumentTypeCoercion(
                        'Type ' . $upper_bound_type->getId() . ' should be a subtype of '
                            . $lower_bound_type->getId(),
                        $code_location,
                        $function_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )
                ) {
                    // continue
                }
            } else {
                if (IssueBuffer::accepts(
                    new ArgumentTypeCoercion(
                        'Type ' . $upper_bound_type->getId() . ' should be a subtype of '
                            . $lower_bound_type->getId(),
                        $code_location,
                        $function_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )
                ) {
                    // continue
                }
            }
        } elseif ($union_comparison_result->scalar_type_match_found) {
            if (IssueBuffer::accepts(
                new InvalidScalarTypedLocalVariableIssue(
                    'Type ' . $upper_bound_type->getId() . ' should be a subtype of '
                        . $lower_bound_type->getId(),
                    $code_location,
                    $function_id
                ),
                $statements_analyzer->getSuppressedIssues()
            )
            ) {
                // continue
            }
        } else {
            if (IssueBuffer::accepts(
                new InvalidTypedLocalVariableIssue(
                    'Type ' . $upper_bound_type->getId() . ' should be a subtype of '
                        . $lower_bound_type->getId(),
                    $code_location,
                    $function_id
                ),
                $statements_analyzer->getSuppressedIssues()
            )
            ) {
                // continue
            }
        }
    }
}
