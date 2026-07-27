<?php

namespace App\Services;

/**
 * Compatibility boundary for legacy Festival AI callers.
 *
 * A Festival AI visual is one provider-created image. Business branding is
 * supplied to the provider in the prompt and as a logo reference image; this
 * class must never add a header, footer, logo, contacts, or panel afterwards.
 */
class FestivalAiBrandComposer
{
    public function compose(string $imageBinary, array $business, array $chrome): string
    {
        return $imageBinary;
    }
}
