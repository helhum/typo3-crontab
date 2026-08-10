<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\ViewHelpers;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class ExpressionViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments()
    {
        $this->registerArgument('expr', 'string', 'Expression to evaluate', true);
    }

    public function render()
    {
        $expressionLanguage = new ExpressionLanguage();

        return $expressionLanguage->evaluate($this->arguments['expr'], $this->renderingContext->getVariableProvider()->getAll());
    }
}
