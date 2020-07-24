<?php
declare(strict_types=1);

namespace Sfp\Psalm\TypedLocalVariablePlugin;

use PhpParser;
use PhpParser\Node\Stmt;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\IssueBuffer;
use Psalm\Plugin\Hook\AfterExpressionAnalysisInterface;
use Psalm\Plugin\Hook\AfterStatementAnalysisInterface;
use Psalm\StatementsSource;
use Psalm\Type\Union;

final class TypedLocalVariableChecker implements AfterExpressionAnalysisInterface, AfterStatementAnalysisInterface
{
    public static function afterStatementAnalysis(
        Stmt $stmt,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ) {
//        var_dump($stmt->getDocComment());
        var_dump($context->getScopeSummary());
    }


    /**
     * Called after an expression has been checked
     *
     * @param  PhpParser\Node\Expr  $expr
     * @param  Context              $context
     * @param  FileManipulation[]   $file_replacements
     *
     * @return null|false
     */
    public static function afterExpressionAnalysis(
        PhpParser\Node\Expr $expr,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ) {
        return null;

        if ($expr instanceof PhpParser\Node\Expr\Assign) {

            $doc_comment = $expr->var->getDocComment();

            if ($doc_comment) {
                $types = CommentAnalyzer::getTypeFromComment($expr->var->getDocComment(), $statements_source, $statements_source->getAliases());

                if (count($types) === 0) {
                    return null;
                }

                $originalTypes = [];
                foreach ($types as $type) {
                    if ($type->type instanceof Union) {
                        foreach ($type->type->getAtomicTypes() as $atomicType) {
                            $originalTypes[] = (string)$atomicType;
                        }
                    } else {
                        $originalTypes[] = $type->original_type;
                    }
                }

                $expr_type = $statements_source->getNodeTypeProvider()->getType($expr->expr);

                $type_matched = false;
                $atomicTypes = [];
                foreach ($expr_type->getAtomicTypes() as $k => $atomicType) {
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
                            sprintf('original types are %s, but assigned types are %s', implode(',', $originalTypes), implode(',', $atomicTypes)),
                            new CodeLocation($statements_source, $expr->expr)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {

                    }
                }
            }
        }
    }
}

