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
    /** @var array<int, {}> */
    private static $assignVarSet = [];

    public static function afterStatementAnalysis(
        PhpParser\Node\FunctionLike $stmt,
        FunctionLikeStorage $function_like_storage,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ) {

        $vars = self::filterCurrentFunctionStatementVar($stmt);

        // debug
//        foreach ($vars as $pos => $varSet) {
//            echo $pos . ' ' . $varSet['expr']->var->name, PHP_EOL;
//        }

        $initVars = [];
        foreach ($vars as $varSet) {
            $name = $varSet['expr']->var->name;
            if (!isset($initVars[$name])) {
                $initVars[$name] = $varSet['contextVar'];
            }

            AssignAnalyzer::analyzeAssign($varSet['expr'], $initVars[$name], $codebase, $statements_source);
        }

    }

    /**
     * @return PhpParser\Node\Expr\Assign&PhpParser\Node\Expr\Variable[]
     */
    private static function filterCurrentFunctionStatementVar(PhpParser\Node\FunctionLike $stmt)
    {
        $currentVars = [];
        foreach (self::$assignVarSet as $startFilePos => $varSet) {
            if (($stmt->getStartFilePos() < $startFilePos) && ($startFilePos < $stmt->getEndFilePos())) {
                $currentVars[$startFilePos] = $varSet;
            }
        }

        foreach (array_keys($currentVars) as $currentVarStartFilePos) {
            unset(self::$assignVarSet[$currentVarStartFilePos]);
        }

        return $currentVars;
    }


    /**
     *
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

            self::$assignVarSet[$expr->getStartFilePos()] = [
                // 'name' => $expr->var->name,
                'expr' => $expr,
                'contextVar' => $context->vars_in_scope['$'.$expr->var->name], // assign timing context var.
                'context' => $context,
                'statements_source' => $statements_source
            ];

            return null;
        }
    }

}
