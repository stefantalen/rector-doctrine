<?php

namespace Rector\Doctrine\Tests\Rector\Property\DoctrineTargetEntityStringToClassConstantRector\Fixture\Attribute;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Rector\Doctrine\Tests\Rector\Property\DoctrineTargetEntityStringToClassConstantRector\Source\AnotherClass;

final class MyEntity
{
    #[ORM\Embedded(class: "AnotherClass")]
    private readonly ?Collection $other;
    #[ORM\OneToMany(targetEntity: "AnotherClass")]
    private readonly ?Collection $items;
    #[ORM\ManyToOne(targetEntity: "AnotherClass")]
    private readonly ?Collection $items2;
    #[ORM\ManyToMany(targetEntity: "AnotherClass")]
    private readonly ?Collection $items3;
    #[ORM\OneToOne(mappedBy: 'class', targetEntity: "AnotherClass")]
    private readonly ?Collection $items4;

    public function addItem(AnotherClass $myOtherEntity): void
    {
        $this->items->add($myOtherEntity);
    }
}

?>
-----
<?php

namespace Rector\Doctrine\Tests\Rector\Property\DoctrineTargetEntityStringToClassConstantRector\Fixture\Attribute;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Rector\Doctrine\Tests\Rector\Property\DoctrineTargetEntityStringToClassConstantRector\Source\AnotherClass;

final class MyEntity
{
    #[ORM\Embedded(class: \Rector\Doctrine\Tests\Rector\Property\DoctrineTargetEntityStringToClassConstantRector\Source\AnotherClass::class)]
    private readonly ?Collection $other;
    #[ORM\OneToMany(targetEntity: \Rector\Doctrine\Tests\Rector\Property\DoctrineTargetEntityStringToClassConstantRector\Source\AnotherClass::class)]
    private readonly ?Collection $items;
    #[ORM\ManyToOne(targetEntity: \Rector\Doctrine\Tests\Rector\Property\DoctrineTargetEntityStringToClassConstantRector\Source\AnotherClass::class)]
    private readonly ?Collection $items2;
    #[ORM\ManyToMany(targetEntity: \Rector\Doctrine\Tests\Rector\Property\DoctrineTargetEntityStringToClassConstantRector\Source\AnotherClass::class)]
    private readonly ?Collection $items3;
    #[ORM\OneToOne(mappedBy: 'class', targetEntity: \Rector\Doctrine\Tests\Rector\Property\DoctrineTargetEntityStringToClassConstantRector\Source\AnotherClass::class)]
    private readonly ?Collection $items4;

    public function addItem(AnotherClass $myOtherEntity): void
    {
        $this->items->add($myOtherEntity);
    }
}

?>
