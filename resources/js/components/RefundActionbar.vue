<script setup>
const props=defineProps({
  applyAction: {
    type: Function,
    required: true,
  },
  clearAction: {
    type: Function,
    required: true,
  },
})

const filterItems = [
  { key: 'transaction_id', value: 'Transaction Id' },
  { key: 'refund_id', value: 'Refund Id' },
  { key: 'refund_amount', value: 'Amount' },
]

const limitItems = [
  { key: '10', value: '10' },
  { key: '20', value: '20' },
  { key: '30', value: '30' },
  { key: '40', value: '40' },
  { key: '50', value: '50' },
]

const statusItems = [
  { key: 'All', value: 'All' },
  { key: 'Success', value: 'Success' },
  { key: 'Failed', value: 'Failed' },
  { key: 'Pending', value: 'Pending' },
  { key: 'Processing', value: 'Processing' },
]


const today = new Date()
let tomorrow = new Date(today)
tomorrow.setDate(today.getDate()+1)
tomorrow.toLocaleDateString()

const year = tomorrow.getUTCFullYear()
const month = tomorrow.getMonth() + 1
const date = tomorrow.getDate()
</script>

<template>
  <VRow>
    <VCol
      cols="12"
      sm="6"
      md="6"
      lg="4"
    >
      <div class="d-flex">
        <AppSelect
          v-model="filterKey"
          :items="filterItems"
          item-title="value"
          class="w-50"
          item-value="key"
        />
        <AppTextField
          v-model="filterVal"
          type="text"
          class="w-50"
          placeholder="Enter Search Value"
        />
      </div>
    </VCol>
    <VCol
      cols="12"
      sm="6"
      md="3"
      lg="2"
    >
      <AppSelect
        v-model="status"
        :items="statusItems"
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
    >
      <AppSelect
        v-model="limit"
        :items="limitItems"
        item-title="value"
        item-value="key"
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
        @click="props.applyAction({filterKey, filterVal, status, limit, dateRange})"
      >
        Apply
      </VBtn>
      <VBtn
        type="reset"
        variant="tonal"
        @click="props.clearAction()"
      >
        Clear
      </VBtn>
    </VCol>
  </VRow>
</template>

<script>
export default {
  data() {
    return {
      filterKey: "transaction_id",
      filterVal: "",
      status: "All",
      limit: 10,
      dateRange: [],
    }
  },
}
</script>
