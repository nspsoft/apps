<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Check whether an index already exists safely across MySQL and SQLite.
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            return DB::table('sqlite_master')
                ->where('type', 'index')
                ->where('name', $indexName)
                ->exists();
        }

        if ($driver === 'mysql') {
            $dbName = DB::selectOne('select database() as name')?->name;
            if (!$dbName) {
                return false;
            }

            return DB::table('information_schema.statistics')
                ->where('table_schema', $dbName)
                ->where('table_name', $table)
                ->where('index_name', $indexName)
                ->exists();
        }

        return false;
    }

    /**
     * Run the migrations to add high-performance scalability indexes.
     */
    public function up(): void
    {
        // 1. activity_log: Optimize range queries, sorting, and dashboard aggregates
        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table) {
                if (Schema::hasColumn('activity_log', 'created_at') && !$this->indexExists('activity_log', 'activity_log_created_at_idx')) {
                    $table->index('created_at', 'activity_log_created_at_idx');
                }
                if (Schema::hasColumn('activity_log', 'log_name') && Schema::hasColumn('activity_log', 'created_at') && !$this->indexExists('activity_log', 'activity_log_log_created_idx')) {
                    $table->index(['log_name', 'created_at'], 'activity_log_log_created_idx');
                }
            });
        }

        // 2. whatsapp_messages: Optimize chat history order & subquery lookup per phone
        if (Schema::hasTable('whatsapp_messages')) {
            Schema::table('whatsapp_messages', function (Blueprint $table) {
                if (Schema::hasColumn('whatsapp_messages', 'phone') && Schema::hasColumn('whatsapp_messages', 'created_at') && !$this->indexExists('whatsapp_messages', 'whatsapp_messages_phone_created_idx')) {
                    $table->index(['phone', 'created_at'], 'whatsapp_messages_phone_created_idx');
                }
                if (Schema::hasColumn('whatsapp_messages', 'module') && Schema::hasColumn('whatsapp_messages', 'phone') && Schema::hasColumn('whatsapp_messages', 'created_at') && !$this->indexExists('whatsapp_messages', 'whatsapp_messages_module_phone_created_idx')) {
                    $table->index(['module', 'phone', 'created_at'], 'whatsapp_messages_module_phone_created_idx');
                }
            });
        }

        // 3. delivery_orders: Optimize warehouse delivery tracking, status filtering & calendar dates
        if (Schema::hasTable('delivery_orders')) {
            Schema::table('delivery_orders', function (Blueprint $table) {
                if (Schema::hasColumn('delivery_orders', 'status') && Schema::hasColumn('delivery_orders', 'created_at') && !$this->indexExists('delivery_orders', 'delivery_orders_status_created_idx')) {
                    $table->index(['status', 'created_at'], 'delivery_orders_status_created_idx');
                }
                if (Schema::hasColumn('delivery_orders', 'delivery_date') && !$this->indexExists('delivery_orders', 'delivery_orders_delivery_date_idx')) {
                    $table->index('delivery_date', 'delivery_orders_delivery_date_idx');
                }
            });
        }

        // 4. journals & journal_items: Optimize general ledger and financial statements
        if (Schema::hasTable('journals')) {
            Schema::table('journals', function (Blueprint $table) {
                if (Schema::hasColumn('journals', 'date') && !$this->indexExists('journals', 'journals_date_idx')) {
                    $table->index('date', 'journals_date_idx');
                }
                if (Schema::hasColumn('journals', 'status') && Schema::hasColumn('journals', 'date') && !$this->indexExists('journals', 'journals_status_date_idx')) {
                    $table->index(['status', 'date'], 'journals_status_date_idx');
                }
            });
        }

        if (Schema::hasTable('journal_items')) {
            Schema::table('journal_items', function (Blueprint $table) {
                if (Schema::hasColumn('journal_items', 'coa_id') && Schema::hasColumn('journal_items', 'created_at') && !$this->indexExists('journal_items', 'journal_items_coa_created_idx')) {
                    $table->index(['coa_id', 'created_at'], 'journal_items_coa_created_idx');
                }
            });
        }

        // 5. sales_orders: Optimize status & order_date filtering
        if (Schema::hasTable('sales_orders')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                if (Schema::hasColumn('sales_orders', 'status') && Schema::hasColumn('sales_orders', 'order_date') && !$this->indexExists('sales_orders', 'sales_orders_status_order_date_idx')) {
                    $table->index(['status', 'order_date'], 'sales_orders_status_order_date_idx');
                }
            });
        }

        // 6. inv_stock_movements: Optimize movement type and audit queries
        if (Schema::hasTable('inv_stock_movements')) {
            Schema::table('inv_stock_movements', function (Blueprint $table) {
                if (Schema::hasColumn('inv_stock_movements', 'type') && Schema::hasColumn('inv_stock_movements', 'created_at') && !$this->indexExists('inv_stock_movements', 'inv_stock_movements_type_created_idx')) {
                    $table->index(['type', 'created_at'], 'inv_stock_movements_type_created_idx');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table) {
                if ($this->indexExists('activity_log', 'activity_log_created_at_idx')) $table->dropIndex('activity_log_created_at_idx');
                if ($this->indexExists('activity_log', 'activity_log_log_created_idx')) $table->dropIndex('activity_log_log_created_idx');
            });
        }

        if (Schema::hasTable('whatsapp_messages')) {
            Schema::table('whatsapp_messages', function (Blueprint $table) {
                if ($this->indexExists('whatsapp_messages', 'whatsapp_messages_phone_created_idx')) $table->dropIndex('whatsapp_messages_phone_created_idx');
                if ($this->indexExists('whatsapp_messages', 'whatsapp_messages_module_phone_created_idx')) $table->dropIndex('whatsapp_messages_module_phone_created_idx');
            });
        }

        if (Schema::hasTable('delivery_orders')) {
            Schema::table('delivery_orders', function (Blueprint $table) {
                if ($this->indexExists('delivery_orders', 'delivery_orders_status_created_idx')) $table->dropIndex('delivery_orders_status_created_idx');
                if ($this->indexExists('delivery_orders', 'delivery_orders_delivery_date_idx')) $table->dropIndex('delivery_orders_delivery_date_idx');
            });
        }

        if (Schema::hasTable('journals')) {
            Schema::table('journals', function (Blueprint $table) {
                if ($this->indexExists('journals', 'journals_date_idx')) $table->dropIndex('journals_date_idx');
                if ($this->indexExists('journals', 'journals_status_date_idx')) $table->dropIndex('journals_status_date_idx');
            });
        }

        if (Schema::hasTable('journal_items')) {
            Schema::table('journal_items', function (Blueprint $table) {
                if ($this->indexExists('journal_items', 'journal_items_coa_created_idx')) $table->dropIndex('journal_items_coa_created_idx');
            });
        }

        if (Schema::hasTable('sales_orders')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                if ($this->indexExists('sales_orders', 'sales_orders_status_order_date_idx')) $table->dropIndex('sales_orders_status_order_date_idx');
            });
        }

        if (Schema::hasTable('inv_stock_movements')) {
            Schema::table('inv_stock_movements', function (Blueprint $table) {
                if ($this->indexExists('inv_stock_movements', 'inv_stock_movements_type_created_idx')) $table->dropIndex('inv_stock_movements_type_created_idx');
            });
        }
    }
};
