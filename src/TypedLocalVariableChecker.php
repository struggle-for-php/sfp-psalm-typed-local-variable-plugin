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
    /** @var array<string, \Psalm\Type\Union> */
    private static $initializeContextVars;

    /** @var array<int, {}> */
    private static $closureVars = [];

    public static function afterStatementAnalysis(
        PhpParser\Node\FunctionLike $stmt,
        FunctionLikeStorage $function_like_storage,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ) {
        if ($function_like_storage->cased_name) {
            self::$initializeContextVars = [];
            return;
        }

        $vars = self::filterCurrentFunctionStatementVar($stmt);

        // debug
//        foreach ($vars as $pos => $varSet) {
//            echo $pos . ' ' . $varSet['expr']->var->name, PHP_EOL;
//        }

        $initVars = [];
        foreach ($vars as $varSet) {
            $name = $varSet['expr']->var->name;
            if (!isset($initVars[$name])) {
                $initVars[$name] = $varSet['initVar'];
            }

            AssignAnalyzer::analyzeAssign($varSet['expr'], $initVars[$name], $codebase, $statements_source);
        }

    }

    /**
     * @return PhpParser\Node\Expr\Assign&PhpParser\Node\Expr\Variable[]
     */
    private static function filterCurrentFunctionStatementVar(PhpParser\Node\FunctionLike $stmt)
    {
//        var_dump(__METHOD__ . $stmt->getStartFilePos() . " - " . $stmt->getEndFilePos());

        $currentVars = [];
        foreach (self::$closureVars as $startFilePos => $varSet) {
            if (($stmt->getStartFilePos() < $startFilePos) && ($startFilePos < $stmt->getEndFilePos())) {
                $currentVars[$startFilePos] = $varSet;
            }
        }

        foreach (array_keys($currentVars) as $currentVarStartFilePos) {
            unset(self::$closureVars[$currentVarStartFilePos]);
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

            if ($context->calling_method_id === null || $context->calling_function_id === null) {
                self::$closureVars[$expr->getStartFilePos()] = [
                    // 'name' => $expr->var->name,
                    'expr' => $expr,
                    'initVar' => $context->vars_in_scope['$'.$expr->var->name],
                    'context' => $context,
                    'statements_source' => $statements_source
                ];

                // debug
                // self::$closureVars[$expr->getStartFilePos()] = $expr->var->name;

                return null;
            }


            // hold variable initialize type
            if (! isset(self::$initializeContextVars[$expr->var->name])) {

                //
                if ($context->calling_method_id) {
                    $method_id = new \Psalm\Internal\MethodIdentifier(...explode('::', $context->calling_method_id));
                    foreach ($codebase->methods->getStorage($method_id)->params as $param) {
                        self::$initializeContextVars[$param->name] = $param->type;
                    }
                }


                if (! isset(self::$initializeContextVars[$expr->var->name])) {
                    self::$initializeContextVars[$expr->var->name] = $context->vars_in_scope['$'.$expr->var->name];
                }
            }

            AssignAnalyzer::analyzeAssign($expr, self::$initializeContextVars[$expr->var->name], $codebase, $statements_source);

            return null;
        }
    }

}
