<?php
// includes/pagination.php
// Reusable pagination renderer used across all pages.
// Usage:
//   include '../includes/pagination.php';  (or 'includes/pagination.php' from root)
//   echo renderPagination($page, $totalPages, $buildUrlFn);
//
// $buildUrlFn is a callable that accepts ['page' => 'N'] and returns a URL string.

function renderPagination(int $page, int $totalPages, callable $buildUrlFn): string {
    if ($totalPages <= 1) return '';

    $html  = '<div class="pagination">';

    // << First
    if ($page > 1) {
        $html .= '<a href="' . $buildUrlFn(['page' => '1']) . '" class="pg-arrow">&lt;&lt;</a>';
    } else {
        $html .= '<span class="pg-arrow">&lt;&lt;</span>';
    }

    // < Prev
    if ($page > 1) {
        $html .= '<a href="' . $buildUrlFn(['page' => (string)($page - 1)]) . '" class="pg-arrow">&lt;</a>';
    } else {
        $html .= '<span class="pg-arrow">&lt;</span>';
    }

    // Page numbers — show up to 5 centred around current page
    $start = max(1, $page - 2);
    $end   = min($totalPages, $page + 2);

    if ($start > 1) {
        $html .= '<a href="' . $buildUrlFn(['page' => '1']) . '" class="pg-num">1</a>';
        if ($start > 2) $html .= '<span class="dots">…</span>';
    }

    for ($i = $start; $i <= $end; $i++) {
        if ($i === $page) {
            $html .= '<span class="current">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $buildUrlFn(['page' => (string)$i]) . '" class="pg-num">' . $i . '</a>';
        }
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<span class="dots">…</span>';
        $html .= '<a href="' . $buildUrlFn(['page' => (string)$totalPages]) . '" class="pg-num">' . $totalPages . '</a>';
    }

    // > Next
    if ($page < $totalPages) {
        $html .= '<a href="' . $buildUrlFn(['page' => (string)($page + 1)]) . '" class="pg-arrow">&gt;</a>';
    } else {
        $html .= '<span class="pg-arrow">&gt;</span>';
    }

    // >> Last
    if ($page < $totalPages) {
        $html .= '<a href="' . $buildUrlFn(['page' => (string)$totalPages]) . '" class="pg-arrow">&gt;&gt;</a>';
    } else {
        $html .= '<span class="pg-arrow">&gt;&gt;</span>';
    }

    $html .= '</div>';
    return $html;
}
