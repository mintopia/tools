<?php

namespace App\Console\Commands\OpnSense;

use App\Console\Commands\OpnSense\Models\Lease;
use App\Console\Commands\OpnSense\Models\Scope;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use IPTools\IP;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;
use Symfony\Component\Console\Helper\TableSeparator;
use function Laravel\Prompts\table;

class DHCP extends Command
{
    const string ENDPOINT = 'https://router.entropylan.party/';
    const string KEY = '';
    const string SECRET = '';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opnsense:dhcp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'DHCP Information';

    /**
     * @var Collection<Scope>
     */
    protected Collection $networks;
    protected Collection $leases;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->createNetworks();
        $this->getLeases();
        $this->displayNetworks();
        $this->displayLeases();
    }

    protected function createNetworks(): void
    {
        $this->networks = new Collection();
        $this->networks->add(new Scope('Public IPv4', '44.30.69.0/25', '44.30.69.10-44.30.69.127'));
        $this->networks->add(new Scope('Public IPv6', '2a0f:85c1:d91::/64', '2a0f:85c1:d91::1000-2a0f:85c1:d91::2000'));
        $this->networks->add(new Scope('Private IPv4', '10.30.0.0/24', '10.30.0.100-10.30.0.254'));
        $this->networks->add(new Scope('Private IPv6', '2a0f:85c1:d91:1030::/64', '2a0f:85c1:d91:1030::1000-2a0f:85c1:d91:1030::2000'));
    }

    protected function getLeases(): void
    {
        $this->leases = new Collection();
        $client = new Client([
            'base_uri' => self::ENDPOINT,
            'auth' => [
                self::KEY,
                self::SECRET,
            ],
        ]);
        $data = json_decode($client->get('/api/dnsmasq/leases/search')->getBody()->getContents());
        if ($data === false) {
            return;
        }

        foreach ($data->rows as $row) {
            $this->leases->push(new Lease($this->networks, $row));
        }
    }

    public function displayNetworks(): void
    {
        $this->output->writeln('  <fg=cyan>Networks</>');
        $headers = [
            'Name',
            'Network',
            'Start',
            'End',
            'Total',
            'Used',
            'Free',
            'Percentage',
        ];
        $rows = [];
        $right = [
            'style' => new TableCellStyle([
                'align' => 'right',
            ]),
        ];
        foreach ($this->networks as $network) {
            $percent = round(($network->used->count() / $network->range->count()) * 100, 2);
            $colour = 'green';
            if ($percent > 80) {
                $colour = 'yellow';
            }
            if ($percent > 90) {
                $colour = 'red';
            }
            $rows[] = [
                $network->name,
                (string) $network->network,
                (string) $network->range->getFirstIP(),
                (string) $network->range->getLastIP(),
                new TableCell($network->range->count(), $right),
                new TableCell($network->used->count(), $right),
                new TableCell($network->range->count() - $network->used->count(), $right),
                new TableCell("<fg={$colour}>{$percent}%</>", $right),
            ];
        }
        table($headers, $rows);
    }

    protected function displayLeases(): void
    {
        $this->output->writeln('  <fg=cyan>Leases</>');
        $headers = [
            'MAC Address',
            'Manufacturer',
            'Hostname',
            'IPv4',
            'IPv6',
        ];
        $rows = [];
        $grouped = $this->leases->sortBy('ip')->groupBy('macAddress');
        foreach ($grouped as $macAddress => $leases) {
            if (count($rows) > 0) {
                $rows[] = new TableSeparator();
            }
            $ipv4 = $leases->where('version', IP::IP_V4);
            $ipv6 = $leases->where('version', IP::IP_V6);
            $rows[] = [
                $leases[0]->macAddress,
                $leases[0]->macInfo,
                $leases[0]->hostname,
                $ipv4->map(function (Lease $lease) {
                    return (string)$lease->ip;
                })->implode(PHP_EOL),
                $ipv6->map(function (Lease $lease) {
                    return (string)$lease->ip;
                })->implode(PHP_EOL),
            ];
        }
        table($headers, $rows);
    }
}
