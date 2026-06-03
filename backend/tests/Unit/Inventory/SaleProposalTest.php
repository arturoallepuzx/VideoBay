<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use App\Inventory\Domain\Entity\SaleProposal;
use App\Inventory\Domain\Exception\SaleProposalAlreadyDecidedException;
use App\Inventory\Domain\ValueObject\CopyCondition;
use App\Inventory\Domain\ValueObject\CopyFormat;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class SaleProposalTest extends TestCase
{
    private function makeProposal(): SaleProposal
    {
        return SaleProposal::dddCreate(
            Uuid::generate(),
            null,
            'Inception',
            null,
            CopyFormat::create('BLURAY'),
            CopyCondition::create('good'),
            null,
            null,
        );
    }

    public function test_ddd_create_starts_proposed(): void
    {
        $proposal = $this->makeProposal();

        $this->assertSame('proposed', $proposal->status()->value());
    }

    public function test_accept_transitions_to_accepted(): void
    {
        $proposal = $this->makeProposal();

        $proposal->accept();

        $this->assertSame('accepted', $proposal->status()->value());
        $this->assertTrue($proposal->wasModified());
    }

    public function test_reject_transitions_to_rejected(): void
    {
        $proposal = $this->makeProposal();

        $proposal->reject();

        $this->assertSame('rejected', $proposal->status()->value());
    }

    public function test_accept_on_already_accepted_throws(): void
    {
        $proposal = $this->makeProposal();
        $proposal->accept();

        $this->expectException(SaleProposalAlreadyDecidedException::class);

        $proposal->accept();
    }

    public function test_reject_on_already_accepted_throws(): void
    {
        $proposal = $this->makeProposal();
        $proposal->accept();

        $this->expectException(SaleProposalAlreadyDecidedException::class);

        $proposal->reject();
    }
}
