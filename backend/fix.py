import os

path = 'd:/Project/HRMS/backend/database/migrations/2026_08_28_130705_create_device_tokens_table.php'
with open(path, 'r') as f:
    content = f.read()

content = content.replace("$table->text('token')->index();", "$table->string('token', 255)->index();")

with open(path, 'w') as f:
    f.write(content)

