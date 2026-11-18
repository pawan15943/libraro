<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ordering & Services (docs/ORDERING.md): learners order food, stationery, printing and other
 * services to their seat, a cafeteria table or the counter; staff run the orders from a board.
 *
 * Every table is per branch (library_id + branch_id). Order status, payment status and the
 * fulfilment status of each line are kept apart on purpose (spec §8 / "Important Design Principle").
 * All tables are new: dropping them (down) leaves the rest of the portal untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        // One row per branch: switches, rules and timings of the module
        if (! Schema::hasTable('order_settings')) {
            Schema::create('order_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('library_id')->index();
                $table->unsignedBigInteger('branch_id')->unique();
                $table->boolean('is_enabled')->default(false);
                // order modes
                $table->boolean('mode_seat')->default(true);
                $table->boolean('mode_table')->default(false);
                $table->boolean('mode_counter')->default(true);
                $table->boolean('seat_change_allowed')->default(false);
                // operating hours (null = whole day) and timings
                $table->time('opens_at')->nullable();
                $table->time('closes_at')->nullable();
                $table->unsignedSmallInteger('prep_minutes')->default(15);
                $table->decimal('delivery_charge', 10, 2)->default(0);
                // payment methods (also limited per category)
                $table->boolean('pay_online')->default(true);
                $table->boolean('pay_offline')->default(true);
                $table->boolean('pay_later')->default(false);
                $table->enum('pay_later_scope', ['all', 'selected'])->default('all');
                $table->json('pay_later_learner_ids')->nullable();
                $table->decimal('pay_later_limit', 10, 2)->default(0); // unpaid amount a learner may run up (0 = no limit)
                // eligibility
                $table->boolean('expired_can_order')->default(false);
                $table->unsignedTinyInteger('max_open_orders')->default(3); // 0 = no limit
                // staff response
                $table->unsignedSmallInteger('reminder_minutes')->default(2);
                $table->unsignedSmallInteger('escalation_minutes')->default(5);
                $table->boolean('sound_enabled')->default(true);
                // replacements
                $table->boolean('replacement_enabled')->default(true);
                $table->boolean('replacement_multiple')->default(true);
                $table->boolean('partial_fulfilment')->default(true);
                $table->unsignedSmallInteger('replacement_timeout_minutes')->default(10);
                $table->enum('replacement_timeout_action', ['remove', 'escalate'])->default('remove');
                // cancellation: the last stage at which a learner may still cancel by themselves
                $table->enum('learner_cancel_until', ['new', 'under_review', 'confirmed'])->default('new');
                // printing uploads
                $table->string('upload_types', 100)->default('pdf,jpg,jpeg,png,doc,docx');
                $table->unsignedSmallInteger('upload_max_mb')->default(10);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('order_categories')) {
            Schema::create('order_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('library_id');
                $table->unsignedBigInteger('branch_id');
                $table->string('name', 80);
                $table->string('icon', 40)->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_enabled')->default(true);
                $table->boolean('is_service')->default(false); // requests (charging, locker…) rather than goods
                $table->boolean('allow_online')->default(true);
                $table->boolean('allow_offline')->default(true);
                $table->boolean('allow_pay_later')->default(true);
                $table->timestamps();
                $table->softDeletes();
                $table->index(['library_id', 'branch_id']);
            });
        }

        if (! Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('library_id');
                $table->unsignedBigInteger('branch_id');
                $table->unsignedBigInteger('order_category_id')->index();
                $table->string('name', 120);
                $table->string('description', 500)->nullable();
                $table->string('image')->nullable();
                $table->decimal('price', 10, 2)->default(0);
                $table->string('price_unit', 30)->nullable(); // e.g. "per page"
                $table->boolean('is_enabled')->default(true);   // shown in the menu at all
                $table->boolean('is_available')->default(true); // in stock right now
                $table->boolean('is_featured')->default(false);
                $table->boolean('needs_upload')->default(false); // printing: learner attaches a file
                $table->json('modes')->nullable(); // null = every mode; else subset of seat/table/counter
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
                $table->index(['library_id', 'branch_id']);
            });
        }

        if (! Schema::hasTable('order_tables')) {
            Schema::create('order_tables', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('library_id');
                $table->unsignedBigInteger('branch_id');
                $table->string('label', 30);
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['branch_id', 'label']);
                $table->index('library_id');
            });
        }

        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('library_id');
                $table->unsignedBigInteger('branch_id');
                $table->unsignedBigInteger('learner_id');
                $table->string('order_no', 30)->unique();
                $table->string('idempotency_key', 64)->nullable(); // one order per checkout click
                $table->enum('mode', ['seat', 'table', 'counter']);
                $table->string('seat_no', 50)->nullable();
                $table->unsignedBigInteger('order_table_id')->nullable();
                $table->string('table_label', 30)->nullable();
                // order status: new, under_review, attention, replacement_pending, confirmed, preparing,
                // ready, out_for_delivery, delivery_attempted, delivered, collected, completed, rejected, cancelled
                $table->string('status', 30)->default('new');
                $table->enum('payment_method', ['online', 'offline', 'pay_later']);
                // payment status: pending, processing, paid, failed, partially_paid, refund_pending, refunded, partially_refunded
                $table->string('payment_status', 30)->default('pending');
                $table->decimal('subtotal', 10, 2)->default(0);
                $table->decimal('delivery_charge', 10, 2)->default(0);
                $table->decimal('total', 10, 2)->default(0);
                $table->decimal('paid_amount', 10, 2)->default(0);
                $table->decimal('refunded_amount', 10, 2)->default(0);
                $table->boolean('allow_partial')->default(true);
                $table->string('note', 500)->nullable();
                $table->string('payment_screenshot')->nullable();
                $table->string('payment_ref', 100)->nullable();
                $table->timestamp('estimated_ready_at')->nullable();
                // who handles it (library owner or staff)
                $table->string('handled_by_type', 20)->nullable();
                $table->unsignedBigInteger('handled_by_id')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('preparing_at')->nullable();
                $table->timestamp('ready_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->string('cancel_reason', 255)->nullable();
                $table->string('reject_reason', 255)->nullable();
                // staff-response escalation (orders:sweep)
                $table->timestamp('reminded_at')->nullable();
                $table->timestamp('escalated_at')->nullable();
                $table->unsignedInteger('version')->default(0); // optimistic lock: two staff, one order
                $table->timestamps();
                $table->unique(['learner_id', 'idempotency_key']);
                $table->index(['branch_id', 'status']);
                $table->index(['branch_id', 'payment_status']);
                $table->index(['learner_id', 'created_at']);
                $table->index('library_id');
            });
        }

        if (! Schema::hasTable('order_lines')) {
            Schema::create('order_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->index();
                $table->unsignedBigInteger('order_item_id')->nullable();
                $table->unsignedBigInteger('order_category_id')->nullable();
                // snapshot at the time of ordering (the item may change or go later)
                $table->string('category_name', 80)->nullable();
                $table->string('item_name', 120);
                $table->decimal('unit_price', 10, 2);
                $table->unsignedSmallInteger('quantity')->default(1);
                $table->decimal('line_total', 10, 2);
                // fulfilment status: pending, unavailable, replacement_pending, replaced, removed, fulfilled
                $table->string('fulfilment_status', 30)->default('pending');
                $table->unsignedBigInteger('replaced_line_id')->nullable(); // line this one replaced
                $table->string('upload_path')->nullable();
                $table->string('upload_name')->nullable();
                $table->json('options')->nullable(); // print specs: copies, colour, sides
                $table->string('note', 255)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('order_replacements')) {
            Schema::create('order_replacements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->index();
                $table->unsignedBigInteger('order_line_id')->index();
                $table->unsignedBigInteger('suggested_item_id')->nullable();
                $table->string('suggested_name', 120)->nullable(); // null = "no replacement, item unavailable"
                $table->decimal('suggested_price', 10, 2)->nullable();
                $table->unsignedSmallInteger('quantity')->default(1);
                $table->decimal('price_diff', 10, 2)->default(0); // + learner pays more, − refund/credit
                $table->enum('status', ['pending', 'accepted', 'rejected', 'expired', 'withdrawn'])->default('pending');
                $table->timestamp('deadline_at')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->timestamp('reminded_at')->nullable();
                $table->string('suggested_by_type', 20)->nullable();
                $table->unsignedBigInteger('suggested_by_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('order_payments')) {
            Schema::create('order_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('library_id');
                $table->unsignedBigInteger('branch_id');
                $table->unsignedBigInteger('order_id')->index();
                $table->enum('kind', ['payment', 'refund'])->default('payment');
                $table->string('method', 30); // upi_qr, cash, upi_counter, card, credit…
                $table->decimal('amount', 10, 2);
                $table->enum('status', ['pending', 'verified', 'failed'])->default('pending');
                $table->string('txn_ref', 100)->nullable();
                $table->string('screenshot')->nullable();
                $table->string('note', 255)->nullable();
                $table->string('recorded_by_type', 20)->nullable();
                $table->unsignedBigInteger('recorded_by_id')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
                $table->unique(['order_id', 'kind', 'txn_ref']); // the same UTR is never counted twice
                $table->index(['branch_id', 'created_at']);
            });
        }

        // Audit trail: every important action with who and when (business rules 12 and 15)
        if (! Schema::hasTable('order_activities')) {
            Schema::create('order_activities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->index();
                $table->string('actor_type', 20); // learner, library, library_user, system
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('action', 50);
                $table->string('from_status', 30)->nullable();
                $table->string('to_status', 30)->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        foreach (['order_activities', 'order_payments', 'order_replacements', 'order_lines', 'orders',
            'order_tables', 'order_items', 'order_categories', 'order_settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
