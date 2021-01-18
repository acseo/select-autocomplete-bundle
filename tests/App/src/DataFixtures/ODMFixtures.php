<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Tests\App\DataFixtures;

use Acseo\SelectAutocomplete\Tests\App\Document\Bar;
use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Persistence\ObjectManager;

class ODMFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        for ($i = 0; $i < 20; ++$i) {
            $manager->persist(
                (new Bar())
                    ->setName('Bar '.$i)
                    ->setChild((new Bar())->setName('Child '.$i))
                    ->addChild((new Bar())->setName('Children '.$i))
                    ->addItem((new Bar())->setName('Item '.$i))
                    ->setEmbedded((new Bar())->setName('Embedded '.$i))
            );
        }

        $manager->flush();
    }
}
