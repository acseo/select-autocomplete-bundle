<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Tests\App\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="bar")
 */
class Bar
{
    /**
     * @var int
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    protected $id;

    /**
     * @var string|null
     *
     * @ODM\Field
     */
    protected $name;

    /**
     * @var Collection|Bar[]
     *
     * @ODM\ReferenceMany(targetDocument=Bar::class, cascade="persist", storeAs="id")
     */
    protected $children;

    /**
     * @var Bar|null
     *
     * @ODM\ReferenceOne(targetDocument=Bar::class, cascade="persist", storeAs="id")
     */
    protected $child;

    /**
     * @var Bar|null
     *
     * @ODM\EmbedOne(targetDocument=Bar::class)
     */
    protected $embedded;

    /**
     * @var Bar|null
     *
     * @ODM\EmbedMany(targetDocument=Bar::class)
     */
    protected $items;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->items = new ArrayCollection();
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

    public function getEmbedded(): ?self
    {
        return $this->embedded;
    }

    public function setEmbedded(?self $embedded): self
    {
        $this->embedded = $embedded;

        return $this;
    }

    public function addItem(self $bar): self
    {
        if (!$this->items->contains($bar)) {
            $this->items->add($bar);
        }

        return $this;
    }

    public function removeItem(self $bar): self
    {
        if ($this->items->contains($bar)) {
            $this->items->removeElement($bar);
        }

        return $this;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }
}
