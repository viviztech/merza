<?php

namespace App\Filament\Filters;

use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * "When did this arrive" date-range filter, shared across Contacts, Leads,
 * and Conversations so the picker/behaviour stays identical everywhere.
 */
class CreatedAtRangeFilter
{
    public static function make(string $label = 'Date Added'): Filter
    {
        return Filter::make('created_at')
            ->label($label)
            ->form([
                DatePicker::make('created_from')
                    ->label('From')
                    ->native(false),
                DatePicker::make('created_until')
                    ->label('Until')
                    ->native(false),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when($data['created_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                    ->when($data['created_until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];

                if ($data['created_from'] ?? null) {
                    $indicators['created_from'] = 'From ' . Carbon::parse($data['created_from'])->format('d M Y');
                }

                if ($data['created_until'] ?? null) {
                    $indicators['created_until'] = 'Until ' . Carbon::parse($data['created_until'])->format('d M Y');
                }

                return $indicators;
            });
    }
}
