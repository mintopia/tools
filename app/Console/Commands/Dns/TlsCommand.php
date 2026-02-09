<?php

namespace App\Console\Commands\Dns;

use Illuminate\Console\Command;
use NetDNS2\Resolver;

class TlsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dns:dot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'DNS Over TLS Test';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $resolver = new Resolver(['nameservers' => ['1.1.1.1', '1.0.0.1']]);
        $resolver->use_tls = true;
        $result = $resolver->query('google.com', 'A');
        echo (string)$result;
    }
}
