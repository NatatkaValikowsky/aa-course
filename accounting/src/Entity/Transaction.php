<?php

namespace Accounting\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * @ORM\Entity(repositoryClass="Accounting\Repository\TransactionRepository")
 * @ORM\Table(name="transactions")
 */
class Transaction
{
    public const STATUS_COMPLETED = 'completed';

    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_PAYOUT = 'payout';

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
    protected string $status = self::STATUS_COMPLETED;

    /**
     * @ORM\Column(type="string", length=32)
     */
    protected string $type = self::TYPE_DEPOSIT;

    /**
     * @ORM\Column(type="string", length=32)
     */
    protected string $amount = '0';

    /**
     * @ORM\Column(type="datetime")
     */
    protected \DateTime $createdAt;

    /**
     * @ORM\Column(type="string", length=36)
     */
    protected string $publicId = '';

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    protected array $meta = [];

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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

    public function getMeta(): array
    {
        return $this->meta ?? [];
    }

    public function setMeta(array $meta): void
    {
        $this->meta = $meta;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getPublicId(): string
    {
        return $this->publicId;
    }


    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'amount' => $this->amount,
            'user' => $this->user->getPublicId(),
            'create_at' => $this->createdAt->format(DATE_ATOM),
        ];
    }
}