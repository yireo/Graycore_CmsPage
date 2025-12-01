<?php declare(strict_types=1);

namespace Graycore\CmsPage\ViewModel;

use Magento\Framework\App\State;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\LayoutInterface;

class BlockRenderer implements ArgumentInterface
{
    private array $styles = [];
    private array $mediaQueries = [];

    public function __construct(
        private LayoutInterface $layout,
        private State $state
    ) {
    }

    public function renderChild(array $childData): string
    {
        if (false === array_key_exists('type', $childData)) {
            return '';
        }

        $blockName = 'cms.page_ai.' . $childData['type'];
        $block = $this->layout->getBlock($blockName);
        if (false === $block instanceof AbstractBlock) {
            return $this->notfound($blockName);
        }

        unset($childData['type']);
        $block->setData('block_renderer', $this);
        $block->addData($childData);

        $this->registerStyles($block, $childData);

        return (string)$block->toHtml();
    }

    private function registerStyles(AbstractBlock $block, array $childData
    ): void {
        if (empty($childData['styles'])) {
            return;
        }

        $hash = md5(json_encode($childData));
        $block->setData('element_id', $hash);

        if (!empty($childData['styles']['base'])) {
            $this->styles[$hash] = $childData['styles']['base'];
        }

        if (!empty($childData['styles']['breakpoints'])) {
            foreach (
                $childData['styles']['breakpoints'] as $breakpointName =>
                $breakpointStyle
            ) {
                $breakpointName = str_replace(' ', '', $breakpointName);
                $this->mediaQueries[$breakpointName][$hash] = $breakpointStyle;
            }
        }
    }

    public function getStyles(): array
    {
        return $this->styles;
    }

    public function getMediaQueries(): array
    {
        return $this->mediaQueries;
    }

    public function getCss(): string
    {
        $sep = $this->isDeveloperMode() ? "\n" : '';
        $css = '';
        foreach ($this->getStyles() as $elementId => $styleDeclarations) {
            $elementCss = '';
            $baseStyles = [];
            foreach ($styleDeclarations as $styleName => $styleValue) {
                $baseStyles[] = $styleName . ':' . $styleValue;
            }

            $elementCss .= implode(';', $baseStyles) . ';' . $sep;
            $css .= '#' . $elementId . ' {' . $sep . $elementCss . '}' . $sep;
        }

        if ($this->getMediaQueries()) {
            foreach ($this->getMediaQueries() as $mediaQuery => $elementStyles) {
                $mediaQueryCss = '';
                foreach ($elementStyles as $elementId => $styleDeclarations) {
                    $elementCss = [];
                    foreach ($styleDeclarations as $styleName => $styleValue) {
                        $elementCss[] = $styleName . ':' . $styleValue;
                    }
                    $mediaQueryCss .= '#'.$elementId .'{' . implode(';', $elementCss) . '}' . $sep;
                }

                $css .= '@media '.$mediaQuery.'{'.$sep.$mediaQueryCss.'}'.$sep;
            }
        }

        return $css;
    }

    private function notfound(string $blockName): string
    {
        if (false === $this->isDeveloperMode()) {
            return '';
        }

        return '<div>Block with name "' . $blockName . '" was not found</div>';
    }

    private function isDeveloperMode(): bool
    {
        return $this->state->getMode() === State::MODE_DEVELOPER;
    }
}
