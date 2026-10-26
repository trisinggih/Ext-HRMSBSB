<template>
  <div class="payslip-container" ref="payslipContent">
    <!-- Header -->
    <div class="header">
      <div class="company-info">
        <h2 class="company-name">PT. JANGEBY</h2>
        <p class="company-address">Slip Gaji Karyawan</p>
      </div>
      <div class="slip-title">
        <h1>SLIP GAJI</h1>
        <p class="period">Periode: {{ currentMonthYear }}</p>
      </div>
    </div>

    <!-- Employee Info -->
    <div class="employee-info">
      <table class="info-table">
        <tr>
          <td class="label">Nama</td>
          <td class="colon">:</td>
          <td class="value"><strong>{{ employee?.name || '-' }}</strong></td>
          <td class="label">NIK</td>
          <td class="colon">:</td>
          <td class="value">{{ employee?.employee_id || '-' }}</td>
        </tr>
        <tr>
          <td class="label">Jabatan</td>
          <td class="colon">:</td>
          <td class="value">{{ employee?.designation?.name || '-' }}</td>
          <td class="label">Departemen</td>
          <td class="colon">:</td>
          <td class="value">{{ employee?.department?.name || '-' }}</td>
        </tr>
      </table>
    </div>

    <!-- Salary Details -->
    <div class="salary-details">
      <!-- Earnings Column -->
      <div class="details-col">
        <h3 class="section-title">PENERIMAAN</h3>
        <table class="details-table">
          <tr v-for="(item, index) in earnings" :key="'earning-'+index">
            <td>{{ item.label }}</td>
            <td class="amount">{{ formatRupiah(item.amount) }}</td>
          </tr>
          <tr v-if="simulationData?.overtime?.amount > 0">
            <td>Lembur</td>
            <td class="amount">{{ formatRupiah(simulationData.overtime.amount) }}</td>
          </tr>
          <tr v-for="(reward, index) in simulationData?.rewards" :key="'reward-'+index">
            <td>{{ reward.title || 'Bonus/Reward' }}</td>
            <td class="amount">{{ formatRupiah(reward.amount) }}</td>
          </tr>
          <tr class="total-row">
            <td><strong>Total Penerimaan</strong></td>
            <td class="amount"><strong>{{ formatRupiah(totalEarnings) }}</strong></td>
          </tr>
        </table>
      </div>

      <!-- Deductions Column -->
      <div class="details-col">
        <h3 class="section-title">POTONGAN</h3>
        <table class="details-table">
          <tr v-for="(item, index) in deductions" :key="'deduction-'+index">
            <td>{{ item.label }}</td>
            <td class="amount">{{ formatRupiah(item.amount) }}</td>
          </tr>
          <tr v-if="simulationData?.daily_rules?.late_deduction > 0">
            <td>Potongan Keterlambatan</td>
            <td class="amount">{{ formatRupiah(simulationData.daily_rules.late_deduction) }}</td>
          </tr>
          <tr v-if="simulationData?.daily_rules?.absent_deduction > 0">
            <td>Potongan Mangkir/Absen</td>
            <td class="amount">{{ formatRupiah(simulationData.daily_rules.absent_deduction) }}</td>
          </tr>
          <tr v-for="(deduction, index) in simulationData?.deductions" :key="'misc-deduction-'+index">
            <td>{{ deduction.title || 'Potongan Lain-Lain' }}</td>
            <td class="amount">{{ formatRupiah(deduction.amount) }}</td>
          </tr>
          <tr class="total-row">
            <td><strong>Total Potongan</strong></td>
            <td class="amount"><strong>{{ formatRupiah(totalDeductions) }}</strong></td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Take Home Pay -->
    <div class="take-home-pay">
      <div class="thp-label">TAKE HOME PAY (PENERIMAAN BERSIH)</div>
      <div class="thp-amount">{{ formatRupiah(simulationData?.net_salary || 0) }}</div>
    </div>

    <!-- Signatures -->
    <div class="signatures">
      <div class="signature-box">
        <p>Penerima,</p>
        <div class="sign-space"></div>
        <p class="sign-name">( {{ employee?.name || '-' }} )</p>
      </div>
      <div class="signature-box">
        <p>Disetujui Oleh,</p>
        <div class="sign-space"></div>
        <p class="sign-name">( HRD Manager )</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  employee: Object,
  simulationData: Object
});

const currentMonthYear = computed(() => {
  const date = new Date();
  const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  return `${months[date.getMonth()]} ${date.getFullYear()}`;
});

const earnings = computed(() => {
  if (!props.simulationData || !props.simulationData._details) return [];
  return props.simulationData._details.filter(d => d.type === 'earning');
});

const deductions = computed(() => {
  if (!props.simulationData || !props.simulationData._details) return [];
  return props.simulationData._details.filter(d => d.type === 'deduction' || d.type === 'bpjs' || d.type === 'tax');
});

const totalEarnings = computed(() => {
  if (!props.simulationData) return 0;
  let total = earnings.value.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0);
  if (props.simulationData.overtime?.amount) total += parseFloat(props.simulationData.overtime.amount);
  if (props.simulationData.rewards?.length) {
    total += props.simulationData.rewards.reduce((sum, r) => sum + (parseFloat(r.amount) || 0), 0);
  }
  return total;
});

const totalDeductions = computed(() => {
  if (!props.simulationData) return 0;
  let total = deductions.value.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0);
  if (props.simulationData.daily_rules?.late_deduction) total += parseFloat(props.simulationData.daily_rules.late_deduction);
  if (props.simulationData.daily_rules?.absent_deduction) total += parseFloat(props.simulationData.daily_rules.absent_deduction);
  if (props.simulationData.deductions?.length) {
    total += props.simulationData.deductions.reduce((sum, d) => sum + (parseFloat(d.amount) || 0), 0);
  }
  return total;
});

const formatRupiah = (value) => {
  if (!value) return 'Rp 0';
  return 'Rp ' + Number(value).toLocaleString('id-ID');
};
</script>

<style scoped>
.payslip-container {
  width: 100%;
  max-width: 800px;
  background-color: #ffffff;
  padding: 40px;
  font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
  color: #333333;
}

.header {
  display: flex;
  justify-content: space-between;
  border-bottom: 2px solid #2c3e50;
  padding-bottom: 15px;
  margin-bottom: 25px;
}

.company-name {
  margin: 0;
  color: #2c3e50;
  font-size: 24px;
  font-weight: bold;
}

.company-address {
  margin: 5px 0 0 0;
  color: #7f8c8d;
  font-size: 14px;
}

.slip-title {
  text-align: right;
}

.slip-title h1 {
  margin: 0;
  color: #2c3e50;
  font-size: 28px;
  letter-spacing: 2px;
}

.period {
  margin: 5px 0 0 0;
  font-size: 14px;
  font-weight: bold;
}

.employee-info {
  margin-bottom: 30px;
  background-color: #f8f9fa;
  padding: 15px;
  border-radius: 5px;
}

.info-table {
  width: 100%;
  border-collapse: collapse;
}

.info-table td {
  padding: 5px;
  font-size: 14px;
}

.info-table .label {
  width: 15%;
  color: #7f8c8d;
}

.info-table .colon {
  width: 2%;
}

.info-table .value {
  width: 33%;
}

.salary-details {
  display: flex;
  justify-content: space-between;
  gap: 30px;
  margin-bottom: 30px;
}

.details-col {
  width: 48%;
}

.section-title {
  font-size: 16px;
  color: #2c3e50;
  border-bottom: 1px solid #bdc3c7;
  padding-bottom: 8px;
  margin-bottom: 15px;
}

.details-table {
  width: 100%;
  border-collapse: collapse;
}

.details-table td {
  padding: 8px 0;
  font-size: 14px;
  border-bottom: 1px dashed #ecf0f1;
}

.details-table .amount {
  text-align: right;
}

.total-row td {
  padding-top: 15px;
  border-bottom: none;
  font-size: 15px;
}

.take-home-pay {
  background-color: #2c3e50;
  color: white;
  padding: 15px 20px;
  border-radius: 5px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 40px;
}

.thp-label {
  font-size: 16px;
  font-weight: bold;
}

.thp-amount {
  font-size: 24px;
  font-weight: bold;
}

.signatures {
  display: flex;
  justify-content: space-between;
  padding: 0 40px;
}

.signature-box {
  text-align: center;
  width: 200px;
}

.signature-box p {
  margin: 0;
  font-size: 14px;
}

.sign-space {
  height: 80px;
}

.sign-name {
  font-weight: bold;
  border-bottom: 1px solid #333;
  padding-bottom: 2px;
  display: inline-block;
  min-width: 150px;
}
</style>
