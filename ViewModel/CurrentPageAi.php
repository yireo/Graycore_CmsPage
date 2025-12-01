<?php declare(strict_types=1);

namespace Graycore\CmsPage\ViewModel;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\Page;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class CurrentPageAi implements ArgumentInterface
{
    public function __construct(
        private RequestInterface $request,
        private PageRepositoryInterface $pageRepository,
    ) {
    }

    public function getAiSchema(): array
    {
        $page = $this->getPageFromRequest();
        if (empty($page)) {
            return [];
        }

        $aiSchema = $page->getAiSchemaJson();
        if (empty($aiSchema)) {
            return [];
        }

        return json_decode($aiSchema, true);
    }

    private function getPageFromRequest(): ?Page
    {
        $pageId = $this->getPageIdFromRequest();
        if (empty($pageId)) {
            return null;
        }

        return $this->pageRepository->getById($pageId);
    }

    private function getPageIdFromRequest(): int
    {
        return (int)$this->request->getParam("page_id");
    }
}
