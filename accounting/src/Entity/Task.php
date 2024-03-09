<?php

namespace Accounting\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * @ORM\Entity(repositoryClass="Accounting\Repository\TaskRepository")
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
     * @ORM\Column(type="string", length=32, options={"default" : ""})
     */
    protected string $jiraId = '';

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

    public function getJiraId(): string
    {
        return $this->jiraId;
    }

    public function setJiraId(string $jiraId): void
    {
        $this->jiraId = $jiraId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getPublicId(): string
    {
        return $this->publicId;
    }

    public function setPublicId(string $publicId): void
    {
        $this->publicId = $publicId;
    }
}