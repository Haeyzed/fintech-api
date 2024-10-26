<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\ExportReady;
use Illuminate\Support\Facades\Storage;

class SendExportEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $email;
    protected $fileName;
    protected $columns;
    protected $model;

    public function __construct($email, $fileName, $columns, $model)
    {
        $this->email = $email;
        $this->fileName = $fileName;
        $this->columns = $columns;
        $this->model = $model;
    }

    public function handle(): void
    {
        Mail::to($this->email)->send(new ExportReady($this->fileName, $this->columns, $this->model));

        // Delete the file after sending
        Storage::delete($this->fileName);
    }
}
