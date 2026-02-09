<?php
namespace App\Console\Commands\OpnSense\Models;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use IPTools\IP;

class Lease
{
    public CarbonImmutable $expiry;
    public string $macAddress;
    public ?string $hostname = null;
    public string $macInfo;
    public IP $ip;
    public Scope $scope;
    public string $version;

    /**
     * @param Collection<Scope> $scopes
     * @param object $row
     */
    public function __construct(Collection $scopes, object $row)
    {
        $this->expiry = CarbonImmutable::createFromTimestamp($row->expire);
        $this->macAddress = $row->hwaddr;
        $this->macInfo = $row->mac_info;
        $this->ip = IP::parse($row->address);
        $this->hostname = $row->hostname;
        $this->version = $this->ip->getVersion();

        foreach ($scopes as $scope) {
            if ($scope->network->getHosts()->contains($this->ip)) {
                $this->scope = $scope;
                $scope->addLease($this);
            }
        }
    }
}
