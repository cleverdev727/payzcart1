<script setup>
const props = defineProps({
  summary: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <VRow class="mb-6">
    <VCol
      cols="12"
      sm="6"
      md="3"
    >
      <VCard>
        <VCardText>
          <div class="pb-2">
            Total Transaction
          </div>
          <div class="pb-2">
            {{ props.summary?.total_txn }}
          </div>
        </VCardText>
      </VCard>        
    </VCol>
    <VCol
      cols="12"
      sm="6"
      md="3"
    >
      <VCard>
        <VCardText>
          <div class="pb-2">
            Total Payment Amount
          </div>
          <div class="pb-2">
            ₹ {{ props.summary?.total_payment_amount }}
          </div>
        </VCardText>
      </VCard>        
    </VCol>
    <VCol
      cols="12"
      sm="6"
      md="3"
    >
      <VCard>
        <VCardText>
          <div class="pb-2">
            Total Payable Amount
          </div>
          <div class="pb-2">
            ₹ {{ props.summary?.total_payable_amount }}
          </div>
        </VCardText>
      </VCard>        
    </VCol>
    <VCol
      cols="12"
      sm="6"
      md="3"
    >
      <VCard>
        <VCardText>
          <div class="pb-2">
            Total Fees
          </div>
          <div class="pb-2">
            ₹ {{ props.summary?.total_pg_fees }}
          </div>
        </VCardText>
      </VCard>        
    </VCol>
  </VRow>
</template>
