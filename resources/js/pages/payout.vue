<template>
  <div>
    <VCard class="mb-6">
      <VCardText>Home > Payout</VCardText>
    </VCard>
    <VCard title="Payout">
      <VCardText>
        <PayActionbar
          :apply-action="filterApply"
          :clear-action="clearApplied"
          type="payout"
        />
        <PayoutStatus :summary="resData?.summary??{}" />

        <VTable>
          <thead>
            <tr>
              <th class="text-center">
                @
              </th>
              <th>Payout#</th>
              <th>Customer</th>
              <th>Status</th>
              <th>Amount</th>
              <th>Type</th>
              <th>Bank Response</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in resData.data"
              :key="item.payout_id"
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
                      :title="item.payout_id"
                      class="mx-0"
                    />
                    <VListItem
                      v-if="item.is_webhook_called>0 && (item.payout_status==='Success' || item.payout_status==='Failed')"
                      title="Resend Webhook"
                      class="mx-0"
                      @click="resendWebhook(item.payout_id)"
                    />
                    <VListItem
                      v-if="item.payout_status==='Initialized' && !item.is_approved"
                      title="Approve Payout"
                      class="mx-0"
                      @click="approvePayout(item.payout_id)"
                    />
                    <VListItem
                      v-if="item.payout_status==='Initialized' && !item.is_approved"
                      title="Cancel Payout"
                      class="mx-0"
                      @click="cancelPayout(item.payout_id)"
                    />
                    <VListItem
                      v-if="(item.payout_status==='Initialized' && item.is_approved) || item.is_webhook_called==0"
                      title="There is no action allowed"
                      class="mx-0"
                    />
                  </VList>
                </VMenu>
              </td>
              <td>
                <span class="font-weight-bold d-block mb-1"> Payout#: {{ item.payout_id }}</span>
                <span class="font-weight-bold d-block mb-1">Order#: {{ item.ref_id }}</span>
                <span class="d-block font-weight-bold mb-1">UTR: {{ item.bank_rrn ? item.bank_rrn : '-' }}</span>
                <span class="d-block font-weight-bold mb-1"> Date: {{ item.payout_date_ind }} </span>
              </td>
              <td>
                <span class="font-weight-bold d-block mb-1"> Name: {{ item.account_holder_name }}</span>
                <span class="font-weight-bold d-block mb-1"> A/C: {{ item.bank_account ? item.bank_account : '-' }}</span>
                <span class="font-weight-bold d-block mb-1"> IFSC: {{ item.ifsc_code ? item.ifsc_code : '-' }}</span>
              </td>
              <td>
                <VChip
                  :color="resolveStatusVariant(item.payout_status)"
                  density="comfortable"
                  class="font-weight-medium"
                  size="small"
                >
                  {{ item.payout_status }}
                </VChip>
              </td>
              <td>
                <span class="d-block font-weight-bold mb-1">Payout: ₹{{ item.payout_amount }}</span>
                <span class="d-block font-weight-bold mb-1">Assoc. Fees: ₹ {{ item.associate_fees ? item.associate_fees : 0 }}</span>
                <span class="d-block font-weight-bold mb-1">PG Fees: ₹ {{ item.payout_fees }}</span>
                <span class="d-block font-weight-bold mb-1">Total: ₹ {{ item.total_amount ? item.total_amount : 0 }}</span>
              </td>
              <td>
                <span class="d-block font-weight-bold mb-1">{{ item.payout_type ? item.payout_type : '-' }}</span>
              </td>
              <td>
                <span class="d-block font-weight-bold mb-1">{{ item.pg_response_msg ? item.pg_response_msg : '-' }}</span>
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
          v-if="resData?.data"
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
        payout_id: null,
        ref_id: null,
        bank_rrn: null,
        account_no: null,
        ifsc_code: null,
        customer_email: null,
        mobile_no: null,
        payout_amount: null,
        status: 'All',
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
      axios.post('/api/payout', postData)
        .then(res=>{
          this.resData = res.data
          this.showStart = (this.resData.current_page-1) * this.limit
        })
        .catch(e=>{
          this.resData = {}
        })
    },
    resendWebhook(pid){
      const $toast = useToast()

      axios.post("/api/payout/resend/webhook", {
        payout_id: pid,
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
    approvePayout(pid) {
      const $toast = useToast()

      this.$confirm(
        {
          message: 'Are you sure approve payout request',
          button: {
            no: 'No',
            yes: 'Yes',
          },
          callback: confirm => {
            if (confirm) {
              axios.post("/api/payout/request/approve", {
                payout_id: pid,
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
            }
          },
        },
      )
    },
    cancelPayout(pid) {
      const $toast = useToast()

      this.$confirm(
        {
          message: 'Are you sure cancel payout request?',
          button: {
            no: 'No',
            yes: 'Yes',
          },
          callback: confirm => {
            if (confirm) {
              axios.post("/api/payout/request/cancel", {
                payout_id: pid,
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
            }
          },
        },
      )
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
