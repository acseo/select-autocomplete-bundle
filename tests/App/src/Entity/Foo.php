<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Tests\App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Foo
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=100)
     */
    private $name;

    /**
     * @var Collection|Foo[]
     *
     * @ORM\ManyToMany(targetEntity=Foo::class, cascade="persist")
     */
    private $children;

    /**
     * @var Foo|null
     *
     * @ORM\ManyToOne(targetEntity=Foo::class, cascade="persist")
     */
    private $child;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->contains($child)) {
            $this->children->removeElement($child);
        }

        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getChild(): ?self
    {
        return $this->child;
    }

    public function setChild(self $child): self
    {
        $this->child = $child;

        return $this;
    }
}
