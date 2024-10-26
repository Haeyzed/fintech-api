<?php

use App\Enums\TransactionStatusEnum;
use App\Models\BankAccount;
use App\Models\PaymentMethod;
use App\Models\User;
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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(BankAccount::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(PaymentMethod::class)->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default(TransactionStatusEnum::PENDING);
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
