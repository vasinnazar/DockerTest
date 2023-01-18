<?php

namespace App\Console\Commands;

use App\Services\SettlementAgreementsService;
use Illuminate\Console\Command;

class SettlementAgreements extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settlement agreements';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'автоматические мировые соглашения';

    private $agreementsService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(SettlementAgreementsService $agreementsService)
    {
        parent::__construct();
        $this->agreementsService = $agreementsService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): int
    {
        $this->agreementsService->autoSettlementAgreements();
        return 0;
    }

}
