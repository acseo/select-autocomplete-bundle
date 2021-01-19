<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Form\Type;

use Acseo\SelectAutocomplete\DataProvider\DataProviderInterface;
use Acseo\SelectAutocomplete\DataProvider\DataProviderRegistry;
use Acseo\SelectAutocomplete\DataProvider\ProxyDataProvider;
use Acseo\SelectAutocomplete\Form\Transformer\ModelTransformer;
use Acseo\SelectAutocomplete\Traits\PropertyAccessorTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

class AutocompleteType extends AbstractType
{
    use PropertyAccessorTrait;

    protected const RESPONSE_DEFAULT_FORMAT = 'json';
    protected const REQUEST_FIELD_KEY = 'acseo_autocomplete_form_name';
    protected const RESPONSE_FORMAT_KEY = 'response_format';
    protected const DEFAULT_ATTR_CLASS = 'acseo-select-autocomplete';

    /**
     * @var EncoderInterface
     */
    protected $encoder;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var DataProviderRegistry
     */
    protected $dataProviders;

    public function __construct(RequestStack $requestStack, EncoderInterface $encoder, DataProviderRegistry $dataProviders)
    {
        $this->encoder = $encoder;
        $this->requestStack = $requestStack;
        $this->dataProviders = $dataProviders;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addModelTransformer(new ModelTransformer($options['provider'], $options['class'], $options['identifier'], $options['multiple']))
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($options): void {
                $request = $this->requestStack->getCurrentRequest();

                // Check if request is default autocomplete action
                if (null === $request || null === $field = $request->query->get(static::REQUEST_FIELD_KEY)) {
                    return;
                }

                // Check if autocomplete action concerned this field to avoid conflict between
                // other AutocompleteType fields in other part of form
                if ($event->getForm()->createView()->vars['full_name'] === $field) {
                    $this->renderAutocompleteResponse($request, $options);
                }
            })
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['multiple']) {
            $view->vars['full_name'] .= '[]';
        }

        // Input attributes
        $view->vars['attr']['multiple'] = $options['multiple'];
        $view->vars['attr']['name'] = $view->vars['full_name'];
        $view->vars['attr']['required'] = $options['required'];
        $view->vars['attr']['class'] = $options['attr']['class'] ?? static::DEFAULT_ATTR_CLASS;

        // Build entrypoint used to fetch results of search
        $view->vars['attr']['data-autocomplete-url'] = $options['autocomplete_url']
            ?? $this->buildAutocompleteEntrypoint($view->vars['full_name'])
        ;

        // Cast data to array to build selected choices
        $data = $form->getData();
        if (!is_iterable($data)) {
            $data = null !== $data ? [$data] : [];
        }

        // Build selected choices
        $view->vars['selected'] = $this->buildChoices($data, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'class' => null,
                'properties' => 'id',
                'property' => null,
                'strategy' => 'contains',
                'multiple' => false,
                'format' => static::RESPONSE_DEFAULT_FORMAT,
                'display' => null,
                'provider' => null,
                'autocomplete_url' => null,
                'identifier' => 'id',
            ])

            ->setRequired(['class'])

            ->setAllowedTypes('class', ['string'])
            ->setAllowedTypes('display', ['string', 'callable', 'array', 'null'])
            ->setAllowedTypes('strategy', ['string', 'null'])
            ->setAllowedTypes('properties', ['string', 'array'])
            ->setAllowedTypes('property', ['string', 'array', 'null'])
            ->setAllowedTypes('format', ['string', 'callable'])
            ->setAllowedTypes('identifier', ['string', 'null'])
            ->setAllowedTypes('multiple', ['boolean'])
            ->setAllowedTypes('provider', ['callable', 'string', 'array', 'object', 'null'])
            ->setAllowedTypes('autocomplete_url', ['string', 'null'])

            ->setNormalizer('properties', function (OptionsResolver $options, $value) {
                $properties = $options['property'] ?? $value;

                return \is_array($properties) ? $properties : [$properties];
            })
            ->setNormalizer('display', function (OptionsResolver $options, $value) {
                if (null === $value) {
                    return $options['properties'];
                }

                return \is_array($value) ? $value : [$value];
            })
            ->setNormalizer('provider', function (OptionsResolver $options, $value) {
                return $this->resolveProvider($options['class'], $value);
            })
            ->setNormalizer('format', function (OptionsResolver $options, $value) {
                if (\is_callable($value)) {
                    return $value;
                }

                return function (array $normalized, Response $response, string $expectedFormat = null) use ($value): Response {
                    return $this->encodeSearchResponse($normalized, $response, $expectedFormat ?? $value);
                };
            })
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'acseo_select_autocomplete';
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return TextType::class;
    }

    /**
     * Normalize retrieved collection to exploitable choices.
     */
    protected function buildChoices(iterable $data, array $options): array
    {
        $choices = [];
        $display = $options['display'];

        foreach ($data as $item) {
            if (\is_callable($display)) {
                $label = $display($item);
            } else {
                $values = [];
                foreach ($display as $property) {
                    $values[] = $this->getValue($item, $property);
                }
                $label = implode(' ', $values);
            }

            // Build choices like [value => label]
            $choices[$this->getValue($item, $options['identifier'])] = $label;
        }

        return $choices;
    }

    /**
     * Build autocomplete entrypoint where search results can be retrieved.
     * This entrypoint will be set in widget attributes to be call from front side.
     */
    protected function buildAutocompleteEntrypoint(string $key): ?string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return null;
        }

        // By default the entrypoint uri is current uri
        return sprintf('%s?%s=%s', $request->getPathInfo(), static::REQUEST_FIELD_KEY, $key);
    }

    /**
     * Fetch results for a terms by invoking providers and build response of normalized exploitable choices.
     */
    protected function renderAutocompleteResponse(Request $request, array $options): Response
    {
        $terms = (string) $request->query->get('q');

        // Call provider to fetch results
        $collection = $options['provider']->findByTerms($options['class'], $options['properties'], $terms, $options['strategy']);

        // Normalize to exploitable choices and encode response
        $normalized = $this->buildChoices($collection, $options);
        $expectedFormat = $request->query->get(static::RESPONSE_FORMAT_KEY);
        $response = $options['format']($normalized, new Response(), $expectedFormat);

        // No need to let process continue, stop process and print response to get best performances.
        return $response->send();
    }

    /**
     * Resolve provider service by analyzing provider option value.
     *
     * @param string|callable|array|object $provider
     */
    protected function resolveProvider(string $class, $provider): DataProviderInterface
    {
        if (!\is_callable($provider) && \is_object($provider)) {
            if (!$provider instanceof DataProviderInterface) {
                throw new \UnexpectedValueException(sprintf('Provider must implements %s', DataProviderInterface::class));
            }

            return $provider;
        }

        if (\is_string($provider)) {
            $service = $this->dataProviders->getProviderByClassName($provider);

            if (null === $service) {
                throw new \InvalidArgumentException(sprintf('No provider found with class "%s"', $provider));
            }

            return $service;
        }

        $service = $this->dataProviders->getProvider($class);

        if (\is_callable($provider)) {
            return new ProxyDataProvider(['find_by_terms' => $provider], $service);
        }

        if (\is_array($provider)) {
            return new ProxyDataProvider($provider, $service);
        }

        if (null === $service) {
            throw new \RuntimeException(sprintf('No provider found for model class "%s"', $class));
        }

        return $service;
    }

    /**
     * Encode search response to expected format.
     */
    protected function encodeSearchResponse(array $normalized, Response $response, string $expectedFormat): Response
    {
        $response->headers->set('Content-Type', sprintf('application/%s', $expectedFormat));

        return $response->setContent($this->encoder->encode($normalized, $expectedFormat));
    }
}
