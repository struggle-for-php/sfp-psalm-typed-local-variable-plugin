<?php

declare(strict_types=1);

namespace Sfp\Psalm\TypedLocalVariablePlugin;

use PhpParser;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\AfterFunctionLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\AfterFunctionLikeAnalysisEvent;
use Psalm\Type\Union;
use function is_array;

final class TypedLocalVariableChecker implements AfterExpressionAnalysisInterface, AfterFunctionLikeAnalysisInterface
{
    private const CONTEXT_ATTRIBUTE_KEY = '__sfp_psalm_context';

    public static function afterStatementAnalysis(
        AfterFunctionLikeAnalysisEvent $event
    ): ?bool {
        $stmts = $event->getStmt()->getStmts();
        if ($stmts === null) {
            // @codeCoverageIgnoreStart
            return null;
            // @codeCoverageIgnoreEnd
        }

        $assignVariables = self::filterStatementsVar($stmts);

        /** @var array<string, ?Union> $initVars */
        $initVars = [];
        foreach ($event->getClasslikeStorage()->params as $param) {
            $initVars[$param->name] = $param->type;
        }

        foreach ($assignVariables as $name => $assignVariable) {
            if (! isset($initVars[$name])) {
                $initVars[$name] = $assignVariable['context_var'];
            }

            AssignAnalyzer::analyzeAssign($assignVariable['expr'], $initVars[$name], $event->getCodebase(), $assignVariable['statements_source']);
        }

        return null;
    }

    /**
     * @return \Generator<string, array{
     *     expr: PhpParser\Node\Expr\Assign,
     *     context_var: Union,
     *     statements_source: \Psalm\StatementsSource
     * }, null, void>
     */
    private static function filterStatementsVar(array $stmts): \Generator
    {
        foreach ($stmts as $expr) {
            if (isset($expr->stmts) && is_array($expr->stmts)) {
                yield from self::filterStatementsVar($expr->stmts);
            }

            if (! ($expr instanceof PhpParser\Node\Stmt\Expression) ||
                ! ($expr->expr instanceof PhpParser\Node\Expr\Assign) ||
                ! ($expr->expr->var instanceof PhpParser\Node\Expr\Variable) ||
                $expr->expr->var->name instanceof PhpParser\Node\Expr
            ) {
                continue;
            }

            /** @var array{
             *      context_var: Union,
             *      statements_source: \Psalm\StatementsSource
             * }|null $assign_variable_context
             */
            $assign_variable_context = $expr->expr->getAttribute(self::CONTEXT_ATTRIBUTE_KEY);
            if ($assign_variable_context === null) {
                continue;
            }

            yield $expr->expr->var->name => ['expr' => $expr->expr] + $assign_variable_context;
        }
    }

    /**
     * Called after an expression has been checked
     *
     * @return false|null
     */
    public static function afterExpressionAnalysis(
        AfterExpressionAnalysisEvent $event
    ) :?bool {
        $expr = $event->getExpr();
        if ($expr instanceof PhpParser\Node\Expr\Assign && $expr->var instanceof PhpParser\Node\Expr\Variable) {
            if ($expr->var->name instanceof PhpParser\Node\Expr) {
                return null;
            }

            $expr->setAttribute(self::CONTEXT_ATTRIBUTE_KEY, [
                'context_var' => $event->getContext()->vars_in_scope['$' . $expr->var->name], // assign timing context var.
                'statements_source' => $event->getStatementsSource(),
            ]);

            return null;
        }

        return null;
    }
}
