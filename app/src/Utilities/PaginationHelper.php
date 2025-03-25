<?php

namespace alo\Utilities;

class PaginationHelper
{
    public static function generatePaginationLinks(
        int $currentPage,
        int $totalPages,
        string $baseUrl,
        array $queryParams = []
    ): array {
        if ($totalPages <= 10) {
            return self::generateFullPaginationLinks(
                $currentPage,
                $totalPages,
                $baseUrl,
                $queryParams
            );
        }

        return self::generateTruncatedPaginationLinks(
            $currentPage,
            $totalPages,
            $baseUrl,
            $queryParams
        );
    }

    private static function generateFullPaginationLinks(
        int $currentPage,
        int $totalPages,
        string $baseUrl,
        array $queryParams = []
    ): array {
        $links = [];

        $links[] = [
            'label' => _e('first'),
            'url' => self::buildPageUrl($baseUrl, 1, $queryParams),
            'disabled' => $currentPage === 1
        ];
        $links[] = [
            'label' => _e('prev'),
            'url' => self::buildPageUrl($baseUrl, max(1, $currentPage - 1), $queryParams),
            'disabled' => $currentPage === 1
        ];

        for ($i = 1; $i <= $totalPages; $i++) {
            $links[] = [
                'label' => (string)$i,
                'url' => self::buildPageUrl($baseUrl, $i, $queryParams),
                'active' => $i === $currentPage
            ];
        }

        $links[] = [
            'label' => _e('next'),
            'url' => self::buildPageUrl($baseUrl, min($totalPages, $currentPage + 1), $queryParams),
            'disabled' => $currentPage === $totalPages
        ];
        $links[] = [
            'label' => _e('last'),
            'url' => self::buildPageUrl($baseUrl, $totalPages, $queryParams),
            'disabled' => $currentPage === $totalPages
        ];

        return $links;
    }

    private static function generateTruncatedPaginationLinks(
        int $currentPage,
        int $totalPages,
        string $baseUrl,
        array $queryParams = []
    ): array {
        $links = [];

        $links[] = [
            'label' => _e('first'),
            'url' => self::buildPageUrl($baseUrl, 1, $queryParams),
            'disabled' => $currentPage === 1
        ];
        $links[] = [
            'label' => _e('prev'),
            'url' => self::buildPageUrl($baseUrl, max(1, $currentPage - 1), $queryParams),
            'disabled' => $currentPage === 1
        ];

        $linksToShow = self::calculatePagesToShow($currentPage, $totalPages);

        foreach ($linksToShow as $page) {
            if ($page === '...') {
                $links[] = [
                    'label' => '...',
                    'url' => '#',
                    'disabled' => true
                ];
            } else {
                $links[] = [
                    'label' => (string)$page,
                    'url' => self::buildPageUrl($baseUrl, $page, $queryParams),
                    'active' => $page === $currentPage
                ];
            }
        }

        $links[] = [
            'label' => _e('next'),
            'url' => self::buildPageUrl($baseUrl, min($totalPages, $currentPage + 1), $queryParams),
            'disabled' => $currentPage === $totalPages
        ];
        $links[] = [
            'label' => _e('last'),
            'url' => self::buildPageUrl($baseUrl, $totalPages, $queryParams),
            'disabled' => $currentPage === $totalPages
        ];

        return $links;
    }

    private static function calculatePagesToShow(int $currentPage, int $totalPages): array
    {
        if ($totalPages <= 10) {
            return range(1, $totalPages);
        }

        $pages = [];

        $pages[] = 1;
        $pages[] = 2;

        $start = max(3, $currentPage - 2);
        $end = min($totalPages - 1, $currentPage + 2);

        if ($start > 3) {
            $pages[] = '...';
        }

        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }

        if ($end < $totalPages - 1) {
            $pages[] = '...';
        }

        $pages[] = $totalPages - 1;
        $pages[] = $totalPages;

        return $pages;
    }

    private static function buildPageUrl(
        string $baseUrl,
        int $page,
        array $queryParams = []
    ): string {
        unset($queryParams['page']);

        $url = $baseUrl . ($page > 1 ? '/page/' . $page : '');

        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
    }
}
