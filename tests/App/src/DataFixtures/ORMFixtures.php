<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Tests\App\DataFixtures;

use Acseo\SelectAutocomplete\Tests\App\Entity\Foo;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ORMFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        for ($i = 0; $i < 20; ++$i) {
            $manager->persist(
                (new Foo())->setName('Foo '.$i)
            );
        }

        $manager->flush();
    }
}
