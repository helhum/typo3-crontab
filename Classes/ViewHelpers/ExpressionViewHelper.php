<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\ViewHelpers;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class ExpressionViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;

    public function initializeArguments()
    {
        $this->registerArgument('expr', 'string', 'Expression to evaluate', true);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $expressionLanguage = new ExpressionLanguage();

        return $expressionLanguage->evaluate($arguments['expr'], $renderingContext->getVariableProvider()->getAll());
    }
}
