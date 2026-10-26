<template>
  <div>
    <!-- Dashboard Header -->
    <div class="row mb-4 align-items-center">
      <div class="col-sm-6">
        <h4 class="mb-0 font-size-18">Dashboard CMS</h4>
        <p class="text-muted mt-1 mb-0">Kelola konten website perusahaan Anda.</p>
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
            <h4 class="card-title mb-4">Statistik Pengunjung Website (7 Hari Terakhir)</h4>
            <div class="text-center text-muted">
              <apexchart type="area" height="350" :options="visitorChartOptions" :series="visitorChartSeries"></apexchart>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="card">
          <div class="card-body">
            <h4 class="card-title mb-4">Aktivitas Terakhir</h4>
            <ul class="list-unstyled activity-widget mb-0" v-if="recentActivities.length > 0">
              <li class="activity-list activity-border" v-for="(act, index) in recentActivities" :key="index">
                <div class="activity-icon avatar-xs">
                  <span :class="['avatar-title', 'rounded-circle', act.iconBg, act.iconColor]">
                    <i :class="act.icon"></i>
                  </span>
                </div>
                <div class="timeline-list-item">
                  <div class="d-flex">
                    <div class="flex-grow-1">
                      <h5 class="font-size-14 mb-1">{{ act.title }}</h5>
                      <p class="text-muted mb-0">{{ act.desc }} - {{ act.time }}</p>
                    </div>
                  </div>
                </div>
              </li>
            </ul>
            <div v-else class="text-center text-muted py-4">
              <p class="mb-0">Belum ada aktivitas terbaru.</p>
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
  { label: 'Total Halaman', value: '0', icon: 'bx bx-file' },
  { label: 'Cluster Hunian', value: '3', icon: 'bx bx-home-alt' },
  { label: 'Total Media', value: '0', icon: 'bx bx-image-alt' },
  { label: 'Seksi Web', value: '0', icon: 'bx bx-layout' },
])

const recentActivities = ref([])

const visitorChartOptions = ref({
  chart: { type: 'area', toolbar: { show: false } },
  colors: ['#0f9cf3', '#6fd088'],
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2 },
  xaxis: { categories: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] },
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } }
})
const visitorChartSeries = ref([
  { name: 'Pengunjung Baru', data: [0, 0, 0, 0, 0, 0, 0] },
  { name: 'Kembali (Returning)', data: [0, 0, 0, 0, 0, 0, 0] }
])

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
.activity-widget .activity-list {
  position: relative;
  padding: 0 0 30px 30px;
}
.activity-widget .activity-list .activity-icon {
  position: absolute;
  left: 0;
  top: 0;
  z-index: 9;
}
.activity-widget .activity-list.activity-border:before {
  content: "";
  position: absolute;
  border-left: 2px dashed #e4e6ef;
  left: 14px;
  height: 100%;
  top: 0;
}
.activity-widget .activity-list:last-child {
  padding-bottom: 0;
}
.activity-widget .activity-list:last-child.activity-border:before {
  display: none;
}
</style>
