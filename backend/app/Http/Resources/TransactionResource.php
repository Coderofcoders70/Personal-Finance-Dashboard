<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array. It's like a formatter between backend and frontend
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'amount' => $this->amount,
            'date' => $this->transaction_date,
            'category' => new CategoryResource($this->whenLoaded('category')), // If the category relationship has been loaded, include the formatted category
        ];
    }
}
