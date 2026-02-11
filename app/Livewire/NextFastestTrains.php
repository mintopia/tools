<?php

namespace App\Livewire;

use App\Models\TrainStation;
use App\Services\RealTimeTrains\RealTimeTrainsService;
use Livewire\Attributes\Url;
use Livewire\Component;

class NextFastestTrains extends Component
{
    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public ?TrainStation $fromStation = null;
    public ?TrainStation $toStation = null;
    public array $trains = [];
    public array $excluded = [];

    public function mount()
    {
        $this->trains = [];
        $this->excluded = [];

        if ($this->from && $this->to) {
            $this->search();
        }
    }

    public function search()
    {
        $validated = $this->validate([
            'from' => 'required|string|max:255',
            'to' => 'required|string|max:255',
        ]);

        $this->fromStation = TrainStation::where('crs', strtoupper($validated['from']))
            ->orWhere('name', 'like', $validated['from'])
            ->first();

        $this->toStation = TrainStation::where('crs', strtoupper($validated['to']))
            ->orWhere('name', 'like', $validated['to'])
            ->first();

        if (!$this->fromStation) {
            $this->addError('from', 'Station not found');
            $this->trains = [];
            return;
        }

        if (!$this->toStation) {
            $this->addError('to', 'Station not found');
            $this->trains = [];
            return;
        }

        $rtt = app(RealTimeTrainsService::class);
        $this->trains = $rtt->nextFastestTrains($this->fromStation, $this->toStation)
            ->map(fn ($train) => $this->normalizeTrain($train))
            ->values()
            ->all();
    }

    protected function normalizeTrain(object $train): array
    {
        return json_decode(json_encode($train, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    public function render()
    {
        return view('livewire.trains.next-fastest');
    }
}
