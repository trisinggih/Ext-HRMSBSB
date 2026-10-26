<?php

namespace App\Services\Payroll;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ExpressionEvaluator
{
    protected ExpressionLanguage $evaluator;

    public function __construct()
    {
        $this->evaluator = new ExpressionLanguage();
    }

    /**
     * Evaluate a formula safely with given variables.
     * 
     * @param string|null $formula
     * @param array $variables
     * @return mixed
     */
    public function evaluate(?string $formula, array $variables = [])
    {
        if (empty($formula)) {
            return 0;
        }

        try {
            return $this->evaluator->evaluate($formula, $variables);
        } catch (\Exception $e) {
            \Log::error('Expression Evaluation Error: ' . $e->getMessage(), [
                'formula' => $formula,
                'variables' => $variables
            ]);
            return 0;
        }
    }

    /**
     * Evaluate a condition (returns boolean).
     * 
     * @param string|null $condition
     * @param array $variables
     * @return bool
     */
    public function evaluateCondition(?string $condition, array $variables = []): bool
    {
        if (empty($condition)) {
            return true; // Empty condition means always true
        }

        try {
            return (bool) $this->evaluator->evaluate($condition, $variables);
        } catch (\Exception $e) {
            \Log::error('Condition Evaluation Error: ' . $e->getMessage(), [
                'condition' => $condition,
                'variables' => $variables
            ]);
            return false;
        }
    }
}
