<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Tests\Form\Type;

use Acseo\SelectAutocomplete\DataProvider\DataProviderRegistry;
use Acseo\SelectAutocomplete\DataProvider\Doctrine\AbstractDoctrineDataProvider;
use Acseo\SelectAutocomplete\DataProvider\Doctrine\ODMDataProvider;
use Acseo\SelectAutocomplete\DataProvider\Doctrine\ORMDataProvider;
use Acseo\SelectAutocomplete\DataProvider\ProxyDataProvider;
use Acseo\SelectAutocomplete\Form\Transformer\ModelTransformer;
use Acseo\SelectAutocomplete\Form\Transformer\SimpleTransformer;
use Acseo\SelectAutocomplete\Form\Type\AutocompleteType;
use Acseo\SelectAutocomplete\Tests\App\Document\Bar;
use Acseo\SelectAutocomplete\Tests\App\Entity\Foo;
use Acseo\SelectAutocomplete\Tests\App\Form\DataProvider\CustomProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
            'acseo_autocomplete_uid' => 'test',
            'q' => 'x',
        ]);

        $this->requestStack->push($request);

        $builder = $this->formFactory
            ->createBuilder()
            ->add('prop', AutocompleteType::class, [
                'class' => Foo::class,
                'multiple' => true,
                'transformer' => new SimpleTransformer(true),
            ])
            ->add('test', AutocompleteType::class, [
                'uniq_id' => 'test',
                'class' => Foo::class,
                'multiple' => true,
                'properties' => 'id',
                'display' => ['name'],
                'provider' => CustomProvider::class,
            ])
        ;

        self::assertInstanceOf(FormInterface::class, $builder->getForm()->get('test'));

        $r = new \ReflectionProperty(ModelTransformer::class, 'provider');
        $r->setAccessible(true);
        foreach ($builder->get('test')->getModelTransformers() as $transformer) {
            if ($transformer instanceof ModelTransformer) {
                self::assertInstanceOf(CustomProvider::class, $r->getValue($transformer));
            }
        }
    }

    public function testBuildView()
    {
        $this->requestStack->push(Request::create('/'));

        $form = $this->formFactory
            ->createBuilder()
            ->add('test', AutocompleteType::class, [
                'uniq_id' => 'test',
                'class' => Foo::class,
                'display' => function (Foo $item) {
                    return $item->getName();
                },
                'multiple' => true,
                'provider' => $this->dataProviders->getProviderByClassName(ORMDataProvider::class),
                'properties' => 'name',
                'strategy' => 'starts_with',
                'format' => function (array $normalized, Response $response): Response {
                    return $response->setContent(json_encode($normalized));
                },
                'model_transformer' => new ModelTransformer($this->dataProviders->getProviderByClassName(ORMDataProvider::class), Foo::class, 'id', true),
            ])
            ->getForm()
        ;

        $vars = $form->createView()->children['test']->vars;

        self::assertIsArray($vars['selected']);
        self::assertTrue($vars['attr']['multiple'] ?? null);
        self::assertTrue($vars['attr']['required'] ?? null);
        self::assertEquals('form[test][]', $vars['attr']['name'] ?? null);
        self::assertEquals('acseo-select-autocomplete', $vars['attr']['class'] ?? null);
        self::assertEquals('/?acseo_autocomplete_uid=test', $vars['attr']['data-autocomplete-url'] ?? null);

        $form = $this->formFactory
            ->createBuilder()
            ->add('test', AutocompleteType::class, [
                'class' => Foo::class,
                'identifier' => 'name',
                'display' => 'name',
                'transformer' => false,
                'multiple' => true,
                'data' => ['Foo 1'],
            ])
            ->getForm()
        ;

        $vars = $form->createView()->children['test']->vars;

        self::assertIsArray($vars['selected']);
        self::assertTrue(isset($vars['selected']['Foo 1']));
    }

    public function testBuildChoices()
    {
        $r = new \ReflectionMethod(AutocompleteType::class, 'buildChoices');
        $r->setAccessible(true);

        $data = $this->dataProviders->getProvider(Foo::class)->findByIds(Foo::class, 'id', [1]);

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
            'display' => ['id'],
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

        $url = $r->invoke($this->autocompleteType, '6f960ad7d4d50c3fd708888902c2d20c');
        self::assertEquals('/?acseo_autocomplete_uid=6f960ad7d4d50c3fd708888902c2d20c', $url);
    }

    public function testRenderAutocompleteResponse()
    {
        $r = new \ReflectionMethod(AutocompleteType::class, 'renderAutocompleteResponse');
        $r->setAccessible(true);

        $request = Request::create('/', 'GET', ['q' => 'Foo 1']);
        $this->requestStack->push($request);

        $response = $r->invoke($this->autocompleteType, $request, [
            'class' => Foo::class,
            'properties' => ['name'],
            'identifier' => 'id',
            'display' => ['name'],
            'strategy' => 'equals',
            'format' => function (array $normalized, Response $response): Response {
                return $response->setContent(json_encode($normalized));
            },
            'provider' => new ProxyDataProvider([
                'find_by_terms' => function (string $terms, AbstractDoctrineDataProvider $provider) {
                    return $provider->getRepository(Foo::class)
                        ->findBy(['name' => $terms])
                    ;
                },
            ], $this->dataProviders->getProvider(Foo::class)),
        ]);

        self::assertEquals(json_encode([4 => 'Foo 1']), $response->getContent());

        $request->query->set('q', 'Bar 0');
        $response = $r->invoke($this->autocompleteType, $request, [
            'class' => Bar::class,
            'identifier' => 'id',
            'properties' => ['name', 'children.name'],
            'display' => ['name'],
            'strategy' => 'equals',
            'format' => function (array $normalized, Response $response): Response {
                return $response->setContent(json_encode($normalized));
            },
            'provider' => $this->dataProviders->getProviderByClassName(ODMDataProvider::class),
        ]);

        self::assertEquals(json_encode([1 => 'Bar 0']), $response->getContent());
    }

    public function testResolveProvider()
    {
        $r = new \ReflectionMethod(AutocompleteType::class, 'resolveProvider');
        $r->setAccessible(true);

        self::assertInstanceOf(ProxyDataProvider::class, $r->invoke($this->autocompleteType, Foo::class, function () {}));
        self::assertInstanceOf(ProxyDataProvider::class, $r->invoke($this->autocompleteType, Foo::class, []));

        try {
            $r->invoke($this->autocompleteType, Foo::class, Foo::class);
            self::assertTrue(false);
        } catch (\Exception $e) {
            self::assertInstanceOf(\InvalidArgumentException::class, $e);
        }

        $customProvider = new ProxyDataProvider();
        self::assertEquals($customProvider, $r->invoke($this->autocompleteType, Foo::class, $customProvider));

        try {
            $r->invoke($this->autocompleteType, Foo::class, new Foo());
            self::assertTrue(false);
        } catch (\Exception $e) {
            self::assertInstanceOf(\UnexpectedValueException::class, $e);
        }

        try {
            $r->invoke($this->autocompleteType, self::class, null);
            self::assertTrue(false);
        } catch (\Exception $e) {
            self::assertInstanceOf(\RuntimeException::class, $e);
        }

        self::assertInstanceOf(ORMDataProvider::class, $r->invoke($this->autocompleteType, Foo::class, null));
    }
}
