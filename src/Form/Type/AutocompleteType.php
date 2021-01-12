<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Form\Type;

use Acseo\SelectAutocomplete\DataProvider\DataProviderRegistry;
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
        $provider = \is_string($options['provider'])
            ? $this->dataProviders->getProviderByClassName($options['provider'])
            : $this->dataProviders->getProvider($options['class'])
        ;

        $builder
            ->addModelTransformer(new ModelTransformer($provider, $options['class'], $options['identifier'], $options['multiple']))
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
        $resolver->setDefaults([
            'class' => null,
            'property' => 'id',
            'strategy' => 'contains',
            'multiple' => false,
            'format' => null,
            'display' => null,
            'provider' => null,
            'autocomplete_url' => null,
            'identifier' => 'id',
        ]);

        $resolver
            ->setRequired(['class'])
            ->setAllowedTypes('class', ['string'])
            ->setAllowedTypes('display', ['string', 'callable', 'null'])
            ->setAllowedTypes('strategy', ['string', 'null'])
            ->setAllowedTypes('property', ['string', 'null'])
            ->setAllowedTypes('format', ['string', 'null'])
            ->setAllowedTypes('identifier', ['string', 'null'])
            ->setAllowedTypes('multiple', ['boolean'])
            ->setAllowedTypes('provider', ['callable', 'string', 'null'])
            ->setAllowedTypes('autocomplete_url', ['string', 'null'])
        ;

        $resolver->setNormalizer('display', function (OptionsResolver $options, $optionValue) {
            // Fallback to "property" option value if "display" option is null
            return \is_string($options['property']) && null === $optionValue ? $options['property'] : $optionValue;
        });
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
        $choiceLabel = $options['display'];

        foreach ($data as $item) {
            // Build choices like [value => label]
            $choices[$this->getValue($item, $options['identifier'])] = \is_callable($choiceLabel)
                ? $choiceLabel($item)
                : $this->getValue($item, $choiceLabel)
            ;
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

        // Determine usable provider
        $provider = \is_string($options['provider'])
            ? $this->dataProviders->getProviderByClassName($options['provider'])
            : $this->dataProviders->getProvider($options['class'])
        ;

        // Call provider to fetch results
        $collection = \is_callable($options['provider'])
            ? $options['provider']($terms, $provider)
            : $provider->findByTerms($options['class'], $options['property'], $terms, $options['strategy'])
        ;

        // Normalize to exploitable choices and encode response
        $format = $request->query->get(static::RESPONSE_FORMAT_KEY) ?? $options['format'] ?? static::RESPONSE_DEFAULT_FORMAT;
        $content = $this->encoder->encode($this->buildChoices($collection, $options), $format);

        $response = new Response($content, 200, [
            'Content-Type' => sprintf('application/%s', $format),
        ]);

        // No need to let process continue, stop process and print response to get best performances.
        return $response->send();
    }
}
