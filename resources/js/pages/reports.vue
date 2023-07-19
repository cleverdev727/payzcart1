<script setup>
import { reportItems, reportStatusItems } from '@/constant'
import { resolveStatusVariant } from '@core/utils/DpzHelper'

const today = new Date()
let tomorrow = new Date(today)
tomorrow.setDate(today.getDate()+1)
tomorrow.toLocaleDateString()

const year = tomorrow.getUTCFullYear()
const month = tomorrow.getMonth() + 1
const date = tomorrow.getDate()
</script>

<template>
  <div>
    <VCard class="mb-6">
      <VCardText>Home > Reports</VCardText>
    </VCard>
    <VCard title="Report">
      <VCardText>
        <VRow class="mb-6">
          <VCol
            cols="12"
            sm="6"
            md="3"
            lg="2"
          >
            <AppSelect
              v-model="report"
              :items="reportItems"
              item-title="value"
              item-value="key"
            />
          </VCol>
          <VCol
            cols="12"
            sm="6"
            md="3"
            lg="2"
          >
            <AppSelect
              v-model="status"
              :items="reportStatusItems"
              item-title="value"
              item-value="key"
            />
          </VCol>
          <VCol
            cols="12"
            sm="6"
            md="3"
            lg="2"
          >
            <AppDateTimePicker
              v-model="dateRange"
              placeholder="Select Date"
              prepend-inner-icon="tabler-calendar"
              :config="{ mode: 'range', disable:[{from:`${year}-${month}-${date}`, to:'2100--1-1'}] }"
            />
          </VCol>
          <VCol
            cols="12"
            sm="6"
            md="3"
            lg="2"
            class="d-flex gap-4"
          >
            <VBtn
              type="button"
              @click="generateReport"
            >
              Generate
            </VBtn>
          </VCol>
        </VRow>
        
        <VTable>
          <thead>
            <tr>
              <th class="pt-0">
                Batch#
              </th>
              <th class="pt-0">
                Report Type
              </th>
              <th class="pt-0">
                Date
              </th>
              <th class="pt-0">
                Status
              </th>
              <th class="pt-0">
                No. of Record
              </th>
              <th class="pt-0">
                Expire At
              </th>
              <th class="pt-0">
                Report Date
              </th>
              <th class="pt-0">
                Action
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in resData.data"
              :key="item.transaction_id"
            >
              <td>
                <span class="font-weight-bold d-block cursor-pointer mb-1">{{ item.report_id }}</span>
              </td>
              <td>
                <span class="d-block">{{ item.report_type }}</span>
              </td>
              <td>
                <span class="d-block">From: {{ item.filter_start_date }}</span>
                <span class="d-block">To: {{ item.filter_end_date }}</span>
              </td>
              <td>
                <VChip
                  :color="resolveStatusVariant(item.report_status_f)"
                  density="comfortable"
                  class="font-weight-medium"
                  size="small"
                >
                  {{ item.report_status_f }}
                </VChip>
              </td>
              <td>
                <span class="d-block font-weight-bold mb-1">{{ item.record ? item.record : '-' }}</span>
              </td>
              <td>
                <span class="d-block font-weight-bold mb-1">{{ item.expiry_date_f ? item.expiry_date_f : '-' }}</span>
              </td>
              <td>
                <span class="d-block font-weight-bold mb-1">{{ item.report_date }}</span>
              </td>
              <td>
                <VBtn 
                  v-if="item.report_status_f=='Success'"
                  @click="downloadReport(item.report_id)"
                >
                  Download
                </VBtn>
              </td>
            </tr>
            <tr
              v-if="!resData.data"
              class="text-center"
            >
              <td colspan="8">
                No data available
              </td>
            </tr>
          </tbody>
        </VTable>
        <VCardText
          v-if="resData.data"
          class="d-flex justify-end w-100"
        >
          <div class="d-flex">
            <div class="my-auto mx-5">
              {{ showStart + 1 }} - {{ showStart + resData.current_item_count }} of {{ resData.total_item }}
            </div>
            <button
              type="button"
              aria-label="First page"
              @click="goToLastPage(-1)"
            >
              <IconBtn icon="tabler-chevrons-left" />
            </button>
            <button
              type="button"
              aria-label="Previous page"
              @click="goToNextPage(-1)"
            >
              <IconBtn icon="tabler-chevron-left" />
            </button>
            <button
              type="button"
              aria-label="Next page"
              @click="goToNextPage(1)"
            >
              <IconBtn icon="tabler-chevron-right" />
            </button>
            <button
              type="button"
              aria-label="Last page"
              @click="goToLastPage(1)"
            >
              <IconBtn icon="tabler-chevrons-right" />
            </button>
          </div>
        </VCardText>
      </VCardText>
    </VCard>
  </div>
</template>

<script>
import axios from '@axios'
import moment from 'moment'
import { useToast } from 'vue-toast-notification'
import 'vue-toast-notification/dist/theme-sugar.css'

export default {
  data() {
    return {
      filter_data: {
        type: null,
        status: null,
        start_date: null,
        end_date: null,
      },
      page_no: 1,
      limit: 50,
      resData: {},
      showStart: 0,
      
      dateRange: [],
      status: "All",
      report: "PAYIN",
    }
  },
  mounted(){
    this.getData({
      filter_data: {
        type: null,
        status: null,
        start_date: moment().subtract(7, 'd').format('YYYY-MM-DD'),
        end_date: moment().format('YYYY-MM-DD'),
      },
      page_no: this.page_no,
      limit: this.limit,
    })
  },
  methods: {
    getData(postData){
      axios.post('/api/report/get', postData)
        .then(res=>{
          this.resData = res.data.data
          this.showStart = (this.resData.current_page-1) * this.resData.current_item_count
        })
        .catch(e=>{
          this.resData = {}
        })
    },
    generateReport(){
      const $toast = useToast()
      let filter_data = { 
        ...this.filter_data, 
        type: this.report,
        status: this.status,
      }
      if(this.dateRange.length > 0){
        const dataVals = this.dateRange?.split('to')

        filter_data.start_date = dataVals[0]
        filter_data.end_date = dataVals[1]
      }

      axios.post('/api/report/add', {
        report_type: this.report,
        status: this.status,
        start_date: filter_data.start_date,
        end_date: filter_data.end_date,
      })
        .then(res=>{
          this.getData({
            filter_data,
            page_no: this.page_no,
            limit: data.limit,
          })
        })
        .catch(error=>{
          $toast.error(error.response.data.message)
        })
    },
    downloadReport(report_id) {
      const $toast = useToast()

      axios.post('/api/report/download', {
        report_id: report_id,
      })
        .then(res=>{
          window.open(res.data.data.report_url)
        })
        .catch(error=>{
          $toast.error(error.response.data.message)
        })
    },

    goToLastPage(flag) {
      if(flag == 1){
        if(this.resData.last_page == this.resData.current_page) return
        this.getData({
          filter_data: this.filter_data,
          page_no: this.resData.last_page,
          limit: this.limit,
        })
      } else {
        if(this.resData.current_page == 1) return
        this.getData({
          filter_data: this.filter_data,
          page_no: 1,
          limit: this.limit,
        })
      }
    },
    goToNextPage(flag) {
      if(this.resData.last_page == this.resData.current_page || this.resData.current_page == 1) return

      let page_no = 1
      if(flag == 1){
        page_no = this.resData.current_page + 1
      } else {
        page_no = this.resData.current_page - 1
      }
      this.getData({
        filter_data: this.filter_data,
        page_no,
        limit: this.limit,
      })
    },
  },
}
</script>

