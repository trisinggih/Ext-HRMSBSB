import os
import re

router_file = 'src/router/index.js'
with open(router_file, 'r', encoding='utf-8') as f:
    router_content = f.read()

import_statement = "import EmployeeMutations from '../views/EmployeeMutations.vue'"
if import_statement not in router_content:
    router_content = router_content.replace(
        "import Employees from '../views/Employees.vue'",
        "import Employees from '../views/Employees.vue'\n" + import_statement
    )

route_definition = """
      {
        path: 'mutations',
        name: 'EmployeeMutations',
        component: EmployeeMutations
      },"""
if "path: 'mutations'" not in router_content:
    router_content = router_content.replace(
        "path: 'employees',",
        "path: 'employees'," + route_definition
    )

with open(router_file, 'w', encoding='utf-8') as f:
    f.write(router_content)

layout_file = 'src/layouts/DefaultLayout.vue'
with open(layout_file, 'r', encoding='utf-8') as f:
    layout_content = f.read()

menu_link = """<router-link to="/hrms/mutations" class="dropdown-item">Mutasi & Promosi</router-link>"""
if "/hrms/mutations" not in layout_content:
    layout_content = layout_content.replace(
        '<router-link to="/hrms/employees" class="dropdown-item">Karyawan</router-link>',
        '<router-link to="/hrms/employees" class="dropdown-item">Karyawan</router-link>\n                    ' + menu_link
    )

with open(layout_file, 'w', encoding='utf-8') as f:
    f.write(layout_content)

print("Web routes and navigation injected.")
