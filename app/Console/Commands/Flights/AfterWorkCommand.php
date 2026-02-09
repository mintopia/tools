<?php
namespace App\Console\Commands\Flights;

use Illuminate\Support\Collection;
use Mintopia\LaravelFlights\Models\Itinerary;

class AfterWorkCommand extends AbstractSearchCommand{
    protected $signature = 'flights:afterwork';
    protected $description = "Fetch flights that aren't during working hours";

    protected function filterItineraries(Collection $itineraries): Collection
    {
        $itineraries = $itineraries->filter(function (Itinerary $itinerary) {
            foreach ($itinerary->journeys as $journey) {
                if ($journey->departure->isWeekend()) {
                    continue;
                }
                if ($this->isPublicHoliday($journey->departure)) {
                    continue;
                }
                if ($journey->departure->hour > 19) {
                    continue;
                }
                if ($journey->arrival->hour < 10) {
                    continue;
                }
                return false;
            }
            return true;
        });

        return $itineraries->sortBy('price')->values();
    }
}
