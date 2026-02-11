<?php
namespace App\Console\Commands\OpnSense\Models;

use Illuminate\Support\Collection;
use IPTools\Network;
use IPTools\Range;

class Scope
{
    public Network $network;
    /**
     * @var Collection<Lease>
     */
    public Collection $leases;
    /**
     * @var Collection<Lease>
     */
    public Collection $used;
    public Range $range;
    public function __construct(public string $name, string $network, string $range)
    {
        $this->leases = new Collection();
        $this->used = new Collection();
        $this->network = Network::parse($network);
        $this->range = Range::parse($range);
    }

    public function addLease(Lease $lease): self
    {
        $this->leases->push($lease);
        if ($this->range->contains($lease->ip)) {
            $this->used->push($lease);
        }
        return $this;
    }
}
