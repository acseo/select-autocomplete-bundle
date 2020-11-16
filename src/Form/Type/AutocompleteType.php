<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Form\Type;

use Acseo\SelectAutocomplete\Doctrine\ManagerRegistry;
use Acseo\SelectAutocomplete\Form\Transformer\DoctrineObjectTransformer;
use Acseo\SelectAutocomplete\Formatter\Formatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\RouterInterface;

class AutocompleteType extends AbstractType
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var Formatter
     */
    private $formatter;

    public function __construct(ManagerRegistry $registry, Formatter $formatter, RouterInterface $router)
    {
        $this->router = $router;
        $this->registry = $registry;
        $this->formatter = $formatter;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(
            new DoctrineObjectTransformer($this->registry, $options['class'], $options['multiple'])
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $data = $form->getData();

        if (!is_iterable($data)) {
            $data = null !== $data ? [$data] : [];
        }

        if ($options['multiple']) {
            $view->vars['full_name'] = ($view->vars['full_name']).'[]';
        }

        $view->vars['selected'] = $this->formatter->format($options['class'], $data, $options['property']);
        $view->vars['attr']['multiple'] = $options['multiple'];
        $view->vars['attr']['name'] = $view->vars['full_name'];
        $view->vars['attr']['required'] = $options['required'];

        $view->vars['attr']['data-url'] = $this->router->generate('acseo_select_autocomplete', [
            'class' => $options['class'],
            'property' => $options['property'],
            'strategy' => $options['strategy'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'invalid_message' => 'The selected item does not exist',
            'class' => null,
            'property' => null,
            'strategy' => 'starts_with',
            'multiple' => false,
        ]);

        $resolver
            ->setRequired(['class'])
            ->setRequired(['property'])
        ;

        $resolver
            ->setAllowedTypes('class', ['string'])
            ->setAllowedTypes('property', ['string'])
            ->setAllowedTypes('strategy', ['string'])
            ->setAllowedTypes('multiple', ['boolean'])
        ;
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'acseo_autocomplete';
    }
}
