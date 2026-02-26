<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UkHolidaysService
{
    protected ?Collection $holidays = null;

    /**
     * Get UK holidays from the government API and cache for 1 day.
     */
    protected function getHolidays(): Collection
    {
        if ($this->holidays !== null) {
            return $this->holidays;
        }

        $this->holidays = Cache::remember('uk_holidays', 86400, function () {
            try {
                $response = Http::get('https://www.gov.uk/bank-holidays.json');

                if (!$response->successful()) {
                    Log::warning('Failed to fetch UK holidays', ['status' => $response->status()]);
                    return collect();
                }

                $data = $response->json();

                $englandWales = $data['england-and-wales'];
                $dates = collect();

                // Check if 'events' key exists (current API structure)
                if (isset($englandWales['events']) && is_array($englandWales['events'])) {
                    foreach ($englandWales['events'] as $event) {
                        if (isset($event['date'])) {
                            try {
                                // Parse date from YYYY-MM-DD format (current API format)
                                $date = Carbon::parse($event['date']);
                                $dates->push($date->format('Y-m-d'));
                            } catch (\Exception $e) {
                                Log::warning('Failed to parse UK holiday date', [
                                    'date' => $event['date'],
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    }
                } else {
                    // Fallback: try old structure with year keys
                    foreach ($englandWales as $key => $value) {
                        // Skip non-numeric keys (like "title")
                        if (!is_numeric($key)) {
                            continue;
                        }

                        // Each year contains an array of events
                        if (is_array($value)) {
                            foreach ($value as $event) {
                                if (isset($event['date'])) {
                                    try {
                                        // Parse date from DD/MM/YYYY format
                                        $date = Carbon::createFromFormat('d/m/Y', $event['date']);
                                        $dates->push($date->format('Y-m-d'));
                                    } catch (\Exception $e) {
                                        Log::warning('Failed to parse UK holiday date', [
                                            'date' => $event['date'],
                                            'error' => $e->getMessage()
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }

                return $dates;
            } catch (\Exception $e) {
                Log::error('Failed to fetch UK holidays', ['error' => $e->getMessage()]);
                return collect();
            }
        });

        return $this->holidays;
    }

    /**
     * Check if a date is a UK public holiday.
     */
    public function isHoliday(CarbonImmutable $date): bool
    {
        return $this->getHolidays()->contains($date->format('Y-m-d'));
    }

    /**
     * Check if a date is a weekend.
     */
    public function isWeekend(CarbonImmutable $date): bool
    {
        return $date->isWeekend();
    }

    /**
     * Get the day type for a date.
     */
    public function getDayType(CarbonImmutable $date): string
    {
        if ($this->isHoliday($date)) {
            return 'holiday';
        }

        if ($this->isWeekend($date)) {
            return 'weekend';
        }

        return 'weekday';
    }
}


