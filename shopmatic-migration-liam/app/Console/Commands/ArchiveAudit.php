<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Models\Audit;

class ArchiveAudit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audits:archive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archives the audits older than 7 days.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Storage::disk('local')->put('audits.log', '');
        
        Audit::where('created_at', '<', now()->subDays(7))->chunkById(200, function($audits) {
            $string = "";
            $ids = [];
            foreach ($audits as $audit) {
                $ids[] = $audit->id;
                $string .= $audit->created_at . " [" . $audit->auditable_type . ':' . $audit->auditable_id . ':' . $audit->event .'] User ID:' . ($audit->user_id ?? '-') . '. IP: ' . $audit->ip_address . '. URL: ' . $audit->url . '. Old: ' . print_r($audit->old_values, true) . '. New: ' . print_r($audit->new_values, true) . "\r\n";
            }
            Storage::disk('local')->append('audits.log', $string);
            Audit::whereIn('id', $ids)->delete();
        });
        $stream = Storage::disk('local')->getDriver()->readStream('audits.log');

        Storage::disk('s3')->put('audits/' . now()->format('Y-m-d') . '-audits.log', $stream);
        
    }
}
