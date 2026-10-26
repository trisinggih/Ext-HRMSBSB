import os
import re

file_path = 'app/Services/ApprovalEngineService.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

model_map_insert = """            'loan_request'=> \App\Models\LoanRequest::class,
            'employee_mutations' => \App\Models\EmployeeMutation::class,"""
content = content.replace("'loan_request'=> \App\Models\LoanRequest::class,", model_map_insert)

mutation_logic = """
            if ($module === 'employee_mutations' && $record instanceof \App\Models\EmployeeMutation && $status === 'approved') {
                $employee = \App\Models\Employee::with('assignments')->find($record->employee_id);
                if ($employee) {
                    $primary = $employee->primaryAssignment() ?? $employee->assignments->first();
                    if ($primary) {
                        $primary->update([
                            'company_id' => $record->new_company_id,
                            'branch_id' => $record->new_branch_id,
                            'department_id' => $record->new_department_id,
                            'division_id' => $record->new_division_id,
                            'position_id' => $record->new_position_id,
                            'supervisor_id' => $record->new_supervisor_id,
                        ]);
                    }
                }
            }
"""

if "'employee_mutations'" not in content:
    # Let's insert it inside the DB::transaction callback
    # Find LoanRequest block and insert after
    insert_point = "if ($module === 'loan_request' && $record instanceof \App\Models\LoanRequest && $status === 'approved') {"
    parts = content.split(insert_point)
    
    if len(parts) == 2:
        idx = parts[1].find('}') # end of LoanRequest block
        idx2 = parts[1].find('}', idx+1) # end of EmployeeLoan::create
        idx3 = parts[1].find('}', idx2+1) # end of inner if block
        # we'll just use simple regex or replace
        pass

# I'll just use replace for the loan_request block
old_loan_block = """            if ($module === 'loan_request' && $record instanceof \App\Models\LoanRequest && $status === 'approved') {
                \App\Models\EmployeeLoan::create([
                    'employee_id' => $record->employee_id,
                    'total_loan' => $record->amount,
                    'amount' => $record->amount,
                    'tenor' => $record->tenor,
                    'interest_rate' => $record->interest_rate ?? 0,
                    'installment_amount' => $record->installment_amount,
                    'status' => 'active',
                    'disbursed_date' => now()->toDateString(),
                ]);
            }"""

if mutation_logic not in content:
    content = content.replace(old_loan_block, old_loan_block + "\n" + mutation_logic)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("ApprovalEngineService updated.")
