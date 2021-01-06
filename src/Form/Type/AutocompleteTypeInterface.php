<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Form\Type;

interface AutocompleteTypeInterface
{
    /**
     * Normalize object[] to choices like ['id_of_object' => 'label_to_display'].
     */
    public function buildChoices(iterable $data, array $options): array;
}
