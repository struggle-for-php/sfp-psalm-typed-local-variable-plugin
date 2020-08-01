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
use Psalm\Type\Union;

final class TypedLocalVariableChecker implements AfterExpressionAnalysisInterface, AfterFunctionLikeAnalysisInterface
{
    private const CONTEXT_ATTRIBUTE_KEY = '__sfp_psalm_context';

    public static function afterStatementAnalysis(
        PhpParser\Node\FunctionLike $stmt,
        FunctionLikeStorage $function_like_storage,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ): void {

        $stmts = $stmt->getStmts();
        if ($stmts === null) {
            // @codeCoverageIgnoreStart
            return ;
            // @codeCoverageIgnoreEnd
        }

        $assignVariables = self::filterStatementsVar($stmts);

        /** @var array<string, ?Union> $initVars */
        $initVars = [];
        foreach ($function_like_storage->params as $param) {
            $initVars[$param->name] = $param->type;
        }

        foreach ($assignVariables as $name => $assignVariable) {
            if (! isset($initVars[$name])) {
                $initVars[$name] = $assignVariable['context_var'];
            }

            AssignAnalyzer::analyzeAssign($assignVariable['expr'], $initVars[$name], $codebase, $assignVariable['statements_source']);
        }
    }

    private static function filterStatementsVar(array $stmts): iterable
    {

        foreach ($stmts as $expr) {
            if (isset($expr->stmts)) {
                yield from self::filterStatementsVar($expr->stmts);
            }

            if (! ($expr instanceof PhpParser\Node\Stmt\Expression) ||
                ! ($expr->expr instanceof PhpParser\Node\Expr\Assign) ||
                ! ($expr->expr->var instanceof PhpParser\Node\Expr\Variable) ||
                $expr->expr->var->name instanceof PhpParser\Node\Expr
            ) {
                continue;
            }

            yield $expr->expr->var->name => ['expr' => $expr->expr] + $expr->expr->getAttribute(self::CONTEXT_ATTRIBUTE_KEY);
        }
    }

    /**
     * Called after an expression has been checked
     *
     * @param  FileManipulation[] $file_replacements
     *
     * @return false|null
     */
    public static function afterExpressionAnalysis(
        PhpParser\Node\Expr $expr,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ) {
        if ($expr instanceof PhpParser\Node\Expr\Assign && $expr->var instanceof PhpParser\Node\Expr\Variable) {
            if ($expr->var->name instanceof PhpParser\Node\Expr) {
                return null;
            }

            $expr->setAttribute(self::CONTEXT_ATTRIBUTE_KEY, [
                'context_var' => $context->vars_in_scope['$' . $expr->var->name], // assign timing context var.
                'statements_source' => $statements_source,
            ]);

            return null;
        }
    }
}
