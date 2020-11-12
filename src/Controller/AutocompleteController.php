<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Controller;

use Acseo\SelectAutocomplete\Doctrine\DataProviderCollection;
use Acseo\SelectAutocomplete\Doctrine\Provider\DataProviderInterface;
use Acseo\SelectAutocomplete\Formatter\Formatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class AutocompleteController extends AbstractController
{
    public function __invoke(
        Request $request,
        DataProviderCollection $dataProvider,
        SerializerInterface $serializer,
        Formatter $formatter
    ): Response {
        $class = (string) $request->query->get('class');
        $property = (string) $request->query->get('property');
        $format = (string) $request->query->get('format', 'json');

        if (null !== $error = $this->isValidAndAllowed($class, $property)) {
            return new Response($serializer->serialize(['error' => $error], $format), 400);
        }

        $strategy = (string) $request->query->get('strategy', DataProviderInterface::DEFAULT_STRATEGY);
        $terms = (string) $request->query->get('terms');

        $results = $dataProvider->getCollection($class, $property, $terms, $strategy);
        $formatted = $formatter->format($class, $results, $property);

        return new Response($serializer->serialize($formatted, $format));
    }

    protected function isValidAndAllowed(string $class, string $property): ?string
    {
        if (!class_exists($class)) {
            return 'Unvalid class given';
        }

        if (!property_exists($class, $property)) {
            return 'Unvalid property given';
        }

        $allowedProperties = $this->getParameter('select_autocomplete.classes')[$class]['properties'] ?? null;

        if (!\in_array($property, $allowedProperties, true)) {
            return sprintf('Autocompletion of class "%s" on property "%s" is not allowed', $class, $property);
        }

        return null;
    }
}
