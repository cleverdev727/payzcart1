<template>
  <div>
    <VCard class="mb-6">
      <VCardText>Home > Payin</VCardText>
    </VCard>
    <VCard title="Payin">
      <VCardText>
        <PayActionbar
          :apply-action="filterApply"
          :clear-action="clearApplied"
          type="payin"
        />
        <PayStatus :summary="resData?.summary??{}" />

        <VTable>
          <thead>
            <tr>
              <th class="text-center">
                @
              </th>
              <th>Transactions</th>
              <th>Customer</th>
              <th>Status</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Bank Response</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in resData.data"
              :key="item.transaction_id"
            >
              <td>
                <VMenu location="bottom">
                  <template #activator="{ props }">
                    <IconBtn
                      icon="tabler-align-justified"
                      v-bind="props"
                    />
                  </template>

                  <VList>
                    <VListItem
                      :title="item.transaction_id"
                      class="mx-0"
                    />
                    <VListItem
                      v-if="item.is_webhook_call>0 && (item.payment_status==='Success' || item.payment_status==='Failed' || item.payment_status==='Full Refund' || item.payment_status==='Partial Refund')"
                      title="Resend Webhook"
                      class="mx-0"
                      @click="resendWebhook(item.transaction_id)"
                    />
                    <VListItem
                      v-if="item.payment_status==='Failed' || item.payment_status==='Pending'"
                      title="View Bank Status"
                      class="mx-0"
                      @click="viewBankStatus(item.transaction_id)"
                    />
                    <VListItem
                      v-if="!(item.is_webhook_call>0 && (item.payment_status==='Success' || item.payment_status==='Failed' || item.payment_status==='Full Refund' || item.payment_status==='Partial Refund')) && !(item.payment_status==='Failed' || item.payment_status==='Pending')"
                      title="There is no action allowed"
                      class="mx-0"
                    />
                  </VList>
                </VMenu>
              </td>
              <td>
                <span class="d-block font-weight-bold mb-1">TXN#: {{ item.transaction_id }}</span>
                <span class="d-block font-weight-bold mb-1">ORD#: {{ item.merchant_order_id }}</span>
                <span class="d-block font-weight-bold mb-1">UTR#: {{ item.bank_rrn ? item.bank_rrn : '-' }}</span>
                <span class="d-block mb-1 font-weight-bold ">DATE: {{ item.transaction_date_ind }}</span>
                <span class="d-block mb-1 font-weight-bold ">PG: {{ item.pg_name ?item.pg_name :'-' }}</span>
              </td>
              <td>
                <span class="d-block font-weight-bold mb-1">Cust#: {{ item.customer_id }}</span>
                <span class="d-block font-weight-bold mb-1">Email: {{ item.customer_email ? item.customer_email : "-" }}</span>
                <span class="d-block font-weight-bold mb-1">Phone: {{ item.customer_mobile ? item.customer_mobile : "-" }}</span>
              </td>
              <td>
                <VChip
                  :color="resolveStatusVariant(item.payment_status)"
                  density="comfortable"
                  class="font-weight-medium"
                  size="small"
                >
                  {{ item.payment_status }}
                </VChip>
              </td>
              <td>
                <span class="d-block font-weight-bold mb-1">Payment: ₹{{ item.payment_amount }}</span>
                <span class="d-block font-weight-bold mb-1">PG Fees: -₹{{ item.pg_fees }}</span>
                <span class="d-block font-weight-bold mb-1">Assoc Fees: .-₹{{ item.associate_fees }}</span>
                <span class="d-block font-weight-bold mb-1">Settled: .-₹{{ item.payable_amount }}</span>
              </td>
              <td>
                <span class="d-block font-weight-bold mb-1">{{ item.payment_method ? item.payment_method : '-' }}</span>
              </td>
              <td>
                <span class="d-block font-weight-bold mb-1">{{ item.pg_res_msg ? item.pg_res_msg : '-' }}</span>
              </td>
            </tr>
            <tr
              v-if="!resData.data"
              class="text-center"
            >
              <td colspan="7">
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
        transaction_id: null,
        order_id: null,
        customer_email: null,
        customer_mobile: null,
        payment_amount: null,
        status: 'All',
        bank_rrn: null,
        udf1: null,
        udf2: null,
        udf3: null,
        udf4: null,
        udf5: null,
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
      axios.post('/api/payin', postData)
        .then(res=>{
          this.resData = res.data
          this.showStart = (this.resData.current_page-1) * this.limit
        })
        .catch(e=>{
          this.resData = {}
        })
    },
    resendWebhook(tid){
      const $toast = useToast()

      axios.post("/api/payin/resend/webhook", {
        transaction_id: tid,
      })
        .then(response => {
          $toast.success(response.data.message)
          this.getData({
            filter_data: this.filter_data,
            page_no: this.resData.current_page,
            limit: this.limit,
          })
        })
        .catch(error => {
          $toast.error(error.response.data.message)
        })
    },
    viewBankStatus(tid){
      const $toast = useToast()

      axios.post("/api/view/payin/status", {
        transaction_id: tid,
      })
        .then(response => {
          console.log(response.data)
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
        this.page_no = 1
        this.getData({
          filter_data: this.filter_data,
          page_no: 1,
          limit: this.limit,
        })
      }
    },
    goToNextPage(flag) {
      let page_no = 1
      if(flag == 1){
        if (this.resData.last_page == this.resData.current_page) return
        page_no = this.resData.current_page + 1
      } else {
        if (this.resData.current_page == 1) return
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
