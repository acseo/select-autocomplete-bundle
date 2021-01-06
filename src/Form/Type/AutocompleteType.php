<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Form\Type;

use Acseo\SelectAutocomplete\Doctrine\ManagerRegistry;
use Acseo\SelectAutocomplete\Form\Transformer\DoctrineObjectTransformer;
use Acseo\SelectAutocomplete\Traits\PropertyAccessorTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AutocompleteType extends AbstractType implements AutocompleteTypeInterface
{
    use PropertyAccessorTrait;

    public const AUTOCOMPLETE_CONTEXT_PARAM = 'acseo_autocomplete_context';
    public const AUTOCOMPLETE_REQUEST_CHILD = 'acseo_autocomplete_form_child';

    private $registry;

    private $requestStack;

    public function __construct(ManagerRegistry $registry, RequestStack $requestStack)
    {
        $this->registry = $registry;
        $this->requestStack = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addModelTransformer(new DoctrineObjectTransformer($this->registry->getManagerForClass($options['class']), $options['class'], $options['multiple']))
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event): void {
                $request = $this->requestStack->getCurrentRequest();

                // Attach form builder in request attributes to retrieve it for building response
                if (null !== $request && null !== $context = $request->query->get(self::AUTOCOMPLETE_CONTEXT_PARAM)) {
                    $form = $event->getForm();
                    $fullName = $form->createView()->vars['full_name'] ?? null;

                    if ($fullName === $context) {
                        $request->attributes->set(self::AUTOCOMPLETE_REQUEST_CHILD, $form);
                    }
                }
            })
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // Force name to be a collection
        if ($options['multiple']) {
            $view->vars['full_name'] = $view->vars['full_name'].'[]';
        }

        // Input attributes
        $view->vars['attr']['multiple'] = $options['multiple'];
        $view->vars['attr']['name'] = $view->vars['full_name'];
        $view->vars['attr']['required'] = $options['required'];
        $view->vars['attr']['class'] = $options['attr']['class'] ?? 'acseo-select-autocomplete';

        // Get entrypoint to fetch results
        $view->vars['attr']['data-autocomplete-url'] = $options['autocomplete_url'];
        if (null === $options['autocomplete_url'] && $request = $this->requestStack->getCurrentRequest()) {
            $view->vars['attr']['data-autocomplete-url'] = sprintf('%s?%s=%s', $request->getPathInfo(), self::AUTOCOMPLETE_CONTEXT_PARAM, $view->vars['full_name']);
        }

        $data = $form->getData();
        if (!is_iterable($data)) {
            $data = null !== $data ? [$data] : [];
        }

        // Build selected choices
        $view->vars['selected'] = $this->buildChoices($data, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => null,
            'property' => 'id',
            'strategy' => 'contains',
            'multiple' => false,
            'display' => null,
            'provider' => null,
            'autocomplete_url' => null,
        ]);

        $resolver
            ->setRequired(['class'])
            ->setAllowedTypes('class', ['string'])
            ->setAllowedTypes('display', ['string', 'callable', 'null'])
            ->setAllowedTypes('strategy', ['string', 'null'])
            ->setAllowedTypes('property', ['string'])
            ->setAllowedTypes('multiple', ['boolean'])
            ->setAllowedTypes('provider', ['callable', 'null'])
            ->setAllowedTypes('autocomplete_url', ['string', 'null'])
        ;

        // Use property value as choice label if display is null
        $resolver->setNormalizer('display', function (OptionsResolver $options, $optionValue) {
            return \is_string($options['property']) && null === $optionValue ? $options['property'] : $optionValue;
        });
    }

    public function buildChoices(iterable $data, array $options): array
    {
        $class = $options['class'];
        $choiceLabel = $options['display'];
        $identifier = $this->registry->getManagerForClass($class)->getClassMetadata($class)->getIdentifierFieldNames()[0] ?? 'id';

        $choices = [];
        foreach ($data as $item) {
            $value = $this->getValue($item, $identifier);
            $choices[$value] = \is_callable($choiceLabel) ? $choiceLabel($item) : $this->getValue($item, $choiceLabel);
        }

        return $choices;
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'acseo_select_autocomplete';
    }
}
