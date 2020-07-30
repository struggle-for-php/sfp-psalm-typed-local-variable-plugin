<?php
declare(strict_types=1);

namespace Sfp\Psalm\TypedLocalVariablePlugin;

use PhpParser;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\Plugin\Hook\AfterExpressionAnalysisInterface;
use Psalm\Plugin\Hook\AfterFunctionLikeAnalysisInterface;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionLikeStorage;

final class TypedLocalVariableChecker implements AfterExpressionAnalysisInterface, AfterFunctionLikeAnalysisInterface
{
    public static function afterStatementAnalysis(
        PhpParser\Node\FunctionLike $stmt,
        FunctionLikeStorage $function_like_storage,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ) {

        $assignVariables = self::filterCurrentFunctionStatementVar($stmt);

        $initVars = [];
        foreach ($assignVariables as $assignVariable) {
            $name = $assignVariable['expr']->var->name;
            if (!isset($initVars[$name])) {
                $initVars[$name] = $assignVariable['context_var'];
            }

            AssignAnalyzer::analyzeAssign($assignVariable['expr'], $initVars[$name], $codebase, $assignVariable['statements_source']);
        }

    }

    private static function filterCurrentFunctionStatementVar(PhpParser\Node\FunctionLike $stmt) : \Generator
    {
        foreach ($stmt->getStmts() as $expr) {
            if ($expr instanceof PhpParser\Node\Stmt\Expression &&
                $expr->expr instanceof PhpParser\Node\Expr\Assign &&
                $expr->expr->var instanceof PhpParser\Node\Expr\Variable) {

                if (($stmt->getStartFilePos() < $expr->expr->getStartFilePos()) && ($expr->expr->getStartFilePos() < $stmt->getEndFilePos())) {
                    yield $expr->expr->getStartFilePos() => ['expr' => $expr->expr] + $expr->expr->getAttribute('__sfp_psalm_context');
                }
            }
        }
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
        if ($expr instanceof PhpParser\Node\Expr\Assign && $expr->var instanceof PhpParser\Node\Expr\Variable) {

            if (! isset($context->vars_in_scope['$'.$expr->var->name])) {
                return null;
            }

            $expr->setAttribute('__sfp_psalm_context',  [
                'context_var' => $context->vars_in_scope['$'.$expr->var->name], // assign timing context var.
                'statements_source' => $statements_source
            ]);

            return null;
        }
    }
}
