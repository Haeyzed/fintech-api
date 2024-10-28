<?php

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Country::class)->nullable();
            $table->foreignIdFor(Currency::class)->nullable();
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('code');
            $table->string('slug')->unique()->nullable();
            $table->string('long_code')->nullable();
            $table->string('gateway')->nullable();
            $table->boolean('pay_with_bank')->default(false);
            $table->string('ussd')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};
