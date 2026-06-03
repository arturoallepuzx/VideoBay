<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Console;

use App\Order\Application\CancelExpiredOrder\CancelExpiredOrder;
use Illuminate\Console\Command;

class CancelExpiredOrdersCommand extends Command
{
    protected $signature = 'order:cancel-expired';

    protected $description = 'Release stock for orders that expired in pending_payment state.';

    public function __construct(private CancelExpiredOrder $cancelExpiredOrder)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $count = ($this->cancelExpiredOrder)();

        $this->info(sprintf('Cancelled %d expired order(s).', $count));

        return self::SUCCESS;
    }
}
