<?php

namespace Accounting\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * @ORM\Entity(repositoryClass="Accounting\Repository\BalanceRepository")
 * @ORM\Table(name="balance")
 */
class Balance
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=36)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator")
     */
    protected string $id = '';

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="assignedTasks")
     */
    protected ?User $user = null;

    /**
     * @ORM\Column(type="string", length=32)
     */
    protected string $amount = '0';

    /**
     * @ORM\Column(type="datetime")
     */
    protected \DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    protected \DateTime $updatedAt;

    /**
     * @ORM\Column(type="string", length=36)
     */
    protected string $publicId = '';

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->publicId = Uuid::v4()->toRfc4122();
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }

    public function applyTransaction(Transaction $transaction): void
    {
        $currentAmount = $this->getAmount();
        $amount = bcadd($currentAmount, $transaction->getAmount());

        $this->setAmount($amount);
        $this->updatedAt = new \DateTime();
    }

    public function reset(): void
    {
        $this->setAmount('0');
        $this->updatedAt = new \DateTime();
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'amount' => $this->amount,
            'user' => $this->user->getPublicId(),
        ];
    }
}