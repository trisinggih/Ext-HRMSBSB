<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="close"></div>
    
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="relative bg-white dark:bg-slate-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-3xl w-full border border-slate-200 dark:border-slate-700">
        
        <!-- Header -->
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/20">
          <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-100">Buat Surat Mutasi / Promosi</h3>
          <button @click="close" class="text-slate-400 hover:text-slate-500">
            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
              <path d="M11.414 10l4.293-4.293a1 1 0 00-1.414-1.414L10 8.586 5.707 4.293a1 1 0 00-1.414 1.414L8.586 10l-4.293 4.293a1 1 0 101.414 1.414L10 11.414l4.293 4.293a1 1 0 001.414-1.414L11.414 10z" />
            </svg>
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm">
          <div class="px-5 py-4 overflow-y-auto max-h-[70vh]">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <!-- Employee -->
              <div>
                <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-slate-300">Pilih Karyawan <span class="text-rose-500">*</span></label>
                <select v-model="form.employee_id" class="form-select w-full" required>
                  <option value="">Pilih Karyawan</option>
                  <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                    {{ emp.first_name }} {{ emp.last_name }} ({{ emp.employee_id }})
                  </option>
                </select>
              </div>

              <!-- Type -->
              <div>
                <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-slate-300">Tipe <span class="text-rose-500">*</span></label>
                <select v-model="form.type" class="form-select w-full" required>
                  <option value="mutasi">Mutasi (Transfer)</option>
                  <option value="promosi">Promosi (Kenaikan Jabatan)</option>
                </select>
              </div>
            </div>

            <!-- New Assignment Details -->
            <h4 class="font-semibold text-slate-800 dark:text-slate-100 mb-3 mt-6 border-b pb-2">Tujuan Mutasi / Jabatan Baru</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-sm font-medium mb-1">Perusahaan <span class="text-rose-500">*</span></label>
                <select v-model="form.new_company_id" class="form-select w-full" required>
                  <option value="">Pilih Perusahaan</option>
                  <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium mb-1">Cabang</label>
                <select v-model="form.new_branch_id" class="form-select w-full">
                  <option value="">(Tetap sama / Pilih Baru)</option>
                  <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Departemen <span class="text-rose-500">*</span></label>
                <select v-model="form.new_department_id" class="form-select w-full" required>
                  <option value="">Pilih Departemen</option>
                  <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Divisi</label>
                <select v-model="form.new_division_id" class="form-select w-full">
                  <option value="">(Tetap sama / Pilih Baru)</option>
                  <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Jabatan Fungsional <span class="text-rose-500">*</span></label>
                <select v-model="form.new_position_id" class="form-select w-full" required>
                  <option value="">Pilih Jabatan</option>
                  <option v-for="p in positions" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Tanggal Efektif <span class="text-rose-500">*</span></label>
                <input type="date" v-model="form.effective_date" class="form-input w-full" required />
              </div>
            </div>

            <!-- Approvers Selection -->
            <h4 class="font-semibold text-slate-800 dark:text-slate-100 mb-3 mt-6 border-b pb-2">Pengaturan Approval (Persetujuan)</h4>
            <div class="bg-indigo-50 dark:bg-indigo-500/10 p-3 rounded text-sm mb-4">
              Tentukan siapa saja yang harus menyetujui mutasi ini. Approver ke-1 wajib, dan Anda dapat menambahkan hingga 5 approver.
            </div>
            
            <div class="space-y-3">
              <div v-for="(approver, index) in form.approvers" :key="index" class="flex items-center space-x-2">
                <span class="text-sm font-medium w-24">Approver {{ index + 1 }} <span v-if="index === 0" class="text-rose-500">*</span></span>
                <select v-model="form.approvers[index]" class="form-select flex-1" :required="index === 0">
                  <option value="">Pilih Approver</option>
                  <!-- Assuming approvers are employees/users -->
                  <option v-for="emp in employees" :key="emp.id" :value="emp.user_id">
                    {{ emp.first_name }} {{ emp.last_name }} ({{ emp.employee_id }})
                  </option>
                </select>
                <button type="button" @click="removeApprover(index)" v-if="index > 0" class="text-rose-500 hover:text-rose-600 p-2">
                  <svg class="w-4 h-4 fill-current" viewBox="0 0 16 16">
                    <path d="M5 7h6v2H5zM12 1H4C2.9 1 2 1.9 2 3v10c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zM4 3h8v10H4V3z" />
                  </svg>
                </button>
              </div>
              <button 
                type="button" 
                @click="addApprover" 
                v-if="form.approvers.length < 5"
                class="btn-sm bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 text-indigo-500 mt-2"
              >
                + Tambah Approver
              </button>
            </div>

            <!-- Notes -->
            <div class="mt-4">
              <label class="block text-sm font-medium mb-1">Catatan</label>
              <textarea v-model="form.reason" rows="3" class="form-input w-full"></textarea>
            </div>

          </div>
          
          <!-- Footer -->
          <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-700 flex flex-wrap justify-end space-x-2 bg-slate-50 dark:bg-slate-800/20">
            <button type="button" class="btn-sm border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 text-slate-600 dark:text-slate-300" @click="close">Batal</button>
            <button type="submit" class="btn-sm bg-indigo-500 hover:bg-indigo-600 text-white" :disabled="loading">
              {{ loading ? 'Menyimpan...' : 'Simpan & Ajukan' }}
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, watch } from 'vue'
import axios from 'axios'

export default {
  name: 'MutationFormModal',
  props: {
    isOpen: { type: Boolean, default: false }
  },
  emits: ['close', 'success'],
  setup(props, { emit }) {
    const loading = ref(false)
    const employees = ref([])
    const companies = ref([])
    const branches = ref([])
    const departments = ref([])
    const divisions = ref([])
    const positions = ref([])

    const form = reactive({
      employee_id: '',
      type: 'mutasi',
      new_company_id: '',
      new_branch_id: '',
      new_department_id: '',
      new_division_id: '',
      new_position_id: '',
      effective_date: '',
      reason: '',
      approvers: [''] // 1 mandatory
    })

    const close = () => {
      emit('close')
    }

    const addApprover = () => {
      if (form.approvers.length < 5) {
        form.approvers.push('')
      }
    }

    const removeApprover = (index) => {
      form.approvers.splice(index, 1)
    }

    const fetchDropdowns = async () => {
      try {
        const [emp, comp, br, dep, div, pos] = await Promise.all([
          axios.get('/api/employees'), // using employee list
          axios.get('/api/companies'),
          axios.get('/api/branches'),
          axios.get('/api/departments'),
          axios.get('/api/divisions'),
          axios.get('/api/positions'),
        ])
        employees.value = emp.data.data || []
        companies.value = comp.data.data || []
        branches.value = br.data.data || []
        departments.value = dep.data.data || []
        divisions.value = div.data.data || []
        positions.value = pos.data.data || []
      } catch (e) {
        console.error("Error loading dropdowns", e)
      }
    }

    const submitForm = async () => {
      // Validate approvers
      const validApprovers = form.approvers.filter(a => a !== '')
      if (validApprovers.length === 0) {
        alert("Pilih setidaknya 1 approver wajib!")
        return
      }

      loading.value = true
      try {
        const payload = {
          ...form,
          approvers: validApprovers
        }
        await axios.post('/api/employee-mutations', payload)
        
        // Reset form
        form.employee_id = ''
        form.type = 'mutasi'
        form.new_company_id = ''
        form.new_branch_id = ''
        form.new_department_id = ''
        form.new_division_id = ''
        form.new_position_id = ''
        form.effective_date = ''
        form.reason = ''
        form.approvers = ['']

        emit('success')
        close()
      } catch (e) {
        alert("Terjadi kesalahan: " + (e.response?.data?.message || e.message))
      } finally {
        loading.value = false
      }
    }

    watch(() => props.isOpen, (newVal) => {
      if (newVal && employees.value.length === 0) {
        fetchDropdowns()
      }
    })

    return {
      form,
      loading,
      employees, companies, branches, departments, divisions, positions,
      close,
      addApprover,
      removeApprover,
      submitForm
    }
  }
}
</script>
