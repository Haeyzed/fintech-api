<?php

use App\Models\Currency;
use App\Models\User;
use App\Models\Bank;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Bank::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Currency::class)->constrained()->cascadeOnDelete();
            $table->string('account_number');
            $table->string('account_type');
            $table->decimal('balance', 10, 2)->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
