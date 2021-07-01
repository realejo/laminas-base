<?php

declare(strict_types=1);

namespace Realejo\Service;

class PaginatorOptions
{

    protected int $pageRange = 10;
    protected int $currentPageNumber = 1;
    protected int $itemCountPerPage = 10;

    public function setPageRange(int $pageRange): self
    {
        $this->pageRange = $pageRange;

        return $this;
    }

    public function setCurrentPageNumber(int $currentPageNumber): self
    {
        $this->currentPageNumber = $currentPageNumber;

        return $this;
    }

    public function setItemCountPerPage(int $itemCountPerPage): self
    {
        $this->itemCountPerPage = $itemCountPerPage;

        return $this;
    }

    public function getPageRange(): int
    {
        return $this->pageRange;
    }

    public function getCurrentPageNumber(): int
    {
        return $this->currentPageNumber;
    }

    public function getItemCountPerPage(): int
    {
        return $this->itemCountPerPage;
    }
}
