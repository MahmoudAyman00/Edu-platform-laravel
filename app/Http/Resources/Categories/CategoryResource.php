<?php

namespace App\Http\Resources\Categories;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $translation = $this->translation();

        return [
            'id' => $this->id,
            'title' => $translation?->title,
            'description' => $translation?->description,
            'translations' => $this->whenLoaded(
                'translations',
                fn () => $this->translations->mapWithKeys(fn ($t) => [
                    $t->locale => ['title' => $t->title, 'description' => $t->description],
                ])
            ),
            'created_at' => $this->created_at,
        ];
    }
}
