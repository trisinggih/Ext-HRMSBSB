<template>
  <div>
    <!-- Dashboard Header -->
    <div class="row mb-4 align-items-center">
      <div class="col-sm-6">
        <h4 class="mb-0 font-size-18">Dashboard RMS (Rekrutmen)</h4>
        <p class="text-muted mt-1 mb-0">Ringkasan aktivitas rekrutmen dan kandidat.</p>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
      <div class="col-xl-3 col-md-6" v-for="c in summaryCards" :key="c.label">
        <div class="card mini-stats-wid">
          <div class="card-body">
            <div class="d-flex">
              <div class="flex-grow-1">
                <p class="text-muted fw-medium">{{ c.label }}</p>
                <h4 class="mb-0">{{ c.value }}</h4>
              </div>
              <div class="avatar-sm rounded-circle bg-primary align-self-center mini-stat-icon">
                <span class="avatar-title rounded-circle bg-primary">
                  <i :class="c.icon + ' font-size-24'"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-xl-8">
        <div class="card">
          <div class="card-body">
            <h4 class="card-title mb-4">Tren Lamaran Masuk (Bulan Ini)</h4>
            <div class="text-center text-muted">
              <apexchart type="bar" height="350" :options="applicationChartOptions" :series="applicationChartSeries"></apexchart>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="card">
          <div class="card-body">
            <h4 class="card-title mb-4">Lowongan Aktif</h4>
            <div class="table-responsive">
              <table class="table table-borderless table-nowrap table-centered mb-0 align-middle">
                <tbody>
                  <tr v-for="job in activeJobs" :key="job.title">
                    <td>
                      <h5 class="font-size-14 m-0"><a href="#" class="text-dark">{{ job.title }}</a></h5>
                      <span class="text-muted font-size-12">{{ job.department }}</span>
                    </td>
                    <td>
                      <div class="text-end">
                        <span class="badge bg-subtle-success text-success font-size-12">{{ job.applicants }} Lamaran</span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import VueApexCharts from 'vue3-apexcharts'
export default {
  components: {
    apexchart: VueApexCharts
  }
}
</script>

<script setup>
import { ref } from 'vue'

const summaryCards = ref([
  { label: 'Lowongan Aktif', value: '0', icon: 'bx bx-briefcase' },
  { label: 'Total Kandidat', value: '0', icon: 'bx bx-user-circle' },
  { label: 'Lamaran Baru', value: '0', icon: 'bx bx-file-blank' },
  { label: 'Menunggu Interview', value: '0', icon: 'bx bx-calendar-event' },
])

const applicationChartOptions = ref({
  chart: { type: 'bar', toolbar: { show: false } },
  colors: ['#0f9cf3', '#f32f53'],
  plotOptions: { bar: { columnWidth: '40%', borderRadius: 4 } },
  dataLabels: { enabled: false },
  stroke: { show: true, width: 2, colors: ['transparent'] },
  xaxis: { categories: ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'] },
  fill: { opacity: 1 },
})
const applicationChartSeries = ref([
  { name: 'Lamaran Masuk', data: [0, 0, 0, 0] },
  { name: 'Ditolak Awal', data: [0, 0, 0, 0] }
])

const activeJobs = ref([])

// TODO: Load data dari endpoint saat onMounted()
</script>

<style scoped>
.mini-stat-icon {
  width: 3rem;
  height: 3rem;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
