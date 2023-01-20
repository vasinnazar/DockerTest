<?php

namespace App\Console\Commands;

use App\Services\RepaymentOfferService;
use Illuminate\Console\Command;

class RepaymentOfferAutoPeace extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repayment-offers:auto-peace';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'автоматические мировые соглашения для УПР';

    private $repaymentOfferService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(RepaymentOfferService $repaymentOfferService)
    {
        parent::__construct();
        $this->repaymentOfferService = $repaymentOfferService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): int
    {
        $this->repaymentOfferService->autoPeaceForUPR();
        return 0;
    }

}
