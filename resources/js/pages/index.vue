<template>
  <div>
    <VCard class="mb-6">
      <VCardText>Home > Dashboard</VCardText>
    </VCard>

    <VRow class="match-height mb-6">
      <VCol
        cols="12"
        md="6"
        lg="3"
      >
        <VCard>
          <VRow class="mt-1">
            <VCol>
              <div class="px-4 pb-2">
                Total PayIn
              </div>
              <div class="px-4 pb-2">
                {{ amountParse(data.TOTAL_BALANCE??0) }}
              </div>
            </VCol>
            <VCol>
              <div class="px-4 pb-2">
                Total PayOut
              </div>
              <div class="px-4 pb-2">
                {{ amountParse(data.TODAY_PAYOUT??0) }}
              </div>
            </VCol>
          </VRow>
          <hr class="hr-line">
          <VRow class="mt-1">
            <VCol>
              <div class="px-4 pb-2">
                Settled Balance
              </div>
              <div class="px-4 pb-2">
                {{ amountParse(data.SETTLED_BALANCE??0) }}
              </div>
            </VCol>
            <VCol>
              <div class="px-4 pb-2">
                Available Balance
              </div>
              <div class="px-4 pb-2">
                {{ amountParse(data.REMAINING_BALANCE??0) }}
              </div>
            </VCol>
          </VRow>
          <hr class="hr-line">
          <VRow class="mt-1">
            <VCol>
              <div class="px-4 pb-2">
                Unsettled Balance
              </div>
              <div class="px-4 pb-2">
                {{ amountParse(data.UNSETTLED_BALANCE??0) }}
              </div>
            </VCol>
            <VCol>
              <div class="px-4 pb-2">
                Total Load Balance
              </div>
              <div class="px-4 pb-2">
                {{ amountParse(data.LOAD_BALANCE??0) }}
              </div>
            </VCol>
          </VRow>
        </VCard>
      </VCol>
      <VCol
        cols="12"
        md="6"
        lg="3"
      >
        <VCard>
          <div class="px-3 mt-1 pb-2">
            <VCol>
              Today's Success Tnx (Nos.)
            </VCol>
            <VCol>
              {{ data.TODAY_SUCCESS_TXN??0 }}
            </VCol>
          </div>
          <hr class="hr-line">
          <div class="px-3 mt-1 pb-2">
            <VCol>
              Today's Success PayIn Amount
            </VCol>
            <VCol>
              {{ amountParse(data.TODAY_SUCCESS_TXN_AMOUNT??0) }}
            </VCol>
          </div>
        </VCard>
      </VCol>
      <VCol
        cols="12"
        md="6"
        lg="3"
      >
        <VCard>
          <div class="px-3 mt-1 pb-2">
            <VCol>
              Today's PayOut Tnx (Nos.)
            </VCol>
            <VCol>
              {{ data.TODAY_PAYOUT??0 }}
            </VCol>
          </div>
          <hr class="hr-line">
          <div class="px-3 mt-1 pb-2">
            <VCol>
              Today's PayOut Amount
            </VCol>
            <VCol>
              {{ amountParse(data.TODAY_PAYOUT_AMOUNT??0) }}
            </VCol>
          </div>
        </VCard>
      </VCol>
      <VCol
        cols="12"
        md="6"
        lg="3"
      >
        <VCard>
          <div class="px-3 mt-1 pb-2">
            <VCol>
              Pending PayOut
            </VCol>
            <VCol>
              {{ data.PENDING_PAYOUT??0 }}
            </VCol>
          </div>
          <hr class="hr-line">
          <div class="px-3 mt-1 pb-2">
            <VCol>
              Pending PayOut Amount
            </VCol>
            <VCol>
              {{ amountParse(data.PENDING_PAYOUT_AMOUNT??0) }}
            </VCol>
          </div>
        </VCard>
      </VCol>
    </VRow>

    <VCard title="PAYIN & PAYOUT Analytics">
      <template #append>
        <div class="date-picker-wrapper">
          <AppDateTimePicker
            v-model="dateRange"
            prepend-inner-icon="tabler-calendar"
            :config="{ mode: 'range', disable:[{from:tomorrow, to:'2100-1-1'}] }"
          />
        </div>
      </template>
      <VCardText>
        <ChartJsLineChart :cdata="chartdata" />
      </VCardText>
    </VCard>
  </div>
</template>

<script setup>
import ChartJsLineChart from '@/views/charts/ChartJsLineChart.vue'
</script>

<script>
import axios from '@axios'
import { amountParse } from '@core/utils/DpzHelper'

export default {
  data() {
    return {
      data: {},
      chartdata: {},
      dateRange: [],
      tomorrow: '',
      beforeAMonth: '',
    }
  },
  watch: {
    dateRange(val) {
      if(typeof val === 'string'){
        const dataVals = val.split('to')

        this.beforeAMonth = dataVals[0]
        this.tomorrow = dataVals[1]
        this.getData()
      }
    },
  },
  mounted() {
    const today = new Date()
    let tw = new Date(today)
    tw.setDate(today.getDate()+1)
    tw.toLocaleDateString()

    let beforeAMonth = new Date(today)
    beforeAMonth.setDate(today.getDate()-30)
    beforeAMonth.toLocaleDateString()

    this.tomorrow = `${tw.getUTCFullYear()}-${tw.getMonth() + 1}-${tw.getDate()}`
    this.beforeAMonth = `${beforeAMonth.getUTCFullYear()}-${beforeAMonth.getMonth() + 1}-${beforeAMonth.getDate()}`
    this.dateRange = [this.tomorrow, this.beforeAMonth]
  },
  methods: {
    getData(){
      axios.get('/api/dashboard/summary')
        .then(res=>{
          this.data = res.data.data
        })
    
      axios.post('/api/dashboard/chart/summary', {
        start_date: this.beforeAMonth,
        end_date: this.tomorrow,
      })
        .then(res=>{
          this.chartdata = res.data.data
        })
    },
  },
}
</script>

<style lang="scss">
.date-picker-wrapper {
  inline-size: 15.2rem;
}

.hr-line {
  border: 1px solid #f1f1f1;
}
</style>
