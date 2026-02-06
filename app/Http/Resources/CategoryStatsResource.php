<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'category' => [
                'id' => $this->resource['category']['id'],
                'name' => $this->resource['category']['name'],
            ],
            'period' => $this->resource['period'],
            'date_range' => [
                'start' => $this->resource['date_range']['start'],
                'end' => $this->resource['date_range']['end'],
            ],
            'summary' => [
                'budget' => $this->resource['summary']['budget'],
                'spent' => $this->resource['summary']['spent'],
                'remaining' => $this->resource['summary']['remaining'],
                'percentage_used' => $this->resource['summary']['percentage_used'],
                'status' => $this->resource['summary']['status'],
                'average_daily_spending' => $this->resource['summary']['average_daily_spending'],
                'transaction_count' => $this->resource['summary']['transaction_count'],
            ],
            'daily_breakdown' => $this->resource['daily_breakdown'],
            'trend' => $this->resource['trend'],
            'warning' => $this->resource['warning'],
        ];
    }
}
