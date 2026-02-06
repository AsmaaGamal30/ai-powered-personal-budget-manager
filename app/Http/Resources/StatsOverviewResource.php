<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatsOverviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'period' => $this->resource['period'],
            'date_range' => [
                'start' => $this->resource['date_range']['start'],
                'end' => $this->resource['date_range']['end'],
            ],
            'overview' => [
                'total_budget' => $this->resource['overview']['total_budget'],
                'total_spent' => $this->resource['overview']['total_spent'],
                'total_remaining' => $this->resource['overview']['total_remaining'],
                'percentage_used' => $this->resource['overview']['percentage_used'],
                'status' => $this->resource['overview']['status'],
            ],
            'category_breakdown' => $this->resource['category_breakdown'],
            'warnings' => $this->resource['warnings'],
            'insights' => $this->resource['insights'],
        ];
    }
}
