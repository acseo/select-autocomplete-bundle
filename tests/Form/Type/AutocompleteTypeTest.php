<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Tests\Form\Type;

use Acseo\SelectAutocomplete\DataProvider\DataProviderRegistry;
use Acseo\SelectAutocomplete\DataProvider\Doctrine\AbstractDoctrineDataProvider;
use Acseo\SelectAutocomplete\DataProvider\Doctrine\ODMDataProvider;
use Acseo\SelectAutocomplete\Form\Type\AutocompleteType;
use Acseo\SelectAutocomplete\Tests\App\Document\Bar;
use Acseo\SelectAutocomplete\Tests\App\Entity\Foo;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

final class AutocompleteTypeTest extends KernelTestCase
{
    /**
     * @var \Symfony\Component\Form\FormFactory
     */
    private $formFactory;

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    private $requestStack;

    /**
     * @var AutocompleteType
     */
    private $autocompleteType;

    /**
     * @var DataProviderRegistry
     */
    private $dataProviders;

    protected function setUp()
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        $this->formFactory = $container->get('form.factory');
        $this->requestStack = $container->get('request_stack');
        $this->dataProviders = $container->get(DataProviderRegistry::class);
        $this->autocompleteType = new AutocompleteType(
            $this->requestStack,
            $container->get('serializer'),
            $this->dataProviders
        );
    }

    public function testBuildForm()
    {
        $request = Request::create('/', 'GET', [
            'acseo_autocomplete_form_name' => 'form[test][]',
            'q' => 'x',
        ]);

        $this->requestStack->push($request);

        $form = $this->formFactory
            ->createBuilder()
            ->add('test', AutocompleteType::class, [
                'class' => Foo::class,
                'multiple' => true,
            ])
            ->getForm()
        ;

        self::assertInstanceOf(FormInterface::class, $form->get('test'));
    }

    public function testBuildView()
    {
        $this->requestStack->push(Request::create('/'));

        $form = $this->formFactory
            ->createBuilder()
            ->add('test', AutocompleteType::class, [
                'class' => Foo::class,
                'display' => function (Foo $item) {
                    return $item->getName();
                },
                'property' => 'name',
                'strategy' => 'starts_with',
            ])
            ->getForm()
        ;

        $vars = $form->createView()->children['test']->vars;

        self::assertIsArray($vars['selected']);
        self::assertFalse($vars['attr']['multiple'] ?? null);
        self::assertTrue($vars['attr']['required'] ?? null);
        self::assertEquals('form[test]', $vars['attr']['name'] ?? null);
        self::assertEquals('acseo-select-autocomplete', $vars['attr']['class'] ?? null);
        self::assertEquals('/?acseo_autocomplete_form_name=form[test]', $vars['attr']['data-autocomplete-url'] ?? null);
    }

    public function testBuildChoices()
    {
        $r = new \ReflectionMethod(AutocompleteType::class, 'buildChoices');
        $r->setAccessible(true);

        $data = $this->dataProviders->getProvider(Foo::class)->findByProperty(Foo::class, 'id', 1);

        $choices = $r->invoke($this->autocompleteType, $data, [
            'identifier' => 'id',
            'display' => function ($object) use ($data) {
                self::assertEquals($data[0], $object);

                return $object->getName();
            },
        ]);

        self::assertEquals($data[0]->getName(), $choices[$data[0]->getId()] ?? null);

        $choices = $r->invoke($this->autocompleteType, $data, [
            'identifier' => 'name',
            'display' => 'id',
        ]);

        self::assertEquals($data[0]->getId(), $choices[$data[0]->getName()] ?? null);
        self::assertEquals($data[0]->getName(), array_key_first($choices));
    }

    public function testBuildAutocompleteEntrypoint()
    {
        $r = new \ReflectionMethod(AutocompleteType::class, 'buildAutocompleteEntrypoint');
        $r->setAccessible(true);

        $url = $r->invoke($this->autocompleteType, 'form[test]');
        self::assertNull($url);

        $this->requestStack->push(Request::create('/'));

        $url = $r->invoke($this->autocompleteType, 'form[test]');
        self::assertEquals('/?acseo_autocomplete_form_name=form[test]', $url);
    }

    public function testRenderAutocompleteResponse()
    {
        $r = new \ReflectionMethod(AutocompleteType::class, 'renderAutocompleteResponse');
        $r->setAccessible(true);

        $request = Request::create('/', 'GET', ['q' => 'Foo 1']);
        $this->requestStack->push($request);

        $response = $r->invoke($this->autocompleteType, $request, [
            'class' => Foo::class,
            'display' => 'name',
            'identifier' => 'id',
            'provider' => function (string $terms, AbstractDoctrineDataProvider $provider) {
                return $provider->getRepository(Foo::class)
                    ->findBy(['name' => $terms])
                ;
            },
        ]);

        self::assertEquals(json_encode([2 => 'Foo 1']), $response->getContent());

        $request->query->set('q', 'Bar 1');
        $response = $r->invoke($this->autocompleteType, $request, [
            'class' => Bar::class,
            'display' => 'name',
            'identifier' => 'id',
            'property' => 'name',
            'strategy' => 'equals',
            'provider' => ODMDataProvider::class,
        ]);

        self::assertEquals(json_encode([2 => 'Bar 1']), $response->getContent());
    }
}
