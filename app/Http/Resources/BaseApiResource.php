<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BaseApiResource extends JsonResource
{
    public function toArray($request)
    {
        // Get original model array
        $data = $this->resource->toArray();

        // Replace null values (only for strings)
        return $this->replaceNullStrings($data);
    }

    protected function replaceNullStrings(array $data): array
    {
        foreach ($data as $key => $value) {

            if (is_array($value)) {
                $data[$key] = $this->replaceNullStrings($value);
            }

            elseif (is_null($value)) {
                $data[$key] = '';
            }
        }

        return $data;
    }
}