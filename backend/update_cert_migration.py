import os
import glob
import re

migration_files = glob.glob('database/migrations/*_create_employee_certifications_table.php')
if not migration_files:
    print("Migration file not found")
    exit(1)

migration_file = migration_files[0]
with open(migration_file, 'r') as f:
    content = f.read()

new_fields = """
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('issuer')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('credential_id')->nullable();
            $table->string('credential_url')->nullable();
            $table->text('description')->nullable();
"""

content = re.sub(
    r"(\$table->id\(\);)",
    r"\1" + new_fields,
    content
)

with open(migration_file, 'w') as f:
    f.write(content)

print("Migration updated.")
