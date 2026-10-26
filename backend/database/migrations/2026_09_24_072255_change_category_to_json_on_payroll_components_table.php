<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new category JSON column
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->json('category_json')->nullable()->after('category');
        });

        // Migrate data
        $components = DB::table('payroll_components')->get();
        foreach ($components as $component) {
            $mergedCategories = [];
            
            // Add existing category
            if ($component->category) {
                if (!in_array($component->category, $mergedCategories)) {
                    $mergedCategories[] = $component->category;
                }
            }

            // Merge cycle_types
            if (isset($component->cycle_types) && $component->cycle_types) {
                $cycles = json_decode($component->cycle_types, true);
                if (is_array($cycles)) {
                    foreach ($cycles as $c) {
                        if (!in_array($c, $mergedCategories)) {
                            $mergedCategories[] = $c;
                        }
                    }
                }
            }
            
            // If empty for some reason, default to monthly
            if (empty($mergedCategories)) {
                $mergedCategories = ['monthly'];
            }

            DB::table('payroll_components')
                ->where('id', $component->id)
                ->update(['category_json' => json_encode($mergedCategories)]);
        }

        // Drop old category and cycle_types columns, rename new column
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->dropColumn('category');
            $table->dropColumn('cycle_types');
        });

        Schema::table('payroll_components', function (Blueprint $table) {
            $table->renameColumn('category_json', 'category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->enum('category_old', ['monthly', 'annual', 'loan', 'daily', 'weekly'])->default('monthly');
            $table->json('cycle_types')->nullable();
        });

        $components = DB::table('payroll_components')->get();
        foreach ($components as $component) {
            $catStr = 'monthly';
            $cycles = [];
            if ($component->category) {
                $categories = json_decode($component->category, true);
                if (is_array($categories)) {
                    if (in_array('annual', $categories)) $catStr = 'annual';
                    elseif (in_array('loan', $categories)) $catStr = 'loan';
                    elseif (in_array('daily', $categories)) $catStr = 'daily';
                    elseif (in_array('weekly', $categories)) $catStr = 'weekly';
                    else $catStr = 'monthly';
                    
                    if (in_array('monthly', $categories)) $cycles[] = 'monthly';
                    if (in_array('weekly', $categories)) $cycles[] = 'weekly';
                    if (in_array('daily', $categories)) $cycles[] = 'daily';
                }
            }
            
            DB::table('payroll_components')
                ->where('id', $component->id)
                ->update([
                    'category_old' => $catStr,
                    'cycle_types' => json_encode($cycles)
                ]);
        }

        Schema::table('payroll_components', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('payroll_components', function (Blueprint $table) {
            $table->renameColumn('category_old', 'category');
        });
    }
};
