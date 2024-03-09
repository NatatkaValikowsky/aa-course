<?php

namespace Task\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * @ORM\Entity(repositoryClass="Task\Repository\TaskRepository")
 * @ORM\Table(name="tasks")
 */
class Task
{
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_COMPLETED = 'completed';

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=36)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator")
     */
    protected string $id = '';

    /**
     * @ORM\Column(type="string", length=64)
     */
    protected string $name = '';

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

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="assignedTasks")
     */
    protected ?User $assignee = null;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="ownedTasks")
     */
    protected ?User $owner = null;

    /**
     * @ORM\Column(type="string", length=32)
     */
    protected string $status = self::STATUS_ASSIGNED;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->publicId = Uuid::v4()->toRfc4122();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getAssignee(): ?User
    {
        return $this->assignee;
    }

    public function setAssignee(?User $assignee): void
    {
        $this->assignee = $assignee;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): void
    {
        $this->owner = $owner;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function mightBeMarkedAsCompleted(): bool
    {
        return $this->status === self::STATUS_ASSIGNED;
    }

    public function complete(): void
    {
        $this->setStatus(self::STATUS_COMPLETED);
        $this->setUpdatedAt(new \DateTime());
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'name' => $this->name,
            'status' => $this->status,
            'assignee' => $this->assignee->getPublicId(),
        ];
    }
}