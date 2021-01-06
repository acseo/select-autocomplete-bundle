<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\EventListener;

use Acseo\SelectAutocomplete\Doctrine\DataProviderRegistry;
use Acseo\SelectAutocomplete\Doctrine\ManagerRegistry;
use Acseo\SelectAutocomplete\Form\Type\AutocompleteType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

final class ResponseListener
{
    private $managerRegistry;

    private $dataProviderRegistry;

    private $encoder;

    public function __construct(ManagerRegistry $managerRegistry, DataProviderRegistry $dataProviderRegistry, EncoderInterface $encoder)
    {
        $this->managerRegistry = $managerRegistry;
        $this->dataProviderRegistry = $dataProviderRegistry;
        $this->encoder = $encoder;
    }

    public function __invoke(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $terms = $request->query->get('q');
        $form = $request->attributes->get(AutocompleteType::AUTOCOMPLETE_REQUEST_CHILD);

        // Check context
        if (null === $terms || null === $form) {
            return;
        }

        $config = $form->getConfig();
        $formType = $config->getType()->getInnerType();

        // Check form type
        if (!$formType instanceof AutocompleteType) {
            throw new \RuntimeException(sprintf('"%s" must be instance of "%s"', \get_class($formType), AutocompleteType::class));
        }

        $class = $config->getOption('class');
        $provider = $config->getOption('provider');

        $results = \is_callable($provider)
            ? $provider($this->managerRegistry->getManagerForClass($class)->getRepository($class), $terms)
            : $this->dataProviderRegistry->fetch($class, $config->getOption('property'), $terms, $config->getOption('strategy'))
        ;

        // Normalize results
        $data = $formType->buildChoices($results, $config->getOptions());
        $reponseContent = $this->encoder->encode($data, $request->query->get('autocomplete_format', 'json'));

        // Override event response
        $event->setResponse(new Response($reponseContent));
    }
}
