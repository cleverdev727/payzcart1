<template>
  <div>
    <VCard class="mb-6">
      <VCardText>Home > Refunds</VCardText>
    </VCard>
    <VCard title="Refunds">
      <VCardText>
        <RefundActionbar
          :apply-action="filterApply"
          :clear-action="clearApplied"
          class="mb-6"
        />

        <VTable>
          <thead>
            <tr>
              <th>Date</th>
              <th>Refund#</th>
              <th>TXN#</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Type</th>
              <th>Bank Reference Id</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in resData.data"
              :key="item.transaction_id"
            >
              <td>
                <span class="d-block font-weight-bold mb-1">{{ item.refund_date_ind }}</span>
              </td>
              <td>
                <span class="font-weight-bold d-block mb-1">{{ item.refund_id }}</span>
              </td>
              <td>
                <span class="font-weight-bold d-block mb-1">{{ item.transaction_id }}</span>
              </td>
              <td>
                <VChip
                  :color="resolveStatusVariant(item.refund_status)"
                  density="comfortable"
                  class="font-weight-medium"
                  size="small"
                >
                  {{ item.refund_status }}
                </VChip>
              </td>
              <td>
                <span class="d-block font-weight-bold mb-1">₹{{ item.refund_amount }}</span>
              </td>
              <td>
                <span class="d-block font-weight-bold mb-1">{{ item.refund_type ? item.refund_type : '-' }}</span>
              </td>
              <td>
                <span class="d-block font-weight-bold mb-1">{{ item.bank_rrn ? item.bank_rrn : '-' }}</span>
              </td>
              <td>
                <VBtn 
                  v-if="item.is_webhook_call>0 && (item.payment_status==='Success' || item.payment_status==='Failed')"
                  @click="resendWebhook(item.transaction_id)"
                >
                  Resend Webhook
                </VBtn>
              </td>
            </tr>
            <tr
              v-if="!resData.data"
              class="text-center"
            >
              <td colspan="9">
                No data available
              </td>
            </tr>
          </tbody>
        </VTable>
      </VCardText>
    </VCard>
  </div>
</template>

<script setup>
import { resolveStatusVariant } from '@core/utils/DpzHelper'
</script>

<script>
import axios from '@axios'
import { useToast } from 'vue-toast-notification'
import 'vue-toast-notification/dist/theme-sugar.css'

export default {
  data() {
    return {
      filter_data: {
        refund_id: null,
        transaction_id: null,
        refund_amount: null,
        status: null,
        start_date: null,
        end_date: null,
      },
      page_no: 1,
      limit: 50,
      resData: {},
      showStart: 0,
    }
  },
  mounted(){
    this.getData({
      filter_data: this.filter_data,
      page_no: this.page_no,
      limit: this.limit,
    })
  },
  methods: {
    getData(postData){
      axios.post('/api/refund', postData)
        .then(res=>{
          this.resData = res.data
          this.showStart = (this.resData.current_page-1) * this.resData.current_item_count
        })
        .catch(e=>{
          this.resData = {}
        })
    },
    resendWebhook(tid){
      const $toast = useToast()

      axios.post("/api/refund/resend/webhook", {
        transaction_id: tid,
      })
        .then(response => {
          $toast.success(response.data.message)
        })
        .catch(error => {
          $toast.error(error.response.data.message)
        })
    },
    filterApply(data){
      let filter_data = { 
        ...this.filter_data, 
        [data.filterKey]: data.filterVal,
        status: data.status,
      }
      if(data.dateRange.length > 0){
        const dataVals = data.dateRange?.split('to')

        filter_data.start_date = dataVals[0]
        filter_data.end_date = dataVals[1]
      }

      const postData = {
        filter_data,
        page_no: this.page_no,
        limit: data.limit,
      }

      this.getData(postData)
    },
    clearApplied(){
      this.getData({
        filter_data: this.filter_data,
        page_no: this.page_no,
        limit: this.limit,
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
