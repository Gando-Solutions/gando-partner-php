<?php

declare(strict_types=1);

namespace Gando\Partner\Api;

use Gando\Partner\Deposits as GeneratedDeposits;
use Gando\Partner\Models\Operations\DepositsListRequest;
use Gando\Partner\Utils\Options;

/**
 * Auto-paginates {@see GeneratedDeposits::list()} using <code>page</code> + <code>limit</code> until a short page or empty result.
 *
 * @implements \IteratorAggregate<int, \Gando\Partner\Models\Operations\Item>
 */
final class DepositsListIterable implements \IteratorAggregate
{
    public function __construct(
        private readonly GeneratedDeposits $deposits,
        private readonly DepositsListRequest $template,
        private readonly ?Options $options,
    ) {
    }

    public function getIterator(): \Generator
    {
        $limit = (int) ($this->template->limit ?? 20);
        $limit = min(100, max(1, $limit));
        $page = max(1, (int) ($this->template->page ?? 1));

        while (true) {
            $req = self::copyWithPage($this->template, $page, $limit);
            $res = $this->deposits->list($req, $this->options);
            $items = $res->object?->data->items ?? [];
            if ($items === []) {
                return;
            }
            foreach ($items as $item) {
                yield $item;
            }
            if (count($items) < $limit) {
                return;
            }
            $page++;
        }
    }

    private static function copyWithPage(DepositsListRequest $t, int $page, int $limit): DepositsListRequest
    {
        return new DepositsListRequest(
            accountId: $t->accountId,
            sortBy: $t->sortBy,
            sortOrder: $t->sortOrder,
            status: $t->status,
            clientId: $t->clientId,
            startAtFrom: $t->startAtFrom,
            startAtTo: $t->startAtTo,
            expiresAtFrom: $t->expiresAtFrom,
            expiresAtTo: $t->expiresAtTo,
            includeCounts: $t->includeCounts,
            amountMin: $t->amountMin,
            amountMax: $t->amountMax,
            page: $page,
            limit: $limit,
        );
    }
}
