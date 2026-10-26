<template>
  <div class="max-w-3xl mx-auto px-4 py-10">
    <div v-if="!started">
      <!-- Intro -->
      <button @click="$router.back()" class="text-sm text-blue-600 hover:text-blue-800 mb-4">← Kembali</button>
      <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 my-4">{{ error }}</div>

      <div v-if="test" class="bg-white border rounded-xl p-8 shadow-sm text-center">
        <span class="inline-block bg-[#1e2a3a] text-white text-xs font-semibold px-3 py-1 rounded-full mb-4">Tes Online</span>
        <h1 class="text-2xl font-bold text-slate-800">{{ test.name }}</h1>
        <p v-if="test.description" class="text-slate-500 mt-2">{{ test.description }}</p>
        <div class="mt-4 flex justify-center gap-3 text-sm text-slate-600">
          <span class="px-3 py-1 bg-slate-100 rounded-lg">Durasi: {{ test.duration_minutes || 15 }} menit</span>
          <span class="px-3 py-1 bg-slate-100 rounded-lg">{{ questions.length }} soal</span>
          <span class="px-3 py-1 bg-slate-100 rounded-lg">Passing: {{ test.passing_score }}%</span>
        </div>
        <div class="mt-6 max-w-sm mx-auto">
          <label class="block text-sm font-medium text-slate-600 mb-1 text-left">ID Kandidat</label>
          <input v-model="submitForm.candidate_id" type="number" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Masukkan ID kandidat Anda" />
        </div>
        <button @click="start" :disabled="!submitForm.candidate_id" class="mt-4 px-6 py-2.5 bg-[#1e2a3a] hover:bg-[#2a3b4f] text-white rounded-lg text-sm font-medium disabled:opacity-50">
          Mulai Tes
        </button>
      </div>
    </div>

    <template v-else>
      <!-- Soal -->
      <div class="sticky top-0 bg-slate-50 z-30 py-3 flex items-center justify-between">
        <div>
          <h1 class="text-lg font-bold text-slate-800">{{ test?.name }}</h1>
          <p class="text-xs text-slate-500">Jawab semua soal lalu submit.</p>
        </div>
        <div class="text-sm font-semibold text-slate-700">Soal {{ answeredCount }}/{{ questions.length }}</div>
      </div>

      <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 my-4">{{ error }}</div>

      <div v-for="(q, idx) in questions" :key="q.id" class="bg-white border rounded-xl p-6 shadow-sm mt-4">
        <p class="font-medium text-slate-800"><span class="text-slate-400 mr-2">{{ idx + 1 }}.</span>{{ q.question }}</p>
        <div class="mt-3 space-y-2">
          <label v-for="opt in ['A','B','C','D']" :key="opt"
            class="flex items-center gap-3 border rounded-lg p-3 cursor-pointer text-sm transition"
            :class="answers[q.id] === opt ? 'border-[#4cc2ff] bg-[#4cc2ff]/10 text-slate-800' : 'border-slate-200 hover:border-slate-300 text-slate-600'">
            <input type="radio" :name="'q'+q.id" :value="opt" v-model="answers[q.id]" class="accent-[#4cc2ff]" />
            {{ opt }}. {{ q[`option_${opt.toLowerCase()}`] }}
          </label>
        </div>
      </div>

      <div class="mt-6 flex justify-between">
        <button @click="started = false" class="px-4 py-2 border rounded-lg text-sm text-slate-600">← Batal</button>
        <button @click="submit" :disabled="submitting" class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium disabled:opacity-50">
          {{ submitting ? 'Mengirim...' : 'Submit Tes' }}
        </button>
      </div>
    </template>

    <!-- Result -->
    <div v-if="result" class="mt-8 bg-white border rounded-xl p-8 shadow-sm text-center">
      <div class="text-5xl mb-4">{{ result.passed ? '🎉' : '😔' }}</div>
      <h2 class="text-2xl font-bold text-slate-800">Skor Anda: {{ result.score }}%</h2>
      <p class="text-slate-500 mt-2">Benar {{ result.correct_count }} dari {{ result.total_questions }} soal. Passing score: {{ result.passing_score }}%.</p>
      <div class="mt-3">
        <span class="px-4 py-2 text-sm rounded-full font-semibold" :class="result.passed ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
          {{ result.passed ? 'LULUS' : 'BELUM LULUS' }}
        </span>
      </div>
      <a :href="'/karir/status'" class="mt-6 inline-block text-[#4cc2ff] font-semibold text-sm">Cek Status Lamaran →</a>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../../api'

const route = useRoute()
const test = ref(null)
const questions = ref([])
const answers = ref({})
const started = ref(false)
const submitting = ref(false)
const error = ref('')
const result = ref(null)
const submitForm = ref({ candidate_id: route.query.candidate_id || localStorage.getItem('karir_candidate') || '', email: localStorage.getItem('karir_email') || '' })

const answeredCount = computed(() => Object.values(answers.value).filter(a => a).length)

onMounted(async () => {
  try {
    const { data } = await api.get(`/recruitment/tests/${route.params.id}`)
    test.value = data.data.test
    // normalize questions: bisa array atau object
    questions.value = Array.isArray(data.data.questions)
      ? data.data.questions
      : (data.data.questions?.data || [])
  } catch (e) { error.value = e.response?.data?.message || 'Test tidak ditemukan' }
})

const start = () => {
  if (!submitForm.value.candidate_id && !submitForm.value.email) { error.value = 'Masukkan ID kandidat atau email'; return }
  started.value = true
}

const submit = async () => {
  submitting.value = true; error.value = ''
  const payload = {
    candidate_id: submitForm.value.candidate_id || undefined,
    email: submitForm.value.email || undefined,
    answers: Object.entries(answers.value).filter(([, v]) => v).map(([qid, chosen]) => ({ question_id: Number(qid), chosen })),
  }
  try {
    const { data } = await api.post(`/recruitment/tests/${route.params.id}/submit`, payload)
    result.value = data.data
  } catch (e) {
    error.value = e.response?.data?.errors ? Object.values(e.response.data.errors)[0][0] : (e.response?.data?.message || 'Gagal submit')
  } finally { submitting.value = false }
}
</script>
