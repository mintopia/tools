<?php
namespace App\Console\Commands\Flights;

class SearchCommand extends AbstractSearchCommand
{
    protected $signature = 'flights:search';
    protected $description = 'Fetch one-way flights for a date range';
}
