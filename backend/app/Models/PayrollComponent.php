<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollComponent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'description', 'type', 'calculation_source', 'calculation_method',
        'default_value', 'formula', 'is_bpjs_basis', 'show_on_payslip', 'category', 'payment_schema',
        'sort_order', 'is_active', 'is_taxable', 'effective_from', 'has_rules'
    ];

    protected $casts = [
        'default_value' => 'float',
        'is_bpjs_basis' => 'boolean',
        'show_on_payslip' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_separate_payslip' => 'boolean',
        'is_taxable' => 'boolean',
        'effective_from' => 'date',
        'has_rules' => 'boolean',
        'payment_schema' => 'array',
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(PayrollRule::class, 'payroll_component_id');
    }

    public function salaryComponents(): HasMany
    {
        return $this->hasMany(EmployeeSalaryComponent::class, 'payroll_component_id');
    }

    public function taxRules()
    {
        return $this->belongsToMany(TaxRule::class, 'tax_rule_components');
    }
}
