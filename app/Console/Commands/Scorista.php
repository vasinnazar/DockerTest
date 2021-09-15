<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Claim;
use App\Utils\HelperUtil;
use Log;
use App\MySoap;
use Carbon\Carbon;

class Scorista extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scorista';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Получить данные о решении по заявкам из Scorista';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        \App\Scorista::checkStatuses();
    }

}
